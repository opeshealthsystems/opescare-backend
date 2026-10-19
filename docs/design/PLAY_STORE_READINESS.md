# Play Store readiness: OpesCare mobile (2026-10-19)

Read-only audit of `C:\laragon\www\opescare-finder-mobile` (Expo SDK ~57.0.30,
RN 0.86.3, package `com.opescare.patientid`) against the live backend
https://opescare.com (APP_ENV=production). Paths without a prefix are in the
mobile repo; `backend:` means this repo. Policy points I cannot confirm are
current are marked **[verify]**. Severity: **blocker** (rejection or policy
breach), **should** (likely review friction or user harm), **nice**.

## 1. Production config

| # | Sev | Finding | Evidence | Fix |
|---|---|---|---|---|
| C1 | blocker | Production profile disables push (`OPESCARE_BUILD_WITHOUT_PUSH=1`) but the manifest still requests `POST_NOTIFICATIONS` and the app asks for it. Either ship push or drop the permission; the data-safety doc also declares push-token sharing. | `eas.json` production.env; `app.json:58-63` | Preferred: add `GOOGLE_SERVICES_JSON` as an EAS file variable for `production` and remove `OPESCARE_BUILD_WITHOUT_PUSH`. Otherwise move `POST_NOTIFICATIONS` to `blockedPermissions` and remove the push row from the Data safety form. |
| C2 | ok | API base is `https://opescare.com/api` in production; fallback in `lib/api/endpoints.ts:8` is also opescare.com; `app.config.js` refuses a production build pointing at `finder.opescare.cloud` unless `OPESCARE_ALLOW_DEMO_BACKEND=1`. That flag is **not** set in production. | `eas.json`; `app.config.js` (DEMO_API_HOST guard) | Keep. Before submit, grep the exported bundle for `finder.opescare.cloud`, `192.168`, `localhost` (Metro caches EXPO_PUBLIC values; export with `--clear`). |
| C3 | should | `EXPO_PUBLIC_DEMO_LOGINS=1` in `development`, `preview`, `demo` profiles; demo accounts no longer exist on prod, so those builds show dead buttons. Production has `0` (correct). | `eas.json`; `app/(auth)/login.tsx:44` | Set `0` in preview/demo too, or remove the demo-login UI. |
| C4 | should | `local-api` profile uses cleartext `http://192.168.1.33`; `usesCleartextTraffic` is derived so production is clean. | `eas.json`; `app.config.js` header | Confirm `expo config --type public` with production env shows no cleartext flag. |
| C5 | should | Stale comments name `finder.opescare.cloud` as the live backend. Not shipped behaviour, but misleading. | `lib/fileDelivery.ts:4`, `lib/api/doctorQueries.ts:9`, `lib/push/capability.ts:18` | Update comments when convenient. |

## 2. Deep links / App Links

| # | Sev | Finding | Evidence | Fix |
|---|---|---|---|---|
| D1 | should | No `intentFilters` in app.json and no `assetlinks.json` anywhere: `backend:public/.well-known/` does not exist, and `GET https://opescare.com/.well-known/assetlinks.json` returns **404**. `/verify/qr` and `/verify/doc/{token}` links therefore always open the browser. Not a rejection reason, but required if verified links are wanted. | `app.json` android block; live GET 404 | Backend: add `public/.well-known/assetlinks.json` with package `com.opescare.patientid` and the **Play App Signing** SHA-256 (Play Console → App integrity), served as `application/json`. App: add `android.intentFilters` with `autoVerify: true`, `scheme: https`, `host: opescare.com`, `pathPrefix: /verify`. |

## 3. Health app requirements

