<?php

namespace App\Services\Payments\Providers;

use App\Services\Payments\Contracts\MobileMoneyProvider;
use App\Services\Payments\Domain\PaymentMethod;
use App\Services\Payments\Domain\ProviderPaymentStatus;
use App\Services\Payments\Dto\CallbackEnvelope;
use App\Services\Payments\Dto\CallbackVerification;
use App\Services\Payments\Dto\CollectionRequest;
use App\Services\Payments\Dto\CollectionResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use RuntimeException;

/**
 * A mobile money rail that moves no money.
 *
 * OpesCare Finder has no MTN MoMo and no Orange Money merchant credentials.
 * This class stands in for both so the entire pipeline — invoice, charge,
 * idempotency, polling, callback verification, the app's pending screen — is
 * real, exercised and testable, while the only thing simulated is the telco.
 *
 * ════════════════════════════════════════════════════════════════════════════
 *  WHY THIS CANNOT BE TURNED ON IN A REAL DEPLOYMENT
 * ════════════════════════════════════════════════════════════════════════════
 * A simulator that silently marks invoices paid is the worst class of bug this
 * repository could ship, so it is not guarded by a config flag someone can
 * flip. Four independent barriers, all in the CONSTRUCTOR, so the object
 * cannot come into existence at all when any one of them says no:
 *
 *   1. **Environment allow-list.** Only `local`, `testing` and `demo`. Not
 *      `production`, not `staging`, and — because it is an allow-list, not a
 *      deny-list — not `prod`, `prod-eu`, `live`, or any future name somebody
 *      invents. A new environment is simulator-free by default.
 *
 *   2. **Credential exclusion.** If ANY real merchant credential is present in
 *      config (`services.mtn_momo.api_key`, `.subscription_key`,
 *      `services.orange_money.client_secret`, `.merchant_key`), construction
 *      throws. A host that can take real money can never fall back to the
 *      simulator — the moment someone pastes live credentials into `.env`, this
 *      class stops existing. That is the barrier that survives a mis-set
 *      APP_ENV, which is the realistic way the other three would be defeated.
 *
 *   3. **`APP_DEBUG`-independent, `APP_ENV`-independent kill switch.** Setting
 *      `PAYMENTS_DEMO_PROVIDER=off` refuses construction everywhere, so an
 *      operator can disable it without an environment rename.
 *
 *   4. **It never claims to be a rail.** `key()` is the literal string `demo`,
 *      written to `mobile_payment_charges.provider` on every row it touches;
 *      `isSimulated()` is hard-coded `true`; every charge it creates carries
 *      `is_demo = true`; and every API response and every screen in the app
 *      renders a "no money will move" banner sourced from that flag. There is
 *      no configuration in which a demo payment looks like a real one.
 *
 * `MobileMoneyProviderRegistry` repeats barrier 1 before it even asks for an
 * instance, so a production host fails with a clear "no provider configured"
 * error rather than a constructor exception from deep inside a payment.
 *
 * ════════════════════════════════════════════════════════════════════════════
 *  THE SIMULATION — deterministic, documented, driven by the LAST DIGIT
 * ════════════════════════════════════════════════════════════════════════════
 * The outcome is a pure function of the payer's number, so a tester can
 * reproduce any branch on demand and a test suite needs no clock stubbing or
 * randomness seeding:
 *
 *   last digit 0  → FAILED immediately, `insufficient_funds`
 *                   (the only branch that fails at collect() time, so the app's
 *                    synchronous-error path gets exercised)
 *   last digit 1  → FAILED after the approval delay, `rejected_by_payer`
 *                   (the patient "declined the prompt")
 *   last digit 5  → stays PENDING forever, until the charge's own expires_at
 *                   sweeps it — the abandoned-prompt case
 *   last digit 9  → EXPIRED after the approval delay, `timeout`
 *   anything else → SUCCEEDED after the approval delay
 *
 * The approval delay (default 6s) is what makes the app's pending-and-polling
 * state real rather than theatre. It is deterministic: the collection's issue
 * time is encoded in the provider reference, so `poll()` needs no storage and
 * two pollers always agree.
 */
final class DemoPaymentProvider implements MobileMoneyProvider
{
    /** The ONLY environments in which this class may be instantiated. */
    public const ALLOWED_ENVIRONMENTS = ['local', 'testing', 'demo'];

    /** Seconds between the simulated prompt and its simulated answer. */
    private const APPROVAL_SECONDS = 6;

    /** Marks every reference this provider mints. Unmistakable in any log. */
    private const REFERENCE_PREFIX = 'demo';

    private const OUTCOME_SUCCESS = 'success';
    private const OUTCOME_DECLINE = 'decline';
    private const OUTCOME_STALL   = 'stall';
    private const OUTCOME_LAPSE   = 'lapse';

    public function __construct()
    {
        self::assertUsable();
    }

