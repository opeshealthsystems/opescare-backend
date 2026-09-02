<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class SdkTokenScopeRedactionTest extends TestCase
{
    public function test_403_response_does_not_expose_scope_names(): void
    {
        // The VerifySdkToken middleware returns 403 when scope is insufficient.
        // We verify the response body does NOT contain any scope name strings.

        // We test this by examining the middleware source directly.
        $source = file_get_contents(
            app_path('Http/Middleware/VerifySdkToken.php')
        );

        // The 403 response body must not contain 'required_scope', 'required', or 'granted' as JSON keys
        // or embed the scope value dynamically in the message string
        $this->assertStringNotContainsString(
            "'required'",
            $source,
            '403 response must not expose required scopes'
        );
        $this->assertStringNotContainsString(
            "'granted'",
            $source,
            '403 response must not expose granted scopes'
        );
    }

    public function test_403_message_does_not_interpolate_scope_names(): void
    {
        $source = file_get_contents(app_path('Http/Middleware/VerifySdkToken.php'));

        // The 403 must use a generic message, not one that interpolates the scope name
        // Check that we don't have "implode(', ', $missing)" or similar constructs
        $this->assertStringNotContainsString(
            'implode',
            $source,
            '403 message must not dynamically build scope list'
        );
    }

    public function test_403_message_is_generic(): void
    {
        $source = file_get_contents(app_path('Http/Middleware/VerifySdkToken.php'));

        // The 403 must use a generic message, not one that mentions specific scopes
        $this->assertStringContainsString(
            'does not have the required permissions',
            $source,
            '403 must use generic permissions message'
        );
    }
}
