<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single "Refer & Earn" invitation/conversion.
 *
 * GROWTH referral (not the clinical ReferralCase). Created when a new patient
 * signs up using a referrer's shareable code; both parties earn Premium days.
 */
class ReferralInvite extends Model
{
    use HasUuids;

    protected $fillable = [
        'referrer_patient_id',
        'code',
        'referee_email',
        'referee_patient_id',
        'status',
        'referrer_reward_days',
        'referee_reward_days',
        'rewarded_at',
    ];

    protected $casts = [
        'referrer_reward_days' => 'integer',
        'referee_reward_days'  => 'integer',
        'rewarded_at'          => 'datetime',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'referrer_patient_id');
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'referee_patient_id');
    }
}
