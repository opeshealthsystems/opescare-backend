<?php

namespace Tests\Feature;

use App\Console\Commands\ImportRoutesOfAdministration;
use App\Console\Commands\ImportUcumUnits;
use App\Models\ClinicalCodeDictionary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Guards the three vocabularies that make a unit, a route and a vaccine
 * comparable: UCUM, the EDQM routes of administration and CDC CVX.
 *
 * These tests deliberately do NOT run the importers end to end. The importers
 * talk to GitHub, the French terminology server and cdc.gov; a test that
 * reaches the public internet fails on a bad day and says nothing about this
 * code when it does. What is tested is what must stay true of the ROWS and of
 * the parsing done to the published text: that none of these can be offered
 * as a diagnosis, that the vocabularies do not collide, that a UCUM unit is
 * read off the case-sensitive code, and that a French title is only ever the
 * publisher's own.
 */
class UcumRouteVaccineImportTest extends TestCase
{
    use RefreshDatabase;

    private function code(array $attributes = []): ClinicalCodeDictionary
    {
        return ClinicalCodeDictionary::create(array_merge([
            'id' => (string) Str::uuid(),
            'system' => 'UCUM',
            'code' => 'L',
            'display' => 'liter',
            'kind' => 'unit',
            'is_active' => true,
            'source' => 'test',
        ], $attributes));
    }

    public function test_a_unit_a_route_and_a_vaccine_are_never_offered_as_a_diagnosis(): void
    {
        // "liter", "Intramuscular use" and "BCG" are things a result is
        // measured in, a way a medicine is given, and a product. None of them
        // is something a patient HAS, and the assignable scope — which the
        // diagnosis pickers read — must hand back none of them.
        $this->code();
        $this->code(['system' => 'EDQM-route', 'code' => '20035000', 'display' => 'Intramuscular use', 'kind' => 'route']);
        $this->code(['system' => 'CVX', 'code' => '19', 'display' => 'Bacillus Calmette-Guerin vaccine', 'kind' => 'vaccine']);
        $this->code(['system' => 'CVX', 'code' => '12', 'display' => 'diphtheria antitoxin', 'kind' => 'nonvaccine']);
        $this->code(['system' => 'ICD-10', 'code' => 'A00', 'display' => 'Cholera', 'kind' => 'category']);

        $this->assertSame(['A00'], ClinicalCodeDictionary::assignable()->pluck('code')->all());
    }

    public function test_the_same_code_string_in_three_vocabularies_does_not_collide(): void
    {
        // 'L' is a UCUM unit (liter) AND an ATC anatomical main group
        // (antineoplastics) AND an ICD-10 chapter (skin). Uniqueness is on
        // (system, code) precisely so all three can exist; if that ever
        // regressed, importing UCUM would overwrite a WHO chapter heading.
        $this->code();
        $this->code(['system' => 'ATC', 'code' => 'L', 'display' => 'ANTINEOPLASTIC AND IMMUNOMODULATING AGENTS', 'kind' => 'chapter']);
        $this->code(['system' => 'ICD-10', 'code' => 'L', 'display' => 'Diseases of the skin', 'kind' => 'chapter']);

        $this->assertSame(3, ClinicalCodeDictionary::whereIn('code', ['L'])->count());
    }

    public function test_a_ucum_unit_is_read_off_the_case_sensitive_code(): void
    {
        // UCUM publishes `Code` (case-sensitive, the one written in a FHIR
        // Quantity and in a lab result) and `CODE` (its case-insensitive
        // variant). Taking the wrong one would file the liter as "L" in one
        // release and "L"/"l" inconsistently in another, and would put two
        // rows in the table for one unit.
        $xml = simplexml_load_string(
            '<unit Code="mol" CODE="MOL" isMetric="yes" class="si">'
            . '<name>mole</name><printSymbol>mol</printSymbol>'
            . '<property>amount of substance</property></unit>'
        );

        $row = $this->build($xml);

        $this->assertSame('mol', $row['code']);
        $this->assertSame('mole', $row['display']);
        $this->assertSame('mol', $row['short_name']);
        $this->assertSame('amount of substance', $row['property']);
        $this->assertSame('si', $row['chapter']);
        $this->assertSame('unit', $row['kind']);
    }

    public function test_a_ucum_unit_is_never_given_a_machine_translated_french_name(): void
    {
        // UCUM publishes English only. A French unit name here could only
        // have been guessed, and a guess in front of a clinician is the one
        // thing this table must never contain.
        $xml = simplexml_load_string(
            '<unit Code="L" CODE="L" isMetric="yes" class="iso1000">'
            . '<name>liter</name><printSymbol>L</printSymbol><property>volume</property></unit>'
        );

        $this->assertNull($this->build($xml)['display_fr']);
    }

