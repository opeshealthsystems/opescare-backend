<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Recompute `prescriptions.status` from the lines that actually record a
 * hand-over.
 *
 * `prescription_items.status` (pending|dispensed|cancelled) has always recorded
 * the hand-over one drug at a time. The header, though, was hard-set to
 * 'dispensed' by PrescriptionService::dispense() on every call, so
 * `partially_dispensed` — a status the table has documented since it was
 * created, and which six readers check for (the pharmacy desk queue, the
 * dispensing basis of ClinicalRelationshipGate, the pharmacy portal, and three
 * Health Passport pages) — was never written by anything. A script with one
 * drug collected and one still on the shelf therefore sat at 'active', and the
 * header contradicted its own lines.
 *
 * The writers now derive the header (Prescription::dispensingStatusFromItems).
 * This repairs the rows they already wrote.
 *
 * What it will and will not touch:
 *
 *  - Only headers at 'active' or 'partially_dispensed' — the two states that
 *    still owe the patient medicine. A terminal header ('dispensed', 'voided',
 *    'cancelled', 'entered_in_error', 'amended') is a clinical decision that
 *    the lines do not get to overrule: a voided script with one line already
 *    handed over stays voided. 'expired' is a fact about time, not dispensing,
 *    and is left alone too.
 *  - Only rows where the header actually DISAGREES with the lines. A row that
 *    already agrees is not written, so a second run changes nothing.
 *  - Cancelled / voided / entered-in-error lines are not outstanding, so a
 *    script whose only remaining line was cancelled repairs to 'dispensed',
 *    not 'partially_dispensed'.
 *  - A row with no dispensed line at all is not touched: nothing has been
 *    handed over, so 'active' is already the truth.
 *
 * Where a row is completed to 'dispensed' and has no `dispensed_at`, the
 * timestamp is taken from the last line handed over — the moment the pharmacy
 * actually recorded — rather than invented as "now".
 *
 * Deliberately written with the query builder: Prescription's model guards
 * refuse a status change out of a terminal state and refuse deletes, which is
 * exactly right for application code and exactly wrong for a documented repair
 * of rows those guards let through in the first place. Nothing here moves a row
 * OUT of a terminal state.
 */
return new class extends Migration
{
    /** `prescription_items.status` values that close a line without filling it. */
    private const CLOSED_ITEM_STATUSES = ['cancelled', 'voided', 'entered_in_error'];

    /** `prescriptions.status` values this repair is allowed to rewrite. */
    private const REPAIRABLE = ['active', 'partially_dispensed'];

    public function up(): void
    {
        if (! Schema::hasTable('prescriptions') || ! Schema::hasTable('prescription_items')) {
            return;
        }

        $repaired = ['partially_dispensed' => 0, 'dispensed' => 0];

        DB::table('prescriptions')
            ->whereIn('status', self::REPAIRABLE)
            ->select('id', 'status', 'dispensed_at')
            ->orderBy('id')
            ->chunk(500, function ($headers) use (&$repaired) {
                $lines = DB::table('prescription_items')
                    ->whereIn('prescription_id', $headers->pluck('id')->all())
                    ->select('prescription_id', 'status', 'dispensed_at')
                    ->get()
                    ->groupBy('prescription_id');

                foreach ($headers as $header) {
                    $own = $lines->get($header->id);

                    if ($own === null) {
                        continue; // a header with no lines says nothing about dispensing
                    }

                    $fillable = $own->reject(
                        fn ($line) => in_array((string) $line->status, self::CLOSED_ITEM_STATUSES, true)
                    );
                    $handedOver = $fillable->where('status', 'dispensed');

                    // Nothing left to fill, or nothing handed over yet: the
                    // lines do not contradict the header, so leave it alone.
                    if ($fillable->isEmpty() || $handedOver->isEmpty()) {
                        continue;
                    }

                    $target = $handedOver->count() === $fillable->count()
                        ? 'dispensed'
                        : 'partially_dispensed';

                    if ($target === $header->status) {
                        continue; // already agrees — this is what makes a re-run a no-op
                    }

                    $update = ['status' => $target, 'updated_at' => now()];

                    if ($target === 'dispensed' && $header->dispensed_at === null) {
                        $lastHandover = $handedOver->pluck('dispensed_at')->filter()->max();

                        if ($lastHandover !== null) {
                            $update['dispensed_at'] = $lastHandover;
                        }
                    }

                    DB::table('prescriptions')->where('id', $header->id)->update($update);

                    $repaired[$target]++;
                }
            });

        $total = $repaired['partially_dispensed'] + $repaired['dispensed'];

        if ($total > 0) {
            echo "repaired {$total} prescription header(s) from their lines: "
                . "{$repaired['partially_dispensed']} → partially_dispensed, "
                . "{$repaired['dispensed']} → dispensed\n";
        }
    }

    public function down(): void
    {
        // The only thing to undo here is a header that agrees with its own
        // lines. There is no reason to want that back.
    }
};
