# API Resource Layer (DTO / Wire-Contract Decoupling)

**Status:** Foundation shipped · migration in progress (ratcheted)
**Owner:** Platform / API
**Related:** [API-VERSIONING.md](API-VERSIONING.md) §4 (the contract this enforces)

---

## 1. Why this exists

Every OpesCare API endpoint must return data through an **explicit resource
class** — never a raw Eloquent model. The resource *is* the public wire
contract. A database column can be added, renamed, re-typed, or removed without
changing the JSON a client receives, unless the resource is deliberately edited
(and an edit is then a conscious, reviewable, *additive* change per the
versioning policy).

Returning a raw model couples the wire format to the schema: any migration
silently reshapes the API, leaks internal columns (soft-delete flags, internal
FKs, `*_token` fields), and makes the "zero breaking changes" guarantee
unenforceable. The resource layer breaks that coupling.

```
Controller → Service → Eloquent Model  ── App\Http\Resources\* ──▶  JSON wire contract
                       (schema, mutable)        (stable, reviewed)
```

## 2. The convention

All resources extend `App\Http\Resources\ApiResource`
([app/Http/Resources/ApiResource.php](../app/Http/Resources/ApiResource.php)),
a thin base over Laravel's `JsonResource` with envelope wrapping **disabled**
(`public static $wrap = null`) because controllers compose the envelope
themselves.

**Rules**

1. One resource per externally-exposed entity, named `<Entity>Resource`.
2. `toArray()` lists **every** field explicitly. No `parent::toArray()`, no
   `$this->resource->toArray()`, no `$this->only(...)` of an unbounded set —
   an allow-list, never a deny-list. New columns stay invisible until someone
   adds them here on purpose.
3. Cast at the edge: booleans `(bool)`, timestamps `?->toISOString()`, money to
   integer minor units, etc. — so the wire type is stable regardless of DB/cast
   drift.
4. Compose the envelope in the controller:
   `response()->json(['message' => __('...'), 'data' => FooResource::make($m)])`
   or, for a collection, `FooResource::collection($paginator)`.
5. If the endpoint being converted eager-loads relations (`->with([...])`), the
   resource MUST re-emit them behind `$this->whenLoaded('rel')` — otherwise the
   conversion silently drops keys consumers already receive. Use the related
   entity's own resource when one exists; pass the relation through raw when it
   does not (still an allow-listed key, and shape-identical to the pre-resource
   response). `whenLoaded` emits nothing when the relation was not loaded, so
   endpoints that never load it are unaffected.

**Example — the reference slice** (already converted):
[EncounterController.php](../app/Http/Controllers/Api/V1/EncounterController.php)
returns [`AllergyResource`](../app/Http/Resources/AllergyResource.php),
[`DiagnosisResource`](../app/Http/Resources/DiagnosisResource.php), and
[`ClinicalNoteResource`](../app/Http/Resources/ClinicalNoteResource.php).

```php
// before — schema leaks straight onto the wire
return response()->json(['message' => __('api.allergy_recorded'), 'data' => $allergy], 201);

// after — resource is the contract
return response()->json(['message' => __('api.allergy_recorded'), 'data' => AllergyResource::make($allergy)], 201);
```

```php
class AllergyResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'patient_id'  => $this->patient_id,
            'provider_id' => $this->provider_id,
            'substance'   => $this->substance,
            'severity'    => $this->severity,
            'status'      => $this->status,
            'created_at'  => $this->created_at?->toISOString(),
        ];
    }
}
```

## 3. The guardrail (ratchet)

[`tests/Feature/Architecture/ApiResponseRatchetTest.php`](../tests/Feature/Architecture/ApiResponseRatchetTest.php)
scans `app/Http/Controllers/Api` for raw-variable serialization
(`'data' => $var` and `->json($var)`) and asserts the total never **exceeds**
the recorded baseline.

- Add a new raw-model response → count goes **up** → **test fails**. This stops
  the bleeding: no endpoint can regress to a raw model.
- Convert an endpoint to a resource → count goes **down** → **lower the
  baseline** to lock in the gain. The test also fails if the count drops below
  the baseline, forcing you to ratchet it tighter.

A `FooResource::make($m)` return does not match the pattern, so converting an
endpoint cleanly removes it from the count.

**Current baseline: 86** (FHIR excluded). Progress: 219 → 205 (Communication)
→ 197 (Legal + Support) → 191 (CareMap + Document + PenTest) → 182 (Mortuary
+ MobileGovernance) → 172 (Admin/Tier-1) → 146 (Tier-2) → 117 (Tier-3) → 115
(Tier-4 tail) → **86** (Tier-5: Radiology, Maternity, AdvanceDirective,
PenTest, BloodInventory, PaymentPlan, ResearchAccess). The resources live under
`app/Http/Resources/`.

## 4. Migration backlog

86 heuristic sites remain across the controllers. Convert high-stakes external
surfaces first (mobile + Connect partner-facing, then clinical writes), then the
long tail. After each batch, run the ratchet test and lower the baseline.

