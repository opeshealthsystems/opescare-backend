<?php

namespace Tests\Feature\Admin;

use App\Models\AuditEvent;
use App\Models\ClinicalNote;
use App\Models\CorrectionRequest;
use App\Models\DataExportRequest;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\RecordCorrectionDecision;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use App\Modules\Governance\Services\DataExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;
use Tests\TestCase;

/**
 * The patient-rights desk at /admin/governance.
 *
 * The defect this pins: a patient could file a statutory data-export or
 * record-correction request from the mobile app, the row landed in the
 * database as `pending`, and NOTHING in the product could approve it. The
 * only approval code (`Api\V1\Admin\AdminGovernanceController`) had no route
 * and no screen, and `DataExportService::downloadExport()` refuses anything
 * not `approved`. Every request filed under Cameroon Law No. 2010/012 was
 * therefore permanently unfulfillable.
 *
 * What is pinned here is the round trip and the law around it:
 *
 *  - a filed request appears in the admin queue;
 *  - approving it lets the patient actually obtain the export;
 *  - an unapproved request yields nothing;
 *  - somebody who is NOT the requester obtains nothing either, approved or not;
 *  - a refusal REQUIRES a reason and records it;
 *  - a non-admin is refused with 403, not 404;
 *  - a correction produces an amendment and the ORIGINAL value survives.
 */
class GovernanceRequestsDeskTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role, ?string $primaryFacilityId = null): User
    {
        return User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role)])->id,
            'primary_facility_id' => $primaryFacilityId,
        ]);
    }

    private function admin(): User
    {
        return $this->userWithRole('super_admin', Facility::factory()->create()->id);
    }

    private function patientUser(): User
    {
        return $this->userWithRole('patient');
    }

    private function exportRequest(User $filer, Patient $patient, string $type = 'encounters'): DataExportRequest
    {
        return app(DataExportService::class)->requestExport(
            (string) $patient->id,
            (string) $filer->id,
            $type,
            [],
        );
    }

    private function actingAdmin(User $admin)
    {
        return $this->actingAs($admin)->withSession(['mfa.verified' => true]);
    }

    // ── The queue exists at all ───────────────────────────────────────────

    /** A request a patient filed must be visible to somebody who can act on it. */
    public function test_a_filed_request_appears_in_the_admin_queue(): void
    {
        $patient = Patient::factory()->create();
        $filer   = $this->patientUser();
        $export  = $this->exportRequest($filer, $patient);

        $this->actingAdmin($this->admin())
            ->get('/admin/governance')
            ->assertOk()
            ->assertSee($export->export_type);
    }

    /**
     * The desk must be REACHABLE, not merely routed. A page you can only get
     * to by typing the URL is a page nobody will ever open, which is how the
     * approval code sat unrouted for months in the first place.
     */
    public function test_the_desk_is_linked_from_the_admin_navigation(): void
    {
        $this->actingAdmin($this->admin())
            ->get('/admin/terminology')
            ->assertOk()
            ->assertSee(route('portals.admin.governance.index'), false);
    }

    /** Refusal for a facility-tier user is 403, not a 404 and not a redirect. */
    public function test_a_non_admin_is_refused_with_403(): void
    {
        $facility = Facility::factory()->create();
        $doctor   = $this->userWithRole('doctor', $facility->id);

        $this->actingAs($doctor)
            ->withSession(['mfa.verified' => true])
            ->get('/admin/governance')
            ->assertStatus(403);
    }

    // ── Export: the round trip ────────────────────────────────────────────

    /** Approving through the desk must let the patient actually download. */
    public function test_approving_an_export_lets_the_patient_obtain_it(): void
    {
        $patient = Patient::factory()->create();
        $filer   = $this->patientUser();
        $export  = $this->exportRequest($filer, $patient);

        $this->actingAdmin($this->admin())
            ->post("/admin/governance/exports/{$export->id}/approve", [
                'reason' => 'Statutory right to a copy of the record, Law No. 2010/012.',
            ])
            ->assertRedirect();

        $this->assertSame('approved', $export->fresh()->status);

        $payload = app(DataExportService::class)->downloadExport((string) $export->id, (string) $filer->id);

        $this->assertArrayHasKey('content', $payload);
        $this->assertArrayHasKey('sections', $payload['content']);
        $this->assertSame($patient->health_id, $payload['content']['patient']['health_id']);
        $this->assertSame('downloaded', $export->fresh()->status);

        // The patient is told, IN THE SAME REQUEST. Finder runs no queue
        // worker, so a queued notification would never have been delivered;
        // this asserts the row the in-app bell reads is already there.
        $this->assertDatabaseHas('notification_events', [
            'recipient_user_id' => (string) $filer->id,
            'event_type'        => 'data_export_approved',
        ]);
    }

    /** An unapproved request yields nothing — this is the gate the desk unlocks. */
    public function test_an_unapproved_export_yields_nothing(): void
    {
        $patient = Patient::factory()->create();
        $filer   = $this->patientUser();
        $export  = $this->exportRequest($filer, $patient);

        $this->expectException(UnauthorizedException::class);
        app(DataExportService::class)->downloadExport((string) $export->id, (string) $filer->id);
    }

    /**
     * An export is the patient's WHOLE record. Holding the request id is not
     * authorisation to read it: only the person who filed it may download.
     */
    public function test_an_export_is_not_retrievable_by_anyone_but_its_requester(): void
    {
        $patient  = Patient::factory()->create();
        $filer    = $this->patientUser();
        $stranger = $this->patientUser();
        $export   = $this->exportRequest($filer, $patient);

        $this->actingAdmin($this->admin())
            ->post("/admin/governance/exports/{$export->id}/approve", ['reason' => 'Granted under the law.']);

        $this->expectException(UnauthorizedException::class);
        app(DataExportService::class)->downloadExport((string) $export->id, (string) $stranger->id);
    }

    /** Every decision is audited, and actor_id is a uuid column. */
    public function test_approving_an_export_writes_an_audit_row_with_a_uuid_actor(): void
    {
        $patient = Patient::factory()->create();
        $export  = $this->exportRequest($this->patientUser(), $patient);
        $admin   = $this->admin();

        $this->actingAdmin($admin)
            ->post("/admin/governance/exports/{$export->id}/approve", ['reason' => 'Granted under the law.']);

        $event = AuditEvent::where('action_type', 'governance.export_approved')->firstOrFail();

        $this->assertSame((string) $admin->id, (string) $event->actor_id);
        $this->assertSame('governance_desk', $event->source_system);
        $this->assertSame('Granted under the law.', $event->reason);
    }

    // ── Refusal must be possible, and must carry a reason ─────────────────

    /** A refusal with no reason is rejected by validation, and nothing changes. */
    public function test_refusing_an_export_requires_a_reason(): void
    {
        $export = $this->exportRequest($this->patientUser(), Patient::factory()->create());

        $this->actingAdmin($this->admin())
            ->post("/admin/governance/exports/{$export->id}/refuse", ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertSame('pending', $export->fresh()->status);
    }

    /** A refusal with a reason records the reason on the row. */
    public function test_refusing_an_export_records_the_reason(): void
    {
        $export = $this->exportRequest($this->patientUser(), Patient::factory()->create());

        $this->actingAdmin($this->admin())
            ->post("/admin/governance/exports/{$export->id}/refuse", [
                'reason' => 'Identity of the requester could not be confirmed.',
            ])
            ->assertRedirect();

        $fresh = $export->fresh();
        $this->assertSame('rejected', $fresh->status);
        $this->assertSame('Identity of the requester could not be confirmed.', $fresh->decision_reason);

        $this->assertDatabaseHas('audit_events', [
            'action_type' => 'governance.export_refused',
            'resource_id' => (string) $export->id,
        ]);
    }

    /** A refused export still yields nothing. */
    public function test_a_refused_export_yields_nothing(): void
    {
        $filer  = $this->patientUser();
        $export = $this->exportRequest($filer, Patient::factory()->create());

        $this->actingAdmin($this->admin())
            ->post("/admin/governance/exports/{$export->id}/refuse", ['reason' => 'Not the patient.']);

        $this->expectException(UnauthorizedException::class);
        app(DataExportService::class)->downloadExport((string) $export->id, (string) $filer->id);
    }

    // ── Correction: an amendment, never an overwrite ──────────────────────

    private function disputedNote(Patient $patient, User $clinician): ClinicalNote
    {
        $visit = Visit::create([
            'patient_id'  => $patient->id,
            'facility_id' => Facility::factory()->create()->id,
            'provider_id' => $clinician->id,
            'visit_type'  => 'outpatient',
            'status'      => 'closed',
        ]);

        return ClinicalNote::create([
            'visit_id'                   => $visit->id,
            'provider_id'                => $clinician->id,
            'history_of_present_illness' => 'Patient reports smoking 20 cigarettes a day.',
            'examination_findings'       => 'Chest clear.',
            'treatment_plan'             => 'Smoking cessation counselling.',
            'status'                     => 'signed',
            'signed_at'                  => now(),
        ]);
    }

    /**
     * The platform law: a correction is an amendment. The disputed value must
     * still be readable afterwards, together with who recorded it.
     */
    public function test_approving_a_correction_amends_and_keeps_the_original(): void
    {
        $patient   = Patient::factory()->create();
        $clinician = $this->userWithRole('doctor', Facility::factory()->create()->id);
        $filer     = $this->patientUser();
        $note      = $this->disputedNote($patient, $clinician);

        $correction = CorrectionRequest::create([
            'patient_id'           => $patient->id,
            'requested_by_user_id' => $filer->id,
            'resource_type'        => 'clinical_note',
            'resource_id'          => $note->id,
            'reason'               => 'I have never smoked. This belongs to another patient.',
            'status'               => 'pending',
        ]);

        $this->actingAdmin($this->admin())
            ->post("/admin/governance/corrections/{$correction->id}/approve", [
                'reason' => 'Confirmed with the recording clinician; the history was entered on the wrong chart.',
            ])
            ->assertRedirect();

        $this->assertSame('approved', $correction->fresh()->status);

        // The original row is still there, still says what it said, and still
        // names the clinician who wrote it. Only the status moved.
        $original = ClinicalNote::find($note->id);
        $this->assertNotNull($original);
        $this->assertSame('Patient reports smoking 20 cigarettes a day.', $original->history_of_present_illness);
        $this->assertSame((string) $clinician->id, (string) $original->provider_id);
        $this->assertSame('entered_in_error', $original->status);

        // And an amendment exists that points back at it.
        $amendment = ClinicalNote::where('amends_note_id', $note->id)->first();
        $this->assertNotNull($amendment);
        $this->assertStringContainsString('never smoked', $amendment->treatment_plan);

        // The decision, with its reason, is durable.
        $decision = RecordCorrectionDecision::where('correction_request_id', $correction->id)->firstOrFail();
        $this->assertSame('approved', $decision->decision);
        $this->assertStringContainsString('wrong chart', $decision->decision_reason);
    }

    /** Refusing a correction requires a reason and records it. */
    public function test_refusing_a_correction_requires_and_records_a_reason(): void
    {
        $patient   = Patient::factory()->create();
        $clinician = $this->userWithRole('doctor', Facility::factory()->create()->id);
        $note      = $this->disputedNote($patient, $clinician);

        $correction = CorrectionRequest::create([
            'patient_id'           => $patient->id,
            'requested_by_user_id' => $this->patientUser()->id,
            'resource_type'        => 'clinical_note',
            'resource_id'          => $note->id,
            'reason'               => 'I disagree with this diagnosis.',
            'status'               => 'pending',
        ]);

        $this->actingAdmin($this->admin())
            ->post("/admin/governance/corrections/{$correction->id}/refuse", ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertSame('pending', $correction->fresh()->status);

        $this->actingAdmin($this->admin())
            ->post("/admin/governance/corrections/{$correction->id}/refuse", [
                'reason' => 'The finding is clinically correct and is supported by the laboratory result of the same day.',
            ])
            ->assertRedirect();

        $this->assertSame('rejected', $correction->fresh()->status);
        $this->assertSame('signed', ClinicalNote::find($note->id)->status);

        $decision = RecordCorrectionDecision::where('correction_request_id', $correction->id)->firstOrFail();
        $this->assertSame('rejected', $decision->decision);
        $this->assertStringContainsString('clinically correct', $decision->decision_reason);
    }

    /**
     * A resource type this desk cannot amend safely is REFUSED, not faked.
     * The screen says so, and the approve path writes nothing.
     */
    public function test_an_unsupported_resource_type_is_refused_not_faked(): void
    {
        $patient = Patient::factory()->create();

        $correction = CorrectionRequest::create([
            'patient_id'           => $patient->id,
            'requested_by_user_id' => $this->patientUser()->id,
            'resource_type'        => 'lab_result',
            'resource_id'          => (string) \Illuminate\Support\Str::uuid(),
            'reason'               => 'This haemoglobin value is not mine.',
            'status'               => 'pending',
        ]);

        $this->actingAdmin($this->admin())
            ->get("/admin/governance/corrections/{$correction->id}")
            ->assertOk()
            ->assertSee(__('governance_desk.refusal_type_unsupported'), false);

        $this->actingAdmin($this->admin())
            ->post("/admin/governance/corrections/{$correction->id}/approve", ['reason' => 'Patient says so.'])
            ->assertSessionHas('error');

        $this->assertSame('pending', $correction->fresh()->status);
        $this->assertSame(0, DB::table('record_correction_decisions')->count());
    }
}
