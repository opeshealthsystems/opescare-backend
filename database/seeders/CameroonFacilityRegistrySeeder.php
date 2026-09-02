<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds the facility_registry table with real Cameroonian healthcare facilities.
 * Sources: MINSANTE, ONPC, WHO DHIS2 Cameroon, OpenStreetMap health nodes.
 * Idempotent — safe to run multiple times. Claimed rows are never touched.
 * GPS coordinates present for ~31 major facilities to enable CareMap discovery.
 */
class CameroonFacilityRegistrySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->facilities() as $row) {
            $this->insertIfMissing($row);
        }

        $this->command?->info('CameroonFacilityRegistrySeeder: ' .
            DB::table('facility_registry')->count() . ' total registry entries.');
    }

    /**
     * Insert if the row does not already exist (by unique name+region+city index).
     * If the row exists but has no GPS coordinates, backfill them (unclaimed rows only).
     */
    private function insertIfMissing(array $row): void
    {
        DB::table('facility_registry')->insertOrIgnore(array_merge($row, [
            'id'         => (string) Str::uuid(),
            'source'     => 'initial_seed_2026',
            'status'     => 'unverified',
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        // Backfill GPS on existing unclaimed rows when we now have coordinates
        if (isset($row['gps_lat']) && isset($row['gps_lng'])) {
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
    }

    private function facilities(): array
    {
        return array_merge(
            $this->centre(),
            $this->littoral(),
            $this->nordOuest(),
            $this->sudOuest(),
            $this->ouest(),
            $this->adamaoua(),
            $this->nord(),
            $this->extremeNord(),
            $this->est(),
            $this->sud(),
            $this->pharmacies(),
            $this->laboratories(),
            $this->imagingCenters(),
            $this->diagnosticCenters(),
        );
    }

    // ─── CENTRE ──────────────────────────────────────────────────────────────

    private function centre(): array
    {
        return [
            ['name' => 'Hôpital Central de Yaoundé',                         'type' => 'hospital',      'ownership' => 'public',      'region' => 'Centre', 'city' => 'Yaoundé',   'accreditation_level' => 'Hôpital de Référence',             'bed_capacity' => 600, 'gps_lat' => 3.8669,  'gps_lng' => 11.5167],
            ['name' => 'CHU de Yaoundé',                                      'type' => 'hospital',      'ownership' => 'public',      'region' => 'Centre', 'city' => 'Yaoundé',   'accreditation_level' => 'Centre Hospitalier Universitaire', 'bed_capacity' => 400, 'gps_lat' => 3.8800,  'gps_lng' => 11.5193],
            ['name' => 'Hôpital Général de Yaoundé',                          'type' => 'hospital',      'ownership' => 'public',      'region' => 'Centre', 'city' => 'Yaoundé',   'accreditation_level' => 'Hôpital Général',                  'bed_capacity' => 350, 'gps_lat' => 3.8780,  'gps_lng' => 11.5080],
            ['name' => 'Hôpital Gynéco-Obstétrique et Pédiatrique de Yaoundé','type' => 'hospital',      'ownership' => 'public',      'region' => 'Centre', 'city' => 'Yaoundé',   'accreditation_level' => 'Hôpital Spécialisé',                              'gps_lat' => 3.8646,  'gps_lng' => 11.4968],
            ['name' => 'Fondation Chantal Biya',                              'type' => 'clinic',        'ownership' => 'ngo',         'region' => 'Centre', 'city' => 'Yaoundé',   'phone' => '+237 222 20 18 00',                                              'gps_lat' => 3.8817,  'gps_lng' => 11.4894],
            ['name' => 'Hôpital de la CNPS Yaoundé',                          'type' => 'hospital',      'ownership' => 'public',      'region' => 'Centre', 'city' => 'Yaoundé',   'bed_capacity' => 150,                                                       'gps_lat' => 3.8655,  'gps_lng' => 11.5219],
            ['name' => 'Hôpital de District de Yaoundé Centre',               'type' => 'hospital',      'ownership' => 'public',      'region' => 'Centre', 'city' => 'Yaoundé',   'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Yaoundé VI',                   'type' => 'hospital',      'ownership' => 'public',      'region' => 'Centre', 'city' => 'Yaoundé',   'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District d\'Efoulan',                      'type' => 'hospital',      'ownership' => 'public',      'region' => 'Centre', 'city' => 'Yaoundé',   'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Nsimeyong',                    'type' => 'hospital',      'ownership' => 'public',      'region' => 'Centre', 'city' => 'Yaoundé',   'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Nkomo',                        'type' => 'hospital',      'ownership' => 'public',      'region' => 'Centre', 'city' => 'Nkomo',     'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Mfou',                         'type' => 'hospital',      'ownership' => 'public',      'region' => 'Centre', 'city' => 'Mfou',      'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Mbalmayo',                     'type' => 'hospital',      'ownership' => 'public',      'region' => 'Centre', 'city' => 'Mbalmayo',  'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District d\'Obala',                        'type' => 'hospital',      'ownership' => 'public',      'region' => 'Centre', 'city' => 'Obala',     'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Bafia',                        'type' => 'hospital',      'ownership' => 'public',      'region' => 'Centre', 'city' => 'Bafia',     'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Polyclinique Bastos',                                  'type' => 'clinic',        'ownership' => 'private',     'region' => 'Centre', 'city' => 'Yaoundé'],
            ['name' => 'Centre Médical La Cathédrale',                         'type' => 'clinic',        'ownership' => 'faith_based', 'region' => 'Centre', 'city' => 'Yaoundé'],
            ['name' => 'Polyclinique La Croix du Sud',                         'type' => 'clinic',        'ownership' => 'private',     'region' => 'Centre', 'city' => 'Yaoundé'],
            ['name' => 'Centre Médical d\'Arrondissement de Biyem-Assi',       'type' => 'health_center', 'ownership' => 'public',      'region' => 'Centre', 'city' => 'Yaoundé'],
            ['name' => 'Centre Médical d\'Arrondissement d\'Ekounou',          'type' => 'health_center', 'ownership' => 'public',      'region' => 'Centre', 'city' => 'Yaoundé'],
            ['name' => 'Centre Médical d\'Arrondissement de Mendong',          'type' => 'health_center', 'ownership' => 'public',      'region' => 'Centre', 'city' => 'Yaoundé'],
            ['name' => 'Clinique Amour',                                        'type' => 'clinic',        'ownership' => 'private',     'region' => 'Centre', 'city' => 'Yaoundé'],
            ['name' => 'Polyclinique de l\'Université',                         'type' => 'clinic',        'ownership' => 'private',     'region' => 'Centre', 'city' => 'Yaoundé'],
            ['name' => 'Centre Médical de Soa',                                'type' => 'health_center', 'ownership' => 'public',      'region' => 'Centre', 'city' => 'Soa'],
            ['name' => 'Hôpital de District de Ngoumou',                       'type' => 'hospital',      'ownership' => 'public',      'region' => 'Centre', 'city' => 'Ngoumou',   'accreditation_level' => 'Hôpital de District'],
        ];
    }

    // ─── LITTORAL ─────────────────────────────────────────────────────────────

    private function littoral(): array
    {
        return [
            ['name' => 'Hôpital Général de Douala',          'type' => 'hospital',      'ownership' => 'public',      'region' => 'Littoral', 'city' => 'Douala',      'accreditation_level' => 'Hôpital Général',                  'bed_capacity' => 500, 'gps_lat' => 4.0583, 'gps_lng' => 9.7019],
            ['name' => 'Hôpital Laquintinie de Douala',      'type' => 'hospital',      'ownership' => 'public',      'region' => 'Littoral', 'city' => 'Douala',      'accreditation_level' => 'Hôpital Provincial',               'bed_capacity' => 350, 'gps_lat' => 4.0455, 'gps_lng' => 9.7222],
            ['name' => 'CHU de Douala',                      'type' => 'hospital',      'ownership' => 'public',      'region' => 'Littoral', 'city' => 'Douala',      'accreditation_level' => 'Centre Hospitalier Universitaire', 'bed_capacity' => 300, 'gps_lat' => 4.0471, 'gps_lng' => 9.7155],
            ['name' => 'Hôpital de la CNPS Douala',          'type' => 'hospital',      'ownership' => 'public',      'region' => 'Littoral', 'city' => 'Douala',      'bed_capacity' => 200,                                                                'gps_lat' => 4.0592, 'gps_lng' => 9.6978],
            ['name' => 'Hôpital Protestante de Bonabéri',    'type' => 'hospital',      'ownership' => 'faith_based', 'region' => 'Littoral', 'city' => 'Douala',      'bed_capacity' => 150,                                                                'gps_lat' => 4.0741, 'gps_lng' => 9.6612],
            ['name' => 'Hôpital de District de Bonabéri',    'type' => 'hospital',      'ownership' => 'public',      'region' => 'Littoral', 'city' => 'Douala',      'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Douala 5e',   'type' => 'hospital',      'ownership' => 'public',      'region' => 'Littoral', 'city' => 'Douala',      'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Ndog-Passi',  'type' => 'hospital',      'ownership' => 'public',      'region' => 'Littoral', 'city' => 'Douala',      'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Bassa',       'type' => 'hospital',      'ownership' => 'public',      'region' => 'Littoral', 'city' => 'Douala',      'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de New Bell',    'type' => 'hospital',      'ownership' => 'public',      'region' => 'Littoral', 'city' => 'Douala',      'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Loum',        'type' => 'hospital',      'ownership' => 'public',      'region' => 'Littoral', 'city' => 'Loum',        'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District d\'Edéa',        'type' => 'hospital',      'ownership' => 'public',      'region' => 'Littoral', 'city' => 'Edéa',        'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Nkongsamba',  'type' => 'hospital',      'ownership' => 'public',      'region' => 'Littoral', 'city' => 'Nkongsamba',  'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital Sainte-Marie de Nkongsamba', 'type' => 'hospital',      'ownership' => 'faith_based', 'region' => 'Littoral', 'city' => 'Nkongsamba'],
            ['name' => 'Clinique des Spécialités de Douala', 'type' => 'clinic',        'ownership' => 'private',     'region' => 'Littoral', 'city' => 'Douala'],
            ['name' => 'Centre Médical Louis Paul Aujoulat', 'type' => 'clinic',        'ownership' => 'faith_based', 'region' => 'Littoral', 'city' => 'Douala'],
            ['name' => 'Polyclinique les Flamboyants',       'type' => 'clinic',        'ownership' => 'private',     'region' => 'Littoral', 'city' => 'Douala'],
            ['name' => 'Polyclinique de l\'Océan',           'type' => 'clinic',        'ownership' => 'private',     'region' => 'Littoral', 'city' => 'Douala'],
            ['name' => 'Centre Médical de Mbanga',           'type' => 'health_center', 'ownership' => 'public',      'region' => 'Littoral', 'city' => 'Mbanga'],
            ['name' => 'Hôpital de District de Manjo',       'type' => 'hospital',      'ownership' => 'public',      'region' => 'Littoral', 'city' => 'Manjo',       'accreditation_level' => 'Hôpital de District'],
        ];
    }

    // ─── NORD-OUEST ───────────────────────────────────────────────────────────

    private function nordOuest(): array
    {
        return [
            ['name' => 'Hôpital Régional de Bamenda',    'type' => 'hospital', 'ownership' => 'public',      'region' => 'Nord-Ouest', 'city' => 'Bamenda', 'accreditation_level' => 'Hôpital Régional', 'bed_capacity' => 250, 'gps_lat' => 5.9631, 'gps_lng' => 10.1580],
            ['name' => 'Baptist Hospital Bamenda',       'type' => 'hospital', 'ownership' => 'faith_based', 'region' => 'Nord-Ouest', 'city' => 'Bamenda', 'bed_capacity' => 200,                                                                           'gps_lat' => 5.9598, 'gps_lng' => 10.1541],
            ['name' => 'Shisong Catholic Hospital',      'type' => 'hospital', 'ownership' => 'faith_based', 'region' => 'Nord-Ouest', 'city' => 'Kumbo',   'bed_capacity' => 180,                                                                           'gps_lat' => 6.2000, 'gps_lng' => 10.6614],
            ['name' => 'Mbingo Baptist Hospital',        'type' => 'hospital', 'ownership' => 'faith_based', 'region' => 'Nord-Ouest', 'city' => 'Tubah',   'bed_capacity' => 160,                                                                           'gps_lat' => 5.9996, 'gps_lng' => 10.0480],
            ['name' => 'Hôpital de District de Bamenda', 'type' => 'hospital', 'ownership' => 'public',      'region' => 'Nord-Ouest', 'city' => 'Bamenda', 'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Nkwen District Hospital',        'type' => 'hospital', 'ownership' => 'public',      'region' => 'Nord-Ouest', 'city' => 'Bamenda', 'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Kumbo',   'type' => 'hospital', 'ownership' => 'public',      'region' => 'Nord-Ouest', 'city' => 'Kumbo',   'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Wum',     'type' => 'hospital', 'ownership' => 'public',      'region' => 'Nord-Ouest', 'city' => 'Wum',     'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Fundong', 'type' => 'hospital', 'ownership' => 'public',      'region' => 'Nord-Ouest', 'city' => 'Fundong', 'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Ndop',    'type' => 'hospital', 'ownership' => 'public',      'region' => 'Nord-Ouest', 'city' => 'Ndop',    'accreditation_level' => 'Hôpital de District'],
        ];
    }

    // ─── SUD-OUEST ────────────────────────────────────────────────────────────

    private function sudOuest(): array
    {
        return [
            ['name' => 'Hôpital Régional de Buéa',          'type' => 'hospital', 'ownership' => 'public',      'region' => 'Sud-Ouest', 'city' => 'Buéa',      'accreditation_level' => 'Hôpital Régional', 'bed_capacity' => 200, 'gps_lat' => 4.1581, 'gps_lng' => 9.2422],
            ['name' => 'Limbe Regional Hospital',            'type' => 'hospital', 'ownership' => 'public',      'region' => 'Sud-Ouest', 'city' => 'Limbe',     'accreditation_level' => 'Hôpital Régional', 'bed_capacity' => 180, 'gps_lat' => 4.0219, 'gps_lng' => 9.1994],
            ['name' => 'Baptist Hospital Muyuka',            'type' => 'hospital', 'ownership' => 'faith_based', 'region' => 'Sud-Ouest', 'city' => 'Muyuka',    'bed_capacity' => 100],
            ['name' => 'Kumba District Hospital',            'type' => 'hospital', 'ownership' => 'public',      'region' => 'Sud-Ouest', 'city' => 'Kumba',     'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Tiko',        'type' => 'hospital', 'ownership' => 'public',      'region' => 'Sud-Ouest', 'city' => 'Tiko',      'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Mamfe',       'type' => 'hospital', 'ownership' => 'public',      'region' => 'Sud-Ouest', 'city' => 'Mamfe',     'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Muyuka',      'type' => 'hospital', 'ownership' => 'public',      'region' => 'Sud-Ouest', 'city' => 'Muyuka',    'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Mutengene',   'type' => 'hospital', 'ownership' => 'public',      'region' => 'Sud-Ouest', 'city' => 'Mutengene', 'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Buéa',        'type' => 'hospital', 'ownership' => 'public',      'region' => 'Sud-Ouest', 'city' => 'Buéa',      'accreditation_level' => 'Hôpital de District'],
        ];
    }

    // ─── OUEST ───────────────────────────────────────────────────────────────

    private function ouest(): array
    {
        return [
            ['name' => 'Hôpital Régional de Bafoussam',         'type' => 'hospital', 'ownership' => 'public',      'region' => 'Ouest', 'city' => 'Bafoussam',  'accreditation_level' => 'Hôpital Régional',                 'bed_capacity' => 300, 'gps_lat' => 5.4702, 'gps_lng' => 10.4178],
            ['name' => 'CHU de Bafoussam',                      'type' => 'hospital', 'ownership' => 'public',      'region' => 'Ouest', 'city' => 'Bafoussam',  'accreditation_level' => 'Centre Hospitalier Universitaire',               'gps_lat' => 5.4761, 'gps_lng' => 10.4117],
            ['name' => 'Hôpital de District de Bafoussam',      'type' => 'hospital', 'ownership' => 'public',      'region' => 'Ouest', 'city' => 'Bafoussam',  'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital Sainte-Élisabeth de Nkongsamba','type' => 'hospital', 'ownership' => 'faith_based', 'region' => 'Ouest', 'city' => 'Nkongsamba', 'bed_capacity' => 120],
            ['name' => 'Hôpital de District de Mbouda',         'type' => 'hospital', 'ownership' => 'public',      'region' => 'Ouest', 'city' => 'Mbouda',     'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Dschang',        'type' => 'hospital', 'ownership' => 'public',      'region' => 'Ouest', 'city' => 'Dschang',    'accreditation_level' => 'Hôpital de District',                            'gps_lat' => 5.4433, 'gps_lng' => 10.0589],
            ['name' => 'Hôpital de District de Foumbot',        'type' => 'hospital', 'ownership' => 'public',      'region' => 'Ouest', 'city' => 'Foumbot',    'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Bangangté',      'type' => 'hospital', 'ownership' => 'public',      'region' => 'Ouest', 'city' => 'Bangangté',  'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Foumban',        'type' => 'hospital', 'ownership' => 'public',      'region' => 'Ouest', 'city' => 'Foumban',    'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Baham',          'type' => 'hospital', 'ownership' => 'public',      'region' => 'Ouest', 'city' => 'Baham',      'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Tonga',          'type' => 'hospital', 'ownership' => 'public',      'region' => 'Ouest', 'city' => 'Tonga',      'accreditation_level' => 'Hôpital de District'],
        ];
    }

    // ─── ADAMAOUA ─────────────────────────────────────────────────────────────

    private function adamaoua(): array
    {
        return [
            ['name' => 'Hôpital Régional de Ngaoundéré',   'type' => 'hospital', 'ownership' => 'public',      'region' => 'Adamaoua', 'city' => 'Ngaoundéré', 'accreditation_level' => 'Hôpital Régional', 'bed_capacity' => 200, 'gps_lat' => 7.3222, 'gps_lng' => 13.5869],
            ['name' => 'Hôpital Adventiste de Ngaoundéré', 'type' => 'hospital', 'ownership' => 'faith_based', 'region' => 'Adamaoua', 'city' => 'Ngaoundéré', 'bed_capacity' => 100,                                                                          'gps_lat' => 7.3261, 'gps_lng' => 13.5798],
            ['name' => 'Hôpital de District de Ngaoundéré','type' => 'hospital', 'ownership' => 'public',      'region' => 'Adamaoua', 'city' => 'Ngaoundéré', 'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Tibati',    'type' => 'hospital', 'ownership' => 'public',      'region' => 'Adamaoua', 'city' => 'Tibati',     'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Meiganga',  'type' => 'hospital', 'ownership' => 'public',      'region' => 'Adamaoua', 'city' => 'Meiganga',   'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Banyo',     'type' => 'hospital', 'ownership' => 'public',      'region' => 'Adamaoua', 'city' => 'Banyo',      'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Tignère',   'type' => 'hospital', 'ownership' => 'public',      'region' => 'Adamaoua', 'city' => 'Tignère',    'accreditation_level' => 'Hôpital de District'],
        ];
    }

    // ─── NORD ────────────────────────────────────────────────────────────────

    private function nord(): array
    {
        return [
            ['name' => 'Hôpital Régional de Garoua',        'type' => 'hospital', 'ownership' => 'public', 'region' => 'Nord', 'city' => 'Garoua',    'accreditation_level' => 'Hôpital Régional', 'bed_capacity' => 250, 'gps_lat' => 9.3018, 'gps_lng' => 13.3979],
            ['name' => 'Hôpital de District de Garoua',     'type' => 'hospital', 'ownership' => 'public', 'region' => 'Nord', 'city' => 'Garoua',    'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Guider',     'type' => 'hospital', 'ownership' => 'public', 'region' => 'Nord', 'city' => 'Guider',    'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Tcholliré',  'type' => 'hospital', 'ownership' => 'public', 'region' => 'Nord', 'city' => 'Tcholliré', 'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Rey Bouba',  'type' => 'hospital', 'ownership' => 'public', 'region' => 'Nord', 'city' => 'Rey Bouba', 'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Poli',       'type' => 'hospital', 'ownership' => 'public', 'region' => 'Nord', 'city' => 'Poli',      'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Lagdo',      'type' => 'hospital', 'ownership' => 'public', 'region' => 'Nord', 'city' => 'Lagdo',     'accreditation_level' => 'Hôpital de District'],
        ];
    }

    // ─── EXTRÊME-NORD ─────────────────────────────────────────────────────────

    private function extremeNord(): array
    {
        return [
            ['name' => 'Hôpital Régional de Maroua',        'type' => 'hospital', 'ownership' => 'public', 'region' => 'Extrême-Nord', 'city' => 'Maroua',    'accreditation_level' => 'Hôpital Régional', 'bed_capacity' => 220, 'gps_lat' => 10.5918, 'gps_lng' => 14.3197],
            ['name' => 'Hôpital de District de Maroua',     'type' => 'hospital', 'ownership' => 'public', 'region' => 'Extrême-Nord', 'city' => 'Maroua',    'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Kousseri',   'type' => 'hospital', 'ownership' => 'public', 'region' => 'Extrême-Nord', 'city' => 'Kousseri',  'accreditation_level' => 'Hôpital de District',                            'gps_lat' => 12.0754, 'gps_lng' => 15.0297],
            ['name' => 'Hôpital de District de Mokolo',     'type' => 'hospital', 'ownership' => 'public', 'region' => 'Extrême-Nord', 'city' => 'Mokolo',    'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Yagoua',     'type' => 'hospital', 'ownership' => 'public', 'region' => 'Extrême-Nord', 'city' => 'Yagoua',    'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Mora',       'type' => 'hospital', 'ownership' => 'public', 'region' => 'Extrême-Nord', 'city' => 'Mora',      'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Kaélé',      'type' => 'hospital', 'ownership' => 'public', 'region' => 'Extrême-Nord', 'city' => 'Kaélé',     'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Mindif',     'type' => 'hospital', 'ownership' => 'public', 'region' => 'Extrême-Nord', 'city' => 'Mindif',    'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Moutourwa',  'type' => 'hospital', 'ownership' => 'public', 'region' => 'Extrême-Nord', 'city' => 'Moutourwa', 'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Touboro',    'type' => 'hospital', 'ownership' => 'public', 'region' => 'Extrême-Nord', 'city' => 'Touboro',   'accreditation_level' => 'Hôpital de District'],
        ];
    }

    // ─── EST ──────────────────────────────────────────────────────────────────

    private function est(): array
    {
        return [
            ['name' => 'Hôpital Régional de Bertoua',        'type' => 'hospital', 'ownership' => 'public', 'region' => 'Est', 'city' => 'Bertoua',     'accreditation_level' => 'Hôpital Régional', 'bed_capacity' => 180, 'gps_lat' => 4.5785, 'gps_lng' => 13.6847],
            ['name' => 'Hôpital de District de Bertoua',     'type' => 'hospital', 'ownership' => 'public', 'region' => 'Est', 'city' => 'Bertoua',     'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District d\'Abong-Mbang', 'type' => 'hospital', 'ownership' => 'public', 'region' => 'Est', 'city' => 'Abong-Mbang', 'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Batouri',     'type' => 'hospital', 'ownership' => 'public', 'region' => 'Est', 'city' => 'Batouri',     'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Yokadouma',   'type' => 'hospital', 'ownership' => 'public', 'region' => 'Est', 'city' => 'Yokadouma',   'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Doumé',       'type' => 'hospital', 'ownership' => 'public', 'region' => 'Est', 'city' => 'Doumé',       'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Ngoura',      'type' => 'hospital', 'ownership' => 'public', 'region' => 'Est', 'city' => 'Ngoura',      'accreditation_level' => 'Hôpital de District'],
        ];
    }

    // ─── SUD ──────────────────────────────────────────────────────────────────

    private function sud(): array
    {
        return [
            ['name' => 'Hôpital Régional d\'Ebolowa',        'type' => 'hospital', 'ownership' => 'public', 'region' => 'Sud', 'city' => 'Ebolowa',    'accreditation_level' => 'Hôpital Régional', 'bed_capacity' => 180, 'gps_lat' => 2.8995,  'gps_lng' => 11.1526],
            ['name' => 'Hôpital de District d\'Ebolowa',     'type' => 'hospital', 'ownership' => 'public', 'region' => 'Sud', 'city' => 'Ebolowa',    'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Sangmelima',  'type' => 'hospital', 'ownership' => 'public', 'region' => 'Sud', 'city' => 'Sangmelima', 'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District d\'Ambam',       'type' => 'hospital', 'ownership' => 'public', 'region' => 'Sud', 'city' => 'Ambam',      'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District de Kribi',       'type' => 'hospital', 'ownership' => 'public', 'region' => 'Sud', 'city' => 'Kribi',      'accreditation_level' => 'Hôpital de District',                            'gps_lat' => 2.9399,  'gps_lng' => 9.9060],
            ['name' => 'Hôpital de District de Lolodorf',    'type' => 'hospital', 'ownership' => 'public', 'region' => 'Sud', 'city' => 'Lolodorf',   'accreditation_level' => 'Hôpital de District'],
            ['name' => 'Hôpital de District d\'Akom II',     'type' => 'hospital', 'ownership' => 'public', 'region' => 'Sud', 'city' => 'Akom II',    'accreditation_level' => 'Hôpital de District'],
        ];
    }

    // ─── PHARMACIES ───────────────────────────────────────────────────────────

    private function pharmacies(): array
    {
        return [
            ['name' => 'Pharmacie Centrale de Yaoundé',              'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Centre',       'city' => 'Yaoundé'],
            ['name' => 'Pharmacie du Soleil',                        'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Centre',       'city' => 'Yaoundé'],
            ['name' => 'Pharmacie de la Paix',                       'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Centre',       'city' => 'Yaoundé'],
            ['name' => 'Pharmacie Hippocrate',                       'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Centre',       'city' => 'Yaoundé'],
            ['name' => 'Pharmacie de la Nlongkak',                   'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Centre',       'city' => 'Yaoundé'],
            ['name' => 'Pharmacie Jouvence',                         'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Centre',       'city' => 'Yaoundé'],
            ['name' => 'Pharmacie Saint-Louis',                      'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Centre',       'city' => 'Yaoundé'],
            ['name' => 'Pharmacie de Melen',                         'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Centre',       'city' => 'Yaoundé'],
            ['name' => 'Pharmacie de l\'Université',                  'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Centre',       'city' => 'Yaoundé'],
            ['name' => 'Pharmacie de Bastos',                        'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Centre',       'city' => 'Yaoundé'],
            ['name' => 'Pharmacie de Biyem-Assi',                    'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Centre',       'city' => 'Yaoundé'],
            ['name' => 'Pharmacie de l\'Hôpital Central',             'type' => 'pharmacy', 'ownership' => 'public',      'region' => 'Centre',       'city' => 'Yaoundé'],
            ['name' => 'Pharmacie Mbalmayo',                         'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Centre',       'city' => 'Mbalmayo'],
            ['name' => 'Grande Pharmacie du Wouri',                  'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Littoral',     'city' => 'Douala'],
            ['name' => 'Pharmacie de Bonanjo',                       'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Littoral',     'city' => 'Douala'],
            ['name' => 'Pharmacie de l\'Akwa',                        'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Littoral',     'city' => 'Douala'],
            ['name' => 'Pharmacie de Bali',                          'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Littoral',     'city' => 'Douala'],
            ['name' => 'Pharmacie Nouvelle de Douala',               'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Littoral',     'city' => 'Douala'],
            ['name' => 'Pharmacie de la Cité Douala',                'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Littoral',     'city' => 'Douala'],
            ['name' => 'Pharmacie du Carrefour Douala',              'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Littoral',     'city' => 'Douala'],
            ['name' => 'Pharmacie de l\'Hôpital Général de Douala',   'type' => 'pharmacy', 'ownership' => 'public',      'region' => 'Littoral',     'city' => 'Douala'],
            ['name' => 'Pharmacie de Nkongsamba',                    'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Littoral',     'city' => 'Nkongsamba'],
            ['name' => 'Pharmacie Régionale de Bamenda',             'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Nord-Ouest',   'city' => 'Bamenda'],
            ['name' => 'Pharmacie de la Santé Bamenda',              'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Nord-Ouest',   'city' => 'Bamenda'],
            ['name' => 'Baptist Hospital Pharmacy Bamenda',          'type' => 'pharmacy', 'ownership' => 'faith_based', 'region' => 'Nord-Ouest',   'city' => 'Bamenda'],
            ['name' => 'Pharmacie Régionale de Buéa',                'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Sud-Ouest',    'city' => 'Buéa'],
            ['name' => 'Pharmacie de Limbe',                         'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Sud-Ouest',    'city' => 'Limbe'],
            ['name' => 'Pharmacie du Marché de Limbe',               'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Sud-Ouest',    'city' => 'Limbe'],
            ['name' => 'Pharmacie Régionale de Bafoussam',           'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Ouest',        'city' => 'Bafoussam'],
            ['name' => 'Pharmacie de la Paix Bafoussam',             'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Ouest',        'city' => 'Bafoussam'],
            ['name' => 'Pharmacie de Dschang',                       'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Ouest',        'city' => 'Dschang'],
            ['name' => 'Pharmacie Régionale de Ngaoundéré',          'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Adamaoua',     'city' => 'Ngaoundéré'],
            ['name' => 'Pharmacie Adventiste de Ngaoundéré',         'type' => 'pharmacy', 'ownership' => 'faith_based', 'region' => 'Adamaoua',     'city' => 'Ngaoundéré'],
            ['name' => 'Pharmacie Régionale de Garoua',              'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Nord',         'city' => 'Garoua'],
            ['name' => 'Pharmacie du Marché de Garoua',              'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Nord',         'city' => 'Garoua'],
            ['name' => 'Pharmacie Régionale de Maroua',              'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Extrême-Nord', 'city' => 'Maroua'],
            ['name' => 'Pharmacie de Kousseri',                      'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Extrême-Nord', 'city' => 'Kousseri'],
            ['name' => 'Pharmacie Régionale de Bertoua',             'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Est',          'city' => 'Bertoua'],
            ['name' => 'Pharmacie Régionale d\'Ebolowa',              'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Sud',          'city' => 'Ebolowa'],
            ['name' => 'Pharmacie de Kribi',                         'type' => 'pharmacy', 'ownership' => 'private',     'region' => 'Sud',          'city' => 'Kribi'],
        ];
    }

    // ─── LABORATORIES ─────────────────────────────────────────────────────────

    private function laboratories(): array
    {
        return [
            ['name' => 'Centre Pasteur du Cameroun — Yaoundé',           'type' => 'laboratory', 'ownership' => 'public',  'region' => 'Centre',       'city' => 'Yaoundé',    'accreditation_level' => 'Laboratoire de Référence National', 'website' => 'https://www.pasteur-yaounde.org', 'gps_lat' => 3.8697, 'gps_lng' => 11.5166],
            ['name' => 'Centre Pasteur du Cameroun — Douala',            'type' => 'laboratory', 'ownership' => 'public',  'region' => 'Littoral',     'city' => 'Douala',                                                                                                                              'gps_lat' => 4.0583, 'gps_lng' => 9.7044],
            ['name' => 'Laboratoire National de Santé Publique',         'type' => 'laboratory', 'ownership' => 'public',  'region' => 'Centre',       'city' => 'Yaoundé',    'accreditation_level' => 'Laboratoire National',                                                                         'gps_lat' => 3.8663, 'gps_lng' => 11.5143],
            ['name' => 'Laboratoire Médical de Yaoundé',                 'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre',       'city' => 'Yaoundé'],
            ['name' => 'Laboratoire de la Cité Verte',                   'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre',       'city' => 'Yaoundé'],
            ['name' => 'Laboratoire de Biologie Médicale de Bastos',     'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre',       'city' => 'Yaoundé'],
            ['name' => 'Laboratoire de l\'Hôpital Central',               'type' => 'laboratory', 'ownership' => 'public',  'region' => 'Centre',       'city' => 'Yaoundé'],
            ['name' => 'Centre Médical de Biologie',                     'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre',       'city' => 'Yaoundé'],
            ['name' => 'Biomedical Laboratory Yaoundé',                  'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Centre',       'city' => 'Yaoundé'],
            ['name' => 'Lanacome Laboratory Douala',                     'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral',     'city' => 'Douala'],
            ['name' => 'Laboratoire de Bonanjo',                         'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral',     'city' => 'Douala'],
            ['name' => 'Laboratoire de l\'Hôpital Général de Douala',    'type' => 'laboratory', 'ownership' => 'public',  'region' => 'Littoral',     'city' => 'Douala'],
            ['name' => 'Labo Diagnos Douala',                            'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral',     'city' => 'Douala'],
            ['name' => 'Laboratoire de Bali Douala',                     'type' => 'laboratory', 'ownership' => 'private', 'region' => 'Littoral',     'city' => 'Douala'],
            ['name' => 'Laboratoire de l\'Hôpital Régional de Bamenda',  'type' => 'laboratory', 'ownership' => 'public',  'region' => 'Nord-Ouest',   'city' => 'Bamenda'],
            ['name' => 'Laboratoire de l\'Hôpital Régional de Bafoussam','type' => 'laboratory', 'ownership' => 'public',  'region' => 'Ouest',        'city' => 'Bafoussam'],
            ['name' => 'Laboratoire de l\'Hôpital Régional de Garoua',   'type' => 'laboratory', 'ownership' => 'public',  'region' => 'Nord',         'city' => 'Garoua'],
            ['name' => 'Laboratoire de l\'Hôpital Régional de Maroua',   'type' => 'laboratory', 'ownership' => 'public',  'region' => 'Extrême-Nord', 'city' => 'Maroua'],
            ['name' => 'Laboratoire de l\'Hôpital Régional de Ngaoundéré','type' => 'laboratory', 'ownership' => 'public', 'region' => 'Adamaoua',     'city' => 'Ngaoundéré'],
            ['name' => 'Laboratoire de l\'Hôpital Régional de Bertoua',  'type' => 'laboratory', 'ownership' => 'public',  'region' => 'Est',          'city' => 'Bertoua'],
            ['name' => 'Laboratoire de l\'Hôpital Régional d\'Ebolowa',  'type' => 'laboratory', 'ownership' => 'public',  'region' => 'Sud',          'city' => 'Ebolowa'],
            ['name' => 'Laboratoire de l\'Hôpital Régional de Buéa',     'type' => 'laboratory', 'ownership' => 'public',  'region' => 'Sud-Ouest',    'city' => 'Buéa'],
        ];
    }

    // ─── IMAGING CENTERS ──────────────────────────────────────────────────────

    private function imagingCenters(): array
    {
        return [
            ['name' => 'Centre d\'Imagerie Médicale de Yaoundé',       'type' => 'imaging_center', 'ownership' => 'private', 'region' => 'Centre',       'city' => 'Yaoundé'],
            ['name' => 'Centre de Scanner de Yaoundé',                 'type' => 'imaging_center', 'ownership' => 'private', 'region' => 'Centre',       'city' => 'Yaoundé'],
            ['name' => 'Centre d\'IRM de Yaoundé',                      'type' => 'imaging_center', 'ownership' => 'private', 'region' => 'Centre',       'city' => 'Yaoundé'],
            ['name' => 'Imagerie de l\'Hôpital Central de Yaoundé',     'type' => 'imaging_center', 'ownership' => 'public',  'region' => 'Centre',       'city' => 'Yaoundé'],
            ['name' => 'Centre de Radiologie et d\'Imagerie (CRIMAR)',  'type' => 'imaging_center', 'ownership' => 'private', 'region' => 'Littoral',     'city' => 'Douala'],
            ['name' => 'Centre Scano de Douala',                        'type' => 'imaging_center', 'ownership' => 'private', 'region' => 'Littoral',     'city' => 'Douala'],
            ['name' => 'Imagerie Médicale Sainte-Rita Douala',          'type' => 'imaging_center', 'ownership' => 'private', 'region' => 'Littoral',     'city' => 'Douala'],
            ['name' => 'Imagerie de l\'Hôpital Général de Douala',      'type' => 'imaging_center', 'ownership' => 'public',  'region' => 'Littoral',     'city' => 'Douala'],
            ['name' => 'Centre de Radiologie de Bamenda',               'type' => 'imaging_center', 'ownership' => 'public',  'region' => 'Nord-Ouest',   'city' => 'Bamenda'],
            ['name' => 'Centre d\'Imagerie de Bafoussam',                'type' => 'imaging_center', 'ownership' => 'public',  'region' => 'Ouest',        'city' => 'Bafoussam'],
            ['name' => 'Centre d\'Imagerie de Garoua',                   'type' => 'imaging_center', 'ownership' => 'public',  'region' => 'Nord',         'city' => 'Garoua'],
            ['name' => 'Centre d\'Imagerie de Maroua',                   'type' => 'imaging_center', 'ownership' => 'public',  'region' => 'Extrême-Nord', 'city' => 'Maroua'],
            ['name' => 'Centre d\'Imagerie de Ngaoundéré',               'type' => 'imaging_center', 'ownership' => 'public',  'region' => 'Adamaoua',     'city' => 'Ngaoundéré'],
        ];
    }

    // ─── DIAGNOSTIC CENTERS ───────────────────────────────────────────────────

    private function diagnosticCenters(): array
    {
        return [
            ['name' => 'Centre de Cardiologie de Yaoundé',              'type' => 'diagnostic_center', 'ownership' => 'private', 'region' => 'Centre',    'city' => 'Yaoundé'],
            ['name' => 'Centre Ophtalmologique de Yaoundé',             'type' => 'diagnostic_center', 'ownership' => 'private', 'region' => 'Centre',    'city' => 'Yaoundé'],
            ['name' => 'Centre d\'Endoscopie de Yaoundé',                'type' => 'diagnostic_center', 'ownership' => 'private', 'region' => 'Centre',    'city' => 'Yaoundé'],
            ['name' => 'Centre Dentaire de Yaoundé',                    'type' => 'dental',             'ownership' => 'private', 'region' => 'Centre',    'city' => 'Yaoundé'],
            ['name' => 'Centre ORL de Yaoundé',                         'type' => 'diagnostic_center', 'ownership' => 'private', 'region' => 'Centre',    'city' => 'Yaoundé'],
            ['name' => 'Centre de Diagnostic de la Fondation Chantal Biya','type' => 'diagnostic_center','ownership' => 'ngo',   'region' => 'Centre',    'city' => 'Yaoundé'],
            ['name' => 'Centre Médical de Diagnostic Avancé Douala',    'type' => 'diagnostic_center', 'ownership' => 'private', 'region' => 'Littoral',  'city' => 'Douala'],
            ['name' => 'Centre Ophtalmologique de Douala',              'type' => 'diagnostic_center', 'ownership' => 'private', 'region' => 'Littoral',  'city' => 'Douala'],
            ['name' => 'Centre Cardiologique de Douala',                'type' => 'diagnostic_center', 'ownership' => 'private', 'region' => 'Littoral',  'city' => 'Douala'],
            ['name' => 'Centre Dentaire de Douala',                     'type' => 'dental',             'ownership' => 'private', 'region' => 'Littoral',  'city' => 'Douala'],
            ['name' => 'Centre de Diagnostic Médical de Bafoussam',     'type' => 'diagnostic_center', 'ownership' => 'private', 'region' => 'Ouest',     'city' => 'Bafoussam'],
            ['name' => 'Centre de Diagnostic de Bamenda',               'type' => 'diagnostic_center', 'ownership' => 'private', 'region' => 'Nord-Ouest','city' => 'Bamenda'],
            ['name' => 'Centre de Diagnostic de Ngaoundéré',            'type' => 'diagnostic_center', 'ownership' => 'private', 'region' => 'Adamaoua',  'city' => 'Ngaoundéré'],
        ];
    }
}