| # | Sev | Finding | Evidence | Fix |
|---|---|---|---|---|
| H1 | blocker | **Health apps declaration** (Play Console → App content → Health apps) must be completed. Categories that apply: medical records / health records management, appointment booking, medication/pharmacy lookup, clinician tools (clinician scanner exists). | `app.json:125` (clinician scan), store listing | Owner completes the declaration; tick the categories above. [verify] exact category labels. |
| H2 | should | Medical disclaimer exists only in scattered screens (terms, care map, blood education). Play expects health apps not to imply diagnosis/treatment and to state limits clearly. | `app/legal/terms.tsx`, `app/care-map.tsx`, `app/blood-finder/education.tsx` | Add a one-line disclaimer on first run / About and in the store description: "OpesCare is not a substitute for professional medical advice; in an emergency call 15/119" [verify Cameroon numbers]. EN+FR. |
| H3 | blocker | **Government / health-ID claims.** Listing says "Your Health ID identifies you at any facility" and "national facility directory". Play's misleading-claims and government-affiliation rules [verify] reject apps that imply official government status without proof. | `playstore/listing/store-listing-en.md:21,33,39` | Either attach MINSANTE authorisation evidence in the declaration, or reword: "OpesCare Health ID, used at facilities on the OpesCare network", and add "OpesCare is not a government app" to the description (EN/FR). |
| H4 | should | Sensitive health data: transport is HTTPS only in production; tokens in `expo-secure-store`; `allowBackup:false`. Good. Push titles can name health events (third-party via FCM). | `app.json` `allowBackup`; `playstore/listing/data-safety.md` note 3 | Make push titles generic ("You have a new notification") or declare sharing as already drafted. |
| H5 | should | Privacy policy at https://opescare.com/privacy (200) covers health data, location (foreground only), photo, push tokens, mobile-money number, retention, children, deletion rights. It does **not** name third processors (Google FCM, Expo push) or biometrics-stay-on-device. | live GET; text extract | Backend: add a processors section naming Expo and Google FCM, and a line that biometric unlock never leaves the device. |

## 4. Data safety form (derived from code)

The draft in `playstore/listing/data-safety.md` is code-derived and accurate;
use it, with these corrections:

| Data type | Collected / shared | Evidence |
|---|---|---|
| Name, phone, DOB, sex, emergency contact, email (optional) | Collected, not shared | `backend:MobileAuthController::register()`; data-safety.md |
| Health info (records, prescriptions, labs, vitals) | Collected, not shared | `lib/api/` query modules |
| Photos | Collected, optional | `app.json:97` image-picker |
| Precise + approximate location | Collected, ephemeral only if backend does not log lat/lng | `lib/api/queries.ts` `useNearbyPharmacies`, `useBloodSearch` |
| Financial (MoMo MSISDN) | Collected, not shared by app (server passes to MTN/Orange) | pay flow; data-safety.md note 1 |
| Messages | Collected | data-safety.md |
| Device IDs / push token | Collected; shared only if push ships (see C1) | `lib/push/` |
| Crash/analytics | Not collected (no Sentry/Firebase Analytics in package.json) | `package.json` |

Fix: before ticking "processed ephemerally" for location, confirm the backend
request log / access log does not persist query strings with lat/lng. Declare
"data encrypted in transit: yes" and "users can request deletion: yes".

## 5. Account deletion

| # | Sev | Finding | Evidence | Fix |
|---|---|---|---|---|
| A1 | ok | In-app deletion screen exists and is linked from About. Backend `GET/POST /api/mobile/account/deletion` (401 unauthenticated, correct), nightly `opescare:execute-account-deletions`. | `app/account/delete.tsx`; `app/about.tsx:177`; `backend:routes/mobile_account_deletion.php:93,102`; `backend:routes/console.php:47` | Keep. |
| A2 | blocker-if-missing | Web deletion URL for the Play form: `https://opescare.com/account-deletion` returns 200. | live GET | Enter `https://opescare.com/account-deletion` in Data safety → account deletion URL. Check that page explains steps without needing the app, and what is retained (legal record-retention). |

## 6. Permissions

