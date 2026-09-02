<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DocumentVersion extends Model
{
    use \App\Traits\IsDemoRecord;
    use HasUuids;

    protected $table = 'document_versions';

    protected $fillable = [
        'official_document_id',
        'version',
        'payload_json',
        'standard_mapping_json',
        'pdf_path',
        'document_hash',
        'payload_hash',
        'change_reason',
        'created_by',
        'is_demo',
        'demo_seed_key',
        'demo_reset_group',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'standard_mapping_json' => 'array',
        'is_demo' => 'boolean',
    ];

    public function document()
    {
        return $this->belongsTo(OfficialDocument::class, 'official_document_id');
    }
}
