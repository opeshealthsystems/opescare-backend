<?php

namespace Tests\Feature\Seo;

use App\Models\CareFacility;
use App\Support\CarePlaces;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CarePlacesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        // 4 active pharmacies in Yaoundé (spelling variants fold into one city).
        foreach (['Yaoundé', 'Yaoundé', 'YAOUNDE', 'yaoundé'] as $i => $city) {
            $this->listing('Pharmacie Test ' . $i, 'pharmacy', $city);
        }
        // Inactive listings must not count.
        $this->listing('Pharmacie Suspended', 'pharmacy', 'Yaoundé', 'suspended');
        $this->listing('Hôpital Draft A', 'hospital', 'Douala', 'draft');
        $this->listing('Hôpital Draft B', 'hospital', 'Douala', 'draft');
        // Thin: 2 active + 2 inactive hospitals in Douala → no page.
        $this->listing('Hôpital Actif A', 'hospital', 'Douala');
        $this->listing('Hôpital Actif B', 'hospital', 'Douala');
    }

    private function listing(string $name, string $type, string $city, string $status = 'active'): CareFacility
    {
        return CareFacility::create([
            'facility_name' => $name, 'facility_type' => $type, 'listing_status' => $status,
            'verification_status' => 'unverified', 'phone_primary' => 'N/A',
            'region' => 'Centre', 'city' => $city, 'address' => 'Quartier ' . $name,
            'latitude' => 3.86, 'longitude' => 11.51,
        ]);
    }

    public function test_place_page_renders_real_counts_and_json_ld(): void
    {
        $res = $this->get('/care-map/pharmacy/yaounde');

        $res->assertOk()
            ->assertSee('<title>Pharmacies in Yaoundé — OpesCare</title>', false)
            ->assertSee('OpesCare lists 4 pharmacies in Yaoundé.')
            ->assertSee('Pharmacie Test 0')
            ->assertDontSee('Pharmacie Suspended')
            ->assertSee('"@type":"ItemList"', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSee('"numberOfItems":4', false);
    }

    public function test_french_title(): void
    {
        $this->get('/care-map/pharmacy/yaounde?lang=fr')
            ->assertOk()
            ->assertSee('<title>Pharmacies à Yaoundé — OpesCare</title>', false)
            ->assertSee('Où trouver des pharmacies à Yaoundé ?');
    }

    public function test_thin_and_unknown_combos_404(): void
    {
        $this->get('/care-map/hospital/douala')->assertNotFound();   // 2 active only
        $this->get('/care-map/pharmacy/nowhere')->assertNotFound();
        $this->get('/care-map/not-a-type/yaounde')->assertNotFound();
    }

    public function test_inactive_listings_excluded_from_combos(): void
    {
        $combos = CarePlaces::combos();

        $this->assertCount(1, $combos);
        $this->assertSame('pharmacy', $combos[0]['type']);
        $this->assertSame(4, $combos[0]['count']);
    }

    public function test_hub_lists_combos_and_directory_links_hub(): void
    {
        $this->get('/care-map/places')->assertOk()
            ->assertSee(route('public.care-map.place', ['type' => 'pharmacy', 'city' => 'yaounde']), false)
            ->assertDontSee('/care-map/hospital/douala', false);

        $this->get('/care-map')->assertOk()->assertSee(route('public.care-map.places'), false);
        $this->get('/sitemap')->assertOk()->assertSee(url('/care-map/places'), false);
    }

    public function test_xml_sitemap_includes_combos_in_both_locales(): void
    {
        $xml = $this->get('/sitemap-pages.xml')->assertOk()->getContent();

        $this->assertStringContainsString('/care-map/pharmacy/yaounde</loc>', $xml);
        $this->assertStringContainsString('/care-map/pharmacy/yaounde?lang=fr</loc>', $xml);
        $this->assertStringContainsString('/care-map/places</loc>', $xml);
        $this->assertStringNotContainsString('/care-map/hospital/douala', $xml);
    }

    public function test_facility_profile_route_is_not_shadowed(): void
    {
        $f = CareFacility::where('listing_status', 'active')->first();
        $this->get('/care-map/facility/' . $f->id)->assertOk();
    }
}
