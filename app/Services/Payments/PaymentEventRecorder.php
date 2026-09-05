<?php

namespace App\Services\Payments;

use App\Models\MobileInvoice;
use App\Models\MobilePaymentCharge;
use App\Models\MobilePaymentEvent;
use App\Services\Payments\Domain\PaymentStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * The ONLY writer of `mobile_payment_events`, and the only place in this
 * subsystem that is allowed to call `Log`.
 *
 * Centralising both is the point. A payment log is read by support staff, by
 * whoever is on call, and by anyone who opens a Sentry issue — so the rule
 * "never log a full phone number, never log a provider secret" has to be
 * enforced by there being exactly one function that can write, rather than by
 * everyone remembering.
 *
 * `redact()` below is applied to every free-text value on the way in. It is a
 * second line, not the first: the first is that no caller has a full MSISDN to
 * pass (it lives inside an `Msisdn` whose `__toString` is the mask), and the
 * third is the database's own CHECK constraints rejecting any run of six or
 * more digits in these columns.
 */
final class PaymentEventRecorder
{
    /** Structured-log channel key, so payment lines are greppable as a set. */
    private const LOG_EVENT = 'mobile_payment_transition';

    /**
     * Record one status move.
     *
     * @param  string  $source  'api' | 'poll' | 'callback' | 'sweeper'
     */
    public function record(
        ?MobilePaymentCharge $charge,
        ?MobileInvoice $invoice,
        string $source,
        ?PaymentStatus $from,
        PaymentStatus $to,
        ?string $providerStatusCode = null,
        ?string $failureCode = null,
        ?string $note = null,
    ): MobilePaymentEvent {
        $event = MobilePaymentEvent::create([
            'charge_id'            => $charge?->id,
            'invoice_id'           => $invoice?->id ?? $charge?->invoice_id,
            'source'               => $source,
            'from_status'          => $from?->value,
            'to_status'            => $to->value,
            'provider_status_code' => $this->redact($providerStatusCode),
            'failure_code'         => $this->redact($failureCode),
            'note'                 => $this->redact($note),
            'occurred_at'          => Carbon::now(),
        ]);

        // The application log gets IDs and codes. It never gets a number, an
        // amount attributable to a named person, or a provider token.
        Log::info(self::LOG_EVENT, [
            'event'       => self::LOG_EVENT,
            'charge_id'   => $charge?->id,
            'invoice_id'  => $event->invoice_id,
            'source'      => $source,
            'from'        => $from?->value,
            'to'          => $to->value,
            'provider'    => $charge?->provider,
            'status_code' => $event->provider_status_code,
            'failure'     => $event->failure_code,
        ]);

        return $event;
    }

    /**
     * Log a callback that failed verification.
     *
     * Nothing from the body is logged — an unverified callback is attacker-
     * controlled input, and echoing it into a log is how a log becomes an
     * injection sink. Only the reason and the provider key.
     */
    public function recordRejectedCallback(string $providerKey, string $reason): void
    {
        Log::warning('mobile_payment_callback_rejected', [
            'event'    => 'mobile_payment_callback_rejected',
            'provider' => $providerKey,
            'reason'   => $reason,
        ]);
    }

    /**
     * Strip anything that looks like a phone number or a long secret.
     *
     * Six or more consecutive digits is the shape of a Cameroonian national
     * number (nine digits) and of every plausible fragment of one. Status codes
     * ('SUCCESSFUL', 'NOT_ENOUGH_FUNDS', 'DEMO_PENDING') contain none, so this
     * never fires on legitimate content — and when it does fire, it fires
     * before the database's identical CHECK would have thrown, so a redaction
     * miss degrades into a masked log line rather than a 500 in the middle of
     * a payment.
     */
    private function redact(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Long digit runs → masked.
        $value = preg_replace('/\d{6,}/', '[redacted]', $value) ?? '[redacted]';

        // Long opaque tokens (base64/hex secrets) → masked.
        $value = preg_replace('/[A-Za-z0-9+\/_-]{40,}={0,2}/', '[redacted]', $value) ?? '[redacted]';

        return mb_substr($value, 0, 255);
    }
}
