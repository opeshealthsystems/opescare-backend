<?php

namespace Tests\Feature\Prescriptions;

use App\Models\AuditEvent;
use App\Models\Prescription;
use App\Services\Clinical\PrescriptionService;
use App\Services\Pharmacy\PharmacyDeskService;
use App\Support\ClinicalRelationshipGate;
use Tests\Feature\Mobile\PharmacyDeskTestCase;

/**
 * `prescriptions.status` must tell the truth about its own lines.
 *
 * The schema has allowed `partially_dispensed` since the table was created and
 * six readers check for it — the pharmacy desk queue, the dispensing basis of
 * ClinicalRelationshipGate, the pharmacy portal, and three Health Passport
 * pages — but nothing ever wrote it: PrescriptionService::dispense() marked
 * every line dispensed and hard-set the header to 'dispensed'. A script with
 * one drug collected and one still on the shelf therefore sat at 'active',
 * contradicting `prescription_items.status`, which had recorded the hand-over
 * correctly all along.
 *
 * The header is now DERIVED from the lines on BOTH write paths — the shared
 * service (which the pharmacy portal calls) and the pharmacy desk API — so the
 * two cannot disagree.
 *
 * The fixture is the pharmacy desk's: one pharmacy with its directory listing,
 * the hospital that writes the scripts, one patient, and a provider session per
 * role. It is the only fixture that gives both write paths at once.
 */
class PartialDispensingTest extends PharmacyDeskTestCase
{
    /** Two drugs on one script — the smallest thing that can be half collected. */
    private const TWO_DRUGS = [
        ['drug_name' => 'Metformin 1000 mg', 'quantity' => 60],
        ['drug_name' => 'Amlodipine 5 mg',   'quantity' => 30],
    ];

    private function service(): PrescriptionService
    {
        return app(PrescriptionService::class);
    }

    // ── The truth table, on the shared service ────────────────────────────

    public function test_every_line_handed_over_leaves_the_header_dispensed(): void
    {
        $rx = $this->prescription([], self::TWO_DRUGS);

        $this->service()->dispense($rx, $this->users['pharmacist']->id);

        $rx = $rx->fresh(['items']);

        $this->assertSame('dispensed', $rx->status);
        $this->assertNotNull($rx->dispensed_at, 'A completed hand-over records when it happened.');
        $this->assertSame($this->users['pharmacist']->id, $rx->dispensed_by);
        $this->assertSame(['dispensed', 'dispensed'], $rx->items->pluck('status')->sort()->values()->all());
    }

    public function test_some_lines_handed_over_leaves_the_header_partially_dispensed(): void
    {
        $rx    = $this->prescription([], self::TWO_DRUGS);
        $first = $rx->items->first();

        $this->service()->dispense($rx, $this->users['pharmacist']->id, ['item_ids' => [$first->id]]);

        $rx = $rx->fresh(['items']);

        $this->assertSame('partially_dispensed', $rx->status);
        $this->assertNull($rx->dispensed_at, 'Nothing is "dispensed at" a moment while a drug is still owed.');
        $this->assertSame($this->users['pharmacist']->id, $rx->dispensed_by, 'The pharmacist behind the counter is still named.');

        $this->assertSame('dispensed', $rx->items->firstWhere('id', $first->id)->status);
        $this->assertSame('pending', $rx->items->firstWhere('id', '!=', $first->id)->status);

        // A partial is audited as a partial, not as a completed hand-over.
        $this->assertDatabaseHas('audit_events', [
            'resource_id' => $rx->id,
            'action_type' => 'prescription_partially_dispensed',
        ]);
        $this->assertSame(0, AuditEvent::where('resource_id', $rx->id)
            ->where('action_type', 'prescription_dispensed')->count());
    }

    public function test_no_lines_handed_over_leaves_the_header_unchanged(): void
    {
        $rx = $this->prescription([], self::TWO_DRUGS);

        $this->assertSame('active', $rx->status);
        $this->assertNull(
            $rx->dispensingStatusFromItems(),
            'With nothing handed over the lines say nothing, so the header keeps what it has.'
        );

        // And a call that would hand over nothing is refused rather than
        // silently reporting a dispense that did not happen.
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('PRESCRIPTION_NO_ITEMS_TO_DISPENSE');

        $this->service()->dispense($rx, $this->users['pharmacist']->id, ['item_ids' => []]);
    }

