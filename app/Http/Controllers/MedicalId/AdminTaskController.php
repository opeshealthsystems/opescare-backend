<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Tasks\Models\ActionTask;
use App\Modules\Tasks\Services\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * AdminTaskController — facility task management.
 *
 * Complements the per-user staff inbox (TaskInboxController): managers see every
 * action-task in their facility, create + assign them, reassign, and close or
 * escalate. Scoped to the active facility; platform admins (no facility context)
 * see all. Backed by the existing TaskService.
 */
class AdminTaskController extends Controller
{
    private const TASK_TYPES = ['follow_up', 'callback', 'review', 'referral', 'admin', 'clinical'];

    public function __construct(private readonly TaskService $tasks) {}

    private function facilityId(): ?string
    {
        return session('active_facility_id') ?? Auth::user()?->primary_facility_id;
    }

    /**
     * Staff user IDs at the active facility. Tasks are scoped to their assignee's
     * facility because action_tasks.facility_id is a legacy bigint column that
     * cannot hold the UUID facility keys this platform uses.
     */
    private function facilityStaffIds(): ?array
    {
        $facilityId = $this->facilityId();
        if (!$facilityId) {
            return null; // platform admin / no facility context -> all tasks
        }

        return User::where('primary_facility_id', $facilityId)
            ->whereDoesntHave('role', fn ($q) => $q->where('name', 'patient'))
            ->pluck('id')->map(fn ($id) => (string) $id)->all();
    }

    public function index(Request $request): View
    {
        $staffIds = $this->facilityStaffIds();

        $query = ActionTask::query()
            ->when($staffIds !== null, fn ($q) => $q->whereIn('assigned_to', $staffIds))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderByRaw("CASE WHEN status IN ('completed','cancelled') THEN 1 ELSE 0 END")
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END, due_at')
            ->orderByDesc('created_at');

        $tasks = $query->paginate(25)->withQueryString();

        $assigneeIds = $tasks->getCollection()->pluck('assigned_to')->filter()->unique()->all();
        $assignees   = User::whereIn('id', $assigneeIds)->get(['id', 'name'])->keyBy('id');

        $staff = ($staffIds !== null
            ? User::whereIn('id', $staffIds)
            : User::whereDoesntHave('role', fn ($q) => $q->where('name', 'patient')))
            ->orderBy('name')->limit(200)->get(['id', 'name']);

        return view('portals.admin.tasks.index', [
            'tasks'     => $tasks,
            'assignees' => $assignees,
            'staff'     => $staff,
            'types'     => self::TASK_TYPES,
            'status'    => $request->input('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'       => 'required|string|max:160',
            'task_type'   => 'required|string|in:'.implode(',', self::TASK_TYPES),
            'description' => 'nullable|string|max:1000',
            'assigned_to' => 'required|uuid|exists:users,id',
            'priority'    => 'nullable|in:low,normal,high,urgent',
            'due_at'      => 'nullable|date',
        ]);

        $this->assertAssigneeInFacility($data['assigned_to']);

        // facility_id is intentionally omitted (legacy bigint column, incompatible
        // with UUID facility keys); the assignee determines the facility.
        $this->tasks->createTask([
            'task_type'   => $data['task_type'],
            'title'       => $data['title'],
            'description' => $data['description'] ?? '',
            'assigned_to' => $data['assigned_to'],
            'priority'    => $data['priority'] ?? 'normal',
            'due_at'      => $data['due_at'] ?? null,
        ]);

        return redirect()->route('portals.admin.tasks')->with('success', __('tasks.created'));
    }

    public function reassign(Request $request, string $uuid): RedirectResponse
    {
        $data = $request->validate(['assigned_to' => 'required|uuid|exists:users,id']);

        $this->scopedTask($uuid);
        $this->assertAssigneeInFacility($data['assigned_to']);

        ActionTask::where('uuid', $uuid)->firstOrFail()
            ->update(['assigned_to' => $data['assigned_to'], 'status' => 'open', 'acknowledged_at' => null]);

        return back()->with('success', __('tasks.reassigned'));
    }

    /** The new assignee must belong to the admin's facility (when scoped). */
    private function assertAssigneeInFacility(string $userId): void
    {
        $staffIds = $this->facilityStaffIds();
        abort_if($staffIds !== null && !in_array($userId, $staffIds, true), 403);
    }

    public function complete(string $uuid): RedirectResponse
    {
        $this->scopedTask($uuid);
        $this->tasks->completeTask($uuid);

        return back()->with('success', __('tasks.completed'));
    }

    public function escalate(string $uuid): RedirectResponse
    {
        $this->scopedTask($uuid);
        $this->tasks->escalateTask($uuid);

        return back()->with('success', __('tasks.escalated'));
    }

    /** Load a task and ensure its assignee belongs to the admin's facility scope. */
    private function scopedTask(string $uuid): ActionTask
    {
        $task = ActionTask::where('uuid', $uuid)->firstOrFail();
        $staffIds = $this->facilityStaffIds();
        abort_if($staffIds !== null && !in_array((string) $task->assigned_to, $staffIds, true), 403);

        return $task;
    }
}
