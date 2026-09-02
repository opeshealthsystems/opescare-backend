<?php

namespace App\Modules\Broadcasts\Services;

use App\Modules\Broadcasts\Models\Broadcast;
use App\Modules\Broadcasts\Models\BroadcastAcknowledgement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * BroadcastService — Manages the full lifecycle of platform broadcasts.
 *
 * Broadcasts are facility-scoped or platform-wide messages sent to a
 * defined set of recipients (all_staff, all_patients, role:nurse, etc.).
 *
 * Lifecycle:
 *   draft → published (dispatch notifications) → cancelled | expired
 *
 * When requires_acknowledgement = true, acknowledgement records are tracked
 * in broadcast_acknowledgements. The platform will not clear the broadcast
 * from a user's view until they acknowledge it.
 */
class BroadcastService
{
    // ── Draft creation ────────────────────────────────────────────────────

    /**
     * Create a broadcast draft.
     * Status starts as 'draft' — call publish() to send.
     */
    public function create(array $data, string $createdBy): Broadcast
    {
        return Broadcast::create([
            'uuid'                    => (string) Str::uuid(),
            'broadcast_type'          => $data['broadcast_type'],
            'title'                   => $data['title'],
            'body'                    => $data['body'],
            'target_type'             => $data['target_type'],
            'target_ids_json'         => json_encode($data['target_ids'] ?? []),
            'priority'                => $data['priority'] ?? 'normal',
            'language'                => $data['language'] ?? 'en',
            'requires_acknowledgement'=> $data['requires_acknowledgement'] ?? false,
            'expires_at'              => $data['expires_at'] ?? null,
            'status'                  => 'draft',
            'created_by'              => $createdBy,
        ]);
    }

    // ── Publishing ────────────────────────────────────────────────────────

    /**
     * Publish a draft broadcast — sets status to 'published' and records
     * the publish timestamp. Returns the updated broadcast.
     *
     * @throws \DomainException if broadcast is not in draft state
     */
    public function publish(Broadcast $broadcast): Broadcast
    {
        if ($broadcast->status !== 'draft') {
            throw new \DomainException(
                "Cannot publish broadcast — current status is '{$broadcast->status}'. Only drafts can be published."
            );
        }

        $broadcast->update([
            'status'     => 'published',
            'publish_at' => now(),
        ]);

        return $broadcast->fresh();
    }

    /**
     * Update a draft broadcast's content.
     *
     * @throws \DomainException if broadcast has already been published
     */
    public function update(Broadcast $broadcast, array $data): Broadcast
    {
        if ($broadcast->status !== 'draft') {
            throw new \DomainException(
                "Cannot edit broadcast — only drafts can be updated. Current status: '{$broadcast->status}'."
            );
        }

        $broadcast->update(array_filter([
            'broadcast_type'           => $data['broadcast_type'] ?? null,
            'title'                    => $data['title'] ?? null,
            'body'                     => $data['body'] ?? null,
            'target_type'              => $data['target_type'] ?? null,
            'target_ids_json'          => isset($data['target_ids']) ? json_encode($data['target_ids']) : null,
            'priority'                 => $data['priority'] ?? null,
            'language'                 => $data['language'] ?? null,
            'requires_acknowledgement' => $data['requires_acknowledgement'] ?? null,
            'expires_at'               => $data['expires_at'] ?? null,
        ], fn ($v) => $v !== null));

        return $broadcast->fresh();
    }

    // ── Cancellation ──────────────────────────────────────────────────────

    /**
     * Cancel a broadcast. Cancellation is allowed on draft or published broadcasts.
     * Cancelled broadcasts are hidden from recipient feeds.
     *
     * @throws \DomainException if broadcast is already cancelled
     */
    public function cancel(Broadcast $broadcast): Broadcast
    {
        if ($broadcast->status === 'cancelled') {
            throw new \DomainException('Broadcast is already cancelled.');
        }

        $broadcast->update(['status' => 'cancelled']);

        return $broadcast->fresh();
    }

    // ── Acknowledgement ───────────────────────────────────────────────────

    /**
     * Record a user's acknowledgement of a broadcast.
     * Idempotent — if the user has already acknowledged, returns the existing record.
     *
     * @throws \DomainException if broadcast is not published or does not require acknowledgement
     */
    public function acknowledge(
        Broadcast $broadcast,
        string    $userId,
        ?string   $facilityId = null,
        ?string   $ipAddress  = null
    ): BroadcastAcknowledgement {
        if (!$broadcast->isPublished()) {
            throw new \DomainException(
                "Cannot acknowledge broadcast — it is not published (status: '{$broadcast->status}')."
            );
        }

        if (!$broadcast->requires_acknowledgement) {
            throw new \DomainException(
                'This broadcast does not require acknowledgement.'
            );
        }

        // Idempotent: firstOrCreate ensures one record per user per broadcast
        return BroadcastAcknowledgement::firstOrCreate(
            [
                'broadcast_id' => $broadcast->id,
                'user_id'      => $userId,
            ],
            [
                'facility_id'     => $facilityId,
                'ip_address'      => $ipAddress,
                'acknowledged_at' => now(),
            ]
        );
    }

    // ── Queries ───────────────────────────────────────────────────────────

    /**
     * Get all active (published, non-expired) broadcasts.
     * Used for the recipient feed.
     */
    public function getActive(?string $facilityId = null): Collection
    {
        return Broadcast::where('status', 'published')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->when($facilityId, function ($q, $fid) {
                // Include platform-wide (target_type = 'all') and facility-specific
                $q->where(fn ($inner) =>
                    $inner->where('target_type', 'all')
                          ->orWhere(fn ($like) =>
                              $like->where('target_type', 'facility')
                                   ->whereRaw("target_ids_json::text LIKE ?", ["%{$fid}%"])
                          )
                );
            })
            ->withCount('acknowledgements')
            ->orderByDesc('publish_at')
            ->get();
    }

    /**
     * Get acknowledgement summary for a broadcast.
     * Returns total acknowledgements and the list of acknowledging users.
     */
    public function acknowledgementSummary(Broadcast $broadcast): array
    {
        $acks = $broadcast->acknowledgements()->orderByDesc('acknowledged_at')->get();

        return [
            'broadcast_id'          => $broadcast->id,
            'requires_acknowledgement' => $broadcast->requires_acknowledgement,
            'total_acknowledgements'=> $acks->count(),
            'acknowledgements'      => $acks->map(fn ($a) => [
                'user_id'         => $a->user_id,
                'facility_id'     => $a->facility_id,
                'acknowledged_at' => $a->acknowledged_at?->toISOString(),
            ]),
        ];
    }

    /**
     * Check whether a specific user has acknowledged a broadcast.
     */
    public function hasAcknowledged(Broadcast $broadcast, string $userId): bool
    {
        return BroadcastAcknowledgement::where('broadcast_id', $broadcast->id)
            ->where('user_id', $userId)
            ->exists();
    }
}
