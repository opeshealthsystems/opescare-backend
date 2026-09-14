<?php

namespace Tests\Feature\Lab;

use App\Models\CriticalValueAlert;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\LabTestRequest;
use App\Models\Patient;
use App\Models\ReferenceRange;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The laboratory manager's desk (routes/lab_manager.php) and the ways it is
 * not allowed to hurt anyone:
 *
 *   1. THE DASHBOARD READS LIVE TABLES, THIS FACILITY ONLY — a critical value
 *      raised at another laboratory is not on this one's board; turnaround is
 *      computed from real stamps or declared "not enough data", never 0.
 *   2. A PATIENT REQUEST AT ANOTHER FACILITY DOES NOT EXIST — 404; approval
 *      raises a real order HERE and tells the patient; declining needs a reason.
 *   3. A REJECTED SAMPLE IS FINAL AND REASONED — refused once a result exists,
 *      the reason is on the order, the order admits nothing further.
 *   4. THE PRINTED REPORT IS THE PATIENT'S OWN SHEET — released or validated
 *      orders only; another facility's order is not found.
 *   5. A ROLE WITHOUT THE CAPABILITY IS REFUSED — 403.
 */
class LabManagerDeskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function staff(string $roleName = 'lab_manager', ?Facility $facility = null): array
    {
        $role     = Role::where('name', $roleName)->firstOrFail();
        $facility ??= Facility::factory()->create(['type' => 'laboratory']);

        $user = User::factory()->create([
            'role_id'             => $role->id,
            'primary_facility_id' => $facility->id,
            'status'              => 'active',
        ]);

        FacilityRoleAssignment::create([
            'user_id'     => $user->id,
            'facility_id' => $facility->id,
            'role_id'     => $role->id,
            'is_active'   => true,
            'assigned_at' => now(),
        ]);

        return [$user, $facility];
    }

    private function actingAsStaff(User $user, Facility $facility): static
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id]);
    }

    private function order(Facility $facility, array $overrides = []): LabOrder
    {
        return LabOrder::factory()->create(array_merge([
            'facility_id'  => $facility->id,
            'patient_id'   => Patient::factory()->create(['sex' => 'male', 'date_of_birth' => '1980-05-05'])->id,
            'test_name'    => 'Potassium',
            'test_code'    => 'K',
            'status'       => 'collected',
            'collected_at' => now(),
            'resulted_at'  => null,
        ], $overrides));
    }

    private function request(Facility $facility, array $overrides = []): LabTestRequest
    {
        return LabTestRequest::create(array_merge([
            'patient_id'     => Patient::factory()->create()->id,
            'facility_id'    => $facility->id,
            'test_code'      => 'FBC',
            'test_name'      => 'Full blood count',
            'priority'       => 'urgent',
            'status'         => 'submitted',
            'clinical_notes' => 'Tired for weeks',
            'submitted_at'   => now()->subHour(),
        ], $overrides));
    }

    private function potassiumRange(): ReferenceRange
    {
        return ReferenceRange::create([
            'test_code' => 'K', 'test_name' => 'Potassium', 'loinc_code' => null,
            'age_group' => 'all', 'sex' => 'all', 'unit' => 'mmol/L',
            'normal_low' => 3.5, 'normal_high' => 5.1, 'critical_low' => 2.5, 'critical_high' => 6.5,
            'is_active' => true,
        ]);
    }

    // ── Dashboard ───────────────────────────────────────────────────────────

    public function test_the_dashboard_reads_this_facilitys_live_figures_and_nobody_elses_critical_values(): void
    {
        [$manager, $facility]   = $this->staff();
        [$tech]                 = $this->staff('labtech', $facility);
        [$theirTech, $theirLab] = $this->staff('labtech');
        $this->potassiumRange();

        // Two awaiting sample, one in progress here; a released one with a
        // two-hour turnaround; a critical value HERE and one THERE.
        $this->order($facility, ['status' => 'ordered', 'collected_at' => null]);
        $this->order($facility, ['status' => 'ordered', 'collected_at' => null]);
        $mine = $this->order($facility);
        $this->order($facility, ['status' => 'resulted', 'collected_at' => now()->subHours(2), 'resulted_at' => now()]);

        $this->actingAsStaff($tech, $facility)
            ->post('/facility/lab/orders/' . $mine->id . '/results', ['value' => '7.1', 'unit' => 'mmol/L'])
            ->assertRedirect();

        $theirs = $this->order($theirLab);
        $this->actingAsStaff($theirTech, $theirLab)
            ->post('/facility/lab/orders/' . $theirs->id . '/results', ['value' => '7.9', 'unit' => 'mmol/L'])
            ->assertRedirect();

        $this->assertSame(2, CriticalValueAlert::count());

        $page = $this->actingAsStaff($manager, $facility)->get('/facility/lab/dashboard')->assertOk();

        $page->assertViewHas('counts', fn ($c) => $c['ordered'] === 2 && $c['in_progress'] === 1 && $c['resulted'] === 1)
            ->assertViewHas('critical_open', 1)
            ->assertViewHas('turnaround', fn ($t) => $t['released_orders'] === 1 && $t['median_minutes'] === 120 && $t['mean_minutes'] === 120)
            ->assertSee('7.1 mmol/L')
            ->assertDontSee('7.9 mmol/L')
            ->assertSee(__('lab_manager.tat_hours', ['hours' => 2, 'minutes' => 0]))
            ->assertSee('/facility/lab/orders/' . $mine->id)
            ->assertSee('/facility/lab/requests');

        // Nothing to measure: the sentence, not a zero.
        $this->actingAsStaff($theirTech, $theirLab)
            ->get('/facility/lab/dashboard')
            ->assertOk()
            ->assertSee(__('lab_manager.tat_none', ['days' => 30]));

        [$reception] = $this->staff('receptionist', $facility);
        $this->actingAsStaff($reception, $facility)->get('/facility/lab/dashboard')->assertForbidden();
    }

    // ── Patient requests ────────────────────────────────────────────────────

    public function test_a_request_sent_to_another_laboratory_does_not_exist_here(): void
    {
        [$manager, $facility] = $this->staff();
        [, $theirLab]         = $this->staff();
        $theirs = $this->request($theirLab);

        $this->actingAsStaff($manager, $facility)
            ->get('/facility/lab/requests')
            ->assertOk()
            ->assertDontSee($theirs->id)
            ->assertSee(__('lab_manager.requests_empty'));

        $this->actingAsStaff($manager, $facility)
            ->post('/facility/lab/requests/' . $theirs->id . '/approve')
            ->assertNotFound();

        $this->assertSame('submitted', $theirs->fresh()->status);
        $this->assertSame(0, LabOrder::count());
    }

    public function test_approving_a_request_raises_an_order_here_and_tells_the_patient(): void
    {
        [$manager, $facility] = $this->staff();
        $req = $this->request($facility);

        $this->actingAsStaff($manager, $facility)
            ->get('/facility/lab/requests')
            ->assertOk()
            ->assertSee($req->id)
            ->assertSee('Full blood count');

        $this->actingAsStaff($manager, $facility)
            ->post('/facility/lab/requests/' . $req->id . '/approve')
            ->assertRedirect('/facility/lab/requests')
            ->assertSessionHas('success', __('lab_manager.request_approved'));

        $req->refresh();
        $this->assertSame('approved', $req->status);
        $this->assertSame($manager->id, $req->reviewed_by);

        $order = LabOrder::findOrFail($req->lab_order_id);
        $this->assertSame($facility->id, $order->facility_id);
        $this->assertSame($req->patient_id, $order->patient_id);
        $this->assertSame('urgent', $order->urgency);
        $this->assertSame('ordered', $order->status);

        $this->assertSame(1, DB::table('notifications')
            ->where('notifiable_id', $req->patient_id)->where('type', 'lab_request_approved')->count());

        // Now on the worklist, awaiting a sample.
        $this->actingAsStaff($manager, $facility)
            ->get('/facility/lab?status=ordered')
            ->assertOk()
            ->assertSee($order->id);

        // Decided once.
        $this->actingAsStaff($manager, $facility)
            ->post('/facility/lab/requests/' . $req->id . '/decline', ['reason' => 'Too late'])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame('approved', $req->fresh()->status);
    }

    public function test_declining_a_request_needs_a_reason_the_patient_reads(): void
    {
        [$manager, $facility] = $this->staff();
        $req = $this->request($facility);

        $this->actingAsStaff($manager, $facility)
            ->from('/facility/lab/requests')
            ->post('/facility/lab/requests/' . $req->id . '/decline', ['reason' => ''])
            ->assertRedirect('/facility/lab/requests')
            ->assertSessionHasErrors('reason');
        $this->assertSame('submitted', $req->fresh()->status);

        $this->actingAsStaff($manager, $facility)
            ->post('/facility/lab/requests/' . $req->id . '/decline', ['reason' => 'This laboratory does not run haematology.'])
            ->assertRedirect('/facility/lab/requests');

        $req->refresh();
        $this->assertSame('declined', $req->status);
        $this->assertSame('This laboratory does not run haematology.', $req->decision_reason);
        $this->assertNull($req->lab_order_id);
        $this->assertSame(0, LabOrder::count());

        $bell = DB::table('notifications')->where('notifiable_id', $req->patient_id)->where('type', 'lab_request_declined')->first();
        $this->assertNotNull($bell);
        $this->assertStringContainsString('does not run haematology', (string) $bell->data);

        $this->actingAsStaff($manager, $facility)
            ->get('/facility/lab/requests?status=declined')
            ->assertOk()
            ->assertSee('does not run haematology');
    }

    // ── Sample rejection ────────────────────────────────────────────────────

    public function test_a_sample_is_rejected_with_a_reason_and_the_order_is_final(): void
    {
        [$collector, $facility] = $this->staff('sample_collection');
        [$tech]                 = $this->staff('labtech', $facility);
        $clinician              = User::factory()->create();
        $order = $this->order($facility, ['status' => 'ordered', 'collected_at' => null, 'ordered_by' => $clinician->id]);

        $this->actingAsStaff($collector, $facility)
            ->get('/facility/lab/collection')
            ->assertOk()
            ->assertSee('/facility/lab/orders/' . $order->id . '/reject');

        $this->actingAsStaff($collector, $facility)
            ->from('/facility/lab/collection')
            ->post('/facility/lab/orders/' . $order->id . '/reject', ['reason' => ''])
            ->assertRedirect('/facility/lab/collection')
            ->assertSessionHasErrors('reason');

        $this->actingAsStaff($collector, $facility)
            ->post('/facility/lab/orders/' . $order->id . '/reject', ['reason' => 'Haemolysed sample'])
            ->assertRedirect('/facility/lab/orders/' . $order->id)
            ->assertSessionHas('success', __('lab_manager.rejected'));

        $order->refresh();
        $this->assertSame('rejected', $order->status);
        $this->assertStringContainsString('[rejected] Haemolysed sample', (string) $order->notes);
        $this->assertTrue($order->isTerminal());

        // Patient and clinician are told.
        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $order->patient_id)->where('type', 'lab_sample_rejected')->count());
        // The clinician's bell is a NotificationEvent (App\Support\Notify::toUser), not a patient-feed row.
        $this->assertSame(1, DB::table('notification_events')->where('recipient_user_id', $clinician->id)->where('event_type', 'lab_sample_rejected')->count());

        // Final: nothing further, not even a result.
        $this->actingAsStaff($tech, $facility)
            ->post('/facility/lab/orders/' . $order->id . '/results', ['value' => '4.0'])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame(0, LabResult::where('lab_order_id', $order->id)->count());

        $this->actingAsStaff($tech, $facility)
            ->post('/facility/lab/orders/' . $order->id . '/reject', ['reason' => 'Again'])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_an_order_with_a_result_or_at_another_facility_cannot_be_rejected(): void
    {
        [$tech, $facility] = $this->staff('labtech');
        [, $theirLab]      = $this->staff();

        $order = $this->order($facility);
        $this->actingAsStaff($tech, $facility)
            ->post('/facility/lab/orders/' . $order->id . '/results', ['value' => '4.0', 'unit' => 'mmol/L'])
            ->assertRedirect();

        $this->actingAsStaff($tech, $facility)
            ->post('/facility/lab/orders/' . $order->id . '/reject', ['reason' => 'Changed my mind'])
            ->assertRedirect()
            ->assertSessionHas('error', __('lab_manager.err.reject_has_results'));
        $this->assertSame('in_progress', $order->fresh()->status);

        $theirs = $this->order($theirLab, ['status' => 'ordered', 'collected_at' => null]);
        $this->actingAsStaff($tech, $facility)
            ->post('/facility/lab/orders/' . $theirs->id . '/reject', ['reason' => 'Not mine'])
            ->assertNotFound();
        $this->assertSame('ordered', $theirs->fresh()->status);
    }

    // ── Printed report ──────────────────────────────────────────────────────

    public function test_the_bench_prints_the_patients_own_sheet_for_a_released_or_validated_order_only(): void
    {
        [$manager, $facility] = $this->staff();
        [, $theirLab]         = $this->staff();

        $pending = $this->order($facility);
        $this->actingAsStaff($manager, $facility)
            ->get('/facility/lab/orders/' . $pending->id . '/report')
            ->assertNotFound();

        $released = $this->order($facility, ['status' => 'resulted', 'resulted_at' => now()]);
        LabResult::factory()->create(['lab_order_id' => $released->id, 'patient_id' => $released->patient_id, 'parameter_name' => 'Potassium', 'value' => '4.2', 'unit' => 'mmol/L']);

        $this->actingAsStaff($manager, $facility)
            ->get('/facility/lab/orders/' . $released->id)
            ->assertOk()
            ->assertSee('/facility/lab/orders/' . $released->id . '/report');

        $response = $this->actingAsStaff($manager, $facility)
            ->get('/facility/lab/orders/' . $released->id . '/report')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringStartsWith('%PDF', $response->streamedContent());

        $this->assertSame(1, DB::table('document_verifications')
            ->where('patient_id', $released->patient_id)->where('issued_by_user_id', $manager->id)->count());

        // A validated panel (status `verified`) is still a report.
        $validated = $this->order($facility, ['status' => 'verified', 'resulted_at' => now(), 'verified_by' => $manager->id]);
        LabResult::factory()->create(['lab_order_id' => $validated->id, 'patient_id' => $validated->patient_id]);

        $this->actingAsStaff($manager, $facility)
            ->get('/facility/lab/orders/' . $validated->id . '/report')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $theirs = $this->order($theirLab, ['status' => 'resulted', 'resulted_at' => now()]);
        $this->actingAsStaff($manager, $facility)
            ->get('/facility/lab/orders/' . $theirs->id . '/report')
            ->assertNotFound();
    }

    /** Nothing on this desk consults what anyone has paid. */
    public function test_no_lab_manager_route_is_gated_on_payment(): void
    {
        foreach (\Illuminate\Support\Facades\Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'facility/lab')) {
                continue;
            }
            foreach ($route->gatherMiddleware() as $middleware) {
                $this->assertFalse(
                    is_string($middleware) && (str_starts_with($middleware, 'module:') || str_contains($middleware, 'EnforceModuleEntitlement')),
                    $route->uri() . ' is gated on a subscription.'
                );
            }
        }

        $source = (string) file_get_contents(base_path('app/Services/Lab/LabManagerDeskService.php'))
            . (string) file_get_contents(base_path('app/Http/Controllers/MedicalId/LabManagerDeskController.php'));

        foreach (['OrganizationSubscription', 'SubscriptionPlan', 'ModuleEntitlement', 'Invoice', 'Payment'] as $word) {
            $this->assertStringNotContainsString($word, $source);
        }
    }
}
