<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficialDocument extends Model
{
    use \App\Traits\IsDemoRecord;
    use HasFactory;
    use HasUuids;

    protected $table = 'official_documents';

    protected $fillable = [
        'document_type',
        'document_number',
        'verification_code',
        'patient_id',
        'health_id',
        'facility_id',
        'organization_id',
        'issuer_user_id',
        'template_id',
        'template_version',
        'status',
        'version',
        'sensitivity_level',
        'title',
        'payload_json',
        'standard_mapping_json',
        'pdf_path',
        'document_hash',
        'payload_hash',
        'issued_at',
        'released_at',
        'expires_at',
        'revoked_at',
        'revocation_reason',
        'is_demo',
        'demo_seed_key',
        'demo_reset_group',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'standard_mapping_json' => 'array',
        'issued_at' => 'datetime',
        'released_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'is_demo' => 'boolean',
    ];

    public function template()
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function signatures()
    {
        return $this->hasMany(DocumentSignature::class, 'official_document_id');
    }

    public function versions()
    {
        return $this->hasMany(DocumentVersion::class, 'official_document_id');
    }

    public function verificationTokens()
    {
        return $this->hasMany(DocumentVerificationToken::class, 'official_document_id');
    }

    public function codeMappings()
    {
        return $this->hasMany(DocumentCodeMapping::class, 'official_document_id');
    }

    public function specimenEvents()
    {
        return $this->hasMany(DocumentSpecimenEvent::class, 'official_document_id');
    }
}
