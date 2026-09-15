<?php

namespace Tests\Feature\Imports;

use App\Modules\Tasks\Models\ActionTask;
use App\Modules\Tasks\Services\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Staff task inbox imported from the platform (routes/imports_tasks.php).
 * The facility is the desk's (DeskFacilityContext); another facility's task is a 404.
 */
class TaskInboxTest extends TestCase
{
    use ImportsGroupBHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function taskFor(string $userId, string $title = 'Call back patient'): ActionTask
    {
        return app(TaskService::class)->createTask([
            'task_type' => 'callback',
            'title' => $title,
            'description' => 'Ring before noon.',
            'assigned_to' => $userId,
        ]);
    }

    public function test_inbox_lists_my_tasks_and_the_nav_links_to_it(): void
    {
        [$nurse, $facility] = $this->staffAt();
        $this->taskFor($nurse->id, 'Mine to do');

        $this->actingAtFacility($nurse, $facility)
            ->get(route('portals.staff.tasks'))
            ->assertOk()
            ->assertSee('Mine to do')
            ->assertSee(route('portals.staff.support'), false);
    }

    public function test_acknowledge_complete_escalate_at_my_facility(): void
    {
        [$nurse, $facility] = $this->staffAt();
        $task = $this->taskFor($nurse->id);

        $this->actingAtFacility($nurse, $facility)->post(route('portals.staff.tasks.acknowledge', $task->uuid))->assertRedirect();
        $this->assertSame('acknowledged', $task->fresh()->status);

        $this->actingAtFacility($nurse, $facility)->post(route('portals.staff.tasks.escalate', $task->uuid))->assertRedirect();
        $this->assertSame('escalated', $task->fresh()->status);

        $this->actingAtFacility($nurse, $facility)->post(route('portals.staff.tasks.complete', $task->uuid))->assertRedirect();
        $this->assertSame('completed', $task->fresh()->status);
        $this->assertNotNull($task->fresh()->completed_at);
    }

    public function test_a_task_at_another_facility_is_404_and_untouched(): void
    {
        [$nurse, $facility] = $this->staffAt();
        [$foreigner] = $this->staffAt();
        $foreign = $this->taskFor($foreigner->id, 'Elsewhere');

        foreach (['acknowledge', 'complete', 'escalate'] as $action) {
            $this->actingAtFacility($nurse, $facility)
                ->post(route('portals.staff.tasks.'.$action, $foreign->uuid))
                ->assertNotFound();
        }

        $this->assertSame('open', $foreign->fresh()->status);

        $this->actingAtFacility($nurse, $facility)
            ->get(route('portals.staff.tasks', ['scope' => 'facility']))
            ->assertOk()
            ->assertDontSee('Elsewhere');
    }

    public function test_a_colleagues_task_is_visible_in_facility_scope_but_not_actionable(): void
    {
        [$nurse, $facility] = $this->staffAt();
        [$colleague] = $this->staffAt($facility, 'doctor');
        $task = $this->taskFor($colleague->id, 'Colleague task');

        $this->actingAtFacility($nurse, $facility)
            ->get(route('portals.staff.tasks', ['scope' => 'facility']))
            ->assertOk()->assertSee('Colleague task');

        $this->actingAtFacility($nurse, $facility)
            ->post(route('portals.staff.tasks.complete', $task->uuid))
            ->assertForbidden();
    }

    public function test_facility_admin_reaches_the_inbox_but_not_admin_tasks(): void
    {
        [$admin, $facility] = $this->staffAt(null, 'facility_admin');

        $this->actingAtFacility($admin, $facility)->get(route('portals.staff.tasks'))->assertOk();
        $this->actingAtFacility($admin, $facility)->get(route('portals.admin.tasks'))->assertForbidden();
    }

    public function test_platform_admin_creates_and_cancels_a_task(): void
    {
        [$nurse] = $this->staffAt();
        $admin = $this->platformAdmin();

        $this->actingAsAdmin($admin)->get(route('portals.admin.tasks'))->assertOk();

        $this->actingAsAdmin($admin)->post(route('portals.admin.tasks.store'), [
            'title' => 'Review stock',
            'task_type' => 'review',
            'assigned_to' => $nurse->id,
            'priority' => 'high',
        ])->assertRedirect(route('portals.admin.tasks'));

        $task = ActionTask::where('title', 'Review stock')->firstOrFail();

        $this->actingAsAdmin($admin)->post(route('portals.admin.tasks.cancel', $task->uuid))->assertRedirect();
        $this->assertSame('cancelled', $task->fresh()->status);
    }
}
