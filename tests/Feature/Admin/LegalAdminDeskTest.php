<?php

namespace Tests\Feature\Admin;

use App\Models\AccountClosureRequest;
use App\Models\Patient;
use App\Models\PrivacyComplaint;
use App\Models\Role;
use App\Models\User;
use App\Services\Identity\HealthIdGeneratorService;
use Database\Seeders\AccountCategoriesSeeder;
use Database\Seeders\DashboardProfilesSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The legal & compliance desk.
 *
 * LegalAdminController shipped with nine admin methods, five Blade views and
 * all 94 of its EN/FR strings — and no routes, so `route:list --path=legal`
 * returned only the two public pages and every admin screen here was
 * unreachable. The closures table even carried an empty `row-actions` cell
 * where its approve/reject buttons belonged.
 *
 * The tests below pin the two halves that were missing: that the routes exist
 * and are platform-gated, and that a review actually writes — `reviewed_by` is
 * a `uuid` with a foreign key onto `users.id`, and the actor id has to be the
 * signed-in admin's UUID rather than any friendly string, or Postgres answers
 * 22P02 and the action 500s.
 */
class LegalAdminDeskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private function platformAdmin(): User
    {
        $this->seed(AccountCategoriesSeeder::class);
        $this->seed(DashboardProfilesSeeder::class);
        $this->seed(RolesSeeder::class);

        $user = User::factory()->create(['status' => 'active', 'primary_facility_id' => null]);
        $user->role_id = Role::where('name', 'super_admin')->firstOrFail()->id;
        $user->save();

        return $user;
    }

    private function patient(): Patient
    {
        return app(HealthIdGeneratorService::class)->generate('CM', fn (string $healthId) => Patient::create([
            'health_id'           => $healthId,
            'first_name'          => 'Closure',
            'last_name'           => 'Subject',
            'date_of_birth'       => '1988-03-09',
            'sex'                 => 'female',
            'identity_status'     => 'provisional',
            'verification_status' => 'unverified',
        ]));
    }

    private function pendingClosure(): AccountClosureRequest
    {
        return AccountClosureRequest::create([
            'patient_id'             => $this->patient()->id,
            'reason'                 => 'Moving abroad.',
            'status'                 => 'pending',
            'data_delete_requested'  => true,
            'data_export_requested'  => false,
        ]);
    }

    // ─── 1. The routes exist at all ─────────────────────────────────────────

    public function test_every_admin_method_on_the_controller_has_a_route(): void
    {
        $named = collect(Route::getRoutes())->map(fn ($r) => $r->getName())->filter()->all();

        foreach ([
            'portals.admin.legal.index',
            'portals.admin.legal.store',
            'portals.admin.legal.show',
            'portals.admin.legal.versions.publish',
            'portals.admin.legal.closures',
            'portals.admin.legal.closures.review',
            'portals.admin.legal.complaints',
            'portals.admin.legal.complaints.resolve',
            'portals.admin.legal.minor_transitions',
        ] as $name) {
            $this->assertContains($name, $named, "route {$name} is not registered");
        }
    }

    public function test_the_desk_is_platform_gated(): void
    {
        $this->platformAdmin();                       // seeds roles
        $outsider = User::factory()->create(['status' => 'active']);
        $closure  = $this->pendingClosure();

        $this->actingAs($outsider)->get(route('portals.admin.legal.closures'))->assertForbidden();
        $this->actingAs($outsider)
            ->post(route('portals.admin.legal.closures.review', $closure->id), ['action' => 'approve'])
            ->assertForbidden();

        $this->assertSame('pending', $closure->fresh()->status);
    }

    // ─── 2. A review actually writes ────────────────────────────────────────

    public function test_approving_a_closure_records_the_admins_uuid(): void
    {
        $admin   = $this->platformAdmin();
        $closure = $this->pendingClosure();

        $this->actingAs($admin)
            ->from(route('portals.admin.legal.closures'))
            ->post(route('portals.admin.legal.closures.review', $closure->id), [
                'action'      => 'approve',
                'review_note' => 'Identity confirmed in person.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $closure->refresh();

        $this->assertSame('approved', $closure->status);
        $this->assertSame('Identity confirmed in person.', $closure->review_note);
        $this->assertNotNull($closure->reviewed_at);

        // The column is a uuid with an FK onto users.id. A friendly string here
        // is SQLSTATE 22P02, not a cosmetic problem.
        $this->assertSame((string) $admin->id, (string) $closure->reviewed_by);
        $this->assertTrue(\Illuminate\Support\Str::isUuid((string) $closure->reviewed_by));
    }

    public function test_rejecting_records_the_same_way(): void
    {
        $admin   = $this->platformAdmin();
        $closure = $this->pendingClosure();

        $this->actingAs($admin)
            ->post(route('portals.admin.legal.closures.review', $closure->id), ['action' => 'reject'])
            ->assertRedirect();

        $closure->refresh();
        $this->assertSame('rejected', $closure->status);
        $this->assertSame((string) $admin->id, (string) $closure->reviewed_by);
    }

    // ─── 3. The list renders, with the controls that were missing ───────────

    public function test_the_closures_page_renders_its_approve_and_reject_controls(): void
    {
        $admin = $this->platformAdmin();
        $this->pendingClosure();

        $response = $this->actingAs($admin)->get(route('portals.admin.legal.closures'));

        $response->assertOk();
        $response->assertSee(route('portals.admin.legal.closures.review', AccountClosureRequest::first()->id));
        $response->assertSee(__('public.adm_legal_closures_btn_approve'));
        $response->assertSee(__('public.adm_legal_closures_btn_reject'));
    }

    /**
     * `account_closure_requests` is shared with the patient-facing self-service
     * deletion flow, which moves rows through pending → cancelled/completed/
     * purged on the patient's OWN authority. Those are not this desk's to
     * approve, and an approve button on a row the patient already cancelled
     * would be a way to un-cancel it.
     */
    public function test_a_request_the_patient_already_settled_offers_no_admin_action(): void
    {
        $admin   = $this->platformAdmin();
        $closure = $this->pendingClosure();
        $closure->update(['status' => 'cancelled']);

        $response = $this->actingAs($admin)->get(route('portals.admin.legal.closures'));

        $response->assertOk();
        $response->assertDontSee(route('portals.admin.legal.closures.review', $closure->id));
    }

    // ─── 4. Complaints ──────────────────────────────────────────────────────

    public function test_resolving_a_complaint_records_the_account_of_what_was_done(): void
    {
        $admin     = $this->platformAdmin();
        $complaint = PrivacyComplaint::create([
            'patient_id'  => $this->patient()->id,
            'complaint_type' => 'unauthorised_access',
            'description' => 'A clinician I have never met opened my record.',
            'status'      => 'open',
        ]);

        $this->actingAs($admin)
            ->post(route('portals.admin.legal.complaints.resolve', $complaint->id), [
                'resolution' => 'Access log reviewed; the read was a break-glass event and the patient was notified.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $complaint->refresh();
        $this->assertSame('resolved', $complaint->status);
        $this->assertNotNull($complaint->resolved_at);
        $this->assertStringContainsString('break-glass', $complaint->resolution);
    }

    public function test_a_resolution_that_says_nothing_is_refused(): void
    {
        $admin     = $this->platformAdmin();
        $complaint = PrivacyComplaint::create([
            'patient_id'     => $this->patient()->id,
            'complaint_type' => 'unauthorised_access',
            'description'    => 'A clinician I have never met opened my record.',
            'status'         => 'open',
        ]);

        $this->actingAs($admin)
            ->post(route('portals.admin.legal.complaints.resolve', $complaint->id), ['resolution' => 'ok'])
            ->assertSessionHasErrors('resolution');

        $this->assertSame('open', $complaint->fresh()->status);
    }
}
