<?php

namespace Tests\Feature\Mobile;

use App\Models\MedicineReservation;
use App\Models\PharmacyRoute;
use App\Models\Prescription;
use App\Services\Pharmacy\PharmacyDeskService;
use Illuminate\Support\Str;

/**
 * The pharmacy desk's home and prescription bench:
 * /api/mobile/pharmacy-desk/{home,prescriptions,prescriptions/lookup,prescriptions/{id}/*}.
 */
class PharmacyDeskPrescriptionsTest extends PharmacyDeskTestCase
{
    // ── Gates ─────────────────────────────────────────────────────────────

    public function test_the_desk_needs_a_provider_session(): void
    {
        $this->getJson(self::BASE . '/home')->assertUnauthorized();
        $this->getJson(self::BASE . '/prescriptions')->assertUnauthorized();
        $this->postJson(self::BASE . '/prescriptions/' . Str::uuid() . '/accept')->assertUnauthorized();
    }

    public function test_only_roles_that_dispense_may_open_the_desk(): void
    {
        foreach (['doctor', 'receptionist', 'stockkeeper'] as $role) {
            foreach (['/home', '/prescriptions', '/prescriptions/lookup?health_id=' . self::HEALTH_ID, '/reservations'] as $path) {
                $this->desk($role, 'GET', $path)
                    ->assertForbidden()
                    ->assertJsonPath('error_code', 'role_forbidden')
                    ->assertJsonPath('required', ['dispense']);
            }

            $this->desk($role, 'POST', '/prescriptions/' . Str::uuid() . '/dispense')
                ->assertForbidden()
                ->assertJsonPath('error_code', 'role_forbidden');
        }

        // A receptionist with a complete dispense body still leaves the script untouched.
        $rx = $this->routed($this->facility);
        $this->desk('receptionist', 'POST', "/prescriptions/{$rx->id}/dispense", ['items' => [['id' => $rx->items->first()->id]]])
            ->assertForbidden();
        $this->assertSame('active', $rx->fresh()->status);
    }

    // ── Home ──────────────────────────────────────────────────────────────

    public function test_home_counts_the_bench_the_reservations_and_the_low_lines_for_this_facility_only(): void
    {
        $this->routed($this->facility);                                          // incoming
        $this->routed($this->facility, PharmacyDeskService::ROUTING_SENT);       // incoming
        $this->routed($this->facility, PharmacyDeskService::ROUTING_CONFIRMED);  // preparing
        $this->routed($this->facility, PharmacyDeskService::ROUTING_READY);      // ready
        $this->routed($this->facility, PharmacyDeskService::ROUTING_DISPENSED, ['status' => 'dispensed', 'dispensed_at' => now()]);
        $this->routed($this->otherFacility);                                     // the rival's, not ours

        $this->reservation();                                                             // pending pickup
        $this->reservation(['fulfilment_mode' => MedicineReservation::FULFILMENT_DELIVERY, 'status' => MedicineReservation::STATUS_ACCEPTED]);
        $this->reservation(['status' => MedicineReservation::STATUS_COLLECTED]);
        $this->reservation(['stock' => $this->stock($this->otherListing, $this->paracetamol, 9, 1000)]); // rival's

        $this->desk('pharmacist', 'GET', '/home')
            ->assertOk()
            ->assertJsonPath('data.facility.id', $this->facility->id)
            ->assertJsonPath('data.facility.name', 'Pharmacie du Test')
            ->assertJsonPath('data.counts.incoming', 2)
            ->assertJsonPath('data.counts.preparing', 1)
            ->assertJsonPath('data.counts.ready', 1)
            ->assertJsonPath('data.counts.reservations_pending', 1)
            ->assertJsonPath('data.counts.deliveries_open', 1)
            ->assertJsonPath('data.counts.low_stock', 1);

        // The rival sees only its own work.
        $this->desk('rival', 'GET', '/home')
            ->assertOk()
            ->assertJsonPath('data.counts.incoming', 1)
            ->assertJsonPath('data.counts.preparing', 0)
            ->assertJsonPath('data.counts.reservations_pending', 1)
            ->assertJsonPath('data.counts.low_stock', 0);
    }

    // ── Queue ─────────────────────────────────────────────────────────────

