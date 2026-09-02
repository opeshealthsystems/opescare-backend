<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\QueueTicket;
use App\Modules\Queue\Services\QueueDisplayService;
use App\Modules\Queue\Services\QueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    public function index(Request $request)
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['error' => 'forbidden', 'message' => __('api.facility_unresolved_auth')], 403);
        }

        $query = QueueTicket::query()->orderBy('priority_level')->orderBy('checked_in_at');
        $query->where('facility_id', $facilityId);

        if ($request->filled('queue_name')) {
            $query->where('current_queue', $request->query('queue_name'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return response()->json(['data' => $query->get()->map(fn (QueueTicket $ticket) => $this->serialize($ticket))->values()]);
    }

    public function checkIn(Request $request, QueueService $service)
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['error' => 'forbidden', 'message' => __('api.facility_unresolved_auth')], 403);
        }

        $validated = $request->validate([
            'patient_id' => ['required', 'uuid'],
            'provider_id' => ['nullable', 'uuid'],
            'appointment_id' => ['nullable', 'uuid'],
            'visit_id' => ['nullable', 'uuid'],
            'destination_queue' => ['required', 'string'],
            'visit_type' => ['nullable', 'string'],
            'actor_id' => ['nullable', 'uuid'],
            'priority_level' => ['nullable', 'integer'],
        ]);
        $validated['facility_id'] = $facilityId;

        $ticket = $service->checkInWalkIn($validated);

        return response()->json(['data' => $this->serialize($ticket)], 201);
    }

    public function callNext(Request $request, QueueService $service)
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['error' => 'forbidden', 'message' => __('api.facility_unresolved_auth')], 403);
        }

        $validated = $request->validate([
            'queue_name' => ['required', 'string'],
            'actor_id' => ['nullable', 'uuid'],
        ]);

        return response()->json(['data' => $this->serialize($service->callNext($facilityId, $validated['queue_name'], $validated['actor_id'] ?? null))]);
    }

    public function startService(Request $request, QueueTicket $ticket, QueueService $service)
    {
        $validated = $request->validate(['actor_id' => ['nullable', 'uuid']]);

        return response()->json(['data' => $this->serialize($service->startService($ticket, $validated['actor_id'] ?? null))]);
    }

    public function transfer(Request $request, QueueTicket $ticket, QueueService $service)
    {
        $validated = $request->validate([
            'to_queue' => ['required', 'string'],
            'reason' => ['required', 'string'],
            'actor_id' => ['nullable', 'uuid'],
        ]);

        return response()->json(['data' => $this->serialize($service->transfer($ticket, $validated['to_queue'], $validated['reason'], $validated['actor_id'] ?? null))]);
    }

    public function prioritize(Request $request, QueueTicket $ticket, QueueService $service)
    {
        $validated = $request->validate([
            'status' => ['required', 'string'],
            'reason' => ['required', 'string'],
            'actor_id' => ['nullable', 'uuid'],
        ]);

        return response()->json(['data' => $this->serialize($service->prioritize($ticket, $validated['status'], $validated['reason'], $validated['actor_id'] ?? null))]);
    }

    public function complete(Request $request, QueueTicket $ticket, QueueService $service)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string'],
            'actor_id' => ['nullable', 'uuid'],
        ]);

        return response()->json(['data' => $this->serialize($service->complete($ticket, $validated['reason'], $validated['actor_id'] ?? null))]);
    }

    public function cancel(Request $request, QueueTicket $ticket, QueueService $service)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string'],
            'actor_id' => ['nullable', 'uuid'],
        ]);

        return response()->json(['data' => $this->serialize($service->cancel($ticket, $validated['reason'], $validated['actor_id'] ?? null))]);
    }

    public function display(Request $request, QueueService $service)
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['error' => 'forbidden', 'message' => __('api.facility_unresolved_auth')], 403);
        }

        $validated = $request->validate([
            'queue_name' => ['nullable', 'string'],
        ]);

        return response()->json(['data' => $service->maskedDisplay($facilityId, $validated['queue_name'] ?? null)]);
    }

    // ── Public Display Board ──────────────────────────────────────────────

    /**
     * Public queue display board — sanitized, no PHI.
     * Returns called ticket numbers, station assignments, and waiting count.
     *
     * PRIVACY RULE: Never returns patient names, DOB, or any identifiable info.
     * facility_id from middleware attributes (preferred) or from query string
     * for public display boards that may not carry auth tokens.
     *
     * GET /v1/queues/public-display?facility_id=...
     */
    public function publicDisplay(Request $request, QueueDisplayService $service): JsonResponse
    {
        // facility_id from middleware when authenticated; fallback to query param
        // for unauthenticated public display boards (lobby screens, kiosks)
        $facilityId = $request->attributes->get('facility_id')
            ?? $request->query('facility_id');

        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_id_required')], 422);
        }

        return response()->json($service->getPublicDisplayData($facilityId));
    }

    /**
     * Station-level queue view for staff workstations.
     * Returns waiting and called tickets for a specific queue station.
     *
     * GET /v1/queues/stations/{stationId}
     */
    public function stationQueue(string $stationId, QueueDisplayService $service): JsonResponse
    {
        $tickets = $service->getStationQueue($stationId);

        return response()->json([
            'station_id' => $stationId,
            'count'      => $tickets->count(),
            'tickets'    => $tickets->map(fn ($t) => $this->serialize($t))->values(),
        ]);
    }

    private function serialize(QueueTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'patient_id' => $ticket->patient_id,
            'facility_id' => $ticket->facility_id,
            'visit_id' => $ticket->visit_id,
            'appointment_id' => $ticket->appointment_id,
            'queue_number' => $ticket->queue_number,
            'current_queue' => $ticket->current_queue,
            'status' => $ticket->status,
            'priority_level' => $ticket->priority_level,
            'assigned_to_id' => $ticket->assigned_to_id,
            'checked_in_at' => $ticket->checked_in_at?->toISOString(),
        ];
    }
}
