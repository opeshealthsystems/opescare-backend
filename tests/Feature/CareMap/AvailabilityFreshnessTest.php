<?php

namespace Tests\Feature\CareMap;

use App\Models\BloodAvailability;
use App\Models\CareFacility;
use App\Models\Medicine;
use App\Models\MedicinePharmacyStock;
use App\Models\PharmacyStockAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * meta.warning on the public medicine and blood searches.
 *
 * WHY THIS MATTERED
 * -----------------
 * CareMapController::searchMedicine() and searchBlood() both returned a
 * literal `'warning' => 'fresh'` in the response meta. Nothing computed it.
 * It was 'fresh' when the newest report was two years old, and it was 'fresh'
 * when the result set was completely empty.
 *
 * Freshness is the only trust signal these endpoints carry. A clinician
 * searching for O-negative reads "fresh" and drives to a blood bank on the
 * strength of a reading the platform never took; a patient reads "fresh" and
 * travels for a medicine last confirmed on a shelf months ago. Claiming
 * freshness over an empty result set is the worst of the three — there is not
 * even a stale fact behind it.
 *
 * CONTRACT: meta.warning is derived from the newest report timestamp among the
 * rows actually returned, and an empty result set does not claim freshness at
 * all.
 *
 * Fixtures are written to BOTH the legacy `pharmacy_stock_availability` table
 * and the `medicine_pharmacy_stocks` table pharmacies really write to, with
 * matching timestamps, so this file pins freshness regardless of which table
 * the medicine search ends up reading (see PublicMedicineStockSourceTest).
 */
class AvailabilityFreshnessTest extends TestCase
{
    use RefreshDatabase;

    private const LAT = 4.0511;
    private const LON = 9.7679;

    /** Values that honestly say "we have nothing to report on". */
    private const NO_DATA_VALUES = ['no_data', 'none', 'unknown', 'no_results', 'not_reported'];

    private function pharmacy(string $name = 'Bonanjo Pharmacy'): CareFacility
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

    /**
     * One medicine reported at one pharmacy, at a chosen point in time,
     * written into both the legacy and the current stock tables.
     */
    private function reportStock(CareFacility $pharmacy, Carbon $reportedAt, string $freshness): Medicine
    {
        $medicine = Medicine::create([
            'name'          => 'Paracetamol 500mg Tablet',
            'generic_name'  => 'Paracetamol',
            'brand_name'    => 'Doliprane',
            'strength'      => '500mg',
            'form'          => 'tablet',
            'category'      => 'pain_relief',
            'currency'      => 'XAF',
            'is_active'     => true,
        ]);

        MedicinePharmacyStock::create([
            'medicine_id'      => $medicine->id,
            'care_facility_id' => $pharmacy->id,
            'stock_status'     => 'in_stock',
            'packs_available'  => 12,
            'pack_size'        => '10 tablets',
            'unit_price'       => 500,
            'currency'         => 'XAF',
            'source_system'    => 'portal',
            'last_stocked_at'  => $reportedAt,
            'last_reported_at' => $reportedAt,
        ]);

        PharmacyStockAvailability::create([
            'facility_id'         => $pharmacy->id,
            'medicine_name'       => 'Paracetamol 500mg Tablet',
            'generic_name'        => 'Paracetamol',
            'brand_name'          => 'Doliprane',
            'strength'            => '500mg',
            'form'                => 'tablet',
            'availability_status' => 'reported_available',
            'price'               => 500,
            'currency'            => 'XAF',
            'source_system'       => 'portal',
            'freshness_status'    => $freshness,
            'last_updated_at'     => $reportedAt,
        ]);

        return $medicine;
    }

    private function medicineWarning(string $medicine): mixed
    {
        return $this->getJson(
            '/api/v1/care-map/pharmacies/medicine-search?medicine=' . urlencode($medicine)
        )->assertOk()->json('meta.warning');
    }

    private function bloodWarning(string $group): mixed
    {
        return $this->getJson(
            '/api/v1/care-map/blood/search?blood_group=' . urlencode($group)
        )->assertOk()->json('meta.warning');
    }

    // ── Medicine ────────────────────────────────────────────────────────────

    /**
     * Nothing came back, so there is no freshness to report. Saying "fresh"
     * over zero rows is a claim about data that does not exist.
     */
    public function test_medicine_search_does_not_claim_freshness_when_nothing_was_found(): void
    {
        $this->pharmacy();

        $warning = $this->medicineWarning('Amoxicillin');

        $this->assertNotSame(
            'fresh',
            $warning,
            'an empty medicine result set was reported as "fresh" — there is no report to be fresh'
        );
        $this->assertContains(
            $warning,
            self::NO_DATA_VALUES,
            'an empty result set should report no_data, got: ' . var_export($warning, true)
        );
    }