The tier tables below are the *original* survey and are no longer a live
worklist — many of the controllers named there are already converted. Run
`php artisan test --filter=ApiResponseRatchetTest` to print the current
hotspots before picking a batch.

> **The count is a heuristic, not a leak list.** A meaningful share of the
> remainder are NOT raw-model leaks — they are **service-returned arrays / DTOs** that the
> regex matches but which must be left as-is (e.g. `CarePlanController`,
> `ControlledSubstanceController` return `$this->service->...()` results guarded
> by `is_array($x) ? $x['id'] : $x->id` — converting them to `Resource::make()`
> would break when the service returns an array). **Per-site judgment is
> required**: convert only confirmed Eloquent models/collections; skip
> service-DTOs, manual `serialize*()` helpers, and relation-loaded returns whose
> base resource would drop the relation (e.g. `CommunicationController::getThread`).

> **FHIR is exempt.** `Api/Fhir/FhirController` serializes to the HL7 FHIR R4
> wire format (its own external contract / CapabilityStatement), not the
> proprietary OpesCare envelope. The ratchet scan **skips the `Fhir/`
> directory**, so it is excluded from both the baseline and these tiers.

### Tier 1 — highest-volume / partner- & mobile-facing (do first)

| Sites | Controller |
|------:|------------|
| 18 | `V1/CommunicationController` |
| 11 | `V1/Admin/AdminGovernanceController` |
| 11 | `V1/CareMapController` |
| 10 | `V1/MaternityController` |
|  7 | `V1/DocumentController` |
|  7 | `V1/MortuaryController` |
|  6 | `V1/RadiologyReportController` |
|  6 | `V1/Security/PenTestController` |
|  5 | `Mobile/MobileGovernanceController` |
|  5 | `V1/Admin/AdminPlatformController` |
|  5 | `V1/Admin/PlatformAdminController` |
|  5 | `V1/CarePlanController` |
|  5 | `V1/ControlledSubstanceController` |
|  5 | `V1/LegalDocumentController` |
|  5 | `V1/SupportController` |

### Tier 2 — 3–4 sites each

`V1/AdvanceDirectiveController` (4), `V1/BloodInventoryController` (4),
`V1/DrugFormularyController` (4), `V1/InsuranceController` (4),
`V1/PatientPaymentPlanController` (4), `V1/ResearchAccessController` (4),
`Mobile/MobileSurveyController` (3), `V1/ClinicalDecisionSupportController` (3),
`V1/ConsentManagementController` (3), `V1/DeathCertificateController` (3),
`V1/LabPathController` (3), `V1/PublicHealth/IntelligenceController` (3),
`V1/PublicHealth/PublicHealthController` (3),
`V1/Reports/ProviderPerformanceController` (3).

### Tier 3 — 2 sites each

`Mobile/MobileCarePlanController`, `Mobile/MobileFamilyController`,
`V1/AdrReportController`, `V1/AefiController`, `V1/AlliedHealthController`,
`V1/AnalyticsController`, `V1/BillingController`, `V1/ClinicalReviewController`,
`V1/HivCounsellingController`, `V1/MdrCaseController`,
`V1/NursingRecordController`, `V1/OccupationalHealthController`,
`V1/OperationalFlowController`, `V1/PalliativeCareController`,
`V1/PediatricController`, `V1/PerioperativeController`,
`V1/PsychiatricAssessmentController`, `V1/SpecialCareController`,
`V1/SpecialtyDiagnosticsController`, `V1/WardAdminController`,
`V1/WardController`.

### Tier 4 — 1 site each

`Mobile/MedicalRecordExportController`, `Mobile/MobileInsuranceController`,
`Mobile/MobileInsuranceMarketplaceController`, `Mobile/MobileReferralController`,
`ProviderMobile/ProviderMobileAuthController`,
`V1/Admin/AccessControlController`, `V1/Admin/CountryExpansionController`,
`V1/Admin/PartnerGovernanceController`, `V1/AppointmentController`,
`V1/Connect/ConnectGovernanceController`, `V1/FileStorageController`,
`V1/OfflineSyncController`,
`V1/PrescriptionController`, `V1/Reports/SurveyReportController`,
`V1/SubscriptionController`, `V1/TelemedicineController`,
`V1/TriageController`.

## 5. How to convert an endpoint

1. Read the model's `$fillable`/`$casts` and the controller method.
2. Create `app/Http/Resources/<Entity>Resource.php extends ApiResource` with an
   explicit `toArray()` allow-list (cast at the edge).
3. Replace `'data' => $model` / `->json($model)` with
   `EntityResource::make($model)` (or `::collection(...)` for lists).
4. `php artisan test --filter=ApiResponseRatchetTest` → confirm the count
   dropped, then **lower `ApiResponseRatchetTest::BASELINE`** to the new number.
5. If the endpoint has a feature test, assert the exact JSON keys so the
   contract is pinned.
