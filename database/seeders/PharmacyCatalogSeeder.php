<?php

namespace Database\Seeders;

use App\Enums\MedicineCategory;
use App\Enums\PharmacyStockStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the Medicine Finder reference data: a medicine catalog plus
 * per-pharmacy stock held at REAL pharmacies from the facility directory.
 *
 * Idempotent — every row has a stable UUID and is written with updateOrInsert,
 * so re-running refreshes prices/stock without duplicating rows.
 *
 * This seeder used to insert six invented directory listings ("OpesCare
 * Pharmacy", "PharmaPlus Bonapriso", ...) into care_facilities and hang the
 * stock off those. care_facilities is the public, patient-facing directory, so
 * that put fabricated businesses in front of patients alongside real
 * MINSANTE/OSM-sourced institutions. Stock levels here remain illustrative
 * demo data, but the pharmacies holding them are now real, and are resolved at
 * run time by resolvePharmacies().
 *
 * Depends on the real pharmacy directory existing first — run
 * CameroonOsmPharmacySeeder (real names + OSM coordinates) and
 * CareMapRegistryStubSeeder (promotes registry -> care_facilities) before this.
 * Coordinates are required: MedicineFinderService filters on
 * whereNotNull('latitude')/('longitude').
 *
 * Run:  php artisan db:seed --class=PharmacyCatalogSeeder
 */
class PharmacyCatalogSeeder extends Seeder
{
    /**
     * Catalog. Prices are indicative national ranges in XAF (FCFA).
     * [ id_suffix, name, generic, strength, form, category, rx?, description, indications, pack sizes, min, max ]
     */
    private function catalog(): array
    {
        return [
            ['001', 'Paracetamol 500mg Tablet', 'Paracetamol', '500mg', 'tablet', MedicineCategory::PainRelief, false,
                'Relieves mild to moderate pain such as headache and body aches, and reduces fever.',
                ['Pain relief', 'Fever', 'Headache'], ['10 tablets', '20 tablets', '50 tablets'], 200, 500],
            ['002', 'Ibuprofen 400mg Tablet', 'Ibuprofen', '400mg', 'tablet', MedicineCategory::PainRelief, false,
                'Anti-inflammatory painkiller for muscular pain, period pain, and fever.',
                ['Pain relief', 'Inflammation', 'Fever'], ['10 tablets', '20 tablets'], 300, 750],
            ['003', 'Diclofenac 50mg Tablet', 'Diclofenac Sodium', '50mg', 'tablet', MedicineCategory::PainRelief, true,
                'Prescription anti-inflammatory used for arthritis and moderate to severe pain.',
                ['Arthritis', 'Inflammation', 'Pain relief'], ['20 tablets'], 500, 1200],
            ['004', 'Amoxicillin 500mg Capsule', 'Amoxicillin', '500mg', 'capsule', MedicineCategory::Antibiotics, true,
                'Broad-spectrum penicillin antibiotic for chest, ear, throat, and urinary infections.',
                ['Bacterial infection', 'Chest infection'], ['15 capsules', '21 capsules'], 1500, 3000],
            ['005', 'Amoxicillin + Clavulanic Acid 625mg Tablet', 'Co-amoxiclav', '625mg', 'tablet', MedicineCategory::Antibiotics, true,
                'Combination antibiotic used when amoxicillin alone is not sufficient.',
                ['Bacterial infection', 'Resistant infection'], ['14 tablets'], 3500, 6500],
            ['006', 'Azithromycin 500mg Tablet', 'Azithromycin', '500mg', 'tablet', MedicineCategory::Antibiotics, true,
                'Macrolide antibiotic given as a short course for respiratory and skin infections.',
                ['Bacterial infection', 'Respiratory infection'], ['3 tablets', '6 tablets'], 2000, 4500],
            ['007', 'Ciprofloxacin 500mg Tablet', 'Ciprofloxacin', '500mg', 'tablet', MedicineCategory::Antibiotics, true,
                'Fluoroquinolone antibiotic used mainly for urinary and gastrointestinal infections.',
                ['Urinary infection', 'Bacterial infection'], ['10 tablets'], 1200, 2800],
            ['008', 'Metformin 500mg Tablet', 'Metformin Hydrochloride', '500mg', 'tablet', MedicineCategory::Diabetes, true,
                'First-line medicine for type 2 diabetes; lowers blood sugar produced by the liver.',
                ['Type 2 diabetes', 'Blood sugar control'], ['30 tablets', '60 tablets'], 1000, 2500],
            ['009', 'Glibenclamide 5mg Tablet', 'Glibenclamide', '5mg', 'tablet', MedicineCategory::Diabetes, true,
                'Sulfonylurea that stimulates insulin release, used in type 2 diabetes.',
                ['Type 2 diabetes'], ['30 tablets'], 800, 2000],
            ['010', 'Insulin Glargine 100IU/mL Injection', 'Insulin Glargine', '100IU/mL', 'injection', MedicineCategory::Diabetes, true,
                'Long-acting basal insulin providing 24-hour background blood-sugar control. Keep refrigerated.',
                ['Type 1 diabetes', 'Type 2 diabetes'], ['1 pen (3ml)', '5 pens (3ml)'], 12000, 28000],
            ['011', 'Amlodipine 5mg Tablet', 'Amlodipine', '5mg', 'tablet', MedicineCategory::Cardio, true,
                'Calcium-channel blocker that lowers blood pressure and relieves angina.',
                ['High blood pressure', 'Angina'], ['30 tablets'], 900, 2200],
            ['012', 'Lisinopril 10mg Tablet', 'Lisinopril', '10mg', 'tablet', MedicineCategory::Cardio, true,
                'ACE inhibitor for high blood pressure and heart failure.',
                ['High blood pressure', 'Heart failure'], ['30 tablets'], 1200, 2800],
            ['013', 'Atorvastatin 20mg Tablet', 'Atorvastatin', '20mg', 'tablet', MedicineCategory::Cardio, true,
                'Statin that lowers cholesterol and reduces cardiovascular risk.',
                ['High cholesterol'], ['30 tablets'], 2500, 5500],
            ['014', 'Vitamin C 500mg Tablet', 'Ascorbic Acid', '500mg', 'tablet', MedicineCategory::Vitamins, false,
                'Vitamin C supplement supporting immune function and iron absorption.',
                ['Immune support', 'Supplement'], ['20 tablets', '60 tablets'], 500, 1500],
            ['015', 'Ferrous Sulphate + Folic Acid Tablet', 'Ferrous Sulphate/Folic Acid', '200mg/0.4mg', 'tablet', MedicineCategory::Vitamins, false,
                'Iron and folic acid supplement used to prevent and treat anaemia, especially in pregnancy.',
                ['Anaemia', 'Pregnancy', 'Supplement'], ['30 tablets', '90 tablets'], 700, 1800],
            ['016', 'Multivitamin Syrup 100ml', 'Multivitamin', '100ml', 'syrup', MedicineCategory::Vitamins, false,
                'Liquid multivitamin for children and adults with poor dietary intake.',
                ['Supplement', 'Children'], ['1 bottle (100ml)'], 1500, 3200],
            ['017', 'Salbutamol 100mcg Inhaler', 'Salbutamol', '100mcg/dose', 'inhaler', MedicineCategory::Respiratory, true,
                'Reliever inhaler that opens the airways quickly during an asthma attack.',
                ['Asthma', 'Wheezing', 'Breathlessness'], ['1 inhaler (200 doses)'], 3000, 6500],
            ['018', 'Cetirizine 10mg Tablet', 'Cetirizine', '10mg', 'tablet', MedicineCategory::Respiratory, false,
                'Non-drowsy antihistamine for hay fever, allergic rhinitis, and itching.',
                ['Allergy', 'Hay fever', 'Itching'], ['10 tablets', '30 tablets'], 400, 1200],
            ['019', 'Beclometasone 250mcg Inhaler', 'Beclometasone Dipropionate', '250mcg/dose', 'inhaler', MedicineCategory::Respiratory, true,
                'Preventer inhaler taken daily to control asthma inflammation.',
                ['Asthma', 'Preventer'], ['1 inhaler (200 doses)'], 5000, 11000],
            ['020', 'Hydrocortisone 1% Cream 15g', 'Hydrocortisone', '1%', 'cream', MedicineCategory::SkinCare, false,
                'Mild steroid cream for eczema, insect bites, and irritated skin.',
                ['Eczema', 'Itching', 'Skin irritation'], ['1 tube (15g)'], 800, 2000],
            ['021', 'Ketoconazole 2% Cream 30g', 'Ketoconazole', '2%', 'cream', MedicineCategory::SkinCare, false,
                'Antifungal cream for ringworm, athlete\'s foot, and fungal skin infections.',
                ['Fungal infection', 'Ringworm'], ['1 tube (30g)'], 1200, 2800],
            ['022', 'Oral Rehydration Salts Sachet', 'Oral Rehydration Salts', 'WHO formula', 'sachet', MedicineCategory::Digestive, false,
                'Replaces fluid and salts lost through diarrhoea or vomiting. Essential for children.',
                ['Diarrhoea', 'Dehydration', 'Children'], ['10 sachets', '20 sachets'], 300, 900],
            ['023', 'Omeprazole 20mg Capsule', 'Omeprazole', '20mg', 'capsule', MedicineCategory::Digestive, false,
                'Reduces stomach acid; used for reflux, heartburn, and stomach ulcers.',
                ['Heartburn', 'Reflux', 'Ulcer'], ['14 capsules', '28 capsules'], 1500, 3500],
            ['024', 'Artemether + Lumefantrine 20/120mg Tablet', 'Artemether/Lumefantrine', '20/120mg', 'tablet', MedicineCategory::Antimalarial, false,
                'First-line ACT treatment for uncomplicated malaria. Complete the full course.',
                ['Malaria', 'Fever'], ['24 tablets'], 1000, 3000],
            ['025', 'Artesunate 60mg Injection', 'Artesunate', '60mg', 'injection', MedicineCategory::Antimalarial, true,
                'Injectable treatment for severe malaria, given in a health facility.',
                ['Severe malaria'], ['1 vial'], 2500, 6000],
            ['026', 'Zinc Sulphate 20mg Dispersible Tablet', 'Zinc Sulphate', '20mg', 'tablet', MedicineCategory::MaternalChild, false,
                'Given with ORS for 10-14 days to shorten childhood diarrhoea.',
                ['Diarrhoea', 'Children'], ['10 tablets'], 300, 900],
            ['027', 'Paracetamol 120mg/5ml Syrup 60ml', 'Paracetamol', '120mg/5ml', 'syrup', MedicineCategory::MaternalChild, false,
                'Children\'s paracetamol suspension for fever and pain. Dose by weight.',
                ['Fever', 'Pain relief', 'Children'], ['1 bottle (60ml)'], 600, 1500],
        ];
    }

    public function run(): void
    {
        $now = now();

        $pharmacyIds = $this->resolvePharmacies();

        if ($pharmacyIds === []) {
            $this->command?->warn(
                'PharmacyCatalogSeeder: no real GPS-bearing pharmacy found in care_facilities — '
                . 'skipping stock. Run CameroonOsmPharmacySeeder + CareMapRegistryStubSeeder first.'
            );
            return;
        }

        $medicineIds = $this->seedMedicines($now);
        $this->seedStock($medicineIds, $pharmacyIds, $now);

        $this->command?->info(sprintf(
            'PharmacyCatalogSeeder: %d real pharmacies stocked, %d medicines, %d stock rows.',
            count($pharmacyIds),
            count($medicineIds),
            DB::table('medicine_pharmacy_stocks')->count(),
        ));
    }

    /**
     * Picks real pharmacies from the directory to hold the demo stock.
     *
     * This seeder used to insert six invented listings ("OpesCare Pharmacy",
     * "PharmaPlus Bonapriso", ...) straight into care_facilities — the public,
     * patient-facing directory — so fabricated businesses appeared alongside
     * real MINSANTE/OSM-sourced institutions. Stock levels are illustrative,
     * but the pharmacies holding them must be real.
     *
     * GPS is required, not optional: MedicineFinderService filters on
     * whereNotNull('latitude')/('longitude'), so stock at a coordinate-less
     * pharmacy could never surface in the Medicine Finder.
     *
     * @return list<string> care_facilities ids
     */
    private function resolvePharmacies(): array
    {
        return DB::table('care_facilities')
            ->where('facility_type', 'pharmacy')
            ->where('country_code', 'CM')
            ->where('listing_status', 'active')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('facility_name')   // deterministic across runs
            ->limit(6)
            ->pluck('id')
            ->all();
    }


    /** @return array<string,string> catalog suffix => medicine uuid */
    private function seedMedicines(\Illuminate\Support\Carbon $now): array
    {
        $ids = [];

        foreach ($this->catalog() as $row) {
            [$suffix, $name, $generic, $strength, $form, $category, $rx,
                $description, $indications, $packs, $priceMin, $priceMax] = $row;

            $id = '00000000-0000-0000-0000-a20000000' . $suffix;
            $ids[$suffix] = $id;

            DB::table('medicines')->updateOrInsert(
                ['id' => $id],
                [
                    'name'                  => $name,
                    'generic_name'          => $generic,
                    'brand_name'            => null,
                    'strength'              => $strength,
                    'form'                  => $form,
                    'category'              => $category->value,
                    'description'           => $description,
                    'indications'           => json_encode($indications),
                    'prescription_required' => $rx,
                    'is_controlled'         => false,
                    'default_pack_size'     => $packs[0],
                    'pack_size_options'     => json_encode($packs),
                    'price_min'             => $priceMin,
                    'price_max'             => $priceMax,
                    'currency'              => 'XAF',
                    'is_active'             => true,
                    'updated_at'            => $now,
                    'created_at'            => $now,
                ],
            );
        }

        return $ids;
    }

    /**
     * Stock per pharmacy. Paracetamol 500mg is pinned to the exact prices and
     * stock levels from the Medicine Finder design reference so the screen can
     * be diffed against it; every other medicine is spread deterministically
     * (hash of medicine id + pharmacy id) so the data is varied but stable
     * across re-seeds.
     *
     * @param  array<string,string>  $medicineIds
     */
    /**
     * @param list<string> $pharmacyIds care_facilities ids resolved by resolvePharmacies()
     */
    private function seedStock(array $medicineIds, array $pharmacyIds, \Illuminate\Support\Carbon $now): void
    {
        // Position in $pharmacyIds => [status, packs, unit price] for Paracetamol
        // 500mg, so the flagship medicine always shows a readable spread of
        // in-stock / low-stock / out-of-stock across the pharmacy list.
        $paracetamolReference = [
            0 => [PharmacyStockStatus::InStock, 45, 250],
            1 => [PharmacyStockStatus::InStock, 32, 250],
            2 => [PharmacyStockStatus::InStock, 12, 300],
            3 => [PharmacyStockStatus::InStock, 7, 350],
            4 => [PharmacyStockStatus::LowStock, 3, 400],
            5 => [PharmacyStockStatus::OutOfStock, 0, null],
        ];

        $catalog = collect($this->catalog())->keyBy(0);

        foreach ($pharmacyIds as $index => $pharmacyId) {
            foreach ($medicineIds as $suffix => $medicineId) {
                $row      = $catalog[$suffix];
                $priceMin = (int) $row[10];
                $priceMax = (int) $row[11];
                $packs    = $row[9];

                if ($suffix === '001' && isset($paracetamolReference[$index])) {
                    [$status, $packsAvailable, $price] = $paracetamolReference[$index];
                } else {
                    // Deterministic spread: same inputs always give the same row.
                    $seed  = crc32($medicineId . $pharmacyId);
                    $bucket = $seed % 10;

                    $status = match (true) {
                        $bucket <= 5 => PharmacyStockStatus::InStock,
                        $bucket <= 7 => PharmacyStockStatus::LowStock,
                        $bucket === 8 => PharmacyStockStatus::OutOfStock,
                        default      => PharmacyStockStatus::Unknown,
                    };

                    $packsAvailable = match ($status) {
                        PharmacyStockStatus::InStock  => 8 + ($seed % 40),
                        PharmacyStockStatus::LowStock => 1 + ($seed % 4),
                        default                       => 0,
                    };

                    $span  = max(1, $priceMax - $priceMin);
                    $price = $status === PharmacyStockStatus::OutOfStock
                        ? null
                        : $priceMin + ($seed % $span);
                }

                $stockId = $this->deterministicId('medicine-stock', $medicineId, $pharmacyId);

                // Match on (medicine_id, care_facility_id) — the table's real
                // unique key (medicine_pharmacy_stocks_unique) — not on the
                // synthetic id. Keying on id breaks the moment a stock row is
                // moved to a different pharmacy: the derived id no longer
                // matches the existing row, so the insert collides on the
                // natural key instead of updating it.
                $payload = [
                    'stock_status'        => $status->value,
                    'packs_available'     => $packsAvailable,
                    'pack_size'           => $packs[0],
                    'unit_price'          => $price,
                    'currency'            => 'XAF',
                    'reservation_enabled' => $status->isReservable(),
                    'source_system'       => 'seed',
                    'last_stocked_at'     => $now->copy()->subDays(($packsAvailable % 7) + 1),
                    'last_reported_at'    => $now,
                    'updated_at'          => $now,
                ];

                // Existence is decided on (medicine_id, care_facility_id) — the
                // table's real unique key — but the row's own id is never
                // rewritten on update: reservations reference it, and a stock
                // row that has been moved between pharmacies would otherwise
                // have its primary key changed underneath them.
                $existing = DB::table('medicine_pharmacy_stocks')
                    ->where('medicine_id', $medicineId)
                    ->where('care_facility_id', $pharmacyId)
                    ->first(['id']);

                if ($existing !== null) {
                    DB::table('medicine_pharmacy_stocks')
                        ->where('id', $existing->id)
                        ->update($payload);
                } else {
                    DB::table('medicine_pharmacy_stocks')->insert($payload + [
                        'id'               => $stockId,
                        'medicine_id'      => $medicineId,
                        'care_facility_id' => $pharmacyId,
                        'created_at'       => $now,
                    ]);
                }
            }
        }
    }

    /**
     * Stable UUID-shaped id derived from a namespace plus its parts, so
     * re-seeding updates the same row instead of inserting a new one.
     */
    private function deterministicId(string $namespace, string ...$parts): string
    {
        $hash = md5('opescare:' . $namespace . ':' . implode(':', $parts));

        return sprintf(
            '%s-%s-5%s-%s-%s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 13, 3),
            substr($hash, 16, 4),
            substr($hash, 20, 12),
        );
    }
}