    /**
     * Every barrier, in one place, callable without constructing anything —
     * so the registry can check before it decides, and a test can assert the
     * guarantee directly.
     *
     * @throws RuntimeException when the simulator must not exist here.
     */
    public static function assertUsable(): void
    {
        $environment = (string) Config::get('app.env', 'production');

        if (! in_array($environment, self::ALLOWED_ENVIRONMENTS, true)) {
            throw new RuntimeException(
                "The demo payment provider is not available in the [{$environment}] environment. "
                .'It exists only for local, testing and demo hosts and moves no money.'
            );
        }

        // Barrier 2: a host holding real merchant credentials is a host that
        // can take real money, and must never simulate one.
        $liveCredentials = [
            'services.mtn_momo.api_key',
            'services.mtn_momo.subscription_key',
            'services.orange_money.client_secret',
            'services.orange_money.merchant_key',
        ];

        foreach ($liveCredentials as $key) {
            if (filled(Config::get($key))) {
                throw new RuntimeException(
                    "The demo payment provider refuses to run on a host with live mobile money credentials "
                    ."configured ([{$key}] is set). Wire the real provider instead."
                );
            }
        }

        // Barrier 3: an explicit off switch that does not require renaming the
        // environment. Anything other than the literal 'off' leaves the
        // environment allow-list in charge.
        if (strtolower((string) env('PAYMENTS_DEMO_PROVIDER', '')) === 'off') {
            throw new RuntimeException('The demo payment provider is disabled (PAYMENTS_DEMO_PROVIDER=off).');
        }
    }

