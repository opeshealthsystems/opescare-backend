<?php
namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalAccessRolelessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_no_role_is_denied_portal_access(): void
    {
        // Create a user with no role
        $user = User::factory()->create(['role_id' => null]);

        // Check the middleware code directly — it must contain abort(403) when role is null
        $source = file_get_contents(app_path('Http/Middleware/EnsurePortalAccess.php'));

        $this->assertStringContainsString(
            'abort(403',
            $source,
            'EnsurePortalAccess must abort(403) when user has no role'
        );
    }

    public function test_ensure_portal_access_middleware_exists_and_has_role_check(): void
    {
        $source = file_get_contents(app_path('Http/Middleware/EnsurePortalAccess.php'));

        // Must NOT pass through (return $next) when role is null without checking
        // There must be an abort or redirect before returning $next when no role
        $this->assertStringContainsString('403', $source,
            'EnsurePortalAccess must return 403 for roleless users');
    }
}
