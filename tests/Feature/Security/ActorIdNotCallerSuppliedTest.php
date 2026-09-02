<?php
namespace Tests\Feature\Security;

use Tests\TestCase;

class ActorIdNotCallerSuppliedTest extends TestCase
{
    public function test_governance_controller_does_not_read_actor_id_from_request_input(): void
    {
        $source = file_get_contents(
            app_path('Http/Controllers/Api/V1/Connect/ConnectGovernanceController.php')
        );

        // Test using regex to catch any form: input(...'actor_id'...) or request(...'actor_id'...)
        // preg_match returns 0 when no match found, 1 when found, false on error
        $this->assertEquals(
            0,
            preg_match("/input\([^)]*'actor_id'[^)]*\)/", $source),
            'ConnectGovernanceController must not read actor_id from request input() — derive it from authenticated client'
        );

        $this->assertEquals(
            0,
            preg_match("/request\([^)]*'actor_id'[^)]*\)/", $source),
            'ConnectGovernanceController must not read actor_id from request() helper'
        );

        // Check that no direct property access ->actor_id exists
        $this->assertStringNotContainsString(
            "->actor_id",
            $source,
            'ConnectGovernanceController must not read actor_id directly from request object'
        );
    }
}
