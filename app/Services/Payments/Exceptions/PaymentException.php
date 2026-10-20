<?php

namespace App\Services\Payments\Exceptions;

use RuntimeException;

/**
 * A payment refused for a reason the patient (or the client) needs to see.
 *
 * Carries an HTTP status and a translation KEY rather than a sentence: the
 * message a patient reads has to be in their own language, and a hardcoded
 * English string thrown from a service is unreachable by `lang/fr`.
 *
 * The `errorCode` is the stable machine value the app switches on. It never
 * changes when the wording does.
 */
class PaymentException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly string $translationKey,
        public readonly int $status = 422,
        /** @var array<string, mixed> */
        public readonly array $replace = [],
        /** Extra payload the client needs, e.g. the in-flight charge. */
        public readonly ?array $context = null,
    ) {
        parent::__construct($errorCode);
    }

    /** @return array<string, mixed> */
    public function toPayload(): array
    {
        return array_filter([
            'error'   => $this->errorCode,
            'message' => __($this->translationKey, $this->replace),
            'data'    => $this->context,
        ], static fn ($v) => $v !== null);
    }

    // ── The refusals this subsystem can produce ─────────────────────────────

    /**
     * @param  string  $status  The raw enum value. It is translated here rather
     *         than interpolated raw — a French patient must not be told their
     *         invoice is "expired" in the middle of a French sentence.
     */
    public static function invoiceNotPayable(string $status): self
    {
        return new self('invoice_not_payable', 'payments.errors.invoice_not_payable', 409, [
            'status' => __('payments.status.'.$status),
        ]);
    }

    public static function invoiceExpired(): self
    {
        return new self('invoice_expired', 'payments.errors.invoice_expired', 409);
    }

    public static function alreadyPaid(): self
    {
        return new self('invoice_already_paid', 'payments.errors.invoice_already_paid', 409);
    }

    /** @param array<string, mixed> $charge */
    public static function paymentInProgress(array $charge): self
    {
        return new self('payment_in_progress', 'payments.errors.payment_in_progress', 409, context: ['charge' => $charge]);
    }

    public static function idempotencyKeyRequired(): self
    {
        return new self('idempotency_key_required', 'payments.errors.idempotency_key_required', 400);
    }

    public static function idempotencyConflict(): self
    {
        return new self('idempotency_conflict', 'payments.errors.idempotency_conflict', 409);
    }

    public static function providerUnavailable(): self
    {
        return new self('provider_unavailable', 'payments.errors.provider_unavailable', 409);
    }

    public static function invalidMsisdn(): self
    {
        return new self('invalid_msisdn', 'payments.errors.invalid_msisdn', 422);
    }
}
