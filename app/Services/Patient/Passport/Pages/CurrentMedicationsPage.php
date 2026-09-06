<?php

namespace App\Services\Patient\Passport\Pages;

use App\Models\AllergyRecord;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Services\Patient\Passport\PageSource;

/**
 * Page 06 — Current Medications.
 *
 * WHAT THIS PAGE HAS TO GET RIGHT. A medication list is read by someone about
 * to prescribe. Two mistakes are dangerous in opposite directions: leaving out
 * a drug the patient is taking, and listing one they are not. So the page reads
 * each prescription line as the line itself was written — drug, dose, route,
 * frequency — instead of flattening it into a sentence, and it is explicit
 * about which prescriptions count as current.
 *
 * WHAT COUNTS AS CURRENT. A prescription is a standing order to take something:
 * `active` means it was issued and not yet fully collected, `partially_dispensed`
 * means some of it has been. Both are medicines the patient is on. The other
 * statuses are not: `dispensed` is a finished, fully-collected script (it lives
 * in the history block below), and `cancelled`, `voided`, `entered_in_error` and
 * `amended` are orders that never stood at all.
 *
 * Status alone is not enough. A script carries `expires_at`, and an `active` row
 * whose expiry has passed is a lapsed authorisation, not a current medicine —
 * the same test Prescription::isDispensable() applies at the pharmacy counter.
 * The demo data contains exactly that row, and the flat list this page replaces
 * showed it as a current medication.
 *
 * WHAT THE APPROVED SHEET ASKS FOR AND THIS DEPLOYMENT CANNOT SUPPLY. The sheet
 * gives each line an INDICATION, and gives the page an ADHERENCE tile, a LAST /
 * NEXT REVIEW date and an adherence note. Nothing records any of them here.
 * Indication in particular is not derivable: a prescription line is not linked
 * to the diagnosis it treats, so pairing a drug with a condition off the problem
 * list would put a guessed reason next to a real medicine. Those columns and
 * tiles are left out rather than invented.
 */
class CurrentMedicationsPage extends PageSource
{
    /** Statuses under which the patient still has medicine to take. */
    private const STILL_TO_TAKE = ['active', 'partially_dispensed'];

    /**
     * Statuses meaning the order never stood. These are excluded from the
     * history block as well: a voided script is a record of a correction, not
     * evidence that anybody took anything.
     */
    private const NEVER_STOOD = ['cancelled', 'voided', 'entered_in_error', 'amended'];

    /** Allergy rows the record itself has withdrawn. */
    private const WITHDRAWN_ALLERGY = ['inactive', 'entered-in-error', 'entered_in_error', 'refuted', 'resolved'];

    public function build(Patient $patient): array
    {
        $prescriptions = Prescription::with(['items' => fn ($q) => $q->orderBy('created_at')->orderBy('drug_name')])
            ->where('patient_id', $patient->id)
            ->orderByDesc('prescribed_at')
            ->get();

        if ($prescriptions->isEmpty()) {
            return [];
        }

        $current = $prescriptions->filter(fn (Prescription $rx) => $this->isCurrent($rx));

        $past = $prescriptions
            ->reject(fn (Prescription $rx) => $this->isCurrent($rx))
            ->reject(fn (Prescription $rx) => in_array($this->word($rx->status), self::NEVER_STOOD, true));

        $currentRows = $this->currentRows($current);
        $pastRows    = $this->pastRows($past);

        if ($currentRows === [] && $pastRows === []) {
            return [];
        }

        $sections = [];

        // The sheet opens on four tiles — total medications, adherence, last
        // review, next review. Only the count has a source here.
        $sections[] = $this->stats(
            __('passport.sections.summary'),
            [['value' => count($currentRows), 'label' => __('passport.stats.medicines')]],
            'green',
            'pill'
        );

        if ($currentRows !== []) {
            $sections[] = $this->table(
                __('passport.sections.medications'),
                [
                    __('passport.columns.medication'),
                    __('passport.columns.dose'),
                    // No passport.columns.route exists yet; prescriber.route is
                    // the same word and is already 1:1 in EN/FR.
                    __('prescriber.route'),
                    __('passport.columns.frequency'),
                    __('passport.columns.prescribed'),
                    __('passport.columns.status'),
                ],
                $currentRows,
                'blue',
                'pill'
            );
        }

        if ($pastRows !== []) {
            $sections[] = $this->table(
                __('passport.sections.dispensing'),
                [
                    __('passport.columns.medication'),
                    __('passport.columns.dose'),
                    __('passport.columns.dispensed'),
                    __('passport.columns.status'),
                ],
                $pastRows,
                'teal',
                'pill'
            );
        }

        $allergyRows = $this->allergyRows($patient);

        if ($allergyRows !== []) {
            $sections[] = $this->table(
                __('passport.sections.allergies'),
                [__('passport.columns.allergen'), __('passport.columns.severity')],
                $allergyRows,
                'red',
                'alert-triangle'
            );
        }

        return $sections;
    }

