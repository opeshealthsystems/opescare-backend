<?php

namespace App\Services\Simulators;

use Illuminate\Support\Facades\Log;

class SimulatedWebhookService
{
    public function dispatchWebhook(string $url, array $payload): bool
    {
        if (config('demo.enabled') && config('demo.external_services_simulated')) {
            // Check if URL matches allowed demo domains
            $allowedDomains = config('demo.webhook.allowed_domains', []);
            $host = parse_url($url, PHP_URL_HOST);
            
            $isAllowed = false;
            foreach ($allowedDomains as $domain) {
                if ($host === $domain || str_ends_with($host, $domain)) {
                    $isAllowed = true;
                    break;
                }
            }

            if (!$isAllowed) {
                Log::channel('single')->warning('BLOCKED SIMULATED WEBHOOK: URL ' . $url . ' not in allowed demo domains.');
                return false;
            }

            Log::channel('single')->info('SIMULATED WEBHOOK: To ' . $url . ' - Payload: ' . json_encode($payload));
            return true;
        }

        // Logic for actual Webhook dispatch would go here.
        return true;
    }
}
