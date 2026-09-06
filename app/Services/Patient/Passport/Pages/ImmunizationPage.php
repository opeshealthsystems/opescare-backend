<?php

namespace App\Services\Patient\Passport\Pages;

use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\ImmunizationRecord;
use App\Models\Patient;
use App\Services\Patient\Passport\PageSource;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Page 14 — Immunisation Record: every dose, and what it adds up to.
 *
 * This is the page people produce most often and to the widest audience — a
 * school, a border post, an employer, a clinic starting a course — and it is
 * the only page whose reader is frequently not a clinician. It therefore
 * carries both readings of the same record, in the order they are needed:
 *
 *  1. IMMUNISATION STATUS — one line per vaccine, the way the approved sheet
 *     sets it (Vaccine · Status · Last dose, with the green "Completed" pill).
 *     This is what someone at a counter is looking for: am I covered, and when
 *     was the last one.
 *  2. VACCINES GIVEN — every dose as its own row, oldest first, the way a
 *     yellow card or a child health card reads.
 *
 * ── WHY BOTH, AND NOT JUST THE GROUPED VIEW THE SHEET SHOWS ───────────────
 *
 * The sheet's block is on page 04, where immunisation is one domain among six
 * and a summary is all that fits. Page 14 is the record itself, and grouping
 * alone would destroy the two things that make a vaccination record usable:
 * a course is three doses on three dates, and "Pentavalent · Completed · 14 Feb
 * 2014" cannot tell a clinician whether doses one and two were given, when, or
 * whether the intervals were right. The record holds a row per dose; the page
 * prints a row per dose. The grouped block sits above it as the summary, built
 * from the same rows so the two can never disagree.
 *
 * ── THERE IS NO "NEXT DUE" COLUMN, AND THAT IS DELIBERATE ─────────────────
 *
 * The sheet's fourth column is Next due. `immunization_records` holds no such
 * column, and the one table that could carry it — `vaccination_schedules`, with
 * its `due_date` — is empty in this deployment and has no writer. A next-due
 * date could therefore only be computed here from an EPI schedule this codebase
 * does not hold: a clinical recommendation composed by software, printed on a
 * document a parent would act on. A missed appointment is the cost of getting
 * that wrong, so the column is left out entirely rather than filled.
 *
 * ── WHAT ELSE THE RECORD HOLDS THAT THIS PAGE DOES NOT PRINT ──────────────
 *
 *  • FACILITY IS SHOWN ONLY FOR A DOSE THIS PLATFORM RECORDED. `facility_id` is
 *    NOT NULL, so a dose imported from a paper card or reported by the patient
 *    (`is_historical`) still carries one — the facility that keyed it in, which
 *    is not necessarily the facility that gave the injection. On a document
 *    presented as proof, naming the wrong place is a false statement, so a
 *    historical row leaves that cell blank: the administering facility genuinely
 *    is not recorded. Where no row on the page can name one, the column goes.
 *  • LOT NUMBER, MANUFACTURER, ROUTE, SITE and VERIFICATION STATUS are all
 *    recorded and all omitted, for want of an unambiguous column heading in the
 *    shared passport vocabulary ("Number" beside a "Dose" column reads as the
 *    dose number) rather than for want of a source. They are named in the
 *    handover so the vocabulary can be extended deliberately.
 *  • ADVERSE REACTIONS — `adverse_reaction_notes` exists as a table and a model,
 *    but nothing in the platform writes to it and it holds no rows, and there is
 *    no section heading for it. A reaction block would always be empty here.
 *
 * ── WHICH ROWS ARE COUNTED AS COVER ───────────────────────────────────────
 *
 * A dose marked entered-in-error is a withdrawal. It is dropped from the STATUS
 * block, which is read as "what protection stands" — page 04 drops retracted
 * entries for the same reason — but it is kept in the full history, where the
 * recorded word is printed in the Status column beside it. A reader given the
 * shorter list believes it complete and cannot see that the entry was made and
 * set aside; that is the treatment page 07 settled on and this page follows it.
 * A dose recorded as not done is not a withdrawal but a recorded decision, so it
 * appears in both, and it never counts as the last dose given.
 */
class ImmunizationPage extends PageSource
{
    /** Statuses that withdraw the entry: recorded, then taken back. */
    private const RETRACTED = ['entered_in_error', 'entered-in-error', 'error', 'voided', 'retracted'];

    /** Statuses where no vaccine went in, whatever the row's date says. */
    private const NOT_GIVEN = ['not_done', 'not-done', 'notdone', 'refused', 'declined', 'contraindicated'];

