<?php

namespace App\Services;

use App\Models\PushDeviceToken;
use App\Support\PushChannels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Patient push delivery — the server half that did not exist.
 *
 * ## What was missing
 *
 * `POST /api/mobile/push-tokens` has stored rows in `push_device_tokens` since
 * the mobile app shipped, and **nothing has ever read that table**. Grep it:
 * `MobileSettingsController` writes it, `PushDeviceToken` models it, and there
 * the trail ends. Worse, the mobile client sent the device's own random
 * fingerprint as `push_token`, so even a reader would have had nothing
 * deliverable to send to. `AppointmentReminderService` records
 * `'channels' => ['sms', 'push']` on each reminder row, but
 * `SendAppointmentRemindersCommand` dispatches `AppointmentSmsReminder`, whose
 * `via()` is `['database', 'vonage']` — the in-app feed and SMS. The 'push'
 * in that array has never meant anything.
 *
 * This class is the missing reader.
 *
 * ## Why Expo and not the existing FCM sender
 *
 * `App\Modules\Notifications\Services\PushNotificationService` is a real FCM
 * HTTP v1 client, and it is the right tool for `provider_devices` (the staff
 * app). It is the wrong tool for the patient app, which is a managed Expo
 * build: FCM would require `google-services.json` committed into the mobile
 * repo, an `FCM_SERVICE_ACCOUNT_JSON` secret on this host, and a separate APNs
 * path for iOS. Expo's push service needs **no secret on this host at all**
 * and covers both platforms; the credentials live once, in EAS, uploaded by
 * the owner. So the patient app mints `ExponentPushToken[…]`
 * (`lib/push/token.ts` in opescare-finder-mobile) and this sends to it.
 *
 * ## What still has to happen before a patient sees a push
 *
 * This class works today. It will send nothing today, and it says so rather
 * than pretending: `push_device_tokens` currently holds fingerprints, not
 * tokens, and `isExpoPushToken()` refuses them — they come back as
 * `skippedNoToken`, never as "sent". Real tokens start arriving only once the
 * owner gives the mobile app an EAS project id and uploads FCM/APNs
 * credentials to EAS. And nothing in this codebase calls this class yet:
 * wiring it into `AppointmentSmsReminder::via()` or `App\Support\Notify`
 * touches files this change does not own.
 *
 * ## PHI
 *
 * A push lands on a lock screen, which is a public surface. `$title` and
 * `$body` must never carry a diagnosis, a result value, or a medication name —
 * "You have a new lab result" is the whole of what may be said; the record
 * lives behind authentication in the app. `$data` is for routing only
 * (`type`, `screen`, resource ids). This class does not and cannot inspect the
 * strings it is handed, so the rule belongs to every caller.
 *
 * @see \App\Services\ExpoPushResult for how outcomes are reported.
 */
class ExpoPushService
{
    private const SEND_ENDPOINT = 'https://exp.host/--/api/v2/push/send';

    private const RECEIPTS_ENDPOINT = 'https://exp.host/--/api/v2/push/getReceipts';

    /** Expo's documented maximum messages per request. */
    private const CHUNK_SIZE = 100;

    /** Expo's documented maximum receipt ids per request. */
    private const RECEIPT_CHUNK_SIZE = 300;

    /**
     * Expo has minted both prefixes; the push API accepts either. Mirrors
     * `EXPO_PUSH_TOKEN_PATTERN` in the mobile app's `lib/push/token.ts` —
     * change one, change both.
     */
    private const TOKEN_PATTERN = '/^Expo(?:nent)?PushToken\[[^\s\[\]]+\]$/';

    /**
     * The single gate that stops a device fingerprint being sent to Expo as if
     * it were a credential.
     */
    public static function isExpoPushToken(?string $token): bool
    {
        return is_string($token) && preg_match(self::TOKEN_PATTERN, $token) === 1;
    }

    /**
     * Whether this host is allowed to push at all.
     *
     * Expo's API needs no credential on this host (see the class docblock), so
     * "configured" cannot mean "a secret is present". It is the one switch in
     * config/push_channels.php — `push_channels.expo.enabled`, from
     * EXPO_PUSH_ENABLED, on by default — that lets an operator or a test
     * silence every patient push in one place. App\Support\PatientPush checks
     * it before touching the database.
     */
    public function isConfigured(): bool
    {
        return (bool) config('push_channels.expo.enabled', true);
    }

