<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Modules\Admin\Services\PlatformAdminService;
use App\Services\Payments\Domain\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Plans & pricing — the screen that owns what OpesCare charges.
 *
 * The plans were established by database/seeders/PlatformFeeSeeder.php, whose
 * own docblock says it "establishes the plans, it does not own the prices".
 * This is the screen it was pointing at. From here on, a price change is an
 * administrator's action on a live catalogue, not a code change.
 *
 * ── Amounts are whole francs, and this screen must never forget it ─────────
 * XAF has an ISO 4217 exponent of 0 — there is no centime in circulation — so
 * `daily_price_minor = 50` is fifty francs. There is no division by 100 in this
 * controller, in the service it calls, or in the Blade it renders. An earlier
 * version of this schema called the column `price_kobo` and divided; that would
 * have shown a 25 000 FCFA plan as 250. See
 * App\Services\Payments\Domain\Money, which is the class that actually charges.
 *
 * ── This screen sets prices; it does not reshape the catalogue ─────────────
 * A plan is sold on the intervals whose price column is non-null — that is what
 * SubscriptionPlan::intervalsOffered() means, and it is how a facility ends up
 * billed yearly while a patient may choose by day, month or year. So:
 *
 *   - an interval a plan already offers can be re-priced, or cleared to blank
 *     ("no longer sold this way");
 *   - an interval a plan does NOT offer is locked. Typing 50 into a hospital's
 *     "per day" box must not put hospitals on a daily rate, and a hand-crafted
 *     POST that tries it is rejected rather than ignored.
 *
 * Re-introducing an interval is therefore deliberately outside this screen: it
 * changes what the plan IS, not what it costs.
 *
 * Platform-tier only: /admin/plans is covered by the platform.admin middleware
 * on its route group (RequirePlatformAdmin treats everything under /admin/ as
 * platform-only).
 */
class AdminPlanPricingController extends Controller
{
    public function __construct(private readonly PlatformAdminService $admin)
    {
    }

    public function index(): View
    {
        $plans = $this->admin->allSubscriptionPlans();

        // "Who last changed it" is read back out of the audit trail rather than
        // duplicated onto the plan row — see PlatformAdminService::lastPlanChanges().
        $lastChanges = $this->admin->lastPlanChanges($plans->pluck('id')->all());

        $actorIds = collect($lastChanges)
            ->pluck('actor_id')
            ->filter(fn ($id) => is_string($id) && Str::isUuid($id))
            ->unique()
            ->values()
            ->all();

        $actors = $actorIds === []
            ? []
            : User::whereIn('id', $actorIds)->pluck('name', 'id')->all();

        return view('portals.admin.plans.index', [
            'plans'       => $plans,
            'lastChanges' => $lastChanges,
            'actors'      => $actors,
            'intervals'   => SubscriptionPlan::INTERVALS,
        ]);
    }

