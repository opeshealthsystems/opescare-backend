# Platform imports into the Finder (2026-09-15)

Source: `C:\laragon\www\opescare\apps\api-laravel` (read-only). Rule: copy the
platform code, then adapt only where the Finder's rules require it — never
rewrite from scratch. All tables and models already exist in the Finder (fork).

| Group | Route file | Imported from the platform |
|---|---|---|
| A — patient file attachments + radiology report release | routes/imports_files.php | Modules/FileStorage/Services/FileStorageService, MedicalId/FileStorageController, views portals/staff/files/*, Api/V1/RadiologyReportController (+ existing RadiologyReportService) |
| B — staff tasks, support tickets, broadcasts | routes/imports_tasks.php | Modules/Tasks/*, TaskInboxController, AdminTaskController, views portals/{staff,admin}/tasks; Modules/Support/Services/*; Modules/Broadcasts/*, AdminBroadcastController, view portals/admin/broadcasts |
| C — drug interactions, antenatal presets, KPIs + exports | routes/imports_clinical.php | Modules/ClinicalDecisionSupport/*, Api/V1/ClinicalDecisionSupportController; Modules/Maternity/Services/*; Modules/Analytics/Services/*, KpiDashboardController, views portals/admin/kpi/* |
| D — facility readiness, onboarding portal, security operations | routes/imports_ops.php | Modules/FacilityReadiness/Services/*, OnboardingPortalController, views portals/admin/onboarding/*; Modules/SecurityOperations/Services/* |
| E — DHIS2 aggregate reporting to MINSANTE | routes/imports_dhis2.php | Services/Integration/Dhis2Service, Services/Interoperability/Dhis2PushService, Jobs/PushPublicHealthToDhis2Job, Console/Commands/PushDhis2ReportCommand, config/dhis2.php, Modules/PublicHealth/Services/* |

Adaptation rules (every group): facility from session only; cross-facility 404;
void instead of hard delete; EN/FR 1:1; Lucide; route + nav link + view + test.
Group E is aggregate-only (no patient-level data), draft → review → push.

Not importable (absent from the platform): imaging request desk, dentist and
radiographer roles, diagnostic packages, nurse prescribing protocols.

## Status (2026-09-15)

| Group | State | Notes |
|---|---|---|
| A files + radiology | imported | 5 models copied (only the tables existed); hard delete → void; signed downloads with access log; released files appear in patient documents; added the missing RadiologyReportDistributed event so release notifies the patient. Lab roles lack clinical_chart (decision). |
| B tasks/support/broadcasts | imported | new StaffSupportController; broadcasts target facilities/roles and track acknowledgement; KnowledgeBaseService and TicketAssignmentService copied but unwired (schema mismatch). |
| C interactions/antenatal/KPI | imported | platform interaction data is only 4 hard-coded pairs — imported as-is, nothing invented; warnings never block; KPI export aggregates only; insurance metric dropped. |
| D readiness/onboarding/secops | imported | readiness uses the Finder's six real go-live checks; reconciled with existing security pages (nothing duplicated); ApiAbuseDetectionService unwired (no request metrics). |
| E DHIS2 | imported | aggregate only; draft → approve → queued push; idempotent per facility/period/element; patient-level and invented elements dropped; needs MINSANTE credentials, org unit ids, dataset and data element ids. |
