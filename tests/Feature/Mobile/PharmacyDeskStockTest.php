<?php

namespace Tests\Feature\Mobile;

use App\Models\StockMovement;
use Illuminate\Support\Str;

/**
 * The pharmacy desk's stock list and ledger:
 * /api/mobile/pharmacy-desk/stock and /stock/{id}/adjust.
 */
class PharmacyDeskStockTest extends PharmacyDeskTestCase
{
    public function test_the_stock_list_is_this_pharmacys_listing_searchable_by_catalogue_name_and_filterable_to_low_lines(): void
    {
        $this->stock($this->otherListing, $this->paracetamol, 99, 900); // the rival's line

        $rows = $this->desk('pharmacist', 'GET', '/stock')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [[
                'id', 'medicine_id', 'name', 'strength', 'form', 'quantity', 'reorder_level', 'unit_price', 'currency', 'updated_at',
            ]]])
            ->json('data');

        $byName = collect($rows)->keyBy('name');
        $this->assertSame($this->paracetamolStock->id, $byName['Paracetamol 500mg Tablet']['id']);
        $this->assertSame(20, $byName['Paracetamol 500mg Tablet']['quantity']);
        $this->assertSame(5, $byName['Paracetamol 500mg Tablet']['reorder_level']);
        $this->assertSame(1500.0, (float) $byName['Paracetamol 500mg Tablet']['unit_price']);
        $this->assertSame('XAF', $byName['Paracetamol 500mg Tablet']['currency']);
        $this->assertSame(4, $byName['Amoxicillin 500mg Capsule']['quantity']);
        $this->assertTrue($byName['Amoxicillin 500mg Capsule']['is_low']);
        $this->assertFalse($byName['Paracetamol 500mg Tablet']['is_low']);

        $this->desk('pharmacist', 'GET', '/stock?q=para')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Paracetamol 500mg Tablet');

        $this->desk('pharmacist', 'GET', '/stock?low=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->amoxicillinStock->id);

        $this->desk('rival', 'GET', '/stock')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_adjusting_stock_changes_the_count_recomputes_the_status_and_writes_one_ledger_row(): void
    {
        $this->assertSame(0, StockMovement::count());

        $this->desk('pharmacist', 'POST', "/stock/{$this->paracetamolStock->id}/adjust", [
            'delta'  => -8,
            'reason' => 'Dispensed against paper scripts this morning',
        ])
            ->assertOk()
            ->assertJsonPath('data.id', $this->paracetamolStock->id)
            ->assertJsonPath('data.quantity', 12)
            ->assertJsonPath('data.stock_status', 'in_stock');

        $movement = StockMovement::sole();
        $this->assertSame($this->facility->id, $movement->facility_id);
        $this->assertNull($movement->inventory_item_id);
        $this->assertSame('adjustment', $movement->movement_type);
        $this->assertSame(-8, $movement->quantity);
        $this->assertSame('medicine_pharmacy_stock', $movement->reference_type);
        $this->assertSame($this->paracetamolStock->id, $movement->reference_id);
        $this->assertSame($this->users['pharmacist']->id, $movement->performed_by);
        $this->assertStringContainsString('Dispensed against paper scripts this morning', $movement->reason);
        $this->assertNotNull($movement->performed_at);

        // The public listing row is what changed — stamped as a real report.
        $this->paracetamolStock->refresh();
        $this->assertSame(12, $this->paracetamolStock->packs_available);
        $this->assertSame('portal', $this->paracetamolStock->source_system);
        $this->assertDatabaseHas('facility_update_audits', [
            'facility_id'   => $this->listing->id,
            'actor_id'      => $this->users['pharmacist']->id,
            'field_changed' => 'medicine_pharmacy_stock.updated',
        ]);

        // Down to the reorder level → low; down to nothing → out; back up → in stock.
        $this->desk('pharmacist', 'POST', "/stock/{$this->paracetamolStock->id}/adjust", ['delta' => -7, 'reason' => 'count correction'])
            ->assertOk()->assertJsonPath('data.quantity', 5)->assertJsonPath('data.stock_status', 'low_stock');
        $this->desk('pharmacist', 'POST', "/stock/{$this->paracetamolStock->id}/adjust", ['delta' => -5, 'reason' => 'last packs sold'])
            ->assertOk()->assertJsonPath('data.quantity', 0)->assertJsonPath('data.stock_status', 'out_of_stock');
        $this->desk('pharmacist', 'POST', "/stock/{$this->paracetamolStock->id}/adjust", ['delta' => 30, 'reason' => 'delivery received'])
            ->assertOk()->assertJsonPath('data.quantity', 30)->assertJsonPath('data.stock_status', 'in_stock');

        $this->assertSame(4, StockMovement::count());
        $this->desk('pharmacist', 'GET', '/home')->assertOk()->assertJsonPath('data.counts.low_stock', 1);
    }

    public function test_stock_never_goes_negative_and_a_refused_adjustment_leaves_no_ledger_row(): void
    {
        $this->desk('pharmacist', 'POST', "/stock/{$this->amoxicillinStock->id}/adjust", [
            'delta'  => -5,
            'reason' => 'typo — we only have four',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'stock_negative')
            ->assertJsonStructure(['message', 'error_code']);

        $this->assertSame(4, $this->amoxicillinStock->fresh()->packs_available);
        $this->assertSame(0, StockMovement::count());
        $this->assertDatabaseMissing('facility_update_audits', ['facility_id' => $this->listing->id]);

        // Exactly to zero is fine.
        $this->desk('pharmacist', 'POST', "/stock/{$this->amoxicillinStock->id}/adjust", ['delta' => -4, 'reason' => 'sold out'])
            ->assertOk()
            ->assertJsonPath('data.quantity', 0);
    }

    public function test_an_adjustment_needs_a_non_zero_delta_and_a_reason(): void
    {
        $this->desk('pharmacist', 'POST', "/stock/{$this->paracetamolStock->id}/adjust", ['delta' => 0, 'reason' => 'nothing'])
            ->assertStatus(422)->assertJsonValidationErrors(['delta']);
        $this->desk('pharmacist', 'POST', "/stock/{$this->paracetamolStock->id}/adjust", ['delta' => 3])
            ->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->desk('pharmacist', 'POST', "/stock/{$this->paracetamolStock->id}/adjust", ['delta' => 'three', 'reason' => 'found'])
            ->assertStatus(422)->assertJsonValidationErrors(['delta']);

        $this->assertSame(20, $this->paracetamolStock->fresh()->packs_available);
        $this->assertSame(0, StockMovement::count());
    }

    public function test_another_pharmacys_line_is_not_on_this_list(): void
    {
        $rivals = $this->stock($this->otherListing, $this->paracetamol, 99, 900);

        $this->desk('pharmacist', 'POST', "/stock/{$rivals->id}/adjust", ['delta' => -1, 'reason' => 'not mine'])
            ->assertNotFound()
            ->assertJsonPath('error_code', 'stock_not_found');
        $this->assertSame(99, $rivals->fresh()->packs_available);

        $this->desk('pharmacist', 'POST', '/stock/' . Str::uuid() . '/adjust', ['delta' => -1, 'reason' => 'ghost'])->assertNotFound();
        $this->desk('pharmacist', 'POST', '/stock/nope/adjust', ['delta' => -1, 'reason' => 'ghost'])->assertNotFound();
    }

    public function test_the_stock_only_role_keeps_stock_but_may_not_dispense_and_the_front_desk_may_do_neither(): void
    {
        // medicine_stock holds pharmacy_stock, not dispense.
        $this->desk('stockkeeper', 'GET', '/stock')->assertOk();
        $this->desk('stockkeeper', 'POST', "/stock/{$this->paracetamolStock->id}/adjust", ['delta' => 2, 'reason' => 'found two packs'])
            ->assertOk()
            ->assertJsonPath('data.quantity', 22);
        $this->assertSame($this->users['stockkeeper']->id, StockMovement::sole()->performed_by);

        $this->desk('stockkeeper', 'GET', '/home')
            ->assertForbidden()
            ->assertJsonPath('error_code', 'role_forbidden')
            ->assertJsonPath('required', ['dispense']);

        foreach (['doctor', 'receptionist'] as $role) {
            $this->desk($role, 'GET', '/stock')
                ->assertForbidden()
                ->assertJsonPath('error_code', 'role_forbidden')
                ->assertJsonPath('required', ['pharmacy_stock']);
            $this->desk($role, 'POST', "/stock/{$this->paracetamolStock->id}/adjust", ['delta' => -1, 'reason' => 'nope'])
                ->assertForbidden();
        }

        $this->assertSame(22, $this->paracetamolStock->fresh()->packs_available);
        $this->assertSame(1, StockMovement::count());
    }

    public function test_a_token_without_a_facility_is_refused_with_the_one_shape(): void
    {
        \Illuminate\Support\Facades\DB::table('provider_access_tokens')
            ->where('user_id', $this->users['pharmacist']->id)
            ->update(['facility_id' => null]);

        $response = $this->desk('pharmacist', 'GET', '/stock')
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'facility_required')
            ->assertJsonPath('message', __('pharmacy_api.errors.facility_required'));

        $keys = array_keys($response->json());
        sort($keys);
        $this->assertSame(['error_code', 'message'], $keys);
    }
}
