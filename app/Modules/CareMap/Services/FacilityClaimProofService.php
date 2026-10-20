<?php

namespace App\Modules\CareMap\Services;

use App\Enums\FacilityClaimStatus;
use App\Models\CareFacility;
use App\Models\FacilityClaim;
use App\Modules\Notifications\Services\SmsNotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Ownership proof for a directory claim (readiness OB-1).
 *
 * Two pieces of evidence, both decided by the owner on 2026-10-20:
 *
 *  1. A one-time code sent by SMS to the phone number the LISTING already
 *     carries (`care_facilities.phone_primary`). Never a number the claimant
 *     typed: proving you can answer the phone the public directory already
 *     shows is the point. The code is stored as an HMAC with an expiry, a
 *     per-code attempt limit and a per-claim / per-user send limit, and it
 *     is never returned to the claimant on any path.
 *     When it cannot be delivered (no SMS provider, or a listing with no
 *     real phone) the claim moves to review flagged in `sms_status`, so the
 *     administrator calls the facility instead.
 *
 *  2. A licence / authorisation document (PDF, JPEG, PNG) on the private
 *     disk, checked by magic bytes as well as by the upload validator, and
 *     handed only to platform admins through a short-lived signed link.
 */
class FacilityClaimProofService
{
    public const CODE_TTL_MINUTES = 15;
    public const MAX_ATTEMPTS     = 5;
    /** Sends per claim (first send included). */
    public const MAX_SENDS        = 3;
    /** Seconds between two sends for the same claim. */
    public const RESEND_COOLDOWN  = 60;
    /** Sends per claimant per hour, across every claim. */
    public const USER_SENDS_PER_HOUR = 6;

    public const MAX_DOCUMENT_KB  = 5120;
    public const DOCUMENT_DIR     = 'facility-claims';
    public const DOCUMENT_URL_TTL_MINUTES = 15;

    /** sms_status values. */
    public const SMS_SENT            = 'sent';
    public const SMS_NOT_DELIVERED   = 'not_delivered';
    public const SMS_NO_LISTING_PHONE = 'no_listing_phone';

    public function __construct(private readonly ClaimSmsGateway $gateway)
    {
    }

    /** Upload rule for the document field (shared by web and API). */
    public static function documentRules(): array
    {
        return ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:' . self::MAX_DOCUMENT_KB];
    }

    /**
     * Store the document privately and stamp it on the claim.
     *
     * @throws \Exception DOCUMENT_INVALID
     */
    public function storeDocument(FacilityClaim $claim, UploadedFile $file): void
    {
        $mime = self::sniffMime($file);
        if ($mime === null) {
            throw new \Exception('DOCUMENT_INVALID');
        }

        $ext  = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'][$mime];
        $disk = Storage::disk(self::disk());
        $path = self::DOCUMENT_DIR . '/' . $claim->id . '/' . Str::random(40) . '.' . $ext;

        $disk->put($path, (string) file_get_contents($file->getRealPath()));

        $claim->forceFill([
            'document_path'          => $path,
            'document_mime'          => $mime,
            'document_original_name' => mb_substr(basename((string) $file->getClientOriginalName()), 0, 255),
            'document_size'          => (int) $file->getSize(),
        ])->save();
    }

    public static function disk(): string
    {
        return (string) config('filesystems.private_disk', 'local');
    }

    /**
     * The real type from the first bytes — the extension and the browser's
     * Content-Type are both claimant-controlled. Anything that is not a PDF,
     * JPEG or PNG by its own header is refused.
     */
    public static function sniffMime(UploadedFile $file): ?string
    {
        $handle = @fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            return null;
        }
        $head = (string) fread($handle, 8);
        fclose($handle);

