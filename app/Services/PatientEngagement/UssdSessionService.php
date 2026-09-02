<?php
namespace App\Services\PatientEngagement;

use App\Models\UssdSession;

class UssdSessionService
{
    private string $mainMenu = "Welcome to OpesCare\n1. My Appointments\n2. My Results\n3. Find a Clinic\n4. Emergency Contacts\n0. Exit";

    public function handle(
        string $sessionId,
        string $phoneNumber,
        string $text,
        string $serviceCode,
    ): array {
        $session = UssdSession::firstOrCreate(
            ['session_id' => $sessionId],
            [
                'phone_number'   => $phoneNumber,
                'service_code'   => $serviceCode,
                'current_menu'   => 'main',
                'initiated_at'   => now(),
                'last_active_at' => now(),
            ]
        );

        $session->update(['last_active_at' => now()]);

        if ($text === '') {
            return $this->respond('CON', $this->mainMenu);
        }

        $inputs = explode('*', $text);
        return match ($inputs[0]) {
            '1' => $this->respond('CON', "My Appointments\n1. View next appointment\n2. Book appointment\n0. Back"),
            '2' => $this->respond('CON', "My Results\nPlease visit the patient portal or call your facility."),
            '3' => $this->respond('CON', "Find a Clinic\nVisit opescare.com/caremap"),
            '4' => $this->respond('END', "Emergency: 1510 (SAMU)\nOpesCare: +237 XXX XXX XXX"),
            '0' => $this->respond('END', "Thank you for using OpesCare. Stay healthy!"),
            default => $this->respond('END', "Invalid option. Please try again."),
        };
    }

    private function respond(string $type, string $message): array
    {
        return ['type' => $type, 'message' => $message];
    }
}
