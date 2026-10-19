# Functional audit — Finder mobile app vs Laravel API (2026-10-19)

Scope: `C:\laragon\www\opescare-finder-mobile` against this repo's `routes/` and the
local API (`http://localhost:8010/api`, demo accounts).

## Method
1. **Contract.** Every `/mobile/*` path in the app (`lib/api/endpoints.ts`, every
   `*Endpoints` object and inline literal in `lib/api`, `app`, `components`) was
   normalised and matched against `php artisan route:list --json` for path, and each
   `apiClient.<method>()` call was matched for HTTP method. Middleware checked for
   auth on every non-public route.
2. **Live shapes.** Patient token (demo.reviewer@) called 26 endpoints; doctor
   token called the clinician set. Top-level keys compared to `lib/api/types.ts`
   and the query hooks.
3. **Error handling.** Every `useMutation` call site in `app/` and `components/`
   (excluding `app/(auth)/**`, `app/settings.tsx`, `components/security/**`,
   owned by another agent) checked for `onError`, a rendered `isError`, or a
   `try/catch` around `mutateAsync`.

## Findings

| # | Finding | Severity | Fix | Commit (mobile) |
|---|---|---|---|---|
| 1 | Contract: 0 missing paths, 0 wrong methods. The checker's only hits were false positives (prefix-variable joins, `reservations/{id}/{action}` which maps to six real routes). Only public routes lacking auth are the intended pre-auth ones (login, register, OTP, refresh, forgot/reset, app-config), all throttled. | — | none needed | — |
| 2 | Live patient shapes match the TS types: `me`, `health-id-card`, `allergies` (unwrapped), `timeline`, paginated lists (`data`+`pagination`), invoices (`data`+`meta`), access logs (Laravel paginator, read via `current_page/last_page`), consent inbox (bare array, as typed). | — | none needed | — |
| 3 | Donor pause / resume / withdraw (`app/blood-finder/become-donor.tsx`) failed silently: no `onError`, `setStatus.isError` never rendered. The user believed they had withdrawn. | Medium | `onError` opens a dialog (`donor.statusFailed*`, EN+FR) | fix(api): mutation failures |
| 4 | Blood-request cancel (`app/blood-finder/modify.tsx`) failed silently; the request stayed active with no message. | Medium | `onError` dialog (`bloodModify.cancelFailed*`, EN+FR) | fix(api): mutation failures |
| 5 | Passport QR mint (`components/passport/VerificationCentre.tsx`) called `void present()` around an un-caught `mutateAsync` — unhandled promise rejection on any 4xx/5xx/offline (the on-screen `mintFailed` line already existed). | Low | `try/catch` around `mutateAsync` | fix(api): mutation failures |
| 6 | `GET /mobile/records/imported` exists and returns `{label, records}` but no app hook reads it (only invalidated). Dead endpoint key, not a bug. | Info | left as is | — |
| 7 | Clinician: `doctor/patients` 404 without `health_id` is intentional (no enumeration); `desk/home` 403 for a doctor is the capability gate. | — | none needed | — |

Guarded by `__tests__/mutationErrorHandling.test.ts`. `npm run verify` is green.

## Not covered (environment)
Clinician live probes past provider login were cut short: the shared login
throttle (5/min) was used up by concurrent agents, then local Postgres went into
**recovery mode** under host load (`SQLSTATE[08006] … in recovery mode`, bash fork
failures). Clinician chart sections, prescribe, lab results and desk queues were
checked by contract only (path + method), not by live response shape. Re-run
step 2 for the clinician set once the host is quiet.

No backend change was needed, so no Feature test was added.
