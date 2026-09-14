<?php

namespace Tests\Feature\Admin;

use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\FacilityStaffInvite;
use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use App\Notifications\FacilityStaffInviteNotification;
use Database\Seeders\AccountCategoriesSeeder;
use Database\Seeders\DashboardProfilesSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Lead → tenant conversion from the admin leads desk (P-6,
 * LeadConversionService). A platform admin turns an organisation signup
 * into a `facilities` tenant with a linked (or new) public listing and the
 * SAME staff invite the team page sends; no user is created until the
 * invite is accepted. Converting is idempotent, platform-tier only, and
 * never takes over a listing another tenant already owns.
 */
class LeadConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccountCategoriesSeeder::class);
        $this->seed(DashboardProfilesSeeder::class);
        $this->seed(RolesSeeder::class);
        $this->withoutMiddleware(ThrottleRequests::class);
        Notification::fake();
    }

    private function platformAdmin(): User
    {
        $role  = Role::where('name', 'super_admin')->firstOrFail();
        $admin = User::factory()->create(['status' => 'active', 'primary_facility_id' => null]);
        $admin->role_id = $role->id;
        $admin->save();

        return $admin;
    }

    private function lead(array $overrides = []): Lead
    {
        return Lead::create(array_merge([
            'name'              => 'Awa Mballa',
            'email'             => 'awa@pharmacie-akwa.cm',
            'phone'             => '+237699000111',
            'organization_name' => 'Pharmacie Akwa',
            'organization_type' => 'pharmacy',
            'source'            => 'request_demo',
            'status'            => 'qualified',
        ], $overrides));
    }

    private function unlinkedListing(array $overrides = []): CareFacility
    {
        return CareFacility::create(array_merge([
            'facility_id' => null, 'facility_name' => 'Pharmacie Akwa', 'facility_type' => 'pharmacy',
            'listing_status' => 'active', 'country_code' => 'CM', 'region' => 'Littoral', 'city' => 'Douala',
            'address' => 'Rue Joss, Akwa', 'phone_primary' => 'N/A', 'source_system' => 'minsante_registry',
        ], $overrides));
    }

    private function asAdmin(User $admin)
    {
        return $this->actingAs($admin)->withSession(['mfa.verified' => true]);
    }

    public function test_a_platform_admin_converts_a_pharmacy_lead_linking_an_unlinked_listing_and_issuing_the_real_invite(): void
    {
        $admin   = $this->platformAdmin();
        $lead    = $this->lead();
        $listing = $this->unlinkedListing();
        $other   = $this->unlinkedListing(['facility_name' => 'Pharmacie de Bonabéri', 'city' => 'Douala']);
        $this->unlinkedListing(['facility_name' => 'Pharmacie Bastos', 'city' => 'Yaoundé']);

        // The form offers only unlinked pharmacies in that city, exact name first.
        $page = $this->asAdmin($admin)->get(route('portals.admin.leads.convert', ['lead' => $lead, 'type' => 'pharmacy', 'city' => 'douala', 'q' => 'Pharmacie Akwa']));
        $page->assertOk()
            ->assertSee(__('leads.conversion.heading'))
            ->assertSee('Pharmacie Akwa')
            ->assertSee('Pharmacie de Bonabéri')
            ->assertDontSee('Pharmacie Bastos')
            ->assertSee(__('team.roles.pharmacy_manager'))
            ->assertSeeInOrder(['value="' . $listing->id . '"', 'value="' . $other->id . '"'], false);

        $this->asAdmin($admin)->post(route('portals.admin.leads.convert.store', $lead), [
            'facility_type' => 'pharmacy', 'name' => 'Pharmacie Akwa', 'city' => 'Douala', 'region' => 'Littoral',
            'listing_id' => $listing->id,
        ])->assertRedirect(route('portals.admin.leads.convert', $lead))
          ->assertSessionHasNoErrors()
          ->assertSessionHas('invite_link')
          ->assertSessionHas('invite_link_for', 'awa@pharmacie-akwa.cm');

        $lead->refresh();
        $this->assertSame('converted', $lead->status);
        $this->assertNotNull($lead->converted_facility_id);
        $this->assertNotNull($lead->converted_at);
        $this->assertSame($admin->id, $lead->converted_by);

        $facility = Facility::withoutGlobalScopes()->findOrFail($lead->converted_facility_id);
        $this->assertSame('Pharmacie Akwa', $facility->name);
        $this->assertSame('pharmacy', $facility->type);

        $this->assertSame($facility->id, $listing->refresh()->facility_id, 'the chosen listing is linked');
        $this->assertNull($other->refresh()->facility_id);
        $this->assertSame(1, CareFacility::where('facility_id', $facility->id)->count(), 'no second listing was created');

        $invite = FacilityStaffInvite::where('facility_id', $facility->id)->firstOrFail();
        $this->assertSame('awa@pharmacie-akwa.cm', $invite->email);
        $this->assertSame('pharmacy_manager', $invite->role->name);
        $this->assertSame($admin->id, $invite->invited_by);
        $this->assertTrue($invite->isUsable());
        $this->assertSame(0, User::withoutGlobalScopes()->where('email', 'awa@pharmacie-akwa.cm')->count(), 'no user until acceptance');

        // The link in the session is the real token: the public page accepts it.
        $link = session('invite_link');
        $this->assertStringContainsString('/invite/', $link);
        $this->get($link)->assertOk()->assertDontSee(__('team.accept_failed'));

        Notification::assertSentOnDemand(FacilityStaffInviteNotification::class, fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === 'awa@pharmacie-akwa.cm');

        $this->assertDatabaseHas('audit_events', [
            'actor_id' => $admin->id, 'facility_id' => $facility->id,
            'action_type' => 'lead_converted_to_facility', 'resource_type' => 'Lead', 'resource_id' => $lead->id,
        ]);
        $this->assertDatabaseHas('facility_update_audits', [
            'facility_id' => $listing->id, 'field_changed' => 'facility_id', 'new_value' => $facility->id,
            'source' => 'admin_lead_conversion', 'requires_review' => false,
        ]);

        // The desk now shows the tenant and offers no second conversion.
        $this->asAdmin($admin)->get(route('portals.admin.leads.convert', $lead))
            ->assertOk()->assertSee(__('leads.conversion.converted_title'))->assertDontSee(__('leads.conversion.btn_convert'));
        $this->asAdmin($admin)->get(route('portals.admin.leads'))
            ->assertOk()->assertSee(__('leads.statuses.converted'))->assertSee(__('leads.conversion.action_view_tenant'));
    }

    public function test_a_second_conversion_is_refused_and_the_status_cannot_be_moved_by_hand(): void
    {
        $admin = $this->platformAdmin();
        $lead  = $this->lead();

        $post = fn () => $this->asAdmin($admin)->post(route('portals.admin.leads.convert.store', $lead), [
            'facility_type' => 'pharmacy', 'name' => 'Pharmacie Akwa', 'city' => 'Douala', 'address' => 'Rue Joss',
        ]);

        $post()->assertSessionHasNoErrors();
        $tenantId = $lead->refresh()->converted_facility_id;

        $post()->assertRedirect(route('portals.admin.leads.convert', $lead))
            ->assertSessionHas('error', __('leads.conversion.error_already_converted'));

        $this->assertSame($tenantId, $lead->refresh()->converted_facility_id);
        $this->assertSame(1, Facility::withoutGlobalScopes()->where('name', 'Pharmacie Akwa')->count());
        $this->assertSame(1, FacilityStaffInvite::where('email', 'awa@pharmacie-akwa.cm')->count());

        $this->asAdmin($admin)->post(route('portals.admin.leads.status', $lead), ['status' => 'lost'])
            ->assertSessionHas('error');
        $this->assertSame('converted', $lead->refresh()->status);
    }

    public function test_a_facility_admin_is_refused_with_403(): void
    {
        $facility = Facility::factory()->create(['type' => 'hospital']);
        $role     = Role::where('name', 'facility_admin')->firstOrFail();
        $user     = User::factory()->create(['role_id' => $role->id, 'primary_facility_id' => $facility->id, 'status' => 'active']);
        FacilityRoleAssignment::create(['user_id' => $user->id, 'facility_id' => $facility->id, 'role_id' => $role->id, 'is_active' => true, 'assigned_at' => now()]);
        $lead = $this->lead();

        $this->actingAs($user)->withSession(['mfa.verified' => true, 'active_facility_id' => $facility->id])
            ->get(route('portals.admin.leads.convert', $lead))->assertForbidden();
        $this->actingAs($user)->withSession(['mfa.verified' => true, 'active_facility_id' => $facility->id])
            ->post(route('portals.admin.leads.convert.store', $lead), [
                'facility_type' => 'pharmacy', 'name' => 'Pharmacie Akwa', 'city' => 'Douala', 'address' => 'Rue Joss',
            ])->assertForbidden();

        $this->assertNull($lead->refresh()->converted_facility_id);
        $this->assertSame(0, FacilityStaffInvite::count());
    }

    public function test_a_listing_that_already_belongs_to_a_tenant_is_never_taken_over(): void
    {
        $admin = $this->platformAdmin();
        $lead  = $this->lead();
        $owner = Facility::factory()->create(['type' => 'pharmacy', 'name' => 'Pharmacie Akwa']);
        $taken = $this->unlinkedListing(['facility_id' => $owner->id]);

        // Not offered on the form…
        $this->asAdmin($admin)->get(route('portals.admin.leads.convert', ['lead' => $lead, 'type' => 'pharmacy', 'city' => 'Douala']))
            ->assertOk()->assertDontSee('value="' . $taken->id . '"', false);

        // …and refused if posted anyway, with nothing written.
        $this->asAdmin($admin)->post(route('portals.admin.leads.convert.store', $lead), [
            'facility_type' => 'pharmacy', 'name' => 'Pharmacie Akwa', 'city' => 'Douala', 'listing_id' => $taken->id,
        ])->assertRedirect(route('portals.admin.leads.convert', $lead))
          ->assertSessionHas('error', __('leads.conversion.error_listing_linked'));

        $this->assertSame($owner->id, $taken->refresh()->facility_id);
        $this->assertNull($lead->refresh()->converted_facility_id);
        $this->assertSame('qualified', $lead->status);
        $this->assertSame(1, Facility::withoutGlobalScopes()->where('name', 'Pharmacie Akwa')->count(), 'no tenant was created');
        $this->assertSame(0, FacilityStaffInvite::count());
    }

    public function test_a_hospital_lead_creates_a_new_self_reported_listing_and_a_facility_admin_invite_that_can_be_accepted(): void
    {
        $admin = $this->platformAdmin();
        $lead  = $this->lead(['organization_name' => 'Clinique Bonapriso', 'organization_type' => 'facility', 'email' => 'dir@bonapriso.cm']);

        $this->asAdmin($admin)->post(route('portals.admin.leads.convert.store', $lead), [
            'facility_type' => 'clinic', 'name' => 'Clinique Bonapriso', 'city' => 'Douala', 'region' => 'Littoral',
            'address' => 'Rue Njo-Njo, Bonapriso',
        ])->assertSessionHasNoErrors();

        $facility = Facility::withoutGlobalScopes()->findOrFail($lead->refresh()->converted_facility_id);
        $listing  = CareFacility::where('facility_id', $facility->id)->firstOrFail();
        $this->assertSame('clinic', $listing->facility_type);
        $this->assertSame('self_reported', $listing->verification_status);
        $this->assertSame('portal', $listing->source_system);
        $this->assertSame('Rue Njo-Njo, Bonapriso', $listing->address);
        $this->assertSame('+237699000111', $listing->phone_primary);
        $this->assertNull($listing->latitude);

        $invite = FacilityStaffInvite::where('facility_id', $facility->id)->firstOrFail();
        $this->assertSame('facility_admin', $invite->role->name);
        $this->assertNotContains('facility_admin', FacilityStaffInvite::INVITABLE_ROLES, 'a facility admin still cannot mint this role');

        // Redeem it: the account is created, bound to the tenant, in that role.
        $link = session('invite_link');
        $this->post($link, [
            'name' => 'Dr Ngo Bassong', 'password' => 'Secret-Passw0rd', 'confirm_password' => 'Secret-Passw0rd', 'accept_terms' => '1',
        ])->assertRedirect(route('login'));

        $user = User::withoutGlobalScopes()->where('email', 'dir@bonapriso.cm')->firstOrFail();
        $this->assertSame($facility->id, $user->primary_facility_id);
        $this->assertSame('facility_admin', $user->role->name);
        $this->assertTrue($invite->refresh()->isAccepted());
    }

    public function test_a_required_address_and_an_email_less_lead_are_refused(): void
    {
        $admin = $this->platformAdmin();
        $lead  = $this->lead();

        $this->asAdmin($admin)->from(route('portals.admin.leads.convert', $lead))
            ->post(route('portals.admin.leads.convert.store', $lead), ['facility_type' => 'pharmacy', 'name' => 'X', 'city' => 'Douala'])
            ->assertSessionHasErrors('address');

        $silent = $this->lead(['email' => null, 'organization_name' => 'Sans Mail']);
        $this->asAdmin($admin)->post(route('portals.admin.leads.convert.store', $silent), [
            'facility_type' => 'pharmacy', 'name' => 'Sans Mail', 'city' => 'Douala', 'address' => 'x',
        ])->assertSessionHas('error', __('leads.conversion.error_no_email'));

        $this->assertSame(0, FacilityStaffInvite::count());
        $this->assertSame(0, Facility::withoutGlobalScopes()->whereIn('name', ['X', 'Sans Mail'])->count());
    }
}
