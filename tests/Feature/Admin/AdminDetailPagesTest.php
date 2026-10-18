<?php

namespace Tests\Feature\Admin;

use App\Models\Facility;
use App\Models\Lead;
use App\Models\MpiCandidate;
use App\Models\Patient;
use App\Models\ProviderCredential;
use App\Models\Role;
use App\Models\SecurityIncident;
use App\Models\User;
use App\Modules\Tasks\Models\ActionTask;
use App\Modules\Tasks\Services\TaskService;
use App\Services\Identity\HealthIdGeneratorService;
use Database\Seeders\AccountCategoriesSeeder;
use Database\Seeders\DashboardProfilesSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Every admin list has a detail page.
 *
 * Five lists did not: tasks, credential verifications, leads, identity pairs
 * and security incidents were each a table whose rows opened nothing (or only
 * a modal), so a task's description, a lead's note history and an incident's
 * full summary were written to the database and never shown back. Each has a
 * page now; this pins that it renders the record, that the list links to it,
 * that an unknown id is a 404 rather than a 500, and that the pages keep the
 * platform tier of the desks they belong to.
 */
class AdminDetailPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->seed(AccountCategoriesSeeder::class);
        $this->seed(DashboardProfilesSeeder::class);
        $this->seed(RolesSeeder::class);
    }

    private function platformAdmin(): User
    {
        $user = User::factory()->create(['status' => 'active', 'primary_facility_id' => null]);
        $user->role_id = Role::where('name', 'super_admin')->firstOrFail()->id;
        $user->save();

        return $user;
    }

    private function facilityAdmin(): User
    {
        return User::factory()->create([
            'status'              => 'active',
            'role_id'             => Role::where('name', 'facility_admin')->firstOrFail()->id,
            'primary_facility_id' => Facility::factory()->create()->id,
        ]);
    }

    private function asAdmin(User $user)
    {
        return $this->actingAs($user)->withSession(['mfa.verified' => true]);
    }

    private function patient(string $first = 'Jude', string $last = 'Nshome'): Patient
    {
        return app(HealthIdGeneratorService::class)->generate('CM', fn (string $healthId) => Patient::create([
            'health_id'           => $healthId,
            'first_name'          => $first,
            'last_name'           => $last,
            'date_of_birth'       => '1990-04-17',
            'sex'                 => 'male',
            'identity_status'     => 'provisional',
            'verification_status' => 'unverified',
        ]));
    }

    // ── Tasks ───────────────────────────────────────────────────────────────

    public function test_a_task_has_a_detail_page_linked_from_the_list(): void
    {
        $admin = $this->platformAdmin();
        $task = app(TaskService::class)->createTask([
            'task_type'   => 'callback',
            'title'       => 'Ring the Bamenda pharmacy about its stock feed',
            'description' => 'The feed has been silent since Tuesday; ask whether the connector was switched off.',
            'assigned_to' => $admin->id,
        ]);

        $this->asAdmin($admin)->get(route('portals.admin.tasks'))
            ->assertOk()
            ->assertSee(route('portals.admin.tasks.show', $task->uuid), false);

        $this->asAdmin($admin)->get(route('portals.admin.tasks.show', $task->uuid))
            ->assertOk()
            ->assertSee('Ring the Bamenda pharmacy about its stock feed')
            ->assertSee('connector was switched off')
            ->assertSee($admin->name)
            ->assertSee(route('portals.admin.tasks.cancel', $task->uuid), false);

        $this->asAdmin($admin)->get(route('portals.admin.tasks.show', (string) Str::uuid()))->assertNotFound();
    }

    public function test_a_closed_task_offers_no_transitions(): void
    {
        $admin = $this->platformAdmin();
        $task = app(TaskService::class)->createTask([
            'task_type' => 'admin', 'title' => 'Archive me', 'description' => '', 'assigned_to' => $admin->id,
        ]);
        ActionTask::where('uuid', $task->uuid)->update(['status' => 'cancelled']);

        $this->asAdmin($admin)->get(route('portals.admin.tasks.show', $task->uuid))
            ->assertOk()
            ->assertSee(__('admin_desk.task_closed_hint'))
            ->assertDontSee(route('portals.admin.tasks.complete', $task->uuid), false);
    }

    // ── Credential verifications ────────────────────────────────────────────

    public function test_a_credential_has_a_detail_page_with_its_decision(): void
    {
        $admin = $this->platformAdmin();
        $doctor = User::factory()->create([
            'name'    => 'Dr Amina Tchoupo',
            'status'  => 'active',
            'role_id' => Role::where('name', 'doctor')->firstOrFail()->id,
        ]);
        $credential = ProviderCredential::create([
            'provider_id'       => $doctor->id,
            'credential_type'   => 'medical_license',
            'issuing_body'      => 'Ordre National des Médecins du Cameroun',
            'credential_number' => 'ONMC-2024-0918',
            'issued_date'       => '2024-01-15',
            'status'            => 'active',
        ]);

        $this->asAdmin($admin)->get(route('portals.admin.verifications.index'))
            ->assertOk()
            ->assertSee(route('portals.admin.verifications.show', $credential->id), false);

        $this->asAdmin($admin)->get(route('portals.admin.verifications.show', $credential->id))
            ->assertOk()
            ->assertSee('Dr Amina Tchoupo')
            ->assertSee('ONMC-2024-0918')
            ->assertSee(__('admin_desk.awaiting_review'))
            ->assertSee(route('portals.admin.verifications.verify', $credential->id), false);

        // once decided, the page reads the decision back instead of offering it again
        $this->asAdmin($admin)->post(route('portals.admin.verifications.verify', $credential->id))->assertRedirect();

        $this->asAdmin($admin)->get(route('portals.admin.verifications.show', $credential->id))
            ->assertOk()
            ->assertSee(__('admin_desk.credential_decided_hint'))
            ->assertSee($admin->name)
            ->assertDontSee(route('portals.admin.verifications.verify', $credential->id), false);

        $this->asAdmin($admin)->get(route('portals.admin.verifications.show', (string) Str::uuid()))->assertNotFound();
    }

    // ── Leads ───────────────────────────────────────────────────────────────

    public function test_a_lead_has_a_detail_page_showing_its_message_and_note_history(): void
    {
        $admin = $this->platformAdmin();
        $lead = Lead::create([
            'name'              => 'Paul Ekani',
            'email'             => 'paul@clinique-ekani.cm',
            'phone'             => '+237 6 99 00 11 22',
            'organization_name' => 'Clinique Ekani',
            'organization_type' => 'facility',
            'message'           => 'We run two sites in Douala and want both listed.',
            'source'            => 'request_demo',
            'status'            => 'new',
        ]);

        $this->asAdmin($admin)->get(route('portals.admin.leads'))
            ->assertOk()
            ->assertSee(route('portals.admin.leads.show', $lead), false);

        $this->asAdmin($admin)->get(route('portals.admin.leads.show', $lead))
            ->assertOk()
            ->assertSee('Clinique Ekani')
            ->assertSee('two sites in Douala')
            ->assertSee(__('admin_desk.lead_no_notes'));

        // a status change with a note lands in the history the page shows
        $this->asAdmin($admin)->post(route('portals.admin.leads.status', $lead), [
            'status' => 'contacted', 'note' => 'Called; demo booked for Thursday.',
        ])->assertRedirect();

        $this->asAdmin($admin)->get(route('portals.admin.leads.show', $lead))
            ->assertOk()
            ->assertSee('demo booked for Thursday')
            ->assertSee(__('leads.statuses.contacted'));

        $this->asAdmin($admin)->get('/admin/leads/' . Str::uuid())->assertNotFound();
    }

    // ── Identity pairs ──────────────────────────────────────────────────────

    public function test_an_identity_pair_has_a_detail_page_linked_from_the_desk(): void
    {
        $admin = $this->platformAdmin();
        $a = $this->patient();
        $b = $this->patient();
        $candidate = MpiCandidate::create([
            'source_patient_id' => $a->id,
            'target_patient_id' => $b->id,
            'match_score'       => 86,
            'match_reasons'     => ['name_match', 'dob_match'],
            'status'            => 'pending_review',
            'detected_by'       => 'scan',
        ]);

        $this->asAdmin($admin)->get(route('portals.admin.mpi.index'))
            ->assertOk()
            ->assertSee(route('portals.admin.mpi.candidates.show', $candidate->id), false);

        $this->asAdmin($admin)->get(route('portals.admin.mpi.candidates.show', $candidate->id))
            ->assertOk()
            ->assertSee($a->health_id)
            ->assertSee($b->health_id)
            ->assertSee(__('mpi.reason_name_match'))
            ->assertSee(route('portals.admin.mpi.candidates.merge', $candidate->id), false)
            ->assertSee(route('portals.admin.mpi.candidates.reject', $candidate->id), false);

        $this->asAdmin($admin)->get(route('portals.admin.mpi.candidates.show', (string) Str::uuid()))->assertNotFound();
    }

    // ── Security incidents ──────────────────────────────────────────────────

    public function test_an_incident_has_a_detail_page_and_updates_from_it_land_back_on_it(): void
    {
        $admin = $this->platformAdmin();
        $incident = SecurityIncident::create([
            'incident_type' => 'credential_stuffing',
            'severity'      => 'high',
            'status'        => 'open',
            'summary'       => 'Four hundred failed logins against staff accounts from one range in twenty minutes.',
            'detected_at'   => now()->subHour(),
            'created_by'    => $admin->id,
        ]);

        $this->asAdmin($admin)->get(route('portals.admin.security.incidents'))
            ->assertOk()
            ->assertSee(route('portals.admin.security.incidents.show', $incident->id), false);

        $this->asAdmin($admin)->get(route('portals.admin.security.incidents.show', $incident->id))
            ->assertOk()
            ->assertSee('credential_stuffing')
            ->assertSee('Four hundred failed logins')
            ->assertSee($admin->name);

        $this->asAdmin($admin)->post(route('portals.admin.security.incidents.update', $incident->id), [
            'status' => 'contained', 'contained_at' => now()->format('Y-m-d'), 'stay' => 1,
        ])->assertRedirect(route('portals.admin.security.incidents.show', $incident->id));

        $this->assertSame('contained', $incident->fresh()->status);

        $this->asAdmin($admin)->get(route('portals.admin.security.incidents.show', (string) Str::uuid()))->assertNotFound();
        $this->asAdmin($admin)->get('/admin/security/incidents/not-a-uuid')->assertNotFound();
    }

    // ── Tier ────────────────────────────────────────────────────────────────

    public function test_the_detail_pages_stay_platform_tier(): void
    {
        $facilityAdmin = $this->facilityAdmin();
        $id = (string) Str::uuid();

        foreach ([
            route('portals.admin.tasks.show', $id),
            route('portals.admin.verifications.show', $id),
            '/admin/leads/' . $id,
            route('portals.admin.mpi.candidates.show', $id),
            route('portals.admin.security.incidents.show', $id),
        ] as $url) {
            $res = $this->asAdmin($facilityAdmin)->get($url);
            $this->assertContains($res->status(), [403, 404, 302], "{$url} answered {$res->status()} to a facility admin");
            $this->assertNotSame(200, $res->status(), "{$url} opened for a facility admin");
        }
    }
}
