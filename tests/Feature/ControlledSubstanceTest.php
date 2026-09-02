<?php
namespace Tests\Feature;

use App\Models\ControlledSubstanceDispensing;
use App\Models\ControlledSubstanceInventory;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControlledSubstanceTest extends TestCase {
    use RefreshDatabase;

    private array $headers = ['X-Client-ID' => 'test_client_id', 'X-Client-Secret' => 'test_client_secret'];
    private User $user;
    private User $witness;
    private Facility $facility;
    private Patient $patient;

    protected function setUp(): void {
        parent::setUp();
        $this->user     = User::factory()->create();
        $this->witness  = User::factory()->create();
        $this->facility = Facility::factory()->create();
        $this->patient  = Patient::factory()->create();
    }

    private function seedInventory(string $drugCode = 'OXY10', float $balance = 100.0): ControlledSubstanceInventory {
        return ControlledSubstanceInventory::factory()->create([
            'facility_id'     => $this->facility->id,
            'drug_code'       => $drugCode,
            'drug_name'       => 'Oxycodone HCl',
            'schedule'        => 'schedule_ii',
            'current_balance' => $balance,
            'unit'            => 'tablet',
        ]);
    }

    private function dispensePayload(array $overrides = []): array {
        return array_merge([
            'facility_id'          => $this->facility->id,
            'patient_id'           => $this->patient->id,
            'prescription_id'      => fake()->uuid(),
            'prescription_item_id' => fake()->uuid(),
            'drug_code'            => 'OXY10',
            'drug_name'            => 'Oxycodone HCl',
            'schedule'             => 'schedule_ii',
            'quantity_dispensed'   => 10,
            'unit'                 => 'tablet',
            'dispensed_by'         => $this->user->id,
            'witness_id'           => $this->witness->id,
        ], $overrides);
    }

    public function test_dispense_schedule_ii_requires_witness(): void {
        $this->seedInventory();
        $payload = $this->dispensePayload(['witness_id' => null]);
        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v1/pharmacy/controlled-substances/dispense', $payload);
        $response->assertStatus(500);
    }

    public function test_dispense_schedule_ii_with_witness_succeeds(): void {
        $this->seedInventory('OXY10', 100.0);
        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v1/pharmacy/controlled-substances/dispense', $this->dispensePayload());
        $response->assertCreated();
        $this->assertEquals(10.0, (float) $response->json('data.quantity_dispensed'));
        $this->assertEquals(90.0, (float) $response->json('data.stock_balance_after'));
        $this->assertDatabaseHas('controlled_substance_inventories', [
            'facility_id'     => $this->facility->id,
            'drug_code'       => 'OXY10',
            'current_balance' => 90.0,
        ]);
    }

    public function test_confirm_witness_sets_confirmed_at(): void {
        $this->seedInventory();
        $dispensing = ControlledSubstanceDispensing::factory()->create([
            'facility_id'          => $this->facility->id,
            'patient_id'           => $this->patient->id,
            'prescription_id'      => fake()->uuid(),
            'prescription_item_id' => fake()->uuid(),
            'drug_code'            => 'OXY10',
            'drug_name'            => 'Oxycodone HCl',
            'schedule'             => 'schedule_ii',
            'quantity_dispensed'   => 5,
            'unit'                 => 'tablet',
            'dispensed_by'         => $this->user->id,
            'witness_id'           => null,
            'witness_confirmed_at' => null,
            'stock_balance_before' => 100.0,
            'stock_balance_after'  => 95.0,
            'dispensed_at'         => now(),
        ]);
        $response = $this->withHeaders($this->headers)
            ->postJson("/api/v1/pharmacy/controlled-substances/{$dispensing->id}/witness", [
                'witness_id' => $this->witness->id,
            ]);
        $response->assertOk();
        $this->assertNotNull($response->json('data.witness_confirmed_at'));
        $this->assertEquals($this->witness->id, $response->json('data.witness_id'));
    }

    public function test_inventory_balance_updated_after_dispense(): void {
        $this->seedInventory('MOR10', 50.0);
        $payload = $this->dispensePayload([
            'drug_code'          => 'MOR10',
            'drug_name'          => 'Morphine Sulfate',
            'quantity_dispensed' => 5,
        ]);
        $this->withHeaders($this->headers)
            ->postJson('/api/v1/pharmacy/controlled-substances/dispense', $payload)
            ->assertCreated();
        $this->assertDatabaseHas('controlled_substance_inventories', [
            'facility_id'     => $this->facility->id,
            'drug_code'       => 'MOR10',
            'current_balance' => 45.0,
        ]);
    }

    public function test_reconcile_inventory_updates_balance(): void {
        $this->seedInventory('OXY10', 80.0);
        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v1/pharmacy/controlled-substances/reconcile', [
                'facility_id'    => $this->facility->id,
                'drug_code'      => 'OXY10',
                'actual_balance' => 78.5,
                'reconciler_id'  => $this->user->id,
            ]);
        $response->assertOk();
        $this->assertEquals(78.5, (float) $response->json('data.current_balance'));
        $this->assertNotNull($response->json('data.last_reconciled_at'));
    }
}
