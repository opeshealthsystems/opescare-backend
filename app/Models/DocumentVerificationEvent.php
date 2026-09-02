<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DocumentVerificationEvent extends Model
{
    use \App\Traits\IsDemoRecord;
    use HasUuids;

    protected $table = 'document_verification_events';

    protected $fillable = [
        'official_document_id',
        'verification_code',
        'token_hash',
        'result',
        'ip_address',
        'user_agent',
        'verified_by_user_id',
        'public_verification',
        'is_demo',
        'demo_seed_key',
        'demo_reset_group',
    ];

    protected $casts = [
        'public_verification' => 'boolean',
        'is_demo' => 'boolean',
    ];

    public function document()
    {
        return $this->belongsTo(OfficialDocument::class, 'official_document_id');
    }
}