    /**
     * Push to every active device this patient has registered.
     *
     * Rows whose `push_token` is not an Expo token are counted as
     * `skippedNoToken` and left alone: they are not corrupt, they are the
     * legacy fingerprint registrations, and the mobile client heals them on
     * its next registration (the table is unique on
     * patient_id + device_fingerprint + platform, so a real token overwrites
     * the same row).
     *
     * @param  array<string, scalar>  $data  Routing only. No clinical content.
     * @param  string|null  $sound  Bundled sound filename; defaults to the
     *                              channel's sound from config/push_channels.php.
     */
    public function sendToPatient(
        string $patientId,
        string $title,
        string $body,
        array $data = [],
        string $channelId = 'default',
        string $priority = 'high',
        ?string $sound = null,
    ): ExpoPushResult {
        $rows = PushDeviceToken::query()
            ->where('patient_id', $patientId)
            ->where('is_active', true)
            ->whereNull('revoked_at')
            ->get(['id', 'push_token']);

        if ($rows->isEmpty()) {
            return new ExpoPushResult();
        }

        $tokens  = [];
        $skipped = 0;

        foreach ($rows as $row) {
            if (self::isExpoPushToken($row->push_token)) {
                $tokens[] = $row->push_token;
            } else {
                $skipped++;
            }
        }

        if ($tokens === []) {
            // The honest outcome for the state this app is in today: the
            // patient has registered devices, and not one of them can be
            // reached. Logged at info, not error — nothing has malfunctioned.
            Log::info('ExpoPushService: no deliverable token for patient', [
                'patient_id'       => $patientId,
                'skipped_no_token' => $skipped,
            ]);

            return new ExpoPushResult(skippedNoToken: $skipped);
        }

        $result = $this->sendToTokens(array_values(array_unique($tokens)), $title, $body, $data, $channelId, $priority, $sound);

        return $result->merge(new ExpoPushResult(skippedNoToken: $skipped));
    }

    /**
     * Push to an explicit list of Expo push tokens.
     *
     * Anything that is not an Expo token is dropped before the request is
     * built, so a caller cannot get a fingerprint onto the wire by going round
     * `sendToPatient()`.
     *
     * The wire shape, fixed by the app's channel setup (T3) and by
     * config/push_channels.php:
     *
     *     {to, title, body, sound: '<file>.wav'|'default', priority, channelId, data: {type, …}}
     *
     * `channelId` picks the Android channel (whose sound the OS plays);
     * `sound` is the file iOS plays. Left null, `$sound` is the channel's own
     * sound, so a caller on channel 'consent' gets the hospital chime on both
     * platforms without naming it; a caller passing nothing at all keeps the
     * pre-existing 'default' / 'default' behaviour.
     *
     * @param  list<string>  $tokens
     * @param  array<string, scalar>  $data  Should carry `type` (see PushChannels).
     * @param  string|null  $sound  Bundled sound filename; defaults to the
     *                              channel's sound from config/push_channels.php.
     */
    public function sendToTokens(
        array $tokens,
        string $title,
        string $body,
        array $data = [],
        string $channelId = 'default',
        string $priority = 'high',
        ?string $sound = null,
    ): ExpoPushResult {
        $deliverable = array_values(array_filter($tokens, [self::class, 'isExpoPushToken']));
        $skipped     = count($tokens) - count($deliverable);

        if ($deliverable === []) {
            return new ExpoPushResult(skippedNoToken: $skipped);
        }

        $sound = ($sound === null || $sound === '') ? PushChannels::soundFor($channelId) : $sound;

        $result = new ExpoPushResult(skippedNoToken: $skipped);

        foreach (array_chunk($deliverable, self::CHUNK_SIZE) as $chunk) {
            $messages = array_map(
                fn (string $token): array => array_filter([
                    'to'        => $token,
                    'title'     => $title,
                    'body'      => $body,
                    'sound'     => $sound,
                    'priority'  => $priority,
                    'channelId' => $channelId,
                    // FCM/APNs values must be strings.
                    'data'      => $data === [] ? null : array_map('strval', $data),
                ], static fn ($v) => $v !== null),
                $chunk,
            );

            $result = $result->merge($this->dispatchChunk($messages, $chunk));
        }

        return $result;
    }

    /**
     * Ask Expo what actually happened to messages it accepted earlier.
     *
     * A ticket says "queued"; a receipt says "delivered to APNs/FCM, or here is
     * why not". Receipts are only meaningful ~15 minutes after the send and
     * Expo keeps them for about 24 hours, so this belongs on a scheduled pass,
     * not inline. `DeviceNotRegistered` here retires the row, which is the
     * only way an uninstalled app stops being pushed to forever.
     *
     * NOTE FOR THE OWNER: nothing schedules this yet. Doing so means a console
     * command plus a `routes/console.php` line, neither of which this change
     * owns.
     *
     * @param  list<string>  $ticketIds
     * @return array<string, string>  ticket id => error code, for failures only
     */
    public function checkReceipts(array $ticketIds): array
    {
        $failures = [];

        foreach (array_chunk(array_values(array_unique($ticketIds)), self::RECEIPT_CHUNK_SIZE) as $chunk) {
            try {
                $response = $this->request()->post(self::RECEIPTS_ENDPOINT, ['ids' => $chunk]);
            } catch (\Throwable $e) {
                Log::warning('ExpoPushService: receipt lookup failed', ['error' => $e->getMessage()]);

                continue;
            }

            if (! $response->successful()) {
                Log::warning('ExpoPushService: receipt lookup rejected', ['status' => $response->status()]);

                continue;
            }

            foreach ((array) $response->json('data', []) as $ticketId => $receipt) {
                if (($receipt['status'] ?? null) === 'ok') {
                    continue;
                }

                $code = $receipt['details']['error'] ?? 'Unknown';
                $failures[(string) $ticketId] = (string) $code;

                if ($code === 'DeviceNotRegistered' && isset($receipt['details']['expoPushToken'])) {
                    $this->retireToken((string) $receipt['details']['expoPushToken'], 'receipt:DeviceNotRegistered');
                }
            }
        }

        return $failures;
    }