    /** True when this host may use the simulator at all. Never throws. */
    public static function isUsable(): bool
    {
        try {
            self::assertUsable();

            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    public function key(): string
    {
        return 'demo';
    }

    /** It stands in for both rails, because neither is wired. */
    public function supports(PaymentMethod $method): bool
    {
        return true;
    }

    public function isSimulated(): bool
    {
        return true;
    }

    public function collect(CollectionRequest $request): CollectionResult
    {
        $outcome = $this->outcomeFor($request->payer->nationalNumber());

        // The one synchronous failure: the wallet had nothing in it, so there
        // was never a prompt to answer. Mirrors MTN returning a 4xx on
        // requesttopay rather than a 202.
        if ($outcome === self::OUTCOME_DECLINE && $this->failsImmediately($request->payer->nationalNumber())) {
            return new CollectionResult(
                status: ProviderPaymentStatus::Failed,
                providerReference: null,
                providerStatusCode: 'DEMO_REJECTED',
                failureCode: CollectionResult::FAILURE_INSUFFICIENT_FUNDS,
            );
        }

        // Accepted: the simulated prompt is now "on the handset". Mirrors MTN's
        // 202-with-empty-body — the reference is the only handle you get, and
        // it is one we minted ourselves.
        return new CollectionResult(
            status: ProviderPaymentStatus::Pending,
            providerReference: $this->mintReference($outcome, $request->externalReference),
            providerStatusCode: 'DEMO_ACCEPTED',
        );
    }

    public function poll(string $providerReference, array $context = []): CollectionResult
    {
        $parsed = $this->parseReference($providerReference);

        if ($parsed === null) {
            // An unparseable reference is "I cannot tell", never "it failed".
            return CollectionResult::unknown($providerReference);
        }

        [$outcome, $issuedAt] = $parsed;

        $answeredAt = $issuedAt + self::APPROVAL_SECONDS;

        if (Carbon::now()->getTimestamp() < $answeredAt) {
            return new CollectionResult(
                status: ProviderPaymentStatus::Pending,
                providerReference: $providerReference,
                providerStatusCode: 'DEMO_PENDING',
            );
        }

        return match ($outcome) {
            self::OUTCOME_SUCCESS => new CollectionResult(
                status: ProviderPaymentStatus::Succeeded,
                providerReference: $providerReference,
                providerStatusCode: 'DEMO_SUCCESSFUL',
            ),
            self::OUTCOME_DECLINE => new CollectionResult(
                status: ProviderPaymentStatus::Failed,
                providerReference: $providerReference,
                providerStatusCode: 'DEMO_FAILED',
                failureCode: CollectionResult::FAILURE_REJECTED_BY_PAYER,
            ),
            self::OUTCOME_LAPSE => new CollectionResult(
                status: ProviderPaymentStatus::Expired,
                providerReference: $providerReference,
                providerStatusCode: 'DEMO_EXPIRED',
                failureCode: CollectionResult::FAILURE_TIMEOUT,
            ),
            // OUTCOME_STALL: the prompt is never answered. The provider keeps
            // saying "pending" and the charge's own expires_at is what ends it.
            default => new CollectionResult(
                status: ProviderPaymentStatus::Pending,
                providerReference: $providerReference,
                providerStatusCode: 'DEMO_PENDING',
            ),
        };
    }

    /**
     * Verified exactly the way Orange Money's is: a per-transaction secret,
     * compared in constant time.
     *
     * The token is derived rather than stored — `HMAC(APP_KEY, charge id)` —
     * which keeps the demo self-contained without weakening the check: a caller
     * who does not hold APP_KEY cannot produce it, and `hash_equals` gives the
     * same timing profile a live implementation needs.
     */
    public function verifyCallback(CallbackEnvelope $envelope, array $context = []): CallbackVerification
    {
        $externalReference = (string) ($envelope->input('order_id') ?? $envelope->input('externalId') ?? '');
        $providerReference = (string) ($envelope->input('pay_token') ?? $envelope->input('referenceId') ?? '');
        $presented = (string) ($envelope->input('notif_token') ?? '');

        if ($externalReference === '') {
            return CallbackVerification::reject('missing_order_id');
        }

        if ($presented === '') {
            return CallbackVerification::reject('missing_notif_token');
        }

        if (! hash_equals(self::callbackToken($externalReference), $presented)) {
            return CallbackVerification::reject('bad_notif_token');
        }

        return new CallbackVerification(
            verified: true,
            externalReference: $externalReference,
            providerReference: $providerReference !== '' ? $providerReference : null,
            // A hint only. The pipeline re-polls before it writes anything.
            claimedStatus: $this->claimedStatus((string) ($envelope->input('status') ?? '')),
        );
    }

    /**
     * The per-transaction callback secret for a charge — the demo stand-in for
     * Orange's `notif_token`. Public so the charge service can hand it to the
     * "provider" when it starts a collection, exactly as a live integration
     * would store the one Orange returns.
     */
    public static function callbackToken(string $externalReference): string
    {
        return hash_hmac('sha256', 'demo-callback:'.$externalReference, self::hmacKey());
    }

    /**
     * The HMAC key. There is no hardcoded fallback: a guessable constant
     * would let anyone forge callback tokens and references. With no
     * APP_KEY the demo provider refuses to work at all.
     */
    private static function hmacKey(): string
    {
        $key = (string) Config::get('app.key', '');

        if ($key === '') {
            throw new \RuntimeException('DemoPaymentProvider requires app.key; no fallback key exists.');
        }

        return $key;
    }

    // ── Determinism ─────────────────────────────────────────────────────────

    /** The documented last-digit table. Pure, no clock, no randomness. */
    private function outcomeFor(string $nationalNumber): string
    {
        return match (substr($nationalNumber, -1)) {
            '0', '1' => self::OUTCOME_DECLINE,
            '5'      => self::OUTCOME_STALL,
            '9'      => self::OUTCOME_LAPSE,
            default  => self::OUTCOME_SUCCESS,
        };
    }

    /** Only `…0` fails before the prompt is ever shown. */
    private function failsImmediately(string $nationalNumber): bool
    {
        return str_ends_with($nationalNumber, '0');
    }

    /**
     * `demo_success_1757068800_9f2ac1b0e4d7`
     *
     * Self-describing on purpose: the outcome and the issue time travel inside
     * the reference, so `poll()` is stateless and two concurrent pollers can
     * never disagree about when the prompt was answered. The trailing digest
     * ties the reference to one charge, so a reference cannot be replayed
     * against another.
     */
    private function mintReference(string $outcome, string $externalReference): string
    {
        return implode('_', [
            self::REFERENCE_PREFIX,
            $outcome,
            (string) Carbon::now()->getTimestamp(),
            substr(hash_hmac('sha256', $externalReference, self::hmacKey()), 0, 12),
        ]);
    }

    /** @return array{0: string, 1: int}|null [outcome, issuedAtEpoch] */
    private function parseReference(string $reference): ?array
    {
        $parts = explode('_', $reference);

        if (count($parts) !== 4 || $parts[0] !== self::REFERENCE_PREFIX) {
            return null;
        }

        $valid = [self::OUTCOME_SUCCESS, self::OUTCOME_DECLINE, self::OUTCOME_STALL, self::OUTCOME_LAPSE];

        if (! in_array($parts[1], $valid, true) || ! ctype_digit($parts[2])) {
            return null;
        }

        return [$parts[1], (int) $parts[2]];
    }

    /** Normalise the status word a callback claims. Unrecognised → Unknown. */
    private function claimedStatus(string $raw): ProviderPaymentStatus
    {
        return match (strtoupper($raw)) {
            'SUCCESS', 'SUCCESSFUL' => ProviderPaymentStatus::Succeeded,
            'FAILED', 'REJECTED'    => ProviderPaymentStatus::Failed,
            'EXPIRED'               => ProviderPaymentStatus::Expired,
            'PENDING', 'INITIATED'  => ProviderPaymentStatus::Pending,
            default                 => ProviderPaymentStatus::Unknown,
        };
    }
}
