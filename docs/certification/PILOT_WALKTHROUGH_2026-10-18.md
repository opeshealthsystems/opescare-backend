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
