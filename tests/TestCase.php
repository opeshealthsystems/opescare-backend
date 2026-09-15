<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Refuse to run against anything that is not a test database.
     *
     * This check used to live in setUp(), AFTER parent::setUp() — which is far
     * too late. Laravel's TestCase::setUp() calls refreshApplication() and then
     * setUpTraits(), and it is setUpTraits() that fires RefreshDatabase. By the
     * time a check in setUp() could speak, the database had already been
     * migrated fresh and truncated. The guard reported the loss rather than
     * preventing it, which is exactly what happened on 2026-09-01: a stale
     * bootstrap/cache/config.php (left by `php artisan config:cache`) overrode
     * the DB_DATABASE env var, and the dev database was emptied before the
     * "Refusing to run tests" message was ever printed.
     *
     * refreshApplication() runs after the container exists — so config() is
     * readable — but before setUpTraits(), so nothing has touched the database
     * yet. That is the only correct place for this.
     *
     * It throws rather than calling $this->fail() so it cannot be swallowed by
     * a test that expects an exception.
     */
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $this->assertDatabaseIsSafeForTesting();
    }

    /**
     * Tests never hit real DNS for partner URLs. Reserved example domains
     * (RFC 2606: *.example.com, *.example, *.test) resolve to a documentation
     * public address; anything else is unresolvable. IP literals bypass this and
     * are always checked for real, so private/metadata addresses stay blocked.
     */
    protected function setUp(): void
    {
        parent::setUp();

        \App\Support\PublicUrlGuard::resolveUsing(static function (string $host): array {
            $h = strtolower($host);

            return ($h === 'example.com' || str_ends_with($h, '.example.com') || str_ends_with($h, '.example') || str_ends_with($h, '.test'))
                ? ['203.0.113.10']
                : [];
        });
    }

    protected function tearDown(): void
    {
        \App\Support\PublicUrlGuard::resolveUsing(null);

        parent::tearDown();
    }

    private function assertDatabaseIsSafeForTesting(): void
    {
        $connection = config('database.default');
        $database   = (string) config("database.connections.{$connection}.database");

        if ($this->looksLikeATestDatabase($database)) {
            return;
        }

        throw new RuntimeException(sprintf(
            'Refusing to run tests against database "%s" (connection "%s"). Test databases must be ":memory:", '
            . 'end in "_test", or contain "_test_". This is checked before RefreshDatabase runs, so nothing has '
            . 'been dropped. Run "php artisan config:clear" — a cached config overrides phpunit.xml and any '
            . 'DB_DATABASE you set on the command line.',
            $database,
            $connection
        ));
    }

    private function looksLikeATestDatabase(string $database): bool
    {
        return $database === ':memory:'
            || str_ends_with($database, '_test')
            || str_contains($database, '_test_')
            // The Finder extraction's per-agent test databases are provisioned
            // as opescare_finder_a1..aN. The pattern is exact — the dev
            // database "opescare_finder" (no suffix) is still refused.
            || preg_match('/^opescare_finder_a\d+$/', $database) === 1;
    }
}
