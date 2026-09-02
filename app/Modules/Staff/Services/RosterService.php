<?php

namespace App\Modules\Staff\Services;

use App\Models\StaffShift;
use App\Models\DutyRoster;
use App\Models\RosterAssignment;
use Illuminate\Support\Collection;

class RosterService
{
    // ── Shifts ──────────────────────────────────────────────

    public function listShifts(string $facilityId): Collection
    {
        return StaffShift::where('facility_id', $facilityId)
            ->orderBy('name')
            ->get();
    }

    public function createShift(string $facilityId, array $data): StaffShift
    {
        return StaffShift::create(array_merge($data, ['facility_id' => $facilityId]));
    }

    public function toggleShiftStatus(string $shiftId): StaffShift
    {
        $shift = StaffShift::findOrFail($shiftId);
        $shift->update(['status' => $shift->status === 'active' ? 'inactive' : 'active']);
        return $shift;
    }

    // ── Rosters ──────────────────────────────────────────────

    public function listRosters(string $facilityId, array $filters = []): Collection
    {
        $query = DutyRoster::where('facility_id', $facilityId)
            ->orderByDesc('period_start');

        if (!empty($filters['department'])) {
            $query->where('department', $filters['department']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->withCount('assignments')->get();
    }

    public function createRoster(string $facilityId, string $actorId, array $data): DutyRoster
    {
        return DutyRoster::create(array_merge($data, [
            'facility_id' => $facilityId,
            'created_by'  => $actorId,
            'status'      => 'draft',
        ]));
    }

    public function publishRoster(string $rosterId): DutyRoster
    {
        $roster = DutyRoster::findOrFail($rosterId);
        if (!$roster->canBePublished()) {
            throw new \RuntimeException("Roster cannot be published in status: {$roster->status}");
        }
        $roster->update([
            'status'       => 'published',
            'published_at' => now(),
        ]);
        return $roster;
    }

    public function archiveRoster(string $rosterId): DutyRoster
    {
        $roster = DutyRoster::findOrFail($rosterId);
        if (!$roster->canBeArchived()) {
            throw new \RuntimeException("Roster cannot be archived in status: {$roster->status}");
        }
        $roster->update(['status' => 'archived']);
        return $roster;
    }

    // ── Roster Assignments ───────────────────────────────────

    public function addAssignment(string $rosterId, string $actorId, array $data): RosterAssignment
    {
        // Check for double-booking
        $exists = RosterAssignment::where('staff_profile_id', $data['staff_profile_id'])
            ->where('work_date', $data['work_date'])
            ->where('staff_shift_id', $data['staff_shift_id'])
            ->exists();

        if ($exists) {
            throw new \RuntimeException('This staff member is already assigned to this shift on that date.');
        }

        return RosterAssignment::create(array_merge($data, [
            'duty_roster_id' => $rosterId,
            'assigned_by'    => $actorId,
            'status'         => 'scheduled',
        ]));
    }

    public function removeAssignment(string $assignmentId): void
    {
        RosterAssignment::findOrFail($assignmentId)->delete();
    }

    public function getRosterWithAssignments(string $rosterId): DutyRoster
    {
        return DutyRoster::with([
            'assignments.staffProfile',
            'assignments.staffShift',
        ])->findOrFail($rosterId);
    }
}
