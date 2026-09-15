<?php

namespace Tests\Feature\Imports;

use App\Models\AccessLog;
use App\Models\Appointment;
use App\Models\AttachmentAccessLog;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\FileAsset;
use App\Models\MedicalAttachment;
use App\Models\Patient;
use App\Models\RadiologyReport;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Group A of docs/PLATFORM_IMPORTS.md: the platform's file attachments and
 * radiology report release, behind the Finder's chart gate, facility scope,
 * signed downloads, void-not-delete and release-to-patient.
 */
class PatientFilesTest extends TestCase
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
    }

    private function staff(): array
    {
        $role     = Role::where('name', 'doctor')->firstOrFail();
        $facility = Facility::factory()->create(['type' => 'hospital']);

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

    private function basis(User $doctor, Facility $facility, Patient $patient): void
    {
        Appointment::create([
            'patient_id'       => $patient->id,
            'facility_id'      => $facility->id,
            'provider_id'      => $doctor->id,
            'appointment_type' => 'consultation',
            'status'           => 'scheduled',
            'scheduled_at'     => now()->addDay(),
        ]);
    }

    private function upload(User $doctor, Facility $facility, Patient $patient, string $category = 'report'): MedicalAttachment
    {
        $this->as($doctor, $facility)
            ->post(route('portals.files.store', ['patientId' => $patient->id]), [
                'file'        => UploadedFile::fake()->createWithContent('report.pdf', self::PDF),
                'category'    => $category,
                'description' => 'Chest X-ray report',
            ])
            ->assertRedirect(route('portals.files.index', ['patientId' => $patient->id]))
            ->assertSessionHasNoErrors();

        return MedicalAttachment::with('fileAsset')->latest()->firstOrFail();
    }

    public function test_upload_list_and_signed_download_writes_an_access_log(): void
    {
        [$doctor, $facility] = $this->staff();
        $patient = $this->patient();
        $this->basis($doctor, $facility, $patient);

        $attachment = $this->upload($doctor, $facility, $patient);
        $asset      = $attachment->fileAsset;

        $this->assertSame('application/pdf', $asset->mime_type);
        $this->assertSame($facility->id, $asset->facility_id);
        Storage::disk('local')->assertExists($asset->path);

        $this->as($doctor, $facility)
            ->get(route('portals.files.index', ['patientId' => $patient->id]))
            ->assertOk()
            ->assertSee('report.pdf')
            ->assertSee('signature=', false);

        // Unsigned: refused.
        $this->as($doctor, $facility)
            ->get(route('portals.files.download', $attachment->id))
            ->assertForbidden();

        $signed = URL::temporarySignedRoute('portals.files.download', now()->addMinutes(5), ['id' => $attachment->id]);
        $this->as($doctor, $facility)->get($signed)->assertOk();

        $this->assertSame(1, AttachmentAccessLog::where('file_asset_id', $asset->id)->where('accessed_by', $doctor->id)->where('access_type', 'download')->count());
    }

    public function test_no_basis_is_a_404_with_a_denied_row(): void
    {
        [$doctor, $facility] = $this->staff();
        $patient = $this->patient();

        $this->as($doctor, $facility)
            ->get(route('portals.files.index', ['patientId' => $patient->id]))
            ->assertNotFound();

        $this->as($doctor, $facility)
            ->post(route('portals.files.store', ['patientId' => $patient->id]), [
                'file'     => UploadedFile::fake()->createWithContent('report.pdf', self::PDF),
                'category' => 'report',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('access_logs', [
            'patient_id'  => $patient->id,
            'actor_id'    => $doctor->id,
            'access_type' => 'denied',
        ]);
        $this->assertSame(0, MedicalAttachment::count());
    }

    public function test_another_facilitys_file_is_a_404(): void
    {
        [$doctor, $facility] = $this->staff();
        [$other, $otherFacility] = $this->staff();
        $patient = $this->patient();
        $this->basis($doctor, $facility, $patient);
        $this->basis($other, $otherFacility, $patient);

        $attachment = $this->upload($doctor, $facility, $patient);

        $signed = URL::temporarySignedRoute('portals.files.download', now()->addMinutes(5), ['id' => $attachment->id]);
        $this->as($other, $otherFacility)->get($signed)->assertNotFound();
        $this->as($other, $otherFacility)
            ->post(route('portals.files.void', $attachment->id), ['reason' => 'not mine'])
            ->assertNotFound();

        // And it is not listed at the other facility.
        $this->as($other, $otherFacility)
            ->get(route('portals.files.index', ['patientId' => $patient->id]))
            ->assertOk()
            ->assertDontSee('report.pdf');

        $this->assertSame(0, AttachmentAccessLog::count());
    }

    public function test_void_keeps_the_bytes_and_hides_the_file_from_the_patient(): void
    {
        [$doctor, $facility] = $this->staff();
        $patient = $this->patient();
        $this->basis($doctor, $facility, $patient);
        $patientUser = User::factory()->create(['patient_id' => $patient->id]);

        $attachment = $this->upload($doctor, $facility, $patient);
        $this->as($doctor, $facility)->post(route('portals.files.release', $attachment->id))->assertRedirect();

        $this->actingAs($patientUser)->get(route('portals.patient.documents'))
            ->assertOk()->assertSee('Chest X-ray report');

        // A reason is required.
        $this->as($doctor, $facility)
            ->post(route('portals.files.void', $attachment->id), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->as($doctor, $facility)
            ->post(route('portals.files.void', $attachment->id), ['reason' => 'Wrong patient'])
            ->assertRedirect();

        $attachment->refresh();
        $this->assertSame('voided', $attachment->status);
        $this->assertSame('Wrong patient', $attachment->void_reason);
        $this->assertSame($doctor->id, $attachment->voided_by);
        $this->assertNotNull($attachment->voided_at);
        $this->assertNotNull(FileAsset::find($attachment->file_asset_id));
        Storage::disk('local')->assertExists($attachment->fileAsset->path);

        $this->actingAs($patientUser)->get(route('portals.patient.documents'))
            ->assertOk()->assertDontSee('Chest X-ray report');

        $signed = URL::temporarySignedRoute('portals.patient.files.download', now()->addMinutes(5), ['id' => $attachment->id]);
        $this->actingAs($patientUser)->get($signed)->assertNotFound();
    }

    public function test_wrong_type_and_too_large_are_rejected(): void
    {
        [$doctor, $facility] = $this->staff();
        $patient = $this->patient();
        $this->basis($doctor, $facility, $patient);

        // A text file named .pdf: the bytes decide.
        $this->as($doctor, $facility)
            ->post(route('portals.files.store', ['patientId' => $patient->id]), [
                'file'     => UploadedFile::fake()->createWithContent('fake.pdf', 'just some text'),
                'category' => 'report',
            ])
            ->assertSessionHasErrors('file');

        $this->as($doctor, $facility)
            ->post(route('portals.files.store', ['patientId' => $patient->id]), [
                'file'     => UploadedFile::fake()->create('big.pdf', 11 * 1024, 'application/pdf'),
                'category' => 'report',
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, MedicalAttachment::count());
        $this->assertSame(0, FileAsset::count());
    }

    public function test_the_patient_sees_a_released_file_and_downloads_it_with_a_log(): void
    {
        [$doctor, $facility] = $this->staff();
        $patient = $this->patient();
        $this->basis($doctor, $facility, $patient);
        $patientUser = User::factory()->create(['patient_id' => $patient->id]);

        $attachment = $this->upload($doctor, $facility, $patient);

        // Not released yet: not listed.
        $this->actingAs($patientUser)->get(route('portals.patient.documents'))
            ->assertOk()->assertDontSee('data-section="documents-files"', false);

        $this->as($doctor, $facility)->post(route('portals.files.release', $attachment->id))->assertRedirect();

        $this->actingAs($patientUser)->get(route('portals.patient.documents'))
            ->assertOk()
            ->assertSee('data-section="documents-files"', false)
            ->assertSee('Chest X-ray report');

        $signed = URL::temporarySignedRoute('portals.patient.files.download', now()->addMinutes(5), ['id' => $attachment->id]);
        $this->actingAs($patientUser)->get($signed)->assertOk();
        $this->assertSame(1, AttachmentAccessLog::where('accessed_by', $patientUser->id)->count());

        // Another patient's signed link to this file: 404.
        $stranger = User::factory()->create(['patient_id' => $this->patient()->id]);
        $this->actingAs($stranger)->get($signed)->assertNotFound();
    }

    public function test_releasing_a_radiology_report_finalizes_it_and_notifies_the_patient(): void
    {
        [$doctor, $facility] = $this->staff();
        $patient = $this->patient();
        $this->basis($doctor, $facility, $patient);

        $attachment = $this->upload($doctor, $facility, $patient, 'radiology_report');

        // Radiology release needs the study details.
        $this->as($doctor, $facility)
            ->post(route('portals.files.release', $attachment->id), [])
            ->assertSessionHasErrors(['modality', 'body_part', 'study_date']);
        $this->assertNull($attachment->fresh()->released_at);

        $this->as($doctor, $facility)
            ->post(route('portals.files.release', $attachment->id), [
                'modality'   => 'xray',
                'body_part'  => 'Chest',
                'study_date' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $attachment->refresh();
        $this->assertNotNull($attachment->released_at);

        $report = RadiologyReport::findOrFail($attachment->radiology_report_id);
        $this->assertSame('final', $report->status);
        $this->assertSame($patient->id, $report->patient_id);
        $this->assertNotNull($report->distributed_at);

        $this->assertSame(1, DB::table('notifications')
            ->where('notifiable_id', $patient->id)
            ->where('type', 'radiology_report_available')
            ->count());
    }

    public function test_the_chart_links_to_patient_files(): void
    {
        [$doctor, $facility] = $this->staff();
        $patient = $this->patient();
        $this->basis($doctor, $facility, $patient);

        $this->as($doctor, $facility)
            ->get('/facility/patients/' . $patient->id)
            ->assertOk()
            ->assertSee(route('portals.files.index', ['patientId' => $patient->id]));
    }
}
