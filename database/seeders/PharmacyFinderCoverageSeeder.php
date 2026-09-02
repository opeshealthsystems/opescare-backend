<?php

namespace Database\Seeders;

use App\Enums\MedicineCategory as Cat;
use App\Enums\PharmacyStockStatus as Stock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Medicine Finder coverage — national essential-medicines catalogue plus
 * reported stock across the real pharmacy directory.
 *
 * Two separate kinds of data live here and they must not be confused:
 *
 *  1. `medicines` is a REFERENCE CATALOGUE, not demo data. Every row is a real
 *     product from the WHO Model List of Essential Medicines / the Cameroon
 *     national essential medicines list: real INN generic names, real WHO ATC
 *     codes, real strengths and dosage forms. Prices are indicative national
 *     XAF ranges. Nothing here is invented.
 *
 *  2. `medicine_pharmacy_stocks` is necessarily SYNTHETIC — no pharmacy in the
 *     country reports to us yet — so every row this seeder writes is stamped
 *     `source_system = 'demo_seed'`. That stamp is the whole point: when real
 *     feeds arrive (bridge agent, partner API, pharmacist self-report) they are
 *     trivially separable from seeded claims, and this seeder never overwrites
 *     a row it did not write.
 *
 * Why the coverage matters: measured on 2026-08-31 the finder held 27 medicines
 * and stock for 9 of 379 pharmacies (243 rows). A patient searching for anything
 * ordinary got an empty screen. `docs/plans/V1_LAUNCH_SCOPE.md` calls this the
 * #1 launch blocker and sets the gates honoured here: >= 300 medicines,
 * >= 150 pharmacies with stock, >= 3,000 stock rows.
 *
 * Institutional data is never touched. The 897 rows in `care_facilities` are
 * real MINSANTE / OSM-sourced institutions: this seeder only ADDS stock rows
 * pointing at them, and only at pharmacies that carry coordinates — the finder
 * filters on `whereNotNull('latitude')`, so a GPS-less pharmacy could never
 * surface and seeding it would be wasted work.
 *
 * Idempotent: medicine ids are derived from (ATC code + product name), stock
 * rows are matched on the table's real unique key (medicine_id,
 * care_facility_id) with an explicit insert-vs-update branch, so a stock row's
 * primary key is never rewritten (medicine_reservations.stock_id points at it).
 *
 * Run:  php artisan db:seed --class=PharmacyFinderCoverageSeeder
 */
class PharmacyFinderCoverageSeeder extends Seeder
{
    /** Stamped on every stock row so real feeds stay distinguishable. */
    private const SOURCE_SYSTEM = 'demo_seed';

    /**
     * How widely a medicine is stocked, as a probability at a mid-sized
     * pharmacy. Paracetamol is behind almost every counter in the country;
     * trastuzumab is behind almost none. A finder where everything is in stock
     * everywhere is as useless as an empty one.
     */
    private const UBIQUITY = [
        'core'       => 0.95,   // the shelf every pharmacy has
        'common'     => 0.55,
        'standard'   => 0.25,
        'uncommon'   => 0.10,
        'specialist' => 0.03,   // referral / programme supply
        'hospital'   => 0.012,  // theatre, ICU, oncology day unit
        'controlled' => 0.02,   // narcotics & psychotropics — licensed outlets only
    ];

    /**
     * Pharmacy size mix, as cumulative buckets over 0..99, and the exponent
     * applied to each medicine's ubiquity. alpha < 1 widens a big pharmacy's
     * range; alpha > 1 collapses a kiosk down to the essentials; `null` is a
     * pharmacy that reports nothing at all, which is the honest majority case
     * for a network that has just launched.
     */
    private const PHARMACY_TIERS = [
        ['ceiling' => 10,  'alpha' => null],   // 10% report nothing
        ['ceiling' => 18,  'alpha' => 0.60],   //  8% large / hospital-adjacent
        ['ceiling' => 48,  'alpha' => 1.00],   // 30% medium
        ['ceiling' => 100, 'alpha' => 2.40],   // 52% small neighbourhood outlet
    ];

    /**
     * The 27 catalogue entries seeded by PharmacyCatalogSeeder, which predate
     * the `atc_code` column being populated. Listed here so (a) their ATC codes
     * get backfilled and (b) they take part in the stock spread at the right
     * ubiquity — the brief calls out paracetamol, amoxicillin, metformin,
     * amlodipine and ORS as the drugs that must be stocked widely, and all five
     * live in that original 27.
     *
     * Keyed by the exact `medicines.name` written by that seeder.
     *
     * @var array<string, array{0:string, 1:string}>  name => [atc, ubiquity tier]
     */
    private const LEGACY_CATALOGUE = [
        'Paracetamol 500mg Tablet'                        => ['N02BE01', 'core'],
        'Paracetamol 120mg/5ml Syrup 60ml'                => ['N02BE01', 'core'],
        'Ibuprofen 400mg Tablet'                          => ['M01AE01', 'core'],
        'Diclofenac 50mg Tablet'                          => ['M01AB05', 'common'],
        'Amoxicillin 500mg Capsule'                       => ['J01CA04', 'core'],
        'Amoxicillin + Clavulanic Acid 625mg Tablet'      => ['J01CR02', 'common'],
        'Azithromycin 500mg Tablet'                       => ['J01FA10', 'common'],
        'Ciprofloxacin 500mg Tablet'                      => ['J01MA02', 'common'],
        'Metformin 500mg Tablet'                          => ['A10BA02', 'core'],
        'Glibenclamide 5mg Tablet'                        => ['A10BB01', 'common'],
        'Insulin Glargine 100IU/mL Injection'             => ['A10AE04', 'uncommon'],
        'Amlodipine 5mg Tablet'                           => ['C08CA01', 'core'],
        'Lisinopril 10mg Tablet'                          => ['C09AA03', 'common'],
        'Atorvastatin 20mg Tablet'                        => ['C10AA05', 'common'],
        'Vitamin C 500mg Tablet'                          => ['A11GA01', 'common'],
        'Ferrous Sulphate + Folic Acid Tablet'            => ['B03AD03', 'common'],
        'Multivitamin Syrup 100ml'                        => ['A11BA',   'common'],
        'Salbutamol 100mcg Inhaler'                       => ['R03AC02', 'core'],
        'Cetirizine 10mg Tablet'                          => ['R06AE07', 'common'],
        'Beclometasone 250mcg Inhaler'                    => ['R03BA01', 'standard'],
        'Hydrocortisone 1% Cream 15g'                     => ['D07AA02', 'common'],
        'Ketoconazole 2% Cream 30g'                       => ['D01AC08', 'common'],
        'Oral Rehydration Salts Sachet'                   => ['A07CA',   'core'],
        'Omeprazole 20mg Capsule'                         => ['A02BC01', 'core'],
        'Artemether + Lumefantrine 20/120mg Tablet'       => ['P01BF01', 'core'],
        'Artesunate 60mg Injection'                       => ['P01BE03', 'uncommon'],
        'Zinc Sulphate 20mg Dispersible Tablet'           => ['A12CB01', 'common'],
    ];

    // ── Entry point ─────────────────────────────────────────────────────────

    public function run(): void
    {
        $now = Carbon::now();

        [$catalogueInserted, $catalogueUpdated, $catalogueSkipped] = $this->seedCatalogue($now);
        $legacyBackfilled = $this->backfillLegacyAtcCodes();

        $pharmacyIds = $this->resolvePharmacies();

        if ($pharmacyIds === []) {
            $this->command?->warn(
                'PharmacyFinderCoverageSeeder: no active pharmacy with coordinates found in '
                . 'care_facilities — run CameroonOsmPharmacySeeder + CareMapRegistryStubSeeder first.'
            );

            return;
        }

        [$stockInserted, $stockUpdated, $stockLeftAlone] = $this->seedStock($pharmacyIds, $now);

        $this->command?->info(sprintf(
            'PharmacyFinderCoverageSeeder: catalogue +%d new / %d refreshed / %d skipped (name already taken), '
            . '%d legacy ATC codes backfilled.',
            $catalogueInserted,
            $catalogueUpdated,
            $catalogueSkipped,
            $legacyBackfilled,
        ));

        $this->command?->info(sprintf(
            'PharmacyFinderCoverageSeeder: stock +%d new / %d refreshed / %d left to their owning source; '
            . '%d medicines, %d pharmacies with stock, %d stock rows total.',
            $stockInserted,
            $stockUpdated,
            $stockLeftAlone,
            DB::table('medicines')->count(),
            DB::table('medicine_pharmacy_stocks')->distinct()->count('care_facility_id'),
            DB::table('medicine_pharmacy_stocks')->count(),
        ));
    }

    // ── 1. Reference catalogue ──────────────────────────────────────────────

    /**
     * @return array{0:int, 1:int, 2:int} inserted, updated, skipped
     */
    private function seedCatalogue(Carbon $now): array
    {
        $inserted = $updated = $skipped = 0;

        foreach ($this->catalogue() as $row) {
            [$name, $generic, $strength, $form, $category, $atc, $rx,
                $controlled, $priceMin, $priceMax, , $pack, $about, $indications] = $row;

            $id = $this->deterministicId('eml-medicine', $atc, $name);

            // The existing 27 entries are kept, never replaced. If a catalogue
            // name is already taken by a different row, that row wins and this
            // one is skipped rather than creating a duplicate product.
            $clash = DB::table('medicines')
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->where('id', '!=', $id)
                ->exists();

            if ($clash) {
                $skipped++;

                continue;
            }

            $payload = [
                'name'                  => $name,
                'generic_name'          => $generic,
                'strength'              => $strength,
                'form'                  => $form,
                'category'              => $category->value,
                'atc_code'              => $atc,
                'description'           => $about,
                'indications'           => json_encode($indications, JSON_UNESCAPED_UNICODE),
                'prescription_required' => $rx,
                'is_controlled'         => $controlled,
                'default_pack_size'     => $pack,
                'pack_size_options'     => json_encode([$pack], JSON_UNESCAPED_UNICODE),
                'price_min'             => $priceMin,
                'price_max'             => $priceMax,
                'currency'              => 'XAF',
                'is_active'             => true,
                'updated_at'            => $now,
            ];

            if (DB::table('medicines')->where('id', $id)->exists()) {
                DB::table('medicines')->where('id', $id)->update($payload);
                $updated++;
            } else {
                DB::table('medicines')->insert($payload + ['id' => $id, 'created_at' => $now]);
                $inserted++;
            }
        }

        return [$inserted, $updated, $skipped];
    }

    /**
     * The original 27 catalogue rows were written before `atc_code` was used,
     * so they carry NULL. Filling it in does not replace them — every other
     * column is left exactly as PharmacyCatalogSeeder wrote it.
     */
    private function backfillLegacyAtcCodes(): int
    {
        $filled = 0;

        foreach (self::LEGACY_CATALOGUE as $name => [$atc, $_tier]) {
            $filled += DB::table('medicines')
                ->where('name', $name)
                ->whereNull('atc_code')
                ->update(['atc_code' => $atc]);
        }

        return $filled;
    }

    // ── 2. Stock coverage ───────────────────────────────────────────────────

    /**
     * Real pharmacies that the finder can actually surface.
     *
     * MedicineFinderService::nearbyPharmacies() filters on
     * facility_type = 'pharmacy', listing_status = 'active' and non-null
     * coordinates, so anything outside that set is invisible to a patient and
     * seeding it would be wasted rows.
     *
     * @return list<string>
     */
    private function resolvePharmacies(): array
    {
        return DB::table('care_facilities')
            ->where('facility_type', 'pharmacy')
            ->where('listing_status', 'active')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('id')          // deterministic across runs
            ->pluck('id')
            ->all();
    }

