<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class DemoSecretEndpointTest extends TestCase
{
    public function test_demo_secret_generation_endpoint_is_removed(): void
    {
        $response = $this->post('/api/demo/api/generate-temporary-secret');
        // Must be 404 (route removed), not 200 or 403
        $response->assertStatus(404);
    }
}
