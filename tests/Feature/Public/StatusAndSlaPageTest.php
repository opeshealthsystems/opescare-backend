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

    public function test_sla_page_renders_commitment(): void
    {
        $res = $this->get('/sla');

        $res->assertOk();
        $res->assertSee('99.9%');
        $res->assertSee('SEV1 — Critical');
        $res->assertSee('Service credits');
    }
}
