<?php

namespace Tests\Feature\Admin;

use App\Models\AuditEvent;
use App\Models\CareFacility;
use App\Models\CodeSystemMapping;
use App\Models\Facility;
use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\LabTestAvailability;
use App\Models\Role;
use App\Models\User;
use App\Services\Terminology\TerminologyApplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The clinical terminology desk at /admin/terminology.
 *
 * Approving a mapping is a claim about a code; applying one writes that code
 * onto patients' laboratory results. The things pinned here are the ones that
 * would be silent and wrong if they regressed:
 *
 *  - only a platform admin gets in, and refusal is 403, not 404;
 *  - a mapping that has not been approved cannot be applied;
 *  - a code already on a record is never silently replaced;
 *  - a `lab_result` mapping does not cross a unit boundary — creatinine in
 *    umol/L and in mg/dL are different codes and matching on the analyte name
 *    alone is how a molar result acquires a mass-concentration code;
 *  - a code that fails its own check digit is refused at APPLY time, not
 *    merely when it was proposed;
 *  - and applying a code changes the code and nothing else. Not the value,
 *    not the unit, not the reference range, not the flag, not resulted_at.
 */
class TerminologyDeskTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role, ?string $primaryFacilityId = null): User
    {
        return User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role)])->id,
            'primary_facility_id' => $primaryFacilityId,
        ]);
    }

    private function admin(): User
    {
        return $this->userWithRole('super_admin', Facility::factory()->create()->id);
    }

    /** A LOINC mapping for an analyte. 718-7 is haemoglobin in g/dL. */
    private function mapping(array $attrs = []): CodeSystemMapping
    {
        return CodeSystemMapping::create(array_merge([
            'local_code'         => 'Haemoglobin',
            'local_name'         => 'Haemoglobin',
            'local_unit'         => 'g/dL',
            'resource_type'      => 'lab_result',
            'standard_system'    => 'LOINC',
            'standard_code'      => '718-7',
            'standard_display'   => 'Hemoglobin [Mass/volume] in Blood',
            'mapping_confidence' => 'high',
            'status'             => 'pending',
            'created_by'         => 'test',
        ], $attrs));
    }

    private function labResult(array $attrs = []): LabResult
    {
        $order = LabOrder::factory()->create();

        return LabResult::factory()->create(array_merge([
            'lab_order_id'    => $order->id,
            'patient_id'      => $order->patient_id,
            'parameter_name'  => 'Haemoglobin',
            'value'           => '13.4',
            'unit'            => 'g/dL',
            'reference_range' => '12.0-16.0',
            'flag'            => null,
            'loinc_code'      => null,
            'loinc_display'   => null,
        ], $attrs));
    }

    private function availability(array $attrs = []): LabTestAvailability
    {
        $listing = CareFacility::create([
            'facility_id'    => Facility::factory()->create()->id,
            'facility_name'  => 'Laboratoire Central',
            'facility_type'  => 'laboratory',
            'listing_status' => 'active',
            'country_code'   => 'CM',
            'region'         => 'Centre',
            'city'           => 'Yaounde',
            'address'        => 'Rue de la Reunification',
            'phone_primary'  => '+237222234000',
        ]);

        return LabTestAvailability::create(array_merge([
            'facility_id'     => $listing->id,
            'test_name'       => 'Full Blood Count',
            'local_test_code' => 'FBC',
            'loinc_code'      => null,
        ], $attrs));
    }

    private function applier(): TerminologyApplier
    {
        return app(TerminologyApplier::class);
    }

    // -- Access ---------------------------------------------------------------

    public function test_a_non_platform_admin_is_refused_with_403_not_404(): void
    {
        $facility = Facility::factory()->create();
        $mapping = $this->mapping();

        foreach (['clinic_admin', 'hospital_admin', 'doctor', 'lab_technician', 'patient'] as $role) {
            $user = $this->userWithRole($role, $facility->id);

            $this->actingAs($user)->get('/admin/terminology')->assertStatus(403);
            $this->actingAs($user)->get('/admin/terminology/log')->assertStatus(403);
            $this->actingAs($user)->get("/admin/terminology/{$mapping->id}")->assertStatus(403);
            $this->actingAs($user)->post("/admin/terminology/{$mapping->id}/approve")->assertStatus(403);
            $this->actingAs($user)->post("/admin/terminology/{$mapping->id}/apply")->assertStatus(403);
        }

        $this->assertSame('pending', $mapping->fresh()->status);
    }

    public function test_a_guest_is_sent_to_login_not_shown_the_desk(): void
    {
        $this->get('/admin/terminology')->assertRedirect();
        $this->assertGuest();
    }

    public function test_a_platform_admin_sees_the_queue_and_a_mapping(): void
    {
        $mapping = $this->mapping();

        $this->actingAs($this->admin())->get('/admin/terminology')->assertOk()->assertSee('718-7');
        $this->actingAs($this->admin())->get("/admin/terminology/{$mapping->id}")->assertOk();
        $this->actingAs($this->admin())->get('/admin/terminology/log')->assertOk();
    }

    public function test_a_malformed_mapping_id_is_a_404_not_a_database_cast_error(): void
    {
        $this->actingAs($this->admin())->get('/admin/terminology/not-a-uuid')->assertNotFound();
        $this->actingAs($this->admin())->post('/admin/terminology/not-a-uuid/apply')->assertNotFound();
    }

    // -- facility_id is never taken from the request ---------------------------

    public function test_a_facility_id_in_the_query_or_body_never_reaches_the_audit_trail(): void
    {
        $theirs = Facility::factory()->create();
        $mapping = $this->mapping(['status' => 'approved']);
        $this->labResult();

        $this->actingAs($this->admin())
            ->get('/admin/terminology?facility_id='.$theirs->id)
            ->assertOk();

        $this->actingAs($this->admin())
            ->post("/admin/terminology/{$mapping->id}/apply", ['facility_id' => $theirs->id])
            ->assertRedirect();

        $this->assertSame(
            0,
            AuditEvent::where('facility_id', $theirs->id)->count(),
            'A facility id supplied by the request must never be stamped on an audit row.'
        );
    }

    // -- Review ---------------------------------------------------------------

    public function test_approving_records_the_reviewer_and_writes_nothing_to_a_record(): void
    {
        $admin = $this->admin();
        $mapping = $this->mapping();
        $result = $this->labResult();

        $this->actingAs($admin)->post("/admin/terminology/{$mapping->id}/approve")->assertRedirect();

        $mapping->refresh();
        $this->assertSame('approved', $mapping->status);
        $this->assertSame((string) $admin->id, $mapping->approved_by);
        $this->assertNotNull($mapping->approved_at);

        // Approval is a statement about the mapping. It is NOT an application.
        $this->assertNull($result->fresh()->loinc_code);
    }

    public function test_rejecting_requires_a_reason_and_keeps_it_on_the_record(): void
    {
        $admin = $this->admin();
        $mapping = $this->mapping(['notes' => 'An alternative code exists.']);

        $this->actingAs($admin)
            ->post("/admin/terminology/{$mapping->id}/reject", ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertSame('pending', $mapping->fresh()->status);

        $this->actingAs($admin)
            ->post("/admin/terminology/{$mapping->id}/reject", ['reason' => 'The panel code is the better fit here.'])
            ->assertRedirect();

        $mapping->refresh();
        $this->assertSame('rejected', $mapping->status);
        $this->assertStringContainsString('An alternative code exists.', $mapping->notes);
        $this->assertStringContainsString('better fit', $mapping->notes);
        $this->assertSame(1, AuditEvent::where('action_type', 'terminology.rejected')->count());
    }

    // -- The applier refuses what it should ------------------------------------

    public function test_a_pending_mapping_cannot_be_applied(): void
    {
        $mapping = $this->mapping(['status' => 'pending']);
        $result = $this->labResult();

        $out = $this->applier()->apply($mapping, null, null, null);

        $this->assertSame('terminology.refuse_not_approved', $out['refused']);
        $this->assertSame(0, $out['applied']);
        $this->assertNull($result->fresh()->loinc_code);

        $this->actingAs($this->admin())
            ->post("/admin/terminology/{$mapping->id}/apply")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNull($result->fresh()->loinc_code);
    }

    public function test_a_rejected_mapping_cannot_be_applied(): void
    {
        $mapping = $this->mapping(['status' => 'rejected']);
        $result = $this->labResult();

        $this->assertSame('terminology.refuse_not_approved', $this->applier()->apply($mapping, null, null, null)['refused']);
        $this->assertNull($result->fresh()->loinc_code);
    }

    public function test_an_approved_mapping_can_be_applied(): void
    {
        $admin = $this->admin();
        $mapping = $this->mapping(['status' => 'approved']);
        $result = $this->labResult();

        $this->actingAs($admin)
            ->post("/admin/terminology/{$mapping->id}/apply")
            ->assertRedirect()
            ->assertSessionHas('success');

        $result->refresh();
        $this->assertSame('718-7', $result->loinc_code);
        $this->assertSame('Hemoglobin [Mass/volume] in Blood', $result->loinc_display);

        // Traceable: the code on this result names its mapping and its actor.
        $event = AuditEvent::where('action_type', TerminologyApplier::ACTION_APPLY)->firstOrFail();
        $this->assertSame((string) $mapping->id, $event->resource_id);
        $this->assertSame((string) $admin->id, $event->actor_id);
        $this->assertSame((string) $result->patient_id, $event->patient_id);
        $this->assertNull($event->before_state['loinc_code']);
        $this->assertSame('718-7', $event->after_state['loinc_code']);
    }

    public function test_a_lab_order_mapping_lands_on_lab_test_availability(): void
    {
        $mapping = $this->mapping([
            'local_code'       => 'FBC',
            'local_name'       => 'Full Blood Count',
            'local_unit'       => null,
            'resource_type'    => 'lab_order',
            'standard_code'    => '58410-2',
            'standard_display' => 'CBC panel - Blood by Automated count',
            'status'           => 'approved',
        ]);
        $row = $this->availability();

        $out = $this->applier()->apply($mapping, null, null, null);

        $this->assertSame(1, $out['applied']);
        $this->assertSame('58410-2', $row->fresh()->loinc_code);
    }

    public function test_a_condition_mapping_has_no_target_and_is_refused(): void
    {
        // ICD-10 already lives on `diagnoses.code`. There is no separate column
        // to fill, and inventing a destination would be worse than refusing.
        $mapping = $this->mapping([
            'local_code'      => 'E11',
            'resource_type'   => 'condition',
            'standard_system' => 'ICD-10',
            'standard_code'   => 'E11',
            'local_unit'      => null,
            'status'          => 'approved',
        ]);

        $this->assertSame('terminology.refuse_no_target', $this->applier()->apply($mapping, null, null, null)['refused']);
    }

    // -- Never a silent overwrite ---------------------------------------------

    public function test_an_existing_differing_code_is_never_silently_overwritten(): void
    {
        $mapping = $this->mapping(['status' => 'approved']);
        $result = $this->labResult(['loinc_code' => '30313-1', 'loinc_display' => 'Something else']);

        $out = $this->applier()->apply($mapping, null, null, null);

        $this->assertSame(0, $out['applied']);
        $this->assertSame(0, $out['recoded']);
        $this->assertSame(1, $out['skipped'][TerminologyApplier::CONFLICT]);
        $this->assertSame('30313-1', $result->fresh()->loinc_code);
    }

    public function test_an_existing_code_is_replaced_only_when_the_operator_confirms_it(): void
    {
        $mapping = $this->mapping(['status' => 'approved']);
        $result = $this->labResult(['loinc_code' => '30313-1', 'loinc_display' => 'Something else']);

        $out = $this->applier()->apply($mapping, null, null, null, confirmRecode: true);

        $this->assertSame(1, $out['recoded']);
        $this->assertSame('718-7', $result->fresh()->loinc_code);

        // The replaced code is on the record, so the change can be undone.
        $event = AuditEvent::where('action_type', TerminologyApplier::ACTION_APPLY)->firstOrFail();
        $this->assertSame('30313-1', $event->before_state['loinc_code']);
    }

    public function test_a_row_already_carrying_this_exact_code_is_left_alone(): void
    {
        $mapping = $this->mapping(['status' => 'approved']);
        $this->labResult(['loinc_code' => '718-7', 'loinc_display' => 'Hemoglobin [Mass/volume] in Blood']);

        $out = $this->applier()->apply($mapping, null, null, null);

        $this->assertSame(0, $out['applied']);
        $this->assertSame(1, $out['skipped'][TerminologyApplier::ALREADY]);
    }

    // -- The unit is part of the mapping --------------------------------------

    public function test_a_lab_result_mapping_does_not_apply_across_a_unit_mismatch(): void
    {
        // Creatinine in umol/L is 14682-9; in mg/dL it is a different code
        // entirely. Matching on the analyte name alone is exactly how a molar
        // result acquires a mass-concentration code.
        $mapping = $this->mapping([
            'local_code'       => 'Creatinine',
            'local_name'       => 'Creatinine',
            'local_unit'       => 'umol/L',
            'standard_code'    => '14682-9',
            'standard_display' => 'Creatinine [Moles/volume] in Serum or Plasma',
            'status'           => 'approved',
        ]);

        $molar = $this->labResult(['parameter_name' => 'Creatinine', 'unit' => 'umol/L', 'value' => '78']);
        $mass  = $this->labResult(['parameter_name' => 'Creatinine', 'unit' => 'mg/dL',  'value' => '0.9']);

        $out = $this->applier()->apply($mapping, null, null, null);

        $this->assertSame(1, $out['applied']);
        $this->assertSame(1, $out['skipped'][TerminologyApplier::UNIT_MISMATCH]);

        $this->assertSame('14682-9', $molar->fresh()->loinc_code);
        $this->assertNull($mass->fresh()->loinc_code, 'The mg/dL result must not receive the molar code.');
    }

    public function test_a_unit_mismatch_is_reported_not_hidden(): void
    {
        $mapping = $this->mapping(['local_unit' => 'umol/L', 'status' => 'approved']);
        $this->labResult(['unit' => 'g/dL']);

        $radius = $this->applier()->radius($mapping);

        $this->assertSame(1, $radius['total']);
        $this->assertSame(1, $radius[TerminologyApplier::UNIT_MISMATCH]);
        $this->assertSame(0, $radius[TerminologyApplier::WRITE]);
    }

    // -- Validation happens at apply time -------------------------------------

    public function test_a_code_failing_clinical_code_validation_is_refused_at_apply_time(): void
    {
        // 70563-7 was offered for a malaria rapid test and fails its own LOINC
        // check digit — it is the code the seeder refused to write. A mapping
        // carrying it must be refused again HERE, at the moment of writing,
        // not merely when it was proposed.
        $mapping = $this->mapping(['status' => 'approved', 'standard_code' => '70563-7']);
        $result = $this->labResult();

        $out = $this->applier()->apply($mapping, null, null, null);

        $this->assertSame('terminology.refuse_malformed_code', $out['refused']);
        $this->assertNull($result->fresh()->loinc_code);
    }

    public function test_a_malformed_code_cannot_even_be_approved(): void
    {
        $mapping = $this->mapping(['standard_code' => '70563-7']);

        $this->actingAs($this->admin())
            ->post("/admin/terminology/{$mapping->id}/approve")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('pending', $mapping->fresh()->status);
    }

    public function test_a_non_loinc_code_is_never_written_into_a_loinc_column(): void
    {
        $mapping = $this->mapping([
            'status'          => 'approved',
            'standard_system' => 'ICD-10',
            'standard_code'   => 'E11',
        ]);
        $result = $this->labResult();

        $this->assertSame('terminology.refuse_wrong_system', $this->applier()->apply($mapping, null, null, null)['refused']);
        $this->assertNull($result->fresh()->loinc_code);
    }

    // -- Applying adds a code and nothing else --------------------------------

    public function test_applying_never_alters_a_results_value_unit_range_flag_or_timestamp(): void
    {
        $mapping = $this->mapping(['status' => 'approved']);
        $result = $this->labResult(['value' => '13.4', 'unit' => 'g/dL', 'reference_range' => '12.0-16.0', 'flag' => 'H']);

        $before = $result->only(['value', 'unit', 'reference_range', 'flag', 'notes', 'patient_id', 'lab_order_id', 'parameter_name']);
        $resultedAt = $result->resulted_at;

        $this->applier()->apply($mapping, null, null, null);

        $result->refresh();

        $this->assertSame('718-7', $result->loinc_code);
        $this->assertSame(
            $before,
            $result->only(['value', 'unit', 'reference_range', 'flag', 'notes', 'patient_id', 'lab_order_id', 'parameter_name']),
            'Applying a terminology code must add a code and change nothing else about the result.'
        );
        $this->assertEquals(
            $resultedAt->toDateTimeString(),
            $result->resulted_at->toDateTimeString(),
            'resulted_at must not move: the results inbox fingerprints it and would re-raise every acknowledgement.'
        );
    }

    public function test_applying_twice_is_a_no_op_the_second_time(): void
    {
        $mapping = $this->mapping(['status' => 'approved']);
        $result = $this->labResult();

        $this->applier()->apply($mapping, null, null, null);
        $second = $this->applier()->apply($mapping, null, null, null);

        $this->assertSame(0, $second['applied']);
        $this->assertSame(1, $second['skipped'][TerminologyApplier::ALREADY]);
        $this->assertSame('718-7', $result->fresh()->loinc_code);
    }

    // -- Access logging --------------------------------------------------------

    public function test_reading_a_blast_radius_is_access_logged(): void
    {
        $mapping = $this->mapping();
        $this->labResult();

        $this->actingAs($this->admin())->get("/admin/terminology/{$mapping->id}")->assertOk();

        $this->assertSame(
            1,
            AuditEvent::where('action_type', TerminologyApplier::ACTION_PREVIEW)
                ->where('resource_id', $mapping->id)->count()
        );
    }

    public function test_an_apply_run_leaves_a_summary_row_naming_what_it_did(): void
    {
        $mapping = $this->mapping(['status' => 'approved']);
        $this->labResult();

        $this->applier()->apply($mapping, null, null, null);

        $run = AuditEvent::where('action_type', TerminologyApplier::ACTION_RUN)->firstOrFail();
        $this->assertSame(1, $run->after_state['applied']);
        $this->assertSame('718-7', $run->after_state['standard_code']);
        $this->assertCount(1, $run->after_state['rows']);
    }
}
