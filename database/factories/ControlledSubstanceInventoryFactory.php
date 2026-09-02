<?php
namespace Database\Factories;

use App\Models\ControlledSubstanceInventory;
use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

class ControlledSubstanceInventoryFactory extends Factory {
    protected $model = ControlledSubstanceInventory::class;

    public function definition(): array {
        return [
            'facility_id'        => Facility::factory(),
            'drug_code'          => 'OXY10',
            'drug_name'          => 'Oxycodone HCl',
            'schedule'           => 'schedule_ii',
            'current_balance'    => 100.00,
            'unit'               => 'tablet',
            'last_reconciled_at' => null,
            'last_reconciled_by' => null,
        ];
    }
}
