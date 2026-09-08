<?php

namespace Tests\Feature;

use App\Models\ClinicalCodeDictionary;
use App\Support\Terminology\ClinicalCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The code dictionary holds published reference vocabularies. These tests
 * guard the properties that make it safe to rely on, not the import plumbing.
 */
class ClinicalCodeDictionaryTest extends TestCase
{
    use RefreshDatabase;

    private function code(array $attributes = []): ClinicalCodeDictionary
    {
        return ClinicalCodeDictionary::create(array_merge([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'system' => 'ICD-10',
            'code' => 'A00',
            'display' => 'Cholera',
            'kind' => 'category',
            'is_active' => true,
            'source' => 'WHO ICD-10 ClaML',
            'source_version' => '2019',
        ], $attributes));
    }

    public function test_a_chapter_heading_is_never_assignable(): void
    {
        // A chapter is a heading, not a diagnosis. Offering "Chapter I:
        // Certain infectious and parasitic diseases" as somebody's diagnosis
        // is the failure this scope exists to prevent.
        $this->code(['code' => 'I', 'kind' => 'chapter', 'display' => 'Certain infectious diseases']);
        $this->code(['code' => 'A00-A09', 'kind' => 'block', 'display' => 'Intestinal infectious diseases']);
        $this->code(['code' => 'A00', 'kind' => 'category']);

        $assignable = ClinicalCodeDictionary::assignable()->pluck('code')->all();

        $this->assertSame(['A00'], $assignable);
    }

    public function test_a_withdrawn_code_is_kept_but_not_assignable(): void
    {
        // Historical records still point at a withdrawn code and must still
        // resolve, so it stays in the table — it simply cannot be chosen again.
        $this->code(['code' => 'B99', 'is_active' => false]);

        $this->assertDatabaseHas('clinical_code_dictionary', ['code' => 'B99']);
        $this->assertSame(0, ClinicalCodeDictionary::assignable()->where('code', 'B99')->count());
    }

    public function test_search_matches_code_prefix_display_and_synonyms(): void
    {
        $this->code(['code' => 'A00.0', 'display' => 'Cholera due to Vibrio cholerae 01, biovar cholerae',
            'synonyms' => 'Classical cholera']);
        $this->code(['code' => 'E11', 'display' => 'Type 2 diabetes mellitus']);

        // By code prefix.
        $this->assertSame(['E11'], ClinicalCodeDictionary::search('E11')->pluck('code')->all());

        // By a word in the published title.
        $this->assertSame(['A00.0'], ClinicalCodeDictionary::search('cholerae')->pluck('code')->all());
        $this->assertSame(['E11'], ClinicalCodeDictionary::search('diabetes')->pluck('code')->all());

        // By an inclusion term — how a clinician actually searches. "Classical
        // cholera" appears nowhere in A00.0's preferred label.
        $this->assertSame(['A00.0'], ClinicalCodeDictionary::search('Classical')->pluck('code')->all());
    }

    public function test_search_treats_wildcards_as_literal_text(): void
    {
        $this->code(['code' => 'A00', 'display' => 'Cholera']);

        // A bare % must not match everything: it is a character the user
        // typed, not an instruction to the database.
        $this->assertSame(0, ClinicalCodeDictionary::search('%')->count());
    }

    public function test_an_empty_search_does_not_filter(): void
    {
        $this->code(['code' => 'A00']);
        $this->code(['code' => 'A01', 'display' => 'Typhoid fever']);

        $this->assertSame(2, ClinicalCodeDictionary::search(null)->count());
        $this->assertSame(2, ClinicalCodeDictionary::search('   ')->count());
    }

