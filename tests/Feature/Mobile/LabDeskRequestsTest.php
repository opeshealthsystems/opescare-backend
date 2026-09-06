<?php

namespace Tests\Feature\Mobile;

use App\Models\LabOrder;
use App\Models\LabTestRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The lab desk answers the patient's test requests: approving raises a real
 * order at this facility, declining needs a reason, and the patient hears
 * about either.
 */
class LabDeskRequestsTest extends LabDeskTestCase
{
    public function test_the_desk_lists_pending_requests_submitted_to_this_facility(): void
    {
        $urgent  = $this->request();
        $routine = $this->request(['priority' => 'routine', 'test_code' => 'FBC', 'test_name' => 'Full Blood Count', 'submitted_at' => now()->subHours(3)]);
        $decided = $this->request(['status' => 'declined']);
        $this->request(['facility_id' => $this->otherFacility->id]);
        $this->request(['facility_id' => null]);

        $response = $this->asLab('GET', '/api/mobile/lab/requests')->assertOk();

        $this->assertSame([$urgent->id, $routine->id], collect($response->json('data'))->pluck('id')->all());

        $response->assertJsonPath('data.0.test_name', 'Fasting Blood Glucose')
            ->assertJsonPath('data.0.test_code', 'GLUF')
            ->assertJsonPath('data.0.priority', 'urgent')
            ->assertJsonPath('data.0.status', 'submitted')
            ->assertJsonPath('data.0.clinical_notes', 'Repeat fasting sugar before my next visit.')
            ->assertJsonPath('data.0.patient.health_id', self::HEALTH_ID)
            ->assertJsonPath('data.0.patient.display_name', 'Amina Ngo')
            ->assertJsonMissingPath('data.0.patient.dob');

        // Decided ones on request.
        $this->assertSame([$decided->id], collect($this->asLab('GET', '/api/mobile/lab/requests?status=declined')->json('data'))->pluck('id')->all());
        $this->assertCount(3, $this->asLab('GET', '/api/mobile/lab/requests?status=all')->json('data'));
    }

