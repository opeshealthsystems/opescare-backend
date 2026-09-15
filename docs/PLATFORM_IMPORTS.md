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
