# Master demo data — shared brief

Every demo seeder writes **one coherent patient story**, not scattered rows. A
reviewer opening the app should be able to follow a single plausible history
across every screen. Random data makes each screen look populated and the app
look incoherent.

## The patient

| | |
|---|---|
| Name | Demo Patient One |
| Patient id | `00000000-0000-0000-0000-300000000001` |
| Health ID | `OC-DEMO-PAT-0001` |
| Sex / DOB | female · 1992-04-14 |
| Blood group | **O+** |
| Home | Bastos, Yaoundé (Centre) |
| Emergency contact | Marie Dupont · Spouse · +237 699 111 222 |

## Her story — every seeder must fit this

She is **34**, lives in Yaoundé, and is managed for two chronic conditions at
**Hôpital Central de Yaoundé**, with one episode of care in Douala.

- **2023-02** — diagnosed **Type 2 diabetes** at Hôpital Central de Yaoundé.
  On Metformin since.
- **2024-06** — diagnosed **hypertension**. Amlodipine added.
- **Allergies** — Penicillin (severe, anaphylaxis) and Peanuts (moderate).
  These are clinically load-bearing: a penicillin allergy must be visible
  anywhere medication is shown.
- **Routine** — reviewed roughly every 3 months. Last review recent, next one
  upcoming.
- Fully immunised as an adult; had a **COVID-19 booster** and **Tetanus**.

Everything seeded should be consistent with that: labs reflect diabetes and
blood pressure monitoring, prescriptions match the two conditions, the care
plan targets glycaemic control, messages are with her actual care team.

## Real entities only — never invent

The database already holds **897 real Cameroonian facilities** and real staff.
Faker-generated names ("Corwin, Parker and Flatley Clinic", "Stanton,
Altenwerth and Feil Insurance") were found polluting patient-facing screens and
removed. **Do not reintroduce that class of data.**

Resolve these by query, never hardcode a name you have not confirmed exists:

| Need | Use |
|---|---|
| Her main hospital | `care_facilities` where `facility_name = 'Hôpital Central de Yaoundé'` |
| A Douala episode | `Hôpital Laquintinie de Douala` |
| A laboratory | `Centre Pasteur du Cameroun — Yaoundé` |
| A pharmacy | any `facility_type = 'pharmacy'` with a non-null `latitude` |
| Her clinician | `users` joined to `roles` where role is `doctor` — Dr. Amara Diallo, `00000000-0000-0000-0000-200000000001` |
| A specialist | Dr. Ibrahim Sow (Cardiologist), `00000000-0000-0000-0000-200000000011` |
| Insurer | one of the 15 real insurers in `insurance_providers` |

Only 17 facilities are linked for booking (`care_facilities.facility_id` not
null). Appointments must use one of those.

## Rules

- **Demo patient only.** Never write clinical rows for another patient, and
  never modify the 897 real facilities beyond what your task states.
- **Idempotent.** Fixed UUIDs or an existence check. Running twice must not
  duplicate.
- **Typed backed enums.** Check `app/Enums` first and write `->value`; never a
  bare string that happens to match today.
- **PostgreSQL.** UUID primary keys throughout — `$table->morphs()` style
  bigints are exactly the bug that broke notifications.
- **Do not register your seeder** in `DatabaseSeeder.php`; report it and it
  will be wired centrally.
- **Do not touch `apps/mobile-expo`.**
- Dates are relative to *now*, computed at run time, so the data never goes
  stale. Never hardcode a calendar date that will drift into the past.

## Verify before reporting

Run your seeder **twice** (prove idempotency), then `curl` the mobile endpoint
your data feeds and paste the real response. A row in the database that the API
does not return is not done.

Auth: `POST /api/mobile/auth/login-email` with
`{"email":"demo.patient@opescare.test","password":"DemoPass!2026"}` → use the
`access_token` as `Authorization: Bearer`.

PHP is not on PATH:
`export PATH="/c/laragon/bin/php/php-8.3.30-Win32-vs16-x64:$PATH"`