    public function test_approving_raises_the_order_at_this_facility_and_tells_the_patient(): void
    {
        $request = $this->request();

        $this->asLab('POST', '/api/mobile/lab/requests/' . $request->id . '/approve')
            ->assertOk()
            ->assertJsonPath('data.id', $request->id)
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.reviewed_by_name', 'Labtech Test');

        $fresh = $request->fresh();
        $this->assertSame('approved', $fresh->status);
        $this->assertSame($this->users['labtech']->id, $fresh->reviewed_by);
        $this->assertNotNull($fresh->reviewed_at);
        $this->assertNotNull($fresh->lab_order_id);

        $order = LabOrder::find($fresh->lab_order_id);
        $this->assertNotNull($order);
        $this->assertSame($this->facility->id, $order->facility_id);
        $this->assertSame($this->patient->id, $order->patient_id);
        $this->assertSame($this->users['labtech']->id, $order->ordered_by);
        $this->assertSame('Fasting Blood Glucose', $order->test_name);
        $this->assertSame('GLUF', $order->test_code);
        $this->assertSame('urgent', $order->urgency);
        $this->assertSame('ordered', $order->status);
        $this->assertSame('Repeat fasting sugar before my next visit.', $order->clinical_indication);

        // It is now on the worklist, awaiting collection.
        $this->assertContains($order->id, collect($this->asLab('GET', '/api/mobile/lab/orders?status=ordered')->json('data'))->pluck('id')->all());

        $bell = json_decode(DB::table('notifications')->where('notifiable_id', $this->patient->id)->value('data'), true);
        $this->assertSame('lab_request_approved', $bell['type']);
        $this->assertSame($request->id, $bell['lab_test_request_id']);
        $this->assertSame($order->id, $bell['lab_order_id']);

        // A decision is final.
        $this->asLab('POST', '/api/mobile/lab/requests/' . $request->id . '/approve')
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'already_decided');
        $this->asLab('POST', '/api/mobile/lab/requests/' . $request->id . '/decline', ['reason' => 'Changed my mind.'])
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'already_decided');

        $this->assertSame(1, LabOrder::count());
        $this->assertSame(1, DB::table('notifications')->count());
    }

    public function test_declining_needs_a_reason_the_patient_will_read(): void
    {
        $request = $this->request();

        $this->asLab('POST', '/api/mobile/lab/requests/' . $request->id . '/decline')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);

        $this->asLab('POST', '/api/mobile/lab/requests/' . $request->id . '/decline', ['reason' => ''])->assertStatus(422);

        $this->assertSame('submitted', $request->fresh()->status);
        $this->assertDatabaseCount('notifications', 0);

        $this->asLab('POST', '/api/mobile/lab/requests/' . $request->id . '/decline', [
            'reason' => 'This test needs a clinician\'s order; please see your doctor first.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'declined')
            ->assertJsonPath('data.decision_reason', 'This test needs a clinician\'s order; please see your doctor first.')
            ->assertJsonPath('data.lab_order_id', null);

        $fresh = $request->fresh();
        $this->assertSame('declined', $fresh->status);
        $this->assertSame($this->users['labtech']->id, $fresh->reviewed_by);
        $this->assertNull($fresh->lab_order_id);
        $this->assertSame(0, LabOrder::count());

        $bell = json_decode(DB::table('notifications')->where('notifiable_id', $this->patient->id)->value('data'), true);
        $this->assertSame('lab_request_declined', $bell['type']);
        $this->assertStringContainsString('please see your doctor first', $bell['message']);
    }

    public function test_a_request_to_another_facility_is_not_found(): void
    {
        $elsewhere = $this->request(['facility_id' => $this->otherFacility->id]);

        $this->asLab('POST', '/api/mobile/lab/requests/' . $elsewhere->id . '/approve')
            ->assertNotFound()
            ->assertJsonPath('error_code', 'request_not_found');
        $this->asLab('POST', '/api/mobile/lab/requests/' . $elsewhere->id . '/decline', ['reason' => 'Not ours.'])
            ->assertNotFound()
            ->assertJsonPath('error_code', 'request_not_found');
        $this->asLab('POST', '/api/mobile/lab/requests/not-a-uuid/approve')->assertNotFound();
        $this->asLab('POST', '/api/mobile/lab/requests/' . Str::uuid() . '/approve')->assertNotFound();

        $this->assertSame('submitted', $elsewhere->fresh()->status);
        $this->assertSame(0, LabOrder::count());
    }

    public function test_requests_are_for_laboratory_roles_only(): void
    {
        $request = $this->request();

        foreach (['doctor', 'pharmacist', 'receptionist', 'sample_collection'] as $role) {
            $this->hit($role, 'GET', '/api/mobile/lab/requests')
                ->assertForbidden()
                ->assertJsonPath('error_code', 'role_forbidden')
                ->assertJsonPath('required', ['lab_results']);
            $this->hit($role, 'POST', '/api/mobile/lab/requests/' . $request->id . '/approve')->assertForbidden();
            $this->hit($role, 'POST', '/api/mobile/lab/requests/' . $request->id . '/decline', ['reason' => 'No.'])->assertForbidden();
        }

        $this->assertSame('submitted', LabTestRequest::find($request->id)->status);
        $this->assertSame(0, LabOrder::count());
    }

    // ── access log ────────────────────────────────────────────────────────

    public function test_the_request_list_is_logged_once_at_list_level_and_decisions_per_patient(): void
    {
        $mine = $this->request();
        foreach (range(1, 2) as $i) {
            $other = \App\Models\Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]);
            $this->request(['patient_id' => $other->id]);
        }

        $this->asLab('GET', '/api/mobile/lab/requests')->assertOk()->assertJsonPath('meta.count', 3);

        $this->assertSame(1, DB::table('access_logs')->count());
        $this->assertDatabaseHas('access_logs', [
            'purpose'       => 'lab_worklist',
            'patient_id'    => null,
            'actor_id'      => $this->users['labtech']->id,
            'facility_id'   => $this->facility->id,
            'resource_type' => 'LabTestRequest',
            'resource_id'   => null,
            'access_type'   => 'view',
        ]);
        $this->assertSame(0, DB::table('access_logs')->where('purpose', 'lab_desk_requests')->count());

        // A decision is about one patient and is logged for that patient.
        $this->asLab('POST', '/api/mobile/lab/requests/' . $mine->id . '/approve')->assertOk();
        $this->assertSame(1, DB::table('access_logs')->whereNotNull('patient_id')->count());
        $this->assertDatabaseHas('access_logs', [
            'purpose'     => 'lab_desk_request_approved',
            'patient_id'  => $this->patient->id,
            'resource_id' => $mine->id,
            'access_type' => 'update',
        ]);
    }
}
