<?php

namespace App\Models;

use App\Enums\PatientReferralRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A patient's request FOR a referral — see the table migration for why it is
 * not a `referral_cases` row.
 *
 * `status` and `referral_case_id` are not fillable. A patient's write path can
 * file a request and withdraw its own; it cannot mark one accepted, and it
 * cannot attach a referral case. Only a clinician-side path (which does not
 * exist in this feature) may do that, and the database CHECK constraint refuses
 * a case link on any status but `accepted` regardless.
 */
class PatientReferralRequest extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'reference',
        'patient_id',
        'requested_specialty',
        'preferred_care_facility_id',
        'reason',
        'symptom_duration',
        'reported_urgency',
        'patient_note',
        'contact_phone',
    ];

    protected $casts = [
        'status'       => PatientReferralRequestStatus::class,
        'reviewed_at'  => 'datetime',
        'withdrawn_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /** The public directory entry the patient asked to be sent to, if any. */
    public function preferredCareFacility()
    {
        return $this->belongsTo(CareFacility::class, 'preferred_care_facility_id');
    }

    /**
     * The referral a clinician issued off the back of this request — null on
     * every request that has not been accepted, which is the point.
     */
    public function referralCase()
    {
        return $this->belongsTo(ReferralCase::class, 'referral_case_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    public function scopeForPatient(Builder $query, string $patientId): Builder
    {
        return $query->where('patient_id', $patientId);
    }

    /** Still waiting on a clinician. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [
            PatientReferralRequestStatus::Submitted->value,
            PatientReferralRequestStatus::UnderReview->value,
        ]);
    }

    /**
     * The patient takes their own request back. Never a delete: a facility may
     * already have read it, and erasing that is not the patient's to do.
     */
    public function withdraw(?string $reason = null): bool
    {
        if (! $this->status->isWithdrawable()) {
            return false;
        }

        $this->status = PatientReferralRequestStatus::Withdrawn;
        $this->withdrawn_at = now();
        $this->withdrawal_reason = $reason;

        return $this->save();
    }

    /**
     * A human-quotable reference, in the house format (OC-BL-… for blood,
     * OC-RR-… here). Collision-checked rather than assumed unique.
     */
    public static function newReference(): string
    {
        do {
            $reference = 'OC-RR-' . strtoupper(Str::random(8));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }
}
