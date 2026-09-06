<?php

namespace App\Services\Patient\Passport\Pages;

use App\Models\Patient;
use App\Models\PharmacyRoute;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Services\Patient\Passport\PageSource;

/**
 * Page 16 — Medication Dispensing.
 *
 * WHAT THIS PAGE IS FOR. Page 6 answers "what is this patient meant to be
 * taking". This one answers a different question a pharmacist asks at a
 * counter: what has actually been handed over to them, when, and by whom. The
 * two must never contradict each other, so the same rules about which orders
 * ever stood are applied here as there.
 *
 * WHY IT READS THE ITEMS AND NOT THE PRESCRIPTION HEADER. A dispense is
 * recorded twice in this schema: on `prescriptions` (`status`, `dispensed_at`,
 * `dispensed_by`) and, line by line, on `prescription_items` (`status`,
 * `dispensed_at`, `dispense_notes`). Only the line-level record can tell the
 * truth about a partly collected script. The flat list this page replaces read
 * `prescriptions.dispensed_at` alone, so a script whose three drugs included
 * two the pharmacy had in stock showed as nothing at all — the two collected
 * boxes were invisible, because the header was still `active`. Every row below
 * is one drug line that the record says was handed over.
 *
 * AND WHY IT ALSO SHOWS WHAT WAS NOT COLLECTED. A page that lists only
 * hand-overs invites the reader to assume the rest of the script came with
 * them. The record distinguishes a collected line from an uncollected one, so
 * the page does too: the second block is the lines still waiting at a counter,
 * on scripts a pharmacy could still dispense against — `isDispensable()`, the
 * same test applied at the counter itself, so an expired or terminal script is
 * not offered as collectable.
 *
 * WHAT THE RECORD CANNOT SUPPLY HERE.
 *
 * - THE QUANTITY ACTUALLY HANDED OVER. `prescription_items.quantity` is the
 *   quantity ORDERED, and there is no `quantity_dispensed` column anywhere. A
 *   pharmacist who hands over fewer than the ordered number records it through
 *   PharmacyDeskService::dispense(), which stringifies it into the line's
 *   `dispense_notes` ("Quantity dispensed: 10") — free text, in whichever
 *   language the counter was running. Parsing a translated sentence back into
 *   a number to fill a column would be a guess dressed as a fact, so the
 *   Quantity column shows the ordered amount and the counter's own words are
 *   shown verbatim beside it: the Notes column appears exactly when some line
 *   carries a note, and is absent when none does.
 *
 * - THE DISPENSING PHARMACIST BY NAME. `prescriptions.dispensed_by` is a user
 *   id, and naming a person from it is a join this page does not need; the
 *   counter is what the reader wants and that is the Pharmacy column.
 *
 * The Pharmacy column stays even when nothing fills it. Where a script was
 * dispensed through the pharmacy desk, `routeToFacility()` stamps the counter's
 * own name on `dispensing_pharmacy_name` before the hand-over is recorded, so
 * the column is the honest "where". Where a dispense was recorded straight
 * through PrescriptionService — an in-facility hand-over, a back-office
 * correction — nothing names a pharmacy, and the reader is told that rather
 * than left to assume the prescribing facility dispensed it.
 */
class DispensingPage extends PageSource
{
    /**
     * Orders that never stood. Read exactly as CurrentMedicationsPage reads
     * them, because the two pages describe the same events and a line that is
     * medication history on one must not be a hand-over on the other. Voiding
     * a script does not rewrite its items, so a disavowed order can still carry
     * a line stamped `dispensed`; the record's own correction wins.
     */
    private const NEVER_STOOD = ['cancelled', 'voided', 'entered_in_error', 'amended'];

    public function build(Patient $patient): array
    {
        $prescriptions = Prescription::with(['items' => fn ($q) => $q->orderBy('created_at')->orderBy('drug_name')])
            ->where('patient_id', $patient->id)
            ->orderByDesc('prescribed_at')
            ->get()
            ->reject(fn (Prescription $rx) => in_array($this->word($rx->status), self::NEVER_STOOD, true));

        if ($prescriptions->isEmpty()) {
            return [];
        }

        $sections = [];

        if (($handovers = $this->handovers($prescriptions)) !== []) {
            $sections[] = $this->dispensedTable($handovers);
        }

        if (($waiting = $this->waitingRows($prescriptions)) !== []) {
            $sections[] = $this->table(
                __('passport.sections.prescriptions'),
                [
                    __('passport.columns.medication'),
                    __('passport.columns.dose'),
                    __('passport.columns.quantity'),
                    __('passport.columns.prescribed'),
                    __('passport.columns.status'),
                ],
                $waiting,
                'amber',
                'clipboard'
            );
        }

        return $sections;
    }

    // ── What a pharmacy handed over ──────────────────────────────────────

    /**
     * The hand-overs, newest first, kept to the passport's recent window.
     *
     * Sorted on when the medicine actually changed hands — the line's own
     * `dispensed_at` where the counter stamped it, the script's otherwise —
     * and not on when the script was written, which is a different date and on
     * a repeat script can be months earlier.
     *
     * @param  \Illuminate\Support\Collection<int,Prescription>  $prescriptions
     * @return list<array{rx:Prescription,line:PrescriptionItem,at:mixed}>
     */
    private function handovers($prescriptions): array
    {
        $handovers = [];

        foreach ($prescriptions as $rx) {
            foreach ($rx->items as $line) {
                if ($this->word($line->status) !== 'dispensed') {
                    continue;
                }

                $handovers[] = [
                    'rx'   => $rx,
                    'line' => $line,
                    'at'   => $line->dispensed_at ?: $rx->dispensed_at,
                ];
            }
        }

        usort($handovers, fn (array $a, array $b) => $this->sortKey($b['at']) <=> $this->sortKey($a['at']));

        return array_slice($handovers, 0, self::RECENT_LIMIT);
    }

