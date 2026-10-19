<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\MobileInvoice;
use App\Models\MobilePaymentCharge;
use App\Services\Payments\Domain\Money;
use App\Services\Payments\Domain\Msisdn;
use App\Services\Payments\Domain\PaymentMethod;
use App\Services\Payments\Domain\PaymentStatus;
use App\Services\Payments\Exceptions\PaymentException;
use App\Services\Payments\InvoiceService;
use App\Services\Payments\MobileMoneyProviderRegistry;
use App\Services\Payments\PaymentChargeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Mobile Patient API — invoices and mobile money payments.
 *
 * ════════════════════════════════════════════════════════════════════════════
 *  ISOLATION
 * ════════════════════════════════════════════════════════════════════════════
 * `patient_id` comes from `$request->attributes` — set by
 * `AuthenticateMobilePatient` from the bearer token — and from nowhere else.
 * There is no patient identifier in any path, query string or body on this
 * controller, so there is nothing to spoof.
 *
 * Every lookup is scoped by that id **in the WHERE clause**, not checked after
 * the fact: `findInvoiceOrFail()` and `findChargeOrFail()` query
 * `where('patient_id', $mine)`, so another patient's invoice is a plain 404 —
 * the row is not found, rather than found and then refused. That distinction
 * matters: a 403 confirms the id exists, and an invoice id that can be
 * confirmed is an enumeration oracle over who is being billed for what.
 *
 * ════════════════════════════════════════════════════════════════════════════
 *  MONEY
 * ════════════════════════════════════════════════════════════════════════════
 * Amounts cross this boundary as `amount_minor`, an INTEGER of minor units.
 * XAF has ISO 4217 exponent 0, so the minor unit is the franc: 5000 means
 * 5 000 FCFA. Validation is `integer`, never `numeric` — `numeric` accepts
 * "5000.4" and floats are how a franc goes missing. The response also carries
 * `amount_display` ("5 000 FCFA"), so no client ever re-derives a currency.
 *
 * ════════════════════════════════════════════════════════════════════════════
 *  IDEMPOTENCY
 * ════════════════════════════════════════════════════════════════════════════
 * `POST .../charges` REQUIRES an `Idempotency-Key` header — it is a 400 without
 * one, not a convenience. The key plus a fingerprint of the canonicalised body
 * is what makes a retry safe; see `PaymentChargeService` for the three layers
 * behind it.
 *
 * (This controller deliberately does not use the `IdempotencyProtection`
 * middleware. That middleware keys on `integration_client_id` — a B2B partner
 * identity that does not exist on a patient bearer token, and it falls back to
 * the literal string 'test_client_id', which would make one patient's
 * idempotency key collide with every other patient's.)
 */
class MobilePaymentController extends Controller
{
    /** Bound below the DB column width so an over-long key is a 422, not a 500. */
    private const MAX_IDEMPOTENCY_KEY = 190;

    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly PaymentChargeService $charges,
        private readonly MobileMoneyProviderRegistry $providers,
    ) {
    }

    /**
     * GET /api/mobile/payments/methods
     *
     * What this host can actually take, and whether it is real. The app renders
     * its method chooser and its "no money will move" banner from this — the
     * client never hardcodes either, so a host that gains real credentials
     * needs no app release to stop showing the demo banner.
     */
    public function methods(Request $request): JsonResponse
    {
        $this->applyLocale($request);
        $this->resolvePatientId($request);

        $available = $this->providers->isAvailable();

        return response()->json([
            'data' => [
                'available' => $available,
                'simulated' => $available && $this->providers->isSimulated(),
                'currency'  => 'XAF',
                'currency_symbol' => 'FCFA',
                'methods'   => array_map(fn (PaymentMethod $m) => [
                    'value' => $m->value,
                    'label' => $m->label(),
                ], PaymentMethod::cases()),
            ],
        ]);
    }

    /**
     * GET /api/mobile/payments/invoices
     *
     * The patient's own invoices, newest first. `?status=` filters.
     */
    public function index(Request $request): JsonResponse
    {
        $this->applyLocale($request);
        $patientId = $this->resolvePatientId($request);

        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(PaymentStatus::values())],
            'limit'  => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = MobileInvoice::where('patient_id', $patientId)->latest('created_at');

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $invoices = $query->limit((int) ($validated['limit'] ?? 25))->get();

        return response()->json([
            'data' => $invoices->map(fn (MobileInvoice $i) => $i->toApiArray())->all(),
            'meta' => ['count' => $invoices->count()],
        ]);
    }

    /**
     * POST /api/mobile/payments/invoices
     *
     * Raise an invoice for the authenticated patient.
     *
     * The Finder's payable modules (booking, dispensing, lab orders) do not
     * issue invoices yet, so without this the payment surface has no input and
     * cannot be exercised at all. Rows created here are stamped
     * `subject_type = 'self_service'` and stay trivially separable from real
     * facility billing; when a real issuer lands it calls
     * `InvoiceService::issueFor()` directly and this endpoint can be deleted
     * without touching anything else.
     */
    public function store(Request $request): JsonResponse
    {
        $this->applyLocale($request);
        $patientId = $this->resolvePatientId($request);

        // A patient-chosen amount is never a real bill. This path exists only
        // so a SIMULATED provider (demo hosts) can be exercised end to end;
        // wherever money could be real, or no provider is configured, it is
        // refused outright. Real invoices come from InvoiceService::issueFor()
        // with a server-computed amount.
        if (! $this->providers->isAvailable() || ! $this->providers->isSimulated()) {
            return response()->json([
                'message' => __('api.self_service_invoice_unavailable'),
                'code'    => 'self_service_invoice_unavailable',
            ], 403);
        }

        $validated = $request->validate([
            // INTEGER, not `numeric`: `numeric` would accept "5000.4".
            'amount_minor' => ['required', 'integer', 'min:1', 'max:'.Money::MAX_MINOR],
            'description'  => ['required', 'string', 'min:2', 'max:255'],
            'facility_id'  => ['sometimes', 'uuid', 'exists:care_facilities,id'],
        ]);

        $invoice = $this->invoices->issueSelfService(
            patientId: $patientId,
            amount: Money::ofMinor((int) $validated['amount_minor']),
            description: $validated['description'],
            facilityId: $validated['facility_id'] ?? null,
            isDemo: true,
        );

        return response()->json(['data' => $invoice->toApiArray()], 201);
    }

    /** GET /api/mobile/payments/invoices/{invoice} — mine, or a 404. */
    public function show(Request $request, string $invoice): JsonResponse
    {
        $this->applyLocale($request);
        $patientId = $this->resolvePatientId($request);

        $model = $this->findInvoiceOrFail($patientId, $invoice);

        return response()->json([
            'data' => [
                ...$model->toApiArray(),
                'charges' => $model->charges()
                    ->latest('created_at')
                    ->get()
                    ->map(fn (MobilePaymentCharge $c) => $c->toApiArray())
                    ->all(),
            ],
        ]);
    }

    /**
     * POST /api/mobile/payments/invoices/{invoice}/charges
     *
     * Start (or safely replay) a payment. Requires `Idempotency-Key`.
     *
     * 201 — a new charge was opened.
     * 200 — this exact request had already been made; same charge, no second
     *       prompt to the patient's handset.
     * 409 — a different payment is already in flight, the invoice is settled,
     *       or the same key was reused with a different body.
     */
    public function pay(Request $request, string $invoice): JsonResponse
    {
        $this->applyLocale($request);
        $patientId = $this->resolvePatientId($request);

        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));

        if ($idempotencyKey === '' || mb_strlen($idempotencyKey) > self::MAX_IDEMPOTENCY_KEY) {
            return $this->fail(PaymentException::idempotencyKeyRequired());
        }

        $validated = $request->validate([
            'method' => ['required', Rule::in(PaymentMethod::values())],
            'msisdn' => ['required', 'string', 'max:32'],
            // The amount the app SHOWED the patient. Optional on the wire, but
            // the app always sends it: a patient authorises an amount, not an
            // invoice id.
            'amount_minor' => ['sometimes', 'integer', 'min:1'],
        ]);

        $model = $this->findInvoiceOrFail($patientId, $invoice);
        $method = PaymentMethod::from($validated['method']);

        try {
            $payer = Msisdn::parse($validated['msisdn']);
        } catch (InvalidArgumentException) {
            // The exception message is not echoed: it was built from caller
            // input, and a 422 body is a fine place to reflect an attacker's
            // string back at a support engineer reading logs.
            return $this->fail(PaymentException::invalidMsisdn());
        }

        // Was there already a charge under this key? Decides 200 vs 201, and is
        // read BEFORE the call so a replay is reported honestly.
        $wasReplay = MobilePaymentCharge::where('patient_id', $patientId)
            ->where('idempotency_key', $idempotencyKey)
            ->exists();

        try {
            $charge = $this->charges->start(
                invoice: $model,
                method: $method,
                payer: $payer,
                idempotencyKey: $idempotencyKey,
                // The fingerprint covers exactly the fields that define the
                // payment. The MSISDN goes in as its HASH, never as digits —
                // this value is stored, and a stored fingerprint computed over
                // a raw phone number is a phone number in the database.
                requestFingerprint: hash('sha256', json_encode([
                    'invoice' => $model->id,
                    'method'  => $method->value,
                    'payer'   => $payer->hash(),
                    'amount'  => $model->amount_minor,
                ], JSON_THROW_ON_ERROR)),
                expectedAmountMinor: isset($validated['amount_minor']) ? (int) $validated['amount_minor'] : null,
            );
        } catch (PaymentException $e) {
            return $this->fail($e);
        }

        return response()->json(
            [
                'data' => $charge->toApiArray(),
                'meta' => [
                    'replayed' => $wasReplay,
                    // How long the app should wait between polls. A server
                    // decision, so the cadence can change without an app release.
                    'poll_after_seconds' => 2,
                ],
            ],
            $wasReplay ? 200 : 201,
        );
    }

    /**
     * GET /api/mobile/payments/charges/{charge}
     *
     * The app's polling endpoint. Asks the provider for the authoritative
     * answer while the charge is still open, then returns the (possibly
     * settled) charge alongside its invoice, so one response updates both
     * halves of the screen.
     */
    public function charge(Request $request, string $charge): JsonResponse
    {
        $this->applyLocale($request);
        $patientId = $this->resolvePatientId($request);

        $model = $this->findChargeOrFail($patientId, $charge);

        if ($model->status === PaymentStatus::Pending) {
            $model = $this->charges->syncFromProvider($model);
        }

        return response()->json([
            'data' => [
                'charge'  => $model->toApiArray(),
                'invoice' => $model->invoice?->fresh()?->toApiArray(),
            ],
        ]);
    }

    // ── Lookup ──────────────────────────────────────────────────────────────

    /**
     * The patient's own invoice, or a 404.
     *
     * The ownership test is in the WHERE clause. Another patient's id simply
     * does not match, so there is no "found then refused" branch that could be
     * forgotten, and no status code that confirms the id exists.
     *
     * The id is shape-checked BEFORE it reaches the query. Postgres rejects a
     * non-UUID comparison against a uuid column with SQLSTATE 22P02, which
     * Laravel surfaces as a 500 carrying the failing SQL — so
     * `GET /payments/invoices/foo` answered with a stack trace and the table
     * name instead of "not found". A malformed id is simply an id that does not
     * exist, and must be indistinguishable from any other one that does not.
     */
    private function findInvoiceOrFail(string $patientId, string $invoiceId): MobileInvoice
    {
        abort_unless($this->isUuid($invoiceId), 404, __('payments.errors.invoice_not_found'));

        $invoice = MobileInvoice::where('patient_id', $patientId)
            ->whereKey($invoiceId)
            ->first();

        abort_if($invoice === null, 404, __('payments.errors.invoice_not_found'));

        return $invoice;
    }

    /** Same rule for a charge. */
    private function findChargeOrFail(string $patientId, string $chargeId): MobilePaymentCharge
    {
        abort_unless($this->isUuid($chargeId), 404, __('payments.errors.charge_not_found'));

        $charge = MobilePaymentCharge::where('patient_id', $patientId)
            ->whereKey($chargeId)
            ->first();

        abort_if($charge === null, 404, __('payments.errors.charge_not_found'));

        return $charge;
    }

    /**
     * Any RFC 4122 variant, version 1-8 — these ids are UUIDv7 (time-ordered),
     * so a version-4-only check would reject every real id.
     */
    private function isUuid(string $value): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $value,
        );
    }

    // ── Request context ─────────────────────────────────────────────────────

    /**
     * The authenticated patient, and only ever the authenticated patient.
     * Set by `AuthenticateMobilePatient` from the bearer token.
     */
    private function resolvePatientId(Request $request): string
    {
        $patientId = $request->attributes->get('patient_id');

        abort_if(! is_string($patientId) || $patientId === '', 401, 'Unauthenticated.');

        return $patientId;
    }

    /**
     * Speak the patient's language.
     *
     * `SetLocale` is registered on the `web` group only, so an API request
     * would otherwise always render English — and a French-speaking patient
     * being told "Payment failed" in English at the moment money is involved is
     * exactly the wrong time to fall back. Accepts `?lang=` and the standard
     * `Accept-Language` header, restricted to the two locales that are kept 1:1.
     */
    private function applyLocale(Request $request): void
    {
        $candidates = [
            $request->query('lang'),
            $request->getPreferredLanguage(['en', 'fr']),
        ];

        foreach ($candidates as $candidate) {
            $locale = is_string($candidate) ? strtolower(substr($candidate, 0, 2)) : null;

            if (in_array($locale, ['en', 'fr'], true)) {
                App::setLocale($locale);

                return;
            }
        }
    }

    /** Render a domain refusal with its own status and translated message. */
    private function fail(PaymentException $e): JsonResponse
    {
        return response()->json($e->toPayload(), $e->status);
    }
}
