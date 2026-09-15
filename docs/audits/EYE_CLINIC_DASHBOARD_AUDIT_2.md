# Eye clinic dashboard — audit 2 (2026-09-15)

Scope: `eye_clinic`. There are 23 listings in `care_facilities` locally, and 23 on prod
when filtered by type. These are ophthalmologists and optometrists: consultations,
referral for cataract surgery, glasses prescriptions. Logins: `demo.eye.admin`
(clinic_admin) and `demo.eye.doctor` (specialist) at "Clinique Ophtalmologique Demo".
Yardstick: `docs/DASHBOARD_SCOPE.md`. Prod was checked GET-only on
finder.opescare.cloud (form login, cookie jar). Findings that apply to every
facility are listed once under "Inherited" and were not investigated again.

## PLAN (ranked) and status

| # | Finding | Owner | Status |
|---|---|---|---|
| E-1 | **Patients cannot find an eye clinic by the word they type.** Prod `/care-map?query=`: `ophthalmologist` 0, `optometrist` 0, `eye doctor` 0. `ophtalmologie` returns 1, by name match only. `Eye` returns 8 against 24 for `eye`, because the `facility_type` LIKE is case-sensitive. `CareMapSearchService::TYPE_SYNONYMS` only holds `dental`. | this pass + dental auditor's file | **FIXED (needs one shared line)**. New class `App\Support\EyeCare\EyeCareSearchTerms` holds the EN/FR words for the kind of place or practitioner. It has no condition names and no opticians. The wiring line is below. Tests are green. The end-to-end search test is marked *incomplete* until that line lands, and then it runs. |
| E-2 | **Eye procedures are in the dictionary but cannot be published.** ICHI has cataract surgery: `BBF.JK.AA` Extraction of lens, `BBF.JK.AE` Phacoemulsification of lens, `BBF.ML.AA` Insertion of prosthetic lens. It also has 168 eye-chapter codes (`B[A-D]`). HL7 service types include `217` Ophthalmology, `62` Optometry and `395` Optometrist. The admin CAN add these with a whole-franc price (prod browse: "phacoemulsification" 1, "ophthalm" 25, "cataract" 20). But only LOINC items are projected, so ICHI and HL7 items never reach the public profile. | inherited I-3 (imaging) | OPEN — owner decision, same as imaging I-3. |
| E-3 | **The basic eye examinations are not in the dictionary.** Local counts across LOINC and ICHI are 0 for "visual acuity", "intraocular pressure", "refraction", "tonometry", "keratometry" and "slit lamp". On prod, browse for "refraction" returns 3, and those are ICD-10 refraction disorders. The eye terms that LOINC does have are OCT retinal nerve fibre layer thickness and retinal photography: 57 matching rows out of 19,314. | owner decision (terminology import) | OPEN — no codes invented. The services an eye clinic prices most often are a consultation with acuity, an IOP check, and refraction for glasses. Until an eye-exam LOINC subset is imported, they can only be published as the HL7 service type or as an ICHI assessment (`BBE.AA.AH` Assessment of anterior chamber of eye), which is also blocked by E-2. |
| E-4 | **The specialist sidebar shows Blood inventory and Blood requests** to an eye doctor at an eye clinic. Both pages return 200 on prod and show an honest empty state ("Aucun inventaire sanguin"). Every doctor role gets `blood_inventory` whatever the facility type. Triage still makes sense, because an eye emergency still needs priority. There is no lab link, which is correct. | lead (sidebar/capabilities) — owner decision | OPEN — diff below. |
| E-5 | **Referral to a hospital for cataract surgery.** The local `specialist_doctor` sidebar already has `portals.referrals.index` under `@capability('clinical_chart')`, and the specialist role holds that capability. On prod, `/facility/referrals` returns 404 because it is not deployed yet. | referral desk builder | OK locally; ships with the referral desk. |
| E-6 | **A glasses (optical) prescription cannot be recorded.** The prescriber desk handles medicines only (ATC/formulary). Decision in two parts. A *structured* spectacle prescription (sphere/cylinder/axis/add/PD per eye) is **OUT** of scope: it is the output of refraction, and ophthalmic examination data and refraction charts are out. The medicine pipeline (RX code → pharmacy dispensing) does not apply to spectacles either. A signed glasses prescription kept as a *document* is **IN** scope ("documents/PDF sheets"): a PDF attached to the patient's documents that any optician can read. That needs a staff screen to attach a document to a patient, and no facility has one. | inherited I-6 (imaging) | OPEN — owner decision; not built. |
| E-7 | Two ophthalmology centres in the directory have type `diagnostic_center`: "Centre Ophtalmologique de Yaoundé" and "Centre Ophtalmologique de Douala". The `eye_clinic` type filter misses them, and so will E-1. | owner decision (directory data) | OPEN — reclassify them in directory review; not changed here. |
| E-8 | The French triage empty state has no accents: "Aucune evaluation de triage n’a encore ete enregistree dans cet etablissement." (prod, doctor, `/facility/triage`). The i18n audit passes because the text is valid UTF-8. | triage owner (lang triage) | OPEN — cosmetic. |

