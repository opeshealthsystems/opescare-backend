<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Patient;
use App\Models\HealthIdQrToken;
use App\Models\MedicalIdAccessEvent;
use App\Services\Identity\HealthIdGeneratorService;
use App\Services\Identity\QrTokenService;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MedicalIdDemoSeeder extends Seeder
{
    public function run(): void
    {
        $generator = new HealthIdGeneratorService();
        $qrService = new QrTokenService();

        // Check if there is already a patient with a health_id
        $patient = Patient::whereNotNull('health_id')->first();

        if (!$patient) {
            $patient = Patient::first();
            if (!$patient) {
                // Create a demo patient if none exists
                $patient = Patient::create([
                    'first_name' => 'Demo',
                    'last_name' => 'Patient',
                    'date_of_birth' => '1985-06-15',
                    'sex' => 'female',
                    'phone_number' => '+237600000000',
                    'country_code' => 'CM',
                    'verification_status' => 'facility_verified',
                    'identity_status' => 'verified',
                    'is_demo' => true
                ]);
            }

            // Assign a Health ID
            $patient->health_id = $generator->generate($patient->country_code ?? 'CM');
            $patient->save();
        }

        // Generate Qr Tokens
        $qrService->generateToken($patient->id, 'static_identity_qr');
        $qrService->generateToken($patient->id, 'temporary_consent_qr', 60);

        // Generate Access Logs
        $purposes = ['treatment', 'pharmacy_dispense', 'lab_order', 'insurance_claim'];
        $results = ['success', 'success', 'success', 'denied']; // 75% success rate
        $types = ['verify_health_id', 'verify_qr'];

        for ($i = 0; $i < 15; $i++) {
            MedicalIdAccessEvent::create([
                'patient_id' => $patient->id,
                'health_id' => $patient->health_id,
                'actor_id' => Str::uuid(),
                'actor_type' => 'facility_staff',
                'facility_id' => Str::uuid(),
                'access_type' => $types[array_rand($types)],
                'purpose' => $purposes[array_rand($purposes)],
                'result' => $results[array_rand($results)],
                'ip_address' => '192.168.1.' . rand(1, 255),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) OpesCareDemo/1.0',
                'created_at' => Carbon::now()->subHours(rand(1, 72))->subMinutes(rand(0, 59)),
            ]);
        }
        
        // Generate a random invalid access log
        MedicalIdAccessEvent::create([
            'patient_id' => $patient->id, // Used existing patient to bypass DB constraints in older migrations
            'health_id' => 'CM-HID-XXXX-YYYY-ZZZZ',
            'actor_id' => Str::uuid(),
            'actor_type' => 'api_client',
            'access_type' => 'verify_health_id',
            'purpose' => 'insurance_claim',
            'result' => 'denied',
            'ip_address' => '10.0.0.45',
            'user_agent' => 'InsuranceBot/2.0',
            'created_at' => Carbon::now()->subMinutes(15),
        ]);

        // Create a secondary patient for duplicate review
        $secondaryPatient = Patient::create([
            'first_name' => 'Demo',
            'last_name' => 'Test (Duplicate)',
            'date_of_birth' => '1985-06-15',
            'sex' => 'female',
            'phone_number' => '+237600000001',
            'country_code' => 'CM',
            'verification_status' => 'duplicate_suspected',
            'identity_status' => 'unverified',
            'is_demo' => true,
            'health_id' => $generator->generate('CM')
        ]);

        // Create an IdentityMergeCase
        \App\Models\IdentityMergeCase::create([
            'uuid' => Str::uuid(),
            'primary_patient_id' => $patient->id,
            'secondary_patient_id' => $secondaryPatient->id,
            'status' => 'pending_review',
            'match_score' => 92.5,
        ]);
    }
}