    /**
     * Is this a medicine the patient is on right now?
     *
     * Both halves matter. The status says the order still stands; the expiry
     * says the authorisation has not lapsed. Either one alone would put a drug
     * on the list that the patient is no longer meant to be taking.
     */
    private function isCurrent(Prescription $rx): bool
    {
        if (! in_array($this->word($rx->status), self::STILL_TO_TAKE, true)) {
            return false;
        }

        return ! ($rx->expires_at && $rx->expires_at->isPast());
    }

    /**
     * The current list is never truncated. Every other page here keeps to a
     * recent window, but a medication list that stops at eight rows hides the
     * ninth drug from the person deciding what to prescribe next.
     *
     * @param  \Illuminate\Support\Collection<int,Prescription>  $prescriptions
     * @return list<list<array<string,string>>>
     */
    private function currentRows($prescriptions): array
    {
        $rows = [];

        foreach ($prescriptions as $rx) {
            foreach ($rx->items as $line) {
                // A cancelled line on a standing prescription is not a current
                // medicine, even though its parent still is.
                if ($this->word($line->status) === 'cancelled') {
                    continue;
                }

                $rows[] = [
                    $this->cell((string) $line->drug_name),
                    $this->cell((string) $line->dose),
                    $this->cell($this->route($line)),
                    $this->cell((string) $line->frequency),
                    $this->cell($this->date($rx->prescribed_at)),
                    $this->cell($this->statusLabel((string) $rx->status), $this->statusTone((string) $rx->status)),
                ];
            }
        }

        return $rows;
    }

    /**
     * Medicines the patient has finished with — and only ones a pharmacy
     * actually handed over. A line that was prescribed but never dispensed is
     * not something the patient took, so it is not medication history.
     *
     * @param  \Illuminate\Support\Collection<int,Prescription>  $prescriptions
     * @return list<list<array<string,string>>>
     */
    private function pastRows($prescriptions): array
    {
        $rows = [];

        foreach ($prescriptions as $rx) {
            foreach ($rx->items as $line) {
                if ($this->word($line->status) !== 'dispensed') {
                    continue;
                }

                $rows[] = [
                    $this->cell((string) $line->drug_name),
                    $this->cell((string) $line->dose),
                    $this->cell($this->date($line->dispensed_at ?: $rx->dispensed_at)),
                    $this->cell($this->statusLabel((string) $line->status), $this->statusTone((string) $line->status)),
                ];

                if (count($rows) >= self::RECENT_LIMIT) {
                    return $rows;
                }
            }
        }

        return $rows;
    }

    /**
     * The sheet repeats known allergies beside the medication list, because
     * that is where they are needed. Rows the record has withdrawn are left
     * out — an entry marked entered-in-error is the record saying it was never
     * true, not a warning to repeat.
     *
     * @return list<list<array<string,string>>>
     */
    private function allergyRows(Patient $patient): array
    {
        return AllergyRecord::where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->limit(self::RECENT_LIMIT)
            ->get()
            ->reject(fn (AllergyRecord $a) => in_array($this->word($a->status), self::WITHDRAWN_ALLERGY, true))
            ->map(fn (AllergyRecord $a) => [
                $this->cell((string) $a->substance),
                $this->cell($this->statusLabel((string) $a->severity), $this->severityTone((string) $a->severity)),
            ])
            ->values()
            ->all();
    }

    /**
     * Route is recorded free-text and arrives in whatever case the prescriber
     * typed — "oral" from one desk, "Oral" from another. Sentence-casing an
     * all-lowercase value tidies that without touching "IV", "IM" or "PO",
     * which are abbreviations and must keep their capitals.
     */
    private function route(PrescriptionItem $line): string
    {
        $route = trim((string) $line->route);

        return preg_match('/^[a-z]+$/', $route) === 1 ? ucfirst($route) : $route;
    }

    /** A recorded word, lowered and trimmed, for comparison against a list. */
    private function word($value): string
    {
        return strtolower(trim((string) $value));
    }
}
