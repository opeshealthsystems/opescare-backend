<?php
namespace Tests\Feature\Billing;

use App\Services\Billing\CopayCalculationService;
use Tests\TestCase;

class CopayCalculationTest extends TestCase
{
    public function test_fixed_copay_calculated_correctly(): void
    {
        $service = new CopayCalculationService();
        $result  = $service->calculateRaw(
            billedAmount: 150000,
            insurancePct: 80,
            copayType:    'fixed',
            copayValue:   5000,
        );

        $this->assertEquals(5000, $result['patient_copay']);
        $this->assertEquals(145000, $result['insurance_portion']);
    }

    public function test_percentage_copay_calculated_correctly(): void
    {
        $service = new CopayCalculationService();
        $result  = $service->calculateRaw(
            billedAmount: 200000,
            insurancePct: 80,
            copayType:    'percentage',
            copayValue:   20,
        );

        $this->assertEquals(40000, $result['patient_copay']);
        $this->assertEquals(160000, $result['insurance_portion']);
    }

    public function test_deductible_reduces_insurance_payout(): void
    {
        $service = new CopayCalculationService();
        $result  = $service->calculateRaw(
            billedAmount: 100000,
            insurancePct: 80,
            copayType:    'fixed',
            copayValue:   0,
            deductible:   20000,
        );

        // Deductible 20000, remainder 80000, insurance=64000, patient=36000
        $this->assertEquals(36000, $result['patient_copay']);
        $this->assertEquals(64000, $result['insurance_portion']);
    }
}
