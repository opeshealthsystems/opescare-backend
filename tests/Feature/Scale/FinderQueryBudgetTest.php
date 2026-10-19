<?php

namespace Tests\Feature\Scale;

use App\Models\BloodAvailability;
use App\Models\CareFacility;
use App\Modules\CareMap\Services\BloodAvailabilitySearchService;
use App\Modules\CareMap\Services\CareMapSearchService;
use App\Modules\CareMap\Services\PharmacyStockSearchService;
use App\Support\Geo\BoundingBox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Query budgets for the public finders: the number of queries must not grow
 * with the number of results (no N+1), and every geo search must carry the
 * index-friendly bounding box BEFORE the Haversine.
 */
class FinderQueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    private const LAT = 4.0511;
    private const LON = 9.7679;

    public function test_directory_search_query_count_is_constant_in_the_number_of_results(): void
    {
        $this->facilities(3);
        // Warm-up: DatabaseHealthMiddleware runs a cached `SELECT 1` probe
        // (RegionHealthService, 30s TTL) on the first request only. It is
        // constant in the result count, so it is primed here rather than
        // counted against whichever measurement happens to go first.
        $this->getJson('/api/v1/care-map/search?query=synth&latitude='.self::LAT.'&longitude='.self::LON)->assertOk();
        $few = $this->countQueries(fn () => $this->getJson('/api/v1/care-map/search?query=synth&latitude='.self::LAT.'&longitude='.self::LON)->assertOk());

        $this->facilities(30);
        $many = $this->countQueries(fn () => $this->getJson('/api/v1/care-map/search?query=synth&latitude='.self::LAT.'&longitude='.self::LON)->assertOk());

        $this->assertSame($few, $many, 'Directory search issues a query per result (N+1).');
        $this->assertLessThanOrEqual(8, $many);
    }

    public function test_directory_geo_search_prefilters_on_a_bounding_box_and_still_applies_the_exact_radius(): void
    {
        $near = $this->facility(self::LAT + 0.01, self::LON);       // ~1 km
        $corner = $this->facility(self::LAT + 0.40, self::LON + 0.40); // inside a 50 km box, ~63 km away
        $far = $this->facility(self::LAT + 3, self::LON);            // ~330 km

        $sql = [];
        DB::listen(function ($q) use (&$sql) { $sql[] = $q->sql; });

        $ids = collect(app(CareMapSearchService::class)->searchNearby([
            'latitude' => self::LAT, 'longitude' => self::LON, 'radius' => 50,
        ]))->pluck('id')->all();

        $this->assertContains($near->id, $ids);
        $this->assertNotContains($corner->id, $ids, 'The box is only a prefilter; the exact radius must still apply.');
        $this->assertNotContains($far->id, $ids);
        $this->assertTrue(collect($sql)->contains(fn ($s) => str_contains($s, '"latitude" between')), 'No bounding-box prefilter.');
    }

    public function test_directory_text_search_is_case_insensitive_and_treats_wildcards_literally(): void
    {
        $this->facility(self::LAT, self::LON, 'Cabinet Dentaire Bonanjo');
        $this->facility(self::LAT, self::LON, 'Clinique 100% Sante');

        $svc = app(CareMapSearchService::class);
        $this->assertCount(1, $svc->searchNearby(['query' => 'dentaire']));
        $this->assertCount(1, $svc->searchNearby(['query' => '100%']));
        $this->assertCount(0, $svc->searchNearby(['query' => '%%%']), 'A bare wildcard must not match everything.');
    }

    public function test_blood_search_query_count_is_constant_and_keeps_listings_without_coordinates(): void
    {
        $this->bloodAt(3);
        $few = $this->countQueries(fn () => app(BloodAvailabilitySearchService::class)->searchBlood('O+', 'whole_blood', self::LAT, self::LON, 50));

        $this->bloodAt(20);
        $noCoords = $this->facility(null, null);
        $this->blood($noCoords);

        $many = 0;
        $results = null;
        $many = $this->countQueries(function () use (&$results) {
            $results = app(BloodAvailabilitySearchService::class)->searchBlood('O+', 'whole_blood', self::LAT, self::LON, 50);
        });

        $this->assertSame($few, $many, 'Blood search issues a query per result (N+1).');
        $this->assertContains($noCoords->id, collect($results)->pluck('id')->all(), 'Listings without coordinates were always shown with a null distance.');
    }

    public function test_medicine_search_query_count_is_bounded(): void
    {
        $count = $this->countQueries(fn () => app(PharmacyStockSearchService::class)->searchMedicine('paracetamol', self::LAT, self::LON, 50));

        $this->assertLessThanOrEqual(6, $count);
    }

    public function test_bounding_box_contains_the_circle(): void
    {
        $box = BoundingBox::around(self::LAT, self::LON, 50);

        // 50 km due north / east must sit inside the box.
        $this->assertGreaterThanOrEqual(self::LAT + 50 / 111.32 - 1e-9, $box->maxLat);
        $this->assertGreaterThan(self::LON + 50 / 111.32, $box->maxLng);
        $this->assertLessThan(self::LAT, $box->minLat);
    }

    // ── helpers ────────────────────────────────────────────────────────────

    private function countQueries(callable $fn): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $fn();
        $n = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $n;
    }

    private function facilities(int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            $f = $this->facility(self::LAT + $i * 0.001, self::LON);
            DB::table('care_facility_services')->insert([
                'id' => (string) Str::uuid(), 'facility_id' => $f->id, 'service_name' => 'Consultation', 'service_category' => 'consultation',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function facility(?float $lat, ?float $lon, string $name = 'Synth Clinic'): CareFacility
    {
        return CareFacility::create([
            'facility_name'  => $name.' '.Str::random(4),
            'facility_type'  => 'hospital',
            'listing_status' => 'active',
            'country_code'   => 'CMR',
            'city'           => 'Douala',
            'address'        => 'Rue 1',
            'latitude'       => $lat,
            'longitude'      => $lon,
            'phone_primary'  => '+237233420000',
        ]);
    }

    private function bloodAt(int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            $this->blood($this->facility(self::LAT + $i * 0.001, self::LON));
        }
    }

    private function blood(CareFacility $f): void
    {
        BloodAvailability::forceCreate([
            'facility_id'         => $f->id,
            'blood_group'         => 'O+',
            'component_type'      => 'whole_blood',
            'availability_status' => 'available',
            'source_system'       => \App\Modules\CareMap\Services\BloodAvailabilityProjector::SOURCE_OPERATIONAL,
            'last_updated_at'     => now(),
        ]);
    }
}
