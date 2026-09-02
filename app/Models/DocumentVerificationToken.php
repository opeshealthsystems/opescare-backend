<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DocumentVerificationToken extends Model
{
    use \App\Traits\IsDemoRecord;
    use HasUuids;

    protected $table = 'document_verification_tokens';

    protected $fillable = [
        'official_document_id',
        'token_hash',
        'status',
        'expires_at',
        'revoked_at',
        'last_used_at',
        'is_demo',
        'demo_seed_key',
        'demo_reset_group',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_used_at' => 'datetime',
        'is_demo' => 'boolean',
    ];

    public function document()
    {
        return $this->belongsTo(OfficialDocument::class, 'official_document_id');
    }
}
