<?php

namespace Tests\Feature\CareMap;

use App\Enums\PharmacyStockStatus;
use App\Models\CareFacility;
use App\Models\Medicine;
use App\Models\MedicinePharmacyStock;
use App\Models\PharmacyStockAvailability;
use App\Modules\Pharmacy\Services\PharmacyStockReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Where the public medicine search reads from, and what it must refuse to serve.
 *
 * WHY THIS MATTERED
 * -----------------
 * Two things were wrong at once, and they pulled in opposite directions.
 *
 * 1. The public search read `pharmacy_stock_availability`, which held zero
 *    rows in production. Pharmacies and partners write to
 *    `medicine_pharmacy_stocks` — 22,789 rows. A pharmacist could log in,
 *    report exactly what was on the shelf, and no patient would ever see it.
 *    The write path and the read path were pointed at different tables.
 *
 * 2. Of those 22,789 rows, 22,627 carried `source_system = 'demo_seed'` —
 *    invented data from a demo seeder — and nothing anywhere filtered on
 *    `source_system`. So the moment the read path is repointed at the table
 *    that actually has rows in it, the public search starts serving fiction.
 *
 * The second is the dangerous one. A patient told a medicine is in stock on
 * the strength of seeded data travels to a pharmacy that never held it. For
 * someone out of antimalarials or insulin, a wasted journey on a fabricated
 * stock report is a real harm, and it is one this platform would have caused.
 *
 * CONTRACT: stock written the way a pharmacy writes it is findable by the
 * public search, and a row with `source_system = 'demo_seed'` is never
 * returned to a public caller.
 */
class PublicMedicineStockSourceTest extends TestCase
{
    use RefreshDatabase;

    private const LAT = 4.0511;
    private const LON = 9.7679;

    private function pharmacy(string $name): CareFacility
    {
        return CareFacility::create([
            'facility_name'       => $name,
            'facility_type'       => 'pharmacy',
            'listing_status'      => 'active',
            'verification_status' => 'unverified',
            'country_code'        => 'CMR',
            'region'              => 'Littoral',
            'city'                => 'Douala',
            'address'             => 'Rue Joss',
            'latitude'            => self::LAT,
            'longitude'           => self::LON,
            'phone_primary'       => '+237233430000',
        ]);
    }

    private function medicine(): Medicine
    {
        return Medicine::create([
            'name'         => 'Artemether-Lumefantrine 20/120mg Tablet',
            'generic_name' => 'Artemether-Lumefantrine',
            'brand_name'   => 'Coartem',
            'strength'     => '20/120mg',
            'form'         => 'tablet',
            'category'     => 'antimalarial',
            'currency'     => 'XAF',
            'is_active'    => true,
        ]);
    }

    /**
     * Write one stock row the way a demo seeder did — into both the legacy
     * CareMap table and the finder table — so the exclusion is pinned whichever
     * table the public search reads.
     */
    private function seedDemoStock(CareFacility $pharmacy, Medicine $medicine): void
    {
        MedicinePharmacyStock::create([
            'medicine_id'      => $medicine->id,
            'care_facility_id' => $pharmacy->id,
            'stock_status'     => PharmacyStockStatus::InStock->value,
            'packs_available'  => 40,
            'pack_size'        => '24 tablets',
            'unit_price'       => 1500,
            'currency'         => 'XAF',
            'source_system'    => 'demo_seed',
            'last_stocked_at'  => Carbon::now()->subHour(),
            'last_reported_at' => Carbon::now()->subHour(),
        ]);

        PharmacyStockAvailability::create([
            'facility_id'         => $pharmacy->id,
            'medicine_name'       => $medicine->name,
            'generic_name'        => $medicine->generic_name,
            'brand_name'          => $medicine->brand_name,
            'strength'            => $medicine->strength,
            'form'                => $medicine->form,
            'availability_status' => 'reported_available',
            'price'               => 1500,
            'currency'            => 'XAF',
            'source_system'       => 'demo_seed',
            'freshness_status'    => 'fresh',
            'last_updated_at'     => Carbon::now()->subHour(),
        ]);
    }

    private function search(string $term): \Illuminate\Testing\TestResponse
    {
        return $this->getJson(
            '/api/v1/care-map/pharmacies/medicine-search?medicine=' . urlencode($term)
        );
    }

