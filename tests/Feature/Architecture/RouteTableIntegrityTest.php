<?php

namespace Tests\Feature\Architecture;

use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\ClinicalDecisionSupportController;
use App\Http\Controllers\Api\V1\FrozenModulePlaceholderController;
use App\Http\Controllers\Api\V1\TelemedicineController;
use App\Support\Features;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/**
 * Guards `php artisan route:list` against the failure that broke it app-wide.
 *
 * ── What happened ─────────────────────────────────────────────────────────
 *
 * Three modules were frozen out of V1 and their controllers deleted. Their
 * route groups stayed in routes/api.php, which is SEALED and could not be
 * edited to remove them. At request time nothing broke — EnforceFeatureFlag is
 * global middleware and 404s a frozen URI before routing resolves a controller
 * — so the damage was invisible in production and total in the toolchain:
 *
 *     ReflectionException: Class "App\Http\Controllers\Api\V1\BillingController"
 *     does not exist
 *       at RouteListCommand.php:274
 *
 * RouteListCommand reflects EVERY action's class to print its file, so ONE
 * missing controller made route:list fatal for all 1200+ routes. route:list is
 * one of the four "verify before calling it done" checks in the project brief,
 * and the whole team lost it — routes had to be verified through the router by
 * hand for as long as this stood.
 *
 * ── What this test asserts ────────────────────────────────────────────────
 *
 * 1. Every routed controller class and method actually resolves. This is the
 *    ratchet: delete a controller whose routes survive it and this test goes
 *    red immediately, naming the class and the routes that point at it —
 *    instead of route:list dying silently for everyone weeks later.
 * 2. The route:list command itself runs to completion (exercises the exact
 *    reflection path that failed).
 * 3. The frozen surfaces still 404 — byte-identical to a route that never
 *    existed. Supplying the missing classes must not make a frozen module
 *    reachable, and must not turn its 404 into anything else.
 * 4. No placeholder action can ever answer 2xx. A stub that returns an empty
 *    success is worse than the missing class was; the ReflectionException at
 *    least told the truth.
 *
 * @see \App\Http\Controllers\Api\V1\FrozenModulePlaceholderController
 * @see \App\Http\Middleware\EnforceFeatureFlag
 */
class RouteTableIntegrityTest extends TestCase
{
    /**
     * The frozen module placeholders, keyed by their config/features.php flag.
     *
     * @var array<string, class-string<FrozenModulePlaceholderController>>
     */
    private const PLACEHOLDERS = [
        'billing'                    => BillingController::class,
        'clinical_decision_support'  => ClinicalDecisionSupportController::class,
        'telemedicine_full'          => TelemedicineController::class,
    ];

    /**
     * One representative URI per frozen surface, plus the verbs they answer.
     *
     * @var list<array{0: string, 1: string}>
     */
    private const FROZEN_PROBES = [
        ['GET',  '/api/v1/billing/invoices'],
        ['POST', '/api/v1/billing/invoices'],
        ['POST', '/api/v1/billing/invoices/inv-1/payments'],
        ['POST', '/api/v1/billing/payments/pay-1/refund'],
        ['POST', '/api/v1/billing/wallets/deposit'],
        ['POST', '/api/v1/billing/cashier-sessions'],
        ['POST', '/api/v1/billing/cashier-sessions/s-1/close'],
        ['POST', '/api/v1/billing/cashier-sessions/s-1/reconcile'],
        ['POST', '/api/v1/cdss/run'],
        ['GET',  '/api/v1/cdss/visits/v-1/alerts'],
        ['GET',  '/api/v1/cdss/patients/p-1/alerts'],
        ['POST', '/api/v1/cdss/alerts/a-1/acknowledge'],
        ['POST', '/api/v1/cdss/alerts/a-1/override'],
        ['POST', '/api/v1/cdss/alerts/a-1/dismiss'],
        ['GET',  '/api/v1/cdss/facilities/f-1/summary'],
        ['POST', '/api/v1/cdss/overrides'],
        ['GET',  '/api/v1/cdss/overrides/high-risk'],
        ['POST', '/api/v1/cdss/overrides/o-1/qa-review'],
        ['POST', '/api/v1/telemedicine/consultations'],
        ['GET',  '/api/v1/telemedicine/consultations/c-1'],
        ['POST', '/api/v1/telemedicine/consultations/c-1/cancel'],
        ['POST', '/api/v1/telemedicine/consultations/c-1/consent'],
        ['POST', '/api/v1/telemedicine/consultations/c-1/waiting-room'],
        ['POST', '/api/v1/telemedicine/consultations/c-1/call'],
        ['POST', '/api/v1/telemedicine/sessions/s-1/end'],
    ];

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
            . "\nIf the module was frozen rather than removed, supply a placeholder that "
            . "extends " . FrozenModulePlaceholderController::class . " — never edit the "
            . "sealed routes/api.php to delete the group."
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
     * The frozen surfaces must be indistinguishable from routes that were never
     * registered — same status, same body. Supplying the missing controllers
     * must not have opened anything.
     */
    public function test_frozen_module_surfaces_still_return_a_plain_404(): void
    {
        $this->freezeEverything();

        // The reference answer: a URI with no route behind it at all.
        $reference = $this->getJson('/api/v1/this-route-has-never-existed');
        $reference->assertStatus(404);
        $expectedBody = $reference->getContent();

        foreach (self::FROZEN_PROBES as [$verb, $uri]) {
            $response = $this->json($verb, $uri);

            $response->assertStatus(404);

            $this->assertSame(
                $expectedBody,
                $response->getContent(),
                "{$verb} {$uri} must be byte-identical to a nonexistent route. A frozen "
                . "module that answers differently advertises that it exists, which tells "
                . "an enumerating client exactly which modules to come back for."
            );
        }
    }

