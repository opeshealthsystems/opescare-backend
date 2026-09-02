<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds demo public health report types and sample reports.
 * Idempotent – safe to run multiple times.
 */
class DemoPublicHealthSeeder extends Seeder
{
    private const FAC = '00000000-0000-0000-0000-100000000001';

    public function run(): void
    {
        // ── Report types ─────────────────────────────────────────────
        $types = [
            ['id' => '00000000-0000-0000-0021-100000000001', 'code' => 'WEEKLY_MORBIDITY',
             'name' => 'Weekly Morbidity Report', 'sensitivity' => 'aggregate'],
            ['id' => '00000000-0000-0000-0021-100000000002', 'code' => 'OUTPATIENT_MONTHLY',
             'name' => 'Monthly Outpatient Statistics', 'sensitivity' => 'aggregate'],
            ['id' => '00000000-0000-0000-0021-100000000003', 'code' => 'DISEASE_NOTIFICATION',
             'name' => 'Notifiable Disease Report', 'sensitivity' => 'aggregate'],
            ['id' => '00000000-0000-0000-0021-100000000004', 'code' => 'MATERNAL_HEALTH',
             'name' => 'Maternal & Child Health Monthly', 'sensitivity' => 'aggregate'],
        ];

        foreach ($types as $t) {
            if (DB::table('public_health_report_types')->where('id', $t['id'])->doesntExist()) {
                DB::table('public_health_report_types')->insert([
                    'id'                     => $t['id'],
                    'code'                   => $t['code'],
                    'name'                   => $t['name'],
                    'sensitivity_level'      => $t['sensitivity'],
                    'default_review_required'=> true,
                    'is_active'              => true,
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ]);
            }
        }

        // ── Sample reports ───────────────────────────────────────────
        $reports = [
            ['id' => '00000000-0000-0000-0022-100000000001',
             'type_id' => '00000000-0000-0000-0021-100000000001',
             'start' => now()->subWeek()->startOfWeek(),
             'end'   => now()->subWeek()->endOfWeek(),
             'status' => 'submitted'],
            ['id' => '00000000-0000-0000-0022-100000000002',
             'type_id' => '00000000-0000-0000-0021-100000000002',
             'start' => now()->subMonth()->startOfMonth(),
             'end'   => now()->subMonth()->endOfMonth(),
             'status' => 'approved_for_submission'],
            ['id' => '00000000-0000-0000-0022-100000000003',
             'type_id' => '00000000-0000-0000-0021-100000000001',
             'start' => now()->startOfWeek(),
             'end'   => now()->endOfWeek(),
             'status' => 'draft'],
        ];

        foreach ($reports as $r) {
            if (DB::table('public_health_reports')->where('id', $r['id'])->doesntExist()) {
                DB::table('public_health_reports')->insert([
                    'id'                     => $r['id'],
                    'report_type_id'         => $r['type_id'],
                    'facility_id'            => self::FAC,
                    'reporting_period_start' => $r['start'],
                    'reporting_period_end'   => $r['end'],
                    'status'                 => $r['status'],
                    'sensitivity_level'      => 'aggregate',
                    'data_classification'    => 'public',
                    'generated_by_system'    => true,
                    'data_quality_score'     => 95,
                    'requires_review'        => true,
                    'requires_correction'    => false,
                    'payload_json'           => json_encode([
                        'total_outpatient_visits' => 248,
                        'total_admissions' => 12,
                        'top_diagnoses' => ['J18.9', 'I21.1', 'K29.7'],
                    ]),
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ]);
            }
        }
    }
}
