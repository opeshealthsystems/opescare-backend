<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * NotificationEvent — Cross-Module Notification Tracking
 *
 * Tracks every notification dispatched across the platform (visit alerts,
 * appointment reminders, lab-result ready, critical alerts, etc.).
 *
 * Each record is an immutable event once sent. Status progression:
 * pending → sent → delivered → read
 * pending → sent → failed
 */
class NotificationEvent extends Model
{
    use HasUuids;

    protected $fillable = [
        'event_type',         // visit_created|appointment_confirmed|lab_ready|critical_alert|etc
        'notifiable_type',    // Patient|Staff|Provider
        'notifiable_id',
        'channel',            // sms|email|push|in_app
        'status',             // pending|sent|delivered|failed|read
        'payload',
        'reference_type',     // Visit|Appointment|LabOrder|etc
        'reference_id',
        'sent_at',
        'delivered_at',
        'read_at',
        'failure_reason',
    ];

    protected $casts = [
        'payload'      => 'array',
        'sent_at'      => 'datetime',
        'delivered_at' => 'datetime',
        'read_at'      => 'datetime',
    ];

    public function scopeForNotifiable($query, string $type, string $id)
    {
        return $query->where('notifiable_type', $type)->where('notifiable_id', $id);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function markSent(): void
    {
        $this->update(['status' => 'sent', 'sent_at' => now()]);
    }

    public function markDelivered(): void
    {
        $this->update(['status' => 'delivered', 'delivered_at' => now()]);
    }

    public function markRead(): void
    {
        $this->update(['status' => 'read', 'read_at' => now()]);
    }

    public function markFailed(string $reason): void
    {
        $this->update(['status' => 'failed', 'failure_reason' => $reason]);
    }

    public function isDelivered(): bool
    {
        return in_array($this->status, ['delivered', 'read'], true);
    }
}
