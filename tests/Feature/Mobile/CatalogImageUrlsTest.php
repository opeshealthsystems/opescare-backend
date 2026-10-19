<?php

namespace Tests\Feature\Mobile;

use App\Enums\MedicineCategory;
use App\Enums\PharmacyStockStatus;
use App\Models\AppointmentSlot;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\Medicine;
use App\Models\MedicinePharmacyStock;
use App\Models\Patient;
use App\Models\User;
use App\Support\Media\CatalogImageUrls;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\WithMobileAuth;

/**
 * Facility logos and medicine images reach the app as absolute, signed,
 * hour-stable links, and as null (never a placeholder) when nothing was
 * uploaded.
 */
class CatalogImageUrlsTest extends TestCase
{
    use RefreshDatabase, WithMobileAuth;

    private Patient $patient;
    private CareFacility $pharmacy;
    private CareFacility $bare;
    private Medicine $medicine;
    private Facility $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::disk('local')->put('facility-photos/x/logo.jpg', 'jpeg-bytes');
        Storage::disk('local')->put('medicine-images/para.png', 'png-bytes');

        $this->patient = Patient::create([
            'health_id' => 'OC-TST-7300-0001-01', 'first_name' => 'Ines', 'last_name' => 'Image',
            'sex' => 'female', 'date_of_birth' => '1991-02-03', 'is_demo' => false,
        ]);

        $this->tenant = Facility::create(['name' => 'PharmaPlus Akwa', 'type' => 'pharmacy', 'status' => 'active']);

        $this->pharmacy = CareFacility::create([
            'facility_id' => $this->tenant->id,
            'facility_name' => 'PharmaPlus Akwa', 'facility_type' => 'pharmacy', 'listing_status' => 'active',
            'city' => 'Douala', 'region' => 'Littoral', 'country_code' => 'CM', 'address' => 'Rue Kitchener',
            'latitude' => 4.0611, 'longitude' => 9.7710, 'phone_primary' => '+237233421001',
        ]);
        $this->pharmacy->forceFill(['logo_path' => 'facility-photos/x/logo.jpg', 'last_profile_update_at' => now()])->save();

        $this->bare = CareFacility::create([
            'facility_name' => 'Pharmacie Sans Logo', 'facility_type' => 'pharmacy', 'listing_status' => 'active',
            'city' => 'Douala', 'region' => 'Littoral', 'country_code' => 'CM', 'address' => 'Bonapriso',
            'latitude' => 4.0520, 'longitude' => 9.7690, 'phone_primary' => '+237233421002',
        ]);

        $this->medicine = Medicine::create([
            'name' => 'Paracetamol 500mg Tablet', 'generic_name' => 'Paracetamol', 'strength' => '500mg',
            'form' => 'tablet', 'category' => MedicineCategory::PainRelief->value, 'currency' => 'XAF',
            'image_path' => 'medicine-images/para.png',
        ]);

