<?php
namespace Database\Seeders;

use Database\Seeders\Support\DemoFacilityResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds demo pharmacy_inventories and blood_inventories.
 * Idempotent – safe to run multiple times.
 */
class DemoPharmacyStockSeeder extends Seeder
{
    // Fallbacks only. These three ids used to point at invented facilities
    // ("Demo Central Hospital", "DemoCare Pharmacy", "Demo Diagnostic
    // Laboratory") whose names surfaced in patient-facing stock and blood
    // results. Real institutions are resolved from the directory at run time.
    private const FAC_HOSPITAL_FALLBACK = '00000000-0000-0000-0000-100000000001';
    private const FAC_PHARMACY_FALLBACK = '00000000-0000-0000-0000-100000000004';
    private const FAC_LAB_FALLBACK      = '00000000-0000-0000-0000-100000000005';

    private string $facHospital = self::FAC_HOSPITAL_FALLBACK;
    private string $facPharmacy = self::FAC_PHARMACY_FALLBACK;
    private string $facLab      = self::FAC_LAB_FALLBACK;

    public function run(): void
    {
        $this->facHospital = DemoFacilityResolver::primaryHospital() ?? self::FAC_HOSPITAL_FALLBACK;
        $this->facPharmacy = DemoFacilityResolver::pharmacy()        ?? self::FAC_PHARMACY_FALLBACK;
        $this->facLab      = DemoFacilityResolver::laboratory()      ?? self::FAC_LAB_FALLBACK;

        // ── Pharmacy inventory ───────────────────────────────────────
        $medicines = [
            ['id' => '00000000-0000-0000-0011-100000000001', 'fac' => $this->facHospital,
             'name' => 'Amoxicillin', 'generic' => 'Amoxicillin', 'form' => 'Capsule',
             'strength' => '500 mg', 'qty' => 350, 'status' => 'in_stock'],
            ['id' => '00000000-0000-0000-0011-100000000002', 'fac' => $this->facHospital,
             'name' => 'Paracetamol', 'generic' => 'Paracetamol', 'form' => 'Tablet',
             'strength' => '1000 mg', 'qty' => 1200, 'status' => 'in_stock'],
            ['id' => '00000000-0000-0000-0011-100000000003', 'fac' => $this->facHospital,
             'name' => 'Metformin', 'generic' => 'Metformin HCl', 'form' => 'Tablet',
             'strength' => '500 mg', 'qty' => 28, 'status' => 'low_stock'],
            ['id' => '00000000-0000-0000-0011-100000000004', 'fac' => $this->facHospital,
             'name' => 'Atorvastatin', 'generic' => 'Atorvastatin Calcium', 'form' => 'Tablet',
             'strength' => '40 mg', 'qty' => 180, 'status' => 'in_stock'],
            ['id' => '00000000-0000-0000-0011-100000000005', 'fac' => $this->facHospital,
             'name' => 'Amlodipine', 'generic' => 'Amlodipine Besilate', 'form' => 'Tablet',
             'strength' => '5 mg', 'qty' => 0, 'status' => 'out_of_stock'],
            ['id' => '00000000-0000-0000-0011-100000000006', 'fac' => $this->facHospital,
             'name' => 'Omeprazole', 'generic' => 'Omeprazole', 'form' => 'Capsule',
             'strength' => '20 mg', 'qty' => 540, 'status' => 'in_stock'],
            ['id' => '00000000-0000-0000-0011-100000000007', 'fac' => $this->facHospital,
             'name' => 'Salbutamol Inhaler', 'generic' => 'Salbutamol', 'form' => 'MDI Inhaler',
             'strength' => '100 mcg', 'qty' => 15, 'status' => 'low_stock'],
            ['id' => '00000000-0000-0000-0011-100000000008', 'fac' => $this->facHospital,
             'name' => 'Ciprofloxacin', 'generic' => 'Ciprofloxacin HCl', 'form' => 'Tablet',
             'strength' => '500 mg', 'qty' => 200, 'status' => 'in_stock'],
            ['id' => '00000000-0000-0000-0011-100000000009', 'fac' => $this->facPharmacy,
             'name' => 'Insulin Glargine', 'generic' => 'Insulin Glargine', 'form' => 'Injection',
             'strength' => '100 IU/mL', 'qty' => 60, 'status' => 'in_stock'],
            ['id' => '00000000-0000-0000-0011-100000000010', 'fac' => $this->facPharmacy,
             'name' => 'Artemether/Lumefantrine', 'generic' => 'Artemether/Lumefantrine',
             'form' => 'Tablet', 'strength' => '20/120 mg', 'qty' => 84, 'status' => 'in_stock'],
        ];

        foreach ($medicines as $m) {
            if (DB::table('pharmacy_inventories')->where('id', $m['id'])->doesntExist()) {
                DB::table('pharmacy_inventories')->insert([
                    'id'                 => $m['id'],
                    'facility_id'        => $m['fac'],
                    'medicine_name'      => $m['name'],
                    'generic_name'       => $m['generic'],
                    'form'               => $m['form'],
                    'strength'           => $m['strength'],
                    'available_quantity' => $m['qty'],
                    'stock_status'       => $m['status'],
                    'is_expired'         => false,
                    'is_recalled'        => false,
                    'is_quarantined'     => false,
                    'last_stock_update'  => now(),
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
            }
        }

        // ── Blood inventory ──────────────────────────────────────────
        $bloodGroups = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];
        $components  = ['whole_blood', 'packed_red_cells', 'fresh_frozen_plasma', 'platelets'];
        $startUuid   = 1;

        foreach ($bloodGroups as $bg) {
            foreach ($components as $comp) {
                $id = sprintf('00000000-0000-0000-0012-%012d', $startUuid++);
                // Vary quantities realistically
                $units = match(true) {
                    $bg === 'O+' && $comp === 'packed_red_cells' => 18,
                    $bg === 'O-' => random_int(2, 6),    // rare, kept low
                    $comp === 'platelets' => random_int(4, 12),
                    default => random_int(5, 25),
                };

                if (DB::table('blood_inventories')->where('id', $id)->doesntExist()) {
                    DB::table('blood_inventories')->insert([
                        'id'               => $id,
                        'facility_id'      => $this->facHospital,
                        'blood_group'      => $bg,
                        'component'        => $comp,
                        'available_units'  => $units,
                        'is_expired'       => false,
                        'is_quarantined'   => false,
                        'is_unsafe'        => false,
                        'last_stock_update'=> now(),
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ]);
                }
            }
        }
    }
}
