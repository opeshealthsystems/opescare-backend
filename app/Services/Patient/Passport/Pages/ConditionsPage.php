<?php

namespace App\Services\Patient\Passport\Pages;

use App\Models\Diagnosis;
use App\Models\Patient;
use App\Services\Patient\Passport\PageSource;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Page 07 — Conditions & Diagnoses: the problem list in full.
 *
 * Page 04 shows the eight most recent conditions as part of a handover. This
 * page is the whole list, and the split down the middle of it is the point:
 * what the patient is living with now, and what is behind them. A clinician
 * reads the first table to decide what to do next and the second to understand
 * how the patient got here, so a row landing in the wrong one is not a cosmetic
 * error — it is a condition either forgotten or wrongly treated.
 *
 * ── HOW A ROW IS SORTED, AND WHY IT ERRS TOWARDS "ACTIVE" ─────────────────
 *
 * `diagnoses.status` is free text. The column comment names three values and
 * the record actually carries more — this deployment's own demo data writes
 * `confirmed` and `chronic`, neither of which is in that comment. So the split
 * is made by recognising the words that genuinely CLOSE a condition (resolved,
 * inactive, remission, cured…) and by treating everything else as current.
 * A status nobody here has met stays in ACTIVE CONDITIONS. Retiring a condition
 * on the strength of a word this class does not recognise would quietly drop it
 * out of the reader's view; leaving an already-settled one in the active table
 * costs a second look and nothing more. The asymmetry is deliberate.
 *
 * ── WHY A WITHDRAWN DIAGNOSIS IS STILL PRINTED ────────────────────────────
 *
 * A diagnosis marked entered-in-error or refuted is a withdrawal, and page 04
 * drops it: it must never appear on a summary somebody treats from. This page
 * is the full record rather than a summary, and the same reasoning that keeps a
 * withdrawn allergy visible on page 05 applies here — a reader given a shorter
 * list believes it complete, and cannot see that the entry was made and set
 * aside. Such rows are therefore carried at the FOOT of the closed table, never
 * in the active one, with the recorded status word ("Entered in error") spelled
 * out in the Status column beside them.
 *
 * ── WHAT THE APPROVED SHEET SHOWS THAT THIS DEPLOYMENT CANNOT ─────────────
 *
 *  • NOTES — the sheet's fourth column ("Managed with inhaler", "Seasonal
 *    triggers") is a clinician's free-text comment on the condition. `diagnoses`
 *    holds no notes column. Rather than fill it with something plausible, the
 *    fourth column carries the coded REFERENCE the record does hold — the
 *    ICD-10 or SNOMED CT concept the diagnosis was recorded against — and it is
 *    dropped entirely from a table where not one row carries a code.
 *  • SINCE / YEAR is when the condition entered the record: the visit it was
 *    diagnosed at, or failing that when the row was written. This schema has no
 *    onset date, and a passport must not imply one it does not hold. Both
 *    tables therefore show a year, as the sheet does, and not a false precision.
 */
class ConditionsPage extends PageSource
{
    /** Statuses that put a condition behind the patient rather than in front of them. */
    private const PAST = ['resolved', 'inactive', 'remission', 'past', 'cured', 'closed', 'recovered'];

    /** Statuses that withdraw the entry: recorded, then taken back. */
    private const RETRACTED = ['entered_in_error', 'entered-in-error', 'error', 'refuted', 'voided', 'retracted'];

    /**
     * How many rows the page reads before it partitions them.
     *
     * Deliberately far beyond any real problem list: this is the page that is
     * supposed to be complete, so the limit exists only to stop a corrupted
     * record building a page of ten thousand rows, not to summarise.
     */
    private const WINDOW = 200;

    public function build(Patient $patient): array
    {
        $conditions = Diagnosis::with('visit')
            ->where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->limit(self::WINDOW)
            ->get();

        if ($conditions->isEmpty()) {
            return [];
        }

        $active    = $this->chronological($conditions->reject(fn (Diagnosis $d) => $this->isClosed($d)));
        $past      = $this->chronological($conditions->filter(fn (Diagnosis $d) => $this->isPast($d)));
        $retracted = $this->chronological($conditions->filter(fn (Diagnosis $d) => $this->isRetracted($d)));

        // Withdrawn entries close the second table rather than opening it: they
        // are the least clinically live thing on the page.
        $closed = $past->concat($retracted)->values();

        $sections = [];

        if ($active->isNotEmpty()) {
            $sections[] = $this->table(
                __('passport.sections.active_conditions'),
                $this->columns(__('passport.columns.since'), $active),
                $this->rows($active),
                'blue',
                'activity'
            );
        }

        if ($closed->isNotEmpty()) {
            $sections[] = $this->table(
                __('passport.sections.past_conditions'),
                $this->columns(__('passport.columns.year'), $closed),
                $this->rows($closed),
                'blue',
                'calendar-check'
            );
        }

        return $sections;
    }

