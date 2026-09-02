<?php

namespace App\Services\Simulators;

use Illuminate\Support\Facades\Log;

class SimulatedEmailService
{
    public function sendEmail(string $emailAddress, string $subject, string $message): bool
    {
        if (config('demo.enabled') && config('demo.external_services_simulated')) {
            Log::channel('single')->info('SIMULATED EMAIL: To ' . $emailAddress . ' - Subject: ' . $subject . ' - Message: ' . $message);
            return true;
        }

        // Logic for actual Email would go here.
        return true;
    }
}
