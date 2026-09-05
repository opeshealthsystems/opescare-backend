<?php

namespace App\Services\Payments\Contracts;

use App\Services\Payments\Domain\PaymentMethod;
use App\Services\Payments\Dto\CallbackEnvelope;
use App\Services\Payments\Dto\CallbackVerification;
use App\Services\Payments\Dto\CollectionRequest;
use App\Services\Payments\Dto\CollectionResult;

/**
 * One mobile money rail, from the platform's point of view.
 *
 * ════════════════════════════════════════════════════════════════════════════
 *  WHAT IS AND IS NOT WIRED ON THIS HOST
 * ════════════════════════════════════════════════════════════════════════════
 * OpesCare Finder holds **no MTN MoMo and no Orange Money merchant
 * credentials**. The only implementation registered here is
 * `App\Services\Payments\Providers\DemoPaymentProvider`, which moves no money
 * and says so in every response it produces. This interface is shaped against
 * the two real APIs so that adding a live implementation later is a new class
 * and a registry entry — not a redesign of the invoice, the charge table, the
 * webhook, or the app.
 *
 * ════════════════════════════════════════════════════════════════════════════
 *  HOW THE FOUR METHODS MAP ONTO THE REAL APIs
 * ════════════════════════════════════════════════════════════════════════════
 *
 * ── MTN MoMo — Collections API ──────────────────────────────────────────────
 * Docs: https://momodeveloper.mtn.com/docs/services/collection
 * Auth: `POST /collection/token/` with Basic(apiUser:apiKey) +
 *       `Ocp-Apim-Subscription-Key`, yielding a ~1h bearer token.
 *
 *   collect()  → `POST /collection/v1_0/requesttopay`
 *                Headers: Authorization: Bearer, X-Reference-Id: <UUID v4 WE
 *                mint>, X-Target-Environment: sandbox|mtncameroon,
 *                Ocp-Apim-Subscription-Key, X-Callback-Url (optional).
 *                Body: { amount: "5000" (STRING, integer-valued),
 *                        currency: "XAF", externalId: <our charge id>,
 *                        payer: { partyIdType: "MSISDN",
 *                                 partyId: "237670000001" },
 *                        payerMessage, payeeNote }
 *                Success is **202 Accepted with an EMPTY body**. The only
 *                handle you get back is the `X-Reference-Id` you sent — which
 *                is precisely why `CollectionResult::providerReference` is
 *                allowed to be a value we generated. A 202 means "the prompt is
 *                on its way to the handset", NOT "paid".
 *
 *   poll()     → `GET /collection/v1_0/requesttopay/{X-Reference-Id}`
 *                → { status: "PENDING"|"SUCCESSFUL"|"FAILED",
 *                    reason: "PAYER_NOT_FOUND"|"NOT_ENOUGH_FUNDS"|
 *                            "PAYER_LIMIT_REACHED"|"EXPIRED"|…,
 *                    financialTransactionId, amount, currency, externalId }
 *                This is the AUTHORITATIVE source. It is the only call whose
 *                answer may settle a charge.
 *
 *   verifyCallback()
 *              → MTN PUTs/POSTs the same body to `X-Callback-Url`. **There is
 *                no signature and no shared secret** — MTN's entire security
 *                model for this callback is that the URL is unguessable and
 *                served over TLS. A live implementation therefore verifies
 *                what it CAN (a high-entropy path segment, an optional source
 *                IP allow-list) and returns the claimed status as a *hint*.
 *                The pipeline then re-polls before writing anything. This is
 *                not defensive over-engineering: without it, anyone who learns
 *                the callback URL can mark invoices paid.
 *
 * ── Orange Money Cameroon — Web Payment API ─────────────────────────────────
 * Docs: https://developer.orange.com/apis/om-webpay-prod/getting-started
 * Auth: `POST /oauth/v3/token` with Basic(clientId:clientSecret),
 *       grant_type=client_credentials.
 *
 *   collect()  → `POST /orange-money-webpay/cm/v1/webpayment`
 *                Body: { merchant_key, currency: "XAF", order_id: <our charge
 *                        id>, amount: 5000 (INTEGER, not a string — the two
 *                        rails genuinely differ here), return_url, cancel_url,
 *                        notif_url, lang: "fr"|"en", reference }
 *                → { status: 201, message, pay_token, payment_url, notif_token }
 *                `payment_url` is a HOSTED CHECKOUT the patient must open —
 *                that is what `CollectionResult::authorizationUrl` carries, and
 *                why the field exists at all. `notif_token` is the shared
 *                secret for this ONE transaction and must be stored against the
 *                charge; it is the whole of Orange's callback authentication.
 *
 *   poll()     → `POST /orange-money-webpay/cm/v1/transactionstatus`
 *                Body: { order_id, amount, pay_token }
 *                → { status: "INITIATED"|"PENDING"|"SUCCESS"|"FAILED"|"EXPIRED" }
 *                Note it is a POST that echoes the amount — Orange treats a
 *                status check as a three-way match, so a poll with the wrong
 *                amount is not a lookup miss, it is a mismatch.
 *
 *   verifyCallback()
 *              → Orange POSTs to `notif_url`:
 *                { status, notif_token, txnid, order_id, amount }
 *                Verification is a **constant-time comparison** of the inbound
 *                `notif_token` against the one stored when the payment was
 *                created. `hash_equals`, never `===` — a timing-distinguishable
 *                compare on a per-transaction secret is a real oracle.
 *
 * ════════════════════════════════════════════════════════════════════════════
 *  RULES EVERY IMPLEMENTATION MUST FOLLOW
 * ════════════════════════════════════════════════════════════════════════════
 *  1. **Never return `Failed` for "I could not tell."** A timeout, a 500, an
 *     unparseable body and an unrecognised status word are all
 *     `ProviderPaymentStatus::Unknown`. A wallet may well have been debited
 *     behind an unanswered request; calling that a failure is how a patient
 *     pays twice.
 *  2. **Never retry a call that reached the provider.** Retry only on
 *     connection-level failure (the request never arrived). A retried
 *     `requesttopay` that did arrive is a second prompt on the patient's
 *     handset for the same bill.
 *  3. **Never log the payer's number or a provider secret.** The number is
 *     wrapped in `Msisdn`, whose `__toString` yields the mask. Tokens,
 *     subscription keys and `notif_token` never enter a log line at any level.
 *  4. **Never put a provider response body into `CollectionResult`.** MTN's
 *     poll response contains `payer.partyId` — the full MSISDN. Map to a short
 *     code and drop the rest.
 *  5. **`collect()` must be safe to call once per charge row and no more.**
 *     Idempotency is the caller's job (see `PaymentChargeService`), but an
 *     implementation must not internally re-send on its own initiative.
 */
