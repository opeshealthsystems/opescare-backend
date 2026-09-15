<?php

namespace Tests\Feature\Queue;

use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Patient;
use App\Models\QueueTicket;
use App\Models\Role;
use App\Models\User;
use App\Services\Queue\PatientQueueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The queue, triage and chart desks resolve their facility the way every
 * /facility/* page does (App\Services\Portal\DeskFacilityContext).
 *
 * Before: those three route files carry no `facility.context`, so with no
 * session value the desk fell back to `users.primary_facility_id` — a column
 * that can name a facility the clinician holds NO posting at — and a
 * clinician posted at two hospitals was pinned to one silently, where the
 * appointment book would have sent them to /select-facility.
 */
class DeskFacilityContextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function posting(User $user, Facility $facility, string $roleName): void
    {
        FacilityRoleAssignment::create([
            'user_id' => $user->id, 'facility_id' => $facility->id,
            'role_id' => Role::where('name', $roleName)->firstOrFail()->id,
            'is_active' => true, 'assigned_at' => now(),
        ]);
    }

    private function ticketAt(Facility $facility, User $actor): QueueTicket
    {
        return app(PatientQueueService::class)->checkIn([
            'patient'        => Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]),
            'facility_id'    => $facility->id,
            'actor_id'       => $actor->id,
            'priority_level' => 5,
        ]);
    }

    public function test_with_no_session_the_desk_uses_the_one_real_posting_without_a_primary_facility(): void
    {
        $role    = Role::where('name', 'doctor')->firstOrFail();
        $stale   = Facility::factory()->create(['type' => 'hospital', 'name' => 'Elsewhere']);
        $posted  = Facility::factory()->create(['type' => 'hospital', 'name' => 'Real Posting']);

        // No primary_facility_id: PortalContextService had nothing to fall back
        // on and the desk closed (403) although the doctor holds a real posting.
        // (FacilityMembershipService counts primary_facility_id AS a posting, so
        // a primary plus another posting is a choice → the selector, test below.)
        $doctor = User::factory()->create(['role_id' => $role->id, 'primary_facility_id' => null, 'status' => 'active']);
        $this->posting($doctor, $posted, 'doctor');

        $mine     = $this->ticketAt($posted, $doctor);
        $stranger = $this->ticketAt($stale, $doctor);

        // No active_facility_id in the session — a bookmark straight to the desk.
        $this->actingAs($doctor)->get('/facility/queue')
            ->assertOk()
            ->assertSee($mine->patient->health_id)
            ->assertDontSee($stranger->patient->health_id);

        $this->assertSame($posted->id, session('active_facility_id'));

        // Same rule on the triage and chart desks.
        $this->actingAs($doctor)->get('/facility/triage')->assertOk();
        $this->actingAs($doctor)->get('/facility/patients')->assertOk();
    }

    public function test_a_clinician_posted_at_two_facilities_is_sent_to_the_selector_not_pinned_silently(): void
    {
        $role = Role::where('name', 'nurse')->firstOrFail();
        $a    = Facility::factory()->create(['type' => 'hospital']);
        $b    = Facility::factory()->create(['type' => 'hospital']);

        $nurse = User::factory()->create(['role_id' => $role->id, 'primary_facility_id' => $a->id, 'status' => 'active']);
        $this->posting($nurse, $a, 'nurse');
        $this->posting($nurse, $b, 'nurse');

        foreach (['/facility/queue', '/facility/triage', '/facility/patients'] as $desk) {
            $this->actingAs($nurse)->get($desk)->assertRedirect(route('select-facility'));
        }

        // An explicit choice is honoured on every desk.
        $this->actingAs($nurse)->withSession(['active_facility_id' => $b->id])->get('/facility/queue')->assertOk();
    }

    public function test_an_account_with_no_posting_is_refused_not_shown_a_strangers_desk(): void
    {
        $role = Role::where('name', 'nurse')->firstOrFail();
        $nurse = User::factory()->create(['role_id' => $role->id, 'primary_facility_id' => null, 'status' => 'active']);

        $this->actingAs($nurse)->get('/facility/queue')->assertForbidden();
    }
}
