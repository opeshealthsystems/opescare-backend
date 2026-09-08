<?php

namespace Tests\Feature\Security;

use App\Models\AuditEvent;
use App\Models\EmergencyAccessEvent;
use App\Models\EmergencyReviewCase;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\Role;
use App\Models\SecurityIncident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The break-glass oversight desk at /admin/security.
 *
 * This desk exists so that "anyone with the capability can break the glass and
 * only the patient might notice" is not the posture. The properties pinned
 * here are the ones that would make it useless while still looking like it
 * worked: it must be platform-tier only, it must see EVERY override rather
 * than only the demo ones, it must never alter the override it reviews, and
 * it must never become a way to browse patients.
 */
class SecurityOpsDeskTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTES = [
        ['get', '/admin/security'],
        ['get', '/admin/security/emergency-access'],
        ['get', '/admin/security/incidents'],
        ['get', '/admin/security/audit'],
    ];

    private function userWithRole(string $role, ?string $primaryFacilityId = null): User
    {
        return User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role)])->id,
            'primary_facility_id' => $primaryFacilityId,
        ]);
    }

    private function event(Patient $patient, Facility $facility, User $provider, bool $isDemo, array $attrs = []): EmergencyAccessEvent
    {
        $event = new EmergencyAccessEvent();
        $event->forceFill(array_merge([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'provider_id' => $provider->id,
            'reason' => 'Unconscious on arrival',
            'is_demo' => $isDemo,
        ], $attrs))->save();

        return $event;
    }

    private function patient(string $healthId, string $first = 'Testina', string $last = 'Undisclosed'): Patient
    {
        return Patient::factory()->create([
            'health_id' => $healthId,
            'first_name' => $first,
            'last_name' => $last,
            'is_demo' => false,
        ]);
    }

    // -- Access ---------------------------------------------------------------

    public function test_a_non_platform_admin_is_refused_with_403_on_every_route(): void
    {
        $facility = Facility::factory()->create();

        foreach (['clinic_admin', 'hospital_admin', 'doctor', 'nurse', 'patient'] as $role) {
            $user = $this->userWithRole($role, $facility->id);

            foreach (self::ROUTES as [$verb, $url]) {
                $this->actingAs($user)->{$verb}($url)->assertStatus(403);
            }

            $this->actingAs($user)->post('/admin/security/incidents', [
                'incident_type' => 'unauthorized_access',
                'severity' => 'high',
                'summary' => 'x',
                'detected_at' => now()->toDateTimeString(),
            ])->assertStatus(403);
        }

        $this->assertSame(0, SecurityIncident::withoutGlobalScope('isolate_demo')->count());
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        foreach (self::ROUTES as [$verb, $url]) {
            $this->{$verb}($url)->assertRedirect();
        }
        $this->assertGuest();
    }

    public function test_a_security_officer_and_a_platform_admin_both_get_in(): void
    {
        foreach (['super_admin', 'security_officer', 'privacy_officer', 'emergency_access_reviewer'] as $role) {
            $user = $this->userWithRole($role);

            foreach (self::ROUTES as [$verb, $url]) {
                $this->actingAs($user)->{$verb}($url)->assertOk();
            }
        }
    }

    // -- Demo isolation is lifted --------------------------------------------

    public function test_the_desk_shows_real_overrides_as_well_as_demo_ones_when_demo_mode_is_on(): void
    {
        $facility = Facility::factory()->create();
        $provider = $this->userWithRole('doctor', $facility->id);
        $admin = $this->userWithRole('super_admin');

        $real = $this->patient('OC-REAL-001');
        $demo = $this->patient('OC-DEMO-001');

        $this->event($real, $facility, $provider, false);
        $this->event($demo, $facility, $provider, true);

        // Exactly how the hosted Finder runs. Without lifting IsDemoRecord the
        // desk would show the demo override and HIDE the real one — backwards
        // from what oversight is for.
        config(['demo.enabled' => true]);

        // Control: the global scope really is active and really would hide it.
        $this->assertSame(1, EmergencyAccessEvent::count());
        $this->assertSame(2, EmergencyAccessEvent::withoutGlobalScope('isolate_demo')->count());

        $queue = $this->actingAs($admin)->get('/admin/security/emergency-access');
        $queue->assertOk();
        $queue->assertSee('OC-REAL-001');
        $queue->assertSee('OC-DEMO-001');

        $this->actingAs($admin)->get('/admin/security')
            ->assertOk()
            ->assertSee('OC-REAL-001')
            ->assertSee('OC-DEMO-001');
    }

    public function test_with_demo_mode_off_the_desk_still_shows_both(): void
    {
        $facility = Facility::factory()->create();
        $provider = $this->userWithRole('doctor', $facility->id);
        $admin = $this->userWithRole('super_admin');

        $this->event($this->patient('OC-REAL-002'), $facility, $provider, false);
        $this->event($this->patient('OC-DEMO-002'), $facility, $provider, true);

        config(['demo.enabled' => false]);

        $this->actingAs($admin)->get('/admin/security/emergency-access')
            ->assertOk()
            ->assertSee('OC-REAL-002')
            ->assertSee('OC-DEMO-002');
    }

    public function test_incidents_are_listed_regardless_of_demo_flag(): void
    {
        $admin = $this->userWithRole('super_admin');

        foreach ([[false, 'real-incident-summary'], [true, 'demo-incident-summary']] as [$isDemo, $summary]) {
            $incident = new SecurityIncident();
            $incident->forceFill([
                'incident_type' => 'unauthorized_access',
                'severity' => 'high',
                'status' => 'open',
                'summary' => $summary,
                'detected_at' => now(),
                'is_demo' => $isDemo,
            ])->save();
        }

        config(['demo.enabled' => true]);

        $this->actingAs($admin)->get('/admin/security/incidents')
            ->assertOk()
            ->assertSee('real-incident-summary')
            ->assertSee('demo-incident-summary');
    }

    // -- Oversight is cross-facility and takes no facility from the request ---

    public function test_overrides_at_every_facility_are_visible_and_a_facility_id_in_the_query_changes_nothing(): void
    {
        $mine = Facility::factory()->create();
        $theirs = Facility::factory()->create();
        $provider = $this->userWithRole('doctor', $mine->id);
        $admin = $this->userWithRole('super_admin', $mine->id);

        $this->event($this->patient('OC-HERE-001'), $mine, $provider, false);
        $this->event($this->patient('OC-AWAY-001'), $theirs, $provider, false);

        $plain = $this->actingAs($admin)->get('/admin/security/emergency-access');
        $plain->assertOk()->assertSee('OC-HERE-001')->assertSee('OC-AWAY-001');

        // A facility id off the query string must not narrow the queue.
        $this->actingAs($admin)
            ->get('/admin/security/emergency-access?facility_id='.$mine->id)
            ->assertOk()
            ->assertSee('OC-HERE-001')
            ->assertSee('OC-AWAY-001');

        // Nor may a session facility.
        $this->actingAs($admin)
            ->withSession(['active_facility_id' => $mine->id])
            ->get('/admin/security/emergency-access')
            ->assertOk()
            ->assertSee('OC-AWAY-001');
    }

    // -- A review annotates, it never edits ----------------------------------

    public function test_reviewing_writes_a_case_and_leaves_the_access_event_byte_identical(): void
    {
        $facility = Facility::factory()->create();
        $provider = $this->userWithRole('doctor', $facility->id);
        $admin = $this->userWithRole('security_officer');
        $event = $this->event($this->patient('OC-REV-001'), $facility, $provider, false);

        $before = DB::table('emergency_access_events')->where('id', $event->id)->first();

        $this->actingAs($admin)->post("/admin/security/emergency-access/{$event->id}/review", [
            'status' => 'approved',
            'notes' => 'Justified: patient was unconscious.',
        ])->assertRedirect(route('portals.admin.security.emergency_access'));

        $after = DB::table('emergency_access_events')->where('id', $event->id)->first();
        $this->assertEquals($before, $after, 'a review must never modify the override it reviews');

        $case = EmergencyReviewCase::withoutGlobalScope('isolate_demo')
            ->where('emergency_access_event_id', $event->id)->firstOrFail();
        $this->assertSame('approved', $case->status);
        $this->assertSame('Justified: patient was unconscious.', $case->reviewer_notes);
        $this->assertSame($admin->id, $case->reviewed_by);
        $this->assertNotNull($case->reviewed_at);
    }

    public function test_a_second_review_replaces_the_case_rather_than_duplicating_it(): void
    {
        $facility = Facility::factory()->create();
        $provider = $this->userWithRole('doctor', $facility->id);
        $first = $this->userWithRole('security_officer');
        $second = $this->userWithRole('privacy_officer');
        $event = $this->event($this->patient('OC-REV-002'), $facility, $provider, false);

        $this->actingAs($first)->post("/admin/security/emergency-access/{$event->id}/review", [
            'status' => 'approved', 'notes' => 'first look',
        ])->assertRedirect();

        $this->actingAs($second)->post("/admin/security/emergency-access/{$event->id}/review", [
            'status' => 'escalated', 'notes' => 'second look',
        ])->assertRedirect();

        $cases = EmergencyReviewCase::withoutGlobalScope('isolate_demo')
            ->where('emergency_access_event_id', $event->id)->get();

        $this->assertCount(1, $cases);
        $this->assertSame('escalated', $cases[0]->status);
        $this->assertSame('second look', $cases[0]->reviewer_notes);
        $this->assertSame($second->id, $cases[0]->reviewed_by, 'the case must re-stamp who decided');
    }

    public function test_a_review_status_outside_the_allowed_set_is_rejected(): void
    {
        $facility = Facility::factory()->create();
        $provider = $this->userWithRole('doctor', $facility->id);
        $admin = $this->userWithRole('security_officer');
        $event = $this->event($this->patient('OC-REV-003'), $facility, $provider, false);

        $this->actingAs($admin)
            ->post("/admin/security/emergency-access/{$event->id}/review", ['status' => 'dismissed'])
            ->assertSessionHasErrors('status');

        $this->assertSame(0, EmergencyReviewCase::withoutGlobalScope('isolate_demo')->count());
    }

    public function test_a_demo_override_can_still_be_reviewed(): void
    {
        $facility = Facility::factory()->create();
        $provider = $this->userWithRole('doctor', $facility->id);
        $admin = $this->userWithRole('security_officer');
        $event = $this->event($this->patient('OC-REV-004'), $facility, $provider, true);

        config(['demo.enabled' => false]); // the scope would hide this event

        $this->actingAs($admin)->post("/admin/security/emergency-access/{$event->id}/review", [
            'status' => 'approved',
        ])->assertRedirect();

        $this->assertSame(1, EmergencyReviewCase::withoutGlobalScope('isolate_demo')
            ->where('emergency_access_event_id', $event->id)->count());
    }

    // -- "Unreviewed" means no case, or a case still pending -----------------

    public function test_unreviewed_means_no_case_or_a_case_still_pending(): void
    {
        $facility = Facility::factory()->create();
        $provider = $this->userWithRole('doctor', $facility->id);
        $admin = $this->userWithRole('super_admin');

        $noCase = $this->event($this->patient('OC-NOCASE-01'), $facility, $provider, false);
        $pending = $this->event($this->patient('OC-PENDING-1'), $facility, $provider, false);
        $decided = $this->event($this->patient('OC-DECIDED-1'), $facility, $provider, false);

        EmergencyReviewCase::withoutGlobalScope('isolate_demo')->create([
            'emergency_access_event_id' => $pending->id,
            'status' => 'pending',
        ]);
        EmergencyReviewCase::withoutGlobalScope('isolate_demo')->create([
            'emergency_access_event_id' => $decided->id,
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        $unreviewed = $this->actingAs($admin)->get('/admin/security/emergency-access?review=unreviewed');
        $unreviewed->assertOk();
        $unreviewed->assertSee('OC-NOCASE-01');
        $unreviewed->assertSee('OC-PENDING-1');
        $unreviewed->assertDontSee('OC-DECIDED-1');

        $reviewed = $this->actingAs($admin)->get('/admin/security/emergency-access?review=reviewed');
        $reviewed->assertOk();
        $reviewed->assertSee('OC-DECIDED-1');
        $reviewed->assertDontSee('OC-NOCASE-01');
        $reviewed->assertDontSee('OC-PENDING-1');

        // The landing screen queues the same two.
        $home = $this->actingAs($admin)->get('/admin/security');
        $home->assertOk();
        $home->assertSee('OC-NOCASE-01');
        $home->assertSee('OC-PENDING-1');
        $home->assertDontSee('OC-DECIDED-1');

        $this->assertNotNull($noCase->id);
    }

    // -- No patient enumeration ----------------------------------------------

    public function test_the_queue_identifies_a_patient_by_health_id_and_never_by_name(): void
    {
        $facility = Facility::factory()->create();
        $provider = $this->userWithRole('doctor', $facility->id);
        $admin = $this->userWithRole('super_admin');

        $patient = $this->patient('OC-NAME-0001', 'Zubeidatou', 'Nkwenti');
        $this->event($patient, $facility, $provider, false);

        $response = $this->actingAs($admin)->get('/admin/security/emergency-access');

        $response->assertOk();
        $response->assertSee('OC-NAME-0001');
        $response->assertDontSee('Zubeidatou');
        $response->assertDontSee('Nkwenti');
    }

    public function test_there_is_no_patient_filter_on_the_queue(): void
    {
        $facility = Facility::factory()->create();
        $provider = $this->userWithRole('doctor', $facility->id);
        $admin = $this->userWithRole('super_admin');

        $one = $this->patient('OC-FILTER-001');
        $two = $this->patient('OC-FILTER-002');
        $this->event($one, $facility, $provider, false);
        $this->event($two, $facility, $provider, false);

        // Anything that looks like a patient search must be ignored, not honoured.
        foreach ([
            'patient_id='.$one->id,
            'health_id=OC-FILTER-001',
            'q=OC-FILTER-001',
            'search=OC-FILTER-001',
        ] as $query) {
            $response = $this->actingAs($admin)->get('/admin/security/emergency-access?'.$query);
            $response->assertOk();
            $response->assertSee('OC-FILTER-001');
            $response->assertSee('OC-FILTER-002');
        }
    }

    public function test_the_audit_explorer_never_resolves_a_patient_name(): void
    {
        $facility = Facility::factory()->create();
        $admin = $this->userWithRole('super_admin');
        $patient = $this->patient('OC-AUDIT-0001', 'Marceline', 'Fombutu');

        AuditEvent::create([
            'actor_id' => $admin->id,
            'facility_id' => $facility->id,
            'patient_id' => $patient->id,
            'action_type' => 'read',
            'resource_type' => 'patient',
            'resource_id' => $patient->id,
            'emergency_override' => true,
            'source_system' => 'portal',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/security/audit');

        $response->assertOk();
        $response->assertDontSee('Marceline');
        $response->assertDontSee('Fombutu');
        $response->assertDontSee('OC-AUDIT-0001');
        // Only a truncated identifier is rendered.
        $response->assertSee(substr($patient->id, 0, 8));
    }

    public function test_the_audit_explorer_filters_without_touching_the_rows(): void
    {
        $facility = Facility::factory()->create();
        $admin = $this->userWithRole('super_admin');
        $other = $this->userWithRole('doctor', $facility->id);

        AuditEvent::create([
            'actor_id' => $admin->id, 'facility_id' => $facility->id,
            'action_type' => 'read', 'resource_type' => 'patient',
            'emergency_override' => true, 'source_system' => 'portal', 'created_at' => now(),
        ]);
        AuditEvent::create([
            'actor_id' => $other->id, 'facility_id' => $facility->id,
            'action_type' => 'update', 'resource_type' => 'appointment',
            'emergency_override' => false, 'source_system' => 'portal', 'created_at' => now(),
        ]);

        $before = DB::table('audit_events')->orderBy('id')->get();

        $this->actingAs($admin)->get('/admin/security/audit?emergency_only=1')->assertOk();
        $this->actingAs($admin)->get('/admin/security/audit?action_type=read')->assertOk();
        $this->actingAs($admin)->get('/admin/security/audit?actor_id='.$other->id)->assertOk();
        $this->actingAs($admin)->get('/admin/security/audit?resource_type=appointment')->assertOk();
        $this->actingAs($admin)->get('/admin/security/audit?date_from=2000-01-01&date_to=2100-01-01')->assertOk();

        $this->assertEquals($before, DB::table('audit_events')->orderBy('id')->get(), 'the audit trail is read-only here');
    }

    // -- Incidents -----------------------------------------------------------

    public function test_logging_an_incident_records_the_reporter_as_a_uuid_and_opens_it(): void
    {
        $admin = $this->userWithRole('security_officer');

        $this->actingAs($admin)->post('/admin/security/incidents', [
            'incident_type' => 'unauthorized_access',
            'severity' => 'critical',
            'summary' => 'A ward terminal was left signed in overnight.',
            'detected_at' => now()->toDateTimeString(),
        ])->assertRedirect(route('portals.admin.security.incidents'));

        $incident = SecurityIncident::withoutGlobalScope('isolate_demo')->firstOrFail();
        $this->assertSame('open', $incident->status);
        $this->assertSame('critical', $incident->severity);
        $this->assertSame($admin->id, $incident->created_by);
    }

    public function test_an_incident_severity_outside_the_allowed_set_is_rejected(): void
    {
        $admin = $this->userWithRole('security_officer');

        $this->actingAs($admin)->post('/admin/security/incidents', [
            'incident_type' => 'unauthorized_access',
            'severity' => 'catastrophic',
            'summary' => 'x',
            'detected_at' => now()->toDateTimeString(),
        ])->assertSessionHasErrors('severity');

        $this->assertSame(0, SecurityIncident::withoutGlobalScope('isolate_demo')->count());
    }

    public function test_an_incident_can_be_advanced_through_its_statuses(): void
    {
        $admin = $this->userWithRole('security_officer');
        $incident = new SecurityIncident();
        $incident->forceFill([
            'incident_type' => 'data_exposure',
            'severity' => 'high',
            'status' => 'open',
            'summary' => 'original summary',
            'detected_at' => now(),
            'is_demo' => false,
        ])->save();

        $this->actingAs($admin)->post("/admin/security/incidents/{$incident->id}", [
            'status' => 'contained',
            'contained_at' => now()->toDateTimeString(),
        ])->assertRedirect(route('portals.admin.security.incidents'));

        $incident->refresh();
        $this->assertSame('contained', $incident->status);
        $this->assertNotNull($incident->contained_at);
        // A null field in the form must not blank an existing value.
        $this->assertSame('original summary', $incident->summary);
    }
}
