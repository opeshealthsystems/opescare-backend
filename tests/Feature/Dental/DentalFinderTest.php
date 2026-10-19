<?php

namespace Tests\Feature\Dental;

use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\FacilityCatalogueItem;
use App\Modules\CareMap\Services\CareMapSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Dental practice audit 2: a patient must be able to find a dentist by the word
 * they type, and the prices a practice publishes in its catalogue must reach
 * its public profile.
 */
class DentalFinderTest extends TestCase
{
    use RefreshDatabase;

    private function listing(array $overrides = []): CareFacility
    {
        return CareFacility::create(array_merge([
            'facility_name'       => 'Cabinet Dentaire Test ' . Str::random(5),
            'facility_type'       => 'dental',
            'listing_status'      => 'active',
            'verification_status' => 'unverified',
            'country_code'        => 'CM',
            'region'              => 'Centre',
            'city'                => 'Yaoundé',
            'address'             => 'Bastos',
            'phone_primary'       => '+237222000000',
        ], $overrides));
    }

    private function code(string $system, string $code, string $display): void
    {
        DB::table('clinical_code_dictionary')->insert([
            'id' => (string) Str::uuid(), 'system' => $system, 'code' => $code,
            'display' => $display, 'kind' => $system === 'ICHI' ? 'procedure' : 'term',
            'is_active' => true, 'source' => 'test', 'source_version' => 'test', 'imported_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_lowercase_french_name_finds_a_cabinet_dentaire(): void
    {
        $l = $this->listing(['facility_name' => 'Cabinet Dentaire Mvog-Ada']);

        $ids = collect(app(CareMapSearchService::class)->searchNearby(['query' => 'dentaire']))->pluck('id');

        $this->assertContains($l->id, $ids->all());
    }

    public function test_dentist_and_dentiste_find_dental_type_listings(): void
    {
        $l = $this->listing(['facility_name' => 'Smile Care Bastos']);
        $other = $this->listing(['facility_name' => 'Clinique Sans Rapport', 'facility_type' => 'clinic']);

        foreach (['dentist', 'Dentiste', 'dental practice'] as $q) {
            $ids = collect(app(CareMapSearchService::class)->searchNearby(['query' => $q]))->pluck('id')->all();
            $this->assertContains($l->id, $ids, $q);
            $this->assertNotContains($other->id, $ids, $q);
        }

        $this->get('/care-map?query=dentiste')->assertOk()->assertSee('Smile Care Bastos');
    }

    public function test_profile_shows_published_procedure_prices_but_not_lab_tests_or_retired_rows(): void
    {
        $tenant = Facility::create(['name' => 'Cabinet Dentaire Demo', 'type' => 'dental', 'status' => 'active']);
        $l = $this->listing(['facility_id' => $tenant->id]);
        $this->code('ICHI', 'KAE.JE.AC', 'Extraction of tooth without incision');
        $this->code('ICHI', 'KAE.ML.AC', 'Root canal therapy');
        $this->code('LOINC', '718-7', 'Hemoglobin [Mass/volume] in Blood');

        FacilityCatalogueItem::create(['facility_id' => $tenant->id, 'system' => 'ICHI', 'code' => 'KAE.JE.AC', 'price_xaf' => 15000, 'is_active' => true]);
        FacilityCatalogueItem::create(['facility_id' => $tenant->id, 'system' => 'ICHI', 'code' => 'KAE.ML.AC', 'price_xaf' => null, 'is_active' => true]);
        FacilityCatalogueItem::create(['facility_id' => $tenant->id, 'system' => 'LOINC', 'code' => '718-7', 'price_xaf' => 2000, 'is_active' => true]);

        $other = Facility::create(['name' => 'Autre Cabinet', 'type' => 'dental', 'status' => 'active']);
        $this->code('ICHI', 'KAE.JG.AC', 'Dental scaling, polishing, and debridement');
        FacilityCatalogueItem::create(['facility_id' => $other->id, 'system' => 'ICHI', 'code' => 'KAE.JG.AC', 'price_xaf' => 9000, 'is_active' => true]);

        $this->get(route('public.care-map.profile', $l->id))
            ->assertOk()
            ->assertSee('Extraction of tooth without incision')
            ->assertSee('15,000 FCFA')
            ->assertSee(__('finder_directory.price_not_published'))
            ->assertDontSee('Hemoglobin [Mass/volume] in Blood', false)
            ->assertDontSee('Dental scaling');
    }

    public function test_unlinked_listing_shows_no_price_section(): void
    {
        $l = $this->listing();

        $this->get(route('public.care-map.profile', $l->id))
            ->assertOk()
            ->assertDontSee('id="price-list"', false);
    }
}
