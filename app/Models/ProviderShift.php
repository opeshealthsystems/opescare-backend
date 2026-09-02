<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProviderShift extends Model
{
    use HasUuids, HasFactory, SoftDeletes;
    use \App\Traits\HasFacilityScope;

    protected $fillable = [
        'provider_id', 'facility_id', 'shift_date', 'start_time', 'end_time',
        'shift_type', 'is_confirmed', 'swap_requested_with', 'notes',
        'is_on_call', 'department',
    ];

    protected $casts = [
        'shift_date'   => 'date',
        'is_confirmed' => 'boolean',
        'is_on_call'   => 'boolean',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function swapTarget(): BelongsTo
    {
        return $this->belongsTo(User::class, 'swap_requested_with');
    }
}
