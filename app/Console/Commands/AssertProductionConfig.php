<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Fail-fast production configuration gate.
 *
 * Run in CI/deploy before serving traffic. Exits non-zero (and prints each
 * problem) when required or unsafe configuration is detected, so a misconfigured
 * release never reaches users.
 */
class AssertProductionConfig extends Command
{
    protected $signature = 'config:assert-production';
    protected $description = 'Fail fast if required production configuration is missing or unsafe.';

    public function handle(): int
    {
        $errors = [];

        if (empty(config('app.key'))) {
            $errors[] = 'APP_KEY is not set.';
        }
        if (config('app.debug') === true) {
            $errors[] = 'APP_DEBUG must be false in production.';
        }
        if (config('cache.default') !== 'redis') {
            $errors[] = 'CACHE_STORE should be redis in production (got: '.config('cache.default').').';
        }
        if (config('queue.default') !== 'redis') {
            $errors[] = 'QUEUE_CONNECTION should be redis in production (got: '.config('queue.default').').';
        }
        if (in_array(config('session.driver'), ['array', null], true)) {
            $errors[] = 'SESSION_DRIVER is not durable (got: '.config('session.driver').').';
        }
        if (empty(config('mail.default'))) {
            $errors[] = 'MAIL_MAILER is not set.';
        }

        // Billing providers: if a key is partially configured, require it complete
        // so a half-set provider never silently fails at checkout time.
        $momoKey = config('services.mtn_momo.subscription_key');
        if (!empty($momoKey) && empty(config('services.mtn_momo.api_key'))) {
            $errors[] = 'MTN MoMo partially configured (subscription_key set, api_key missing).';
        }
        $orangeId = config('services.orange_money.client_id');
        if (!empty($orangeId) && empty(config('services.orange_money.client_secret'))) {
            $errors[] = 'Orange Money partially configured (client_id set, client_secret missing).';
        }

        if (!empty($errors)) {
            foreach ($errors as $e) {
                $this->error('  ✗ '.$e);
            }
            $this->error(count($errors).' production config problem(s) found.');
            return self::FAILURE;
        }

        $this->info('✓ Production configuration looks good.');
        return self::SUCCESS;
    }
}
