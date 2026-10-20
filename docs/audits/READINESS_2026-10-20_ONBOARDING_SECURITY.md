# Readiness: self-service claiming and onboarding (2026-10-20)

Read-only audit. Code read at `f504b694`; live checks are GET-only curls against https://opescare.com. No tests, migrations or DB access were run.

## Verdict: open claiming now?

**Not yet as self-service. Yes as an assisted pilot.** You can submit, review and approve claims, and lead conversion plus the copy-the-invite-link flow can create a real tenant without email. Five things stand in the way of an open door:

1. Ownership is never verified. A claim is just contact details.
2. A second approval silently hands a listing to someone else.
3. Facility managers do not need MFA by default.
4. Email is dead (null MX record).
5. The privacy notice does not cite Cameroon's 2024 personal-data law.

Fix OB-1 to OB-4 and finish the owner items, then open claiming. Until then, onboard facilities by hand through Leads, then Convert.

## Path checklist

| Step | State | Evidence |
|---|---|---|
| Claim button on a real public profile | OK (live) | `/care-map/facility/0062292a-…` links to `/claim`; the claim page sends anonymous users to `/login` (302) |
| Account creation | Works. Public `/register` sends you to login (302); signup is throttled (`signup` limiter) | `AppServiceProvider.php:486` |
| Ownership / identity verification | **MISSING.** No documents, no phone OTP, no email check. Name, role, email, phone and reason only | `FacilityClaimController.php:47-53` |
| Duplicate claim by the same user | Blocked | `FacilityClaimService.php:80-87` |
| Claim on a listing someone else already holds | Blocked at submit | `FacilityClaimService.php:92-94` |
| Admin review desk | Works (`platform.admin`, MFA for the platform tier) | `routes/web.php:612-623` |
| Approval | **No state or conflict guard** (OB-2) | `FacilityClaimService.php:179-236` |
| Tenant creation and `care_facilities.facility_id` link | Not done by claim approval. It only links when the claimant already holds exactly one posting; otherwise it writes a review audit. Tenant creation happens only through Lead convert | `FacilityClaimService.php:449-494`, `LeadConversionService.php:183,253` |
| First admin login with MFA | **Not enforced for facility roles** (OB-3) | `config/mfa.php:30`, `User.php:61`, `LeadConversionService.php:67-73` |
| Staff invites | Work as a copyable link (no email needed); emailed copies fail while MX is null | `LeadAdminController` convert flashes `invite_link` |
| Onboarding desk / go-live checklist | Works on real tenants (removes the demo scope on purpose) | `OnboardingPortalController.php:44,149` |

## Findings

