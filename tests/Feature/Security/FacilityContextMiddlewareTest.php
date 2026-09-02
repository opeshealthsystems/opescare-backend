<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class FacilityContextMiddlewareTest extends TestCase
{
    public function test_require_facility_context_middleware_has_super_admin_bypass_logic(): void
    {
        $source = file_get_contents(
            app_path('Http/Middleware/RequireFacilityContext.php')
        );

        // Must log when bypassing facility context for super-admin/platform admin
        $this->assertStringContainsString(
            'Log::info',
            $source,
            'RequireFacilityContext must Log::info when bypassing facility context'
        );
    }

    public function test_super_admin_bypass_is_documented_in_code(): void
    {
        $source = file_get_contents(
            app_path('Http/Middleware/RequireFacilityContext.php')
        );

        // The bypass must be explicit — not just fall-through
        $this->assertStringContainsString(
            'super_admin',
            strtolower($source),
            'RequireFacilityContext must have explicit super_admin bypass documented'
        );
    }
}