| Permission | Declared | Used by | Sev / fix |
|---|---|---|---|
| ACCESS_FINE/COARSE_LOCATION | `app.json:59-60` | Near-me search (foreground) | ok. Prominent disclosure not needed for foreground-only [verify]; keep the in-context rationale. |
| CAMERA | via expo-camera / image-picker plugins | Health ID photo; clinician QR scan | ok; both purpose strings present (`app.json:98,125`). |
| READ_MEDIA_IMAGES | may be added by expo-image-picker [verify in AAB manifest] | Photo pick | should: Play's photo/video permission policy requires using the Android photo picker for one-off picks. Block `READ_MEDIA_IMAGES`/`READ_MEDIA_VIDEO` in `blockedPermissions`; expo-image-picker uses the system picker. |
| POST_NOTIFICATIONS | `app.json:62` | Push (disabled in prod) | blocker, see C1. |
| USE_BIOMETRIC / USE_FINGERPRINT | added by expo-local-authentication | App unlock | ok; disclosed in `playstore/listing/permissions.md:22`. |
| RECORD_AUDIO, storage, SYSTEM_ALERT_WINDOW, BOOT | blocked `app.json:64-69` | none | ok. |

Fix: after the production build, run `bundletool dump manifest` on the AAB and
compare to this table; the release must match `playstore/listing/permissions.md`.

## 7. Build, SDK, signing, versioning

| # | Sev | Finding | Evidence | Fix |
|---|---|---|---|---|
| B1 | ok | Target SDK: Expo SDK 57 defaults target API 35/36, satisfying Play's "within one year of latest" rule (API 35 required for new apps since 2025-08-31) [verify current floor]. No `expo-build-properties` override. | `package.json:12` | Confirm targetSdk in the AAB manifest. |
| B2 | ok | 64-bit: EAS AAB ships arm64-v8a + armeabi-v7a by default. | `eas.json` production `app-bundle` | none |
| B3 | should | Versioning: `appVersionSource: remote` + `autoIncrement` in production; `app.json:49` `versionCode: 1` is ignored and misleading. `version` 1.5.0 → versionName. | `eas.json`; `app.json:4,49` | Remove `android.versionCode` from app.json (or leave a comment); confirm remote versionCode exceeds anything already uploaded; set backend `min_supported_build` / `latest_version` (`lib/api/appConfigQueries.ts:29`) after release. |
| B4 | blocker (first upload) | App signing: enrol in Play App Signing; EAS-managed upload key. | `playstore/SIGNING.md` | Follow SIGNING.md; record the Play signing SHA-256 for assetlinks (D1). |

## 8. Other declarations

| # | Sev | Item | Fix |
|---|---|---|---|
| P1 | blocker | Content rating questionnaire | Complete IARC; answer "no" to violence etc., "yes" to sharing user info/location with server and user-to-user messaging (patient-care-team). Expect Everyone/3+ or PEGI 3. |
| P2 | blocker | Ads declaration | "No ads" (no ad SDK in `package.json`). |
| P3 | should | Financial features declaration | Declare "payments for goods/services via mobile money" (not a lending/crypto/banking app) [verify form wording]. Payments are unavailable until MTN/Orange keys: hide or clearly label the pay button as unavailable so the reviewer does not hit a dead flow. |
| P4 | should | Target audience | Adults 18+ for account holders; guardian/family flow exists, so do not select under-13 audiences (would trigger Families policy). |
| P5 | should | Clinician/role features | Clinician scanner and desks are reachable only by role; state in reviewer notes that they need a staff account. `EXPO_PUBLIC_DOCTOR_PERSONA=0` in production. |

## 9. Reviewer access

Demo accounts are gone and `demo.reviewer@opescare.test` no longer works.
Play requires working credentials for any login-gated content.

- **Account type:** one **verified patient** account with email + password login
  (not phone+PIN+OTP: the reviewer cannot receive Cameroon SMS), **no MFA
  enrolled** (otherwise `mfa_required`, per `MOBILE_AUTH_BRIEF.md` §1), with a
  little real-looking, non-identifying content (one appointment, one record entry)
  so screens are not empty.
- **Owner creates it themselves** on production: register via the app or web
  with a dedicated mailbox (e.g. `play-review@<owner domain>`), complete
  verification through the normal admin flow, add sample entries through the
  normal facility workflow, and put the credentials only in Play Console →
  App access. Do not seed it with a seeder, and do not mark it `is_demo`
  (the IsDemoRecord scope would hide it in production).
