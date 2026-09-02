<?php

namespace App\Modules\Queue\Services;

use App\Models\QueueTicket;
use App\Models\QueueStation;
use App\Models\QueueDisplaySetting;

/**
 * QueueDisplayService — Manages public-facing queue display data.
 *
 * PRIVACY RULE: The public queue display MUST NEVER show patient full names.
 * Only ticket numbers, masked identifiers, or first names may be displayed.
 * Display settings are configurable per facility and per station.
 *
 * Display shows: queue numbers, queue assignments, estimated wait time.
 *
 * Schema notes: queue_tickets has no station foreign key — tickets are routed
 * via the `current_queue` string (a facility_queues.name). Stations link to
 * queues via queue_stations.queue_id → facility_queues.
 */
class QueueDisplayService
{
    /**
     * Get sanitized queue data for public display board.
     * Returns ONLY queue number and current queue — no PHI.
     */
    public function getPublicDisplayData(string $facilityId): array
    {
        $settings = QueueDisplaySetting::where('facility_id', $facilityId)
            ->whereNull('station_id')
            ->where('is_active', true)
            ->first();

        $calledTickets = QueueTicket::where('facility_id', $facilityId)
            ->where('status', 'called')
            ->with('patient:id,first_name')
            ->orderByDesc('called_at')
            ->limit($settings?->called_list_count ?? 10)
            ->get();

        $waitingCount = QueueTicket::where('facility_id', $facilityId)
            ->where('status', 'waiting')
            ->count();

        return [
            'called_tickets' => $calledTickets->map(fn ($ticket) => [
                'ticket_number' => $ticket->queue_number,
                'queue'         => $ticket->current_queue,
                'display_name'  => $this->getMaskedDisplay($ticket, $settings),
                'called_at'     => $ticket->called_at?->toTimeString(),
            ])->toArray(),
            'waiting_count' => $waitingCount,
            'last_updated'  => now()->toIso8601String(),
        ];
    }

    /**
     * Get station-level queue for staff display (internal, may show more detail).
     *
     * Tickets carry no station_id — they are matched through the station's
     * linked facility queue (current_queue holds the facility_queues.name).
     */
    public function getStationQueue(string $stationId): \Illuminate\Database\Eloquent\Collection
    {
        $station = QueueStation::with('queue')->findOrFail($stationId);

        $query = QueueTicket::where('facility_id', $station->facility_id)
            ->whereIn('status', ['waiting', 'called']);

        if ($station->queue) {
            $query->where('current_queue', $station->queue->name);
        }

        return $query
            ->orderBy('priority_level')
            ->orderBy('created_at')
            ->get();
    }

    private function getMaskedDisplay(QueueTicket $ticket, ?QueueDisplaySetting $settings): string
    {
        // Default: show only the queue number — never full name
        if (! $settings) {
            return $ticket->queue_number;
        }

        $firstName = $ticket->patient?->first_name;

        return $settings->maskPatientReference(
            $ticket->queue_number,
            $firstName ? substr($firstName, 0, 1) : null
        );
    }
}
