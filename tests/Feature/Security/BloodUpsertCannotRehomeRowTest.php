<?php

namespace Tests\Feature\Security;

use App\Models\BloodInventory;
use App\Models\Facility;
use App\Modules\Inventory\Services\BloodInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The staff blood-inventory form passed the whole request into upsertUnit(),
 * and `facility_id` is fillable: on the UPDATE path a body `facility_id` moved
 * the row onto another blood bank's shelf. Only stock fields are writable now.
 */
class BloodUpsertCannotRehomeRowTest extends TestCase
{
    use RefreshDatabase;

    public function test_body_facility_id_cannot_move_an_existing_row(): void
    {
        $mine  = Facility::factory()->create();
        $other = Facility::factory()->create();

        BloodInventory::create([
            'facility_id' => $mine->id, 'blood_group' => 'O+', 'component' => 'whole_blood',
            'available_units' => 3, 'last_stock_update' => now(),
        ]);

        app(BloodInventoryService::class)->upsertUnit($mine->id, [
            'facility_id'     => $other->id,
            'blood_group'     => 'O+',
            'component'       => 'whole_blood',
            'available_units' => 9,
        ]);

        $this->assertSame(0, BloodInventory::where('facility_id', $other->id)->count());
        $this->assertSame(9, BloodInventory::where('facility_id', $mine->id)->value('available_units'));
    }

    public function test_body_facility_id_is_ignored_on_create(): void
    {
        $mine  = Facility::factory()->create();
        $other = Facility::factory()->create();

        app(BloodInventoryService::class)->upsertUnit($mine->id, [
            'facility_id' => $other->id, 'blood_group' => 'A-', 'component' => 'plasma', 'available_units' => 2,
        ]);

        $this->assertSame(0, BloodInventory::where('facility_id', $other->id)->count());
        $this->assertSame(1, BloodInventory::where('facility_id', $mine->id)->count());
    }
}
