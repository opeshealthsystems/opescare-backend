<?php

namespace Tests\Feature\MedicalId;

use Tests\TestCase;
use App\Services\Identity\HealthIdGeneratorService;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HealthIdGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected HealthIdGeneratorService $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new HealthIdGeneratorService();
    }

    public function test_generated_id_starts_with_country_code_and_not_opes()
    {
        $id = $this->generator->generate('ZA');
        $this->assertStringStartsWith('ZA-HID-', $id);
        $this->assertStringNotContainsString('OPES', $id);
    }

    public function test_id_uses_safe_alphabet_and_matches_pattern()
    {
        $id = $this->generator->generate('CM');
        $this->assertTrue($this->generator->isValid($id));
    }

    public function test_id_is_not_sequential()
    {
        $id1 = $this->generator->generate('CM');
        $id2 = $this->generator->generate('CM');
        
        $this->assertNotEquals($id1, $id2);
        
        // Ensure they aren't off by just one character in a sequential manner
        $blocks1 = explode('-', $id1);
        $blocks2 = explode('-', $id2);
        
        $this->assertNotEquals($blocks1[2], $blocks2[2]);
    }

    public function test_invalid_checksum_is_rejected()
    {
        $id = $this->generator->generate('CM');
        // Mutate the checkblock
        $mutatedId = substr($id, 0, -1) . ($id[strlen($id)-1] === 'A' ? 'B' : 'A');
        
        $this->assertFalse($this->generator->isValid($mutatedId));
    }
}
