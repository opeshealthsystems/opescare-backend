<?php
namespace Tests\Feature\Security;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_web_responses_include_x_frame_options(): void
    {
        $response = $this->get('/login');
        $response->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_web_responses_include_x_content_type_options(): void
    {
        $response = $this->get('/login');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_web_responses_include_referrer_policy(): void
    {
        $response = $this->get('/login');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_api_responses_include_x_content_type_options(): void
    {
        $response = $this->get('/api/fhir/R4/metadata');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
