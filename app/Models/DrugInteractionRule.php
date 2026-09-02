<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DrugInteractionRule extends Model
{
    use HasUuids;

    protected $fillable = [
        'drug_a_code', 'drug_a_name', 'drug_b_code', 'drug_b_name',
        'severity', 'interaction_description', 'clinical_effect', 'management', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Find interactions involving a given drug code (bidirectional).
     */
    public static function findForDrug(string $drugCode): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)
            ->where(function ($q) use ($drugCode) {
                $q->where('drug_a_code', $drugCode)
                  ->orWhere('drug_b_code', $drugCode);
            })->get();
    }

    /**
     * Check if two specific drugs interact.
     */
    public static function checkPair(string $codeA, string $codeB): ?self
    {
        return static::where('is_active', true)
            ->where(function ($q) use ($codeA, $codeB) {
                $q->where(function ($q2) use ($codeA, $codeB) {
                    $q2->where('drug_a_code', $codeA)->where('drug_b_code', $codeB);
                })->orWhere(function ($q2) use ($codeA, $codeB) {
                    $q2->where('drug_a_code', $codeB)->where('drug_b_code', $codeA);
                });
            })->first();
    }
}
