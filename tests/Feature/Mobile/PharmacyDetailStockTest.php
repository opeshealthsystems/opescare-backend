<?php

namespace Tests\Feature\Mobile;

use App\Enums\MedicineCategory;
use App\Enums\PharmacyStockStatus;
use App\Models\CareFacility;
use App\Models\Medicine;
use App\Models\MedicinePharmacyStock;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithMobileAuth;

/**
 * GET /api/mobile/pharmacy/pharmacies/{id}/stock — the drugs list on a
 * pharmacy's detail page. Only allow-listed (live) provenance is published.
 */
class PharmacyDetailStockTest extends TestCase
{
    use RefreshDatabase, WithMobileAuth;

    private Patient $patient;
    private CareFacility $pharmacy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = Patient::create([
            'health_id'     => 'OC-TST-7100-0002-01',
            'first_name'    => 'Ade',
            'last_name'     => 'Stock',
            'sex'           => 'female',
            'date_of_birth' => '1990-01-01',
            'is_demo'       => false,
        ]);

        $this->pharmacy = CareFacility::create([
            'facility_name'  => 'PharmaPlus Akwa',
            'facility_type'  => 'pharmacy',
            'listing_status' => 'active',
            'city'           => 'Douala',
            'region'         => 'Littoral',
            'country_code'   => 'CM',
            'address'        => 'Akwa',
            'phone_primary'  => '+237233421001',
        ]);
    }

    private function medicine(string $name, string $generic): Medicine
    {
        return Medicine::create([
            'name'                  => $name,
            'generic_name'          => $generic,
            'form'                  => 'tablet',
            'category'              => MedicineCategory::PainRelief->value,
            'prescription_required' => false,
            'currency'              => 'XAF',
        ]);
    }

    private function stock(Medicine $m, ?string $source, PharmacyStockStatus $status = PharmacyStockStatus::InStock): void
    {
        MedicinePharmacyStock::create([
            'medicine_id'      => $m->id,
            'care_facility_id' => $this->pharmacy->id,
            'stock_status'     => $status->value,
            'packs_available'  => 10,
            'unit_price'       => 250,
            'currency'         => 'XAF',
            'source_system'    => $source,
            'last_reported_at' => now(),
        ]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson("/api/mobile/pharmacy/pharmacies/{$this->pharmacy->id}/stock")->assertStatus(401);
    }

    public function test_lists_only_live_provenance_in_stock_lines(): void
    {
        $this->stock($this->medicine('Paracetamol 500mg', 'Paracetamol'), 'portal');
        $this->stock($this->medicine('Seeded Drug', 'Seeded'), 'demo_seed');
        $this->stock($this->medicine('Unclaimed Drug', 'Unclaimed'), null);
        $this->stock($this->medicine('Gone Drug', 'Gone'), 'portal', PharmacyStockStatus::OutOfStock);

        $res = $this->mobileGetJson($this->patient, "/api/mobile/pharmacy/pharmacies/{$this->pharmacy->id}/stock");

        $res->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Paracetamol 500mg')
            ->assertJsonPath('data.0.currency', 'XAF')
            ->assertJsonStructure(['data' => [['medicine_id', 'unit_price', 'status', 'last_reported_at']]]);
    }

    public function test_search_filters_by_name(): void
    {
        $this->stock($this->medicine('Paracetamol 500mg', 'Paracetamol'), 'portal');
        $this->stock($this->medicine('Amoxicillin 500mg', 'Amoxicillin'), 'portal');

        $res = $this->mobileGetJson($this->patient, "/api/mobile/pharmacy/pharmacies/{$this->pharmacy->id}/stock?q=amox");

        $res->assertStatus(200)->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.generic_name', 'Amoxicillin');
    }

    public function test_empty_list_is_a_valid_answer(): void
    {
        $this->mobileGetJson($this->patient, "/api/mobile/pharmacy/pharmacies/{$this->pharmacy->id}/stock")
            ->assertStatus(200)->assertJsonPath('meta.total', 0)->assertJsonPath('data', []);
    }

    public function test_non_pharmacy_returns_404(): void
    {
        $clinic = CareFacility::create([
            'facility_name'  => 'Clinic',
            'facility_type'  => 'clinic',
            'listing_status' => 'active',
            'country_code'   => 'CM',
            'address'        => 'Akwa',
            'phone_primary'  => '+237233421001',
            'city'           => 'Douala',
            'region'         => 'Littoral',
        ]);

        $this->mobileGetJson($this->patient, "/api/mobile/pharmacy/pharmacies/{$clinic->id}/stock")
            ->assertStatus(404)->assertJsonPath('error_code', 'PHARMACY_NOT_FOUND');
    }
}
