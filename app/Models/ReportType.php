<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ReportType extends Model
{
    use HasUuids;

    protected $table = 'public_health_report_types';

    protected $fillable = [
        'code',
        'name',
        'description',
        'sensitivity_level',
        'default_review_required',
        'is_active'
    ];

    protected $casts = [
        'default_review_required' => 'boolean',
        'is_active' => 'boolean'
    ];

    public function rules()
    {
        return $this->hasMany(ReportingRule::class, 'report_type_id');
    }
}