    // ── The table ────────────────────────────────────────────────────────

    /**
     * The headings for one table: the sheet's four, with the fourth dropped
     * when no row beneath it carries a code.
     *
     * @param  Collection<int,Diagnosis>  $rows
     * @return list<string>
     */
    private function columns(string $dateHeading, Collection $rows): array
    {
        $columns = [
            __('passport.columns.condition'),
            $dateHeading,
            __('passport.columns.status'),
        ];

        if ($this->anyCoded($rows)) {
            $columns[] = __('passport.columns.reference');
        }

        return $columns;
    }

    /**
     * @param  Collection<int,Diagnosis>  $rows
     * @return list<list<array{v:string,pill?:string}>>
     */
    private function rows(Collection $rows): array
    {
        $coded = $this->anyCoded($rows);

        return $rows->map(function (Diagnosis $d) use ($coded) {
            $row = [
                $this->cell($this->conditionName($d)),
                $this->cell($this->year($this->recordedAt($d))),
                // No pill is invented for a status the shared reader does not
                // recognise — the word itself is printed, uncoloured.
                $this->cell($this->statusLabel($d->status), $this->statusTone($d->status)),
            ];

            if ($coded) {
                $row[] = $this->cell($this->reference($d));
            }

            return $row;
        })->values()->all();
    }

    // ── Reading one condition ────────────────────────────────────────────

    private function conditionName(Diagnosis $d): string
    {
        return trim((string) ($d->display_name ?: $d->snomed_display ?: $d->code ?: ''));
    }

    /**
     * The code the diagnosis was recorded against, named by its system so the
     * number means something: "ICD-10 E11", "SNOMED CT 44054006".
     */
    private function reference(Diagnosis $d): string
    {
        $code = trim((string) $d->code);

        if ($code !== '') {
            $system = trim((string) $d->code_system);

            return trim($system . ' ' . $code);
        }

        $snomed = trim((string) $d->snomed_code);

        // "SNOMED CT" is the name of a standard, not a phrase — it is the same
        // in both languages, exactly as the ICD-10 held in `code_system` is.
        return $snomed !== '' ? 'SNOMED CT ' . $snomed : '';
    }

    /**
     * When the condition entered the record: the visit it was diagnosed at, or
     * failing that the moment the row was written. Never an onset date — this
     * schema does not hold one.
     */
    private function recordedAt(Diagnosis $d): mixed
    {
        return optional($d->visit)->started_at ?: $d->created_at;
    }

    // ── The split ────────────────────────────────────────────────────────

    private function isClosed(Diagnosis $d): bool
    {
        return $this->isPast($d) || $this->isRetracted($d);
    }

    private function isPast(Diagnosis $d): bool
    {
        return in_array($this->word($d->status), self::PAST, true);
    }

    private function isRetracted(Diagnosis $d): bool
    {
        return in_array($this->word($d->status), self::RETRACTED, true);
    }

    private function word(?string $value): string
    {
        return strtolower(trim((string) $value));
    }

    // ── Order ────────────────────────────────────────────────────────────

    /**
     * Oldest first, as the sheet sets both tables and as the page's own
     * subtitle promises — a problem list read over time.
     *
     * @param  Collection<int,Diagnosis>  $rows
     * @return Collection<int,Diagnosis>
     */
    private function chronological(Collection $rows): Collection
    {
        return $rows->sortBy(fn (Diagnosis $d) => $this->sortKey($d))->values();
    }

    /** A row whose date cannot be read sorts last rather than to 1970. */
    private function sortKey(Diagnosis $d): int
    {
        $recorded = $this->recordedAt($d);

        if (! $recorded) {
            return PHP_INT_MAX;
        }

        try {
            return Carbon::parse($recorded)->getTimestamp();
        } catch (\Throwable) {
            return PHP_INT_MAX;
        }
    }

    /** @param  Collection<int,Diagnosis>  $rows */
    private function anyCoded(Collection $rows): bool
    {
        return $rows->contains(fn (Diagnosis $d) => $this->reference($d) !== '');
    }
}
