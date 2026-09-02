<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Modules\Tasks\Models\ActionTask;
use App\Modules\Tasks\Services\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * TaskInboxController — staff/admin action-task inbox.
 *
 * Surfaces the existing Tasks module (TaskService + ActionTask) which had no UI:
 * staff see action-tasks assigned to them and can acknowledge / complete /
 * escalate. Backed entirely by the existing service so behaviour stays
 * consistent with the API/escalation paths.
 */
class TaskInboxController extends Controller
{
    public function __construct(private readonly TaskService $tasks) {}

    public function index(): View
    {
        $userId = (string) Auth::id();

        $open = ActionTask::where('assigned_to', $userId)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END, due_at')
            ->paginate(25);

        return view('portals.staff.tasks.index', ['tasks' => $open]);
    }

    public function acknowledge(string $uuid): RedirectResponse
    {
        $this->authorizeOwnership($uuid);
        $this->tasks->acknowledgeTask($uuid, (string) Auth::id());

        return back()->with('success', __('tasks.acknowledged'));
    }

    public function complete(string $uuid): RedirectResponse
    {
        $this->authorizeOwnership($uuid);
        $this->tasks->completeTask($uuid);

        return back()->with('success', __('tasks.completed'));
    }

    public function escalate(string $uuid): RedirectResponse
    {
        $this->authorizeOwnership($uuid);
        $this->tasks->escalateTask($uuid);

        return back()->with('success', __('tasks.escalated'));
    }

    /** A user may only act on a task assigned to them (or their role). */
    private function authorizeOwnership(string $uuid): void
    {
        $task = ActionTask::where('uuid', $uuid)->firstOrFail();
        $user = Auth::user();
        $ownsByUser = $task->assigned_to !== null && (string) $task->assigned_to === (string) Auth::id();
        $ownsByRole = $task->assigned_role !== null && $task->assigned_role === ($user->role->name ?? null);
        abort_unless($ownsByUser || $ownsByRole, 403);
    }
}
