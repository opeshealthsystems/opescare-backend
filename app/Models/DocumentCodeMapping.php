<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DocumentCodeMapping extends Model
{
    use \App\Traits\IsDemoRecord;
    use HasUuids;

    protected $table = 'document_code_mappings';

    protected $fillable = [
        'official_document_id',
        'resource_type',
        'local_code',
        'standard_code',
        'code_system',
        'mapping_status',
        'mapped_by',
        'mapped_at',
        'is_demo',
        'demo_seed_key',
        'demo_reset_group',
    ];

    protected $casts = [
        'mapped_at' => 'datetime',
        'is_demo' => 'boolean',
    ];

    public function document()
    {
        return $this->belongsTo(OfficialDocument::class, 'official_document_id');
    }
}
