<?php

namespace Tests\Feature;

use App\Models\CareFacility;
use App\Models\CareFacilityService;
use App\Models\PharmacyStockAvailability;
use App\Models\BloodAvailability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CareMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_search_nearby_facilities_with_haversine_distance()
    {
        // 1. Create target facilities with physical coordinates
        $facilityA = CareFacility::create([
            'facility_name' => 'Downtown Hospital',
            'facility_type' => 'hospital',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'listing_status' => 'active',
            'verification_status' => 'license_verified',
            'phone_primary' => '123456789',
            'region' => 'Paris',
            'city' => 'Paris',
            'address' => '1 Main Street',
        ]);

        $facilityB = CareFacility::create([
            'facility_name' => 'Far Away Clinic',
            'facility_type' => 'clinic',
            'latitude' => 51.5074,
            'longitude' => -0.1278,
            'listing_status' => 'active',
            'verification_status' => 'license_verified',
            'phone_primary' => '987654321',
            'region' => 'London',
            'city' => 'London',
            'address' => '2 Suburb Road',
        ]);

        // 2. Search nearby Paris coordinates (48.85, 2.35)
        $response = $this->getJson('/api/v1/care-map/facilities?latitude=48.85&longitude=2.35&radius=50');

        $response->assertStatus(200);
        $response->assertJsonFragment(['facility_name' => 'Downtown Hospital']);
        $response->assertJsonMissing(['facility_name' => 'Far Away Clinic']);
    }

    public function test_high_risk_field_changes_require_moderation_review()
    {
        $facility = CareFacility::create([
            'facility_name' => 'City EMR Center',
            'facility_type' => 'clinic',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'listing_status' => 'active',
            'verification_status' => 'unverified',
            'phone_primary' => '111111111',
            'region' => 'Paris',
            'city' => 'Paris',
            'address' => '3 High Way',
        ]);

        $actor = User::create([
            'name' => 'Dr. Partner',
            'email' => 'partner@opescare.com',
            'password' => bcrypt('password'),
        ]);

        // Request update to high-risk field (facility_type) and low-risk field (address)
        $verificationService = $this->app->make(\App\Modules\CareMap\Services\FacilityVerificationService::class);
        $result = $verificationService->updateProfile($facility->id, [
            'facility_type' => 'hospital', // High-risk: should NOT apply immediately
            'address' => '12 New Renovated Blvd', // Low-risk: should apply immediately
        ], $actor->id);

        $this->assertEquals(1, $result['pending_review_count']);
        
        // Assert low-risk applied
        $facility->refresh();
        $this->assertEquals('12 New Renovated Blvd', $facility->address);
        
        // Assert high-risk was intercepted and did NOT apply
        $this->assertEquals('clinic', $facility->facility_type);

        // Assert audit ledger record exists
        $this->assertDatabaseHas('facility_update_audits', [
            'facility_id' => $facility->id,
            'field_changed' => 'facility_type',
            'new_value' => 'hospital',
            'requires_review' => true,
        ]);
    }

    public function test_patient_can_search_medicine_and_blood_availability()
    {
        $pharmacy = CareFacility::create([
            'facility_name' => 'St Jude Pharmacy',
            'facility_type' => 'pharmacy',
            'listing_status' => 'active',
            'verification_status' => 'license_verified',
            'phone_primary' => '222222222',
            'region' => 'Paris',
            'city' => 'Paris',
            'address' => '4 Central St',
        ]);

        $stock = PharmacyStockAvailability::create([
            'facility_id' => $pharmacy->id,
            'medicine_name' => 'Paracetamol',
            'generic_name' => 'Acetaminophen',
            'brand_name' => 'Panadol',
            'strength' => '500mg',
            'form' => 'tablet',
            'availability_status' => 'available',
            'quantity_available_range' => 'medium',
            'price' => 5.50,
            'currency' => 'EUR',
            'freshness_status' => 'fresh',
            'last_updated_at' => now(),
            // Provenance, as a real report carries it. The public search
            // withholds rows whose source is unknown or synthetic, so a
            // fixture without this is not a pharmacy report — it is an
            // unattributed row, and a patient must not be sent to a shop on
            // the strength of one. PharmacyStockReportService::SOURCE_PORTAL.
            'source_system' => 'portal',
        ]);

        // Search for Paracetamol
        $response = $this->getJson('/api/v1/care-map/pharmacies/medicine-search?medicine=Paracetamol');

        $response->assertStatus(200);
        $response->assertJsonFragment(['facility_name' => 'St Jude Pharmacy']);
        
        // Assert safety disclaimer is rendered in meta
        $response->assertJsonStructure([
            'meta' => ['disclaimer', 'warning']
        ]);
    }
}