    /**
     * @param  list<string>  $pharmacyIds
     * @return array{0:int, 1:int, 2:int} inserted, updated, left alone
     */
    private function seedStock(array $pharmacyIds, Carbon $now): array
    {
        $medicines = DB::table('medicines')
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'name', 'default_pack_size', 'price_min', 'price_max']);

        $ubiquity = $this->ubiquityByMedicineId();

        // Existing rows, keyed by the table's real unique key. Loaded once —
        // a per-row existence query across ~330 medicines x ~340 pharmacies
        // would be a hundred thousand round trips.
        $existing = [];
        DB::table('medicine_pharmacy_stocks')
            ->select(['id', 'medicine_id', 'care_facility_id', 'source_system'])
            ->orderBy('id')
            ->chunk(5000, function ($rows) use (&$existing): void {
                foreach ($rows as $row) {
                    $existing[$row->medicine_id . '|' . $row->care_facility_id] = $row;
                }
            });

        $toInsert = [];
        $toUpdate = [];
        $leftAlone = 0;

        foreach ($pharmacyIds as $pharmacyId) {
            $alpha = $this->pharmacyAlpha($pharmacyId);

            if ($alpha === null) {
                continue;   // this pharmacy reports nothing
            }

            foreach ($medicines as $medicine) {
                $u = $ubiquity[$medicine->id] ?? self::UBIQUITY['uncommon'];

                // Carrying probability: ubiquity shaped by the pharmacy's size.
                if ($this->unitHash('carry', $medicine->id, $pharmacyId) >= $u ** $alpha) {
                    continue;
                }

                $key = $medicine->id . '|' . $pharmacyId;
                $row = $existing[$key] ?? null;

                // A row written by another source (a real feed, or the curated
                // Medicine Finder design reference in PharmacyCatalogSeeder) is
                // never rewritten by a demo seeder.
                if ($row !== null && $row->source_system !== self::SOURCE_SYSTEM) {
                    $leftAlone++;

                    continue;
                }

                $payload = $this->stockPayload($medicine, $pharmacyId, $u, $now);

                if ($row !== null) {
                    // Match on the natural key, update by the row's OWN id: the
                    // primary key is referenced by medicine_reservations.stock_id
                    // and must never be rewritten.
                    $toUpdate[] = ['id' => $row->id] + $payload;
                } else {
                    $toInsert[] = $payload + [
                        'id'               => $this->deterministicId('medicine-stock', $medicine->id, $pharmacyId),
                        'medicine_id'      => $medicine->id,
                        'care_facility_id' => $pharmacyId,
                        'created_at'       => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($toInsert, 500) as $chunk) {
            DB::table('medicine_pharmacy_stocks')->insert($chunk);
        }

        foreach (array_chunk($toUpdate, 500) as $chunk) {
            $this->bulkUpdateStock($chunk);
        }

        return [count($toInsert), count($toUpdate), $leftAlone];
    }

    /**
     * One stock row's mutable columns. Every value is derived deterministically
     * from (medicine id, pharmacy id), so a re-run reproduces the same shelf
     * rather than reshuffling the whole country's stock.
     *
     * @return array<string, mixed>
     */
    private function stockPayload(object $medicine, string $pharmacyId, float $ubiquity, Carbon $now): array
    {
        $status = $this->stockStatus($medicine->id, $pharmacyId, $ubiquity);

        $packs = match ($status) {
            Stock::InStock    => 6 + (int) ($this->unitHash('packs', $medicine->id, $pharmacyId) * 60),
            Stock::LowStock   => 1 + (int) ($this->unitHash('packs', $medicine->id, $pharmacyId) * 4),
            Stock::OutOfStock => 0,
            Stock::Unknown    => null,
        };

        // Price only where the pharmacy is actually claiming to hold the item —
        // quoting a price against out-of-stock or unknown stock is a lie.
        $price = $status->isAvailable()
            ? $this->shelfPrice((float) $medicine->price_min, (float) $medicine->price_max, $medicine->id, $pharmacyId)
            : null;

        $reportedAt = $this->reportedAt($medicine->id, $pharmacyId, $now);

        return [
            'stock_status'        => $status->value,
            'packs_available'     => $packs,
            'pack_size'           => $medicine->default_pack_size,
            'unit_price'          => $price,
            'currency'            => 'XAF',
            'reservation_enabled' => $status->isReservable(),
            'source_system'       => self::SOURCE_SYSTEM,
            'last_stocked_at'     => $reportedAt->copy()->subDays(
                1 + (int) ($this->unitHash('stocked', $medicine->id, $pharmacyId) * 21)
            ),
            'last_reported_at'    => $reportedAt,
            'updated_at'          => $now,
        ];
    }

    /**
     * Availability mix. Widely-stocked essentials are usually on the shelf;
     * the rarer an item is, the more often the answer is "we ran out" or
     * "we do not know" — which is what a reported-stock network actually looks
     * like. Unknown is a first-class state and is never rendered as available.
     */
    private function stockStatus(string $medicineId, string $pharmacyId, float $ubiquity): Stock
    {
        $r = $this->unitHash('status', $medicineId, $pharmacyId);

        $inStock  = 0.50 + 0.25 * $ubiquity;
        $lowStock = $inStock + 0.20 - 0.05 * $ubiquity;
        $outOf    = $lowStock + 0.22 - 0.12 * $ubiquity;

        return match (true) {
            $r < $inStock  => Stock::InStock,
            $r < $lowStock => Stock::LowStock,
            $r < $outOf    => Stock::OutOfStock,
            default        => Stock::Unknown,
        };
    }

    /**
     * A shelf price inside the medicine's indicative national range, rounded to
     * a denomination that exists in XAF cash.
     */
    private function shelfPrice(float $min, float $max, string $medicineId, string $pharmacyId): float
    {
        if ($min <= 0.0 && $max <= 0.0) {
            return 0.0;
        }

        $max   = max($max, $min);
        $price = $min + ($max - $min) * $this->unitHash('price', $medicineId, $pharmacyId);
        $step  = $price >= 1000 ? 50 : 25;

        return (float) (max(1, (int) round($price / $step)) * $step);
    }

    /**
     * Freshness spread. `last_reported_at` is the finder's only trust signal —
     * if every row is identically fresh the signal carries no information and
     * a stale-stock warning can never be exercised. Roughly 63% of rows land
     * inside the 7-day window the launch gate measures, the rest fan out to
     * ~30 days so some listings render as stale.
     */
    private function reportedAt(string $medicineId, string $pharmacyId, Carbon $now): Carbon
    {
        $f = $this->unitHash('freshness', $medicineId, $pharmacyId);

        $days = match (true) {
            $f < 0.63 => (int) (($f / 0.63) * 7),                  // 0-6 days   (63%)
            $f < 0.80 => 7 + (int) ((($f - 0.63) / 0.17) * 7),     // 7-13 days  (17%)
            $f < 0.92 => 14 + (int) ((($f - 0.80) / 0.12) * 7),    // 14-20 days (12%)
            default   => 21 + (int) ((($f - 0.92) / 0.08) * 10),   // 21-30 days  (8%)
        };

        $minutes = (int) ($this->unitHash('freshness-minute', $medicineId, $pharmacyId) * 1439);

        return $now->copy()->subDays($days)->subMinutes($minutes);
    }

    /**
     * Which size bucket a pharmacy falls into, as the exponent applied to each
     * medicine's ubiquity. Null means the pharmacy reports no stock at all.
     */
    private function pharmacyAlpha(string $pharmacyId): ?float
    {
        $bucket = (int) ($this->unitHash('pharmacy-tier', $pharmacyId) * 100);

        foreach (self::PHARMACY_TIERS as $tier) {
            if ($bucket < $tier['ceiling']) {
                return $tier['alpha'];
            }
        }

        return null;
    }

    /**
     * Ubiquity per medicine id, resolved by product name across both the
     * catalogue seeded here and the 27 entries that predate it.
     *
     * @return array<string, float>
     */
    private function ubiquityByMedicineId(): array
    {
        $tierByName = [];

        foreach (self::LEGACY_CATALOGUE as $name => [$_atc, $tier]) {
            $tierByName[mb_strtolower($name)] = $tier;
        }

        foreach ($this->catalogue() as $row) {
            $tierByName[mb_strtolower($row[0])] = $row[10];
        }

        $map = [];

        foreach (DB::table('medicines')->get(['id', 'name']) as $medicine) {
            $tier = $tierByName[mb_strtolower($medicine->name)] ?? 'uncommon';
            $map[$medicine->id] = self::UBIQUITY[$tier] ?? self::UBIQUITY['uncommon'];
        }

        return $map;
    }

    // ── Plumbing ────────────────────────────────────────────────────────────

    /**
     * Refreshes an already-seeded batch in one statement. Tens of thousands of
     * single-row UPDATEs would make a re-seed take minutes; this is PostgreSQL's
     * UPDATE ... FROM (VALUES ...), and the row ids come from the existing rows
     * so no primary key is ever rewritten.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    private function bulkUpdateStock(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $placeholders = [];
        $bindings     = [];

        foreach ($rows as $row) {
            $placeholders[] = '(?::uuid, ?::varchar, ?::integer, ?::varchar, ?::numeric, '
                . '?::boolean, ?::varchar, ?::timestamp, ?::timestamp, ?::timestamp)';

            array_push(
                $bindings,
                $row['id'],
                $row['stock_status'],
                $row['packs_available'],
                $row['pack_size'],
                $row['unit_price'],
                $row['reservation_enabled'] ? 'true' : 'false',
                $row['source_system'],
                $row['last_stocked_at']->format('Y-m-d H:i:s'),
                $row['last_reported_at']->format('Y-m-d H:i:s'),
                $row['updated_at']->format('Y-m-d H:i:s'),
            );
        }

        DB::statement(
            'UPDATE medicine_pharmacy_stocks AS s SET '
            . 'stock_status = v.stock_status, '
            . 'packs_available = v.packs_available, '
            . 'pack_size = v.pack_size, '
            . 'unit_price = v.unit_price, '
            . 'reservation_enabled = v.reservation_enabled, '
            . 'source_system = v.source_system, '
            . 'last_stocked_at = v.last_stocked_at, '
            . 'last_reported_at = v.last_reported_at, '
            . 'updated_at = v.updated_at '
            . 'FROM (VALUES ' . implode(', ', $placeholders) . ') AS v ('
            . 'id, stock_status, packs_available, pack_size, unit_price, '
            . 'reservation_enabled, source_system, last_stocked_at, last_reported_at, updated_at) '
            . 'WHERE s.id = v.id',
            $bindings,
        );
    }

    /** Deterministic float in [0,1) — same inputs always give the same shelf. */
    private function unitHash(string $namespace, string ...$parts): float
    {
        $hash = substr(md5('opescare:' . $namespace . ':' . implode(':', $parts)), 0, 8);

        return hexdec($hash) / 4294967296.0;
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

    // ── The catalogue ───────────────────────────────────────────────────────

    /**
     * WHO Model List of Essential Medicines / Cameroon national essential
     * medicines list.
     *
     * Row shape:
     *  0 name · 1 INN generic · 2 strength · 3 dosage form · 4 shopper category
     *  5 WHO ATC code · 6 prescription required · 7 controlled substance
     *  8 price_min (XAF) · 9 price_max (XAF) · 10 ubiquity tier
     *  11 default pack · 12 patient-facing description · 13 indications
     *
     * @return list<array{0:string,1:string,2:string,3:string,4:Cat,5:string,6:bool,7:bool,8:int,9:int,10:string,11:string,12:string,13:list<string>}>
     */
    private function catalogue(): array
    {
        return array_merge(
            $this->catalogueAntibacterials(),
            $this->catalogueAntiInfectives(),
            $this->cataloguePainAndNeuro(),
            $this->catalogueChronicCare(),
            $this->catalogueMaternalChildAndTopical(),
            $this->catalogueHospitalAndSpecialist(),
        );
    }

    /** Antibacterials, antituberculosis and antileprosy agents. */
    private function catalogueAntibacterials(): array
    {
        return [
            ['Amoxicillin 250mg/5ml Powder for Oral Suspension 100ml', 'Amoxicillin', '250mg/5ml', 'suspension', Cat::Antibiotics, 'J01CA04', true, false, 1200, 2800, 'core', '1 bottle (100ml)',
                'Children\'s amoxicillin suspension for chest, ear and throat infections. Dose by weight.', ['Bacterial infection', 'Children']],
            ['Ampicillin 500mg Powder for Injection', 'Ampicillin', '500mg', 'injection', Cat::Antibiotics, 'J01CA01', true, false, 500, 1500, 'standard', '1 vial',
                'Injectable penicillin used in hospital for serious bacterial infections.', ['Bacterial infection']],
            ['Benzathine Benzylpenicillin 2.4MU Powder for Injection', 'Benzathine Benzylpenicillin', '2.4 MU', 'injection', Cat::Antibiotics, 'J01CE08', true, false, 1200, 3500, 'standard', '1 vial',
                'Single long-acting penicillin injection for syphilis and rheumatic fever prevention.', ['Syphilis', 'Rheumatic fever']],
            ['Benzylpenicillin 1MU Powder for Injection', 'Benzylpenicillin', '1 MU', 'injection', Cat::Antibiotics, 'J01CE01', true, false, 400, 1200, 'standard', '1 vial',
                'Fast-acting injectable penicillin for severe infections including meningitis.', ['Bacterial infection', 'Meningitis']],
            ['Procaine Benzylpenicillin 3MU Powder for Injection', 'Procaine Benzylpenicillin', '3 MU', 'injection', Cat::Antibiotics, 'J01CE09', true, false, 800, 2200, 'uncommon', '1 vial',
                'Slow-release penicillin injection given once daily.', ['Bacterial infection']],
            ['Phenoxymethylpenicillin 250mg Tablet', 'Phenoxymethylpenicillin', '250mg', 'tablet', Cat::Antibiotics, 'J01CE02', true, false, 700, 2000, 'standard', '20 tablets',
                'Oral penicillin for throat infections and to prevent rheumatic fever.', ['Throat infection', 'Prophylaxis']],
            ['Cloxacillin 500mg Capsule', 'Cloxacillin', '500mg', 'capsule', Cat::Antibiotics, 'J01CF02', true, false, 1500, 3500, 'common', '20 capsules',
                'Penicillin that resists staphylococcal enzymes; used for skin, wound and bone infections.', ['Skin infection', 'Wound infection']],
            ['Cefalexin 500mg Capsule', 'Cefalexin', '500mg', 'capsule', Cat::Antibiotics, 'J01DB01', true, false, 1800, 4500, 'common', '12 capsules',
                'First-generation cephalosporin for skin, urinary and respiratory infections.', ['Bacterial infection', 'Urinary infection']],
            ['Cefuroxime 500mg Tablet', 'Cefuroxime Axetil', '500mg', 'tablet', Cat::Antibiotics, 'J01DC02', true, false, 3500, 8000, 'standard', '10 tablets',
                'Second-generation cephalosporin for respiratory and urinary infections.', ['Respiratory infection', 'Urinary infection']],
            ['Cefixime 200mg Tablet', 'Cefixime', '200mg', 'tablet', Cat::Antibiotics, 'J01DD08', true, false, 2500, 6000, 'common', '10 tablets',
                'Oral third-generation cephalosporin, including for gonorrhoea and typhoid.', ['Bacterial infection', 'Typhoid']],
            ['Cefotaxime 1g Powder for Injection', 'Cefotaxime', '1g', 'injection', Cat::Antibiotics, 'J01DD01', true, false, 1500, 4000, 'standard', '1 vial',
                'Injectable cephalosporin for severe infections and meningitis.', ['Severe infection', 'Meningitis']],
            ['Ceftriaxone 1g Powder for Injection', 'Ceftriaxone', '1g', 'injection', Cat::Antibiotics, 'J01DD04', true, false, 1200, 3500, 'common', '1 vial',
                'Once-daily injectable cephalosporin widely used for severe infections.', ['Severe infection', 'Meningitis']],
            ['Ceftazidime 1g Powder for Injection', 'Ceftazidime', '1g', 'injection', Cat::Antibiotics, 'J01DD02', true, false, 3000, 8000, 'uncommon', '1 vial',
                'Injectable cephalosporin active against Pseudomonas.', ['Severe infection']],
            ['Cefazolin 1g Powder for Injection', 'Cefazolin', '1g', 'injection', Cat::Antibiotics, 'J01DB04', true, false, 1200, 3200, 'uncommon', '1 vial',
                'Given before surgery to prevent wound infection.', ['Surgical prophylaxis']],
            ['Meropenem 1g Powder for Injection', 'Meropenem', '1g', 'injection', Cat::Antibiotics, 'J01DH02', true, false, 12000, 32000, 'hospital', '1 vial',
                'Reserve carbapenem antibiotic for multi-resistant hospital infections.', ['Severe infection', 'Resistant infection']],
            ['Piperacillin + Tazobactam 4g/0.5g Powder for Injection', 'Piperacillin/Tazobactam', '4g/0.5g', 'injection', Cat::Antibiotics, 'J01CR05', true, false, 8000, 20000, 'hospital', '1 vial',
                'Broad-spectrum injectable combination for serious hospital-acquired infections.', ['Severe infection']],
            ['Chloramphenicol 250mg Capsule', 'Chloramphenicol', '250mg', 'capsule', Cat::Antibiotics, 'J01BA01', true, false, 800, 2200, 'uncommon', '20 capsules',
                'Reserve antibiotic for typhoid and meningitis where alternatives are unavailable.', ['Typhoid', 'Meningitis']],
            ['Clarithromycin 500mg Tablet', 'Clarithromycin', '500mg', 'tablet', Cat::Antibiotics, 'J01FA09', true, false, 4000, 9000, 'standard', '14 tablets',
                'Macrolide antibiotic for chest infections and part of ulcer eradication therapy.', ['Respiratory infection', 'Ulcer']],
            ['Erythromycin 250mg Tablet', 'Erythromycin', '250mg', 'tablet', Cat::Antibiotics, 'J01FA01', true, false, 1200, 3000, 'standard', '20 tablets',
                'Macrolide antibiotic, useful when penicillin cannot be given.', ['Bacterial infection', 'Penicillin allergy']],
            ['Clindamycin 300mg Capsule', 'Clindamycin', '300mg', 'capsule', Cat::Antibiotics, 'J01FF01', true, false, 3000, 7500, 'standard', '16 capsules',
                'Antibiotic for dental, bone and severe skin infections.', ['Dental infection', 'Skin infection']],
            ['Doxycycline 100mg Capsule', 'Doxycycline', '100mg', 'capsule', Cat::Antibiotics, 'J01AA02', true, false, 600, 1800, 'common', '10 capsules',
                'Tetracycline antibiotic for chest infections, acne and some tropical diseases.', ['Bacterial infection', 'Acne']],
            ['Gentamicin 40mg/mL Injection', 'Gentamicin', '40mg/mL', 'injection', Cat::Antibiotics, 'J01GB03', true, false, 300, 1000, 'standard', '1 ampoule (2ml)',
                'Aminoglycoside injection for severe infections; blood levels are monitored.', ['Severe infection']],
            ['Amikacin 250mg/mL Injection', 'Amikacin', '250mg/mL', 'injection', Cat::Antibiotics, 'J01GB06', true, false, 2500, 6500, 'uncommon', '1 vial (2ml)',
                'Aminoglycoside for resistant infections and drug-resistant tuberculosis.', ['Resistant infection']],
            ['Streptomycin 1g Powder for Injection', 'Streptomycin', '1g', 'injection', Cat::Antibiotics, 'J01GA01', true, false, 1500, 4000, 'uncommon', '1 vial',
                'Injectable antibiotic used in tuberculosis regimens.', ['Tuberculosis']],
            ['Metronidazole 400mg Tablet', 'Metronidazole', '400mg', 'tablet', Cat::Antibiotics, 'J01XD01', true, false, 300, 1000, 'core', '21 tablets',
                'Treats anaerobic bacterial infections, amoebiasis, giardiasis and dental abscess.', ['Amoebiasis', 'Dental infection']],
            ['Metronidazole 500mg/100ml Infusion', 'Metronidazole', '500mg/100ml', 'infusion', Cat::Antibiotics, 'J01XD01', true, false, 700, 2000, 'standard', '1 bag (100ml)',
                'Intravenous metronidazole for severe anaerobic infection.', ['Severe infection']],
            ['Nitrofurantoin 100mg Tablet', 'Nitrofurantoin', '100mg', 'tablet', Cat::Antibiotics, 'J01XE01', true, false, 1200, 3000, 'standard', '14 tablets',
                'Antibiotic concentrated in urine, used for bladder infections.', ['Urinary infection']],
            ['Sulfamethoxazole + Trimethoprim 400mg/80mg Tablet', 'Co-trimoxazole', '400mg/80mg', 'tablet', Cat::Antibiotics, 'J01EE01', true, false, 300, 1000, 'core', '20 tablets',
                'Combination antibiotic; also given long-term to prevent infection in HIV.', ['Bacterial infection', 'HIV prophylaxis']],
            ['Sulfamethoxazole + Trimethoprim 200mg/40mg per 5ml Suspension 100ml', 'Co-trimoxazole', '200mg/40mg per 5ml', 'suspension', Cat::Antibiotics, 'J01EE01', true, false, 700, 1800, 'common', '1 bottle (100ml)',
                'Children\'s co-trimoxazole suspension. Dose by weight.', ['Bacterial infection', 'Children']],
            ['Trimethoprim 200mg Tablet', 'Trimethoprim', '200mg', 'tablet', Cat::Antibiotics, 'J01EA01', true, false, 600, 1600, 'uncommon', '14 tablets',
                'Single-agent antibiotic for uncomplicated urinary infection.', ['Urinary infection']],
            ['Levofloxacin 500mg Tablet', 'Levofloxacin', '500mg', 'tablet', Cat::Antibiotics, 'J01MA12', true, false, 2500, 6000, 'standard', '7 tablets',
                'Fluoroquinolone for pneumonia, urinary infection and drug-resistant tuberculosis.', ['Pneumonia', 'Urinary infection']],
            ['Ofloxacin 200mg Tablet', 'Ofloxacin', '200mg', 'tablet', Cat::Antibiotics, 'J01MA01', true, false, 1200, 3000, 'standard', '10 tablets',
                'Fluoroquinolone antibiotic for urinary and genital infections.', ['Urinary infection']],
            ['Moxifloxacin 400mg Tablet', 'Moxifloxacin', '400mg', 'tablet', Cat::Antibiotics, 'J01MA14', true, false, 5000, 13000, 'uncommon', '7 tablets',
                'Fluoroquinolone used in drug-resistant tuberculosis regimens.', ['Tuberculosis', 'Respiratory infection']],
            ['Vancomycin 500mg Powder for Injection', 'Vancomycin', '500mg', 'injection', Cat::Antibiotics, 'J01XA01', true, false, 6000, 16000, 'hospital', '1 vial',
                'Reserve antibiotic for MRSA and severe resistant infection.', ['Resistant infection']],
            ['Linezolid 600mg Tablet', 'Linezolid', '600mg', 'tablet', Cat::Antibiotics, 'J01XX08', true, false, 15000, 40000, 'specialist', '10 tablets',
                'Reserve antibiotic, also used in drug-resistant tuberculosis.', ['Resistant infection', 'Tuberculosis']],
            ['Fosfomycin 3g Sachet', 'Fosfomycin Trometamol', '3g', 'sachet', Cat::Antibiotics, 'J01XX01', true, false, 4000, 10000, 'uncommon', '1 sachet',
                'Single-dose oral antibiotic for uncomplicated bladder infection.', ['Urinary infection']],
            ['Colistimethate Sodium 1MU Powder for Injection', 'Colistimethate Sodium', '1 MU', 'injection', Cat::Antibiotics, 'J01XB01', true, false, 12000, 30000, 'hospital', '1 vial',
                'Last-line antibiotic for multi-resistant Gram-negative infection.', ['Resistant infection']],
            ['Spectinomycin 2g Powder for Injection', 'Spectinomycin', '2g', 'injection', Cat::Antibiotics, 'J01XX04', true, false, 3000, 8000, 'specialist', '1 vial',
                'Single injection for gonorrhoea when other options cannot be used.', ['Gonorrhoea']],
            ['Azithromycin 200mg/5ml Powder for Oral Suspension 30ml', 'Azithromycin', '200mg/5ml', 'suspension', Cat::Antibiotics, 'J01FA10', true, false, 2000, 4500, 'common', '1 bottle (30ml)',
                'Children\'s azithromycin suspension, given as a short course.', ['Bacterial infection', 'Children']],
            ['Isoniazid 300mg Tablet', 'Isoniazid', '300mg', 'tablet', Cat::Antibiotics, 'J04AC01', true, false, 500, 1500, 'standard', '28 tablets',
                'Core tuberculosis medicine, also used to prevent TB in people with HIV.', ['Tuberculosis']],
            ['Rifampicin 300mg Capsule', 'Rifampicin', '300mg', 'capsule', Cat::Antibiotics, 'J04AB02', true, false, 1500, 4000, 'standard', '30 capsules',
                'Core tuberculosis and leprosy medicine. Turns urine orange, which is harmless.', ['Tuberculosis', 'Leprosy']],
            ['Ethambutol 400mg Tablet', 'Ethambutol', '400mg', 'tablet', Cat::Antibiotics, 'J04AK02', true, false, 900, 2500, 'standard', '28 tablets',
                'Part of first-line tuberculosis treatment. Report any change in vision.', ['Tuberculosis']],
            ['Pyrazinamide 500mg Tablet', 'Pyrazinamide', '500mg', 'tablet', Cat::Antibiotics, 'J04AK01', true, false, 900, 2500, 'standard', '28 tablets',
                'Part of the intensive phase of tuberculosis treatment.', ['Tuberculosis']],
            ['Rifampicin + Isoniazid 150mg/75mg Tablet', 'Rifampicin/Isoniazid', '150mg/75mg', 'tablet', Cat::Antibiotics, 'J04AM02', true, false, 2000, 5000, 'standard', '28 tablets',
                'Fixed-dose combination for the continuation phase of tuberculosis treatment.', ['Tuberculosis']],
            ['Rifampicin + Isoniazid + Pyrazinamide + Ethambutol 150/75/400/275mg Tablet', 'RHZE Fixed-Dose Combination', '150/75/400/275mg', 'tablet', Cat::Antibiotics, 'J04AM06', true, false, 3000, 7500, 'standard', '28 tablets',
                'Four-drug fixed-dose combination for the intensive phase of tuberculosis treatment.', ['Tuberculosis']],
            ['Bedaquiline 100mg Tablet', 'Bedaquiline', '100mg', 'tablet', Cat::Antibiotics, 'J04AK05', true, false, 60000, 180000, 'specialist', '24 tablets',
                'Programme-supplied medicine for multidrug-resistant tuberculosis.', ['Drug-resistant tuberculosis']],
            ['Delamanid 50mg Tablet', 'Delamanid', '50mg', 'tablet', Cat::Antibiotics, 'J04AK06', true, false, 70000, 200000, 'specialist', '48 tablets',
                'Programme-supplied medicine for multidrug-resistant tuberculosis.', ['Drug-resistant tuberculosis']],
            ['Cycloserine 250mg Capsule', 'Cycloserine', '250mg', 'capsule', Cat::Antibiotics, 'J04AB01', true, false, 20000, 55000, 'specialist', '100 capsules',
                'Second-line tuberculosis medicine for resistant disease.', ['Drug-resistant tuberculosis']],
            ['Ethionamide 250mg Tablet', 'Ethionamide', '250mg', 'tablet', Cat::Antibiotics, 'J04AD03', true, false, 12000, 32000, 'specialist', '100 tablets',
                'Second-line tuberculosis medicine for resistant disease.', ['Drug-resistant tuberculosis']],
            ['Clofazimine 100mg Capsule', 'Clofazimine', '100mg', 'capsule', Cat::Antibiotics, 'J04BA01', true, false, 8000, 22000, 'specialist', '100 capsules',
                'Used in leprosy multidrug therapy and in resistant tuberculosis.', ['Leprosy', 'Drug-resistant tuberculosis']],
            ['Dapsone 100mg Tablet', 'Dapsone', '100mg', 'tablet', Cat::Antibiotics, 'J04BA02', true, false, 900, 2500, 'uncommon', '30 tablets',
                'Core leprosy medicine; also used to prevent certain infections in HIV.', ['Leprosy']],
        ];
    }

    /** Antifungals, antivirals, antimalarials, anthelminthics and antiprotozoals. */
    private function catalogueAntiInfectives(): array
    {
        return [
            // ── Antifungals ──────────────────────────────────────────────────
            ['Fluconazole 200mg Capsule', 'Fluconazole', '200mg', 'capsule', Cat::Antibiotics, 'J02AC01', true, false, 1500, 4000, 'common', '7 capsules',
                'Antifungal for thrush, cryptococcal meningitis and other fungal infections.', ['Fungal infection', 'Thrush']],
            ['Itraconazole 100mg Capsule', 'Itraconazole', '100mg', 'capsule', Cat::Antibiotics, 'J02AC02', true, false, 5000, 13000, 'uncommon', '15 capsules',
                'Antifungal for nail, skin and deep fungal infections.', ['Fungal infection']],
            ['Voriconazole 200mg Tablet', 'Voriconazole', '200mg', 'tablet', Cat::Antibiotics, 'J02AC03', true, false, 40000, 110000, 'specialist', '14 tablets',
                'Reserve antifungal for invasive aspergillosis.', ['Invasive fungal infection']],
            ['Amphotericin B 50mg Powder for Injection', 'Amphotericin B', '50mg', 'injection', Cat::Antibiotics, 'J02AA01', true, false, 15000, 45000, 'hospital', '1 vial',
                'Intravenous antifungal for cryptococcal meningitis and severe fungal disease.', ['Cryptococcal meningitis']],
            ['Flucytosine 250mg Capsule', 'Flucytosine', '250mg', 'capsule', Cat::Antibiotics, 'J02AX01', true, false, 25000, 70000, 'specialist', '100 capsules',
                'Given with amphotericin B for cryptococcal meningitis.', ['Cryptococcal meningitis']],
            ['Griseofulvin 500mg Tablet', 'Griseofulvin', '500mg', 'tablet', Cat::Antibiotics, 'D01BA01', true, false, 1500, 4000, 'uncommon', '20 tablets',
                'Oral antifungal for scalp ringworm in children.', ['Ringworm', 'Children']],
            ['Terbinafine 250mg Tablet', 'Terbinafine', '250mg', 'tablet', Cat::Antibiotics, 'D01BA02', true, false, 2500, 6500, 'standard', '14 tablets',
                'Oral antifungal for stubborn nail and skin fungal infections.', ['Fungal infection', 'Nail infection']],
            ['Nystatin 100,000 IU/mL Oral Suspension 30ml', 'Nystatin', '100,000 IU/mL', 'suspension', Cat::Antibiotics, 'A07AA02', false, false, 1200, 3000, 'common', '1 bottle (30ml)',
                'Held in the mouth to treat oral thrush in infants and adults.', ['Thrush', 'Children']],

            // ── Antivirals ───────────────────────────────────────────────────
            ['Aciclovir 200mg Tablet', 'Aciclovir', '200mg', 'tablet', Cat::Antibiotics, 'J05AB01', true, false, 1200, 3500, 'common', '25 tablets',
                'Antiviral for cold sores, genital herpes and shingles.', ['Herpes', 'Shingles']],
            ['Aciclovir 250mg Powder for Injection', 'Aciclovir', '250mg', 'injection', Cat::Antibiotics, 'J05AB01', true, false, 4000, 11000, 'uncommon', '1 vial',
                'Intravenous antiviral for severe herpes infection and herpes encephalitis.', ['Severe herpes infection']],
            ['Valaciclovir 500mg Tablet', 'Valaciclovir', '500mg', 'tablet', Cat::Antibiotics, 'J05AB11', true, false, 4000, 11000, 'uncommon', '10 tablets',
                'Antiviral taken less often than aciclovir for herpes and shingles.', ['Herpes', 'Shingles']],
            ['Oseltamivir 75mg Capsule', 'Oseltamivir', '75mg', 'capsule', Cat::Antibiotics, 'J05AH02', true, false, 8000, 20000, 'uncommon', '10 capsules',
                'Antiviral for influenza, most effective started within 48 hours.', ['Influenza']],
            ['Tenofovir Disoproxil Fumarate 300mg Tablet', 'Tenofovir Disoproxil Fumarate', '300mg', 'tablet', Cat::Antibiotics, 'J05AF07', true, false, 4000, 11000, 'standard', '30 tablets',
                'Antiretroviral for HIV; also treats chronic hepatitis B.', ['HIV', 'Hepatitis B']],
            ['Lamivudine 150mg Tablet', 'Lamivudine', '150mg', 'tablet', Cat::Antibiotics, 'J05AF05', true, false, 2500, 7000, 'standard', '60 tablets',
                'Antiretroviral used in combination HIV therapy.', ['HIV']],
            ['Zidovudine 300mg Tablet', 'Zidovudine', '300mg', 'tablet', Cat::Antibiotics, 'J05AF01', true, false, 3000, 8000, 'uncommon', '60 tablets',
                'Antiretroviral used in combination HIV therapy.', ['HIV']],
            ['Abacavir 300mg Tablet', 'Abacavir', '300mg', 'tablet', Cat::Antibiotics, 'J05AF06', true, false, 6000, 16000, 'uncommon', '60 tablets',
                'Antiretroviral for HIV. Stop and seek care if a rash or fever develops.', ['HIV']],
            ['Efavirenz 600mg Tablet', 'Efavirenz', '600mg', 'tablet', Cat::Antibiotics, 'J05AG03', true, false, 4000, 11000, 'uncommon', '30 tablets',
                'Antiretroviral taken at night as part of HIV combination therapy.', ['HIV']],
            ['Nevirapine 200mg Tablet', 'Nevirapine', '200mg', 'tablet', Cat::Antibiotics, 'J05AG01', true, false, 2500, 7000, 'uncommon', '60 tablets',
                'Antiretroviral used in HIV combination therapy.', ['HIV']],
            ['Dolutegravir 50mg Tablet', 'Dolutegravir', '50mg', 'tablet', Cat::Antibiotics, 'J05AJ03', true, false, 5000, 14000, 'standard', '30 tablets',
                'Integrase inhibitor, the preferred anchor drug in first-line HIV therapy.', ['HIV']],
            ['Tenofovir + Lamivudine + Dolutegravir 300/300/50mg Tablet', 'TLD Fixed-Dose Combination', '300/300/50mg', 'tablet', Cat::Antibiotics, 'J05AR27', true, false, 6000, 16000, 'standard', '30 tablets',
                'Single-tablet first-line HIV regimen taken once daily.', ['HIV']],
            ['Lopinavir + Ritonavir 200mg/50mg Tablet', 'Lopinavir/Ritonavir', '200mg/50mg', 'tablet', Cat::Antibiotics, 'J05AR10', true, false, 12000, 32000, 'uncommon', '120 tablets',
                'Boosted protease inhibitor for second-line HIV therapy.', ['HIV']],
            ['Atazanavir 300mg Capsule', 'Atazanavir', '300mg', 'capsule', Cat::Antibiotics, 'J05AE08', true, false, 15000, 40000, 'specialist', '30 capsules',
                'Protease inhibitor for second-line HIV therapy, taken with ritonavir.', ['HIV']],
            ['Ritonavir 100mg Tablet', 'Ritonavir', '100mg', 'tablet', Cat::Antibiotics, 'J05AE03', true, false, 6000, 16000, 'specialist', '30 tablets',
                'Boosts the level of other HIV protease inhibitors.', ['HIV']],
            ['Darunavir 600mg Tablet', 'Darunavir', '600mg', 'tablet', Cat::Antibiotics, 'J05AE10', true, false, 35000, 95000, 'specialist', '60 tablets',
                'Protease inhibitor for third-line HIV therapy.', ['HIV']],
            ['Raltegravir 400mg Tablet', 'Raltegravir', '400mg', 'tablet', Cat::Antibiotics, 'J05AJ01', true, false, 30000, 80000, 'specialist', '60 tablets',
                'Integrase inhibitor used when first-line HIV therapy fails.', ['HIV']],
            ['Zidovudine + Lamivudine 300mg/150mg Tablet', 'Zidovudine/Lamivudine', '300mg/150mg', 'tablet', Cat::Antibiotics, 'J05AR01', true, false, 5000, 13000, 'uncommon', '60 tablets',
                'Fixed-dose antiretroviral backbone for HIV therapy.', ['HIV']],
            ['Abacavir + Lamivudine 600mg/300mg Tablet', 'Abacavir/Lamivudine', '600mg/300mg', 'tablet', Cat::Antibiotics, 'J05AR02', true, false, 9000, 24000, 'uncommon', '30 tablets',
                'Fixed-dose antiretroviral backbone taken once daily.', ['HIV']],
            ['Emtricitabine + Tenofovir 200mg/300mg Tablet', 'Emtricitabine/Tenofovir', '200mg/300mg', 'tablet', Cat::Antibiotics, 'J05AR03', true, false, 8000, 22000, 'uncommon', '30 tablets',
                'Antiretroviral backbone; also used for HIV pre-exposure prophylaxis.', ['HIV', 'PrEP']],
            ['Entecavir 0.5mg Tablet', 'Entecavir', '0.5mg', 'tablet', Cat::Antibiotics, 'J05AF10', true, false, 15000, 40000, 'specialist', '30 tablets',
                'Antiviral for chronic hepatitis B.', ['Hepatitis B']],
            ['Sofosbuvir 400mg Tablet', 'Sofosbuvir', '400mg', 'tablet', Cat::Antibiotics, 'J05AP08', true, false, 60000, 180000, 'specialist', '28 tablets',
                'Direct-acting antiviral that cures most hepatitis C infections.', ['Hepatitis C']],
            ['Sofosbuvir + Velpatasvir 400mg/100mg Tablet', 'Sofosbuvir/Velpatasvir', '400mg/100mg', 'tablet', Cat::Antibiotics, 'J05AP55', true, false, 90000, 250000, 'specialist', '28 tablets',
                'Pan-genotypic single-tablet cure for hepatitis C.', ['Hepatitis C']],
            ['Daclatasvir 60mg Tablet', 'Daclatasvir', '60mg', 'tablet', Cat::Antibiotics, 'J05AP07', true, false, 45000, 130000, 'specialist', '28 tablets',
                'Direct-acting antiviral given with sofosbuvir for hepatitis C.', ['Hepatitis C']],
            ['Ribavirin 200mg Capsule', 'Ribavirin', '200mg', 'capsule', Cat::Antibiotics, 'J05AP01', true, false, 20000, 55000, 'specialist', '56 capsules',
                'Antiviral used in hepatitis C and some viral haemorrhagic fevers.', ['Hepatitis C', 'Viral haemorrhagic fever']],

            // ── Antimalarials ────────────────────────────────────────────────
            ['Artesunate + Amodiaquine 100mg/270mg Tablet', 'Artesunate/Amodiaquine', '100mg/270mg', 'tablet', Cat::Antimalarial, 'P01BF03', false, false, 800, 2500, 'common', '6 tablets',
                'Artemisinin combination therapy for uncomplicated malaria. Complete the full course.', ['Malaria']],
            ['Dihydroartemisinin + Piperaquine 40mg/320mg Tablet', 'Dihydroartemisinin/Piperaquine', '40mg/320mg', 'tablet', Cat::Antimalarial, 'P01BF05', false, false, 1500, 4000, 'common', '9 tablets',
                'Artemisinin combination therapy taken once daily for three days.', ['Malaria']],
            ['Artesunate + Mefloquine 100mg/220mg Tablet', 'Artesunate/Mefloquine', '100mg/220mg', 'tablet', Cat::Antimalarial, 'P01BF02', false, false, 2000, 5500, 'uncommon', '6 tablets',
                'Artemisinin combination therapy for uncomplicated malaria.', ['Malaria']],
            ['Artesunate 100mg Rectal Capsule', 'Artesunate', '100mg', 'suppository', Cat::Antimalarial, 'P01BE03', true, false, 1500, 4000, 'uncommon', '2 capsules',
                'Emergency pre-referral treatment for a child with severe malaria who cannot swallow.', ['Severe malaria', 'Children']],
            ['Artemether 80mg/mL Injection', 'Artemether', '80mg/mL', 'injection', Cat::Antimalarial, 'P01BE02', true, false, 1200, 3500, 'standard', '1 ampoule',
                'Intramuscular treatment for severe malaria.', ['Severe malaria']],
            ['Quinine Sulfate 300mg Tablet', 'Quinine Sulfate', '300mg', 'tablet', Cat::Antimalarial, 'P01BC01', true, false, 800, 2200, 'common', '30 tablets',
                'Older antimalarial, still used in pregnancy and where ACTs are unavailable.', ['Malaria', 'Pregnancy']],
            ['Quinine Dihydrochloride 300mg/mL Injection', 'Quinine Dihydrochloride', '300mg/mL', 'injection', Cat::Antimalarial, 'P01BC01', true, false, 700, 2000, 'standard', '1 ampoule (2ml)',
                'Intravenous treatment for severe malaria where artesunate is unavailable.', ['Severe malaria']],
            ['Chloroquine Phosphate 150mg Tablet', 'Chloroquine Phosphate', '150mg', 'tablet', Cat::Antimalarial, 'P01BA01', false, false, 300, 1000, 'standard', '10 tablets',
                'Antimalarial for Plasmodium vivax; also used in some rheumatic conditions.', ['Malaria']],
            ['Amodiaquine 200mg Tablet', 'Amodiaquine', '200mg', 'tablet', Cat::Antimalarial, 'P01BA06', false, false, 500, 1500, 'uncommon', '9 tablets',
                'Antimalarial used in combination therapy and seasonal chemoprevention.', ['Malaria']],
            ['Primaquine 15mg Tablet', 'Primaquine', '15mg', 'tablet', Cat::Antimalarial, 'P01BA03', true, false, 1500, 4000, 'uncommon', '14 tablets',
                'Clears dormant liver-stage malaria and blocks transmission. Not in G6PD deficiency.', ['Malaria']],
            ['Mefloquine 250mg Tablet', 'Mefloquine', '250mg', 'tablet', Cat::Antimalarial, 'P01BC02', true, false, 3000, 8000, 'uncommon', '8 tablets',
                'Antimalarial for treatment and for travel prophylaxis.', ['Malaria', 'Travel prophylaxis']],
            ['Sulfadoxine + Pyrimethamine 500mg/25mg Tablet', 'Sulfadoxine/Pyrimethamine', '500mg/25mg', 'tablet', Cat::Antimalarial, 'P01BD51', false, false, 400, 1200, 'common', '3 tablets',
                'Given in pregnancy for intermittent preventive treatment of malaria.', ['Malaria', 'Pregnancy']],
            ['Proguanil 100mg Tablet', 'Proguanil', '100mg', 'tablet', Cat::Antimalarial, 'P01BB01', false, false, 2500, 7000, 'uncommon', '28 tablets',
                'Taken daily to prevent malaria while travelling.', ['Travel prophylaxis']],
            ['Atovaquone + Proguanil 250mg/100mg Tablet', 'Atovaquone/Proguanil', '250mg/100mg', 'tablet', Cat::Antimalarial, 'P01BB51', true, false, 12000, 32000, 'uncommon', '12 tablets',
                'Malaria prevention and treatment for travellers.', ['Malaria', 'Travel prophylaxis']],

            // ── Anthelminthics ───────────────────────────────────────────────
            ['Albendazole 400mg Chewable Tablet', 'Albendazole', '400mg', 'tablet', Cat::Digestive, 'P02CA03', false, false, 200, 700, 'core', '1 tablet',
                'Single-dose deworming tablet for intestinal worms; used in mass campaigns.', ['Intestinal worms', 'Deworming']],
            ['Mebendazole 500mg Chewable Tablet', 'Mebendazole', '500mg', 'tablet', Cat::Digestive, 'P02CA01', false, false, 200, 700, 'common', '1 tablet',
                'Single-dose deworming tablet for roundworm, hookworm and whipworm.', ['Intestinal worms', 'Deworming']],
            ['Ivermectin 3mg Tablet', 'Ivermectin', '3mg', 'tablet', Cat::Digestive, 'P02CF01', true, false, 800, 2500, 'common', '4 tablets',
                'Treats river blindness, lymphatic filariasis, strongyloidiasis and scabies.', ['River blindness', 'Scabies']],
            ['Praziquantel 600mg Tablet', 'Praziquantel', '600mg', 'tablet', Cat::Digestive, 'P02BA01', true, false, 900, 2500, 'standard', '4 tablets',
                'Treats schistosomiasis (bilharzia) and tapeworm.', ['Schistosomiasis', 'Tapeworm']],
            ['Levamisole 50mg Tablet', 'Levamisole', '50mg', 'tablet', Cat::Digestive, 'P02CE01', false, false, 300, 1000, 'uncommon', '2 tablets',
                'Single-dose treatment for roundworm.', ['Intestinal worms']],
            ['Niclosamide 500mg Chewable Tablet', 'Niclosamide', '500mg', 'tablet', Cat::Digestive, 'P02DA01', true, false, 900, 2500, 'uncommon', '4 tablets',
                'Treats tapeworm infection.', ['Tapeworm']],
            ['Diethylcarbamazine 50mg Tablet', 'Diethylcarbamazine', '50mg', 'tablet', Cat::Digestive, 'P02CB02', true, false, 500, 1500, 'uncommon', '10 tablets',
                'Treats lymphatic filariasis and loiasis.', ['Filariasis']],
            ['Pyrantel 250mg Chewable Tablet', 'Pyrantel Embonate', '250mg', 'tablet', Cat::Digestive, 'P02CC01', false, false, 400, 1200, 'standard', '3 tablets',
                'Deworming tablet suitable for young children and in pregnancy.', ['Intestinal worms', 'Children']],
            ['Triclabendazole 250mg Tablet', 'Triclabendazole', '250mg', 'tablet', Cat::Digestive, 'P02BX04', true, false, 6000, 16000, 'specialist', '4 tablets',
                'Treats liver fluke infection (fascioliasis).', ['Fascioliasis']],

            // ── Antiprotozoals ───────────────────────────────────────────────
            ['Tinidazole 500mg Tablet', 'Tinidazole', '500mg', 'tablet', Cat::Digestive, 'P01AB02', true, false, 800, 2200, 'common', '4 tablets',
                'Short-course treatment for amoebiasis, giardiasis and trichomoniasis.', ['Amoebiasis', 'Giardiasis']],
            ['Diloxanide Furoate 500mg Tablet', 'Diloxanide Furoate', '500mg', 'tablet', Cat::Digestive, 'P01AC01', true, false, 1500, 4000, 'uncommon', '30 tablets',
                'Clears amoebic cysts from the bowel after acute treatment.', ['Amoebiasis']],
            ['Pentamidine Isetionate 300mg Powder for Injection', 'Pentamidine Isetionate', '300mg', 'injection', Cat::Antibiotics, 'P01CX01', true, false, 25000, 70000, 'specialist', '1 vial',
                'Treats Pneumocystis pneumonia and early-stage sleeping sickness.', ['Pneumocystis pneumonia']],
            ['Nifurtimox 120mg Tablet', 'Nifurtimox', '120mg', 'tablet', Cat::Antibiotics, 'P01CC01', true, false, 20000, 55000, 'specialist', '100 tablets',
                'Treats Chagas disease and, with eflornithine, sleeping sickness.', ['Chagas disease']],
            ['Benznidazole 100mg Tablet', 'Benznidazole', '100mg', 'tablet', Cat::Antibiotics, 'P01CA02', true, false, 20000, 55000, 'specialist', '100 tablets',
                'First-line treatment for Chagas disease.', ['Chagas disease']],
            ['Fexinidazole 600mg Tablet', 'Fexinidazole', '600mg', 'tablet', Cat::Antibiotics, 'P01CA03', true, false, 30000, 85000, 'specialist', '24 tablets',
                'Oral treatment for human African trypanosomiasis (sleeping sickness).', ['Sleeping sickness']],
            ['Sodium Stibogluconate 100mg/mL Injection', 'Sodium Stibogluconate', '100mg/mL', 'injection', Cat::Antibiotics, 'P01CB01', true, false, 18000, 50000, 'specialist', '1 vial (30ml)',
                'Treats visceral and cutaneous leishmaniasis.', ['Leishmaniasis']],
        ];
    }

    /** Analgesia, anaesthesia, neurology and mental health. */
    private function cataloguePainAndNeuro(): array
    {
        return [
            // ── Non-opioid analgesia and anti-inflammatories ─────────────────
            ['Aspirin 300mg Tablet', 'Acetylsalicylic Acid', '300mg', 'tablet', Cat::PainRelief, 'N02BA01', false, false, 200, 700, 'common', '20 tablets',
                'Relieves pain and fever; a low daily dose thins the blood after a heart attack or stroke.', ['Pain relief', 'Fever']],
            ['Paracetamol 250mg Suppository', 'Paracetamol', '250mg', 'suppository', Cat::MaternalChild, 'N02BE01', false, false, 700, 2000, 'standard', '10 suppositories',
                'Paracetamol for a child who is vomiting or cannot take medicine by mouth.', ['Fever', 'Children']],
            ['Ibuprofen 100mg/5ml Oral Suspension 100ml', 'Ibuprofen', '100mg/5ml', 'suspension', Cat::MaternalChild, 'M01AE01', false, false, 900, 2400, 'common', '1 bottle (100ml)',
                'Children\'s ibuprofen suspension for fever and pain. Give with food.', ['Fever', 'Children']],
            ['Naproxen 500mg Tablet', 'Naproxen', '500mg', 'tablet', Cat::PainRelief, 'M01AE02', true, false, 900, 2500, 'standard', '20 tablets',
                'Long-acting anti-inflammatory for arthritis, period pain and musculoskeletal pain.', ['Arthritis', 'Pain relief']],
            ['Indometacin 25mg Capsule', 'Indometacin', '25mg', 'capsule', Cat::PainRelief, 'M01AB01', true, false, 700, 2000, 'standard', '30 capsules',
                'Strong anti-inflammatory for gout and arthritis.', ['Gout', 'Arthritis']],
            ['Meloxicam 15mg Tablet', 'Meloxicam', '15mg', 'tablet', Cat::PainRelief, 'M01AC06', true, false, 900, 2600, 'standard', '10 tablets',
                'Once-daily anti-inflammatory for osteoarthritis and rheumatoid arthritis.', ['Arthritis']],
            ['Celecoxib 200mg Capsule', 'Celecoxib', '200mg', 'capsule', Cat::PainRelief, 'M01AH01', true, false, 3000, 8000, 'uncommon', '10 capsules',
                'Anti-inflammatory that is gentler on the stomach lining.', ['Arthritis', 'Pain relief']],
            ['Diclofenac 1% Gel 30g', 'Diclofenac Sodium', '1%', 'gel', Cat::PainRelief, 'M02AA15', false, false, 1500, 4000, 'common', '1 tube (30g)',
                'Rub-on anti-inflammatory gel for strains, sprains and joint pain.', ['Muscle pain', 'Joint pain']],
            ['Allopurinol 100mg Tablet', 'Allopurinol', '100mg', 'tablet', Cat::PainRelief, 'M04AA01', true, false, 900, 2500, 'standard', '30 tablets',
                'Lowers uric acid to prevent gout attacks. Not for treating an attack in progress.', ['Gout']],
            ['Colchicine 500mcg Tablet', 'Colchicine', '500mcg', 'tablet', Cat::PainRelief, 'M04AC01', true, false, 2500, 7000, 'uncommon', '20 tablets',
                'Settles an acute gout attack.', ['Gout']],
            ['Sumatriptan 50mg Tablet', 'Sumatriptan', '50mg', 'tablet', Cat::PainRelief, 'N02CC01', true, false, 4000, 12000, 'uncommon', '6 tablets',
                'Stops a migraine attack once it has started.', ['Migraine']],
            ['Ergotamine + Caffeine 1mg/100mg Tablet', 'Ergotamine Tartrate/Caffeine', '1mg/100mg', 'tablet', Cat::PainRelief, 'N02CA52', true, false, 1500, 4500, 'uncommon', '10 tablets',
                'Older migraine treatment taken at the first sign of an attack.', ['Migraine']],
            ['Tramadol 50mg Capsule', 'Tramadol', '50mg', 'capsule', Cat::PainRelief, 'N02AX02', true, false, 900, 2800, 'common', '20 capsules',
                'Moderate-strength painkiller for pain not controlled by paracetamol.', ['Pain relief']],

            // ── Opioids and controlled analgesia ─────────────────────────────
            ['Codeine Phosphate 30mg Tablet', 'Codeine Phosphate', '30mg', 'tablet', Cat::PainRelief, 'R05DA04', true, true, 1200, 3500, 'controlled', '20 tablets',
                'Opioid painkiller, also used for cough. Controlled medicine — prescription required.', ['Pain relief', 'Cough']],
            ['Morphine Sulfate 10mg Tablet', 'Morphine Sulfate', '10mg', 'tablet', Cat::PainRelief, 'N02AA01', true, true, 1500, 5000, 'controlled', '20 tablets',
                'Strong opioid for severe and cancer pain. Controlled medicine — prescription required.', ['Severe pain', 'Palliative care']],
            ['Morphine Sulfate 10mg/mL Injection', 'Morphine Sulfate', '10mg/mL', 'injection', Cat::PainRelief, 'N02AA01', true, true, 1200, 4000, 'controlled', '1 ampoule',
                'Injectable strong opioid for severe pain. Controlled medicine.', ['Severe pain']],
            ['Pethidine 50mg/mL Injection', 'Pethidine', '50mg/mL', 'injection', Cat::PainRelief, 'N02AB02', true, true, 1200, 3500, 'controlled', '1 ampoule (2ml)',
                'Injectable opioid used in labour and after surgery. Controlled medicine.', ['Severe pain']],
            ['Fentanyl 25mcg/hour Transdermal Patch', 'Fentanyl', '25mcg/hour', 'patch', Cat::PainRelief, 'N02AB03', true, true, 6000, 18000, 'controlled', '5 patches',
                'Slow-release opioid patch for continuous severe pain. Controlled medicine.', ['Severe pain', 'Palliative care']],
            ['Methadone 10mg/mL Oral Solution 100ml', 'Methadone', '10mg/mL', 'solution', Cat::Other, 'N07BC02', true, true, 8000, 22000, 'controlled', '1 bottle (100ml)',
                'Opioid dependence treatment and severe pain. Controlled medicine, supervised dosing.', ['Opioid dependence']],
            ['Buprenorphine 2mg Sublingual Tablet', 'Buprenorphine', '2mg', 'tablet', Cat::Other, 'N07BC01', true, true, 6000, 16000, 'controlled', '7 tablets',
                'Dissolved under the tongue for opioid dependence. Controlled medicine.', ['Opioid dependence']],
            ['Naloxone 0.4mg/mL Injection', 'Naloxone', '0.4mg/mL', 'injection', Cat::Other, 'V03AB15', true, false, 1500, 4500, 'standard', '1 ampoule',
                'Reverses opioid overdose within minutes. Emergency antidote.', ['Opioid overdose', 'Antidote']],

            // ── Anaesthesia and muscle relaxants ─────────────────────────────
            ['Ketamine 50mg/mL Injection', 'Ketamine', '50mg/mL', 'injection', Cat::Other, 'N01AX03', true, true, 2000, 6000, 'hospital', '1 vial (10ml)',
                'Anaesthetic used for short surgical procedures, including in field settings.', ['Anaesthesia']],
            ['Propofol 10mg/mL Injection 20ml', 'Propofol', '10mg/mL', 'injection', Cat::Other, 'N01AX10', true, false, 3000, 9000, 'hospital', '1 vial (20ml)',
                'Intravenous anaesthetic used to start and maintain general anaesthesia.', ['Anaesthesia']],
            ['Thiopental 500mg Powder for Injection', 'Thiopental Sodium', '500mg', 'injection', Cat::Other, 'N01AF03', true, false, 2500, 7000, 'hospital', '1 vial',
                'Short-acting barbiturate used to induce general anaesthesia.', ['Anaesthesia']],
            ['Isoflurane Inhalation Liquid 250ml', 'Isoflurane', '100%', 'solution', Cat::Other, 'N01AB06', true, false, 25000, 60000, 'hospital', '1 bottle (250ml)',
                'Inhaled anaesthetic vapour used to maintain general anaesthesia.', ['Anaesthesia']],
            ['Halothane Inhalation Liquid 250ml', 'Halothane', '100%', 'solution', Cat::Other, 'N01AB01', true, false, 18000, 45000, 'hospital', '1 bottle (250ml)',
                'Inhaled anaesthetic vapour, still used where isoflurane is unavailable.', ['Anaesthesia']],
            ['Lidocaine 2% Injection 20ml', 'Lidocaine Hydrochloride', '2%', 'injection', Cat::Other, 'N01BB02', true, false, 500, 1800, 'standard', '1 vial (20ml)',
                'Local anaesthetic for suturing, dental work and minor procedures.', ['Local anaesthesia']],
            ['Bupivacaine 0.5% Injection 20ml', 'Bupivacaine Hydrochloride', '0.5%', 'injection', Cat::Other, 'N01BB01', true, false, 1500, 4500, 'hospital', '1 vial (20ml)',
                'Long-acting local anaesthetic used for spinal and regional blocks.', ['Local anaesthesia']],
            ['Suxamethonium 50mg/mL Injection', 'Suxamethonium Chloride', '50mg/mL', 'injection', Cat::Other, 'M03AB01', true, false, 1500, 4500, 'hospital', '1 vial (10ml)',
                'Very short-acting muscle relaxant used to secure the airway.', ['Anaesthesia']],
            ['Atracurium 10mg/mL Injection', 'Atracurium Besilate', '10mg/mL', 'injection', Cat::Other, 'M03AC04', true, false, 3500, 9000, 'hospital', '1 vial (5ml)',
                'Muscle relaxant used during general anaesthesia.', ['Anaesthesia']],
            ['Vecuronium 10mg Powder for Injection', 'Vecuronium Bromide', '10mg', 'injection', Cat::Other, 'M03AC03', true, false, 3000, 8000, 'hospital', '1 vial',
                'Muscle relaxant used during general anaesthesia.', ['Anaesthesia']],
            ['Neostigmine 0.5mg/mL Injection', 'Neostigmine Metilsulfate', '0.5mg/mL', 'injection', Cat::Other, 'N07AA01', true, false, 1200, 3500, 'hospital', '1 ampoule',
                'Reverses muscle relaxants after surgery; also used in myasthenia gravis.', ['Anaesthesia reversal', 'Myasthenia gravis']],
            ['Baclofen 10mg Tablet', 'Baclofen', '10mg', 'tablet', Cat::Other, 'M03BX01', true, false, 2000, 6000, 'uncommon', '30 tablets',
                'Relieves muscle spasticity in cerebral palsy, spinal injury and multiple sclerosis.', ['Muscle spasticity']],

            // ── Epilepsy ─────────────────────────────────────────────────────
            ['Carbamazepine 200mg Tablet', 'Carbamazepine', '200mg', 'tablet', Cat::Other, 'N03AF01', true, false, 900, 2500, 'common', '30 tablets',
                'Controls epileptic seizures and trigeminal nerve pain.', ['Epilepsy', 'Nerve pain']],
            ['Sodium Valproate 200mg Tablet', 'Sodium Valproate', '200mg', 'tablet', Cat::Other, 'N03AG01', true, false, 1200, 3200, 'common', '30 tablets',
                'Broad-spectrum epilepsy medicine. Not used in pregnancy — discuss alternatives.', ['Epilepsy']],
            ['Phenytoin Sodium 100mg Capsule', 'Phenytoin Sodium', '100mg', 'capsule', Cat::Other, 'N03AB02', true, false, 700, 2000, 'common', '30 capsules',
                'Long-established epilepsy medicine; blood levels are monitored.', ['Epilepsy']],
            ['Phenobarbital 30mg Tablet', 'Phenobarbital', '30mg', 'tablet', Cat::Other, 'N03AA02', true, true, 400, 1500, 'controlled', '30 tablets',
                'Low-cost epilepsy medicine. Controlled medicine — prescription required.', ['Epilepsy']],
            ['Levetiracetam 500mg Tablet', 'Levetiracetam', '500mg', 'tablet', Cat::Other, 'N03AX14', true, false, 5000, 14000, 'standard', '30 tablets',
                'Modern epilepsy medicine with few drug interactions.', ['Epilepsy']],
            ['Lamotrigine 50mg Tablet', 'Lamotrigine', '50mg', 'tablet', Cat::Other, 'N03AX09', true, false, 4000, 11000, 'uncommon', '30 tablets',
                'Epilepsy and mood-stabilising medicine. The dose is increased slowly.', ['Epilepsy', 'Bipolar disorder']],
            ['Ethosuximide 250mg Capsule', 'Ethosuximide', '250mg', 'capsule', Cat::Other, 'N03AD01', true, false, 8000, 22000, 'specialist', '30 capsules',
                'Specific treatment for childhood absence seizures.', ['Epilepsy', 'Children']],
            ['Diazepam 5mg Tablet', 'Diazepam', '5mg', 'tablet', Cat::Other, 'N05BA01', true, true, 300, 1200, 'controlled', '20 tablets',
                'Short-term relief of severe anxiety and muscle spasm. Controlled medicine.', ['Anxiety', 'Muscle spasm']],
            ['Diazepam 5mg/mL Injection', 'Diazepam', '5mg/mL', 'injection', Cat::Other, 'N05BA01', true, true, 500, 1800, 'controlled', '1 ampoule (2ml)',
                'Emergency treatment to stop a prolonged seizure. Controlled medicine.', ['Seizures']],
            ['Lorazepam 2mg/mL Injection', 'Lorazepam', '2mg/mL', 'injection', Cat::Other, 'N05BA06', true, true, 2000, 6000, 'controlled', '1 ampoule',
                'Preferred emergency injection for status epilepticus. Controlled medicine.', ['Seizures']],
            ['Midazolam 5mg/mL Injection', 'Midazolam', '5mg/mL', 'injection', Cat::Other, 'N05CD08', true, true, 1500, 4500, 'controlled', '1 ampoule',
                'Sedation and emergency seizure control. Controlled medicine.', ['Seizures', 'Sedation']],

            // ── Mental health and movement disorders ─────────────────────────
            ['Amitriptyline 25mg Tablet', 'Amitriptyline', '25mg', 'tablet', Cat::Other, 'N06AA09', true, false, 500, 1800, 'common', '30 tablets',
                'Treats depression, and at low doses persistent nerve pain.', ['Depression', 'Nerve pain']],
            ['Fluoxetine 20mg Capsule', 'Fluoxetine', '20mg', 'capsule', Cat::Other, 'N06AB03', true, false, 1500, 4500, 'standard', '30 capsules',
                'SSRI antidepressant taken once daily.', ['Depression', 'Anxiety']],
            ['Sertraline 50mg Tablet', 'Sertraline', '50mg', 'tablet', Cat::Other, 'N06AB06', true, false, 2500, 7000, 'standard', '30 tablets',
                'SSRI antidepressant, also used for anxiety and post-traumatic stress.', ['Depression', 'Anxiety']],
            ['Chlorpromazine 100mg Tablet', 'Chlorpromazine', '100mg', 'tablet', Cat::Other, 'N05AA01', true, false, 700, 2000, 'standard', '30 tablets',
                'Antipsychotic for schizophrenia and acute agitation.', ['Schizophrenia']],
            ['Haloperidol 5mg Tablet', 'Haloperidol', '5mg', 'tablet', Cat::Other, 'N05AD01', true, false, 600, 1800, 'standard', '30 tablets',
                'Antipsychotic for schizophrenia and severe agitation.', ['Schizophrenia']],
            ['Haloperidol 5mg/mL Injection', 'Haloperidol', '5mg/mL', 'injection', Cat::Other, 'N05AD01', true, false, 900, 2500, 'uncommon', '1 ampoule',
                'Injectable antipsychotic for acute severe agitation.', ['Acute agitation']],
            ['Fluphenazine Decanoate 25mg/mL Injection', 'Fluphenazine Decanoate', '25mg/mL', 'injection', Cat::Other, 'N05AB02', true, false, 3000, 8000, 'uncommon', '1 ampoule',
                'Long-acting antipsychotic injection given every few weeks.', ['Schizophrenia']],
            ['Risperidone 2mg Tablet', 'Risperidone', '2mg', 'tablet', Cat::Other, 'N05AX08', true, false, 2500, 7000, 'standard', '30 tablets',
                'Antipsychotic for schizophrenia and bipolar disorder.', ['Schizophrenia', 'Bipolar disorder']],
            ['Olanzapine 5mg Tablet', 'Olanzapine', '5mg', 'tablet', Cat::Other, 'N05AH03', true, false, 3000, 9000, 'standard', '28 tablets',
                'Antipsychotic for schizophrenia and bipolar disorder.', ['Schizophrenia', 'Bipolar disorder']],
            ['Clozapine 100mg Tablet', 'Clozapine', '100mg', 'tablet', Cat::Other, 'N05AH02', true, false, 9000, 25000, 'specialist', '28 tablets',
                'Reserved for schizophrenia that has not responded to other medicines. Blood counts required.', ['Schizophrenia']],
            ['Lithium Carbonate 300mg Tablet', 'Lithium Carbonate', '300mg', 'tablet', Cat::Other, 'N05AN01', true, false, 2000, 6000, 'uncommon', '30 tablets',
                'Mood stabiliser for bipolar disorder. Blood levels must be monitored.', ['Bipolar disorder']],
            ['Levodopa + Carbidopa 250mg/25mg Tablet', 'Levodopa/Carbidopa', '250mg/25mg', 'tablet', Cat::Other, 'N04BA02', true, false, 4000, 11000, 'standard', '30 tablets',
                'Main treatment for Parkinson disease.', ['Parkinson disease']],
            ['Biperiden 2mg Tablet', 'Biperiden', '2mg', 'tablet', Cat::Other, 'N04AA02', true, false, 1200, 3500, 'uncommon', '30 tablets',
                'Controls tremor and stiffness caused by antipsychotic medicines.', ['Parkinsonism']],
            ['Trihexyphenidyl 2mg Tablet', 'Trihexyphenidyl', '2mg', 'tablet', Cat::Other, 'N04AA01', true, false, 900, 2500, 'uncommon', '30 tablets',
                'Controls drug-induced parkinsonism.', ['Parkinsonism']],
            ['Nicotine 2mg Chewing Gum', 'Nicotine', '2mg', 'gum', Cat::Other, 'N07BA01', false, false, 3000, 8000, 'uncommon', '30 pieces',
                'Nicotine replacement to help stop smoking.', ['Smoking cessation']],
        ];
    }

    /** Cardiovascular, diabetes, respiratory, digestive and vitamins. */
    private function catalogueChronicCare(): array
    {
        return [
            // ── Blood pressure and heart ─────────────────────────────────────
            ['Enalapril 5mg Tablet', 'Enalapril Maleate', '5mg', 'tablet', Cat::Cardio, 'C09AA02', true, false, 800, 2200, 'common', '30 tablets',
                'ACE inhibitor for high blood pressure and heart failure.', ['High blood pressure', 'Heart failure']],
            ['Ramipril 5mg Tablet', 'Ramipril', '5mg', 'tablet', Cat::Cardio, 'C09AA05', true, false, 1500, 4000, 'standard', '30 tablets',
                'Once-daily ACE inhibitor for blood pressure and heart protection.', ['High blood pressure']],
            ['Losartan 50mg Tablet', 'Losartan Potassium', '50mg', 'tablet', Cat::Cardio, 'C09CA01', true, false, 1500, 4000, 'common', '30 tablets',
                'Angiotensin blocker for high blood pressure; useful if ACE inhibitors cause cough.', ['High blood pressure']],
            ['Telmisartan 40mg Tablet', 'Telmisartan', '40mg', 'tablet', Cat::Cardio, 'C09CA07', true, false, 2500, 7000, 'standard', '30 tablets',
                'Long-acting angiotensin blocker for high blood pressure.', ['High blood pressure']],
            ['Hydrochlorothiazide 25mg Tablet', 'Hydrochlorothiazide', '25mg', 'tablet', Cat::Cardio, 'C03AA03', true, false, 400, 1400, 'common', '30 tablets',
                'Thiazide water tablet, usually the first step in treating high blood pressure.', ['High blood pressure']],
            ['Furosemide 40mg Tablet', 'Furosemide', '40mg', 'tablet', Cat::Cardio, 'C03CA01', true, false, 400, 1400, 'common', '30 tablets',
                'Strong water tablet that clears fluid in heart, kidney and liver disease.', ['Heart failure', 'Oedema']],
            ['Furosemide 10mg/mL Injection', 'Furosemide', '10mg/mL', 'injection', Cat::Cardio, 'C03CA01', true, false, 300, 1200, 'standard', '1 ampoule (2ml)',
                'Injectable diuretic for acute fluid overload and pulmonary oedema.', ['Heart failure']],
            ['Spironolactone 25mg Tablet', 'Spironolactone', '25mg', 'tablet', Cat::Cardio, 'C03DA01', true, false, 1200, 3200, 'standard', '30 tablets',
                'Potassium-sparing diuretic for heart failure and liver ascites.', ['Heart failure']],
            ['Atenolol 50mg Tablet', 'Atenolol', '50mg', 'tablet', Cat::Cardio, 'C07AB03', true, false, 500, 1600, 'common', '30 tablets',
                'Beta blocker that slows the heart and lowers blood pressure.', ['High blood pressure', 'Angina']],
            ['Bisoprolol 5mg Tablet', 'Bisoprolol Fumarate', '5mg', 'tablet', Cat::Cardio, 'C07AB07', true, false, 1500, 4000, 'standard', '30 tablets',
                'Heart-selective beta blocker used in heart failure and angina.', ['Heart failure', 'Angina']],
            ['Carvedilol 12.5mg Tablet', 'Carvedilol', '12.5mg', 'tablet', Cat::Cardio, 'C07AG02', true, false, 1500, 4000, 'standard', '30 tablets',
                'Beta blocker proven to improve survival in heart failure.', ['Heart failure']],
            ['Propranolol 40mg Tablet', 'Propranolol Hydrochloride', '40mg', 'tablet', Cat::Cardio, 'C07AA05', true, false, 500, 1600, 'standard', '30 tablets',
                'Beta blocker for tremor, migraine prevention and overactive thyroid symptoms.', ['Migraine prevention', 'Tremor']],
            ['Nifedipine 20mg Retard Tablet', 'Nifedipine', '20mg', 'tablet', Cat::Cardio, 'C08CA05', true, false, 700, 2000, 'common', '30 tablets',
                'Calcium-channel blocker for blood pressure; also used to delay preterm labour.', ['High blood pressure']],
            ['Verapamil 80mg Tablet', 'Verapamil Hydrochloride', '80mg', 'tablet', Cat::Cardio, 'C08DA01', true, false, 1200, 3200, 'uncommon', '30 tablets',
                'Calcium-channel blocker for angina and fast heart rhythms.', ['Angina', 'Arrhythmia']],
            ['Methyldopa 250mg Tablet', 'Methyldopa', '250mg', 'tablet', Cat::Cardio, 'C02AB01', true, false, 900, 2500, 'common', '30 tablets',
                'The standard blood pressure medicine in pregnancy.', ['High blood pressure', 'Pregnancy']],
            ['Hydralazine 20mg Powder for Injection', 'Hydralazine Hydrochloride', '20mg', 'injection', Cat::Cardio, 'C02DB02', true, false, 1500, 4000, 'uncommon', '1 ampoule',
                'Injectable treatment for severe high blood pressure, including in pre-eclampsia.', ['Severe hypertension', 'Pre-eclampsia']],
            ['Sodium Nitroprusside 50mg Powder for Injection', 'Sodium Nitroprusside', '50mg', 'injection', Cat::Cardio, 'C02DD01', true, false, 8000, 22000, 'hospital', '1 vial',
                'Intensive-care infusion for a hypertensive emergency.', ['Hypertensive emergency']],
            ['Glyceryl Trinitrate 0.5mg Sublingual Tablet', 'Glyceryl Trinitrate', '0.5mg', 'tablet', Cat::Cardio, 'C01DA02', true, false, 900, 2500, 'standard', '30 tablets',
                'Dissolved under the tongue to relieve angina within minutes.', ['Angina']],
            ['Isosorbide Dinitrate 5mg Sublingual Tablet', 'Isosorbide Dinitrate', '5mg', 'tablet', Cat::Cardio, 'C01DA08', true, false, 700, 2000, 'standard', '30 tablets',
                'Nitrate for angina relief and prevention.', ['Angina']],
            ['Digoxin 250mcg Tablet', 'Digoxin', '250mcg', 'tablet', Cat::Cardio, 'C01AA05', true, false, 500, 1600, 'standard', '30 tablets',
                'Slows a fast irregular heartbeat and supports the failing heart.', ['Atrial fibrillation', 'Heart failure']],
            ['Amiodarone 200mg Tablet', 'Amiodarone Hydrochloride', '200mg', 'tablet', Cat::Cardio, 'C01BD01', true, false, 3000, 8000, 'uncommon', '30 tablets',
                'Controls serious heart rhythm disorders. Thyroid and liver are monitored.', ['Arrhythmia']],
            ['Simvastatin 20mg Tablet', 'Simvastatin', '20mg', 'tablet', Cat::Cardio, 'C10AA01', true, false, 1200, 3500, 'common', '30 tablets',
                'Statin taken at night to lower cholesterol and cardiovascular risk.', ['High cholesterol']],
            ['Clopidogrel 75mg Tablet', 'Clopidogrel', '75mg', 'tablet', Cat::Cardio, 'B01AC04', true, false, 2500, 7000, 'standard', '30 tablets',
                'Antiplatelet taken after a heart attack, stent or stroke.', ['Heart attack', 'Stroke prevention']],
            ['Warfarin 5mg Tablet', 'Warfarin Sodium', '5mg', 'tablet', Cat::Cardio, 'B01AA03', true, false, 900, 2500, 'standard', '30 tablets',
                'Blood thinner for clots and atrial fibrillation. Requires regular INR blood tests.', ['Blood clot', 'Atrial fibrillation']],
            ['Rivaroxaban 20mg Tablet', 'Rivaroxaban', '20mg', 'tablet', Cat::Cardio, 'B01AF01', true, false, 12000, 32000, 'uncommon', '28 tablets',
                'Blood thinner taken once daily without routine blood tests.', ['Blood clot', 'Atrial fibrillation']],
            ['Heparin Sodium 5000 IU/mL Injection', 'Heparin Sodium', '5000 IU/mL', 'injection', Cat::Cardio, 'B01AB01', true, false, 2500, 7000, 'standard', '1 vial (5ml)',
                'Injectable blood thinner used in hospital.', ['Blood clot']],
            ['Enoxaparin 40mg/0.4ml Injection', 'Enoxaparin Sodium', '40mg/0.4ml', 'injection', Cat::Cardio, 'B01AB05', true, false, 3500, 9000, 'standard', '2 syringes',
                'Once-daily injection to prevent and treat blood clots.', ['Blood clot']],
            ['Adrenaline (Epinephrine) 1mg/mL Injection', 'Epinephrine', '1mg/mL', 'injection', Cat::Cardio, 'C01CA24', true, false, 500, 1800, 'standard', '1 ampoule',
                'Emergency treatment for anaphylaxis and cardiac arrest.', ['Anaphylaxis', 'Cardiac arrest']],
            ['Dopamine 40mg/mL Injection', 'Dopamine Hydrochloride', '40mg/mL', 'injection', Cat::Cardio, 'C01CA04', true, false, 1200, 3500, 'hospital', '1 ampoule (5ml)',
                'Intensive-care infusion to support blood pressure in shock.', ['Shock']],
            ['Dobutamine 12.5mg/mL Injection', 'Dobutamine', '12.5mg/mL', 'injection', Cat::Cardio, 'C01CA07', true, false, 3000, 8000, 'hospital', '1 vial (20ml)',
                'Intensive-care infusion that strengthens the heartbeat in cardiogenic shock.', ['Shock']],
            ['Ephedrine 30mg/mL Injection', 'Ephedrine Sulfate', '30mg/mL', 'injection', Cat::Cardio, 'C01CA26', true, false, 900, 2500, 'hospital', '1 ampoule',
                'Raises blood pressure that has fallen during spinal anaesthesia.', ['Hypotension']],
            ['Tranexamic Acid 100mg/mL Injection', 'Tranexamic Acid', '100mg/mL', 'injection', Cat::Cardio, 'B02AA02', true, false, 900, 2800, 'standard', '1 ampoule (5ml)',
                'Reduces bleeding after trauma and postpartum haemorrhage.', ['Bleeding', 'Postpartum haemorrhage']],
            ['Phytomenadione (Vitamin K1) 10mg/mL Injection', 'Phytomenadione', '10mg/mL', 'injection', Cat::MaternalChild, 'B02BA01', true, false, 500, 1800, 'standard', '1 ampoule',
                'Given to newborns to prevent bleeding, and to reverse warfarin.', ['Newborn care', 'Bleeding']],
            ['Mannitol 20% Infusion 500ml', 'Mannitol', '20%', 'infusion', Cat::Other, 'B05BC01', true, false, 3000, 8000, 'standard', '1 bag (500ml)',
                'Reduces raised pressure inside the skull or the eye.', ['Raised intracranial pressure']],

            // ── Diabetes ─────────────────────────────────────────────────────
            ['Insulin Human Soluble 100 IU/mL Injection', 'Insulin Human (Soluble)', '100 IU/mL', 'injection', Cat::Diabetes, 'A10AB01', true, false, 5000, 14000, 'standard', '1 vial (10ml)',
                'Short-acting insulin given before meals and in diabetic emergencies. Keep refrigerated.', ['Type 1 diabetes', 'Type 2 diabetes']],
            ['Insulin Human Isophane (NPH) 100 IU/mL Injection', 'Insulin Human (Isophane)', '100 IU/mL', 'injection', Cat::Diabetes, 'A10AC01', true, false, 5000, 14000, 'standard', '1 vial (10ml)',
                'Intermediate-acting background insulin. Keep refrigerated.', ['Type 1 diabetes', 'Type 2 diabetes']],
            ['Gliclazide 80mg Tablet', 'Gliclazide', '80mg', 'tablet', Cat::Diabetes, 'A10BB09', true, false, 1200, 3500, 'common', '30 tablets',
                'Sulfonylurea that stimulates insulin release in type 2 diabetes.', ['Type 2 diabetes']],
            ['Empagliflozin 10mg Tablet', 'Empagliflozin', '10mg', 'tablet', Cat::Diabetes, 'A10BK03', true, false, 12000, 32000, 'uncommon', '30 tablets',
                'Lowers blood sugar and protects the heart and kidneys in type 2 diabetes.', ['Type 2 diabetes', 'Heart failure']],
            ['Glucagon 1mg Powder for Injection', 'Glucagon', '1mg', 'injection', Cat::Diabetes, 'H04AA01', true, false, 15000, 40000, 'specialist', '1 kit',
                'Emergency injection that raises blood sugar in severe hypoglycaemia.', ['Hypoglycaemia']],
            ['Glucose 50% Injection 50ml', 'Glucose (Dextrose)', '50%', 'injection', Cat::Diabetes, 'B05BA03', true, false, 700, 2200, 'standard', '1 vial (50ml)',
                'Given intravenously to reverse severe low blood sugar.', ['Hypoglycaemia']],

            // ── Respiratory and allergy ──────────────────────────────────────
            ['Salbutamol 5mg/mL Nebuliser Solution 20ml', 'Salbutamol', '5mg/mL', 'solution', Cat::Respiratory, 'R03AC02', true, false, 2000, 5500, 'standard', '1 bottle (20ml)',
                'Nebulised reliever for a severe asthma attack.', ['Asthma', 'Wheezing']],
            ['Ipratropium Bromide 20mcg/dose Inhaler', 'Ipratropium Bromide', '20mcg/dose', 'inhaler', Cat::Respiratory, 'R03BB01', true, false, 4000, 11000, 'standard', '1 inhaler (200 doses)',
                'Opens the airways in chronic obstructive lung disease and severe asthma.', ['COPD', 'Asthma']],
            ['Budesonide 200mcg/dose Inhaler', 'Budesonide', '200mcg/dose', 'inhaler', Cat::Respiratory, 'R03BA02', true, false, 6000, 16000, 'standard', '1 inhaler (200 doses)',
                'Daily preventer inhaler that controls asthma inflammation.', ['Asthma', 'Preventer']],
            ['Salmeterol + Fluticasone 25/125mcg Inhaler', 'Salmeterol/Fluticasone', '25/125mcg', 'inhaler', Cat::Respiratory, 'R03AK06', true, false, 15000, 40000, 'uncommon', '1 inhaler (120 doses)',
                'Combination preventer and long-acting reliever inhaler.', ['Asthma', 'COPD']],
            ['Aminophylline 25mg/mL Injection', 'Aminophylline', '25mg/mL', 'injection', Cat::Respiratory, 'R03DA05', true, false, 700, 2200, 'uncommon', '1 ampoule (10ml)',
                'Intravenous bronchodilator for severe asthma unresponsive to inhalers.', ['Severe asthma']],
            ['Montelukast 10mg Tablet', 'Montelukast', '10mg', 'tablet', Cat::Respiratory, 'R03DC03', true, false, 4000, 11000, 'standard', '28 tablets',
                'Daily tablet that helps control asthma and allergic rhinitis.', ['Asthma', 'Allergy']],
            ['Loratadine 10mg Tablet', 'Loratadine', '10mg', 'tablet', Cat::Respiratory, 'R06AX13', false, false, 400, 1200, 'common', '10 tablets',
                'Non-drowsy antihistamine for hay fever, hives and itching.', ['Allergy', 'Hay fever']],
            ['Chlorphenamine 4mg Tablet', 'Chlorphenamine Maleate', '4mg', 'tablet', Cat::Respiratory, 'R06AB04', false, false, 200, 800, 'common', '20 tablets',
                'Sedating antihistamine for allergy, itching and allergic reactions.', ['Allergy', 'Itching']],
            ['Promethazine 25mg Tablet', 'Promethazine Hydrochloride', '25mg', 'tablet', Cat::Respiratory, 'R06AD02', false, false, 400, 1400, 'common', '20 tablets',
                'Antihistamine used for allergy, nausea and travel sickness.', ['Allergy', 'Nausea']],
            ['Xylometazoline 0.05% Nasal Drops 10ml', 'Xylometazoline', '0.05%', 'drops', Cat::Respiratory, 'R01AA07', false, false, 900, 2500, 'common', '1 bottle (10ml)',
                'Clears a blocked nose. Do not use for more than five days.', ['Nasal congestion']],
            ['Budesonide 64mcg/dose Nasal Spray', 'Budesonide', '64mcg/dose', 'spray', Cat::Respiratory, 'R01AD05', true, false, 5000, 13000, 'uncommon', '1 spray (120 doses)',
                'Steroid nasal spray for persistent allergic rhinitis.', ['Allergic rhinitis']],

            // ── Stomach and bowel ────────────────────────────────────────────
            ['Famotidine 20mg Tablet', 'Famotidine', '20mg', 'tablet', Cat::Digestive, 'A02BA03', false, false, 900, 2500, 'common', '20 tablets',
                'Reduces stomach acid for heartburn, reflux and ulcers.', ['Heartburn', 'Ulcer']],
            ['Aluminium Hydroxide + Magnesium Hydroxide Oral Suspension 200ml', 'Aluminium/Magnesium Hydroxide', '200ml', 'suspension', Cat::Digestive, 'A02AD01', false, false, 900, 2500, 'common', '1 bottle (200ml)',
                'Antacid that neutralises stomach acid quickly.', ['Heartburn', 'Indigestion']],
            ['Metoclopramide 10mg Tablet', 'Metoclopramide', '10mg', 'tablet', Cat::Digestive, 'A03FA01', true, false, 400, 1400, 'common', '20 tablets',
                'Relieves nausea and vomiting and helps the stomach empty.', ['Nausea', 'Vomiting']],
            ['Metoclopramide 5mg/mL Injection', 'Metoclopramide', '5mg/mL', 'injection', Cat::Digestive, 'A03FA01', true, false, 300, 1200, 'standard', '1 ampoule (2ml)',
                'Injectable anti-sickness medicine when tablets cannot be kept down.', ['Nausea', 'Vomiting']],
            ['Hyoscine Butylbromide 10mg Tablet', 'Hyoscine Butylbromide', '10mg', 'tablet', Cat::Digestive, 'A03BB01', false, false, 700, 2000, 'common', '20 tablets',
                'Relieves stomach and period cramps by relaxing smooth muscle.', ['Abdominal cramp', 'Period pain']],
            ['Loperamide 2mg Capsule', 'Loperamide Hydrochloride', '2mg', 'capsule', Cat::Digestive, 'A07DA03', false, false, 400, 1400, 'common', '10 capsules',
                'Slows diarrhoea in adults. Not for children or bloody diarrhoea.', ['Diarrhoea']],
            ['Bisacodyl 5mg Tablet', 'Bisacodyl', '5mg', 'tablet', Cat::Digestive, 'A06AB02', false, false, 300, 1200, 'common', '10 tablets',
                'Stimulant laxative for short-term constipation.', ['Constipation']],
            ['Senna 7.5mg Tablet', 'Sennosides', '7.5mg', 'tablet', Cat::Digestive, 'A06AB06', false, false, 400, 1400, 'standard', '20 tablets',
                'Plant-based stimulant laxative taken at night.', ['Constipation']],
            ['Lactulose 3.35g/5ml Oral Solution 200ml', 'Lactulose', '3.35g/5ml', 'solution', Cat::Digestive, 'A06AD11', false, false, 2000, 5500, 'standard', '1 bottle (200ml)',
                'Gentle laxative; also used to clear toxins in liver disease.', ['Constipation', 'Liver disease']],
            ['Ondansetron 8mg Tablet', 'Ondansetron', '8mg', 'tablet', Cat::Digestive, 'A04AA01', true, false, 2000, 6000, 'standard', '10 tablets',
                'Strong anti-sickness tablet used with chemotherapy and after surgery.', ['Nausea', 'Vomiting']],
            ['Ondansetron 2mg/mL Injection', 'Ondansetron', '2mg/mL', 'injection', Cat::Digestive, 'A04AA01', true, false, 1200, 3500, 'standard', '1 ampoule (2ml)',
                'Injectable anti-sickness medicine for severe vomiting.', ['Nausea', 'Vomiting']],
            ['Mesalazine 500mg Tablet', 'Mesalazine', '500mg', 'tablet', Cat::Digestive, 'A07EC02', true, false, 12000, 32000, 'uncommon', '30 tablets',
                'Controls inflammation in ulcerative colitis.', ['Ulcerative colitis']],
            ['Pancreatin 10,000 units Capsule', 'Pancreatin', '10,000 units', 'capsule', Cat::Digestive, 'A09AA02', true, false, 12000, 32000, 'uncommon', '50 capsules',
                'Replaces digestive enzymes in chronic pancreatitis and cystic fibrosis.', ['Pancreatic insufficiency']],
            ['Activated Charcoal 50g Powder', 'Activated Charcoal', '50g', 'powder', Cat::Digestive, 'A07BA01', false, false, 2500, 7000, 'uncommon', '1 bottle (50g)',
                'Given soon after poisoning to bind the poison in the stomach.', ['Poisoning', 'Antidote']],

            // ── Vitamins and minerals ────────────────────────────────────────
            ['Folic Acid 5mg Tablet', 'Folic Acid', '5mg', 'tablet', Cat::Vitamins, 'B03BB01', false, false, 200, 800, 'core', '30 tablets',
                'Prevents anaemia and neural tube defects; taken before and during pregnancy.', ['Pregnancy', 'Anaemia']],
            ['Ferrous Sulfate 200mg Tablet', 'Ferrous Sulfate', '200mg', 'tablet', Cat::Vitamins, 'B03AA07', false, false, 300, 1200, 'common', '30 tablets',
                'Iron tablet for iron-deficiency anaemia. Take with vitamin C, not with tea.', ['Anaemia']],
            ['Retinol (Vitamin A) 200,000 IU Capsule', 'Retinol', '200,000 IU', 'capsule', Cat::Vitamins, 'A11CA01', false, false, 300, 1000, 'common', '2 capsules',
                'High-dose vitamin A given in child health campaigns and after measles.', ['Vitamin A deficiency', 'Children']],
            ['Cholecalciferol (Vitamin D3) 1000 IU Tablet', 'Cholecalciferol', '1000 IU', 'tablet', Cat::Vitamins, 'A11CC05', false, false, 1500, 4500, 'common', '30 tablets',
                'Vitamin D supplement for bone health.', ['Vitamin D deficiency', 'Bone health']],
            ['Ergocalciferol 1.25mg (50,000 IU) Capsule', 'Ergocalciferol', '1.25mg', 'capsule', Cat::Vitamins, 'A11CC01', true, false, 2000, 6000, 'uncommon', '4 capsules',
                'High-dose weekly vitamin D for deficiency and rickets.', ['Vitamin D deficiency', 'Rickets']],
            ['Thiamine (Vitamin B1) 100mg Tablet', 'Thiamine', '100mg', 'tablet', Cat::Vitamins, 'A11DA01', false, false, 500, 1600, 'common', '30 tablets',
                'Vitamin B1 for beriberi and alcohol-related deficiency.', ['Vitamin B1 deficiency']],
            ['Pyridoxine (Vitamin B6) 50mg Tablet', 'Pyridoxine', '50mg', 'tablet', Cat::Vitamins, 'A11HA02', false, false, 500, 1600, 'common', '30 tablets',
                'Vitamin B6, given with isoniazid to prevent nerve damage.', ['Vitamin B6 deficiency', 'Tuberculosis']],
            ['Nicotinamide 50mg Tablet', 'Nicotinamide', '50mg', 'tablet', Cat::Vitamins, 'A11HA01', false, false, 600, 1800, 'uncommon', '30 tablets',
                'Vitamin B3 for pellagra.', ['Pellagra']],
            ['Riboflavin 5mg Tablet', 'Riboflavin', '5mg', 'tablet', Cat::Vitamins, 'A11HA04', false, false, 500, 1600, 'uncommon', '30 tablets',
                'Vitamin B2 for deficiency causing mouth and lip sores.', ['Vitamin B2 deficiency']],
            ['Hydroxocobalamin (Vitamin B12) 1mg/mL Injection', 'Hydroxocobalamin', '1mg/mL', 'injection', Cat::Vitamins, 'B03BA03', true, false, 900, 2800, 'standard', '1 ampoule',
                'Vitamin B12 injection for pernicious anaemia and B12 deficiency.', ['Anaemia', 'Vitamin B12 deficiency']],
            ['Calcium Carbonate 500mg Tablet', 'Calcium Carbonate', '500mg', 'tablet', Cat::Vitamins, 'A12AA04', false, false, 900, 2500, 'common', '30 tablets',
                'Calcium supplement for bone health and in pregnancy.', ['Bone health', 'Pregnancy']],
            ['Calcium Gluconate 100mg/mL Injection', 'Calcium Gluconate', '100mg/mL', 'injection', Cat::Vitamins, 'A12AA03', true, false, 700, 2200, 'standard', '1 ampoule (10ml)',
                'Emergency injection for low calcium and for high potassium.', ['Hypocalcaemia', 'Hyperkalaemia']],
            ['Potassium Iodide 60mg Tablet', 'Potassium Iodide', '60mg', 'tablet', Cat::Vitamins, 'V03AB21', true, false, 1500, 4500, 'uncommon', '10 tablets',
                'Used before thyroid surgery and to protect the thyroid after radiation exposure.', ['Thyroid']],
        ];
    }

    /** Maternal, child, reproductive health and topical preparations. */
    private function catalogueMaternalChildAndTopical(): array
    {
        return [
            // ── Labour, delivery and the newborn ─────────────────────────────
            ['Oxytocin 10 IU/mL Injection', 'Oxytocin', '10 IU/mL', 'injection', Cat::MaternalChild, 'H01BB02', true, false, 400, 1500, 'standard', '1 ampoule',
                'Given after delivery to contract the womb and prevent heavy bleeding. Keep refrigerated.', ['Postpartum haemorrhage', 'Labour']],
            ['Misoprostol 200mcg Tablet', 'Misoprostol', '200mcg', 'tablet', Cat::MaternalChild, 'G02AD06', true, false, 900, 2800, 'standard', '4 tablets',
                'Prevents and treats postpartum bleeding where oxytocin cannot be refrigerated.', ['Postpartum haemorrhage']],
            ['Ergometrine Maleate 200mcg/mL Injection', 'Ergometrine Maleate', '200mcg/mL', 'injection', Cat::MaternalChild, 'G02AB03', true, false, 500, 1800, 'standard', '1 ampoule',
                'Contracts the womb to control bleeding after delivery.', ['Postpartum haemorrhage']],
            ['Carbetocin 100mcg/mL Injection', 'Carbetocin', '100mcg/mL', 'injection', Cat::MaternalChild, 'H01BB03', true, false, 3000, 9000, 'uncommon', '1 ampoule',
                'Heat-stable alternative to oxytocin for preventing bleeding after birth.', ['Postpartum haemorrhage']],
            ['Magnesium Sulfate 500mg/mL Injection', 'Magnesium Sulfate', '500mg/mL', 'injection', Cat::MaternalChild, 'B05XA05', true, false, 500, 1800, 'standard', '1 ampoule (10ml)',
                'Prevents and treats fits in severe pre-eclampsia and eclampsia.', ['Eclampsia', 'Pre-eclampsia']],
            ['Mifepristone 200mg Tablet', 'Mifepristone', '200mg', 'tablet', Cat::MaternalChild, 'G03XB01', true, false, 6000, 18000, 'uncommon', '1 tablet',
                'Used with misoprostol for medical management of miscarriage.', ['Miscarriage management']],
            ['Chlorhexidine 7.1% Gel for Cord Care 20g', 'Chlorhexidine Digluconate', '7.1%', 'gel', Cat::MaternalChild, 'D08AC02', false, false, 700, 2200, 'standard', '1 tube (20g)',
                'Applied to the newborn umbilical cord stump to prevent infection.', ['Newborn care']],

            // ── Contraception and reproductive health ────────────────────────
            ['Medroxyprogesterone Acetate 150mg/mL Depot Injection', 'Medroxyprogesterone Acetate', '150mg/mL', 'injection', Cat::MaternalChild, 'G03AC06', true, false, 1200, 3500, 'common', '1 vial',
                'Contraceptive injection given every three months.', ['Contraception']],
            ['Levonorgestrel 1.5mg Tablet', 'Levonorgestrel', '1.5mg', 'tablet', Cat::MaternalChild, 'G03AD01', false, false, 1200, 3500, 'common', '1 tablet',
                'Emergency contraception, most effective taken as soon as possible.', ['Emergency contraception']],
            ['Ethinylestradiol + Levonorgestrel 30mcg/150mcg Tablet', 'Ethinylestradiol/Levonorgestrel', '30mcg/150mcg', 'tablet', Cat::MaternalChild, 'G03AA07', true, false, 700, 2200, 'common', '21 tablets',
                'Combined oral contraceptive pill taken daily.', ['Contraception']],
            ['Ethinylestradiol + Norethisterone 35mcg/1mg Tablet', 'Ethinylestradiol/Norethisterone', '35mcg/1mg', 'tablet', Cat::MaternalChild, 'G03AA05', true, false, 700, 2200, 'standard', '21 tablets',
                'Combined oral contraceptive pill taken daily.', ['Contraception']],
            ['Etonogestrel 68mg Implant', 'Etonogestrel', '68mg', 'implant', Cat::MaternalChild, 'G03AC08', true, false, 8000, 22000, 'uncommon', '1 implant',
                'Single-rod contraceptive implant, effective for three years.', ['Contraception']],
            ['Levonorgestrel 75mg Two-Rod Implant', 'Levonorgestrel', '75mg', 'implant', Cat::MaternalChild, 'G03AC03', true, false, 8000, 22000, 'uncommon', '1 implant set',
                'Two-rod contraceptive implant, effective for five years.', ['Contraception']],
            ['Norethisterone Enantate 200mg/mL Injection', 'Norethisterone Enantate', '200mg/mL', 'injection', Cat::MaternalChild, 'G03AC01', true, false, 1500, 4000, 'uncommon', '1 ampoule',
                'Contraceptive injection given every two months.', ['Contraception']],
            ['Copper-Bearing Intrauterine Device (TCu380A)', 'Copper Intrauterine Device', 'TCu380A', 'device', Cat::MaternalChild, 'G02BA02', true, false, 4000, 12000, 'uncommon', '1 device',
                'Long-acting reversible contraception, effective for up to twelve years.', ['Contraception']],
            ['Clomifene Citrate 50mg Tablet', 'Clomifene Citrate', '50mg', 'tablet', Cat::MaternalChild, 'G03GB02', true, false, 3000, 8000, 'uncommon', '10 tablets',
                'Induces ovulation in women having difficulty conceiving.', ['Infertility']],

            // ── Hormones, thyroid and urology ────────────────────────────────
            ['Levothyroxine 50mcg Tablet', 'Levothyroxine Sodium', '50mcg', 'tablet', Cat::Other, 'H03AA01', true, false, 1500, 4000, 'common', '30 tablets',
                'Replaces thyroid hormone in an underactive thyroid. Taken on an empty stomach.', ['Hypothyroidism']],
            ['Propylthiouracil 50mg Tablet', 'Propylthiouracil', '50mg', 'tablet', Cat::Other, 'H03BA02', true, false, 2500, 7000, 'uncommon', '30 tablets',
                'Reduces thyroid hormone in an overactive thyroid, including in pregnancy.', ['Hyperthyroidism']],
            ['Thiamazole 5mg Tablet', 'Thiamazole', '5mg', 'tablet', Cat::Other, 'H03BB02', true, false, 2000, 6000, 'uncommon', '30 tablets',
                'First-line tablet for an overactive thyroid.', ['Hyperthyroidism']],
            ['Prednisolone 5mg Tablet', 'Prednisolone', '5mg', 'tablet', Cat::Other, 'H02AB06', true, false, 400, 1500, 'common', '30 tablets',
                'Oral steroid for asthma flare-ups, allergy and inflammatory disease.', ['Inflammation', 'Asthma']],
            ['Dexamethasone 4mg/mL Injection', 'Dexamethasone Sodium Phosphate', '4mg/mL', 'injection', Cat::Other, 'H02AB02', true, false, 300, 1200, 'common', '1 ampoule',
                'Steroid injection for severe inflammation, and given before preterm delivery.', ['Inflammation', 'Preterm labour']],
            ['Hydrocortisone Sodium Succinate 100mg Powder for Injection', 'Hydrocortisone Sodium Succinate', '100mg', 'injection', Cat::Other, 'H02AB09', true, false, 900, 2800, 'standard', '1 vial',
                'Emergency steroid injection for anaphylaxis, severe asthma and adrenal crisis.', ['Anaphylaxis', 'Adrenal crisis']],
            ['Fludrocortisone 100mcg Tablet', 'Fludrocortisone Acetate', '100mcg', 'tablet', Cat::Other, 'H02AA02', true, false, 6000, 16000, 'specialist', '30 tablets',
                'Replaces the salt-retaining hormone in adrenal insufficiency.', ['Adrenal insufficiency']],
            ['Testosterone Enantate 250mg/mL Injection', 'Testosterone Enantate', '250mg/mL', 'injection', Cat::Other, 'G03BA03', true, false, 3000, 9000, 'uncommon', '1 ampoule',
                'Replaces testosterone when the body does not make enough.', ['Hypogonadism']],
            ['Finasteride 5mg Tablet', 'Finasteride', '5mg', 'tablet', Cat::Other, 'G04CB01', true, false, 3000, 8000, 'standard', '30 tablets',
                'Shrinks an enlarged prostate and eases urinary symptoms.', ['Enlarged prostate']],
            ['Tamsulosin 0.4mg Capsule', 'Tamsulosin Hydrochloride', '0.4mg', 'capsule', Cat::Other, 'G04CA02', true, false, 3000, 8000, 'standard', '30 capsules',
                'Relaxes the prostate to improve urine flow.', ['Enlarged prostate']],
            ['Sildenafil 20mg Tablet', 'Sildenafil Citrate', '20mg', 'tablet', Cat::Other, 'G04BE03', true, false, 2500, 7000, 'standard', '30 tablets',
                'Used for pulmonary arterial hypertension and erectile dysfunction.', ['Pulmonary hypertension']],
            ['Oxybutynin 5mg Tablet', 'Oxybutynin Hydrochloride', '5mg', 'tablet', Cat::Other, 'G04BD04', true, false, 2000, 6000, 'uncommon', '30 tablets',
                'Calms an overactive bladder.', ['Overactive bladder']],
            ['Desmopressin 4mcg/mL Injection', 'Desmopressin Acetate', '4mcg/mL', 'injection', Cat::Other, 'H01BA02', true, false, 12000, 32000, 'specialist', '1 ampoule',
                'Treats diabetes insipidus and some bleeding disorders.', ['Diabetes insipidus']],

            // ── Skin, antiseptics and infestations ───────────────────────────
            ['Betamethasone Valerate 0.1% Cream 15g', 'Betamethasone Valerate', '0.1%', 'cream', Cat::SkinCare, 'D07AC01', true, false, 1200, 3200, 'common', '1 tube (15g)',
                'Moderately strong steroid cream for eczema and dermatitis.', ['Eczema', 'Dermatitis']],
            ['Miconazole 2% Cream 30g', 'Miconazole Nitrate', '2%', 'cream', Cat::SkinCare, 'D01AC02', false, false, 1200, 3000, 'common', '1 tube (30g)',
                'Antifungal cream for ringworm, athlete\'s foot and nappy rash.', ['Fungal infection', 'Ringworm']],
            ['Clotrimazole 1% Cream 20g', 'Clotrimazole', '1%', 'cream', Cat::SkinCare, 'D01AC01', false, false, 1000, 2800, 'common', '1 tube (20g)',
                'Antifungal cream for skin and groin fungal infections.', ['Fungal infection']],
            ['Clotrimazole 500mg Vaginal Pessary', 'Clotrimazole', '500mg', 'pessary', Cat::MaternalChild, 'G01AF02', false, false, 1500, 4000, 'common', '1 pessary',
                'Single-dose treatment for vaginal thrush.', ['Vaginal thrush']],
            ['Fusidic Acid 2% Cream 15g', 'Fusidic Acid', '2%', 'cream', Cat::SkinCare, 'D06AX01', true, false, 2500, 6500, 'standard', '1 tube (15g)',
                'Antibiotic cream for impetigo and infected skin.', ['Skin infection', 'Impetigo']],
            ['Mupirocin 2% Ointment 15g', 'Mupirocin', '2%', 'ointment', Cat::SkinCare, 'D06AX09', true, false, 3000, 8000, 'standard', '1 tube (15g)',
                'Antibiotic ointment for impetigo and to clear nasal staphylococcus.', ['Skin infection', 'Impetigo']],
            ['Silver Sulfadiazine 1% Cream 50g', 'Silver Sulfadiazine', '1%', 'cream', Cat::SkinCare, 'D06BA01', true, false, 3000, 8000, 'standard', '1 tube (50g)',
                'Antibacterial cream applied to burns to prevent infection.', ['Burns']],
            ['Permethrin 5% Cream 30g', 'Permethrin', '5%', 'cream', Cat::SkinCare, 'P03AC04', false, false, 2500, 6500, 'common', '1 tube (30g)',
                'Applied head to toe to treat scabies. Treat the whole household.', ['Scabies']],
            ['Benzyl Benzoate 25% Lotion 100ml', 'Benzyl Benzoate', '25%', 'lotion', Cat::SkinCare, 'P03AX01', false, false, 900, 2500, 'common', '1 bottle (100ml)',
                'Low-cost lotion for scabies and lice.', ['Scabies', 'Lice']],
            ['Benzoyl Peroxide 5% Gel 30g', 'Benzoyl Peroxide', '5%', 'gel', Cat::SkinCare, 'D10AE01', false, false, 2000, 5500, 'standard', '1 tube (30g)',
                'Treats acne by clearing blocked pores and bacteria.', ['Acne']],
            ['Salicylic Acid 5% Ointment 30g', 'Salicylic Acid', '5%', 'ointment', Cat::SkinCare, 'D01AE12', false, false, 900, 2500, 'standard', '1 tube (30g)',
                'Softens and removes thickened or scaly skin and warts.', ['Warts', 'Scaly skin']],
            ['Benzoic Acid + Salicylic Acid Ointment 30g', 'Benzoic Acid/Salicylic Acid', '6%/3%', 'ointment', Cat::SkinCare, 'D01AE20', false, false, 700, 2000, 'common', '1 tube (30g)',
                'Whitfield\'s ointment — a low-cost antifungal for ringworm and athlete\'s foot.', ['Ringworm', 'Fungal infection']],
            ['Selenium Sulfide 2.5% Shampoo 100ml', 'Selenium Sulfide', '2.5%', 'lotion', Cat::SkinCare, 'D01AE13', false, false, 2500, 6500, 'uncommon', '1 bottle (100ml)',
                'Medicated shampoo for dandruff and pityriasis versicolor.', ['Dandruff', 'Fungal infection']],
            ['Calamine Lotion 100ml', 'Calamine', '15%', 'lotion', Cat::SkinCare, 'D02AB', false, false, 700, 2000, 'common', '1 bottle (100ml)',
                'Soothes itching from chickenpox, insect bites and heat rash.', ['Itching', 'Insect bites']],
            ['Podophyllotoxin 0.5% Solution 3.5ml', 'Podophyllotoxin', '0.5%', 'solution', Cat::SkinCare, 'D06BB04', true, false, 6000, 16000, 'uncommon', '1 bottle (3.5ml)',
                'Applied to genital warts.', ['Genital warts']],
            ['Chlorhexidine 5% Solution 500ml', 'Chlorhexidine Digluconate', '5%', 'solution', Cat::SkinCare, 'D08AC02', false, false, 2000, 5500, 'common', '1 bottle (500ml)',
                'Antiseptic concentrate for skin preparation and wound cleaning.', ['Antiseptic', 'Wound care']],
            ['Povidone Iodine 10% Solution 500ml', 'Povidone Iodine', '10%', 'solution', Cat::SkinCare, 'D08AG02', false, false, 1500, 4500, 'core', '1 bottle (500ml)',
                'Antiseptic for wounds and skin preparation before procedures.', ['Antiseptic', 'Wound care']],
            ['Hydrogen Peroxide 3% Solution 250ml', 'Hydrogen Peroxide', '3%', 'solution', Cat::SkinCare, 'D08AX01', false, false, 700, 2000, 'common', '1 bottle (250ml)',
                'Cleans dirty wounds and loosens debris.', ['Wound care']],
            ['Alcohol-Based Hand Rub 70% Solution 500ml', 'Ethanol', '70%', 'solution', Cat::SkinCare, 'D08AX08', false, false, 1500, 4500, 'common', '1 bottle (500ml)',
                'Hand disinfectant for infection prevention.', ['Hand hygiene']],
        ];
    }

    /** Hospital, oncology, ophthalmology, vaccines, fluids and blood products. */
    private function catalogueHospitalAndSpecialist(): array
    {
        return [
            // ── Eye ──────────────────────────────────────────────────────────
            ['Gentamicin 0.3% Eye Drops 5ml', 'Gentamicin', '0.3%', 'drops', Cat::Other, 'S01AA11', true, false, 900, 2500, 'common', '1 bottle (5ml)',
                'Antibiotic eye drops for bacterial conjunctivitis.', ['Eye infection']],
            ['Tetracycline 1% Eye Ointment 5g', 'Tetracycline', '1%', 'ointment', Cat::Other, 'S01AA09', true, false, 700, 2000, 'common', '1 tube (5g)',
                'Antibiotic eye ointment for trachoma and newborn eye infection.', ['Trachoma', 'Eye infection']],
            ['Ciprofloxacin 0.3% Eye Drops 5ml', 'Ciprofloxacin', '0.3%', 'drops', Cat::Other, 'S01AE03', true, false, 1200, 3200, 'common', '1 bottle (5ml)',
                'Antibiotic eye drops for corneal ulcer and severe eye infection.', ['Eye infection']],
            ['Azithromycin 1.5% Eye Drops 0.25ml', 'Azithromycin', '1.5%', 'drops', Cat::Other, 'S01AA26', true, false, 3000, 8000, 'uncommon', '6 single-dose units',
                'Short-course antibiotic eye drops used in trachoma control.', ['Trachoma']],
            ['Aciclovir 3% Eye Ointment 4.5g', 'Aciclovir', '3%', 'ointment', Cat::Other, 'S01AD03', true, false, 4000, 11000, 'uncommon', '1 tube (4.5g)',
                'Antiviral eye ointment for herpes infection of the cornea.', ['Eye infection', 'Herpes']],
            ['Natamycin 5% Eye Drops 15ml', 'Natamycin', '5%', 'drops', Cat::Other, 'S01AA10', true, false, 12000, 32000, 'specialist', '1 bottle (15ml)',
                'Antifungal eye drops for fungal corneal ulcer.', ['Fungal eye infection']],
            ['Prednisolone Acetate 1% Eye Drops 5ml', 'Prednisolone Acetate', '1%', 'drops', Cat::Other, 'S01BA04', true, false, 2000, 5500, 'standard', '1 bottle (5ml)',
                'Steroid eye drops for inflammation inside the eye. Specialist supervision required.', ['Eye inflammation']],
            ['Timolol 0.5% Eye Drops 5ml', 'Timolol Maleate', '0.5%', 'drops', Cat::Other, 'S01ED01', true, false, 1500, 4500, 'standard', '1 bottle (5ml)',
                'Lowers pressure inside the eye in glaucoma.', ['Glaucoma']],
            ['Latanoprost 0.005% Eye Drops 2.5ml', 'Latanoprost', '0.005%', 'drops', Cat::Other, 'S01EE01', true, false, 6000, 16000, 'uncommon', '1 bottle (2.5ml)',
                'Once-daily eye drops that lower eye pressure in glaucoma.', ['Glaucoma']],
            ['Pilocarpine 2% Eye Drops 10ml', 'Pilocarpine Hydrochloride', '2%', 'drops', Cat::Other, 'S01EB01', true, false, 1500, 4500, 'uncommon', '1 bottle (10ml)',
                'Constricts the pupil to relieve acute angle-closure glaucoma.', ['Glaucoma']],
            ['Acetazolamide 250mg Tablet', 'Acetazolamide', '250mg', 'tablet', Cat::Other, 'S01EC01', true, false, 2000, 6000, 'uncommon', '30 tablets',
                'Tablet that lowers eye pressure in glaucoma; also used for altitude sickness.', ['Glaucoma']],
            ['Atropine 1% Eye Drops 5ml', 'Atropine Sulfate', '1%', 'drops', Cat::Other, 'S01FA01', true, false, 1200, 3500, 'standard', '1 bottle (5ml)',
                'Dilates the pupil for examination and rests the eye in uveitis.', ['Eye examination', 'Uveitis']],
            ['Tetracaine 0.5% Eye Drops 10ml', 'Tetracaine Hydrochloride', '0.5%', 'drops', Cat::Other, 'S01HA03', true, false, 1500, 4500, 'uncommon', '1 bottle (10ml)',
                'Local anaesthetic eye drops for examination and minor eye procedures.', ['Eye examination']],
            ['Fluorescein 1% Eye Drops 5ml', 'Fluorescein Sodium', '1%', 'drops', Cat::Other, 'S01JA01', true, false, 1500, 4500, 'uncommon', '1 bottle (5ml)',
                'Diagnostic dye that reveals scratches and ulcers on the cornea.', ['Eye examination']],

            // ── Ear ──────────────────────────────────────────────────────────
            ['Ciprofloxacin 0.3% Ear Drops 5ml', 'Ciprofloxacin', '0.3%', 'drops', Cat::Other, 'S02AA15', true, false, 2000, 5500, 'common', '1 bottle (5ml)',
                'Antibiotic ear drops for a discharging middle-ear infection.', ['Ear infection']],
            ['Acetic Acid 2% Ear Drops 10ml', 'Acetic Acid', '2%', 'drops', Cat::Other, 'S02AA10', false, false, 900, 2500, 'uncommon', '1 bottle (10ml)',
                'Acidifying ear drops for outer-ear infection (swimmer\'s ear).', ['Ear infection']],

            // ── Intravenous fluids and electrolytes ──────────────────────────
            ['Sodium Chloride 0.9% Infusion 500ml', 'Sodium Chloride', '0.9%', 'infusion', Cat::Other, 'B05CB01', true, false, 700, 2200, 'core', '1 bag (500ml)',
                'Standard intravenous fluid for rehydration and drug dilution.', ['Dehydration', 'Intravenous fluid']],
            ['Glucose 5% Infusion 500ml', 'Glucose (Dextrose)', '5%', 'infusion', Cat::Other, 'B05BA03', true, false, 700, 2200, 'core', '1 bag (500ml)',
                'Intravenous fluid providing water and a small amount of energy.', ['Intravenous fluid']],
            ['Compound Sodium Lactate Infusion 500ml', 'Compound Sodium Lactate (Ringer\'s Lactate)', '500ml', 'infusion', Cat::Other, 'B05BB01', true, false, 800, 2500, 'core', '1 bag (500ml)',
                'Balanced replacement fluid for shock, trauma and severe dehydration.', ['Dehydration', 'Shock']],
            ['Potassium Chloride 15% Concentrate 10ml', 'Potassium Chloride', '15%', 'injection', Cat::Other, 'B05XA01', true, false, 400, 1400, 'standard', '1 ampoule (10ml)',
                'Concentrated potassium, always diluted before infusion.', ['Low potassium']],
            ['Sodium Bicarbonate 8.4% Injection 10ml', 'Sodium Bicarbonate', '8.4%', 'injection', Cat::Other, 'B05XA02', true, false, 500, 1600, 'standard', '1 ampoule (10ml)',
                'Corrects severe acidosis in resuscitation.', ['Acidosis']],
            ['Water for Injection 10ml', 'Water for Injection', '10ml', 'injection', Cat::Other, 'V07AB', false, false, 100, 400, 'common', '1 ampoule (10ml)',
                'Sterile water used to dissolve and dilute injectable medicines.', ['Diluent']],

            // ── Blood products and immunoglobulins ───────────────────────────
            ['Human Albumin 20% Infusion 100ml', 'Human Albumin', '20%', 'infusion', Cat::Other, 'B05AA01', true, false, 45000, 130000, 'hospital', '1 bottle (100ml)',
                'Plasma protein infusion used in severe liver disease and large-volume drainage.', ['Hypoalbuminaemia']],
            ['Normal Immunoglobulin 5% Infusion 50ml', 'Human Normal Immunoglobulin', '5%', 'infusion', Cat::Other, 'J06BA02', true, false, 120000, 350000, 'hospital', '1 bottle (50ml)',
                'Pooled antibody infusion for immune deficiency and certain autoimmune diseases.', ['Immune deficiency']],
            ['Anti-D Immunoglobulin 300mcg Injection', 'Anti-D Immunoglobulin', '300mcg', 'injection', Cat::MaternalChild, 'J06BB01', true, false, 25000, 70000, 'specialist', '1 vial',
                'Given to a rhesus-negative mother to protect a future pregnancy.', ['Rhesus prophylaxis', 'Pregnancy']],
            ['Polyvalent Snake Antivenom Immunoglobulin', 'Snake Antivenom Immunoglobulin', '10ml', 'injection', Cat::Other, 'J06AA03', true, false, 35000, 110000, 'uncommon', '1 vial (10ml)',
                'Emergency treatment for venomous snakebite.', ['Snakebite']],
            ['Rabies Immunoglobulin 300 IU/mL Injection', 'Human Rabies Immunoglobulin', '300 IU/mL', 'injection', Cat::Other, 'J06BB05', true, false, 90000, 260000, 'specialist', '1 vial (2ml)',
                'Given with rabies vaccine after a high-risk animal bite.', ['Rabies exposure']],
            ['Tetanus Immunoglobulin 250 IU Injection', 'Human Tetanus Immunoglobulin', '250 IU', 'injection', Cat::Other, 'J06BB02', true, false, 12000, 35000, 'standard', '1 vial',
                'Given with a dirty wound when tetanus protection is uncertain.', ['Tetanus prophylaxis']],
            ['Coagulation Factor VIII 250 IU Powder for Injection', 'Coagulation Factor VIII', '250 IU', 'injection', Cat::Other, 'B02BD02', true, false, 90000, 260000, 'specialist', '1 vial',
                'Replaces the missing clotting factor in haemophilia A.', ['Haemophilia A']],
            ['Coagulation Factor IX 500 IU Powder for Injection', 'Coagulation Factor IX', '500 IU', 'injection', Cat::Other, 'B02BD01', true, false, 120000, 340000, 'specialist', '1 vial',
                'Replaces the missing clotting factor in haemophilia B.', ['Haemophilia B']],
            ['Epoetin Alfa 4000 IU/mL Injection', 'Epoetin Alfa', '4000 IU/mL', 'injection', Cat::Other, 'B03XA01', true, false, 25000, 70000, 'specialist', '1 syringe',
                'Stimulates red blood cell production in anaemia of kidney failure.', ['Anaemia', 'Kidney disease']],
            ['Filgrastim 300mcg/mL Injection', 'Filgrastim', '300mcg/mL', 'injection', Cat::Other, 'L03AA02', true, false, 35000, 100000, 'specialist', '1 syringe',
                'Raises the white cell count after chemotherapy.', ['Neutropenia']],
            ['Protamine Sulfate 10mg/mL Injection', 'Protamine Sulfate', '10mg/mL', 'injection', Cat::Other, 'V03AB14', true, false, 3000, 9000, 'hospital', '1 ampoule (5ml)',
                'Reverses heparin over-anticoagulation.', ['Antidote']],

            // ── Antidotes and toxicology ─────────────────────────────────────
            ['Acetylcysteine 200mg/mL Injection', 'Acetylcysteine', '200mg/mL', 'injection', Cat::Other, 'V03AB23', true, false, 6000, 16000, 'uncommon', '1 ampoule (10ml)',
                'Antidote for paracetamol overdose. Most effective given early.', ['Paracetamol overdose', 'Antidote']],
            ['Deferoxamine 500mg Powder for Injection', 'Deferoxamine Mesilate', '500mg', 'injection', Cat::Other, 'V03AC01', true, false, 12000, 32000, 'specialist', '1 vial',
                'Removes excess iron after overdose or repeated transfusion.', ['Iron overload', 'Antidote']],
            ['Methylthioninium Chloride 10mg/mL Injection', 'Methylthioninium Chloride', '10mg/mL', 'injection', Cat::Other, 'V03AB17', true, false, 15000, 40000, 'specialist', '1 ampoule',
                'Methylene blue — antidote for methaemoglobinaemia.', ['Antidote']],
            ['Sodium Thiosulfate 250mg/mL Injection', 'Sodium Thiosulfate', '250mg/mL', 'injection', Cat::Other, 'V03AB06', true, false, 8000, 22000, 'specialist', '1 vial (50ml)',
                'Antidote for cyanide poisoning.', ['Antidote']],
            ['Flumazenil 0.1mg/mL Injection', 'Flumazenil', '0.1mg/mL', 'injection', Cat::Other, 'V03AB25', true, false, 8000, 22000, 'hospital', '1 ampoule (5ml)',
                'Reverses benzodiazepine sedation.', ['Antidote']],
            ['Penicillamine 250mg Capsule', 'Penicillamine', '250mg', 'capsule', Cat::Other, 'M01CC01', true, false, 12000, 32000, 'specialist', '30 capsules',
                'Removes copper in Wilson disease and heavy metals after poisoning.', ['Wilson disease', 'Antidote']],
            ['Atropine Sulfate 1mg/mL Injection', 'Atropine Sulfate', '1mg/mL', 'injection', Cat::Other, 'A03BA01', true, false, 400, 1400, 'standard', '1 ampoule',
                'Speeds a dangerously slow heart and treats organophosphate poisoning.', ['Bradycardia', 'Antidote']],
            ['Calcium Folinate 15mg Tablet', 'Calcium Folinate', '15mg', 'tablet', Cat::Other, 'V03AF03', true, false, 6000, 16000, 'uncommon', '10 tablets',
                'Rescue therapy that limits methotrexate toxicity.', ['Chemotherapy support']],
            ['Mesna 100mg/mL Injection', 'Mesna', '100mg/mL', 'injection', Cat::Other, 'V03AF01', true, false, 6000, 16000, 'specialist', '1 ampoule (4ml)',
                'Protects the bladder during cyclophosphamide and ifosfamide chemotherapy.', ['Chemotherapy support']],

            // ── Cancer and immunosuppression ─────────────────────────────────
            ['Cyclophosphamide 500mg Powder for Injection', 'Cyclophosphamide', '500mg', 'injection', Cat::Other, 'L01AA01', true, false, 8000, 22000, 'hospital', '1 vial',
                'Chemotherapy for lymphoma, breast cancer and some autoimmune disease.', ['Cancer']],
            ['Ifosfamide 1g Powder for Injection', 'Ifosfamide', '1g', 'injection', Cat::Other, 'L01AA06', true, false, 25000, 70000, 'hospital', '1 vial',
                'Chemotherapy for sarcoma and testicular cancer. Given with mesna.', ['Cancer']],
            ['Cisplatin 50mg/50ml Injection', 'Cisplatin', '50mg/50ml', 'injection', Cat::Other, 'L01XA01', true, false, 12000, 35000, 'hospital', '1 vial (50ml)',
                'Platinum chemotherapy for cervical, head and neck, and testicular cancer.', ['Cancer']],
            ['Carboplatin 150mg/15ml Injection', 'Carboplatin', '150mg/15ml', 'injection', Cat::Other, 'L01XA02', true, false, 25000, 70000, 'hospital', '1 vial (15ml)',
                'Platinum chemotherapy, gentler on the kidneys than cisplatin.', ['Cancer']],
            ['Oxaliplatin 50mg Powder for Injection', 'Oxaliplatin', '50mg', 'injection', Cat::Other, 'L01XA03', true, false, 40000, 110000, 'hospital', '1 vial',
                'Platinum chemotherapy for colorectal cancer.', ['Cancer']],
            ['Doxorubicin 50mg Powder for Injection', 'Doxorubicin Hydrochloride', '50mg', 'injection', Cat::Other, 'L01DB01', true, false, 15000, 45000, 'hospital', '1 vial',
                'Anthracycline chemotherapy for lymphoma, breast cancer and sarcoma.', ['Cancer']],
            ['Bleomycin 15,000 IU Powder for Injection', 'Bleomycin', '15,000 IU', 'injection', Cat::Other, 'L01DC01', true, false, 20000, 55000, 'hospital', '1 vial',
                'Chemotherapy for lymphoma and testicular cancer.', ['Cancer']],
            ['Etoposide 100mg/5ml Injection', 'Etoposide', '100mg/5ml', 'injection', Cat::Other, 'L01CB01', true, false, 12000, 35000, 'hospital', '1 vial (5ml)',
                'Chemotherapy for lung cancer, lymphoma and testicular cancer.', ['Cancer']],
            ['Vincristine Sulfate 1mg/mL Injection', 'Vincristine Sulfate', '1mg/mL', 'injection', Cat::Other, 'L01CA02', true, false, 12000, 35000, 'hospital', '1 vial',
                'Chemotherapy for leukaemia, lymphoma and Burkitt lymphoma. Intravenous use only.', ['Cancer']],
            ['Vinblastine Sulfate 10mg Powder for Injection', 'Vinblastine Sulfate', '10mg', 'injection', Cat::Other, 'L01CA01', true, false, 15000, 45000, 'hospital', '1 vial',
                'Chemotherapy for Hodgkin lymphoma and Kaposi sarcoma.', ['Cancer']],
            ['Paclitaxel 100mg/16.7ml Injection', 'Paclitaxel', '100mg/16.7ml', 'injection', Cat::Other, 'L01CD01', true, false, 45000, 130000, 'hospital', '1 vial',
                'Taxane chemotherapy for breast, ovarian and Kaposi sarcoma.', ['Cancer']],
            ['Fluorouracil 500mg/10ml Injection', 'Fluorouracil', '500mg/10ml', 'injection', Cat::Other, 'L01BC02', true, false, 6000, 18000, 'hospital', '1 vial (10ml)',
                'Chemotherapy for colorectal, breast and stomach cancer.', ['Cancer']],
            ['Cytarabine 100mg Powder for Injection', 'Cytarabine', '100mg', 'injection', Cat::Other, 'L01BC01', true, false, 12000, 35000, 'hospital', '1 vial',
                'Chemotherapy for acute leukaemia.', ['Leukaemia']],
            ['Dacarbazine 200mg Powder for Injection', 'Dacarbazine', '200mg', 'injection', Cat::Other, 'L01AX04', true, false, 15000, 45000, 'hospital', '1 vial',
                'Chemotherapy for Hodgkin lymphoma and melanoma.', ['Cancer']],
            ['Mercaptopurine 50mg Tablet', 'Mercaptopurine', '50mg', 'tablet', Cat::Other, 'L01BB02', true, false, 15000, 40000, 'specialist', '25 tablets',
                'Oral chemotherapy for maintenance treatment of acute lymphoblastic leukaemia.', ['Leukaemia']],
            ['Hydroxycarbamide 500mg Capsule', 'Hydroxycarbamide', '500mg', 'capsule', Cat::Other, 'L01XX05', true, false, 9000, 25000, 'specialist', '100 capsules',
                'Reduces painful crises in sickle cell disease; also used in some blood cancers.', ['Sickle cell disease']],
            ['Imatinib 400mg Tablet', 'Imatinib', '400mg', 'tablet', Cat::Other, 'L01EA01', true, false, 90000, 260000, 'specialist', '30 tablets',
                'Targeted therapy for chronic myeloid leukaemia and GIST.', ['Leukaemia']],
            ['Rituximab 100mg/10ml Concentrate for Infusion', 'Rituximab', '100mg/10ml', 'infusion', Cat::Other, 'L01FA01', true, false, 200000, 600000, 'hospital', '1 vial (10ml)',
                'Monoclonal antibody for B-cell lymphoma and some autoimmune disease.', ['Lymphoma']],
            ['Trastuzumab 440mg Powder for Infusion', 'Trastuzumab', '440mg', 'infusion', Cat::Other, 'L01FD01', true, false, 350000, 900000, 'hospital', '1 vial',
                'Targeted therapy for HER2-positive breast cancer.', ['Breast cancer']],
            ['Tretinoin 10mg Capsule', 'Tretinoin', '10mg', 'capsule', Cat::Other, 'L01XX14', true, false, 40000, 110000, 'specialist', '100 capsules',
                'All-trans retinoic acid, curative therapy for acute promyelocytic leukaemia.', ['Leukaemia']],
            ['Tamoxifen 20mg Tablet', 'Tamoxifen Citrate', '20mg', 'tablet', Cat::Other, 'L02BA01', true, false, 3000, 9000, 'standard', '30 tablets',
                'Taken daily for years after hormone-receptor-positive breast cancer.', ['Breast cancer']],
            ['Anastrozole 1mg Tablet', 'Anastrozole', '1mg', 'tablet', Cat::Other, 'L02BG03', true, false, 12000, 32000, 'uncommon', '28 tablets',
                'Hormone therapy for breast cancer after the menopause.', ['Breast cancer']],
            ['Bicalutamide 50mg Tablet', 'Bicalutamide', '50mg', 'tablet', Cat::Other, 'L02BB03', true, false, 12000, 32000, 'uncommon', '28 tablets',
                'Hormone therapy for prostate cancer.', ['Prostate cancer']],
            ['Leuprorelin 3.75mg Powder for Injection', 'Leuprorelin Acetate', '3.75mg', 'injection', Cat::Other, 'L02AE02', true, false, 60000, 170000, 'specialist', '1 syringe',
                'Monthly hormone injection for prostate and breast cancer.', ['Prostate cancer']],
            ['Zoledronic Acid 4mg/5ml Concentrate', 'Zoledronic Acid', '4mg/5ml', 'infusion', Cat::Other, 'M05BA08', true, false, 25000, 70000, 'specialist', '1 vial (5ml)',
                'Protects bone in cancer that has spread, and treats high blood calcium.', ['Bone metastases']],
            ['Ciclosporin 25mg Capsule', 'Ciclosporin', '25mg', 'capsule', Cat::Other, 'L04AD01', true, false, 20000, 55000, 'specialist', '50 capsules',
                'Prevents rejection after transplantation and treats severe autoimmune disease.', ['Transplant rejection']],
            ['Tacrolimus 1mg Capsule', 'Tacrolimus', '1mg', 'capsule', Cat::Other, 'L04AD02', true, false, 30000, 85000, 'specialist', '50 capsules',
                'Prevents rejection after organ transplantation. Blood levels are monitored.', ['Transplant rejection']],
            ['Methotrexate 2.5mg Tablet', 'Methotrexate', '2.5mg', 'tablet', Cat::Other, 'L01BA01', true, false, 3000, 9000, 'uncommon', '30 tablets',
                'Taken ONCE WEEKLY for rheumatoid arthritis and psoriasis. Daily dosing is dangerous.', ['Rheumatoid arthritis', 'Psoriasis']],
            ['Hydroxychloroquine 200mg Tablet', 'Hydroxychloroquine Sulfate', '200mg', 'tablet', Cat::Other, 'P01BA02', true, false, 2500, 7000, 'uncommon', '30 tablets',
                'Disease-modifying medicine for rheumatoid arthritis and lupus.', ['Rheumatoid arthritis', 'Lupus']],
            ['Sulfasalazine 500mg Tablet', 'Sulfasalazine', '500mg', 'tablet', Cat::Other, 'A07EC01', true, false, 3000, 8000, 'uncommon', '100 tablets',
                'Disease-modifying medicine for rheumatoid arthritis and ulcerative colitis.', ['Rheumatoid arthritis', 'Ulcerative colitis']],
            ['Azathioprine 50mg Tablet', 'Azathioprine', '50mg', 'tablet', Cat::Other, 'L04AX01', true, false, 6000, 16000, 'specialist', '100 tablets',
                'Immunosuppressant for autoimmune disease and after transplantation.', ['Autoimmune disease']],

            // ── Vaccines ─────────────────────────────────────────────────────
            ['BCG Vaccine', 'BCG Vaccine', '1 dose', 'injection', Cat::Other, 'J07AN01', true, false, 1500, 5000, 'standard', '20-dose vial',
                'Given at birth to protect against severe childhood tuberculosis.', ['Immunisation', 'Children']],
            ['Hepatitis B Vaccine 20mcg/mL', 'Hepatitis B Vaccine', '20mcg/mL', 'injection', Cat::Other, 'J07BC01', true, false, 3000, 9000, 'standard', '1 vial',
                'Three-dose course protecting against hepatitis B infection.', ['Immunisation']],
            ['Measles-Rubella Vaccine', 'Measles-Rubella Vaccine', '1 dose', 'injection', Cat::Other, 'J07BD54', true, false, 2000, 6000, 'standard', '10-dose vial',
                'Routine childhood vaccine against measles and rubella.', ['Immunisation', 'Children']],
            ['Tetanus-Diphtheria (Td) Vaccine', 'Tetanus-Diphtheria Vaccine', '1 dose', 'injection', Cat::Other, 'J07AM51', true, false, 1500, 5000, 'standard', '10-dose vial',
                'Booster vaccine given in pregnancy and after injury.', ['Immunisation', 'Pregnancy']],
            ['Yellow Fever Vaccine', 'Yellow Fever Vaccine', '1 dose', 'injection', Cat::Other, 'J07BL01', true, false, 5000, 15000, 'standard', '10-dose vial',
                'Single dose gives lifelong protection; required for travel to many countries.', ['Immunisation', 'Travel']],
            ['Bivalent Oral Poliomyelitis Vaccine (bOPV)', 'Poliomyelitis Vaccine (Oral, Bivalent)', '1 dose', 'drops', Cat::Other, 'J07BF02', true, false, 800, 3000, 'standard', '20-dose vial',
                'Oral polio drops given in routine immunisation and campaigns.', ['Immunisation', 'Children']],
            ['Inactivated Poliomyelitis Vaccine (IPV)', 'Poliomyelitis Vaccine (Inactivated)', '1 dose', 'injection', Cat::Other, 'J07BF03', true, false, 3000, 9000, 'uncommon', '10-dose vial',
                'Injected polio vaccine given alongside oral polio vaccine.', ['Immunisation', 'Children']],
            ['DTP-HepB-Hib Pentavalent Vaccine', 'Pentavalent Vaccine', '1 dose', 'injection', Cat::Other, 'J07CA09', true, false, 4000, 12000, 'standard', '10-dose vial',
                'Five-in-one infant vaccine covering diphtheria, tetanus, pertussis, hepatitis B and Hib.', ['Immunisation', 'Children']],
            ['Rotavirus Vaccine', 'Rotavirus Vaccine', '1 dose', 'drops', Cat::Other, 'J07BH01', true, false, 5000, 15000, 'uncommon', '1 tube',
                'Oral infant vaccine against severe rotavirus diarrhoea.', ['Immunisation', 'Children']],
            ['Pneumococcal Conjugate Vaccine (PCV13)', 'Pneumococcal Conjugate Vaccine', '1 dose', 'injection', Cat::Other, 'J07AL02', true, false, 8000, 25000, 'uncommon', '1 vial',
                'Protects infants against pneumococcal pneumonia and meningitis.', ['Immunisation', 'Children']],
            ['Human Papillomavirus Vaccine (Quadrivalent)', 'Human Papillomavirus Vaccine', '1 dose', 'injection', Cat::Other, 'J07BM01', true, false, 15000, 45000, 'uncommon', '1 vial',
                'Given to adolescent girls to prevent cervical cancer.', ['Immunisation', 'Cervical cancer prevention']],
            ['Meningococcal A Conjugate Vaccine', 'Meningococcal A Conjugate Vaccine', '1 dose', 'injection', Cat::Other, 'J07AH09', true, false, 3000, 10000, 'uncommon', '10-dose vial',
                'Protects against group A meningococcal meningitis in the meningitis belt.', ['Immunisation']],
            ['Rabies Vaccine', 'Rabies Vaccine', '1 dose', 'injection', Cat::Other, 'J07BG01', true, false, 15000, 45000, 'uncommon', '1 vial',
                'Given after an animal bite, and before travel to high-risk areas.', ['Rabies prophylaxis']],
            ['Typhoid Conjugate Vaccine', 'Typhoid Conjugate Vaccine', '1 dose', 'injection', Cat::Other, 'J07AP03', true, false, 8000, 25000, 'uncommon', '1 vial',
                'Single dose protecting against typhoid fever.', ['Immunisation']],
            ['COVID-19 mRNA Vaccine', 'COVID-19 Vaccine (mRNA)', '1 dose', 'injection', Cat::Other, 'J07BN01', true, false, 3000, 12000, 'uncommon', '6-dose vial',
                'Protects against severe COVID-19 disease.', ['Immunisation']],

            // ── Neonatal and diagnostics ─────────────────────────────────────
            ['Beractant 25mg/mL Intratracheal Suspension', 'Beractant', '25mg/mL', 'suspension', Cat::MaternalChild, 'R07AA02', true, false, 120000, 350000, 'hospital', '1 vial (8ml)',
                'Surfactant given into the lungs of a premature baby with breathing difficulty.', ['Newborn care', 'Respiratory distress']],
            ['Caffeine Citrate 20mg/mL Injection', 'Caffeine Citrate', '20mg/mL', 'injection', Cat::MaternalChild, 'N06BC01', true, false, 15000, 40000, 'hospital', '1 vial (3ml)',
                'Treats apnoea of prematurity in newborn babies.', ['Newborn care']],
            ['Barium Sulfate Oral Suspension 200ml', 'Barium Sulfate', '200ml', 'suspension', Cat::Other, 'V08BA01', true, false, 6000, 16000, 'uncommon', '1 bottle (200ml)',
                'Contrast agent swallowed before an X-ray of the gullet, stomach or bowel.', ['Radiology contrast']],
            ['Iohexol 350mg I/mL Injection 50ml', 'Iohexol', '350mg I/mL', 'injection', Cat::Other, 'V08AB02', true, false, 20000, 55000, 'hospital', '1 vial (50ml)',
                'Iodinated contrast used for CT scans and angiography.', ['Radiology contrast']],
            ['Tuberculin PPD 2 TU/0.1ml Injection', 'Tuberculin Purified Protein Derivative', '2 TU/0.1ml', 'injection', Cat::Other, 'V04CF01', true, false, 8000, 22000, 'uncommon', '1 vial (1.5ml)',
                'Skin test used to detect tuberculosis infection.', ['Tuberculosis testing']],
        ];
    }
}
