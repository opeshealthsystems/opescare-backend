<?php
namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class ProductionSafetyServiceProvider extends ServiceProvider
{
    /**
     * Boot the production safety service provider.
     * Logs critical warnings when dangerous configuration is detected in production.
     * Does NOT throw exceptions — only logs so the app can still start.
     */
    public function boot(): void
    {
        if (!$this->app->isProduction()) {
            return;
        }

        $this->checkAppDebug();
        $this->checkDemoMode();
        $this->checkMailer();
        $this->checkQueueDriver();
        $this->checkLogLevel();
        $this->checkOAuthIssuer();
    }

    /**
     * The OAuth discovery document (/.well-known/oauth-authorization-server)
     * advertises the issuer + token/introspection/jwks URLs to partners. If the
     * issuer falls back to a localhost / non-HTTPS APP_URL, those published URLs
     * are wrong. Log loudly (non-fatal) so the misconfig surfaces without
     * blocking app boot over a discovery-doc setting.
     */
    private function checkOAuthIssuer(): void
    {
        $issuer = (string) (config('services.opescare_oauth.issuer') ?: config('app.url'));

        if ($issuer === ''
            || str_contains($issuer, 'localhost')
            || str_contains($issuer, '127.0.0.1')
            || str_starts_with($issuer, 'http://')
        ) {
            Log::critical('production_safety_check_failed', [
                'check'   => 'OPESCARE_OAUTH_ISSUER',
                'message' => "OAuth discovery issuer resolves to '{$issuer}'. /.well-known/oauth-authorization-server would publish non-HTTPS or localhost endpoints to partners.",
                'action'  => 'Set OPESCARE_OAUTH_ISSUER=https://api.opescare.com (or your public API host).',
            ]);
        }
    }

    private function checkAppDebug(): void
    {
        if (config('app.debug')) {
            Log::critical('production_safety_check_failed', [
                'check'   => 'APP_DEBUG',
                'message' => 'APP_DEBUG is true in production. Stack traces will be exposed to users.',
                'action'  => 'Set APP_DEBUG=false immediately.',
            ]);

            throw new \RuntimeException('Unsafe production configuration: APP_DEBUG must be false.');
        }
    }

    private function checkDemoMode(): void
    {
        if (config('demo.enabled', false)) {
            Log::critical('production_safety_check_failed', [
                'check'   => 'OPESCARE_DEMO_MODE',
                'message' => 'Demo mode is enabled in production. This allows unauthenticated demo access.',
                'action'  => 'Set OPESCARE_DEMO_MODE=false immediately.',
            ]);

            throw new \RuntimeException('Unsafe production configuration: OPESCARE_DEMO_MODE must be false.');
        }
    }

    private function checkMailer(): void
    {
        $mailer = config('mail.default');
        if (in_array($mailer, ['log', 'null', 'array'], true)) {
            Log::critical('production_safety_check_failed', [
                'check'   => 'MAIL_MAILER',
                'message' => "Mail driver '{$mailer}' silently discards emails in production. Users will not receive notifications.",
                'action'  => 'Set MAIL_MAILER=smtp and configure SMTP credentials.',
            ]);
        }
    }

    private function checkQueueDriver(): void
    {
        if (config('queue.default') === 'sync') {
            Log::critical('production_safety_check_failed', [
                'check'   => 'QUEUE_CONNECTION',
                'message' => 'Queue connection is sync in production. Heavy jobs will block HTTP request processing.',
                'action'  => 'Set QUEUE_CONNECTION=redis or database and run queue workers.',
            ]);
        }
    }

    private function checkLogLevel(): void
    {
        if (config('logging.default') === 'daily' && config('logging.channels.daily.level') === 'debug') {
            Log::warning('production_safety_check_failed', [
                'check'   => 'LOG_LEVEL',
                'message' => 'Log level is debug in production. This may expose sensitive data in log files.',
                'action'  => 'Set LOG_LEVEL=warning.',
            ]);
        }
    }

    public function register(): void
    {
        // No bindings needed
    }
}