    public function test_the_same_code_cannot_be_stored_twice_for_one_system(): void
    {
        $this->code(['code' => 'A00']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->code(['code' => 'A00']);
    }

    public function test_the_same_code_may_exist_in_two_different_systems(): void
    {
        // Uniqueness is per system: nothing says LOINC and ICD-10 cannot both
        // mint a string that looks the same.
        $this->code(['system' => 'ICD-10', 'code' => 'X00']);
        $this->code(['system' => 'LOINC', 'code' => 'X00', 'kind' => 'term']);

        $this->assertSame(2, ClinicalCodeDictionary::where('code', 'X00')->count());
    }

    public function test_a_common_flag_always_names_its_basis(): void
    {
        // "Commonly used" is an opinion. A row that claims it without saying
        // whose opinion it is cannot be reviewed, only believed.
        $this->code(['code' => '718-7', 'system' => 'LOINC', 'kind' => 'term',
            'display' => 'Hemoglobin [Mass/volume] in Blood',
            'is_common' => true, 'common_reason' => 'LOINC Universal Lab Orders']);

        $unexplained = ClinicalCodeDictionary::where('is_common', true)
            ->whereNull('common_reason')
            ->count();

        $this->assertSame(0, $unexplained);
    }

    public function test_loinc_codes_in_the_dictionary_pass_their_own_check_digit(): void
    {
        // The importers refuse a code that fails, so anything stored must
        // pass. This is the property that made 70563-7 — offered for a
        // malaria rapid test — impossible to store.
        $this->code(['system' => 'LOINC', 'code' => '718-7', 'kind' => 'term',
            'display' => 'Hemoglobin [Mass/volume] in Blood']);

        foreach (ClinicalCodeDictionary::where('system', 'LOINC')->pluck('code') as $code) {
            $this->assertTrue(ClinicalCode::isLoinc($code), "{$code} is not a valid LOINC code");
        }

        $this->assertFalse(ClinicalCode::isLoinc('70563-7'));
    }

    public function test_a_facility_can_offer_a_procedure_a_service_and_a_vaccine(): void
    {
        // The regression this pins: the facility catalogue filtered on
        // assignable(), which answers "may a clinician record this against a
        // patient". Procedures, services, vaccines and specialties are none of
        // those, so every one of them was invisible on the one screen built to
        // select them — a hospital could declare it runs a haemoglobin assay
        // but not that it performs a caesarean section.
        $this->code(['system' => 'ICHI', 'code' => 'NME.JF.AA', 'kind' => 'procedure',
            'display' => 'Delivery by caesarean section']);
        $this->code(['system' => 'HL7-service-type', 'code' => '57', 'kind' => 'service',
            'display' => 'Immunization']);
        $this->code(['system' => 'CVX', 'code' => '19', 'kind' => 'vaccine',
            'display' => 'Bacillus Calmette-Guerin vaccine']);
        $this->code(['system' => 'SNOMED-CT', 'code' => '394579002', 'kind' => 'specialty',
            'display' => 'Cardiology']);

        $offerable = ClinicalCodeDictionary::offerable()->pluck('code')->all();

        foreach (['NME.JF.AA', '57', '19', '394579002'] as $code) {
            $this->assertContains($code, $offerable, "{$code} must be offerable by a facility");
        }

        // And none of them may be recorded as a diagnosis.
        $this->assertSame(0, ClinicalCodeDictionary::assignable()
            ->whereIn('code', ['NME.JF.AA', '57', '19', '394579002'])->count());
    }

    public function test_headings_and_units_are_never_offerable(): void
    {
        // A facility offers the drug, not the ATC group heading above it; and
        // nobody offers a unit of measure or a route of administration.
        $this->code(['system' => 'ATC', 'code' => 'J01CA', 'kind' => 'group', 'display' => 'Penicillins']);
        $this->code(['system' => 'ICD-10', 'code' => 'I', 'kind' => 'chapter', 'display' => 'Infectious diseases']);
        $this->code(['system' => 'UCUM', 'code' => 'mg', 'kind' => 'unit', 'display' => 'milligram']);
        $this->code(['system' => 'EDQM-route', 'code' => '20035000', 'kind' => 'route', 'display' => 'Intramuscular use']);
        $this->code(['system' => 'CVX', 'code' => '86', 'kind' => 'nonvaccine', 'display' => 'immune globulin']);

        $this->assertSame(0, ClinicalCodeDictionary::offerable()->count());
    }

    public function test_a_withdrawn_code_is_offerable_to_nobody(): void
    {
        // Historical records still resolve it; nothing new may be built on it.
        $this->code(['system' => 'ICHI', 'code' => 'AAN.AD.AA', 'kind' => 'procedure',
            'display' => 'Biopsy of cranial nerve', 'is_active' => false]);

        $this->assertSame(0, ClinicalCodeDictionary::offerable()->count());
        $this->assertDatabaseHas('clinical_code_dictionary', ['code' => 'AAN.AD.AA']);
    }

    public function test_composed_titles_are_distinguishable_from_published_ones(): void
    {
        // WHO publishes no single string for E11.9; its title is assembled
        // from the category and the fourth-character subdivision. Which rows
        // were quoted and which were built must never be a guess.
        $this->code(['code' => 'E11', 'display' => 'Type 2 diabetes mellitus']);
        $this->code(['code' => 'E11.9', 'display' => 'Type 2 diabetes mellitus, without complications',
            'source' => 'WHO ICD-10 ClaML (fourth character composed)']);

        $this->assertSame('WHO ICD-10 ClaML',
            ClinicalCodeDictionary::where('code', 'E11')->value('source'));
        $this->assertStringContainsString('composed',
            ClinicalCodeDictionary::where('code', 'E11.9')->value('source'));
    }
}
