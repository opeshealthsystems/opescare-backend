<?php

namespace Tests\Feature\Blood;

use App\Enums\BloodRequestStatus;
use App\Models\BloodAvailability;
use App\Models\BloodInventory;
use App\Models\BloodRequest;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\Portal\ProviderSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The two blood-request desks must agree.
 *
 * MobileBloodDeskController and BloodRequestDeskController are two doors onto
 * ONE lifecycle: BloodRequestService::decide() and
 * BloodRequestStatus::facilityTransitions(). If they ever disagree, a blood
 * bank's answer would depend on which screen the staff member happened to open.
 *
 * Each test here drives BOTH surfaces over the same fixture and asserts the
 * resulting row is the same — including the one that matters most: neither
 * door moves stock.
 */
class BloodDeskParityTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;
    private CareFacility $listing;
    private Patient $patient;
    private string $bearer;
    private User $webStaff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility = Facility::factory()->create();

        $this->listing = CareFacility::create([
            'facility_id'    => $this->facility->id,
            'facility_name'  => 'Yaounde Central Blood Bank',
            'facility_type'  => 'blood_bank',
            'listing_status' => 'active',
            'country_code'   => 'CM',
            'region'         => 'Centre',
            'city'           => 'Yaounde',
            'address'        => 'Rue de la Reunification',
            'phone_primary'  => '+237222234000',
        ]);

        $this->patient = Patient::create([
            'health_id'     => 'OC-TST-BP-0001-01',
            'first_name'    => 'Amina',
            'last_name'     => 'Njoya',
            'sex'           => 'female',
            'date_of_birth' => '1991-03-04',
            'is_demo'       => (bool) config('demo.enabled'),
        ]);

        $doctor = $this->staffUser('doctor');
        $this->bearer = app(ProviderSessionService::class)->mint($doctor, $this->facility->id, 'parity');

        // A second member of staff at the same blood bank, working in the
        // browser instead of the app.
        $this->webStaff = $this->staffUser('nurse');
    }

    private function staffUser(string $role): User
    {
        DB::table('dashboard_profiles')->updateOrInsert(['key' => $role], [
            'name' => ucfirst($role), 'portal_prefix' => 'staff', 'landing_route' => 'staff.dashboard',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $model = Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role)]);
        $model->forceFill(['dashboard_profile_key' => $role])->save();

        $user = User::factory()->create([
            'is_demo'             => (bool) config('demo.enabled'),
            'primary_facility_id' => $this->facility->id,
        ]);
        $user->role_id = $model->id;
        $user->save();

        return $user;
    }

    private function makeRequest(string $status = 'pending'): BloodRequest
    {
        return BloodRequest::create([
            'care_facility_id' => $this->listing->id,
            'reference'        => 'OC-BL-' . Str::upper(Str::random(8)),
            'patient_id'       => $this->patient->id,
            'blood_group'      => 'O+',
            'component_type'   => 'whole_blood',
            'quantity'         => 2,
            'urgency'          => 'routine',
            'status'           => $status,
            'contact_phone'    => '+237600000000',
            'expires_at'       => now()->addHours(24),
        ]);
    }

    /** Apply one move on the provider app; returns the resulting status. */
    private function viaMobile(BloodRequest $request, string $target): string
    {
        $path = match ($target) {
            'confirmed', 'rejected' => "/api/mobile/blood-desk/requests/{$request->id}/decide",
            'ready'                 => "/api/mobile/blood-desk/requests/{$request->id}/ready",
            'fulfilled'             => "/api/mobile/blood-desk/requests/{$request->id}/fulfil",
        };

        $body = in_array($target, ['confirmed', 'rejected'], true) ? ['decision' => $target] : [];

        $this->withHeaders(['Authorization' => 'Bearer ' . $this->bearer])->postJson($path, $body);

        return $request->refresh()->status->value;
    }

    /** Apply the same move in the browser; returns the resulting status. */
    private function viaWeb(BloodRequest $request, string $target): string
    {
        $path = match ($target) {
            'confirmed', 'rejected' => "/facility/blood-requests/{$request->id}/decide",
            'ready'                 => "/facility/blood-requests/{$request->id}/ready",
            'fulfilled'             => "/facility/blood-requests/{$request->id}/fulfil",
        };

        $body = in_array($target, ['confirmed', 'rejected'], true) ? ['decision' => $target] : [];

        $this->actingAs($this->webStaff)
            ->withSession(['active_facility_id' => $this->facility->id])
            ->post($path, $body);

        return $request->refresh()->status->value;
    }

    public function test_every_move_lands_the_same_way_on_both_desks(): void
    {
        $checked = 0;

        foreach (BloodRequestStatus::cases() as $from) {
            foreach (['confirmed', 'rejected', 'ready', 'fulfilled'] as $target) {
                $onMobile = $this->makeRequest($from->value);
                $onWeb    = $this->makeRequest($from->value);

                $mobileResult = $this->viaMobile($onMobile, $target);
                $webResult    = $this->viaWeb($onWeb, $target);

                $this->assertSame(
                    $mobileResult,
                    $webResult,
                    "{$from->value} -> {$target}: the app landed on {$mobileResult}, the browser on {$webResult}.",
                );

                // And both agree with the enum, which is the only rule either
                // of them is allowed to have.
                $expected = $from->canTransitionTo(BloodRequestStatus::from($target))
                    ? $target
                    : $from->value;

                $this->assertSame($expected, $mobileResult, "{$from->value} -> {$target} disagreed with the enum.");

                $checked++;
            }
        }

        $this->assertSame(28, $checked, 'Seven statuses x four moves.');
    }

    public function test_both_desks_report_the_same_legal_moves_for_a_request(): void
    {
        foreach (BloodRequestStatus::cases() as $case) {
            $request = $this->makeRequest($case->value);
            $moves   = array_map(static fn (BloodRequestStatus $s) => $s->value, $case->facilityTransitions());

            // The app states them outright.
            $this->withHeaders(['Authorization' => 'Bearer ' . $this->bearer])
                ->getJson("/api/mobile/blood-desk/requests/{$request->id}")
                ->assertOk()
                ->assertJsonPath('data.next_decisions', $moves);

            // The browser states them as the buttons it draws.
            $page = $this->actingAs($this->webStaff)
                ->withSession(['active_facility_id' => $this->facility->id])
                ->get("/facility/blood-requests/{$request->id}")
                ->assertOk();

            foreach ([
                'confirmed' => 'value="confirmed"',
                'rejected'  => 'value="rejected"',
                'ready'     => route('portals.staff.blood_requests.ready', $request->id),
                'fulfilled' => route('portals.staff.blood_requests.fulfil', $request->id),
            ] as $move => $marker) {
                in_array($move, $moves, true)
                    ? $page->assertSee($marker, false)
                    : $page->assertDontSee($marker, false);
            }
        }
    }

    public function test_a_lifecycle_split_across_both_desks_lands_on_fulfilled(): void
    {
        $request = $this->makeRequest();

        // Confirmed in the app, made ready in the browser, collected in the app.
        $this->assertSame('confirmed', $this->viaMobile($request, 'confirmed'));
        $this->assertSame('ready', $this->viaWeb($request, 'ready'));
        $this->assertSame('fulfilled', $this->viaMobile($request, 'fulfilled'));

        $fresh = $request->refresh();
        $this->assertNotNull($fresh->confirmed_at);
        $this->assertNotNull($fresh->fulfilled_at);
        $this->assertFalse($fresh->status->isOpen());

        // The row was never deleted or duplicated along the way.
        $this->assertSame(1, BloodRequest::query()->count());
    }

    public function test_neither_desk_moves_stock_anywhere_in_the_lifecycle(): void
    {
        $availability = BloodAvailability::create([
            'facility_id'           => $this->listing->id,
            'blood_group'           => 'O+',
            'component_type'        => 'whole_blood',
            'units_available_range' => '6-10',
            'availability_status'   => 'available',
            'freshness_status'      => 'fresh',
            'source_system'         => 'facility_portal',
            'last_updated_at'       => now(),
        ]);

        BloodInventory::create([
            'facility_id'       => $this->facility->id,
            'blood_group'       => 'O+',
            'component'         => 'whole_blood',
            'available_units'   => 8,
            'last_stock_update' => now(),
        ]);

        $availabilityBefore = DB::table('blood_availability')->orderBy('id')->get()->toJson();
        $inventoryBefore    = DB::table('blood_inventories')->orderBy('id')->get()->toJson();

        // Every move either desk offers, on its own request, from every status.
        foreach (BloodRequestStatus::cases() as $from) {
            foreach (['confirmed', 'rejected', 'ready', 'fulfilled'] as $target) {
                $this->viaMobile($this->makeRequest($from->value), $target);
                $this->viaWeb($this->makeRequest($from->value), $target);
            }
        }

        // Confirming, readying and fulfilling are the blood bank saying "yes,
        // come" — they reserve, hold and decrement nothing. Both tables are
        // byte-identical, updated_at included.
        $this->assertSame($availabilityBefore, DB::table('blood_availability')->orderBy('id')->get()->toJson());
        $this->assertSame($inventoryBefore, DB::table('blood_inventories')->orderBy('id')->get()->toJson());

        $this->assertSame('6-10', $availability->refresh()->units_available_range);
        $this->assertSame('available', $availability->availability_status);
    }
}
