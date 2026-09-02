<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MobileFacilityContext extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'facility_id',
        'provider_mobile_session_id',
        'is_current',
        'switched_at',
    ];

    protected $casts = [
        'is_current'  => 'boolean',
        'switched_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * Switch the provider to a new facility, deactivating the previous context.
     */
    public static function switchTo(string $userId, string $facilityId, ?string $sessionId = null): self
    {
        // Clear existing current context
        self::where('user_id', $userId)->where('is_current', true)->update(['is_current' => false]);

        return self::create([
            'user_id'                       => $userId,
            'facility_id'                   => $facilityId,
            'provider_mobile_session_id'    => $sessionId,
            'is_current'                    => true,
            'switched_at'                   => now(),
        ]);
    }

    /**
     * Get the current active facility context for a provider.
     */
    public static function currentFor(string $userId): ?self
    {
        return self::where('user_id', $userId)
            ->where('is_current', true)
            ->with('facility:id,name')
            ->latest('switched_at')
            ->first();
    }
}
