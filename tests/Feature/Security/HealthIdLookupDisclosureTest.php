<?php

namespace Tests\Feature\Security;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * POST /verify/health-id is public. It used to hand any caller the full name
 * and full date of birth for any Health ID, with nothing logged.
 */
class HealthIdLookupDisclosureTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = Patient::factory()->create([
            'health_id'     => 'OC-LKP-001',
            'first_name'    => 'Amara',
            'last_name'     => 'Diallo',
            'date_of_birth' => '1987-04-23',
            'sex'           => 'female',
            'is_demo'       => false,
        ]);
    }

    private function lookup(string $healthId = 'OC-LKP-001')
    {
        return $this->post('/verify/health-id', ['health_id' => $healthId, 'purpose' => 'scheduled_visit']);
    }

    public function test_anonymous_caller_sees_masked_name_and_birth_year_only(): void
    {
        $this->lookup()
            ->assertOk()
            ->assertSee('Amara D.')
            ->assertSee('1987')
            ->assertDontSee('Diallo')
            ->assertDontSee('23 Apr 1987')
            ->assertDontSee('1987-04-23');

        $this->assertDatabaseHas('medical_id_access_events', [
            'patient_id'  => $this->patient->id,
            'health_id'   => 'OC-LKP-001',
            'access_type' => 'verify_health_id',
            'actor_type'  => 'public',
            'actor_id'    => null,
            'purpose'     => 'scheduled_visit',
            'result'      => 'success',
        ]);
    }

    public function test_signed_in_facility_staff_see_full_details_and_are_logged_with_facility(): void
    {
        $facility = Facility::factory()->create();
        $nurse = User::factory()->create([
            'role_id'             => Role::firstOrCreate(['name' => 'nurse'], ['description' => 'Nurse'])->id,
            'primary_facility_id' => $facility->id,
        ]);

        $this->actingAs($nurse)->lookup()
            ->assertOk()
            ->assertSee('Amara Diallo');

        $this->assertDatabaseHas('medical_id_access_events', [
            'patient_id'  => $this->patient->id,
            'actor_id'    => $nurse->id,
            'actor_type'  => 'staff',
            'facility_id' => $facility->id,
            'result'      => 'success',
        ]);
    }

    public function test_signed_in_patient_account_still_gets_the_masked_view(): void
    {
        $facility = Facility::factory()->create();
        $user = User::factory()->create([
            'role_id'             => Role::firstOrCreate(['name' => 'patient'], ['description' => 'Patient'])->id,
            'primary_facility_id' => $facility->id,
        ]);

        $this->actingAs($user)->lookup()->assertOk()->assertSee('Amara D.')->assertDontSee('Diallo');
    }

    public function test_not_found_lookup_is_logged_too(): void
    {
        $this->lookup('OC-NOPE-999')->assertOk();

        $this->assertDatabaseHas('medical_id_access_events', [
            'patient_id'  => null,
            'health_id'   => 'OC-NOPE-999',
            'access_type' => 'verify_health_id',
            'result'      => 'not_found',
        ]);
        $this->assertSame(1, DB::table('medical_id_access_events')->count());
    }

    public function test_masked_name_helper(): void
    {
        $this->assertSame('Amara D.', \App\Http\Controllers\MedicalId\VerifyController::maskedName('Amara', 'diallo'));
        $this->assertSame('Amara', \App\Http\Controllers\MedicalId\VerifyController::maskedName('Amara', ''));
    }
}
