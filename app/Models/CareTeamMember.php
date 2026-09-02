<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareTeamMember extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'patient_id', 'visit_id', 'provider_id', 'facility_id',
        'role', 'is_primary', 'is_active', 'joined_at', 'left_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active'  => 'boolean',
        'joined_at'  => 'datetime',
        'left_at'    => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }
}
