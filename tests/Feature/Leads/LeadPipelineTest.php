<?php

namespace Tests\Feature\Leads;

use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccountCategoriesSeeder;
use Database\Seeders\DashboardProfilesSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class LeadPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_demo_form_creates_a_lead_with_correct_fields_and_source(): void
    {
        $response = $this->post('/request-demo', [
            'organization_name' => 'Centre Hospitalier de Yaoundé',
            'organization_type' => 'facility',
            'name'              => 'Awa Mballa',
            'email'             => 'awa@chu.cm',
            'phone'             => '+237600000000',
            'message'           => 'We would like a walkthrough for our radiology team.',
            'source'            => 'pricing',
        ]);

        $response->assertRedirect(route('public.request-demo'));
        $response->assertSessionHas('demo_success');

        $this->assertDatabaseHas('leads', [
            'organization_name' => 'Centre Hospitalier de Yaoundé',
            'organization_type' => 'facility',
            'name'              => 'Awa Mballa',
            'email'             => 'awa@chu.cm',
            'phone'             => '+237600000000',
            'source'            => 'pricing',
            'status'            => 'new',
        ]);
    }

    public function test_public_demo_form_defaults_source_to_request_demo_when_not_from_pricing(): void
    {
        $this->post('/request-demo', [
            'organization_name' => 'Acme Labs',
            'organization_type' => 'lab',
            'name'              => 'Jean Paul',
            'email'             => 'jp@acme.test',
        ]);

        $this->assertDatabaseHas('leads', [
            'email'  => 'jp@acme.test',
            'source' => 'request_demo',
        ]);
    }

    public function test_validation_rejects_empty_required_fields(): void
    {
        $response = $this->from('/request-demo')->post('/request-demo', [
            'organization_name' => '',
            'organization_type' => '',
            'name'              => '',
            'email'             => '',
        ]);

        $response->assertSessionHasErrors(['organization_name', 'organization_type', 'name', 'email']);
        $this->assertSame(0, Lead::count());
    }

    public function test_admin_can_update_a_lead_status(): void
    {
        $this->seed(AccountCategoriesSeeder::class);
        $this->seed(DashboardProfilesSeeder::class);
        $this->seed(RolesSeeder::class);
        $this->withoutMiddleware(ThrottleRequests::class);

        $role = Role::where('name', 'super_admin')->firstOrFail();
        $admin = User::factory()->create(['status' => 'active', 'primary_facility_id' => null]);
        $admin->role_id = $role->id;
        $admin->save();

        $lead = Lead::create([
            'name'              => 'Awa Mballa',
            'email'             => 'awa@chu.cm',
            'organization_name' => 'CHU Yaoundé',
            'organization_type' => 'facility',
            'source'            => 'pricing',
            'status'            => 'new',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['mfa.verified' => true])
            ->post(
                route('portals.admin.leads.status', $lead),
                ['status' => 'contacted', 'note' => 'Left a voicemail.']
            );

        $response->assertRedirect(route('portals.admin.leads'));

        $lead->refresh();
        $this->assertSame('contacted', $lead->status);
        $this->assertStringContainsString('Left a voicemail.', (string) $lead->notes);
    }
}
