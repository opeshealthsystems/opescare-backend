# Patient push delivery — the backend half

*Checked against the code on 2026-09-08. If this disagrees with the code, trust the code and fix this.*

This is what the **server** needs, does and reports for phone push to the
patient app (OpesCare Finder, Expo). The **app** half — EAS project id,
FCM/APNs credentials, minting the token, the notification channels and
sounds — lives in the mobile repo's push runbook (`opescare-finder-mobile`).
The two hand off at exactly one place: `POST /api/mobile/push-tokens`.

## 1. What the server needs

| Env | Default | Meaning |
|---|---|---|
| `EXPO_PUSH_ENABLED` | `true` | The one switch. `false` silences every patient push **and** the receipt pass. `config('push_channels.expo.enabled')`. |
| `EXPO_ACCESS_TOKEN` | *(empty)* | Only when the EAS project has **enhanced push security** enabled. Otherwise leave empty — Expo's push API is unauthenticated by default. `config('push_channels.expo.access_token')`. Never commit a value; `.env` is never deployed. |

That is all. **No FCM service account, no APNs key on this host** — those
are uploaded once to EAS by the owner (`eas credentials`) and Expo uses them.
If they are missing, Expo answers every send with the ticket error
`InvalidCredentials`; `ExpoPushService` logs it at `error` and
`opescare:push-status` shows it in the error breakdown.

Also required on the host:

- the scheduler (`php artisan schedule:run` every minute under systemd/cron)
  — the receipt pass is a scheduled command;
- outbound HTTPS to `exp.host` (send and receipts).

Config shape (`config/push_channels.php`) is unchanged: `expo.enabled`,
`expo.access_token`, `channels` (Android channel → sound), `types`
(data.type → channel).

## 2. The pieces

```
caller (ConsentService, LabDesk, PharmacyDesk, FrontDesk, FamilyInvitationNotifier …)
  └─ App\Support\PatientPush::send(Patient, type, title, body, data)
       ├─ switch off?            → nothing, logged at debug
       ├─ patient opted out?     → nothing, result->suppressedBy = column   (§4)
       └─ App\Services\ExpoPushService::sendToPatient()
            ├─ active rows in push_device_tokens, real Expo tokens only
            ├─ POST https://exp.host/--/api/v2/push/send  (≤100 per request)
            ├─ one push_deliveries row per message         (§3)
            └─ ticket DeviceNotRegistered → token retired at once

scheduler, every 15 min
  └─ php artisan push:check-receipts
       └─ ExpoPushService::reconcileReceipts()
            ├─ POST …/push/getReceipts for pending tickets (≤300 per request)
            ├─ row → ok | error(code)
            ├─ receipt DeviceNotRegistered → token retired
            └─ no receipt after 24h → error ReceiptUnavailable (nothing retired)
```

Tables:

- `push_device_tokens` — one row per handset per account
  (`patient_id + device_fingerprint + platform` unique). `push_token` is an
  `ExponentPushToken[…]` / `ExpoPushToken[…]`, or `''` for a fingerprint-only
  registration that is never sent to. `is_active=false` + `revoked_at` is a
  retired row; nothing deletes rows.
- `push_deliveries` — the ledger. `patient_id`, `push_device_token_id`,
  `platform`, `type` (data.type — never the copy), `ticket_id`,
  `status` (`pending|ok|error`), `error_code`, `error_details` (Expo's own
  diagnostic), `sent_at`, `receipt_checked_at`. Cascades with the patient;
  the device link is nulled if the token row ever goes.

## 3. How receipts work

Expo answers a send with a **ticket** per message. A ticket is *"queued"*,
nothing more. Whether the handset was reached — or why not — is in the
**receipt**, which Expo publishes a few minutes after the send and keeps for
about 24 hours. Before this ledger existed the ticket ids were dropped, so
the one signal that an app had been uninstalled (`DeviceNotRegistered` at
receipt time) was never read and a dead token was pushed to forever.

Now:

1. Every message put on the wire writes a `push_deliveries` row: `pending`
   with its ticket, or `error` straight away when Expo refused it in the
   ticket (`DeviceNotRegistered`, `InvalidCredentials`, `MessageTooBig`…).
   A whole request failing (network, non-2xx) writes `error` /
   `TransportError` rows.
2. `push:check-receipts` runs every 15 minutes (`routes/console.php`,
   `withoutOverlapping`). It takes pending rows at least `--min-age=5`
   minutes old (Expo needs a few minutes), oldest first, at most
   `--limit=1000`, asks Expo for their receipts and writes the answer back:
   - `ok` → `status=ok`, `receipt_checked_at` set;
   - `error` → `status=error`, `error_code`, `error_details`;
   - **`DeviceNotRegistered` → the device token is revoked**
     (`is_active=false`, `revoked_at`). The next `PatientPush::send` for that
     patient never reaches Expo for that handset. This is the only code that
     retires a token; every other code keeps the token and records why.
   - no receipt yet → stays `pending`, `receipt_checked_at` updated;
   - no receipt and the row is older than 24h → `error` /
     `ReceiptUnavailable`. Nothing is known, so nothing is retired.
3. Resolved rows older than `--prune-days=90` are deleted on the same pass;
   pending rows are never pruned.

If Expo is unreachable during a pass the rows stay pending, the command
exits 1 and says so; the next run retries. `EXPO_PUSH_ENABLED=false` makes
the pass a no-op.

## 4. The patient's switches

`mobile_app_settings` (PATCH `/api/mobile/settings`) holds one boolean per
kind of push. `PatientPush::PREFERENCE_FOR_TYPE` maps data.type → column:

