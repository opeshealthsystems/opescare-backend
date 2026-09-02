<?php

namespace Tests\Feature\Blood;

use App\Models\BloodInventory;
use App\Models\Facility;
use App\Models\IntegrationClient;
use App\Services\JwtService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * POST /api/v1/connect/inventory/blood-stock/sync
 *
 * This endpoint validated its payload, rejected unscreened components, emitted
 * an audit event and answered {"status":"synced"} — while writing to no table
 * at all. It is the designated live feed into blood_inventories (the
 * inventory_ops freeze deliberately excludes the partner stock-sync ingest), so
 * a partner could sync all day and the Blood Finder would never change.
 *
 * It could not have worked as specified: a blood inventory row is keyed on
 * (facility_id, blood_group, component) and the request carried no blood_group.
 * The contract literally could not express what to store.
 */
class BloodStockSyncPersistsTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/connect/inventory/blood-stock/sync';

    private array $headers;
    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();
        $this->facility = Facility::factory()->create();

        // This route sits under auth.bearer, not VerifyIntegrationClient, so it
        // needs a real signed token — and facility_id must come from the token's
        // claims, never the request body.
        $client = IntegrationClient::factory()->create([
            'client_id'     => 'blood_sync_' . bin2hex(random_bytes(4)),
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

    private function payload(array $items = null): array
    {
        return [
            'facility_reference' => 'BANK-YDE-001',
            'items'              => $items ?? [[
                'blood_group'      => 'O+',
                'component_code'   => 'whole_blood',
                'units'            => 12,
                'screening_status' => 'screened_safe',
            ]],
        ];
    }

    public function test_a_synced_component_is_actually_persisted(): void
    {
        $this->postJson(self::URL, $this->payload(), $this->headers)
            ->assertOk()
            ->assertJsonPath('status', 'synced')
            ->assertJsonPath('stored_rows', 1);

        $this->assertDatabaseHas('blood_inventories', [
            'facility_id'     => $this->facility->id,
            'blood_group'     => 'O+',
            'component'       => 'whole_blood',
            'available_units' => 12,
            'is_unsafe'       => false,
        ]);
    }

    public function test_a_second_sync_updates_rather_than_duplicates(): void
    {
        $this->postJson(self::URL, $this->payload(), $this->headers)->assertOk();

        $this->postJson(self::URL, $this->payload([[
            'blood_group'      => 'O+',
            'component_code'   => 'whole_blood',
            'units'            => 3,
            'screening_status' => 'screened_safe',
        ]]), $this->headers)->assertOk();

        $rows = BloodInventory::where('facility_id', $this->facility->id)
            ->where('blood_group', 'O+')->where('component', 'whole_blood')->get();

        $this->assertCount(1, $rows, 'the upsert key is (facility, group, component)');
        $this->assertSame(3, (int) $rows->first()->available_units);
    }

    public function test_the_sync_republishes_the_patient_facing_availability(): void
    {
        // The whole point of going through BloodInventoryService rather than a
        // raw write: the Blood Finder reads blood_availability, which the
        // projector refreshes on every operational write.
        $listingId = (string) Str::uuid();
        DB::table('care_facilities')->insert([
            'id'                  => $listingId,
            'facility_id'         => $this->facility->id,
            'facility_name'       => 'Test Blood Bank',
            'facility_type'       => 'hospital',
            'country_code'        => 'CM',
            'city'                => 'Yaounde',
            'address'             => 'Yaounde',
            'phone_primary'       => 'N/A',
            'listing_status'      => 'active',
            'verification_status' => 'unverified',
            'license_status'      => 'active',
            'integration_status'  => 'none',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $this->postJson(self::URL, $this->payload(), $this->headers)->assertOk();

        // blood_availability is keyed on the PUBLIC directory row
        // (care_facilities.id), not the operational tenant — that split is the
        // whole reason the projector exists.
        $this->assertDatabaseHas('blood_availability', [
            'facility_id' => $listingId,
            'blood_group' => 'O+',
        ]);
    }

    public function test_an_unscreened_component_is_rejected_and_stores_nothing(): void
    {
        $this->postJson(self::URL, $this->payload([[
            'blood_group'      => 'A-',
            'component_code'   => 'platelets',
            'units'            => 5,
            'screening_status' => 'pending',
        ]]), $this->headers)->assertStatus(422);

        $this->assertDatabaseMissing('blood_inventories', [
            'facility_id' => $this->facility->id,
            'blood_group' => 'A-',
        ]);
    }

    public function test_blood_group_is_required_and_validated(): void
    {
        $this->postJson(self::URL, $this->payload([[
            'component_code'   => 'whole_blood',
            'units'            => 4,
            'screening_status' => 'screened_safe',
        ]]), $this->headers)->assertStatus(422);

        $this->postJson(self::URL, $this->payload([[
            'blood_group'      => 'Z+',
            'component_code'   => 'whole_blood',
            'units'            => 4,
            'screening_status' => 'screened_safe',
        ]]), $this->headers)->assertStatus(422);
    }

    public function test_an_unknown_component_spelling_is_rejected(): void
    {
        // Operational spellings come from BloodAvailabilityProjector, which is
        // what maps them onto the published component a patient sees.
        $this->postJson(self::URL, $this->payload([[
            'blood_group'      => 'O+',
            'component_code'   => 'green_cells',
            'units'            => 4,
            'screening_status' => 'screened_safe',
        ]]), $this->headers)->assertStatus(422);
    }
}
