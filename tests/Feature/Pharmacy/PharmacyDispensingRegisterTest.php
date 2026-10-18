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
 * The dispensing register: /pharmacy/dispensing and its CSV export.
 *
 * One row per script handed over at THIS facility; another pharmacy's
 * hand-overs never appear, and the export carries the same rows the page
 * shows, one line per dispensed item.
 */
class PharmacyDispensingRegisterTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;

    private User $pharmacist;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);

        $this->facility   = Facility::factory()->create(['type' => 'pharmacy', 'name' => 'Pharmacie du Marché']);
        $this->pharmacist = $this->staff('pharmacist');
    }

    private function staff(string $roleName, ?Facility $facility = null): User
    {
        $facility ??= $this->facility;
        $role = Role::where('name', $roleName)->firstOrFail();

        $user = User::factory()->create([
            'name'                => 'Aline Ngo',
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

    private function dispensed(Facility $facility, string $drug, ?string $when = null): Prescription
    {
        $patient = Patient::factory()->create(['first_name' => 'Ngozi', 'last_name' => 'Seeker']);
        $at = $when ? \Illuminate\Support\Carbon::parse($when) : now();

        $rx = Prescription::create([
            'patient_id'   => $patient->id,
            'facility_id'  => $facility->id,
            'status'       => 'active',
            'prescribed_at'=> $at,
        ]);

        PrescriptionItem::create([
            'prescription_id' => $rx->id,
            'drug_name'       => $drug,
            'dose'            => '1 tab',
            'quantity'        => 10,
            'status'          => 'dispensed',
            'dispensed_at'    => $at,
        ]);

        $rx->forceFill(['status' => 'dispensed', 'dispensed_at' => $at, 'dispensed_by' => $this->pharmacist->id])->save();

        return $rx->fresh();
    }

    public function test_a_pharmacist_sees_this_facilitys_hand_overs_only(): void
    {
        $mine = $this->dispensed($this->facility, 'Paracetamol 500mg');

        $other = Facility::factory()->create(['type' => 'pharmacy', 'name' => 'Pharmacie Rivale']);
        $theirs = $this->dispensed($other, 'Ibuprofen 400mg');

        $this->actingAsStaff($this->pharmacist)
            ->get('/pharmacy/dispensing')
            ->assertOk()
            ->assertSee('Paracetamol 500mg')
            ->assertSee('/pharmacy/prescriptions/' . $mine->id)
            ->assertDontSee('Ibuprofen 400mg')
            ->assertDontSee($theirs->id);
    }

    public function test_the_stock_role_is_refused(): void
    {
        $this->actingAsStaff($this->staff('medicine_stock'))
            ->get('/pharmacy/dispensing')
            ->assertForbidden();

        $this->actingAsStaff($this->staff('medicine_stock'))
            ->get('/pharmacy/dispensing/export')
            ->assertForbidden();
    }

    public function test_filters_narrow_the_register(): void
    {
        $this->dispensed($this->facility, 'Paracetamol 500mg', '2026-09-01 10:00:00');
        $this->dispensed($this->facility, 'Morphine 10mg', '2026-10-01 10:00:00');

        $this->actingAsStaff($this->pharmacist)
            ->get('/pharmacy/dispensing?from=2026-09-15&to=2026-10-15')
            ->assertOk()->assertSee('Morphine 10mg')->assertDontSee('Paracetamol 500mg');

        $this->actingAsStaff($this->pharmacist)
            ->get('/pharmacy/dispensing?controlled=1')
            ->assertOk()->assertSee('Morphine 10mg')->assertDontSee('Paracetamol 500mg');

        $this->actingAsStaff($this->pharmacist)
            ->get('/pharmacy/dispensing?pharmacist=' . $this->pharmacist->id)
            ->assertOk()->assertSee('Morphine 10mg')->assertSee('Paracetamol 500mg');
    }

    public function test_the_csv_export_carries_one_line_per_dispensed_item(): void
    {
        $rx = $this->dispensed($this->facility, 'Paracetamol 500mg');

        $other = Facility::factory()->create(['type' => 'pharmacy', 'name' => 'Pharmacie Rivale']);
        $this->dispensed($other, 'Ibuprofen 400mg');

        $response = $this->actingAsStaff($this->pharmacist)->get('/pharmacy/dispensing/export');
        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        $csv = $response->streamedContent();

        $this->assertStringContainsString('dispensed_at,reference,patient,health_id,drug', $csv);
        $this->assertStringContainsString($rx->reference, $csv);
        $this->assertStringContainsString('Paracetamol 500mg', $csv);
        $this->assertStringContainsString('Aline Ngo', $csv);
        // Masked name, never the full surname.
        $this->assertStringContainsString('Ngozi S.', $csv);
        $this->assertStringNotContainsString('Ibuprofen 400mg', $csv);
    }

    public function test_an_empty_register_offers_the_counter_lookup(): void
    {
        $this->actingAsStaff($this->pharmacist)
            ->get('/pharmacy/dispensing')
            ->assertOk()
            ->assertSee('/pharmacy/prescriptions/lookup');
    }
}