    /**
     * Re-price one plan.
     *
     * Validation is written out rather than handed to a rules array because
     * every rejection here needs to name the plan AND the interval — "the
     * monthly price for Patient must be a whole number of francs" is actionable
     * where "prices.monthly is invalid" is not.
     */
    public function updatePrices(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $offered   = $plan->intervalsOffered();
        $submitted = $request->input('prices');
        $submitted = is_array($submitted) ? $submitted : [];

        $errors = [];
        $prices = [];

        foreach ($submitted as $interval => $raw) {
            if (! in_array($interval, SubscriptionPlan::INTERVALS, true)) {
                continue;   // not an interval this application knows about
            }

            $raw   = is_string($raw) ? trim($raw) : $raw;
            $blank = $raw === null || $raw === '';

            if (! in_array($interval, $offered, true)) {
                // Blank for an interval that is already not sold is a no-op, not
                // an offence — the locked inputs post nothing, but a stale form
                // or a crafted request might.
                if (! $blank) {
                    $errors[] = __('plan_pricing.err_interval_not_offered', [
                        'plan'     => $plan->name,
                        'interval' => $this->intervalLabel($interval),
                    ]);
                }

                continue;
            }

            if ($blank) {
                $prices[$interval] = null;   // no longer sold this way
                continue;
            }

            // A whole number of francs. `50.5`, `50,5`, `1e3`, `-20` and
            // ` 50 000 ` are all rejected with a message rather than silently
            // rounded, truncated or reinterpreted — a price nobody typed is a
            // price nobody agreed to.
            if (! is_scalar($raw) || preg_match('/^[0-9]+$/', (string) $raw) !== 1) {
                $errors[] = __('plan_pricing.err_whole_francs', [
                    'plan'     => $plan->name,
                    'interval' => $this->intervalLabel($interval),
                ]);

                continue;
            }

            $minor = (int) $raw;

            // Zero is refused, and this is the one place the screen departs
            // from "any non-negative whole number".
            //
            // SubscriptionPlan::amountFor() says in its own comment that "a
            // zero price is a real answer — a free plan", but it then builds
            // that answer with Money::ofMinor(), which throws on `$minor <= 0`.
            // So a stored 0 does not render as "free": it makes amountFor(),
            // intervalsOffered(), offersInterval(), defaultInterval(),
            // priceFormatted() and isFree() all throw for that plan — this
            // listing included, and the checkout that reads the same model.
            //
            // Both of those files are settled and not mine to change, so this
            // screen refuses to write a value the domain cannot represent
            // rather than storing a row that breaks on the next read. Blank
            // (not sold) and Deactivate cover the real intent.
            if ($minor === 0) {
                $errors[] = __('plan_pricing.err_zero_price', [
                    'plan'     => $plan->name,
                    'interval' => $this->intervalLabel($interval),
                ]);

                continue;
            }

            if ($minor > Money::MAX_MINOR) {
                $errors[] = __('plan_pricing.err_too_large', [
                    'plan'     => $plan->name,
                    'interval' => $this->intervalLabel($interval),
                    'max'      => Money::ofMinor(Money::MAX_MINOR)->format(),
                ]);

                continue;
            }

            $prices[$interval] = $minor;
        }

        // A plan with no price at all cannot be sold, and `price_minor` is NOT
        // NULL besides. Taking a plan off the market is what the Deactivate
        // button is for.
        $resulting = [];
        foreach (SubscriptionPlan::INTERVALS as $interval) {
            $resulting[$interval] = array_key_exists($interval, $prices)
                ? $prices[$interval]
                : $plan->{$interval . '_price_minor'};
        }

        if (count(array_filter($resulting, fn ($v) => $v !== null)) === 0) {
            $errors[] = __('plan_pricing.err_last_interval', ['plan' => $plan->name]);
        }

        if ($errors !== []) {
            return redirect()
                ->route('portals.admin.plans.index')
                ->withInput()
                ->with('price_error_plan', $plan->id)
                ->withErrors($errors);
        }

        $current = [];
        foreach (array_keys($prices) as $interval) {
            $value               = $plan->{$interval . '_price_minor'};
            $current[$interval]  = $value === null ? null : (int) $value;
        }

        if ($current === $prices) {
            return redirect()
                ->route('portals.admin.plans.index')
                ->with('success', __('plan_pricing.flash_prices_same', ['plan' => $plan->name]));
        }

        $this->admin->updatePlanPrices($plan, $prices, $this->actorId(), $request->ip());

        return redirect()
            ->route('portals.admin.plans.index')
            ->with('success', __('plan_pricing.flash_prices_updated', ['plan' => $plan->name]));
    }

    /** Put a plan on sale, or take it off sale. */
    public function toggle(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $active = $request->boolean('active');

        $this->admin->toggleSubscriptionPlan($plan, $active, $this->actorId(), $request->ip());

        return redirect()
            ->route('portals.admin.plans.index')
            ->with('success', __(
                $active ? 'plan_pricing.flash_activated' : 'plan_pricing.flash_deactivated',
                ['plan' => $plan->name]
            ));
    }

    /**
     * Who did it.
     *
     * `admin_action_logs.actor_id` is a plain string column, so this is the
     * signed-in user's UUID and nothing else — no `session('auth_email')`
     * fallback and no placeholder like 'super-admin'. A fabricated actor in an
     * audit row is worse than an absent one, and the routes here require auth,
     * so there is always a real one.
     */
    private function actorId(): string
    {
        return (string) Auth::id();
    }

    /** The interval, in the reader's language, for an error message. */
    private function intervalLabel(string $interval): string
    {
        return __('plan_pricing.interval_' . $interval);
    }
}
