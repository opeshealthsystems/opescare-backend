<?php

namespace Tests\Feature\Mobile;

use App\Http\Middleware\ActAsDependent;
use App\Models\Appointment;
use App\Models\Facility;
use App\Models\FamilyLink;
use App\Models\ImmunizationRecord;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Str;

/**
 * The X-OpesCare-Dependent header: with an ACTIVE link, every record endpoint
 * answers for the dependent; without one, or on a write under read-only, or on
 * an account-level path, it is refused — never silently ignored.
 */
class FamilyActAsDependentTest extends FamilyTestCase
{
    private array $g;
    private Patient $child;
    private FamilyLink $link;

    protected function setUp(): void
    {
        parent::setUp();

        $this->g     = $this->makeGuardian(['health_id' => 'CM-HID-GUAR-0001-AAAA']);
        $this->child = $this->makeChild(['health_id' => 'CM-HID-CHLD-0001-BBBB']);
        $this->link  = $this->link($this->g['user'], $this->child);
    }

    public function test_the_header_serves_the_dependents_health_id_card(): void
    {
        $this->as($this->actingFor($this->g['headers'], $this->child))
            ->getJson('/api/mobile/health-id-card')
            ->assertOk()
            ->assertJsonPath('health_id', 'CM-HID-CHLD-0001-BBBB')
            ->assertJsonPath('display_name', 'Demo Boy')
            ->assertJsonPath('dob', '2017-03-12')
            ->assertJsonPath('sex', 'male');
    }

    public function test_without_the_header_the_caller_still_sees_their_own_record(): void
    {
        $this->as($this->g['headers'])
            ->getJson('/api/mobile/health-id-card')
            ->assertOk()
            ->assertJsonPath('health_id', 'CM-HID-GUAR-0001-AAAA');
    }

    public function test_the_header_serves_the_dependents_immunizations(): void
    {
        $site = Facility::factory()->create();
        ImmunizationRecord::create(['patient_id' => $this->child->id, 'facility_id' => $site->id, 'vaccine_code' => 'BCG', 'vaccine_name' => 'BCG', 'dose_number' => 1, 'administered_at' => '2017-03-13 09:30:00']);
        ImmunizationRecord::create(['patient_id' => $this->g['patient']->id, 'facility_id' => $site->id, 'vaccine_code' => 'TD', 'vaccine_name' => 'Tetanus booster', 'dose_number' => 1, 'administered_at' => '2024-01-10 09:30:00']);

        $this->as($this->actingFor($this->g['headers'], $this->child))
            ->getJson('/api/mobile/immunizations')
            ->assertOk()
            ->assertJsonCount(1, 'immunizations')
            ->assertJsonPath('immunizations.0.vaccine_name', 'BCG');

        $this->as($this->g['headers'])
            ->getJson('/api/mobile/immunizations')
            ->assertOk()
            ->assertJsonCount(1, 'immunizations')
            ->assertJsonPath('immunizations.0.vaccine_name', 'Tetanus booster');
    }

    public function test_the_header_serves_the_dependents_timeline(): void
    {
        Visit::create([
            'patient_id'  => $this->child->id,
            'facility_id' => Facility::factory()->create()->id,
            'visit_type'  => 'outpatient',
            'status'      => 'closed',
            'started_at'  => now()->subDays(3),
        ]);

        $this->as($this->actingFor($this->g['headers'], $this->child))
            ->getJson('/api/mobile/timeline')
            ->assertOk()
            ->assertJsonCount(1, 'timeline')
            ->assertJsonPath('timeline.0.event_type', 'visit');

        $this->as($this->g['headers'])
            ->getJson('/api/mobile/timeline')
            ->assertOk()
            ->assertJsonCount(0, 'timeline');
    }

