# OpesCare facility readiness — certificate, 2026-10-20

Live build: `e87b5f12` on opescare.com. Full test suite green before deploy
(3,395 tests; one flaky triage date test re-ran green twice), i18n audit 0 errors,
every Blade compiles. Detailed audits: `READINESS_2026-10-20_{PHARMACY,HOSPITAL_CLINIC,DIAGNOSTICS,ONBOARDING_SECURITY}.md`.

## Verdict per facility type

| Type | Verdict | Conditions left |
|---|---|---|
| Pharmacy | **Ready** | Dispensing does not yet decrement stock (units vs packs, owner decision); 2-decimal prices on /pharmacy/stock |
| Hospital | **Ready** | Wards are free text; admin landing page choice |
| Clinic (incl. dental) | **Ready** | No `dentist` role yet |
| Health centre / maternity | **Ready with conditions** | Nurse/midwife prescribing and newborn Health ID decisions open |
| Laboratory | **Ready** | Orders only from the lab's own facility |
| Imaging centre | **Ready (walk-in)** | No cross-facility orders yet |
| Diagnostic centre | **Ready** | Same as lab + imaging |
| Blood bank | **Ready** (fixed today) | Stock goes public only once the listing is linked; the desk now says so |

## Fixed and live today
- **Claiming:** licence upload (private, type-checked), SMS code to the listing's own phone, guarded approval (no double, rejected, revoked or already-linked approvals), approval creates and links the facility account, 7-day activation link that requires MFA, claimant notified at each step. 0 old claims on prod needed re-filing.
- **MFA** required for every facility-manager role, from code (no env change).
- **Payments:** "Pay at the facility" until a live MoMo/Orange provider exists; cash at the desk unchanged; app-config exposes `payments.online_available`.
- **Blood banks:** facility admins and new blood-bank roles manage stock and requests; demo tenant and accounts.
- **Imaging:** ordering clinician notified when a report is sent.

## Open for claiming?
**Yes, with admin review.** Every claim lands on `/admin/care-map/review` with the document and phone-verification status. Until an SMS provider is configured, codes are not sent and the admin phones the facility before approving.

## Owner actions (cannot be done in code)
1. SMS provider: `TWILIO_SID`, `TWILIO_TOKEN`, `TWILIO_FROM` + `composer require twilio/sdk` (or another provider).
2. MTN MoMo / Orange Money merchant keys, when online payment should go live.
3. S3 keys for off-server backups (`BACKUP_S3_ENABLED=false` today).
4. Legal review: privacy policy still cites Law 2010/012 — update for Law 2024/017 and add facility data-processing terms.
5. Send one real email from prod to confirm outbound mail (MX records now resolve).
6. Session cookie `SESSION_DOMAIN=opescare.com` → host-only (optional hardening).
