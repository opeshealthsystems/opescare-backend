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
 *           'appointment', 'message', 'emergency_access'). It picks the
 *           Android channel and therefore the sound, and it is what the app
 *           switches on when the push arrives. An unknown type still sends,
 *           on the 'default' channel.
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
 *   - It only reaches ACTIVE, un-revoked rows in push_device_tokens, and of
 *     those only real Expo tokens (ExpoPushService::isExpoPushToken). Legacy
 *     fingerprint rows are counted in the result as skippedNoToken.
 *   - It returns the ExpoPushResult so a caller that cares can log or assert
 *     on it; a caller that does not can ignore the return value.
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
