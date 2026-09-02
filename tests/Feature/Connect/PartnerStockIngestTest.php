<?php

namespace Tests\Feature\Connect;

use App\Models\BloodAvailability;
use App\Models\BloodInventory;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\IntegrationClient;
use App\Models\Medicine;
use App\Models\MedicinePharmacyStock;
use App\Modules\Connect\Services\PartnerStockIngestService;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * POST /api/v1/connect/inventory/pharmacy-stock/sync
 * POST /api/v1/connect/inventory/blood-stock/sync
 *
 * Both endpoints validated their payload, rejected unsafe items, emitted an
 * audit event and answered `{"status":"synced"}` with ZERO model writes. A
 * partner integrating against the published Connect API got a success response
 * and their stock disappeared.
 *
 * These tests pin the whole contract end to end: a sync persists, the PUBLIC
 * finder then returns it, a retry updates instead of duplicating, an
 * unresolvable drug code comes back named as rejected rather than vanishing,
 * expired and unscreened stock is still refused, and a partner's token can only
 * ever write to its own facility.
 */
class PartnerStockIngestTest extends TestCase
{
    use RefreshDatabase;

    private const PHARMACY_URL = '/api/v1/connect/inventory/pharmacy-stock/sync';
    private const BLOOD_URL    = '/api/v1/connect/inventory/blood-stock/sync';

    private Facility $facility;
    private CareFacility $listing;
    private Medicine $medicine;
    private array $headers;
    private string $clientId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility = Facility::factory()->create();
        $this->listing  = $this->listingFor($this->facility, 'Pharmacie Centrale', 'pharmacy');

        $this->medicine = $this->medicine('Ibuprofen 400mg Tablet', 'Ibuprofen', 'M01AE01');

        $this->clientId = 'partner_sync_' . bin2hex(random_bytes(4));

        // These routes sit under auth.bearer, so the test needs a real signed
        // token — and facility_id has to come from its claims, never the body.
        $client = IntegrationClient::factory()->create([
            'client_id'     => $this->clientId,
            'client_secret' => hash('sha256', 'sk_test_' . bin2hex(random_bytes(8))),
            'status'        => 'active',
            'environment'   => 'sandbox',
            'facility_id'   => $this->facility->id,
            'scopes'        => ['inventory:write'],
        ]);

        $token = app(JwtService::class)->issue([
            'sub'         => $client->client_id,
            'client_id'   => $client->client_id,
            'facility_id' => $client->facility_id,
            'environment' => 'sandbox',
            'scopes'      => $client->scopes,
        ]);

