<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InpatientNote — Module 19 (Ward / Admission / Bed Management)
 *
 * Clinical notes written during a patient's inpatient stay.
 * Covers physician progress notes, nursing notes, specialist notes, and discharge summaries.
 */
class InpatientNote extends Model
{
    use HasUuids;

    protected $fillable = [
        'admission_id',
        'patient_id',
        'authored_by',
        'note_type',    // progress|nursing|physician|specialist|discharge_summary
        'content',
        'is_signed',
        'signed_at',
    ];

    protected $casts = [
        'is_signed' => 'boolean',
        'signed_at' => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function sign(): void
    {
        $this->update(['is_signed' => true, 'signed_at' => now()]);
    }

    public function isDischargeNote(): bool
    {
        return $this->note_type === 'discharge_summary';
    }
}
