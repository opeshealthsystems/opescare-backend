# Diagram requests — "The Platform Behind the App"

For the diagram agent. Articles: `content/blog/en/the-platform-behind-the-app.md` and
`content/blog/fr/la-plateforme-derriere-l-application.md`. Existing diagrams already
embedded: `architecture` (intro), `mpi` (s.2), `fhir` (s.3), `consent` (s.8), `qr` (s.9).
Once a new diagram is whitelisted in `BlogDiagrams::NAMES`, add the `{{diagram:NAME}}`
line at the placement given (same position in EN and FR). Every node/edge below cites
the route name or `FQCN::method` that proves it is reachable. Labels need EN/FR keys.

---

## 1. `pharmacy-dispense` — prescription and order state machine

**Placement:** section 5 ("The pharmacy" / "La pharmacie"), after the bullet list, before "Two limits matter" / "Deux limites comptent".

Two lanes.

Lane A — prescription (web + mobile desk):
| Node | Evidence |
|---|---|
| Issued by clinician (interaction check) | `portals.prescriber.store`, `portals.prescriber.interaction_check` (`App\Http\Controllers\MedicalId\PrescriberController::store`) |
| Shared by patient (optional) | `POST api/mobile/prescriptions/{id}/share` (`Api\Mobile\MobilePrescriptionController::share`) |
| In pharmacy list / looked up | `portals.pharmacy.prescriptions`, `portals.pharmacy.prescriptions.lookup`; `api/mobile/pharmacy-desk/prescriptions/lookup` |
| Accepted | `Api\Mobile\Pharmacy\PharmacyDeskController::accept` |
| Ready | `Api\Mobile\Pharmacy\PharmacyDeskController::ready` |
| Dispensed (stock decremented, labels PDF) | `portals.pharmacy.dispense` (`PharmacyPortalController::dispense` via `App\Services\Clinical\PrescriptionService`), `PharmacyDeskController::dispense`, `documents.new.labels` |

Edges: Issued → (Shared) → List → Accepted → Ready → Dispensed. Side note: "written at one facility, filled at another" (comment in `PharmacyPortalController::dispense`).

Lane B — patient medicine order (reservation):
| Node | Evidence |
|---|---|
| Reserved in app | `POST api/mobile/pharmacy/reservations` (`MobilePharmacyController::reserve`) |
| Accepted | `portals.pharmacy.orders.accept` / `PharmacyReservationController::accept` |
| Ready | `portals.pharmacy.orders.ready` |
| Collected | `portals.pharmacy.orders.collected` |
| Dispatched → Delivered | `portals.pharmacy.orders.dispatch`, `portals.pharmacy.orders.delivered` |
| Cancelled (from any open state) | `portals.pharmacy.orders.cancel`, `MobilePharmacyController::cancelReservation` |

## 2. `lab-release` — sample to release with dual notification

**Placement:** section 6 ("The laboratory" / "Le laboratoire"), after the four bullets, before the "Imaging" paragraph.

Sequence (actors: Clinician, Patient app, Lab bench, Lab manager):
| Step | Evidence |
|---|---|
| 1. Clinician orders test | `POST api/mobile/doctor/patients/{patient}/lab-orders` (`MobileDoctorLabOrderController::store`) |
| 1b. Or patient requests test → lab approves/declines | `POST api/mobile/labs/requests`; `portals.lab_manager.requests.approve` / `.decline` |
| 2. Sample collected | `portals.lab.collect` (`LabDeskWebController::collect`) |
| 3. Results entered | `portals.lab.results.store` |
| 4. Validated | `portals.lab.validate` |
| 5. Released (refused if no results; locked row) | `portals.lab.release` → `App\Services\Lab\LabDeskService::release` |
| 6a. Patient notified (bell + push; high priority if critical) | `LabDeskService::notifyReleased` → `bellPatient`, `pushPatient` |
| 6b. Ordering clinician notified in the same step | `LabDeskService::notifyReleased` (`ordered_by` branch, `lab_api.notify.clinician_title`) |
| 6c. Guardians notified once | `App\Listeners\NotifyGuardiansOfPatientEvent::handleLabResult` |
| 7. Critical value → lab records phone call-back | `portals.lab_manager.critical_callback` (`LabManagerDeskController::criticalCallback`) |
| 8. Correction after release (recorded, not overwritten) | `portals.lab.results.correct` |

Key visual: steps 6a and 6b fan out from the single release node simultaneously.

## 3. `blood-request-queue` — blood request lifecycle

**Placement:** section 7 ("The blood bank" / "La banque de sang"), after the bullet list (end of section).

| Node | Evidence |
|---|---|
| Request from patient app | `POST api/mobile/blood/requests` (`MobileBloodController::store`) |
| Request from clinician / facility | `portals.staff.blood_requests.store` (`BloodRequestDeskController::store`) |
| Decision (accept / decline) | `portals.staff.blood_requests.decide`; `api/mobile/blood-desk/requests/{id}/decide`; `api/v1/blood-bank/requests/{id}/decision` (`Api\V1\BloodRequestQueueController::decide`) |
| Ready | `portals.staff.blood_requests.ready`; `MobileBloodDeskController::ready` |
| Fulfilled | `portals.staff.blood_requests.fulfil`; `MobileBloodDeskController::fulfil` |
| Cancelled by requester | `POST api/mobile/blood/requests/{id}/cancel` |
| Request slip PDF | `documents.new.blood_request` |
| Inventory (feeds finder) | `portals.staff.inventory.blood` (+ `.upsert`, `.adjust`, `.flag`); partner sync `POST api/v1/connect/inventory/blood-stock/sync` |
| Patient finder reads inventory | `public.network.blood-finder`; `GET api/mobile/blood/search` |

Edges: two request sources → Decision → Ready → Fulfilled; Inventory → Finder; Inventory ↔ Decision (dashed, "checked against").

## 4. `facility-go-live` — facility onboarding to go-live

**Placement:** section 1 ("What the platform is" / "Ce qu'est la plateforme"), after the paragraph ending "running its own desks" / "ses propres guichets", before the table.

| Step | Evidence |
|---|---|
| 1. Facility listed in directory, claimed by owner | `public.care-map.claim` / `.claim.store` (`CareMap\FacilityClaimController`) |
| 2. Claim reviewed by admin | `admin.care-map.review.claims.approve` / `.reject` |
| 3. Onboarding checklist items marked | `portals.admin.onboarding.show`, `portals.admin.onboarding.mark` (`OnboardingPortalController::markItem`) |
| 4. Readiness scored | `portals.admin.facilities.readiness`, `portals.admin.facilities.readiness.score` (`FacilityGoLiveReadinessController`) |
| 5. Approved / go-live | `portals.admin.onboarding.approve` (`OnboardingPortalController::approve`) |
| 6. Team invited, staff join requests approved | `portals.facility.team.join.approve`; `professional.join.store` |
| 7. Desks live (pharmacy / lab / queue …) | `portals.pharmacy.stock`, `portals.lab.index`, `portals.queue.index` |

Verify the claim→onboarding link before drawing an edge between steps 2 and 3; if they are independent entry points, draw them as two inputs into step 3.
