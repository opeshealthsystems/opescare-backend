<?php

namespace Database\Seeders;

use App\Models\CareFacility;
use App\Services\FacilityCodeGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Pre-populates care_facilities with stub listings from every facility_registry
 * entry (real, MINSANTE/ONPC/WHO DHIS2/OSM-sourced Cameroonian institutions —
 * see CameroonFacilityRegistrySeeder). This is what makes the registry actually
 * reachable by patients: MobileFacilityController/CareMap both read
 * care_facilities, never facility_registry directly, so an entry that never
 * gets stubbed here is permanently invisible in the app. GPS-bearing entries
 * additionally get a pin on the visual CareMap; entries without coordinates
 * (most district-level facilities, pharmacies, and labs) still surface in the
 * searchable Facility Directory and booking flow with latitude/longitude left
 * null — MobileFacilityController does no distance math that requires them.
 *
 * Stub listings have:
 *   - listing_status  = 'active'    (visible in the Facility Directory / CareMap)
 *   - verification_status = 'unverified' (not yet partner-verified)
 *   - country_code    = 'CM'
 *
 * Idempotent: skips registry entries that already have a matching care_facilities
 * listing (matched by facility_name + city + country_code = 'CM'). This table has
 * no is_demo flag and opescare:demo:reset never touches it — these are permanent
 * real-institution records, not demo fixtures.
 *
 * When a facility claims its registry entry and the claim is approved, the
 * corresponding care_facilities stub is activated (verification_status updated).
 */
class CareMapRegistryStubSeeder extends Seeder
{
    public function run(): void
    {
        $registryEntries = DB::table('facility_registry')->get();

        $created  = 0;
        $skipped  = 0;

        foreach ($registryEntries as $entry) {
            // Normalise the city ONCE and use the same value for the lookup and
            // the insert below. 16 registry rows (all laboratories) carry a NULL
            // city, and this check used to compare `city = NULL`, which is never
            // true in PostgreSQL — so the row was judged missing and re-inserted
            // on every run. The insert then stored '' rather than NULL, so the
            // lookup could never match it on a later pass either. Two halves of
            // the same mismatch; a full `db:seed` duplicated those 16 labs each
            // time it ran. Keep the lookup and the write agreeing on one value.
            $city = $entry->city ?? '';

            $alreadyExists = DB::table('care_facilities')
                ->where('facility_name', $entry->name)
                ->where('city', $city)
                ->where('country_code', 'CM')
                ->exists();

            if ($alreadyExists) {
                $skipped++;
                continue;
            }

            DB::table('care_facilities')->insert([
                'id'                  => (string) Str::uuid(),
                'facility_code'       => FacilityCodeGenerator::generate($entry->region ?? 'XX'),
                'facility_name'       => $entry->name,
                'facility_type'       => $entry->type,
                'ownership_type'      => $entry->ownership,
                'country_code'        => 'CM',
                'region'              => $entry->region,
                'city'                => $city,
                // care_facilities.address is NOT NULL, but most registry entries have
                // no street address. Fall back to the city alone rather than
                // synthesising "<city>, Cameroon" — consumers render "address, city",
                // so a synthetic value that already contains the city renders as
                // "Yaoundé, Cameroon, Yaoundé". Storing only what we actually know
                // keeps the record honest and lets the UI dedupe cleanly.
                'address'             => $entry->address ?? ($entry->city ?? ''),
                'latitude'            => $entry->gps_lat,
                'longitude'           => $entry->gps_lng,
                'phone_primary'       => $entry->phone ?? 'N/A',
                'email'               => $entry->email,
                'website'             => $entry->website,
                'listing_status'      => 'active',
                'verification_status' => 'unverified',
                'license_status'      => 'active',
                'integration_status'  => 'none',
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            $created++;
        }

        $total = DB::table('care_facilities')->where('country_code', 'CM')->count();

        $this->command?->info(
            "CareMapRegistryStubSeeder: {$created} stub(s) created, {$skipped} skipped (already exist). " .
            "Total CM care_facilities: {$total}."
        );
    }
}
