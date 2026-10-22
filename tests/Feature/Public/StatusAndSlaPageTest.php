<?php

namespace Tests\Feature\Public;

use App\Models\MaintenanceWindow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusAndSlaPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_page_renders_live_health(): void
    {
        $res = $this->get('/status');

        $res->assertOk();
        // Live infrastructure check ran (DB is up in tests) -> Operational badges.
        $res->assertSee('Operational');
        $res->assertSee('OpesCare Connect API (v1)');
    }

    public function test_status_page_surfaces_active_maintenance_as_incident(): void
    {
        MaintenanceWindow::create([
            'title'     => 'Scheduled DB upgrade',
            'message'   => 'Brief read-only window.',
            'starts_at' => now()->subMinutes(5),
            'ends_at'   => now()->addHour(),
            'is_active' => true,
        ]);

        $res = $this->get('/status');

        $res->assertOk();
        $res->assertSee('Scheduled DB upgrade');
    }

    public function test_sla_page_points_institutions_to_opescare_com_pages(): void
    {
        // No published SLA page; the route survives for old links and points
        // institutions at contact + status on opescare.com, never opescare.cloud.
        $res = $this->get('/sla');

        $res->assertOk();
        $res->assertDontSee('opescare.cloud');
        $res->assertSee(route('public.contact'), false);
        $res->assertSee(route('public.status'), false);
        $res->assertSee(__('finder_story.redirect.heading'));
        $res->assertDontSee('Service credits');
    }
}
