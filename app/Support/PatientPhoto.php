<?php

namespace App\Support;

use App\Models\Patient;
use Illuminate\Support\Facades\URL;

/**
 * The one place that knows how a patient's photo is addressed.
 *
 * A photo is patient data. It is stored on the private disk and handed out
 * only through a signed link that expires, so a leaked URL stops working and
 * a shared proxy has nothing durable to cache. Every response that carries a
 * patient (`/mobile/me`, the Health ID card) asks here for the link.
 */
final class PatientPhoto
{
    /** Where uploads live on the `local` disk. */
    public const DIRECTORY = 'patient-photos';

    /** How long a link handed to the app stays valid. */
    public const URL_TTL_MINUTES = 60;

    /** Every stored photo is re-encoded to this square edge. */
    public const EDGE = 512;

    /** A short-lived signed link to the patient's photo, or null when there is none. */
    public static function urlFor(Patient $patient): ?string
    {
        if (! $patient->photo_path) {
            return null;
        }

        return URL::temporarySignedRoute(
            'mobile.patient.photo',
            now()->addMinutes(self::URL_TTL_MINUTES),
            // The version segment changes with every upload, so an app that
            // cached the old picture under the old link fetches the new one.
            ['patient' => $patient->id, 'v' => optional($patient->photo_updated_at)->timestamp ?? 0],
        );
    }
}
