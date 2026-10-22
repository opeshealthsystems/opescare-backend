# OPES Triage → OpesCare integration (FHIR R4 ingest)

Status: tested end to end **locally** on 2026-10-22 (dev DB `opescare_finder`, `http://127.0.0.1:8010`).
The live account on opescare.com has **not** been created yet. The owner creates it (see "Setting up the LIVE account" below).

OPES Triage (`config/solutions.php`, slug `opes-triage`) pushes triage readings into a patient's
OpesCare record. Every reading must name an **existing Health ID**, and the patient must have given the facility **consent**.

## What OPES Triage can send today

| Endpoint | Scope required | Notes |
|---|---|---|
| `POST /api/fhir/R4/Observation` | `system/Observation.write` | one reading |
| `POST /api/fhir/R4/` (transaction Bundle) | checked per entry, by resource type | all-or-nothing |
| `POST /api/fhir/R4/DiagnosticReport` | `system/DiagnosticReport.write` | laboratory reports |
| `POST /api/fhir/R4/Immunization` | `system/Immunization.write` | CVX codes |
| `GET /api/fhir/R4/metadata` | none | CapabilityStatement |

There is **no** `POST /Patient`. A partner can never create or update a patient, and this API offers no reads.

### Observation rules (enforced in `App\Services\Fhir\FhirIngestService` and `FhirCodeValidator`)
- `category` must carry `vital-signs` or `laboratory` (system `http://terminology.hl7.org/CodeSystem/observation-category`). The server never infers it.
- `vital-signs` codes are a **closed list** (the FHIR R4 vital-signs profile). These are the ones triage uses:

| Reading | LOINC | UCUM unit |
|---|---|---|
| Heart rate | `8867-4` | `/min` |
| Respiratory rate | `9279-1` | `/min` |
| SpO2 (pulse oximetry) | `59408-5` (or `2708-6`) | `%` |
| Body temperature | `8310-5` | `Cel` |
| Blood pressure panel | `85354-9`, components `8480-6` (systolic) and `8462-4` (diastolic) | `mm[Hg]` |
| Body weight / height / BMI | `29463-7` / `8302-2` / `39156-5` | `kg` / `cm` / `kg/m2` |

- A `laboratory` code must pass the LOINC check digit and be active in the deployment's LOINC dictionary.
- **Not accepted today:** pain score (`72514-3`, category `survey`) and triage acuity or level. An Observation with category `survey`
  is refused with `400 category_missing`. Accepting them means extending the accepted categories, which is a product decision.
  Do not work around it by sending pain as `laboratory`.
- `effectiveDateTime` (or `effectivePeriod.start`) is required. `status` defaults to `final`.
- Each Observation needs a value (`valueQuantity`, `valueString` or `valueCodeableConcept`) or at least one `component`.
- `subject.identifier` is required, and `subject.reference` is refused. Accepted identifier systems
  (`FhirIngestGuard::HEALTH_ID_SYSTEMS`): `https://opescare.com/fhir/health-id` (use this one),
  `https://opescare.com/fhir/sid/health-id`, the legacy `opescare.cloud` equivalents, and `urn:oid:2.16.120.1.1.1`.
- The `Idempotency-Key` header is **required** on every write. Replaying a key returns the original response
  (`X-Cache-Idempotency: HIT`). Reusing a key with a different body returns `409`.

## Auth flow

1. Each approved **integration client** (a row in `integration_clients`) is bound to **one facility** (`facility_id`)
   and carries the FHIR scopes.
2. The client exchanges its credentials for an RS256 JWT that is valid for 1 hour:
   ```bash
   curl -X POST https://<host>/api/v1/connect/auth/token \
     -d grant_type=client_credentials -d client_id=<CLIENT_ID> --data-urlencode client_secret=<CLIENT_SECRET>
   # -> {"access_token":"<JWT>","token_type":"Bearer","expires_in":3600,"scope":"system/Observation.write ..."}
   ```
3. Each request sends `Authorization: Bearer <JWT>`. `VerifyBearerToken` copies `facility_id` and `scopes` from the token
   into the request. **The facility is only ever read from the token.** Headers, body fields (`performer`, `meta`) and query
   strings cannot change it. If the client is revoked or suspended, its next request is refused with `401 client_revoked`.

