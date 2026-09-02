<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * DataDictionaryEntry — Canonical field registry for OpesCare.
 *
 * Prevents developers from using inconsistent field names across modules,
 * migrations, API payloads, and import templates.
 *
 * Every field that stores PHI must set is_phi = true.
 * PHI fields are subject to data residency and minimum-necessary-access rules.
 */
class DataDictionaryEntry extends Model
{
    use HasUuids;

    protected $fillable = [
        'field_name', 'display_name', 'description', 'data_type',
        'module', 'table_name', 'column_name',
        'is_phi', 'is_required', 'allowed_values', 'fhir_path',
        'status', 'proposed_by', 'approved_by', 'approved_at', 'deprecated_reason',
    ];

    protected $casts = [
        'is_phi'         => 'boolean',
        'is_required'    => 'boolean',
        'allowed_values' => 'array',
        'approved_at'    => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────────

    public function fieldDefinitions(): HasMany { return $this->hasMany(FieldDefinition::class); }
    public function moduleFieldMaps(): HasMany { return $this->hasMany(ModuleFieldMap::class); }
    public function apiPayloadFieldMaps(): HasMany { return $this->hasMany(ApiPayloadFieldMap::class); }
    public function importTemplateFieldMaps(): HasMany { return $this->hasMany(ImportTemplateFieldMap::class); }

    // ── Actions ────────────────────────────────────────────────────────────

    public function approve(string $approvedBy): void
    {
        $this->update([
            'status'      => 'active',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    public function deprecate(string $reason): void
    {
        $this->update(['status' => 'deprecated', 'deprecated_reason' => $reason]);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeActive($query) { return $query->where('status', 'active'); }
    public function scopePhi($query) { return $query->where('is_phi', true); }
    public function scopeForModule($query, string $module) { return $query->where('module', $module); }
}
