<?php

namespace Tests\Feature\Mobile;

use App\Enums\MedicineCategory;
use App\Enums\MedicineReservationStatus;
use App\Enums\PharmacyStockStatus;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\Medicine;
use App\Models\MedicinePharmacyStock;
use App\Models\MedicineReservation;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithMobileAuth;

class PharmacyMedicineFinderTest extends TestCase
{
    use RefreshDatabase, WithMobileAuth;

    // Douala, Akwa — the search origin used throughout these tests.
    private const ORIGIN_LAT = 4.0511;
    private const ORIGIN_LNG = 9.7679;

    private Patient $patient;
    private Medicine $paracetamol;
    private Medicine $amoxicillin;
    private CareFacility $nearPharmacy;
    private CareFacility $farPharmacy;
    private MedicinePharmacyStock $nearStock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = Patient::create([
            'health_id'     => 'OC-TST-7100-0001-01',
            'first_name'    => 'Nadia',
            'last_name'     => 'Finder',
            'sex'           => 'female',
            'date_of_birth' => '1992-04-11',
            'is_demo'       => false,
        ]);

        $this->paracetamol = Medicine::create([
            'name'                  => 'Paracetamol 500mg Tablet',
            'generic_name'          => 'Paracetamol',
            'strength'              => '500mg',
            'form'                  => 'tablet',
            'category'              => MedicineCategory::PainRelief->value,
            'description'           => 'Relieves mild to moderate pain and reduces fever.',
            'indications'           => ['Pain relief', 'Fever'],
            'prescription_required' => false,
            'default_pack_size'     => '10 tablets',
            'pack_size_options'     => ['10 tablets', '20 tablets'],
            'price_min'             => 200,
            'price_max'             => 500,
            'currency'              => 'XAF',
        ]);

        $this->amoxicillin = Medicine::create([
            'name'                  => 'Amoxicillin 500mg Capsule',
            'generic_name'          => 'Amoxicillin',
            'strength'              => '500mg',
            'form'                  => 'capsule',
            'category'              => MedicineCategory::Antibiotics->value,
            'prescription_required' => true,
            'default_pack_size'     => '15 capsules',
            'price_min'             => 1500,
            'price_max'             => 3000,
            'currency'              => 'XAF',
        ]);

        // ~1.2 km from the origin.
        $this->nearPharmacy = CareFacility::create([
            'facility_name'  => 'PharmaPlus Akwa',
            'facility_type'  => 'pharmacy',
            'listing_status' => 'active',
            'city'           => 'Douala',
            'region'         => 'Littoral',
            'country_code'   => 'CM',
            'address'        => 'Rue Kitchener, Akwa',
            'latitude'       => 4.0611,
            'longitude'      => 9.7710,
            'phone_primary'  => '+237233421001',
        ]);

        // ~40 km away — outside any default radius.
        $this->farPharmacy = CareFacility::create([
            'facility_name'  => 'Pharmacie Edea',
            'facility_type'  => 'pharmacy',
            'listing_status' => 'active',
            'city'           => 'Edea',
            'region'         => 'Littoral',
            'country_code'   => 'CM',
            'address'        => 'Centre ville, Edea',
            'latitude'       => 3.8000,
            'longitude'      => 10.1333,
            'phone_primary'  => '+237233421099',
        ]);

        $this->nearStock = MedicinePharmacyStock::create([
            'medicine_id'         => $this->paracetamol->id,
            'care_facility_id'    => $this->nearPharmacy->id,
            'stock_status'        => PharmacyStockStatus::InStock->value,
            'packs_available'     => 30,
            'pack_size'           => '10 tablets',
            'unit_price'          => 250,
            'currency'            => 'XAF',
            'reservation_enabled' => true,
            'source_system' => 'portal',
        ]);

