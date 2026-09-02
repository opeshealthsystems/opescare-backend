<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DocumentShareLink extends Model
{
    use \App\Traits\IsDemoRecord;
    use HasUuids;

    protected $table = 'document_share_links';

    protected $fillable = [
        'official_document_id',
        'share_token_hash',
        'created_by',
        'recipient_contact',
        'expires_at',
        'revoked_at',
        'access_count',
        'max_access_count',
        'is_demo',
        'demo_seed_key',
        'demo_reset_group',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'access_count' => 'integer',
        'is_demo' => 'boolean',
    ];

    public function document()
    {
        return $this->belongsTo(OfficialDocument::class, 'official_document_id');
    }
}
