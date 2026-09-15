<?php

namespace Tests\Feature\Imports;

use App\Modules\Broadcasts\Models\Broadcast;
use App\Modules\Broadcasts\Models\BroadcastAcknowledgement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Broadcasts imported from the platform: admin composes, staff at the target facility acknowledge. */
class BroadcastTest extends TestCase
{
    use ImportsGroupBHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_a_facility_broadcast_reaches_its_staff_only_and_records_the_acknowledgement(): void
    {
        [$doctor, $facility] = $this->staffAt(null, 'doctor');
        [$outsider, $otherFacility] = $this->staffAt(null, 'doctor');
        $admin = $this->platformAdmin();

        $this->actingAsAdmin($admin)->get(route('portals.admin.broadcasts'))->assertOk();

        $this->actingAsAdmin($admin)->post(route('portals.admin.broadcasts.store'), [
            'title' => 'Generator test at 14:00',
            'body' => 'Power will be cut for ten minutes.',
            'broadcast_type' => 'maintenance',
            'target_type' => 'facility_staff',
            'facility_ids' => [$facility->id],
            'priority' => 'high',
            'requires_acknowledgement' => '1',
            'publish_now' => '1',
        ])->assertRedirect(route('portals.admin.broadcasts'));

        $broadcast = Broadcast::where('title', 'Generator test at 14:00')->firstOrFail();
        $this->assertSame('published', $broadcast->status);

        $this->actingAtFacility($doctor, $facility)->get(route('portals.staff.tasks'))
            ->assertOk()->assertSee('Generator test at 14:00');
        $this->actingAtFacility($outsider, $otherFacility)->get(route('portals.staff.tasks'))
            ->assertOk()->assertDontSee('Generator test at 14:00');

        $this->actingAtFacility($outsider, $otherFacility)
            ->post(route('portals.staff.broadcasts.acknowledge', $broadcast->uuid))
            ->assertNotFound();

        $this->actingAtFacility($doctor, $facility)
            ->post(route('portals.staff.broadcasts.acknowledge', $broadcast->uuid))
            ->assertRedirect();
        $this->actingAtFacility($doctor, $facility)
            ->post(route('portals.staff.broadcasts.acknowledge', $broadcast->uuid))
            ->assertRedirect();

        $ack = BroadcastAcknowledgement::where('broadcast_id', $broadcast->id)->sole();
        $this->assertSame((string) $doctor->id, (string) $ack->user_id);
        $this->assertSame((string) $facility->id, (string) $ack->facility_id);

        $this->actingAsAdmin($admin)->get(route('portals.admin.broadcasts.show', $broadcast->uuid))
            ->assertOk()->assertSee($doctor->name);
    }

    public function test_role_targeting_and_scheduling(): void
    {
        [$pharmacist, $facility] = $this->staffAt(null, 'pharmacist');
        [$nurse] = $this->staffAt($facility, 'nurse');
        $admin = $this->platformAdmin();

        $this->actingAsAdmin($admin)->post(route('portals.admin.broadcasts.store'), [
            'title' => 'New dispensing policy',
            'body' => 'Read before Monday.',
            'broadcast_type' => 'policy',
            'target_type' => 'role',
            'role_names' => ['pharmacist'],
            'publish_now' => '1',
        ]);
        $this->actingAsAdmin($admin)->post(route('portals.admin.broadcasts.store'), [
            'title' => 'Scheduled for next week',
            'body' => 'Not yet.',
            'broadcast_type' => 'announcement',
            'target_type' => 'all_staff',
            'publish_at' => now()->addWeek()->toDateTimeString(),
            'publish_now' => '1',
        ]);

        $this->actingAtFacility($pharmacist, $facility)->get(route('portals.staff.tasks'))
            ->assertOk()->assertSee('New dispensing policy')->assertDontSee('Scheduled for next week');
        $this->actingAtFacility($nurse, $facility)->get(route('portals.staff.tasks'))
            ->assertOk()->assertDontSee('New dispensing policy');

        $scheduled = Broadcast::where('title', 'Scheduled for next week')->firstOrFail();
        $this->actingAsAdmin($admin)->post(route('portals.admin.broadcasts.cancel', $scheduled->uuid))->assertRedirect();
        $this->assertSame('cancelled', $scheduled->fresh()->status);
    }

    public function test_admin_broadcasts_are_403_for_facility_admin(): void
    {
        [$admin, $facility] = $this->staffAt(null, 'facility_admin');

        $this->actingAtFacility($admin, $facility)->get(route('portals.admin.broadcasts'))->assertForbidden();
        $this->actingAtFacility($admin, $facility)->post(route('portals.admin.broadcasts.store'), [])->assertForbidden();
    }
}
