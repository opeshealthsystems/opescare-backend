# Mobile auth brief: what the app must handle (2026-10-19)

Server changes in `MobileAuthController` that the Expo app
(`C:\laragon\www\opescare-finder-mobile`) should adopt. Every change is
backward compatible: refusals still carry a translated `message`, which the
current app already shows via `extractErrorMessage()`. The additions below make
the experience right instead of merely not broken.

## 1. `POST /mobile/auth/login-email`: new refusals

Each refusal has a stable `code` field. Branch on `code`, not on `message`.

| HTTP | `code` | Meaning | What the app should do |
|---|---|---|---|
| 403 | `account_suspended` | Account suspended | Show the message. No retry. |
| 403 | `account_pending` | Non-patient account not yet activated (patient accounts from app registration are NOT refused) | Show the message. |
| 403 | `mfa_required` | The account has an authenticator app enrolled | Show a 6-digit code field and resend the **same** request with `mfa_code` added |
| 401 | `mfa_code_invalid` | The `mfa_code` was wrong | Keep the code screen open and let the user retry |
| 403 | `mfa_setup_required` | The role requires MFA but none is enrolled | Tell the user to enrol on the web portal first |

The request body gains an optional field: `{ email, password, mfa_code? }`.
The route is throttled at 5 requests a minute per IP, so a code retry counts
toward that limit.

## 2. `POST /mobile/auth/refresh`: single-use, 48 h grace

- **Single-use.** A token can be refreshed once. The old token is revoked in
  the same transaction that mints the new one, so a second refresh with the
  old token returns 401. The current interceptor in `lib/api/client.ts` already
  serialises refreshes through `refreshInFlight`, which is correct. Keep that,
  and always store the returned `access_token` before retrying.
- **Grace cut from 7 days to 48 hours.** A token that expired more than 48 h
  ago cannot be refreshed, and the user has to sign in again. The app's
  existing `onSessionExpired` path already handles this.
- Recommended: refresh before expiry rather than only after a 401. The login
  response gives you `expires_in`.

## 3. Phone + PIN first login: the PIN takes effect only after the OTP

`POST /mobile/auth/login` for a patient with no PIN (with `date_of_birth`)
still answers `200 otp_sent`, but the PIN is no longer saved at that point. It
is saved only when `POST /mobile/auth/otp/verify` succeeds. Nothing changes
for the app's flow. Two new answers are possible:

| HTTP | `code` | Where |
|---|---|---|
| 429 | `too_many_attempts` | `login` (more than 3 PIN-bootstrap tries per patient per hour) |
| 429 | `too_many_attempts` | `otp/verify` (5 wrong codes per patient within 10 min; the live codes are burned and the user must request a new one) |

## 4. `POST /mobile/payments/invoices`

This endpoint now answers `403 self_service_invoice_unavailable` on any host
where payments are real or unavailable. The app does not call it today, and it
must not start.
