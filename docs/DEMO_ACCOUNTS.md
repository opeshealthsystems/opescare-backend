# Demo accounts — OpesCare Finder

Every demo login uses the password **`demo1234`** and is stamped `is_demo = true`.
All of them appear as one-click buttons on the login page when `OPESCARE_DEMO_MODE=true`
(tabs: Staff · Facility · Facility types · Patient · Admin · Developer).

Seeded by `database/seeders/FinderDemoSeeder.php` (which calls
`DemoFacilityTypesSeeder`, `DemoNotificationsSeeder`) plus `DemoUsersSeeder` /
`DemoFacilityAssignmentsSeeder` / `DemoPatientStorySeeder` for the clinicians and patients.

Re-seed on the server (idempotent, safe to repeat):

```bash
sudo -u finder /usr/bin/php8.3 artisan db:seed --class=FinderDemoSeeder --force
```

## One demo tenant per facility type

Each type has ONE tenant row (`facilities`), ONE public listing (`care_facilities`,
linked through `care_facilities.facility_id`) with opening hours, and its own logins.
`facilities.type` uses the same word as `care_facilities.facility_type` (the tenant
column is free text and display-only).

| Type | Tenant (`facilities.name`) | City | Listing |
|---|---|---|---|
| hospital | Hôpital Central de Yaoundé (pre-existing, real) | Yaoundé | imported row, now linked |
| clinic | Clinique Demo Bonapriso | Douala | demo |
| health_center | Centre de Santé Demo Nkolbisson | Yaoundé | demo |
| laboratory | Laboratoire Demo Akwa | Douala | demo |
| pharmacy | Pharmacy ESSEC (pre-existing) | Douala | existing |
| dental | Cabinet Dentaire Demo Bastos | Yaoundé | demo |
| imaging_center | Centre d'Imagerie Demo Bonanjo | Douala | demo |
| eye_clinic | Clinique Ophtalmologique Demo | Yaoundé | demo |
| diagnostic_center | Centre de Diagnostic Demo | Douala | demo |
| maternity | Maternité Demo Essos | Yaoundé | demo |
| blood_bank | Banque de Sang Demo Messa | Yaoundé | demo (linked, so stock entered on `/facility/inventory/blood` reaches the Blood Finder; no stock is seeded) |

## Every login

