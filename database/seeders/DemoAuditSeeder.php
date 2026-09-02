<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds demo medical_id_access_events so the admin audit dashboard shows data.
 * Idempotent – inserts only if no demo access events exist yet.
 */
class DemoAuditSeeder extends Seeder
{
    private const PAT1 = '00000000-0000-0000-0000-300000000001';
    private const PAT2 = '00000000-0000-0000-0000-300000000002';
    private const PAT3 = '00000000-0000-0000-0000-300000000003';
    private const DOC  = '00000000-0000-0000-0000-200000000001';
    private const FAC  = '00000000-0000-0000-0000-100000000001';

    public function run(): void
    {
        if (DB::table('medical_id_access_events')->count() > 0) {
            return;
        }

        $events = [
            [self::PAT1, 'OC-DEMO-PAT-0001',  self::DOC, 'user', self::FAC,
             'qr_scan', 'clinical_verification', 'success', now()->subHours(2)],
            [self::PAT1, 'OC-DEMO-PAT-0001',  self::DOC, 'user', self::FAC,
             'direct_lookup', 'appointment_checkin', 'success', now()->subHours(3)],
            [self::PAT3, 'OC-DEMO-EMERGENCY-0001', self::DOC, 'user', self::FAC,
             'emergency_access', 'emergency_treatment', 'success', now()->subDay()],
            [self::PAT3, 'OC-DEMO-EMERGENCY-0001', null, 'public', null,
             'public_verify', 'verification', 'denied', now()->subHours(12)],
            [self::PAT2, 'OC-DEMO-CHILD-0001', self::DOC, 'user', self::FAC,
             'qr_scan', 'outpatient_consultation', 'success', now()->subMinutes(30)],
            [self::PAT1, 'OC-DEMO-PAT-0001',  null, 'public', null,
             'public_verify', 'self_check', 'success', now()->subMinutes(10)],
        ];

        foreach ($events as $e) {
            DB::table('medical_id_access_events')->insert([
                'uuid'        => Str::uuid()->toString(),
                'patient_id'  => $e[0],
                'health_id'   => $e[1],
                'actor_id'    => $e[2],
                'actor_type'  => $e[3],
                'facility_id' => $e[4],
                'access_type' => $e[5],
                'purpose'     => $e[6],
                'result'      => $e[7],
                'ip_address'  => '127.0.0.1',
                'created_at'  => $e[8],
            ]);
        }
    }
}
