<?php

namespace App\Http\Controllers\Api\ProviderMobile;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\MobileFacilityContext;
use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Provider Mobile API — Assigned Tasks
 *
 * Returns the provider's queue and upcoming appointments for today
 * within the current facility context.
 */
class ProviderMobileTaskController extends Controller
{
    /**
     * Get today's task list for the provider (queue + appointments).
     *
     * GET /api/provider-mobile/tasks
     */
    public function index(Request $request): JsonResponse
    {
        $userId  = $this->resolveUserId($request);
        $context = MobileFacilityContext::currentFor($userId);

        $facilityId = $context?->facility_id;

        // Queue entries waiting for this provider
        $queueQuery = QueueTicket::query()
            ->with('patient:id,health_id,first_name,last_name,sex')
            ->where('status', 'waiting')
            ->orderBy('priority_level')
            ->orderBy('created_at');

        if ($facilityId) {
            $queueQuery->where('facility_id', $facilityId);
        }

        $queue = $queueQuery->take(20)->get()->map(fn ($q) => [
            'type'         => 'queue',
            'id'           => $q->id,
            'patient_name' => $q->patient?->first_name . ' ' . $q->patient?->last_name,
            'health_id'    => $q->patient?->health_id,
            'priority'     => $q->priority_level,
            'status'       => $q->status,
            'since'        => $q->created_at?->toIso8601String(),
        ]);

        // Today's appointments for this provider
        $apptQuery = Appointment::query()
            ->with('patient:id,health_id,first_name,last_name')
            ->where('provider_id', $userId)
            ->whereIn('status', ['booked', 'confirmed', 'checked_in'])
            ->whereDate('scheduled_at', today())
            ->orderBy('scheduled_at');

        if ($facilityId) {
            $apptQuery->where('facility_id', $facilityId);
        }

        $appointments = $apptQuery->take(20)->get()->map(fn ($a) => [
            'type'             => 'appointment',
            'id'               => $a->id,
            'patient_name'     => $a->patient?->first_name . ' ' . $a->patient?->last_name,
            'health_id'        => $a->patient?->health_id,
            'appointment_type' => $a->appointment_type,
            'status'           => $a->status,
            'scheduled_at'     => $a->scheduled_at?->toIso8601String(),
        ]);

        return response()->json([
            'facility_context' => $context ? [
                'facility_id'   => $context->facility_id,
                'facility_name' => $context->facility?->name,
            ] : null,
            'queue'        => $queue,
            'appointments' => $appointments,
            'total_tasks'  => $queue->count() + $appointments->count(),
        ]);
    }

    /**
     * Mark a queue entry as called (provider is seeing this patient now).
     *
     * POST /api/provider-mobile/tasks/queue/{id}/call
     */
    public function callQueueEntry(Request $request, string $id): JsonResponse
    {
        $entry = QueueTicket::findOrFail($id);
        $entry->update(['status' => 'in_progress']);

        return response()->json([
            'status'  => 'called',
            'task_id' => $entry->id,
            'patient' => $entry->patient?->health_id,
        ]);
    }

    /**
     * Mark a queue entry as completed.
     *
     * POST /api/provider-mobile/tasks/queue/{id}/complete
     */
    public function completeQueueEntry(Request $request, string $id): JsonResponse
    {
        $entry = QueueTicket::findOrFail($id);
        $entry->update(['status' => 'completed', 'completed_at' => now()]);

        return response()->json([
            'status'  => 'completed',
            'task_id' => $entry->id,
        ]);
    }

    // -------------------------------------------------------------------------

    private function resolveUserId(Request $request): string
    {
        if ($request->has('_user_id')) {
            return $request->input('_user_id');
        }
        return User::value('id') ?? 'demo-provider';
    }
}
