<?php

namespace Tests\Feature\Mobile;

use App\Models\LabOrder;
use App\Models\LabReferenceRange;
use App\Models\LabResult;
use App\Modules\Notifications\Models\NotificationEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The lab desk works its facility's orders: home counts, the worklist and its
 * filters, sample collection, append-only result entry, and a final release
 * that reaches the patient and the ordering clinician.
 */
class LabDeskOrdersTest extends LabDeskTestCase
{
    // ── home ──────────────────────────────────────────────────────────────

    public function test_home_counts_the_facility_worklist_and_nothing_else(): void
    {
        $this->order();                                                                        // ordered, routine
        $this->order(['urgency' => 'urgent']);                                                 // ordered, urgent
        $this->order(['status' => 'collected', 'collected_at' => now()->subMinutes(30)]);      // awaiting results
        $this->order(['status' => 'in_progress', 'collected_at' => now()->subHour()]);         // in progress
        $this->order(['status' => 'resulted', 'resulted_at' => now()->subMinutes(5)]);         // released today
        $this->order(['status' => 'resulted', 'resulted_at' => now()->subDays(2)]);            // released earlier
        $this->order(['status' => 'cancelled']);                                               // closed — nowhere
        $this->order(['facility_id' => $this->otherFacility->id, 'urgency' => 'stat']);        // another facility

        $this->request();
        $this->request(['facility_id' => $this->otherFacility->id]);
        $this->request(['status' => 'declined']);

        $this->asLab('GET', '/api/mobile/lab/home')
            ->assertOk()
            ->assertJsonPath('data.facility.id', $this->facility->id)
            ->assertJsonPath('data.facility.name', 'Laboratoire Test A')
            ->assertJsonPath('data.counts.awaiting_collection', 2)
            ->assertJsonPath('data.counts.awaiting_results', 1)
            ->assertJsonPath('data.counts.in_progress', 1)
            ->assertJsonPath('data.counts.resulted_today', 1)
            ->assertJsonPath('data.counts.urgent', 1)
            ->assertJsonPath('data.counts.requests_pending', 1);
    }

    // ── worklist ──────────────────────────────────────────────────────────

    public function test_the_worklist_is_scoped_to_the_token_facility_and_filters_by_status_and_urgency(): void
    {
        $routine   = $this->order(['ordered_at' => now()->subHours(3)]);
        $urgent    = $this->order(['urgency' => 'urgent', 'ordered_at' => now()->subHour()]);
        $collected = $this->order(['status' => 'collected', 'collected_at' => now()->subMinutes(20)]);
        $released  = $this->order(['status' => 'resulted', 'resulted_at' => now()->subMinutes(5)]);
        $this->order(['status' => 'cancelled']);
        $elsewhere = $this->order(['facility_id' => $this->otherFacility->id]);

        // Default: everything still open, urgent first, another facility's never.
        $open = $this->asLab('GET', '/api/mobile/lab/orders')->assertOk();
        $ids  = collect($open->json('data'))->pluck('id')->all();

        $this->assertSame([$urgent->id, $routine->id, $collected->id], $ids);
        $this->assertNotContains($released->id, $ids);
        $this->assertNotContains($elsewhere->id, $ids);

        // Shape of a row.
        $open->assertJsonPath('data.0.test_name', 'Full Blood Count')
            ->assertJsonPath('data.0.test_code', 'FBC')
            ->assertJsonPath('data.0.urgency', 'urgent')
            ->assertJsonPath('data.0.status', 'ordered')
            ->assertJsonPath('data.0.results_count', 0)
            ->assertJsonPath('data.0.ordered_by_name', 'Doctor Test')
            ->assertJsonPath('data.0.patient.health_id', self::HEALTH_ID)
            ->assertJsonPath('data.0.patient.display_name', 'Amina Ngo')
            ->assertJsonPath('data.0.patient.dob', '1990-06-15')
            ->assertJsonPath('data.0.patient.sex', 'female')
            ->assertJsonPath('data.2.status', 'collected');

        // Status filters.
        $this->assertSame([$urgent->id, $routine->id], collect($this->asLab('GET', '/api/mobile/lab/orders?status=ordered')->json('data'))->pluck('id')->all());
        $this->assertSame([$collected->id], collect($this->asLab('GET', '/api/mobile/lab/orders?status=collected')->json('data'))->pluck('id')->all());
        $this->assertSame([$released->id], collect($this->asLab('GET', '/api/mobile/lab/orders?status=resulted')->json('data'))->pluck('id')->all());
        $this->asLab('GET', '/api/mobile/lab/orders?status=resulted')->assertJsonPath('data.0.status', 'resulted');

        // Urgency filter.
        $this->assertSame([$urgent->id], collect($this->asLab('GET', '/api/mobile/lab/orders?urgency=urgent')->json('data'))->pluck('id')->all());

        // An unknown status is a validation error, not a silent "everything".
        $this->asLab('GET', '/api/mobile/lab/orders?status=whatever')->assertStatus(422);
    }

