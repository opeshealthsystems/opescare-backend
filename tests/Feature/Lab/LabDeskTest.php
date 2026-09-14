<?php

namespace Tests\Feature\Lab;

use App\Models\CriticalValueAlert;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\Patient;
use App\Models\ReferenceRange;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The laboratory desk (/facility/lab), and specifically the ways it is not
 * allowed to hurt anyone:
 *
 *   1. AN ORDER AT ANOTHER FACILITY DOES NOT EXIST — 404, never 403, never a
 *      row in the worklist.
 *   2. A ROLE WITHOUT THE CAPABILITY IS REFUSED — 403 role_forbidden.
 *   3. A VALIDATED RESULT IS NEVER OVERWRITTEN — a correction is a NEW row and
 *      the original survives, value and all.
 *   4. A CRITICAL VALUE RAISES THE ALERT — one CriticalValueAlert row, pending.
 *   5. NO RANGE, NO FLAG — a value with nothing to compare against is stored
 *      `unflagged`, never guessed `normal`.
 */
class LabDeskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /** A professional posted at one facility, exactly as the middleware leaves one. */
    private function staff(string $roleName = 'labtech', ?Facility $facility = null): array
    {
        $role     = Role::where('name', $roleName)->firstOrFail();
        $facility ??= Facility::factory()->create(['type' => 'hospital']);

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

    /** A published interval with critical bounds — the one source that can raise a value to critical. */
    private function potassiumRange(): ReferenceRange
    {
        return ReferenceRange::create([
            'test_code'     => 'K',
            'test_name'     => 'Potassium',
            'loinc_code'    => null,
            'age_group'     => 'all',
            'sex'           => 'all',
            'unit'          => 'mmol/L',
            'normal_low'    => 3.5,
            'normal_high'   => 5.1,
            'critical_low'  => 2.5,
            'critical_high' => 6.5,
            'is_active'     => true,
        ]);
    }

    // ── Tenancy ─────────────────────────────────────────────────────────────

    public function test_an_order_at_another_facility_does_not_exist_for_this_desk(): void
    {
        [$mine, $myFacility]      = $this->staff();
        [$theirs, $theirFacility] = $this->staff();

        $order = $this->order($theirFacility);

        $this->actingAsStaff($mine, $myFacility)
            ->get('/facility/lab/orders/' . $order->id)
            ->assertNotFound();

        $this->actingAsStaff($mine, $myFacility)
            ->post('/facility/lab/orders/' . $order->id . '/results', ['value' => '4.0'])
            ->assertNotFound();

        $this->assertSame(0, LabResult::where('lab_order_id', $order->id)->count());

        // And absent from every column of the worklist.
        $this->actingAsStaff($mine, $myFacility)
            ->get('/facility/lab?status=collected')
            ->assertOk()
            ->assertDontSee($order->id)
            ->assertSee(__('lab_desk.empty_worklist'));

        // While the desk that owns it sees it.
        $this->actingAsStaff($theirs, $theirFacility)
            ->get('/facility/lab?status=collected')
            ->assertOk()
            ->assertSee($order->id);
    }

    // ── Role ────────────────────────────────────────────────────────────────

    public function test_a_labtech_can_enter_a_result_and_a_receptionist_is_refused(): void
    {
        [$tech, $facility] = $this->staff('labtech');
        $order = $this->order($facility);

        $this->actingAsStaff($tech, $facility)
            ->post('/facility/lab/orders/' . $order->id . '/results', ['value' => '4.0', 'unit' => 'mmol/L'])
            ->assertRedirect('/facility/lab/orders/' . $order->id);

        $result = LabResult::where('lab_order_id', $order->id)->firstOrFail();
        $this->assertSame('4.0', $result->value);
        $this->assertSame($tech->id, $result->getAttribute('entered_by'));
        $this->assertNull($result->verified_by, 'Entry is not verification.');
        $this->assertSame('in_progress', $order->fresh()->status);

        [$reception] = $this->staff('receptionist', $facility);

        $this->actingAsStaff($reception, $facility)->get('/facility/lab')->assertForbidden();
        $this->actingAsStaff($reception, $facility)
            ->post('/facility/lab/orders/' . $order->id . '/results', ['value' => '9.9'])
            ->assertForbidden();

        $this->assertSame(1, LabResult::where('lab_order_id', $order->id)->count());
    }

    public function test_a_sample_collection_officer_can_collect_but_never_reaches_a_result(): void
    {
        [$collector, $facility] = $this->staff('sample_collection');
        $order = $this->order($facility, ['status' => 'ordered', 'collected_at' => null]);

        $this->actingAsStaff($collector, $facility)
            ->get('/facility/lab/collection')
            ->assertOk()
            ->assertSee($order->id);

        $this->actingAsStaff($collector, $facility)
            ->post('/facility/lab/orders/' . $order->id . '/collect', ['specimen_type' => 'Serum'])
            ->assertRedirect('/facility/lab/collection');

        $order->refresh();
        $this->assertSame('collected', $order->status);
        $this->assertNotNull($order->collected_at);
        $this->assertStringContainsString('Specimen: Serum', (string) $order->notes);

        // The bench itself is not this role's.
        $this->actingAsStaff($collector, $facility)->get('/facility/lab')->assertForbidden();
        $this->actingAsStaff($collector, $facility)->get('/facility/lab/orders/' . $order->id)->assertForbidden();
    }

    // ── Immutability ────────────────────────────────────────────────────────

    public function test_a_validated_result_survives_an_attempted_correction_verbatim(): void
    {
        [$tech, $facility]  = $this->staff('labtech');
        [$validator]        = $this->staff('lab_validator', $facility);
        $order = $this->order($facility);

        $this->actingAsStaff($tech, $facility)
            ->post('/facility/lab/orders/' . $order->id . '/results', ['value' => '4.0', 'unit' => 'mmol/L'])
            ->assertRedirect();
        $this->actingAsStaff($tech, $facility)
            ->post('/facility/lab/orders/' . $order->id . '/release')
            ->assertRedirect();

        $this->assertSame('resulted', $order->fresh()->status);

        // The tech who entered it may not validate it.
        $this->actingAsStaff($tech, $facility)
            ->post('/facility/lab/orders/' . $order->id . '/validate')
            ->assertForbidden();

        $this->actingAsStaff($validator, $facility)
            ->post('/facility/lab/orders/' . $order->id . '/validate')
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('verified', $order->status);
        $this->assertSame($validator->id, $order->verified_by);

        $original = LabResult::where('lab_order_id', $order->id)->firstOrFail();
        // Every column, as stored — not a chosen few.
        $before   = $original->getAttributes();

        // A new entry on a validated order is refused outright.
        $this->actingAsStaff($tech, $facility)
            ->post('/facility/lab/orders/' . $order->id . '/results', ['value' => '5.0'])
            ->assertRedirect()
            ->assertSessionHas('error', __('lab_desk.err.validated_locked'));
        $this->assertSame(1, LabResult::where('lab_order_id', $order->id)->count());

        // A correction is a NEW row; the original is byte-for-byte what it was.
        $this->actingAsStaff($tech, $facility)
            ->post('/facility/lab/orders/' . $order->id . '/results/' . $original->id . '/correct', [
                'value'  => '4.4',
                'unit'   => 'mmol/L',
                'reason' => 'Transcription error from the analyser printout',
            ])
            ->assertRedirect();

        $this->assertSame($before, $original->fresh()->getAttributes());
        $this->assertSame('4.0', $original->fresh()->value);

        $rows = LabResult::where('lab_order_id', $order->id)->orderBy('created_at')->get();
        $this->assertCount(2, $rows);
        $this->assertSame('4.4', $rows->last()->value);
        $this->assertStringContainsString('[corrects:' . $original->id . ']', (string) $rows->last()->notes);

        // The validator signed off the OLD number: the panel needs validating again.
        $this->assertSame('resulted', $order->fresh()->status);
        $this->assertNull($order->fresh()->verified_by);

        $this->actingAsStaff($tech, $facility)
            ->get('/facility/lab/orders/' . $order->id)
            ->assertOk()
            ->assertSee(__('lab_desk.superseded'));
    }

    // ── Critical values ─────────────────────────────────────────────────────

    public function test_entering_a_critical_value_raises_a_pending_alert_from_the_published_bound(): void
    {
        [$tech, $facility] = $this->staff('labtech');
        $this->potassiumRange();
        $order = $this->order($facility);

        $this->actingAsStaff($tech, $facility)
            ->post('/facility/lab/orders/' . $order->id . '/results', ['value' => '7.1', 'unit' => 'mmol/L'])
            ->assertRedirect();

        $result = LabResult::where('lab_order_id', $order->id)->firstOrFail();

        // Stored so every reader — patient, clinician, bench — reads the same
        // thing, and with its source so none presents it as the lab's verdict.
        $this->assertSame('CRITICAL', $result->flag);
        $this->assertSame('published', $result->getAttribute('flag_source'));
        $this->assertSame('3.5–5.1 mmol/L', $result->reference_range);

        $alert = CriticalValueAlert::where('lab_result_id', $result->id)->firstOrFail();
        $this->assertSame('critical_high', $alert->alert_type);
        $this->assertSame('6.5 mmol/L', $alert->critical_threshold);
        $this->assertSame('7.1 mmol/L', $alert->result_value);
        $this->assertFalse($alert->acknowledged);

        $this->actingAsStaff($tech, $facility)
            ->get('/facility/lab/orders/' . $order->id)
            ->assertOk()
            ->assertSee(__('lab_desk.flag.critical'))
            ->assertSee(__('lab_desk.alert_pending'));

        // And a value inside the interval raises nothing.
        $this->actingAsStaff($tech, $facility)
            ->post('/facility/lab/orders/' . $order->id . '/results', ['value' => '4.2', 'unit' => 'mmol/L'])
            ->assertRedirect();

        $normal = LabResult::where('lab_order_id', $order->id)->where('value', '4.2')->firstOrFail();
        $this->assertSame('N', $normal->flag);
        $this->assertSame(1, CriticalValueAlert::count());
    }

    public function test_a_result_with_no_reference_range_gets_no_flag_rather_than_a_guessed_one(): void
    {
        [$tech, $facility] = $this->staff('labtech');
        $order = $this->order($facility, ['test_name' => 'Serum widgetase', 'test_code' => 'WDG']);

        $this->actingAsStaff($tech, $facility)
            ->post('/facility/lab/orders/' . $order->id . '/results', ['value' => '812'])
            ->assertRedirect();

        $result = LabResult::where('lab_order_id', $order->id)->firstOrFail();

        $this->assertNull($result->flag);
        $this->assertNull($result->getAttribute('flag_source'));
        $this->assertNull($result->reference_range);
        $this->assertNull($result->unit, 'A unit is never filled in for the bench.');
        $this->assertNull($result->loinc_code, 'A code is never invented.');
        $this->assertSame(0, CriticalValueAlert::count());

        $this->actingAsStaff($tech, $facility)
            ->get('/facility/lab/orders/' . $order->id)
            ->assertOk()
            ->assertSee(__('lab_desk.flag.unflagged'))
            ->assertSee(__('lab_desk.no_range'))
            ->assertDontSee(__('lab_desk.flag.normal'));
    }

    // ── Lookup & navigation ─────────────────────────────────────────────────

    public function test_the_worklist_is_narrowed_only_by_an_exact_health_id(): void
    {
        [$tech, $facility] = $this->staff('labtech');
        $patient = Patient::factory()->create(['health_id' => 'OC-LAB-001', 'last_name' => 'Ngassa']);
        $mine    = $this->order($facility, ['patient_id' => $patient->id]);
        $other   = $this->order($facility);

        $this->actingAsStaff($tech, $facility)
            ->get('/facility/lab?status=collected&health_id=OC-LAB-001')
            ->assertOk()
            ->assertSee($mine->id)
            ->assertDontSee($other->id);

        // A prefix is not a match and the surname is not searchable.
        $this->actingAsStaff($tech, $facility)
            ->get('/facility/lab?status=collected&health_id=OC-LAB')
            ->assertOk()
            ->assertSee(__('lab_desk.empty_worklist'))
            ->assertDontSee('Ngassa');
    }

    public function test_the_desk_is_linked_from_the_sidebar_of_roles_that_hold_it(): void
    {
        foreach (['labtech', 'lab_scientist', 'lab_manager', 'lab_validator'] as $roleName) {
            [$user, $facility] = $this->staff($roleName);

            $this->actingAsStaff($user, $facility)
                ->get('/facility')
                ->assertOk()
                ->assertSee('/facility/lab"', false)
                ->assertSee('/facility/lab/collection');
        }

        [$collector, $collectorFacility] = $this->staff('sample_collection');
        $this->actingAsStaff($collector, $collectorFacility)
            ->get('/facility')
            ->assertOk()
            ->assertSee('/facility/lab/collection')
            ->assertDontSee('/facility/lab"', false);

        [$reception, $receptionFacility] = $this->staff('receptionist');
        $this->actingAsStaff($reception, $receptionFacility)
            ->get('/facility')
            ->assertOk()
            ->assertDontSee('/facility/lab');
    }

    /** Nothing on this desk consults what anyone has paid. */
    public function test_no_lab_route_is_gated_on_payment(): void
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

        $source = (string) file_get_contents(base_path('app/Services/Lab/LabDeskWebService.php'))
            . (string) file_get_contents(base_path('app/Http/Controllers/MedicalId/LabDeskWebController.php'));

        foreach (['OrganizationSubscription', 'SubscriptionPlan', 'ModuleEntitlement', 'Invoice', 'Payment'] as $word) {
            $this->assertStringNotContainsString($word, $source);
        }
    }
}
