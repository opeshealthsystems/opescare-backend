<?php

namespace Tests\Feature\EndToEnd;

use App\Enums\MedicineCategory;
use App\Enums\PharmacyStockStatus;
use App\Models\AuditEvent;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\Medicine;
use App\Models\MedicinePharmacyStock;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Role;
use App\Models\User;
use App\Modules\Pharmacy\Services\PharmacyStockReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Medicine and prescriptions, walked end to end.
 *
 * Every step below already has a green unit test, and every step below has
 * still been broken in production — because the failures live BETWEEN the
 * steps, where nothing was looking:
 *
 *   - the public Medicine Finder read `pharmacy_stock_availability`, a table no
 *     code path writes to, so it was permanently empty. The pharmacy write
 *     worked. The patient read worked. Together they returned nothing.
 *   - every stock row in the finder carried `source_system = 'demo_seed'`, so
 *     the only thing a patient could ever have been shown was invented.
 *   - prescriptions had no first link: staff could read a register, patients
 *     could read their list, pharmacies could dispense, and nothing could
 *     issue one.
 *   - `POST /v1/prescriptions` wrote `prescriber_id` (not a column) and item
 *     keys `dosage`/`duration` (columns are `dose`/`duration_days`). Mass
 *     assignment dropped all of them without a word: every API prescription
 *     had a null prescriber and items with no dose, and the endpoint answered
 *     201.
 *
 * Each test here is named for the guarantee it protects and says what a patient
 * would otherwise have suffered. Blood and appointments are covered by
 * CareJourneyTest; nothing here repeats them.
 */
class MedicineAndPrescriptionJourneyTest extends TestCase
{
    use RefreshDatabase;

    /** The public Medicine Finder a patient uses. Unauthenticated by design. */
    private const MEDICINE_SEARCH = '/api/v1/care-map/pharmacies/medicine-search';

    /** The B2B API test-bypass credentials honoured by VerifyIntegrationClient. */
    private const API_HEADERS = [
        'X-Client-ID'     => 'test_client_id',
        'X-Client-Secret' => 'test_client_secret',
    ];

    // ──────────────────────────────────────────────────────────────────
    // Fixtures — built here, never taken from a seeder. A test that leans
    // on seeded rows is measuring the seeder.
    // ──────────────────────────────────────────────────────────────────

    /**
     * A pharmacy: the internal tenant record plus the PUBLIC directory listing
     * the finder actually reads, joined by `care_facilities.facility_id`.
     *
     * @return array{0: Facility, 1: CareFacility}
     */
    private function pharmacy(string $name, float $lat = 4.0480, float $lon = 9.6960): array
    {
        $tenant = Facility::forceCreate([
            'name'    => $name,
            'type'    => 'pharmacy',
            'status'  => 'active',
            'is_demo' => false,
        ]);

        $listing = CareFacility::forceCreate([
            'facility_id'         => $tenant->id,
            'facility_name'       => $name,
            'facility_type'       => 'pharmacy',
            'country_code'        => 'CM',
            'region'              => 'Littoral',
            'city'                => 'Douala',
            'address'             => '12 Rue Joss',
            'latitude'            => $lat,
            'longitude'           => $lon,
            'phone_primary'       => '+237670000010',
            'verification_status' => 'unverified',
            'listing_status'      => 'active',
            'integration_status'  => 'none',
        ]);

        return [$tenant, $listing];
    }

    private function hospital(string $name = 'Hopital Laquintinie'): Facility
    {
        return Facility::forceCreate([
            'name'    => $name,
            'type'    => 'hospital',
            'status'  => 'active',
            'is_demo' => false,
        ]);
    }

    private function medicine(string $name, string $generic, string $atc = 'J01CA04'): Medicine
    {
        return Medicine::create([
            'name'                  => $name,
            'generic_name'          => $generic,
            'strength'              => '500mg',
            'form'                  => 'capsule',
            'category'              => MedicineCategory::Antibiotics->value,
            'atc_code'              => $atc,
            'prescription_required' => true,
            'default_pack_size'     => '21 capsules',
            'currency'              => 'XAF',
            'is_active'             => true,
        ]);
    }

