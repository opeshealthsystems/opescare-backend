<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;

/**
 * BillingController — PLACEHOLDER. Not an implementation.
 *
 * Facility-internal patient billing (invoices, payments, refunds, patient
 * wallets, cashier sessions) is frozen out of V1 behind the `billing` feature
 * flag. The real controller was deleted when the module was frozen; its route
 * group in the SEALED routes/api.php (api/v1/billing/*) could not be, so this
 * class exists purely so that route table can be built. See
 * FrozenModulePlaceholderController for the full rationale.
 *
 * Every method below 404s, exactly as the URI-pattern freeze in
 * bootstrap/app.php already makes it. NONE of them touches money, an invoice,
 * a wallet or a cashier drawer, and none of them returns a success shape.
 *
 * This freeze covers PATIENT billing only. OpesCare's own platform revenue —
 * portals/admin/subscription/*, the MTN MoMo / Orange Money gateway callbacks,
 * and Api\V1\SubscriptionController — is a different surface and stays live.
 *
 * Frozen surface (8 routes, routes/api.php lines 61-70):
 *   GET   api/v1/billing/invoices
 *   POST  api/v1/billing/invoices
 *   POST  api/v1/billing/invoices/{invoice}/payments
 *   POST  api/v1/billing/payments/{payment}/refund
 *   POST  api/v1/billing/wallets/deposit
 *   POST  api/v1/billing/cashier-sessions
 *   POST  api/v1/billing/cashier-sessions/{session}/close
 *   POST  api/v1/billing/cashier-sessions/{session}/reconcile
 *
 * @see \App\Http\Controllers\Api\V1\FrozenModulePlaceholderController
 */
class BillingController extends FrozenModulePlaceholderController
{
    protected function featureKey(): string
    {
        return 'billing';
    }

    protected function moduleLabel(): string
    {
        return 'facility patient billing';
    }

    public function invoices(): JsonResponse
    {
        return $this->unavailable(__FUNCTION__);
    }

    public function createInvoice(): JsonResponse
    {
        return $this->unavailable(__FUNCTION__);
    }

    public function recordPayment(): JsonResponse
    {
        return $this->unavailable(__FUNCTION__);
    }

    public function refund(): JsonResponse
    {
        return $this->unavailable(__FUNCTION__);
    }

    public function depositWallet(): JsonResponse
    {
        return $this->unavailable(__FUNCTION__);
    }

    public function openSession(): JsonResponse
    {
        return $this->unavailable(__FUNCTION__);
    }

    public function closeSession(): JsonResponse
    {
        return $this->unavailable(__FUNCTION__);
    }

    public function reconcileSession(): JsonResponse
    {
        return $this->unavailable(__FUNCTION__);
    }
}
