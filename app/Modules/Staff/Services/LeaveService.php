<?php

namespace App\Modules\Staff\Services;

use App\Models\LeaveRequest;
use Illuminate\Support\Collection;

class LeaveService
{
    public function listLeaveRequests(string $facilityId, array $filters = []): Collection
    {
        $query = LeaveRequest::whereHas('staffProfile', function ($q) use ($facilityId) {
            $q->where('facility_id', $facilityId);
        })->with('staffProfile')->orderByDesc('created_at');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['leave_type'])) {
            $query->where('leave_type', $filters['leave_type']);
        }

        return $query->get();
    }

    public function requestLeave(string $profileId, array $data): LeaveRequest
    {
        $start = \Carbon\Carbon::parse($data['start_date']);
        $end   = \Carbon\Carbon::parse($data['end_date']);
        $days  = $start->diffInWeekdays($end) + 1;

        return LeaveRequest::create(array_merge($data, [
            'staff_profile_id' => $profileId,
            'status'           => 'pending',
            'days_requested'   => $data['days_requested'] ?? $days,
        ]));
    }

    public function approveLeave(string $requestId, string $reviewerId, ?string $notes = null): LeaveRequest
    {
        return $this->review($requestId, 'approved', $reviewerId, $notes);
    }

    public function rejectLeave(string $requestId, string $reviewerId, ?string $notes = null): LeaveRequest
    {
        return $this->review($requestId, 'rejected', $reviewerId, $notes);
    }

    public function withdrawLeave(string $requestId): LeaveRequest
    {
        $request = LeaveRequest::findOrFail($requestId);
        if (!$request->canBeWithdrawn()) {
            throw new \RuntimeException("Leave request cannot be withdrawn in status: {$request->status}");
        }
        $request->update(['status' => 'withdrawn']);
        return $request;
    }

    private function review(string $requestId, string $status, string $reviewerId, ?string $notes): LeaveRequest
    {
        $request = LeaveRequest::findOrFail($requestId);
        if (!$request->canBeReviewed()) {
            throw new \RuntimeException("Leave request cannot be reviewed in status: {$request->status}");
        }
        $request->update([
            'status'       => $status,
            'reviewed_by'  => $reviewerId,
            'review_notes' => $notes,
            'reviewed_at'  => now(),
        ]);

        // If approved, sync staff status to on_leave when leave period starts today or earlier
        if ($status === 'approved') {
            $start = \Carbon\Carbon::parse($request->start_date);
            if ($start->isToday() || $start->isPast()) {
                $request->staffProfile?->update(['status' => 'on_leave']);
            }
        }

        return $request;
    }
}
