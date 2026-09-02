<?php
namespace Tests\Feature\Security;

use Tests\TestCase;

class Wave7RemainingTest extends TestCase
{
    public function test_demo_data_scope_has_octane_guard(): void
    {
        $source = file_get_contents(app_path('Http/Middleware/DemoDataScope.php'));
        $this->assertStringContainsString('octane', $source,
            'DemoDataScope must have Octane incompatibility guard');
    }

    public function test_portal_context_actor_id_returns_null_not_anonymous(): void
    {
        $source = file_get_contents(app_path('Services/Portal/PortalContextService.php'));
        $this->assertStringNotContainsString("'anonymous'", $source,
            'PortalContextService::actorId() must not return the string "anonymous"');
    }

    public function test_analytics_controller_has_facility_id_validation(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Api/V1/AnalyticsController.php'));
        $this->assertStringContainsString('facility_id', $source,
            'AnalyticsController must validate facility_id ownership');
    }
}
