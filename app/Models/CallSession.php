<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CallSession — Module 18 (Telemedicine)
 *
 * Records the technical session details for a teleconsultation call,
 * including provider (Zoom, WebRTC, etc.), connection quality, and recording status.
 */
class CallSession extends Model
{
    use HasUuids;
    use \App\Traits\HasFacilityScope;

    protected $fillable = [
        'teleconsultation_id',
        'session_provider',       // webrtc|zoom|meet|teams
        'external_session_id',
        'status',                 // initiated|active|ended|failed
        'video_enabled',
        'audio_enabled',
        'recording_enabled',
        'recording_url',
        'participant_count',
        'started_at',
        'ended_at',
        'connection_quality_log',
    ];

    protected $casts = [
        'video_enabled'          => 'boolean',
        'audio_enabled'          => 'boolean',
        'recording_enabled'      => 'boolean',
        'participant_count'      => 'integer',
        'started_at'             => 'datetime',
        'ended_at'               => 'datetime',
        'connection_quality_log' => 'array',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function teleconsultation(): BelongsTo
    {
        return $this->belongsTo(Teleconsultation::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function durationSeconds(): ?int
    {
        if (! $this->started_at || ! $this->ended_at) {
            return null;
        }
        return (int) $this->started_at->diffInSeconds($this->ended_at);
    }

    public function durationMinutes(): ?int
    {
        $secs = $this->durationSeconds();
        return $secs !== null ? (int) ceil($secs / 60) : null;
    }
}
