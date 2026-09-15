<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\FacilityRoleAssignment;
use App\Models\StaffProfile;
use App\Models\User;
use App\Modules\Broadcasts\Services\BroadcastService;
use App\Modules\Tasks\Models\ActionTask;
use App\Modules\Tasks\Services\TaskService;
use App\Services\Portal\DeskFacilityContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * TaskInboxController — staff action-task inbox (imported from the platform,
 * docs/PLATFORM_IMPORTS.md group B).
 *
 * Finder adaptations:
 *  - the facility comes ONLY from DeskFacilityContext (never the request);
 *  - action_tasks.facility_id is a legacy bigint that cannot hold a facility
 *    uuid, so a task belongs to the facility its ASSIGNEE is posted at (the
 *    platform's AdminTaskController rule). A task outside that set is a 404,
 *    never a 403 — the inbox does not confirm another facility's task exists;
 *  - "mine" = assigned to me; "facility" = assigned to anyone posted here;
 *  - broadcasts addressed to this user at this facility are listed on top
 *    with an acknowledge action (BroadcastService::forStaff).
 */
class TaskInboxController extends Controller
{
    public function __construct(
        private readonly TaskService $tasks,
        private readonly BroadcastService $broadcasts,
    ) {}

    private function facilityId(): string
    {
        $facilityId = app(DeskFacilityContext::class)->resolve();
        abort_unless($facilityId, 403);

        return $facilityId;
    }

    /** User ids (as strings) of staff posted at a facility. */
    public static function staffIdsAt(string $facilityId): array
    {
        return User::query()
            ->where('primary_facility_id', $facilityId)
            ->pluck('id')
            ->merge(FacilityRoleAssignment::query()->where('facility_id', $facilityId)->active()->pluck('user_id'))
            ->merge(StaffProfile::query()->where('facility_id', $facilityId)->where('status', 'active')->pluck('user_id'))
            ->filter()->map(fn ($id) => (string) $id)->unique()->values()->all();
    }

    private function scoped(string $facilityId): Builder
    {
        return ActionTask::query()->whereIn('assigned_to', self::staffIdsAt($facilityId));
    }

    public function index(Request $request): View
    {
        $facilityId = $this->facilityId();
        $userId = (string) Auth::id();
        $scope = $request->input('scope') === 'facility' ? 'facility' : 'mine';

        $open = $this->scoped($facilityId)
            ->when($scope === 'mine', fn ($q) => $q->where('assigned_to', $userId))
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END, due_at')
            ->paginate(25)
            ->withQueryString();

        $assignees = User::whereIn('id', $open->getCollection()->pluck('assigned_to')->filter()->unique()->all())
            ->get(['id', 'name'])->keyBy(fn ($u) => (string) $u->id);

        return view('portals.staff.tasks.index', [
            'tasks' => $open,
            'scope' => $scope,
            'assignees' => $assignees,
            'broadcasts' => $this->broadcasts->forStaff(Auth::user(), $facilityId),
        ]);
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

    public function acknowledgeBroadcast(Request $request, string $uuid): RedirectResponse
    {
        $facilityId = $this->facilityId();
        $broadcast = $this->broadcasts->forStaff(Auth::user(), $facilityId)->firstWhere('uuid', $uuid);
        abort_unless($broadcast, 404);

        try {
            $this->broadcasts->acknowledge($broadcast, (string) Auth::id(), $facilityId, $request->ip());
        } catch (\DomainException) {
            return back()->with('error', __('broadcasts.ack_not_required'));
        }

        return back()->with('success', __('broadcasts.acknowledged_flash'));
    }

    /**
     * The task must sit at the desk facility (else 404) and be assigned to the
     * user — or to their role — (else 403), as on the platform.
     */
    private function authorizeOwnership(string $uuid): ActionTask
    {
        $task = $this->scoped($this->facilityId())->where('uuid', $uuid)->first();
        abort_unless($task, 404);

        $user = Auth::user();
        $ownsByUser = $task->assigned_to !== null && (string) $task->assigned_to === (string) Auth::id();
        $ownsByRole = $task->assigned_role !== null && $task->assigned_role === ($user->role->name ?? null);
        abort_unless($ownsByUser || $ownsByRole, 403);

        return $task;
    }
}
