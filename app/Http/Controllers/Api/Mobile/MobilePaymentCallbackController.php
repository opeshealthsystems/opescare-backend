<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\MobilePaymentCharge;
use App\Services\Payments\Domain\PaymentMethod;
use App\Services\Payments\Domain\PaymentStatus;
use App\Services\Payments\Dto\CallbackEnvelope;
use App\Services\Payments\MobileMoneyProviderRegistry;
use App\Services\Payments\PaymentChargeService;
use App\Services\Payments\PaymentEventRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Inbound provider callbacks — the webhook MTN MoMo and Orange Money would
 * call.
 *
 * `POST /api/mobile/payments/callbacks/{provider}`
 *
 * ════════════════════════════════════════════════════════════════════════════
 *  WHY A VERIFIED CALLBACK STILL DOES NOT SETTLE ANYTHING
 * ════════════════════════════════════════════════════════════════════════════
 * This endpoint never writes a status from the request body. It verifies the
 * callback, finds the charge, and then **re-polls the provider**, which is the
 * only authoritative source. Three reasons, all real:
 *
 *  1. **MTN's callback is unauthenticated.** MTN MoMo Collections POSTs to your
 *     `X-Callback-Url` with no signature and no shared secret; the entire
 *     security model is that the URL is unguessable. Anyone who learns it could
 *     otherwise mark every invoice on the platform paid.
 *  2. **Neither rail guarantees ordering or exactly-once delivery.** A
 *     `PENDING` callback can arrive after a `SUCCESSFUL` one. Writing the body
 *     verbatim would move a settled charge backwards — which the database
 *     would then reject, turning a routine out-of-order delivery into a 500.
 *  3. **A replayed callback is free to send.** Anyone who captures one can
 *     re-post it forever. Re-polling makes a replay a no-op: the provider
 *     reports the same terminal state, and a terminal charge is left alone.
 *
 * ════════════════════════════════════════════════════════════════════════════
 *  WHY EVERY ANSWER IS 202
 * ════════════════════════════════════════════════════════════════════════════
 * Unverified callback, unknown charge, wrong provider key, valid callback — all
 * `202 Accepted`, all the same shape. The caller is unauthenticated, so the
 * response code is the only channel it has, and a 404-for-unknown / 401-for-bad
 * signature split is an oracle: it tells an attacker which charge ids exist and
 * lets them probe signatures. It also matches how the rails behave in practice
 * — both retry on a non-2xx, so answering 4xx to a garbage POST buys a retry
 * storm and nothing else.
 *
 * The rejections are not silent: they go to the log through
 * `PaymentEventRecorder::recordRejectedCallback()`, with the reason and the
 * provider key and nothing from the body.
 */
class MobilePaymentCallbackController extends Controller
{
    public function __construct(
        private readonly MobileMoneyProviderRegistry $providers,
        private readonly PaymentChargeService $charges,
        private readonly PaymentEventRecorder $events,
    ) {
    }

    public function __invoke(Request $request, string $provider): JsonResponse
    {
        // A single flat response, used on every path. See the class docblock.
        $accepted = response()->json(['status' => 'accepted'], 202);

        if (! $this->providers->isAvailable()) {
            $this->events->recordRejectedCallback($provider, 'no_provider_configured');

            return $accepted;
        }

        // The path segment names a rail, not a charge. Anything else is noise.
        $method = PaymentMethod::tryFrom($provider);
        $implementation = $this->providers->for($method ?? PaymentMethod::MtnMomo);

        // A callback addressed to a rail this host does not service.
        if ($method === null && $provider !== $implementation->key()) {
            $this->events->recordRejectedCallback($provider, 'unknown_provider');

            return $accepted;
        }

        $envelope = new CallbackEnvelope(
            headers: $this->normalisedHeaders($request),
            body: is_array($request->json()->all()) ? $request->json()->all() : [],
            // The exact bytes. A signature computed over a re-encoded body is a
            // signature over different bytes — the classic reason a correct
            // HMAC check fails only in production.
            rawBody: $request->getContent(),
            sourceIp: $request->ip(),
        );

        $verification = $implementation->verifyCallback($envelope);

        if (! $verification->verified) {
            $this->events->recordRejectedCallback(
                $implementation->key(),
                $verification->rejectionReason ?? 'unverified',
            );

            return $accepted;
        }

        // Shape-check before the query. The reference came off the wire, and a
        // non-UUID compared against a uuid column is SQLSTATE 22P02 — a 500
        // carrying the failing SQL, handed to an unauthenticated caller. A
        // malformed reference is simply a charge that does not exist.
        $charge = $this->isUuid((string) $verification->externalReference)
            ? MobilePaymentCharge::whereKey($verification->externalReference)->first()
            : null;

        if ($charge === null) {
            $this->events->recordRejectedCallback($implementation->key(), 'unknown_charge');

            return $accepted;
        }

        // A settled charge is never re-opened, and a replayed callback for one
        // does nothing at all.
        if ($charge->status !== PaymentStatus::Pending) {
            return $accepted;
        }

        // The callback told us *something changed*. What changed is decided by
        // the provider's own status endpoint, never by this request body.
        $this->charges->syncFromProvider($charge);

        return $accepted;
    }

    /** Any RFC 4122 variant, version 1-8 — charge ids are UUIDv7. */
    private function isUuid(string $value): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $value,
        );
    }

    /** @return array<string, string> Lower-cased name => first value. */
    private function normalisedHeaders(Request $request): array
    {
        $out = [];

        foreach ($request->headers->all() as $name => $values) {
            $out[strtolower($name)] = (string) ($values[0] ?? '');
        }

        return $out;
    }
}
