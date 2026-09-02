<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * QueueDisplaySetting — Module 6 (Queue & Patient Flow)
 *
 * Per-facility (or per-station) configuration for the public queue display.
 * Controls what information is shown on waiting-room screens.
 *
 * Security: Public display must never show patient names, Health IDs,
 * diagnoses, or any identifiable clinical data. Only ticket numbers
 * and masked references are allowed.
 */
class QueueDisplaySetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id',
        'station_id',
        'display_mode',
        'show_waiting_count',
        'show_estimated_wait',
        'show_called_list',
        'called_list_count',
        'audio_enabled',
        'audio_language',
        'custom_branding',
        'is_active',
    ];

    protected $casts = [
        'show_waiting_count'  => 'boolean',
        'show_estimated_wait' => 'boolean',
        'show_called_list'    => 'boolean',
        'audio_enabled'       => 'boolean',
        'is_active'           => 'boolean',
        'custom_branding'     => 'array',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(QueueStation::class, 'station_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Returns the display-safe representation of a patient identifier.
     * Never returns full name, Health ID, phone, or clinical data.
     */
    public function maskPatientReference(string $ticketNumber, ?string $firstNameInitial = null): string
    {
        return match($this->display_mode) {
            'ticket_number'      => $ticketNumber,
            'first_name_initial' => $ticketNumber . ($firstNameInitial ? ' (' . strtoupper($firstNameInitial) . '.)' : ''),
            'masked'             => 'Ticket ' . $ticketNumber,
            default              => $ticketNumber,
        };
    }
}