| ID | Sev | Finding | Evidence | Owner? |
|---|---|---|---|---|
| OB-1 | BLOCKER | **No proof of ownership.** Any logged-in user can claim any unclaimed hospital. The desk reviewer sees only self-declared contact data. There is no document upload, no OTP to the listing's registered phone, and no checklist. Approval then grants the self-service listing editor (public hours, services, phone), which is a misdirection risk for patients. | `FacilityClaimController.php:47-53`, `FacilityClaimService.php:96-107` | Decide the verification policy |
| OB-2 | HIGH | **`approveClaim` has no guards.** It approves rejected or revoked claims, re-approves approved ones, and approves a second user's claim on a listing that is already claimed. `stampClaimed` then overwrites `claimed_by_user_id` (the audit keeps the old value, but nothing blocks it). The submit-time check (`:92`) does not cover two claims that are both pending. | `FacilityClaimService.php:179-187, 290-296` | – |
| OB-3 | HIGH | **Facility managers are not MFA-required.** `facility_admin`, `pharmacy_manager` and `lab_manager` are outside `ALWAYS_MFA_ROLES`, and the env default is `super_admin,admin`. These accounts see patient data and invite staff. I could not check prod's `MFA_REQUIRED_ROLES` from outside. | `config/mfa.php:30`, `app/Models/User.php:61` | Set the env, or code it |
| OB-4 | HIGH | **All email is blocked by the null MX record** (HANDOVER owner item 1). Invites, password reset, claim follow-up and backup alerts (`BACKUP_ALERT_EMAIL`) all depend on it. Invite links can be copied as a workaround; password reset has none. | `docs/HANDOVER.md:89` | **DNS (owner)** |
| OB-5 | HIGH | **Privacy notice cites Law 2010/012, 96/03 and GDPR 2016/679, but not Law No. 2024/017 on personal data protection.** Live `/privacy` has no match for "2024". I also found no facility processor/controller terms (DPA) for onboarded facilities. | curl `https://opescare.com/privacy`, `/terms` | **Legal (owner)** |
| OB-6 | HIGH | **Backups are on the server only.** `BACKUP_S3_ENABLED=false`, and the S3 driver and keys are still owed. Onboarding real facility data with no off-site copy is not acceptable. | `.env.example:85-91`, `HANDOVER.md:112` | **Keys (owner)** |
| OB-7 | MEDIUM | **Prod runs with `APP_ENV=demo`** (per memory: `finder-deploy-env`). Payments are `simulated` on demo hosts, and demo scopes and demo buttons hang off that flag. A real facility on a demo-mode host may see simulated payment success. The `/login` page still ships `.demo-panel` CSS. I did not see a visible demo panel. Verify on the server. | memory `finder-deploy-env.md`, `CLAUDE.md` payments row | Owner decision (switch env) |
| OB-8 | MEDIUM | **No notification when a claim is filed or decided.** No Notification or Mail class mentions claims. The desk depends on an admin polling it, and the claimant only sees status on `/portals/listing/claims`. | grep of `app/Notifications`, `app/Mail`: 0 hits | – |
| OB-9 | MEDIUM | **The session cookie is still `domain=opescare.com`** (live header), not host-only `__Host-`. This is owner item 3. | live `Set-Cookie` on `/login` | **Owner (.env)** |
| OB-10 | MEDIUM | **Claim approval does not create a tenant.** A claimant with no posting gets a listing editor but no dashboard. An admin must separately convert a lead and link it, and the two desks are not connected. That is fine for an assisted flow, but it is not self-service onboarding. | `FacilityClaimService.php:461-488` | – |
| OB-11 | LOW | **The API claim endpoint is weaker than the web route.** It has no `throttle:portal`, makes contact fields optional with defaults, and echoes `$e->getMessage()` as `code` (with a generic message for every error). | `routes/api.php:299-302`, `CareMapController.php:282-311` | – |
| OB-12 | LOW | **Spam control on claims** is only `throttle:portal` (120/min per user, 20/min per IP) plus one live claim per user per listing. One account can file claims on hundreds of listings. | `AppServiceProvider.php:529-534` | – |
| OB-13 | LOW | **Duplicate `X-Content-Type-Options` header** (sent by both nginx and the app); harmless. | live headers | – |
| OB-14 | LOW | **Git history scrub not finished** (`refs/original` / reflog). | `HANDOVER.md:93` | Owner |

## What is good (verified)

- **Live headers.** HSTS, nonce-only `script-src`, `frame-ancestors 'none'`, `X-Frame-Options: DENY`, and secure, httponly, samesite=lax cookies.
- **Robots, legal pages and 404s.** `robots.txt` is served. `/privacy` and `/terms` return 200, and unknown paths return a clean 404.
- **Facility identity for listing edits** comes only from the approved claim (`FacilityClaimService.php:120-140`), never from the request.
- **Manual linking is guarded.** `linkListing` refuses ids it did not offer, plus already-linked or already-listed listings (`AdminDirectoryReviewController.php:186-213`).
- **Platform-tier MFA** is always required, and demo users are refused at the platform tier (`SECURITY_REMEDIATION.md` #1, #3).
- **The onboarding desk** sees real tenants on purpose.

## Not verified (need server access)

- Prod `.env`: `APP_ENV`, `MFA_REQUIRED_ROLES`, SMS driver and keys (`VONAGE_SMS_FROM` is empty in the example).
- Whether `finder-queue.service` is running.
- Log channel and alerting.
- Upload MIME and size limits on listing and photo uploads (this audit did not cover them).

## Owner actions

1. Delete the null MX record at Hostinger (OB-4).
2. Add S3 backup keys and enable them (OB-6).
3. Legal: cite Law 2024/017 and add facility processing terms or a DPA (OB-5).
4. Make the session cookie host-only (OB-9).
5. Decide whether prod leaves `APP_ENV=demo` (OB-7).
6. Choose the ownership-proof policy, for example registry licence plus an OTP to the listed phone (OB-1).
7. Finish the git scrub (OB-14).

## Engineering before opening

Do OB-1 (evidence or OTP), OB-2 (status and conflict guards in `approveClaim`), OB-3 (add facility manager roles to the MFA set) and OB-8 (desk and claimant notifications) before opening claiming. OB-11 and OB-12 are hardening work.
