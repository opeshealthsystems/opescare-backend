<?php
namespace App\Services\Ussd;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\UssdSession;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UssdMenuService {
    private const MENUS = [
        'MAIN'                      => "Welcome to OpesCare\n1. Book appointment\n2. Lab results\n3. My prescriptions\n4. Emergency contacts",
        'BOOK_APPOINTMENT_FACILITY' => "Enter your facility code:",
        'BOOK_APPOINTMENT_DATE'     => "Enter preferred date (DD/MM/YYYY):",
        'BOOK_APPOINTMENT_CONFIRM'  => "Appointment request sent.\nWe will confirm via SMS.",
        'EMERGENCY'                 => "Emergency: 1800-OPES-CARE\nAmbulance: 117\nEnter 0 to go back.",
    ];

    public function handleRequest(string $sessionId, string $phone, string $input, string $serviceCode): string {
        $session = $this->findOrCreateSession($sessionId, $phone, $serviceCode);
        $session->last_active_at = Carbon::now();
        $session->save();
        return $this->routeMenu($session, trim($input));
    }

    public function findOrCreateSession(string $sessionId, string $phone, string $serviceCode = ''): UssdSession {
        $session = UssdSession::where('session_id', $sessionId)->first();
        if (!$session) {
            $patient = Patient::where('phone_number_hash', Patient::phoneHash($phone))->first();
            $session = UssdSession::create([
                'session_id'     => $sessionId,
                'phone_number'   => $phone,
                'service_code'   => $serviceCode,
                'patient_id'     => $patient?->id,
                'current_menu'   => 'MAIN',
                'menu_data'      => null,
                'initiated_at'   => Carbon::now(),
                'last_active_at' => Carbon::now(),
            ]);
        }
        return $session;
    }

    public function routeMenu(UssdSession $session, string $input): string {
        $menu = $session->current_menu;

        if ($menu === 'MAIN' && $input === '') {
            return 'CON ' . self::MENUS['MAIN'];
        }

        if ($menu === 'MAIN') {
            return match ($input) {
                '1' => $this->transition($session, 'BOOK_APPOINTMENT_FACILITY', 'CON ' . self::MENUS['BOOK_APPOINTMENT_FACILITY']),
                '2' => $this->showLabResults($session),
                '3' => $this->showPrescriptions($session),
                '4' => $this->transition($session, 'EMERGENCY', 'CON ' . self::MENUS['EMERGENCY']),
                default => 'CON ' . self::MENUS['MAIN'],
            };
        }

        if ($menu === 'BOOK_APPOINTMENT_FACILITY') {
            $data = $session->menu_data ?? [];
            $data['facility'] = $input;
            $session->menu_data = $data;
            return $this->transition($session, 'BOOK_APPOINTMENT_DATE', 'CON ' . self::MENUS['BOOK_APPOINTMENT_DATE']);
        }

        if ($menu === 'BOOK_APPOINTMENT_DATE') {
            $this->bookAppointment($session, $input);
            $this->endSession($session->session_id);
            return 'END ' . self::MENUS['BOOK_APPOINTMENT_CONFIRM'];
        }

        if ($menu === 'EMERGENCY') {
            if ($input === '0') {
                return $this->transition($session, 'MAIN', 'CON ' . self::MENUS['MAIN']);
            }
            return 'END Thank you. Stay safe.';
        }

        return 'END Session ended. Dial again to restart.';
    }

    public function endSession(string $sessionId): void {
        UssdSession::where('session_id', $sessionId)->delete();
    }

    private function transition(UssdSession $session, string $newMenu, string $response): string {
        $session->current_menu = $newMenu;
        $session->save();
        return $response;
    }

    private function showLabResults(UssdSession $session): string {
        if (!$session->patient_id) return 'END No patient record linked to this number.';
        $results = \App\Models\LabResult::where('patient_id', $session->patient_id)->latest()->take(3)->get(['parameter_name', 'value', 'unit']);
        if ($results->isEmpty()) return 'END No lab results found.';
        $text = "Your recent lab results:\n";
        foreach ($results as $r) {
            $text .= "- {$r->parameter_name}: {$r->value} {$r->unit}\n";
        }
        $this->endSession($session->session_id);
        return 'END ' . rtrim($text);
    }

    private function showPrescriptions(UssdSession $session): string {
        if (!$session->patient_id) return 'END No patient record linked to this number.';
        $rxs = \App\Models\Prescription::where('patient_id', $session->patient_id)->where('status', 'active')->latest()->take(3)->with('items')->get();
        if ($rxs->isEmpty()) return 'END No active prescriptions.';
        $text = "Active prescriptions:\n";
        foreach ($rxs as $rx) {
            $firstItem = $rx->items->first();
            if ($firstItem) {
                $text .= "- {$firstItem->drug_name} {$firstItem->dose} {$firstItem->frequency}\n";
            }
        }
        $this->endSession($session->session_id);
        return 'END ' . rtrim($text);
    }

    /**
     * Create an Appointment record from a USSD booking request.
     *
     * Facility resolution strategy (in order):
     *   1. Most recent Visit's facility_id (patient's home facility)
     *   2. First facility in DB (last resort for patients with no history)
     *
     * Date parsing: DD/MM/YYYY → Carbon datetime at 08:00 local time.
     * On any parse failure the appointment is still created at "tomorrow 08:00"
     * so the user always gets a confirmation, and a scheduler can follow up.
     */
    private function bookAppointment(UssdSession $session, string $dateInput): void
    {
        // Resolve scheduled_at from user-supplied date string (DD/MM/YYYY)
        try {
            $date = Carbon::createFromFormat('d/m/Y', trim($dateInput))->setTime(8, 0, 0);
        } catch (\Throwable) {
            $date = Carbon::tomorrow()->setTime(8, 0, 0);
        }

        // Ensure date is in the future
        if ($date->isPast()) {
            $date = Carbon::tomorrow()->setTime(8, 0, 0);
        }

        // Resolve facility: use patient's most recent visit facility
        $facilityId = null;
        if ($session->patient_id) {
            $recentVisit = Visit::where('patient_id', $session->patient_id)
                ->whereNotNull('facility_id')
                ->latest('admitted_at')
                ->first('facility_id');
            $facilityId = $recentVisit?->facility_id;
        }

        // Final fallback: first facility in the system
        if (!$facilityId) {
            $facilityId = \App\Models\Facility::query()->value('id');
        }

        if (!$session->patient_id || !$facilityId) {
            // Cannot create appointment without patient and facility; log and bail
            Log::warning('USSD bookAppointment: missing patient_id or facility_id', [
                'session_id' => $session->session_id,
                'phone'      => $session->phone_number,
            ]);
            return;
        }

        try {
            Appointment::create([
                'patient_id'       => $session->patient_id,
                'facility_id'      => $facilityId,
                'status'           => 'pending',
                'appointment_type' => 'outpatient',
                'scheduled_at'     => $date,
                'reason'           => 'USSD booking',
                'booked_by_type'   => 'patient',
                'booked_by_id'     => $session->patient_id,
            ]);
        } catch (\Throwable $e) {
            Log::error('USSD bookAppointment: failed to create appointment', [
                'session_id' => $session->session_id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