    public function test_an_order_at_another_facility_is_not_found_not_forbidden(): void
    {
        $elsewhere = $this->order(['facility_id' => $this->otherFacility->id]);

        $this->asLab('GET', '/api/mobile/lab/orders/' . $elsewhere->id)
            ->assertNotFound()
            ->assertJsonPath('error_code', 'order_not_found')
            ->assertJsonMissingPath('data');

        // Malformed and unknown ids get the same answer — never a 500.
        $this->asLab('GET', '/api/mobile/lab/orders/not-a-uuid')->assertNotFound()->assertJsonPath('error_code', 'order_not_found');
        $this->asLab('GET', '/api/mobile/lab/orders/' . Str::uuid())->assertNotFound()->assertJsonPath('error_code', 'order_not_found');

        // Every write against it is refused the same way and changes nothing.
        $this->asLab('POST', '/api/mobile/lab/orders/' . $elsewhere->id . '/collect')->assertNotFound();
        $this->asLab('POST', '/api/mobile/lab/orders/' . $elsewhere->id . '/results', ['results' => [['parameter_name' => 'Hb', 'value' => '12']]])->assertNotFound();
        $this->asLab('POST', '/api/mobile/lab/orders/' . $elsewhere->id . '/release')->assertNotFound();

        $this->assertSame('ordered', $elsewhere->fresh()->status);
        $this->assertDatabaseCount('lab_results', 0);
    }

    public function test_the_detail_carries_results_and_published_reference_ranges(): void
    {
        $order = $this->order(['status' => 'in_progress', 'collected_at' => now()->subHour()]);

        LabResult::factory()->create([
            'lab_order_id'    => $order->id,
            'patient_id'      => $this->patient->id,
            'parameter_name'  => 'Haemoglobin',
            'value'           => '9.8',
            'unit'            => 'g/dL',
            'reference_range' => '12.0–16.0',
            'flag'            => 'L',
        ]);

        LabReferenceRange::create([
            'loinc_code' => '718-7', 'test_name' => 'Haemoglobin', 'unit' => 'g/dL', 'gender' => 'female',
            'age_min' => 18, 'age_max' => 120, 'normal_low' => 12.0, 'normal_high' => 16.0, 'critical_low' => 7.0, 'critical_high' => 20.0,
        ]);
        LabReferenceRange::create([
            'loinc_code' => '718-7', 'test_name' => 'Haemoglobin', 'unit' => 'g/dL', 'gender' => 'male',
            'age_min' => 18, 'age_max' => 120, 'normal_low' => 13.0, 'normal_high' => 17.0, 'critical_low' => 7.0, 'critical_high' => 20.0,
        ]);

        $response = $this->asLab('GET', '/api/mobile/lab/orders/' . $order->id)
            ->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.results_count', 1)
            ->assertJsonPath('data.results.0.parameter_name', 'Haemoglobin')
            ->assertJsonPath('data.results.0.value', '9.8')
            ->assertJsonPath('data.results.0.flag', 'L')
            ->assertJsonPath('data.results.0.reference_range', '12.0–16.0')
            ->assertJsonCount(1, 'data.reference_ranges')
            // The patient is female: her interval, not the male one.
            ->assertJsonPath('data.reference_ranges.0.parameter_name', 'Haemoglobin')
            ->assertJsonPath('data.reference_ranges.0.unit', 'g/dL');

        // JSON carries 12.0 as 12; the bounds are numbers either way.
        $this->assertEqualsWithDelta(12.0, $response->json('data.reference_ranges.0.low'), 0.001);
        $this->assertEqualsWithDelta(16.0, $response->json('data.reference_ranges.0.high'), 0.001);
    }

