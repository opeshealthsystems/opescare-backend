<?php

/*
|--------------------------------------------------------------------------
| Subscriptions — English
|--------------------------------------------------------------------------
|
| Every user-facing string the subscription API can return. Nothing in
| app/Services/Subscriptions or MobileSubscriptionController hardcodes a
| sentence: a subscriber reads these while deciding whether to hand over
| money, which is the worst possible moment to fall back to English for a
| French speaker.
|
| MUST stay 1:1 with lang/fr/subscriptions.php — same keys, same
| :placeholders. Verify with `php scripts/i18n-audit.php`.
|
| Currency is always written FCFA, never XAF, in anything a subscriber reads;
| the amounts themselves are formatted by App\Services\Payments\Domain\Money,
| which knows that XAF has an ISO 4217 exponent of 0 and therefore never
| divides by anything.
|
*/

return [

    /*
     | The three cadences a plan can be sold in.
     |
     | Which of them a given subscriber is offered is NOT decided here — it is
     | decided by which price columns the plan carries. These are only the
     | words for them.
     */
    'interval' => [
        'daily'   => 'Daily',
        'monthly' => 'Monthly',
        'annual'  => 'Yearly',
    ],

    // How a subscription describes itself. `pending_payment` is the state of
    // one that has been asked for and not paid for; it grants nothing, and the
    // wording has to make that unmistakable.
    'status' => [
        'pending_payment' => 'Awaiting payment',
        'trialing'        => 'Trial',
        'active'          => 'Active',
        'past_due'        => 'Payment overdue',
        'paused'          => 'Paused',
        'cancelled'       => 'Cancelled',
        'expired'         => 'Expired',
    ],

    // What the subscriber sees on the handset prompt, and on the invoice.
    'invoice_description' => ':plan subscription (:interval)',

    // Joins a list of intervals inside a sentence: "monthly, yearly".
    'list_separator' => ', ',

    'errors' => [
        'interval_not_offered'    => 'This plan is not sold :interval. Available: :offered.',
        'plan_not_available'      => 'That plan is not available.',
        'plan_not_for_subscriber' => 'That plan is not sold to your kind of account.',
        'plan_not_priced'         => 'That plan has no price set, so it cannot be paid for yet.',
        'already_subscribed'      => 'You already have this subscription, and it is still running.',
        'subscriber_not_allowed'  => 'You cannot take out a subscription for someone else.',
        'not_found'               => 'Subscription not found.',
    ],

];
