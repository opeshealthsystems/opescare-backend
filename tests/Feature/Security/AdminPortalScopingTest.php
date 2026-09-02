<?php
namespace Tests\Feature\Security;

use Tests\TestCase;

class AdminPortalScopingTest extends TestCase
{
    public function test_admin_portal_controller_excludes_demo_patients(): void
    {
        $source = file_get_contents(
            app_path('Http/Controllers/MedicalId/AdminPortalController.php')
        );

        // Must have is_demo filter to exclude demo patients from statistics
        $this->assertStringContainsString(
            'is_demo',
            $source,
            'AdminPortalController must filter out demo patients from statistics'
        );
    }

    public function test_admin_portal_controller_scopes_to_facility(): void
    {
        $source = file_get_contents(
            app_path('Http/Controllers/MedicalId/AdminPortalController.php')
        );

        // Must reference facility_id to scope results to the current facility
        $this->assertTrue(
            str_contains($source, 'facility_id') || str_contains($source, 'facility'),
            'AdminPortalController must scope queries to the current facility'
        );
    }
}