| data.type | column |
|---|---|
| `appointment` | `push_appointments` |
| `lab_result`, `lab_request` | `push_lab_results` |
| `prescription`, `reservation` | `push_prescriptions` |
| `message` | `push_messages` *(new column, default true — see §7)* |
| `billing` *(no such push yet)* | `push_billing` |

A `false` stops the push before it is built: no Expo request, no ledger row,
`ExpoPushResult::suppressedBy` names the column. No settings row, or a null
column, means opted **in** — silence must be chosen, never implied.

**Never suppressed**, whatever the switches say: `consent_request`,
`family_invitation` (a professional or a guardian is waiting for the
patient's decision) and `emergency_access` (the record was opened in an
emergency). `push_consent_requests` therefore does not silence the phone; it
is kept for the in-app inbox and the settings screen. The copy for a
settings hint is `push.preferences.always_delivered` (EN/FR).

## 5. Token hygiene at the door — `POST /api/mobile/push-tokens`

Body: `device_fingerprint` (required), `platform` (`ios|android|web`,
required), `push_token` (optional).

- `push_token` **present** → must be a well-formed `ExponentPushToken[…]` /
  `ExpoPushToken[…]` (the sender's own predicate,
  `ExpoPushService::isExpoPushToken`), else **422** and nothing is stored.
  Blank counts as present.
- `push_token` **omitted** → fingerprint-only registration (the in-app path:
  web, or a build with no EAS project). Stored with `push_token=''`,
  answers `deliverable: false`, never sent to. A later registration from the
  same handset with a real token heals the same row.
- Same handset again → the row is updated, `registered_at` moves. Same
  token under a new fingerprint (reinstall) → the row holding the token is
  updated; further duplicates are revoked. One phone, one row.
- Token seen under **another account** → the other account's row is
  revoked. A phone signed out and signed in as someone else stops ringing
  for its previous owner.

Response: `{status: "registered", token_id, platform, deliverable}` (201).

Sign-out: `DELETE /api/mobile/push-tokens` with `{device_fingerprint}`
revokes that handset's rows (the patient's other phone keeps ringing);
without a body it revokes every active row of the account. Idempotent,
answers `{status: "revoked", revoked: n, scope: "device"|"account"}`.
`DELETE /api/mobile/push-tokens/{id}` (the settings switch) is unchanged.
Both are own-account paths: the act-as-dependent header never redirects them.

## 6. Operator tooling

```
php artisan opescare:push-status
```

Prints, in order: whether the switch is on; whether an access token is
present (**never its value**); whether `push:check-receipts` is scheduled
and on what expression; active device rows by platform, split into
deliverable vs fingerprint-only, plus revoked; pending / delivered (24h) /
failed (24h) counts with the top error codes; the last 20 deliveries
(`--recent=N`). Reading it:

| You see | It means |
|---|---|
| `Expo push .. DISABLED` | `EXPO_PUSH_ENABLED=false` — nothing will send until it is on. |
| `Receipt pass .. NOT scheduled` | the `routes/console.php` line is missing; dead tokens will never retire. |
| deliverable `0`, fingerprint-only `>0` | the app has not minted real tokens yet — EAS project id / credentials missing on the app side. |
| errors `InvalidCredentials` | EAS project has no FCM/APNs credentials uploaded. Owner action. |
| errors `DeviceNotRegistered` | normal churn; those tokens are retired automatically. |
| many `pending` older than an hour | the receipt pass is not running (scheduler) or Expo is unreachable (`lookup failed` in the logs). |
| `ReceiptUnavailable` | the pass fell behind by more than a day; check the scheduler. |

```
php artisan opescare:push-test {health_id} [--type=message] [--force] [--receipts]
```

Sends **one real push** to the patient's active devices through
`PatientPush` — the same path every module uses — and prints the device
table, the channel/sound the type resolves to, Expo's counts, and the ledger
rows with their tickets. It refuses a non-demo patient without `--force`,
and each thing that can stop a push says so with exit 1: `push disabled`,
`no active token`, `suppressed — … push_messages`, `Refusing`. `--receipts`
asks Expo for the receipts immediately; they are usually not there yet, and
it says so. Health IDs are matched across the demo partition and active
aliases are followed. Tokens are printed masked.

```
php artisan push:check-receipts [--min-age=5] [--limit=1000] [--prune-days=90]
```

The scheduled pass, safe to run by hand.

## 7. Hand-off — what the app runbook and the owner still supply

- **App side (mobile runbook):** EAS project id in the app config, FCM v1
  service account + APNs key uploaded with `eas credentials`, and the app
  registering the minted token (`POST /api/mobile/push-tokens`) — already
  wired in `lib/api/settingsQueries.ts` / `app/(auth)/permissions.tsx`.
- **App sign-out:** `lib/store/auth.ts` `logout()` does not revoke the push
  token today (only the clinician session). It should call
  `DELETE /api/mobile/push-tokens` with `{device_fingerprint}` before the
  local wipe, best-effort.
- **`push_messages` in the settings UI:** the column exists and the push
  path honours it, but it is not yet in `MobileAppSetting::$fillable`/`$casts`,
  `MobileSettingsController::update()`/`formatSettings()`, the web
  `PatientPortalController` notification form, or the app's `settings.tsx`
  toggle list — until those one-liners land, every patient is opted in to
  message pushes (the default).
- **Verify on the host:** `php artisan opescare:push-status` (switch on,
  pass scheduled), then `php artisan opescare:push-test <demo health id>`
  on a demo patient whose phone has registered a real token, then ~15
  minutes later `opescare:push-status` again — the row should read `ok`.
