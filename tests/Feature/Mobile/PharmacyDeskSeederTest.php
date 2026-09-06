<?php

namespace Tests\Feature\Mobile;

use App\Enums\PharmacyStockStatus;
use App\Models\FacilityRoleAssignment;
use App\Models\MedicinePharmacyStock;
use App\Models\MedicineReservation;
use App\Models\Patient;
use App\Models\PharmacyRoute;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DemoPharmacyDeskSeeder;

/**
 * DemoPharmacyDeskSeeder puts work on the demo pharmacist's counter, once,
 * however many times it runs — and only on demo records.
 */
class PharmacyDeskSeederTest extends PharmacyDeskTestCase
{
    private Patient $reviewer;
    private Patient $guardian;
    private User $demoPharmacist;

    protected function setUp(): void
    {
        parent::setUp();

        // The demo pharmacist, posted at our facility through an assignment.
        $role = Role::firstOrCreate(['name' => 'pharmacist'], ['description' => 'Pharmacist']);
        $this->demoPharmacist = User::factory()->create([
            'email'               => DemoPharmacyDeskSeeder::PHARMACIST_EMAIL,
            'is_demo'             => true,
            'role_id'             => $role->id,
            'primary_facility_id' => $this->otherFacility->id, // the assignment must win over this
        ]);
        FacilityRoleAssignment::create([
            'user_id' => $this->demoPharmacist->id, 'facility_id' => $this->facility->id, 'role_id' => $role->id,
            'is_active' => true, 'assigned_at' => now(),
        ]);

        // The two demo patients, with the reviewer holding two open scripts.
        // is_demo is deliberately not mass-assignable on Patient — forceFill it.
        $this->reviewer = Patient::create([
            'health_id' => 'CM-HID-DEMO-REVIEWER-01', 'first_name' => 'Demo', 'last_name' => 'Reviewer',
            'sex' => 'female', 'date_of_birth' => '1990-01-01', 'email' => DemoPharmacyDeskSeeder::REVIEWER_EMAIL,
        ]);
        $this->reviewer->forceFill(['is_demo' => true])->save();

        $this->guardian = Patient::create([
            'health_id' => 'CM-HID-DEMO-GUARDIAN-01', 'first_name' => 'Demo', 'last_name' => 'Guardian',
            'sex' => 'female', 'date_of_birth' => '1985-01-01', 'email' => DemoPharmacyDeskSeeder::GUARDIAN_EMAIL,
        ]);
        $this->guardian->forceFill(['is_demo' => true])->save();

        foreach ([1, 2] as $n) {
            $rx = Prescription::create([
                'patient_id' => $this->reviewer->id, 'facility_id' => $this->hospital->id, 'prescribed_by' => $this->users['doctor']->id,
                'status' => 'active', 'prescribed_at' => now()->subDays($n), 'expires_at' => now()->addDays(20),
            ]);
            PrescriptionItem::create(['prescription_id' => $rx->id, 'drug_name' => 'Metformin 500 mg', 'dose' => '1 tablet', 'quantity' => 60, 'status' => 'pending']);
        }
    }

    private function runSeeder(): void
    {
        $this->seed(DemoPharmacyDeskSeeder::class);
    }

    private function counts(): array
    {
        return [
            'routed'       => Prescription::whereNotNull('pharmacy_route_id')->count(),
            'routes'       => PharmacyRoute::count(),
            'reservations' => MedicineReservation::count(),
            'low'          => MedicinePharmacyStock::where('care_facility_id', $this->listing->id)->where('stock_status', PharmacyStockStatus::LowStock->value)->count(),
            'stock_rows'   => MedicinePharmacyStock::count(),
        ];
    }

    public function test_it_puts_one_script_two_reservations_and_a_low_line_on_the_demo_counter_and_is_idempotent(): void
    {
        $this->runSeeder();
        $first = $this->counts();

        // One of the reviewer's scripts, routed to the facility the pharmacist is POSTED at (the assignment, not primary_facility_id).
        $this->assertSame(1, $first['routed']);
        $routed = Prescription::whereNotNull('pharmacy_route_id')->first();
        $this->assertSame($this->reviewer->id, $routed->patient_id);
        $this->assertSame('pending', $routed->pharmacy_routing_status);
        $this->assertSame($this->facility->id, PharmacyRoute::find($routed->pharmacy_route_id)->facility_id);
        $this->assertSame('Pharmacie du Test', $routed->dispensing_pharmacy_name);

        // A pending pickup for the reviewer and an accepted delivery for the guardian, both at our listing.
        $this->assertSame(2, $first['reservations']);
        $pickup   = MedicineReservation::where('patient_id', $this->reviewer->id)->sole();
        $delivery = MedicineReservation::where('patient_id', $this->guardian->id)->sole();
        $this->assertSame($this->listing->id, $pickup->care_facility_id);
        $this->assertSame('pickup', $pickup->fulfilment_mode);
        $this->assertSame('pending', $pickup->deskStatus());
        $this->assertSame($this->listing->id, $delivery->care_facility_id);
        $this->assertSame('delivery', $delivery->fulfilment_mode);
        $this->assertSame('accepted', $delivery->deskStatus());
        $this->assertNotNull($delivery->delivery_address);
        $this->assertNotNull($delivery->delivery_phone);
        $this->assertMatchesRegularExpression('/^OC-RX-[2-9A-HJ-NP-Z]{8}$/', $pickup->reference);

        // One low line — the existing stock was used, nothing invented.
        $this->assertGreaterThanOrEqual(1, $first['low']);
        $this->assertSame(2, $first['stock_rows'], 'the listing already had two lines; none were added');

        // The pharmacist's desk sees all of it.
        $this->desk('pharmacist', 'GET', '/home')
            ->assertOk()
            ->assertJsonPath('data.counts.incoming', 1)
            ->assertJsonPath('data.counts.reservations_pending', 1)
            ->assertJsonPath('data.counts.deliveries_open', 1);
        $this->assertGreaterThanOrEqual(1, $this->desk('pharmacist', 'GET', '/home')->json('data.counts.low_stock'));

        // Run it twice more: nothing piles up.
        $this->runSeeder();
        $this->runSeeder();
        $this->assertSame($first, $this->counts());
        $this->assertSame($routed->id, Prescription::whereNotNull('pharmacy_route_id')->sole()->id);
        $this->assertSame($pickup->reference, MedicineReservation::where('patient_id', $this->reviewer->id)->sole()->reference);
    }

    public function test_it_refuses_to_touch_a_real_persons_record(): void
    {
        $this->reviewer->forceFill(['is_demo' => false])->save();
        $this->guardian->forceFill(['is_demo' => false])->save();

        $this->runSeeder();

        $this->assertSame(0, Prescription::whereNotNull('pharmacy_route_id')->count());
        $this->assertSame(0, MedicineReservation::count());
    }

    public function test_it_does_nothing_without_a_demo_pharmacist(): void
    {
        $this->demoPharmacist->forceFill(['is_demo' => false])->save();

        $this->runSeeder();

        $this->assertSame(0, Prescription::whereNotNull('pharmacy_route_id')->count());
        $this->assertSame(0, MedicineReservation::count());
        $this->assertSame(0, PharmacyRoute::count());
    }
}
