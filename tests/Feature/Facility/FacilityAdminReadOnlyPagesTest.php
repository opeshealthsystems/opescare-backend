<?php

namespace Tests\Feature\Facility;

use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Role;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The two read-only pages under the facility overview
 * (docs/HOSPITAL_ADMIN_DASHBOARD_AUDIT.md H-4 and H-5):
 *
 *   /facility/team/overview/invoices    — this facility's invoices, no action.
 *     `capability:billing` was NOT widened to administrators because it gates
 *     the till's writes in the same route group as its reads.
 *   /facility/team/overview/departments — active staff grouped by the
 *     free-text `staff_profiles.department`; no Department model exists.
 *
 * Both are facility-scoped by the session context and closed to clinicians
 * and to the billing desk's own roles the way the overview is.
 */
class FacilityAdminReadOnlyPagesTest extends TestCase
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
    private function staff(string $roleName, ?Facility $facility = null, array $userAttrs = []): array
    {
        $facility ??= Facility::factory()->create(['type' => 'hospital', 'name' => 'Hôpital Laquintinie']);
        $role = Role::where('name', $roleName)->firstOrFail();

        $user = User::factory()->create(array_merge([
            'role_id'             => $role->id,
            'primary_facility_id' => $facility->id,
            'status'              => 'active',
        ], $userAttrs));

        FacilityRoleAssignment::create([
            'user_id' => $user->id, 'facility_id' => $facility->id, 'role_id' => $role->id,
            'is_active' => true, 'assigned_at' => now(),
        ]);

        return [$user, $facility];
    }

    private function asStaff(User $user, Facility $facility): static
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id]);
    }

    // ── Invoices ─────────────────────────────────────────────────────────

    public function test_invoices_page_lists_only_this_facilitys_invoices_and_offers_no_action(): void
    {
        [$admin, $facility] = $this->staff('facility_admin');
        [, $other]          = $this->staff('facility_admin');

        $patient = Patient::factory()->create(['facility_id' => $facility->id, 'first_name' => 'Amina', 'last_name' => 'Ngo']);
        Invoice::factory()->create(['facility_id' => $facility->id, 'patient_id' => $patient->id, 'invoice_number' => 'INV-HERE-1', 'status' => 'paid']);
        Invoice::factory()->create(['facility_id' => $other->id, 'invoice_number' => 'INV-THERE-1', 'status' => 'issued']);

        $html = $this->asStaff($admin, $facility)
            ->get(route('portals.facility.admin.invoices'))
            ->assertOk()
            ->assertSee('INV-HERE-1')
            ->assertDontSee('INV-THERE-1')
            ->assertSee('Amina Ngo')
            ->getContent();

        // Read-only: no form inside the page content (the layout's own sign-out
        // form sits outside <main>), and no link into the billing desk's writes.
        $main = substr($html, strpos($html, '<main'));
        $this->assertStringNotContainsString('<form', $main);
        $this->assertStringNotContainsString('/facility/billing', $main);
    }

    public function test_the_billing_desk_itself_is_still_closed_to_administrators(): void
    {
        [$admin, $facility] = $this->staff('facility_admin');

        // The decision on record: capability:billing gates writes, so it is
        // not widened. An administrator reads invoices under the overview.
        $response = $this->asStaff($admin, $facility)->get('/facility/billing');
        $this->assertContains($response->getStatusCode(), [302, 403], 'capability:billing must still refuse a facility admin');
    }

    public function test_invoices_page_is_refused_to_a_cashier_and_a_doctor(): void
    {
        foreach (['cashier', 'doctor'] as $roleName) {
            [$user, $facility] = $this->staff($roleName);
            $response = $this->asStaff($user, $facility)->get('/facility/team/overview/invoices');
            $this->assertContains($response->getStatusCode(), [302, 403], "{$roleName} must not open the administrator's invoice list");
        }
    }

    public function test_an_invoice_opens_read_only_from_the_list_and_another_facilitys_is_a_404(): void
    {
        [$admin, $facility] = $this->staff('facility_admin');
        [, $other]          = $this->staff('facility_admin');

        $patient = Patient::factory()->create(['facility_id' => $facility->id, 'first_name' => 'Amina', 'last_name' => 'Ngo']);
        $mine    = Invoice::factory()->create(['facility_id' => $facility->id, 'patient_id' => $patient->id, 'invoice_number' => 'INV-HERE-2', 'status' => 'paid']);
        $theirs  = Invoice::factory()->create(['facility_id' => $other->id, 'invoice_number' => 'INV-THERE-2', 'status' => 'issued']);

        // The list row links to it.
        $this->asStaff($admin, $facility)
            ->get(route('portals.facility.admin.invoices'))
            ->assertSee(route('portals.facility.admin.invoices.show', $mine->id));

        $html = $this->asStaff($admin, $facility)
            ->get(route('portals.facility.admin.invoices.show', $mine->id))
            ->assertOk()
            ->assertSee('INV-HERE-2')
            ->assertSee('Amina Ngo')
            ->getContent();

        // Read-only: no form and no billing-desk write link in the content.
        $main = substr($html, strpos($html, '<main'));
        $this->assertStringNotContainsString('<form', $main);
        $this->assertStringNotContainsString('/facility/billing', $main);

        // Another facility's invoice does not exist from here.
        $this->asStaff($admin, $facility)
            ->get(route('portals.facility.admin.invoices.show', $theirs->id))
            ->assertNotFound();
    }

    public function test_the_invoice_view_is_refused_to_a_cashier_and_a_doctor(): void
    {
        foreach (['cashier', 'doctor'] as $roleName) {
            [$user, $facility] = $this->staff($roleName);
            $invoice  = Invoice::factory()->create(['facility_id' => $facility->id, 'status' => 'issued']);
            $response = $this->asStaff($user, $facility)->get(route('portals.facility.admin.invoices.show', $invoice->id));
            $this->assertContains($response->getStatusCode(), [302, 403], "{$roleName} must not open the administrator's invoice view");
        }
    }

    // ── Departments ──────────────────────────────────────────────────────

    public function test_departments_groups_active_staff_by_the_free_text_string_with_an_unassigned_bucket(): void
    {
        [$admin, $facility] = $this->staff('facility_admin');
        [, $other]          = $this->staff('facility_admin');

        [$doc1]   = $this->staff('doctor', $facility, ['name' => 'Dr Fotso']);
        [$doc2]   = $this->staff('doctor', $facility, ['name' => 'Dr Mbarga']);
        [$nurse]  = $this->staff('nurse',  $facility, ['name' => 'Nurse Ateba']);
        [$noDept] = $this->staff('nurse',  $facility, ['name' => 'Nurse Nkeng']);
        [$left]   = $this->staff('doctor', $facility, ['name' => 'Dr Gone']);
        [$away]   = $this->staff('doctor', $other,    ['name' => 'Dr Elsewhere']);

        StaffProfile::create(['user_id' => $doc1->id, 'first_name' => $doc1->name, 'last_name' => '-',   'facility_id' => $facility->id, 'department' => 'Cardiology', 'status' => 'active', 'job_title' => 'Cardiologist', 'staff_category' => 'clinical']);
        StaffProfile::create(['user_id' => $doc2->id, 'first_name' => $doc2->name, 'last_name' => '-',   'facility_id' => $facility->id, 'department' => 'Cardiology', 'status' => 'active', 'staff_category' => 'clinical']);
        StaffProfile::create(['user_id' => $nurse->id, 'first_name' => $nurse->name, 'last_name' => '-',  'facility_id' => $facility->id, 'department' => 'Emergency & Triage', 'status' => 'active', 'staff_category' => 'clinical']);
        StaffProfile::create(['user_id' => $noDept->id, 'first_name' => $noDept->name, 'last_name' => '-', 'facility_id' => $facility->id, 'department' => null, 'status' => 'active', 'staff_category' => 'clinical']);
        StaffProfile::create(['user_id' => $left->id, 'first_name' => $left->name, 'last_name' => '-',   'facility_id' => $facility->id, 'department' => 'Cardiology', 'status' => 'inactive', 'staff_category' => 'clinical']);
        StaffProfile::create(['user_id' => $away->id, 'first_name' => $away->name, 'last_name' => '-',   'facility_id' => $other->id,    'department' => 'Cardiology', 'status' => 'active', 'staff_category' => 'clinical']);

        $html = $this->asStaff($admin, $facility)
            ->get(route('portals.facility.admin.departments'))
            ->assertOk()
            ->assertSee('Cardiology')
            ->assertSee('Emergency &amp; Triage', false)
            ->assertSee('Dr Fotso')
            ->assertSee('Nurse Nkeng')
            ->assertDontSee('Dr Gone')
            ->assertDontSee('Dr Elsewhere')
            ->assertSee(__('facility_admin.departments_free_text'))
            ->getContent();

        // Two active cardiologists here; the inactive one and the neighbour's are not counted.
        $this->assertMatchesRegularExpression('/data-department="Cardiology">.*?data-department-count="2"/s', $html);
        $this->assertMatchesRegularExpression('/data-department="__unassigned">.*?data-department-count="1"/s', $html);
    }

    public function test_departments_page_is_empty_not_invented_when_no_profile_carries_one(): void
    {
        [$admin, $facility] = $this->staff('facility_admin');

        $this->asStaff($admin, $facility)
            ->get(route('portals.facility.admin.departments'))
            ->assertOk()
            ->assertSee(__('facility_admin.departments_empty'))
            ->assertDontSee('data-department=', false);
    }

    public function test_overview_links_to_both_pages_and_the_sidebars_do_too(): void
    {
        [$admin, $facility] = $this->staff('facility_admin');

        $this->asStaff($admin, $facility)
            ->get(route('portals.facility.admin.overview'))
            ->assertOk()
            ->assertSee(route('portals.facility.admin.invoices'))
            ->assertSee(route('portals.facility.admin.departments'));
    }
}