    public function test_the_header_serves_the_dependents_appointments(): void
    {
        // The app's default scope is "upcoming": booked/confirmed and in the future.
        $childAppt = Appointment::factory()->create(['patient_id' => $this->child->id, 'status' => 'booked', 'scheduled_at' => now()->addDays(3)]);
        Appointment::factory()->create(['patient_id' => $this->g['patient']->id, 'status' => 'booked', 'scheduled_at' => now()->addDays(5)]);

        $this->as($this->actingFor($this->g['headers'], $this->child))
            ->getJson('/api/mobile/appointments')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $childAppt->id);
    }

    public function test_the_header_serves_the_dependents_prescriptions(): void
    {
        $childRx = Prescription::factory()->create(['patient_id' => $this->child->id, 'status' => 'active']);
        Prescription::factory()->create(['patient_id' => $this->g['patient']->id, 'status' => 'active']);

        $this->as($this->actingFor($this->g['headers'], $this->child))
            ->getJson('/api/mobile/prescriptions')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $childRx->id);
    }

    public function test_a_write_is_refused_under_a_read_only_link_and_reaches_the_controller_under_full(): void
    {
        $this->link->update(['access_level' => FamilyLink::ACCESS_READ_ONLY]);

        $this->as($this->actingFor($this->g['headers'], $this->child))
            ->postJson('/api/mobile/appointments', [])
            ->assertForbidden()
            ->assertJsonPath('error_code', 'dependent_forbidden')
            ->assertJsonPath('message', __('family_api.dependent_forbidden'));

        $this->link->update(['access_level' => FamilyLink::ACCESS_FULL]);

        // Past the gate: the booking controller's own validation answers now.
        $this->as($this->actingFor($this->g['headers'], $this->child))
            ->postJson('/api/mobile/appointments', [])
            ->assertUnprocessable();
    }

    public function test_reads_are_allowed_under_a_read_only_link(): void
    {
        $this->link->update(['access_level' => FamilyLink::ACCESS_READ_ONLY]);

        $this->as($this->actingFor($this->g['headers'], $this->child))
            ->getJson('/api/mobile/health-id-card')
            ->assertOk()
            ->assertJsonPath('health_id', 'CM-HID-CHLD-0001-BBBB');
    }

    public function test_a_dependent_the_caller_holds_no_link_to_is_forbidden(): void
    {
        $unlinked = $this->makeChild(['first_name' => 'Nobody']);

        foreach ([$unlinked->id, (string) Str::uuid(), 'not-a-uuid', $this->g['patient']->id] as $value) {
            $this->as($this->actingFor($this->g['headers'], $value))
                ->getJson('/api/mobile/health-id-card')
                ->assertForbidden()
                ->assertJsonPath('error_code', 'dependent_forbidden');
        }
    }

    public function test_an_inactive_or_age_expired_link_is_forbidden(): void
    {
        foreach ([FamilyLink::STATUS_REVOKED, FamilyLink::STATUS_PENDING_INVITE, FamilyLink::STATUS_EXPIRED, FamilyLink::STATUS_CANCELLED] as $status) {
            $this->link->update(['status' => $status]);

            $this->as($this->actingFor($this->g['headers'], $this->child))
                ->getJson('/api/mobile/health-id-card')
                ->assertForbidden()
                ->assertJsonPath('error_code', 'dependent_forbidden');
        }

        $this->link->update(['status' => FamilyLink::STATUS_ACTIVE, 'age_transition_expires_at' => now()->subDay()]);

        $this->as($this->actingFor($this->g['headers'], $this->child))
            ->getJson('/api/mobile/health-id-card')
            ->assertForbidden();
    }

    public function test_account_level_paths_stay_the_callers_own_even_with_full_access(): void
    {
        foreach (['/api/mobile/family', '/api/mobile/family/invitations', '/api/mobile/settings', '/api/mobile/family/invitations/incoming'] as $uri) {
            $this->as($this->actingFor($this->g['headers'], $this->child))
                ->getJson($uri)
                ->assertForbidden()
                ->assertJsonPath('error_code', 'dependent_forbidden');
        }

        $this->as($this->actingFor($this->g['headers'], $this->child))
            ->postJson('/api/mobile/push-tokens', ['token' => 'ExponentPushToken[x]'])
            ->assertForbidden();
    }

    public function test_the_dependent_themself_cannot_use_the_header_to_climb_to_the_guardian(): void
    {
        $this->makeUserFor($this->child);

        $this->as($this->actingFor($this->headersFor($this->child), $this->g['patient']))
            ->getJson('/api/mobile/health-id-card')
            ->assertForbidden();
    }

    public function test_a_caller_with_no_user_account_cannot_act_for_anyone(): void
    {
        $loner = $this->makePatient();

        $this->as($this->actingFor($this->headersFor($loner), $this->child))
            ->getJson('/api/mobile/health-id-card')
            ->assertForbidden();
    }

    public function test_acting_for_a_dependent_is_logged_against_the_dependent_with_the_guardian_as_actor(): void
    {
        $this->as($this->actingFor($this->g['headers'], $this->child))
            ->getJson('/api/mobile/immunizations')
            ->assertOk();

        $this->assertDatabaseHas('access_logs', [
            'patient_id'    => $this->child->id,
            'actor_id'      => $this->g['user']->id,
            'actor_type'    => 'guardian',
            'purpose'       => 'guardian_access',
            'data_category' => 'immunizations',
            'resource_type' => 'MobileRequest',
            'access_type'   => 'read',
        ]);

        // A refused request leaves no "access" trace: nothing was accessed.
        $before = \App\Models\AccessLog::withoutGlobalScopes()->count();
        $this->as($this->actingFor($this->g['headers'], (string) Str::uuid()))
            ->getJson('/api/mobile/immunizations')
            ->assertForbidden();
        $this->assertSame($before, \App\Models\AccessLog::withoutGlobalScopes()->count());
    }

    public function test_the_middleware_is_part_of_the_auth_mobile_group_on_every_mobile_route(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn ($route) => in_array('auth.mobile', $route->gatherMiddleware(), true));

        $this->assertGreaterThan(20, $routes->count(), 'auth.mobile must still be recognised on the mobile routes.');

        $groups = app(\Illuminate\Contracts\Http\Kernel::class)->getMiddlewareGroups();
        $this->assertSame(
            [\App\Http\Middleware\AuthenticateMobilePatient::class, ActAsDependent::class],
            $groups['auth.mobile'] ?? null,
            'auth.mobile is the bearer check followed by the act-as context.'
        );
    }

    /**
     * Presenting the dependent's Health ID: a FULL link mints the 15-minute
     * code FOR the dependent (the verifier then shows the child, not the
     * guardian); a READ-ONLY link is refused — the card is view-only for them.
     */
    public function test_a_full_link_mints_a_presentation_code_for_the_dependent_and_a_read_only_link_cannot(): void
    {
        $response = $this->as($this->actingFor($this->g['headers'], $this->child))
            ->postJson('/api/mobile/qr/temporary')
            ->assertOk();

        $this->assertStringContainsString('/verify/qr/qrx_', (string) $response->json('qr_payload'));
        $this->assertDatabaseHas('health_id_qr_tokens', ['patient_id' => $this->child->id, 'status' => 'active']);
        $this->assertDatabaseMissing('health_id_qr_tokens', ['patient_id' => $this->g['patient']->id]);

        $this->link->update(['access_level' => FamilyLink::ACCESS_READ_ONLY]);

        $this->as($this->actingFor($this->g['headers'], $this->child))
            ->postJson('/api/mobile/qr/temporary')
            ->assertForbidden()
            ->assertJsonPath('error_code', 'dependent_forbidden');
    }

    public function test_a_read_only_link_gets_the_dependents_card_without_a_qr_to_present(): void
    {
        $this->link->update(['access_level' => FamilyLink::ACCESS_READ_ONLY]);

        $card = $this->as($this->actingFor($this->g['headers'], $this->child))
            ->getJson('/api/mobile/health-id-card')
            ->assertOk()
            ->json();

        $this->assertSame($this->child->health_id, $card['health_id']);
        $this->assertNull($card['qr_payload'] ?? null);
    }

    public function test_the_guardians_own_inbox_surfaces_never_run_as_the_dependent(): void
    {
        foreach (['/api/mobile/notifications', '/api/mobile/messages/threads'] as $path) {
            $this->as($this->actingFor($this->g['headers'], $this->child))
                ->getJson($path)
                ->assertForbidden()
                ->assertJsonPath('error_code', 'dependent_forbidden');
        }
    }
}
