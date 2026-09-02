<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ClaimDocument extends Model
{
    use HasUuids;

    protected $fillable = [
        'insurance_claim_id',
        'document_type',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'uploaded_by',
    ];

    public function claim()
    {
        return $this->belongsTo(InsuranceClaim::class, 'insurance_claim_id');
    }
}