        $this->headers = ['Authorization' => 'Bearer ' . $token];
    }

    /*
    |--------------------------------------------------------------------------
    | Pharmacy stock
    |--------------------------------------------------------------------------
    */

    public function test_a_partner_sync_persists_stock_the_public_medicine_search_returns(): void
    {
        $response = $this->postJson(self::PHARMACY_URL, $this->pharmacyPayload(), $this->headers)
            ->assertOk()
            ->assertJsonPath('status', 'synced')
            ->assertJsonPath('accepted_count', 1)
            ->assertJsonPath('updated_count', 0)
            ->assertJsonPath('rejected_count', 0);

        $this->assertDatabaseHas('medicine_pharmacy_stocks', [
            'medicine_id'      => $this->medicine->id,
            'care_facility_id' => $this->listing->id,
            'packs_available'  => 40,
            'stock_status'     => 'in_stock',
        ]);

        // The point of the whole exercise: a patient can now find it.
        $search = $this->getJson('/api/v1/care-map/pharmacies/medicine-search?medicine=Ibuprofen')
            ->assertOk();

        $this->assertCount(1, $search->json('data'));
        $this->assertSame($this->listing->id, $search->json('data.0.id'));
        $this->assertSame(
            $response->json('source_system'),
            $search->json('data.0.matched_stock.source_system'),
        );
    }

    public function test_synced_stock_is_stamped_so_it_passes_the_real_source_scope(): void
    {
        $this->postJson(self::PHARMACY_URL, $this->pharmacyPayload(), $this->headers)->assertOk();

        $stock = MedicinePharmacyStock::firstOrFail();

        // Provenance decides publication: seeded AND unattributed rows are
        // withheld from every public surface, so an unstamped write would
        // persist and still reach nobody.
        $this->assertSame(
            PartnerStockIngestService::SOURCE_PREFIX . ':' . $this->clientId,
            $stock->source_system,
        );
        $this->assertNotContains(
            $stock->source_system,
            MedicinePharmacyStock::SYNTHETIC_SOURCE_SYSTEMS,
            'partner stock must never be stamped as seeded fiction',
        );
        // ...nor as the pharmacy portal, which is a pharmacist's own statement.
        $this->assertNotSame('portal', $stock->source_system);

        $this->assertSame(1, MedicinePharmacyStock::query()->reportedByRealSource()->count());
        $this->assertNotNull($stock->last_reported_at, 'freshness is the finder\'s only trust signal');
    }

    public function test_a_repeat_sync_updates_rather_than_duplicates(): void
    {
        $this->postJson(self::PHARMACY_URL, $this->pharmacyPayload(), $this->headers)
            ->assertOk()
            ->assertJsonPath('accepted_count', 1);

        $this->postJson(self::PHARMACY_URL, $this->pharmacyPayload(['quantity' => 3]), $this->headers)
            ->assertOk()
            ->assertJsonPath('accepted_count', 0)
            ->assertJsonPath('updated_count', 1);

        $rows = MedicinePharmacyStock::query()
            ->where('medicine_id', $this->medicine->id)
            ->where('care_facility_id', $this->listing->id)
            ->get();

        $this->assertCount(1, $rows, 'the upsert key is (medicine_id, care_facility_id)');
        $this->assertSame(3, (int) $rows->first()->packs_available);
        // 3 packs is low, not in stock — the update really re-derived status.
        $this->assertSame('low_stock', $rows->first()->stock_status->value);
    }

    public function test_a_retry_carrying_the_same_idempotency_key_replays_the_stored_response(): void
    {
        $headers = $this->headers + ['Idempotency-Key' => 'sync-' . Str::uuid()];

        $first = $this->postJson(self::PHARMACY_URL, $this->pharmacyPayload(), $headers)->assertOk();

        $second = $this->postJson(self::PHARMACY_URL, $this->pharmacyPayload(), $headers)->assertOk();

        $second->assertHeader('X-Cache-Idempotency', 'HIT');
        $this->assertSame($first->json('correlation_id'), $second->json('correlation_id'));
        $this->assertSame(1, MedicinePharmacyStock::query()->count());
    }

    public function test_reusing_an_idempotency_key_with_a_different_body_is_a_conflict(): void
    {
        $headers = $this->headers + ['Idempotency-Key' => 'sync-' . Str::uuid()];

        $this->postJson(self::PHARMACY_URL, $this->pharmacyPayload(), $headers)->assertOk();

        $this->postJson(self::PHARMACY_URL, $this->pharmacyPayload(['quantity' => 9]), $headers)
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'IDEMPOTENCY_CONFLICT');

        // The conflicting body was not applied.
        $this->assertSame(40, (int) MedicinePharmacyStock::firstOrFail()->packs_available);
    }

    public function test_an_unresolvable_drug_code_is_reported_rejected_not_silently_dropped(): void
    {
        $response = $this->postJson(self::PHARMACY_URL, [
            'facility_reference' => 'HIS-PHARM-01',
            'items'              => [
                ['drug_code' => 'M01AE01', 'quantity' => 40],
                ['drug_code' => 'NOT-A-REAL-CODE', 'quantity' => 12],
            ],
        ], $this->headers)->assertOk();

        $response->assertJsonPath('accepted_count', 1)
            ->assertJsonPath('rejected_count', 1)
            ->assertJsonPath('rejected_items.0.index', 1)
            ->assertJsonPath('rejected_items.0.drug_code', 'NOT-A-REAL-CODE')
            ->assertJsonPath('rejected_items.0.reason', 'unknown_drug_code');

        $this->assertNotEmpty($response->json('rejected_items.0.message'));
        $this->assertSame(1, MedicinePharmacyStock::query()->count());
    }

    public function test_an_ambiguous_atc_code_is_rejected_rather_than_guessed(): void
    {
        // ATC is not unique in this catalogue — 18 codes currently sit on more
        // than one row. Picking one would file a pharmacy's 400mg stock against
        // the 200mg catalogue entry and publish it to patients as fact.
        $this->medicine('Ibuprofen 200mg Tablet', 'Ibuprofen', 'M01AE01');

        $this->postJson(self::PHARMACY_URL, [
            'facility_reference' => 'HIS-PHARM-01',
            'items'              => [['drug_code' => 'M01AE01', 'quantity' => 40]],
        ], $this->headers)
            ->assertStatus(422)
            ->assertJsonPath('status', 'rejected')
            ->assertJsonPath('rejected_items.0.reason', 'ambiguous_drug_code');

        $this->assertSame(0, MedicinePharmacyStock::query()->count());
    }

    public function test_a_catalogue_uuid_resolves_as_a_drug_code(): void
    {
        $this->postJson(self::PHARMACY_URL, [
            'facility_reference' => 'HIS-PHARM-01',
            'items'              => [['drug_code' => $this->medicine->id, 'quantity' => 40]],
        ], $this->headers)->assertOk()->assertJsonPath('accepted_count', 1);

        $this->assertDatabaseHas('medicine_pharmacy_stocks', [
            'medicine_id' => $this->medicine->id,
        ]);
    }

    public function test_a_batch_where_nothing_resolves_is_rejected_and_stores_nothing(): void
    {
        $this->postJson(self::PHARMACY_URL, [
            'facility_reference' => 'HIS-PHARM-01',
            'items'              => [['drug_code' => 'NOPE-1', 'quantity' => 5]],
        ], $this->headers)
            ->assertStatus(422)
            ->assertJsonPath('status', 'rejected')
            ->assertJsonPath('error_code', 'VALIDATION_FAILED');

        $this->assertSame(0, MedicinePharmacyStock::query()->count());
    }

    public function test_an_expired_item_is_still_refused_and_the_whole_batch_stores_nothing(): void
    {
        $this->postJson(self::PHARMACY_URL, [
            'facility_reference' => 'HIS-PHARM-01',
            'items'              => [
                ['drug_code' => 'M01AE01', 'quantity' => 40],
                ['drug_code' => 'M01AE01', 'quantity' => 10, 'expiry_date' => now()->subDay()->toDateString()],
            ],
        ], $this->headers)
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'UNSAFE_STOCK_STATUS')
            ->assertJsonPath('expired_items.0.index', 1);

        // All-or-nothing on purpose: a batch carrying expired stock is not
        // partially trustworthy.
        $this->assertSame(0, MedicinePharmacyStock::query()->count());
    }

    public function test_a_partner_cannot_write_pharmacy_stock_to_another_facility(): void
    {
        $otherTenant  = Facility::factory()->create();
        $otherListing = $this->listingFor($otherTenant, 'Pharmacie Rivale', 'pharmacy');

        // Everything the body can say about identity says "the other pharmacy".
        // facility_id comes from the bearer token's claims and nowhere else.
        $this->postJson(self::PHARMACY_URL, [
            'facility_reference' => (string) $otherListing->id,
            'facility_id'        => (string) $otherTenant->id,
            'care_facility_id'   => (string) $otherListing->id,
            'items'              => [['drug_code' => 'M01AE01', 'quantity' => 40]],
        ], $this->headers)->assertOk()->assertJsonPath('facility_id', $this->facility->id);

        $this->assertDatabaseHas('medicine_pharmacy_stocks', [
            'care_facility_id' => $this->listing->id,
        ]);
        $this->assertDatabaseMissing('medicine_pharmacy_stocks', [
            'care_facility_id' => $otherListing->id,
        ]);
    }

    public function test_a_facility_with_no_public_pharmacy_listing_is_told_so(): void
    {
        // Stock hangs off the public listing. With none, storing would be
        // pointless — and answering "synced" would be the original defect.
        $this->listing->delete();

        $this->postJson(self::PHARMACY_URL, $this->pharmacyPayload(), $this->headers)
            ->assertStatus(422)
            ->assertJsonPath('rejected_items.0.reason', 'pharmacy_listing_unlinked');

        $this->assertSame(0, MedicinePharmacyStock::query()->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Blood stock
    |--------------------------------------------------------------------------
    */

    public function test_a_blood_sync_persists_and_the_public_blood_search_returns_it(): void
    {
        $bank = $this->bloodBankListing();

        $this->postJson(self::BLOOD_URL, $this->bloodPayload(), $this->headers)
            ->assertOk()
            ->assertJsonPath('status', 'synced')
            ->assertJsonPath('accepted_count', 1)
            ->assertJsonPath('stored_rows', 1);

        $this->assertDatabaseHas('blood_inventories', [
            'facility_id'     => $this->facility->id,
            'blood_group'     => 'O+',
            'component'       => 'whole_blood',
            'available_units' => 12,
        ]);

        $search = $this->getJson('/api/v1/care-map/blood/search?blood_group=' . urlencode('O+'))
            ->assertOk();

        $ids = collect($search->json('data'))->pluck('id')->all();
        $this->assertContains($bank->id, $ids, 'partner-synced blood must reach the Blood Finder');
    }

    public function test_synced_blood_is_stamped_so_it_passes_the_real_source_scope(): void
    {
        $this->bloodBankListing();

        $this->postJson(self::BLOOD_URL, $this->bloodPayload(), $this->headers)->assertOk();

        $published = BloodAvailability::query()->reportedByRealSource()->get();

        $this->assertCount(1, $published);
        $this->assertSame(
            PartnerStockIngestService::SOURCE_PREFIX . ':' . $this->clientId,
            $published->first()->source_system,
        );
        $this->assertTrue($published->first()->isReportedByRealSource());
    }

    public function test_a_repeat_blood_sync_updates_rather_than_duplicates(): void
    {
        $this->bloodBankListing();

        $this->postJson(self::BLOOD_URL, $this->bloodPayload(), $this->headers)
            ->assertOk()
            ->assertJsonPath('accepted_count', 1);

        $this->postJson(self::BLOOD_URL, $this->bloodPayload(3), $this->headers)
            ->assertOk()
            ->assertJsonPath('accepted_count', 0)
            ->assertJsonPath('updated_count', 1);

        $rows = BloodInventory::where('facility_id', $this->facility->id)
            ->where('blood_group', 'O+')
            ->where('component', 'whole_blood')
            ->get();

        $this->assertCount(1, $rows, 'the upsert key is (facility, group, component)');
        $this->assertSame(3, (int) $rows->first()->available_units);

        // The published band followed the shelf down, on one row.
        $this->assertSame(1, BloodAvailability::query()->count());
        $this->assertSame('1-5', BloodAvailability::firstOrFail()->units_available_range);
    }

    public function test_unscreened_blood_is_still_refused_and_stores_nothing(): void
    {
        $this->postJson(self::BLOOD_URL, [
            'facility_reference' => 'BANK-YDE-001',
            'items'              => [[
                'blood_group'      => 'A-',
                'component_code'   => 'platelets',
                'units'            => 5,
                'screening_status' => 'pending',
            ]],
        ], $this->headers)
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'UNSAFE_BLOOD_STATUS');

        $this->assertSame(0, BloodInventory::query()->count());
    }

    public function test_a_partner_cannot_write_blood_stock_to_another_facility(): void
    {
        $otherTenant = Facility::factory()->create();

        $this->postJson(self::BLOOD_URL, [
            'facility_reference' => 'BANK-OTHER-001',
            'facility_id'        => (string) $otherTenant->id,
            'items'              => [[
                'blood_group'      => 'O+',
                'component_code'   => 'whole_blood',
                'units'            => 12,
                'screening_status' => 'screened_safe',
            ]],
        ], $this->headers)->assertOk()->assertJsonPath('facility_id', $this->facility->id);

        $this->assertDatabaseHas('blood_inventories', ['facility_id' => $this->facility->id]);
        $this->assertDatabaseMissing('blood_inventories', ['facility_id' => $otherTenant->id]);
    }

    public function test_a_blood_bank_with_no_public_listing_is_warned_its_stock_reaches_nobody(): void
    {
        // The listing created in setUp() is a pharmacy tied to this tenant, so
        // remove it: with nothing linked, the projector publishes nothing.
        $this->listing->delete();

        $this->postJson(self::BLOOD_URL, $this->bloodPayload(), $this->headers)
            ->assertOk()
            ->assertJsonPath('warnings.0', 'blood_listing_unlinked');

        $this->assertSame(0, BloodAvailability::query()->count());
        // Stored all the same — the operational record is true either way.
        $this->assertSame(1, BloodInventory::query()->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Fixtures
    |--------------------------------------------------------------------------
    */

    private function pharmacyPayload(array $overrides = []): array
    {
        return [
            'facility_reference' => 'HIS-PHARM-01',
            'items'              => [array_merge([
                'drug_code' => 'M01AE01',
                'quantity'  => 40,
            ], $overrides)],
        ];
    }

    private function bloodPayload(int $units = 12): array
    {
        return [
            'facility_reference' => 'BANK-YDE-001',
            'items'              => [[
                'blood_group'      => 'O+',
                'component_code'   => 'whole_blood',
                'units'            => $units,
                'screening_status' => 'screened_safe',
            ]],
        ];
    }

    /**
     * The tenant's one public listing, as a blood bank.
     *
     * BloodAvailabilityProjector publishes onto EVERY care_facilities row
     * linked to the tenant, so the pharmacy listing from setUp() has to go:
     * a facility with two listings legitimately gets two published rows, which
     * would make the counts below measure the fixture instead of the ingest.
     */
    private function bloodBankListing(): CareFacility
    {
        $this->listing->delete();

        return $this->listingFor($this->facility, 'Banque de Sang', 'hospital');
    }

    private function medicine(string $name, string $generic, string $atc): Medicine
    {
        return Medicine::create([
            'name'          => $name,
            'generic_name'  => $generic,
            'category'      => 'pain_relief',
            'atc_code'      => $atc,
            'currency'      => 'XAF',
            'is_active'     => true,
        ]);
    }

    private function listingFor(Facility $facility, string $name, string $type): CareFacility
    {
        $id = (string) Str::uuid();

        DB::table('care_facilities')->insert([
            'id'                  => $id,
            'facility_id'         => $facility->id,
            'facility_name'       => $name,
            'facility_type'       => $type,
            'country_code'        => 'CM',
            'city'                => 'Yaounde',
            'address'             => 'Yaounde',
            'phone_primary'       => '+237699000111',
            'latitude'            => 3.8480,
            'longitude'           => 11.5021,
            'listing_status'      => 'active',
            'verification_status' => 'unverified',
            'license_status'      => 'active',
            'integration_status'  => 'none',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        return CareFacility::findOrFail($id);
    }
}
