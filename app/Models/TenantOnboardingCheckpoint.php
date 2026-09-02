<?php
namespace App\Models;

use App\Traits\HasFacilityScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TenantOnboardingCheckpoint extends Model
{
    use HasFacilityScope, HasUuids;

    protected $fillable = [
        'facility_id',
        'step_key',
        'step_label',
        'step_order',
        'completed',
        'required',
        'completed_at',
    ];

    protected $casts = [
        'completed'    => 'boolean',
        'required'     => 'boolean',
        'completed_at' => 'datetime',
        'step_order'   => 'integer',
    ];
}