## Consent requirement

Every write needs an **active, unexpired row in `consent_grants` from the patient to the token's facility**
(`FhirIngestGuard::requireConsent`). Grants come from the normal consent flow: the facility requests consent
(`ConsentService::requestConsent`), and the patient approves it in the app (`approveConsent`). Without a grant,
the write is refused with **the same 404 as an unknown Health ID**. This is deliberate, so a partner cannot use the
API to find out which patients exist. The audit row records the real reason (`consent_required`).

## Request examples

Single Observation:
```bash
curl -X POST https://<host>/api/fhir/R4/Observation \
  -H "Authorization: Bearer <JWT>" -H "Content-Type: application/fhir+json" \
  -H "Idempotency-Key: <unique per reading, e.g. triage-encounter-123-hr>" \
  --data-binary @hr.json
```
```json
{
  "resourceType": "Observation",
  "status": "final",
  "category": [{"coding": [{"system": "http://terminology.hl7.org/CodeSystem/observation-category", "code": "vital-signs"}]}],
  "code": {"coding": [{"system": "http://loinc.org", "code": "8867-4", "display": "Heart rate"}]},
  "subject": {"identifier": {"system": "https://opescare.com/fhir/health-id", "value": "<HEALTH_ID>"}},
  "effectiveDateTime": "2026-10-22T10:36:00Z",
  "valueQuantity": {"value": 112, "unit": "/min", "system": "http://unitsofmeasure.org", "code": "/min"}
}
```
Response: `201 Created`. The body is the stored Observation, with `meta.security` set to `partner_reported`.

Transaction Bundle for one triage set (`POST /api/fhir/R4/`):
```json
{
  "resourceType": "Bundle", "type": "transaction",
  "entry": [
    {"request": {"method": "POST", "url": "Observation"}, "resource": {
      "resourceType": "Observation", "status": "final",
      "category": [{"coding": [{"system": "http://terminology.hl7.org/CodeSystem/observation-category", "code": "vital-signs"}]}],
      "code": {"coding": [{"system": "http://loinc.org", "code": "85354-9"}]},
      "subject": {"identifier": {"system": "https://opescare.com/fhir/health-id", "value": "<HEALTH_ID>"}},
      "effectiveDateTime": "2026-10-22T10:36:00Z",
      "component": [
        {"code": {"coding": [{"system": "http://loinc.org", "code": "8480-6"}]}, "valueQuantity": {"value": 148, "unit": "mm[Hg]", "system": "http://unitsofmeasure.org", "code": "mm[Hg]"}},
        {"code": {"coding": [{"system": "http://loinc.org", "code": "8462-4"}]}, "valueQuantity": {"value": 92, "unit": "mm[Hg]", "system": "http://unitsofmeasure.org", "code": "mm[Hg]"}}
      ]}}
  ]
}
```
Add SpO2 (`59408-5`), temperature (`8310-5`) and respiratory rate (`9279-1`) as further entries in the same shape.
Response: `200 OK` with a `transaction-response` Bundle, containing one `201 Created` and a `location` per entry. If any entry is refused, nothing is stored.

### Errors

| Case | Status | Body |
|---|---|---|
| No `Authorization` header | 401 | `{"error":"missing_token"}` (Connect JSON) |
| Invalid or expired JWT | 401 | `invalid_token` / `token_expired` |
| Client revoked | 401 | `client_revoked` |
| Missing scope (single-resource route) | 403 | `{"error":"insufficient_scope"}` (Connect JSON, from the middleware) |
| Missing scope inside a Bundle | 403 | FHIR `OperationOutcome` `forbidden` |
| Unknown Health ID | 404 | `OperationOutcome` `not-found` |
| No active consent to the facility | 404 | same response as an unknown Health ID |
| Bad or unknown code, missing category, missing time | 400 | `OperationOutcome` (`code-invalid` / `structure`) |
| Missing `Idempotency-Key` | 400 | `OperationOutcome` |
| Same key, different body | 409 | `OperationOutcome` |

## Where the data lands, and who sees it

- Accepted resources are stored in `fhir_partner_observations` (`PartnerObservation`), `fhir_partner_diagnostic_reports`
  and `fhir_partner_immunizations`. Provenance is pinned to `partner_reported`, and `facility_id` is the token's facility.
