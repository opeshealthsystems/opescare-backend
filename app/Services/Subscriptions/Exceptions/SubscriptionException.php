<?php

namespace App\Services\Subscriptions\Exceptions;

use App\Services\Payments\Exceptions\PaymentException;

/**
 * A subscription checkout refused for a reason the subscriber needs to see.
 *
 * Extends `PaymentException` rather than duplicating it: a refusal to sell a
 * subscription is a refusal on the same wire, read by the same screen, and it
 * has to carry the same three things — a stable machine `error` code, a
 * TRANSLATION KEY (never an English sentence thrown from a service), and an
 * HTTP status. Sharing the base class means `toPayload()`, the controller's
 * `fail()` helper, and every client that already switches on `error` keep
 * working with no second code path.
 *
 * The codes here are additions to that vocabulary, not replacements: a
 * checkout that gets as far as the payment rail still fails with the rail's
 * own codes (`payment_in_progress`, `idempotency_conflict`, …).
 */
final class SubscriptionException extends PaymentException
{
    /**
     * The chosen interval is not one this plan is sold in.
     *
     * This is the refusal that stops a facility being sold a daily rate. It is
     * derived from the plan's own price columns via `intervalsOffered()`, so
     * the message can name exactly what IS on offer — a bare "invalid
     * interval" leaves the app with nothing to show and the subscriber with
     * nothing to choose.
     *
     * @param  list<string>  $offered
     */
    public static function intervalNotOffered(string $interval, array $offered): self
    {
        return new self(
            'interval_not_offered',
            'subscriptions.errors.interval_not_offered',
            422,
            [
                // Translated, not interpolated raw: a French subscriber must
                // not be told their plan is sold "annual" mid-sentence.
                'interval' => __('subscriptions.interval.'.$interval),
                'offered'  => implode(
                    __('subscriptions.list_separator'),
                    array_map(static fn (string $i) => __('subscriptions.interval.'.$i), $offered),
                ),
            ],
            context: ['interval' => $interval, 'intervals_offered' => $offered],
        );
    }

    /** No such plan, or it is not on sale (inactive / not public). */
    public static function planNotAvailable(): self
    {
        return new self('plan_not_available', 'subscriptions.errors.plan_not_available', 404);
    }

    /** The plan exists but is not sold to this kind of subscriber. */
    public static function planNotForSubscriber(): self
    {
        return new self('plan_not_for_subscriber', 'subscriptions.errors.plan_not_for_subscriber', 403);
    }

    /**
     * The plan carries no interval that can be priced at all.
     *
     * Reached when every one of a plan's price columns is null, or when a
     * price is zero or negative — `Money` refuses a non-positive amount, so a
     * free plan cannot be sold through a payment rail (there is nothing to
     * charge). That is a catalogue problem, and saying so is more useful than
     * a 500 from a formatter.
     */
    public static function planNotPriced(): self
    {
        return new self('plan_not_priced', 'subscriptions.errors.plan_not_priced', 409);
    }

    /** @param array<string, mixed> $subscription */
    public static function alreadySubscribed(array $subscription): self
    {
        return new self(
            'already_subscribed',
            'subscriptions.errors.already_subscribed',
            409,
            context: ['subscription' => $subscription],
        );
    }

    /** The requested subscriber is not one this caller may act for. */
    public static function subscriberNotAllowed(): self
    {
        return new self('subscriber_not_allowed', 'subscriptions.errors.subscriber_not_allowed', 403);
    }

    /**
     * The same `Idempotency-Key` came back describing a DIFFERENT purchase.
     *
     * Separate from the rail's own `idempotency_conflict` only in where it is
     * detected — this one fires before a charge exists, when the key's
     * subscription is for another plan, interval or price. Same code on the
     * wire, so the app needs one branch, not two.
     */
    public static function idempotencyMismatch(): self
    {
        return new self('idempotency_conflict', 'payments.errors.idempotency_conflict', 409);
    }

    /** A subscription id that is not this subscriber's. Never a 403. */
    public static function notFound(): self
    {
        return new self('subscription_not_found', 'subscriptions.errors.not_found', 404);
    }
}
