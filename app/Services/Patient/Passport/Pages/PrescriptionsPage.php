<?php

namespace App\Services\Patient\Passport\Pages;

use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Services\Patient\Passport\PageSource;
use Illuminate\Support\Collection;

/**
 * Page 15 — Prescriptions.
 *
 * WHAT THIS PAGE IS, AND WHY IT IS NOT PAGE 06. Page 06 answers "what is this
 * patient taking now?" and its row is a drug line: name, dose, route,
 * frequency. This page answers a different question — "what has been prescribed
 * to this patient, by whom, and what became of each script?" — and its row is
 * the prescription itself. A prescription is a legal instrument: it is issued
 * by a named clinician on a named day, it carries a quotable reference, it
 * authorises a pharmacy to hand something over, and that authorisation lapses.
 * Those are properties of the document, not of the drug, so they belong here.
 *
 * The two pages must never disagree. They read the same table and share one
 * test for a lapsed authorisation (the same one Prescription::isDispensable()
 * applies at the counter): a script whose expiry has passed is no longer a
 * standing order, whatever its status column still says. Page 06 leaves such a
 * script off the current list; this page keeps it — it happened — but refuses
 * to paint it with the green of a valid order.
 *
 * NOTHING IS DROPPED. Voided, entered-in-error, amended and cancelled scripts
 * stay on this page, each with its own status. A prescription register that
 * silently deletes the script it corrected is not a register: the corrected
 * script may already be in a patient's hand or in a pharmacy's day-book, and
 * the only way to establish that it must not be filled is to find it here and
 * read that it was withdrawn. Page 06 excludes them for the opposite and
 * equally correct reason — a voided order is not evidence anyone took anything.
 *
 * WHAT THE NAME IN THE MEDICATION COLUMN IS. The drug as it was written on the
 * script (`prescription_items.drug_name`), never the catalogue's current name
 * for the same medicine. This page reproduces an issued document, and a
 * document does not change its wording because a catalogue was later renamed.
 *
 * WHAT THE APPROVED SHEET ASKS FOR AND THIS DEPLOYMENT CANNOT SUPPLY. The
 * medication sheet gives each line an INDICATION and the page an ADHERENCE
 * tile with review dates. Nothing records any of them: a prescription line is
 * not linked to the diagnosis it treats, so pairing a script with a condition
 * off the problem list would print a guessed reason beside a real medicine.
 * Those columns and tiles are left out rather than invented.
 */
class PrescriptionsPage extends PageSource
{
    /**
     * How many scripts the register shows. Deliberately deeper than the
     * eight-row "recent" window every other page keeps to: a prescription
     * register that stops at eight hides a script the patient was told they
     * had, and the question asked of this page ("was I ever prescribed this?")
     * is usually asked about an older one. Still bounded, because the passport
     * is read on a phone.
     */
    private const SCRIPT_WINDOW = 24;

    /** Statuses in which the order still stands and medicine may still be collected. */
    private const STILL_STANDING = ['active', 'partially_dispensed'];

    public function build(Patient $patient): array
    {
        $scripts = Prescription::with([
            'items' => fn ($q) => $q->orderBy('created_at')->orderBy('drug_name'),
            'prescriber',
        ])
            ->where('patient_id', $patient->id)
            ->orderByDesc('prescribed_at')
            ->get();

        if ($scripts->isEmpty()) {
            return [];
        }

        return [
            $this->glance($scripts),
            $this->register($scripts),
        ];
    }

    /**
     * The counted tiles the sheet opens on.
     *
     * The counts are of scripts, not of drug lines — this page counts
     * documents. "Active" is the number a pharmacy would still fill today:
     * standing status AND unlapsed authorisation, which is why it can be lower
     * than the number of rows whose status column reads active.
     *
     * @param  Collection<int,Prescription>  $scripts
     */
    private function glance(Collection $scripts): array
    {
        $standing = $scripts
            ->filter(fn (Prescription $rx) => in_array($this->word($rx->status), self::STILL_STANDING, true))
            ->reject(fn (Prescription $rx) => $this->hasLapsed($rx))
            ->count();

        $dispensed = $scripts
            ->filter(fn (Prescription $rx) => $this->word($rx->status) === 'dispensed')
            ->count();

        return $this->stats(
            __('passport.sections.summary'),
            [
                ['value' => $scripts->count(), 'label' => __('passport.sections.prescriptions')],
                ['value' => $standing,         'label' => __('passport.status_active')],
                ['value' => $dispensed,        'label' => __('passport.status_dispensed')],
            ],
            'blue',
            'clipboard'
        );
    }

    /**
     * The register itself: newest script first, one row per prescription.
     *
     * @param  Collection<int,Prescription>  $scripts
     */
    private function register(Collection $scripts): array
    {
        $rows = $scripts
            ->take(self::SCRIPT_WINDOW)
            ->map(fn (Prescription $rx) => [
                $this->cell((string) $rx->reference),
                $this->cell($this->medicines($rx)),
                $this->cell($this->date($rx->prescribed_at)),
                $this->cell((string) ($rx->prescriber?->name ?? '')),
                $this->cell($this->date($rx->expires_at)),
                $this->cell($this->statusLabel((string) $rx->status), $this->tone($rx)),
            ])
            ->values()
            ->all();

        return $this->table(
            __('passport.sections.prescriptions'),
            [
                __('passport.columns.reference'),
                __('passport.columns.medication'),
                __('passport.columns.prescribed'),
                __('passport.columns.prescriber'),
                __('passport.columns.expires'),
                __('passport.columns.status'),
            ],
            $rows,
            'blue',
            'document-text'
        );
    }

    /**
     * What was written on the script, in the order the lines were written.
     *
     * Every line is named, including one a pharmacy later cancelled: the
     * question this column answers is what the prescriber ordered, and that
     * does not change afterwards. What happened to each line is dispensing,
     * and dispensing has its own page.
     */
    private function medicines(Prescription $rx): string
    {
        return $rx->items
            ->map(fn (PrescriptionItem $line) => trim((string) $line->drug_name))
            ->filter(fn (string $name) => $name !== '')
            ->implode(', ');
    }

    /**
     * The pill colour for a script's status.
     *
     * Two additions to the shared reading of tone, both taken from the
     * Prescription model's own statusColor() so that a script is not one colour
     * in the portal and another in the passport:
     *
     *  - voided and entered-in-error are red. They are the strongest thing this
     *    page can say — do not fill this, it was withdrawn — and a reader who
     *    skims only the colours must not skim past that.
     *  - amended is amber: the order was superseded, so it still stands as
     *    history but not as an instruction.
     *
     * A standing script whose authorisation has lapsed gets no colour of
     * validity. The record still says "active" so the page still says "active",
     * but green would tell a pharmacist it may be filled, and the expiry date
     * in the column beside it says otherwise.
     */
    private function tone(Prescription $rx): ?string
    {
        $status = $this->word($rx->status);

        if (in_array($status, self::STILL_STANDING, true) && $this->hasLapsed($rx)) {
            return 'neutral';
        }

        return $this->statusTone($status) ?? match ($status) {
            'voided', 'entered_in_error' => 'danger',
            'amended'                    => 'warning',
            default                      => null,
        };
    }

    /** Has the authorisation to dispense run out? */
    private function hasLapsed(Prescription $rx): bool
    {
        return (bool) ($rx->expires_at && $rx->expires_at->isPast());
    }

    /** A recorded word, lowered and trimmed, for comparison against a list. */
    private function word($value): string
    {
        return strtolower(trim((string) $value));
    }
}
