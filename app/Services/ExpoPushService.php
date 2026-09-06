<?php

namespace App\Services;

use App\Models\PushDelivery;
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
 * ## Tickets, receipts and the ledger
 *
 * Expo answers a send with a TICKET per message — "queued", or an error such
 * as `DeviceNotRegistered`. Whether a handset was actually reached is only
 * known from the RECEIPT, which Expo publishes a few minutes later and keeps
 * for about a day. Every message this class puts on the wire is written to
 * `push_deliveries` (App\Models\PushDelivery) with its ticket; the scheduled
 * `push:check-receipts` pass (every 15 minutes, routes/console.php) calls
 * `reconcileReceipts()`, which marks each row ok/error and — the part that
 * matters — retires the device token when Expo says `DeviceNotRegistered`,
 * so an uninstalled app stops being pushed to. Other errors keep the token
 * and record the code on the row for `opescare:push-status` to show.
 *
 * ## What still has to happen before a patient sees a push
 *
 * Real tokens arrive only once the owner gives the mobile app an EAS project
 * id and uploads FCM/APNs credentials to EAS. Until then `push_device_tokens`
 * holds fingerprint-only rows, `isExpoPushToken()` refuses them, and they come
 * back as `skippedNoToken` — never as "sent". `opescare:push-test` and
 * `opescare:push-status` say which state the host is in.
 *
 * ## PHI
 *
 * A push lands on a lock screen, which is a public surface. `$title` and
 * `$body` must never carry a diagnosis, a result value, or a medication name —
 * "You have a new lab result" is the whole of what may be said; the record
 * lives behind authentication in the app. `$data` is for routing only
 * (`type`, `screen`, resource ids). This class does not and cannot inspect the
 * strings it is handed, so the rule belongs to every caller. The ledger stores
 * `data.type` and Expo's diagnostics, never the copy.
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
     * How long Expo keeps a receipt. A pending ticket older than this will
     * never resolve; reconcileReceipts() closes it as ReceiptUnavailable.
     */
    public const RECEIPT_TTL_HOURS = 24;

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
     * Whether an Expo access token is set — a yes/no for operator tooling.
     * The value itself is never returned or printed anywhere.
     */
    public function hasAccessToken(): bool
    {
        return (string) $this->accessToken() !== '';
    }

    /**
     * Push to every active device this patient has registered.
     *
     * Rows whose `push_token` is not an Expo token are counted as
     * `skippedNoToken` and left alone: they are not corrupt, they are the
     * fingerprint-only registrations, and the mobile client heals them on
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
            ->get(['id', 'patient_id', 'platform', 'push_token']);

        if ($rows->isEmpty()) {
            return new ExpoPushResult();
        }

        $tokens      = [];
        $rowsByToken = [];
        $skipped     = 0;

        foreach ($rows as $row) {
            if (self::isExpoPushToken($row->push_token)) {
                if (! isset($rowsByToken[$row->push_token])) {
                    $tokens[]                         = $row->push_token;
                    $rowsByToken[$row->push_token]    = $this->rowContext($row);
                }
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

        $result = $this->dispatch($tokens, $title, $body, $data, $channelId, $priority, $sound, $rowsByToken);

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
     * Each token is looked up in push_device_tokens so the ledger row can name
     * the patient and device it went to; a token the table does not hold is
     * still sent and still recorded, with those columns null.
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
        $expoTokens  = array_filter($tokens, [self::class, 'isExpoPushToken']);
        $deliverable = array_values(array_unique($expoTokens));
        $skipped     = count($tokens) - count($expoTokens);

        if ($deliverable === []) {
            return new ExpoPushResult(skippedNoToken: $skipped);
        }

        $result = $this->dispatch($deliverable, $title, $body, $data, $channelId, $priority, $sound, $this->rowsFor($deliverable));

        return $result->merge(new ExpoPushResult(skippedNoToken: $skipped));
    }

    /**
     * Ask Expo for the receipts of these tickets — the raw answer.
     *
     * @param  list<string>  $ticketIds  At most RECEIPT_CHUNK_SIZE.
     * @return array<string, array<string, mixed>>|null  ticket id => receipt
     *         ({status: ok} or {status: error, message, details: {error}}),
     *         only for tickets Expo has a receipt for; null when the lookup
     *         itself failed (network, non-2xx) and nothing can be concluded.
     */
    public function fetchReceipts(array $ticketIds): ?array
    {
        $ticketIds = array_values(array_unique(array_map('strval', $ticketIds)));

        if ($ticketIds === []) {
            return [];
        }

        try {
            $response = $this->request()->post(self::RECEIPTS_ENDPOINT, ['ids' => $ticketIds]);
        } catch (\Throwable $e) {
            Log::warning('ExpoPushService: receipt lookup failed', ['error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('ExpoPushService: receipt lookup rejected', ['status' => $response->status()]);

            return null;
        }

        $receipts = [];

        foreach ((array) $response->json('data', []) as $ticketId => $receipt) {
            if (is_array($receipt)) {
                $receipts[(string) $ticketId] = $receipt;
            }
        }

        return $receipts;
    }

    /**
     * Ask Expo what actually happened to messages it accepted earlier, and
     * write the answer onto the matching push_deliveries rows.
     *
     * A ticket says "queued"; a receipt says "delivered to APNs/FCM, or here is
     * why not". Receipts are only meaningful a few minutes after the send and
     * Expo keeps them for about 24 hours. `DeviceNotRegistered` here retires
     * the token, which is the only way an uninstalled app stops being pushed
     * to forever. Scheduled through reconcileReceipts() / `push:check-receipts`;
     * callable directly with a known list of tickets (opescare:push-test).
     *
     * @param  list<string>  $ticketIds
     * @return array<string, string>  ticket id => error code, for failures only
     */
    public function checkReceipts(array $ticketIds): array
    {
        $failures = [];

        foreach (array_chunk(array_values(array_unique($ticketIds)), self::RECEIPT_CHUNK_SIZE) as $chunk) {
            $receipts = $this->fetchReceipts($chunk);

            if ($receipts === null) {
                continue;
            }

            $this->applyReceipts($receipts);

            foreach ($receipts as $ticketId => $receipt) {
                if (($receipt['status'] ?? null) !== 'ok') {
                    $failures[(string) $ticketId] = (string) ($receipt['details']['error'] ?? 'Unknown');
                }
            }
        }

        return $failures;
    }

    /**
     * The scheduled pass: resolve every pending ledger row Expo can report on.
     *
     * Rows are taken oldest first, only once they are at least $minAgeMinutes
     * old (Expo needs a few minutes to produce a receipt), at most $limit per
     * run. A row whose receipt Expo no longer holds — older than
     * RECEIPT_TTL_HOURS with nothing returned — is closed as
     * ReceiptUnavailable so it does not stay pending forever; nothing is
     * retired for it, because nothing is known.
     *
     * @return array{checked:int, ok:int, error:int, retired:int, unavailable:int, still_pending:int, lookup_failed:bool}
     */
    public function reconcileReceipts(int $minAgeMinutes = 5, int $limit = 1000): array
    {
        $summary = [
            'checked'       => 0,
            'ok'            => 0,
            'error'         => 0,
            'retired'       => 0,
            'unavailable'   => 0,
            'still_pending' => 0,
            'lookup_failed' => false,
        ];

        $pending = PushDelivery::query()
            ->pending()
            ->where('sent_at', '<=', now()->subMinutes(max(0, $minAgeMinutes)))
            ->orderBy('sent_at')
            ->limit(max(1, $limit))
            ->get(['id', 'ticket_id', 'sent_at']);

        if ($pending->isEmpty()) {
            return $summary;
        }

        $summary['checked'] = $pending->count();
        $expiredBefore      = now()->subHours(self::RECEIPT_TTL_HOURS);

        foreach ($pending->chunk(self::RECEIPT_CHUNK_SIZE) as $rows) {
            $ticketIds = $rows->pluck('ticket_id')->unique()->values()->all();
            $receipts  = $this->fetchReceipts($ticketIds);

            if ($receipts === null) {
                $summary['lookup_failed'] = true;

                continue;
            }

            $applied = $this->applyReceipts($receipts);
            $summary['ok']      += $applied['ok'];
            $summary['error']   += $applied['error'];
            $summary['retired'] += $applied['retired'];

            // Tickets Expo said nothing about: not ready yet, or gone for good.
            foreach ($rows as $row) {
                if (isset($receipts[(string) $row->ticket_id])) {
                    continue;
                }

                if ($row->sent_at !== null && $row->sent_at->lt($expiredBefore)) {
                    PushDelivery::query()->whereKey($row->id)->where('status', PushDelivery::STATUS_PENDING)->update([
                        'status'             => PushDelivery::STATUS_ERROR,
                        'error_code'         => PushDelivery::ERROR_RECEIPT_UNAVAILABLE,
                        'error_details'      => json_encode(['message' => 'Expo returned no receipt within '.self::RECEIPT_TTL_HOURS.' hours of the send.']),
                        'receipt_checked_at' => now(),
                    ]);
                    $summary['unavailable']++;
                } else {
                    PushDelivery::query()->whereKey($row->id)->update(['receipt_checked_at' => now()]);
                    $summary['still_pending']++;
                }
            }
        }

        return $summary;
    }

    // -------------------------------------------------------------------------

    /**
     * Build the messages and send them, one Expo request per CHUNK_SIZE.
     *
     * @param  list<string>  $tokens  Expo tokens only — already filtered.
     * @param  array<string, array{id:?string, patient_id:?string, platform:?string}>  $rowsByToken
     */
    private function dispatch(
        array $tokens,
        string $title,
        string $body,
        array $data,
        string $channelId,
        string $priority,
        ?string $sound,
        array $rowsByToken,
    ): ExpoPushResult {
        $sound = ($sound === null || $sound === '') ? PushChannels::soundFor($channelId) : $sound;
        $type  = isset($data['type']) ? (string) $data['type'] : null;

        $result = new ExpoPushResult();

        foreach (array_chunk($tokens, self::CHUNK_SIZE) as $chunk) {
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

            $result = $result->merge($this->dispatchChunk($messages, $chunk, $rowsByToken, $type));
        }

        return $result;
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @param  list<string>  $tokens  Same order as $messages — Expo returns
     *                                tickets positionally, which is the only
     *                                way to map an error back to a token.
     * @param  array<string, array{id:?string, patient_id:?string, platform:?string}>  $rowsByToken
     */
    private function dispatchChunk(array $messages, array $tokens, array $rowsByToken, ?string $type): ExpoPushResult
    {
        try {
            $response = $this->request()->post(self::SEND_ENDPOINT, $messages);
        } catch (\Throwable $e) {
            Log::error('ExpoPushService: send failed', ['error' => $e->getMessage()]);
            $this->recordDeliveries($tokens, $rowsByToken, $type, [], PushDelivery::ERROR_TRANSPORT, $e->getMessage());

            return new ExpoPushResult(transportError: $e->getMessage());
        }

        if (! $response->successful()) {
            Log::error('ExpoPushService: send rejected', [
                'status' => $response->status(),
                'body'   => $response->json('errors') ?? $response->body(),
            ]);
            $this->recordDeliveries($tokens, $rowsByToken, $type, [], PushDelivery::ERROR_TRANSPORT, 'HTTP '.$response->status());

            return new ExpoPushResult(transportError: 'HTTP '.$response->status());
        }

        $tickets  = array_values((array) $response->json('data', []));
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
            if ($code === PushDelivery::ERROR_DEVICE_NOT_REGISTERED && $token !== null) {
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

        $this->recordDeliveries($tokens, $rowsByToken, $type, $tickets);

        return new ExpoPushResult(
            accepted: $accepted,
            rejected: $rejected,
            retired: $retired,
            ticketIds: $ids,
            errors: $errors,
        );
    }

    /**
     * Write one ledger row per message in this chunk.
     *
     * Bookkeeping must never break a send that otherwise succeeded, so every
     * failure here is logged and swallowed — the result the caller gets is
     * the same either way.
     *
     * @param  list<string>  $tokens
     * @param  array<string, array{id:?string, patient_id:?string, platform:?string}>  $rowsByToken
     * @param  list<array<string, mixed>>  $tickets  Positional, as Expo returned them; [] on transport failure.
     */
    private function recordDeliveries(
        array $tokens,
        array $rowsByToken,
        ?string $type,
        array $tickets,
        ?string $transportCode = null,
        ?string $transportMessage = null,
    ): void {
        $sentAt = now();

        foreach ($tokens as $index => $token) {
            $row    = $rowsByToken[$token] ?? ['id' => null, 'patient_id' => null, 'platform' => null];
            $ticket = $tickets[$index] ?? null;

            $attributes = [
                'patient_id'           => $row['patient_id'],
                'push_device_token_id' => $row['id'],
                'platform'             => $row['platform'],
                'type'                 => $type !== null ? substr($type, 0, 40) : null,
                'sent_at'              => $sentAt,
            ];

            if ($transportCode !== null) {
                $attributes += [
                    'status'        => PushDelivery::STATUS_ERROR,
                    'error_code'    => $transportCode,
                    'error_details' => ['message' => $transportMessage],
                ];
            } elseif (($ticket['status'] ?? null) === 'ok') {
                $attributes += [
                    'status'    => PushDelivery::STATUS_PENDING,
                    'ticket_id' => isset($ticket['id']) ? substr((string) $ticket['id'], 0, 64) : null,
                ];
            } else {
                $attributes += [
                    'status'        => PushDelivery::STATUS_ERROR,
                    'error_code'    => substr((string) ($ticket['details']['error'] ?? 'Unknown'), 0, 64),
                    'error_details' => [
                        'message' => $ticket['message'] ?? null,
                        'details' => $ticket['details'] ?? null,
                    ],
                ];
            }

            try {
                PushDelivery::create($attributes);
            } catch (\Throwable $e) {
                Log::warning('ExpoPushService: could not record delivery', ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Write Expo's receipts onto the pending ledger rows that carry their
     * tickets, retiring the device token behind a DeviceNotRegistered.
     *
     * A ticket the ledger does not know (a caller that kept its own ids) is
     * still acted on for DeviceNotRegistered when the receipt names the token.
     *
     * @param  array<string, array<string, mixed>>  $receipts  ticket id => receipt
     * @return array{ok:int, error:int, retired:int}
     */
    private function applyReceipts(array $receipts): array
    {
        $out = ['ok' => 0, 'error' => 0, 'retired' => 0];

        foreach ($receipts as $ticketId => $receipt) {
            $ticketId = (string) $ticketId;
            $ok       = ($receipt['status'] ?? null) === 'ok';
            $code     = $ok ? null : substr((string) ($receipt['details']['error'] ?? 'Unknown'), 0, 64);

            $rows = PushDelivery::query()
                ->where('ticket_id', $ticketId)
                ->where('status', PushDelivery::STATUS_PENDING)
                ->get();

            foreach ($rows as $row) {
                try {
                    $row->forceFill([
                        'status'             => $ok ? PushDelivery::STATUS_OK : PushDelivery::STATUS_ERROR,
                        'error_code'         => $code,
                        'error_details'      => $ok ? null : [
                            'message' => $receipt['message'] ?? null,
                            'details' => $receipt['details'] ?? null,
                        ],
                        'receipt_checked_at' => now(),
                    ])->save();
                } catch (\Throwable $e) {
                    Log::warning('ExpoPushService: could not update delivery', ['error' => $e->getMessage()]);
                }
            }

            if ($ok) {
                $out['ok'] += max(1, $rows->count());

                continue;
            }

            $out['error'] += max(1, $rows->count());

            if ($code !== PushDelivery::ERROR_DEVICE_NOT_REGISTERED) {
                // Every other code (MessageTooBig, MessageRateExceeded,
                // InvalidCredentials, MismatchSenderId, …) is about the
                // message or the project, not the handset: the token stays
                // and the code is on the row for push-status to show.
                Log::info('ExpoPushService: receipt reported an error', ['ticket' => $ticketId, 'code' => $code]);

                continue;
            }

            // The handset is gone. Prefer the token string (it retires every
            // row holding it, on any account); fall back to the ledger's
            // device row when the receipt does not name the token.
            $token = isset($receipt['details']['expoPushToken']) ? (string) $receipt['details']['expoPushToken'] : null;

            if ($token === null) {
                $tokenIds = $rows->pluck('push_device_token_id')->filter()->unique()->values()->all();
                $token    = $tokenIds === []
                    ? null
                    : PushDeviceToken::query()->whereIn('id', $tokenIds)->value('push_token');
            }

            if ($token !== null && $token !== '') {
                $out['retired'] += $this->retireToken($token, 'receipt:DeviceNotRegistered');
            }
        }

        return $out;
    }

    /**
     * The ledger context for a list of tokens: which row (and so which patient
     * and platform) each one belongs to. First active row wins for a token
     * that somehow has several.
     *
     * @param  list<string>  $tokens
     * @return array<string, array{id:?string, patient_id:?string, platform:?string}>
     */
    private function rowsFor(array $tokens): array
    {
        $out = [];

        try {
            $rows = PushDeviceToken::query()
                ->whereIn('push_token', $tokens)
                ->where('is_active', true)
                ->whereNull('revoked_at')
                ->orderByDesc('registered_at')
                ->get(['id', 'patient_id', 'platform', 'push_token']);

            foreach ($rows as $row) {
                if (! isset($out[$row->push_token])) {
                    $out[$row->push_token] = $this->rowContext($row);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('ExpoPushService: could not resolve token rows', ['error' => $e->getMessage()]);
        }

        return $out;
    }

    /** @return array{id:?string, patient_id:?string, platform:?string} */
    private function rowContext(PushDeviceToken $row): array
    {
        return [
            'id'         => $row->id !== null ? (string) $row->id : null,
            'patient_id' => $row->patient_id !== null ? (string) $row->patient_id : null,
            'platform'   => $row->platform !== null ? (string) $row->platform : null,
        ];
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
        $accessToken = $this->accessToken();

        $client = Http::timeout(15)
            ->retry(2, 250, throw: false)
            ->withHeaders([
                'Accept'           => 'application/json',
                'Accept-Encoding'  => 'gzip, deflate',
                'Content-Type'     => 'application/json',
            ]);

        return $accessToken ? $client->withToken($accessToken) : $client;
    }

    private function accessToken(): ?string
    {
        $token = config('push_channels.expo.access_token') ?: config('services.expo.access_token');

        return is_string($token) && trim($token) !== '' ? trim($token) : null;
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
