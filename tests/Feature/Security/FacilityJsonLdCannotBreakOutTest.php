<?php

namespace Tests\Feature\Security;

use App\Models\CareFacility;
use App\Models\Facility;
use App\Services\CareMap\FacilitySettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Security scan 2026-09-15, item 2. The public facility profile printed the
 * listing's name/address into <script type="application/ld+json"> with
 * JSON_UNESCAPED_SLASHES and no JSON_HEX_TAG, so a value containing
 * `</script>` closed the block and ran script on the Finder origin.
 */
class FacilityJsonLdCannotBreakOutTest extends TestCase
{
    use RefreshDatabase;

    private const PAYLOAD = 'Pharmacie X</script><script>window.__pwned=1</script>';

    public function test_a_hostile_listing_name_cannot_close_the_json_ld_script_block(): void
    {
        // As if the value arrived through a path other than the settings form
        // (directory import, older data): the output encoding must hold alone.
        $tenant = Facility::factory()->create(['type' => 'pharmacy']);
        $listing = CareFacility::create([
            'facility_id'    => $tenant->id,
            'facility_name'  => self::PAYLOAD,
            'facility_type'  => 'pharmacy',
            'listing_status' => 'active',
            'country_code'   => 'CM',
            'region'         => 'Littoral',
            'city'           => 'Douala</script><script>window.__pwned=2</script>',
            'address'        => 'Rue Joss, Akwa',
            'latitude'       => 4.0520,
            'longitude'      => 9.7690,
            'phone_primary'  => '+237600000001',
        ]);

        $html = $this->get(route('public.care-map.profile', $listing->id))->assertOk()->getContent();

        $this->assertStringNotContainsString('</script><script>window.__pwned', $html,
            'A listing value must never close a script block on the public profile.');
        // Proves the JSON-LD block actually rendered with this listing and the
        // value was neutralised, not that the block was simply absent.
        $this->assertStringContainsString('\u003C/script\u003E\u003Cscript\u003Ewindow.__pwned=1', $html,
            'The JSON-LD must carry the name with < and > hex-encoded.');
    }

    public function test_the_settings_form_refuses_angle_brackets_in_public_fields(): void
    {
        $identity = Validator::make(['facility_name' => self::PAYLOAD], FacilitySettingsService::identityRules());
        $this->assertTrue($identity->errors()->has('facility_name'));

        $location = Validator::make(
            ['address' => '<img src=x onerror=alert(1)>', 'city' => 'Douala>', 'region' => '<b>'],
            FacilitySettingsService::locationRules()
        );
        $this->assertTrue($location->errors()->has('address'));
        $this->assertTrue($location->errors()->has('city'));
        $this->assertTrue($location->errors()->has('region'));

        // Ordinary names with apostrophes, accents and ampersands still pass.
        $ok = Validator::make(['facility_name' => "Pharmacie de l'Étoile & Fils"], FacilitySettingsService::identityRules());
        $this->assertFalse($ok->errors()->has('facility_name'));
    }
}
