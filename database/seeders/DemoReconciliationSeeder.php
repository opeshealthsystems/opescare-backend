<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds demo payment_reconciliation records.
 * Idempotent – inserts only if the table is empty.
 */
class DemoReconciliationSeeder extends Seeder
{
    private const FAC = '00000000-0000-0000-0000-100000000001';

    public function run(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('payment_reconciliations')) {
            return;
        }

        if (DB::table('payment_reconciliations')->count() > 0) {
            return;
        }

        DB::table('payment_reconciliations')->insert([
            [
                'id'                  => '00000000-0000-0000-0023-100000000001',
                'facility_id'         => self::FAC,
                'reconciliation_date' => now()->subDay()->toDateString(),
                'expected_cash'       => 45000,
                'actual_cash'         => 45000,
                'expected_digital'    => 15000,
                'actual_digital'      => 15000,
                'variance'            => 0,
                'status'              => 'balanced',
                'reconciled_by'       => '00000000-0000-0000-0000-200000000022',
                'notes'               => 'Demo reconciliation — all balanced.',
                'created_at'          => now()->subDay(),
                'updated_at'          => now()->subDay(),
            ],
            [
                'id'                  => '00000000-0000-0000-0023-100000000002',
                'facility_id'         => self::FAC,
                'reconciliation_date' => now()->toDateString(),
                'expected_cash'       => 45500,
                'actual_cash'         => 45000,
                'expected_digital'    => 0,
                'actual_digital'      => 0,
                'variance'            => -500,
                'status'              => 'discrepancy',
                'reconciled_by'       => '00000000-0000-0000-0000-200000000022',
                'notes'               => 'Cash short by XAF 500 — under investigation.',
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
        ]);
    }
}
