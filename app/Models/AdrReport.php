<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdrReport extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'adr_reports';

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'reaction_onset_date' => 'date',
        'drug_stopped'        => 'boolean',
        'rechallenged'        => 'boolean',
        'reaction_resolved'   => 'boolean',
    ];
}
