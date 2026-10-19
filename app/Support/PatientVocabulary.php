<?php

namespace App\Support;

use App\Http\Controllers\Api\Mobile\MobileConsentRequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;

/**
 * PatientVocabulary — the ONE place a wire code becomes words a patient reads.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHY THIS EXISTS
 * ─────────────────────────────────────────────────────────────────────────
 *
 * A clinician asking for access picks from a labelled list: "Record summary",
 * "Allergies", "24 hours" — served by
 * MobileConsentRequestController::options() out of lang/{en,fr}/consent_requests.php.
 * The patient, deciding whether to open their medical record to that facility,
 * was shown the OTHER side of the same row:
 *
 *     WHAT THEY WOULD SEE:  Patient.summary / Allergies.read / 1440 min
 *
 * — the wire values, title-cased by the client, identical in French because
 * nothing was ever translated: the labels simply were not on the wire. The
 * client carried its own scope table (`privacy.scope.read_records`,
 * `privacy.scope.outpatient`, …) built from a vocabulary this API has never
 * emitted, so every real code missed it and fell through to the raw string.
 *
 * That is the drift a second copy always produces, so there is not going to be
 * a second copy. The labels are resolved HERE, from the same lang files and
 * the same allow-lists the clinician's picker uses, and travel to the client
 * already in the patient's language. The client renders what it is given.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * AN UNKNOWN CODE IS NEVER GIVEN A FRIENDLY NAME
 * ─────────────────────────────────────────────────────────────────────────
 *
 * `consent_grants.scope` really does hold values outside the consent
 * vocabulary — `patients:read`, the B2B Connect token scope, is in the table
 * today. Title-casing that into "Patients: Read" would be a guess, and a guess
 * on a consent screen is a lie about how much of a medical record is being
 * handed over: a scope that reads narrow may not be narrow.
 *
 * So every label carries `recognised`. When it is false the label IS the raw
 * code, unchanged, and a note travels with it saying, in the patient's
 * language, that OpesCare has no name for this one and that they should ask
 * what it covers before approving. Two membership tests have to pass before a
 * code is called recognised: it must be in the controller's allow-list AND
 * have a real translation. Laravel's `__()` returns the key itself for a
 * missing string, which would otherwise put `consent_requests.scope.foo` on
 * screen and call it a label.
 */
final class PatientVocabulary
{
    /** The two languages this platform ships. */
    public const LOCALES = ['en', 'fr'];

    /**
     * Access-log purposes that carry a second segment naming the authority the
     * read rested on — `treatment:treating_clinician`,
     * `emergency:break_glass:<event id>`. Anything after the basis is an
     * internal record id and is not rendered: it names no concept, so there is
     * no label for it and nothing is lost by leaving it on the raw `purpose`
     * field, which this class never rewrites.
     *
     * @var list<string>
     */
    private const PURPOSES_WITH_BASIS = ['treatment', 'emergency'];

    // ══════════════════════════════════════════════════════════════════
    // Consent vocabulary — the clinician's picker, read back to the patient
    // ══════════════════════════════════════════════════════════════════

    /**
     * One requested scope, as the patient reads it.
     *
     * @return array{code: string, label: string, recognised: bool}
     */
    /**
     * Legacy short codes still written by older request paths and seeded data
     * (`conditions`, `vitals`, `lab_results`…). They name the same things as the
     * canonical scopes, so the patient sees the plain-language name instead of a
     * raw code and a "not in our vocabulary" warning.
     */
    private const SCOPE_ALIASES = [
        'summary'     => 'patient.summary',
        'allergies'   => 'allergies.read',
        'conditions'  => 'problems.read',
        'problems'    => 'problems.read',
        'encounters'  => 'encounters.read',
        'visits'      => 'encounters.read',
        'vitals'      => 'vitals.read',
        'medications' => 'medications.read',
        'diagnoses'   => 'diagnoses.read',
        'orders'      => 'orders.read',
        'lab_results' => 'results.read',
        'labs'        => 'results.read',
    ];

    /** Label-only scopes: named for the patient, not requestable on their own. */
    private const LABEL_ONLY_SCOPES = ['results.read'];

    public static function scope(string $code, string $locale): array
    {
        $canonical = self::SCOPE_ALIASES[$code] ?? $code;
        $key = 'consent_requests.scope.' . $canonical;
        $known = in_array($canonical, MobileConsentRequestController::SCOPES, true)
            || in_array($canonical, self::LABEL_ONLY_SCOPES, true);

        if (! $known || ! Lang::has($key, $locale)) {
            return ['code' => $code, 'label' => $code, 'recognised' => false];
        }

        return ['code' => $code, 'label' => __($key, [], $locale), 'recognised' => true];
    }

    /**
     * A whole scope list, plus the one note that has to appear when any member
     * of it could not be resolved.
     *
     * @param  mixed  $codes  whatever the column held; a non-list is treated as empty
     * @return array{items: list<array{code: string, label: string, recognised: bool}>, note: string|null}
     */
    public static function scopes(mixed $codes, string $locale): array
    {
        $items = [];

        foreach (is_array($codes) ? $codes : [] as $code) {
            if (! is_string($code) || $code === '') {
                continue;
            }

            $items[] = self::scope($code, $locale);
        }

        $unknown = array_filter($items, fn (array $i) => ! $i['recognised']);

        return [
            'items' => array_values($items),
            'note'  => $unknown === [] ? null : __('consent_requests.scope_unrecognised', [], $locale),
        ];
    }