    public function test_extra_ucum_names_stay_searchable_instead_of_being_dropped(): void
    {
        // Several UCUM units carry more than one published name. The first is
        // the display; silently discarding the others would lose the wording
        // somebody will actually search for.
        $xml = simplexml_load_string(
            '<unit Code="Cel" CODE="CEL" isMetric="yes" class="si">'
            . '<name>degree Celsius</name><name>degree centigrade</name>'
            . '<printSymbol>°C</printSymbol><property>temperature</property></unit>'
        );

        $row = $this->build($xml);

        $this->assertSame('degree Celsius', $row['display']);
        $this->assertSame('degree centigrade', $row['synonyms']);
    }

    public function test_a_unit_with_no_published_name_is_skipped_rather_than_invented(): void
    {
        $xml = simplexml_load_string('<unit Code="zz" CODE="ZZ" class="dimless"><property>number</property></unit>');

        $this->assertNull($this->build($xml));
    }

    public function test_the_french_route_title_is_the_publishers_own_and_the_english_is_its_designation(): void
    {
        // The French server publishes EDQM with French as the display and
        // English as a designation. Both titles are EDQM's; neither side of
        // this row is translated by us, which is the whole reason this source
        // was chosen over the SNOMED-backed HL7 value set.
        $concept = [
            'code' => '20035000',
            'display' => 'Voie intramusculaire',
            'designation' => [
                ['language' => 'en', 'value' => 'Intramuscular use'],
            ],
        ];

        $this->assertSame('Intramuscular use', $this->designation($concept, 'en'));
        $this->assertNull($this->designation($concept, 'de'));
    }

    public function test_edqm_class_and_status_are_read_from_the_published_properties(): void
    {
        // `class` is what separates the 108 routes from the 1,197 dose forms,
        // containers and devices in the same CodeSystem; `status` is what
        // decides is_active. Both are read, never assumed.
        $properties = $this->properties([
            'property' => [
                ['code' => 'class', 'valueCode' => 'ROA'],
                ['code' => 'status', 'valueString' => 'Rejected'],
                ['code' => 'versionInfo', 'valueInteger' => 1],
            ],
        ]);

        $this->assertSame('ROA', $properties['class']);
        $this->assertSame('Rejected', $properties['status']);
        // A non-scalar or absent property is absent, not empty-stringed.
        $this->assertArrayNotHasKey('mapping', $properties);
    }

    public function test_a_withdrawn_code_stays_in_the_table_and_still_resolves(): void
    {
        // A record may already point at a code its publisher has since
        // retired. Deleting it would break the record; is_active = false is
        // how the table says "withdrawn" without losing the meaning.
        $this->code(['system' => 'CVX', 'code' => '01', 'display' => 'diphtheria, tetanus toxoids and pertussis vaccine', 'kind' => 'vaccine', 'is_active' => false]);

        $this->assertSame(
            'diphtheria, tetanus toxoids and pertussis vaccine',
            ClinicalCodeDictionary::where('system', 'CVX')->where('code', '01')->value('display'),
        );
        $this->assertSame(0, ClinicalCodeDictionary::assignable()->where('system', 'CVX')->count());
    }

    public function test_a_cvx_code_keeps_its_leading_zero(): void
    {
        // '09' is Td (adult). '9' is not a CVX code at all. Trimming the code
        // as a number instead of as a string would quietly file the wrong
        // vaccine under the wrong identifier.
        $this->code(['system' => 'CVX', 'code' => '09', 'display' => 'tetanus and diphtheria toxoids, adsorbed', 'kind' => 'vaccine']);

        $this->assertSame(1, ClinicalCodeDictionary::where('system', 'CVX')->where('code', '09')->count());
        $this->assertSame(0, ClinicalCodeDictionary::where('system', 'CVX')->where('code', '9')->count());
    }

    /** @return array<string, mixed>|null */
    private function build(\SimpleXMLElement $unit): ?array
    {
        $method = new ReflectionMethod(ImportUcumUnits::class, 'row');
        $method->setAccessible(true);

        return $method->invoke(app(ImportUcumUnits::class), $unit, '2.2 (2024-06-17)');
    }

    /** @param array<string, mixed> $concept */
    private function designation(array $concept, string $language): ?string
    {
        $method = new ReflectionMethod(ImportRoutesOfAdministration::class, 'designation');
        $method->setAccessible(true);

        return $method->invoke(app(ImportRoutesOfAdministration::class), $concept, $language);
    }

    /**
     * @param  array<string, mixed>  $concept
     * @return array<string, string>
     */
    private function properties(array $concept): array
    {
        $method = new ReflectionMethod(ImportRoutesOfAdministration::class, 'properties');
        $method->setAccessible(true);

        return $method->invoke(app(ImportRoutesOfAdministration::class), $concept);
    }
}
