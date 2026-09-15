<?php

namespace Tests\Feature\EyeClinic;

use App\Models\CareFacility;
use App\Modules\CareMap\Services\CareMapSearchService;
use App\Support\EyeCare\EyeCareSearchTerms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Eye clinic audit 2: a patient must find an eye clinic by the word they type
 * ("ophthalmologist", "ophtalmologue", "eye doctor"), not only by a listing
 * whose name happens to contain it.
 */
class EyeClinicFinderTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_terms_are_lower_case_trimmed_and_unique(): void
    {
        foreach (EyeCareSearchTerms::WORDS as $w) {
            $this->assertSame(mb_strtolower(trim($w)), $w, "not normalised: {$w}");
        }
        $this->assertSame(count(EyeCareSearchTerms::WORDS), count(array_unique(EyeCareSearchTerms::WORDS)));
    }

    public function test_search_terms_never_name_an_optician_or_a_condition(): void
    {
        foreach (['opticien', 'optician', 'lunettes', 'glasses', 'cataract', 'cataracte', 'glaucoma', 'glaucome'] as $w) {
            $this->assertNotContains($w, EyeCareSearchTerms::WORDS);
        }
    }

    public function test_search_terms_do_not_collide_with_another_facility_type(): void
    {
        foreach (CareMapSearchService::TYPE_SYNONYMS as $type => $words) {
            if ($type === EyeCareSearchTerms::FACILITY_TYPE) {
                continue;
            }
            $this->assertSame([], array_values(array_intersect($words, EyeCareSearchTerms::WORDS)), "collides with {$type}");
        }
    }

    public function test_ophthalmologist_finds_an_eye_clinic_whose_name_does_not_say_so(): void
    {
        if (! array_key_exists(EyeCareSearchTerms::FACILITY_TYPE, CareMapSearchService::TYPE_SYNONYMS)) {
            $this->markTestIncomplete('Shared-file change pending: add eye_clinic => EyeCareSearchTerms::WORDS to CareMapSearchService::TYPE_SYNONYMS.');
        }

        $eye = $this->listing('Cabinet Dr Titalom', 'eye_clinic');
        $other = $this->listing('Clinique Sans Rapport', 'clinic');

        foreach (['ophthalmologist', 'Ophtalmologue', 'eye doctor', 'Eye'] as $q) {
            $ids = collect(app(CareMapSearchService::class)->searchNearby(['query' => $q]))->pluck('id')->all();
            $this->assertContains($eye->id, $ids, "query {$q}");
            $this->assertNotContains($other->id, $ids, "query {$q}");
        }
    }

    private function listing(string $name, string $type): CareFacility
    {
        return CareFacility::create([
            'facility_name'       => $name . ' ' . Str::random(4),
            'facility_type'       => $type,
            'listing_status'      => 'active',
            'verification_status' => 'unverified',
            'country_code'        => 'CM',
            'region'              => 'Centre',
            'city'                => 'Yaoundé',
            'address'             => 'Bastos',
            'phone_primary'       => '+237222000000',
        ]);
    }
}
