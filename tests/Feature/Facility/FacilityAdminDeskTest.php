<?php

namespace Tests\Feature\Facility;

use App\Models\Appointment;
use App\Models\AuditEvent;
use App\Models\Bed;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Invoice;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Models\Ward;
use App\Services\Queue\PatientQueueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The facility administrator's desk (/facility/team/overview and
 * /facility/team/members/{user}) — see docs/HOSPITAL_ADMIN_DASHBOARD_AUDIT.md.
 *
 * Three things these tests hold:
 *   1. every number on the overview is a count over THIS facility's rows —
 *      another facility's appointments, orders, invoices and staff never
 *      leak in, and a figure with no measurement says so instead of "0";
 *   2. the desk answers the role → surface map: facility admins and the CEO
 *      tier reach it, clinicians and pharmacy staff are refused (403), and a
 *      member of another facility is a 404;
 *   3. the two roster writes (change role, end/reopen posting) touch only the
 *      allow-listed roles, never the acting admin, and each leaves an
 *      AuditEvent with before/after state.
 */
class FacilityAdminDeskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /** @return array{0: User, 1: Facility} a professional posted at one facility */
    private function staff(string $roleName, ?Facility $facility = null): array
    {
        $facility ??= Facility::factory()->create(['type' => 'hospital', 'name' => 'Hôpital Laquintinie']);
        $role = Role::where('name', $roleName)->firstOrFail();

        $user = User::factory()->create([
            'role_id'             => $role->id,
            'primary_facility_id' => $facility->id,
            'status'              => 'active',
        ]);

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

    /** Read a `data-kpi="name"` value off the overview page. */
    private function kpi(string $html, string $name): ?string
    {
        return preg_match('/data-kpi="' . preg_quote($name, '/') . '">([^<]*)</', $html, $m) ? trim($m[1]) : null;
    }

    // ── Overview ─────────────────────────────────────────────────────────

    public function test_overview_counts_only_this_facilitys_rows_and_never_invents_a_number(): void
    {
        [$admin, $facility] = $this->staff('facility_admin');
        [, $other]          = $this->staff('facility_admin');

        // Appointments today: two here (one no-show), one elsewhere.
        Appointment::factory()->create(['facility_id' => $facility->id, 'scheduled_at' => now()->setTime(9, 0)]);
        Appointment::factory()->create(['facility_id' => $facility->id, 'scheduled_at' => now()->setTime(10, 0), 'status' => 'no_show', 'no_show_at' => now()]);
        Appointment::factory()->create(['facility_id' => $other->id, 'scheduled_at' => now()->setTime(11, 0)]);
        Appointment::factory()->create(['facility_id' => $facility->id, 'scheduled_at' => now()->addDays(3)]); // not today

        // Lab: one open here, one resulted today (30 min turnaround), one elsewhere.
        LabOrder::factory()->create(['facility_id' => $facility->id, 'status' => 'ordered', 'resulted_at' => null, 'collected_at' => null]);
        LabOrder::factory()->create(['facility_id' => $facility->id, 'status' => 'resulted', 'ordered_at' => now()->subMinutes(30), 'resulted_at' => now()]);
        LabOrder::factory()->create(['facility_id' => $other->id, 'status' => 'ordered', 'resulted_at' => null]);

        // Billing: 2 500 F collected today here, 1 000 F outstanding, nothing of the neighbour's.
        Invoice::factory()->create(['facility_id' => $facility->id, 'status' => 'paid', 'paid_amount' => 2500, 'balance_amount' => 0, 'paid_at' => now()]);
        Invoice::factory()->create(['facility_id' => $facility->id, 'status' => 'issued', 'paid_amount' => 0, 'balance_amount' => 1000]);
        Invoice::factory()->create(['facility_id' => $other->id, 'status' => 'paid', 'paid_amount' => 9999, 'balance_amount' => 0, 'paid_at' => now()]);

        // Team: the admin plus a doctor here; the other facility's admin is not counted.
        $this->staff('doctor', $facility);

        $html = $this->asStaff($admin, $facility)
            ->get(route('portals.facility.admin.overview'))
            ->assertOk()
            ->assertSee('Hôpital Laquintinie')
            ->getContent();

        $this->assertSame('2', $this->kpi($html, 'appointments_today'));
        $this->assertSame('1', $this->kpi($html, 'appointments_no_show'));
        $this->assertSame('1', $this->kpi($html, 'lab_open'));
        $this->assertSame('1', $this->kpi($html, 'lab_resulted_today'));
        $this->assertSame('30 min', $this->kpi($html, 'lab_turnaround'));
        $this->assertSame('2 500 FCFA', $this->kpi($html, 'collected_today'));
        $this->assertSame('1 000 FCFA', $this->kpi($html, 'outstanding'));
        $this->assertSame('2', $this->kpi($html, 'team_active'));

        // No queue ticket has been called today: the wait is "not measured",
        // not "0 min". No ward exists: no occupancy tile at all.
        $this->assertSame('0', $this->kpi($html, 'queue_checked_in'));
        $this->assertSame(__('facility_admin.kpi_no_measure'), $this->kpi($html, 'queue_median_wait'));
        $this->assertNull($this->kpi($html, 'occupancy'));
        $this->assertStringContainsString(__('facility_admin.beds_none'), $html);
    }

    public function test_overview_reads_the_queue_and_the_wards_when_they_exist(): void
    {
        [$admin, $facility] = $this->staff('facility_admin');
        [$nurse]            = $this->staff('nurse', $facility);
        $patient            = Patient::factory()->create();

        app(PatientQueueService::class)->checkIn([
            'patient' => $patient, 'facility_id' => $facility->id, 'actor_id' => $nurse->id, 'priority_level' => 5,
        ]);

        $ward = Ward::create(['facility_id' => $facility->id, 'name' => 'Maternité A', 'ward_type' => 'maternity', 'total_beds' => 2]);
        Bed::create(['ward_id' => $ward->id, 'bed_number' => 'M-01', 'status' => 'occupied']);
        Bed::create(['ward_id' => $ward->id, 'bed_number' => 'M-02', 'status' => 'available']);

        $html = $this->asStaff($admin, $facility)
            ->get(route('portals.facility.admin.overview'))
            ->assertOk()
            ->getContent();

        $this->assertSame('1', $this->kpi($html, 'queue_checked_in'));
        $this->assertSame('1', $this->kpi($html, 'queue_waiting'));
        $this->assertSame('1', $this->kpi($html, 'wards'));
        $this->assertSame('2', $this->kpi($html, 'beds'));
        $this->assertSame('1', $this->kpi($html, 'beds_occupied'));
        $this->assertSame('50 %', $this->kpi($html, 'occupancy'));
    }

    public function test_the_desk_answers_the_role_map(): void
    {
        // 200: holds facility_team. 403: inside the facility/team portal
        // bucket but without the capability (pharmacist). 302: not in the
        // bucket at all — portal.access sends clinicians to /facility before
        // the capability layer runs (same shape as StaffRoleMatrixTest).
        foreach (['facility_admin' => 200, 'hospital_admin' => 200, 'facility_ceo' => 200, 'branch_admin' => 200,
                  'pharmacy_manager' => 200, 'pharmacist' => 403, 'doctor' => 302, 'nurse' => 302] as $role => $status) {
            [$user, $facility] = $this->staff($role);

            $this->asStaff($user, $facility)
                ->get(route('portals.facility.admin.overview'))
                ->assertStatus($status, "{$role} should get {$status} on the overview");
        }
    }

    public function test_team_page_links_every_member_to_their_page(): void
    {
        [$admin, $facility] = $this->staff('facility_admin');
        [$doctor]           = $this->staff('doctor', $facility);

        $this->asStaff($admin, $facility)
            ->get(route('portals.facility.team'))
            ->assertOk()
            ->assertSee(route('portals.facility.admin.member', $doctor->id), false);
    }

    // ── Member page ──────────────────────────────────────────────────────

    public function test_a_member_of_another_facility_is_a_404_not_a_403(): void
    {
        [$admin, $facility] = $this->staff('facility_admin');
        [$stranger]         = $this->staff('doctor');

        $this->asStaff($admin, $facility)
            ->get(route('portals.facility.admin.member', $stranger->id))
            ->assertNotFound();

        $this->asStaff($admin, $facility)
            ->post(route('portals.facility.admin.member.deactivate', $stranger->id))
            ->assertNotFound();

        $this->assertTrue($stranger->facilityRoleAssignments()->active()->exists());
    }

    public function test_member_page_shows_role_here_credentials_and_offers_the_actions(): void
    {
        [$admin, $facility] = $this->staff('facility_admin');
        [$doctor]           = $this->staff('doctor', $facility);

        $doctor->providerCredentials()->create([
            'credential_type' => 'medical_license', 'credential_number' => 'ONMC-4471',
            'issuing_body' => 'Ordre National des Médecins du Cameroun', 'status' => 'active',
            'issued_date' => now()->subYear(), 'expiry_date' => now()->addYear(), 'verified_at' => now(),
        ]);

        $this->asStaff($admin, $facility)
            ->get(route('portals.facility.admin.member', $doctor->id))
            ->assertOk()
            ->assertSee('ONMC-4471')
            ->assertSee(__('facility_admin.credential_verified'))
            ->assertSee(route('portals.facility.admin.member.role', $doctor->id), false)
            ->assertSee(route('portals.facility.admin.member.deactivate', $doctor->id), false);
    }

    public function test_change_role_rewrites_the_posting_and_audits_before_and_after(): void
    {
        [$admin, $facility] = $this->staff('facility_admin');
        [$doctor]           = $this->staff('doctor', $facility);
        $nurseRole          = Role::where('name', 'nurse')->firstOrFail();

        $this->asStaff($admin, $facility)
            ->post(route('portals.facility.admin.member.role', $doctor->id), ['role' => 'nurse'])
            ->assertRedirect(route('portals.facility.admin.member', $doctor->id))
            ->assertSessionHas('success');

        $this->assertSame('nurse', $doctor->fresh()->roleAtFacility($facility->id)?->name);
        $this->assertSame($nurseRole->id, $doctor->fresh()->role_id, 'home facility: the account role follows');
        $this->assertSame(1, FacilityRoleAssignment::where('user_id', $doctor->id)->where('is_active', true)->count());

        $event = AuditEvent::where('action_type', 'facility_member_role_changed')->where('resource_id', $doctor->id)->first();
        $this->assertNotNull($event);
        $this->assertSame($facility->id, $event->facility_id);
        $this->assertSame($admin->id, $event->actor_id);
        $this->assertSame('doctor', data_get($event->before_state, 'role'));
        $this->assertSame('nurse', data_get($event->after_state, 'role'));
    }

    public function test_change_role_cannot_name_an_administration_role_nor_touch_self_or_another_admin(): void
    {
        [$admin, $facility] = $this->staff('facility_admin');
        [$doctor]           = $this->staff('doctor', $facility);
        [$coAdmin]          = $this->staff('hospital_admin', $facility);

        // The allow-list is a validation rule: an admin role is rejected before any write.
        $this->asStaff($admin, $facility)
            ->from(route('portals.facility.admin.member', $doctor->id))
            ->post(route('portals.facility.admin.member.role', $doctor->id), ['role' => 'facility_admin'])
            ->assertSessionHasErrors('role');
        $this->assertSame('doctor', $doctor->fresh()->roleAtFacility($facility->id)?->name);

        // Never yourself.
        $this->asStaff($admin, $facility)
            ->post(route('portals.facility.admin.member.role', $admin->id), ['role' => 'nurse'])
            ->assertSessionHas('error');
        $this->assertSame('facility_admin', $admin->fresh()->roleAtFacility($facility->id)?->name);

        // Never a colleague whose role is outside the allow-list.
        $this->asStaff($admin, $facility)
            ->post(route('portals.facility.admin.member.deactivate', $coAdmin->id))
            ->assertSessionHas('error');
        $this->assertTrue($coAdmin->facilityRoleAssignments()->active()->exists());
        $this->assertSame('active', $coAdmin->fresh()->status);
    }

    public function test_deactivate_ends_the_posting_suspends_a_home_only_account_and_reactivate_reverses_it(): void
    {
        [$admin, $facility] = $this->staff('facility_admin');
        [$doctor]           = $this->staff('doctor', $facility);

        $this->asStaff($admin, $facility)
            ->post(route('portals.facility.admin.member.deactivate', $doctor->id))
            ->assertRedirect(route('portals.facility.admin.member', $doctor->id))
            ->assertSessionHas('success', __('facility_admin.deactivated_and_suspended'));

        $doctor->refresh();
        $this->assertFalse($doctor->facilityRoleAssignments()->active()->exists());
        $this->assertSame('suspended', $doctor->status, 'home facility and nowhere else: login must be refused');

        $event = AuditEvent::where('action_type', 'facility_member_deactivated')->where('resource_id', $doctor->id)->first();
        $this->assertNotNull($event);
        $this->assertTrue((bool) data_get($event->after_state, 'account_suspended'));

        // The page still opens for a deactivated member, and offers reactivation.
        $this->asStaff($admin, $facility)
            ->get(route('portals.facility.admin.member', $doctor->id))
            ->assertOk()
            ->assertSee(route('portals.facility.admin.member.reactivate', $doctor->id), false)
            ->assertDontSee(route('portals.facility.admin.member.deactivate', $doctor->id), false);

        $this->asStaff($admin, $facility)
            ->post(route('portals.facility.admin.member.reactivate', $doctor->id))
            ->assertSessionHas('success');

        $doctor->refresh();
        $this->assertSame('doctor', $doctor->roleAtFacility($facility->id)?->name);
        $this->assertTrue($doctor->facilityRoleAssignments()->active()->where('facility_id', $facility->id)->exists());
        $this->assertSame('active', $doctor->status);
        $this->assertDatabaseHas('audit_events', ['action_type' => 'facility_member_reactivated', 'resource_id' => $doctor->id]);
    }

    public function test_deactivate_keeps_the_account_of_a_clinician_who_also_works_elsewhere(): void
    {
        [$admin, $facility] = $this->staff('facility_admin');
        [$doctor]           = $this->staff('doctor', $facility);
        $elsewhere          = Facility::factory()->create(['type' => 'clinic']);
        FacilityRoleAssignment::create([
            'user_id' => $doctor->id, 'facility_id' => $elsewhere->id, 'role_id' => $doctor->role_id,
            'is_active' => true, 'assigned_at' => now(),
        ]);

        $this->asStaff($admin, $facility)
            ->post(route('portals.facility.admin.member.deactivate', $doctor->id))
            ->assertSessionHas('success', __('facility_admin.deactivated'));

        $doctor->refresh();
        $this->assertSame('active', $doctor->status);
        $this->assertFalse($doctor->facilityRoleAssignments()->active()->where('facility_id', $facility->id)->exists());
        $this->assertTrue($doctor->facilityRoleAssignments()->active()->where('facility_id', $elsewhere->id)->exists());
    }

    public function test_a_user_with_no_facility_context_is_refused_not_shown_a_strangers_desk(): void
    {
        $role = Role::where('name', 'facility_admin')->firstOrFail();
        $stray = User::factory()->create(['role_id' => $role->id, 'primary_facility_id' => null, 'status' => 'active']);

        // portal.access aborts 403 (no role at any facility) before the controller's own 403.
        $this->actingAs($stray)->get(route('portals.facility.admin.overview'))->assertStatus(403);
    }
}