    /**
     * A pharmacist reports stock through the portal write path. A patient must
     * be able to find it. Until the read path and the write path agree on a
     * table, every honest stock report a pharmacy ever filed was invisible.
     */
    public function test_stock_a_pharmacy_reports_through_the_portal_is_findable_by_the_public_search(): void
    {
        $pharmacy = $this->pharmacy('Bonanjo Pharmacy');
        $medicine = $this->medicine();

        // The real write path: this is the only thing a logged-in pharmacy
        // touches when it reports availability.
        app(PharmacyStockReportService::class)->report($pharmacy, $medicine, [
            'stock_status'        => PharmacyStockStatus::InStock,
            'packs_available'     => 24,
            'pack_size'           => '24 tablets',
            'unit_price'          => 1500.0,
            'reservation_enabled' => true,
        ]);

        $this->assertDatabaseHas('medicine_pharmacy_stocks', [
            'care_facility_id' => $pharmacy->id,
            'source_system'    => PharmacyStockReportService::SOURCE_PORTAL,
        ]);

        $this->search('Artemether')
            ->assertOk()
            ->assertJsonFragment(['facility_name' => 'Bonanjo Pharmacy']);
    }

    /**
     * THE IMPORTANT ONE. Seeded data is not a stock report. A public caller
     * must never be handed a demo_seed row, whatever table it sits in.
     */
    public function test_demo_seed_stock_is_never_returned_to_a_public_caller(): void
    {
        $pharmacy = $this->pharmacy('Seeded Demo Pharmacy');
        $this->seedDemoStock($pharmacy, $this->medicine());

        $response = $this->search('Artemether')->assertOk();

        $response->assertJsonMissing(['facility_name' => 'Seeded Demo Pharmacy']);

        $this->assertSame(
            [],
            $response->json('data'),
            'the only stock row in the database is demo_seed, so a patient must be told nothing is available '
            . '— not sent to a pharmacy on the strength of invented data'
        );
    }

    /**
     * The mixed case, which is what production actually looks like: one real
     * report among a pile of seeded ones. Only the real pharmacy is a place a
     * patient should be sent.
     */
    public function test_a_seeded_pharmacy_is_not_listed_alongside_one_that_really_reported(): void
    {
        $medicine = $this->medicine();

        $real = $this->pharmacy('Bonapriso Pharmacy');
        app(PharmacyStockReportService::class)->report($real, $medicine, [
            'stock_status'    => PharmacyStockStatus::InStock,
            'packs_available' => 10,
            'pack_size'       => '24 tablets',
            'unit_price'      => 1500.0,
        ]);

        $seeded = $this->pharmacy('Deido Demo Pharmacy');
        $this->seedDemoStock($seeded, $medicine);

        $response = $this->search('Artemether')->assertOk();

        $response->assertJsonFragment(['facility_name' => 'Bonapriso Pharmacy']);
        $response->assertJsonMissing(['facility_name' => 'Deido Demo Pharmacy']);

        $this->assertCount(
            1,
            $response->json('data'),
            'exactly one pharmacy really reported this medicine; the seeded one must not pad the list'
        );
    }

    /**
     * A guard on the SHAPE of the fix. `source_system` must not be filtered by
     * blacklisting the one literal string 'demo_seed'. PharmacyStockReportService
     * already treats 'seed' as the same thing — seeded fiction that a pharmacy
     * never reported — so a near-variant must be withheld too. A patient cannot
     * tell the difference between two brands of invented stock.
     */
    public function test_a_row_marked_source_system_seed_is_withheld_just_like_demo_seed(): void
    {
        $pharmacy = $this->pharmacy('Legacy Seed Pharmacy');
        $medicine = $this->medicine();

        MedicinePharmacyStock::create([
            'medicine_id'      => $medicine->id,
            'care_facility_id' => $pharmacy->id,
            'stock_status'     => PharmacyStockStatus::InStock->value,
            'packs_available'  => 30,
            'currency'         => 'XAF',
            'source_system'    => 'seed',
            'last_reported_at' => Carbon::now(),
        ]);

        PharmacyStockAvailability::create([
            'facility_id'         => $pharmacy->id,
            'medicine_name'       => $medicine->name,
            'generic_name'        => $medicine->generic_name,
            'availability_status' => 'reported_available',
            'source_system'       => 'seed',
            'freshness_status'    => 'fresh',
            'last_updated_at'     => Carbon::now(),
        ]);

        $this->search('Artemether')
            ->assertOk()
            ->assertJsonMissing(['facility_name' => 'Legacy Seed Pharmacy']);
    }
}
