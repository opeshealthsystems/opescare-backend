<?php

namespace Tests\Feature\Marketing;

use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccountCategoriesSeeder;
use Database\Seeders\DashboardProfilesSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * /solutions — the OPES health systems catalogue, its detail pages, the
 * buy / donation request form (a lead with source 'solutions'), and its
 * place in the footer and the sitemaps.
 */
class SolutionsCatalogueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'system'        => 'pharmis',
            'request_kind'  => 'donation',
            'facility_name' => 'CSI de Nkolbisson',
            'facility_type' => 'community_health_centre',
            'city'          => 'Yaoundé',
            'contact_name'  => 'Awa Mballa',
            'phone'         => '+237 670 00 00 00',
            'email'         => 'awa@example.cm',
            'message'       => 'We have no system.',
            'has_system'    => 'no',
        ], $overrides);
    }

    public function test_index_lists_every_configured_system(): void
    {
        $systems = config('solutions.systems');
        $this->assertCount(22, $systems);

        $response = $this->get('/solutions')->assertOk();
        foreach (array_keys($systems) as $slug) {
            $response->assertSee(route('public.solutions.show', $slug), false);
            $response->assertSee(__("solutions.systems.{$slug}.name"));
        }
        $response->assertSee('"@type":"ItemList"', false);
    }

    public function test_each_detail_page_renders_with_both_standard_blocks(): void
    {
        foreach (array_keys(config('solutions.systems')) as $slug) {
            $this->get("/solutions/{$slug}")
                ->assertOk()
                ->assertSee('data-block="includes"', false)
                ->assertSee('data-block="offer"', false)
                ->assertSee(__('solutions.includes.chart_title'))
                ->assertSee(__('solutions.includes.interop_title'))
                ->assertSee('"applicationCategory":"HealthApplication"', false)
                ->assertSee('"@type":"BreadcrumbList"', false);
        }
    }

    public function test_unknown_slug_is_404_and_older_audience_pages_still_resolve(): void
    {
        $this->get('/solutions/not-a-system')->assertNotFound();
        $this->get('/solutions/hospitals')->assertOk();
    }

    public function test_french_title_and_hreflang(): void
    {
        $this->get('/solutions?lang=fr')
            ->assertOk()
            ->assertSee('<title>' . e(\App\Support\FinderSeo::branded('Systèmes de santé pour les établissements')) . '</title>', false)
            ->assertSee('hreflang="fr"', false);
    }

    public function test_footer_links_to_solutions(): void
    {
        $this->get('/about/facts')->assertOk()->assertSee(route('public.solutions.index'), false);
    }

    public function test_request_form_validates(): void
    {
        $this->get('/solutions/request')->assertOk();

        $this->post('/solutions/request', [])
            ->assertSessionHasErrors(['request_kind', 'facility_name', 'facility_type', 'city', 'contact_name', 'phone', 'email', 'has_system']);

        $this->post('/solutions/request', $this->validPayload(['system' => 'not-a-system', 'email' => 'nope']))
            ->assertSessionHasErrors(['system', 'email']);

        $this->assertSame(0, Lead::count());
    }

    public function test_request_stores_a_solutions_lead_and_shows_thanks(): void
    {
        $this->post('/solutions/request', $this->validPayload())
            ->assertRedirect(route('public.solutions.request'))
            ->assertSessionHas('solutions_success');

        $lead = Lead::sole();
        $this->assertSame('solutions', $lead->source);
        $this->assertSame('donation', $lead->request_kind);
        $this->assertSame('pharmis', $lead->solution_slug);
        $this->assertSame('community_health_centre', $lead->facility_type);
        $this->assertSame('facility', $lead->organization_type);
        $this->assertSame('Yaoundé', $lead->city);
        $this->assertFalse($lead->has_system);
        $this->assertSame('new', $lead->status);

        $this->get('/solutions/request')->assertSee(__('solutions.request.thanks_title'));
    }

    public function test_honeypot_stores_nothing(): void
    {
        $this->post('/solutions/request', $this->validPayload(['website' => 'http://spam.example']))
            ->assertRedirect(route('public.solutions.request'));

        $this->assertSame(0, Lead::count());
    }

    public function test_request_is_throttled(): void
    {
        RateLimiter::clear('signup|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/solutions/request', $this->validPayload())->assertRedirect();
        }
        $this->post('/solutions/request', $this->validPayload())->assertStatus(429);
        $this->assertSame(5, Lead::count());
    }

    public function test_admin_leads_desk_filters_and_badges_donations(): void
    {
        $this->seed(AccountCategoriesSeeder::class);
        $this->seed(DashboardProfilesSeeder::class);
        $this->seed(RolesSeeder::class);

        $admin = User::factory()->create(['status' => 'active', 'primary_facility_id' => null]);
        $admin->role_id = Role::where('name', 'super_admin')->firstOrFail()->id;
        $admin->save();

        Lead::create(['name' => 'A', 'email' => 'a@example.cm', 'organization_name' => 'Donation Org', 'source' => 'solutions', 'request_kind' => 'donation', 'solution_slug' => 'opes-emr', 'status' => 'new']);
        Lead::create(['name' => 'B', 'email' => 'b@example.cm', 'organization_name' => 'Purchase Org', 'source' => 'solutions', 'request_kind' => 'purchase', 'status' => 'new']);

        $this->actingAs($admin)->withSession(['mfa.verified' => true])
            ->get(route('portals.admin.leads', ['kind' => 'donation']))
            ->assertOk()
            ->assertSee('Donation Org')
            ->assertDontSee('Purchase Org')
            ->assertSee(__('solutions.admin.kind_donation'));
    }

    public function test_sitemaps_include_the_pages(): void
    {
        $xml = $this->get('/sitemap-pages.xml')->assertOk()->getContent();
        $this->assertStringContainsString('/solutions</loc>', $xml);
        foreach (array_keys(config('solutions.systems')) as $slug) {
            $this->assertStringContainsString("/solutions/{$slug}</loc>", $xml);
        }

        $this->get('/sitemap')->assertOk()
            ->assertSee(route('public.solutions.show', 'rcmis'), false)
            ->assertSee(__('solutions.systems.rcmis.name'));
    }
}