    /**
     * How many doses the page reads.
     *
     * Deliberately far beyond any real immunisation history — a full EPI
     * childhood schedule with a lifetime of boosters and travel vaccines runs
     * to perhaps forty rows. The limit exists to stop a corrupted record
     * building a page of ten thousand rows, not to summarise: this is the page
     * that is supposed to be complete.
     */
    private const WINDOW = 200;

    public function build(Patient $patient): array
    {
        $doses = $this->chronological(
            ImmunizationRecord::where('patient_id', $patient->id)
                ->orderByDesc('administered_at')
                ->orderBy('vaccine_name')
                ->limit(self::WINDOW)
                ->get()
        );

        if ($doses->isEmpty()) {
            return [];
        }

        $sections = [];
        $standing = $doses->reject(fn (ImmunizationRecord $i) => $this->isRetracted($i))->values();

        if ($standing->isNotEmpty()) {
            $sections[] = $this->statusBlock($standing);
        }

        $sections[] = $this->historyBlock($doses);

        return $sections;
    }

    // ── The blocks ───────────────────────────────────────────────────────

    /**
     * The sheet's block: one line per vaccine, its standing, and the date of
     * the last dose actually given.
     *
     * Grouping is by VACCINE CODE rather than by product name, because the same
     * vaccine arrives under whatever brand was in the fridge that day — a
     * patient whose two COVID-19 doses were Janssen and then Comirnaty has had
     * two doses of one vaccine, not one dose each of two. The line is labelled
     * with the most recent product name; the history below names the product on
     * every dose.
     *
     * @param  Collection<int,ImmunizationRecord>  $standing
     */
    private function statusBlock(Collection $standing): array
    {
        $groups = $standing
            ->groupBy(fn (ImmunizationRecord $i) => $this->groupKey($i))
            ->map(fn (Collection $doses) => [
                // The rows arrive oldest first, so the last of a group is the
                // most recent thing recorded about that vaccine.
                'latest' => $doses->last(),
                'given'  => $doses->reject(fn (ImmunizationRecord $i) => $this->isNotGiven($i))->last(),
            ])
            ->sortBy(fn (array $group) => $group['given'] ? $this->sortKey($group['given']) : PHP_INT_MAX)
            ->values();

        return $this->table(
            __('passport.sections.immunisation_status'),
            [
                __('passport.columns.vaccine'),
                __('passport.columns.status'),
                __('passport.columns.last_dose'),
            ],
            $groups->map(fn (array $group) => [
                $this->cell($this->vaccineName($group['latest'])),
                $this->cell(
                    $this->statusLabel($group['latest']->status),
                    $this->statusTone($group['latest']->status)
                ),
                // No dose given means no date to print — never the date of the
                // visit at which it was declined.
                $this->cell($group['given'] ? $this->date($group['given']->administered_at) : ''),
            ])->all(),
            'green',
            'shield-check'
        );
    }

    /**
     * Every dose, oldest first, as a vaccination card is read.
     *
     * @param  Collection<int,ImmunizationRecord>  $doses
     */
    private function historyBlock(Collection $doses): array
    {
        $facilities = $this->facilityNames(
            $doses->reject(fn (ImmunizationRecord $i) => (bool) $i->is_historical)
                ->pluck('facility_id')
                ->all()
        );

        $showsDose     = $doses->contains(fn (ImmunizationRecord $i) => $this->dose($i) !== '');
        $showsFacility = $doses->contains(fn (ImmunizationRecord $i) => $this->facilityName($i, $facilities) !== '');

        $columns = [__('passport.columns.date'), __('passport.columns.vaccine')];

        if ($showsDose) {
            $columns[] = __('passport.columns.dose');
        }

        if ($showsFacility) {
            $columns[] = __('passport.columns.facility');
        }

        // The status column is never dropped, however uniform the record looks:
        // it is the only thing separating a dose that was given from one that
        // was declined or withdrawn.
        $columns[] = __('passport.columns.status');

        $rows = $doses->map(function (ImmunizationRecord $i) use ($facilities, $showsDose, $showsFacility) {
            $row = [
                $this->cell($this->date($i->administered_at)),
                $this->cell($this->vaccineName($i)),
            ];

            if ($showsDose) {
                $row[] = $this->cell($this->dose($i));
            }

            if ($showsFacility) {
                $row[] = $this->cell($this->facilityName($i, $facilities));
            }

            $row[] = $this->cell($this->statusLabel($i->status), $this->statusTone($i->status));

            return $row;
        })->all();

        return $this->table(
            __('passport.sections.immunisations'),
            $columns,
            $rows,
            'green',
            'calendar-check'
        );
    }

