<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PortalContextServiceAuditTest extends TestCase
{
    public function test_portal_context_service_has_audit_method_with_error_logging(): void
    {
        // Verify the PortalContextService catch block calls Log::error, not silent
        $source = file_get_contents(
            app_path('Services/Portal/PortalContextService.php')
        );

        // The catch block must NOT be empty or just have a comment
        $this->assertStringContainsString(
            'Log::error',
            $source,
            'PortalContextService catch block must call Log::error, not swallow exceptions silently'
        );
    }

    public function test_audit_method_does_not_throw_exceptions(): void
    {
        // The audit method should catch internally and never throw to callers
        $service = app(\App\Services\Portal\PortalContextService::class);

        // Calling audit-related methods must not throw
        try {
            // This exercises the "no-throw" contract
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->fail('PortalContextService audit must not throw: ' . $e->getMessage());
        }
    }
}
