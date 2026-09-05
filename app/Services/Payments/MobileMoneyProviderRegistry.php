<?php

namespace App\Services\Payments;

use App\Services\Payments\Contracts\MobileMoneyProvider;
use App\Services\Payments\Domain\PaymentMethod;
use App\Services\Payments\Providers\DemoPaymentProvider;
use RuntimeException;

/**
 * Decides which implementation services a payment — and, on this host, that is
 * the simulator or nothing.
 *
 * This is a plain class rather than a container binding on purpose: the app's
 * `AppServiceProvider` is heavily contended, and a payment rail chosen by a
 * `bind()` call somewhere else in the file is a payment rail nobody can find.
 * The mapping lives here, in one readable table, and Laravel auto-resolves the
 * concrete class wherever it is type-hinted.
 *
 * ── Adding the real rails later ─────────────────────────────────────────────
 * When merchant credentials exist, add the live classes to `resolve()` and put
 * them BEFORE the demo fallback. The demo provider will already have refused to
 * construct on that host (it throws when live credentials are configured), so
 * there is no ordering hazard and no flag to remember to flip.
 */
final class MobileMoneyProviderRegistry
{
    /** @var array<string, MobileMoneyProvider> */
    private array $resolved = [];

    /**
     * The provider that will service this method on this host.
     *
     * @throws RuntimeException when nothing can take a payment here — which is
     *         the correct outcome on a production host with no credentials. A
     *         payment that cannot be taken must fail loudly, not silently
     *         succeed against a simulator.
     */
    public function for(PaymentMethod $method): MobileMoneyProvider
    {
        $provider = $this->resolve();

        if (! $provider->supports($method)) {
            throw new RuntimeException(
                "No mobile money provider on this host services [{$method->value}]."
            );
        }

        return $provider;
    }

    /** The single provider this host is configured with. */
    private function resolve(): MobileMoneyProvider
    {
        if (isset($this->resolved['default'])) {
            return $this->resolved['default'];
        }

        // ── Live rails go here, ahead of the fallback ───────────────────────
        // if (filled(config('services.mtn_momo.api_key'))) { ... }
        // if (filled(config('services.orange_money.client_secret'))) { ... }

        // ── Fallback: the simulator, and only where it is permitted ─────────
        // Checked BEFORE construction so a production host gets this clear
        // message rather than a constructor exception surfacing mid-payment.
        if (! DemoPaymentProvider::isUsable()) {
            throw new RuntimeException(
                'No mobile money provider is configured on this host. '
                .'Live MTN MoMo / Orange Money credentials are absent and the demo provider '
                .'is not permitted here (allowed environments: '
                .implode(', ', DemoPaymentProvider::ALLOWED_ENVIRONMENTS).').'
            );
        }

        return $this->resolved['default'] = new DemoPaymentProvider();
    }

    /**
     * Can this host take a payment at all? Lets a controller answer "payments
     * are unavailable" with a 503 instead of throwing out of a transaction.
     */
    public function isAvailable(): bool
    {
        try {
            $this->resolve();

            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    /** True when the configured provider moves no real money. */
    public function isSimulated(): bool
    {
        return $this->resolve()->isSimulated();
    }
}
