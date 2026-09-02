<?php
namespace Tests\Feature\Lab;

use App\Models\LabReferenceRange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabReferenceRangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_reference_range(): void
    {
        $range = LabReferenceRange::create([
            'loinc_code'   => '2823-3',
            'test_name'    => 'Serum Potassium',
            'unit'         => 'mmol/L',
            'gender'       => 'all',
            'age_min'      => 18,
            'age_max'      => 120,
            'normal_low'   => 3.5,
            'normal_high'  => 5.0,
            'critical_low' => 3.0,
            'critical_high'=> 6.5,
        ]);

        $this->assertEquals('2823-3', $range->loinc_code);
        $this->assertEquals(3.5, (float) $range->normal_low);
    }

    public function test_lookup_range_for_demographic(): void
    {
        LabReferenceRange::create([
            'loinc_code'   => '718-7',
            'test_name'    => 'Haemoglobin',
            'unit'         => 'g/dL',
            'gender'       => 'female',
            'age_min'      => 18,
            'age_max'      => 120,
            'normal_low'   => 12.0,
            'normal_high'  => 16.0,
            'critical_low' => 7.0,
        ]);

        $range = LabReferenceRange::forLoinc('718-7', 'female', 30)->first();
        $this->assertNotNull($range);
        $this->assertEquals(12.0, (float) $range->normal_low);
    }
}
