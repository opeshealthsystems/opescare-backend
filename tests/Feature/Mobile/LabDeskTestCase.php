<?php

namespace Tests\Feature\Mobile;

use App\Models\Facility;
use App\Models\LabOrder;
use App\Models\LabTestRequest;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\Portal\ProviderSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Shared fixture for the lab desk tests: two facilities, one professional per
 * role (lab tech, sample collector, doctor, pharmacist, receptionist) with a
 * provider session at facility A, and a patient. Same recipe as
 * ProviderScanTest / ProviderRoleMatrixTest.
 */
abstract class LabDeskTestCase extends TestCase
{
    use RefreshDatabase;

    protected const ROLES = ['labtech', 'sample_collection', 'doctor', 'pharmacist', 'receptionist'];

    protected const HEALTH_ID = 'CM-HID-TEST-LAB-0001';

    protected Facility $facility;

    protected Facility $otherFacility;

    protected Patient $patient;

    /** @var array<string, User> */
    protected array $users = [];

    /** @var array<string, string> */
    protected array $bearer = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility      = Facility::factory()->create(['name' => 'Laboratoire Test A', 'type' => 'lab']);
        $this->otherFacility = Facility::factory()->create(['name' => 'Laboratoire Test B', 'type' => 'lab']);

        foreach (self::ROLES as $role) {
            $this->users[$role]  = $this->professional($role);
            $this->bearer[$role] = app(ProviderSessionService::class)->mint($this->users[$role], $this->facility->id, 'lab-desk-test');
        }

        $this->patient = Patient::factory()->create([
            'health_id'     => self::HEALTH_ID,
            'first_name'    => 'Amina',
            'last_name'     => 'Ngo',
            'sex'           => 'female',
            'date_of_birth' => '1990-06-15',
            'is_demo'       => (bool) config('demo.enabled'),
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
            'name'    => ucfirst($role) . ' Test',
            'is_demo' => (bool) config('demo.enabled'),
            'role_id' => $model->id,
        ]);
    }

    protected function hit(string $role, string $method, string $path, array $body = []): TestResponse
    {
        return $this->withHeaders(['Authorization' => "Bearer {$this->bearer[$role]}"])
            ->json($method, $path, $body);
    }

    protected function asLab(string $method, string $path, array $body = []): TestResponse
    {
        return $this->hit('labtech', $method, $path, $body);
    }

    /** An open order at facility A for the patient, placed by the doctor. */
    protected function order(array $attributes = []): LabOrder
    {
        return LabOrder::factory()->create($attributes + [
            'patient_id'   => $this->patient->id,
            'facility_id'  => $this->facility->id,
            'ordered_by'   => $this->users['doctor']->id,
            'test_name'    => 'Full Blood Count',
            'test_code'    => 'FBC',
            'urgency'      => 'routine',
            'status'       => 'ordered',
            'ordered_at'   => now()->subHour(),
            'collected_at' => null,
            'resulted_at'  => null,
        ]);
    }

    /** A submitted patient request to facility A. */
    protected function request(array $attributes = []): LabTestRequest
    {
        return LabTestRequest::create($attributes + [
            'patient_id'     => $this->patient->id,
            'facility_id'    => $this->facility->id,
            'test_code'      => 'GLUF',
            'test_name'      => 'Fasting Blood Glucose',
            'priority'       => 'urgent',
            'status'         => 'submitted',
            'clinical_notes' => 'Repeat fasting sugar before my next visit.',
            'submitted_at'   => now()->subHours(2),
        ]);
    }
}
