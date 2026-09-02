<?php

namespace App\Console\Commands;

use App\Services\Staff\CredentialingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class NotifyExpiringCredentialsCommand extends Command
{
    protected $signature = 'opescare:notify-expiring-credentials {--days=30 : Number of days ahead to check}';

    protected $description = 'Notify administrators of provider credentials expiring soon';

    public function __construct(private readonly CredentialingService $credentialingService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $days        = (int) $this->option('days');
        $credentials = $this->credentialingService->getExpiringCredentials($days);

        if ($credentials->isEmpty()) {
            $this->info("No credentials expiring within {$days} days.");
            Log::info("opescare:notify-expiring-credentials: No credentials expiring within {$days} days.");
            return self::SUCCESS;
        }

        $this->info("Found {$credentials->count()} credential(s) expiring within {$days} days:");

        $tableRows = [];
        foreach ($credentials as $credential) {
            $providerName = optional($credential->provider)->name ?? $credential->provider_id;
            $expiryDate   = $credential->expiry_date->format('Y-m-d');

            $tableRows[] = [
                $providerName,
                $credential->credential_type,
                $credential->issuing_body,
                $expiryDate,
                $credential->status,
            ];

            Log::warning('Provider credential expiring soon', [
                'credential_id'   => $credential->id,
                'provider_id'     => $credential->provider_id,
                'provider_name'   => $providerName,
                'credential_type' => $credential->credential_type,
                'expiry_date'     => $expiryDate,
                'days_remaining'  => (int) now()->diffInDays($credential->expiry_date),
            ]);
        }

        $this->table(
            ['Provider', 'Type', 'Issuing Body', 'Expiry Date', 'Status'],
            $tableRows,
        );

        // Fire notification event — receivers hook into this; falls back to log if no listeners configured
        // event(new \App\Events\CredentialsExpiringSoon($credentials));

        return self::SUCCESS;
    }
}