- Write reviewer notes: location is optional (type a city); payments are not
  live yet; clinician features need a staff role.

## 10. Quality

| # | Sev | Finding | Evidence | Fix |
|---|---|---|---|---|
| Q1 | should | No React error boundary found under `app/` (grep `ErrorBoundary` empty). An uncaught render error is a white screen / crash, which pre-launch reports flag. | `app/_layout.tsx` | Export `ErrorBoundary` from `app/_layout.tsx` (expo-router supports it) with a translated retry screen. |
| Q2 | should | No `@react-native-community/netinfo`; offline state relies on request failures. | `package.json` | Make sure every query screen has an error + retry state; memory notes a past "profile has not loaded" failure mode. Run the Play pre-launch report and fix any crash it lists. |
| Q3 | ok | Accessibility: 545 `accessibilityLabel` uses; no `allowFontScaling={false}` / `maxFontSizeMultiplier` (font scaling respected). | grep counts | Spot-check at 200% font scale and with TalkBack on login, home, Health ID. Check brand #0F4C81 text on cream `#FDF8F0` (passes AA) and any grey-on-cream text. |
| Q4 | should | EN/FR: `CFBundleLocalizations` en/fr; store listings exist in both. | `playstore/listing/store-listing-{en,fr}.md` | Run the app's i18n parity check before build; upload the FR listing as `fr-FR` (and optionally `fr-CM` if offered [verify]). |
| Q5 | should | Store assets present: 512 icon, 1024×500 feature graphic, screenshots. | `playstore/graphics/` | Re-shoot screenshots after the logo change (L1) and against production (no demo data visible). |

## 11. Backend changes required

1. **assetlinks.json** at `public/.well-known/assetlinks.json` (D1), plus nginx serving it as JSON.
2. **Privacy policy**: name Expo + Google FCM as processors; biometric stays on device; mobile-money number handling (H5).
3. **MFA on mobile login** per `docs/design/MOBILE_AUTH_BRIEF.md`: the app must handle `mfa_required` / `mfa_code_invalid` / `mfa_setup_required` / `account_suspended` / `account_pending` codes and single-use refresh with 48 h grace; reviewer account must have no MFA.
4. **Logo** per `docs/design/MOBILE_LOGO_BRIEF.md`: replace the drawn in-app logo with the canonical mark, fix favicon upscale, notification icon heartbeat line, background colours.
5. **Account-deletion web page** content check at `/account-deletion` (A2).
6. **Location logging**: confirm lat/lng are not persisted in logs before declaring ephemeral (section 4).
7. **App config**: set `min_supported_build` / `latest_version` and the store URL to the Play listing once published.
8. **Payments**: return a clear "unavailable" state from the payment-methods endpoint until MTN/Orange keys exist (P3).

## Top 10

1. **C1** Ship push for production (google-services.json EAS secret) or remove `POST_NOTIFICATIONS`. Blocker.
2. **H1** Complete the Health apps declaration. Blocker.
3. **H3** Remove or substantiate national / government Health ID wording; add "not a government app". Blocker.
4. **Reviewer account**: owner creates a verified email+password patient, no MFA, with sample data; enter in App access. Blocker.
5. **A2 + section 4** Data safety form from the code-derived draft, deletion URL `https://opescare.com/account-deletion`. Blocker.
6. **P1/P2/P3/P4** Content rating, no-ads, financial features, target audience 18+. Blocker (forms).
7. **B4** Play App Signing enrolment; capture signing SHA-256.
8. **Q1** Add a root `ErrorBoundary`; run pre-launch report and fix crashes.
9. **Photo permission**: block `READ_MEDIA_*`, verify the AAB manifest matches `permissions.md`.
10. **D1 + backend 1** assetlinks.json and intent filters for `/verify/*`; medical disclaimer (H2) and MFA codes (backend 3) in the same release.
