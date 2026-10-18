<?php

namespace Tests\Feature\DiagnosticCentre;

use App\Enums\ImagingOrderStatus;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\ImagingOrder;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\Dashboard\DashboardProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The diagnostic centre landing (/facility/diagnostic) and the ways it is
 * not allowed to mislead:
 *
 *   1. A LAB MANAGER AT A DIAGNOSTIC CENTRE SEES BOTH HALVES — lab and
 *      imaging figures, read from this facility's live tables.
 *   2. A LAB-ONLY FACILITY IS SENT TO ITS OWN DASHBOARD (302), never shown
 *      a page of imaging zeros.
 *   3. A RECEPTIONIST IS REFUSED — 403, not a redirect.
 *   4. EMPTY STATES SAY WHAT TO DO NEXT, with no invented figure.
 *   5. THE FACILITY ADMINISTRATOR of a centre may open it, and the admin
 *      overview hides the Beds panel there (I-7).
 *   6. LOGIN LANDS a lab manager at a centre here; at a laboratory it does not.
 */
class DiagnosticDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function staff(string $roleName = 'lab_manager', ?Facility $facility = null, string $type = 'diagnostic_center'): array
    {
        $role     = Role::where('name', $roleName)->firstOrFail();
        $facility ??= Facility::factory()->create(['type' => $type]);

        $user = User::factory()->create([
            'role_id'             => $role->id,
            'primary_facility_id' => $facility->id,
            'status'              => 'active',
        ]);

        FacilityRoleAssignment::create([
            'user_id'     => $user->id,
            'facility_id' => $facility->id,
            'role_id'     => $role->id,
            'is_active'   => true,
            'assigned_at' => now(),
        ]);

        return [$user, $facility];
    }

    private function actingAsStaff(User $user, Facility $facility): static
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id]);
    }

    private function labOrder(Facility $facility, array $overrides = []): LabOrder
    {
        return LabOrder::factory()->create(array_merge([
            'facility_id'  => $facility->id,
            'patient_id'   => Patient::factory()->create(['date_of_birth' => '1980-05-05'])->id,
            'test_name'    => 'Potassium',
            'test_code'    => 'K',
            'status'       => 'ordered',
            'collected_at' => null,
            'resulted_at'  => null,
        ], $overrides));
    }

    private function imagingOrder(Facility $facility, User $by, array $overrides = []): ImagingOrder
    {
        return ImagingOrder::create(array_merge([
            'patient_id'          => Patient::factory()->create(['date_of_birth' => '1980-05-05'])->id,
            'facility_id'         => $facility->id,
            'ordered_by'          => $by->id,
            'modality'            => 'xray',
            'body_part'           => 'chest',
            'clinical_indication' => 'Cough',
            'urgency'             => 'routine',
            'status'              => ImagingOrderStatus::Pending,
            'ordered_at'          => now(),
            'accession_number'    => 'IMG-TEST-' . fake()->unique()->numerify('####'),
        ], $overrides));
    }

    // ── 1. Both halves, live figures ─────────────────────────────────────────

    public function test_a_lab_manager_at_a_diagnostic_centre_sees_both_halves_with_live_figures(): void
    {
        [$manager, $centre] = $this->staff();
        [, $elsewhere]      = $this->staff('lab_manager', null, 'diagnostic_center');

        // Lab: two open here, one released today; one open elsewhere.
        $this->labOrder($centre);
        $this->labOrder($centre, ['status' => 'collected', 'collected_at' => now()->subHours(3)]);
        $this->labOrder($centre, ['status' => 'resulted', 'collected_at' => now()->subHours(2), 'resulted_at' => now()]);
        $this->labOrder($elsewhere);

        // Imaging: two pending today, one completed awaiting a report; one elsewhere.
        $this->imagingOrder($centre, $manager);
        $this->imagingOrder($centre, $manager);
        $this->imagingOrder($centre, $manager, ['status' => ImagingOrderStatus::Completed, 'completed_at' => now()]);
        $this->imagingOrder($elsewhere, $manager);

        $page = $this->actingAsStaff($manager, $centre)->get('/facility/diagnostic')->assertOk();

        $page->assertSee('data-half="lab"', false)
            ->assertSee('data-half="imaging"', false)
            ->assertSee('data-panel="publication"', false)
            ->assertViewHas('lab', fn ($lab) => $lab['open'] === 2 && $lab['resulted_today'] === 1 && $lab['has_work'] === true)
            ->assertViewHas('imaging', fn ($img) => $img['pending'] === 2 && $img['today'] === 3 && $img['awaiting_report'] === 1 && $img['reported'] === 0);

        // No listing linked: the panel says so and counts nothing.
        $page->assertSee(__('diagnostic_desk.pub_unlinked'))
            ->assertViewHas('publication', fn ($p) => $p['linked'] === false && $p['lab_published'] === 0 && $p['imaging_published'] === 0);

        // An imaging centre lands here too.
        [$imgManager, $imgCentre] = $this->staff('lab_manager', null, 'imaging_center');
        $this->actingAsStaff($imgManager, $imgCentre)->get('/facility/diagnostic')->assertOk()
            ->assertSee(__('diagnostic_desk.type_imaging'));
    }

    // ── 2. Lab-only facility → its own dashboard ─────────────────────────────

    public function test_a_lab_only_facility_is_sent_to_the_lab_dashboard(): void
    {
        [$manager, $lab] = $this->staff('lab_manager', null, 'laboratory');

        $this->actingAsStaff($manager, $lab)
            ->get('/facility/diagnostic')
            ->assertRedirect(route('portals.lab_manager.dashboard'))
            ->assertSessionHas('info', __('diagnostic_desk.not_a_centre'));
    }

    // ── 3. Wrong role → 403 ──────────────────────────────────────────────────

    public function test_a_receptionist_is_refused_with_a_403(): void
    {
        [$manager, $centre] = $this->staff();
        [$reception]        = $this->staff('receptionist', $centre);

        $this->actingAsStaff($reception, $centre)->get('/facility/diagnostic')->assertForbidden();

        // The sidebar link is only offered at a centre: a lab manager at a
        // laboratory does not see it.
        [$labManager, $lab] = $this->staff('lab_manager', null, 'laboratory');
        $this->actingAsStaff($labManager, $lab)->get('/facility/lab/dashboard')->assertOk()
            ->assertDontSee(route('portals.diagnostic.dashboard'));
        $this->actingAsStaff($manager, $centre)->get('/facility/lab/dashboard')->assertOk()
            ->assertSee(route('portals.diagnostic.dashboard'));
    }

    // ── 4. Empty states ──────────────────────────────────────────────────────

    public function test_empty_halves_say_what_to_do_next_and_show_no_figure(): void
    {
        [$manager, $centre] = $this->staff();

        $this->actingAsStaff($manager, $centre)->get('/facility/diagnostic')->assertOk()
            ->assertSee('data-empty="lab"', false)
            ->assertSee(__('diagnostic_desk.lab_empty_title'))
            ->assertSee(__('diagnostic_desk.lab_empty_action'))
            ->assertSee('data-empty="imaging"', false)
            ->assertSee(__('diagnostic_desk.imaging_empty_title'))
            ->assertSee(route('portals.imaging.create'))
            ->assertDontSee('data-kpi="lab_open"', false)
            ->assertDontSee('data-kpi="imaging_pending"', false)
            ->assertSee(__('diagnostic_desk.pub_lab_none'))
            ->assertSee(__('diagnostic_desk.pub_imaging_none'));
    }

    // ── 5. Facility administrator; admin overview hides beds (I-7) ──────────

    public function test_the_facility_administrator_of_a_centre_opens_it_and_the_overview_hides_beds(): void
    {
        [$admin, $centre] = $this->staff('facility_admin');

        $this->actingAsStaff($admin, $centre)->get('/facility/diagnostic')->assertOk()
            ->assertSee('data-half="imaging"', false);

        $this->actingAsStaff($admin, $centre)->get('/facility/team/overview')->assertOk()
            ->assertDontSee('data-panel="beds"', false)
            ->assertDontSee(route('portals.facility.admin.wards'))
            ->assertDontSee(route('portals.facility.admin.departments'))
            ->assertSee(route('portals.diagnostic.dashboard'));

        // A hospital keeps its beds panel and its links.
        [$hospAdmin, $hospital] = $this->staff('facility_admin', null, 'hospital');
        $this->actingAsStaff($hospAdmin, $hospital)->get('/facility/team/overview')->assertOk()
            ->assertSee('data-panel="beds"', false)
            ->assertSee(route('portals.facility.admin.wards'))
            ->assertSee(route('portals.facility.admin.departments'))
            ->assertDontSee(route('portals.diagnostic.dashboard'));
    }

    // ── 6. Landing after login ───────────────────────────────────────────────

    public function test_a_lab_manager_lands_on_the_centre_page_only_at_a_centre(): void
    {
        [$manager, $centre] = $this->staff();
        [$labManager]       = $this->staff('lab_manager', null, 'laboratory');

        $landing = app(DashboardProfileService::class);

        $this->assertSame(route('portals.diagnostic.dashboard'), $landing->landingUrlForUser($manager));
        $this->assertNotSame(route('portals.diagnostic.dashboard'), $landing->landingUrlForUser($labManager));

        // The active facility wins over the primary posting.
        session(['active_facility_id' => $centre->id]);
        $this->assertSame(route('portals.diagnostic.dashboard'), $landing->landingUrlForUser($labManager));
    }
}
