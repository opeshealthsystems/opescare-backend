<?php

namespace Tests\Feature\Security;

use App\Http\Controllers\MedicalId\StaffHRPortalController;
use App\Models\Admission;
use App\Models\Bed;
use App\Models\BridgeAgent;
use App\Models\DutyRoster;
use App\Models\Facility;
use App\Models\FileAsset;
use App\Models\ImportJob;
use App\Models\LeaveRequest;
use App\Models\Patient;
use App\Models\Role;
use App\Models\RosterAssignment;
use App\Models\StaffProfile;
use App\Models\StaffShift;
use App\Models\User;
use App\Models\Visit;
use App\Models\Ward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Cross-facility isolation for the HR and operations portals.
 *
 * The bug this suite pins down had one shape and two halves. Controllers
 * answered "which facility am I acting for?" with `Facility::value('id')` —
 * whichever row Postgres returned first out of 345 — and then looked their
 * records up with a bare `Model::findOrFail($id)`, with the id taken straight
 * off the URL. Either half alone is enough to act on another hospital's data;
 * fixing only the first would have left every `{id}` route wide open.
 *
 * So each surface is asserted three ways:
 *
 *   READ    — facility A's page never contains facility B's rows.
 *   WRITE   — a form submitted at A cannot be aimed at B by naming B's ids.
 *   ACT     — an action route cannot be pointed at B's record by its id.
 *
 * plus the fourth property that made the original helper dangerous:
 *
 *   FAIL CLOSED — with no facility in session and more than one facility in
 *   the table, the request errors instead of quietly picking one.
 */
class CrossFacilityHrAndOpsTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facilityA;
    private Facility $facilityB;
    private User $staffAtA;

    protected function setUp(): void
    {
        parent::setUp();

        // Two facilities: the whole point is that "the first row in the table"
        // is never the right answer.
        $this->facilityA = Facility::factory()->create(['name' => 'Bamenda Regional']);
        $this->facilityB = Facility::factory()->create(['name' => 'Douala General']);

        $this->staffAtA = $this->staffUser($this->facilityA, 'nurse_supervisor');
    }

    // ── Fixtures ─────────────────────────────────────────────────────────

    private function role(string $name): Role
    {
        return Role::firstOrCreate(['name' => $name], ['description' => ucfirst($name)]);
    }

    private function staffUser(Facility $facility, string $roleName): User
    {
        $user = User::factory()->create(['primary_facility_id' => $facility->id]);
        $user->role_id = $this->role($roleName)->id;
        $user->save();

        return $user->fresh();
    }

    private function staffProfile(Facility $facility, string $lastName = 'Nkemcha'): StaffProfile
    {
        return StaffProfile::create([
            'facility_id'     => $facility->id,
            'first_name'      => 'Ada',
            'last_name'       => $lastName,
            'staff_category'  => 'nursing',
            'employment_type' => 'full_time',
            'department'      => 'maternity',
            'status'          => 'active',
        ]);
    }

    private function shift(Facility $facility): StaffShift
    {
        return StaffShift::create([
            'facility_id' => $facility->id,
            'name'        => 'Night',
            'start_time'  => '20:00:00',
            'end_time'    => '06:00:00',
            'status'      => 'active',
        ]);
    }

    private function roster(Facility $facility, string $status = 'draft'): DutyRoster
    {
        return DutyRoster::create([
            'facility_id'  => $facility->id,
            'department'   => 'maternity',
            'period_start' => now()->toDateString(),
            'period_end'   => now()->addWeek()->toDateString(),
            'status'       => $status,
        ]);
    }

    private function leaveRequest(StaffProfile $profile): LeaveRequest
    {
        return LeaveRequest::create([
            'staff_profile_id' => $profile->id,
            'leave_type'       => 'annual',
            'start_date'       => now()->addDay()->toDateString(),
            'end_date'         => now()->addDays(3)->toDateString(),
            'days_requested'   => 3,
            'status'           => 'pending',
        ]);
    }

    // ── Roster: READ ─────────────────────────────────────────────────────

    public function test_the_roster_page_shows_only_this_facilitys_rosters(): void
    {
        $mine     = $this->roster($this->facilityA);
        $theirs   = $this->roster($this->facilityB);
        $mineOnly = $this->staffProfile($this->facilityA, 'Ours');
        $this->staffProfile($this->facilityB, 'Theirs');

        $response = $this->actingAs($this->staffAtA)->get(route('portals.staff.hr.roster'));

        $response->assertOk();

        $rosterIds = collect($response->viewData('rosters'))->pluck('id');
        $this->assertTrue($rosterIds->contains($mine->id), 'our own roster must be listed');
        $this->assertFalse($rosterIds->contains($theirs->id), "another facility's roster must never be listed");

        $staffIds = collect($response->viewData('staff'))->pluck('id');
        $this->assertSame([$mineOnly->id], $staffIds->all(), 'the staff picker must not offer another facility’s people');
    }

    // ── Roster: ACT on someone else's record ─────────────────────────────

    public function test_another_facilitys_roster_cannot_be_published_by_id(): void
    {
        $theirs = $this->roster($this->facilityB);

        $this->actingAs($this->staffAtA)
            ->post(route('portals.staff.hr.roster.publish', $theirs->id))
            ->assertNotFound();

        $this->assertSame('draft', $theirs->fresh()->status, "the other facility's roster must be untouched");
    }

    public function test_another_facilitys_roster_cannot_be_archived_by_id(): void
    {
        $theirs = $this->roster($this->facilityB);

        $this->actingAs($this->staffAtA)
            ->post(route('portals.staff.hr.roster.archive', $theirs->id))
            ->assertNotFound();

        $this->assertSame('draft', $theirs->fresh()->status);
    }

    // ── Roster: WRITE into someone else's data ───────────────────────────

    public function test_a_roster_assignment_cannot_name_another_facilitys_staff(): void
    {
        $myRoster    = $this->roster($this->facilityA);
        $myShift     = $this->shift($this->facilityA);
        $theirPerson = $this->staffProfile($this->facilityB, 'Theirs');

        $this->actingAs($this->staffAtA)
            ->post(route('portals.staff.hr.roster.assign', $myRoster->id), [
                'staff_profile_id' => $theirPerson->id,
                'staff_shift_id'   => $myShift->id,
                'work_date'        => now()->addDay()->toDateString(),
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('roster_assignments', 0);
    }

    public function test_a_roster_assignment_cannot_be_written_into_another_facilitys_roster(): void
    {
        $theirRoster = $this->roster($this->facilityB);
        $myShift     = $this->shift($this->facilityA);
        $myPerson    = $this->staffProfile($this->facilityA);

        $this->actingAs($this->staffAtA)
            ->post(route('portals.staff.hr.roster.assign', $theirRoster->id), [
                'staff_profile_id' => $myPerson->id,
                'staff_shift_id'   => $myShift->id,
                'work_date'        => now()->addDay()->toDateString(),
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('roster_assignments', 0);
    }

    public function test_another_facilitys_assignment_cannot_be_removed(): void
    {
        $theirRoster  = $this->roster($this->facilityB);
        $theirShift   = $this->shift($this->facilityB);
        $theirPerson  = $this->staffProfile($this->facilityB, 'Theirs');

        $assignment = RosterAssignment::create([
            'duty_roster_id'   => $theirRoster->id,
            'staff_profile_id' => $theirPerson->id,
            'staff_shift_id'   => $theirShift->id,
            'work_date'        => now()->addDay()->toDateString(),
            'status'           => 'scheduled',
        ]);

        $this->actingAs($this->staffAtA)
            ->delete(route('portals.staff.hr.roster.unassign', $assignment->id))
            ->assertNotFound();

        $this->assertDatabaseHas('roster_assignments', ['id' => $assignment->id]);
    }

    public function test_a_roster_is_stamped_with_the_session_facility_not_the_submitted_one(): void
    {
        $this->actingAs($this->staffAtA)
            ->post(route('portals.staff.hr.roster.store'), [
                'department'   => 'surgery',
                'period_start' => now()->toDateString(),
                'period_end'   => now()->addWeek()->toDateString(),
                // A tampered field naming the other hospital.
                'facility_id'  => $this->facilityB->id,
            ])
            ->assertRedirect(route('portals.staff.hr.roster'));

        $roster = DutyRoster::where('department', 'surgery')->firstOrFail();
        $this->assertSame($this->facilityA->id, $roster->facility_id, 'the form must not be able to choose the facility');
    }

    // ── Leave: READ / ACT / WRITE ────────────────────────────────────────

    public function test_the_leave_page_shows_only_this_facilitys_requests(): void
    {
        $mine   = $this->leaveRequest($this->staffProfile($this->facilityA, 'Ours'));
        $theirs = $this->leaveRequest($this->staffProfile($this->facilityB, 'Theirs'));

        $response = $this->actingAs($this->staffAtA)->get(route('portals.staff.hr.leave'));

        $response->assertOk();

        $ids = collect($response->viewData('requests'))->pluck('id');
        $this->assertTrue($ids->contains($mine->id));
        $this->assertFalse($ids->contains($theirs->id), "another facility's leave request must never be listed");
    }

    public function test_another_facilitys_leave_request_cannot_be_approved(): void
    {
        $theirs = $this->leaveRequest($this->staffProfile($this->facilityB, 'Theirs'));

        $this->actingAs($this->staffAtA)
            ->post(route('portals.staff.hr.leave.approve', $theirs->id), ['review_notes' => 'ok'])
            ->assertNotFound();

        $this->assertSame('pending', $theirs->fresh()->status);
        $this->assertNull($theirs->fresh()->reviewed_by);
    }

    public function test_another_facilitys_leave_request_cannot_be_rejected_or_withdrawn(): void
    {
        $theirs = $this->leaveRequest($this->staffProfile($this->facilityB, 'Theirs'));

        $this->actingAs($this->staffAtA)
            ->post(route('portals.staff.hr.leave.reject', $theirs->id), ['review_notes' => 'no'])
            ->assertNotFound();

        $this->actingAs($this->staffAtA)
            ->post(route('portals.staff.hr.leave.withdraw', $theirs->id))
            ->assertNotFound();

        $this->assertSame('pending', $theirs->fresh()->status);
    }

    public function test_leave_cannot_be_booked_against_another_facilitys_staff(): void
    {
        $theirPerson = $this->staffProfile($this->facilityB, 'Theirs');

        $this->actingAs($this->staffAtA)
            ->post(route('portals.staff.hr.leave.store'), [
                'staff_profile_id' => $theirPerson->id,
                'leave_type'       => 'annual',
                'start_date'       => now()->addDay()->toDateString(),
                'end_date'         => now()->addDays(2)->toDateString(),
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('leave_requests', 0);
    }

    // ── Directory ────────────────────────────────────────────────────────

    public function test_the_directory_lists_only_this_facilitys_staff(): void
    {
        $mine   = $this->staffProfile($this->facilityA, 'Ours');
        $theirs = $this->staffProfile($this->facilityB, 'Theirs');

        $response = $this->actingAs($this->staffAtA)->get(route('portals.staff.hr.directory'));

        $response->assertOk();

        $ids = collect($response->viewData('staff'))->pluck('id');
        $this->assertTrue($ids->contains($mine->id));
        $this->assertFalse($ids->contains($theirs->id));
    }

    public function test_another_facilitys_staff_status_cannot_be_changed(): void
    {
        $theirs = $this->staffProfile($this->facilityB, 'Theirs');

        $this->actingAs($this->staffAtA)
            ->post(route('portals.staff.hr.directory.status', $theirs->id), ['status' => 'terminated'])
            ->assertNotFound();

        $this->assertSame('active', $theirs->fresh()->status);
    }

    public function test_a_license_cannot_be_added_to_another_facilitys_staff(): void
    {
        $theirs = $this->staffProfile($this->facilityB, 'Theirs');

        $this->actingAs($this->staffAtA)
            ->post(route('portals.staff.hr.directory.license', $theirs->id), [
                'profession'     => 'nursing',
                'license_number' => 'LIC-1',
                'issuing_body'   => 'MINSANTE',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('professional_licenses', 0);
    }

    public function test_another_facilitys_shift_cannot_be_toggled(): void
    {
        $theirs = $this->shift($this->facilityB);

        $this->actingAs($this->staffAtA)
            ->post(route('portals.staff.hr.shifts.toggle', $theirs->id))
            ->assertNotFound();

        $this->assertSame('active', $theirs->fresh()->status);
    }

    // ── Fail closed ──────────────────────────────────────────────────────

    /**
     * With no facility in session and more than one facility in the table,
     * there is no safe guess — and this is exactly what the old
     * `Facility::value('id')` helper did instead: return one anyway.
     *
     * The staff portal's own middleware (RequireFacilityContext) means an HTTP
     * request never reaches these actions without a facility, so the property
     * is asserted where it lives: on the controller itself.
     */
    public function test_hr_actions_fail_closed_when_no_facility_is_resolved(): void
    {
        $this->assertSame(2, Facility::count(), 'the fallback is only safe for a single-facility deployment');

        $controller = app(StaffHRPortalController::class);

        try {
            $controller->roster(Request::create('/portals/staff/hr/roster'));
            $this->fail('the roster page must not resolve a facility out of the table when none is in session');
        } catch (HttpException $e) {
            $this->assertSame(409, $e->getStatusCode());
        }
    }

    public function test_a_single_facility_deployment_still_resolves(): void
    {
        // The one condition that made the fallback safe: exactly one facility,
        // so "the first row" and "the only row" are the same thing.
        Facility::whereKey($this->facilityB->id)->delete();
        $this->assertSame(1, Facility::count());

        $controller = app(StaffHRPortalController::class);
        $view = $controller->roster(Request::create('/portals/staff/hr/roster'));

        $this->assertSame('portals.staff.hr.roster', $view->name());
    }

    public function test_a_bridge_agent_cannot_be_registered_without_a_facility_in_session(): void
    {
        // A platform-tier account bypasses RequireFacilityContext, so it really
        // does reach a portal action with no facility resolved. The agent key it
        // would mint is a long-lived write credential — better a 409 than a key
        // for whichever hospital sorted first.
        $platformAdmin = User::factory()->create(['primary_facility_id' => null]);
        $platformAdmin->role_id = $this->role('platform_admin')->id;
        $platformAdmin->save();

        $this->actingAs($platformAdmin->fresh())
            ->post(route('portals.admin.bridge.store'), ['name' => 'On-prem agent'])
            ->assertStatus(409);

        $this->assertDatabaseCount('bridge_agents', 0);
    }

    // ── Neighbouring surfaces: same bug, same proof ──────────────────────

    public function test_the_visit_list_and_patient_picker_are_facility_scoped(): void
    {
        $minePatient   = Patient::factory()->create(['facility_id' => $this->facilityA->id]);
        $theirsPatient = Patient::factory()->create(['facility_id' => $this->facilityB->id]);

        $mine = Visit::create([
            'patient_id' => $minePatient->id, 'facility_id' => $this->facilityA->id,
            'visit_type' => 'general', 'status' => 'open', 'started_at' => now(),
        ]);
        $theirs = Visit::create([
            'patient_id' => $theirsPatient->id, 'facility_id' => $this->facilityB->id,
            'visit_type' => 'general', 'status' => 'open', 'started_at' => now(),
        ]);

        $response = $this->actingAs($this->staffAtA)->get(route('portals.staff.visits'));
        $response->assertOk();

        $visitIds = collect($response->viewData('visits'))->pluck('id');
        $this->assertTrue($visitIds->contains($mine->id));
        $this->assertFalse($visitIds->contains($theirs->id), "another facility's visits must never be listed");

        $patientIds = collect($response->viewData('patients'))->pluck('id');
        $this->assertFalse(
            $patientIds->contains($theirsPatient->id),
            'the patient picker must not enumerate patients this facility has no relationship with'
        );
    }

    public function test_another_facilitys_visit_cannot_be_opened_or_cancelled(): void
    {
        $patient = Patient::factory()->create(['facility_id' => $this->facilityB->id]);
        $theirs  = Visit::create([
            'patient_id' => $patient->id, 'facility_id' => $this->facilityB->id,
            'visit_type' => 'general', 'status' => 'open', 'started_at' => now(),
        ]);

        $this->actingAs($this->staffAtA)
            ->get(route('portals.staff.visits.consult', $theirs->id))
            ->assertNotFound();

        $this->actingAs($this->staffAtA)
            ->post(route('portals.staff.visits.cancel', $theirs->id))
            ->assertNotFound();

        $this->assertSame('open', $theirs->fresh()->status);
    }

    public function test_another_facilitys_import_job_cannot_be_read_or_approved(): void
    {
        $theirs = ImportJob::create([
            'facility_id'       => $this->facilityB->id,
            'import_type'       => 'patients',
            'status'            => 'validated',
            'original_filename' => 'douala-patients.csv',
            'stored_path'       => 'imports/douala-patients.csv',
            'file_extension'    => 'csv',
        ]);

        $this->actingAs($this->staffAtA)
            ->get(route('portals.staff.data_import.preview', $theirs->id))
            ->assertNotFound();

        $this->actingAs($this->staffAtA)
            ->post(route('portals.staff.data_import.approve', $theirs->id))
            ->assertNotFound();

        $this->assertSame('validated', $theirs->fresh()->status, 'the job must not have been approved');
    }

    public function test_another_facilitys_file_cannot_be_downloaded(): void
    {
        $theirs = FileAsset::create([
            'original_name' => 'scan.pdf',
            'stored_name'   => 'scan-1.pdf',
            'disk'          => 'local',
            'path'          => 'files/scan-1.pdf',
            'mime_type'     => 'application/pdf',
            'size_bytes'    => 10,
            'facility_id'   => $this->facilityB->id,
        ]);

        $this->actingAs($this->staffAtA)
            ->get(route('portals.staff.files.download', $theirs->id))
            ->assertNotFound();
    }

    public function test_another_facilitys_admission_cannot_be_discharged(): void
    {
        $ward = Ward::create([
            'facility_id' => $this->facilityB->id,
            'name'        => 'Maternity',
            'ward_type'   => 'maternity',
            'total_beds'  => 1,
            'is_active'   => true,
        ]);
        $bed = Bed::create(['ward_id' => $ward->id, 'bed_number' => 'M-01', 'status' => 'occupied']);
        $patient = Patient::factory()->create(['facility_id' => $this->facilityB->id]);

        $admission = Admission::create([
            'facility_id' => $this->facilityB->id,
            'patient_id'  => $patient->id,
            'bed_id'      => $bed->id,
            'admitted_by' => 'someone@douala.test',
            'status'      => 'active',
            'admitted_at' => now(),
        ]);

        $this->actingAs($this->staffAtA)
            ->post(route('portals.staff.wards.discharge', $admission->id), [
                'discharge_destination' => 'home',
            ])
            ->assertNotFound();

        $this->assertSame('active', $admission->fresh()->status);
    }

    public function test_a_patient_cannot_be_admitted_into_another_facilitys_bed(): void
    {
        $theirWard = Ward::create([
            'facility_id' => $this->facilityB->id,
            'name'        => 'Surgical',
            'ward_type'   => 'surgical',
            'total_beds'  => 1,
            'is_active'   => true,
        ]);
        $theirBed = Bed::create(['ward_id' => $theirWard->id, 'bed_number' => 'S-01', 'status' => 'available']);
        $patient  = Patient::factory()->create(['facility_id' => $this->facilityA->id]);

        $this->actingAs($this->staffAtA)
            ->post(route('portals.staff.wards.admit'), [
                'patient_id' => $patient->id,
                'bed_id'     => $theirBed->id,
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('admissions', 0);
        $this->assertSame('available', $theirBed->fresh()->status);
    }
}
