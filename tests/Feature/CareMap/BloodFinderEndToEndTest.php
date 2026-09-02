<?php

namespace Tests\Feature\CareMap;

use App\Models\BloodAvailability;
use App\Models\BloodInventory;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The Blood Finder, from a clerk's keyboard to a patient's search.
 *
 * WHAT WAS BROKEN
 * ---------------
 * GET /api/v1/care-map/blood/search returned [] for ever. Not because the
 * query was wrong — because `blood_availability` had no reachable writer. The
 * only route that fed it, POST /portals/staff/inventory/blood, matched the
 * URI pattern `portals/staff/inventory/*` in the `inventory_ops` freeze and
 * 404'd in production. The finder shipped in V1; the one screen that could
 * give it data did not.
 *
 * That contradicted what config/features.php had always said of the flag:
 * it "does NOT cover the pharmacy/blood FINDERS ... nor the partner stock-sync
 * ingest that feeds them".
 *
 * WHAT THIS FILE PINS
 * -------------------
 *  1. The loop closes: staff record stock -> the public search returns that
 *     facility, with the timestamp the freshness contract reads.
 *  2. The carve-out is surgical: blood is reachable while pharmacy inventory,
 *     supply chain, api/v1/inventory and portals/pharmacy/inventory stay 404.
 *  3. Provenance: a seeded row and an unattributed row are NEVER returned to a
 *     patient. Blood is the search where being wrong is most expensive — a
 *     relative drives past a hospital that has units to reach one that never
 *     did. The same rule the medicine finder applies
 *     (MedicinePharmacyStock::scopeReportedByRealSource()).
 *  4. An empty result says `no_data`, not a freshness claim.
 *  5. The screen has a nav link. A page reachable only by URL is not shipped.
 */
class BloodFinderEndToEndTest extends TestCase
{
    use RefreshDatabase;

    private const LAT = 4.0511;   // Douala
    private const LON = 9.7679;

    private Facility $tenant;
    private CareFacility $listing;

    protected function setUp(): void
    {
        parent::setUp();

        // Production has these frozen. Everything below must hold in exactly
        // that configuration — testing defaults them ON, which would let the
        // carve-out pass vacuously.
        $this->freezeAll();

        $this->tenant = Facility::create([
            'name'   => 'Hopital Laquintinie de Douala',
            'type'   => 'hospital',
            'status' => 'active',
        ]);

        $this->listing = $this->publicListing($this->tenant);
    }

    /** Turn every V1-frozen module off, as production has them. */
    private function freezeAll(): void
    {
        config(['features.flags' => array_fill_keys(
            array_keys(config('features.flags', [])),
            false
        )]);
    }

    private function publicListing(Facility $tenant, string $name = 'Hopital Laquintinie de Douala'): CareFacility
    {
        return CareFacility::create([
            'facility_id'         => $tenant->id,   // the link the projector publishes across
            'facility_name'       => $name,
            'facility_type'       => 'hospital',
            'listing_status'      => 'active',
            'verification_status' => 'verified',
            'country_code'        => 'CMR',
            'region'              => 'Littoral',
            'city'                => 'Douala',
            'address'             => 'Boulevard de la Liberte',
            'latitude'            => self::LAT,
            'longitude'           => self::LON,
            'phone_primary'       => '+237233420000',
            'emergency_contact'   => '+237699000111',
        ]);
    }

    /** A signed-in lab technician at the blood bank. */
    private function bloodBankStaff(): User
    {
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);