        MedicinePharmacyStock::create([
            'medicine_id' => $this->medicine->id, 'care_facility_id' => $this->pharmacy->id,
            'stock_status' => PharmacyStockStatus::InStock->value, 'packs_available' => 10,
            'pack_size' => '10 tablets', 'unit_price' => 250, 'currency' => 'XAF',
            'reservation_enabled' => true, 'source_system' => 'portal',
        ]);
    }

    private function assertSignedAbsolute(?string $url, string $pathFragment): void
    {
        $this->assertNotNull($url);
        $this->assertMatchesRegularExpression('#^https?://#', $url);
        $this->assertStringContainsString($pathFragment, $url);
        $this->assertStringContainsString('signature=', $url);
        $this->assertStringContainsString('expires=', $url);
    }

    public function test_directory_list_and_detail_carry_a_logo_url_and_never_the_private_path(): void
    {
        $list = $this->mobileGetJson($this->patient, '/api/mobile/facilities?type=pharmacy')->assertOk();
        $rows = collect($list->json('data'))->keyBy('id');

        $this->assertSignedAbsolute($rows[$this->pharmacy->id]['logo_url'], '/facility/photo/' . $this->pharmacy->id);
        $this->assertNull($rows[$this->bare->id]['logo_url']);
        $this->assertArrayNotHasKey('logo_path', $rows[$this->pharmacy->id]);

        $detail = $this->mobileGetJson($this->patient, '/api/mobile/facilities/' . $this->pharmacy->id)->assertOk();
        $this->assertSignedAbsolute($detail->json('data.logo_url'), '/facility/photo/');
        $this->assertNull($this->mobileGetJson($this->patient, '/api/mobile/facilities/' . $this->bare->id)->json('data.logo_url'));
    }

    public function test_the_logo_link_actually_serves_the_image_and_is_cacheable(): void
    {
        $url = CatalogImageUrls::facilityLogo($this->pharmacy);

        $res = $this->get($url)->assertOk();
        $this->assertSame('image/jpeg', $res->headers->get('Content-Type'));
        $this->assertStringContainsString('max-age=3600', (string) $res->headers->get('Cache-Control'));

        // A tampered link is refused.
        $this->get($url . 'x')->assertForbidden();
    }

    public function test_the_link_is_stable_within_the_hour_so_clients_can_cache_it(): void
    {
        $this->travelTo(now()->startOfHour()->addMinutes(5));
        $first = CatalogImageUrls::facilityLogo($this->pharmacy);
        $this->travelTo(now()->startOfHour()->addMinutes(50));
        $this->assertSame($first, CatalogImageUrls::facilityLogo($this->pharmacy));

        // Still valid at least an hour after it was handed out.
        $this->travel(65)->minutes();
        $this->get($first)->assertOk();
    }

    public function test_medicine_search_detail_and_image_route(): void
    {
        $search = $this->mobileGetJson($this->patient, '/api/mobile/pharmacy/medicines?q=para')->assertOk();
        $this->assertSignedAbsolute($search->json('data.0.image_url'), '/api/mobile/medicines/' . $this->medicine->id . '/image');

        $detail = $this->mobileGetJson($this->patient, '/api/mobile/pharmacy/medicines/' . $this->medicine->id)->assertOk();
        $url = $detail->json('data.image_url');
        $this->assertSignedAbsolute($url, '/image');

        $img = $this->get($url)->assertOk();
        $this->assertSame('image/png', $img->headers->get('Content-Type'));
    }

    public function test_a_medicine_without_an_image_gets_null_not_a_placeholder(): void
    {
        $this->medicine->forceFill(['image_path' => null])->save();

        $detail = $this->mobileGetJson($this->patient, '/api/mobile/pharmacy/medicines/' . $this->medicine->id)->assertOk();
        $this->assertNull($detail->json('data.image_url'));
    }

    public function test_nearby_pharmacies_carry_logo_urls(): void
    {
        $res = $this->mobileGetJson($this->patient, '/api/mobile/pharmacy/nearby?lat=4.0511&lng=9.7679&radius_km=5')->assertOk();
        $rows = collect($res->json('data'))->keyBy('id');

        $this->assertSignedAbsolute($rows[$this->pharmacy->id]['logo_url'], '/facility/photo/');
        $this->assertNull($rows[$this->bare->id]['logo_url']);
    }

    public function test_reservation_carries_pharmacy_logo_and_medicine_image(): void
    {
        $res = $this->mobilePostJson($this->patient, '/api/mobile/pharmacy/reservations', [
            'medicine_id' => $this->medicine->id, 'care_facility_id' => $this->pharmacy->id, 'quantity' => 1,
        ])->assertCreated();

        $this->assertSignedAbsolute($res->json('data.pharmacy.logo_url'), '/facility/photo/');
        $this->assertSignedAbsolute($res->json('data.medicine.image_url'), '/image');
    }

    public function test_appointments_carry_the_linked_listing_logo(): void
    {
        $slot = AppointmentSlot::create([
            'facility_id' => $this->tenant->id, 'provider_id' => User::factory()->create()->id,
            'starts_at' => now()->addDay()->setTime(9, 0), 'ends_at' => now()->addDay()->setTime(9, 30),
            'capacity' => 2, 'booked_count' => 0, 'status' => 'open',
        ]);

        $booked = $this->mobilePostJson($this->patient, '/api/mobile/appointments', [
            'facility_id' => $this->tenant->id, 'appointment_slot_id' => $slot->id, 'appointment_type' => 'consultation',
        ])->assertCreated();
        $this->assertSignedAbsolute($booked->json('data.facility_logo_url'), '/facility/photo/' . $this->pharmacy->id);

        $list = $this->mobileGetJson($this->patient, '/api/mobile/appointments?scope=all')->assertOk();
        $this->assertSignedAbsolute($list->json('data.0.facility_logo_url'), '/facility/photo/');
    }
}
