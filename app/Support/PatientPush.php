<?php

namespace App\Support;

use App\Models\MobileAppSetting;
use App\Models\Patient;
use App\Services\ExpoPushResult;
use App\Services\ExpoPushService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

/**
 * Push one notification to every phone a patient has registered.
 *
 * ## The contract (stable — other modules call this guarded by class_exists())
 *
 *     PatientPush::send(Patient $patient, string $type, string $title, string $body, array $data = []): ExpoPushResult
 *
 *   $type   one of the data.type values in config/push_channels.php
 *           ('consent_request', 'family_invitation', 'lab_result',
 *           'appointment', 'message', 'emergency_access', 'prescription',
 *           'reservation', 'lab_request'). It picks the Android channel and
 *           therefore the sound, it is what the app switches on when the push
 *           arrives, and it is what the patient's preferences are keyed on.
 *           An unknown type still sends, on the 'default' channel.
 *   $title  already translated, in the PATIENT's language — see localeFor().
 *   $body   idem. Both land on a lock screen: name the facility and the kind
 *           of event, never a diagnosis, a result value or a medication.
 *   $data   routing only — resource ids and an `action_url` the app opens.
 *           `type` is merged in for you (and cannot be overridden). Values are
 *           stringified on the wire, so pass scalars.
 *
 * ## What it promises
 *
 *   - It never throws. A push is a courtesy on top of a record that is
 *     already committed (the consent request, the invitation, the result);
 *     anything that goes wrong is logged at warning and swallowed.
 *   - It is a no-op when Expo push is switched off
 *     (config push_channels.expo.enabled / EXPO_PUSH_ENABLED=false) — logged
 *     at debug so a silent host can still be diagnosed.
 *   - It honours the patient's own switches (below).
 *   - It only reaches ACTIVE, un-revoked rows in push_device_tokens, and of
 *     those only real Expo tokens (ExpoPushService::isExpoPushToken). Legacy
 *     fingerprint rows are counted in the result as skippedNoToken.
 *   - Every message it puts on the wire is written to push_deliveries, so
 *     `opescare:push-status` can show what happened to it.
 *   - It returns the ExpoPushResult so a caller that cares can log or assert
 *     on it; a caller that does not can ignore the return value.
 *
 * ## The patient's switches
 *
 * mobile_app_settings holds one boolean per kind of push the app lets the
 * patient turn off (PATCH /api/mobile/settings). PREFERENCE_FOR_TYPE maps a
 * data.type to the column that governs it; a `false` there means the push is
 * not built at all, and the result says so (`suppressedBy`). A patient with
 * no settings row, or a column that is null, is treated as opted IN — the
 * migration defaults are all true and silence must be chosen, never implied.
 *
 * Two kinds of push are NEVER suppressed, whatever the settings say:
 *
 *   - `consent_request` (and `family_invitation`, which is the same kind of
 *     decision): a professional is waiting for the patient's yes or no, and
 *     nothing happens to the record until it arrives. A decision must reach
 *     the phone. `push_consent_requests` therefore governs nothing on this
 *     path — it is kept for the in-app inbox and the settings screen.
 *   - `emergency_access`: someone opened the record under emergency access
 *     and the patient has a right to know at once, not to find out later.
 *
 * ## Locale
 *
 * Callers that want the patient's own language for $title/$body use
 * localeFor($patient) with the three-argument __():
 *
 *     $locale = PatientPush::localeFor($patient);
 *     PatientPush::send($patient, 'lab_result',
 *         __('push.lab_result.title', [], $locale),
 *         __('push.lab_result.body', ['test' => $name], $locale),
 *         ['lab_order_id' => $order->id, 'action_url' => '/labs/'.$order->id]);
 */
final class PatientPush
{
    /** Locales the platform has strings for. */
    private const LOCALES = ['en', 'fr'];

    /**
     * data.type → the mobile_app_settings column that may silence it.
     *
     * A type absent from this map has no switch and always sends. `billing`
     * is not a push type yet; it is listed so the existing `push_billing`
     * toggle takes effect the day one is added.
     *
     * @var array<string, string>
     */
    public const PREFERENCE_FOR_TYPE = [
        'appointment'  => 'push_appointments',
        'lab_result'   => 'push_lab_results',
        'lab_request'  => 'push_lab_results',
        'prescription' => 'push_prescriptions',
        'reservation'  => 'push_prescriptions',
        'message'      => 'push_messages',
        'billing'      => 'push_billing',
    ];

