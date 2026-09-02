<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class RecordControllerSafetyTest extends TestCase
{
    public function test_record_controller_does_not_use_user_first_as_actor(): void
    {
        $source = file_get_contents(
            app_path('Http/Controllers/Api/V1/Connect/RecordController.php')
        );
        $this->assertStringNotContainsString(
            'User::first()',
            $source,
            'RecordController must not use User::first() — use config(opescare.system_provider_id) instead'
        );
    }

    public function test_record_controller_has_no_test_patient_uuid_fallback(): void
    {
        $source = file_get_contents(
            app_path('Http/Controllers/Api/V1/Connect/RecordController.php')
        );
        $this->assertStringNotContainsString(
            'test_patient_uuid_01',
            $source,
            'RecordController must not use test_patient_uuid_01 as audit fallback — use null instead'
        );
    }

    public function test_opescare_config_has_system_provider_id(): void
    {
        $this->assertNotEmpty(
            config('opescare.system_provider_id'),
            'config/opescare.php must define system_provider_id'
        );
    }
}