    // -------------------------------------------------------------------------

    /**
     * @param  list<array<string, mixed>>  $messages
     * @param  list<string>  $tokens  Same order as $messages — Expo returns
     *                                tickets positionally, which is the only
     *                                way to map an error back to a token.
     */
    private function dispatchChunk(array $messages, array $tokens): ExpoPushResult
    {
        try {
            $response = $this->request()->post(self::SEND_ENDPOINT, $messages);
        } catch (\Throwable $e) {
            Log::error('ExpoPushService: send failed', ['error' => $e->getMessage()]);

            return new ExpoPushResult(transportError: $e->getMessage());
        }

        if (! $response->successful()) {
            Log::error('ExpoPushService: send rejected', [
                'status' => $response->status(),
                'body'   => $response->json('errors') ?? $response->body(),
            ]);

            return new ExpoPushResult(transportError: 'HTTP '.$response->status());
        }

        $tickets  = (array) $response->json('data', []);
        $accepted = 0;
        $rejected = 0;
        $retired  = 0;
        $ids      = [];
        $errors   = [];

        foreach ($tickets as $index => $ticket) {
            if (($ticket['status'] ?? null) === 'ok') {
                $accepted++;
                if (isset($ticket['id'])) {
                    $ids[] = (string) $ticket['id'];
                }

                continue;
            }

            $rejected++;
            $code     = (string) ($ticket['details']['error'] ?? 'Unknown');
            $errors[] = $code.': '.(string) ($ticket['message'] ?? '');
            $token    = $tokens[$index] ?? null;

            // A handset that uninstalled the app, or revoked notifications at
            // the OS. Retiring the row is the whole point of reading tickets:
            // otherwise it is retried forever.
            if ($code === 'DeviceNotRegistered' && $token !== null) {
                $retired += $this->retireToken($token, 'ticket:DeviceNotRegistered');
            }

            // The one error that means "the owner has not finished the setup":
            // the EAS project has no FCM service account / APNs key uploaded.
            // Called out explicitly so it is not lost among transient failures.
            if ($code === 'InvalidCredentials') {
                Log::error(
                    'ExpoPushService: EAS project has no push credentials — '
                    .'upload the FCM v1 service account and the APNs key with `eas credentials`. '
                    .'Until then no patient push can be delivered.',
                    ['message' => $ticket['message'] ?? null],
                );
            }
        }

        return new ExpoPushResult(
            accepted: $accepted,
            rejected: $rejected,
            retired: $retired,
            ticketIds: $ids,
            errors: $errors,
        );
    }

    /**
     * Expo's push API is unauthenticated by default; a bearer token is needed
     * only when the project has "enhanced push security" enabled.
     *
     * Read through `config()` rather than `env()` on purpose: this app runs
     * `config:cache` on deploy, and an `env()` call outside a config file
     * returns null once that cache exists — which would silently drop the
     * header exactly on the host that needs it.
     *
     * The token lives in `config/push_channels.php` (`push_channels.expo
     * .access_token`, from EXPO_ACCESS_TOKEN); a `services.expo.access_token`
     * entry is honoured too if one is ever added. Both null — the default
     * project — means the request goes out unauthenticated, which is correct.
     */
    private function request(): \Illuminate\Http\Client\PendingRequest
    {
        $accessToken = config('push_channels.expo.access_token') ?: config('services.expo.access_token');

        $client = Http::timeout(15)
            ->retry(2, 250, throw: false)
            ->withHeaders([
                'Accept'           => 'application/json',
                'Accept-Encoding'  => 'gzip, deflate',
                'Content-Type'     => 'application/json',
            ]);

        return $accessToken ? $client->withToken($accessToken) : $client;
    }

    /** Deactivate every active row holding this token. Returns rows changed. */
    private function retireToken(string $token, string $reason): int
    {
        try {
            $changed = PushDeviceToken::query()
                ->where('push_token', $token)
                ->where('is_active', true)
                ->update(['is_active' => false, 'revoked_at' => now()]);

            if ($changed > 0) {
                Log::info('ExpoPushService: retired push token', ['reason' => $reason, 'rows' => $changed]);
            }

            return $changed;
        } catch (\Throwable $e) {
            // Never let bookkeeping break a send that otherwise succeeded.
            Log::warning('ExpoPushService: could not retire token', ['error' => $e->getMessage()]);

            return 0;
        }
    }
}