    /**
     * Types no preference can silence — a decision or an emergency must
     * reach the phone. See the class docblock.
     *
     * @var list<string>
     */
    public const ALWAYS_DELIVERED = ['consent_request', 'family_invitation', 'emergency_access'];

    /**
     * @param  array<string, scalar|null>  $data
     */
    public static function send(Patient $patient, string $type, string $title, string $body, array $data = []): ExpoPushResult
    {
        try {
            /** @var ExpoPushService $expo */
            $expo = app(ExpoPushService::class);

            if (! $expo->isConfigured()) {
                Log::debug('PatientPush: Expo push is switched off — nothing sent', [
                    'patient_id' => $patient->getKey(),
                    'type'       => $type,
                ]);

                return new ExpoPushResult();
            }

            $suppressedBy = self::suppressedBy($patient, $type);

            if ($suppressedBy !== null) {
                Log::info('PatientPush: push suppressed by the patient\'s preference', [
                    'patient_id' => $patient->getKey(),
                    'type'       => $type,
                    'preference' => $suppressedBy,
                ]);

                return new ExpoPushResult(suppressedBy: $suppressedBy);
            }

            $channel = PushChannels::channelFor($type);

            // Nulls would be stringified to "" on the wire; drop them instead.
            $payload = array_filter($data, static fn ($v) => $v !== null);
            $payload['type'] = $type;

            return $expo->sendToPatient(
                (string) $patient->getKey(),
                $title,
                $body,
                $payload,
                $channel,
                'high',
                PushChannels::soundFor($channel),
            );
        } catch (\Throwable $e) {
            Log::warning('PatientPush: push not sent', [
                'patient_id' => $patient->getKey(),
                'type'       => $type,
                'error'      => $e->getMessage(),
            ]);

            return new ExpoPushResult(transportError: $e->getMessage());
        }
    }

    /**
     * The settings column that silences this kind of push for this patient,
     * or null when it may go out.
     *
     * Reads the column first and `extra_preferences[column]` second, so a
     * switch that exists only as a JSON preference (before its column is
     * migrated) is still honoured. Any failure to read means "send": a
     * settings lookup must never decide, by breaking, that a patient hears
     * nothing.
     */
    public static function suppressedBy(Patient $patient, string $type): ?string
    {
        if (in_array($type, self::ALWAYS_DELIVERED, true)) {
            return null;
        }

        $column = self::PREFERENCE_FOR_TYPE[$type] ?? null;

        if ($column === null) {
            return null;
        }

        try {
            $settings = MobileAppSetting::query()
                ->where('patient_id', $patient->getKey())
                ->first();
        } catch (\Throwable) {
            return null;
        }

        if ($settings === null) {
            return null;
        }

        $value = $settings->getAttribute($column);

        if ($value === null) {
            $extra = $settings->extra_preferences;
            $value = is_array($extra) ? ($extra[$column] ?? null) : null;
        }

        if ($value === null) {
            return null;
        }

        $enabled = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $enabled === false ? $column : null;
    }

    /** The settings column that governs a type, if any — for tooling and docs. */
    public static function preferenceFor(string $type): ?string
    {
        if (in_array($type, self::ALWAYS_DELIVERED, true)) {
            return null;
        }

        return self::PREFERENCE_FOR_TYPE[$type] ?? null;
    }

    /**
     * The language the patient asked the app to speak — mobile_app_settings
     * .preferred_language when it is one we have strings for, else the
     * current application locale, else 'en'.
     */
    public static function localeFor(Patient $patient): string
    {
        try {
            $preferred = MobileAppSetting::query()
                ->where('patient_id', $patient->getKey())
                ->value('preferred_language');

            if (is_string($preferred)) {
                $preferred = strtolower(substr(trim($preferred), 0, 2));

                if (in_array($preferred, self::LOCALES, true)) {
                    return $preferred;
                }
            }
        } catch (\Throwable) {
            // A settings lookup must never decide whether the push goes out.
        }

        $current = App::getLocale();

        return in_array($current, self::LOCALES, true) ? $current : 'en';
    }
}