    public function test_the_queue_is_filtered_by_bench_position_and_scoped_to_this_facility(): void
    {
        $incoming  = $this->routed($this->facility);
        $preparing = $this->routed($this->facility, PharmacyDeskService::ROUTING_CONFIRMED);
        $ready     = $this->routed($this->facility, PharmacyDeskService::ROUTING_READY);
        $dispensed = $this->routed($this->facility, PharmacyDeskService::ROUTING_DISPENSED, ['status' => 'dispensed', 'dispensed_at' => now()]);
        $rivals    = $this->routed($this->otherFacility);
        $walkIn    = $this->prescription(); // not routed anywhere — not on any bench

        $ids = fn (string $query) => collect($this->desk('pharmacist', 'GET', '/prescriptions' . $query)->assertOk()->json('data'))->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$incoming->id], $ids('?status=incoming'));
        $this->assertEqualsCanonicalizing([$preparing->id], $ids('?status=preparing'));
        $this->assertEqualsCanonicalizing([$ready->id], $ids('?status=ready'));
        $this->assertEqualsCanonicalizing([$dispensed->id], $ids('?status=dispensed'));
        $this->assertEqualsCanonicalizing([$incoming->id, $preparing->id, $ready->id], $ids(''));

        foreach ([$rivals->id, $walkIn->id] as $foreign) {
            $this->assertNotContains($foreign, $ids(''));
            $this->assertNotContains($foreign, $ids('?status=incoming'));
        }

        $this->desk('pharmacist', 'GET', '/prescriptions?status=bogus')->assertStatus(422);
    }

    public function test_a_queue_row_carries_the_contract_shape(): void
    {
        $rx = $this->routed($this->facility);

        $row = $this->desk('pharmacist', 'GET', '/prescriptions?status=incoming')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data' => [[
                'id', 'reference', 'status', 'pharmacy_routing_status', 'prescribed_at', 'expires_at', 'prescriber_name',
                'patient' => ['id', 'health_id', 'display_name'],
                'items'   => [['id', 'drug_name', 'dose', 'frequency', 'duration_days', 'quantity', 'status']],
            ]]])
            ->json('data.0');

        $this->assertSame($rx->id, $row['id']);
        $this->assertSame($rx->reference, $row['reference']);
        $this->assertSame('active', $row['status']);
        $this->assertSame('pending', $row['pharmacy_routing_status']);
        $this->assertSame($this->users['doctor']->name, $row['prescriber_name']);
        $this->assertSame(self::HEALTH_ID, $row['patient']['health_id']);
        $this->assertSame('Nadia Finder', $row['patient']['display_name']);
        $this->assertSame('Paracetamol 500 mg', $row['items'][0]['drug_name']);
        $this->assertSame('pending', $row['items'][0]['status']);
    }

    // ── Lookup ────────────────────────────────────────────────────────────

    public function test_lookup_is_by_exact_health_id_and_shows_only_what_this_counter_may_fill(): void
    {
        $walkIn      = $this->prescription();
        $routedHere  = $this->routed($this->facility);
        $routedAway  = $this->routed($this->otherFacility);
        $dispensed   = $this->prescription(['status' => 'dispensed', 'dispensed_at' => now()]);
        $expired     = $this->prescription(['expires_at' => now()->subDay()]);

        $response = $this->desk('pharmacist', 'GET', '/prescriptions/lookup?health_id=' . self::HEALTH_ID)
            ->assertOk()
            ->assertJsonPath('patient.health_id', self::HEALTH_ID)
            ->assertJsonPath('patient.display_name', 'Nadia Finder');

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$walkIn->id, $routedHere->id], $ids);
        foreach ([$routedAway->id, $dispensed->id, $expired->id] as $hidden) {
            $this->assertNotContains($hidden, $ids);
        }

        // Case is normalised on the whole identifier — still an equality.
        $this->desk('pharmacist', 'GET', '/prescriptions/lookup?health_id=' . strtolower(self::HEALTH_ID))
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_a_partial_health_id_finds_nothing_and_says_nothing(): void
    {
        $this->prescription();

        foreach (['CM-HID-TEST-DESK', 'CM-HID-TEST-DESK-000', 'CM-HID-TEST-DESK-0001%', 'NADIA'] as $partial) {
            $this->desk('pharmacist', 'GET', '/prescriptions/lookup?health_id=' . urlencode($partial))
                ->assertOk()
                ->assertJsonPath('patient', null)
                ->assertJsonCount(0, 'data');
        }

        $this->desk('pharmacist', 'GET', '/prescriptions/lookup')->assertStatus(422);
    }

    // ── accept → ready → dispense ─────────────────────────────────────────

    public function test_a_routed_script_goes_incoming_to_preparing_to_ready_to_dispensed(): void
    {
        $rx   = $this->routed($this->facility);
        $item = $rx->items->first();

        $this->desk('pharmacist', 'POST', "/prescriptions/{$rx->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.id', $rx->id)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.pharmacy_routing_status', 'confirmed');
        $this->assertNotNull($rx->fresh()->pharmacy_confirmed_at);
        $this->assertCount(0, $this->patientNotifications());

        $this->desk('pharmacist', 'POST', "/prescriptions/{$rx->id}/ready")
            ->assertOk()
            ->assertJsonPath('data.pharmacy_routing_status', 'ready');

        $ready = $this->patientNotifications('pharmacy_prescription_ready');
        $this->assertCount(1, $ready);
        $this->assertSame('pharmacy_prescription_ready', $ready->first()->payload['type']);
        $this->assertSame('/prescriptions', $ready->first()->payload['action_url']);
        $this->assertStringContainsString('Pharmacie du Test', $ready->first()->payload['message']);
        $this->assertStringContainsString($rx->reference, $ready->first()->payload['message']);
        $this->assertStringNotContainsString('Paracetamol', $ready->first()->payload['message']);
        foreach (['title', 'message', 'severity', 'action_url', 'action_label'] as $key) {
            $this->assertArrayHasKey($key, $ready->first()->payload);
        }

        $this->desk('pharmacist', 'POST', "/prescriptions/{$rx->id}/dispense", [
            'items' => [['id' => $item->id, 'quantity_dispensed' => 10]],
            'notes' => 'Generic substituted with patient consent.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'dispensed')
            ->assertJsonPath('data.pharmacy_routing_status', 'dispensed')
            ->assertJsonPath('data.items.0.status', 'dispensed');

        $rx->refresh();
        $this->assertSame('dispensed', $rx->status);
        $this->assertSame($this->users['pharmacist']->id, $rx->dispensed_by, 'the dispenser is the provider the token names');
        $this->assertNotNull($rx->dispensed_at);

        $item->refresh();
        $this->assertSame('dispensed', $item->status);
        $this->assertStringContainsString('Quantity dispensed: 10', (string) $item->dispense_notes);
        $this->assertStringContainsString('Generic substituted', (string) $item->dispense_notes);

        $this->assertCount(1, $this->patientNotifications('pharmacy_prescription_dispensed'));
        $this->assertDatabaseHas('audit_events', ['resource_type' => 'Prescription', 'resource_id' => $rx->id, 'action_type' => 'prescription_dispensed']);

        // Dispensed is terminal — for the counter and for the record.
        $this->desk('pharmacist', 'POST', "/prescriptions/{$rx->id}/dispense")
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'invalid_transition');
        $this->desk('pharmacist', 'POST', "/prescriptions/{$rx->id}/accept")
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'invalid_transition');
        $this->assertCount(1, $this->patientNotifications('pharmacy_prescription_dispensed'), 'a refused replay tells the patient nothing');
    }

    public function test_out_of_order_steps_are_refused_as_invalid_transitions(): void
    {
        $rx = $this->routed($this->facility);

        // ready before accept
        $this->desk('pharmacist', 'POST', "/prescriptions/{$rx->id}/ready")
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'invalid_transition');
        $this->assertSame('pending', $rx->fresh()->pharmacy_routing_status);

        $this->desk('pharmacist', 'POST', "/prescriptions/{$rx->id}/accept")->assertOk();

        // accept twice
        $this->desk('pharmacist', 'POST', "/prescriptions/{$rx->id}/accept")
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'invalid_transition');
        $this->assertSame('confirmed', $rx->fresh()->pharmacy_routing_status);
    }

    public function test_a_walk_in_script_found_by_health_id_is_claimed_and_dispensed_on_the_spot(): void
    {
        $rx = $this->prescription(); // written at the hospital, routed nowhere
        $this->assertNull($rx->pharmacy_route_id);

        $this->desk('pharmacist', 'POST', "/prescriptions/{$rx->id}/dispense", ['notes' => 'Counter dispense.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'dispensed')
            ->assertJsonPath('data.pharmacy_routing_status', 'dispensed');

        $rx->refresh();
        $route = PharmacyRoute::where('facility_id', $this->facility->id)->where('is_active', true)->first();
        $this->assertNotNull($route, 'the first claim creates the facility\'s dispensing route');
        $this->assertSame($route->id, $rx->pharmacy_route_id);
        $this->assertSame('Pharmacie du Test', $rx->dispensing_pharmacy_name);
        $this->assertSame($this->users['pharmacist']->id, $rx->dispensed_by);
        $this->assertSame('Counter dispense.', $rx->items->first()->dispense_notes);

        // One route per facility, however many scripts are claimed.
        $this->desk('pharmacist', 'POST', '/prescriptions/' . $this->prescription()->id . '/accept')->assertOk();
        $this->assertSame(1, PharmacyRoute::where('facility_id', $this->facility->id)->count());
    }

    public function test_a_script_routed_to_another_pharmacy_is_not_at_this_counter(): void
    {
        $rivals = $this->routed($this->otherFacility);

        foreach (['accept', 'ready', 'dispense'] as $step) {
            $this->desk('pharmacist', 'POST', "/prescriptions/{$rivals->id}/{$step}")
                ->assertNotFound()
                ->assertJsonPath('error_code', 'prescription_not_found');
        }

        $rivals->refresh();
        $this->assertSame('pending', $rivals->pharmacy_routing_status);
        $this->assertSame('active', $rivals->status);
        $this->assertSame('Pharmacie Rivale', $rivals->dispensing_pharmacy_name, 'never re-routed away from the rival');

        // Nonexistent and malformed ids get the very same answer.
        $this->desk('pharmacist', 'POST', '/prescriptions/' . Str::uuid() . '/accept')
            ->assertNotFound()
            ->assertJsonPath('error_code', 'prescription_not_found');
        $this->desk('pharmacist', 'POST', '/prescriptions/not-a-uuid/accept')->assertNotFound();
    }

    public function test_a_script_that_can_no_longer_be_filled_is_refused_and_left_alone(): void
    {
        $expired = $this->routed($this->facility, PharmacyDeskService::ROUTING_PENDING, ['expires_at' => now()->subDay()]);
        $this->desk('pharmacist', 'POST', "/prescriptions/{$expired->id}/accept")
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'not_dispensable');
        $this->assertSame('pending', $expired->fresh()->pharmacy_routing_status);

        $voided = $this->routed($this->facility, PharmacyDeskService::ROUTING_CONFIRMED);
        Prescription::whereKey($voided->id)->update(['status' => 'voided', 'voided_at' => now(), 'void_reason' => 'test']);
        $this->desk('pharmacist', 'POST', "/prescriptions/{$voided->id}/dispense")
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'not_dispensable');
        $this->assertSame('voided', $voided->fresh()->status);
    }

    public function test_dispensing_refuses_an_item_that_is_not_on_the_script(): void
    {
        $rx    = $this->routed($this->facility, PharmacyDeskService::ROUTING_CONFIRMED);
        $other = $this->prescription();

        $this->desk('pharmacist', 'POST', "/prescriptions/{$rx->id}/dispense", [
            'items' => [['id' => $other->items->first()->id, 'quantity_dispensed' => 1]],
        ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'item_not_on_prescription');

        $this->assertSame('active', $rx->fresh()->status);
        $this->assertSame('pending', $rx->fresh()->items->first()->status);
        $this->assertCount(0, $this->patientNotifications());

        $this->desk('pharmacist', 'POST', "/prescriptions/{$rx->id}/dispense", ['items' => [['id' => 'nope']]])
            ->assertStatus(422);
    }

    public function test_the_rival_pharmacist_cannot_see_or_move_our_bench(): void
    {
        $ours = $this->routed($this->facility);

        $this->desk('rival', 'GET', '/prescriptions')->assertOk()->assertJsonCount(0, 'data');
        $this->desk('rival', 'POST', "/prescriptions/{$ours->id}/accept")->assertNotFound();
        $this->assertSame('pending', $ours->fresh()->pharmacy_routing_status);
    }
}
