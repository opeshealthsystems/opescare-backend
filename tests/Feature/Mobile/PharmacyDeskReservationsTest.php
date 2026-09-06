<?php

namespace Tests\Feature\Mobile;

use App\Models\MedicineReservation;
use Illuminate\Support\Str;
use Tests\Traits\WithMobileAuth;

/**
 * The pharmacy desk's reservation bench: /api/mobile/pharmacy-desk/reservations
 * and the patient-side fulfilment fields it depends on.
 */
class PharmacyDeskReservationsTest extends PharmacyDeskTestCase
{
    use WithMobileAuth;

    // ── List ──────────────────────────────────────────────────────────────

    public function test_the_list_is_scoped_to_this_pharmacy_and_filtered_by_desk_status(): void
    {
        $pending    = $this->reservation();
        $accepted   = $this->reservation(['status' => MedicineReservation::STATUS_ACCEPTED]);
        $ready      = $this->reservation(['status' => MedicineReservation::STATUS_READY]);
        $collected  = $this->reservation(['status' => MedicineReservation::STATUS_COLLECTED, 'collected_at' => now()]);
        $dispatched = $this->reservation(['fulfilment_mode' => MedicineReservation::FULFILMENT_DELIVERY, 'status' => MedicineReservation::STATUS_READY, 'delivery_status' => MedicineReservation::DELIVERY_DISPATCHED, 'delivery_address' => 'Bastos']);
        $delivered  = $this->reservation(['fulfilment_mode' => MedicineReservation::FULFILMENT_DELIVERY, 'status' => MedicineReservation::STATUS_COLLECTED, 'delivery_status' => MedicineReservation::DELIVERY_DELIVERED, 'delivery_address' => 'Bastos']);
        $cancelled  = $this->reservation(['status' => MedicineReservation::STATUS_CANCELLED, 'cancelled_at' => now()]);
        $rejected   = $this->reservation(['status' => MedicineReservation::STATUS_REJECTED]);
        $rivals     = $this->reservation(['stock' => $this->stock($this->otherListing, $this->paracetamol, 9, 1000)]);

        $ids = fn (string $query) => collect($this->desk('pharmacist', 'GET', '/reservations' . $query)->assertOk()->json('data'))->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$pending->id], $ids('?status=pending'));
        $this->assertEqualsCanonicalizing([$accepted->id], $ids('?status=accepted'));
        $this->assertEqualsCanonicalizing([$ready->id], $ids('?status=ready'));
        $this->assertEqualsCanonicalizing([$collected->id], $ids('?status=collected'));
        $this->assertEqualsCanonicalizing([$dispatched->id], $ids('?status=dispatched'));
        $this->assertEqualsCanonicalizing([$delivered->id], $ids('?status=delivered'));
        $this->assertEqualsCanonicalizing([$cancelled->id, $rejected->id], $ids('?status=cancelled'));
        $this->assertEqualsCanonicalizing([$pending->id, $accepted->id, $ready->id, $dispatched->id], $ids(''), 'no filter = everything still open');

        $this->assertNotContains($rivals->id, $ids(''));
        $this->assertNotContains($rivals->id, $ids('?status=pending'));
        $this->assertContains($rivals->id, collect($this->desk('rival', 'GET', '/reservations')->json('data'))->pluck('id')->all());

        $this->desk('pharmacist', 'GET', '/reservations?status=ready_for_pickup')->assertStatus(422, 'the legacy column spelling is not the desk vocabulary');
    }

    public function test_a_row_carries_the_contract_shape_with_the_desk_status_and_a_short_patient_name(): void
    {
        $r = $this->reservation([
            'fulfilment_mode'  => MedicineReservation::FULFILMENT_DELIVERY,
            'delivery_address' => 'Quartier Bastos, Yaoundé',
            'delivery_phone'   => '+237 6 77 00 11 22',
            'status'           => MedicineReservation::STATUS_ACCEPTED,
            'patient_note'     => 'Call before delivering.',
        ]);

        $row = $this->desk('pharmacist', 'GET', '/reservations?status=accepted')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data' => [[
                'id', 'reference', 'status', 'fulfilment_mode', 'delivery_address', 'delivery_phone', 'delivery_status',
                'quantity', 'total_price', 'currency', 'expires_at', 'created_at',
                'medicine' => ['id', 'name', 'strength', 'form'],
                'patient'  => ['display_name', 'health_id'],
            ]]])
            ->json('data.0');

        $this->assertSame($r->id, $row['id']);
        $this->assertSame('accepted', $row['status']);
        $this->assertSame('delivery', $row['fulfilment_mode']);
        $this->assertSame('Quartier Bastos, Yaoundé', $row['delivery_address']);
        $this->assertSame('+237 6 77 00 11 22', $row['delivery_phone']);
        $this->assertNull($row['delivery_status']);
        $this->assertSame(2, $row['quantity']);
        $this->assertSame(3000.0, (float) $row['total_price']);
        $this->assertSame('XAF', $row['currency']);
        $this->assertSame('Paracetamol 500mg Tablet', $row['medicine']['name']);
        $this->assertSame('Nadia F.', $row['patient']['display_name']);
        $this->assertSame(self::HEALTH_ID, $row['patient']['health_id']);
        $this->assertSame('Call before delivering.', $row['patient_note']);
    }

    // ── Pickup machine ────────────────────────────────────────────────────

    public function test_a_pickup_goes_pending_to_accepted_to_ready_to_collected(): void
    {
        $r = $this->reservation();

        $this->desk('pharmacist', 'POST', "/reservations/{$r->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.status', 'accepted');
        $r->refresh();
        $this->assertSame('confirmed', $r->status->value, 'the column keeps the legacy spelling');
        $this->assertNotNull($r->accepted_at);
        $this->assertCount(0, $this->patientNotifications());

        $this->desk('pharmacist', 'POST', "/reservations/{$r->id}/ready")
            ->assertOk()
            ->assertJsonPath('data.status', 'ready');
        $r->refresh();
        $this->assertSame('ready_for_pickup', $r->status->value);
        $this->assertNotNull($r->ready_at);

        $ready = $this->patientNotifications('pharmacy_reservation_ready');
        $this->assertCount(1, $ready);
        $this->assertStringContainsString($r->reference, $ready->first()->payload['message']);
        $this->assertStringContainsString('Pharmacie du Test', $ready->first()->payload['message']);
        $this->assertStringNotContainsString('Paracetamol', $ready->first()->payload['title'] . $ready->first()->payload['message']);
        $this->assertSame($r->id, $ready->first()->payload['reservation_id']);

        $this->desk('pharmacist', 'POST', "/reservations/{$r->id}/collected")
            ->assertOk()
            ->assertJsonPath('data.status', 'collected');
        $r->refresh();
        $this->assertSame('collected', $r->status->value);
        $this->assertNotNull($r->collected_at);
        $this->assertNull($r->delivery_status);

        // Terminal.
        foreach (['accept', 'ready', 'collected', 'dispatch', 'delivered'] as $step) {
            $this->desk('pharmacist', 'POST', "/reservations/{$r->id}/{$step}")
                ->assertStatus(409)
                ->assertJsonPath('error_code', 'invalid_transition');
        }
        $this->desk('pharmacist', 'POST', "/reservations/{$r->id}/cancel", ['reason' => 'Too late'])
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'invalid_transition');
        $this->assertSame('collected', $r->fresh()->status->value);
    }

    public function test_skipping_a_step_is_an_invalid_transition_and_changes_nothing(): void
    {
        $r = $this->reservation();

        foreach (['ready', 'collected', 'dispatch', 'delivered'] as $step) {
            $this->desk('pharmacist', 'POST', "/reservations/{$r->id}/{$step}")
                ->assertStatus(409)
                ->assertJsonPath('error_code', $step === 'dispatch' ? 'dispatch_is_for_delivery' : 'invalid_transition')
                ->assertJsonStructure(['message', 'error_code']);
        }

        $r->refresh();
        $this->assertSame('pending', $r->status->value);
        $this->assertNull($r->ready_at);
        $this->assertNull($r->collected_at);
        $this->assertCount(0, $this->patientNotifications());

        $this->desk('pharmacist', 'POST', "/reservations/{$r->id}/accept")->assertOk();
        $this->desk('pharmacist', 'POST', "/reservations/{$r->id}/accept")
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'invalid_transition');
    }

    // ── Delivery machine ──────────────────────────────────────────────────

    public function test_a_delivery_goes_ready_to_dispatched_to_delivered_and_tells_the_patient_at_each_step(): void
    {
        $r = $this->reservation([
            'fulfilment_mode'  => MedicineReservation::FULFILMENT_DELIVERY,
            'delivery_address' => 'Quartier Bastos, Yaoundé',
            'delivery_phone'   => '+237 6 77 00 11 22',
        ]);

        $this->desk('pharmacist', 'POST', "/reservations/{$r->id}/accept")->assertOk()->assertJsonPath('data.status', 'accepted');
        $this->desk('pharmacist', 'POST', "/reservations/{$r->id}/ready")->assertOk()->assertJsonPath('data.status', 'ready');

        // A delivery is not collected at the counter.
        $this->desk('pharmacist', 'POST', "/reservations/{$r->id}/collected")
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'collect_is_for_pickup');

        $this->desk('pharmacist', 'POST', "/reservations/{$r->id}/dispatch")
            ->assertOk()
            ->assertJsonPath('data.status', 'dispatched')
            ->assertJsonPath('data.delivery_status', 'dispatched')
            ->assertJsonPath('data.fulfilment_mode', 'delivery');
        $r->refresh();
        $this->assertSame('ready_for_pickup', $r->status->value, 'the legacy column never learns a word the enum lacks');
        $this->assertSame('dispatched', $r->delivery_status);
        $this->assertNotNull($r->dispatched_at);

        // Dispatch twice, or deliver before dispatch, is refused.
        $this->desk('pharmacist', 'POST', "/reservations/{$r->id}/dispatch")->assertStatus(409)->assertJsonPath('error_code', 'invalid_transition');

        $this->desk('pharmacist', 'POST', "/reservations/{$r->id}/delivered")
            ->assertOk()
            ->assertJsonPath('data.status', 'delivered')
            ->assertJsonPath('data.delivery_status', 'delivered');
        $r->refresh();
        $this->assertSame('collected', $r->status->value);
        $this->assertSame('delivered', $r->delivery_status);
        $this->assertNotNull($r->delivered_at);
        $this->assertNotNull($r->collected_at);

        $this->desk('pharmacist', 'POST', "/reservations/{$r->id}/delivered")->assertStatus(409)->assertJsonPath('error_code', 'invalid_transition');

        // ready, dispatched, delivered — one bell entry each, none naming the medicine.
        $types = $this->patientNotifications()->pluck('payload.type')->all();
        $this->assertEqualsCanonicalizing(['pharmacy_reservation_ready', 'pharmacy_reservation_dispatched', 'pharmacy_reservation_delivered'], $types);
        foreach ($this->patientNotifications() as $n) {
            $this->assertStringNotContainsString('Paracetamol', $n->payload['title'] . ' ' . $n->payload['message']);
            $this->assertSame('/pharmacy', $n->payload['action_url']);
            $this->assertSame('normal', $n->payload['severity']);
        }
    }

    public function test_a_delivery_that_was_never_dispatched_cannot_be_delivered(): void
    {
        $r = $this->reservation(['fulfilment_mode' => MedicineReservation::FULFILMENT_DELIVERY, 'delivery_address' => 'Bastos', 'status' => MedicineReservation::STATUS_READY]);

        $this->desk('pharmacist', 'POST', "/reservations/{$r->id}/delivered")
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'invalid_transition');
        $this->assertNull($r->fresh()->delivery_status);
    }

    // ── Cancel ────────────────────────────────────────────────────────────

    public function test_any_open_reservation_can_be_cancelled_with_a_reason_and_only_with_one(): void
    {
        $pending    = $this->reservation();
        $dispatched = $this->reservation(['fulfilment_mode' => MedicineReservation::FULFILMENT_DELIVERY, 'delivery_address' => 'Bastos', 'status' => MedicineReservation::STATUS_READY, 'delivery_status' => MedicineReservation::DELIVERY_DISPATCHED]);

        $this->desk('pharmacist', 'POST', "/reservations/{$pending->id}/cancel")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
        $this->assertSame('pending', $pending->fresh()->status->value);

        $this->desk('pharmacist', 'POST', "/reservations/{$pending->id}/cancel", ['reason' => 'Out of stock after a recall.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.cancelled_reason', 'Out of stock after a recall.');
        $pending->refresh();
        $this->assertSame('cancelled', $pending->status->value);
        $this->assertNotNull($pending->cancelled_at);

        $this->desk('pharmacist', 'POST', "/reservations/{$dispatched->id}/cancel", ['reason' => 'Courier could not find the address.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        // Cancelled is terminal.
        $this->desk('pharmacist', 'POST', "/reservations/{$pending->id}/accept")->assertStatus(409)->assertJsonPath('error_code', 'invalid_transition');
        $this->desk('pharmacist', 'POST', "/reservations/{$pending->id}/cancel", ['reason' => 'again'])->assertStatus(409);
    }

    // ── Scope ─────────────────────────────────────────────────────────────

    public function test_another_pharmacys_reservation_is_not_at_this_desk(): void
    {
        $rivals = $this->reservation(['stock' => $this->stock($this->otherListing, $this->paracetamol, 9, 1000)]);

        foreach (['accept', 'ready', 'collected', 'dispatch', 'delivered'] as $step) {
            $this->desk('pharmacist', 'POST', "/reservations/{$rivals->id}/{$step}")
                ->assertNotFound()
                ->assertJsonPath('error_code', 'reservation_not_found');
        }
        $this->desk('pharmacist', 'POST', "/reservations/{$rivals->id}/cancel", ['reason' => 'not mine'])->assertNotFound();
        $this->assertSame('pending', $rivals->fresh()->status->value);

        $this->desk('pharmacist', 'POST', '/reservations/' . Str::uuid() . '/accept')->assertNotFound();
        $this->desk('pharmacist', 'POST', '/reservations/nope/accept')->assertNotFound();

        // The rival's own pharmacist can.
        $this->desk('rival', 'POST', "/reservations/{$rivals->id}/accept")->assertOk();
    }

    public function test_roles_that_do_not_dispense_cannot_touch_reservations(): void
    {
        $r = $this->reservation();

        foreach (['doctor', 'receptionist', 'stockkeeper'] as $role) {
            $this->desk($role, 'POST', "/reservations/{$r->id}/accept")
                ->assertForbidden()
                ->assertJsonPath('error_code', 'role_forbidden');
        }

        $this->assertSame('pending', $r->fresh()->status->value);
    }

    // ── The patient side keeps working ────────────────────────────────────

    public function test_the_patient_can_ask_for_delivery_when_reserving_and_pickup_stays_the_default(): void
    {
        $delivery = $this->mobilePostJson($this->patient, '/api/mobile/pharmacy/reservations', [
            'medicine_id'      => $this->paracetamol->id,
            'care_facility_id' => $this->listing->id,
            'quantity'         => 1,
            'fulfilment_mode'  => 'delivery',
            'delivery_address' => 'Quartier Bastos, rue 1.771',
            'delivery_phone'   => '+237 6 77 00 11 22',
        ])->assertCreated();

        $this->assertDatabaseHas('medicine_reservations', [
            'id'               => $delivery->json('data.id'),
            'fulfilment_mode'  => 'delivery',
            'delivery_address' => 'Quartier Bastos, rue 1.771',
            'delivery_phone'   => '+237 6 77 00 11 22',
            'status'           => 'pending',
        ]);

        // …and the desk sees it as an open delivery.
        $this->desk('pharmacist', 'GET', '/home')->assertOk()->assertJsonPath('data.counts.deliveries_open', 1);

        // A delivery needs somewhere to go.
        $this->mobilePostJson($this->patient, '/api/mobile/pharmacy/reservations', [
            'medicine_id'      => $this->amoxicillin->id,
            'care_facility_id' => $this->listing->id,
            'fulfilment_mode'  => 'delivery',
        ])->assertStatus(422)->assertJsonValidationErrors(['delivery_address']);

        // Not a mode we know.
        $this->mobilePostJson($this->patient, '/api/mobile/pharmacy/reservations', [
            'medicine_id'      => $this->amoxicillin->id,
            'care_facility_id' => $this->listing->id,
            'fulfilment_mode'  => 'teleport',
        ])->assertStatus(422)->assertJsonValidationErrors(['fulfilment_mode']);

        // Nothing said = pickup, and a pickup keeps no address even if one is sent.
        $this->stock($this->otherListing, $this->paracetamol, 9, 1000);
        $pickup = $this->mobilePostJson($this->patient, '/api/mobile/pharmacy/reservations', [
            'medicine_id'      => $this->paracetamol->id,
            'care_facility_id' => $this->otherListing->id,
            'delivery_address' => 'should be dropped',
        ])->assertCreated();
        $this->assertDatabaseHas('medicine_reservations', ['id' => $pickup->json('data.id'), 'fulfilment_mode' => 'pickup', 'delivery_address' => null]);
    }

    public function test_the_patients_own_list_still_reads_a_dispatched_delivery_through_the_legacy_enum(): void
    {
        $r = $this->reservation(['fulfilment_mode' => MedicineReservation::FULFILMENT_DELIVERY, 'delivery_address' => 'Bastos', 'status' => MedicineReservation::STATUS_READY, 'delivery_status' => MedicineReservation::DELIVERY_DISPATCHED]);

        $this->mobileGetJson($this->patient, '/api/mobile/pharmacy/reservations')
            ->assertOk()
            ->assertJsonPath('data.0.id', $r->id)
            ->assertJsonPath('data.0.status', 'ready_for_pickup')
            ->assertJsonPath('data.0.is_open', true);
    }
}