    /** `role_id` is deliberately not mass-assignable on User — set it explicitly. */
    private function staffAt(Facility $facility, string $roleName, string $email): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['description' => ucfirst($roleName), 'dashboard_profile_key' => $roleName],
        );

        $user = User::create([
            'name'                => ucfirst($roleName) . ' ' . uniqid(),
            'email'               => $email,
            'password'            => bcrypt('secret-pass-1234'),
            'primary_facility_id' => $facility->id,
            'status'              => 'active',
        ]);
        $user->role_id = $role->id;
        $user->save();

        return $user;
    }

    private function patientUserFor(Patient $patient, string $email): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'patient'],
            ['description' => 'Patient', 'dashboard_profile_key' => 'patient'],
        );

        $user = User::create([
            'name'       => $patient->first_name . ' ' . $patient->last_name,
            'email'      => $email,
            'password'   => bcrypt('secret-pass-1234'),
            'patient_id' => $patient->id,
            'status'     => 'active',
        ]);
        $user->role_id = $role->id;
        $user->save();

        return $user;
    }

    private function asUserOf(User $user, Facility $facility): self
    {
        return $this->actingAs($user)->withSession([
            'mfa.verified'       => true,
            'active_facility_id' => $facility->id,
        ]);
    }

    /**
     * A stock report written the way the pharmacy portal writes it, but stamped
     * at an arbitrary age so freshness can be exercised without waiting a week.
     */
    private function reportStockAgedHours(CareFacility $listing, Medicine $medicine, int $hoursAgo): MedicinePharmacyStock
    {
        $this->travelTo(now()->subHours($hoursAgo));

        $stock = app(PharmacyStockReportService::class)->report($listing, $medicine, [
            'stock_status'        => PharmacyStockStatus::InStock,
            'packs_available'     => 12,
            'pack_size'           => '21 capsules',
            'unit_price'          => 2500,
            'reservation_enabled' => true,
        ]);

        $this->travelBack();

        return $stock;
    }

    private function search(string $term): \Illuminate\Testing\TestResponse
    {
        return $this->getJson(self::MEDICINE_SEARCH . '?medicine=' . urlencode($term));
    }

    // ══════════════════════════════════════════════════════════════════
    // Journey 1 — a pharmacy publishes stock, and a patient finds it
    // ══════════════════════════════════════════════════════════════════

    /**
     * GUARANTEE: what a pharmacist publishes in the portal is what a patient
     * searching for that medicine gets back.
     *
     * WHY: this is the join that was broken for the whole life of the feature.
     * The pharmacy screen wrote one table, the patient search read another, and
     * both had passing tests. A patient looking for their antibiotic saw an
     * empty result and concluded no pharmacy in Douala had it.
     */
    public function test_a_pharmacy_that_publishes_stock_becomes_findable_by_a_patient(): void
    {
        [$tenant, $listing] = $this->pharmacy('Pharmacie du Wouri');
        $medicine   = $this->medicine('Amoxicillin 500mg Capsule', 'Amoxicillin');
        $pharmacist = $this->staffAt($tenant, 'pharmacist', 'wouri.pharmacist@example.test');

        // BEFORE: nothing has been reported, so nothing may be offered.
        $this->assertSame([], $this->search('Amoxicillin')->assertOk()->json('data'));

        // The pharmacist publishes stock through the portal.
        $this->asUserOf($pharmacist, $tenant)
            ->post(route('portals.pharmacy.stock.report'), [
                'medicine_id'         => $medicine->id,
                'stock_status'        => PharmacyStockStatus::InStock->value,
                'packs_available'     => 24,
                'pack_size'           => '21 capsules',
                'unit_price'          => 2500,
                'reservation_enabled' => 1,
            ])
            ->assertRedirect();

        // The portal write path stamps provenance — that stamp is the only
        // thing that lets the row out to the public.
        $stock = MedicinePharmacyStock::where('medicine_id', $medicine->id)->firstOrFail();
        $this->assertSame(PharmacyStockReportService::SOURCE_PORTAL, $stock->source_system);
        $this->assertSame(PharmacyStockStatus::InStock, $stock->stock_status);
        $this->assertNotNull($stock->last_reported_at, 'Freshness is the finder\'s only trust signal.');

        // AFTER: the patient's own search finds that pharmacy.
        $response = $this->search('Amoxicillin')->assertOk();

        $data = $response->json('data');
        $this->assertCount(1, $data, 'A published pharmacy must be findable.');
        $this->assertSame($listing->id, $data[0]['id']);
        $this->assertSame('Pharmacie du Wouri', $data[0]['facility_name']);
        $this->assertSame('reported_available', $data[0]['matched_stock']['availability_status']);
        $this->assertSame('portal', $data[0]['matched_stock']['source_system']);
        $this->assertSame($medicine->id, $data[0]['matched_stock']['medicine_id']);
        $this->assertSame(2500.0, (float) $data[0]['matched_stock']['price']);
        $this->assertSame('XAF', $data[0]['matched_stock']['currency']);
    }

    // ══════════════════════════════════════════════════════════════════
    // Journey 2 — seeded stock is never published
    // ══════════════════════════════════════════════════════════════════

    /**
     * GUARANTEE: a row a pharmacy did not report is never shown to a patient —
     * `demo_seed`, `seed`, and no provenance at all are all withheld.
     *
     * WHY: this is the rule that matters most in this file. A patient may cross
     * a city, spend a day's wage on transport, or stop looking elsewhere on the
     * strength of one search result. Doing that for a row a seeder invented is
     * a safety failure, not a cosmetic one. `scopeReportedByRealSource()` is
     * the single mechanism; this proves it is actually in the patient's path.
     */
    public function test_seeded_and_unprovenanced_stock_is_never_shown_to_a_patient(): void
    {
        $medicine = $this->medicine('Artemether-Lumefantrine 20/120mg', 'Artemether');

        [, $demo]      = $this->pharmacy('Pharmacie Demo', 4.0500, 9.7000);
        [, $seeded]    = $this->pharmacy('Pharmacie Seed', 4.0510, 9.7010);
        [, $unstamped] = $this->pharmacy('Pharmacie Sans Source', 4.0520, 9.7020);

        // Three ways a row can exist without any pharmacy having claimed it.
        foreach ([[$demo, 'demo_seed'], [$seeded, 'seed'], [$unstamped, null]] as [$listing, $source]) {
            MedicinePharmacyStock::create([
                'medicine_id'      => $medicine->id,
                'care_facility_id' => $listing->id,
                'stock_status'     => PharmacyStockStatus::InStock->value,
                'packs_available'  => 40,
                'currency'         => 'XAF',
                'source_system'    => $source,
                'last_reported_at' => now(),
            ]);
        }

        $this->assertSame(
            3,
            MedicinePharmacyStock::count(),
            'The rows exist — the point is that they exist and are still withheld.'
        );

        $this->assertSame(
            [],
            $this->search('Artemether')->assertOk()->json('data'),
            'Invented stock must never reach a patient, whatever label it carries.'
        );

        // And now a real pharmacy reports the same medicine: exactly one result,
        // and it is the reported one. The filter withholds fiction, not everything.
        [$realTenant, $realListing] = $this->pharmacy('Pharmacie Bonanjo', 4.0530, 9.7030);
        app(PharmacyStockReportService::class)->report($realListing, $medicine, [
            'stock_status' => PharmacyStockStatus::InStock,
        ]);

        $data = $this->search('Artemether')->assertOk()->json('data');

        $this->assertCount(1, $data);
        $this->assertSame($realListing->id, $data[0]['id']);
        $this->assertSame('portal', $data[0]['matched_stock']['source_system']);
    }

    // ══════════════════════════════════════════════════════════════════
    // Journey 3 — freshness is derived from the rows, not asserted
    // ══════════════════════════════════════════════════════════════════

    /**
     * GUARANTEE: an empty result set makes NO freshness claim.
     *
     * WHY: "last updated just now" printed above zero results is a lie a patient
     * acts on — they read it as "checked, and there is none", when the truth is
     * "nobody has told us anything". `warning = no_data` and a null timestamp is
     * the only honest answer.
     */
    public function test_an_empty_medicine_search_makes_no_freshness_claim(): void
    {
        $this->medicine('Metformin 500mg Tablet', 'Metformin', 'A10BA02');

        $meta = $this->search('Metformin')->assertOk()->json('meta');

        $this->assertSame('no_data', $meta['warning']);
        $this->assertNull($meta['last_reported_at'], 'There is no timestamp to report about nothing.');
        $this->assertNull($meta['oldest_reported_at']);
        $this->assertSame(0, $meta['results_count']);

        // The thresholds are still published so a client can say what "fresh"
        // would have meant. They mirror FacilityFreshnessService's pharmacy rule.
        $this->assertSame(['fresh' => 24, 'recent' => 72], $meta['freshness_window_hours']);
    }

    /**
     * GUARANTEE: the freshness in `meta` is computed from the age of the rows
     * actually returned — 24h fresh / 72h recent, the same thresholds
     * FacilityFreshnessService writes onto the CareMap availability rows.
     *
     * WHY: a stock report decays. A four-day-old "in stock" is a hint, not a
     * promise, and the patient must be able to tell the difference before
     * deciding to travel. A hard-coded "fresh" is worse than no label.
     */
    public function test_the_search_reports_the_real_age_of_the_rows_it_returns(): void
    {
        $cases = [
            // [medicine, generic, atc, hours old, expected bucket]
            ['Paracetamol 500mg Tablet',  'Paracetamol',  'N02BE01', 2,   'fresh'],
            ['Ibuprofen 400mg Tablet',    'Ibuprofen',    'M01AE01', 36,  'recent'],
            ['Ceftriaxone 1g Injection',  'Ceftriaxone',  'J01DD04', 120, 'stale'],
        ];

        foreach ($cases as $index => [$name, $generic, $atc, $hoursAgo, $expected]) {
            [, $listing] = $this->pharmacy("Pharmacie {$generic}", 4.05 + ($index / 1000), 9.70);
            $medicine    = $this->medicine($name, $generic, $atc);

            $this->reportStockAgedHours($listing, $medicine, $hoursAgo);

            $response = $this->search($generic)->assertOk();

            $this->assertCount(1, $response->json('data'), "{$generic} should return its one pharmacy");

            $this->assertSame(
                $expected,
                $response->json('meta.warning'),
                "A report {$hoursAgo}h old must be described as '{$expected}'."
            );

            // The row a patient reads must not contradict the header above it.
            $this->assertSame(
                $expected,
                $response->json('data.0.matched_stock.freshness_status'),
                'Row-level freshness and meta freshness must agree.'
            );

            $this->assertNotNull(
                $response->json('meta.last_reported_at'),
                'A result set that exists always has a real timestamp behind it.'
            );
            $this->assertSame(1, $response->json('meta.results_count'));
        }
    }

    /**
     * GUARANTEE: a stale row is never dressed up as fresh, even when it is the
     * only thing the finder has.
     *
     * WHY: the temptation is to report the newest thing available as "fresh"
     * because it is the newest. It is not fresh; it is five days old, and the
     * pharmacy may have sold out four days ago.
     */
    public function test_a_stale_stock_report_is_never_described_as_fresh(): void
    {
        [, $listing] = $this->pharmacy('Pharmacie Akwa');
        $medicine    = $this->medicine('Azithromycin 250mg Tablet', 'Azithromycin', 'J01FA10');

        $this->reportStockAgedHours($listing, $medicine, 24 * 5);

        $meta = $this->search('Azithromycin')->assertOk()->json('meta');

        $this->assertSame('stale', $meta['warning']);
        $this->assertNotSame('fresh', $meta['warning']);
        $this->assertNotSame('no_data', $meta['warning'], 'There IS data — it is simply old.');
        $this->assertNotNull($meta['last_reported_at']);
        $this->assertNotNull($meta['oldest_reported_at']);
    }

    // ══════════════════════════════════════════════════════════════════
    // Journey 4 — the prescribing chain, first link to last
    // ══════════════════════════════════════════════════════════════════

    /**
     * GUARANTEE: a clinician can issue a prescription, the patient can see that
     * exact prescription in their own portal, a pharmacy can dispense it, and a
     * second dispense is refused without disturbing the first.
     *
     * WHY: `prescriptions` had zero rows in production because the chain had no
     * first link. And on the last link, a double-clicked "Dispense" button that
     * re-stamps the record destroys the answer to "when was this medicine
     * actually handed over, and by whom" — the question asked after a patient is
     * harmed by a double dose.
     */
    public function test_a_prescription_travels_from_clinician_to_patient_to_pharmacy_exactly_once(): void
    {
        $hospital   = $this->hospital();
        $doctor     = $this->staffAt($hospital, 'doctor', 'chain.doctor@example.test');
        $pharmacist = $this->staffAt($hospital, 'pharmacist', 'chain.pharmacist@example.test');
        $medicine   = $this->medicine('Amoxicillin 500mg Capsule', 'Amoxicillin');

        $patient = Patient::factory()->create(['facility_id' => $hospital->id]);
        $patientUser = $this->patientUserFor($patient, 'chain.patient@example.test');

        // ── Link 1: the clinician issues it ───────────────────────────────
        $this->asUserOf($doctor, $hospital)
            ->post(route('portals.staff.prescriptions.store'), [
                'patient_id'    => $patient->id,
                'validity_days' => 30,
                'notes'         => 'Complete the full course.',
                'items'         => [[
                    'medicine_id'   => $medicine->id,
                    'dose'          => '500 mg',
                    'frequency'     => '3 times daily',
                    'route'         => 'oral',
                    'duration_days' => 7,
                    'quantity'      => 21,
                ]],
            ])
            ->assertRedirect(route('portals.staff.prescriptions'));

        $prescription = Prescription::where('patient_id', $patient->id)->firstOrFail();
        $this->assertSame('active', $prescription->status);
        $this->assertSame($doctor->id, $prescription->prescribed_by, 'Who prescribed it must be recorded.');
        $this->assertSame($hospital->id, $prescription->facility_id);

        // Prescribed against the CATALOGUE, so the pharmacy reads the same
        // identifier the medicine finder and the stock listing speak.
        $item = $prescription->items()->firstOrFail();
        $this->assertSame($medicine->id, $item->medicine_id);
        $this->assertSame('500 mg', $item->dose);
        $this->assertSame(7, $item->duration_days);
        $this->assertSame('pending', $item->status);

        // ── Link 2: the patient sees it in their own portal ───────────────
        $this->actingAs($patientUser)->withSession(['mfa.verified' => true])
            ->get(route('portals.patient.prescriptions'))
            ->assertOk()
            ->assertViewHas('prescriptions', fn ($list) => $list->contains('id', $prescription->id));

        // ── Link 3: the pharmacy dispenses it ─────────────────────────────
        $this->asUserOf($pharmacist, $hospital)
            ->get(route('portals.pharmacy.prescriptions'))
            ->assertOk()
            ->assertViewHas('prescriptions', fn ($list) => $list->contains('id', $prescription->id));

        $this->asUserOf($pharmacist, $hospital)
            ->post(route('portals.pharmacy.dispense', $prescription->id))
            ->assertRedirect(route('portals.pharmacy.prescriptions'))
            ->assertSessionHas('success');

        $prescription->refresh();
        $this->assertSame('dispensed', $prescription->status);
        $this->assertSame($pharmacist->id, $prescription->dispensed_by);
        $this->assertNotNull($prescription->dispensed_at);
        $this->assertSame('dispensed', $prescription->items()->firstOrFail()->status);

        $firstDispensedAt = $prescription->dispensed_at->toDateTimeString();
        $firstItemStamp   = $prescription->items()->firstOrFail()->dispensed_at->toDateTimeString();

        // ── Link 3 again: a double click, a resubmitted form, a retry ──────
        $this->asUserOf($pharmacist, $hospital)
            ->post(route('portals.pharmacy.dispense', $prescription->id))
            ->assertRedirect(route('portals.pharmacy.prescriptions'))
            ->assertSessionHas('error');

        $prescription->refresh();
        $this->assertSame('dispensed', $prescription->status);
        $this->assertSame(
            $firstDispensedAt,
            $prescription->dispensed_at->toDateTimeString(),
            'A repeat dispense must never re-stamp the original hand-over.'
        );
        $this->assertSame($pharmacist->id, $prescription->dispensed_by);
        $this->assertSame(
            $firstItemStamp,
            $prescription->items()->firstOrFail()->dispensed_at->toDateTimeString(),
            'The dispensed line is part of the record too.'
        );

        $this->assertSame(
            1,
            AuditEvent::where('action_type', 'prescription_dispensed')
                ->where('resource_id', $prescription->id)
                ->count(),
            'Exactly one dispense may ever be recorded against a prescription.'
        );
    }

    // ══════════════════════════════════════════════════════════════════
    // Journey 5 — a prescription is an immutable clinical event
    // ══════════════════════════════════════════════════════════════════

    /**
     * GUARANTEE: a prescription is never deleted and never silently rewritten.
     * A mistake is corrected by void / entered-in-error / amend, and the record
     * — including its lines — survives every one of those.
     *
     * WHY: the prescription is the evidence. If a wrong dose can be edited away
     * or the row dropped, then nobody can reconstruct what a patient was
     * actually told to take on the day they were harmed, and the clinician who
     * wrote it cannot be distinguished from the one who corrected it.
     */
    public function test_a_prescription_is_corrected_by_voiding_and_survives_intact(): void
    {
        $hospital   = $this->hospital('Clinique du Littoral');
        $doctor     = $this->staffAt($hospital, 'doctor', 'immutable.doctor@example.test');
        $pharmacist = $this->staffAt($hospital, 'pharmacist', 'immutable.pharmacist@example.test');
        $medicine   = $this->medicine('Amoxicillin 500mg Capsule', 'Amoxicillin');
        $patient    = Patient::factory()->create(['facility_id' => $hospital->id]);
        $stranger   = Patient::factory()->create(['facility_id' => $hospital->id]);

        $this->asUserOf($doctor, $hospital)
            ->post(route('portals.staff.prescriptions.store'), [
                'patient_id' => $patient->id,
                'items'      => [[
                    'medicine_id' => $medicine->id,
                    'dose'        => '500 mg',
                    'frequency'   => '3 times daily',
                ]],
            ])
            ->assertRedirect(route('portals.staff.prescriptions'));

        $prescription = Prescription::where('patient_id', $patient->id)->firstOrFail();
        $item         = $prescription->items()->firstOrFail();

        // There is no delete, at any level.
        try {
            $prescription->delete();
            $this->fail('Hard-deleting a prescription must be refused.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('cannot be deleted', $e->getMessage());
        }

        // And no silent re-pointing at a different patient.
        try {
            $prescription->patient_id = $stranger->id;
            $prescription->save();
            $this->fail('Re-assigning the patient on a prescription must be refused.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('immutable', $e->getMessage());
        }

        // Correction is a documented void — the supported route.
        $this->asUserOf($doctor, $hospital)
            ->post(route('portals.staff.prescriptions.void', $prescription->id), [
                'void_reason' => 'Wrong strength ordered — a corrected prescription follows.',
            ])
            ->assertRedirect(route('portals.staff.prescriptions'));

        $prescription->refresh();
        $this->assertSame('voided', $prescription->status);
        $this->assertSame($doctor->id, $prescription->voided_by);
        $this->assertNotNull($prescription->voided_at);
        $this->assertStringContainsString('Wrong strength', $prescription->void_reason);

        // The evidence survives in full: the row, the patient it was for, and
        // the exact line that was written.
        $this->assertDatabaseHas('prescriptions', [
            'id'         => $prescription->id,
            'patient_id' => $patient->id,
        ]);
        $this->assertDatabaseHas('prescription_items', [
            'id'          => $item->id,
            'dose'        => '500 mg',
            'medicine_id' => $medicine->id,
        ]);
        $this->assertSame(1, PrescriptionItem::where('prescription_id', $prescription->id)->count());

        // A voided prescription can no longer be handed over.
        $this->asUserOf($pharmacist, $hospital)
            ->post(route('portals.pharmacy.dispense', $prescription->id))
            ->assertSessionHas('error');

        $this->assertSame('voided', $prescription->refresh()->status);
        $this->assertNull($prescription->dispensed_at);
    }

    // ══════════════════════════════════════════════════════════════════
    // Journey 6 — the API and the portal agree
    // ══════════════════════════════════════════════════════════════════

    /**
     * GUARANTEE: a prescription created over `POST /v1/prescriptions` persists a
     * REAL prescriber and REAL item doses.
     *
     * WHY: this is the exact historical bug, asserted at the only level that
     * would have caught it. The endpoint took `prescriber_id` (not a column;
     * the model's fillable has `prescribed_by`) and item keys `dosage` /
     * `duration` (columns are `dose` / `duration_days`). Mass assignment
     * dropped every one of them and the endpoint still answered 201, so a
     * partner hospital's whole prescribing history was stored with no
     * prescriber and no dose — and nobody knew, because the request said it
     * worked. Asserting the RESPONSE would have caught nothing; only the stored
     * row tells the truth.
     */
    public function test_the_api_prescribing_endpoint_persists_a_real_prescriber_and_real_doses(): void
    {
        $hospital = $this->hospital('Hopital General de Douala');
        $doctor   = $this->staffAt($hospital, 'doctor', 'api.doctor@example.test');
        $medicine = $this->medicine('Amoxicillin 500mg Capsule', 'Amoxicillin');
        $patient  = Patient::factory()->create(['facility_id' => $hospital->id]);

        $response = $this->withHeaders(self::API_HEADERS)
            ->postJson('/v1/prescriptions', [
                // Scopes the middleware's test client to this real facility.
                'facility_id'   => $hospital->id,
                'patient_id'    => $patient->id,
                'prescriber_id' => $doctor->id,
                'notes'         => 'Issued by an integrated partner system.',
                'items'         => [[
                    'medicine_id' => $medicine->id,
                    'dosage'      => '500 mg',      // historical alias of `dose`
                    'frequency'   => 'twice daily',
                    'duration'    => '5 days',      // historical alias of `duration_days`
                    'quantity'    => 10,
                    'route'       => 'oral',
                ]],
            ])
            ->assertStatus(201);

        $prescriptionId = $response->json('data.id');
        $this->assertNotNull($prescriptionId);

        // The stored row — not the echoed response — is the assertion that counts.
        $stored = Prescription::findOrFail($prescriptionId);
        $this->assertSame(
            $doctor->id,
            $stored->prescribed_by,
            'A prescription with no prescriber cannot be questioned, corrected, or defended.'
        );
        $this->assertSame($hospital->id, $stored->facility_id);
        $this->assertSame($patient->id, $stored->patient_id);
        $this->assertSame('active', $stored->status);

        $item = $stored->items()->firstOrFail();
        $this->assertSame('500 mg', $item->dose, 'A prescription line with no dose cannot be dispensed safely.');
        $this->assertSame(5, $item->duration_days);
        $this->assertSame($medicine->id, $item->medicine_id);
        $this->assertSame('Amoxicillin 500mg Capsule', $item->drug_name);
        $this->assertSame('J01CA04', $item->drug_code);

        // Belt and braces: nothing landed with the null prescriber the bug produced.
        $this->assertSame(
            0,
            Prescription::whereNull('prescribed_by')->count(),
            'No prescription may be stored without a prescriber.'
        );
    }

    /**
     * GUARANTEE: the API and the portal produce the SAME stored prescription —
     * both go through PrescriptionService, so they cannot drift.
     *
     * WHY: two write paths for one clinical record is how a field ends up
     * populated on one and null on the other. A pharmacy reading its queue must
     * not be able to tell whether the prescription arrived from a clinician's
     * screen or a partner's integration.
     */
    public function test_the_api_and_the_portal_store_the_same_prescription(): void
    {
        $hospital = $this->hospital('Hopital de Bonassama');
        $doctor   = $this->staffAt($hospital, 'doctor', 'parity.doctor@example.test');
        $medicine = $this->medicine('Amoxicillin 500mg Capsule', 'Amoxicillin');

        $viaPortal = Patient::factory()->create(['facility_id' => $hospital->id]);
        $viaApi    = Patient::factory()->create(['facility_id' => $hospital->id]);

        $this->asUserOf($doctor, $hospital)
            ->post(route('portals.staff.prescriptions.store'), [
                'patient_id' => $viaPortal->id,
                'items'      => [[
                    'medicine_id'   => $medicine->id,
                    'dose'          => '500 mg',
                    'frequency'     => 'twice daily',
                    'duration_days' => 5,
                    'quantity'      => 10,
                    'route'         => 'oral',
                ]],
            ])
            ->assertRedirect(route('portals.staff.prescriptions'));

        $this->withHeaders(self::API_HEADERS)
            ->postJson('/v1/prescriptions', [
                'facility_id'   => $hospital->id,
                'patient_id'    => $viaApi->id,
                'prescriber_id' => $doctor->id,
                'items'         => [[
                    'medicine_id' => $medicine->id,
                    'dosage'      => '500 mg',
                    'frequency'   => 'twice daily',
                    'duration'    => '5 days',
                    'quantity'    => 10,
                    'route'       => 'oral',
                ]],
            ])
            ->assertStatus(201);

        $portalRx = Prescription::where('patient_id', $viaPortal->id)->firstOrFail();
        $apiRx    = Prescription::where('patient_id', $viaApi->id)->firstOrFail();

        $comparable = fn (Prescription $rx) => [
            'status'        => $rx->status,
            'facility_id'   => $rx->facility_id,
            'prescribed_by' => $rx->prescribed_by,
        ];

        $this->assertSame($comparable($portalRx), $comparable($apiRx));

        $comparableItem = fn (Prescription $rx) => $rx->items()->firstOrFail()
            ->only(['medicine_id', 'drug_name', 'drug_code', 'dose', 'frequency', 'route', 'duration_days', 'quantity', 'status']);

        $this->assertSame(
            $comparableItem($portalRx),
            $comparableItem($apiRx),
            'The same order written through two doors must be stored identically.'
        );
    }
}
