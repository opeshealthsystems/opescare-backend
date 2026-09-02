<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * TriageAudit — Triage & Emergency Module
 *
 * Append-only audit log for triage workflow events.
 * Must NEVER be updated or deleted.
 *
 * CDSS Safety: triage audit records support clinical accountability.
 * They assist review workflows but do NOT override clinical judgment.
 */
class TriageAudit extends Model
{
    use HasUuids;

    protected $fillable = [
        'visit_id',
        'triage_record_id',
        'action',             // assessed|score_assigned|escalated|reassessed|overridden
        'performed_by',
        'performed_by_role',
        'payload',
        'clinical_note',
    ];

    protected $casts = ['payload' => 'array'];

    public static function record(string $action, array $extra = []): self
    {
        return static::create(array_merge(['action' => $action], $extra));
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \LogicException('TriageAudit records are append-only.');
    }

    public function delete(): ?bool
    {
        throw new \LogicException('TriageAudit records are append-only.');
    }
}
