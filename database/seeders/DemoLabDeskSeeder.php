<?php

namespace Database\Seeders;

use App\Models\FacilityRoleAssignment;
use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\LabTestRequest;
use App\Models\Patient;
use App\Models\User;
use App\Services\Portal\FacilityMembershipService;
use Database\Seeders\Support\DemoCareTeam;
use Database\Seeders\Support\DemoFacilityResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Demo work for the LAB DESK — what demo.labtech@opescare.test sees the moment
 * they open the clinician app.
 *
 * Everything written here is `is_demo = true` and carries a `demo_seed_key`,
 * so a re-run finds its own rows and writes nothing twice. A row the reviewer
 * has already worked (collected, resulted, released) is left exactly as they
 * left it: lab orders are immutable clinical events and this seeder never
 * walks one back.
 *
 * The orders live at the facility the lab tech's session will START at —
 * resolved the way the provider login resolves it (FacilityMembershipService::
 * defaultFacilityIdFor), so the worklist is never seeded into a hospital the
 * token is not scoped to. If the lab tech is not posted there, they are.
 *
 * What it seeds, for that facility:
 *   • demo.reviewer — a routine Full Blood Count, ORDERED, awaiting a sample;
 *   • demo.reviewer — an URGENT Serum Potassium, COLLECTED, awaiting results;
 *   • demo.girl     — a Full Blood Count IN PROGRESS with two of its analytes
 *                     entered (one flagged low), the rest still to come;
 *   • demo.patient  — a PENDING request for a Fasting Blood Glucose.
 *
 * Ordered by demo.doctor (Dr. Amara Diallo), so a released panel lands in
 * their results inbox, as it would in life.
 */
class DemoLabDeskSeeder extends Seeder
{
    /** Prefix for every demo_seed_key this seeder owns. */
    public const KEY = 'demo_lab_desk_v1';

    public const REVIEWER_EMAIL = 'demo.reviewer@opescare.test';
    public const GIRL_EMAIL     = 'demo.girl@opescare.test';
    public const PATIENT_EMAIL  = 'demo.patient@opescare.test';

    public function run(): void
    {
        DemoFacilityResolver::flush();
        DemoCareTeam::flush();

        $labTech = $this->labTech();

        if ($labTech === null) {
            $this->command?->warn('DemoLabDeskSeeder: demo.labtech is not on this estate — nothing seeded.');

            return;
        }

        $facilityId = $this->deskFacility($labTech);

        if ($facilityId === null) {
            $this->command?->warn('DemoLabDeskSeeder: no facility to post the lab tech at — nothing seeded.');

            return;
        }

        $this->ensurePosted($labTech, $facilityId);

        $doctorId = DemoCareTeam::doctor() ?? (string) $labTech->id;

        $reviewer = $this->demoPatient(self::REVIEWER_EMAIL);
        $girl     = $this->demoPatient(self::GIRL_EMAIL);
        $patient  = $this->demoPatient(self::PATIENT_EMAIL);

        $seeded = [];

        if ($reviewer !== null) {
            $seeded[] = $this->order($reviewer, $facilityId, $doctorId, 'reviewer:fbc', [
                'test_name'           => 'Full Blood Count',
                'test_code'           => 'FBC',
                'urgency'             => 'routine',
                'status'              => 'ordered',
                'clinical_indication' => 'Fatigue and pallor for two weeks — anaemia screen.',
                'ordered_at'          => now()->subHours(3),
            ]);

            $seeded[] = $this->order($reviewer, $facilityId, $doctorId, 'reviewer:potassium', [
                'test_name'           => 'Serum Potassium',
                'test_code'           => 'K',
                'urgency'             => 'urgent',
                'status'              => 'collected',
                'clinical_indication' => 'On an ACE inhibitor; check potassium before the next dose.',
                'ordered_at'          => now()->subHours(2),
                'collected_at'        => now()->subMinutes(40),
            ]);
        } else {
            $this->command?->warn('DemoLabDeskSeeder: ' . self::REVIEWER_EMAIL . ' not found — their two orders skipped.');
        }

        if ($girl !== null) {
            $order = $this->order($girl, $facilityId, $doctorId, 'girl:fbc', [
                'test_name'           => 'Full Blood Count',
                'test_code'           => 'FBC',
                'urgency'             => 'routine',
                'status'              => 'in_progress',
                'clinical_indication' => 'Recurrent fever; rule out anaemia and infection.',
                'ordered_at'          => now()->subHours(5),
                'collected_at'        => now()->subHours(4),
            ]);
            $seeded[] = $order;

            $this->result($order, (string) $labTech->id, 'girl:fbc:hgb', [
                'parameter_name'  => 'Haemoglobin',
                'value'           => '10.8',
                'unit'            => 'g/dL',
                'reference_range' => '11.5–15.5',
                'flag'            => 'L',
                'resulted_at'     => now()->subMinutes(25),
            ]);

            $this->result($order, (string) $labTech->id, 'girl:fbc:wbc', [
                'parameter_name'  => 'White Cell Count',
                'value'           => '7.9',
                'unit'            => '10^9/L',
                'reference_range' => '4.5–13.5',
                'flag'            => 'N',
                'resulted_at'     => now()->subMinutes(20),
            ]);
        } else {
            $this->command?->warn('DemoLabDeskSeeder: ' . self::GIRL_EMAIL . ' not found — the in-progress order skipped.');
        }

        if ($patient !== null) {
            $this->request($patient, $facilityId, 'patient:glucose', [
                'test_code'      => 'GLUF',
                'test_name'      => 'Fasting Blood Glucose',
                'priority'       => 'routine',
                'clinical_notes' => 'My doctor asked me to repeat my fasting sugar before the next visit.',
                'submitted_at'   => now()->subHours(6),
            ]);
        } else {
            $this->command?->warn('DemoLabDeskSeeder: ' . self::PATIENT_EMAIL . ' not found — the pending request skipped.');
        }

        $this->command?->info(sprintf(
            'DemoLabDeskSeeder: %d order(s), %d result row(s), %d request(s) at facility %s.',
            LabOrder::query()->where('demo_seed_key', 'like', self::KEY . ':%')->count(),
            LabResult::query()->where('demo_seed_key', 'like', self::KEY . ':%')->count(),
            LabTestRequest::query()->where('demo_seed_key', 'like', self::KEY . ':%')->count(),
            $facilityId
        ));
    }

