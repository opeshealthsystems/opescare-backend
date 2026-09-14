<?php

namespace App\Mail\Support;

use App\Http\Middleware\SetLocale;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;

/**
 * Which language an email should be written in.
 *
 * The locale of the process that SENDS a mail is the wrong answer more often
 * than not: a break-glass alert is raised from a clinician's English session,
 * a Health ID expiry warning from a scheduled command with no session at all,
 * and both land in the inbox of a patient who set their app to French. The
 * only preference this platform actually stores for a patient is
 * mobile_app_settings.preferred_language (the same column PatientPush reads),
 * so that is what wins here.
 *
 * Order of precedence:
 *   1. the recipient's stored preference (Patient, or a User linked to one);
 *   2. a HasLocalePreference implementation, should a notifiable ever add one;
 *   3. the locale explicitly set on the notification / mailable (->locale());
 *   4. the current application locale.
 *
 * Every lookup is wrapped: a failed preference read must never decide whether
 * the message goes out.
 */
final class RecipientLocale
{
    /**
     * @param  object|null  $notifiable  a User, a Patient, an AnonymousNotifiable, or anything ->notify()-able
     * @param  string|null  $explicit    the locale set on the notification / mailable, if any
     */
    public static function for(?object $notifiable, ?string $explicit = null): string
    {
        $stored = $notifiable === null ? null : self::storedPreference($notifiable);

        return self::normalise($stored)
            ?? self::normalise($notifiable instanceof HasLocalePreference ? $notifiable->preferredLocale() : null)
            ?? self::normalise($explicit)
            ?? self::normalise(app()->getLocale())
            ?? 'en';
    }

    /**
     * For Mail::to($address)->send(...) where there is no notifiable at all:
     * the address is matched against the patient register, then the stored
     * preference is read the same way.
     */
    public static function forEmail(?string $email, ?string $explicit = null): string
    {
        $stored = null;

        if (is_string($email) && $email !== '') {
            try {
                $patientId = DB::table('patients')->where('email', $email)->value('id');
                $stored    = $patientId ? self::preferenceForPatient((string) $patientId) : null;
            } catch (\Throwable) {
                $stored = null;
            }
        }

        return self::normalise($stored)
            ?? self::normalise($explicit)
            ?? self::normalise(app()->getLocale())
            ?? 'en';
    }

    private static function storedPreference(object $notifiable): ?string
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            return null;
        }

        try {
            // Anything carrying preferred_language directly (MobileAppSetting,
            // PatientIdentityProfile, a future column on User).
            if (isset($notifiable->preferred_language) && is_string($notifiable->preferred_language)) {
                return $notifiable->preferred_language;
            }

            $patientId = null;

            if ($notifiable instanceof \App\Models\Patient) {
                $patientId = $notifiable->getKey();
            } elseif ($notifiable instanceof \App\Models\User) {
                $patientId = $notifiable->patient_id;
            }

            return $patientId ? self::preferenceForPatient((string) $patientId) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function preferenceForPatient(string $patientId): ?string
    {
        $value = DB::table('mobile_app_settings')
            ->where('patient_id', $patientId)
            ->value('preferred_language');

        return is_string($value) ? $value : null;
    }

    private static function normalise(?string $locale): ?string
    {
        if (! is_string($locale) || trim($locale) === '') {
            return null;
        }

        $short = strtolower(substr(trim($locale), 0, 2));

        return in_array($short, SetLocale::SUPPORTED, true) ? $short : null;
    }
}
