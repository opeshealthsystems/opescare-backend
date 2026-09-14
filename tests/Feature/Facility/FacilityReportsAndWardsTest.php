<?php

namespace Tests\Feature\Facility;

use App\Models\Appointment;
use App\Models\AuditEvent;
use App\Models\Bed;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Invoice;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Models\Ward;
use App\Services\Portal\FacilityAdminDeskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Reports & exports (H-6) and the wards/beds desk (H-5 writer) under
 * /facility/team/overview — docs/HOSPITAL_ADMIN_DASHBOARD_AUDIT.md.
 *
 * What these hold:
 *   1. every report row is a count over THE SESSION FACILITY's rows — the
 *      neighbour's appointments, invoices and staff never leak in;
 *   2. an export carries aggregates only: a patient with a distinctive name
 *      and Health ID on this facility's rows never reaches the CSV;
 *   3. a range longer than 366 days is refused, not clamped;
 *   4. ward create / beds add / bed out-of-service persist and each leaves an
 *      AuditEvent; a ward of another facility is a 404; a pharmacist is 403;
 *   5. the overview bed tile and the ward desk read the same rows.
 */
class FacilityReportsAndWardsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /** @return array{0: User, 1: Facility} */
    private function staff(string $roleName, ?Facility $facility = null): array
    {
        $facility ??= Facility::factory()->create(['type' => 'hospital', 'name' => 'Hôpital Laquintinie']);
        $role = Role::where('name', $roleName)->firstOrFail();

        $user = User::factory()->create([
            'role_id'             => $role->id,
            'primary_facility_id' => $facility->id,
            'status'              => 'active',
        ]);

        FacilityRoleAssignment::create([
            'user_id' => $user->id, 'facility_id' => $facility->id, 'role_id' => $role->id,
            'is_active' => true, 'assigned_at' => now(),
        ]);

        return [$user, $facility];
    }

    private function asStaff(User $user, Facility $facility): static
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id]);
    }

    // ── Reports ──────────────────────────────────────────────────────────

    public function test_reports_count_only_the_session_facilitys_rows(): void
    {
        [$admin, $facility] = $this->staff('facility_admin');
        [, $other]          = $this->staff('facility_admin');
        $this->staff('doctor', $facility);
        $this->staff('doctor', $other);
        $this->staff('doctor', $other);

        $day = now()->startOfMonth()->addDays(2)->setTime(9, 0);

        Appointment::factory()->count(3)->create(['facility_id' => $facility->id, 'scheduled_at' => $day]);
        Appointment::factory()->create(['facility_id' => $facility->id, 'scheduled_at' => $day, 'status' => 'no_show', 'no_show_at' => $day]);
        Appointment::factory()->count(7)->create(['facility_id' => $other->id, 'scheduled_at' => $day]);

        LabOrder::factory()->create(['facility_id' => $facility->id, 'status' => 'resulted', 'ordered_at' => $day, 'resulted_at' => $day->copy()->addMinutes(45)]);
        LabOrder::factory()->create(['facility_id' => $other->id, 'status' => 'resulted', 'ordered_at' => $day, 'resulted_at' => $day->copy()->addMinutes(5)]);

        Invoice::factory()->create(['facility_id' => $facility->id, 'status' => 'paid', 'paid_amount' => 2500, 'balance_amount' => 0, 'paid_at' => $day, 'created_at' => $day]);
        Invoice::factory()->create(['facility_id' => $other->id, 'status' => 'paid', 'paid_amount' => 99999, 'balance_amount' => 0, 'paid_at' => $day, 'created_at' => $day]);

        $html = $this->asStaff($admin, $facility)
            ->get(route('portals.facility.admin.reports'))
            ->assertOk()
            ->getContent();

        $d = $day->toDateString();
        // Appointments: 4 booked here, 1 no-show → 25 %; the neighbour's 7 are absent.
        $this->assertMatchesRegularExpression('/data-day="' . $d . '">.*?<td data-total>4<\/td>/s', $html);
        $this->assertStringContainsString(__('facility_admin.reports_no_show_rate_value', ['pct' => 25, 'no_show' => 1, 'total' => 4]), $html);
        $this->assertStringNotContainsString('<td data-total>7</td>', $html);
        $this->assertStringNotContainsString('<td data-total>11</td>', $html);
        // Lab: 45 min here, not 5.
        $this->assertStringContainsString('45 min', $html);
        $this->assertStringNotContainsString('>5 min<', $html);
        // Billing: 2 500 F collected, never 99 999.
        $this->assertStringContainsString('<td data-collected-amount>2 500</td>', $html);
        $this->assertStringNotContainsString('99 999', $html);
        // Staff: 1 admin + 1 doctor here; the neighbour's two doctors are not counted.
        $this->assertMatchesRegularExpression('/data-role="doctor">.*?<td data-headcount>1<\/td>/s', $html);
        $this->assertMatchesRegularExpression('/data-role="facility_admin">.*?<td data-headcount>1<\/td>/s', $html);
    }

    public function test_export_is_aggregates_only_and_never_carries_a_patient(): void
    {
        [$admin, $facility] = $this->staff('facility_admin');
        $patient = Patient::factory()->create(['first_name' => 'Zephyrinus', 'last_name' => 'Nkoulouzock', 'health_id' => 'OC-ZZQ-777']);

        $day = now()->startOfMonth()->addDay()->setTime(10, 0);
        Appointment::factory()->create(['facility_id' => $facility->id, 'patient_id' => $patient->id, 'scheduled_at' => $day]);
        Invoice::factory()->create(['facility_id' => $facility->id, 'patient_id' => $patient->id, 'status' => 'paid', 'paid_amount' => 1500, 'balance_amount' => 0, 'paid_at' => $day, 'created_at' => $day]);
        LabOrder::factory()->create(['facility_id' => $facility->id, 'patient_id' => $patient->id, 'status' => 'resulted', 'ordered_at' => $day, 'resulted_at' => $day->copy()->addMinutes(20)]);

        $from = now()->startOfMonth()->toDateString();
        $to   = now()->toDateString();

        foreach (FacilityAdminDeskService::REPORT_TABLES as $table) {
            $response = $this->asStaff($admin, $facility)
                ->get(route('portals.facility.admin.reports.export', ['table' => $table, 'from' => $from, 'to' => $to]))
                ->assertOk()
                ->assertHeader('content-type', 'text/csv; charset=UTF-8');

            $disposition = $response->headers->get('content-disposition');
            $this->assertStringContainsString("hopital-laquintinie_{$table}_{$from}_{$to}.csv", $disposition);

            $csv = $response->streamedContent();
            $this->assertStringStartsWith("\xEF\xBB\xBF", $csv, "{$table}: UTF-8 BOM");
            $this->assertStringNotContainsString('Zephyrinus', $csv, "{$table}: patient first name leaked");
            $this->assertStringNotContainsString('Nkoulouzock', $csv, "{$table}: patient last name leaked");
            $this->assertStringNotContainsString('OC-ZZQ-777', $csv, "{$table}: Health ID leaked");
            $this->assertStringNotContainsString($patient->id, $csv, "{$table}: patient id leaked");
        }

        // The appointment table carries the day and the count, and nothing else.
        $csv = $this->asStaff($admin, $facility)
            ->get(route('portals.facility.admin.reports.export', ['table' => 'appointments', 'from' => $from, 'to' => $to]))
            ->streamedContent();
        $this->assertStringContainsString($day->toDateString() . ',1,1', str_replace("\xEF\xBB\xBF", '', $csv));

        // Every export is audited, with the range and never a patient.
        $this->assertSame(
            count(FacilityAdminDeskService::REPORT_TABLES) + 1,
            AuditEvent::where('action_type', 'facility_report_exported')->where('facility_id', $facility->id)->count()
        );
    }

    public function test_a_range_longer_than_366_days_is_refused_and_a_bad_table_too(): void
    {
        [$admin, $facility] = $this->staff('facility_admin');

        $this->asStaff($admin, $facility)
            ->from(route('portals.facility.admin.reports'))
            ->get(route('portals.facility.admin.reports', ['from' => '2025-01-01', 'to' => '2026-01-02']))
            ->assertRedirect(route('portals.facility.admin.reports'))
            ->assertSessionHasErrors('to');

        // 366 days inclusive is the ceiling and passes.
        $this->asStaff($admin, $facility)
            ->get(route('portals.facility.admin.reports', ['from' => '2025-01-01', 'to' => '2026-01-01']))
            ->assertOk();

        // End before start.
        $this->asStaff($admin, $facility)
            ->from(route('portals.facility.admin.reports'))
            ->get(route('portals.facility.admin.reports', ['from' => '2026-03-01', 'to' => '2026-02-01']))
            ->assertSessionHasErrors('to');

        $this->asStaff($admin, $facility)
            ->from(route('portals.facility.admin.reports'))
            ->get(route('portals.facility.admin.reports.export', ['table' => 'patients', 'from' => '2026-01-01', 'to' => '2026-01-31']))
            ->assertSessionHasErrors('table');
    }

    // ── Wards & beds ─────────────────────────────────────────────────────

    public function test_ward_create_beds_add_and_out_of_service_persist_and_audit(): void
    {
        [$admin, $facility] = $this->staff('facility_admin');

        $this->asStaff($admin, $facility)
            ->post(route('portals.facility.admin.wards.create'), ['name' => 'Maternité B', 'ward_type' => 'maternity', 'capacity' => 4])
            ->assertRedirect(route('portals.facility.admin.wards'))
            ->assertSessionHas('success');

        $ward = Ward::where('facility_id', $facility->id)->where('name', 'Maternité B')->firstOrFail();
        $this->assertSame('maternity', $ward->ward_type);
        $this->assertSame(4, $ward->total_beds);
        $this->assertTrue($ward->is_active);

        $created = AuditEvent::where('action_type', 'facility_ward_created')->where('resource_id', $ward->id)->first();
        $this->assertNotNull($created);
        $this->assertSame($facility->id, $created->facility_id);
        $this->assertSame($admin->id, $created->actor_id);
        $this->assertSame('Maternité B', data_get($created->after_state, 'name'));

        // Two batches: labels continue, never collide, capacity grows to fit.
        $this->asStaff($admin, $facility)
            ->post(route('portals.facility.admin.wards.beds', $ward->id), ['count' => 3, 'prefix' => 'M'])
            ->assertRedirect(route('portals.facility.admin.wards'))
            ->assertSessionHas('success');
        $this->asStaff($admin, $facility)
            ->post(route('portals.facility.admin.wards.beds', $ward->id), ['count' => 2, 'prefix' => 'M'])
            ->assertSessionHas('success');

        $labels = Bed::where('ward_id', $ward->id)->orderBy('bed_number')->pluck('bed_number')->all();
        $this->assertSame(['M-01', 'M-02', 'M-03', 'M-04', 'M-05'], $labels);
        $this->assertSame(5, $ward->fresh()->total_beds);
        $this->assertSame(5, Bed::where('ward_id', $ward->id)->where('status', 'available')->count());

        $added = AuditEvent::where('action_type', 'facility_ward_beds_added')->where('resource_id', $ward->id)->orderBy('created_at')->get();
        $this->assertCount(2, $added);
        $this->assertSame(3, data_get($added[1]->before_state, 'beds'));
        $this->assertSame(5, data_get($added[1]->after_state, 'beds'));
        $this->assertSame(['M-04', 'M-05'], data_get($added[1]->after_state, 'labels'));

        // Out of service, then back — the row is never deleted.
        $bed = Bed::where('ward_id', $ward->id)->where('bed_number', 'M-02')->firstOrFail();

        $this->asStaff($admin, $facility)
            ->post(route('portals.facility.admin.wards.bed.service', [$ward->id, $bed->id]), ['in_service' => 0])
            ->assertRedirect(route('portals.facility.admin.wards'))
            ->assertSessionHas('success');
        $this->assertSame(FacilityAdminDeskService::BED_OUT_OF_SERVICE, $bed->fresh()->status);

        $event = AuditEvent::where('action_type', 'facility_bed_service_changed')->where('resource_id', $bed->id)->first();
        $this->assertNotNull($event);
        $this->assertSame('available', data_get($event->before_state, 'status'));
        $this->assertSame('maintenance', data_get($event->after_state, 'status'));

        // Same state again is refused, not re-audited.
        $this->asStaff($admin, $facility)
            ->post(route('portals.facility.admin.wards.bed.service', [$ward->id, $bed->id]), ['in_service' => 0])
            ->assertSessionHas('error');
        $this->assertSame(1, AuditEvent::where('action_type', 'facility_bed_service_changed')->count());

        $this->asStaff($admin, $facility)
            ->post(route('portals.facility.admin.wards.bed.service', [$ward->id, $bed->id]), ['in_service' => 1])
            ->assertSessionHas('success');
        $this->assertSame('available', $bed->fresh()->status);
        $this->assertSame(5, Bed::where('ward_id', $ward->id)->count(), 'no bed is ever deleted');

        // The desk page shows the ward, its beds and the scope note.
        $html = $this->asStaff($admin, $facility)
            ->get(route('portals.facility.admin.wards'))
            ->assertOk()
            ->assertSee('Maternité B')
            ->assertSee('M-05')
            ->assertSee(__('facility_admin.wards_scope_note'))
            ->getContent();
        $this->assertMatchesRegularExpression('/data-ward="' . $ward->id . '">.*?<td[^>]*data-beds>5<\/td>.*?<td[^>]*data-occupied>0<\/td>/s', $html);
    }

    public function test_overview_bed_tile_and_ward_desk_read_the_same_rows(): void
    {
        [$admin, $facility] = $this->staff('facility_admin');
        [$nurse]            = $this->staff('nurse', $facility);
        $patient            = Patient::factory()->create();

        $ward = Ward::create(['facility_id' => $facility->id, 'name' => 'Chirurgie', 'ward_type' => 'surgical', 'total_beds' => 3]);
        $b1 = Bed::create(['ward_id' => $ward->id, 'bed_number' => 'C-01']);
        Bed::create(['ward_id' => $ward->id, 'bed_number' => 'C-02']);
        Bed::create(['ward_id' => $ward->id, 'bed_number' => 'C-03', 'status' => FacilityAdminDeskService::BED_OUT_OF_SERVICE]);

        // Occupancy comes from an active, undischarged admission — a
        // discharged one on the same bed counts for nothing.
        \App\Models\Admission::create([
            'facility_id' => $facility->id, 'patient_id' => $patient->id, 'bed_id' => $b1->id,
            'admitted_by' => $nurse->id, 'status' => 'active', 'admitted_at' => now()->subDay(),
        ]);
        \App\Models\Admission::create([
            'facility_id' => $facility->id, 'patient_id' => $patient->id, 'bed_id' => $b1->id,
            'admitted_by' => $nurse->id, 'status' => 'discharged', 'admitted_at' => now()->subWeek(), 'discharged_at' => now()->subDays(5),
        ]);

        $board = app(FacilityAdminDeskService::class)->wardBoard($facility->id);
        $this->assertSame(1, $board['active_admissions']);
        $this->assertSame(['beds' => 3, 'occupied' => 1, 'available' => 1, 'out_of_service' => 1, 'occupancy_pct' => 33],
            array_intersect_key($board['wards'][0], array_flip(['beds', 'occupied', 'available', 'out_of_service', 'occupancy_pct'])));

        $html = $this->asStaff($admin, $facility)->get(route('portals.facility.admin.overview'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/data-kpi="beds">3</', $html);
        $this->assertMatchesRegularExpression('/data-kpi="beds_occupied">1</', $html);
        $this->assertMatchesRegularExpression('/data-kpi="beds_available">1</', $html);
        $this->assertMatchesRegularExpression('/data-kpi="occupancy">33 %</', $html);
    }

    public function test_a_ward_of_another_facility_is_a_404_and_a_pharmacist_is_403(): void
    {
        [$admin, $facility] = $this->staff('facility_admin');
        [, $other]          = $this->staff('facility_admin');
        $foreign = Ward::create(['facility_id' => $other->id, 'name' => 'Ailleurs', 'ward_type' => 'general', 'total_beds' => 1]);
        $bed     = Bed::create(['ward_id' => $foreign->id, 'bed_number' => 'X-01']);

        $this->asStaff($admin, $facility)
            ->post(route('portals.facility.admin.wards.beds', $foreign->id), ['count' => 1, 'prefix' => 'X'])
            ->assertNotFound();
        $this->asStaff($admin, $facility)
            ->post(route('portals.facility.admin.wards.bed.service', [$foreign->id, $bed->id]), ['in_service' => 0])
            ->assertNotFound();
        $this->asStaff($admin, $facility)
            ->post(route('portals.facility.admin.wards.beds', 'not-a-uuid'), ['count' => 1])
            ->assertNotFound();

        $this->assertSame(1, Bed::where('ward_id', $foreign->id)->count());
        $this->assertSame('available', $bed->fresh()->status);

        // The other facility's ward never shows on this desk.
        $this->asStaff($admin, $facility)->get(route('portals.facility.admin.wards'))->assertOk()->assertDontSee('Ailleurs');

        [$pharmacist, $pharmacy] = $this->staff('pharmacist');
        $this->asStaff($pharmacist, $pharmacy)->get(route('portals.facility.admin.reports'))->assertForbidden();
        $this->asStaff($pharmacist, $pharmacy)->get(route('portals.facility.admin.wards'))->assertForbidden();
        $this->asStaff($pharmacist, $pharmacy)
            ->post(route('portals.facility.admin.wards.create'), ['name' => 'X', 'ward_type' => 'general', 'capacity' => 1])
            ->assertForbidden();
        $this->assertSame(0, Ward::where('facility_id', $pharmacy->id)->count());
    }

    public function test_reports_and_wards_are_linked_from_the_overview_and_the_sidebar(): void
    {
        [$admin, $facility] = $this->staff('facility_admin');

        $this->asStaff($admin, $facility)
            ->get(route('portals.facility.admin.overview'))
            ->assertOk()
            ->assertSee(route('portals.facility.admin.reports'), false)
            ->assertSee(route('portals.facility.admin.wards'), false);
    }
}
