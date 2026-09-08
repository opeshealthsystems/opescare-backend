<?php

namespace Tests\Feature\Admin;

use App\Jobs\ExecuteImportJob;
use App\Models\Facility;
use App\Models\ImportAuditEvent;
use App\Models\ImportJob;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The bulk-import desk at /admin/data-import.
 *
 * Approving an import writes rows — patients among them — into a facility at
 * scale, so the three things pinned here are the three that would be silent
 * and catastrophic if they regressed: only a platform admin gets in, the
 * facility is never taken from the request, and the id in a URL is never
 * enough to reach another facility's job.
 */
class DataImportDeskTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role, ?string $primaryFacilityId = null): User
    {
        return User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role)])->id,
            'primary_facility_id' => $primaryFacilityId,
        ]);
    }

    private function job(Facility $facility, array $attrs = []): ImportJob
    {
        return ImportJob::create(array_merge([
            'facility_id' => $facility->id,
            'import_type' => 'insurance_providers',
            'status' => 'validated',
            'original_filename' => 'providers.csv',
            'stored_path' => 'imports/private/providers.csv',
            'file_extension' => 'csv',
            'file_size_bytes' => 10,
            'detected_headers' => ['provider_name', 'provider_code'],
            'mapping' => ['provider_name' => 'provider_name', 'provider_code' => 'provider_code'],
            'valid_rows' => 1,
            'created_by' => 'seed',
        ], $attrs));
    }

    private function csv(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'providers.csv',
            "provider_name,provider_code\nActa Assurance,ACT\n"
        );
    }

    // -- Access ---------------------------------------------------------------

    public function test_a_non_platform_admin_is_refused_with_403_not_404(): void
    {
        $facility = Facility::factory()->create();

        foreach (['clinic_admin', 'hospital_admin', 'doctor', 'receptionist', 'patient'] as $role) {
            $user = $this->userWithRole($role, $facility->id);

            $this->actingAs($user)->get('/admin/data-import')->assertStatus(403);
            $this->actingAs($user)->get('/admin/data-import/new')->assertStatus(403);
            $this->actingAs($user)->post('/admin/data-import', [
                'import_type' => 'insurance_providers',
                'file' => $this->csv(),
            ])->assertStatus(403);
        }
    }

    public function test_a_guest_is_sent_to_login_not_shown_the_desk(): void
    {
        $this->get('/admin/data-import')->assertRedirect();
        $this->assertGuest();
    }

    public function test_a_platform_admin_with_a_facility_in_context_sees_the_desk(): void
    {
        $facility = Facility::factory()->create();
        $admin = $this->userWithRole('super_admin', $facility->id);

        $this->actingAs($admin)->get('/admin/data-import')->assertOk();
        $this->actingAs($admin)->get('/admin/data-import/new')->assertOk();
    }

    // -- facilityId() fails closed --------------------------------------------

    public function test_without_a_facility_in_context_the_desk_fails_closed_with_409(): void
    {
        // Exactly one facility exists — the main platform's version fell back
        // to it. A fallback default is not a chosen facility.
        Facility::factory()->create();
        $admin = $this->userWithRole('super_admin'); // no primary facility, no session

        $this->actingAs($admin)->get('/admin/data-import')->assertStatus(409);
        $this->actingAs($admin)->get('/admin/data-import/new')->assertStatus(409);
        $this->actingAs($admin)->post('/admin/data-import', [
            'import_type' => 'insurance_providers',
            'file' => $this->csv(),
        ])->assertStatus(409);

        $this->assertSame(0, ImportJob::count());
    }

    public function test_a_facility_id_in_the_query_string_cannot_supply_the_missing_context(): void
    {
        $facility = Facility::factory()->create();
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)
            ->get('/admin/data-import?facility_id='.$facility->id)
            ->assertStatus(409);
    }

    public function test_a_facility_id_in_the_body_cannot_supply_the_missing_context(): void
    {
        $facility = Facility::factory()->create();
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->post('/admin/data-import', [
            'import_type' => 'insurance_providers',
            'facility_id' => $facility->id,
            'file' => $this->csv(),
        ])->assertStatus(409);

        $this->assertSame(0, ImportJob::count());
    }

    // -- facility_id is never taken from the request --------------------------

    public function test_the_listing_ignores_an_injected_facility_id(): void
    {
        $mine = Facility::factory()->create();
        $theirs = Facility::factory()->create();
        $admin = $this->userWithRole('super_admin', $mine->id);

        $this->job($mine, ['original_filename' => 'mine-only.csv']);
        $theirJob = $this->job($theirs, ['original_filename' => 'theirs-only.csv']);

        $response = $this->actingAs($admin)->get('/admin/data-import?facility_id='.$theirs->id);

        $response->assertOk();
        $response->assertSee('mine-only.csv');
        $response->assertDontSee('theirs-only.csv');
        $response->assertDontSee($theirJob->id, false);
    }

    public function test_an_uploaded_job_is_stamped_with_the_session_facility_not_the_posted_one(): void
    {
        Storage::fake('local');

        $mine = Facility::factory()->create();
        $theirs = Facility::factory()->create();
        $admin = $this->userWithRole('super_admin', $mine->id);

        $this->actingAs($admin)->post('/admin/data-import', [
            'import_type' => 'insurance_providers',
            'facility_id' => $theirs->id,
            'file' => $this->csv(),
        ])->assertRedirect();

        $job = ImportJob::firstOrFail();
        $this->assertSame($mine->id, $job->facility_id);
        $this->assertNotSame($theirs->id, $job->facility_id);
    }

    public function test_the_session_facility_wins_over_the_users_primary_facility(): void
    {
        $primary = Facility::factory()->create();
        $active = Facility::factory()->create();
        $admin = $this->userWithRole('super_admin', $primary->id);

        $this->job($active, ['original_filename' => 'active-only.csv']);
        $this->job($primary, ['original_filename' => 'primary-only.csv']);

        $this->actingAs($admin)
            ->withSession(['active_facility_id' => $active->id])
            ->get('/admin/data-import')
            ->assertOk()
            ->assertSee('active-only.csv')
            ->assertDontSee('primary-only.csv');
    }

    // -- Another facility's job is unreachable by id --------------------------

    public function test_every_per_job_action_404s_on_another_facilitys_job(): void
    {
        $mine = Facility::factory()->create();
        $theirs = Facility::factory()->create();
        $admin = $this->userWithRole('super_admin', $mine->id);
        $foreign = $this->job($theirs, ['status' => 'completed']);

        $this->actingAs($admin)->get("/admin/data-import/{$foreign->id}/mapping")->assertNotFound();
        $this->actingAs($admin)->get("/admin/data-import/{$foreign->id}/preview")->assertNotFound();
        $this->actingAs($admin)->get("/admin/data-import/{$foreign->id}/log")->assertNotFound();
        $this->actingAs($admin)->post("/admin/data-import/{$foreign->id}/validate")->assertNotFound();
        $this->actingAs($admin)->post("/admin/data-import/{$foreign->id}/approve")->assertNotFound();
        $this->actingAs($admin)->post("/admin/data-import/{$foreign->id}/cancel")->assertNotFound();
        $this->actingAs($admin)->post("/admin/data-import/{$foreign->id}/rollback", ['reason' => 'x'])->assertNotFound();
        $this->actingAs($admin)->post("/admin/data-import/{$foreign->id}/mapping", [
            'mapping' => ['provider_name' => 'provider_name'],
        ])->assertNotFound();

        // Untouched: not approved, not cancelled, not rolled back, no audit trail.
        $this->assertSame('completed', $foreign->fresh()->status);
        $this->assertSame(0, ImportAuditEvent::where('import_job_id', $foreign->id)->count());
    }

    public function test_a_malformed_job_id_is_a_404_not_a_database_cast_error(): void
    {
        $mine = Facility::factory()->create();
        $admin = $this->userWithRole('super_admin', $mine->id);

        $this->actingAs($admin)->get('/admin/data-import/not-a-uuid/preview')->assertNotFound();
        $this->actingAs($admin)->post('/admin/data-import/not-a-uuid/approve')->assertNotFound();
    }

    // -- Approve --------------------------------------------------------------

    public function test_approving_a_validated_job_queues_the_import_and_records_the_actor(): void
    {
        Queue::fake();

        $facility = Facility::factory()->create();
        $admin = $this->userWithRole('super_admin', $facility->id);
        $job = $this->job($facility, ['status' => 'validated']);

        $this->actingAs($admin)
            ->post("/admin/data-import/{$job->id}/approve")
            ->assertRedirect(route('portals.admin.data_import.index'));

        $job->refresh();
        $this->assertSame('approved_for_import', $job->status);
        $this->assertSame($admin->id, $job->approved_by);
        $this->assertNotNull($job->approved_at);

        $this->assertSame(1, ImportAuditEvent::where('import_job_id', $job->id)->where('action', 'approved')->count());

        Queue::assertPushedOn('imports', ExecuteImportJob::class, fn ($j) => $j->importJobId === $job->id);
    }

    public function test_a_job_that_is_not_validated_cannot_be_approved_and_nothing_is_queued(): void
    {
        Queue::fake();

        $facility = Facility::factory()->create();
        $admin = $this->userWithRole('super_admin', $facility->id);
        $job = $this->job($facility, ['status' => 'preview_ready']);

        $this->actingAs($admin)->post("/admin/data-import/{$job->id}/approve")->assertRedirect();

        $this->assertSame('preview_ready', $job->fresh()->status);
        $this->assertNull($job->fresh()->approved_at);
        Queue::assertNothingPushed();
    }
}
