<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Extends facility_registry with additional REAL, source-traceable Cameroonian
 * pharmacies, medical laboratories and imaging centres.
 *
 * Two provenances, each carried on the row via `source` + `source_url`:
 *
 *  1. `osm_2026`  — OpenStreetMap (ODbL), queried via the Overpass API on
 *     2026-08-31. Region comes from Overpass point-in-polygon against the
 *     Cameroon admin_level=4 relations; city from the nearest OSM place=city|town
 *     node; name/coords/phone straight from the element's tags. Every row keeps
 *     its OSM element URL in `source_url`.
 *
 *  2. `dpml_minsante_2026` — MINSANTE / Direction de la Pharmacie, du Médicament
 *     et des Laboratoires, "Cartographie des laboratoires d'analyses médicales
 *     privés agréés au Cameroun", Réf 01, version 31 Mars 2026. Entries whose
 *     OBSERVATION column declares the licence lapsed ("Agrément expiré" /
 *     "Agrément non valide") are deliberately excluded.
 *
 * Nothing here is inferred or reconstructed: a facility appears only if it was
 * present in one of those two retrieved sources. Where a source gave no phone,
 * no coordinates or no locality, the column is left NULL rather than guessed.
 *
 * Idempotent, and never touches a row that has been claimed by a facility.
 */
class CameroonPharmacyLabRegistrySeeder extends Seeder
{
    public function run(): void
    {
        $inserted = 0;

        foreach ($this->osmFacilities() as $row) {
            $inserted += $this->insertIfMissing($row, 'osm_2026');
        }

        foreach ($this->dpmlLaboratories() as $row) {
            $inserted += $this->insertIfMissing($row, 'dpml_minsante_2026');
        }

        $this->command?->info(sprintf(
            'CameroonPharmacyLabRegistrySeeder: %d new entries inserted, %d total registry entries.',
            $inserted,
            DB::table('facility_registry')->count()
        ));
    }

