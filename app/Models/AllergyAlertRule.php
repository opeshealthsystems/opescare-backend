<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AllergyAlertRule extends Model
{
    use HasUuids;

    protected $fillable = [
        'drug_code', 'drug_name', 'allergen_code', 'allergen_name',
        'cross_reactivity_group', 'severity', 'alert_message', 'is_active',
        'is_hard_stop', 'facility_id',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'is_hard_stop' => 'boolean',
    ];

    /**
     * Find allergy rules for a given drug code.
     */
    public static function findForDrug(string $drugCode): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)->where('drug_code', $drugCode)->get();
    }
}
