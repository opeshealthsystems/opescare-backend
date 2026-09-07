<?php

namespace Tests\Feature\Architecture;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Nobody is ever cut off from care, or from their own record, for owing money.
 *
 * This is the platform owner's standing instruction, recorded on 2026-09-07 as
 * the subscription fees were introduced: *"never cut anyone because of unpaid
 * fees"*. It is a policy, so it belongs in a test rather than in a comment
 * somebody can read past.
 *
 * The hazard is specific and it is already in the codebase. `module:` middleware
 * (App\Http\Middleware\EnforceModuleEntitlement) refuses a request when the
 * subscriber's plan does not include a module. It is registered and currently
 * applied to no route at all — but the moment subscriptions carry real rows, a
 * single `->middleware('module:...')` on a clinical route would start turning
 * patients away from their allergy list over an unpaid invoice, and it would
 * look like a routine line of routing while doing it.
 *
 * So: the paths that decide whether a person reaches a health record may not
 * consult what anyone has paid. Billing may chase, remind, suspend a facility's
 * *marketing listing*, or lock the admin panel — none of which is care.
 *
 * If this test ever fails, the right response is almost certainly to remove the
 * gate, not to amend the test.
 */
class CareIsNeverGatedOnPaymentTest extends TestCase
{
    /**
     * URI prefixes behind which a person reaches care or their own record.
     */
    private const CARE_PREFIXES = [
        'api/mobile',   // the patient app and every clinician desk
        'patient/',     // the patient web portal
        'verify/',      // presenting and checking a credential
    ];

    /**
     * Files that decide whether a record may be opened. None of them may reason
     * about money.
     */
    private const ACCESS_DECIDERS = [
        'app/Support/ClinicalRelationshipGate.php',
        'app/Http/Controllers/Api/Mobile/MobileDoctorChartController.php',
        'app/Http/Controllers/Api/Mobile/MobileHealthPassportController.php',
        'app/Http/Controllers/MedicalId/PatientPortalController.php',
        'app/Modules/Governance/Services/EmergencyAccessService.php',
    ];

    /** Words that mean "has this been paid for". */
    private const MONEY_WORDS = [
        'OrganizationSubscription',
        'SubscriptionPlan',
        'subscription_plans',
        'organization_subscriptions',
        'ModuleEntitlement',
    ];

    public function test_no_care_route_is_behind_a_subscription_gate(): void
    {
        $offences = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            $isCare = false;
            foreach (self::CARE_PREFIXES as $prefix) {
                if (str_starts_with($uri, $prefix)) {
                    $isCare = true;
                    break;
                }
            }

            if (! $isCare) {
                continue;
            }

            foreach ($route->gatherMiddleware() as $middleware) {
                if (! is_string($middleware)) {
                    continue;
                }

                if (str_starts_with($middleware, 'module:') || str_contains($middleware, 'EnforceModuleEntitlement')) {
                    $offences[] = sprintf('%s  [%s]', $uri, $middleware);
                }
            }
        }

        $this->assertSame(
            [],
            $offences,
            "A care route is gated on what the subscriber has paid.\n"
            . "Nobody is cut off from their health record for owing money — see this class's docblock.\n"
            . "Offenders:\n  " . implode("\n  ", $offences)
        );
    }

    public function test_the_access_decisions_do_not_read_subscription_state(): void
    {
        $offences = [];

        foreach (self::ACCESS_DECIDERS as $relative) {
            $path = base_path($relative);

            if (! is_file($path)) {
                // The file moved or was renamed: that is a change to an access
                // decision, and this test must be pointed at its new home
                // rather than quietly passing over its absence.
                $offences[] = $relative . ' (missing — repoint this test)';
                continue;
            }

            $source = (string) file_get_contents($path);

            foreach (self::MONEY_WORDS as $word) {
                if (str_contains($source, $word)) {
                    $offences[] = sprintf('%s mentions %s', $relative, $word);
                }
            }
        }

        $this->assertSame(
            [],
            $offences,
            "Something that decides whether a record may be opened is reasoning about money.\n"
            . "Whether a fee is paid must never be part of that decision.\n"
            . "Offenders:\n  " . implode("\n  ", $offences)
        );
    }
}