| Email | Role | Tenant | Type | Lands on |
|---|---|---|---|---|
| demo.admin@opescare.test | platform_admin | — | — | `/admin` |
| demo.developer@opescare.test | developer | — | — | `/developer` |
| demo.facility.admin@opescare.test | facility_admin | Hôpital Central de Yaoundé | hospital | `/facility/team/overview` |
| demo.hospital.admin@opescare.test | hospital_admin | Hôpital Central de Yaoundé | hospital | `/facility/team/overview` |
| demo.facility.ceo@opescare.test | facility_ceo | Hôpital Central de Yaoundé | hospital | `/facility/team` |
| demo.receptionist@opescare.test | receptionist | Hôpital Central de Yaoundé | hospital | `/facility` |
| demo.doctor@opescare.test | doctor | demo hospital (DemoFacilitiesSeeder) | hospital | `/facility` |
| demo.nurse@opescare.test | nurse | demo hospital | hospital | `/facility` |
| demo.specialist@opescare.test | specialist | demo hospital | hospital | `/facility` |
| demo.labtech@opescare.test | labtech | demo hospital | hospital | `/facility/lab` |
| demo.clinic.admin@opescare.test | clinic_admin | Clinique Demo Bonapriso | clinic | `/facility/team/overview` |
| demo.clinic.doctor@opescare.test | doctor | Clinique Demo Bonapriso | clinic | `/facility` |
| demo.clinic.reception@opescare.test | receptionist | Clinique Demo Bonapriso | clinic | `/facility` |
| demo.clinic.nurse@opescare.test | nurse | Clinique Demo Bonapriso | clinic | `/facility` |
| demo.healthcenter.admin@opescare.test | facility_admin | Centre de Santé Demo Nkolbisson | health_center | `/facility/team/overview` |
| demo.healthcenter.nurse@opescare.test | nurse | Centre de Santé Demo Nkolbisson | health_center | `/facility` |
| demo.healthcenter.midwife@opescare.test | midwife | Centre de Santé Demo Nkolbisson | health_center | `/facility` |
| demo.lab.manager@opescare.test | lab_manager | Laboratoire Demo Akwa | laboratory | `/facility/lab/dashboard` |
| demo.lab.scientist@opescare.test | lab_scientist | Laboratoire Demo Akwa | laboratory | `/facility/lab` |
| demo.lab.collection@opescare.test | sample_collection | Laboratoire Demo Akwa | laboratory | `/facility/lab` |
| demo.pharmacist@opescare.test | pharmacist | Pharmacy ESSEC | pharmacy | `/pharmacy` |
| demo.pharmacy.manager@opescare.test | pharmacy_manager | Pharmacy ESSEC | pharmacy | `/pharmacy` |
| demo.pharmacy.stock@opescare.test | medicine_stock | Pharmacy ESSEC | pharmacy | `/pharmacy` |
| demo.dental.admin@opescare.test | facility_admin | Cabinet Dentaire Demo Bastos | dental | `/facility/team/overview` |
| demo.dental.doctor@opescare.test | doctor | Cabinet Dentaire Demo Bastos | dental | `/facility` |
| demo.imaging.admin@opescare.test | facility_admin | Centre d'Imagerie Demo Bonanjo | imaging_center | `/facility/team/overview` |
| demo.imaging.tech@opescare.test | imaging_tech | Centre d'Imagerie Demo Bonanjo | imaging_center | `/facility` (imaging desk `/facility/imaging`) |
| demo.eye.admin@opescare.test | clinic_admin | Clinique Ophtalmologique Demo | eye_clinic | `/facility/team/overview` |
| demo.eye.doctor@opescare.test | specialist | Clinique Ophtalmologique Demo | eye_clinic | `/facility` |
| demo.diagnostic.admin@opescare.test | facility_admin | Centre de Diagnostic Demo | diagnostic_center | `/facility/team/overview` |
| demo.diagnostic.lab@opescare.test | lab_manager | Centre de Diagnostic Demo | diagnostic_center | `/facility/lab/dashboard` |
| demo.maternity.admin@opescare.test | facility_admin | Maternité Demo Essos | maternity | `/facility/team/overview` |
| demo.maternity.midwife@opescare.test | midwife | Maternité Demo Essos | maternity | `/facility` |
| demo.bloodbank.manager@opescare.test | blood_bank_manager | Banque de Sang Demo Messa | blood_bank | `/facility` (blood stock `/facility/inventory/blood`, requests `/facility/blood-requests`) |
| demo.bloodbank.officer@opescare.test | blood_bank_officer | Banque de Sang Demo Messa | blood_bank | `/facility` |
| demo.patient@opescare.test | patient (provisional ID) | — | — | `/patient` |
| demo.reviewer@opescare.test | patient (verified ID) | — | — | `/patient` |
| demo.boy@opescare.test | patient (child) | — | — | `/patient` |
| demo.girl@opescare.test | patient (child) | — | — | `/patient` |
| demo.guardian@opescare.test | guardian | — | — | `/patient` |
| demo.grandmother@opescare.test | patient (older person) | — | — | `/patient` |

Notes
- Landing paths are the portal buckets in `EnsurePortalAccess::portalForRole`; the
  admin/manager rows are asserted with a 200 in `tests/Feature/Demo/DemoFacilityTypesSeederTest.php`.
- `care_facilities` has no `is_demo` column; demo listings carry `source_system = 'demo'`.
- The seeder never takes over a listing that already belongs to another tenant.
