<?php

namespace Tests\Feature\Prescriptions;

use App\Enums\MedicineCategory;
use App\Models\AuditEvent;
use App\Models\Facility;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Role;
use App\Models\User;
use App\Services\Clinical\PrescriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The prescribing chain, end to end.
 *
 * `prescriptions` had 0 rows in production for one reason: nothing could create
 * one. Staff could read a register, patients could read their list, pharmacies
 * could dispense — and the first link, a clinician issuing a prescription, did
 * not exist. Every test here walks the whole chain rather than a single screen,
 * because a working link that connects to nothing is what was already shipped.
 */
class PrescribingChainTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;
    private User $doctor;
    private User $pharmacist;
    private Patient $patient;
    private User $patientUser;
    private Medicine $medicine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility = Facility::create([
            'name'   => 'Hôpital Général de Douala',
            'type'   => 'hospital',
            'status' => 'active',
        ]);

        $this->doctor     = $this->staffUser('doctor', 'amina.doctor@example.test', 'Dr Amina Fouda');
        $this->pharmacist = $this->staffUser('pharmacist', 'jean.pharmacist@example.test', 'Jean Mbarga');

        $this->patient = Patient::create([
            'health_id'     => 'OC-TST-4100-0001-01',
            'first_name'    => 'Estelle',
            'last_name'     => 'Ngassa',
            'sex'           => 'female',
            'date_of_birth' => '1991-04-11',
            'facility_id'   => $this->facility->id,
            'is_demo'       => false,
        ]);

        $patientRole = Role::firstOrCreate(
            ['name' => 'patient'],
            ['description' => 'Patient', 'dashboard_profile_key' => 'patient'],
        );

        $this->patientUser = User::create([
            'name'       => 'Estelle Ngassa',
            'email'      => 'estelle.patient@example.test',
            'password'   => bcrypt('secret-pass-1234'),
            'patient_id' => $this->patient->id,
            'status'     => 'active',
        ]);
        $this->patientUser->role_id = $patientRole->id;
        $this->patientUser->save();

        $this->medicine = Medicine::create([
            'name'                  => 'Amoxicillin 500mg Capsule',
            'generic_name'          => 'Amoxicillin',
            'strength'              => '500mg',
            'form'                  => 'capsule',
            'category'              => MedicineCategory::cases()[0]->value,
            'atc_code'              => 'J01CA04',
            'prescription_required' => true,
            'default_pack_size'     => '21 capsules',
            'currency'              => 'XAF',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────

    private function staffUser(string $roleName, string $email, string $name): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['description' => ucfirst($roleName), 'dashboard_profile_key' => $roleName],
        );

        $user = User::create([
            'name'                => $name,
            'email'               => $email,
            'password'            => bcrypt('secret-pass-1234'),
            'primary_facility_id' => $this->facility->id,
            'status'              => 'active',
        ]);
        // `role_id` is deliberately not mass-assignable on User.
        $user->role_id = $role->id;
        $user->save();

        return $user;
    }

    private function asDoctor(): self
    {
        return $this->actingAs($this->doctor)
            ->withSession(['active_facility_id' => $this->facility->id]);
    }

    private function asPharmacist(): self
    {
        return $this->actingAs($this->pharmacist)
            ->withSession(['active_facility_id' => $this->facility->id]);
    }

    /** @return array<string,mixed> the payload the prescribing form submits */
    private function prescriptionPayload(array $overrides = []): array
    {
        return array_merge([
            'patient_id'    => $this->patient->id,
            'validity_days' => 30,
            'notes'         => 'Complete the full course.',
            'items'         => [
                [
                    'medicine_id'   => $this->medicine->id,
                    'dose'          => '500 mg',
                    'frequency'     => '3 times daily',
                    'route'         => 'oral',
                    'duration_days' => 7,
                    'quantity'      => 21,
                ],
            ],
        ], $overrides);
    }

    /** Issue a prescription through the portal and return it. */
    private function issueViaPortal(array $overrides = []): Prescription
    {
        $this->asDoctor()
            ->post(route('portals.staff.prescriptions.store'), $this->prescriptionPayload($overrides))
            ->assertRedirect(route('portals.staff.prescriptions'));

        return Prescription::latest('created_at')->firstOrFail();
    }

    // ──────────────────────────────────────────────────────────────────
    // The chain
    // ──────────────────────────────────────────────────────────────────

    public function test_the_prescribing_form_is_reachable_and_offers_this_facilitys_patients(): void
    {
        $this->asDoctor()
            ->get(route('portals.staff.prescriptions.create'))
            ->assertOk()
            ->assertViewHas('patients', fn ($patients) => $patients->contains('id', $this->patient->id))
            ->assertViewHas('medicines', fn ($medicines) => $medicines->contains('id', $this->medicine->id));
    }

    public function test_a_clinician_issues_a_prescription_and_the_patient_sees_it(): void
    {
        // BEFORE: this is the production state — nothing has ever been prescribed.
        $this->assertSame(0, Prescription::count());

        $prescription = $this->issueViaPortal();

        $this->assertSame('active', $prescription->status);
        $this->assertSame($this->patient->id, $prescription->patient_id);
        $this->assertSame($this->facility->id, $prescription->facility_id);
        $this->assertSame($this->doctor->id, $prescription->prescribed_by, 'The prescriber must be recorded.');
        $this->assertNotNull($prescription->expires_at);

        // Prescribed against the CATALOGUE, not free text — the same identifier
        // the pharmacy stock listing and the medicine finder speak.
        $item = $prescription->items()->firstOrFail();
        $this->assertSame($this->medicine->id, $item->medicine_id);
        $this->assertSame('Amoxicillin 500mg Capsule', $item->drug_name);
        $this->assertSame('J01CA04', $item->drug_code);
        $this->assertSame('500 mg', $item->dose);
        $this->assertSame(7, $item->duration_days);
        $this->assertSame('pending', $item->status);

        // AFTER: the patient's own portal now carries it.
        $this->actingAs($this->patientUser)
            ->withSession(['active_facility_id' => $this->facility->id])
            ->get(route('portals.patient.prescriptions'))
            ->assertOk()
            ->assertViewHas('prescriptions', fn ($list) => $list->contains('id', $prescription->id));
    }

    public function test_a_pharmacy_dispenses_the_prescription_and_the_dispense_is_recorded(): void
    {
        $prescription = $this->issueViaPortal();

        // The pharmacy queue shows it before dispensing.
        $this->asPharmacist()
            ->get(route('portals.pharmacy.prescriptions'))
            ->assertOk()
            ->assertViewHas('prescriptions', fn ($list) => $list->contains('id', $prescription->id));

        $this->asPharmacist()
            ->post(route('portals.pharmacy.dispense', $prescription->id))
            ->assertRedirect(route('portals.pharmacy.prescriptions'));

        $prescription->refresh();
        $this->assertSame('dispensed', $prescription->status);
        $this->assertNotNull($prescription->dispensed_at, 'When it was dispensed must be recorded.');
        $this->assertSame($this->pharmacist->id, $prescription->dispensed_by, 'Who dispensed it must be recorded.');

        // The lines are dispensed too — not just the header.
        $this->assertSame('dispensed', $prescription->items()->firstOrFail()->status);
        $this->assertNotNull($prescription->items()->firstOrFail()->dispensed_at);

        $this->assertDatabaseHas('audit_events', [
            'action_type'  => 'prescription_dispensed',
            'resource_id'  => $prescription->id,
            'patient_id'   => $this->patient->id,
        ]);
    }

    public function test_dispensing_cannot_be_silently_repeated(): void
    {
        $prescription = $this->issueViaPortal();

        $this->asPharmacist()->post(route('portals.pharmacy.dispense', $prescription->id));

        $firstDispensedAt = $prescription->refresh()->dispensed_at;

        // A second POST — a double click, a resubmitted form, a retried request.
        $this->asPharmacist()
            ->post(route('portals.pharmacy.dispense', $prescription->id))
            ->assertRedirect(route('portals.pharmacy.prescriptions'))
            ->assertSessionHas('error');

        $prescription->refresh();
        $this->assertSame('dispensed', $prescription->status);
        $this->assertEquals(
            $firstDispensedAt->toDateTimeString(),
            $prescription->dispensed_at->toDateTimeString(),
            'A repeat dispense must not re-stamp the original one.'
        );

        $this->assertSame(
            1,
            AuditEvent::where('action_type', 'prescription_dispensed')
                ->where('resource_id', $prescription->id)
                ->count(),
            'Exactly one dispense may ever be recorded against a prescription.'
        );
    }

    // ──────────────────────────────────────────────────────────────────
    // Immutability — a clinical event is corrected, never overwritten
    // ──────────────────────────────────────────────────────────────────

    public function test_a_prescription_cannot_be_hard_deleted(): void
    {
        $prescription = $this->issueViaPortal();

        try {
            $prescription->delete();
            $this->fail('Deleting a prescription must be refused.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('cannot be deleted', $e->getMessage());
        }

        $this->assertDatabaseHas('prescriptions', ['id' => $prescription->id]);

        // Its lines are just as protected.
        $item = $prescription->items()->firstOrFail();
        try {
            $item->delete();
            $this->fail('Deleting a prescription item must be refused.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('cannot be deleted', $e->getMessage());
        }
        $this->assertDatabaseHas('prescription_items', ['id' => $item->id]);
    }

    public function test_a_prescription_cannot_be_overwritten(): void
    {
        $prescription = $this->issueViaPortal();

        $otherPatient = Patient::create([
            'health_id'   => 'OC-TST-4100-0002-01',
            'first_name'  => 'Yannick',
            'last_name'   => 'Etoa',
            'sex'         => 'male',
            'facility_id' => $this->facility->id,
            'is_demo'     => false,
        ]);

        // Re-pointing a prescription at a different patient is the exact silent
        // rewrite the immutability rule exists to stop.
        try {
            $prescription->patient_id = $otherPatient->id;
            $prescription->save();
            $this->fail('Re-assigning the patient on a prescription must be refused.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('immutable', $e->getMessage());
        }

        $this->assertDatabaseHas('prescriptions', [
            'id'         => $prescription->id,
            'patient_id' => $this->patient->id,
        ]);

        // The clinical content of a line is frozen the same way.
        $item = $prescription->items()->firstOrFail();
        try {
            $item->dose = '1 g';
            $item->save();
            $this->fail('Rewriting a prescribed dose must be refused.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('immutable', $e->getMessage());
        }
        $this->assertDatabaseHas('prescription_items', ['id' => $item->id, 'dose' => '500 mg']);
    }

    public function test_a_dispensed_prescription_cannot_be_walked_back_to_active(): void
    {
        $prescription = $this->issueViaPortal();
        $this->asPharmacist()->post(route('portals.pharmacy.dispense', $prescription->id));
        $prescription->refresh();

        try {
            $prescription->status = 'active';
            $prescription->save();
            $this->fail('Un-dispensing a prescription must be refused.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('dispensed', $e->getMessage());
        }

        $this->assertDatabaseHas('prescriptions', ['id' => $prescription->id, 'status' => 'dispensed']);
    }

    public function test_a_mistake_is_corrected_by_voiding_not_by_deleting(): void
    {
        $prescription = $this->issueViaPortal();

        $this->asDoctor()
            ->post(route('portals.staff.prescriptions.void', $prescription->id), [
                'void_reason' => 'Wrong strength ordered — a corrected prescription follows.',
            ])
            ->assertRedirect(route('portals.staff.prescriptions'));

        $prescription->refresh();
        $this->assertSame('voided', $prescription->status);
        $this->assertNotNull($prescription->voided_at);
        $this->assertSame($this->doctor->id, $prescription->voided_by);
        $this->assertStringContainsString('Wrong strength', $prescription->void_reason);

        // The record — and its lines — survive in full.
        $this->assertDatabaseHas('prescriptions', ['id' => $prescription->id]);
        $this->assertSame(1, $prescription->items()->count());

        // And a voided prescription can no longer be dispensed.
        $this->asPharmacist()
            ->post(route('portals.pharmacy.dispense', $prescription->id))
            ->assertSessionHas('error');

        $this->assertSame('voided', $prescription->refresh()->status);
    }

    public function test_a_prescription_can_be_marked_entered_in_error(): void
    {
        $prescription = $this->issueViaPortal();

        $this->asDoctor()
            ->post(route('portals.staff.prescriptions.void', $prescription->id), [
                'void_reason'      => 'Recorded against the wrong patient at the bedside.',
                'entered_in_error' => '1',
            ])
            ->assertRedirect(route('portals.staff.prescriptions'));

        $this->assertSame('entered_in_error', $prescription->refresh()->status);
        $this->assertDatabaseHas('audit_events', [
            'action_type' => 'prescription_entered_in_error',
            'resource_id' => $prescription->id,
        ]);
    }

    public function test_an_amendment_creates_a_new_prescription_linked_to_the_original(): void
    {
        $original = $this->issueViaPortal();

        $amendment = app(PrescriptionService::class)->amend(
            $original,
            ['items' => [[
                'medicine_id' => $this->medicine->id,
                'dose'        => '250 mg',
                'frequency'   => 'twice daily',
            ]]],
            'Dose reduced after renal function review.',
            $this->doctor->id,
        );

        $this->assertSame('amended', $original->refresh()->status, 'The original is closed, not rewritten.');
        $this->assertSame($original->id, $amendment->amends_prescription_id);
        $this->assertSame('active', $amendment->status);
        $this->assertSame('250 mg', $amendment->items()->firstOrFail()->dose);

        // Both records exist — the history is the point.
        $this->assertSame(2, Prescription::count());
    }

    // ──────────────────────────────────────────────────────────────────
    // Audit + consent-equivalent scoping
    // ──────────────────────────────────────────────────────────────────

    public function test_issuing_a_prescription_is_audited(): void
    {
        $prescription = $this->issueViaPortal();

        $this->assertDatabaseHas('audit_events', [
            'action_type'   => 'prescription_issued',
            'resource_type' => 'Prescription',
            'resource_id'   => $prescription->id,
            'patient_id'    => $this->patient->id,
            'facility_id'   => $this->facility->id,
            'actor_id'      => $this->doctor->id,
        ]);

        $this->assertDatabaseHas('audit_events', [
            'action_type' => 'staff_prescription_issued',
            'resource_id' => $prescription->id,
            'actor_id'    => $this->doctor->id,
        ]);
    }

    public function test_a_clinician_cannot_prescribe_for_a_patient_outside_their_facility(): void
    {
        $otherFacility = Facility::create([
            'name'   => 'Clinique du Littoral',
            'type'   => 'clinic',
            'status' => 'active',
        ]);

        $stranger = Patient::create([
            'health_id'   => 'OC-TST-4100-0003-01',
            'first_name'  => 'Clarisse',
            'last_name'   => 'Bekolo',
            'sex'         => 'female',
            'facility_id' => $otherFacility->id,
            'is_demo'     => false,
        ]);

        $this->asDoctor()
            ->post(route('portals.staff.prescriptions.store'), $this->prescriptionPayload([
                'patient_id' => $stranger->id,
            ]))
            ->assertForbidden();

        $this->assertSame(0, Prescription::where('patient_id', $stranger->id)->count());

        // The form does not even offer them.
        $this->asDoctor()
            ->get(route('portals.staff.prescriptions.create'))
            ->assertViewHas('patients', fn ($patients) => ! $patients->contains('id', $stranger->id));
    }

    public function test_a_patient_reachable_only_by_consent_grant_can_be_prescribed_for(): void
    {
        $otherFacility = Facility::create([
            'name'   => 'Clinique du Littoral',
            'type'   => 'clinic',
            'status' => 'active',
        ]);

        $referred = Patient::create([
            'health_id'   => 'OC-TST-4100-0004-01',
            'first_name'  => 'Serge',
            'last_name'   => 'Manga',
            'sex'         => 'male',
            'facility_id' => $otherFacility->id,
            'is_demo'     => false,
        ]);

        DB::table('consent_grants')->insert([
            'id'                => (string) \Illuminate\Support\Str::uuid(),
            'patient_id'        => $referred->id,
            'facility_id'       => $this->facility->id,
            'authorizing_actor' => 'patient',
            'scope'             => json_encode(['patients:read', 'patients:write']),
            'status'            => 'active',
            'expires_at'        => now()->addYear(),
            'created_at'        => now(),
            'updated_at'        => now(),
            'is_demo'           => false,
        ]);

        $this->asDoctor()
            ->post(route('portals.staff.prescriptions.store'), $this->prescriptionPayload([
                'patient_id' => $referred->id,
            ]))
            ->assertRedirect(route('portals.staff.prescriptions'));

        $this->assertSame(1, Prescription::where('patient_id', $referred->id)->count());
    }

    public function test_prescribing_requires_a_medicine_from_the_catalogue(): void
    {
        $this->asDoctor()
            ->post(route('portals.staff.prescriptions.store'), $this->prescriptionPayload([
                'items' => [[
                    'medicine_id' => (string) \Illuminate\Support\Str::uuid(),
                    'frequency'   => 'once daily',
                ]],
            ]))
            ->assertSessionHasErrors('items.0.medicine_id');

        $this->assertSame(0, Prescription::count());
    }

    // ──────────────────────────────────────────────────────────────────
    // Portal and API share one write path
    // ──────────────────────────────────────────────────────────────────

    public function test_the_api_field_aliases_now_persist_through_the_shared_service(): void
    {
        // The `dosage` / `duration` keys are what POST /v1/prescriptions has
        // always accepted. They are not columns, so mass assignment used to drop
        // them: every API prescription had items with a null dose. The shared
        // service normalises them, which is the whole point of extracting it.
        $prescription = app(PrescriptionService::class)->issue([
            'patient_id'    => $this->patient->id,
            'facility_id'   => $this->facility->id,
            'prescribed_by' => $this->doctor->id,
            'items'         => [[
                'medicine_id' => $this->medicine->id,
                'dosage'      => '500 mg',
                'frequency'   => 'twice daily',
                'duration'    => '5 days',
                'quantity'    => 10,
            ]],
        ], $this->doctor->id);

        $item = $prescription->items()->firstOrFail();
        $this->assertSame('500 mg', $item->dose);
        $this->assertSame(5, $item->duration_days);
        $this->assertSame($this->medicine->id, $item->medicine_id);
        $this->assertSame($this->doctor->id, $prescription->prescribed_by);
    }

    public function test_the_service_falls_back_to_the_catalogue_for_name_code_and_strength(): void
    {
        $prescription = app(PrescriptionService::class)->issue([
            'patient_id'  => $this->patient->id,
            'facility_id' => $this->facility->id,
            'items'       => [['medicine_id' => $this->medicine->id, 'frequency' => 'once daily']],
        ]);

        $item = $prescription->items()->firstOrFail();
        $this->assertSame('Amoxicillin 500mg Capsule', $item->drug_name);
        $this->assertSame('J01CA04', $item->drug_code);
        $this->assertSame('500mg', $item->dose, 'The catalogue strength stands in when no dose is typed.');
    }

    public function test_an_expired_prescription_cannot_be_dispensed(): void
    {
        $prescription = $this->issueViaPortal();

        // Expiry is not an immutable attribute — but reaching it must close the
        // door on dispensing.
        DB::table('prescriptions')
            ->where('id', $prescription->id)
            ->update(['expires_at' => now()->subDay()]);

        $this->asPharmacist()
            ->post(route('portals.pharmacy.dispense', $prescription->id))
            ->assertSessionHas('error');

        $this->assertSame('active', $prescription->refresh()->status);
        $this->assertNull($prescription->dispensed_at);
    }

    public function test_the_prescribing_page_is_linked_from_the_prescriber_sidebar(): void
    {
        // A feature reachable only by URL is not shipped.
        $this->asDoctor()
            ->get(route('portals.staff.prescriptions'))
            ->assertOk()
            ->assertSee(route('portals.staff.prescriptions.create'), false);
    }

    public function test_prescription_items_are_never_orphaned_from_their_catalogue_link(): void
    {
        $prescription = $this->issueViaPortal();

        $this->assertSame(
            1,
            PrescriptionItem::where('prescription_id', $prescription->id)
                ->whereNotNull('medicine_id')
                ->count(),
            'Every prescribed line must carry the catalogue id the pharmacy reads.'
        );
    }
}
