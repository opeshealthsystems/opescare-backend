<?php

namespace Tests\Feature\Mobile;

use App\Models\Appointment;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\Portal\ProviderSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * One facility with a front desk and a care team, one other facility with its
 * own doctor, one patient with a known Health ID — the fixture every desk and
 * follow-up test starts from. Same provider-session recipe as
 * ProviderScanTest / ProviderRoleMatrixTest.
 */
abstract class FrontDeskTestCase extends TestCase
{
    use RefreshDatabase;

    protected const HEALTH_ID = 'CM-HID-TEST-DESK-0001';

    /** Roles that get a provider session at $this->facility. */
    protected const ROLES = ['receptionist', 'doctor', 'nurse', 'specialist', 'pharmacist', 'labtech', 'cashier'];

    /** Of those, the ones that are posted here with an active facility_role_assignments row. */
    protected const POSTED = ['receptionist', 'doctor', 'nurse', 'specialist', 'pharmacist', 'labtech', 'cashier'];

    protected Facility $facility;

    protected Facility $otherFacility;

    protected Patient $patient;

    /** A doctor posted at the OTHER facility only. */
    protected User $otherDoctor;

    /** @var array<string, User> */
    protected array $users = [];

    /** @var array<string, string> */
    protected array $bearer = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility      = Facility::factory()->create(['is_demo' => (bool) config('demo.enabled')]);
        $this->otherFacility = Facility::factory()->create(['is_demo' => (bool) config('demo.enabled')]);

        foreach (self::ROLES as $role) {
            $this->users[$role]  = $this->professional($role);
            $this->bearer[$role] = app(ProviderSessionService::class)->mint($this->users[$role], $this->facility->id, 'desk-test');

            if (in_array($role, self::POSTED, true)) {
                $this->postAt($this->users[$role], $this->facility, $role);
            }
        }

        $this->otherDoctor = $this->professional('doctor');
        $this->postAt($this->otherDoctor, $this->otherFacility, 'doctor');

        $this->patient = Patient::factory()->create([
            'health_id'  => self::HEALTH_ID,
            'first_name' => 'Amara',
            'last_name'  => 'Ndi',
            'is_demo'    => (bool) config('demo.enabled'),
        ]);
    }

    /**
     * A user whose role passes isProfessional(): a dashboard profile outside
     * the excluded set. Same recipe as ProviderScanTest.
     */
    protected function professional(string $role): User
    {
        DB::table('dashboard_profiles')->updateOrInsert(['key' => $role], [
            'name' => ucfirst($role), 'portal_prefix' => 'staff', 'landing_route' => 'staff.dashboard',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $model = Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role)]);
        $model->forceFill(['dashboard_profile_key' => $role])->save(); // not mass-assignable

        return User::factory()->create([
            'is_demo' => (bool) config('demo.enabled'),
            'role_id' => $model->id,
        ]);
    }

    /** An active posting: what makes a clinician routable at a facility. */
    protected function postAt(User $user, Facility $facility, string $role, bool $active = true): FacilityRoleAssignment
    {
        return FacilityRoleAssignment::create([
            'user_id'     => $user->id,
            'facility_id' => $facility->id,
            'role_id'     => Role::where('name', $role)->value('id'),
            'is_active'   => $active,
            'assigned_at' => now(),
        ]);
    }

    protected function asRole(string $role): static
    {
        return $this->withHeaders(['Authorization' => "Bearer {$this->bearer[$role]}"]);
    }

    protected function hit(string $role, string $method, string $path, array $body = []): TestResponse
    {
        return $this->asRole($role)->json($method, $path, $body);
    }

    /** An appointment on $this->facility's book (override anything). */
    protected function appointment(array $attributes = []): Appointment
    {
        return Appointment::factory()->create(array_merge([
            'patient_id'       => $this->patient->id,
            'facility_id'      => $this->facility->id,
            'provider_id'      => $this->users['doctor']->id,
            'appointment_type' => 'consultation',
            'status'           => 'scheduled',
            'scheduled_at'     => $this->today(10, 0),
            'booked_by_type'   => 'staff',
        ], $attributes));
    }

    protected function today(int $hour, int $minute = 0): Carbon
    {
        return Carbon::now(config('app.timezone'))->startOfDay()->setTime($hour, $minute);
    }

    protected function tomorrow(int $hour, int $minute = 0): Carbon
    {
        return Carbon::now(config('app.timezone'))->addDay()->startOfDay()->setTime($hour, $minute);
    }
}
