<?php

namespace Tests\Feature\Tasks;

use App\Models\Facility;
use App\Models\User;
use App\Modules\Tasks\Models\ActionTask;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminTaskTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\EnsurePortalAccess::class,
            \App\Http\Middleware\EnsureTwoFactorVerified::class,
            \App\Http\Middleware\RequirePlatformAdmin::class,
            \App\Http\Middleware\RequireFacilityContext::class,
        ]);
    }

    private function adminAtFacility(string $facilityId): User
    {
        // Non-patient (role-less here) users at a facility count as its staff.
        return User::factory()->create(['primary_facility_id' => $facilityId]);
    }

    public function test_admin_can_create_and_assign_a_task(): void
    {
        $fac = Facility::factory()->create();
        $admin = $this->adminAtFacility($fac->id);
        $staff = $this->adminAtFacility($fac->id);

        $title = 'Verify lab batch '.Str::random(8);
        $this->actingAs($admin)->post(route('portals.admin.tasks.store'), [
            'title' => $title,
            'task_type' => 'review',
            'assigned_to' => $staff->id,
            'priority' => 'high',
        ])->assertSessionHasNoErrors()->assertRedirect(route('portals.admin.tasks'));

        $this->assertDatabaseHas('action_tasks', [
            'title'       => $title,
            'assigned_to' => $staff->id,
            'task_type'   => 'review',
            'status'      => 'open',
        ]);
    }

    public function test_admin_can_reassign_a_task(): void
    {
        $fac = Facility::factory()->create();
        $admin = $this->adminAtFacility($fac->id);
        $a = $this->adminAtFacility($fac->id);
        $b = $this->adminAtFacility($fac->id);
        $task = ActionTask::create([
            'uuid' => (string) Str::uuid(), 'task_type' => 'review', 'title' => 'X',
            'description' => '', 'assigned_to' => $a->id,
            'priority' => 'normal', 'status' => 'acknowledged',
        ]);

        $this->actingAs($admin)
            ->post(route('portals.admin.tasks.reassign', $task->uuid), ['assigned_to' => $b->id])
            ->assertRedirect();

        $fresh = $task->fresh();
        $this->assertSame((string) $b->id, (string) $fresh->assigned_to);
        $this->assertSame('open', $fresh->status);
    }

    public function test_admin_cannot_touch_another_facilitys_task(): void
    {
        $facA = Facility::factory()->create();
        $facB = Facility::factory()->create();
        $admin = $this->adminAtFacility($facA->id);
        $bStaff = $this->adminAtFacility($facB->id);
        $task = ActionTask::create([
            'uuid' => (string) Str::uuid(), 'task_type' => 'review', 'title' => 'Y',
            'description' => '', 'assigned_to' => $bStaff->id,
            'priority' => 'normal', 'status' => 'open',
        ]);

        $this->actingAs($admin)
            ->post(route('portals.admin.tasks.complete', $task->uuid))
            ->assertForbidden();
    }
}
