<?php

/*
|--------------------------------------------------------------------------
| Mobile Money Payments — English
|--------------------------------------------------------------------------
|
| Every user-facing string the payment API can return. Nothing in
| app/Services/Payments or the payment controllers hardcodes a sentence: a
| patient reads these at the moment money is involved, which is the worst
| possible moment to fall back to English for a French speaker.
|
| MUST stay 1:1 with lang/fr/payments.php — same keys, same :placeholders.
| Verify with `php scripts/i18n-audit.php`.
|
| Currency is always written FCFA, never XAF, in anything a patient reads.
| XAF is the ISO code for an API payload; FCFA is what Cameroonians read.
|
*/

return [

    // What appears on an invoice or a charge.
    'status' => [
        'pending'  => 'Awaiting payment',
        'paid'     => 'Paid',
        'failed'   => 'Payment failed',
        'expired'  => 'Expired',
        'refunded' => 'Refunded',
    ],

    // The two mobile money rails. Brand names, not translated — but the keys
    // exist in both files so nothing renders a raw enum value.
    'method' => [
        'mtn_momo'     => 'MTN Mobile Money',
        'orange_money' => 'Orange Money',
    ],

    /*
     | Why a payment did not go through, in words a patient can act on.
     |
     | These are OUR normalised codes, never a provider's own message. Telco
     | error strings are English-only, change without notice, and occasionally
     | contain the payer's own number — none of which belongs on a patient's
     | screen.
     */
    'failure' => [
        'insufficient_funds' => 'There was not enough money in that Mobile Money account.',
        'rejected_by_payer'  => 'The payment was declined on the phone.',
        'payer_not_found'    => 'That number does not have a Mobile Money account.',
        'payer_limit'        => 'That account has reached its transaction limit.',
        'timeout'            => 'The payment request expired before it was approved on the phone.',
        'provider_error'     => 'The Mobile Money service could not complete the payment.',
        'unreachable'        => 'The Mobile Money service could not be reached. If money left your account, it will be confirmed shortly — do not pay again yet.',
    ],

    'errors' => [
        'invoice_not_found'        => 'Invoice not found.',
        'charge_not_found'         => 'Payment not found.',
        'invoice_not_payable'      => 'This invoice cannot be paid: it is :status.',
        'invoice_expired'          => 'This invoice has expired and can no longer be paid.',
        'invoice_already_paid'     => 'This invoice has already been paid.',
        'payment_in_progress'      => 'A payment is already in progress for this invoice. Check your phone for the prompt.',
        'idempotency_key_required' => 'An Idempotency-Key header is required to start a payment.',
        'idempotency_conflict'     => 'That Idempotency-Key was already used for a different payment.',
        'provider_unavailable'     => 'Mobile Money payments are not available right now.',
        'invalid_msisdn'           => 'Enter a valid Cameroonian mobile number.',
        'amount_mismatch'          => 'The amount shown has changed. This invoice is for :expected.',
    ],

    // Shown on the handset prompt when an invoice carries no description.
    'default_description' => 'OpesCare payment',

];
