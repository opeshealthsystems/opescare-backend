<?php

namespace Tests\Feature\Imports;

use App\Models\AuditEvent;
use App\Models\Facility;
use App\Models\FacilityGoLiveReadiness;
use App\Models\FacilityRoleAssignment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Group D import — the platform onboarding portal at /admin/onboarding. */
class OnboardingPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function platformAdmin(): User
    {
        return User::factory()->create(['role_id' => Role::where('name', 'super_admin')->firstOrFail()->id]);
    }

    public function test_onboarding_list_is_200_for_a_platform_admin_and_writes_nothing(): void
    {
        Facility::factory()->create(['name' => 'Hôpital de District de Bonassama']);
        $events = AuditEvent::count();

        $this->actingAs($this->platformAdmin())->get('/admin/onboarding')
            ->assertOk()->assertSee('Hôpital de District de Bonassama');

        $this->assertSame(0, FacilityGoLiveReadiness::count(), 'listing facilities must not create readiness rows');
        $this->assertSame($events, AuditEvent::count());
    }

    public function test_onboarding_is_403_for_a_facility_admin(): void
    {
        $facility = Facility::factory()->create();
        $role = Role::where('name', 'facility_admin')->firstOrFail();
        $user = User::factory()->create(['role_id' => $role->id, 'primary_facility_id' => $facility->id, 'status' => 'active']);
        FacilityRoleAssignment::create([
            'user_id' => $user->id, 'facility_id' => $facility->id, 'role_id' => $role->id,
            'is_active' => true, 'assigned_at' => now(),
        ]);

        $this->actingAs($user)->withSession(['active_facility_id' => $facility->id])->get('/admin/onboarding')->assertForbidden();
        $this->actingAs($user)->withSession(['active_facility_id' => $facility->id])->get("/admin/onboarding/{$facility->id}")->assertForbidden();
        $this->actingAs($user)->withSession(['active_facility_id' => $facility->id])
            ->post("/admin/onboarding/{$facility->id}/mark", ['item' => 'facility_verified', 'complete' => 1])->assertForbidden();
    }

    public function test_marking_items_records_the_actor_and_approval_needs_the_automatic_checks(): void
    {
        $facility = Facility::factory()->create(['status' => 'pending']);
        $admin = $this->platformAdmin();

        $this->actingAs($admin)->get("/admin/onboarding/{$facility->id}")->assertOk();

        foreach (['facility_verified', 'admin_account_created', 'staff_roles_assigned', 'privacy_training_completed', 'demo_training_completed', 'support_contact_defined'] as $item) {
            $this->actingAs($admin)->post("/admin/onboarding/{$facility->id}/mark", ['item' => $item, 'complete' => 1])->assertRedirect();
        }

        $readiness = FacilityGoLiveReadiness::where('facility_id', $facility->id)->firstOrFail();
        $this->assertSame('ready_for_approval', $readiness->status);
        $this->assertTrue(AuditEvent::where('resource_type', 'facility_go_live_readiness')->where('actor_id', $admin->id)->exists());

        // A dropped platform item is refused.
        $this->actingAs($admin)->post("/admin/onboarding/{$facility->id}/mark", ['item' => 'document_templates_active', 'complete' => 1])
            ->assertSessionHas('error');

        // Manual checklist complete, but no listing/hours/catalogue/staff/photo: approval refused.
        $this->actingAs($admin)->post("/admin/onboarding/{$facility->id}/approve", ['approval_note' => 'ok'])->assertSessionHas('error');
        $this->assertSame('ready_for_approval', $readiness->fresh()->status);
        $this->assertSame('pending', $facility->fresh()->status);
    }
}
