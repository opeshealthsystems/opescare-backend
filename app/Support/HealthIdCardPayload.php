<?php

namespace App\Support;

use App\Models\Patient;
use App\Services\Identity\QrTokenService;
use Illuminate\Support\Facades\Cache;

/**
 * The Health ID card as the mobile app renders it.
 *
 * One shape, two readers: `GET /mobile/health-id-card` for the patient's own
 * card, and the family member detail for a dependent's. Field names and
 * semantics are the app's `HealthIdCard` type — change them here and both
 * screens change together, or not at all.
 *
 *   health_id, display_name, sex, dob (YYYY-MM-DD), blood_type, photo_url
 *   (signed, short-lived, or null), qr_payload (verification URL, or null when
 *   the reader may not present the card), status (identity status value).
 */
final class HealthIdCardPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function for(Patient $patient, bool $withQr = true): array
    {
        return [
            'health_id'    => $patient->health_id,
            'display_name' => $patient->first_name . ' ' . $patient->last_name,
            'sex'          => $patient->sex,
            'dob'          => $patient->date_of_birth?->toDateString(),
            'blood_type'   => $patient->blood_group,
            'photo_url'    => PatientPhoto::urlFor($patient),
            'qr_payload'   => $withQr ? self::qrPayload($patient) : null,
            'status'       => $patient->identity_status ?? 'active',
        ];
    }

    /** How long one card QR stays valid. Re-served from cache within that window. */
    private const CARD_QR_MINUTES = 1440;

    /**
     * The card QR: the OpesCare Secure verification URL (/verify/qr/qrx_…),
     * minted by the same QrTokenService the web verifier and the clinician
     * scanner read.
     *
     * It used to be base64 JSON of the Health ID, first name, DOB and sex. A
     * phone camera showed that as raw characters, the clinician scanner
     * (MobileProviderScanController::extractToken) rejected it, and base64 is
     * not encryption — anyone could decode a patient's name and birth date
     * from a photo of the card. Now the code is only a link; what the verifier
     * shows is decided server-side, per scan.
     *
     * One token per patient per day: the raw token (a string, never an object
     * — see the never-cache-objects rule) is cached so every card fetch does
     * not mint a new database row. A cache miss mints a fresh one.
     */
    public static function qrPayload(Patient $patient): string
    {
        $key = 'health_id_card_qr:' . $patient->id;

        $raw = Cache::get($key);
        if (!is_string($raw) || app(QrTokenService::class)->verifyToken($raw) === null) {
            $raw = app(QrTokenService::class)
                ->generateToken($patient->id, 'static_identity_qr', self::CARD_QR_MINUTES)['raw_token'];
            Cache::put($key, $raw, now()->addMinutes(self::CARD_QR_MINUTES - 5));
        }

        return route('verify.qr', ['token' => $raw]);
    }
}