    /**
     * Insert only when the row is absent.
     *
     * The unique index is (name, region, city). PostgreSQL treats NULLs as
     * distinct, so a row with a NULL city would slip past insertOrIgnore and be
     * duplicated on every run — hence the explicit existence check, which
     * matches NULL cities properly and keeps the seeder genuinely idempotent.
     *
     * A row already claimed by a facility is never updated or overwritten.
     */
    private function insertIfMissing(array $row, string $source): int
    {
        $exists = DB::table('facility_registry')
            ->where('name', $row['name'])
            ->where('region', $row['region'])
            ->when($row['city'] === null,
                fn ($q) => $q->whereNull('city'),
                fn ($q) => $q->where('city', $row['city']))
            ->exists();

        if ($exists) {
            return 0;
        }

        DB::table('facility_registry')->insertOrIgnore(array_merge($row, [
            'id'         => (string) Str::uuid(),
            'source'     => $source,
            'status'     => 'unverified',
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return 1;
    }

    /**
     * OpenStreetMap contributors, ODbL. Retrieved via Overpass 2026-08-31.
     * 211 entries.
     */
    private function osmFacilities(): array
    {
        return [
            ['name' => 'Pharmacie Adama', 'type' => 'pharmacy', 'region' => 'Adamaoua', 'city' => 'Ngaoundéré', 'gps_lat' => 7.3210125, 'gps_lng' => 13.5751594, 'source_url' => 'https://www.openstreetmap.org/node/3116264554'],
            ['name' => 'Pharmacie Awal', 'type' => 'pharmacy', 'region' => 'Adamaoua', 'city' => 'Ngaoundéré', 'gps_lat' => 7.3191906, 'gps_lng' => 13.5899123, 'source_url' => 'https://www.openstreetmap.org/node/5940279348'],
            ['name' => 'Pharmacie Coop Univers', 'type' => 'pharmacy', 'region' => 'Adamaoua', 'city' => 'Ngaoundéré', 'gps_lat' => 7.2940144, 'gps_lng' => 13.5743036, 'phone' => '695220824', 'source_url' => 'https://www.openstreetmap.org/node/5940279347'],
            ['name' => 'Pharmacie Garou', 'type' => 'pharmacy', 'region' => 'Adamaoua', 'city' => 'Ngaoundéré', 'gps_lat' => 7.3191263, 'gps_lng' => 13.5878922, 'source_url' => 'https://www.openstreetmap.org/node/5940279349'],
            ['name' => 'Pharmacie Ngaha Clovis', 'type' => 'pharmacy', 'region' => 'Adamaoua', 'city' => 'Ngaoundéré', 'gps_lat' => 7.3197974, 'gps_lng' => 13.5927438, 'phone' => '699630238', 'source_url' => 'https://www.openstreetmap.org/node/5940279350'],
            ['name' => 'Pharmacie Oxygène', 'type' => 'pharmacy', 'region' => 'Adamaoua', 'city' => 'Ngaoundéré', 'gps_lat' => 7.3256641, 'gps_lng' => 13.5849246, 'source_url' => 'https://www.openstreetmap.org/node/5126030895'],
            ['name' => 'Pharmacie de Beka-hossere', 'type' => 'pharmacy', 'region' => 'Adamaoua', 'city' => 'Ngaoundéré', 'gps_lat' => 7.311846, 'gps_lng' => 13.5506028, 'phone' => '654819415', 'source_url' => 'https://www.openstreetmap.org/node/5940279344'],
            ['name' => 'Pharmacie de L\'enttente', 'type' => 'pharmacy', 'region' => 'Adamaoua', 'city' => 'Ngaoundéré', 'gps_lat' => 7.3190732, 'gps_lng' => 13.5851314, 'source_url' => 'https://www.openstreetmap.org/node/5940279345'],
            ['name' => 'Pharmacie de L\'esperance', 'type' => 'pharmacy', 'region' => 'Adamaoua', 'city' => 'Ngaoundéré', 'gps_lat' => 7.3211319, 'gps_lng' => 13.5790409, 'phone' => '222251176', 'source_url' => 'https://www.openstreetmap.org/node/5940279339'],
            ['name' => 'Pharmacie de La Vina', 'type' => 'pharmacy', 'region' => 'Adamaoua', 'city' => 'Ngaoundéré', 'gps_lat' => 7.3215403, 'gps_lng' => 13.5826483, 'source_url' => 'https://www.openstreetmap.org/node/5940279346'],
            ['name' => 'Pharmacie de la Gare', 'type' => 'pharmacy', 'region' => 'Adamaoua', 'city' => 'Ngaoundéré', 'gps_lat' => 7.333106, 'gps_lng' => 13.5871131, 'source_url' => 'https://www.openstreetmap.org/node/5940279340'],
            ['name' => 'Pharmacie du campus', 'type' => 'pharmacy', 'region' => 'Adamaoua', 'city' => 'Ngaoundéré', 'gps_lat' => 7.4114884, 'gps_lng' => 13.547933, 'phone' => '243710988', 'source_url' => 'https://www.openstreetmap.org/node/5714563884'],
            ['name' => 'Pharmacie le Saare', 'type' => 'pharmacy', 'region' => 'Adamaoua', 'city' => 'Ngaoundéré', 'gps_lat' => 7.3268497, 'gps_lng' => 13.5832497, 'source_url' => 'https://www.openstreetmap.org/node/5940279341'],
            ['name' => 'Pharmacie La santé', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Akonolinga', 'gps_lat' => 3.7740864, 'gps_lng' => 12.2468556, 'source_url' => 'https://www.openstreetmap.org/way/1414270277'],
            ['name' => 'Pharmacie AIDA', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Bafia', 'gps_lat' => 4.7432912, 'gps_lng' => 11.2251908, 'source_url' => 'https://www.openstreetmap.org/way/332390869'],
            ['name' => 'Pharmacie Bafia', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Bafia', 'gps_lat' => 4.7421709, 'gps_lng' => 11.2250904, 'source_url' => 'https://www.openstreetmap.org/way/324955652'],
            ['name' => 'Pharmacie Tabita', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Mbalmayo', 'gps_lat' => 3.5275258, 'gps_lng' => 11.5147121, 'source_url' => 'https://www.openstreetmap.org/node/11482261166'],
            ['name' => 'Pharmacie Vimli', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Mbalmayo', 'gps_lat' => 3.5208244, 'gps_lng' => 11.5053473, 'source_url' => 'https://www.openstreetmap.org/node/5303143122'],
            ['name' => 'Pharmacie caméric', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Mbankomo', 'gps_lat' => 3.7890339, 'gps_lng' => 11.4012493, 'source_url' => 'https://www.openstreetmap.org/way/211356153'],
            ['name' => 'Afamba', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Obala', 'gps_lat' => 4.1663908, 'gps_lng' => 11.5351055, 'source_url' => 'https://www.openstreetmap.org/node/6021352188'],
            ['name' => 'Pharmacie Saint Thomas', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Obala', 'gps_lat' => 4.1651556, 'gps_lng' => 11.5342753, 'source_url' => 'https://www.openstreetmap.org/node/6112301285'],
            ['name' => 'Pharmacie de la Lekie', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Obala', 'gps_lat' => 4.1671035, 'gps_lng' => 11.5286715, 'source_url' => 'https://www.openstreetmap.org/node/12718321519'],
            ['name' => 'Pharmacie de Sa\'a', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Sa\'a', 'gps_lat' => 4.3623152, 'gps_lng' => 11.4399521, 'source_url' => 'https://www.openstreetmap.org/way/282240345'],
            ['name' => 'Olembé Pharmacy', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Soa', 'gps_lat' => 3.9569055, 'gps_lng' => 11.5334315, 'source_url' => 'https://www.openstreetmap.org/node/9009718217'],
            ['name' => 'Pharmacie Jireh', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Soa', 'gps_lat' => 3.9672671, 'gps_lng' => 11.5936877, 'source_url' => 'https://www.openstreetmap.org/node/2997531365'],
            ['name' => 'Pharmacie de Nkozoa', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Soa', 'gps_lat' => 3.9653961, 'gps_lng' => 11.5407427, 'source_url' => 'https://www.openstreetmap.org/node/13272056302'],
            ['name' => 'Serenity Medical Center', 'type' => 'imaging_center', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.82311, 'gps_lng' => 11.4891829, 'phone' => '683539388', 'source_url' => 'https://www.openstreetmap.org/node/12889045001'],
            ['name' => 'Camdiagnostic', 'type' => 'laboratory', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8600785, 'gps_lng' => 11.5049953, 'source_url' => 'https://www.openstreetmap.org/node/11752581123'],
            ['name' => 'Laboratoire Bethanie deluxe', 'type' => 'laboratory', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.865497, 'gps_lng' => 11.4965594, 'phone' => '+237673026232,691890761', 'source_url' => 'https://www.openstreetmap.org/node/13775416138'],
            ['name' => 'Laboratoire International de Yaoundé', 'type' => 'laboratory', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8991521, 'gps_lng' => 11.5181274, 'source_url' => 'https://www.openstreetmap.org/way/762007064'],
            ['name' => 'Laboratoire Meka', 'type' => 'laboratory', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8932127, 'gps_lng' => 11.5091239, 'source_url' => 'https://www.openstreetmap.org/node/6337736395'],
            ['name' => 'Laboratoire Moderne de Référence', 'type' => 'laboratory', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8707845, 'gps_lng' => 11.4991046, 'phone' => '+237674495446', 'source_url' => 'https://www.openstreetmap.org/node/11400083237'],
            ['name' => 'Laboratoire Prima - analyses médicales', 'type' => 'laboratory', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8794732, 'gps_lng' => 11.5183007, 'source_url' => 'https://www.openstreetmap.org/way/192778421'],
            ['name' => 'Laboratoire d\'analyse médicale Owonto de Mokolo', 'type' => 'laboratory', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8766704, 'gps_lng' => 11.4960822, 'phone' => '+237 677826149', 'source_url' => 'https://www.openstreetmap.org/node/3615210616'],
            ['name' => 'Laboratoire d\'analyses médicales bio-diagnostica', 'type' => 'laboratory', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8673655, 'gps_lng' => 11.5209719, 'phone' => '+237 699 98 45 99', 'source_url' => 'https://www.openstreetmap.org/node/3604582974'],
            ['name' => 'Ananaraie', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8338107, 'gps_lng' => 11.5364517, 'source_url' => 'https://www.openstreetmap.org/way/193528984'],
            ['name' => 'Béatitudes', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8458393, 'gps_lng' => 11.4927971, 'source_url' => 'https://www.openstreetmap.org/way/219052045'],
            ['name' => 'CYFO', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8105093, 'gps_lng' => 11.5400263, 'source_url' => 'https://www.openstreetmap.org/way/219052048'],
            ['name' => 'Cousin Castor', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8697335, 'gps_lng' => 11.5311049, 'source_url' => 'https://www.openstreetmap.org/way/192819817'],
            ['name' => 'François Tchoupou', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8593653, 'gps_lng' => 11.5534379, 'source_url' => 'https://www.openstreetmap.org/way/219052057'],
            ['name' => 'Jouvence', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8300171, 'gps_lng' => 11.4820326, 'source_url' => 'https://www.openstreetmap.org/way/217004251'],
            ['name' => 'Lido', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8701728, 'gps_lng' => 11.5309413, 'source_url' => 'https://www.openstreetmap.org/way/219052059'],
            ['name' => 'Lienou', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8594935, 'gps_lng' => 11.5556369, 'source_url' => 'https://www.openstreetmap.org/way/219052060'],
            ['name' => 'Obili', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8512057, 'gps_lng' => 11.4934746, 'source_url' => 'https://www.openstreetmap.org/way/219052065'],
            ['name' => 'Pharmacie Aumachae', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8688083, 'gps_lng' => 11.5528177, 'source_url' => 'https://www.openstreetmap.org/way/218797818'],
            ['name' => 'Pharmacie Balance', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8617127, 'gps_lng' => 11.5370343, 'phone' => '22237074', 'source_url' => 'https://www.openstreetmap.org/way/218797785'],
            ['name' => 'Pharmacie Buvole Abondo', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8892827, 'gps_lng' => 11.4927156, 'source_url' => 'https://www.openstreetmap.org/way/218797787'],
            ['name' => 'Pharmacie Camerounaise', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.862906, 'gps_lng' => 11.5214425, 'phone' => '+237 2 42 00 11 33', 'source_url' => 'https://www.openstreetmap.org/way/410241002'],
            ['name' => 'Pharmacie Du Cypres', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.872903, 'gps_lng' => 11.4549636, 'source_url' => 'https://www.openstreetmap.org/way/219052051'],
            ['name' => 'Pharmacie Ekounou', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8439552, 'gps_lng' => 11.5399051, 'phone' => '22306778', 'source_url' => 'https://www.openstreetmap.org/way/192874285'],
            ['name' => 'Pharmacie Emmanuel', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8797062, 'gps_lng' => 11.508359, 'source_url' => 'https://www.openstreetmap.org/way/218797788'],
            ['name' => 'Pharmacie Hakkore', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8435794, 'gps_lng' => 11.536017, 'phone' => '99680918', 'source_url' => 'https://www.openstreetmap.org/way/192878102'],
            ['name' => 'Pharmacie Jouvance', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8352357, 'gps_lng' => 11.4849434, 'source_url' => 'https://www.openstreetmap.org/way/217004444'],
            ['name' => 'Pharmacie Moliva', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8779385, 'gps_lng' => 11.4947821, 'source_url' => 'https://www.openstreetmap.org/way/218797792'],
            ['name' => 'Pharmacie Mongale', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8862089, 'gps_lng' => 11.4923311, 'source_url' => 'https://www.openstreetmap.org/way/218797793'],
            ['name' => 'Pharmacie Notre-Dame', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8855856, 'gps_lng' => 11.5026391, 'source_url' => 'https://www.openstreetmap.org/way/218797794'],
            ['name' => 'Pharmacie Nsimeyong', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8352367, 'gps_lng' => 11.4944118, 'phone' => '242-31-42-61', 'source_url' => 'https://www.openstreetmap.org/way/219052067'],
            ['name' => 'Pharmacie Odza', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8075445, 'gps_lng' => 11.5303141, 'phone' => '77741769', 'source_url' => 'https://www.openstreetmap.org/way/218661665'],
            ['name' => 'Pharmacie Royale', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8317579, 'gps_lng' => 11.4707698, 'source_url' => 'https://www.openstreetmap.org/way/217003168'],
            ['name' => 'Pharmacie Saint-Luc', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8680767, 'gps_lng' => 11.5319933, 'source_url' => 'https://www.openstreetmap.org/way/192831466'],
            ['name' => 'Pharmacie Sim Ba\'a', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.828305, 'gps_lng' => 11.4793414, 'source_url' => 'https://www.openstreetmap.org/way/193522681'],
            ['name' => 'Pharmacie Star', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8538515, 'gps_lng' => 11.5240268, 'phone' => '22302569', 'source_url' => 'https://www.openstreetmap.org/way/218661664'],
            ['name' => 'Pharmacie Theriaque', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8522755, 'gps_lng' => 11.5358543, 'phone' => '22306325', 'source_url' => 'https://www.openstreetmap.org/way/192852955'],
            ['name' => 'Pharmacie XAVYO', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8546978, 'gps_lng' => 11.5200944, 'source_url' => 'https://www.openstreetmap.org/way/410232321'],
            ['name' => 'Pharmacie d\'Étondi', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.9164405, 'gps_lng' => 11.5234718, 'source_url' => 'https://www.openstreetmap.org/way/218797796'],
            ['name' => 'Pharmacie de Messassi', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.9422455, 'gps_lng' => 11.5190906, 'source_url' => 'https://www.openstreetmap.org/way/1005200057'],
            ['name' => 'Pharmacie de Nkoldongo', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8560185, 'gps_lng' => 11.5272655, 'phone' => '99358422', 'source_url' => 'https://www.openstreetmap.org/way/218661663'],
            ['name' => 'Pharmacie de Tongolo', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.9068149, 'gps_lng' => 11.52501, 'source_url' => 'https://www.openstreetmap.org/way/218797797'],
            ['name' => 'Pharmacie de la Brique', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8760773, 'gps_lng' => 11.5120364, 'source_url' => 'https://www.openstreetmap.org/way/192811154'],
            ['name' => 'Pharmacie des Acacias', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8412473, 'gps_lng' => 11.4879527, 'source_url' => 'https://www.openstreetmap.org/way/385137757'],
            ['name' => 'Pharmacie des Capucines', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8493537, 'gps_lng' => 11.5215103, 'source_url' => 'https://www.openstreetmap.org/way/192865796'],
            ['name' => 'Pharmacie des Hortensia', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8814503, 'gps_lng' => 11.5065239, 'source_url' => 'https://www.openstreetmap.org/way/218797799'],
            ['name' => 'Pharmacie des Sésames', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8772569, 'gps_lng' => 11.4841477, 'source_url' => 'https://www.openstreetmap.org/way/192809859'],
            ['name' => 'Pharmacie du Château', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8636263, 'gps_lng' => 11.5446105, 'source_url' => 'https://www.openstreetmap.org/way/218797801'],
            ['name' => 'Pharmacie du Jourdain', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8847155, 'gps_lng' => 11.4977339, 'source_url' => 'https://www.openstreetmap.org/way/218797802'],
            ['name' => 'Pharmacie du Palais', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.9145154, 'gps_lng' => 11.525617, 'source_url' => 'https://www.openstreetmap.org/way/218797804'],
            ['name' => 'Pharmacie la Concorde', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8918053, 'gps_lng' => 11.5226326, 'source_url' => 'https://www.openstreetmap.org/way/218797809'],
            ['name' => 'Pharmacie la Flêche', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8675074, 'gps_lng' => 11.5491529, 'phone' => '22230548', 'source_url' => 'https://www.openstreetmap.org/way/218797810'],
            ['name' => 'Pharmacie la Grâce', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8316173, 'gps_lng' => 11.4709087, 'source_url' => 'https://www.openstreetmap.org/way/217002844'],
            ['name' => 'Pharmacie le Cristallis', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8819156, 'gps_lng' => 11.5234988, 'source_url' => 'https://www.openstreetmap.org/way/218797812'],
            ['name' => 'Pharmacie les manguiers', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8985052, 'gps_lng' => 11.5316515, 'source_url' => 'https://www.openstreetmap.org/way/192691240'],
            ['name' => 'Pharmacie École de Police', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8784154, 'gps_lng' => 11.5105848, 'source_url' => 'https://www.openstreetmap.org/way/218797813'],
            ['name' => 'Pharmacie Élobi', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8778123, 'gps_lng' => 11.5010669, 'source_url' => 'https://www.openstreetmap.org/way/218797814'],
            ['name' => 'Quifferou', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8683616, 'gps_lng' => 11.5315165, 'source_url' => 'https://www.openstreetmap.org/way/192827527'],
            ['name' => 'Sweet Life', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8503814, 'gps_lng' => 11.4932952, 'source_url' => 'https://www.openstreetmap.org/node/2017553690'],
            ['name' => 'le Flamboyant', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8435064, 'gps_lng' => 11.4872997, 'source_url' => 'https://www.openstreetmap.org/way/219052089'],
            ['name' => 'Pro-Pharmacie', 'type' => 'pharmacy', 'region' => 'Centre', 'city' => 'Yoko', 'gps_lat' => 5.5409983, 'gps_lng' => 12.3186904, 'source_url' => 'https://www.openstreetmap.org/way/349167282'],
            ['name' => 'Pharmacie Galien', 'type' => 'pharmacy', 'region' => 'Est', 'city' => 'Bertoua', 'gps_lat' => 4.5774887, 'gps_lng' => 13.686422, 'source_url' => 'https://www.openstreetmap.org/node/6555260791'],
            ['name' => 'Pharmacie Mokolo', 'type' => 'pharmacy', 'region' => 'Est', 'city' => 'Bertoua', 'gps_lat' => 4.579299, 'gps_lng' => 13.6841366, 'phone' => '+237222242540', 'source_url' => 'https://www.openstreetmap.org/node/4455405630'],
            ['name' => 'Pharmacie de Bertoua', 'type' => 'pharmacy', 'region' => 'Est', 'city' => 'Bertoua', 'gps_lat' => 4.5756739, 'gps_lng' => 13.6827337, 'source_url' => 'https://www.openstreetmap.org/node/6555260790'],
            ['name' => 'Pharmacie la Générale', 'type' => 'pharmacy', 'region' => 'Est', 'city' => 'Bertoua', 'gps_lat' => 4.5746379, 'gps_lng' => 13.6818732, 'source_url' => 'https://www.openstreetmap.org/node/4455404450'],
            ['name' => 'Pharmacie de Kaélé', 'type' => 'pharmacy', 'region' => 'Extrême-Nord', 'city' => 'Kaélé', 'gps_lat' => 10.1095197, 'gps_lng' => 14.4474219, 'source_url' => 'https://www.openstreetmap.org/way/426140753'],
            ['name' => 'La référence Boîte à Pharmacie du Marche du MIL', 'type' => 'pharmacy', 'region' => 'Extrême-Nord', 'city' => 'Kousseri', 'gps_lat' => 12.070828, 'gps_lng' => 15.030934, 'source_url' => 'https://www.openstreetmap.org/way/399452714'],
            ['name' => 'Pharmacie Al Cnar', 'type' => 'pharmacy', 'region' => 'Extrême-Nord', 'city' => 'Kousseri', 'gps_lat' => 12.1116037, 'gps_lng' => 15.0283757, 'source_url' => 'https://www.openstreetmap.org/node/5596576514'],
            ['name' => 'Pharmacie Al Maraz', 'type' => 'pharmacy', 'region' => 'Extrême-Nord', 'city' => 'Kousseri', 'gps_lat' => 12.0847548, 'gps_lng' => 15.0260294, 'source_url' => 'https://www.openstreetmap.org/way/399645539'],
            ['name' => 'Pharmacie Al Salama', 'type' => 'pharmacy', 'region' => 'Extrême-Nord', 'city' => 'Kousseri', 'gps_lat' => 12.0872325, 'gps_lng' => 15.0280881, 'source_url' => 'https://www.openstreetmap.org/way/399644422'],
            ['name' => 'Pharmacie du Quartier', 'type' => 'pharmacy', 'region' => 'Extrême-Nord', 'city' => 'Kousseri', 'gps_lat' => 12.078113, 'gps_lng' => 15.0344122, 'source_url' => 'https://www.openstreetmap.org/way/399897851'],
            ['name' => 'Pharmacie EMERAUDE', 'type' => 'pharmacy', 'region' => 'Extrême-Nord', 'city' => 'Maroua', 'gps_lat' => 10.6013586, 'gps_lng' => 14.3215949, 'source_url' => 'https://www.openstreetmap.org/way/417527996'],
            ['name' => 'Pharmacie Ferngo', 'type' => 'pharmacy', 'region' => 'Extrême-Nord', 'city' => 'Maroua', 'gps_lat' => 10.5945889, 'gps_lng' => 14.3148331, 'source_url' => 'https://www.openstreetmap.org/way/416948038'],
            ['name' => 'Pharmacie KALIAO', 'type' => 'pharmacy', 'region' => 'Extrême-Nord', 'city' => 'Maroua', 'gps_lat' => 10.5997372, 'gps_lng' => 14.3177771, 'source_url' => 'https://www.openstreetmap.org/way/417527998'],
            ['name' => 'Pharmacie Masseboeuf', 'type' => 'pharmacy', 'region' => 'Extrême-Nord', 'city' => 'Maroua', 'gps_lat' => 10.6050154, 'gps_lng' => 14.3258706, 'source_url' => 'https://www.openstreetmap.org/way/417579446'],
            ['name' => 'Pharmacie de Maroua', 'type' => 'pharmacy', 'region' => 'Extrême-Nord', 'city' => 'Maroua', 'gps_lat' => 10.6013264, 'gps_lng' => 14.3361618, 'source_url' => 'https://www.openstreetmap.org/way/417617546'],
            ['name' => 'Pharmacie de domayo', 'type' => 'pharmacy', 'region' => 'Extrême-Nord', 'city' => 'Maroua', 'gps_lat' => 10.5911831, 'gps_lng' => 14.3106417, 'source_url' => 'https://www.openstreetmap.org/way/416933638'],
            ['name' => 'Pharmacie de lextrème nord', 'type' => 'pharmacy', 'region' => 'Extrême-Nord', 'city' => 'Maroua', 'gps_lat' => 10.6033749, 'gps_lng' => 14.3291326, 'source_url' => 'https://www.openstreetmap.org/way/417579447'],
            ['name' => 'Pharmacie du Boulevard', 'type' => 'pharmacy', 'region' => 'Extrême-Nord', 'city' => 'Maroua', 'gps_lat' => 10.5941316, 'gps_lng' => 14.3218412, 'phone' => '+237222293219', 'source_url' => 'https://www.openstreetmap.org/way/417500711'],
            ['name' => 'Pharmacie du Centre', 'type' => 'pharmacy', 'region' => 'Extrême-Nord', 'city' => 'Maroua', 'gps_lat' => 10.605619, 'gps_lng' => 14.3343796, 'source_url' => 'https://www.openstreetmap.org/way/417617548'],
            ['name' => 'Pharmacie du Sahel', 'type' => 'pharmacy', 'region' => 'Extrême-Nord', 'city' => 'Maroua', 'gps_lat' => 10.5953292, 'gps_lng' => 14.3305074, 'source_url' => 'https://www.openstreetmap.org/way/417508592'],
            ['name' => 'Pharmacie du Mayo Danay', 'type' => 'pharmacy', 'region' => 'Extrême-Nord', 'city' => 'Yagoua', 'gps_lat' => 10.3431214, 'gps_lng' => 15.2307747, 'source_url' => 'https://www.openstreetmap.org/way/463463307'],
            ['name' => 'La Providence', 'type' => 'pharmacy', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0872769, 'gps_lng' => 9.6650832, 'source_url' => 'https://www.openstreetmap.org/way/298435283'],
            ['name' => 'Nkomba Mambanda Phamarcy', 'type' => 'pharmacy', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.071182, 'gps_lng' => 9.6679655, 'source_url' => 'https://www.openstreetmap.org/way/299453623'],
            ['name' => 'Pharmacie Akwa-Nord', 'type' => 'pharmacy', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0733526, 'gps_lng' => 9.7168246, 'source_url' => 'https://www.openstreetmap.org/way/456090655'],
            ['name' => 'Pharmacie Axiale', 'type' => 'pharmacy', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0707651, 'gps_lng' => 9.718707, 'source_url' => 'https://www.openstreetmap.org/way/299769716'],
            ['name' => 'Pharmacie Carrefour Pk11', 'type' => 'pharmacy', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0510887, 'gps_lng' => 9.7767535, 'phone' => '677086975', 'source_url' => 'https://www.openstreetmap.org/way/410034588'],
            ['name' => 'Pharmacie Kotto', 'type' => 'pharmacy', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0917918, 'gps_lng' => 9.7496647, 'source_url' => 'https://www.openstreetmap.org/way/300204108'],
            ['name' => 'Pharmacie La Concorde', 'type' => 'pharmacy', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0550517, 'gps_lng' => 9.6989115, 'source_url' => 'https://www.openstreetmap.org/way/455843766'],
            ['name' => 'Pharmacie La Shekinah', 'type' => 'pharmacy', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0022331, 'gps_lng' => 9.7619248, 'source_url' => 'https://www.openstreetmap.org/way/300367521'],
            ['name' => 'Pharmacie Mondial', 'type' => 'pharmacy', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0579721, 'gps_lng' => 9.7103307, 'source_url' => 'https://www.openstreetmap.org/way/457396216'],
            ['name' => 'Pharmacie Olympique', 'type' => 'pharmacy', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0599154, 'gps_lng' => 9.7022587, 'phone' => '233370297', 'source_url' => 'https://www.openstreetmap.org/way/410257559'],
            ['name' => 'Pharmacie de La République PK14', 'type' => 'pharmacy', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0782897, 'gps_lng' => 9.7934699, 'source_url' => 'https://www.openstreetmap.org/way/300477787'],
            ['name' => 'Pharmacie de Ndobo', 'type' => 'pharmacy', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0943965, 'gps_lng' => 9.6430018, 'source_url' => 'https://www.openstreetmap.org/way/409970886'],
            ['name' => 'Pharmacie de Rocher', 'type' => 'pharmacy', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0486982, 'gps_lng' => 9.7408878, 'source_url' => 'https://www.openstreetmap.org/way/300103488'],
            ['name' => 'Pharmacie de l\'Harmonie', 'type' => 'pharmacy', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0828583, 'gps_lng' => 9.7200695, 'source_url' => 'https://www.openstreetmap.org/way/457299351'],
            ['name' => 'Pharmacie de l\'Horizon', 'type' => 'pharmacy', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0660691, 'gps_lng' => 9.7293575, 'source_url' => 'https://www.openstreetmap.org/way/442604623'],
            ['name' => 'Pharmacie de salen', 'type' => 'pharmacy', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0984116, 'gps_lng' => 9.7369068, 'source_url' => 'https://www.openstreetmap.org/way/300008634'],
            ['name' => 'Pharmacie des Hopitaux', 'type' => 'pharmacy', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0486785, 'gps_lng' => 9.7011281, 'source_url' => 'https://www.openstreetmap.org/way/457419049'],
            ['name' => 'Pharmacy Cosmos Sante', 'type' => 'pharmacy', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0461071, 'gps_lng' => 9.7322225, 'source_url' => 'https://www.openstreetmap.org/way/299887223'],
            ['name' => 'CERBC', 'type' => 'laboratory', 'region' => 'Littoral', 'city' => 'Ekité', 'gps_lat' => 3.8150769, 'gps_lng' => 10.1013821, 'source_url' => 'https://www.openstreetmap.org/way/606726088'],
            ['name' => 'Fondation Médicale Adluchem Pharmacie Centrale Entenne De Garoua', 'type' => 'pharmacy', 'region' => 'Nord', 'city' => 'Garoua', 'gps_lat' => 9.3347384, 'gps_lng' => 13.4059221, 'source_url' => 'https://www.openstreetmap.org/node/6769738329'],
            ['name' => 'Pharmacie Assalam', 'type' => 'pharmacy', 'region' => 'Nord', 'city' => 'Garoua', 'gps_lat' => 9.3075596, 'gps_lng' => 13.4024969, 'source_url' => 'https://www.openstreetmap.org/node/6769738335'],
            ['name' => 'Pharmacie Barka', 'type' => 'pharmacy', 'region' => 'Nord', 'city' => 'Garoua', 'gps_lat' => 9.3071764, 'gps_lng' => 13.3990798, 'source_url' => 'https://www.openstreetmap.org/node/6769738333'],
            ['name' => 'Pharmacie De La Grande Mosquée', 'type' => 'pharmacy', 'region' => 'Nord', 'city' => 'Garoua', 'gps_lat' => 9.3300166, 'gps_lng' => 13.4112141, 'source_url' => 'https://www.openstreetmap.org/node/6769738330'],
            ['name' => 'Pharmacie De La Persévérance', 'type' => 'pharmacy', 'region' => 'Nord', 'city' => 'Garoua', 'gps_lat' => 9.2978343, 'gps_lng' => 13.3995927, 'source_url' => 'https://www.openstreetmap.org/node/6731571956'],
            ['name' => 'Pharmacie Du Grand Marché', 'type' => 'pharmacy', 'region' => 'Nord', 'city' => 'Garoua', 'gps_lat' => 9.3078875, 'gps_lng' => 13.3995071, 'source_url' => 'https://www.openstreetmap.org/node/6769738334'],
            ['name' => 'Pharmacie Du Lamidat Dr Souleymanou Saly', 'type' => 'pharmacy', 'region' => 'Nord', 'city' => 'Garoua', 'gps_lat' => 9.3059256, 'gps_lng' => 13.3982345, 'source_url' => 'https://www.openstreetmap.org/node/6731571950'],
            ['name' => 'Pharmacie Jade', 'type' => 'pharmacy', 'region' => 'Nord', 'city' => 'Garoua', 'gps_lat' => 9.2967464, 'gps_lng' => 13.3891945, 'phone' => '(+237) 658939357', 'source_url' => 'https://www.openstreetmap.org/node/6731571945'],
            ['name' => 'Pharmacie Mere D\'enfants Ni', 'type' => 'pharmacy', 'region' => 'Nord', 'city' => 'Garoua', 'gps_lat' => 9.3037535, 'gps_lng' => 13.3925284, 'source_url' => 'https://www.openstreetmap.org/node/6731571947'],
            ['name' => 'Pharmacie Populaire', 'type' => 'pharmacy', 'region' => 'Nord', 'city' => 'Garoua', 'gps_lat' => 9.3168363, 'gps_lng' => 13.4043696, 'source_url' => 'https://www.openstreetmap.org/node/2353391716'],
            ['name' => 'Pharmacie Provinciale', 'type' => 'pharmacy', 'region' => 'Nord', 'city' => 'Garoua', 'gps_lat' => 9.3188883, 'gps_lng' => 13.4057598, 'source_url' => 'https://www.openstreetmap.org/node/6769738331'],
            ['name' => 'Pharmacie de Djamboutou', 'type' => 'pharmacy', 'region' => 'Nord', 'city' => 'Garoua', 'gps_lat' => 9.3042325, 'gps_lng' => 13.3967556, 'source_url' => 'https://www.openstreetmap.org/node/2352021450'],
            ['name' => 'Pharmacie de Garoua', 'type' => 'pharmacy', 'region' => 'Nord', 'city' => 'Garoua', 'gps_lat' => 9.3072574, 'gps_lng' => 13.3964666, 'source_url' => 'https://www.openstreetmap.org/node/2352021451'],
            ['name' => 'Pharmacie de l\'Amitié', 'type' => 'pharmacy', 'region' => 'Nord', 'city' => 'Garoua', 'gps_lat' => 9.3030541, 'gps_lng' => 13.3964911, 'source_url' => 'https://www.openstreetmap.org/node/2353471794'],
            ['name' => 'Pharmacie de l\'Etoile', 'type' => 'pharmacy', 'region' => 'Nord', 'city' => 'Garoua', 'gps_lat' => 9.2966502, 'gps_lng' => 13.3889622, 'source_url' => 'https://www.openstreetmap.org/node/2353500536'],
            ['name' => 'Pharmacie du Nord', 'type' => 'pharmacy', 'region' => 'Nord', 'city' => 'Garoua', 'gps_lat' => 9.2996212, 'gps_lng' => 13.3904095, 'source_url' => 'https://www.openstreetmap.org/way/227130459'],
            ['name' => 'Pharmacie du centre', 'type' => 'pharmacy', 'region' => 'Nord', 'city' => 'Garoua', 'gps_lat' => 9.3064553, 'gps_lng' => 13.3951274, 'source_url' => 'https://www.openstreetmap.org/node/2318362169'],
            ['name' => 'Faith pharmacy', 'type' => 'pharmacy', 'region' => 'Nord-Ouest', 'city' => 'Ndop', 'gps_lat' => 5.9874847, 'gps_lng' => 10.4346599, 'source_url' => 'https://www.openstreetmap.org/node/1069146913'],
            ['name' => 'Centre de Radiologie et d\'Imagerie Medicale de l\'Ouest', 'type' => 'imaging_center', 'region' => 'Ouest', 'city' => 'Bafoussam', 'gps_lat' => 5.4556672, 'gps_lng' => 10.4297803, 'website' => 'http://crimosarl.com', 'source_url' => 'https://www.openstreetmap.org/way/381189838'],
            ['name' => 'Nouvele Pharmacie du Benin', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Bafoussam', 'gps_lat' => 5.4798952, 'gps_lng' => 10.4136871, 'source_url' => 'https://www.openstreetmap.org/way/384964746'],
            ['name' => 'Nouvelle Pharmacie du Bénin', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Bafoussam', 'gps_lat' => 5.4808768, 'gps_lng' => 10.4130095, 'source_url' => 'https://www.openstreetmap.org/node/3021902094'],
            ['name' => 'Pharmacie Amienoise', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Bafoussam', 'gps_lat' => 5.470068, 'gps_lng' => 10.4174216, 'source_url' => 'https://www.openstreetmap.org/way/424740417'],
            ['name' => 'Pharmacie BINAM', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Bafoussam', 'gps_lat' => 5.4827335, 'gps_lng' => 10.4291904, 'source_url' => 'https://www.openstreetmap.org/node/8676482528'],
            ['name' => 'Pharmacie Madelon', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Bafoussam', 'gps_lat' => 5.4635963, 'gps_lng' => 10.4243995, 'source_url' => 'https://www.openstreetmap.org/way/424940901'],
            ['name' => 'Pharmacie Nectar', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Bafoussam', 'gps_lat' => 5.4844189, 'gps_lng' => 10.4377372, 'source_url' => 'https://www.openstreetmap.org/way/425159783'],
            ['name' => 'Pharmacie Noubissi', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Bafoussam', 'gps_lat' => 5.4717794, 'gps_lng' => 10.4212428, 'source_url' => 'https://www.openstreetmap.org/way/424739322'],
            ['name' => 'Pharmacie SALVIA', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Bafoussam', 'gps_lat' => 5.4894513, 'gps_lng' => 10.4056805, 'source_url' => 'https://www.openstreetmap.org/way/395447253'],
            ['name' => 'Pharmacie Sainte Monique', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Bafoussam', 'gps_lat' => 5.4618517, 'gps_lng' => 10.4277697, 'source_url' => 'https://www.openstreetmap.org/way/425149117'],
            ['name' => 'Pharmacie des Martyrs', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Bafoussam', 'gps_lat' => 5.4777978, 'gps_lng' => 10.416742, 'source_url' => 'https://www.openstreetmap.org/way/372956626'],
            ['name' => 'Pharmacie des Montagnes', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Bafoussam', 'gps_lat' => 5.4761661, 'gps_lng' => 10.4187008, 'source_url' => 'https://www.openstreetmap.org/way/424738604'],
            ['name' => 'Pharmacie du Benin', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Bafoussam', 'gps_lat' => 5.4800181, 'gps_lng' => 10.4135681, 'source_url' => 'https://www.openstreetmap.org/way/384964681'],
            ['name' => 'Pharmacie du Marché B', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Bafoussam', 'gps_lat' => 5.4859965, 'gps_lng' => 10.4091119, 'source_url' => 'https://www.openstreetmap.org/way/395465452'],
            ['name' => 'Pharmacie du Secours', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Bafoussam', 'gps_lat' => 5.4703727, 'gps_lng' => 10.4214663, 'source_url' => 'https://www.openstreetmap.org/way/424738603'],
            ['name' => 'Pharmacie la Grâce', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Bafoussam', 'gps_lat' => 5.4802001, 'gps_lng' => 10.4232115, 'source_url' => 'https://www.openstreetmap.org/way/425155470'],
            ['name' => 'Pharmacie la vision', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Bafoussam', 'gps_lat' => 5.4800769, 'gps_lng' => 10.4161428, 'source_url' => 'https://www.openstreetmap.org/way/384479420'],
            ['name' => 'Pharmacie pierre Moyo', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Bafoussam', 'gps_lat' => 5.4781648, 'gps_lng' => 10.4189688, 'source_url' => 'https://www.openstreetmap.org/way/384822849'],
            ['name' => 'Polypharma', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Bafoussam', 'gps_lat' => 5.4806769, 'gps_lng' => 10.4251086, 'source_url' => 'https://www.openstreetmap.org/way/425155472'],
            ['name' => 'Koung Khi', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Bandjoun', 'gps_lat' => 5.3717708, 'gps_lng' => 10.4126455, 'source_url' => 'https://www.openstreetmap.org/node/7815381493'],
            ['name' => 'New Todjon', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Bandjoun', 'gps_lat' => 5.3729973, 'gps_lng' => 10.4148181, 'source_url' => 'https://www.openstreetmap.org/node/7815381492'],
            ['name' => 'Pharmacie De L\'espoir', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Bangangté', 'gps_lat' => 5.1421589, 'gps_lng' => 10.5208055, 'phone' => '33484513', 'source_url' => 'https://www.openstreetmap.org/node/4979030622'],
            ['name' => 'Pharmacie Banka', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Banka', 'gps_lat' => 5.1627255, 'gps_lng' => 10.1912603, 'source_url' => 'https://www.openstreetmap.org/node/9063451640'],
            ['name' => 'Grande Pharmacie Lah', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Dschang', 'gps_lat' => 5.4462702, 'gps_lng' => 10.0561683, 'source_url' => 'https://www.openstreetmap.org/node/6352362787'],
            ['name' => 'Pharmacie Départementale', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Dschang', 'gps_lat' => 5.452106, 'gps_lng' => 10.0674435, 'source_url' => 'https://www.openstreetmap.org/node/6352362790'],
            ['name' => 'Pharmacie de l\'Unité', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Dschang', 'gps_lat' => 5.4582812, 'gps_lng' => 10.0692694, 'source_url' => 'https://www.openstreetmap.org/node/6352362789'],
            ['name' => 'Pharmacie de la Gare', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Dschang', 'gps_lat' => 5.4402491, 'gps_lng' => 10.0500801, 'source_url' => 'https://www.openstreetmap.org/node/6352362785'],
            ['name' => 'Pharmacie de la Menoua', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Dschang', 'gps_lat' => 5.4478425, 'gps_lng' => 10.0600194, 'source_url' => 'https://www.openstreetmap.org/node/6352362788'],
            ['name' => 'Pharmacie du Centre', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Dschang', 'gps_lat' => 5.444414, 'gps_lng' => 10.0554768, 'source_url' => 'https://www.openstreetmap.org/node/4534873636'],
            ['name' => 'Pharmacie du Marché', 'type' => 'pharmacy', 'region' => 'Ouest', 'city' => 'Dschang', 'gps_lat' => 5.4426714, 'gps_lng' => 10.0535936, 'source_url' => 'https://www.openstreetmap.org/node/6352362786'],
            ['name' => 'Laboratoire BETHSAÏDA', 'type' => 'laboratory', 'region' => 'Sud', 'city' => 'Ebolowa', 'gps_lat' => 2.9133588, 'gps_lng' => 11.1678124, 'phone' => '+237 6 98 16 64 12', 'source_url' => 'https://www.openstreetmap.org/node/11465294988'],
            ['name' => 'Laboratoire De Confiance', 'type' => 'laboratory', 'region' => 'Sud', 'city' => 'Ebolowa', 'gps_lat' => 2.9102489, 'gps_lng' => 11.1478511, 'phone' => '+237 6 93 52 95 69', 'source_url' => 'https://www.openstreetmap.org/way/1290844921'],
            ['name' => 'Laboratoire Simama', 'type' => 'laboratory', 'region' => 'Sud', 'city' => 'Ebolowa', 'gps_lat' => 2.9173912, 'gps_lng' => 11.1546719, 'phone' => '+237 6 56 56 63 96', 'source_url' => 'https://www.openstreetmap.org/node/11480777375'],
            ['name' => 'Pharmacie Equaser', 'type' => 'pharmacy', 'region' => 'Sud', 'city' => 'Ebolowa', 'gps_lat' => 2.9229009, 'gps_lng' => 11.149987, 'source_url' => 'https://www.openstreetmap.org/node/11472652734'],
            ['name' => 'Pharmacie La Destinée', 'type' => 'pharmacy', 'region' => 'Sud', 'city' => 'Ebolowa', 'gps_lat' => 2.914639, 'gps_lng' => 11.1667841, 'phone' => '+237 2 43 00 35 40', 'source_url' => 'https://www.openstreetmap.org/node/11465403716'],
            ['name' => 'Pharmacie Samba', 'type' => 'pharmacy', 'region' => 'Sud', 'city' => 'Ebolowa', 'gps_lat' => 2.910287, 'gps_lng' => 11.1488866, 'phone' => '+237 2 22 28 46 34', 'source_url' => 'https://www.openstreetmap.org/way/1290844980'],
            ['name' => 'Pharmacie de la Mvila', 'type' => 'pharmacy', 'region' => 'Sud', 'city' => 'Ebolowa', 'gps_lat' => 2.9190833, 'gps_lng' => 11.1516224, 'source_url' => 'https://www.openstreetmap.org/node/4450608871'],
            ['name' => 'Pharmacie de la Renaissance', 'type' => 'pharmacy', 'region' => 'Sud', 'city' => 'Ebolowa', 'gps_lat' => 2.923978, 'gps_lng' => 11.1549474, 'source_url' => 'https://www.openstreetmap.org/way/448025225'],
            ['name' => 'Pharmacie du Bercail', 'type' => 'pharmacy', 'region' => 'Sud', 'city' => 'Ebolowa', 'gps_lat' => 2.9208897, 'gps_lng' => 11.1505664, 'source_url' => 'https://www.openstreetmap.org/way/448026252'],
            ['name' => 'Laboratoire bethanie ocean', 'type' => 'laboratory', 'region' => 'Sud', 'city' => 'Kribi', 'gps_lat' => 2.9533215, 'gps_lng' => 9.9252312, 'source_url' => 'https://www.openstreetmap.org/node/8684965574'],
            ['name' => 'Laboratoire de biologie medicale de l\'ocean labocean', 'type' => 'laboratory', 'region' => 'Sud', 'city' => 'Kribi', 'gps_lat' => 2.9557603, 'gps_lng' => 9.9194603, 'phone' => '679487948', 'source_url' => 'https://www.openstreetmap.org/node/8684965575'],
            ['name' => 'Pharmacie De La Gloire', 'type' => 'pharmacy', 'region' => 'Sud', 'city' => 'Kribi', 'gps_lat' => 2.9465013, 'gps_lng' => 9.9132842, 'source_url' => 'https://www.openstreetmap.org/node/7934551242'],
            ['name' => 'Pharmacie de l\'atlantique', 'type' => 'pharmacy', 'region' => 'Sud', 'city' => 'Kribi', 'gps_lat' => 2.940137, 'gps_lng' => 9.9136025, 'source_url' => 'https://www.openstreetmap.org/node/7934551241'],
            ['name' => 'Grosmelf Lab', 'type' => 'laboratory', 'region' => 'Sud-Ouest', 'city' => 'Buéa', 'gps_lat' => 4.1548001, 'gps_lng' => 9.2896447, 'source_url' => 'https://www.openstreetmap.org/node/3684296940'],
            ['name' => 'Amazing Pharmacy', 'type' => 'pharmacy', 'region' => 'Sud-Ouest', 'city' => 'Buéa', 'gps_lat' => 4.1562157, 'gps_lng' => 9.2881471, 'source_url' => 'https://www.openstreetmap.org/node/3684296931'],
            ['name' => 'Enamen Pharmacie', 'type' => 'pharmacy', 'region' => 'Sud-Ouest', 'city' => 'Buéa', 'gps_lat' => 4.158921, 'gps_lng' => 9.2825413, 'source_url' => 'https://www.openstreetmap.org/node/7066538011'],
            ['name' => 'Royal Pharmacy', 'type' => 'pharmacy', 'region' => 'Sud-Ouest', 'city' => 'Buéa', 'gps_lat' => 4.1535901, 'gps_lng' => 9.252612, 'source_url' => 'https://www.openstreetmap.org/node/3684296962'],
            ['name' => 'Salvation Pharmacy', 'type' => 'pharmacy', 'region' => 'Sud-Ouest', 'city' => 'Buéa', 'gps_lat' => 4.1544231, 'gps_lng' => 9.2603165, 'source_url' => 'https://www.openstreetmap.org/node/3684296964'],
            ['name' => 'Winner\'s Pharmacy', 'type' => 'pharmacy', 'region' => 'Sud-Ouest', 'city' => 'Buéa', 'gps_lat' => 4.1539871, 'gps_lng' => 9.2574073, 'source_url' => 'https://www.openstreetmap.org/node/3684296970'],
            ['name' => 'Pharmacie Rapido Fiango', 'type' => 'pharmacy', 'region' => 'Sud-Ouest', 'city' => 'Kumba', 'gps_lat' => 4.6390629, 'gps_lng' => 9.448743, 'source_url' => 'https://www.openstreetmap.org/node/10249499611'],
            ['name' => 'Prestige Pharmacy', 'type' => 'pharmacy', 'region' => 'Sud-Ouest', 'city' => 'Kumba', 'gps_lat' => 4.6344068, 'gps_lng' => 9.441044, 'source_url' => 'https://www.openstreetmap.org/node/5671968923'],
            ['name' => 'Wisdom Pharmacy', 'type' => 'pharmacy', 'region' => 'Sud-Ouest', 'city' => 'Kumba', 'gps_lat' => 4.6301602, 'gps_lng' => 9.4425948, 'source_url' => 'https://www.openstreetmap.org/node/5759180822'],
            ['name' => 'Grosmelf médical laboratory', 'type' => 'laboratory', 'region' => 'Sud-Ouest', 'city' => 'Limbe', 'gps_lat' => 4.0269999, 'gps_lng' => 9.1916339, 'source_url' => 'https://www.openstreetmap.org/node/7072565598'],
            ['name' => 'Labogenie', 'type' => 'laboratory', 'region' => 'Sud-Ouest', 'city' => 'Limbe', 'gps_lat' => 4.0049246, 'gps_lng' => 9.2104898, 'source_url' => 'https://www.openstreetmap.org/node/7072565599'],
            ['name' => 'Desteny Pharmacy', 'type' => 'pharmacy', 'region' => 'Sud-Ouest', 'city' => 'Limbe', 'gps_lat' => 4.0217739, 'gps_lng' => 9.209618, 'source_url' => 'https://www.openstreetmap.org/way/371370682'],
            ['name' => 'Fako Atlantic Pharmacy', 'type' => 'pharmacy', 'region' => 'Sud-Ouest', 'city' => 'Limbe', 'gps_lat' => 4.0110907, 'gps_lng' => 9.2076329, 'source_url' => 'https://www.openstreetmap.org/node/4462243439'],
            ['name' => 'Grace standart Pharmacie', 'type' => 'pharmacy', 'region' => 'Sud-Ouest', 'city' => 'Limbe', 'gps_lat' => 4.0116002, 'gps_lng' => 9.2161205, 'source_url' => 'https://www.openstreetmap.org/node/7072565596'],
            ['name' => 'Medecine Shoppe Pharmacy', 'type' => 'pharmacy', 'region' => 'Sud-Ouest', 'city' => 'Limbe', 'gps_lat' => 4.0220445, 'gps_lng' => 9.1968695, 'source_url' => 'https://www.openstreetmap.org/way/445928661'],
            ['name' => 'Prime Pharmacy', 'type' => 'pharmacy', 'region' => 'Sud-Ouest', 'city' => 'Limbe', 'gps_lat' => 4.0150393, 'gps_lng' => 9.2089792, 'source_url' => 'https://www.openstreetmap.org/way/449328710'],
            ['name' => 'Rainbow', 'type' => 'pharmacy', 'region' => 'Sud-Ouest', 'city' => 'Limbe', 'gps_lat' => 4.0126867, 'gps_lng' => 9.2077166, 'source_url' => 'https://www.openstreetmap.org/node/7072565594'],
            ['name' => 'Rainbow Chemist Limited', 'type' => 'pharmacy', 'region' => 'Sud-Ouest', 'city' => 'Limbe', 'gps_lat' => 4.0127722, 'gps_lng' => 9.2078312, 'phone' => '233332425', 'source_url' => 'https://www.openstreetmap.org/way/370010239'],
            ['name' => 'Rapha Pharmacie', 'type' => 'pharmacy', 'region' => 'Sud-Ouest', 'city' => 'Limbe', 'gps_lat' => 4.0147677, 'gps_lng' => 9.2010324, 'source_url' => 'https://www.openstreetmap.org/node/7072565591'],
            ['name' => 'Taf pharmacy', 'type' => 'pharmacy', 'region' => 'Sud-Ouest', 'city' => 'Limbe', 'gps_lat' => 4.0152025, 'gps_lng' => 9.2065219, 'phone' => '233 26 07', 'source_url' => 'https://www.openstreetmap.org/node/4464702478'],
            ['name' => 'Friveralin pharmacy', 'type' => 'pharmacy', 'region' => 'Sud-Ouest', 'city' => 'Mutengene', 'gps_lat' => 4.0902089, 'gps_lng' => 9.3162779, 'source_url' => 'https://www.openstreetmap.org/node/7070021019'],
            ['name' => 'Faith standard pharmacy', 'type' => 'pharmacy', 'region' => 'Sud-Ouest', 'city' => 'Tiko', 'gps_lat' => 4.0699974, 'gps_lng' => 9.3695028, 'source_url' => 'https://www.openstreetmap.org/node/7070021021'],
            ['name' => 'Thé glory pharmacy', 'type' => 'pharmacy', 'region' => 'Sud-Ouest', 'city' => 'Tiko', 'gps_lat' => 4.0840503, 'gps_lng' => 9.3574244, 'source_url' => 'https://www.openstreetmap.org/node/7070021020'],
        ];
    }

    /**
     * MINSANTE/DPML register of licensed private medical-analysis laboratories,
     * version 31 Mars 2026. 80 entries with a currently valid agrément.
     */
    private function dpmlLaboratories(): array
    {
        return [
            // Réf. n°1 du registre DPML
            ['name' => 'Casar Laboratoires', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Adamaoua', 'city' => 'Ngaoundéré', 'phone' => '+237 695101098', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°2 du registre DPML
            ['name' => 'Grace Labo Sarl', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Adamaoua', 'city' => null, 'phone' => '+237 699576021', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°3 du registre DPML
            ['name' => 'Sunshine Labo', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Adamaoua', 'city' => 'Ngaoundéré', 'phone' => '+237 699692669', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°4 du registre DPML
            ['name' => 'Best Medical Polyclinic', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'phone' => '+237 670495264', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°5 du registre DPML
            ['name' => 'Centre de Diagnostic Archange Ariel', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => null, 'phone' => '+237 699561336', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°6 du registre DPML
            ['name' => 'Centre de Diagnostic Médical', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'phone' => '+237 655106006', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°8 du registre DPML
            ['name' => 'Elyben Laboratory', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => null, 'phone' => '+237 697968616', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°9 du registre DPML
            ['name' => 'Excellence Labo Sarl', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => null, 'phone' => '+237 698908683', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°10 du registre DPML
            ['name' => 'GT Labo', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => null, 'phone' => '+237 696929031', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°12 du registre DPML
            ['name' => 'Labo Gel Medical Technoligie', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => null, 'phone' => '+237 670140602', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°15 du registre DPML
            ['name' => 'Labo Owonto', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'phone' => '+237 694219833', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°16 du registre DPML
            ['name' => 'Labo Saint Christophe', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'phone' => '+237 691574832', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°17 du registre DPML
            ['name' => 'Laboratoire Apex', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°18 du registre DPML
            ['name' => 'Laboratoire Bethel', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'phone' => '+237 677242838', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°19 du registre DPML
            ['name' => 'Laboratoire Bio-Diagnostica', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'phone' => '+237 699984599', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°21 du registre DPML
            ['name' => 'Laboratoire Bioserm', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'phone' => '+237 670368962', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°23 du registre DPML
            ['name' => 'Laboratoire Cemrio', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'phone' => '+237 696341164', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°24 du registre DPML
            ['name' => 'Laboratoire Cerimed', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => null, 'phone' => '+237 699974157', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°25 du registre DPML
            ['name' => 'Laboratoire Communautaire de Diagnostic Maria Neme Sarl', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => null, 'phone' => '+237 696770886', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°26 du registre DPML
            ['name' => 'Laboratoire d\'Analyse Médicale Lamat', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'phone' => '+237 699911798', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°28 du registre DPML
            ['name' => 'Laboratoire d\'Analyses Médicales Béthanie Melen', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°29 du registre DPML
            ['name' => 'Laboratoire d\'Analyses Médicales du Dr Manga Sarl', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'phone' => '+237 699762568', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°30 du registre DPML
            ['name' => 'Laboratoire de Galilée', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Mbankomo', 'phone' => '+237 696022851', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°31 du registre DPML
            ['name' => 'Laboratoire de la Polyclinique du Palais', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'phone' => '+237 698009899', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°32 du registre DPML
            ['name' => 'Laboratoire de Nkoldongo', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°34 du registre DPML
            ['name' => 'Laboratoire de Recherche et d\'Expertise Biomédicale (REB Labo)', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°35 du registre DPML
            ['name' => 'Laboratoire Divine Service', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'phone' => '+237 677871606', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°37 du registre DPML
            ['name' => 'Laboratoire du Centre Médical La Cathédrale', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'phone' => '+237 698492020', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°38 du registre DPML
            ['name' => 'Laboratoire du Groupe Médical St-Hilaire', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'phone' => '+237 679050404', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°41 du registre DPML
            ['name' => 'Laboratoire Groupe Duniya-Kamer Labo', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°43 du registre DPML
            ['name' => 'Laboratoire La Grâce', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'phone' => '+237 691388972', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°44 du registre DPML
            ['name' => 'Laboratoire Les Caducée', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => null, 'phone' => '+237 699756261', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°46 du registre DPML
            ['name' => 'Laboratoire New Tech', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°47 du registre DPML
            ['name' => 'Laboratoire Rapha', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'phone' => '+237 656425473', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°48 du registre DPML
            ['name' => 'Laboratoire Santé Plus', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°50 du registre DPML
            ['name' => 'Laboratoire Sion', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => null, 'phone' => '+237 694210205', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°53 du registre DPML
            ['name' => 'PN Holding Laboratoire', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => null, 'phone' => '+237 672616421', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°54 du registre DPML
            ['name' => 'Prima Labo Sarl', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°55 du registre DPML
            ['name' => 'Rapid Biotech Lab', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre', 'city' => null, 'phone' => '+237 694057936', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°56 du registre DPML
            ['name' => 'Bethesda Levant', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Est', 'city' => 'Bertoua', 'phone' => '+237 699937623', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°58 du registre DPML
            ['name' => 'Laboratoire Emmaüs', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Est', 'city' => null, 'phone' => '+237 670892339', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°59 du registre DPML
            ['name' => 'Saint Pasteur Plus', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Est', 'city' => 'Bertoua', 'phone' => '+237 675272976', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°61 du registre DPML
            ['name' => 'Laboratoire du Diamaré', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Extrême-Nord', 'city' => 'Maroua', 'phone' => '+237 698653120', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°62 du registre DPML
            ['name' => 'Laboratoire du Réseau BMTC', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Extrême-Nord', 'city' => 'Maroua', 'phone' => '+237 699757572', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°63 du registre DPML
            ['name' => 'Laboratoire Lamka', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Extrême-Nord', 'city' => 'Kaélé', 'phone' => '+237 694504978', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°64 du registre DPML
            ['name' => 'Mawen Labo', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Extrême-Nord', 'city' => 'Maroua', 'phone' => '+237 657181740', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°65 du registre DPML
            ['name' => 'Aube Labo', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'phone' => '+237 693068184', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°66 du registre DPML
            ['name' => 'Biomedicam', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'phone' => '+237 699003207', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°67 du registre DPML
            ['name' => 'Crystal Labo Sarl', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'phone' => '+237 690462257', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°68 du registre DPML
            ['name' => 'Département Biologique', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°69 du registre DPML
            ['name' => 'Diagmed Labo', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°70 du registre DPML
            ['name' => 'Douala Labo', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°72 du registre DPML
            ['name' => 'Kylaya Labo', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'phone' => '+237 696139851', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°73 du registre DPML
            ['name' => 'Lab Tag', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°77 du registre DPML
            ['name' => 'Laboratoire Concorde', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'phone' => '+237 677157635', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°79 du registre DPML
            ['name' => 'Laboratoire d\'Analyses Biomédicales Moderne MJ', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'phone' => '+237 699424432', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°80 du registre DPML
            ['name' => 'Laboratoire de l\'Espérance', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral', 'city' => null, 'phone' => '+237 690749564', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°81 du registre DPML
            ['name' => 'Laboratoire Drouot', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'phone' => '+237 699092955', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°83 du registre DPML
            ['name' => 'Laboratoire Phanuel', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'phone' => '+237 699982909', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°84 du registre DPML
            ['name' => 'Laboratoire Populaire', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'phone' => '+237 677575660', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°86 du registre DPML
            ['name' => 'Laboratoire Socasavie', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Edéa', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°87 du registre DPML
            ['name' => 'Laboratoire Sainte Thérèse', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°88 du registre DPML
            ['name' => 'Lamedic', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°89 du registre DPML
            ['name' => 'Le Bon Diagnostic', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°90 du registre DPML
            ['name' => 'Liten Labo Sarl', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'phone' => '+237 655592929', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°91 du registre DPML
            ['name' => 'Litto Labo', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°92 du registre DPML
            ['name' => 'Louis Pasteur Labo', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'phone' => '+237 696109471', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°93 du registre DPML
            ['name' => 'Medical Center Bonanjo Sarl', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'phone' => '+237 699839034', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°96 du registre DPML
            ['name' => 'Paleologos', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'phone' => '+237 333429924', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°97 du registre DPML
            ['name' => 'Pathcare Diagnostic', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'phone' => '+237 671965569', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°98 du registre DPML
            ['name' => 'Reine Labo', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral', 'city' => null, 'phone' => '+237 693663199', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°100 du registre DPML
            ['name' => 'Uni Labo', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°102 du registre DPML
            ['name' => 'Labomengong', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Nord', 'city' => 'Ngong', 'phone' => '+237 675471408', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°108 du registre DPML
            ['name' => 'Laboratoire Bethalama', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Ouest', 'city' => 'Bafoussam', 'phone' => '+237 677242838', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°110 du registre DPML
            ['name' => 'Laboratoire des Montagnes', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Ouest', 'city' => 'Bafoussam', 'phone' => '+237 677676952', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°111 du registre DPML
            ['name' => 'Laboratoire Montréal', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Ouest', 'city' => 'Bangangté', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°112 du registre DPML
            ['name' => 'Pilem Laboratoire', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Ouest', 'city' => 'Bafoussam', 'phone' => '+237 670579138', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°114 du registre DPML
            ['name' => 'Laboratoire Siloé', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Sud', 'city' => null, 'phone' => '+237 693498106', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°94 du registre DPML
            ['name' => 'Mediplus', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Sud-Ouest', 'city' => 'Limbe', 'phone' => '+237 674361802', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
            // Réf. n°115 du registre DPML
            ['name' => 'Global Heath System Laboratory Limited', 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Sud-Ouest', 'city' => 'Limbe', 'phone' => '+237 696124683', 'accreditation_level' => 'Laboratoire privé agréé (MINSANTE/DPML)'],
        ];
    }
}