    /**
     * The newest report is ten days old. Whatever vocabulary is chosen, it is
     * not "fresh" — a patient must not travel on a ten-day-old shelf count.
     */
    public function test_medicine_search_does_not_report_a_ten_day_old_stock_report_as_fresh(): void
    {
        $pharmacy = $this->pharmacy();
        $this->reportStock($pharmacy, Carbon::now()->subDays(10), 'stale');

        $warning = $this->medicineWarning('Paracetamol');

        $this->assertNotSame(
            'fresh',
            $warning,
            'a stock report from ten days ago was presented as fresh'
        );
        $this->assertContains(
            $warning,
            ['stale', 'recent'],
            'a ten-day-old report should read as stale, got: ' . var_export($warning, true)
        );
    }

    /**
     * The honest positive case: a report taken minutes ago is fresh.
     */
    public function test_medicine_search_reports_fresh_only_when_a_pharmacy_reported_just_now(): void
    {
        $pharmacy = $this->pharmacy();
        $this->reportStock($pharmacy, Carbon::now()->subMinutes(5), 'fresh');

        $this->assertSame(
            'fresh',
            $this->medicineWarning('Paracetamol'),
            'a report taken five minutes ago should be fresh'
        );
    }

    /**
     * Freshness follows the NEWEST row returned, not the oldest and not a
     * constant: one stale pharmacy must not poison a pharmacy that reported
     * this morning, and one fresh pharmacy must not be reported as stale.
     */
    public function test_medicine_freshness_follows_the_newest_report_in_the_result_set(): void
    {
        $stalePharmacy = $this->pharmacy('Deido Pharmacy');
        $this->reportStock($stalePharmacy, Carbon::now()->subDays(30), 'stale');

        $freshPharmacy = $this->pharmacy('Bonapriso Pharmacy');
        $this->reportStock($freshPharmacy, Carbon::now()->subMinutes(2), 'fresh');

        $this->assertSame(
            'fresh',
            $this->medicineWarning('Paracetamol'),
            'the newest report in the result set is two minutes old, so the result set is fresh'
        );
    }

    // ── Blood ───────────────────────────────────────────────────────────────

    /**
     * The same lie, on the endpoint where it costs the most. A clinician
     * searching for a group nobody holds must not be told the answer is fresh.
     */
    public function test_blood_search_does_not_claim_freshness_when_nothing_was_found(): void
    {
        $warning = $this->bloodWarning('O-');

        $this->assertNotSame(
            'fresh',
            $warning,
            'an empty blood result set was reported as "fresh" — there is no report to be fresh'
        );
        $this->assertContains(
            $warning,
            self::NO_DATA_VALUES,
            'an empty result set should report no_data, got: ' . var_export($warning, true)
        );
    }

    /**
     * Blood availability moves in hours. A three-day-old reading is not fresh
     * and must never be presented as one to somebody arranging a transfusion.
     */
    public function test_blood_search_does_not_report_a_three_day_old_reading_as_fresh(): void
    {
        $bank = CareFacility::create([
            'facility_name'       => 'Douala Blood Bank',
            'facility_type'       => 'blood_bank',
            'listing_status'      => 'active',
            'verification_status' => 'unverified',
            'country_code'        => 'CMR',
            'region'              => 'Littoral',
            'city'                => 'Douala',
            'address'             => 'Avenue Charles de Gaulle',
            'latitude'            => self::LAT,
            'longitude'           => self::LON,
            'phone_primary'       => '+237233440000',
        ]);

        BloodAvailability::create([
            'facility_id'           => $bank->id,
            'blood_group'           => 'O-',
            'component_type'        => 'whole_blood',
            'units_available_range' => '1-5',
            'availability_status'   => 'available',
            'freshness_status'      => 'stale',
            // A real report, like the medicine fixtures above. Blood
            // availability now carries provenance and the finder withholds
            // seeded and unattributed rows
            // (BloodAvailability::scopeReportedByRealSource()), so an
            // unstamped row here would be filtered out and this test would
            // measure the empty-set path instead of the staleness it is about.
            'source_system'         => 'portal',
            'last_updated_at'       => Carbon::now()->subDays(3),
        ]);

        $warning = $this->bloodWarning('O-');

        $this->assertNotSame(
            'fresh',
            $warning,
            'a blood reading from three days ago was presented as fresh'
        );
        $this->assertContains(
            $warning,
            ['stale', 'recent'],
            'a three-day-old blood reading should read as stale, got: ' . var_export($warning, true)
        );
    }
}
