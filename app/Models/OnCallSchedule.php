<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OnCallSchedule extends Model
{
    use HasUuids, HasFactory, SoftDeletes;
    use \App\Traits\HasFacilityScope;

    protected $fillable = [
        'provider_id', 'facility_id', 'specialty',
        'on_call_date', 'start_time', 'end_time',
        'backup_provider_id', 'is_confirmed', 'notes',
    ];

    protected $casts = [
        'on_call_date' => 'date',
        'is_confirmed' => 'boolean',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function backupProvider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'backup_provider_id');
    }
}
