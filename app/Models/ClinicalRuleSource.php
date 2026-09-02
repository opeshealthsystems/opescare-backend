<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ClinicalRuleSource — Clinical Decision Support (Module 23)
 *
 * Links a ClinicalRule to its evidence source(s) — guidelines, studies,
 * expert consensus, or external databases.
 *
 * Traceability is mandatory: every CDSS rule must reference at least one
 * evidence source for clinical governance review.
 */
class ClinicalRuleSource extends Model
{
    use HasUuids;

    protected $fillable = [
        'clinical_rule_id',
        'source_type',      // guideline|study|database|expert_consensus
        'title',
        'publication',
        'url',
        'doi',
        'year',
        'evidence_level',   // I|II|III|IV|V (Oxford Evidence Levels)
    ];

    protected $casts = [
        'year' => 'integer',
    ];

    public function clinicalRule(): BelongsTo
    {
        return $this->belongsTo(ClinicalRule::class);
    }

    public function scopeForRule($query, string $ruleId)
    {
        return $query->where('clinical_rule_id', $ruleId);
    }

    public function evidenceLevelLabel(): string
    {
        return match ($this->evidence_level) {
            'I'   => 'Level I — Systematic Review / RCT',
            'II'  => 'Level II — Cohort Study',
            'III' => 'Level III — Case-Control Study',
            'IV'  => 'Level IV — Case Series',
            'V'   => 'Level V — Expert Opinion',
            default => $this->evidence_level ?? 'Ungraded',
        };
    }
}