    public function test_the_header_stays_active_when_a_refused_call_hands_over_nothing(): void
    {
        $rx = $this->prescription([], self::TWO_DRUGS);

        try {
            $this->service()->dispense($rx, $this->users['pharmacist']->id, ['item_ids' => []]);
        } catch (\LogicException $e) {
            // expected — asserted in the test above
        }

        $rx = $rx->fresh(['items']);

        $this->assertSame('active', $rx->status);
        $this->assertSame(['pending', 'pending'], $rx->items->pluck('status')->all());
    }

    public function test_a_cancelled_line_is_not_outstanding_so_the_script_is_fully_dispensed(): void
    {
        $rx = $this->prescription([], self::TWO_DRUGS);

        // The second drug is cancelled — out of stock, discontinued, whatever.
        // Nobody is waiting for it, so it must not hold the script at partial.
        $cancelled = $rx->items->last();
        $cancelled->status = 'cancelled';
        $cancelled->save();

        $this->service()->dispense($rx, $this->users['pharmacist']->id);

        $rx = $rx->fresh(['items']);

        $this->assertSame('dispensed', $rx->status);
        $this->assertSame('cancelled', $rx->items->firstWhere('id', $cancelled->id)->status,
            'A cancelled line is never handed over by a dispense.');
    }

    public function test_completing_a_partially_dispensed_script_marks_it_dispensed(): void
    {
        $rx    = $this->prescription([], self::TWO_DRUGS);
        $first = $rx->items->first();

        $this->service()->dispense($rx, $this->users['pharmacist']->id, ['item_ids' => [$first->id]]);
        $this->assertSame('partially_dispensed', $rx->fresh()->status);

        // Still dispensable — `partially_dispensed` is not a terminal state.
        $this->service()->dispense($rx->fresh(), $this->users['pharmacist']->id);

        $rx = $rx->fresh(['items']);

        $this->assertSame('dispensed', $rx->status);
        $this->assertNotNull($rx->dispensed_at);
        $this->assertSame(['dispensed', 'dispensed'], $rx->items->pluck('status')->sort()->values()->all());

        // A fully dispensed script is closed to a third attempt, as before.
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('PRESCRIPTION_ALREADY_DISPENSED');
        $this->service()->dispense($rx->fresh(), $this->users['pharmacist']->id);
    }

    // ── The same truth, through the pharmacy desk ─────────────────────────

