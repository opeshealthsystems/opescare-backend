<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds demo consent grants for demo patients.
 * Idempotent – safe to run multiple times.
 */
class DemoConsentSeeder extends Seeder
{
    private const PAT1 = '00000000-0000-0000-0000-300000000001';
    private const PAT2 = '00000000-0000-0000-0000-300000000002';
    private const PAT3 = '00000000-0000-0000-0000-300000000003';
    private const FAC  = '00000000-0000-0000-0000-100000000001';

    public function run(): void
    {
        $grants = [
            ['id' => '00000000-0000-0000-0020-100000000001',
             'patient_id' => self::PAT1, 'facility_id' => self::FAC,
             'actor' => 'patient',
             'scope' => ['outpatient', 'pharmacy', 'lab', 'emergency_access'],
             'expires_at' => now()->addYear()],
            ['id' => '00000000-0000-0000-0020-100000000002',
             'patient_id' => self::PAT2, 'facility_id' => self::FAC,
             'actor' => 'guardian',
             'scope' => ['outpatient', 'immunisation', 'lab'],
             'expires_at' => now()->addYear()],
            ['id' => '00000000-0000-0000-0020-100000000003',
             'patient_id' => self::PAT3, 'facility_id' => self::FAC,
             'actor' => 'facility_policy',
             'scope' => ['emergency_access', 'inpatient', 'specialist'],
             'expires_at' => now()->addMonths(6)],
        ];

        foreach ($grants as $g) {
            if (DB::table('consent_grants')->where('id', $g['id'])->doesntExist()) {
                DB::table('consent_grants')->insert([
                    'id'               => $g['id'],
                    'patient_id'       => $g['patient_id'],
                    'facility_id'      => $g['facility_id'],
                    'authorizing_actor' => $g['actor'],
                    'scope'            => json_encode($g['scope']),
                    'status'           => 'active',
                    'expires_at'       => $g['expires_at'],
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }
        }
    }
}
