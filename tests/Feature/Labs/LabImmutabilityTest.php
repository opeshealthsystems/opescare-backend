<?php

namespace Tests\Feature\Labs;

use App\Models\Facility;
use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A released laboratory result is a clinical event: corrected, never erased.
 *
 * The companion of tests/Feature/Prescriptions/PrescribingChainTest.php's
 * immutability block, for the two lab models. Both directions are asserted on
 * purpose — a guard that only ever proves the refusal is one broken amend away
 * from taking a working bench flow down with it.
 */
class LabImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility = Facility::create([
            'name'   => 'Laboratoire Central de Yaoundé',
            'type'   => 'laboratory',
            'status' => 'active',
        ]);

        $this->patient = Patient::create([
            'health_id'     => 'OC-TST-4200-0001-01',
            'first_name'    => 'Bertrand',
            'last_name'     => 'Etoundi',
            'sex'           => 'male',
            'date_of_birth' => '1984-02-19',
            'facility_id'   => $this->facility->id,
            'is_demo'       => false,
        ]);
    }

    private function order(string $status = 'resulted'): LabOrder
    {
        return LabOrder::create([
            'patient_id'  => $this->patient->id,
            'facility_id' => $this->facility->id,
            'ordered_by'  => null,
            'test_name'   => 'Full Blood Count',
            'test_code'   => 'FBC',
            'urgency'     => 'routine',
            'status'      => $status,
            'ordered_at'  => now()->subDay(),
            'resulted_at' => $status === 'resulted' ? now()->subHours(2) : null,
        ]);
    }

    private function analyte(LabOrder $order, string $flag = 'HH'): LabResult
    {
        return LabResult::create([
            'lab_order_id'    => $order->id,
            'patient_id'      => $this->patient->id,
            'parameter_name'  => 'Potassium',
            'value'           => '6.9',
            'unit'            => 'mmol/L',
            'reference_range' => '3.5–5.1',
            'flag'            => $flag,
            'resulted_at'     => now()->subHours(2),
        ]);
    }

    // ── Refusals ──────────────────────────────────────────────────────────

    public function test_a_lab_result_cannot_be_hard_deleted(): void
    {
        $result = $this->analyte($this->order());

        try {
            $result->delete();
            $this->fail('Deleting a lab result must be refused.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('cannot be deleted', $e->getMessage());
        }

        $this->assertDatabaseHas('lab_results', ['id' => $result->id]);
    }

    public function test_a_lab_order_cannot_be_hard_deleted(): void
    {
        $order  = $this->order();
        $result = $this->analyte($order);

        try {
            $order->delete();
            $this->fail('Deleting a lab order must be refused.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('cannot be deleted', $e->getMessage());
        }

        // The order surviving is the point: lab_results.lab_order_id is
        // ON DELETE CASCADE, so the released analytes would have gone with it.
        $this->assertDatabaseHas('lab_orders', ['id' => $order->id]);
        $this->assertDatabaseHas('lab_results', ['id' => $result->id]);
    }

    public function test_a_lab_result_cannot_be_moved_to_another_patient(): void
    {
        $result = $this->analyte($this->order());

        $other = Patient::create([
            'health_id'     => 'OC-TST-4200-0002-01',
            'first_name'    => 'Aline',
            'last_name'     => 'Mbida',
            'sex'           => 'female',
            'date_of_birth' => '1996-08-02',
            'facility_id'   => $this->facility->id,
            'is_demo'       => false,
        ]);

        try {
            $result->patient_id = $other->id;
            $result->save();
            $this->fail('Re-assigning the patient on a lab result must be refused.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('immutable', $e->getMessage());
        }

        $this->assertSame($this->patient->id, $result->fresh()->patient_id);
    }

    public function test_a_lab_result_cannot_be_relabelled_as_a_different_analyte(): void
    {
        $result = $this->analyte($this->order());

        try {
            $result->parameter_name = 'Sodium';
            $result->save();
            $this->fail('Renaming the analyte on a lab result must be refused.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('immutable', $e->getMessage());
        }

        $this->assertSame('Potassium', $result->fresh()->parameter_name);
    }

    public function test_a_lab_order_cannot_be_relabelled_as_a_different_test(): void
    {
        $order = $this->order();

        try {
            $order->test_name = 'Renal Panel';
            $order->save();
            $this->fail('Renaming the test on a lab order must be refused.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('immutable', $e->getMessage());
        }

        $this->assertSame('Full Blood Count', $order->fresh()->test_name);
    }

    public function test_a_released_lab_order_cannot_be_walked_back(): void
    {
        $order = $this->order('resulted');

        try {
            $order->status = 'pending';
            $order->save();
            $this->fail('Un-releasing a resulted lab order must be refused.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('cannot be walked back', $e->getMessage());
        }

        $this->assertSame('resulted', $order->fresh()->status);
    }

    public function test_a_cancelled_lab_order_cannot_be_reopened(): void
    {
        $order = $this->order('cancelled');

        try {
            $order->status = 'processing';
            $order->save();
            $this->fail('Reopening a cancelled lab order must be refused.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('cannot transition', $e->getMessage());
        }

        $this->assertSame('cancelled', $order->fresh()->status);
    }

    // ── What must keep working ────────────────────────────────────────────

    public function test_a_bench_can_amend_a_released_result(): void
    {
        $result = $this->analyte($this->order());

        $result->amend(
            ['value' => '4.4', 'flag' => null],
            'Haemolysed sample; repeat on a fresh draw.'
        );

        $fresh = $result->fresh();

        $this->assertSame('4.4', $fresh->value);
        $this->assertNull($fresh->flag);
        $this->assertStringContainsString('Haemolysed sample', (string) $fresh->notes);
        $this->assertFalse($fresh->isAbnormal());
    }

    public function test_a_plain_save_of_a_corrected_value_still_works(): void
    {
        // The bench does not have to go through amend() — the sanctioned door
        // is not the only door, and the six subsystems that read these models
        // must not be broken by a guard that refuses ordinary correction.
        $result = $this->analyte($this->order());

        $result->value           = '5.0';
        $result->flag            = null;
        $result->reference_range = '3.5–5.1';
        $result->notes           = 'Corrected after repeat assay.';
        $result->resulted_at     = now();
        $result->save();

        $this->assertSame('5.0', $result->fresh()->value);
    }

    public function test_amend_refuses_to_touch_an_identity_column(): void
    {
        $result = $this->analyte($this->order());

        $this->expectException(\LogicException::class);
        $result->amend(['patient_id' => $this->patient->id], 'nice try');
    }

    public function test_amend_requires_a_documented_reason(): void
    {
        $result = $this->analyte($this->order());

        $this->expectException(\InvalidArgumentException::class);
        $result->amend(['value' => '4.4'], '   ');
    }

    public function test_an_order_moves_through_its_lifecycle_and_can_be_retracted(): void
    {
        $order = $this->order('pending');

        foreach (['collected', 'processing', 'resulted'] as $status) {
            $order->status = $status;
            $order->save();
            $this->assertSame($status, $order->fresh()->status);
        }

        // A released panel can still be taken back — that is the void path the
        // walk-back refusal points callers at.
        $order->status = 'voided';
        $order->save();

        $this->assertSame('voided', $order->fresh()->status);
        $this->assertTrue($order->fresh()->isTerminal());
    }

    public function test_the_seeders_quiet_re_run_path_is_untouched(): void
    {
        // DemoLabResultsSeeder re-runs with updateQuietly() so it never fires
        // result-created listeners. Quiet writes fire no events, so the guard
        // must not be what makes a re-seed fail.
        $order = $this->order();

        $order->updateQuietly(['notes' => 'Re-seeded.', 'status' => 'resulted']);

        $this->assertSame('Re-seeded.', $order->fresh()->notes);
    }
}
