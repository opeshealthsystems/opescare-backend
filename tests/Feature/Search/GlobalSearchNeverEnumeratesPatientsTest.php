<?php

namespace Tests\Feature\Search;

use App\Models\Patient;
use App\Modules\Search\Services\GlobalSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Global search must never enumerate patients.
 *
 * Until 2026-09-14 GlobalSearchService carried a patientResults() method that
 * ran `LOWER(first_name) LIKE %term%` against the national patient register.
 * It was reachable through an `include_sensitive` context flag that its only
 * caller hardcoded to false — one boolean from a name search across every
 * patient in the country. The method and the flag are gone; this test keeps
 * them gone. Patient lookup is exact Health ID via Patient::resolveHealthId(),
 * behind a consent gate, on the desks that need it — never here.
 */
class GlobalSearchNeverEnumeratesPatientsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_patient_search_method_and_its_flag_no_longer_exist(): void
    {
        $this->assertFalse(
            method_exists(GlobalSearchService::class, 'patientResults'),
            'GlobalSearchService::patientResults() is a patient name-enumeration search and must not come back.'
        );

        $source = (string) file_get_contents((new \ReflectionClass(GlobalSearchService::class))->getFileName());

        $this->assertStringNotContainsString('include_sensitive', $source, 'The include_sensitive switch must not come back.');
        $this->assertStringNotContainsString('use App\\Models\\Patient;', $source, 'GlobalSearchService must not touch the Patient model.');
    }

    public function test_a_search_issues_no_query_against_the_patients_table_even_when_the_flag_is_set(): void
    {
        Patient::factory()->create([
            'first_name' => 'Amara',
            'last_name'  => 'Ndi',
            'health_id'  => 'CM-HID-TEST-GSRCH-0001',
            'is_demo'    => (bool) config('demo.enabled'),
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $result = app(GlobalSearchService::class)->search('amara', [
            'actor_id'          => 'someone',
            'facility_id'       => 'somewhere',
            'include_sensitive' => true, // the old switch: must be inert
        ]);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertNotEmpty($queries, 'The search should have queried something.');

        $touchingPatients = array_values(array_filter(
            array_map(fn (array $q) => $q['query'], $queries),
            fn (string $sql) => preg_match('/\bfrom\s+"?patients"?\b/i', $sql) === 1
                || preg_match('/\bjoin\s+"?patients"?\b/i', $sql) === 1
        ));

        $this->assertSame([], $touchingPatients, "Global search queried the patients table:\n  " . implode("\n  ", $touchingPatients));

        $types = array_column($result['results'], 'type');
        $this->assertNotContains('patient', $types);
        $this->assertArrayNotHasKey('patient', $result['counts']);
    }
}
