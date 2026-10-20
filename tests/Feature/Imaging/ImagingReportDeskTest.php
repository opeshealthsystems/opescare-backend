<?php

namespace Tests\Feature\Imaging;

use App\Models\AuditEvent;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\ImagingOrder;
use App\Models\MedicalAttachment;
use App\Models\Patient;
use App\Models\RadiologyReport;
use App\Models\Role;
use App\Models\User;
use App\Modules\Notifications\Models\NotificationEvent;
use App\Services\Patient\PatientDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The radiologist's report desk (routes/web.php "Imaging report desk",
 * routes/imaging_desk.php `portals.imaging.reports.index`):
 *
 *   1. A RADIOLOGIST DRAFTS, SIGNS AND DISTRIBUTES — and every look at the
 *      patient's study leaves an audit row.
 *   2. THE TECHNOLOGIST IS REFUSED — `imaging_tech` holds `imaging`, not
 *      `imaging_report`: 403 on every report route.
 *   3. AN AMENDMENT IS A NEW VERSION — the signed text it replaces is kept,
 *      marked superseded, and the order's current report is v2.
 *   4. THE PATIENT RECEIVES IT — the distributed report is in the patient's
 *      documents list, renders as a PDF on demand, rings the
 *      `radiology_report_available` bell, and the uploaded attachment is released.
 *   5. ANOTHER FACILITY'S ORDER DOES NOT EXIST — 404, never 403.
 */
class ImagingReportDeskTest extends TestCase
{
    use RefreshDatabase;

