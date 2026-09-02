<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Order matters for the facility pipeline:
        //   1. the three registry seeders fill facility_registry with real
        //      institutions (MINSANTE annuaire, ONPC/WHO, OpenStreetMap),
        //   2. CareMapRegistryStubSeeder promotes every registry row into
        //      care_facilities — the table the mobile app and Care Map read,
        //   3. PharmacyCatalogSeeder then attaches medicine stock to real,
        //      GPS-bearing pharmacies that step 2 created,
        //   4. PharmacyFinderCoverageSeeder widens that to national coverage,
        //   5. the two bookable-facility seeders link registry rows to
        //      operational `facilities` and open appointment slots.
        //
        // Steps 4 and 5 are NOT optional extras: without them the medicine
        // finder answers almost every search with nothing (9 of 379 pharmacies
        // carried stock) and every facility dead-ends in the booking wizard
        // (0 bookable on a fresh install, since neither bookable seeder used
        // to be registered anywhere).
        $this->call([
            AccountCategoriesSeeder::class,
            DashboardProfilesSeeder::class,
            RolesSeeder::class,
            NotificationTemplateSeeder::class,
            CameroonFacilityRegistrySeeder::class,
            MinsanteFosaRegistrySeeder::class,
            CameroonOsmPharmacySeeder::class,
            CameroonPharmacyLabRegistrySeeder::class,
            // Backfills GPS/phone onto hospitals the registry seeders above
            // already created, so it must run after all of them. It only ever
            // fills NULLs and never inserts.
            MinsanteCat14BackfillSeeder::class,
            CameroonInsuranceSeeder::class,
            CareMapRegistryStubSeeder::class,
            PharmacyCatalogSeeder::class,
            // Must follow PharmacyCatalogSeeder: that seeder pins 6 curated
            // pharmacies with source_system='seed' and full freshness, and
            // running it afterwards would re-stamp rows this one deliberately
            // ages. 419 medicines (WHO/Cameroon EML) across 306 pharmacies.
            PharmacyFinderCoverageSeeder::class,
            // Bookable directory. Slots first (17 flagship hospitals), then the
            // national pass (121 across all 10 regions); their hospital slot
            // grids are identical so the order is safe either way, but this one
            // matches how the data was built.
            BookableFacilitySlotsSeeder::class,
            BookableFacilityNetworkSeeder::class,
            // Gives geocoded pharmacies an operational tenant. Without it the
            // stock write path is unreachable: PharmacyStockReportService
            // resolves a pharmacy through care_facilities.facility_id, and on a
            // fresh install nothing sets that link — so a pharmacist can log in
            // and still not be able to report stock a patient can see.
            PharmacyOperationalLinkSeeder::class,
        ]);

        // When demo mode is enabled, seed all demo data automatically so that
        // `php artisan db:seed` (or migrate:fresh --seed) is a single-step setup.
        if (config('demo.enabled', (bool) env('OPESCARE_DEMO_MODE', false))) {
            $this->call(DemoDatabaseSeeder::class);
            // Demo vitals for the demo patient only, so the home screen's
            // Health Vitals card renders populated. Guarded on the demo
            // patient existing, so it is a no-op elsewhere.
            $this->call(DemoPatientVitalsSeeder::class);
            // The demo patient's clinical record, in dependency order: the core
            // (blood group, conditions, allergies, visits, immunisations) is the
            // foundation the rest of her story hangs off, then the care pathway
            // (appointments, referral, care plan, surveys) which references both
            // those conditions and the bookable facilities linked above.
            // Without these, Records, Profile and Appointments render empty
            // states and Home reports "0 allergies / 0 conditions".
            $this->call(DemoClinicalCoreSeeder::class);
            $this->call(DemoCarePathwaySeeder::class);

            // LAST in the demo block, deliberately. Several older demo seeders
            // still write against five invented facilities ("Demo Central
            // Hospital" and friends), and those names rendered straight into
            // the patient's own timeline. Rather than rewrite seven seeders
            // that hardcode those ids — and risk foreign-key breakage if the
            // rows vanish underneath them — this runs at the end, repoints
            // every reference onto the real institution each one stood in for,
            // and deletes the invented rows. Self-healing and idempotent: on a
            // second run it reports "nothing to repair".
            $this->call(RealFacilityRepairSeeder::class);
        }
    }
}
