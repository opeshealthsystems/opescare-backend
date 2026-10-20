# Readiness certification — Hospitals, Clinics, Health centres (2026-10-20)

Read-only audit (no tests run, no DB touched; public GETs on opescare.com only). Yardstick: `docs/DASHBOARD_SCOPE.md`.
Scope of verification was a spot-check of the riskiest paths, not a full line-by-line walk; items not re-checked are marked UNVERIFIED and rely on `docs/audits/*_DASHBOARD_AUDIT_2.md` (2026-09-15).

## Verdicts
| Type | Verdict |
|---|---|
| Hospital | Ready with conditions |
| Clinic (incl. dental) | Ready with conditions |
| Health centre (incl. maternity) | Ready with conditions (nurse/midwife protocol decisions open) |

Conditions common to all: (1) the null MX record at Hostinger must be removed or staff invites / notification emails never arrive; (2) an operator must confirm that an approved claim ends with a linked tenant `facilities` row + a facility-admin staff profile (see HC-2).

## Journey checklist
| # | Journey | Result | Evidence |
|---|---|---|---|
| 1 | Discover listing, claim | PASS | `routes/web.php:536-539` claim routes; `/care-map` 200 live |
| 1 | Admin approval of claim | PASS (UNVERIFIED end state) | `FacilityClaimService.php:181-207` approves and touches listing/registry; tenant provisioning not traced |
| 1 | Departments / staff | PASS (limited) | `routes/facility_admin.php` departments = grouping of free-text `staff_profiles.department`; no wards model |
| 1 | Clinical roles | PASS | `config/staff_capabilities.php:20,23,31` nurse family incl. midwife, front desk, billing incl. refund_approver; no `dentist`/`radiographer` role |
| 2 | Appointments, queue, triage, check-in | PASS (prior audit) | hospital audit fixes; `tests/Feature/Triage` |
| 3 | Chart gate, break-glass, access log | PASS (prior audit) | `ClinicalRelationshipGate`; `routes/mobile_emergency_access.php`, `routes/web.php:866` review desk |
| 3 | Immunisation desk | PASS | `routes/immunisation_desk.php:25,42` capability:clinical_chart; facility from `DeskFacilityContext` (`ImmunisationDeskController.php:279-287`); `tests/Feature/Immunisation/*` |
| 4 | Invoices, payments, refunds | PASS | `routes/billing.php:54-63`; second signature `capability:refund_approve`; self-approval refused `BillingDeskService.php:383`; refund scoped to session facility `BillingDeskController.php:345` |
| 4 | Facility admin read-only invoices | PASS | `routes/facility_admin.php:52-70`, no write routes |
| 4 | DHIS2 aggregates | PASS (admin only) | CLAUDE.md, `routes/imports_dhis2.php` |
| 5 | Facility isolation | PASS on sampled desks | facility from context services, never request |
| 5 | Email notifications | FAIL (infra) | null MX (owner) |
| 5 | FR completeness, mobile width | UNVERIFIED this pass | run `php scripts/i18n-audit.php` |

## Findings
| ID | Sev | Finding | Evidence | Status |
|---|---|---|---|---|
| HC-1 | BLOCKER (ops) | Null MX at Hostinger blocks all outbound email: staff invites, lead-conversion invites, notifications | `docs/FACILITY_READINESS.md` cross-cutting; memory finder-mail-deliverability | Owner action |
| HC-2 | HIGH | Two facility registers (`care_facilities` vs `facilities`); claim approval path not proven to create/link the tenant facility a desk needs — a newly claimed hospital may land on desks with no facility context (403 `no_facility_context`) | `FacilityClaimService.php:181-207`; `FacilitySettingsService.php:24` notes only 468/482 linked | UNVERIFIED — trace or test once |
| HC-3 | MEDIUM | Departments/wards are free text; no ward/bed model | `routes/facility_admin.php` comment above departments route | By design (scope) |
| HC-4 | MEDIUM | Nurse/midwife prescribing and nurse-recorded malaria RDT undecided for health centres | `FACILITY_READINESS.md` Health centre row | Owner decision |
| HC-5 | MEDIUM | No `dentist` role; dental mobile search ignores type words | `FACILITY_READINESS.md` Dental row; grep of `staff_capabilities.php` | Open |
| HC-6 | MEDIUM | Maternity: no antenatal/postnatal presets; midwife newborn Health ID at desk undecided | `FACILITY_READINESS.md` Maternity row | Owner decision |
| HC-7 | LOW | Admin-tier roles opening a clinical desk get redirect, not named 403 | readiness cross-cutting | Design call |
| HC-8 | LOW | Public profile never shows facility photo; directory search not paginated in SQL | readiness cross-cutting | Open |
| HC-9 | LOW | Waitlist caps at 200; admin landing page undecided | Hospital row | Open |

No privilege-escalation or cross-facility leak found in the sampled paths (billing, refunds, immunisation desk, facility-admin invoices).

## Owner decisions
1. Delete the null MX record (unblocks HC-1).
2. Confirm onboarding procedure for a claimed facility links `care_facilities.facility_id` and seeds a facility admin (HC-2).
3. Nurse/midwife prescribing per national protocol; midwife newborn Health ID (HC-4, HC-6).
4. Add `dentist` role? (HC-5). 5. Admin landing page (HC-9).