    private const PDF = "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF";

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);

        // The capability map and the two roles are the intake desk's change;
        // this test holds its own contract so it is green on either load order.
        if (! in_array('radiologist', (array) config('staff_capabilities.capabilities.imaging_report', []), true)) {
            config(['staff_capabilities.capabilities.imaging_report' => ['radiologist', 'lab_manager']]);
        }
        if (! in_array('imaging_tech', (array) config('staff_capabilities.capabilities.imaging', []), true)) {
            config(['staff_capabilities.capabilities.imaging' => ['imaging_tech', 'radiologist', 'lab_manager']]);
        }
        foreach (['radiologist' => 'Radiologist', 'imaging_tech' => 'Imaging Technologist'] as $name => $label) {
            if (! Role::where('name', $name)->exists()) {
                Role::create(['name' => $name, 'display_name' => $label, 'description' => $label]);
            }
        }
    }

    private function staff(string $roleName = 'radiologist', ?Facility $facility = null): array
    {
        $role     = Role::where('name', $roleName)->firstOrFail();
        $facility ??= Facility::factory()->create(['type' => 'laboratory']);

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

        return [$user, $facility];
    }

    private function as(User $user, Facility $facility): static
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id]);
    }

    private function patient(): Patient
    {
        return Patient::factory()->create([
            'is_demo'             => false,
            'privacy_preferences' => ['require_consent_for_full_record' => false],
        ]);
    }

    private function order(Facility $facility, User $orderedBy, ?Patient $patient = null, array $overrides = []): ImagingOrder
    {
        return ImagingOrder::create(array_merge([
            'patient_id'          => ($patient ?? $this->patient())->id,
            'facility_id'         => $facility->id,
            'ordered_by'          => $orderedBy->id,
            'modality'            => 'xray',
            'body_part'           => 'Chest',
            'clinical_indication' => 'Cough for three weeks',
            'urgency'             => 'routine',
            'status'              => 'completed',
            'accession_number'    => 'ACC-' . fake()->unique()->numerify('######'),
            'ordered_at'          => now()->subDay(),
            'completed_at'        => now()->subHour(),
        ], $overrides));
    }

    private function text(array $overrides = []): array
    {
        return array_merge([
            'findings'       => 'Lungs are clear. No consolidation, effusion or pneumothorax.',
            'impression'     => 'Normal chest radiograph.',
            'recommendation' => null,
        ], $overrides);
    }

    // ── 1. Draft, finalize, distribute ──────────────────────────────────────

    public function test_radiologist_can_draft_finalize_and_distribute_and_each_step_is_audited(): void
    {
        [$radiologist, $facility] = $this->staff();
        $order = $this->order($facility, $radiologist);

        $this->as($radiologist, $facility)->get(route('portals.imaging.reports.index'))
            ->assertOk()
            ->assertSee($order->accession_number);

        $this->as($radiologist, $facility)->get(route('portals.imaging.report.edit', $order->id))
            ->assertOk()
            ->assertSee($order->accession_number)
            ->assertDontSee($order->patient->date_of_birth);

        $this->as($radiologist, $facility)
            ->post(route('portals.imaging.report.save', $order->id), $this->text())
            ->assertRedirect(route('portals.imaging.report.edit', $order->id))
            ->assertSessionHasNoErrors();

        $report = RadiologyReport::where('imaging_order_id', $order->id)->firstOrFail();
        $this->assertSame('draft', $report->status);
        $this->assertSame(1, $report->version);
        $this->assertSame($radiologist->id, $report->reported_by);

        // The draft is not a document the patient can have.
        $this->assertSame([], app(PatientDocumentService::class)->available($order->patient));

        $this->as($radiologist, $facility)
            ->post(route('portals.imaging.report.finalize', $order->id), $this->text(['impression' => 'Normal chest radiograph. No follow-up.']))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $report->refresh();
        $this->assertSame('final', $report->status);
        $this->assertNotNull($report->finalized_at);
        $this->assertNull($report->distributed_at);
        $this->assertSame('Normal chest radiograph. No follow-up.', $report->impression);

        // Signed: editing in place is refused, the amendment path is the only way.
        $this->as($radiologist, $facility)
            ->post(route('portals.imaging.report.save', $order->id), $this->text(['impression' => 'Sneaky edit']))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame('Normal chest radiograph. No follow-up.', $report->fresh()->impression);

        $this->as($radiologist, $facility)
            ->post(route('portals.imaging.report.distribute', $order->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertNotNull($report->distributed_at);

        foreach (['imaging_report_view', 'imaging_report_draft_saved', 'imaging_report_finalized', 'imaging_report_distributed'] as $action) {
            $this->assertTrue(
                AuditEvent::where('action_type', $action)->where('patient_id', $order->patient_id)->exists(),
                "audit row {$action} missing"
            );
        }

        // The signed report shows on the detail panel and in the queue as handed over.
        $page = $this->as($radiologist, $facility)->get(route('portals.imaging.report.edit', $order->id))->assertOk();
        $page->assertSee('Normal chest radiograph. No follow-up.');
    }

    // ── 2. The technologist is refused ──────────────────────────────────────

    public function test_imaging_tech_gets_403_on_every_report_route(): void
    {
        [$tech, $facility] = $this->staff('imaging_tech');
        $order = $this->order($facility, $tech);

        $this->as($tech, $facility)->get(route('portals.imaging.reports.index'))->assertForbidden();
        $this->as($tech, $facility)->get(route('portals.imaging.report.edit', $order->id))->assertForbidden();
        $this->as($tech, $facility)->post(route('portals.imaging.report.save', $order->id), $this->text())->assertForbidden();
        $this->as($tech, $facility)->post(route('portals.imaging.report.finalize', $order->id), $this->text())->assertForbidden();
        $this->as($tech, $facility)->post(route('portals.imaging.report.amend', $order->id), $this->text(['reason' => 'x']))->assertForbidden();
        $this->as($tech, $facility)->post(route('portals.imaging.report.distribute', $order->id))->assertForbidden();
        $this->as($tech, $facility)->get(route('portals.imaging.report.attachment', $order->id))->assertForbidden();

        $this->assertSame(0, RadiologyReport::count());
    }

    // ── 3. Amendment is a new version ───────────────────────────────────────

    public function test_amend_creates_a_new_version_and_keeps_the_old_one(): void
    {
        [$radiologist, $facility] = $this->staff();
        $order = $this->order($facility, $radiologist);

        $this->as($radiologist, $facility)->post(route('portals.imaging.report.finalize', $order->id), $this->text())->assertRedirect();
        $v1 = RadiologyReport::where('imaging_order_id', $order->id)->firstOrFail();
        $this->as($radiologist, $facility)->post(route('portals.imaging.report.distribute', $order->id))->assertRedirect();

        // A reason is required.
        $this->as($radiologist, $facility)
            ->post(route('portals.imaging.report.amend', $order->id), $this->text(['impression' => 'Small right effusion.']))
            ->assertSessionHasErrors('reason');

        $this->as($radiologist, $facility)
            ->post(route('portals.imaging.report.amend', $order->id), $this->text([
                'impression' => 'Small right pleural effusion.',
                'reason'     => 'Effusion overlooked on first read',
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(2, RadiologyReport::where('imaging_order_id', $order->id)->count());

        $v1->refresh();
        $this->assertSame('final', $v1->status);
        $this->assertNotNull($v1->superseded_at);
        $this->assertSame('Normal chest radiograph.', $v1->impression, 'the old text must be kept');

        $v2 = RadiologyReport::where('imaging_order_id', $order->id)->whereNull('superseded_at')->firstOrFail();
        $this->assertSame(2, $v2->version);
        $this->assertSame('amended', $v2->status);
        $this->assertSame($v1->id, $v2->supersedes_report_id);
        $this->assertSame('Small right pleural effusion.', $v2->impression);
        $this->assertSame('Effusion overlooked on first read', $v2->amendment_reason);
        $this->assertNull($v2->distributed_at, 'an amendment is handed over as its own act');

        // The patient sees NO version until v2 is distributed: v1 is withdrawn.
        $this->assertSame([], app(PatientDocumentService::class)->available($order->patient));

        $this->as($radiologist, $facility)->post(route('portals.imaging.report.distribute', $order->id))->assertRedirect();
        $available = app(PatientDocumentService::class)->available($order->patient);
        $this->assertCount(1, $available);
        $this->assertSame($v2->id, $available[0]['record']);

        // Both versions are on the page; the old one is labelled superseded.
        $this->as($radiologist, $facility)->get(route('portals.imaging.report.edit', $order->id))
            ->assertOk()
            ->assertSee('Small right pleural effusion.')
            ->assertSee('Normal chest radiograph.')
            ->assertSee(__('imaging_report.status.superseded'));
    }

    // ── 4. The patient receives it ──────────────────────────────────────────

    public function test_distributed_report_appears_in_patient_documents_and_renders_a_pdf(): void
    {
        [$radiologist, $facility] = $this->staff();
        $patient = $this->patient();
        $order   = $this->order($facility, $radiologist, $patient);
        $patientUser = User::factory()->create(['patient_id' => $patient->id]);

        $this->as($radiologist, $facility)
            ->post(route('portals.imaging.report.finalize', $order->id), $this->text() + [
                'attachment' => UploadedFile::fake()->createWithContent('report.pdf', self::PDF),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $report = RadiologyReport::where('imaging_order_id', $order->id)->firstOrFail();
        $attachment = MedicalAttachment::where('radiology_report_id', $report->id)->firstOrFail();
        $this->assertNull($attachment->released_at, 'the file reaches the patient only at distribution');

        // Before distribution: nothing for the patient, no bell, the sheet 404s.
        $this->assertSame([], app(PatientDocumentService::class)->available($patient));
        $this->actingAs($patientUser)
            ->get(route('portals.patient.documents.render', ['type' => PatientDocumentService::IMAGING_REPORT, 'record' => $report->id]))
            ->assertNotFound();

        $this->as($radiologist, $facility)->post(route('portals.imaging.report.distribute', $order->id))->assertRedirect();

        $available = app(PatientDocumentService::class)->available($patient);
        $this->assertCount(1, $available);
        $this->assertSame(PatientDocumentService::IMAGING_REPORT, $available[0]['type']);
        $this->assertSame($report->id, $available[0]['record']);
        $this->assertSame($order->accession_number, $available[0]['reference']);

        $this->assertNotNull($attachment->fresh()->released_at);

        $this->actingAs($patientUser)->get(route('portals.patient.documents'))
            ->assertOk()
            ->assertSee(__('imaging_report.doc.title'));

        $response = $this->actingAs($patientUser)
            ->get(route('portals.patient.documents.render', ['type' => PatientDocumentService::IMAGING_REPORT, 'record' => $report->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->streamedContent());

        // The sheet's QR resolves through the document register.
        $this->assertDatabaseHas('document_verifications', [
            'patient_id'    => $patient->id,
            'document_type' => PatientDocumentService::IMAGING_REPORT,
        ]);

        // The imaging twin of lab_result_available rang.
        $this->assertTrue(
            DB::table('notifications')->where('notifiable_id', $patient->id)->where('type', 'radiology_report_available')->exists()
        );

        // A stranger's record id is not theirs: 404.
        $otherUser = User::factory()->create(['patient_id' => $this->patient()->id]);
        $this->actingAs($otherUser)
            ->get(route('portals.patient.documents.render', ['type' => PatientDocumentService::IMAGING_REPORT, 'record' => $report->id]))
            ->assertNotFound();

        // The radiologist can pull the attachment back; the download is logged.
        $this->as($radiologist, $facility)->get(route('portals.imaging.report.attachment', $order->id))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    // ── 5. Cross-facility ───────────────────────────────────────────────────

    public function test_an_order_at_another_facility_is_not_found(): void
    {
        [$radiologist, $facility] = $this->staff();
        [$theirs, $theirFacility] = $this->staff();
        $order = $this->order($theirFacility, $theirs);

        $this->as($radiologist, $facility)->get(route('portals.imaging.report.edit', $order->id))->assertNotFound();
        $this->as($radiologist, $facility)->post(route('portals.imaging.report.save', $order->id), $this->text())->assertNotFound();
        $this->as($radiologist, $facility)->post(route('portals.imaging.report.finalize', $order->id), $this->text())->assertNotFound();
        $this->as($radiologist, $facility)->post(route('portals.imaging.report.distribute', $order->id))->assertNotFound();
        $this->as($radiologist, $facility)->get(route('portals.imaging.report.attachment', $order->id))->assertNotFound();

        // Their queue does not list it either.
        $this->as($radiologist, $facility)->get(route('portals.imaging.reports.index'))
            ->assertOk()
            ->assertDontSee($order->accession_number);

        $this->assertSame(0, RadiologyReport::count());
    }

    /** DX-3: distributing a report tells the clinician who ordered the study. */
    public function test_distribution_notifies_the_ordering_clinician_with_a_link_to_the_report(): void
    {
        [$radiologist, $facility] = $this->staff();
        $doctor = User::factory()->create(['status' => 'active', 'primary_facility_id' => $facility->id]);
        $order  = $this->order($facility, $doctor);

        $this->as($radiologist, $facility)
            ->post(route('portals.imaging.report.finalize', $order->id), $this->text())
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->as($radiologist, $facility)
            ->post(route('portals.imaging.report.distribute', $order->id))
            ->assertRedirect();

        $report = RadiologyReport::where('imaging_order_id', $order->id)->firstOrFail();
        $bell   = NotificationEvent::where('recipient_user_id', $doctor->id)
            ->where('event_type', 'radiology_report_ready')
            ->first();

        $this->assertNotNull($bell, 'the ordering clinician is told the report is out');
        $payload = json_decode((string) $bell->payload_json, true);
        $this->assertSame('/clinician/results/rad-' . $report->id, $payload['url']);
        $this->assertSame($report->id, $payload['radiology_report_id']);
        $this->assertNotSame('', (string) $payload['title']);
        $this->assertStringNotContainsString('imaging_report.', (string) $payload['message'], 'the bell text is translated');
        $this->assertSame('RadiologyReport', $bell->related_resource_type);

        // The radiologist distributing it is not pinged about their own work.
        $this->assertSame(0, NotificationEvent::where('recipient_user_id', $radiologist->id)
            ->where('event_type', 'radiology_report_ready')->count());
    }

    public function test_no_clinician_bell_when_the_radiologist_ordered_the_study_themselves(): void
    {
        [$radiologist, $facility] = $this->staff();
        $order = $this->order($facility, $radiologist);

        $this->as($radiologist, $facility)
            ->post(route('portals.imaging.report.finalize', $order->id), $this->text())
            ->assertRedirect();
        $this->as($radiologist, $facility)
            ->post(route('portals.imaging.report.distribute', $order->id))
            ->assertRedirect();

        $this->assertSame(0, NotificationEvent::where('event_type', 'radiology_report_ready')->count());
    }
}