interface MobileMoneyProvider
{
    /**
     * Stable machine key, written to `mobile_payment_charges.provider`.
     * 'demo' | 'mtn_momo' | 'orange_money'.
     */
    public function key(): string;

    /** Can this implementation service the rail the patient chose? */
    public function supports(PaymentMethod $method): bool;

    /**
     * True when this implementation moves no real money.
     *
     * Surfaced in every API response and rendered as a banner in the app, so a
     * simulated payment can never be mistaken for a real one by a patient, a
     * tester, or a screenshot in a bug report.
     */
    public function isSimulated(): bool;

    /**
     * Ask the payer's wallet for the money.
     *
     * MUST NOT throw for a provider-side failure — a declined payment is a
     * `CollectionResult`, not an exception. Only a programming error (an
     * unsupported method, a missing configuration) may throw.
     */
    public function collect(CollectionRequest $request): CollectionResult;

    /**
     * The authoritative current state of one transaction.
     *
     * This is the ONLY method whose answer may settle a charge. `$context`
     * carries the extra fields a rail needs to look a transaction up —
     * Orange's `transactionstatus` wants `order_id` and `amount` alongside the
     * `pay_token`, MTN needs nothing beyond the reference.
     *
     * @param  array<string, mixed>  $context
     */
    public function poll(string $providerReference, array $context = []): CollectionResult;

    /**
     * Is this inbound callback genuinely from the provider, and which charge is
     * it about?
     *
     * `$context` carries the per-transaction secret when the rail uses one
     * (Orange's `notif_token`). MUST NOT throw: a malformed callback is a
     * rejection, not a 500 — an exception here turns a garbage POST into a
     * Sentry page.
     *
     * @param  array<string, mixed>  $context
     */
    public function verifyCallback(CallbackEnvelope $envelope, array $context = []): CallbackVerification;
}