    /**
     * Every public action on every placeholder — not just the sampled URIs —
     * refuses. With the module frozen that refusal is a 404.
     */
    public function test_no_placeholder_action_can_return_a_success(): void
    {
        $this->freezeEverything();

        $checked = 0;

        foreach (self::PLACEHOLDERS as $flag => $class) {
            $controller = new $class();

            foreach ($this->routableActions($class) as $action) {
                $checked++;

                try {
                    $response = $controller->{$action}();
                } catch (NotFoundHttpException) {
                    continue;   // frozen: the correct answer
                }

                $this->fail(sprintf(
                    '%s::%s() returned HTTP %d instead of refusing. A frozen-module '
                    . 'placeholder must never answer with a success shape — an endpoint '
                    . 'that says "OK" and stores nothing is the failure mode this whole '
                    . 'hierarchy exists to avoid.',
                    $class,
                    $action,
                    $response->getStatusCode()
                ));
            }
        }

        $this->assertSame(25, $checked, 'Expected 25 placeholder actions (8 billing + 10 CDSS + 7 telemedicine).');
    }

    /**
     * If someone flips a flag ON without shipping an implementation, the answer
     * is an explicit 501 — never a plausible empty 200.
     */
    public function test_an_unfrozen_but_unimplemented_module_fails_loudly(): void
    {
        foreach (self::PLACEHOLDERS as $flag => $class) {
            config(["features.flags.{$flag}" => true]);
            Features::forgetStateCache();

            $controller = new $class();
            $action = $this->routableActions($class)[0];
            $response = $controller->{$action}();

            $this->assertSame(
                501,
                $response->getStatusCode(),
                "{$class}::{$action}() must answer 501 Not Implemented when its flag is on "
                . "but no implementation is deployed."
            );

            $payload = json_decode($response->getContent(), true);

            $this->assertSame('error', $payload['status'] ?? null);
            $this->assertSame('NOT_IMPLEMENTED', $payload['error_code'] ?? null);
            $this->assertSame($flag, $payload['feature'] ?? null);
            $this->assertNotEmpty($payload['message'] ?? null);
        }
    }

    /**
     * Every placeholder is a placeholder on purpose — it carries no service
     * dependencies and inherits its only behaviour. If someone starts building
     * the real module here, they must delete the class rather than grow it.
     */
    public function test_placeholders_declare_themselves_as_placeholders(): void
    {
        foreach (self::PLACEHOLDERS as $flag => $class) {
            $reflection = new ReflectionClass($class);

            $this->assertTrue(
                $reflection->isSubclassOf(FrozenModulePlaceholderController::class),
                "{$class} must extend " . FrozenModulePlaceholderController::class
            );

            $this->assertNull(
                $reflection->getConstructor(),
                "{$class} must not take constructor dependencies — it implements nothing."
            );

            $source = file_get_contents($reflection->getFileName());

            $this->assertStringContainsString(
                'PLACEHOLDER',
                $source,
                "{$class} must say in its docblock that it is a placeholder, not an implementation."
            );
        }
    }

    /**
     * Force every frozen flag off and assert we actually got there. config()
     * supplies the default; a feature_states row could override it, and this
     * turns that into a clear failure rather than a confusing one downstream.
     */
    private function freezeEverything(): void
    {
        foreach (array_keys(self::PLACEHOLDERS) as $flag) {
            config(["features.flags.{$flag}" => false]);
        }

        Features::forgetStateCache();

        foreach (array_keys(self::PLACEHOLDERS) as $flag) {
            $this->assertTrue(
                Features::frozen($flag),
                "Expected feature '{$flag}' to be frozen after setting its config flag to false. "
                . "A feature_states row in the test database is overriding the config default."
            );
        }
    }

    /**
     * Public methods a route could dispatch to — i.e. the module's frozen surface.
     *
     * @param  class-string  $class
     * @return list<string>
     */
    private function routableActions(string $class): array
    {
        $declared = (new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC);

        return array_values(array_map(
            fn (ReflectionMethod $m) => $m->getName(),
            array_filter(
                $declared,
                fn (ReflectionMethod $m) => $m->getDeclaringClass()->getName() === $class
            )
        ));
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
