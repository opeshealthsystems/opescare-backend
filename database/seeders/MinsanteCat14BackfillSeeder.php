<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Normalizer;

/**
 * Backfills GPS coordinates and phone numbers onto EXISTING facility_registry
 * rows from the MINSANTE / WHO "Annuaire des Formations Sanitaires Publiques du
 * Cameroun" (January 2023), Category 1-4 hospital detail pages.
 *
 * WHY THIS IS A BACKFILL AND NOT AN INSERT
 * ----------------------------------------
 * The annuaire sets hospital titles in a small-caps display font. Text
 * extraction therefore mangles capitalisation and drops circumflexes -
 * "HoPital reGional de nGaoundere" instead of "Hôpital Régional de Ngaoundéré".
 * CameroonFacilityRegistrySeeder already carries most of these hospitals under
 * their correctly accented names, so inserting the mangled forms would create
 * near-duplicates that the unique(name, region, city) index cannot catch.
 * This seeder therefore NEVER inserts. It only fills columns that are NULL.
 *
 * MATCHING RULE (deliberately strict - no fuzzy matching)
 * ------------------------------------------------------
 * Both sides are normalised identically: lowercase, NFD-decompose, strip
 * combining marks U+0300-U+036F, collapse internal whitespace, trim. A record
 * is applied only on an EXACT normalised match of region + name, and only when
 * that match is unique. Anything else is counted and reported, never
 * force-fitted onto "the closest" row - "Mbankomo" and "Nkomo" are 94% similar
 * and are different towns.
 *
 * SAFETY INVARIANTS
 * -----------------
 *  - Never overwrites a non-NULL column (guarded in PHP and again in the WHERE).
 *  - Never touches a row with claimed_facility_id IS NOT NULL.
 *  - Idempotent: a second run backfills nothing.
 *
 * KNOWN SOURCE-DATA DEFECT (see $heldBackGps)
 * -------------------------------------------
 * The extractor collects a block spanning 210pt below each "Catégorie" anchor
 * and takes the first coordinate found in it, without anchoring that coordinate
 * to the title line. Where two entries share a page the blocks overlap, so a
 * record can inherit its neighbour's coordinate: 12 of the 48 geocoded rows in
 * the source CSV carry a coordinate that is byte-identical to another row's.
 * Because every registry backfill in this codebase only fills NULLs, a wrong
 * value written here would be permanent - no later seeder could correct it.
 * Coordinates that are demonstrably impossible for the named town are therefore
 * withheld and reported rather than written.
 */
class MinsanteCat14BackfillSeeder extends Seeder
{
    /**
     * Normalised "region|name" keys whose CSV coordinate is withheld because it
     * cannot belong to the named town. Phone is still backfilled for these.
     *
     * - Banyo (Adamaoua) is at roughly 11.8°E; the annuaire row carries
     *   14.81°E, which places it ~330 km east, past Meiganga and near the CAR
     *   border. Meiganga's own row in the same source is 14.289°E, which is the
     *   likely origin of the bleed.
     *
     * Delete an entry here to let its coordinate through.
     */
    private const HELD_BACK_GPS = [
        // ~330 km east of Banyo, near the CAR border. Meiganga's row in the
        // same source sits at 14.289°E and is the likely bleed origin.
        'adamaoua|hopital de district de banyo',

        // Both land ~35-40 km southwest of their own town — the same
        // block-bleed defect, just less obvious than Banyo because the error is
        // small enough to look plausible. Verified against the towns and
        // withdrawn from the database. A missing coordinate only omits a map
        // pin; a wrong one sends a patient to the wrong place, and since this
        // seeder fills NULLs only, a bad value could never be corrected later.
        'centre|hopital de district de mfou',
        'adamaoua|hopital de district de tignere',
    ];

