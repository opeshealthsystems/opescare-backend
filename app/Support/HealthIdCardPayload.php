<?php

namespace App\Support;

use App\Models\Patient;

/**
 * The Health ID card as the mobile app renders it.
 *
 * One shape, two readers: `GET /mobile/health-id-card` for the patient's own
 * card, and the family member detail for a dependent's. Field names and
 * semantics are the app's `HealthIdCard` type — change them here and both
 * screens change together, or not at all.
 *
 *   health_id, display_name, sex, dob (YYYY-MM-DD), blood_type, photo_url
 *   (signed, short-lived, or null), qr_payload (base64 JSON, or null when the
 *   reader may not present the card), status (identity status value).
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

    /**
     * The static card QR: the Health ID plus just enough to eyeball a match,
     * stamped with when it was minted. The temporary-access QR is a different
     * thing (MobileQrController) and carries a server-side token instead.
     */
    public static function qrPayload(Patient $patient): string
    {
        return base64_encode(json_encode([
            'hid' => $patient->health_id,
            'fn'  => $patient->first_name,
            'ln'  => substr((string) $patient->last_name, 0, 1),
            'dob' => $patient->date_of_birth?->toDateString(),
            'sex' => $patient->sex,
            'ts'  => now()->toIso8601String(),
        ]));
    }
}
