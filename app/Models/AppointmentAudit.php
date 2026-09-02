<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AppointmentAudit — Appointments Module
 *
 * Append-only audit log for every action on an Appointment record.
 * Must NEVER be updated or deleted.
 */
class AppointmentAudit extends Model
{
    use HasUuids;

    protected $fillable = [
        'appointment_id',
        'action',             // created|rescheduled|cancelled|checked_in|no_show
        'performed_by',
        'performed_by_role',
        'before_state',
        'after_state',
        'ip_address',
        'reason',
    ];

    protected $casts = [
        'before_state' => 'array',
        'after_state'  => 'array',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public static function record(string $appointmentId, string $action, array $payload = []): self
    {
        return static::create(array_merge(['appointment_id' => $appointmentId, 'action' => $action], $payload));
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \LogicException('AppointmentAudit records are append-only.');
    }

    public function delete(): ?bool
    {
        throw new \LogicException('AppointmentAudit records are append-only.');
    }
}