    // ── Reading one dose ─────────────────────────────────────────────────

    private function vaccineName(ImmunizationRecord $i): string
    {
        return trim((string) ($i->vaccine_name ?: $i->vaccine_code));
    }

    /**
     * Where this dose sits in its course.
     *
     * Both halves of that are recorded and neither alone is enough. The
     * sequence carries the meaning a number cannot ("Birth", "Booster"); the
     * number carries what a sequence sometimes leaves out — an imported card
     * summarised as one row of "primary series" with dose_number 3 is three
     * doses of hepatitis B, and printing "Primary series" alone would lose the
     * three. So both are shown, unless the sequence already spells the number
     * out ("primary_3"), in which case repeating it says nothing.
     *
     * A dose_number of 0 is the birth dose — EPI's OPV0 — and the sequence
     * ("Birth") states it better than a zero does.
     */
    private function dose(ImmunizationRecord $i): string
    {
        // statusLabel is the shared tidier: it looks for a translation and
        // otherwise prints the recorded word itself, readably — "primary_1"
        // becomes "Primary 1" rather than a raw column value.
        $sequence = trim((string) $i->dose_sequence) !== ''
            ? $this->statusLabel($i->dose_sequence)
            : '';

        $number = $i->dose_number === null ? null : (int) $i->dose_number;

        if ($sequence === '') {
            return $number === null ? '' : (string) $number;
        }

        if ($number === null || $number === 0 || str_contains($sequence, (string) $number)) {
            return $sequence;
        }

        return $number . ' · ' . $sequence;
    }

    /**
     * The facility that gave the dose, or nothing.
     *
     * See the note at the head of this class: for an imported or self-reported
     * dose the recorded facility is the one that entered it, and this page will
     * not put that name under a heading that says where the injection happened.
     *
     * @param  array<string,string>  $facilities
     */
    private function facilityName(ImmunizationRecord $i, array $facilities): string
    {
        if ($i->is_historical) {
            return '';
        }

        return trim((string) ($facilities[(string) $i->facility_id] ?? ''));
    }

    private function isRetracted(ImmunizationRecord $i): bool
    {
        return in_array($this->word($i->status), self::RETRACTED, true);
    }

    private function isNotGiven(ImmunizationRecord $i): bool
    {
        return in_array($this->word($i->status), self::NOT_GIVEN, true);
    }

    /** The vaccine a dose belongs to: its code, or its name where no code was coded. */
    private function groupKey(ImmunizationRecord $i): string
    {
        return $this->word($i->vaccine_code) ?: $this->word($i->vaccine_name);
    }

    private function word(?string $value): string
    {
        return strtolower(trim((string) $value));
    }

    // ── Order ────────────────────────────────────────────────────────────

    /**
     * Oldest first — the order a vaccination card, a child health card and the
     * approved sheet's own immunisation block all read in.
     *
     * @param  Collection<int,ImmunizationRecord>  $doses
     * @return Collection<int,ImmunizationRecord>
     */
    private function chronological(Collection $doses): Collection
    {
        return $doses->sortBy(fn (ImmunizationRecord $i) => $this->sortKey($i))->values();
    }

    /** A row whose date cannot be read sorts last rather than to 1970. */
    private function sortKey(ImmunizationRecord $i): int
    {
        if (! $i->administered_at) {
            return PHP_INT_MAX;
        }

        try {
            return Carbon::parse($i->administered_at)->getTimestamp();
        } catch (\Throwable) {
            return PHP_INT_MAX;
        }
    }

    /**
     * Facility names for a set of ids.
     *
     * This platform carries two facility registers — `facilities` (the tenants
     * that record care) and `care_facilities` (the national directory) — and a
     * clinical row may point at either. Both are asked, in that order, and an
     * id that answers to neither leaves an honest blank rather than a guess.
     *
     * @param  list<mixed>  $ids
     * @return array<string,string>
     */
    private function facilityNames(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('strval', $ids))));

        if ($ids === []) {
            return [];
        }

        $names = [];

        try {
            $names = Facility::whereIn('id', $ids)->pluck('name', 'id')->all();
        } catch (\Throwable) {
            $names = [];
        }

        $missing = array_values(array_diff($ids, array_keys($names)));

        if ($missing !== []) {
            try {
                $names += CareFacility::whereIn('id', $missing)->pluck('facility_name', 'id')->all();
            } catch (\Throwable) {
                // One register being unavailable must not cost the reader the
                // whole dose row; the name simply stays blank.
            }
        }

        return array_map('strval', $names);
    }
}
