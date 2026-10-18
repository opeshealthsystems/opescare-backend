<?php

namespace Tests\Feature\Pharmacy;

use App\Models\ControlledSubstanceDispensing;
use App\Models\ControlledSubstanceInventory;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The monthly controlled-substance register export: the stock roles get it,
 * the front desk does not; it carries this pharmacy's dispense events,
 * witness confirmations and reconciliations for the month asked for — and
 * nothing from another pharmacy or another month.
 */
class PharmacyControlledRegisterExportTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);

        $this->facility = Facility::create(['name' => 'PharmaPlus Akwa', 'type' => 'pharmacy', 'status' => 'active']);
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

    private function entry(Facility $facility, User $by, Patient $patient, string $drug, string $when, ?User $witness = null, ?string $witnessedAt = null): ControlledSubstanceDispensing
    {
        ControlledSubstanceInventory::firstOrCreate(
            ['facility_id' => $facility->id, 'drug_code' => Str::slug($drug)],
            ['drug_name' => $drug, 'schedule' => 'schedule_ii', 'current_balance' => 100, 'unit' => 'tablet'],
        );

        return ControlledSubstanceDispensing::create([
            'facility_id'          => $facility->id,
            'patient_id'           => $patient->id,
            'prescription_id'      => (string) Str::uuid(),
            'prescription_item_id' => (string) Str::uuid(),
            'drug_code'            => Str::slug($drug),
            'drug_name'            => $drug,
            'schedule'             => 'schedule_ii',
            'quantity_dispensed'   => 30,
            'unit'                 => 'tablet',
            'dispensed_by'         => $by->id,
            'dispensed_at'         => $when,
            'witness_id'           => $witness?->id,
            'witness_confirmed_at' => $witnessedAt,
            'stock_balance_before' => 100,
            'stock_balance_after'  => 70,
            'lot_number'           => 'MOR-' . substr(md5($when), 0, 6),
        ]);
    }

    public function test_the_stock_roles_export_the_register_and_the_front_desk_does_not(): void
    {
        foreach (['pharmacist', 'medicine_stock'] as $roleName) {
            $res = $this->actingAsStaff($this->staff($roleName))->get('/pharmacy/controlled/register?month=2026-09');
            $res->assertOk();
            $this->assertSame('application/pdf', $res->headers->get('content-type'));
            $this->assertStringStartsWith('%PDF', $res->getContent());
        }

        $this->actingAsStaff($this->staff('receptionist'))->get('/pharmacy/controlled/register?month=2026-09')->assertForbidden();
        $this->actingAsStaff($this->staff('receptionist'))->get('/pharmacy/controlled/register?month=2026-09&format=csv')->assertForbidden();
    }

    public function test_a_malformed_month_is_rejected(): void
    {
        $this->actingAsStaff($this->staff('pharmacist'))
            ->from('/pharmacy/controlled')
            ->get('/pharmacy/controlled/register?month=September')
            ->assertSessionHasErrors('month');
    }

    public function test_the_controlled_page_offers_the_month_export(): void
    {
        $this->actingAsStaff($this->staff('pharmacist'))->get('/pharmacy/controlled')
            ->assertOk()
            ->assertSee(route('portals.pharmacy.controlled.register'))
            ->assertSee(__('pharmacy_reports.register_export_pdf'))
            ->assertSee(__('pharmacy_reports.register_export_csv'));
    }

    public function test_the_csv_carries_this_months_entries_for_this_pharmacy_only(): void
    {
        $pharmacist = $this->staff('pharmacist');
        $witness    = $this->staff('pharmacist');
        $other      = Facility::create(['name' => 'Pharmacie du Rond-Point', 'type' => 'pharmacy', 'status' => 'active']);
        $otherStaff = $this->staff('pharmacist', $other);

        $patient = Patient::create([
            'health_id'     => 'OC-TST-7400-0001-01',
            'first_name'    => 'Ngozi',
            'last_name'     => 'Seeker',
            'sex'           => 'female',
            'date_of_birth' => '1990-02-02',
            'is_demo'       => false,
        ]);

        // September, ours: one witnessed dispense and one awaiting its witness.
        $this->entry($this->facility, $pharmacist, $patient, 'Morphine Sulfate', '2026-09-10 09:30:00', $witness, '2026-09-10 09:35:00');
        $this->entry($this->facility, $pharmacist, $patient, 'Morphine Sulfate', '2026-09-22 14:00:00', $witness, null);
        // August, ours: must not appear in September.
        $this->entry($this->facility, $pharmacist, $patient, 'Pethidine', '2026-08-30 11:00:00');
        // September, theirs: must never appear.
        $this->entry($other, $otherStaff, $patient, 'Fentanyl', '2026-09-15 10:00:00');

        // A September reconciliation on our morphine row.
        ControlledSubstanceInventory::where('facility_id', $this->facility->id)->where('drug_code', 'morphine-sulfate')
            ->update(['last_reconciled_at' => '2026-09-30 18:00:00', 'last_reconciled_by' => $pharmacist->id, 'current_balance' => 40]);

        $res = $this->actingAsStaff($pharmacist)->get('/pharmacy/controlled/register?month=2026-09&format=csv');
        $res->assertOk();
        $this->assertStringStartsWith('text/csv', $res->headers->get('content-type'));
        $this->assertStringContainsString('controlled-register_2026-09.csv', (string) $res->headers->get('content-disposition'));

        $csv   = $res->streamedContent();
        $lines = array_values(array_filter(array_map('trim', explode("\n", $csv))));

        $this->assertStringContainsString(__('pharmacy_reports.col_when') . ',' . __('pharmacy_reports.col_entry'), $lines[0]);

        // 2 dispense events + 1 witness confirmation + 1 reconciliation. The
        // kind is counted on the body only — the header has a "Witness" column.
        $this->assertCount(5, $lines);
        $body = implode("\n", array_slice($lines, 1));
        $this->assertSame(2, substr_count($body, ',' . __('pharmacy_reports.entry_dispense') . ','));
        $this->assertSame(1, substr_count($body, ',' . __('pharmacy_reports.entry_witness') . ','));
        $this->assertSame(1, substr_count($body, ',' . __('pharmacy_reports.entry_reconciliation') . ','));

        // fputcsv quotes a field with a space in it (names, "Morphine Sulfate").
        $this->assertStringContainsString('"2026-09-10 09:30",' . __('pharmacy_reports.entry_dispense') . ',"Morphine Sulfate",schedule_ii,30,tablet,100,70,OC-TST-7400-0001-01,"' . $pharmacist->name . '","' . $witness->name . '"', $csv);
        $this->assertStringContainsString('"2026-09-22 14:00",' . __('pharmacy_reports.entry_dispense'), $csv);
        $this->assertStringContainsString(__('pharmacy_reports.witness_pending'), $csv);
        $this->assertStringContainsString('"2026-09-30 18:00",' . __('pharmacy_reports.entry_reconciliation') . ',"Morphine Sulfate",schedule_ii,,tablet,,40,,"' . $pharmacist->name . '"', $csv);

        $this->assertStringNotContainsString('Pethidine', $csv);
        $this->assertStringNotContainsString('Fentanyl', $csv);
        $this->assertStringNotContainsString('Ngozi', $csv);

        // The other pharmacy's own register has only its own entry.
        $theirs = $this->actingAsStaff($otherStaff, $other)->get('/pharmacy/controlled/register?month=2026-09&format=csv')->assertOk()->streamedContent();
        $this->assertStringContainsString('Fentanyl', $theirs);
        $this->assertStringNotContainsString('Morphine', $theirs);

        // An empty month is an empty register, not an error.
        $empty = $this->actingAsStaff($pharmacist)->get('/pharmacy/controlled/register?month=2026-01&format=csv')->assertOk()->streamedContent();
        $this->assertCount(1, array_values(array_filter(array_map('trim', explode("\n", $empty)))));
    }
}
