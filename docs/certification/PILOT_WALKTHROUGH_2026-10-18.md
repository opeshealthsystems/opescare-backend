# Hospital pilot walkthrough — 2026-10-18 (local copy)

Walked the exact path a pilot hospital takes, on the local dev app with test data
(`Pilot Test Hospital Bonamoussadi`, contact `pilot.admin@example.test`).

| Step | Result |
|---|---|
| 1. Apply at `/signup/organization` (7-step form, 2 PDF uploads) | **Works.** Lead created, reference `OPC-9C108CA0`, status `new`. |
| 2. OpesCare approves: `/admin/leads/{lead}/convert` | **Works** (run through `LeadConversionService`, the code the button calls). Creates the facility and directory listing and emails the invite. |
| 3. Contact accepts the invite and sets a password | **Works.** Redirects to `/login`. |
| 4. Hospital admin signs in | **Works.** Lands on `/admin`. |
| 5. Look up a patient by Health ID and request consent | **Not reachable for the hospital admin** (see findings). The demo doctor is sent to `/select-facility` first, as expected for a multi-facility clinician. |

## Findings for the cleanup (owner to prioritise)

1. **The hospital admin's home page is titled "Admin Governance Portal".** It reads like OpesCare's own back office. It should say something like "<Hospital name> — Facility admin".
2. **The hospital admin's sidebar has no Health ID or patient entry point.** `/facility/patients` redirects the admin back to `/admin`, apparently by design (the admin is not a clinician). The pilot story is "start with Health IDs", so either:
   - the admin needs a visible "Verify a Health ID" link (`/verify/health-id` works for them), or
   - onboarding must say plainly that clinicians do this, and push "Invite your team" first.
3. **The sign-up form offers "Insurance Company"** as an organisation type. Insurance is out of scope per CLAUDE.md; remove it or confirm it is wanted.
4. **Platform admins cannot use one-click demo login** (correct security), and real admin login needs MFA. Whoever approves pilot hospitals on Tuesday needs a working admin account with MFA set up **on opescare.com** before then.
5. **Invite mail goes out from `noreply@opescare.cloud`** until the opescare.com mail records are added at Namecheap and `MAIL_FROM_ADDRESS` is switched.

Not yet walked: a clinician's first Health ID lookup and consent request end to end, and the patient approving consent on the phone.

## Clinical journey (agent)

Driven end to end over the real HTTP routes by
`tests/Feature/Pilot/PilotClinicalJourneyTest.php` (passes, 69 assertions).
Only the platform admin and the patient's record are created directly; every
other step is the route the product uses.

| Step | Route | Result |
|---|---|---|
| 1. OpesCare converts the hospital lead | `POST /admin/leads/{lead}/convert` (`LeadConversionService`) | **Works.** Facility + listing created, real invite link issued. |
| 2. Hospital contact accepts invite, signs in | `GET/POST /invite/{token}`, `POST /login` | **Works.** Account is `facility_admin` at the new hospital. |
| 3. Hospital admin invites a doctor | `GET /facility/team`, `POST /facility/team/invites` | **Works.** |
| 4. Doctor accepts, signs in (web + app) | `POST /invite/{token}`, `POST /login`, `POST /api/mobile/auth/provider/login` | **Works.** One posting, so no facility selector; `/facility` opens; sidebar shows "Patient chart" (`/facility/patients`). |
| 5. Health ID lookup, no relationship | `GET /facility/patients?health_id=`, `GET /api/mobile/doctor/patients/lookup` | **Works, nothing leaks.** Web says "No chart is available", chart URL 404s, the refusal is in `access_logs`; the app confirms identity only and the chart API answers 403. |
| 6. Doctor asks for consent | `POST /facility/patients/consent-request` (**new**) | **Was broken on the web: dead end.** The desk had no way to ask; asking existed only in the phone app. Fixed (see below). Request carries the session's facility and the doctor; an unknown Health ID gets the identical answer and writes nothing. Also visible in the app outbox `GET /api/mobile/doctor/consent-requests`. |
| 7. Patient approves on the phone | `GET /api/mobile/consent-requests`, `POST /api/mobile/consent-requests/{id}/approve` | **Works.** Active grant created. |
| 8. Doctor opens the chart | `GET /facility/patients?health_id=` → `/facility/patients/{id}` (summary + encounters tab), `GET /api/mobile/doctor/patients/{id}` | **Works.** |
| 9. Patient sees who read the record | `GET /api/mobile/access-logs` | **Works.** Row names the hospital; purpose `treatment:consent_grant`. |
| 10. Patient revokes | `POST /api/mobile/consents/{id}/revoke` | **Works.** Web chart 404s again, app chart 403. |
| 11. Grant expires | (second ask + approve, then `expires_at` in the past) | **Works.** Chart closes at expiry. |
| 12. Break-glass | `POST /api/mobile/doctor/emergency-access`, `GET .../{grant}/profile`, patient `GET /api/mobile/emergency-access` | **Works.** Profile opens, access log row written, `EmergencyAccessAlertNotification` sent to the patient, the patient sees the override. Break-glass is app-only; there is no web desk button. |

### Fixes made

1. **Web desk could not ask for consent.** Added `POST /facility/patients/consent-request`
   (`portals.chart.consent_request`, `routes/chart.php`, behind `capability:request_access`
   + throttle) and `PatientChartController::requestConsent`, which calls the same
   `ConsentService` with the app's closed purpose/scope vocabulary (grant 24 h, ask
   stands 72 h). Facility from `DeskFacilityContext`, clinician from the session.
   The answer never says whether the Health ID exists. The form appears on the
   lookup page under "No chart is available" (`resources/views/portals/chart/index.blade.php`);
   6 new keys in `lang/{en,fr}/chart.php`.

### Notes (not changed)

- The patient-side consent approval is in the app only; there is no web approve
  screen for patients.
- There is no web outbox of a facility's consent requests; the app has one.
- Test-only trap: the app's provider login runs `Auth::attempt` + `Auth::logout`,
  which in a single test process ends the web session; in production they are
  separate requests.