## Every link and reachable page (prod, GET, 2026-09-15)

| Login | Page | Status |
|---|---|---|
| admin | login → `/admin` | 302 → 200 |
| admin | /facility/team/overview (+ /departments, /invoices, /reports, /wards), /facility/settings, /facility/team, /facility/catalogue, /facility/queue, /notifications, /professional/profile, /professional/facilities, /help | all 200 |
| admin | /facility/prescriptions, /facility/triage | 302 → /admin (not linked; inherited C-6 silent redirect) |
| doctor | login → `/facility` | 302 → 200 |
| doctor | /facility/patients, /facility/triage, /facility/queue, /facility/appointments, /facility/prescriptions, /facility/inventory/blood, /facility/blood-requests, /facility/messages, /facility/search, /notifications, /professional/profile, /professional/facilities, /help | all 200 |
| doctor | /facility/catalogue | 403 (correct: cannot edit prices) |
| doctor | /facility/team/overview | 302 → /facility |
| doctor | /facility/referrals, /facility/chart | 404 on prod (exist locally, not deployed) |
| public | /care-map/facility/01a0a09a-6656-7006-8e32-2ca05b97ea37 (demo listing) | 200. Shows name, type "Eye Clinic", phone, address, hours (Mon–Fri 08–17, Sat 08–13), Book appointment, and an honest "not reported" availability. No services: the catalogue has 0 items, and ICHI would not publish anyway (E-2). No photo. |

Neither sidebar has a dead link. None of the sidebar targets is a URL-only page.

## Workflow end to end
1. **Publish services:** admin → Nos services → browse → add ICHI phaco or HL7 Ophthalmology with a whole-franc price. It saves but never appears publicly (E-2). Refraction, acuity and IOP have no code (E-3).
2. **Be found:** by type filter, yes (23); by word, mostly not (E-1, fixed pending wiring).
3. **Book / queue:** appointment request and queue desk are reachable (inherited clinic flow).
4. **Consult:** chart via `/facility/patients`, consent-gated (inherited). Recording eye exam findings is out of scope.
5. **Prescribe drops / medicines:** the prescriber desk works. ATC `S01` ophthalmologicals are present (12 eye-related ATC groups).
6. **Glasses:** no screen (E-6).
7. **Refer for surgery:** the referral desk link is present locally (E-5).

## Security / data
Nothing here is specific to eye clinics. CLINIC audit 2 covered `facility_id` from input, cross-facility 404s, unbounded lists and immutability (C-1 fixed), and those findings apply unchanged. The demo eye tenant does not exist in the local dev DB (0 `facilities` rows match), so cross-facility checks rely on the clinic fixtures already in the suite.

## IN / OUT of scope
- IN: publishing eye services with prices (E-2/E-3), being searchable (E-1), referrals out (E-5), a glasses prescription as a PDF document (E-6), queue/appointments/team.
- OUT: ophthalmic examination records, refraction charts, structured spectacle prescriptions, OCT/fundus image storage or viewing, optical frame/lens inventory beyond a simple product list, surgical records.

## Inherited (listed once, not re-investigated)
- Admin overview shows Laboratory and Beds tiles, plus Departments and "Services et lits" links, to a one-room eye clinic (C-5 / HC-4 / I-7). Add `eye_clinic` to that type list.
- ICHI and other non-LOINC catalogue items are never projected (I-3) → E-2.
- No staff screen to attach a document to a patient (I-6) → E-6.
- A role refusal is a silent 302, not a 403 that names the reason (C-6).
- `?: 'English'` fallbacks after `__()` in sidebars (C-7 pattern; also in `specialist_doctor.blade.php`).
- Two facility registers: the demo listing must stay linked to its tenant.

## Shared-file changes for the lead

1. `app/Modules/CareMap/Services/CareMapSearchService.php` (dental auditor's file), for E-1:
```diff
     public const TYPE_SYNONYMS = [
         'dental' => ['dental', 'dentist', /* unchanged */],
+        'eye_clinic' => \App\Support\EyeCare\EyeCareSearchTerms::WORDS,
     ];
```
After this, `--filter=EyeClinicFinderTest` runs the end-to-end search test instead of marking it incomplete.

2. `resources/views/partials/sidebars/specialist_doctor.blade.php`, for E-4 (owner decision). This hides links and removes nothing; the routes stay reachable and capability-gated:
```diff
     @capability('blood_inventory')
+    @unless (in_array($currentFacilityType ?? null, ['eye_clinic', 'dental'], true))
     <a href="{{ route('portals.staff.inventory.blood') }}" ...>...</a>
     <a href="{{ route('portals.staff.blood_requests.index') }}" ...>...</a>
+    @endunless
     @endcapability
```
`$currentFacilityType` stands for the active posting's `facilities.type`. Take it from whatever the portal layout already resolves for the facility context. I did not find an existing variable, so the layout needs to pass one.

## Removals for the owner (not done)
None.
