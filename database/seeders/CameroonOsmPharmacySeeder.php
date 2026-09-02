<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Real Cameroonian retail pharmacies with verified GPS coordinates, imported
 * from OpenStreetMap (ODbL) via the Overpass API — nodes tagged
 * amenity=pharmacy within the Douala/Yaoundé corridor, retrieved 2026-08-31.
 *
 * Why this matters: MedicineFinderService does a bounding-box + haversine
 * proximity search and filters on whereNotNull('latitude')/('longitude'), so a
 * pharmacy without coordinates can never surface in the Medicine Finder. The
 * MINSANTE-sourced registry entries carry no GPS, which left the finder with
 * nothing to return. These rows supply real names AND real coordinates.
 *
 * Only nodes that carry a name and fall within 25 km of a known city centre are
 * included; anything else was dropped rather than guessing a location. No name,
 * coordinate, or phone number here is synthesised.
 *
 * Idempotent, and never overwrites a row that a facility has already claimed.
 */
class CameroonOsmPharmacySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pharmacies() as $row) {
            DB::table('facility_registry')->insertOrIgnore(array_merge($row, [
                'id'         => (string) Str::uuid(),
                'source'     => 'openstreetmap_2026',
                'source_url' => 'https://www.openstreetmap.org/',
                'status'     => 'unverified',
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            // Backfill coordinates onto an existing unclaimed row of the same name.
            DB::table('facility_registry')
                ->where('name', $row['name'])
                ->where('region', $row['region'])
                ->where('city', $row['city'])
                ->whereNull('claimed_facility_id')
                ->whereNull('gps_lat')
                ->update([
                    'gps_lat'    => $row['gps_lat'],
                    'gps_lng'    => $row['gps_lng'],
                    'updated_at' => now(),
                ]);
        }

        $this->command?->info(
            'CameroonOsmPharmacySeeder: ' . count($this->pharmacies()) . ' OSM pharmacies processed. Registry total: '
            . DB::table('facility_registry')->count() . '.'
        );
    }

    private function pharmacies(): array
    {
        return [
            ['name' => 'Pharmacie Bell', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0358998, 'gps_lng' => 9.6941275],
            ['name' => 'Pharmacie de l\'Hôpital de District d\'Okola', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Okola', 'gps_lat' => 4.0266935, 'gps_lng' => 11.382813],
            ['name' => 'Pharmacie Joss', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0421376, 'gps_lng' => 9.6870513],
            ['name' => 'Pharmacie Olezoa', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.846497, 'gps_lng' => 11.5148132, 'phone' => '+237 696 56 45 72'],
            ['name' => 'Pharmacie du Centre', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8710132, 'gps_lng' => 11.5179547],
            ['name' => 'Pharmacie de Nkomo', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8416617, 'gps_lng' => 11.550225],
            ['name' => 'Pharmacie Tsinga', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8818777, 'gps_lng' => 11.5027686],
            ['name' => 'Mami Macta', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8712872, 'gps_lng' => 11.5346161],
            ['name' => 'Pharmacy de l\'Université', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8594758, 'gps_lng' => 11.5045728],
            ['name' => 'Pharmacie de la Moisson', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8604354, 'gps_lng' => 11.5211667],
            ['name' => 'Pharmacie Mvog-Atangana Mballa', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8488518, 'gps_lng' => 11.5192943, 'phone' => '22306785'],
            ['name' => 'Pharmacie de l\'Aéroport', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8253653, 'gps_lng' => 11.5167072, 'phone' => '22306689'],
            ['name' => 'Pharmacie Moto George', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.889256, 'gps_lng' => 11.5179934, 'phone' => '+237242192827'],
            ['name' => 'Pharmacie Isis', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8642524, 'gps_lng' => 11.5483223, 'phone' => '22233578'],
            ['name' => 'La Référence de Ngousso', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8943449, 'gps_lng' => 11.5491697],
            ['name' => 'Pharmacie du Stade', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8891163, 'gps_lng' => 11.5452392],
            ['name' => 'Pharmacie Jireh', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.9672671, 'gps_lng' => 11.5936877],
            ['name' => 'Saint Martin', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8647393, 'gps_lng' => 11.5181947],
            ['name' => 'Pharmacie Lyonnaise', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8760709, 'gps_lng' => 11.4970116],
            ['name' => 'Pharmacie Ketchy Madagascar', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8828222, 'gps_lng' => 11.4926597],
            ['name' => 'Pharmacie Biyem-assi Dr Tchachou Joseph', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8372952, 'gps_lng' => 11.4849664],
            ['name' => 'Pharmacie de la Rive', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0678133, 'gps_lng' => 9.7126033],
            ['name' => 'Pharmacie Lamarine', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0634896, 'gps_lng' => 9.7060059],
            ['name' => 'pharmacie de la fidelite', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8722691, 'gps_lng' => 11.5050912],
            ['name' => 'Pharmacie Provinciale', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8795091, 'gps_lng' => 11.5159436],
            ['name' => 'Pharmacie la patience', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0811236, 'gps_lng' => 9.7471699],
            ['name' => 'Pharmacie notre dame des victoire', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0869821, 'gps_lng' => 9.7650562],
            ['name' => 'Pharmacie KD', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0668533, 'gps_lng' => 9.7091293],
            ['name' => 'Deido Pharmacy', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0608221, 'gps_lng' => 9.713034],
            ['name' => 'Pharmacie La Coupole', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0636394, 'gps_lng' => 9.71441],
            ['name' => 'Pharmacie Lilias', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0698331, 'gps_lng' => 9.7261406],
            ['name' => 'Pharmacie de Palmier', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0447129, 'gps_lng' => 9.6866806],
            ['name' => 'Pharmacy de Ndog-passi axe-lourd', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0048605, 'gps_lng' => 9.7549902],
            ['name' => 'People\'s pharmacy PK 8', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.047254, 'gps_lng' => 9.7594862],
            ['name' => 'Pharmacie la Vita', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0361231, 'gps_lng' => 9.7627081],
            ['name' => 'Pharmacie Les Merveilles', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.041466, 'gps_lng' => 9.7453275],
            ['name' => 'Pharmacy la Vita Nyalla', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0337102, 'gps_lng' => 9.7835023],
            ['name' => 'Pharmacy ESSEC', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0532982, 'gps_lng' => 9.7359075],
            ['name' => 'Djakoume Phamacy', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0585048, 'gps_lng' => 9.7278217],
            ['name' => 'Pharmacy Gabriel', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0597963, 'gps_lng' => 9.7248179],
            ['name' => 'Pharmacy Sante pour tous', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0520481, 'gps_lng' => 9.7194109],
            ['name' => 'Pharmacie de Grace', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0725379, 'gps_lng' => 9.6685079],
            ['name' => 'Pharmacie du Boulevard', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0229747, 'gps_lng' => 9.7938086],
            ['name' => 'louxia pharmacie', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0310398, 'gps_lng' => 9.7895344],
            ['name' => 'Pharmacie PK8', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0462689, 'gps_lng' => 9.7536397],
            ['name' => 'Pharmacie de la liberté', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8527373, 'gps_lng' => 11.5799773],
            ['name' => 'Pharmacie de l\'Unité', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8588385, 'gps_lng' => 11.5216897],
            ['name' => 'Pharmacie Ste Madeleine', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8618534, 'gps_lng' => 11.5857939],
            ['name' => 'Pharmacie Makepe', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0848383, 'gps_lng' => 9.7552636],
            ['name' => 'Pharmacie du Centre', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0475907, 'gps_lng' => 9.6941258],
            ['name' => 'Pharmacie Nouvelle', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0483093, 'gps_lng' => 9.6944746],
            ['name' => 'Phamacie de l\'Amour', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0957729, 'gps_lng' => 9.6510038],
            ['name' => 'Pharmacie de Côte', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0404472, 'gps_lng' => 9.6934536],
            ['name' => 'Pharmacie de la Jouvence', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0507694, 'gps_lng' => 9.698209],
            ['name' => 'MINDILI', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8245838, 'gps_lng' => 11.5364405],
            ['name' => 'Pharmacie la Colombe', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8849544, 'gps_lng' => 11.5357759],
            ['name' => 'Africa Pharmacy', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0945104, 'gps_lng' => 9.6474628],
            ['name' => 'Pharmacie Bleue', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.9076768, 'gps_lng' => 11.5422648, 'phone' => '+237 2 22 21 42 10'],
            ['name' => 'Phamacie Bayard', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.9024161, 'gps_lng' => 11.5461689, 'phone' => '+237 2 22 20 31 14'],
            ['name' => 'Pharmacie 2000', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8857174, 'gps_lng' => 11.5079212],
            ['name' => 'Pharmacie Ambre', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Edéa', 'gps_lat' => 3.7984985, 'gps_lng' => 10.1294123],
            ['name' => 'Pharmacie Populaire', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8736658, 'gps_lng' => 11.5400568],
            ['name' => 'Pharmacie EMIA', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8626834, 'gps_lng' => 11.5017314],
            ['name' => 'Pharmacie Mandela', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8641188, 'gps_lng' => 11.4972547],
            ['name' => 'Pharmacie MESSA', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8722314, 'gps_lng' => 11.504311],
            ['name' => 'Pharmacie la Providence', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8733533, 'gps_lng' => 11.5031764],
            ['name' => 'Pharmarcie de la cité des palmiers', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0528334, 'gps_lng' => 9.7616632],
            ['name' => 'Afamba', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Okola', 'gps_lat' => 4.1663908, 'gps_lng' => 11.5351055],
            ['name' => 'Pharmacie Saint Thomas', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Okola', 'gps_lat' => 4.1651556, 'gps_lng' => 11.5342753],
            ['name' => 'Pharmacie de Bonamoussadi', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0868264, 'gps_lng' => 9.7356592],
            ['name' => 'Pharmacie Bethesda', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8323132, 'gps_lng' => 11.4885805],
            ['name' => 'Pharmacie Française', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8636485, 'gps_lng' => 11.519731],
            ['name' => 'Pharmacie de l\'Intendance', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8662978, 'gps_lng' => 11.5203653],
            ['name' => 'Pharmacie du bien-être', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.87467, 'gps_lng' => 11.5241352, 'phone' => '+237 222 20 25 19'],
            ['name' => 'Le Mfoundi', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8712767, 'gps_lng' => 11.5237932],
            ['name' => 'Pharmacie Vallée', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8643429, 'gps_lng' => 11.5241083],
            ['name' => 'La Grande Pharmacie des Lumiéres', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8849407, 'gps_lng' => 11.5185535],
            ['name' => 'Pharmacie du Verset', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8767252, 'gps_lng' => 11.5065654],
            ['name' => 'Pharmacie des Congrès', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8869725, 'gps_lng' => 11.5027104, 'phone' => '+237 696 51 90 42'],
            ['name' => 'Pharmacie Bastos', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8920938, 'gps_lng' => 11.5120273],
            ['name' => 'Pharmacie de Douala', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0458593, 'gps_lng' => 9.6992217],
            ['name' => 'Pharmacie de la trinite', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0543108, 'gps_lng' => 9.700579],
            ['name' => 'Pharmacie Carrefour Z', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0545973, 'gps_lng' => 9.7495663],
            ['name' => 'Sotownek', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8690857, 'gps_lng' => 11.4854108],
            ['name' => 'Pharmacie Saint Uriel', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.831293, 'gps_lng' => 11.5399297],
            ['name' => 'Capha Vet Sarl Pharmacie Vétérinaires', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8864393, 'gps_lng' => 11.5342397],
            ['name' => 'Pharmacie Du Bleu', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.880537, 'gps_lng' => 11.4506189],
            ['name' => 'La Renaissance', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8825423, 'gps_lng' => 11.4679932],
            ['name' => 'Pharmacie d\'Oyom-Abang', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8750506, 'gps_lng' => 11.4754035],
            ['name' => 'La Sante', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8724589, 'gps_lng' => 11.5322239],
            ['name' => 'Pharmacie Montesquieu (espace Conseils)', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8625442, 'gps_lng' => 11.5249993],
            ['name' => 'Dr.iguedo\'s Goko', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8722753, 'gps_lng' => 11.5399355, 'phone' => '(+237) 673673033'],
            ['name' => 'Pharmacie Mvog Ada', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8643785, 'gps_lng' => 11.5277271],
            ['name' => 'Pharmacie De L\'arche', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8663421, 'gps_lng' => 11.5295865],
            ['name' => 'Pharmacie Le Bon Berger', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8813214, 'gps_lng' => 11.5388462],
            ['name' => 'Pharmacie De La Rosée', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.9002316, 'gps_lng' => 11.5568303],
            ['name' => 'Pharmacie de La Chapelle', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8988401, 'gps_lng' => 11.5497167],
            ['name' => 'Pharmacie Le Cygne', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8618149, 'gps_lng' => 11.5303784, 'phone' => '(+237) 222222968'],
            ['name' => 'Complexe Miss Ngou', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8878926, 'gps_lng' => 11.5583002],
            ['name' => 'Pharmacie De La Cité', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8851982, 'gps_lng' => 11.5517932],
            ['name' => 'Médicament Vétérinaire', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8689175, 'gps_lng' => 11.5277825],
            ['name' => 'Pharmacie du Lycée Bilingue', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8785935, 'gps_lng' => 11.5496417],
            ['name' => 'Phamarcie Athera', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8867912, 'gps_lng' => 11.5474852],
            ['name' => 'Pro-pharmacie', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.9071578, 'gps_lng' => 11.5665114],
            ['name' => 'Santé St Victor', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.9128037, 'gps_lng' => 11.5616834],
            ['name' => 'La Médecine Naturelle De Maman Nettoyage', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.924255, 'gps_lng' => 11.56052],
            ['name' => 'Pharmacie du Pont', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0708559, 'gps_lng' => 9.6828074],
            ['name' => 'Pharmacie de Bonambappe', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0738794, 'gps_lng' => 9.6760028, 'phone' => '233391739'],
            ['name' => 'Pharmacie La Référence', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8941583, 'gps_lng' => 11.5490815],
            ['name' => 'Pharmacie la Statoise', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8449852, 'gps_lng' => 11.5278381],
            ['name' => 'Pharmacie de l\'Etoile', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8387131, 'gps_lng' => 11.4816492],
            ['name' => 'Pharmacie Jérusalem', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8403714, 'gps_lng' => 11.4768711, 'phone' => '+237691977668'],
            ['name' => 'Nouveau pharmacie d\'Akwa', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0463672, 'gps_lng' => 9.6983813],
            ['name' => 'Pharmacie Siloë', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8489637, 'gps_lng' => 11.482124],
            ['name' => 'Pharmacie urbaine', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8679438, 'gps_lng' => 11.5192009],
            ['name' => 'Pharmacie de Bonapriso', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0245088, 'gps_lng' => 9.6987122, 'phone' => '693600014'],
            ['name' => 'Parapharmacie des Nations', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.887733, 'gps_lng' => 11.5216344],
            ['name' => 'Olembé Pharmacy', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.9569055, 'gps_lng' => 11.5334315],
            ['name' => 'Pharmacie 3A', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8276107, 'gps_lng' => 11.4707056],
            ['name' => 'Pharmacie de Simbock', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8212364, 'gps_lng' => 11.4718734],
            ['name' => 'Pharmacie d\'Edea', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Edéa', 'gps_lat' => 3.7969696, 'gps_lng' => 10.1307904],
            ['name' => 'Pharmacie Efoulan', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8356646, 'gps_lng' => 11.5068517],
            ['name' => 'Pharmacie Messamendongo', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.7966201, 'gps_lng' => 11.5225284],
            ['name' => 'Pharmacie Élite', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8639878, 'gps_lng' => 11.4893178, 'phone' => '237695114998'],
            ['name' => 'Pharmacie Marijova', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8703088, 'gps_lng' => 11.4816399, 'phone' => '+237222230997'],
            ['name' => 'Pro Pharmacie', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8511459, 'gps_lng' => 11.4971182],
            ['name' => 'Pharmacie du carrefour EMIA', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8626434, 'gps_lng' => 11.5017615],
            ['name' => 'Pharmacie de l université', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8590317, 'gps_lng' => 11.5047207],
            ['name' => 'Pharmacie Saint Bernadette', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8528423, 'gps_lng' => 11.4990238],
            ['name' => 'Grande Cœur Pharmacie', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0669048, 'gps_lng' => 9.7588481],
            ['name' => 'Pharmacie d\'Ahala', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.7946619, 'gps_lng' => 11.4899437],
            ['name' => 'Pharmacie Botanik', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8611662, 'gps_lng' => 11.5729856, 'phone' => '690307216'],
            ['name' => 'Pharmacie Nsam', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8251092, 'gps_lng' => 11.5076691],
            ['name' => 'Pharmacie de la Lekie', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Okola', 'gps_lat' => 4.1671035, 'gps_lng' => 11.5286715],
            ['name' => 'Pharmacie de Yassa', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 3.9996216, 'gps_lng' => 9.8027194],
            ['name' => 'La Concorde', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0839733, 'gps_lng' => 9.738054, 'phone' => '+237652070838'],
            ['name' => 'Pharmacie Royale', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0272689, 'gps_lng' => 9.70398],
            ['name' => 'Pharmacie les Pétales', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8692647, 'gps_lng' => 11.5156414, 'phone' => '+237656556757'],
            ['name' => 'Pharmacie de Sion', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8652662, 'gps_lng' => 11.5173643],
            ['name' => 'Pharmacie des Sept Collines', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8671308, 'gps_lng' => 11.4960491],
            ['name' => 'Pharmacie de Logbaba', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0322434, 'gps_lng' => 9.7667239],
            ['name' => 'Pharmacie de Logpom', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.08279, 'gps_lng' => 9.7705],
            ['name' => 'Pharmacie de Nkozoa', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.9653961, 'gps_lng' => 11.5407427],
            ['name' => 'Pharmacie Sipen', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0326219, 'gps_lng' => 9.7253427],
            ['name' => 'Pharmacie Shell', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0087858, 'gps_lng' => 9.7421991, 'phone' => '68321093'],
            ['name' => 'Pharmacie Get Better', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8524808, 'gps_lng' => 11.480823],
            ['name' => 'Priorité Santé', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8835471, 'gps_lng' => 11.5568189],
            ['name' => 'Pharmacie St Pierre', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0145345, 'gps_lng' => 9.7286746],
            ['name' => 'Pharmacie du 20 mai', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8649676, 'gps_lng' => 11.5169918],
            ['name' => 'Parapharmacie PM Beauty Cmr', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8391301, 'gps_lng' => 11.4867953],
            ['name' => 'Pharmacie De La Charité', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Littoral', 'city' => 'Douala', 'gps_lat' => 4.0423039, 'gps_lng' => 9.7154682],
            ['name' => 'Pharmacie de la Valée', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8665688, 'gps_lng' => 11.5226789],
            ['name' => 'Pharmacie M&M', 'type' => 'pharmacy', 'ownership' => 'private', 'region' => 'Centre', 'city' => 'Yaoundé', 'gps_lat' => 3.8631059, 'gps_lng' => 11.4939699],
        ];
    }
}
