<?php

namespace Tests\Feature\Pharmacy;

use App\Enums\MedicineCategory;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\InventoryItem;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\PharmacyRoute;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Role;
use App\Models\User;
use App\Services\Pharmacy\PharmacyPriceService;
use App\Services\Pharmacy\PharmacyStockOpsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The pharmacy reports page and its exports.
 *
 *   - The roles that keep stock open it; the front desk does not.
 *   - Each report reads this pharmacy's OWN ledger: another pharmacy's lots,
 *     prices, rules and hand-overs never appear.
 *   - The dispensing report is counts only — no patient name, no Health ID.
 *   - CSV carries the headers and the seeded row; PDF answers as a PDF.
 */
class PharmacyReportsTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;
    private CareFacility $listing;
    private Medicine $medicine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);

        [$this->facility, $this->listing] = $this->pharmacy('PharmaPlus Akwa');

        $this->medicine = Medicine::create([
            'name'                  => 'Amoxicillin 500mg Capsule',
            'generic_name'          => 'Amoxicillin',
            'strength'              => '500mg',
            'form'                  => 'capsule',
            'category'              => MedicineCategory::PainRelief->value,
            'atc_code'              => 'J01CA04',
            'prescription_required' => true,
            'default_pack_size'     => '16 capsules',
            'currency'              => 'XAF',
        ]);
    }

    /** @return array{0: Facility, 1: CareFacility} */
    private function pharmacy(string $name): array
    {
        $facility = Facility::create(['name' => $name, 'type' => 'pharmacy', 'status' => 'active']);

        $listing = CareFacility::create([
            'facility_id'    => $facility->id,
            'facility_name'  => $name,
            'facility_type'  => 'pharmacy',
            'listing_status' => 'active',
            'country_code'   => 'CM',
            'region'         => 'Littoral',
            'city'           => 'Douala',
            'address'        => 'Rue Joss, Akwa',
            'latitude'       => 4.0520,
            'longitude'      => 9.7690,
            'phone_primary'  => '+237600000001',
        ]);

        return [$facility, $listing];
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

    /** A lot of this medicine at a facility, with a price and a reorder rule. */
    private function stock(Facility $facility, string $lot, int $qty, string $expiry, int $price, int $minimum, User $actor): InventoryItem
    {
        $ops = app(PharmacyStockOpsService::class);

        $ops->receiveDelivery($facility->id, ['received_date' => now()->toDateString()], [
            ['medicine_id' => $this->medicine->id, 'quantity' => $qty, 'lot_number' => $lot, 'expiry_date' => $expiry, 'unit_cost' => 1000],
        ], $actor->id);

        $item = InventoryItem::where('facility_id', $facility->id)->where('medicine_id', $this->medicine->id)->firstOrFail();

        app(PharmacyPriceService::class)->setPrice($item, $price, $actor->id);
        $ops->setReorderRule($facility->id, $this->medicine, $minimum, 50, $actor->id);

        return $item;
    }

    /** A script routed to this pharmacy with one line handed over on $day by $pharmacist. */
    private function handOver(Facility $facility, User $pharmacist, Patient $patient, string $day): Prescription
    {
        $route = PharmacyRoute::firstOrCreate(
            ['facility_id' => $facility->id, 'is_active' => true],
            ['pharmacy_name' => $facility->name, 'pharmacy_type' => 'in_facility', 'routing_method' => 'api'],
        );

        $rx = Prescription::create([
            'patient_id'              => $patient->id,
            'prescribed_by'           => $pharmacist->id,
            'facility_id'             => $facility->id,
            'status'                  => 'dispensed',
            'pharmacy_route_id'       => $route->id,
            'pharmacy_routing_status' => 'dispensed',
            'dispensed_by'            => $pharmacist->id,
            'dispensed_at'            => $day . ' 10:00:00',
        ]);

        PrescriptionItem::create([
            'prescription_id' => $rx->id,
            'drug_name'       => 'Amoxicillin 500mg',
            'quantity'        => 1,
            'status'          => 'dispensed',
            'dispensed_at'    => $day . ' 10:00:00',
        ]);

        return $rx;
    }

    // ── Access ────────────────────────────────────────────────────────────

    public function test_the_stock_roles_open_the_reports_page_and_the_front_desk_does_not(): void
    {
        foreach (['pharmacist', 'medicine_stock'] as $roleName) {
            $this->actingAsStaff($this->staff($roleName))->get('/pharmacy/reports')
                ->assertOk()
                ->assertSee(__('pharmacy_reports.card_expiry'))
                ->assertSee(__('pharmacy_reports.card_valuation'))
                ->assertSee(__('pharmacy_reports.card_dispensing'))
                ->assertSee(__('pharmacy_reports.card_low_stock'));
        }

        $receptionist = $this->staff('receptionist');
        $this->actingAsStaff($receptionist)->get('/pharmacy/reports')->assertForbidden();
        $this->actingAsStaff($receptionist)->get('/pharmacy/reports/expiry/csv')->assertForbidden();
        $this->actingAsStaff($receptionist)->get('/pharmacy/reports/valuation/pdf')->assertForbidden();
    }

    public function test_an_unknown_report_is_a_404(): void
    {
        $this->actingAsStaff($this->staff('pharmacist'))->get('/pharmacy/reports/profits/csv')->assertNotFound();
    }

    // ── Expiry + valuation + low stock, scoped to this pharmacy ──────────

    public function test_the_reports_read_this_pharmacys_ledger_only(): void
    {
        $pharmacist = $this->staff('pharmacist');
        [$other]    = $this->pharmacy('Pharmacie du Rond-Point');
        $otherStaff = $this->staff('pharmacist', $other);

        // Ours: 10 packs expiring in 20 days, priced 1 500, minimum 25 (so: low).
        $this->stock($this->facility, 'OURS-A', 10, now()->addDays(20)->toDateString(), 1500, 25, $pharmacist);
        // Theirs: 99 packs expiring in 5 days, priced 9 000, minimum 100.
        $this->stock($other, 'THEIRS-Z', 99, now()->addDays(5)->toDateString(), 9000, 100, $otherStaff);

        $page = $this->actingAsStaff($pharmacist)->get('/pharmacy/reports?days=30')->assertOk();
        $page->assertSee('OURS-A')->assertDontSee('THEIRS-Z');
        $page->assertSee(__('pharmacy_reports.sum_lots', ['count' => 1, 'packs' => 10]));
        // Valuation: 10 × 1 500 = 15 000 FCFA, whole francs.
        $page->assertSee('15 000');
        $page->assertDontSee('891 000'); // 99 × 9 000 — the other pharmacy's value

        // A 30-day window hides a lot that expires in 60.
        $this->stock($this->facility, 'OURS-LATER', 3, now()->addDays(55)->toDateString(), 1500, 25, $pharmacist);
        $this->actingAsStaff($pharmacist)->get('/pharmacy/reports?days=30')->assertOk()->assertDontSee('OURS-LATER');
        $this->actingAsStaff($pharmacist)->get('/pharmacy/reports?days=60')->assertOk()->assertSee('OURS-LATER');
    }

    public function test_the_csv_carries_the_headers_and_the_seeded_row(): void
    {
        $pharmacist = $this->staff('pharmacist');
        [$other]    = $this->pharmacy('Pharmacie du Rond-Point');
        $this->stock($this->facility, 'OURS-A', 10, now()->addDays(20)->toDateString(), 1500, 25, $pharmacist);
        $this->stock($other, 'THEIRS-Z', 99, now()->addDays(5)->toDateString(), 9000, 100, $this->staff('pharmacist', $other));

        $res = $this->actingAsStaff($pharmacist)->get('/pharmacy/reports/expiry/csv?days=30');
        $res->assertOk();
        $this->assertStringStartsWith('text/csv', $res->headers->get('content-type'));
        $this->assertStringContainsString('expiry_30d.csv', (string) $res->headers->get('content-disposition'));

        $csv = $res->streamedContent();
        $this->assertStringContainsString(__('pharmacy_reports.col_medicine') . ',' . __('pharmacy_reports.col_lot'), $csv);
        $this->assertStringContainsString('OURS-A', $csv);
        $this->assertStringNotContainsString('THEIRS-Z', $csv);

        $valuation = $this->actingAsStaff($pharmacist)->get('/pharmacy/reports/valuation/csv')->assertOk()->streamedContent();
        // fputcsv quotes a field with a space in it.
        $this->assertStringContainsString('"Amoxicillin 500mg Capsule",10,1500,15000', $valuation);

        $low = $this->actingAsStaff($pharmacist)->get('/pharmacy/reports/low_stock/csv')->assertOk()->streamedContent();
        $this->assertStringContainsString('"Amoxicillin 500mg Capsule",10,25,50,' . __('pharmacy_reports.state_low'), $low);
    }

    public function test_the_pdf_answers_as_a_pdf(): void
    {
        $pharmacist = $this->staff('pharmacist');
        $this->stock($this->facility, 'OURS-A', 10, now()->addDays(20)->toDateString(), 1500, 25, $pharmacist);

        $paths = [
            '/pharmacy/reports/expiry/pdf',
            '/pharmacy/reports/valuation/pdf',
            '/pharmacy/reports/dispensing/pdf',
            '/pharmacy/reports/low_stock/pdf',
            '/pharmacy/controlled/register',
        ];

        foreach ($paths as $path) {
            $started = microtime(true);
            $res     = $this->actingAsStaff($pharmacist)->get($path);
            $elapsed = microtime(true) - $started;

            $res->assertOk();
            $this->assertSame('application/pdf', $res->headers->get('content-type'), $path);
            $this->assertStringStartsWith('%PDF-', $res->getContent(), $path);
            // Generous ceiling (a warm render is ~1-2s): the audit saw these
            // hit the 30s request limit, this keeps a regression loud.
            $this->assertLessThan(15.0, $elapsed, "{$path} took {$elapsed}s");
        }
    }

    // ── Dispensing volume: counts only ───────────────────────────────────

    public function test_dispensing_volume_counts_this_counters_hand_overs_and_never_names_a_patient(): void
    {
        $pharmacist = $this->staff('pharmacist');
        [$other]    = $this->pharmacy('Pharmacie du Rond-Point');
        $otherStaff = $this->staff('pharmacist', $other);

        $patient = Patient::create([
            'health_id'     => 'OC-TST-7400-0001-01',
            'first_name'    => 'Ngozi',
            'last_name'     => 'Seeker',
            'sex'           => 'female',
            'date_of_birth' => '1990-02-02',
            'is_demo'       => false,
        ]);

        $day = now()->subDays(2)->toDateString();
        $this->handOver($this->facility, $pharmacist, $patient, $day);
        $this->handOver($this->facility, $pharmacist, $patient, $day);
        $this->handOver($other, $otherStaff, $patient, $day);

        $page = $this->actingAsStaff($pharmacist)->get('/pharmacy/reports')->assertOk();
        $page->assertSee(__('pharmacy_reports.sum_dispensing', ['scripts' => 2, 'lines' => 2, 'days' => 1]));
        $page->assertSee($pharmacist->name);
        $page->assertDontSee($otherStaff->name);
        $page->assertDontSee('Ngozi')->assertDontSee('Seeker')->assertDontSee('OC-TST-7400-0001-01');

        $csv = $this->actingAsStaff($pharmacist)->get('/pharmacy/reports/dispensing/csv')->assertOk()->streamedContent();
        $this->assertStringContainsString(__('pharmacy_reports.group_day') . ',' . $day . ',2,2', $csv);
        // fputcsv quotes a name with a space in it (every factory name has one).
        $this->assertStringContainsString(__('pharmacy_reports.group_pharmacist') . ',"' . $pharmacist->name . '",2,2', $csv);
        $this->assertStringNotContainsString('Ngozi', $csv);
        $this->assertStringNotContainsString('OC-TST-7400-0001-01', $csv);

        // Outside the window: nothing.
        $from = now()->subDays(30)->toDateString();
        $to   = now()->subDays(10)->toDateString();
        $this->actingAsStaff($pharmacist)->get("/pharmacy/reports?from={$from}&to={$to}")->assertOk()
            ->assertSee(__('pharmacy_reports.sum_dispensing', ['scripts' => 0, 'lines' => 0, 'days' => 0]));
    }
}
