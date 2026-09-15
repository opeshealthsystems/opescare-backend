<?php

namespace Tests\Feature\Immunisation;

use App\Models\ConsentGrant;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\ImmunizationRecord;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The web immunisation desk (HC-2): the chart's door, the clinician app's
 * write, and rows that are never edited.
 */
class ImmunisationDeskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);

        // One CVX vaccine, as opescare:import-vaccine-codes writes it.
        DB::table('clinical_code_dictionary')->insert([
            'id' => (string) Str::uuid(), 'system' => 'CVX', 'code' => '89',
            'display' => 'poliovirus vaccine, unspecified formulation', 'kind' => 'vaccine',
            'is_active' => true, 'source' => 'test', 'source_version' => 'test', 'imported_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** @return array{0: User, 1: Facility} */
    private function staff(string $roleName, ?Facility $facility = null): array
    {
        $role     = Role::where('name', $roleName)->firstOrFail();
        $facility ??= Facility::factory()->create(['type' => 'health_center']);

        $user = User::factory()->create([
            'role_id' => $role->id, 'primary_facility_id' => $facility->id, 'status' => 'active',
        ]);

        FacilityRoleAssignment::create([
            'user_id' => $user->id, 'facility_id' => $facility->id, 'role_id' => $role->id,
            'is_active' => true, 'assigned_at' => now(),
        ]);

        return [$user, $facility];
    }

    private function as(User $user, Facility $facility): static
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id]);
    }

    private function patientWithGrant(Facility $facility): Patient
    {
        $patient = Patient::factory()->create(['privacy_preferences' => ['require_consent_for_full_record' => false]]);

        ConsentGrant::create([
            'patient_id' => $patient->id, 'facility_id' => $facility->id, 'authorizing_actor' => 'patient',
            'scope' => ['full'], 'status' => 'active', 'expires_at' => now()->addMonth(),
        ]);

        return $patient;
    }

    private function dose(array $overrides = []): array
    {
        return array_merge([
            'vaccine_code' => '89', 'dose_number' => 1, 'administered_on' => now()->toDateString(),
            'lot_number' => 'LOT-42', 'site' => 'left thigh', 'status' => 'completed',
        ], $overrides);
    }

    public function test_a_nurse_at_a_centre_with_consent_records_a_dose(): void
    {
        [$nurse, $centre] = $this->staff('nurse');
        $patient = $this->patientWithGrant($centre);

        // Reached by the patient's Health ID as well as their uuid.
        $this->as($nurse, $centre)->get("/facility/patients/{$patient->health_id}/immunizations")
            ->assertOk()->assertSee(__('immunisation_desk.form_title'));

        $this->as($nurse, $centre)->post("/facility/patients/{$patient->id}/immunizations", $this->dose([
            'facility_id' => Str::uuid()->toString(), 'administered_by_id' => Str::uuid()->toString(),
        ]))->assertRedirect("/facility/patients/{$patient->id}/immunizations");

        $record = ImmunizationRecord::where('patient_id', $patient->id)->firstOrFail();
        $this->assertSame($centre->id, $record->facility_id);
        $this->assertSame((string) $nurse->id, (string) $record->administered_by_id);
        $this->assertSame('CVX', $record->vaccine_system);
        $this->assertSame('poliovirus vaccine, unspecified formulation', $record->vaccine_name);
        $this->assertSame(1, (int) $record->dose_number);
        $this->assertSame('LOT-42', $record->lot_number);
        $this->assertDatabaseHas('access_logs', [
            'patient_id' => $patient->id, 'actor_id' => $nurse->id, 'access_type' => 'create', 'data_category' => 'immunization',
        ]);

        // A vaccine outside the vocabulary is refused; a double tap is caught.
        $this->as($nurse, $centre)->post("/facility/patients/{$patient->id}/immunizations", $this->dose(['vaccine_code' => 'OPV']))
            ->assertSessionHasErrors('vaccine_code');
        $this->as($nurse, $centre)->post("/facility/patients/{$patient->id}/immunizations", $this->dose())
            ->assertSessionHasErrors('vaccine_code');
        $this->assertSame(1, ImmunizationRecord::where('patient_id', $patient->id)->count());

        // The chart's immunisations tab links to the desk.
        $this->as($nurse, $centre)->get("/facility/patients/{$patient->id}?tab=immunizations")
            ->assertOk()->assertSee(route('portals.immunisations.index', $patient->id), false);
    }

    public function test_no_relationship_is_a_404_with_a_denied_access_row(): void
    {
        [$nurse, $centre] = $this->staff('nurse');
        $stranger = Patient::factory()->create(['privacy_preferences' => ['require_consent_for_full_record' => false]]);

        $this->as($nurse, $centre)->get("/facility/patients/{$stranger->id}/immunizations")->assertNotFound();
        $this->as($nurse, $centre)->post("/facility/patients/{$stranger->id}/immunizations", $this->dose())->assertNotFound();

        $this->assertSame(0, ImmunizationRecord::count());
        $this->assertDatabaseHas('access_logs', [
            'patient_id' => $stranger->id, 'actor_id' => $nurse->id, 'access_type' => 'denied',
        ]);
    }

    public function test_a_future_date_is_rejected(): void
    {
        [$nurse, $centre] = $this->staff('nurse');
        $patient = $this->patientWithGrant($centre);

        $this->as($nurse, $centre)->post("/facility/patients/{$patient->id}/immunizations", $this->dose([
            'administered_on' => now()->addDays(2)->toDateString(),
        ]))->assertSessionHasErrors('administered_on');

        $this->assertSame(0, ImmunizationRecord::count());
    }

    public function test_rows_are_immutable_corrected_only_by_entered_in_error_and_re_record(): void
    {
        [$nurse, $centre] = $this->staff('nurse');
        $patient = $this->patientWithGrant($centre);

        $this->as($nurse, $centre)->post("/facility/patients/{$patient->id}/immunizations", $this->dose(['dose_number' => 2]));
        $wrong = ImmunizationRecord::where('patient_id', $patient->id)->firstOrFail();

        // No update or delete verb exists on the desk.
        $this->as($nurse, $centre)->put("/facility/patients/{$patient->id}/immunizations", $this->dose())->assertStatus(405);
        $this->as($nurse, $centre)->delete("/facility/patients/{$patient->id}/immunizations")->assertStatus(405);

        // Another facility may not retract this facility's entry.
        [$other, $otherCentre] = $this->staff('nurse');
        ConsentGrant::create([
            'patient_id' => $patient->id, 'facility_id' => $otherCentre->id, 'authorizing_actor' => 'patient',
            'scope' => ['full'], 'status' => 'active', 'expires_at' => now()->addMonth(),
        ]);
        $this->as($other, $otherCentre)->post("/facility/patients/{$patient->id}/immunizations/{$wrong->id}/void", ['reason' => 'x'])
            ->assertForbidden();

        $this->as($nurse, $centre)->post("/facility/patients/{$patient->id}/immunizations/{$wrong->id}/void", [])
            ->assertSessionHasErrors('reason');
        $this->as($nurse, $centre)->post("/facility/patients/{$patient->id}/immunizations/{$wrong->id}/void", ['reason' => 'Wrong dose number'])
            ->assertRedirect();

        $this->as($nurse, $centre)->post("/facility/patients/{$patient->id}/immunizations", $this->dose(['dose_number' => 1]))
            ->assertRedirect();

        $rows = ImmunizationRecord::where('patient_id', $patient->id)->orderBy('created_at')->get();
        $this->assertCount(2, $rows);
        $this->assertSame('entered_in_error', $rows[0]->status);
        $this->assertSame(2, (int) $rows[0]->dose_number);
        $this->assertSame('completed', $rows[1]->status);
    }

    public function test_a_pharmacist_is_refused(): void
    {
        [$pharmacist, $pharmacy] = $this->staff('pharmacist');
        $patient = $this->patientWithGrant($pharmacy);

        $this->as($pharmacist, $pharmacy)->get("/facility/patients/{$patient->id}/immunizations")->assertForbidden();
        $this->as($pharmacist, $pharmacy)->post("/facility/patients/{$patient->id}/immunizations", $this->dose())->assertForbidden();
        $this->assertSame(0, ImmunizationRecord::count());
    }
}
