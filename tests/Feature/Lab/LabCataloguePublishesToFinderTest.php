<?php

namespace Tests\Feature\Lab;

use App\Models\CareFacility;
use App\Models\ClinicalCodeDictionary;
use App\Models\Facility;
use App\Models\FacilityCatalogueItem;
use App\Models\FacilityRoleAssignment;
use App\Models\LabTestAvailability;
use App\Models\Role;
use App\Models\User;
use App\Services\Lab\LabCatalogueProjector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The facility service catalogue reaches the public lab finder (audit L-7).
 *
 *   1. ADDING a LOINC test to the catalogue creates ONE availability row on
 *      the public listing, with the whole-franc price and `source_system =
 *      'portal'`.
 *   2. RETIRING it marks the row unavailable. The row is kept, never deleted.
 *   3. An UNLINKED tenant projects nothing, and its catalogue page says so.
 *   4. The PUBLIC SEARCH returns the published test with its price and the
 *      listing's own name/city/coordinates — and NOT a seeded or NULL-source
 *      row, nor a retired one.
 *   5. A second sync is IDEMPOTENT: no duplicate rows.
 *   6. A non-LOINC item (a procedure) is not offered to the lab finder.
 */
class LabCataloguePublishesToFinderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    // ── Fixtures ───────────────────────────────────────────────────────────

    /** A lab manager posted at one laboratory tenant. */
    private function manager(): array
    {
        $role     = Role::where('name', 'lab_manager')->firstOrFail();
        $facility = Facility::factory()->create(['type' => 'laboratory']);

        $user = User::factory()->create([
            'role_id'             => $role->id,
            'primary_facility_id' => $facility->id,
            'status'              => 'active',
        ]);

        FacilityRoleAssignment::create([
            'user_id'     => $user->id,
            'facility_id' => $facility->id,
            'role_id'     => $role->id,
            'is_active'   => true,
            'assigned_at' => now(),
        ]);

        return [$user, $facility];
    }

    private function actingAsManager(User $user, Facility $facility): static
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id]);
    }

    /** The public directory listing linked to a tenant. */
    private function listing(Facility $facility, string $name = 'Laboratoire du Centre'): CareFacility
    {
        return CareFacility::create([
            'facility_id'    => $facility->id,
            'facility_name'  => $name,
            'facility_type'  => 'laboratory',
            'listing_status' => 'active',
            'country_code'   => 'CM',
            'region'         => 'Centre',
            'city'           => 'Yaoundé',
            'address'        => 'Avenue Kennedy',
            'latitude'       => 3.8480,
            'longitude'      => 11.5021,
            'phone_primary'  => '+237600000042',
        ]);
    }

    private function code(string $system = 'LOINC', string $code = '718-7', string $kind = 'term', string $display = 'Hemoglobin [Mass/volume] in Blood'): ClinicalCodeDictionary
    {
        return ClinicalCodeDictionary::create([
            'id'        => (string) Str::uuid(),
            'system'    => $system,
            'code'      => $code,
            'display'   => $display,
            'kind'      => $kind,
            'is_active' => true,
            'source'    => 'test-fixture',
        ]);
    }

    private function search(string $term): array
    {
        return $this->getJson('/api/v1/care-map/labs/test-search?test_name=' . urlencode($term))
            ->assertOk()
            ->json('data');
    }

    // ── 1. Add → published with the price ──────────────────────────────────

    public function test_adding_a_lab_test_to_the_catalogue_publishes_it_with_the_whole_franc_price(): void
    {
        [$manager, $facility] = $this->manager();
        $listing = $this->listing($facility);
        $this->code();

        $this->actingAsManager($manager, $facility)
            ->post('/facility/catalogue', [
                'system'     => 'LOINC',
                'code'       => '718-7',
                'local_name' => 'Hémoglobine',
                'price_xaf'  => 2500,
                'turnaround' => '24h',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('lab_test_availability', [
            'facility_id'         => $listing->id,
            'loinc_code'          => '718-7',
            'test_name'           => 'Hémoglobine',
            'price_xaf'           => 2500,
            'currency'            => 'XAF',
            'turnaround_time'     => '24h',
            'availability_status' => 'available',
            'source_system'       => 'portal',
        ]);

        $row = LabTestAvailability::where('loinc_code', '718-7')->firstOrFail();
        $this->assertSame(2500, $row->price_xaf);
        $this->assertNotNull($row->last_published_at);
        $this->assertNotNull($listing->fresh()->last_availability_update_at);

        // Editing the price re-publishes it.
        $item = FacilityCatalogueItem::where('facility_id', $facility->id)->firstOrFail();
        $this->actingAsManager($manager, $facility)
            ->post('/facility/catalogue/' . $item->id, ['local_name' => 'Hémoglobine', 'price_xaf' => 3000])
            ->assertRedirect();

        $this->assertSame(3000, $row->fresh()->price_xaf);
        $this->assertSame(1, LabTestAvailability::count());
    }

    // ── 2. Retire → unavailable, row kept ──────────────────────────────────

    public function test_retiring_a_test_marks_the_published_row_unavailable_and_keeps_it(): void
    {
        [$manager, $facility] = $this->manager();
        $listing = $this->listing($facility);
        $this->code();

        $this->actingAsManager($manager, $facility)
            ->post('/facility/catalogue', ['system' => 'LOINC', 'code' => '718-7', 'price_xaf' => 2500])
            ->assertRedirect();

        $item = FacilityCatalogueItem::where('facility_id', $facility->id)->firstOrFail();
        $row  = LabTestAvailability::where('facility_id', $listing->id)->where('loinc_code', '718-7')->firstOrFail();

        $this->actingAsManager($manager, $facility)
            ->post('/facility/catalogue/' . $item->id . '/retire')
            ->assertRedirect();

        $this->assertSame(1, LabTestAvailability::count());
        $this->assertSame('unavailable', $row->fresh()->availability_status);
        // The price a patient was quoted stays on the record.
        $this->assertSame(2500, $row->fresh()->price_xaf);

        // ...and the public finder no longer offers it.
        $this->assertSame([], $this->search('718-7'));

        // Offer it again: same row, available again.
        $this->actingAsManager($manager, $facility)
            ->post('/facility/catalogue/' . $item->id . '/restore')
            ->assertRedirect();

        $this->assertSame(1, LabTestAvailability::count());
        $this->assertSame('available', $row->fresh()->availability_status);
        $this->assertCount(1, $this->search('718-7'));
    }

    // ── 3. Unlinked tenant ─────────────────────────────────────────────────

    public function test_an_unlinked_tenant_publishes_nothing_and_its_catalogue_page_says_so(): void
    {
        [$manager, $facility] = $this->manager();
        $this->code();

        // No CareFacility row points at this tenant.
        $this->actingAsManager($manager, $facility)
            ->post('/facility/catalogue', ['system' => 'LOINC', 'code' => '718-7', 'price_xaf' => 2500])
            ->assertRedirect();

        $this->assertDatabaseHas('facility_catalogue_items', ['facility_id' => $facility->id, 'code' => '718-7']);
        $this->assertSame(0, LabTestAvailability::count());

        $this->actingAsManager($manager, $facility)
            ->get('/facility/catalogue')
            ->assertOk()
            ->assertViewHas('unlinked', true)
            ->assertSee(__('catalogue.unlinked_title'));

        // A linked tenant does not get the banner.
        [$other, $otherFacility] = $this->manager();
        $this->listing($otherFacility, 'Laboratoire Relié');

        $this->actingAsManager($other, $otherFacility)
            ->get('/facility/catalogue')
            ->assertOk()
            ->assertViewHas('unlinked', false)
            ->assertDontSee(__('catalogue.unlinked_title'));
    }

    // ── 4. Public search: published rows only ──────────────────────────────

    public function test_the_public_search_returns_the_published_test_with_its_price_and_not_seeded_or_unclaimed_rows(): void
    {
        [$manager, $facility] = $this->manager();
        $listing = $this->listing($facility);
        $this->code();

        $this->actingAsManager($manager, $facility)
            ->post('/facility/catalogue', [
                'system' => 'LOINC', 'code' => '718-7', 'local_name' => 'Hémoglobine', 'price_xaf' => 2500,
            ])
            ->assertRedirect();

        // Two rows the finder must NOT show: a seeded one and an unclaimed one,
        // both on other (active, listed) laboratories, both matching the term.
        $seeded    = $this->listing(Facility::factory()->create(['type' => 'laboratory']), 'Labo Démo');
        $unclaimed = $this->listing(Facility::factory()->create(['type' => 'laboratory']), 'Labo Sans Source');

        LabTestAvailability::create([
            'facility_id' => $seeded->id, 'test_name' => 'Hémoglobine (seed)', 'loinc_code' => '718-7',
            'price_xaf' => 100, 'currency' => 'XAF', 'availability_status' => 'available',
            'freshness_status' => 'fresh', 'source_system' => 'demo_seed', 'last_updated_at' => now(),
        ]);
        LabTestAvailability::create([
            'facility_id' => $unclaimed->id, 'test_name' => 'Hémoglobine (unclaimed)', 'loinc_code' => '718-7',
            'price_xaf' => 100, 'currency' => 'XAF', 'availability_status' => 'available',
            'freshness_status' => 'fresh', 'source_system' => null, 'last_updated_at' => now(),
        ]);

        $this->assertSame(3, LabTestAvailability::count());

        $results = $this->search('hémoglobine');

        $this->assertCount(1, $results);
        $this->assertSame($listing->id, $results[0]['id']);
        $this->assertSame('Laboratoire du Centre', $results[0]['facility_name']);
        $this->assertSame('Yaoundé', $results[0]['city']);
        $this->assertEqualsWithDelta(3.8480, (float) $results[0]['latitude'], 0.0001);
        $this->assertEqualsWithDelta(11.5021, (float) $results[0]['longitude'], 0.0001);
        $this->assertSame(2500, $results[0]['matched_test']['price_xaf']);
        $this->assertSame('XAF', $results[0]['matched_test']['currency']);
        $this->assertSame('718-7', $results[0]['matched_test']['loinc_code']);
        $this->assertSame('portal', $results[0]['matched_test']['source_system']);

        // By LOINC code, and with a distance filter that includes Yaoundé.
        $near = $this->getJson('/api/v1/care-map/labs/test-search?test_name=718-7&latitude=3.85&longitude=11.50&radius=20')
            ->assertOk()->json('data');
        $this->assertCount(1, $near);
        $this->assertSame($listing->id, $near[0]['id']);

        // ...and one that does not (Douala is ~200 km away).
        $far = $this->getJson('/api/v1/care-map/labs/test-search?test_name=718-7&latitude=4.05&longitude=9.77&radius=20')
            ->assertOk()->json('data');
        $this->assertSame([], $far);
    }

    // ── 5. Idempotent ──────────────────────────────────────────────────────

    public function test_a_second_sync_is_idempotent_and_the_backfill_command_runs_every_tenant(): void
    {
        [, $facility] = $this->manager();
        $listing = $this->listing($facility);
        $this->code();
        $this->code('LOINC', '2345-7', 'term', 'Glucose [Mass/volume] in Serum or Plasma');

        FacilityCatalogueItem::create([
            'facility_id' => $facility->id, 'system' => 'LOINC', 'code' => '718-7',
            'price_xaf' => 2500, 'is_active' => true,
        ]);
        FacilityCatalogueItem::create([
            'facility_id' => $facility->id, 'system' => 'LOINC', 'code' => '2345-7',
            'price_xaf' => null, 'is_active' => true,
        ]);

        $projector = app(LabCatalogueProjector::class);

        $this->assertSame(2, $projector->syncListing($facility));
        $this->assertSame(2, $projector->syncListing($facility));
        $this->assertSame(2, LabTestAvailability::where('facility_id', $listing->id)->count());

        // The command reaches the same rows, still without duplicating them.
        $this->artisan('lab:publish-catalogue')->assertSuccessful();
        $this->assertSame(2, LabTestAvailability::count());

        // No price published is NULL, not zero.
        $glucose = LabTestAvailability::where('loinc_code', '2345-7')->firstOrFail();
        $this->assertNull($glucose->price_xaf);
        $this->assertSame('Glucose [Mass/volume] in Serum or Plasma', $glucose->test_name);
    }

    // ── 6. Only lab tests ──────────────────────────────────────────────────

    public function test_a_procedure_on_the_catalogue_is_not_offered_to_the_lab_finder(): void
    {
        [$manager, $facility] = $this->manager();
        $this->listing($facility);
        $this->code('ICHI', 'KDA.AB.AA', 'procedure', 'Caesarean section');

        $this->actingAsManager($manager, $facility)
            ->post('/facility/catalogue', ['system' => 'ICHI', 'code' => 'KDA.AB.AA', 'price_xaf' => 150000])
            ->assertRedirect();

        $this->assertDatabaseHas('facility_catalogue_items', ['facility_id' => $facility->id, 'code' => 'KDA.AB.AA']);
        $this->assertSame(0, LabTestAvailability::count());
    }
}
