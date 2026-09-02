<?php
namespace Database\Factories;

use App\Models\ControlledSubstanceDispensing;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ControlledSubstanceDispensingFactory extends Factory {
    protected $model = ControlledSubstanceDispensing::class;

    public function definition(): array {
        return [
            'facility_id'          => Facility::factory(),
            'patient_id'           => Patient::factory(),
            'prescription_id'      => (string) Str::uuid(),
            'prescription_item_id' => (string) Str::uuid(),
            'drug_code'            => 'OXY10',
            'drug_name'            => 'Oxycodone HCl',
            'schedule'             => 'schedule_ii',
            'quantity_dispensed'   => 10.00,
            'unit'                 => 'tablet',
            'dispensed_by'         => User::factory(),
            'dispensed_at'         => now(),
            'witness_id'           => null,
            'witness_confirmed_at' => null,
            'stock_balance_before' => 100.00,
            'stock_balance_after'  => 90.00,
            'lot_number'           => null,
            'expiry_date'          => null,
            'notes'                => null,
        ];
    }
}
