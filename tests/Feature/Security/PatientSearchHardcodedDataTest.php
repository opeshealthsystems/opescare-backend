<?php
namespace Tests\Feature\Security;

use Tests\TestCase;

class PatientSearchHardcodedDataTest extends TestCase
{
    public function test_patient_search_controller_has_no_hardcoded_health_ids(): void
    {
        $source = file_get_contents(
            app_path('Http/Controllers/Api/V1/Connect/PatientSearchController.php')
        );

        $this->assertStringNotContainsString(
            'OC-CMR-7KQ9-MP42-X8D1',
            $source,
            'PatientSearchController must not contain hardcoded Health IDs'
        );
    }

    public function test_patient_search_controller_has_no_hardcoded_patient_names(): void
    {
        $source = file_get_contents(
            app_path('Http/Controllers/Api/V1/Connect/PatientSearchController.php')
        );

        $this->assertStringNotContainsString(
            'John Doe',
            $source,
            'PatientSearchController must not contain hardcoded patient names'
        );
    }
}