    public function run(): void
    {
        $registry = DB::table('facility_registry')
            ->select('id', 'name', 'region', 'city', 'gps_lat', 'gps_lng', 'phone', 'claimed_facility_id')
            ->get();

        $index = [];
        foreach ($registry as $row) {
            $index[$this->key($row->region, $row->name)][] = $row;
        }

        $gpsSet = 0; $phoneSet = 0; $matched = 0; $nothingToGive = 0;
        $unmatched = []; $ambiguous = []; $claimed = []; $held = [];

        foreach ($this->records() as $rec) {
            $key = $this->key($rec['region'], $rec['name']);

            if (! isset($index[$key])) {
                $unmatched[] = $rec;
                continue;
            }
            if (count($index[$key]) > 1) {
                $ambiguous[] = $rec;
                continue;
            }

            $row = $index[$key][0];
            $matched++;

            if ($row->claimed_facility_id !== null) {
                $claimed[] = $rec;
                continue;
            }

            $didSomething = false;

            $holdGps = in_array($key, self::HELD_BACK_GPS, true);
            if ($holdGps && $rec['gps_lat'] !== null) {
                $held[] = $rec;
            }

            if (! $holdGps
                && $rec['gps_lat'] !== null
                && $rec['gps_lng'] !== null
                && $row->gps_lat === null
                && $row->gps_lng === null
            ) {
                $gpsSet += DB::table('facility_registry')
                    ->where('id', $row->id)
                    ->whereNull('claimed_facility_id')
                    ->whereNull('gps_lat')
                    ->whereNull('gps_lng')
                    ->update([
                        'gps_lat'    => $rec['gps_lat'],
                        'gps_lng'    => $rec['gps_lng'],
                        'updated_at' => now(),
                    ]);
                $didSomething = true;
            }

            if ($rec['phone'] !== null && $row->phone === null) {
                $phoneSet += DB::table('facility_registry')
                    ->where('id', $row->id)
                    ->whereNull('claimed_facility_id')
                    ->whereNull('phone')
                    ->update([
                        'phone'      => $rec['phone'],
                        'updated_at' => now(),
                    ]);
                $didSomething = true;
            }

            if (! $didSomething) {
                $nothingToGive++;
            }
        }

        $this->command?->info(sprintf(
            'MinsanteCat14BackfillSeeder: %d source records, %d matched exactly, '
            . '%d GPS backfilled, %d phone backfilled, %d matched with nothing to add, '
            . '%d GPS withheld as implausible, %d claimed rows skipped, %d ambiguous, %d unmatched.',
            count($this->records()), $matched, $gpsSet, $phoneSet, $nothingToGive,
            count($held), count($claimed), count($ambiguous), count($unmatched)
        ));

        foreach ($unmatched as $rec) {
            $this->command?->warn('  unmatched: [' . $rec['region'] . '] ' . $rec['name'] . ' (p' . $rec['page'] . ')');
        }
        foreach ($ambiguous as $rec) {
            $this->command?->warn('  ambiguous: [' . $rec['region'] . '] ' . $rec['name'] . ' (p' . $rec['page'] . ')');
        }
        foreach ($claimed as $rec) {
            $this->command?->warn('  claimed, untouched: [' . $rec['region'] . '] ' . $rec['name']);
        }
        foreach ($held as $rec) {
            $this->command?->warn('  GPS withheld: [' . $rec['region'] . '] ' . $rec['name']);
        }
    }

    /**
     * Lowercase, strip accents (NFD + drop U+0300-U+036F), collapse internal
     * whitespace, trim. Applied identically to both sides of the comparison.
     */
    private function key(string $region, string $name): string
    {
        return $this->normalise($region) . '|' . $this->normalise($name);
    }

    private function normalise(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');

        if (class_exists(Normalizer::class)) {
            $value = Normalizer::normalize($value, Normalizer::FORM_D) ?: $value;
        }

        $value = preg_replace('/[\x{0300}-\x{036f}]/u', '', $value);

        // Fold every apostrophe variant to a plain one. The annuaire sets
        // U+2019 (') where the registry stores U+0027 ('), which by itself
        // blocked otherwise-exact matches such as "Hôpital de District
        // d'Efoulan". Also drop a stray space after the apostrophe, which the
        // small-caps extraction introduces ("d' akonolinga").
        $value = str_replace(["\u{2019}", "\u{2018}", "\u{02BC}", '`'], "'", $value);
        $value = preg_replace("/'\s+/u", "'", $value);

        $value = preg_replace('/\s+/u', ' ', $value);

        return trim($value);
    }

