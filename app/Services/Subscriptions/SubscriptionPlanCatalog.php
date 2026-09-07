<?php

namespace App\Services\Subscriptions;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\SubscriptionPlan;
use App\Services\Payments\Domain\Money;
use App\Services\Subscriptions\Exceptions\SubscriptionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * What is on sale, to whom, and for how much.
 *
 * Read-only. It writes nothing and charges nothing; it exists so that the
 * checkout service, the mobile API and any future screen all answer "which
 * intervals may this subscriber choose?" from ONE place, and that place is the
 * plan's own price columns.
 *
 * ════════════════════════════════════════════════════════════════════════════
 *  WHERE THE "PATIENTS CHOOSE, FACILITIES DON'T" RULE ACTUALLY LIVES
 * ════════════════════════════════════════════════════════════════════════════
 * It is not in this file, and it is not in any `if`. A patient plan carries a
 * daily, a monthly and an annual price; a facility plan carries only an annual
 * one. `SubscriptionPlan::intervalsOffered()` reads that back, and everything
 * — the catalogue endpoint, the validation at checkout, the error message that
 * lists what IS available — is derived from it.
 *
 * The consequence is the point: when the owner edits a facility plan in the
 * admin panel and gives it a monthly price, facilities can pay monthly, with
 * no code change and nothing left disagreeing. A hardcoded list would have to
 * be found and edited, and until it was, the price list and the checkout would
 * be telling the subscriber two different things.
 *
 * ════════════════════════════════════════════════════════════════════════════
 *  AMOUNTS
 * ════════════════════════════════════════════════════════════════════════════
 * Every price leaves this class as a `Money`, and `Money` is the class that
 * charges. XAF has an ISO 4217 exponent of 0, so the stored integer is a whole
 * number of FRANCS. There is no division and no multiplication anywhere in
 * this file — the integer in `daily_price_minor` is the integer that reaches
 * `mobile_invoices.amount_minor`, unchanged. That identity is what makes a
 * 100× error impossible rather than merely absent, and it is asserted by test.
 */
final class SubscriptionPlanCatalog
{
    /** The `subscription_plans.audience` values this feature can sell. */
    public const AUDIENCE_PATIENT  = 'patient';
    public const AUDIENCE_FACILITY = 'facility';

    /** The `organization_subscriptions.subscriber_type` morph tokens. */
    public const SUBSCRIBER_PATIENT  = 'patient';
    public const SUBSCRIBER_FACILITY = 'facility';

    /**
     * The morph token for a subscriber model.
     *
     * @throws SubscriptionException when the model is not something this
     *         feature knows how to bill.
     */
    public function subscriberTypeFor(Model $subscriber): string
    {
        return match (true) {
            $subscriber instanceof Patient  => self::SUBSCRIBER_PATIENT,
            $subscriber instanceof Facility => self::SUBSCRIBER_FACILITY,
            default => throw SubscriptionException::subscriberNotAllowed(),
        };
    }

    /** The catalogue audience a subscriber shops in. */
    public function audienceFor(Model $subscriber): string
    {
        return match ($this->subscriberTypeFor($subscriber)) {
            self::SUBSCRIBER_PATIENT  => self::AUDIENCE_PATIENT,
            self::SUBSCRIBER_FACILITY => self::AUDIENCE_FACILITY,
        };
    }

