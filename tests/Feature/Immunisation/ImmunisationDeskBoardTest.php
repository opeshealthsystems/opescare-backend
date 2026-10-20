<?php

namespace Tests\Feature\Immunisation;

use App\Models\ConsentGrant;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\ImmunizationRecord;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Models\VaccinationSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The facility-wide immunisation desk: this facility's patients only, each
 * through the chart's gate, due/overdue from real schedule rows only.
 */
class ImmunisationDeskBoardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
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

    private function patient(Facility $facility, string $first): Patient
    {
        $patient = Patient::factory()->create([
            'first_name' => $first, 'privacy_preferences' => ['require_consent_for_full_record' => false],
        ]);

        ConsentGrant::create([
            'patient_id' => $patient->id, 'facility_id' => $facility->id, 'authorizing_actor' => 'patient',
            'scope' => ['full'], 'status' => 'active', 'expires_at' => now()->addMonth(),
        ]);

        return $patient;
    }

    private function schedule(Patient $patient, string $name, string $due, string $status = 'due'): void
    {
        VaccinationSchedule::create([
            'patient_id' => $patient->id, 'vaccine_code' => '89', 'vaccine_name' => $name,
            'dose_number' => 1, 'due_date' => $due, 'status' => $status,
        ]);
    }

    private function given(Patient $patient, Facility $facility, string $name, $at): void
    {
        ImmunizationRecord::create([
            'patient_id' => $patient->id, 'facility_id' => $facility->id, 'vaccine_code' => '20',
            'vaccine_name' => $name, 'administered_at' => $at, 'status' => 'completed',
        ]);
    }

    public function test_due_overdue_and_recent_come_from_real_rows_of_this_facility_only(): void
    {
        [$nurse, $centre] = $this->staff('nurse');
        $other = Facility::factory()->create(['type' => 'health_center']);

        $mine     = $this->patient($centre, 'Amina');
        $late     = $this->patient($centre, 'Bello');
        $stranger = $this->patient($other, 'Zacharie');

        $this->schedule($mine, 'Polio today', now()->toDateString());
        $this->schedule($late, 'Measles late', now()->subDays(10)->toDateString());
        $this->schedule($late, 'Done already', now()->toDateString(), 'completed');
        $this->schedule($stranger, 'Elsewhere vaccine', now()->toDateString());

        $this->given($mine, $centre, 'DTaP given here', now());
        $this->given($stranger, $other, 'DTaP given there', now());

        $this->as($nurse, $centre)->get('/facility/immunisations')
            ->assertOk()
            ->assertSee('Polio today')->assertSee('Amina')
            ->assertDontSee('Done already')->assertDontSee('Elsewhere vaccine')->assertDontSee('Zacharie')
            ->assertSee(route('portals.immunisations.index', $mine->id), false)
            ->assertViewHas('kpis', ['given_today' => 1, 'due_today' => 1, 'overdue' => 1, 'given_week' => 1]);

        $this->as($nurse, $centre)->get('/facility/immunisations?tab=overdue')
            ->assertOk()->assertSee('Measles late')->assertDontSee('Polio today');

        $this->as($nurse, $centre)->get('/facility/immunisations?tab=recent')
            ->assertOk()->assertSee('DTaP given here')->assertDontSee('DTaP given there');

        $this->assertDatabaseHas('access_logs', [
            'patient_id' => $mine->id, 'actor_id' => $nurse->id, 'access_type' => 'read', 'data_category' => 'immunization',
        ]);
        $this->assertDatabaseMissing('access_logs', ['patient_id' => $stranger->id, 'actor_id' => $nurse->id]);
    }

    public function test_a_tie_without_a_gate_basis_is_not_shown(): void
    {
        [$nurse, $centre] = $this->staff('nurse');

        // A dose recorded here years ago is a reason to look, not to see.
        $past = Patient::factory()->create([
            'first_name' => 'Hidden', 'privacy_preferences' => ['require_consent_for_full_record' => false],
        ]);
        $this->given($past, $centre, 'Old dose', now()->subYears(3));
        $this->schedule($past, 'Hidden due', now()->toDateString());

        $this->as($nurse, $centre)->get('/facility/immunisations')
            ->assertOk()->assertDontSee('Hidden due')
            ->assertSee(__('immunisation_desk.empty_due'));
    }

    public function test_an_empty_desk_is_honest(): void
    {
        [$nurse, $centre] = $this->staff('nurse');

        $this->as($nurse, $centre)->get('/facility/immunisations')
            ->assertOk()
            ->assertSee(__('immunisation_desk.empty_due'))
            ->assertSee(__('immunisation_desk.empty_schedule_note'));
    }

    public function test_roles_without_clinical_chart_are_refused(): void
    {
        [$pharmacist, $centre] = $this->staff('pharmacist');

        $this->as($pharmacist, $centre)->get('/facility/immunisations')->assertForbidden();
        $this->as($pharmacist, $centre)->get('/facility/immunisations/find?health_id=X')->assertForbidden();
    }

    public function test_search_is_by_exact_health_id(): void
    {
        [$nurse, $centre] = $this->staff('nurse');
        $patient = $this->patient($centre, 'Amina');

        $this->as($nurse, $centre)->get('/facility/immunisations/find?health_id=' . urlencode($patient->health_id))
            ->assertRedirect(route('portals.immunisations.index', $patient->id));

        $this->as($nurse, $centre)->get('/facility/immunisations/find?health_id=Amina')
            ->assertRedirect(route('portals.immunisations.desk'))
            ->assertSessionHas('error', __('immunisation_desk.search_not_found'));
    }

    public function test_the_sidebar_links_to_the_desk_for_clinicians(): void
    {
        foreach (['nurse', 'doctor', 'midwife'] as $role) {
            [$user, $centre] = $this->staff($role);

            $this->as($user, $centre)->get('/facility/immunisations')
                ->assertOk()
                ->assertSee('href="' . route('portals.immunisations.desk') . '"', false);
        }
    }
}