    /**
     * Why access is being asked for.
     *
     * @return array{code: string, label: string, recognised: bool, note: string|null}|null
     */
    public static function purpose(?string $code, string $locale): ?array
    {
        if ($code === null || $code === '') {
            return null;
        }

        $key = 'consent_requests.purpose.' . $code;

        if (! in_array($code, MobileConsentRequestController::PURPOSES, true) || ! Lang::has($key, $locale)) {
            return [
                'code'       => $code,
                'label'      => $code,
                'recognised' => false,
                'note'       => __('consent_requests.purpose_unrecognised', [], $locale),
            ];
        }

        return [
            'code'       => $code,
            'label'      => __($key, [], $locale),
            'recognised' => true,
            'note'       => null,
        ];
    }

    /**
     * How long the access runs, in the unit the clinician actually chose.
     *
     * The picker is denominated in HOURS (GRANT_HOURS_MIN..MAX), so a request
     * for "24 hours" is stored as 1440 minutes and has to read back as hours,
     * not as "1 day" and certainly not as "1440 min". Days are used only from
     * two whole days up, where the clinician was plainly thinking in days.
     *
     * @return array{minutes: int, label: string}|null
     */
    public static function duration(?int $minutes, string $locale): ?array
    {
        if ($minutes === null || $minutes <= 0) {
            return null;
        }

        [$unit, $count] = match (true) {
            $minutes < 60                              => ['minutes', $minutes],
            $minutes >= 2880 && $minutes % 1440 === 0  => ['days', intdiv($minutes, 1440)],
            $minutes % 60 === 0                        => ['hours', intdiv($minutes, 60)],
            default                                    => ['minutes', $minutes],
        };

        return [
            'minutes' => $minutes,
            'label'   => trans_choice('consent_requests.duration.' . $unit, $count, ['count' => $count], $locale),
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    // Access-log vocabulary — "why was my record opened?"
    // ══════════════════════════════════════════════════════════════════

    /**
     * One `access_logs.purpose` value, as a sentence.
     *
     * The column is a compound: a reason, optionally a colon and the authority
     * the read rested on, optionally a record id after that. The patient was
     * being shown the whole thing title-cased — `Treatment:treating clinician`
     * — which is the audit trail's own shorthand, in English, on the screen
     * whose entire job is telling a patient who opened their record and why.
     *
     * Both halves resolve against vocabularies that already exist: the reason
     * against lang/{en,fr}/access_log.php, the authority against the same
     * `basis` list MobileDoctorChartController::relationshipBasis() returns.
     * If either half is unknown the raw value is shown and said to be
     * unrecognised — an audit line is the last place to paper over a gap.
     *
     * @return array{code: string, label: string, basis: string|null, basis_label: string|null, recognised: bool, note: string|null}|null
     */
    public static function accessPurpose(?string $raw, string $locale): ?array
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $segments = explode(':', $raw);
        $head     = $segments[0];
        $basis    = $segments[1] ?? null;

        $headKey  = 'access_log.purpose.' . $head;
        $basisKey = $basis === null ? null : 'access_log.basis.' . $basis;

        $headOk  = Lang::has($headKey, $locale);
        $basisOk = $basis === null
            || (in_array($head, self::PURPOSES_WITH_BASIS, true) && Lang::has($basisKey, $locale));

        if (! $headOk || ! $basisOk) {
            return [
                'code'        => $raw,
                'label'       => $raw,
                'basis'       => $basis,
                'basis_label' => null,
                'recognised'  => false,
                'note'        => __('access_log.purpose_unrecognised', [], $locale),
            ];
        }

        $headLabel  = __($headKey, [], $locale);
        $basisLabel = $basis === null ? null : __($basisKey, [], $locale);

        return [
            'code'        => $raw,
            'label'       => $basisLabel === null
                ? $headLabel
                : __('access_log.purpose_with_basis', ['purpose' => $headLabel, 'basis' => $basisLabel], $locale),
            'basis'       => $basis,
            'basis_label' => $basisLabel,
            'recognised'  => true,
            'note'        => null,
        ];
    }

    /**
     * Which part of the record was opened.
     *
     * @return array{code: string, label: string, recognised: bool}|null
     */
    public static function dataCategory(?string $code, string $locale): ?array
    {
        if ($code === null || $code === '') {
            return null;
        }

        $key = 'access_log.category.' . $code;

        return Lang::has($key, $locale)
            ? ['code' => $code, 'label' => __($key, [], $locale), 'recognised' => true]
            : ['code' => $code, 'label' => $code, 'recognised' => false];
    }

    // ══════════════════════════════════════════════════════════════════
    // Plumbing
    // ══════════════════════════════════════════════════════════════════

    /**
     * EN or FR for this request.
     *
     * The mobile API is stateless and has no session, so the `web` stack's
     * SetLocale middleware never runs on it. Same resolution order the patient
     * -write and consent-ledger endpoints already use: explicit `?lang=`, then
     * Accept-Language, then whatever the app is set to.
     */
    public static function locale(Request $request): string
    {
        $requested = $request->query('lang');
        if (is_string($requested) && in_array($requested, self::LOCALES, true)) {
            return $requested;
        }

        $header  = (string) $request->header('Accept-Language', '');
        $primary = strtolower(substr(trim(explode(',', $header)[0] ?? ''), 0, 2));
        if (in_array($primary, self::LOCALES, true)) {
            return $primary;
        }

        return in_array(App::getLocale(), self::LOCALES, true) ? App::getLocale() : 'en';
    }
}
