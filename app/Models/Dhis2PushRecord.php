<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** One aggregate value sent to DHIS2, unique per (facility, period, indicator). */
class Dhis2PushRecord extends Model
{
    use HasUuids;

    public const STATUS_IMPORTED = 'imported';
    public const STATUS_FAILED = 'failed';

    protected $table = 'dhis2_push_records';

    protected $fillable = [
        'report_id', 'facility_id', 'period', 'indicator_code', 'org_unit_id',
        'data_element_id', 'category_option_combo_id', 'value', 'status',
        'import_summary', 'attempts', 'pushed_by', 'pushed_at',
    ];

    protected $casts = [
        'value' => 'integer',
        'attempts' => 'integer',
        'import_summary' => 'array',
        'pushed_at' => 'datetime',
    ];
}
