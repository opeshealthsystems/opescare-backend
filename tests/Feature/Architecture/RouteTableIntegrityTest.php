<?php

namespace Tests\Feature\Architecture;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Guards `php artisan route:list` against the failure that broke it app-wide.
 *
 * ── What happened (on the OpesCare platform this was extracted from) ──────
 *
 * Three modules were frozen out of V1 and their controllers deleted. Their
 * route groups stayed in routes/api.php. At request time nothing broke — the
 * freeze middleware 404'd the URIs before routing resolved a controller — so
 * the damage was invisible in production and total in the toolchain:
 *
 *     ReflectionException: Class "App\Http\Controllers\Api\V1\BillingController"
 *     does not exist
 *       at RouteListCommand.php:274
 *
 * RouteListCommand reflects EVERY action's class to print its file, so ONE
 * missing controller made route:list fatal for all routes. route:list is one
 * of the "verify before calling it done" checks, and the whole team lost it.
 *
 * ── The Finder extraction ─────────────────────────────────────────────────
 *
 * In the Finder product the out-of-scope modules are DELETED — routes,
 * controllers and nav together — not frozen behind placeholder controllers,
 * so the placeholder assertions the platform version of this test carried
 * are gone with them. What stays is the invariant that made them necessary:
 *
 * 1. Every routed controller class and method actually resolves. This is the
 *    ratchet: delete a controller whose routes survive it and this test goes
 *    red immediately, naming the class and the routes that point at it —
 *    instead of route:list dying silently for everyone weeks later.
 * 2. The route:list command itself runs to completion (exercises the exact
 *    reflection path that failed).
 *
 * If a route pointing at a deleted controller trips assertion 1, the fix is
 * to delete the route group with the controller — never to stub the class.
 */
class RouteTableIntegrityTest extends TestCase
{
    /**
     * THE regression guard. A controller deleted out from under a live route
     * fails here, loudly, naming the routes that still point at it.
     */
    public function test_every_routed_controller_class_and_method_exists(): void
    {
        $missingClasses = [];
        $missingMethods = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $uses = $route->getAction('uses');

            // Closure routes have nothing to reflect on.
            if (! is_string($uses)) {
                continue;
            }

            [$class, $method] = str_contains($uses, '@')
                ? explode('@', $uses, 2)
                : [$uses, '__invoke'];

            $class = ltrim($class, '\\');

            // Framework-supplied actions (view/redirect routes) are not ours.
            if (str_starts_with($class, 'Illuminate\\')) {
                continue;
            }

            $label = implode('|', $route->methods()) . ' /' . ltrim($route->uri(), '/');

            if (! class_exists($class)) {
                $missingClasses[$class][] = $label;
                continue;
            }

            if (! method_exists($class, $method) && ! method_exists($class, '__call')) {
                $missingMethods[$class . '::' . $method][] = $label;
            }
        }

        $this->assertSame(
            [],
            $missingClasses,
            "Routes point at controller classes that do not exist. This makes "
            . "`php artisan route:list` fatal for the ENTIRE route table (it reflects every "
            . "action's class), not just for these routes:\n"
            . $this->formatOffenders($missingClasses)
            . "\nIn the Finder extraction a module is removed whole: delete the route group "
            . "together with its controller — do not stub the class to silence this test."
        );

        $this->assertSame(
            [],
            $missingMethods,
            "Routes point at controller methods that do not exist:\n"
            . $this->formatOffenders($missingMethods)
        );
    }

    /**
     * Exercises the exact command that was fatal. Belt to the braces above:
     * this would catch a reflection failure the class/method walk misses.
     */
    public function test_the_route_list_command_runs_to_completion(): void
    {
        $this->artisan('route:list')->assertExitCode(0);
    }

    /**
     * @param  array<string, list<string>>  $offenders
     */
    private function formatOffenders(array $offenders): string
    {
        $lines = [];

        foreach ($offenders as $target => $routes) {
            $lines[] = '  - ' . $target;
            foreach ($routes as $route) {
                $lines[] = '      ' . $route;
            }
        }

        return implode("\n", $lines);
    }
}
