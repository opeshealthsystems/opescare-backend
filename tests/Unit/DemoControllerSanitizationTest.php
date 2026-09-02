<?php
namespace Tests\Unit;

use Tests\TestCase;

class DemoControllerSanitizationTest extends TestCase
{
    public function test_user_agent_is_truncated_and_sanitized(): void
    {
        $controller = new \App\Http\Controllers\Demo\DemoAccessController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('sanitizeUserAgent');
        $method->setAccessible(true);

        // Very long user agent (attacker injection attempt)
        $longUa = str_repeat('A', 2000);
        $result  = $method->invoke($controller, $longUa);
        $this->assertLessThanOrEqual(255, strlen($result));

        // Newline injection attempt
        $newlineUa = "Mozilla/5.0\nX-Injected-Header: evil";
        $clean     = $method->invoke($controller, $newlineUa);
        $this->assertStringNotContainsString("\n", $clean);
        $this->assertStringNotContainsString("\r", $clean);
    }
}
