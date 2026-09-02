<?php
namespace Tests\Feature\Security;

use Tests\TestCase;

class IntegrationClientErrorRedactionTest extends TestCase
{
    public function test_integration_client_500_error_does_not_expose_exception_message(): void
    {
        // Verify the middleware source code does not put getMessage() in response body
        $source = file_get_contents(
            app_path('Http/Middleware/VerifyIntegrationClient.php')
        );

        // getMessage() may appear in Log calls — that's fine
        // But it must NOT appear in response()->json() array literal calls

        // Split by response()->json([ to check response body arrays specifically
        $parts = explode('response()->json([', $source);
        array_shift($parts); // Remove part before first response()->json([

        foreach ($parts as $part) {
            // Get just until the closing ] of the array
            $closingBracketPos = strpos($part, '], ');
            if ($closingBracketPos !== false) {
                $responseBody = substr($part, 0, $closingBracketPos);
            } else {
                $responseBody = $part;
            }

            $this->assertStringNotContainsString(
                'getMessage()',
                $responseBody,
                'VerifyIntegrationClient must not expose $e->getMessage() in response body — only in Log calls'
            );
        }
    }

    public function test_integration_client_middleware_source_has_generic_error_message(): void
    {
        $source = file_get_contents(
            app_path('Http/Middleware/VerifyIntegrationClient.php')
        );

        // Should have a generic error message like 'authentication_error' or similar
        $this->assertStringContainsString(
            'authentication_error',
            $source,
            'VerifyIntegrationClient must return a generic authentication error message in 500 responses'
        );
    }
}