        return User::factory()->create([
            'role_id'             => Role::where('name', 'labtech')->value('id'),
            'primary_facility_id' => $this->tenant->id,
            'status'              => 'active',
        ]);
    }

    /** @return array{0:array<int,mixed>,1:array<string,mixed>} data, meta */
    private function publicSearch(string $group, string $component = 'whole_blood'): array
    {
        $response = $this->getJson('/api/v1/care-map/blood/search?' . http_build_query([
            'blood_group'    => $group,
            'component_type' => $component,
        ]))->assertOk();

        return [$response->json('data'), $response->json('meta')];
    }

    // ── 1. The loop closes ───────────────────────────────────────────────────

    public function test_stock_recorded_by_staff_is_returned_by_the_public_blood_search(): void
    {
        // Nothing published yet: the finder must say so, not guess.
        [$before, $beforeMeta] = $this->publicSearch('O-');
        $this->assertSame([], $before);
        $this->assertSame('no_data', $beforeMeta['warning']);

        $staff = $this->bloodBankStaff();

        $this->actingAs($staff)
            ->post('/portals/staff/inventory/blood', [
                'blood_group'     => 'O-',
                'component'       => 'whole_blood',
                'available_units' => 7,
            ])
            ->assertRedirect(route('portals.staff.inventory.blood'));

        // The operational record took the write...
        $this->assertDatabaseHas('blood_inventories', [
            'facility_id'     => $this->tenant->id,
            'blood_group'     => 'O-',
            'component'       => 'whole_blood',
            'available_units' => 7,
        ]);

        // ...and it was published onto the public listing the patient searches.
        [$data, $meta] = $this->publicSearch('O-');

        $this->assertCount(1, $data, 'a staff blood entry did not reach the public Blood Finder');
        $this->assertSame($this->listing->id, $data[0]['id']);
        $this->assertSame('6-20', $data[0]['matched_blood']['units_available_range']);
        $this->assertSame('available', $data[0]['matched_blood']['availability_status']);

        // 4. The freshness contract (2h fresh / 6h recent) reads
        // `last_updated_at`, so the row must carry it.
        $this->assertNotNull($data[0]['matched_blood']['last_updated_at']);
        $this->assertSame('fresh', $meta['warning']);
        $this->assertSame(1, $meta['results_count']);
        $this->assertSame(['fresh' => 2, 'recent' => 6], $meta['freshness_window_hours']);
    }

    public function test_the_published_row_carries_a_real_source(): void
    {
        $staff = $this->bloodBankStaff();

        $this->actingAs($staff)->post('/portals/staff/inventory/blood', [
            'blood_group'     => 'A+',
            'component'       => 'packed_red_cells',
            'available_units' => 3,
        ]);

        // The operational spelling `packed_red_cells` publishes as `red_cells`.
        $row = BloodAvailability::where('facility_id', $this->listing->id)
            ->where('blood_group', 'A+')
            ->where('component_type', 'red_cells')
            ->firstOrFail();

        $this->assertSame('portal', $row->source_system, 'the staff screen must stamp its own provenance');
        $this->assertTrue($row->isReportedByRealSource());
    }

    public function test_the_freshness_meta_ages_with_the_reported_stock(): void
    {
        $staff = $this->bloodBankStaff();

        $this->actingAs($staff)->post('/portals/staff/inventory/blood', [
            'blood_group'     => 'B+',
            'component'       => 'whole_blood',
            'available_units' => 4,
        ]);

        // Age the operational reading and re-publish: blood moves in hours, and
        // a five-hour-old reading is 'recent', not 'fresh'.
        BloodInventory::where('facility_id', $this->tenant->id)
            ->update(['last_stock_update' => Carbon::now()->subHours(5)]);
        app(\App\Modules\CareMap\Services\BloodAvailabilityProjector::class)
            ->projectFacility($this->tenant->id, 'portal');

        [, $meta] = $this->publicSearch('B+');
        $this->assertSame('recent', $meta['warning']);

        BloodInventory::where('facility_id', $this->tenant->id)
            ->update(['last_stock_update' => Carbon::now()->subDays(3)]);
        app(\App\Modules\CareMap\Services\BloodAvailabilityProjector::class)
            ->projectFacility($this->tenant->id, 'portal');

        [, $meta] = $this->publicSearch('B+');
        $this->assertSame('stale', $meta['warning'], 'a three-day-old blood reading was presented as fresher than it is');
    }

    // ── 3. Provenance ────────────────────────────────────────────────────────

    public function test_a_seeded_row_is_never_returned_to_a_patient(): void
    {
        BloodAvailability::create([
            'facility_id'           => $this->listing->id,
            'blood_group'           => 'AB-',
            'component_type'        => 'whole_blood',
            'units_available_range' => '20+',
            'availability_status'   => 'available',
            'freshness_status'      => 'fresh',
            'source_system'         => 'demo_seed',
            'last_updated_at'       => now(),
        ]);

        [$data, $meta] = $this->publicSearch('AB-');

        $this->assertSame([], $data, 'seeded blood stock was offered to a patient as if a bank had reported it');
        $this->assertSame('no_data', $meta['warning']);
    }

    public function test_an_unattributed_row_is_never_returned_to_a_patient(): void
    {
        BloodAvailability::create([
            'facility_id'           => $this->listing->id,
            'blood_group'           => 'AB+',
            'component_type'        => 'whole_blood',
            'units_available_range' => '20+',
            'availability_status'   => 'available',
            'freshness_status'      => 'fresh',
            'source_system'         => null,   // nobody has claimed this row
            'last_updated_at'       => now(),
        ]);

        [$data, $meta] = $this->publicSearch('AB+');

        $this->assertSame([], $data, 'an unattributed blood row was published as if a bank had reported it');
        $this->assertSame('no_data', $meta['warning']);
    }

    public function test_a_real_report_takes_over_a_seeded_row_rather_than_adding_a_second_one(): void
    {
        $seeded = BloodAvailability::create([
            'facility_id'           => $this->listing->id,
            'blood_group'           => 'O+',
            'component_type'        => 'whole_blood',
            'units_available_range' => '20+',
            'availability_status'   => 'available',
            'freshness_status'      => 'fresh',
            'source_system'         => 'demo_seed',
            'last_updated_at'       => now(),
        ]);

        $staff = $this->bloodBankStaff();
        $this->actingAs($staff)->post('/portals/staff/inventory/blood', [
            'blood_group'     => 'O+',
            'component'       => 'whole_blood',
            'available_units' => 2,
        ]);

        $seeded->refresh();
        $this->assertSame('portal', $seeded->source_system);
        $this->assertSame('1-5', $seeded->units_available_range, 'the real number must replace the invented one');
        $this->assertSame(1, BloodAvailability::where('facility_id', $this->listing->id)->count());

        [$data] = $this->publicSearch('O+');
        $this->assertCount(1, $data);
    }

    public function test_unsafe_units_are_never_advertised_even_from_a_real_source(): void
    {
        $staff = $this->bloodBankStaff();
        $this->actingAs($staff)->post('/portals/staff/inventory/blood', [
            'blood_group'     => 'O-',
            'component'       => 'whole_blood',
            'available_units' => 9,
        ]);

        [$data] = $this->publicSearch('O-');
        $this->assertCount(1, $data);

        $item = BloodInventory::where('facility_id', $this->tenant->id)->firstOrFail();
        $this->actingAs($staff)->post("/portals/staff/inventory/blood/{$item->id}/flag", [
            'is_unsafe' => '1',
        ]);

        [$data, $meta] = $this->publicSearch('O-');
        $this->assertSame([], $data, 'a unit flagged unsafe was still advertised to patients');
        $this->assertSame('no_data', $meta['warning']);
    }

    // ── 4. Empty means empty ─────────────────────────────────────────────────

    public function test_an_empty_result_reports_no_data_and_makes_no_freshness_claim(): void
    {
        [$data, $meta] = $this->publicSearch('B-');

        $this->assertSame([], $data);
        $this->assertSame('no_data', $meta['warning']);
        $this->assertNull($meta['last_reported_at']);
        $this->assertNull($meta['oldest_reported_at']);
        $this->assertSame(0, $meta['results_count']);
        $this->assertNotSame('fresh', $meta['warning']);
    }

    // ── 2. The carve-out is surgical ─────────────────────────────────────────

    public function test_the_blood_entry_screen_is_reachable_while_inventory_ops_is_frozen(): void
    {
        $this->assertFalse(\App\Support\Features::enabled('inventory_ops'), 'this test is meaningless unless the flag is off');

        $staff = $this->bloodBankStaff();

        $this->actingAs($staff)->get('/portals/staff/inventory/blood')->assertOk();
        $this->actingAs($staff)->post('/portals/staff/inventory/blood', [
            'blood_group'     => 'O+',
            'component'       => 'whole_blood',
            'available_units' => 1,
        ])->assertRedirect(route('portals.staff.inventory.blood'));
    }

    /**
     * The carve-out must not have leaked. Facility inventory and supply-chain
     * operations are deliberately out of V1 and must still be invisible —
     * 404, never 403, so a frozen module does not advertise itself.
     */
    public function test_pharmacy_inventory_and_supply_chain_are_still_frozen(): void
    {
        $staff = $this->bloodBankStaff();

        $frozenWebPaths = [
            '/portals/staff/inventory/pharmacy',
            '/portals/staff/supply',
            '/portals/staff/supply/stock',
            '/portals/pharmacy/inventory',
        ];

        foreach ($frozenWebPaths as $path) {
            $this->actingAs($staff)->get($path)
                ->assertNotFound("{$path} must stay frozen: the carve-out is for blood only");
        }

        // Writes too — a POST that slipped through would be worse than a GET.
        $this->actingAs($staff)
            ->post('/portals/staff/inventory/pharmacy', ['medicine_name' => 'x'])
            ->assertNotFound();

        // And the facility inventory API, including its blood routes: partners
        // use the unfrozen POST /api/v1/connect/inventory/blood-stock/sync.
        $this->getJson('/api/v1/inventory/blood')->assertNotFound();
        $this->getJson('/api/v1/inventory/pharmacy')->assertNotFound();
    }

    public function test_a_frozen_inventory_path_is_indistinguishable_from_a_route_that_never_existed(): void
    {
        $frozen  = $this->getJson('/api/v1/inventory/blood');
        $missing = $this->getJson('/api/v1/inventory/this-never-existed');

        $this->assertSame($missing->status(), $frozen->status());
        $this->assertSame($missing->json(), $frozen->json());
    }

    public function test_freezing_did_not_delete_anything_the_blood_routes_stay_registered(): void
    {
        $uris = collect(app('router')->getRoutes())->map(fn ($r) => $r->uri())->all();

        foreach ([
            'portals/staff/inventory/blood',
            'portals/staff/inventory/pharmacy',
            'portals/staff/supply',
            'api/v1/inventory/blood',
        ] as $uri) {
            $this->assertContains($uri, $uris, "{$uri} must remain registered — freezing is not deleting.");
        }
    }

    // ── 5. A page nobody can find is not shipped ─────────────────────────────

    public function test_blood_bank_staff_are_offered_a_nav_link_to_the_screen(): void
    {
        $staff = $this->bloodBankStaff();

        $rendered = $this->actingAs($staff)->get('/portals/staff/inventory/blood')
            ->assertOk()
            ->getContent();

        // The lab sidebars carry the link, and it survives the freeze that
        // hides the pharmacy and supply-chain entries beside it.
        foreach (['partials.sidebars.labtech', 'partials.sidebars.lab_manager'] as $sidebar) {
            $html = $this->app['view']->make($sidebar)->render();

            $this->assertStringContainsString('portals/staff/inventory/blood', $html,
                "{$sidebar} offers no way to reach the blood screen");
            $this->assertStringNotContainsString('portals/staff/inventory/pharmacy', $html,
                "{$sidebar} offers a link to a frozen page");
            $this->assertStringNotContainsString('portals/staff/supply', $html,
                "{$sidebar} offers a link to a frozen page");
        }

        // The screen's own sidebar must not offer the frozen neighbours either.
        $this->assertStringNotContainsString('portals/staff/inventory/pharmacy', $rendered);
        $this->assertStringNotContainsString('portals/staff/supply', $rendered);
    }

    public function test_the_screen_says_whether_its_stock_reaches_the_finder(): void
    {
        $staff = $this->bloodBankStaff();

        $this->actingAs($staff)->get('/portals/staff/inventory/blood')
            ->assertOk()
            ->assertSee(__('public.stf_inv_blood_publishes_notice'), false);

        // A facility with no public listing publishes nothing, silently. Say so
        // rather than let a clerk maintain a fridge no patient can see.
        $this->listing->delete();

        $this->actingAs($staff)->get('/portals/staff/inventory/blood')
            ->assertOk()
            ->assertSee(__('public.stf_inv_blood_no_listing_notice'), false);
    }

    // ── Cross-facility ───────────────────────────────────────────────────────

    public function test_a_clerk_publishes_their_own_facility_not_whichever_row_comes_back_first(): void
    {
        // A second tenant that sorts ahead of ours in an unordered scan. The
        // screen used to resolve its facility with Facility::value('id') — the
        // first row Postgres handed back — so a clerk here could publish
        // somebody else's blood bank.
        $other = Facility::create([
            'name'   => 'Aaaa Regional Blood Bank',
            'type'   => 'hospital',
            'status' => 'active',
        ]);
        $otherListing = $this->publicListing($other, 'Aaaa Regional Blood Bank');

        $staff = $this->bloodBankStaff();

        $this->actingAs($staff)->post('/portals/staff/inventory/blood', [
            'blood_group'     => 'O-',
            'component'       => 'whole_blood',
            'available_units' => 6,
        ]);

        $this->assertDatabaseHas('blood_inventories', ['facility_id' => $this->tenant->id]);
        $this->assertDatabaseMissing('blood_inventories', ['facility_id' => $other->id]);

        [$data] = $this->publicSearch('O-');
        $this->assertCount(1, $data);
        $this->assertSame($this->listing->id, $data[0]['id']);
        $this->assertNotSame($otherListing->id, $data[0]['id']);
    }
}
