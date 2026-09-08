<?php

namespace Tests\Feature;

use App\Console\Commands\ImportAtcMedicines;
use App\Models\ClinicalCodeDictionary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Guards the three vocabularies added beside ICD-10 and LOINC: HL7 service
 * types (departments/services), SNOMED practice-setting codes (specialties)
 * and the WHO ATC medicine classification.
 *
 * These tests deliberately do NOT run the importers. The importers talk to
 * hl7.org, the French terminology server and NLM, and a test that reaches the
 * public internet fails on a bad day and says nothing about this code when it
 * does. What is tested here is what has to stay true of the ROWS: that they
 * cannot be confused for diagnoses, that the vocabularies do not collide, and
 * that the ATC hierarchy is read from the published code structure correctly.
 */
class ServiceSpecialtyImportTest extends TestCase
{
    use RefreshDatabase;

    private function code(array $attributes = []): ClinicalCodeDictionary
    {
        return ClinicalCodeDictionary::create(array_merge([
            'id' => (string) Str::uuid(),
            'system' => 'ATC',
            'code' => 'J01CA04',
            'display' => 'amoxicilline',
            'kind' => 'term',
            'is_active' => true,
            'source' => 'test',
        ], $attributes));
    }

    public function test_a_specialty_or_a_service_is_never_offered_as_a_diagnosis(): void
    {
        // "Cardiology" and "Immunization" are things a facility DOES. Neither
        // is something a patient HAS, and the assignable scope — which the
        // diagnosis pickers use — must not hand either one back.
        $this->code(['system' => 'SNOMED-CT', 'code' => '394579002', 'display' => 'Cardiology', 'kind' => 'specialty']);
        $this->code(['system' => 'HL7-service-type', 'code' => '57', 'display' => 'Immunization', 'kind' => 'service']);
        $this->code(['system' => 'ICD-10', 'code' => 'A00', 'display' => 'Cholera', 'kind' => 'category']);

        $this->assertSame(['A00'], ClinicalCodeDictionary::assignable()->pluck('code')->all());
    }

    public function test_an_atc_group_heading_is_not_assignable_but_a_substance_is(): void
    {
        // A prescription names a SUBSTANCE (level 5). "ANTIINFECTIVES FOR
        // SYSTEMIC USE" is a shelf, not a medicine.
        $this->code(['code' => 'J', 'display' => 'ANTIINFECTIVES FOR SYSTEMIC USE', 'kind' => 'chapter']);
        $this->code(['code' => 'J01CA', 'display' => 'Penicillins with extended spectrum', 'kind' => 'group']);
        $this->code(['code' => 'J01CA04', 'display' => 'amoxicilline', 'kind' => 'term']);

        $this->assertSame(['J01CA04'], ClinicalCodeDictionary::assignable()->pluck('code')->all());
    }

    public function test_the_same_code_string_in_two_vocabularies_does_not_collide(): void
    {
        // 'J' is an ICD-10 chapter AND an ATC anatomical main group. The
        // uniqueness is on (system, code) precisely so both can exist; if
        // this ever regressed, importing ATC would overwrite a WHO chapter.
        $this->code(['system' => 'ICD-10', 'code' => 'J', 'display' => 'Diseases of the respiratory system', 'kind' => 'chapter']);
        $this->code(['system' => 'ATC', 'code' => 'J', 'display' => 'ANTIINFECTIVES FOR SYSTEMIC USE', 'kind' => 'chapter']);

        $this->assertSame(
            'Diseases of the respiratory system',
            ClinicalCodeDictionary::where('system', 'ICD-10')->where('code', 'J')->value('display'),
        );
        $this->assertSame(
            'ANTIINFECTIVES FOR SYSTEMIC USE',
            ClinicalCodeDictionary::where('system', 'ATC')->where('code', 'J')->value('display'),
        );
    }

    public function test_the_atc_hierarchy_is_derived_from_the_published_code_lengths(): void
    {
        // ATC's five levels ARE the code lengths 1/3/4/5/7 — this is the
        // published structure, not an inference. A code of any other length
        // must come back as null so the importer skips and reports it rather
        // than inventing a parent for it.
        $command = new ImportAtcMedicines;

        $level = new ReflectionMethod($command, 'level');
        $parent = new ReflectionMethod($command, 'parent');
        $level->setAccessible(true);
        $parent->setAccessible(true);

        $this->assertSame(1, $level->invoke($command, 'A'));
        $this->assertSame(2, $level->invoke($command, 'A10'));
        $this->assertSame(3, $level->invoke($command, 'A10B'));
        $this->assertSame(4, $level->invoke($command, 'A10BA'));
        $this->assertSame(5, $level->invoke($command, 'A10BA02'));
        $this->assertNull($level->invoke($command, 'A10BA0'));

        $this->assertNull($parent->invoke($command, 'A'));
        $this->assertSame('A', $parent->invoke($command, 'A10'));
        $this->assertSame('A10', $parent->invoke($command, 'A10B'));
        $this->assertSame('A10B', $parent->invoke($command, 'A10BA'));
        $this->assertSame('A10BA', $parent->invoke($command, 'A10BA02'));
    }

    public function test_an_atc_medicine_is_findable_by_its_official_french_title(): void
    {
        // The French titles are the whole point of using the ANS server: a
        // francophone prescriber types "amoxicilline", not "amoxicillin".
        $this->code(['code' => 'J01CA04', 'display' => 'amoxicilline', 'display_fr' => 'amoxicilline']);

        $this->assertSame(
            ['J01CA04'],
            ClinicalCodeDictionary::search('amoxicill')->pluck('code')->all(),
        );
    }

    public function test_no_service_or_specialty_row_claims_a_french_title_it_does_not_have(): void
    {
        // HL7 publishes these two lists in English only. A NULL display_fr is
        // the honest state and the label falls back to English; a populated
        // one would mean somebody machine-translated a clinical term.
        $service = $this->code(['system' => 'HL7-service-type', 'code' => '57', 'display' => 'Immunization', 'kind' => 'service']);

        $this->assertNull($service->display_fr);

        $this->app->setLocale('fr');
        $this->assertSame('Immunization', $service->refresh()->label);
    }
}