    // ── collect ───────────────────────────────────────────────────────────

    public function test_collecting_books_the_sample_in_once(): void
    {
        $order = $this->order();

        $this->asLab('POST', '/api/mobile/lab/orders/' . $order->id . '/collect')
            ->assertOk()
            ->assertJsonPath('data.status', 'collected');

        $fresh = $order->fresh();
        $this->assertSame('collected', $fresh->status);
        $this->assertNotNull($fresh->collected_at);

        $this->asLab('POST', '/api/mobile/lab/orders/' . $order->id . '/collect')
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'already_collected');

        $this->assertEquals($fresh->collected_at, $order->fresh()->collected_at);
    }

    public function test_a_sample_collection_account_may_collect_but_sees_no_results(): void
    {
        $order = $this->order();

        $this->hit('sample_collection', 'GET', '/api/mobile/lab/home')
            ->assertForbidden()
            ->assertJsonPath('error_code', 'role_forbidden')
            ->assertJsonPath('required', ['lab_results']);

        $this->hit('sample_collection', 'GET', '/api/mobile/lab/orders')->assertForbidden();
        $this->hit('sample_collection', 'GET', '/api/mobile/lab/orders/' . $order->id)->assertForbidden();
        $this->hit('sample_collection', 'POST', '/api/mobile/lab/orders/' . $order->id . '/results', ['results' => [['parameter_name' => 'Hb', 'value' => '12']]])->assertForbidden();
        $this->hit('sample_collection', 'POST', '/api/mobile/lab/orders/' . $order->id . '/release')->assertForbidden();

        $this->hit('sample_collection', 'POST', '/api/mobile/lab/orders/' . $order->id . '/collect')
            ->assertOk()
            ->assertJsonPath('data.status', 'collected');

        $this->assertDatabaseCount('lab_results', 0);
    }

    // ── results ───────────────────────────────────────────────────────────

    public function test_results_are_appended_never_rewritten_and_move_the_order_to_in_progress(): void
    {
        $order = $this->order(['status' => 'collected', 'collected_at' => now()->subHour()]);

        $this->asLab('POST', '/api/mobile/lab/orders/' . $order->id . '/results', [
            'results' => [
                ['parameter_name' => 'Haemoglobin', 'value' => 13.2, 'unit' => 'g/dL', 'reference_range' => '12.0–16.0', 'flag' => 'N'],
                ['parameter_name' => 'Potassium', 'value' => '6.9', 'unit' => 'mmol/L', 'flag' => 'c', 'notes' => 'Phoned to the ward.'],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.results_count', 2)
            ->assertJsonPath('data.results.0.value', '13.2')
            ->assertJsonPath('data.results.0.flag', 'N')
            ->assertJsonPath('data.results.1.flag', 'C')
            ->assertJsonPath('data.results.1.notes', 'Phoned to the ward.');

        $this->assertSame('in_progress', $order->fresh()->status);

        $first = LabResult::where('lab_order_id', $order->id)->orderBy('parameter_name')->get();
        $this->assertCount(2, $first);
        $this->assertSame($this->users['labtech']->id, $first[0]->verified_by);
        // `C` is stored as the spelling both readers of this table treat as critical.
        $this->assertSame('CRITICAL', $first->firstWhere('parameter_name', 'Potassium')->flag);

        // A correction is a NEW row; the first stays exactly as entered.
        $this->asLab('POST', '/api/mobile/lab/orders/' . $order->id . '/results', [
            'results' => [
                ['parameter_name' => 'Potassium', 'value' => '4.9', 'unit' => 'mmol/L', 'flag' => 'N', 'notes' => 'Corrected: first sample haemolysed.'],
            ],
        ])->assertCreated()->assertJsonPath('data.results_count', 3);

        $all = LabResult::where('lab_order_id', $order->id)->get();
        $this->assertCount(3, $all);
        $this->assertSame('6.9', $all->firstWhere('id', $first->firstWhere('parameter_name', 'Potassium')->id)->value);
        $this->assertSame('CRITICAL', $all->firstWhere('id', $first->firstWhere('parameter_name', 'Potassium')->id)->flag);
    }

    public function test_a_flag_outside_h_l_n_c_is_refused_and_nothing_is_written(): void
    {
        $order = $this->order(['status' => 'collected', 'collected_at' => now()->subHour()]);

        $this->asLab('POST', '/api/mobile/lab/orders/' . $order->id . '/results', [
            'results' => [['parameter_name' => 'Haemoglobin', 'value' => '13.2', 'flag' => 'HH']],
        ])->assertStatus(422)->assertJsonValidationErrors(['results.0.flag']);

        $this->asLab('POST', '/api/mobile/lab/orders/' . $order->id . '/results', [
            'results' => [['parameter_name' => 'Haemoglobin', 'value' => '13.2', 'flag' => 'X']],
        ])->assertStatus(422);

        $this->asLab('POST', '/api/mobile/lab/orders/' . $order->id . '/results', ['results' => []])->assertStatus(422);
        $this->asLab('POST', '/api/mobile/lab/orders/' . $order->id . '/results', [
            'results' => [['parameter_name' => 'Haemoglobin', 'value' => ['nested']]],
        ])->assertStatus(422);

        $this->assertDatabaseCount('lab_results', 0);
        $this->assertSame('collected', $order->fresh()->status);
    }

    public function test_a_missing_reference_range_is_filled_from_the_published_interval_for_this_patient(): void
    {
        $order = $this->order(['status' => 'collected', 'collected_at' => now()->subHour()]);

        LabReferenceRange::create([
            'loinc_code' => '2823-3', 'test_name' => 'Potassium', 'unit' => 'mmol/L', 'gender' => 'all',
            'age_min' => 0, 'age_max' => 120, 'normal_low' => 3.5, 'normal_high' => 5.1, 'critical_low' => 2.5, 'critical_high' => 6.5,
        ]);

        $this->asLab('POST', '/api/mobile/lab/orders/' . $order->id . '/results', [
            'results' => [
                ['parameter_name' => 'potassium', 'value' => '4.2'],
                ['parameter_name' => 'Ferritin', 'value' => '35', 'unit' => 'ng/mL'],
            ],
        ])->assertCreated();

        $potassium = LabResult::where('lab_order_id', $order->id)->where('parameter_name', 'potassium')->first();
        $this->assertSame('3.5–5.1', $potassium->reference_range);
        $this->assertSame('mmol/L', $potassium->unit);
        $this->assertSame('2823-3', $potassium->loinc_code);

        // No published range: nothing is invented.
        $ferritin = LabResult::where('lab_order_id', $order->id)->where('parameter_name', 'Ferritin')->first();
        $this->assertNull($ferritin->reference_range);
        $this->assertNull($ferritin->flag);
    }

    // ── release ───────────────────────────────────────────────────────────

    public function test_release_is_final_and_reaches_the_patient_and_the_ordering_clinician(): void
    {
        $order = $this->order(['status' => 'in_progress', 'collected_at' => now()->subHour()]);

        LabResult::factory()->create([
            'lab_order_id' => $order->id, 'patient_id' => $this->patient->id,
            'parameter_name' => 'Haemoglobin', 'value' => '13.1', 'unit' => 'g/dL', 'flag' => 'N', 'verified_by' => null,
        ]);

        $this->asLab('POST', '/api/mobile/lab/orders/' . $order->id . '/release')
            ->assertOk()
            ->assertJsonPath('data.status', 'resulted')
            ->assertJsonPath('data.verified_by_name', 'Labtech Test');

        $fresh = $order->fresh();
        $this->assertSame('resulted', $fresh->status);
        $this->assertNotNull($fresh->resulted_at);
        $this->assertSame($this->users['labtech']->id, $fresh->verified_by);
        $this->assertSame($this->users['labtech']->id, LabResult::where('lab_order_id', $order->id)->value('verified_by'));

        // The patient's bell — Laravel's `notifications` table, the feed the app reads.
        $bell = DB::table('notifications')
            ->where('notifiable_type', $this->patient->getMorphClass())
            ->where('notifiable_id', $this->patient->id)
            ->first();
        $this->assertNotNull($bell, 'the patient got no bell');
        $data = json_decode($bell->data, true);
        $this->assertSame('lab_result_available', $data['type']);
        $this->assertSame($order->id, $data['lab_order_id']);
        $this->assertSame('/labs/' . $order->id, $data['action_url']);
        $this->assertStringContainsString('Full Blood Count', $data['title']);
        // A lock screen is public: the bell names the test, never the value.
        $this->assertStringNotContainsString('13.1', $data['title'] . $data['message']);

        // The ordering clinician's bell — notification_events, the staff feed.
        $event = NotificationEvent::where('event_type', 'lab_result_released')
            ->where('recipient_user_id', $this->users['doctor']->id)
            ->first();
        $this->assertNotNull($event, 'the ordering clinician got no bell');
        $this->assertSame('LabOrder', $event->related_resource_type);
        $this->assertSame($order->id, (string) $event->related_resource_id);
        $this->assertSame($this->users['labtech']->id, (string) $event->actor_id);
        $this->assertSame('normal', $event->priority);

        // Released once: a second release is refused and changes nothing.
        $this->asLab('POST', '/api/mobile/lab/orders/' . $order->id . '/release')
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'already_released');

        $this->assertEquals($fresh->resulted_at, $order->fresh()->resulted_at);
        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $this->patient->id)->count());
        $this->assertSame(1, NotificationEvent::where('event_type', 'lab_result_released')->count());

        // And it is now in the ordering doctor's results inbox, as today.
        $inbox = $this->hit('doctor', 'GET', '/api/mobile/doctor/results-inbox')->assertOk();
        $this->assertContains('lab-' . $order->id, collect($inbox->json('data'))->pluck('id')->all());
    }

    public function test_a_critical_release_raises_the_clinician_bell_as_critical(): void
    {
        $order = $this->order(['status' => 'in_progress', 'collected_at' => now()->subHour()]);

        $this->asLab('POST', '/api/mobile/lab/orders/' . $order->id . '/results', [
            'results' => [['parameter_name' => 'Potassium', 'value' => '7.1', 'unit' => 'mmol/L', 'flag' => 'C']],
        ])->assertCreated();

        $this->asLab('POST', '/api/mobile/lab/orders/' . $order->id . '/release')->assertOk();

        $event = NotificationEvent::where('event_type', 'lab_result_released')->first();
        $this->assertSame('critical', $event->priority);
        $this->assertTrue((bool) (json_decode($event->payload_json, true)['is_critical'] ?? false));

        $bell = json_decode(DB::table('notifications')->where('notifiable_id', $this->patient->id)->value('data'), true);
        $this->assertSame('high', $bell['severity']);
        $this->assertStringNotContainsString('7.1', $bell['title'] . $bell['message']);
    }

    public function test_an_order_with_no_results_cannot_be_released(): void
    {
        $order = $this->order(['status' => 'collected', 'collected_at' => now()->subHour()]);

        $this->asLab('POST', '/api/mobile/lab/orders/' . $order->id . '/release')
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'no_results');

        $this->assertSame('collected', $order->fresh()->status);
        $this->assertNull($order->fresh()->resulted_at);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_a_cancelled_order_cannot_be_worked(): void
    {
        $order = $this->order(['status' => 'cancelled']);

        $this->asLab('POST', '/api/mobile/lab/orders/' . $order->id . '/collect')->assertStatus(409)->assertJsonPath('error_code', 'order_closed');
        $this->asLab('POST', '/api/mobile/lab/orders/' . $order->id . '/results', ['results' => [['parameter_name' => 'Hb', 'value' => '12']]])->assertStatus(409)->assertJsonPath('error_code', 'order_closed');
        $this->asLab('POST', '/api/mobile/lab/orders/' . $order->id . '/release')->assertStatus(409)->assertJsonPath('error_code', 'order_closed');

        $this->assertDatabaseCount('lab_results', 0);
    }

    public function test_a_correction_after_release_is_a_new_row_and_the_release_stands(): void
    {
        $order = $this->order(['status' => 'resulted', 'resulted_at' => now()->subHour(), 'collected_at' => now()->subHours(2)]);
        LabResult::factory()->create(['lab_order_id' => $order->id, 'patient_id' => $this->patient->id, 'parameter_name' => 'Haemoglobin', 'value' => '13.1', 'flag' => 'N']);

        $this->asLab('POST', '/api/mobile/lab/orders/' . $order->id . '/results', [
            'results' => [['parameter_name' => 'Haemoglobin', 'value' => '11.3', 'flag' => 'L', 'notes' => 'Corrected after analyser recalibration.']],
        ])->assertCreated()->assertJsonPath('data.status', 'resulted')->assertJsonPath('data.results_count', 2);

        $this->assertSame('resulted', $order->fresh()->status);
        $this->assertSame(2, LabResult::where('lab_order_id', $order->id)->count());
    }

    // ── role gate ─────────────────────────────────────────────────────────

    public function test_the_desk_is_for_laboratory_roles_only(): void
    {
        $order = $this->order();

        foreach (['doctor', 'pharmacist', 'receptionist'] as $role) {
            $this->hit($role, 'GET', '/api/mobile/lab/home')
                ->assertForbidden()
                ->assertJsonPath('error_code', 'role_forbidden')
                ->assertJsonPath('required', ['lab_results'])
                ->assertJsonPath('message', __('api.capability_forbidden'));

            $this->hit($role, 'GET', '/api/mobile/lab/orders')->assertForbidden();
            $this->hit($role, 'POST', '/api/mobile/lab/orders/' . $order->id . '/collect')->assertForbidden()->assertJsonPath('required', ['lab_results', 'sample_collection']);
            $this->hit($role, 'POST', '/api/mobile/lab/orders/' . $order->id . '/results', ['results' => [['parameter_name' => 'Hb', 'value' => '12']]])->assertForbidden();
            $this->hit($role, 'POST', '/api/mobile/lab/orders/' . $order->id . '/release')->assertForbidden();
        }

        $this->assertSame('ordered', $order->fresh()->status);
        $this->assertDatabaseCount('lab_results', 0);

        // No token at all: unauthenticated, not forbidden. (withHeaders() keeps
        // the last bearer on the test instance, so drop it first.)
        $this->flushHeaders()->getJson('/api/mobile/lab/home')->assertUnauthorized();
    }

    public function test_the_desk_answers_in_french_when_asked(): void
    {
        $elsewhere = $this->order(['facility_id' => $this->otherFacility->id]);

        $this->withHeaders(['Authorization' => "Bearer {$this->bearer['labtech']}", 'Accept-Language' => 'fr'])
            ->getJson('/api/mobile/lab/orders/' . $elsewhere->id)
            ->assertNotFound()
            ->assertJsonPath('message', __('lab_api.order_not_found', [], 'fr'));

        $this->assertNotSame(__('lab_api.order_not_found', [], 'fr'), __('lab_api.order_not_found', [], 'en'));
    }

    // ── access log ────────────────────────────────────────────────────────

    public function test_the_worklist_is_logged_once_at_list_level_and_single_reads_per_patient(): void
    {
        $mine = $this->order();
        foreach (range(1, 3) as $i) {
            $other = \App\Models\Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]);
            $this->order(['patient_id' => $other->id]);
        }

        $this->asLab('GET', '/api/mobile/lab/orders')->assertOk()->assertJsonPath('meta.count', 4);

        // ONE row for the list — no patient, the facility, which list — not
        // one per patient on it.
        $this->assertSame(1, DB::table('access_logs')->count());
        $this->assertDatabaseHas('access_logs', [
            'purpose'       => 'lab_worklist',
            'patient_id'    => null,
            'actor_id'      => $this->users['labtech']->id,
            'actor_type'    => 'provider',
            'facility_id'   => $this->facility->id,
            'resource_type' => 'LabOrder',
            'resource_id'   => null,
            'access_type'   => 'view',
        ]);
        $this->assertSame(0, DB::table('access_logs')->where('purpose', 'lab_desk_worklist')->count());

        // A second refresh is a second row, not four more.
        $this->asLab('GET', '/api/mobile/lab/orders?status=ordered')->assertOk();
        $this->assertSame(2, DB::table('access_logs')->where('purpose', 'lab_worklist')->count());
        $this->assertSame(0, DB::table('access_logs')->whereNotNull('patient_id')->count());

        // One order opened: ONE patient-scoped row, for THAT patient.
        $this->asLab('GET', '/api/mobile/lab/orders/' . $mine->id)->assertOk();
        $this->assertSame(1, DB::table('access_logs')->whereNotNull('patient_id')->count());
        $this->assertDatabaseHas('access_logs', [
            'purpose'     => 'lab_desk_review',
            'patient_id'  => $this->patient->id,
            'facility_id' => $this->facility->id,
            'resource_id' => $mine->id,
            'access_type' => 'view',
        ]);

        // And every write keeps its patient-scoped row.
        $this->asLab('POST', '/api/mobile/lab/orders/' . $mine->id . '/collect')->assertOk();
        $this->assertDatabaseHas('access_logs', ['purpose' => 'lab_desk_sample_collection', 'patient_id' => $this->patient->id, 'resource_id' => $mine->id, 'access_type' => 'update']);

        $this->asLab('POST', '/api/mobile/lab/orders/' . $mine->id . '/results', ['results' => [['parameter_name' => 'Hb', 'value' => '12']]])->assertCreated();
        $this->assertDatabaseHas('access_logs', ['purpose' => 'lab_desk_result_entry', 'patient_id' => $this->patient->id, 'resource_id' => $mine->id, 'access_type' => 'create']);

        $this->asLab('POST', '/api/mobile/lab/orders/' . $mine->id . '/release')->assertOk();
        $this->assertDatabaseHas('access_logs', ['purpose' => 'lab_desk_release', 'patient_id' => $this->patient->id, 'resource_id' => $mine->id, 'access_type' => 'update']);

        $this->assertSame(4, DB::table('access_logs')->whereNotNull('patient_id')->count());
        $this->assertSame(2, DB::table('access_logs')->whereNull('patient_id')->count());
    }

    // ── a session without a facility ──────────────────────────────────────

    public function test_a_token_without_a_facility_is_refused_with_the_one_shape(): void
    {
        $order = $this->order();
        DB::table('provider_access_tokens')->where('user_id', $this->users['labtech']->id)->update(['facility_id' => null]);

        foreach (['/api/mobile/lab/home', '/api/mobile/lab/orders', '/api/mobile/lab/orders/' . $order->id, '/api/mobile/lab/requests'] as $path) {
            $response = $this->asLab('GET', $path)
                ->assertStatus(422)
                ->assertJsonPath('error_code', 'facility_required')
                ->assertJsonPath('message', __('lab_api.facility_required'));
            $this->assertSame(['error_code', 'message'], array_keys($response->json()), $path);
        }

        $this->asLab('POST', '/api/mobile/lab/orders/' . $order->id . '/collect')
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'facility_required');

        $this->assertSame('ordered', $order->fresh()->status);
        $this->assertDatabaseCount('access_logs', 0);
    }
}
