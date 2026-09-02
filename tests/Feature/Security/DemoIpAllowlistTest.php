<?php
namespace Tests\Feature\Security;

use Tests\TestCase;

class DemoIpAllowlistTest extends TestCase
{
    public function test_demo_login_blocked_from_non_allowlisted_ip(): void
    {
        // Set demo mode enabled but restrict to specific IP
        config(['demo.enabled' => true, 'demo.allowed_ips' => '192.168.1.1']);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->post('/demo-access/login-as', [
                'role' => 'doctor',
                'email' => 'demo@example.com',
            ]);

        // Should be 403 - IP not in allowlist
        $response->assertStatus(403);
    }

    public function test_demo_login_allowed_from_allowlisted_ip(): void
    {
        // When allowed_ips is empty (wildcard), any IP is allowed
        config(['demo.enabled' => true, 'demo.allowed_ips' => '']);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->post('/demo-access/login-as', [
                'role' => 'doctor',
                'email' => 'demo@example.com',
            ]);

        // Should NOT be 403 (may be 422 or other validation error, but not IP block)
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_demo_login_blocked_when_demo_disabled(): void
    {
        config(['demo.enabled' => false]);

        $response = $this->post('/demo-access/login-as', [
            'role' => 'doctor',
            'email' => 'demo@example.com',
        ]);

        // Should be 404 - demo mode disabled (via abort(404) in index())
        $response->assertStatus(404);
    }

    public function test_demo_login_allowed_with_matching_ip_in_list(): void
    {
        // When allowed_ips has multiple IPs and current IP matches
        config(['demo.enabled' => true, 'demo.allowed_ips' => '192.168.1.1, 10.0.0.1, 127.0.0.1']);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->post('/demo-access/login-as', [
                'role' => 'doctor',
                'email' => 'demo@example.com',
            ]);

        // Should NOT be 403 (may be 422 or other validation error, but not IP block)
        $this->assertNotEquals(403, $response->getStatusCode());
    }
}
