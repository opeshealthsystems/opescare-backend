<?php

namespace Tests\Feature\Imaging;

use App\Enums\ImagingOrderStatus;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\ImagingOrder;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The imaging desk (/facility/imaging) and the ways it is not allowed to
 * misbehave:
 *
 *   1. INTAKE CREATES AN ORDER WITH AN ACCESSION NUMBER (IMG-YYYYMM-…-NNNN).
 *   2. THE WORKLIST IS FOR imaging_tech / radiologist / lab_manager — and a
 *      receptionist is refused with a 403, not a redirect.
 *   3. AN ORDER AT ANOTHER FACILITY DOES NOT EXIST — 404.
 *   4. TRANSITIONS FOLLOW pending → scheduled → in_progress → completed; a
 *      skipped step is refused and the row is unchanged.
 *   5. A CANCELLATION NEEDS A REASON.
 *   6. THE PAGES SHOW AN AGE BAND, NEVER THE DATE OF BIRTH.
 */
class ImagingDeskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function staff(string $roleName = 'imaging_tech', ?Facility $facility = null): array
    {
        $role     = Role::where('name', $roleName)->firstOrFail();
        $facility ??= Facility::factory()->create(['type' => 'diagnostic_center']);

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

    private function order(Facility $facility, User $by, array $overrides = []): ImagingOrder
    {
        return ImagingOrder::create(array_merge([
            'patient_id'          => Patient::factory()->create(['date_of_birth' => '1980-05-05'])->id,
            'facility_id'         => $facility->id,
            'ordered_by'          => $by->id,
            'modality'            => 'xray',
            'body_part'           => 'chest',
            'clinical_indication' => 'Cough for three weeks',
            'urgency'             => 'routine',
            'status'              => ImagingOrderStatus::Pending,
            'ordered_at'          => now(),
            'accession_number'    => 'IMG-TEST-' . fake()->unique()->numerify('####'),
        ], $overrides));
    }

    // ── 1. Intake ────────────────────────────────────────────────────────────

    public function test_intake_creates_an_order_with_an_accession_number(): void
    {
        [$tech, $facility] = $this->staff();
        $patient = Patient::factory()->create(['health_id' => 'OC-IMG-001', 'date_of_birth' => '1980-05-05']);

        // The lookup is by exact Health ID and shows the age band, not the DOB.
        $this->actingAsStaff($tech, $facility)
            ->get('/facility/imaging/orders/new?health_id=OC-IMG-001')
            ->assertOk()
            ->assertSee('OC-IMG-001')
            ->assertSee(__('imaging_desk.band.adult'))
            ->assertDontSee('1980-05-05');

        $this->actingAsStaff($tech, $facility)
            ->get('/facility/imaging/orders/new?health_id=OC-NOBODY')
            ->assertOk()
            ->assertSee(__('imaging_desk.patient_not_found'));

        $response = $this->actingAsStaff($tech, $facility)->post('/facility/imaging/orders', [
            'patient_id'          => $patient->id,
            'modality'            => 'ultrasound',
            'body_part'           => 'abdomen',
            'clinical_indication' => 'Right upper quadrant pain',
            'urgency'             => 'urgent',
        ]);

        $order = ImagingOrder::where('patient_id', $patient->id)->firstOrFail();

        $response->assertRedirect(route('portals.imaging.show', $order->id));
        $this->assertSame($facility->id, $order->facility_id);
        $this->assertSame($tech->id, $order->ordered_by);
        $this->assertSame(ImagingOrderStatus::Pending, $order->status);
        $this->assertMatchesRegularExpression('/^IMG-\d{6}-[0-9A-F]{4}-0001$/', $order->accession_number);

        // The second order this month at this facility takes the next number.
        $this->actingAsStaff($tech, $facility)->post('/facility/imaging/orders', [
            'patient_id'          => $patient->id,
            'modality'            => 'xray',
            'clinical_indication' => 'Follow-up',
            'urgency'             => 'routine',
        ]);
        $this->assertStringEndsWith('-0002', ImagingOrder::orderByDesc('accession_number')->value('accession_number'));

        // A slot given at intake books the order straight away.
        $this->actingAsStaff($tech, $facility)->post('/facility/imaging/orders', [
            'patient_id'          => $patient->id,
            'modality'            => 'ct',
            'clinical_indication' => 'Head injury',
            'urgency'             => 'stat',
            'scheduled_at'        => now()->addDay()->format('Y-m-d\TH:i'),
        ]);
        $this->assertSame(ImagingOrderStatus::Scheduled, ImagingOrder::where('modality', 'ct')->firstOrFail()->status);

        // The detail page: accession, age band, never the DOB.
        $this->actingAsStaff($tech, $facility)
            ->get(route('portals.imaging.show', $order->id))
            ->assertOk()
            ->assertSee($order->accession_number)
            ->assertSee(__('imaging_desk.band.adult'))
            ->assertDontSee('1980-05-05')
            ->assertSee('id="report-panel"', false);
    }

    // ── 2. Who may open the worklist ─────────────────────────────────────────

    public function test_the_worklist_is_open_to_the_imaging_roles_and_refused_to_the_front_desk(): void
    {
        foreach (['imaging_tech', 'radiologist', 'lab_manager'] as $roleName) {
            [$user, $facility] = $this->staff($roleName);
            $this->actingAsStaff($user, $facility)->get('/facility/imaging')->assertOk();
            $this->actingAsStaff($user, $facility)->get('/facility/imaging?tab=awaiting_report')->assertOk();
        }

        [$receptionist, $facility] = $this->staff('receptionist');
        $this->actingAsStaff($receptionist, $facility)->get('/facility/imaging')->assertForbidden();
        $this->actingAsStaff($receptionist, $facility)->get('/facility/imaging/orders/new')->assertForbidden();
    }

    // ── 3. Cross-facility ────────────────────────────────────────────────────

    public function test_an_order_at_another_facility_does_not_exist(): void
    {
        [$tech, $facility] = $this->staff();
        [$other, $elsewhere] = $this->staff('imaging_tech');

        $order = $this->order($elsewhere, $other);

        $this->actingAsStaff($tech, $facility)->get(route('portals.imaging.show', $order->id))->assertNotFound();
        $this->actingAsStaff($tech, $facility)->post(route('portals.imaging.start', $order->id))->assertNotFound();

        // And it is not a row in this facility's worklist either.
        $this->actingAsStaff($tech, $facility)->get('/facility/imaging')
            ->assertOk()
            ->assertDontSee($order->accession_number);
    }

    // ── 4. Transitions ───────────────────────────────────────────────────────

    public function test_transitions_follow_the_lifecycle_and_a_skipped_step_is_refused(): void
    {
        [$tech, $facility] = $this->staff();
        $order = $this->order($facility, $tech);

        // pending → in_progress: not allowed.
        $this->actingAsStaff($tech, $facility)
            ->from(route('portals.imaging.show', $order->id))
            ->post(route('portals.imaging.start', $order->id))
            ->assertRedirect(route('portals.imaging.show', $order->id))
            ->assertSessionHas('error');
        $this->assertSame(ImagingOrderStatus::Pending, $order->fresh()->status);

        // pending → scheduled
        $this->actingAsStaff($tech, $facility)
            ->post(route('portals.imaging.schedule', $order->id), ['scheduled_at' => now()->addHour()->format('Y-m-d\TH:i')])
            ->assertRedirect(route('portals.imaging.show', $order->id))
            ->assertSessionHas('success');
        $this->assertSame(ImagingOrderStatus::Scheduled, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->scheduled_at);

        // scheduled → completed: not allowed.
        $this->actingAsStaff($tech, $facility)
            ->from(route('portals.imaging.show', $order->id))
            ->post(route('portals.imaging.complete', $order->id))
            ->assertSessionHas('error');
        $this->assertSame(ImagingOrderStatus::Scheduled, $order->fresh()->status);

        // scheduled → in_progress → completed
        $this->actingAsStaff($tech, $facility)->post(route('portals.imaging.start', $order->id))->assertSessionHas('success');
        $this->assertSame(ImagingOrderStatus::InProgress, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->started_at);

        $this->actingAsStaff($tech, $facility)->post(route('portals.imaging.complete', $order->id))->assertSessionHas('success');
        $this->assertSame(ImagingOrderStatus::Completed, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->completed_at);

        // A completed order sits in "awaiting report" and cannot be cancelled.
        $this->actingAsStaff($tech, $facility)->get('/facility/imaging?tab=awaiting_report')->assertOk()->assertSee($order->accession_number);
        $this->actingAsStaff($tech, $facility)
            ->from(route('portals.imaging.show', $order->id))
            ->post(route('portals.imaging.cancel', $order->id), ['reason' => 'Patient left'])
            ->assertSessionHas('error');
        $this->assertSame(ImagingOrderStatus::Completed, $order->fresh()->status);
    }

    // ── 5. Cancellation ──────────────────────────────────────────────────────

    public function test_a_cancellation_needs_a_reason(): void
    {
        [$tech, $facility] = $this->staff();
        $order = $this->order($facility, $tech);

        $this->actingAsStaff($tech, $facility)
            ->from(route('portals.imaging.show', $order->id))
            ->post(route('portals.imaging.cancel', $order->id), ['reason' => ''])
            ->assertSessionHasErrors('reason');
        $this->assertSame(ImagingOrderStatus::Pending, $order->fresh()->status);

        $this->actingAsStaff($tech, $facility)
            ->post(route('portals.imaging.cancel', $order->id), ['reason' => 'Duplicate request'])
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame(ImagingOrderStatus::Cancelled, $order->status);
        $this->assertSame('Duplicate request', $order->cancel_reason);
        $this->assertNotNull($order->cancelled_at);

        $this->actingAsStaff($tech, $facility)
            ->get(route('portals.imaging.show', $order->id))
            ->assertOk()
            ->assertSee('Duplicate request')
            ->assertSee(__('imaging_desk.no_actions'));
    }
}