    // ── Anchors ──────────────────────────────────────────────────────────────

    private function labTech(): ?User
    {
        $id = DemoCareTeam::labTech();

        if ($id === null) {
            // The care team seeder creates the lab tech when the estate has the
            // primary hospital; on an estate without it this stays null and we
            // say so rather than inventing an account.
            DemoCareTeam::ensure($this->command);
            DemoCareTeam::flush();
            $id = DemoCareTeam::labTech();
        }

        return $id ? User::query()->withoutGlobalScopes()->find($id) : null;
    }

    /**
     * Where the lab tech's session starts — the same resolution the provider
     * login uses — falling back to the story's primary hospital.
     */
    private function deskFacility(User $labTech): ?string
    {
        $service = app(FacilityMembershipService::class);
        $service->forget($labTech);

        return $service->defaultFacilityIdFor($labTech) ?? DemoFacilityResolver::primaryHospital();
    }

    /**
     * An active lab posting at the desk facility, so the capability gate lets
     * the tech onto the desk there. `facility_role_assignments` carries no
     * is_demo column; the user it names is a demo account and that is the
     * marker. Only ever touches a demo user.
     */
    private function ensurePosted(User $labTech, string $facilityId): void
    {
        if (! $labTech->is_demo || ! Schema::hasTable('facility_role_assignments')) {
            return;
        }

        $labRoleIds = DB::table('roles')->whereIn('name', ['labtech', 'lab_scientist', 'lab_manager', 'lab_validator'])->pluck('id', 'name');

        $posted = FacilityRoleAssignment::query()
            ->where('user_id', $labTech->id)
            ->where('facility_id', $facilityId)
            ->active()
            ->whereIn('role_id', $labRoleIds->values()->all())
            ->exists();

        if (! $posted) {
            $roleId = $labRoleIds->get('labtech') ?? $labRoleIds->first();

            if ($roleId === null) {
                $this->command?->warn('DemoLabDeskSeeder: no lab role seeded — the lab tech could not be posted.');

                return;
            }

            FacilityRoleAssignment::updateOrCreate(
                ['user_id' => $labTech->id, 'facility_id' => $facilityId, 'role_id' => (string) $roleId],
                ['is_active' => true, 'expires_at' => null, 'assigned_at' => now()]
            );

            $this->command?->info("DemoLabDeskSeeder: posted demo.labtech at {$facilityId}.");
        }

        if (blank($labTech->primary_facility_id)) {
            DB::table('users')->where('id', $labTech->id)->update(['primary_facility_id' => $facilityId, 'updated_at' => now()]);
        }

        app(FacilityMembershipService::class)->forget($labTech);
    }

    private function demoPatient(string $email): ?Patient
    {
        return Patient::query()
            ->withoutGlobalScopes()
            ->where('email', $email)
            ->where('is_demo', true)
            ->first();
    }

    // ── Rows ─────────────────────────────────────────────────────────────────

    /**
     * One order, once. An existing row is returned untouched — whatever state
     * the reviewer has since worked it into stands.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function order(Patient $patient, string $facilityId, string $orderedBy, string $key, array $attributes): LabOrder
    {
        $seedKey = self::KEY . ':' . $key;

        $existing = LabOrder::query()->where('demo_seed_key', $seedKey)->first();
        if ($existing !== null) {
            return $existing;
        }

        return LabOrder::create($attributes + [
            'patient_id'    => $patient->id,
            'facility_id'   => $facilityId,
            'visit_id'      => null,
            'ordered_by'    => $orderedBy,
            'notes'         => null,
            'collected_at'  => null,
            'resulted_at'   => null,
            'is_demo'       => true,
            'demo_seed_key' => $seedKey,
        ]);
    }

    /**
     * One analyte, once, written quietly: a seeder must not trip the
     * result-created listeners (family notifications) — same rule as
     * DemoLabResultsSeeder.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function result(LabOrder $order, string $verifiedBy, string $key, array $attributes): void
    {
        $seedKey = self::KEY . ':' . $key;

        if (LabResult::query()->where('demo_seed_key', $seedKey)->exists()) {
            return;
        }

        LabResult::withoutEvents(fn () => LabResult::create($attributes + [
            'lab_order_id'  => $order->id,
            'patient_id'    => $order->patient_id,
            'notes'         => null,
            'verified_by'   => $verifiedBy,
            'loinc_code'    => null,
            'loinc_display' => null,
            'is_demo'       => true,
            'demo_seed_key' => $seedKey,
        ]));
    }

    /**
     * One pending request, once. A request the reviewer has since approved or
     * declined stays decided.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function request(Patient $patient, string $facilityId, string $key, array $attributes): void
    {
        $seedKey = self::KEY . ':' . $key;

        if (LabTestRequest::query()->where('demo_seed_key', $seedKey)->exists()) {
            return;
        }

        LabTestRequest::create($attributes + [
            'patient_id'    => $patient->id,
            'facility_id'   => $facilityId,
            'loinc_code'    => null,
            'status'        => 'submitted',
            'is_demo'       => true,
            'demo_seed_key' => $seedKey,
        ]);
    }
}
