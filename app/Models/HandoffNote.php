<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HandoffNote extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'visit_id', 'patient_id', 'from_provider_id', 'to_provider_id', 'from_provider', 'to_provider',
        'facility_id', 'summary', 'content', 'active_problems', 'pending_orders',
        'patient_status', 'flag_for_follow_up', 'handed_off_at',
        'priority', 'acknowledged', 'acknowledged_at',
    ];

    protected $casts = [
        'active_problems'    => 'array',
        'pending_orders'     => 'array',
        'flag_for_follow_up' => 'boolean',
        'handed_off_at'      => 'datetime',
        'acknowledged'       => 'boolean',
        'acknowledged_at'    => 'datetime',
    ];

    public function fromProvider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_provider_id');
    }

    public function toProvider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_provider_id');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }
}
