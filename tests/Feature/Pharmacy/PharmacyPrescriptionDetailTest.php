<?php

namespace Tests\Feature\Pharmacy;

use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Patient;
use App\Models\PharmacyRoute;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The web counter's prescription detail page: /pharmacy/prescriptions/{id}.
 *
 * Scope is PharmacyDeskService::find — a script issued here or routed here
 * (or not routed anywhere) renders; one routed to another pharmacy is a 404,
 * the same answer as a script that does not exist.
 */
class PharmacyPrescriptionDetailTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);

        $this->facility = Facility::factory()->create(['type' => 'pharmacy', 'name' => 'Pharmacie du Marché']);
    }

    private function staff(string $roleName, ?Facility $facility = null): User
    {
        $facility ??= $this->facility;
        $role = Role::where('name', $roleName)->firstOrFail();

        $user = User::factory()->create([
            'role_id'             => $role->id,
            'primary_facility_id' => $facility->id,
            'status'              => 'active',
        ]);

        FacilityRoleAssignment::create([
            'user_id'     => $user->id,
            'facility_id' => $facility->id,
            'role_id'     => $role->id,
            'is_active'   => true,
            'assigned_at' => now(),
        ]);

        return $user;
    }

    private function actingAsStaff(User $user, ?Facility $facility = null): static
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => ($facility ?? $this->facility)->id]);
    }

    private function prescription(array $overrides = []): Prescription
    {
        $patient = Patient::factory()->create(['first_name' => 'Ngozi', 'last_name' => 'Seeker', 'date_of_birth' => '1990-02-02']);

        $rx = Prescription::create(array_merge([
            'patient_id'   => $patient->id,
            'facility_id'  => $this->facility->id,
            'status'       => 'active',
            'prescribed_at'=> now(),
        ], $overrides));

        PrescriptionItem::create([
            'prescription_id' => $rx->id,
            'drug_name'       => 'Amoxicillin 500mg',
            'dose'            => '1 capsule',
            'frequency'       => 'TDS',
            'quantity'        => 21,
            'status'          => 'active',
        ]);

        return $rx->fresh();
    }

    public function test_a_pharmacist_opens_the_detail_page(): void
    {
        $rx = $this->prescription();

        $this->actingAsStaff($this->staff('pharmacist'))
            ->get('/pharmacy/prescriptions/' . $rx->id)
            ->assertOk()
            ->assertSee('Amoxicillin 500mg')
            ->assertSee('Ngozi Seeker')
            ->assertSee($rx->reference)
            // Age band, never the date of birth.
            ->assertDontSee('1990-02-02')
            ->assertDontSee('02 Feb 1990')
            ->assertSee('Adult');
    }

    public function test_the_stock_role_may_not_open_it(): void
    {
        $rx = $this->prescription();

        $this->actingAsStaff($this->staff('medicine_stock'))
            ->get('/pharmacy/prescriptions/' . $rx->id)
            ->assertForbidden();
    }

    public function test_a_script_routed_to_another_pharmacy_is_not_found(): void
    {
        $other = Facility::factory()->create(['type' => 'pharmacy', 'name' => 'Pharmacie Rivale']);
        $route = PharmacyRoute::create([
            'facility_id'    => $other->id,
            'pharmacy_name'  => $other->name,
            'pharmacy_type'  => 'in_facility',
            'routing_method' => 'api',
            'is_active'      => true,
        ]);

        $rx = $this->prescription(['facility_id' => $other->id, 'pharmacy_route_id' => $route->id]);

        $this->actingAsStaff($this->staff('pharmacist'))
            ->get('/pharmacy/prescriptions/' . $rx->id)
            ->assertNotFound();

        // And a made-up id is the same answer.
        $this->actingAsStaff($this->staff('pharmacist'))
            ->get('/pharmacy/prescriptions/' . (string) \Illuminate\Support\Str::uuid())
            ->assertNotFound();
    }

    public function test_dispensing_from_the_detail_page_lands_back_on_it(): void
    {
        $rx   = $this->prescription();
        $item = $rx->items->first();

        $this->actingAsStaff($this->staff('pharmacist'))
            ->post('/pharmacy/prescriptions/' . $rx->id . '/dispense', [
                'item_ids'       => [$item->id],
                'dispense_notes' => 'Counselled',
                'return_to'      => 'detail',
            ])
            ->assertRedirect('/pharmacy/prescriptions/' . $rx->id);

        $this->assertSame('dispensed', $rx->fresh()->status);

        $this->actingAsStaff($this->staff('pharmacist'))
            ->get('/pharmacy/prescriptions/' . $rx->id)
            ->assertOk()
            ->assertSee('Counselled');
    }

    public function test_the_queue_and_the_lookup_link_to_the_detail_page(): void
    {
        $rx = $this->prescription();
        $pharmacist = $this->staff('pharmacist');

        $this->actingAsStaff($pharmacist)->get('/pharmacy/prescriptions')
            ->assertOk()->assertSee('/pharmacy/prescriptions/' . $rx->id);

        $this->actingAsStaff($pharmacist)->get('/pharmacy/prescriptions/lookup?q=' . $rx->reference)
            ->assertOk()->assertSee('/pharmacy/prescriptions/' . $rx->id);

        $this->actingAsStaff($pharmacist)->get('/pharmacy')
            ->assertOk()->assertSee('/pharmacy/prescriptions/' . $rx->id);
    }
}
