<?php

namespace Tests\Feature\HealthCentre;

use App\Models\ConsentGrant;
use App\Models\Facility;
use App\Models\ImmunizationRecord;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\Portal\ProviderSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A Centre de Santé Intégré often has no resident doctor. Its nurse and
 * midwife must be able to put a dose on the patient's Health ID card (the
 * clinician app path, capability:clinical_chart) with no doctor posted there.
 */
class NurseLedImmunisationTest extends TestCase
{
    use RefreshDatabase;

    private function professional(string $role): User
    {
        DB::table('dashboard_profiles')->updateOrInsert(['key' => $role], [
            'name' => ucfirst($role), 'portal_prefix' => 'staff', 'landing_route' => 'staff.dashboard',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $model = Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role)]);
        $model->forceFill(['dashboard_profile_key' => $role])->save();

        return User::factory()->create(['is_demo' => (bool) config('demo.enabled'), 'role_id' => $model->id]);
    }

    public function test_a_nurse_and_a_midwife_record_a_dose_at_a_centre_with_no_doctor(): void
    {
        $centre = Facility::factory()->create(['name' => 'CSI Nkolbisson', 'type' => 'health_center']);

        foreach (['nurse' => 'CM-HID-TEST-CSIN-0001', 'midwife' => 'CM-HID-TEST-CSIM-0002'] as $role => $healthId) {
            $patient = Patient::factory()->create(['health_id' => $healthId, 'is_demo' => (bool) config('demo.enabled')]);
            ConsentGrant::create([
                'patient_id' => $patient->id, 'facility_id' => $centre->id, 'authorizing_actor' => 'patient',
                'scope' => ['patient_identity', 'allergies', 'problems', 'encounters', 'vitals'],
                'status' => 'active', 'expires_at' => now()->addDays(90),
            ]);

            $clinician = $this->professional($role);
            $bearer = app(ProviderSessionService::class)->mint($clinician, $centre->id, 'csi-' . $role);

            $response = $this->withHeaders(['Authorization' => "Bearer {$bearer}"])
                ->json('POST', "/api/mobile/doctor/patients/{$healthId}/immunizations", [
                    'vaccine_code' => 'OPV', 'vaccine_name' => 'Oral polio vaccine', 'dose_number' => 1,
                ]);

            $response->assertStatus(201);
            $record = ImmunizationRecord::findOrFail($response->json('data.id'));
            $this->assertSame($centre->id, $record->facility_id);
            $this->assertSame($clinician->id, $record->administered_by_id);
        }

        $this->assertSame(0, User::whereHas('role', fn ($q) => $q->where('name', 'doctor'))->count());
    }
}