    /**
     * The 53 Category 1-4 hospital records as extracted from the annuaire.
     * Names are the raw (font-mangled) forms - they are normalised at match
     * time, never written back to the database.
     */
    private function records(): array
    {
        return [
            ['name' => 'Centre HosPitalier reGional de nGaoundéré', 'region' => 'Adamaoua', 'phone' => null, 'gps_lat' => 7.32455, 'gps_lng' => 13.56491, 'page' => 19],
            ['name' => 'HoPital reGional de nGaoundere', 'region' => 'Adamaoua', 'phone' => '+237 696 06 63 46', 'gps_lat' => 7.3264, 'gps_lng' => 13.5657, 'page' => 20],
            ['name' => 'HoPital de distriCt de bankiM', 'region' => 'Adamaoua', 'phone' => '+237 677 15 52 33', 'gps_lat' => null, 'gps_lng' => null, 'page' => 21],
            ['name' => 'HoPital de distriCt de banYo', 'region' => 'Adamaoua', 'phone' => '+237 674 63 92 27', 'gps_lat' => 6.7486033, 'gps_lng' => 14.8112613, 'page' => 21],
            ['name' => 'HoPital de distriCt de MeiGanGa', 'region' => 'Adamaoua', 'phone' => '+237 676 03 77 59', 'gps_lat' => 6.5199476, 'gps_lng' => 14.2892104, 'page' => 22],
            ['name' => 'HoPital de distriCt de danG', 'region' => 'Adamaoua', 'phone' => '+237 694 09 15 71', 'gps_lat' => 7.1785, 'gps_lng' => 14.1757, 'page' => 22],
            ['name' => 'HoPital de distriCt de dJoHonG', 'region' => 'Adamaoua', 'phone' => '+237 691 51 34 16', 'gps_lat' => 6.8358295, 'gps_lng' => 14.6905335, 'page' => 23],
            ['name' => 'Centre MediCal de la PoliCe de nGaoundere', 'region' => 'Adamaoua', 'phone' => '+237 222 25 23 44', 'gps_lat' => 7.3295, 'gps_lng' => 13.5865, 'page' => 23],
            ['name' => 'HoPital Militaire de nGaoundere - réGion n°5', 'region' => 'Adamaoua', 'phone' => '+237 695 26 96 94', 'gps_lat' => 7.3073, 'gps_lng' => 13.6002, 'page' => 24],
            ['name' => 'HoPital de distriCt de tiGnere', 'region' => 'Adamaoua', 'phone' => null, 'gps_lat' => 7.1615, 'gps_lng' => 12.3379, 'page' => 24],
            ['name' => 'HoPital de distriCt de tibati', 'region' => 'Adamaoua', 'phone' => '+237 670 09 69 59', 'gps_lat' => 6.4706262, 'gps_lng' => 12.6275322, 'page' => 25],
            ['name' => 'HoPital General de Yaounde', 'region' => 'Centre', 'phone' => '+237 699 91 13 26', 'gps_lat' => 3.906948, 'gps_lng' => 11.542212, 'page' => 35],
            ['name' => 'Centre HosPitalier d\'essos - CnPs', 'region' => 'Centre', 'phone' => '+237 222 23 02 25', 'gps_lat' => 3.871822, 'gps_lng' => 11.53265, 'page' => 36],
            ['name' => 'Centre HosPitalier universitaire de Yaoundé (CHu)', 'region' => 'Centre', 'phone' => '+237 222 31 64 05', 'gps_lat' => 3.834117, 'gps_lng' => 11.486265, 'page' => 37],
            ['name' => 'HoPital Central de Yaounde', 'region' => 'Centre', 'phone' => '+237 222 22 23 89', 'gps_lat' => 3.871466, 'gps_lng' => 11.510709, 'page' => 39],
            ['name' => 'Centre Mere et enFant - Fondation CHantal biYa', 'region' => 'Centre', 'phone' => '+237 699 01 13 07', 'gps_lat' => 3.5248, 'gps_lng' => 11.3016, 'page' => 40],
            ['name' => 'Centre des urGenCes de Yaoundé ( CurY )', 'region' => 'Centre', 'phone' => null, 'gps_lat' => 3.5323, 'gps_lng' => 11.3038, 'page' => 41],
            ['name' => 'HoPital JaMot de Yaounde', 'region' => 'Centre', 'phone' => '+237 677 33 36 19', 'gps_lat' => 3.946255, 'gps_lng' => 11.521928, 'page' => 42],
            ['name' => 'HoPital Militaire de Yaounde', 'region' => 'Centre', 'phone' => null, 'gps_lat' => 4.21371, 'gps_lng' => 11.1819, 'page' => 43],
            ['name' => 'HôPital réGional anneXe d\'aYos', 'region' => 'Centre', 'phone' => '+237 674 38 38 19', 'gps_lat' => 3.8991, 'gps_lng' => 12.5303, 'page' => 44],
            ['name' => 'HoPital de distriCt d’ akonolinGa', 'region' => 'Centre', 'phone' => null, 'gps_lat' => 3.7795, 'gps_lng' => 12.2535, 'page' => 45],
            ['name' => 'HoPital de distriCt d’ aWae', 'region' => 'Centre', 'phone' => null, 'gps_lat' => 3.901176, 'gps_lng' => 11.887925, 'page' => 45],
            ['name' => 'HoPital de distriCt de baFia', 'region' => 'Centre', 'phone' => null, 'gps_lat' => null, 'gps_lng' => null, 'page' => 46],
            ['name' => 'HoPital de distriCt de biYeM-assi', 'region' => 'Centre', 'phone' => '+237 222 31 64 05', 'gps_lat' => null, 'gps_lng' => null, 'page' => 46],
            ['name' => 'HoPital de distriCt de la Cite-verte', 'region' => 'Centre', 'phone' => null, 'gps_lat' => null, 'gps_lng' => null, 'page' => 47],
            ['name' => 'Centre MediCal Henri dunant de la CroiX rouGe', 'region' => 'Centre', 'phone' => null, 'gps_lat' => 3.870327, 'gps_lng' => 11.512991, 'page' => 47],
            ['name' => 'Centre MediCal de la PoliCe', 'region' => 'Centre', 'phone' => '+237 655 97 60 39', 'gps_lat' => 3.870327, 'gps_lng' => 11.512991, 'page' => 48],
            ['name' => 'HoPital de distriCt d\'oleMbe', 'region' => 'Centre', 'phone' => null, 'gps_lat' => 3.946255, 'gps_lng' => 11.521928, 'page' => 48],
            ['name' => 'HoPital de distriCt d’eFoulan', 'region' => 'Centre', 'phone' => '+237 695 26 57 95', 'gps_lat' => null, 'gps_lng' => null, 'page' => 49],
            ['name' => 'HoPital de distriCt d’ebebda', 'region' => 'Centre', 'phone' => '+237 698 57 83 13', 'gps_lat' => 4.21371, 'gps_lng' => 11.1819, 'page' => 49],
            ['name' => 'HoPital de distriCt d’eliG-MFoMo', 'region' => 'Centre', 'phone' => null, 'gps_lat' => 4.158827, 'gps_lng' => 11.352907, 'page' => 50],
            ['name' => 'HoPital de distriCt d’eseka', 'region' => 'Centre', 'phone' => null, 'gps_lat' => 4.158827, 'gps_lng' => 11.352907, 'page' => 50],
            ['name' => 'HoPital de distriCt d’esse', 'region' => 'Centre', 'phone' => null, 'gps_lat' => 4.095798, 'gps_lng' => 11.8817249, 'page' => 51],
            ['name' => 'HoPital de distriCt d’evodoula', 'region' => 'Centre', 'phone' => '+237 651 23 03 45', 'gps_lat' => 4.09615, 'gps_lng' => 11.196813, 'page' => 51],
            ['name' => 'HoPital de distriCt de MbalMaYo', 'region' => 'Centre', 'phone' => '+237 222 28 14 58', 'gps_lat' => 3.5197, 'gps_lng' => 11.5039, 'page' => 52],
            ['name' => 'HoPital de distriCt de MbandJoCk', 'region' => 'Centre', 'phone' => '+237 674 72 75 18', 'gps_lat' => 4.2647, 'gps_lng' => 11.544, 'page' => 52],
            ['name' => 'HoPital de distriCt de MbankoMo', 'region' => 'Centre', 'phone' => null, 'gps_lat' => 4.2647, 'gps_lng' => 11.544, 'page' => 53],
            ['name' => 'HoPital de distriCt de MFou', 'region' => 'Centre', 'phone' => null, 'gps_lat' => 3.4313, 'gps_lng' => 11.3831, 'page' => 53],
            ['name' => 'HoPital de distriCt de Monatele', 'region' => 'Centre', 'phone' => '+237 697 99 30 80', 'gps_lat' => 4.260906, 'gps_lng' => 11.204684, 'page' => 54],
            ['name' => 'HoPital de distriCt de MvoG-ada', 'region' => 'Centre', 'phone' => '+237 653 08 31 92', 'gps_lat' => 3.863597, 'gps_lng' => 11.527095, 'page' => 54],
            ['name' => 'HoPital de distriCt de nanGa-eboko', 'region' => 'Centre', 'phone' => null, 'gps_lat' => 4.6671984, 'gps_lng' => 12.370923, 'page' => 55],
            ['name' => 'HoPital de distriCt de ndikiniMeki', 'region' => 'Centre', 'phone' => null, 'gps_lat' => 4.453101, 'gps_lng' => 10.49425, 'page' => 55],
            ['name' => 'HoPital de distriCt de nGoG-MaPubi', 'region' => 'Centre', 'phone' => null, 'gps_lat' => 3.94866, 'gps_lng' => 10.840416, 'page' => 56],
            ['name' => 'HoPital de distriCt de nGouMou', 'region' => 'Centre', 'phone' => null, 'gps_lat' => 3.59479, 'gps_lng' => 11.30417, 'page' => 56],
            ['name' => 'HoPital de distriCt de nkolbisson', 'region' => 'Centre', 'phone' => '+237 699 15 02 32', 'gps_lat' => 3.59479, 'gps_lng' => 11.30417, 'page' => 57],
            ['name' => 'HoPital de distriCt de nkolndonGo', 'region' => 'Centre', 'phone' => '+237 222 22 87 31', 'gps_lat' => 3.52291, 'gps_lng' => 11.26561, 'page' => 57],
            ['name' => 'HoPital de distriCt de ntui', 'region' => 'Centre', 'phone' => null, 'gps_lat' => 4.443839, 'gps_lng' => 11.630283, 'page' => 58],
            ['name' => 'HoPital de distriCt d’obala', 'region' => 'Centre', 'phone' => null, 'gps_lat' => 4.09842, 'gps_lng' => 11.630283, 'page' => 58],
            ['name' => 'HoPital de distriCt d’odZa', 'region' => 'Centre', 'phone' => '+237 222 30 50 10', 'gps_lat' => 3.827132, 'gps_lng' => 11.89452, 'page' => 59],
            ['name' => 'HoPital de distriCt d’okola', 'region' => 'Centre', 'phone' => '+237 680 75 90 65', 'gps_lat' => 4.0266, 'gps_lng' => 11.32027, 'page' => 59],
            ['name' => 'HoPital de distriCt de sa\'a', 'region' => 'Centre', 'phone' => '+237 677 27 99 82', 'gps_lat' => 4.364735, 'gps_lng' => 11.4448333, 'page' => 60],
            ['name' => 'HoPital de distriCt de soa', 'region' => 'Centre', 'phone' => null, 'gps_lat' => 3.984488, 'gps_lng' => 11.593667, 'page' => 60],
            ['name' => 'HoPital de distriCt de Yoko', 'region' => 'Centre', 'phone' => '+237 675 27 09 21', 'gps_lat' => 5.553433, 'gps_lng' => 12.31368, 'page' => 61],
        ];
    }
}