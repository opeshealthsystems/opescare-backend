<?php

namespace Tests\Feature\Scale;

use App\Models\Appointment;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\LabResult;
use App\Models\Patient;
use App\Models\PatientAccessToken;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Role;
use App\Models\User;
use App\Services\Portal\ProviderSessionService;
use App\Services\Queue\PatientQueueService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Query budgets for the pages a clinic opens all day: the patient chart, the
 * queue desk, the staff dashboard, the patient portal home, and the mobile
 * doctor/patient home + records endpoints.
 *
 * Each test measures the page twice — once with a little data, once with
 * several times more — and asserts (a) the count does not grow with the data
 * (no N+1) and (b) an absolute cap, so a new widget that adds twenty queries
 * is a visible decision rather than a silent drift. Caps are the measured
 * count on 2026-10-19 plus headroom; raise them deliberately, never to make a
 * red test green without looking at what was added.
 */
class StaffAndPortalQueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    // ── Web: chart, queue desk, staff dashboard ──────────────────────────

    public function test_patient_chart_query_count_is_flat_in_the_size_of_the_record(): void
    {
        [$doctor, $facility] = $this->staff('doctor');
        $patient = $this->patient();
        $this->appointment($patient, $facility, $doctor, now()->addDay());
        $this->clinicalRows($patient, $facility, $doctor, 3);

        $url = route('portals.chart.show', $patient->id);
        $this->asStaff($doctor, $facility)->get($url)->assertOk(); // warm caches

        $few = $this->queries(fn () => $this->asStaff($doctor, $facility)->get($url)->assertOk());
        $this->clinicalRows($patient, $facility, $doctor, 12);
        $many = $this->queries(fn () => $this->asStaff($doctor, $facility)->get($url)->assertOk());

        $this->assertFlat('chart show', $few, $many, 70);
    }

    public function test_queue_desk_query_count_is_flat_in_the_number_of_tickets(): void
    {
        [$desk, $facility] = $this->staff('receptionist');
        $this->tickets($facility, $desk, 2);

        $this->asStaff($desk, $facility)->get('/facility/queue')->assertOk();
        $few = $this->queries(fn () => $this->asStaff($desk, $facility)->get('/facility/queue')->assertOk());
        $this->tickets($facility, $desk, 12);
        $many = $this->queries(fn () => $this->asStaff($desk, $facility)->get('/facility/queue')->assertOk());

        $this->assertFlat('queue desk', $few, $many, 45);
    }

    public function test_staff_dashboard_query_count_is_flat_in_the_facility_workload(): void
    {
        [$doctor, $facility] = $this->staff('doctor');
        $this->tickets($facility, $doctor, 2);
        $this->appointment($this->patient(), $facility, $doctor, now()->addMinutes(30));

        $this->asStaff($doctor, $facility)->get('/facility')->assertOk();
        $few = $this->queries(fn () => $this->asStaff($doctor, $facility)->get('/facility')->assertOk());

        $this->tickets($facility, $doctor, 10);
        for ($i = 0; $i < 10; $i++) {
            $this->appointment($this->patient(), $facility, $doctor, now()->addHours($i + 1));
        }
        $many = $this->queries(fn () => $this->asStaff($doctor, $facility)->get('/facility')->assertOk());

        $this->assertFlat('staff dashboard', $few, $many, 80);
    }

    // ── Web: patient portal home ─────────────────────────────────────────

    public function test_patient_portal_home_query_count_is_flat_in_the_size_of_the_record(): void
    {
        $patient = $this->patient();
        $role    = Role::where('name', 'patient')->firstOrFail();
        $user    = User::factory()->create(['role_id' => $role->id, 'patient_id' => $patient->id, 'status' => 'active']);
        $hit     = fn () => $this->actingAs($user)->withSession(['mfa.verified' => true])->get('/patient')->assertOk();

        [, $facility] = $this->staff('doctor');
        $this->patientRows($patient, $facility, 3);
        $hit();
        $few = $this->queries($hit);
        $this->patientRows($patient, $facility, 10);
        $many = $this->queries($hit);

        $this->assertFlat('patient portal home', $few, $many, 70);
    }

    // ── Mobile: doctor home + chart, patient home + records ──────────────

    public function test_mobile_doctor_home_and_chart_are_flat_in_the_workload(): void
    {
        [$doctor, $facility] = $this->staff('doctor');
        $bearer  = app(ProviderSessionService::class)->mint($doctor, $facility->id, 'test');
        $patient = $this->patient();
        $this->appointment($patient, $facility, $doctor, now()->addHour());
        $this->clinicalRows($patient, $facility, $doctor, 3);

        $paths = [
            '/api/mobile/doctor/home',
            "/api/mobile/doctor/patients/{$patient->id}",
            "/api/mobile/doctor/patients/{$patient->id}/encounters",
            "/api/mobile/doctor/patients/{$patient->id}/prescriptions",
        ];

        $few = $this->countEach($bearer, $paths);
        for ($i = 0; $i < 8; $i++) {
            $this->appointment($this->patient(), $facility, $doctor, now()->addHours($i + 2));
        }
        $this->clinicalRows($patient, $facility, $doctor, 8);
        $many = $this->countEach($bearer, $paths);

        foreach ($paths as $p) {
            $this->assertFlat($p, $few[$p], $many[$p], 40);
        }
    }

    public function test_mobile_patient_home_and_records_are_flat_in_the_size_of_the_record(): void
    {
        $patient = $this->patient();
        $token   = 'pat_' . Str::random(40);
        PatientAccessToken::create([
            'patient_id' => $patient->id, 'token_hash' => Hash::make($token),
            'token_prefix' => substr($token, 0, 12), 'expires_at' => now()->addDays(30),
        ]);

        [, $facility] = $this->staff('doctor');
        $this->patientRows($patient, $facility, 3);
        $paths = ['/api/mobile/me', '/api/mobile/timeline', '/api/mobile/clinical', '/api/mobile/labs', '/api/mobile/prescriptions', '/api/mobile/appointments'];

        $few = $this->countEach($token, $paths);
        $this->patientRows($patient, $facility, 10);
        $many = $this->countEach($token, $paths);

        foreach ($paths as $p) {
            $this->assertFlat($p, $few[$p], $many[$p], 25);
        }
    }

    // ── helpers ──────────────────────────────────────────────────────────

    private function assertFlat(string $what, int $few, int $many, int $cap): void
    {
        // +1 slack: a cache miss that happens once is not an N+1.
        $this->assertLessThanOrEqual($few + 1, $many, "{$what}: {$few} queries with little data, {$many} with more (N+1).");
        $this->assertLessThanOrEqual($cap, $many, "{$what}: {$many} queries exceeds the budget of {$cap}.");
    }

    private function queries(callable $fn): int
    {
        $n = 0;
        $this->app['events']->forget(QueryExecuted::class);
        DB::listen(function () use (&$n) { $n++; });
        $fn();
        $this->app['events']->forget(QueryExecuted::class);

        return $n;
    }

    private function countEach(string $bearer, array $paths): array
    {
        $out = [];
        foreach ($paths as $p) {
            $out[$p] = $this->queries(fn () => $this->withHeader('Authorization', "Bearer {$bearer}")->getJson($p)->assertOk());
        }

        return $out;
    }

    private function staff(string $roleName): array
    {
        $role     = Role::where('name', $roleName)->firstOrFail();
        $facility = Facility::factory()->create(['type' => 'hospital', 'is_demo' => (bool) config('demo.enabled')]);
        $user     = User::factory()->create([
            'role_id' => $role->id, 'primary_facility_id' => $facility->id, 'status' => 'active',
            'is_demo' => (bool) config('demo.enabled'),
        ]);
        FacilityRoleAssignment::create([
            'user_id' => $user->id, 'facility_id' => $facility->id, 'role_id' => $role->id,
            'is_active' => true, 'assigned_at' => now(),
        ]);

        return [$user, $facility];
    }

    private function asStaff(User $user, Facility $facility): static
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id, 'mfa.verified' => true]);
    }

    private function patient(): Patient
    {
        return Patient::factory()->create([
            'is_demo'             => (bool) config('demo.enabled'),
            'date_of_birth'       => '1985-04-02',
            'privacy_preferences' => ['require_consent_for_full_record' => false],
        ]);
    }

    private function appointment(Patient $p, Facility $f, User $doctor, $at): Appointment
    {
        return Appointment::create([
            'patient_id' => $p->id, 'facility_id' => $f->id, 'provider_id' => $doctor->id,
            'appointment_type' => 'consultation', 'status' => 'scheduled', 'scheduled_at' => $at,
        ]);
    }

    private function tickets(Facility $f, User $actor, int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            app(PatientQueueService::class)->checkIn([
                'patient' => $this->patient(), 'facility_id' => $f->id, 'actor_id' => $actor->id,
                'priority_level' => 5, 'priority_reason' => null,
            ]);
        }
    }

    /** Prescriptions, labs and appointments on the patient's record. */
    private function patientRows(Patient $p, Facility $f, int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            $rx = Prescription::factory()->create(['patient_id' => $p->id, 'status' => 'active']);
            PrescriptionItem::factory()->create(['prescription_id' => $rx->id]);
            Appointment::factory()->create(['patient_id' => $p->id, 'scheduled_at' => now()->addDays($i + 1)]);
            LabResult::factory()->create(['patient_id' => $p->id]);
        }
    }

    /** Record rows written at this facility (what a chart lists). */
    private function clinicalRows(Patient $p, Facility $f, User $doctor, int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            $visitId = (string) Str::uuid();
            DB::table('visits')->insert([
                'id' => $visitId, 'patient_id' => $p->id, 'facility_id' => $f->id, 'visit_type' => 'outpatient',
                'status' => 'closed', 'started_at' => now()->subDays($i + 1), 'created_at' => now(), 'updated_at' => now(),
                'is_demo' => (bool) config('demo.enabled'),
            ]);
            DB::table('encounters')->insert([
                'id' => (string) Str::uuid(), 'visit_id' => $visitId, 'patient_id' => $p->id, 'facility_id' => $f->id,
                'encounter_type' => 'consultation', 'status' => 'completed', 'started_at' => now()->subDays($i + 1),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $rx = Prescription::factory()->create(['patient_id' => $p->id, 'facility_id' => $f->id, 'status' => 'active']);
            PrescriptionItem::factory()->create(['prescription_id' => $rx->id]);
            LabResult::factory()->create(['patient_id' => $p->id]);
        }
    }
}