    /**
     * Everything this subscriber may buy, cheapest first, already priced.
     *
     * A plan that cannot be priced at all is left out rather than listed
     * broken — see `intervalsOffered()`.
     *
     * @return Collection<int, SubscriptionPlan>
     */
    public function availableTo(Model $subscriber): Collection
    {
        return SubscriptionPlan::query()
            ->forAudience($this->audienceFor($subscriber))
            ->active()
            ->public()
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (SubscriptionPlan $plan) => $this->intervalsOffered($plan) !== [])
            ->values();
    }

    /**
     * One plan this subscriber may buy, or a refusal.
     *
     * Looks the plan up by id OR slug — the app holds ids, a deep link or a
     * marketing page holds a slug, and neither is a secret. The audience check
     * is what matters: a patient cannot buy a hospital plan by quoting its id.
     */
    public function findForSubscriber(Model $subscriber, string $planIdOrSlug): SubscriptionPlan
    {
        $plan = SubscriptionPlan::query()
            ->where(function ($q) use ($planIdOrSlug) {
                $q->where('slug', $planIdOrSlug);

                // Only compare against the uuid column when the value could BE
                // a uuid: Postgres rejects `uuid = 'not-a-uuid'` with SQLSTATE
                // 22P02, which Laravel surfaces as a 500 carrying the SQL.
                if ($this->isUuid($planIdOrSlug)) {
                    $q->orWhere('id', $planIdOrSlug);
                }
            })
            ->active()
            ->first();

        if ($plan === null) {
            throw SubscriptionException::planNotAvailable();
        }

        if ($plan->audience !== $this->audienceFor($subscriber)) {
            // A distinct code from "not found": the plan does exist, the
            // subscriber simply is not who it is sold to. Nothing is leaked —
            // the catalogue is public.
            throw SubscriptionException::planNotForSubscriber();
        }

        if ($this->intervalsOffered($plan) === []) {
            throw SubscriptionException::planNotPriced();
        }

        return $plan;
    }

    /**
     * The intervals this plan is genuinely sold in.
     *
     * Delegates to `SubscriptionPlan::intervalsOffered()` — the catalogue is
     * the authority — and only adds a guard around it.
     *
     * ── The guard, and why it is here rather than in the model ─────────────
     * `SubscriptionPlan::amountFor()` builds a `Money`, and `Money::ofMinor()`
     * refuses an amount that is not positive. So a plan with a price of 0 (a
     * free tier) or a negative price makes `intervalsOffered()` THROW, and an
     * unguarded call would turn one bad catalogue row into a 500 on the plans
     * endpoint for every subscriber. The plan model states in a comment that a
     * zero price "must survive", which the code it comments does not do — that
     * disagreement is reported rather than papered over, and the model is left
     * untouched because it is not this feature's to change.
     *
     * The behaviour chosen here is the conservative one: a plan that cannot be
     * priced is not offered for sale. It is never silently sold at some other
     * price, and it is never charged as zero.
     *
     * @return list<string>
     */
    public function intervalsOffered(SubscriptionPlan $plan): array
    {
        try {
            return $plan->intervalsOffered();
        } catch (InvalidArgumentException $e) {
            Log::warning('Subscription plan cannot be priced and will not be offered.', [
                'plan_slug' => $plan->slug,
                'reason'    => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * What this interval costs on this plan.
     *
     * @throws SubscriptionException when the plan is not sold that way — the
     *         refusal that stops a facility being charged a daily rate.
     */
    public function priceFor(SubscriptionPlan $plan, string $interval): Money
    {
        $offered = $this->intervalsOffered($plan);

        if (! in_array($interval, $offered, true)) {
            throw SubscriptionException::intervalNotOffered($interval, $offered);
        }

        $amount = $plan->amountFor($interval);

        if ($amount === null) {
            // Unreachable while `intervalsOffered()` is derived from the same
            // call; kept because the day it is not, charging nothing would be
            // worse than refusing.
            throw SubscriptionException::planNotPriced();
        }

        return $amount;
    }

    /**
     * The wire shape of one plan, priced for every interval it is sold in.
     *
     * `amount_minor` is the integer of record and `amount_display` is the
     * pre-formatted FCFA string, exactly as the payment rail does it — so no
     * client ever re-derives a currency, which is how "1 500 FCFA" becomes
     * "15.00".
     *
     * @return array<string, mixed>
     */
    public function toApiArray(SubscriptionPlan $plan): array
    {
        $intervals = $this->intervalsOffered($plan);

        return [
            'id'          => $plan->id,
            'slug'        => $plan->slug,
            'name'        => $plan->name,
            'description' => $plan->description,
            'audience'    => $plan->audience,
            // The interval the plan leads with, and which the app should
            // pre-select. A server decision, so it can change without a
            // release.
            'default_interval' => $plan->defaultInterval(),
            'intervals'   => array_map(fn (string $interval) => [
                'interval'       => $interval,
                'interval_label' => __('subscriptions.interval.'.$interval),
                ...$this->priceFor($plan, $interval)->toArray(),
            ], $intervals),
            'features'    => $plan->planFeatures()
                ->orderBy('feature_key')
                ->get()
                ->map(fn ($f) => [
                    'key'         => $f->feature_key,
                    'label'       => $f->feature_label,
                    'limit_type'  => $f->limit_type,
                    'limit_value' => $f->limit_value,
                ])->all(),
            'trial_days'  => (int) $plan->trial_days,
        ];
    }

    /** Any RFC 4122 variant, versions 1-8 — these ids are UUIDv7. */
    private function isUuid(string $value): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $value,
        );
    }
}