- Every accepted or refused write is audited as a row in `fhir_ingest_events`, recording the client, facility, resource type,
  outcome, refusal code, HTTP status and a hash of the identifier. Writes do not create `access_logs` rows, because that table records reads.
- **Clinicians and patients cannot see this data yet.** `PatientChartService` and the mobile chart deliberately skip the
  `fhir_partner_*` tables. They will stay hidden until a clinical decision is made on how partner-reported readings are
  shown next to readings measured at the facility (see the docblock in `app/Services/Chart/PatientChartService.php`
  and `App\Enums\VitalProvenance`). Today, OPES Triage data is stored and audited but not displayed.
  This is the main open item before OPES Triage is useful to a clinician.

## Local test results (2026-10-22)

The local setup was created through the same code paths the portal and admin screens use (`DeveloperPortalController::storeApp`,
the `storeProductionRequest` logic, `ProductionAccessRequest::approve` plus the admin approval's facility binding,
and `ConsentService::requestConsent` / `approveConsent`). It consists of:
- developer `triage-dev@opescare.test` and organization "OPES Triage";
- app and integration client "OPES Triage (local test)", bound to the demo facility **CHU de Yaoundé** with the three FHIR scopes;
- patient `OC-DEMO-PAT-0001` (demo.patient), with an active consent grant to that facility.

The local credentials are in `storage/app/private/opes-triage-local-credentials.txt`. That file is gitignored and only works against the local database.

| # | Case | Expected | Got | Result |
|---|---|---|---|---|
| 1 | Token exchange (client credentials) | 200 + JWT with FHIR scopes | 200 | PASS |
| 2 | POST Observation, heart rate 8867-4 | 201, row stored, event `accepted` | 201 | PASS |
| 3 | Transaction Bundle: BP panel (8480-6/8462-4), SpO2 59408-5, temperature 8310-5, RR 9279-1 | 200, 4 rows | 200, 4 rows | PASS |
| 4 | Pain score 72514-3 (category `survey`) | refused (not supported) | 400 `category_missing` | PASS (limitation) |
| 5 | No token | 401 | 401 | PASS |
| 6 | Malformed token | 401 | 401 | PASS |
| 7 | Token with only `patients:read`, POST Observation | 403 | 403 | PASS |
| 8 | Same wrong-scope token, POST Bundle | 403 | 403 OperationOutcome, event `insufficient_scope` | PASS |
| 9 | Unknown Health ID | 404 | 404, event `patient_unresolvable` | PASS |
| 10 | Real patient with no consent to the facility | 404 | 404, event `consent_required` | PASS |
| 11 | `X-Facility-Id` header plus `performer` and `meta` naming another facility | ignored | 201, stored under the token's facility | PASS |
| 12 | Data visible in clinician chart or patient app | n/a | not shown (deliberate, see above) | OPEN |

## Setting up the LIVE account on opescare.com (owner)

1. **Account.** `/signup/developer` only records a lead (a row in `leads` with source `developer_signup`). It does not create a login.
   Create a user with the `developer` role for the OPES Triage engineer in admin user management. Then sign in as that user and
   complete `/developer/onboard`, which creates the `developer_accounts` row in sandbox-only mode.
2. **App.** In the developer portal, go to `/developer/apps/create` and create "OPES Triage". The client secret is shown **once**.
   Store it in OPES Triage's secret store, and never put it in a repo or a chat.
3. **Production request.** Go to `/developer/production-requests/create`, pick the app and tick `system/Observation.write`.
   Tick `system/DiagnosticReport.write` and `system/Immunization.write` only if they are needed. Accept the terms and submit.
   These scopes were added to the form on 2026-10-22, so deploy that change first.
4. **Approve.** As platform admin (with MFA), open `/admin/developer/production-requests`, approve the request and type the **exact name
   of the facility** where the triage system runs. Approval promotes the client to production and binds its `facility_id`, which every token then carries.
   A client serves one facility, so a second site needs a second app.
5. **Consent.** Each patient needs an active consent grant to that facility: the facility requests consent and the patient approves it in the app.
6. **Smoke test.** With a real patient who has consented, exchange the credentials for a token, send one heart-rate Observation
   and check `fhir_ingest_events`. Rebuild the config cache after the deploy.
