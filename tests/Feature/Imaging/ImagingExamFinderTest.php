<?php

namespace Tests\Feature\Imaging;

use App\Models\CareFacility;
use App\Models\ClinicalCodeDictionary;
use App\Models\Facility;
use App\Models\LabTestAvailability;
use App\Services\Imaging\ImagingExamSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Imaging audit 2, I-1: an imaging centre's published exams are findable.
 *
 *  - an imaging LOINC code published by an active listing is found by name,
 *    by LOINC code and by modality, with its whole-franc price;
 *  - a LAB code on the same availability table is NOT offered as imaging;
 *  - seeded (synthetic-source) and retired rows stay hidden;
 *  - the public page renders in EN and says "no centre published" when empty.
 */
class ImagingExamFinderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Route::has('public.network.imaging-finder')) {
            Route::middleware('web')->group(base_path('routes/imaging_finder.php'));
            Route::getRoutes()->refreshNameLookups();
        }
    }

    private function listing(string $name = 'Centre d\'Imagerie Test'): CareFacility
    {
        $tenant = Facility::factory()->create(['type' => 'imaging_center']);

        return CareFacility::create([
            'facility_id'    => $tenant->id,
            'facility_name'  => $name,
            'facility_type'  => 'imaging_center',
            'listing_status' => 'active',
            'country_code'   => 'CM',
            'region'         => 'Littoral',
            'city'           => 'Douala',
            'address'        => 'Bonanjo',
            'latitude'       => 4.0435,
            'longitude'      => 9.6966,
            'phone_primary'  => '+237600000077',
        ]);
    }

    private function code(string $code, string $display, ?string $reason): void
    {
        ClinicalCodeDictionary::create([
            'id'            => (string) Str::uuid(),
            'system'        => 'LOINC',
            'code'          => $code,
            'display'       => $display,
            'kind'          => 'term',
            'common_reason' => $reason,
            'is_active'     => true,
            'source'        => 'test-fixture',
        ]);
    }

    private function publish(CareFacility $l, string $code, string $name, ?int $price, string $source = 'portal', string $status = 'available'): void
    {
        LabTestAvailability::create([
            'facility_id'         => $l->id,
            'loinc_code'          => $code,
            'test_name'           => $name,
            'price_xaf'           => $price,
            'price'               => $price,
            'currency'            => 'XAF',
            'availability_status' => $status,
            'freshness_status'    => 'fresh',
            'source_system'       => $source,
            'last_updated_at'     => now(),
            'last_published_at'   => now(),
        ]);
    }

    public function test_published_imaging_exam_is_found_by_name_code_and_modality_and_lab_codes_are_not(): void
    {
        $l = $this->listing();
        $this->code('24558-9', 'US Abdomen', 'Imaging: Ultrasound');
        $this->code('718-7', 'Hemoglobin [Mass/volume] in Blood', 'LOINC Universal Lab Orders');
        $this->publish($l, '24558-9', 'Échographie abdominale', 15000);
        $this->publish($l, '718-7', 'Hémoglobine', 2500);

        $search = app(ImagingExamSearch::class);

        foreach (['Échographie', '24558-9', 'ultrasound'] as $term) {
            $hits = $search->search($term);
            $this->assertCount(1, $hits, "term {$term}");
            $this->assertSame(15000, (int) $hits->first()->matched_test->price_xaf);
        }

        $this->assertCount(0, $search->search('Hémoglobine'));
        $this->assertCount(0, $search->search('718-7'));
    }

    public function test_imaging_exams_do_not_appear_on_the_lab_test_finder(): void
    {
        $l = $this->listing('Centre de Diagnostic Mixte');
        $this->code('24725-4', 'CT Head', 'Imaging: CT');
        $this->code('2345-7', 'Glucose', 'Chemistry');
        $this->publish($l, '24725-4', 'CT Head scan', 45000);
        $this->publish($l, '2345-7', 'Head glucose panel', 2000);

        $found = collect(app(\App\Modules\CareMap\Services\LabTestSearchService::class)->searchTests('Head'))
            ->pluck('matched_test.loinc_code')->all();

        $this->assertContains('2345-7', $found, 'a lab test from the same centre still appears');
        $this->assertNotContains('24725-4', $found, 'a CT exam must not be listed as a lab test');
    }

    public function test_seeded_and_retired_rows_and_inactive_listings_stay_hidden(): void
    {
        $this->code('24627-2', 'CT Chest', 'Imaging: CT');
        $this->publish($this->listing('A'), '24627-2', 'Scanner thoracique', 60000, 'seed');
        $this->publish($this->listing('B'), '24627-2', 'Scanner thoracique', 60000, 'portal', 'unavailable');
        $c = $this->listing('C');
        $c->forceFill(['listing_status' => 'pending'])->save();
        $this->publish($c, '24627-2', 'Scanner thoracique', 60000);

        $this->assertCount(0, app(ImagingExamSearch::class)->search('Scanner'));
    }

    public function test_public_page_renders_results_price_and_honest_empty_state(): void
    {
        $l = $this->listing('Imagerie Bonanjo');
        $this->code('24558-9', 'US Abdomen', 'Imaging: Ultrasound');
        $this->publish($l, '24558-9', 'Echographie abdominale', 15000);

        $this->get('/network/imaging-finder')->assertOk()->assertSee(__('imaging_finder.title'));

        $this->get('/network/imaging-finder?q=Echographie')
            ->assertOk()
            ->assertSee('Imagerie Bonanjo')
            ->assertSee('15 000 FCFA');

        $this->get('/network/imaging-finder?q=IRM%20genou')
            ->assertOk()
            ->assertSee(__('imaging_finder.empty_title'));
    }
}