    public function test_the_desk_records_a_partial_hand_over_and_keeps_the_script_on_the_bench(): void
    {
        // routed() builds a one-item script; a partial needs two drugs.
        $rx = $this->desk->routeToFacility($this->prescription([], self::TWO_DRUGS), $this->facility);
        $rx->update(['pharmacy_routing_status' => PharmacyDeskService::ROUTING_CONFIRMED]);
        $rx = $rx->fresh(['items']);

        [$first, $second] = [$rx->items->first(), $rx->items->last()];

        $this->desk('pharmacist', 'POST', "/prescriptions/{$rx->id}/dispense", [
            'items' => [
                ['id' => $first->id,  'quantity_dispensed' => 60],
                ['id' => $second->id, 'quantity_dispensed' => 0],  // none left the shelf
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'partially_dispensed')
            ->assertJsonPath('data.pharmacy_routing_status', PharmacyDeskService::ROUTING_CONFIRMED);

        $rx = $rx->fresh(['items']);
        $this->assertSame('partially_dispensed', $rx->status);
        $this->assertSame('dispensed', $rx->items->firstWhere('id', $first->id)->status);
        $this->assertSame('pending', $rx->items->firstWhere('id', $second->id)->status);

        // It is still on the bench, because a drug is still owed.
        $open = collect($this->desk('pharmacist', 'GET', '/prescriptions?status=preparing')->json('data'))
            ->pluck('id')->all();
        $this->assertContains($rx->id, $open, 'A half-filled script must stay in the queue.');

        $done = collect($this->desk('pharmacist', 'GET', '/prescriptions?status=dispensed')->json('data'))
            ->pluck('id')->all();
        $this->assertNotContains($rx->id, $done);

        // And the counter keeps its access basis — it still has work to do.
        $this->assertSame(
            ClinicalRelationshipGate::DISPENSING,
            ClinicalRelationshipGate::evaluate(
                $this->users['pharmacist']->id,
                $this->facility->id,
                $this->patient->id,
                [ClinicalRelationshipGate::DISPENSING],
            )->basis,
        );

        // Completing it closes both the header and the bench position.
        $this->desk('pharmacist', 'POST', "/prescriptions/{$rx->id}/dispense", [
            'items' => [['id' => $second->id, 'quantity_dispensed' => 30]],
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'dispensed')
            ->assertJsonPath('data.pharmacy_routing_status', PharmacyDeskService::ROUTING_DISPENSED);

        $rx = $rx->fresh(['items']);
        $this->assertSame('dispensed', $rx->status);
        $this->assertNotNull($rx->dispensed_at);
        $this->assertSame(['dispensed', 'dispensed'], $rx->items->pluck('status')->sort()->values()->all());
    }

    public function test_the_desk_refuses_a_hand_over_of_nothing(): void
    {
        $rx   = $this->desk->routeToFacility($this->prescription([], self::TWO_DRUGS), $this->facility);
        $rx   = $rx->fresh(['items']);
        $none = $rx->items->map(fn ($item) => ['id' => $item->id, 'quantity_dispensed' => 0])->all();

        $this->desk('pharmacist', 'POST', "/prescriptions/{$rx->id}/dispense", ['items' => $none])
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'not_dispensable');

        $rx = $rx->fresh(['items']);
        $this->assertSame('active', $rx->status, 'A refused hand-over leaves the header exactly as it was.');
        $this->assertSame(['pending', 'pending'], $rx->items->pluck('status')->all());
    }

    // ── The repair of rows written before the header was derived ──────────

    public function test_the_repair_migration_recomputes_a_header_that_disagrees_with_its_lines(): void
    {
        // The shape the demo data is in: one drug collected, one still pending,
        // header stuck at 'active' because the old writer never wrote partial.
        $stale = $this->prescription([], self::TWO_DRUGS);
        $line  = $stale->items->first();
        $line->status       = 'dispensed';
        $line->dispensed_at = now()->subDay();
        $line->save();

        // A header that already agrees with its lines, to prove the repair
        // leaves it alone.
        $sound = $this->prescription([], self::TWO_DRUGS);

        // A voided script with one line handed over: a clinical decision the
        // lines do not get to overrule.
        $voided = $this->prescription([], self::TWO_DRUGS);
        $voidedLine = $voided->items->first();
        $voidedLine->status = 'dispensed';
        $voidedLine->save();
        $voided->forceFill(['status' => 'voided'])->saveQuietly();

        $migration = require database_path(
            'migrations/2026_09_08_120000_repair_prescription_header_status_from_items.php'
        );
        $migration->up();

        $this->assertSame('partially_dispensed', $stale->fresh()->status);
        $this->assertSame('active', $sound->fresh()->status);
        $this->assertSame('voided', $voided->fresh()->status);

        // Idempotent: a second run finds nothing left to disagree with.
        $migration->up();
        $this->assertSame('partially_dispensed', $stale->fresh()->status);
        $this->assertSame('active', $sound->fresh()->status);
    }

    public function test_the_repair_completes_a_header_whose_every_fillable_line_is_closed(): void
    {
        $rx = $this->prescription([], self::TWO_DRUGS);

        $done = $rx->items->first();
        $done->status       = 'dispensed';
        $done->dispensed_at = now()->subHours(2);
        $done->save();

        $cancelled = $rx->items->last();
        $cancelled->status = 'cancelled';
        $cancelled->save();

        $migration = require database_path(
            'migrations/2026_09_08_120000_repair_prescription_header_status_from_items.php'
        );
        $migration->up();

        $rx = $rx->fresh();

        $this->assertSame('dispensed', $rx->status, 'The only outstanding line was cancelled — nothing is owed.');
        $this->assertNotNull($rx->dispensed_at, 'Taken from the line that was actually handed over.');
        $this->assertSame(
            $done->dispensed_at->toDateTimeString(),
            $rx->dispensed_at->toDateTimeString(),
            'The repair records when the pharmacy handed over, not when the migration ran.',
        );
    }

    // ── Nothing that reads this status loses sight of the script ──────────

    public function test_a_partially_dispensed_script_is_still_a_live_prescription_everywhere(): void
    {
        $rx = $this->prescription([], self::TWO_DRUGS);
        $this->service()->dispense($rx, $this->users['pharmacist']->id, ['item_ids' => [$rx->items->first()->id]]);

        $this->assertSame('partially_dispensed', $rx->fresh()->status);

        // Every "still owes the patient medicine" reader keys on OPEN_STATUSES.
        $this->assertContains('partially_dispensed', Prescription::OPEN_STATUSES);
        $this->assertNotContains('partially_dispensed', Prescription::TERMINAL_STATUSES);

        $live = Prescription::where('patient_id', $this->patient->id)
            ->whereIn('status', Prescription::OPEN_STATUSES)
            ->pluck('id')
            ->all();

        $this->assertContains($rx->id, $live);
        $this->assertTrue($rx->fresh()->isDispensable(), 'The remaining drug can still be collected.');
    }
}
