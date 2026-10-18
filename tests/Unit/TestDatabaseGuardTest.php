<?php

namespace Tests\Unit;

use ReflectionMethod;
use Tests\TestCase;

/**
 * Pins the guard in Tests\TestCase that refuses to run against the dev
 * database. Fixture rows (CM-CONSENT-0001, CM-NOREL-0001, ...) were once found
 * in opescare_finder; if the suite ever reached that database RefreshDatabase
 * would wipe it, so the name must stay refused.
 */
class TestDatabaseGuardTest extends TestCase
{
    private function allows(string $database): bool
    {
        $m = new ReflectionMethod(TestCase::class, 'looksLikeATestDatabase');
        $m->setAccessible(true);

        return $m->invoke($this, $database);
    }

    public function test_the_dev_and_platform_databases_are_refused(): void
    {
        foreach (['opescare_finder', 'opescare', 'postgres', 'opescare_finder_prod', ''] as $db) {
            $this->assertFalse($this->allows($db), "\"{$db}\" must be refused.");
        }
    }

    public function test_test_databases_are_allowed(): void
    {
        foreach (['opescare_test', 'opescare_finder_test_a1', 'opescare_finder_a3', ':memory:'] as $db) {
            $this->assertTrue($this->allows($db), "\"{$db}\" must be allowed.");
        }
    }
}