        MedicinePharmacyStock::create([
            'medicine_id'         => $this->paracetamol->id,
            'care_facility_id'    => $this->farPharmacy->id,
            'stock_status'        => PharmacyStockStatus::InStock->value,
            'packs_available'     => 5,
            'pack_size'           => '10 tablets',
            'unit_price'          => 400,
            'currency'            => 'XAF',
            'reservation_enabled' => true,
            'source_system' => 'portal',
        ]);
    }

    /** Prescriptions require a prescribing facility; create one lazily. */
    private function prescribingFacilityId(): string
    {
        return Facility::firstOrCreate(
            ['name' => 'Akwa Consultation Clinic'],
            ['type' => 'clinic', 'status' => 'active'],
        )->id;
    }

    // ── Catalog search ───────────────────────────────────────────────────────

    public function test_medicine_search_requires_authentication(): void
    {
        $this->getJson('/api/mobile/pharmacy/medicines')->assertStatus(401);
    }

    public function test_medicine_search_matches_generic_name_case_insensitively(): void
    {
        $response = $this->mobileGetJson($this->patient, '/api/mobile/pharmacy/medicines?q=PARACET');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'name', 'generic_name', 'availability']], 'pagination'])
            ->assertJsonFragment(['generic_name' => 'Paracetamol']);

        $names = array_column($response->json('data'), 'name');
        $this->assertNotContains('Amoxicillin 500mg Capsule', $names);
    }

    public function test_medicine_search_filters_by_category(): void
    {
        $response = $this->mobileGetJson(
            $this->patient,
            '/api/mobile/pharmacy/medicines?category=' . MedicineCategory::Antibiotics->value,
        );

        $response->assertStatus(200);
        $names = array_column($response->json('data'), 'name');

        $this->assertContains('Amoxicillin 500mg Capsule', $names);
        $this->assertNotContains('Paracetamol 500mg Tablet', $names);
    }

    public function test_medicine_search_rejects_an_unknown_category(): void
    {
        $this->mobileGetJson($this->patient, '/api/mobile/pharmacy/medicines?category=not_a_category')
            ->assertStatus(422);
    }

    public function test_inactive_medicines_are_never_returned(): void
    {
        $this->paracetamol->update(['is_active' => false]);

        $response = $this->mobileGetJson($this->patient, '/api/mobile/pharmacy/medicines?q=paracetamol');

        $response->assertStatus(200);
        $this->assertSame([], $response->json('data'));
    }

    public function test_medicine_payload_reports_availability_across_pharmacies(): void
    {
        $response = $this->mobileGetJson(
            $this->patient,
            '/api/mobile/pharmacy/medicines/' . $this->paracetamol->id,
        );

        $response->assertStatus(200);
        $availability = $response->json('data.availability');

        $this->assertSame(2, $availability['pharmacy_count']);
        $this->assertSame(250.0, (float) $availability['price_min']);
        $this->assertSame(400.0, (float) $availability['price_max']);
        $this->assertTrue($availability['is_available']);
    }

    public function test_categories_endpoint_returns_counts_per_category(): void
    {
        $response = $this->mobileGetJson($this->patient, '/api/mobile/pharmacy/categories');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['total_medicines', 'categories' => [['value', 'label', 'icon', 'medicine_count']]]]);

        $categories = collect($response->json('data.categories'))->keyBy('value');

        $this->assertSame(1, $categories[MedicineCategory::PainRelief->value]['medicine_count']);
        $this->assertSame(1, $categories[MedicineCategory::Antibiotics->value]['medicine_count']);
        $this->assertSame(0, $categories[MedicineCategory::Diabetes->value]['medicine_count']);
        $this->assertSame(2, $response->json('data.total_medicines'));
    }

    // ── Nearby pharmacies ────────────────────────────────────────────────────

    public function test_nearby_requires_coordinates(): void
    {
        $this->mobileGetJson($this->patient, '/api/mobile/pharmacy/nearby')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['lat', 'lng']);
    }

    public function test_nearby_excludes_pharmacies_outside_the_radius(): void
    {
        $response = $this->mobileGetJson($this->patient, '/api/mobile/pharmacy/nearby?' . http_build_query([
            'lat'       => self::ORIGIN_LAT,
            'lng'       => self::ORIGIN_LNG,
            'radius_km' => 5,
        ]));

        $response->assertStatus(200);
        $names = array_column($response->json('data'), 'name');

        $this->assertContains('PharmaPlus Akwa', $names);
        $this->assertNotContains('Pharmacie Edea', $names, 'A pharmacy 40km away must not appear in a 5km search');
    }

    public function test_nearby_returns_distance_and_stock_for_a_medicine(): void
    {
        $response = $this->mobileGetJson($this->patient, '/api/mobile/pharmacy/nearby?' . http_build_query([
            'lat'         => self::ORIGIN_LAT,
            'lng'         => self::ORIGIN_LNG,
            'radius_km'   => 5,
            'medicine_id' => $this->paracetamol->id,
        ]));

        $response->assertStatus(200);
        $row = collect($response->json('data'))->firstWhere('name', 'PharmaPlus Akwa');

        $this->assertNotNull($row);
        $this->assertGreaterThan(0, $row['distance_km']);
        $this->assertLessThan(5, $row['distance_km']);
        $this->assertSame(PharmacyStockStatus::InStock->value, $row['stock']['status']);
        $this->assertSame(250.0, (float) $row['stock']['unit_price']);
        $this->assertTrue($row['stock']['reservation_enabled']);
    }

    public function test_nearby_never_returns_non_pharmacy_facilities(): void
    {
        CareFacility::create([
            'facility_name'  => 'Akwa General Hospital',
            'facility_type'  => 'hospital',
            'listing_status' => 'active',
            'city'           => 'Douala',
            'country_code'   => 'CM',
            'address'        => 'Akwa',
            'latitude'       => self::ORIGIN_LAT,
            'longitude'      => self::ORIGIN_LNG,
            'phone_primary'  => '+237233421050',
        ]);

        $response = $this->mobileGetJson($this->patient, '/api/mobile/pharmacy/nearby?' . http_build_query([
            'lat' => self::ORIGIN_LAT,
            'lng' => self::ORIGIN_LNG,
        ]));

        $response->assertStatus(200);
        $this->assertNotContains('Akwa General Hospital', array_column($response->json('data'), 'name'));
    }

    public function test_only_stocking_filter_drops_pharmacies_without_available_stock(): void
    {
        $this->nearStock->update(['stock_status' => PharmacyStockStatus::OutOfStock->value]);

        $response = $this->mobileGetJson($this->patient, '/api/mobile/pharmacy/nearby?' . http_build_query([
            'lat'           => self::ORIGIN_LAT,
            'lng'           => self::ORIGIN_LNG,
            'radius_km'     => 5,
            'medicine_id'   => $this->paracetamol->id,
            'only_stocking' => 1,
        ]));

        $response->assertStatus(200);
        $this->assertSame([], $response->json('data'));
    }

    // ── Reservations ─────────────────────────────────────────────────────────

    public function test_reserve_creates_a_pending_hold_with_a_frozen_price(): void
    {
        $response = $this->mobilePostJson($this->patient, '/api/mobile/pharmacy/reservations', [
            'medicine_id'      => $this->paracetamol->id,
            'care_facility_id' => $this->nearPharmacy->id,
            'quantity'         => 2,
            'pack_size'        => '10 tablets',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'reference', 'status', 'total_price', 'expires_at', 'medicine', 'pharmacy']])
            ->assertJsonFragment(['status' => MedicineReservationStatus::Pending->value]);

        $this->assertSame(500.0, (float) $response->json('data.total_price'));
        $this->assertStringStartsWith('OC-RX-', $response->json('data.reference'));

        $this->assertDatabaseHas('medicine_reservations', [
            'patient_id'       => $this->patient->id,
            'medicine_id'      => $this->paracetamol->id,
            'care_facility_id' => $this->nearPharmacy->id,
            'status'           => MedicineReservationStatus::Pending->value,
        ]);

        // A later price change must not rewrite what the patient was quoted.
        $this->nearStock->update(['unit_price' => 900]);
        $this->assertSame(500.0, (float) MedicineReservation::first()->total_price);
    }

    public function test_reserve_is_rejected_when_the_pharmacy_is_out_of_stock(): void
    {
        $this->nearStock->update(['stock_status' => PharmacyStockStatus::OutOfStock->value]);

        $this->mobilePostJson($this->patient, '/api/mobile/pharmacy/reservations', [
            'medicine_id'      => $this->paracetamol->id,
            'care_facility_id' => $this->nearPharmacy->id,
        ])->assertStatus(409)->assertJsonFragment(['error_code' => 'STOCK_NOT_RESERVABLE']);

        $this->assertDatabaseCount('medicine_reservations', 0);
    }

    public function test_reserve_is_rejected_when_the_pharmacy_has_no_stock_row(): void
    {
        $unstocked = CareFacility::create([
            'facility_name'  => 'Pharmacie Sans Stock',
            'facility_type'  => 'pharmacy',
            'listing_status' => 'active',
            'city'           => 'Douala',
            'country_code'   => 'CM',
            'address'        => 'Bonanjo',
            'latitude'       => 4.0451,
            'longitude'      => 9.6892,
            'phone_primary'  => '+237233421077',
        ]);

        $this->mobilePostJson($this->patient, '/api/mobile/pharmacy/reservations', [
            'medicine_id'      => $this->paracetamol->id,
            'care_facility_id' => $unstocked->id,
        ])->assertStatus(409)->assertJsonFragment(['error_code' => 'STOCK_NOT_RESERVABLE']);
    }

    // ── Prescription-only medicines ──────────────────────────────────────────

    public function test_a_prescription_only_medicine_cannot_be_reserved_without_a_prescription(): void
    {
        MedicinePharmacyStock::create([
            'medicine_id'         => $this->amoxicillin->id,
            'care_facility_id'    => $this->nearPharmacy->id,
            'stock_status'        => PharmacyStockStatus::InStock->value,
            'packs_available'     => 12,
            'pack_size'           => '15 capsules',
            'unit_price'          => 2200,
            'reservation_enabled' => true,
            'source_system' => 'portal',
        ]);

        $this->mobilePostJson($this->patient, '/api/mobile/pharmacy/reservations', [
            'medicine_id'      => $this->amoxicillin->id,
            'care_facility_id' => $this->nearPharmacy->id,
        ])->assertStatus(422)->assertJsonFragment(['error_code' => 'PRESCRIPTION_REQUIRED']);

        $this->assertDatabaseCount('medicine_reservations', 0);
    }

    public function test_another_patients_prescription_cannot_be_attached(): void
    {
        MedicinePharmacyStock::create([
            'medicine_id'         => $this->amoxicillin->id,
            'care_facility_id'    => $this->nearPharmacy->id,
            'stock_status'        => PharmacyStockStatus::InStock->value,
            'packs_available'     => 12,
            'pack_size'           => '15 capsules',
            'unit_price'          => 2200,
            'reservation_enabled' => true,
            'source_system' => 'portal',
        ]);

        $stranger = Patient::create([
            'health_id'     => 'OC-TST-7100-0004-01',
            'first_name'    => 'Stranger',
            'last_name'     => 'Patient',
            'sex'           => 'male',
            'date_of_birth' => '1980-05-05',
            'is_demo'       => false,
        ]);

        $foreignPrescription = Prescription::create([
            'patient_id'    => $stranger->id,
            'facility_id'   => $this->prescribingFacilityId(),
            'status'        => 'active',
            'prescribed_at' => now(),
        ]);

        $this->mobilePostJson($this->patient, '/api/mobile/pharmacy/reservations', [
            'medicine_id'      => $this->amoxicillin->id,
            'care_facility_id' => $this->nearPharmacy->id,
            'prescription_id'  => $foreignPrescription->id,
        ])->assertStatus(404)->assertJsonFragment(['error_code' => 'PRESCRIPTION_NOT_FOUND']);

        $this->assertDatabaseCount('medicine_reservations', 0);
    }

    public function test_reserve_succeeds_when_the_patients_own_prescription_is_attached(): void
    {
        MedicinePharmacyStock::create([
            'medicine_id'         => $this->amoxicillin->id,
            'care_facility_id'    => $this->nearPharmacy->id,
            'stock_status'        => PharmacyStockStatus::InStock->value,
            'packs_available'     => 12,
            'pack_size'           => '15 capsules',
            'unit_price'          => 2200,
            'reservation_enabled' => true,
            'source_system' => 'portal',
        ]);

        $prescription = Prescription::create([
            'patient_id'    => $this->patient->id,
            'facility_id'   => $this->prescribingFacilityId(),
            'status'        => 'active',
            'prescribed_at' => now(),
        ]);

        $this->mobilePostJson($this->patient, '/api/mobile/pharmacy/reservations', [
            'medicine_id'      => $this->amoxicillin->id,
            'care_facility_id' => $this->nearPharmacy->id,
            'prescription_id'  => $prescription->id,
        ])->assertStatus(201)->assertJsonFragment(['prescription_id' => $prescription->id]);

        $this->assertDatabaseHas('medicine_reservations', [
            'patient_id'      => $this->patient->id,
            'medicine_id'     => $this->amoxicillin->id,
            'prescription_id' => $prescription->id,
        ]);
    }

    public function test_reserve_rejects_a_duplicate_open_hold(): void
    {
        $payload = [
            'medicine_id'      => $this->paracetamol->id,
            'care_facility_id' => $this->nearPharmacy->id,
        ];

        $this->mobilePostJson($this->patient, '/api/mobile/pharmacy/reservations', $payload)->assertStatus(201);
        $this->mobilePostJson($this->patient, '/api/mobile/pharmacy/reservations', $payload)
            ->assertStatus(409)
            ->assertJsonFragment(['error_code' => 'RESERVATION_ALREADY_OPEN']);

        $this->assertDatabaseCount('medicine_reservations', 1);
    }

    public function test_reserve_rejects_a_non_pharmacy_facility(): void
    {
        $hospital = CareFacility::create([
            'facility_name'  => 'Akwa General Hospital',
            'facility_type'  => 'hospital',
            'listing_status' => 'active',
            'city'           => 'Douala',
            'country_code'   => 'CM',
            'address'        => 'Akwa',
            'phone_primary'  => '+237233421050',
        ]);

        $this->mobilePostJson($this->patient, '/api/mobile/pharmacy/reservations', [
            'medicine_id'      => $this->paracetamol->id,
            'care_facility_id' => $hospital->id,
        ])->assertStatus(404)->assertJsonFragment(['error_code' => 'PHARMACY_NOT_FOUND']);
    }

    public function test_reservation_list_is_scoped_to_the_authenticated_patient(): void
    {
        $other = Patient::create([
            'health_id'     => 'OC-TST-7100-0002-01',
            'first_name'    => 'Other',
            'last_name'     => 'Patient',
            'sex'           => 'male',
            'date_of_birth' => '1988-02-02',
            'is_demo'       => false,
        ]);

        $this->mobilePostJson($other, '/api/mobile/pharmacy/reservations', [
            'medicine_id'      => $this->paracetamol->id,
            'care_facility_id' => $this->nearPharmacy->id,
        ])->assertStatus(201);

        $response = $this->mobileGetJson($this->patient, '/api/mobile/pharmacy/reservations');

        $response->assertStatus(200);
        $this->assertSame([], $response->json('data'), 'A patient must never see another patient\'s reservations');
    }

    public function test_cancelling_another_patients_reservation_is_not_found(): void
    {
        $other = Patient::create([
            'health_id'     => 'OC-TST-7100-0003-01',
            'first_name'    => 'Mallory',
            'last_name'     => 'Patient',
            'sex'           => 'female',
            'date_of_birth' => '1991-03-03',
            'is_demo'       => false,
        ]);

        $created = $this->mobilePostJson($other, '/api/mobile/pharmacy/reservations', [
            'medicine_id'      => $this->paracetamol->id,
            'care_facility_id' => $this->nearPharmacy->id,
        ])->json('data.id');

        $this->mobilePostJson($this->patient, "/api/mobile/pharmacy/reservations/{$created}/cancel", [])
            ->assertStatus(404)
            ->assertJsonFragment(['error_code' => 'RESERVATION_NOT_FOUND']);

        $this->assertDatabaseHas('medicine_reservations', [
            'id'     => $created,
            'status' => MedicineReservationStatus::Pending->value,
        ]);
    }

    public function test_cancel_moves_the_reservation_to_a_terminal_status_without_deleting_it(): void
    {
        $id = $this->mobilePostJson($this->patient, '/api/mobile/pharmacy/reservations', [
            'medicine_id'      => $this->paracetamol->id,
            'care_facility_id' => $this->nearPharmacy->id,
        ])->json('data.id');

        $this->mobilePostJson($this->patient, "/api/mobile/pharmacy/reservations/{$id}/cancel", [
            'reason' => 'Found it closer to home',
        ])->assertStatus(200)->assertJsonFragment(['status' => MedicineReservationStatus::Cancelled->value]);

        $this->assertDatabaseHas('medicine_reservations', [
            'id'               => $id,
            'status'           => MedicineReservationStatus::Cancelled->value,
            'cancelled_reason' => 'Found it closer to home',
        ]);

        // Cancelling twice is refused rather than silently repeated.
        $this->mobilePostJson($this->patient, "/api/mobile/pharmacy/reservations/{$id}/cancel", [])
            ->assertStatus(409)
            ->assertJsonFragment(['error_code' => 'RESERVATION_NOT_CANCELLABLE']);
    }

    public function test_open_scope_hides_cancelled_reservations(): void
    {
        $id = $this->mobilePostJson($this->patient, '/api/mobile/pharmacy/reservations', [
            'medicine_id'      => $this->paracetamol->id,
            'care_facility_id' => $this->nearPharmacy->id,
        ])->json('data.id');

        $this->mobilePostJson($this->patient, "/api/mobile/pharmacy/reservations/{$id}/cancel", []);

        $this->assertCount(1, $this->mobileGetJson($this->patient, '/api/mobile/pharmacy/reservations')->json('data'));
        $this->assertCount(0, $this->mobileGetJson($this->patient, '/api/mobile/pharmacy/reservations?scope=open')->json('data'));
    }
}
