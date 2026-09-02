<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DrugFormulary extends Model {
    use HasUuids, HasFactory, SoftDeletes;

    protected $fillable = [
        'facility_id','generic_name','brand_names','drug_code','drug_class',
        'form','strength','unit','is_available','is_controlled',
        'requires_prior_auth','restricted_to','notes','created_by',
    ];

    protected $casts = [
        'brand_names'         => 'array',
        'restricted_to'       => 'array',
        'is_available'        => 'boolean',
        'is_controlled'       => 'boolean',
        'requires_prior_auth' => 'boolean',
    ];

    public function facility(): BelongsTo { return $this->belongsTo(Facility::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
