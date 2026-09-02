<?php

namespace Tests\Feature\Tasks;

use App\Models\User;
use App\Modules\Tasks\Models\ActionTask;
use Illuminate\Support\Str;
use Tests\TestCase;

class TaskInboxTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Portal-access / MFA gating is environmental and tested elsewhere; this
        // suite exercises the controller's ownership + TaskService logic.
        $this->withoutMiddleware([
            \App\Http\Middleware\EnsurePortalAccess::class,
            \App\Http\Middleware\EnsureTwoFactorVerified::class,
            \App\Http\Middleware\RequirePlatformAdmin::class,
            \App\Http\Middleware\RequireFacilityContext::class,
        ]);
    }

    private function makeTask(string $assignedTo, string $status = 'open'): ActionTask
    {
        return ActionTask::create([
            'uuid' => (string) Str::uuid(),
            'task_type' => 'follow_up',
            'title' => 'Call patient',
            'description' => 'Follow up on lab result',
            'assigned_to' => $assignedTo,
            'priority' => 'normal',
            'status' => $status,
        ]);
    }

    public function test_actor_can_acknowledge_an_assigned_task(): void
    {
        $user = User::factory()->create();
        $task = $this->makeTask((string) $user->id);

        $this->actingAs($user)
            ->post(route('portals.staff.tasks.acknowledge', $task->uuid))
            ->assertRedirect();

        $this->assertSame('acknowledged', $task->fresh()->status);
    }

    public function test_actor_can_complete_an_assigned_task(): void
    {
        $user = User::factory()->create();
        $task = $this->makeTask((string) $user->id);

        $this->actingAs($user)
            ->post(route('portals.staff.tasks.complete', $task->uuid))
            ->assertRedirect();

        $this->assertSame('completed', $task->fresh()->status);
    }

    public function test_cannot_act_on_a_task_assigned_to_someone_else(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $task = $this->makeTask((string) $owner->id);

        $this->actingAs($other)
            ->post(route('portals.staff.tasks.complete', $task->uuid))
            ->assertForbidden();

        $this->assertSame('open', $task->fresh()->status);
    }
}
