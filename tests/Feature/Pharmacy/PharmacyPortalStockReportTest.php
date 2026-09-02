<?php

namespace Tests\Feature\Pharmacy;

use App\Enums\MedicineCategory;
use App\Enums\PharmacyStockStatus;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\FacilityUpdateAudit;
use App\Models\Medicine;
use App\Models\MedicinePharmacyStock;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Modules\Pharmacy\Services\PharmacyStockReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithMobileAuth;

/**
 * The pharmacy portal writes the table the patient Medicine Finder reads.
 *
 * The point of every test here is the round trip: a pharmacist saves a stock
 * report in the portal, and a patient hitting `/api/mobile/pharmacy/nearby`
 * sees it. Before this existed the portal wrote `pharmacy_inventories`, which
 * no patient query has ever touched.
 */
class PharmacyPortalStockReportTest extends TestCase
{
    use RefreshDatabase, WithMobileAuth;

    // Douala, Akwa — the patient's search origin.
    private const ORIGIN_LAT = 4.0511;
    private const ORIGIN_LNG = 9.7679;

    private Facility $facility;
    private CareFacility $listing;
    private Medicine $medicine;
    private User $pharmacist;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility = Facility::create([
            'name'   => 'PharmaPlus Akwa',
            'type'   => 'pharmacy',
            'status' => 'active',
        ]);

        // The public directory listing, explicitly linked to the portal
        // facility. Geocoded — the finder drops listings without coordinates.
        $this->listing = CareFacility::create([
            'facility_id'    => $this->facility->id,
            'facility_name'  => 'PharmaPlus Akwa',
            'facility_type'  => 'pharmacy',
            'listing_status' => 'active',
            'country_code'   => 'CM',
            'region'         => 'Littoral',
            'city'           => 'Douala',
            'address'        => 'Rue Joss, Akwa',
            'latitude'       => 4.0520,
            'longitude'      => 9.7690,
            'phone_primary'  => '+237600000001',
        ]);

        $this->medicine = Medicine::create([
            'name'                  => 'Paracetamol 500mg Tablet',
            'generic_name'          => 'Paracetamol',
            'strength'              => '500mg',
            'form'                  => 'tablet',
            'category'              => MedicineCategory::PainRelief->value,
            'prescription_required' => false,
            'default_pack_size'     => '10 tablets',
            'currency'              => 'XAF',
        ]);

        $role = Role::firstOrCreate(
            ['name' => 'pharmacist'],
            ['description' => 'Pharmacist', 'dashboard_profile_key' => 'pharmacist'],
        );

        $this->pharmacist = User::create([
            'name'                => 'Aline Ngo',
            'email'               => 'aline.pharmacist@example.test',
            'password'            => bcrypt('secret-pass-1234'),
            'primary_facility_id' => $this->facility->id,
            'status'              => 'active',
        ]);
        // `role_id` is deliberately not mass-assignable on User.
        $this->pharmacist->role_id = $role->id;
        $this->pharmacist->save();

