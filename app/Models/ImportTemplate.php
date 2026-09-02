<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * ImportTemplate — Module 12 (Data Import, Migration & Onboarding)
 *
 * Defines the structure of a CSV/Excel import template for a given entity type.
 * Includes required/optional columns, per-column validation rules, and
 * a reference to the example file.
 *
 * Rule: "Do not import data without templates, validation rules,
 * duplicate logic, and rollback."
 */
class ImportTemplate extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'entity_type',          // patient|staff|inventory|appointments|clinical
        'version',
        'required_columns',
        'optional_columns',
        'column_validations',   // JSON: per-column validation rules
        'example_file_path',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'required_columns'   => 'array',
        'optional_columns'   => 'array',
        'column_validations' => 'array',
        'is_active'          => 'boolean',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEntityType($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function allColumns(): array
    {
        return array_merge(
            $this->required_columns ?? [],
            $this->optional_columns ?? []
        );
    }

    public function validationRuleFor(string $column): ?array
    {
        $rules = $this->column_validations ?? [];
        return $rules[$column] ?? null;
    }

    public function hasExampleFile(): bool
    {
        return $this->example_file_path !== null;
    }
}
