<?php

namespace App\Services\Simulators;

use Illuminate\Support\Facades\Log;

class SimulatedSmsService
{
    public function sendSms(string $phoneNumber, string $message): bool
    {
        if (config('demo.enabled') && config('demo.external_services_simulated')) {
            Log::channel('single')->info('SIMULATED SMS: To ' . $phoneNumber . ' - Message: ' . $message);
            return true;
        }

        // Logic for actual SMS would go here.
        // For the sake of demo, returning true.
        return true;
    }
}
