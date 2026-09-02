<?php
namespace Tests\Feature\Security;

use Tests\TestCase;

class RecordControllerHardcodedDataTest extends TestCase
{
    private string $source;

    protected function setUp(): void
    {
        parent::setUp();
        $this->source = file_get_contents(
            app_path('Http/Controllers/Api/V1/Connect/RecordController.php')
        );
    }

    public function test_no_hardcoded_health_id(): void
    {
        $this->assertStringNotContainsString('OC-CMR-7KQ9-MP42-X8D1', $this->source,
            'RecordController must not contain hardcoded health IDs');
    }

    public function test_no_hardcoded_password(): void
    {
        $this->assertStringNotContainsString("bcrypt('password')", $this->source,
            'RecordController must not contain hardcoded passwords');
    }

    public function test_no_hardcoded_clinical_data(): void
    {
        $forbidden = ['Penicillin', 'Amoxicillin', 'Mary Doe', 'O Positive', '1990-04-12'];
        foreach ($forbidden as $term) {
            $this->assertStringNotContainsString($term, $this->source,
                "RecordController must not contain hardcoded clinical data: '{$term}'");
        }
    }
}
