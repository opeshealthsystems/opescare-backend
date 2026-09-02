<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyLink extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'guardian_user_id',
        'dependent_patient_id',
        'relationship',
        'access_level',
        'status',
        'created_by',
        'invite_token',
        'invite_expires_at',
        'notification_prefs',
        'age_transition_notified_at',
        'age_transition_expires_at',
    ];

    protected $casts = [
        'notification_prefs'         => 'array',
        'invite_expires_at'          => 'datetime',
        'age_transition_notified_at' => 'datetime',
        'age_transition_expires_at'  => 'datetime',
    ];

    public function guardianUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_user_id');
    }

    public function dependentPatient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Patient::class, 'dependent_patient_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopePendingInvite(Builder $query): Builder
    {
        return $query->where('status', 'pending_invite');
    }

    public function isExpiredByAge(): bool
    {
        return $this->age_transition_expires_at !== null
            && $this->age_transition_expires_at->isPast();
    }

    public function notificationPrefFor(string $eventKey, string $channel): bool
    {
        $defaults = [
            'lab_result'      => ['portal' => true, 'email' => true,  'sms' => false],
            'appointment'     => ['portal' => true, 'email' => true,  'sms' => false],
            'consent_request' => ['portal' => true, 'email' => true,  'sms' => true],
            'age_transition'  => ['portal' => true, 'email' => true,  'sms' => true],
        ];
        $prefs = $this->notification_prefs[$eventKey] ?? $defaults[$eventKey] ?? [];
        return (bool) ($prefs[$channel] ?? ($defaults[$eventKey][$channel] ?? false));
    }
}