        return match (true) {
            str_starts_with($head, '%PDF-')            => 'application/pdf',
            str_starts_with($head, "\xFF\xD8\xFF")     => 'image/jpeg',
            str_starts_with($head, "\x89PNG\r\n\x1A\n") => 'image/png',
            default                                    => null,
        };
    }

    /**
     * Issue (or re-issue) the code and send it to the listing's phone.
     * Moves the claim to pending_review when it cannot be delivered.
     *
     * @throws \Exception CLAIM_NOT_AWAITING_CODE | CODE_SEND_LIMIT | CODE_RESEND_TOO_SOON
     */
    public function sendCode(FacilityClaim $claim): FacilityClaim
    {
        if ($claim->claim_status !== FacilityClaimStatus::PendingVerification) {
            throw new \Exception('CLAIM_NOT_AWAITING_CODE');
        }

        if ($claim->verification_send_count >= self::MAX_SENDS) {
            throw new \Exception('CODE_SEND_LIMIT');
        }

        if ($claim->verification_sent_at !== null
            && $claim->verification_sent_at->gt(now()->subSeconds(self::RESEND_COOLDOWN))) {
            throw new \Exception('CODE_RESEND_TOO_SOON');
        }

        $userKey = 'facility-claim-sms:' . $claim->claimant_user_id;
        if (RateLimiter::tooManyAttempts($userKey, self::USER_SENDS_PER_HOUR)) {
            throw new \Exception('CODE_SEND_LIMIT');
        }

        $listing = CareFacility::find($claim->care_facility_id);
        $phone   = $listing?->dialablePhone();

        if ($phone === null) {
            return $this->toReview($claim, self::SMS_NO_LISTING_PHONE, null);
        }

        RateLimiter::hit($userKey, 3600);

        $code = (string) random_int(100000, 999999);

        $claim->forceFill([
            'verification_code_hash'       => self::hashCode($claim, $code),
            'verification_code_expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
            'verification_attempts'        => 0,
            'verification_send_count'      => $claim->verification_send_count + 1,
            'verification_sent_at'         => now(),
            'sms_phone_masked'             => SmsNotificationService::maskPhone($phone),
        ])->save();

        $body = __('caremap_claim.sms_code_body', [
            'code'     => $code,
            'facility' => Str::limit((string) $listing->facility_name, 40, ''),
            'minutes'  => self::CODE_TTL_MINUTES,
        ]);

        if (! $this->gateway->deliver($phone, $body)) {
            Log::warning('facility_claim_sms_not_delivered', [
                'claim_id' => $claim->id,
                'to'       => SmsNotificationService::maskPhone($phone),
            ]);

            return $this->toReview($claim, self::SMS_NOT_DELIVERED, $phone);
        }

        $claim->forceFill(['sms_status' => self::SMS_SENT])->save();

        return $claim;
    }

    /**
     * Check a code typed by the claimant.
     *
     * @throws \Exception CLAIM_NOT_AWAITING_CODE | CODE_EXPIRED | CODE_TOO_MANY_ATTEMPTS | CODE_INVALID
     */
    public function verifyCode(FacilityClaim $claim, string $code): FacilityClaim
    {
        if ($claim->claim_status !== FacilityClaimStatus::PendingVerification) {
            throw new \Exception('CLAIM_NOT_AWAITING_CODE');
        }

        if ($claim->verification_code_hash === null
            || $claim->verification_code_expires_at === null
            || $claim->verification_code_expires_at->isPast()) {
            throw new \Exception('CODE_EXPIRED');
        }

        if ($claim->verification_attempts >= self::MAX_ATTEMPTS) {
            throw new \Exception('CODE_TOO_MANY_ATTEMPTS');
        }

        $code = preg_replace('/\D+/', '', $code) ?? '';

        if (! hash_equals($claim->verification_code_hash, self::hashCode($claim, $code))) {
            $attempts = $claim->verification_attempts + 1;
            $claim->forceFill(array_merge(
                ['verification_attempts' => $attempts],
                // The last allowed attempt burns the code outright.
                $attempts >= self::MAX_ATTEMPTS ? ['verification_code_hash' => null] : []
            ))->save();

            throw new \Exception($attempts >= self::MAX_ATTEMPTS ? 'CODE_TOO_MANY_ATTEMPTS' : 'CODE_INVALID');
        }

        $claim->forceFill([
            'claim_status'                 => FacilityClaimStatus::PendingReview->value,
            'phone_verified_at'            => now(),
            'verification_code_hash'       => null,
            'verification_code_expires_at' => null,
        ])->save();

        return $claim;
    }

    private function toReview(FacilityClaim $claim, string $smsStatus, ?string $phone): FacilityClaim
    {
        $claim->forceFill([
            'claim_status'           => FacilityClaimStatus::PendingReview->value,
            'sms_status'             => $smsStatus,
            'sms_phone_masked'       => $phone !== null ? SmsNotificationService::maskPhone($phone) : null,
            // A code nobody received must not stay redeemable.
            'verification_code_hash' => null,
        ])->save();

        return $claim;
    }

    private static function hashCode(FacilityClaim $claim, string $code): string
    {
        return hash_hmac('sha256', $claim->id . '|' . $code, (string) config('app.key'));
    }
}
