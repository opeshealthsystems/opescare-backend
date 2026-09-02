<?php

namespace Tests\Feature;

use App\Models\CriticalValueAcknowledgement;
use App\Models\ImagingOrder;
use App\Models\ReferenceRange;
use App\Services\Lab\CriticalValueService;
use App\Services\Lab\ReferenceRangeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1ATest extends TestCase
{
    use RefreshDatabase;

    public function test_imaging_order_model_has_correct_status_colors(): void
    {
        $order = new ImagingOrder(['status' => 'completed', 'modality' => 'ct']);
        $this->assertEquals('success', $order->statusColor());
        $this->assertEquals('CT Scan', $order->modalityLabel());
    }

    public function test_reference_range_evaluate_returns_correct_flag(): void
    {
        $range = new ReferenceRange([
            'normal_low'   => 4.0,
            'normal_high'  => 10.0,
            'critical_low' => 2.0,
            'critical_high'=> 15.0,
            'unit'         => 'g/dL',
        ]);

        $this->assertEquals('normal',        $range->evaluate(7.0));
        $this->assertEquals('high',          $range->evaluate(12.0));
        $this->assertEquals('critical_high', $range->evaluate(16.0));
        $this->assertEquals('low',           $range->evaluate(3.0));
        $this->assertEquals('critical_low',  $range->evaluate(1.5));
    }

    public function test_reference_range_flag_code(): void
    {
        $range = new ReferenceRange([
            'normal_low'   => 4.0,
            'normal_high'  => 10.0,
            'critical_low' => 2.0,
            'critical_high'=> 15.0,
            'unit'         => 'g/dL',
        ]);

        $this->assertNull($range->flagCode(7.0));
        $this->assertEquals('H',  $range->flagCode(12.0));
        $this->assertEquals('HH', $range->flagCode(16.0));
        $this->assertEquals('L',  $range->flagCode(3.0));
        $this->assertEquals('LL', $range->flagCode(1.5));
    }

    public function test_reference_range_service_upsert_and_lookup(): void
    {
        $service = app(ReferenceRangeService::class);

        $service->upsert([
            'test_code'    => 'HGB',
            'test_name'    => 'Haemoglobin',
            'age_group'    => 'adult',
            'sex'          => 'all',
            'unit'         => 'g/dL',
            'normal_low'   => 12.0,
            'normal_high'  => 17.5,
            'critical_low' => 7.0,
            'critical_high'=> 20.0,
        ]);

        $found = $service->lookup('HGB');
        $this->assertNotNull($found);
        $this->assertEquals('HGB', $found->test_code);

        $result = $service->apply($found, 5.0);
        $this->assertEquals('LL', $result['flag']);
        $this->assertTrue($result['is_critical']);
    }
}
