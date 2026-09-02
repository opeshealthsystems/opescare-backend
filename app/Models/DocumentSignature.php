<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DocumentSignature extends Model
{
    use \App\Traits\IsDemoRecord;
    use HasUuids;

    protected $table = 'document_signatures';

    protected $fillable = [
        'official_document_id',
        'signer_user_id',
        'signer_name',
        'signer_role',
        'signer_license_number',
        'signer_license_body',
        'signature_type',
        'signed_at',
        'signature_metadata_json',
        'is_demo',
        'demo_seed_key',
        'demo_reset_group',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'signature_metadata_json' => 'array',
        'is_demo' => 'boolean',
    ];

    public function document()
    {
        return $this->belongsTo(OfficialDocument::class, 'official_document_id');
    }
}