        $this->patient = Patient::create([
            'health_id'     => 'OC-TST-7300-0001-01',
            'first_name'    => 'Ngozi',
            'last_name'     => 'Seeker',
            'sex'           => 'female',
            'date_of_birth' => '1990-02-02',
            'is_demo'       => false,
        ]);
    }

    /** The pharmacist's session, with the facility context the portal expects. */
    private function asPharmacist(): self
    {
        return $this->actingAs($this->pharmacist)
            ->withSession(['active_facility_id' => $this->facility->id]);
    }

    /** @return array<string,mixed>|null the finder's row for this pharmacy */
    private function patientSeesPharmacy(): ?array
    {
        $res = $this->mobileGetJson($this->patient, '/api/mobile/pharmacy/nearby?' . http_build_query([
            'lat'         => self::ORIGIN_LAT,
            'lng'         => self::ORIGIN_LNG,
            'radius_km'   => 5,
            'medicine_id' => $this->medicine->id,
        ]));

        $res->assertOk();

        foreach ($res->json('data') as $row) {
            if ($row['id'] === $this->listing->id) {
                return $row;
            }
        }

        return null;
    }

    public function test_portal_stock_report_reaches_the_patient_medicine_finder(): void
    {
        // BEFORE: the pharmacy is listed but reports nothing.
        $before = $this->patientSeesPharmacy();
        $this->assertNotNull($before, 'The geocoded pharmacy should be in range.');
        $this->assertNull($before['stock'], 'No stock has been reported yet.');

        $this->asPharmacist()
            ->post('/portals/pharmacy/stock', [
                'medicine_id'         => $this->medicine->id,
                'stock_status'        => PharmacyStockStatus::InStock->value,
                'packs_available'     => 42,
                'pack_size'           => '10 tablets',
                'unit_price'          => 615.50,
                'reservation_enabled' => '1',
            ])
            ->assertRedirect(route('portals.pharmacy.stock'));

        // AFTER: the same patient query now carries the reported availability.
        $after = $this->patientSeesPharmacy();
        $this->assertNotNull($after['stock'], 'The portal write must surface in the patient API.');
        $this->assertSame('in_stock', $after['stock']['status']);
        $this->assertTrue($after['stock']['is_available']);
        $this->assertSame(42, $after['stock']['packs_available']);
        $this->assertSame('10 tablets', $after['stock']['pack_size']);
        $this->assertEqualsWithDelta(615.50, $after['stock']['unit_price'], 0.001);
        $this->assertTrue($after['stock']['reservation_enabled']);
        $this->assertNotNull($after['stock']['last_reported_at'], 'Freshness is the finder trust signal.');
    }

    public function test_a_portal_write_is_distinguishable_from_seeded_data(): void
    {
        $this->asPharmacist()->post('/portals/pharmacy/stock', [
            'medicine_id'  => $this->medicine->id,
            'stock_status' => PharmacyStockStatus::InStock->value,
        ]);

        $stock = MedicinePharmacyStock::query()
            ->where('care_facility_id', $this->listing->id)
            ->where('medicine_id', $this->medicine->id)
            ->firstOrFail();

        $this->assertSame(PharmacyStockReportService::SOURCE_PORTAL, $stock->source_system);
        $this->assertNotSame('demo_seed', $stock->source_system);
        $this->assertNotNull($stock->last_reported_at);
    }

    public function test_updating_an_existing_row_keeps_its_primary_key(): void
    {
        $seeded = MedicinePharmacyStock::create([
            'medicine_id'         => $this->medicine->id,
            'care_facility_id'    => $this->listing->id,
            'stock_status'        => PharmacyStockStatus::OutOfStock->value,
            'packs_available'     => 0,
            'currency'            => 'XAF',
            'reservation_enabled' => false,
            'source_system'       => 'demo_seed',
            'last_reported_at'    => now()->subMonth(),
        ]);

        $this->asPharmacist()->post('/portals/pharmacy/stock', [
            'medicine_id'         => $this->medicine->id,
            'stock_status'        => PharmacyStockStatus::LowStock->value,
            'packs_available'     => 3,
            'reservation_enabled' => '1',
        ]);

        // Same row, updated in place — `medicine_reservations.stock_id` keeps pointing somewhere real.
        $this->assertSame(1, MedicinePharmacyStock::query()
            ->where('care_facility_id', $this->listing->id)
            ->where('medicine_id', $this->medicine->id)
            ->count());

        $fresh = $seeded->fresh();
        $this->assertSame($seeded->id, $fresh->id);
        $this->assertSame(PharmacyStockStatus::LowStock, $fresh->stock_status);
        $this->assertSame(3, $fresh->packs_available);
        $this->assertSame(PharmacyStockReportService::SOURCE_PORTAL, $fresh->source_system);
    }

    public function test_taking_a_row_over_from_another_source_is_audited(): void
    {
        MedicinePharmacyStock::create([
            'medicine_id'      => $this->medicine->id,
            'care_facility_id' => $this->listing->id,
            'stock_status'     => PharmacyStockStatus::InStock->value,
            'currency'         => 'XAF',
            'source_system'    => 'demo_seed',
            'last_reported_at' => now()->subMonth(),
        ]);

        $this->asPharmacist()->post('/portals/pharmacy/stock', [
            'medicine_id'  => $this->medicine->id,
            'stock_status' => PharmacyStockStatus::LowStock->value,
        ]);

        $takeover = FacilityUpdateAudit::query()
            ->where('facility_id', $this->listing->id)
            ->where('field_changed', 'medicine_pharmacy_stock.source_system')
            ->first();

        $this->assertNotNull($takeover, 'Overwriting another source must be recorded.');
        $this->assertSame('demo_seed', $takeover->old_value);
        $this->assertSame(PharmacyStockReportService::SOURCE_PORTAL, $takeover->new_value);
        $this->assertSame($this->pharmacist->id, $takeover->actor_id);
    }

    public function test_out_of_stock_is_reportable_and_hides_the_pharmacy_from_only_stocking_searches(): void
    {
        $this->asPharmacist()->post('/portals/pharmacy/stock', [
            'medicine_id'  => $this->medicine->id,
            'stock_status' => PharmacyStockStatus::OutOfStock->value,
        ]);

        $row = $this->patientSeesPharmacy();
        $this->assertSame('out_of_stock', $row['stock']['status']);
        $this->assertFalse($row['stock']['is_available']);

        $res = $this->mobileGetJson($this->patient, '/api/mobile/pharmacy/nearby?' . http_build_query([
            'lat'           => self::ORIGIN_LAT,
            'lng'           => self::ORIGIN_LNG,
            'radius_km'     => 5,
            'medicine_id'   => $this->medicine->id,
            'only_stocking' => 1,
        ]));

        $res->assertOk();
        $this->assertSame([], $res->json('data'));
    }

    public function test_a_pharmacy_without_a_public_listing_cannot_publish_stock(): void
    {
        // Unlink the listing: the portal facility no longer owns a directory entry.
        $this->listing->forceFill(['facility_id' => null])->save();

        $this->asPharmacist()
            ->post('/portals/pharmacy/stock', [
                'medicine_id'  => $this->medicine->id,
                'stock_status' => PharmacyStockStatus::InStock->value,
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, MedicinePharmacyStock::query()
            ->where('care_facility_id', $this->listing->id)
            ->count());
    }

    public function test_the_stock_page_warns_when_the_listing_has_no_coordinates(): void
    {
        $this->listing->forceFill(['latitude' => null, 'longitude' => null])->save();

        $this->asPharmacist()
            ->get('/portals/pharmacy/stock')
            ->assertOk()
            ->assertSee(__('public.pharmacy_portal.stock_warn_no_coords_title'), false);

        // And the finder does indeed drop it.
        $this->assertNull($this->patientSeesPharmacy());
    }

    public function test_the_stock_page_lists_what_the_finder_serves(): void
    {
        $this->asPharmacist()->post('/portals/pharmacy/stock', [
            'medicine_id'     => $this->medicine->id,
            'stock_status'    => PharmacyStockStatus::InStock->value,
            'packs_available' => 12,
        ]);

        $this->asPharmacist()
            ->get('/portals/pharmacy/stock')
            ->assertOk()
            ->assertSee('Paracetamol 500mg Tablet')
            ->assertSee(__('public.pharmacy_portal.stock_src_portal'), false);

        // The legacy /portals/pharmacy/inventory path is repointed at the same
        // data — it no longer reads pharmacy_inventories.
        $this->asPharmacist()
            ->get('/portals/pharmacy/inventory')
            ->assertOk()
            ->assertSee('Paracetamol 500mg Tablet');
    }

    public function test_a_stock_report_never_writes_to_the_legacy_pharmacy_inventories_table(): void
    {
        $this->asPharmacist()->post('/portals/pharmacy/stock', [
            'medicine_id'  => $this->medicine->id,
            'stock_status' => PharmacyStockStatus::InStock->value,
        ]);

        // pharmacy_inventories is the facility's internal dispensing ledger and
        // stays exactly as it was — nothing migrated, nothing dropped.
        $this->assertSame(0, \App\Models\PharmacyInventory::query()
            ->where('facility_id', $this->facility->id)
            ->count());
    }
}
