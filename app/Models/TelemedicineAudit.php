<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TelemedicineAudit — Telemedicine Module
 *
 * Append-only audit log for every teleconsultation lifecycle event.
 * Must NEVER be updated or deleted.
 */
class TelemedicineAudit extends Model
{
    use HasUuids;

    protected $fillable = [
        'teleconsultation_id',
        'action',   // scheduled|consent_granted|consent_revoked|call_started|call_ended|cancelled
        'performed_by',
        'ip_address',
        'payload',
    ];

    protected $casts = ['payload' => 'array'];

    public function teleconsultation(): BelongsTo
    {
        return $this->belongsTo(Teleconsultation::class);
    }

    public static function record(string $teleconsultationId, string $action, array $extra = []): self
    {
        return static::create(array_merge(
            ['teleconsultation_id' => $teleconsultationId, 'action' => $action],
            $extra
        ));
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \LogicException('TelemedicineAudit records are append-only.');
    }

    public function delete(): ?bool
    {
        throw new \LogicException('TelemedicineAudit records are append-only.');
    }
}
