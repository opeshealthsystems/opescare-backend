<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpecialtyDiagnosticReport extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'specialty_diagnostic_reports';

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'study_date'   => 'date',
        'measurements' => 'array',
    ];
}
