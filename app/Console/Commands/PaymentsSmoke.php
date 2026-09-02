<?php

namespace App\Console\Commands;

use App\Services\Payments\MtnMomoService;
use App\Services\Payments\OrangeMoneyService;
use Illuminate\Console\Command;

/**
 * Go-live credential check for Mobile Money providers.
 *
 * For each configured provider, attempts to obtain an access token and reports
 * pass/fail. Unconfigured providers are skipped. Exits non-zero if any
 * configured provider cannot authenticate — run before enabling paid plans.
 */
class PaymentsSmoke extends Command
{
    protected $signature = 'payments:smoke';
    protected $description = 'Verify configured Mobile Money providers can obtain an access token (go-live check).';

    public function handle(MtnMomoService $momo, OrangeMoneyService $orange): int
    {
        $failures = 0;

        if (config('services.mtn_momo.subscription_key')) {
            $ok = $momo->canAuthenticate();
            $this->line(($ok ? '<info>✓</info>' : '<error>✗</error>').' MTN MoMo token');
            $failures += $ok ? 0 : 1;
        } else {
            $this->comment('• MTN MoMo not configured — skipped.');
        }

        if (config('services.orange_money.client_id')) {
            $ok = $orange->canAuthenticate();
            $this->line(($ok ? '<info>✓</info>' : '<error>✗</error>').' Orange Money token');
            $failures += $ok ? 0 : 1;
        } else {
            $this->comment('• Orange Money not configured — skipped.');
        }

        if ($failures > 0) {
            $this->error("$failures payment provider(s) failed to authenticate.");
            return self::FAILURE;
        }

        $this->info('✓ Payment provider check complete.');
        return self::SUCCESS;
    }
}