    /**
     * The dispensed ledger. The Notes column is added only when a line carries
     * one, so the counter's own record of a short hand-over is never dropped
     * and an all-empty column is never drawn.
     *
     * @param  list<array{rx:Prescription,line:PrescriptionItem,at:mixed}>  $handovers
     */
    private function dispensedTable(array $handovers): array
    {
        $pharmacies = $this->pharmacyNames(array_map(fn (array $h) => $h['rx'], $handovers));

        $withNotes = array_filter($handovers, fn (array $h) => trim((string) $h['line']->dispense_notes) !== '') !== [];

        $columns = [
            __('passport.columns.medication'),
            __('passport.columns.dose'),
            __('passport.columns.quantity'),
            __('passport.columns.dispensed'),
            __('passport.columns.pharmacy'),
        ];

        if ($withNotes) {
            $columns[] = __('passport.columns.notes');
        }

        $columns[] = __('passport.columns.status');

        $rows = [];

        foreach ($handovers as $handover) {
            /** @var PrescriptionItem $line */
            $line = $handover['line'];
            /** @var Prescription $rx */
            $rx = $handover['rx'];

            $row = [
                $this->cell((string) $line->drug_name),
                $this->cell((string) $line->dose),
                $this->cell($this->quantity($line)),
                $this->cell($this->date($handover['at'])),
                $this->cell($this->pharmacy($rx, $pharmacies)),
            ];

            if ($withNotes) {
                // A plain string, not cell(): a line with no note has nothing
                // missing from it, and "Not recorded" in every other row of an
                // optional free-text column reads as a gap where there is none.
                $row[] = trim((string) $line->dispense_notes);
            }

            $row[] = $this->cell(
                $this->statusLabel((string) $line->status),
                $this->statusTone((string) $line->status)
            );

            $rows[] = $row;
        }

        return $this->table(
            __('passport.sections.dispensing'),
            $columns,
            $rows,
            // The same accent page 6 gives its dispensed block: the same events
            // must not read as a different kind of fact on a different page.
            'teal',
            'pill'
        );
    }

    // ── What is still waiting at a counter ───────────────────────────────

    /**
     * Lines a pharmacy could still hand over. Never truncated: a medicine the
     * patient has not collected is exactly the row that must not fall off the
     * bottom of the list, and this block is short by its nature.
     *
     * @param  \Illuminate\Support\Collection<int,Prescription>  $prescriptions
     * @return list<list<array<string,string>>>
     */
    private function waitingRows($prescriptions): array
    {
        $rows = [];

        foreach ($prescriptions as $rx) {
            // The counter's own test: terminal or past its expiry means there
            // is nothing left to collect, whatever the lines still say.
            if (! $rx->isDispensable()) {
                continue;
            }

            foreach ($rx->items as $line) {
                $status = $this->word($line->status);

                if ($status === 'dispensed' || $status === 'cancelled') {
                    continue;
                }

                $rows[] = [
                    $this->cell((string) $line->drug_name),
                    $this->cell((string) $line->dose),
                    $this->cell($this->quantity($line)),
                    $this->cell($this->date($rx->prescribed_at)),
                    $this->cell($this->statusLabel((string) $line->status), $this->statusTone((string) $line->status)),
                ];
            }
        }

        return $rows;
    }

    // ── Reading the record ───────────────────────────────────────────────

    /**
     * Where it was handed over.
     *
     * `dispensing_pharmacy_name` is written by the pharmacy desk when a counter
     * first claims a script, so on the desk path it names the counter that
     * dispensed. Where only the route is set, the route's pharmacy is the same
     * destination under its configured name. Where neither is set, nothing in
     * the record names a pharmacy and the cell says so — the prescribing
     * facility is not an answer to this question.
     *
     * @param  array<string,string>  $pharmacies  route id => pharmacy name
     */
    private function pharmacy(Prescription $rx, array $pharmacies): string
    {
        $named = trim((string) $rx->dispensing_pharmacy_name);

        if ($named !== '') {
            return $named;
        }

        $routeId = (string) $rx->pharmacy_route_id;

        return $routeId !== '' ? trim((string) ($pharmacies[$routeId] ?? '')) : '';
    }

    /**
     * The routed pharmacies for these scripts, read in one query rather than
     * one per row.
     *
     * @param  list<Prescription>  $prescriptions
     * @return array<string,string>
     */
    private function pharmacyNames(array $prescriptions): array
    {
        $ids = array_values(array_unique(array_filter(array_map(
            fn (Prescription $rx) => (string) $rx->pharmacy_route_id,
            $prescriptions
        ), fn (string $id) => $id !== '')));

        if ($ids === []) {
            return [];
        }

        return PharmacyRoute::whereIn('id', $ids)
            ->pluck('pharmacy_name', 'id')
            ->map(fn ($name) => (string) $name)
            ->all();
    }

    /**
     * The quantity on the line — the amount ORDERED, which is what this schema
     * holds. A zero is a recorded number and is shown; a null is not a zero and
     * is left to read as not recorded.
     */
    private function quantity(PrescriptionItem $line): string
    {
        return $line->quantity === null ? '' : (string) $line->quantity;
    }

    /** A sortable instant for a date that may be null or unparseable. */
    private function sortKey($value): int
    {
        if (! $value) {
            return 0;
        }

        try {
            return \Carbon\Carbon::parse($value)->getTimestamp();
        } catch (\Throwable) {
            return 0;
        }
    }

    /** A recorded word, lowered and trimmed, for comparison against a list. */
    private function word($value): string
    {
        return strtolower(trim((string) $value));
    }
}
