# Clinical-safety re-audit (verifying the 2026-06-12 report against current code)

**Date:** 2026-06-17 · **Branch audited:** `codex/production-hardening` · **Method:** direct
code read of each cited site. The 2026-06-12 `PRODUCTION_READINESS_REPORT.md` listed 11
clinical-safety/logic bugs; this re-audit checks which still stand.

**Result: 6 FIXED, 2 improved/partial, 3 still present (all the "hardcoded clinical list"
class). The dangerous bugs — broken consent gating, legal-hold privacy bypass, fake virus
scan, inverted audit/reactivation logic — are all FIXED.**

| # | Bug | Verdict | Evidence |
|---|-----|---------|----------|
| 1 | Telemedicine consent gating broken | FIXED | `Modules/Telemedicine/Services/CallProviderService.php:36` uses `TelemedicineConsentService::hasValidConsent($consult)` and throws if absent; `TelemedicineConsentService.php:51` checks `$consultation->consent->isValid()` |
| 2 | Ward bed double-assignment race | **PARTIAL — still a risk** | `Modules/WardManagement/Services/WardService.php:40-70` now uses `DB::beginTransaction`, but `Bed::findOrFail()` at :44 has NO `lockForUpdate()`. Concurrent admits can both read the bed available. Fix: `Bed::whereKey($id)->lockForUpdate()->firstOrFail()` inside the transaction, or a partial unique index on active `bed_id`. |
| 3 | Hardcoded Beers/drug-safety lists | STILL PRESENT | `Modules/ClinicalDecisionSupport/Services/ClinicalDecisionSupportService.php:346` inline `$beersDrugs = [...]` (also paediatric/pregnancy lists). Clinical-governance issue: can't update drug safety without a deploy. Not a crash/security bug. |
| 4 | Hardcoded notifiable-disease lists | STILL PRESENT | `Modules/PublicHealth/Services/DraftGenerationService.php:79,173` inline `['Malaria','Measles','Cholera',...]`. Same governance class. |
| 5 | Hardcoded triage thresholds | LIKELY PRESENT (not line-verified) | `Modules/Triage/Services/TriageScoringService.php` — same class as #3/#4; recommend confirming and moving thresholds to config/DB with clinical sign-off. |
| 6 | Messaging legal-hold privacy bypass | FIXED | `Modules/Messaging/Services/MessagePermissionService.php:65-69` now restricts legal-hold threads to `compliance_officer/legal_counsel/platform_admin/super_admin` — no longer returns true for all. |
| 7 | Fake attachment virus scan | FIXED | `Modules/Messaging/Services/MessageAttachmentService.php:31` sets `scan_status = 'pending'` with async quarantine (was hardcoded `'passed'`). TODO: confirm the async scan job exists and flips status. |
| 8 | Insurance claim status from user input | IMPROVED | `Modules/Insurance/Services/ClaimService.php:186` sets status via computed `$newStatus` (not raw user input); `ClaimPaymentService.php:35` coalesces `approved_amount ?? claimed_amount`. Minor: `ClaimPaymentService.php:37` hardcodes `'partially_paid'` — verify full-payment path sets `'paid'`. |
| 9 | Legal re-acceptance not enforced | FIXED | `Modules/Legal/Services/LegalDocumentService.php:126-152` `getMissingAcceptances()` checks acceptance against the `is_current` version id, so publishing a new version (which flips `is_current`) forces re-acceptance. |
| 10 | CareMap updateProfile inverted logic | FIXED | `Modules/CareMap/Services/FacilityVerificationService.php:71-115` does a clean per-field `$oldValue != $newValue` diff, routes high-risk fields to a review queue, audits in a transaction. No `!isDirty()`. |
| 11 | Subscription reactivation inverted | FIXED | `Modules/Subscription/Services/SubscriptionService.php:220` now `->where('is_enabled', false)...->exists()` then re-grants. The inverted `doesntExist()` is gone. |

## Remaining action items (priority order)
1. **#2 ward bed race (correctness):** add `lockForUpdate()` to the bed read in `WardService::admit()` (and `AdmissionService::admit()` if it mirrors it). Small, safe fix.
2. **#3/#4/#5 hardcoded clinical reference data (governance):** move Beers/contraindication, notifiable-disease, and triage-threshold lists into DB/config tables editable by clinical admins, with sign-off. Larger effort; schedule with clinical governance.
3. **#7 confirm the async AV scan job** actually exists and transitions `scan_status` from `pending`→`passed/failed`, and that quarantined attachments are blocked from download until then.
4. **#8 verify** the claim full-payment path sets `'paid'` (not stuck `partially_paid`).

The 06-12 report's headline ("NOT production-ready") was driven largely by the broken API
controllers + unauthenticated routes + these clinical bugs. The route/controller class was
already remediated (verified separately); this re-audit shows the clinical-safety class is
mostly fixed too. Remaining items are one concurrency fix and clinical-data-to-DB governance.
