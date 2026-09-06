<?php

namespace App\Services\Patient\Passport;

use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Lang;

/**
 * The small formatting decisions every passport page makes the same way.
 *
 * These used to live as private methods on HealthPassportBuilder. They are a
 * trait now because each page is built by its own class, and a status must not
 * read one way on page 7 and another way on page 15 — a clinician comparing two
 * pages of the same document is entitled to assume the words mean the same
 * thing.
 */
trait FormatsRecords
{
    /**
     * A status the reader can understand, and never a raw translation key.
     *
     * Statuses arrive from several tables and some carry spellings this
     * codebase has not met. A missing translation therefore falls back to the
     * recorded word itself, tidied — "in_progress" reads as "In progress", not
     * as "passport.status_in_progress".
     */
    protected function statusLabel(?string $status): string
    {
        $raw = trim((string) $status);

        if ($raw === '') {
            return __('passport.not_recorded');
        }

        $key = 'passport.status_' . strtolower($raw);

        return Lang::has($key)
            ? __($key)
            : ucfirst(str_replace('_', ' ', strtolower($raw)));
    }

    protected function sexLabel(?string $sex): string
    {
        $key = 'passport.sex_' . strtolower(trim((string) $sex));

        return Lang::has($key) ? __($key) : __('passport.not_recorded');
    }

    protected function displayName(Patient $patient): string
    {
        return trim(((string) $patient->first_name) . ' ' . ((string) $patient->last_name))
            ?: (string) $patient->health_id;
    }

    protected function credentialStatus(Patient $patient): string
    {
        // identity_status is a backed enum on this model (App\Enums\IdentityStatus)
        // and a plain string on older rows; read both without assuming either.
        $raw    = $patient->identity_status;
        $status = strtolower((string) ($raw instanceof \BackedEnum ? $raw->value : ($raw ?? '')));

        return match ($status) {
            'verified', 'active' => 'ACTIVE',
            'provisional'        => 'PROVISIONAL',
            'suspended'          => 'SUSPENDED',
            'revoked', 'merged'  => 'REVOKED',
            default              => 'PROVISIONAL',
        };
    }

    /** A date the way the printed passport sets it, or the honest blank. */
    protected function date($value, string $format = 'd M Y'): string
    {
        if (! $value) {
            return __('passport.not_recorded');
        }

        try {
            return Carbon::parse($value)->format($format);
        } catch (\Throwable) {
            return __('passport.not_recorded');
        }
    }

    /** The year alone, for the design's compact history tables. */
    protected function year($value): string
    {
        return $this->date($value, 'Y');
    }
}
