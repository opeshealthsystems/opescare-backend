<?php
namespace Tests\Feature\Security;

use Tests\TestCase;

class ForceHttpsTest extends TestCase
{
    public function test_https_redirect_does_not_fire_in_testing_environment(): void
    {
        $response = $this->get('/login');
        // In testing (non-production), must NOT be 301
        $this->assertNotEquals(301, $response->getStatusCode());
    }
}
