<?php

namespace Tests\Feature\Architecture;

use Closure;
use Tests\TestCase;

/**
 * Guards the production deploy against unserialisable config.
 *
 * `php artisan config:cache` writes the merged config with var_export(), which
 * cannot represent a Closure. A closure anywhere under config/ makes that
 * command fail with "Call to undefined method Closure::__set_state()".
 *
 * That is a deploy-stopping failure, not a cosmetic one. deploy.yml enters
 * maintenance mode, pulls, runs migrations, and only then caches config — all
 * under `set -e`. A failure at the cache step aborts the script before
 * `php artisan up`, leaving production stranded behind a 503 until someone
 * SSHes in and lifts maintenance by hand.
 *
 * This happened for real: config/sentry.php carried a `before_send` closure
 * from 2026-06-18 until it was moved to App\Support\SentryPhiScrubber. Use a
 * callable array — [SomeClass::class, 'method'] — which is two plain strings
 * and survives var_export() intact.
 */
class ConfigIsCacheableTest extends TestCase
{
    public function test_no_config_file_contains_a_closure(): void
    {
        $offenders = [];

        foreach (glob(config_path('*.php')) as $file) {
            $name = basename($file, '.php');

            foreach ($this->closurePaths(require $file, $name) as $path) {
                $offenders[] = $path;
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Closures found in config, which breaks `php artisan config:cache` and "
            . "strands a production deploy in maintenance mode:\n  - "
            . implode("\n  - ", $offenders)
            . "\n\nReplace each with a callable array, e.g. [MyClass::class, 'method']."
        );
    }

    public function test_the_sentry_phi_scrubber_is_a_serialisable_callable(): void
    {
        $scrub = config('sentry.before_send');

        $this->assertIsNotObject($scrub, 'before_send must not be a Closure — it breaks config:cache.');
        $this->assertIsCallable($scrub);
        $this->assertSame([\App\Support\SentryPhiScrubber::class, 'scrub'], $scrub);
    }

    /**
     * Recursively collect dot-paths of every Closure in a config array.
     *
     * @return list<string>
     */
    private function closurePaths(mixed $value, string $path): array
    {
        if ($value instanceof Closure) {
            return [$path];
        }

        if (! is_array($value)) {
            return [];
        }

        $found = [];

        foreach ($value as $key => $child) {
            foreach ($this->closurePaths($child, "{$path}.{$key}") as $childPath) {
                $found[] = $childPath;
            }
        }

        return $found;
    }
}
