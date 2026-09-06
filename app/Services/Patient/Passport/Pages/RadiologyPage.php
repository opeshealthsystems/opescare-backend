<?php

namespace App\Services\Patient\Passport\Pages;

use App\Models\Patient;
use App\Models\RadiologyReport;
use App\Services\Patient\Passport\PageSource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;

/**
 * Page 12 — Radiology & Imaging: what was imaged, and what the radiologist
 * concluded.
 *
 * The page is laid out the way the approved results sheet lays out laboratory
 * results: the recent reports in full first, then the register of every study
 * behind them. For imaging the "result" is prose rather than a number, and that
 * changes what the page may and may not do with it.
 *
 * ── AN IMPRESSION IS PRINTED AS IT WAS DICTATED, OR NOT AT ALL ────────────
 *
 * The impression is the radiologist's own conclusion and it is what a clinician
 * acts on. It is therefore carried through verbatim: never summarised, never
 * truncated, never rephrased to fit a column. "No evidence of pulmonary
 * tuberculosis" and "no evidence of active pulmonary tuberculosis" are
 * different statements, and a passport that shortens one into the other is
 * dangerous. Where the text is long the page lets it be long.
 *
 * ── WHAT THE PAGE SHOWS FOR AN UNRELEASED REPORT ──────────────────────────
 *
 * `radiology_reports.status` runs draft → preliminary → final, and may then be
 * amended or corrected. This deployment treats only the last three as ISSUED:
 * RadiologyReportService::distribute() refuses anything else, and
 * getPendingForFacility() counts draft and preliminary as work still on the
 * radiologist's desk. A draft is unfinished dictation that can say the opposite
 * of the report that eventually goes out, so its text is not carried onto a
 * page a clinician may treat from.
 *
 * It is not hidden either. Every study the record holds appears in the second
 * table with its date, its facility and its status word, so a reader can see
 * that the study exists and that its report has not been issued yet. Dropping
 * the row entirely would tell them the imaging never happened.
 *
 * ── WHAT THE SHEET SHOWS THAT THIS RECORD CANNOT SUPPLY ───────────────────
 *
 *  • The sheet's opening tiles (total / recent / abnormal / last) count a
 *    laboratory panel. Imaging has no "abnormal" flag to count — abnormality
 *    lives inside the prose — and the passport vocabulary has no label for a
 *    count of studies, so the block is left out rather than filled with a
 *    number the record does not carry.
 *  • The sheet's trend chart, status donut and test-category counts are all
 *    built from numeric results. An imaging report has no numeric result.
 *  • The sheet's closing "important note" is fixed prose; the passport
 *    vocabulary has no key for it and this page must not add one.
 *
 * ── COLUMN NAMES ARE THE PASSPORT'S, NOT RADIOLOGY'S ──────────────────────
 *
 * The shared vocabulary has `findings` and no `impression`, so the conclusion
 * is printed under FINDINGS, and the observation narrative (`findings` on the
 * row) is not printed at all: two paragraphs per study on a phone-width page
 * bury the one line that matters. Likewise the radiologist's recommendation —
 * "repeat film in six weeks" — is printed under NOTES, and only when a report
 * on the page actually carries one. Both are named in the page's report as
 * vocabulary this page would rather have.
 */
class RadiologyPage extends PageSource
{
    /**
     * The statuses in which this deployment has ISSUED a report.
     *
     * Mirrors RadiologyReportService: distribute() accepts exactly these, and
     * finalize() moves a report into them. Anything else is still being
     * written.
     */
    private const RELEASED = ['final', 'amended', 'corrected'];

    /**
     * How many studies the register reads.
     *
     * Well beyond any real imaging history, because this page is meant to be
     * the whole of it — the limit exists to stop a corrupted record building a
     * page of ten thousand rows, not to summarise.
     */
    private const WINDOW = 24;

    public function build(Patient $patient): array
    {
        $studies = RadiologyReport::query()
            // The facility name is on the patient's own report. Facility
            // carries the demo-isolation global scope, and in demo mode that
            // scope hides every facility row flagged is_demo = false — which
            // would blank the name of the hospital that did the scan. Reading
            // it unscoped states where the study was done; it grants no access
            // to anything but the name already printed on this report.
            ->with(['facility' => fn ($q) => $q->withoutGlobalScopes()])
            ->where('patient_id', $patient->id)
            ->orderByDesc('study_date')
            ->orderByDesc('created_at')
            ->limit(self::WINDOW)
            ->get();

        if ($studies->isEmpty()) {
            return [];
        }

        $sections = [];

        $reports = $studies
            ->filter(fn (RadiologyReport $r) => $this->isReleased($r))
            ->take(static::RECENT_LIMIT)
            ->values();

        if ($reports->isNotEmpty()) {
            $sections[] = $this->table(
                __('passport.sections.recent_results'),
                $this->reportColumns($reports),
                $this->reportRows($reports),
                'blue',
                'document-text'
            );
        }

        $sections[] = $this->table(
            __('passport.sections.imaging_reports'),
            [
                __('passport.columns.study'),
                __('passport.columns.modality'),
                __('passport.columns.date'),
                __('passport.columns.facility'),
                __('passport.columns.status'),
            ],
            $this->registerRows($studies),
            'blue',
            'image'
        );

        return $sections;
    }

    // ── The reports, in full ─────────────────────────────────────────────

    /**
     * @param  Collection<int,RadiologyReport>  $reports
     * @return list<string>
     */
    private function reportColumns(Collection $reports): array
    {
        $columns = [
            __('passport.columns.study'),
            __('passport.columns.reported'),
            __('passport.columns.findings'),
        ];

        if ($this->anyAdvised($reports)) {
            $columns[] = __('passport.columns.notes');
        }

        return $columns;
    }

    /**
     * @param  Collection<int,RadiologyReport>  $reports
     * @return list<list<array{v:string,pill?:string}>>
     */
    private function reportRows(Collection $reports): array
    {
        $advised = $this->anyAdvised($reports);

        return $reports->map(function (RadiologyReport $r) use ($advised) {
            $row = [
                $this->cell($this->studyTitle($r)),
                $this->cell($this->date($this->issuedAt($r))),
                // Verbatim. The conclusion a clinician acts on is never
                // shortened to fit a column.
                $this->cell($this->text($r->impression)),
            ];

            if ($advised) {
                $row[] = $this->cell($this->text($r->recommendation));
            }

            return $row;
        })->values()->all();
    }

    // ── The register of studies ──────────────────────────────────────────

    /**
     * @param  Collection<int,RadiologyReport>  $studies
     * @return list<list<array{v:string,pill?:string}>>
     */
    private function registerRows(Collection $studies): array
    {
        return $studies->map(fn (RadiologyReport $r) => [
            $this->cell($this->text($r->body_part)),
            $this->cell($this->modality($r)),
            $this->cell($this->date($r->study_date)),
            $this->cell($this->text(optional($r->facility)->name)),
            $this->cell($this->statusLabel($r->status), $this->reportTone($r->status)),
        ])->values()->all();
    }

    // ── Reading one study ────────────────────────────────────────────────

    /**
     * "X-ray — Chest": the study named the way the results inbox names it, so
     * the same scan reads the same in both places.
     */
    private function studyTitle(RadiologyReport $report): string
    {
        $parts = array_filter([
            $this->modality($report),
            trim((string) $report->body_part),
        ], fn (string $part) => $part !== '');

        return $parts === [] ? '' : implode(' — ', $parts);
    }

    /**
     * The modality through the shared imaging catalogue, never the raw enum:
     * "xray" is a column value, not a word a patient reads, and the catalogue
     * is the only place this deployment translates it into French.
     */
    private function modality(RadiologyReport $report): string
    {
        $recorded = strtolower(trim((string) $report->modality));

        if ($recorded === '') {
            return '';
        }

        $key = 'doctor_results.modality.' . $recorded;

        // A modality the catalogue has not met falls back to the recorded word
        // tidied, never to a missing-key string.
        return Lang::has($key) ? __($key) : ucfirst(str_replace('_', ' ', $recorded));
    }

    /**
     * When the text on this page was issued.
     *
     * An amended report REPLACES its own wording, so the date beside the words
     * being read is the date of the amendment; only an unamended report is
     * still the one that was finalised. Neither recorded means the honest
     * blank — the study date is when the picture was taken, not when anybody
     * signed the report, and must not stand in for it.
     */
    private function issuedAt(RadiologyReport $report): mixed
    {
        return $report->amended_at ?: $report->finalized_at;
    }

    private function isReleased(RadiologyReport $report): bool
    {
        return in_array(strtolower(trim((string) $report->status)), self::RELEASED, true);
    }

    /** @param  Collection<int,RadiologyReport>  $reports */
    private function anyAdvised(Collection $reports): bool
    {
        return $reports->contains(fn (RadiologyReport $r) => trim((string) $r->recommendation) !== '');
    }

    private function text(?string $value): string
    {
        return trim((string) $value);
    }

    /**
     * The pill for a report's own lifecycle.
     *
     * The shared status reader knows the vocabulary of orders and consents; it
     * has never met a radiology report, whose statuses are a closed enum on the
     * table. Reading them here is knowledge of that enum, not a guess: FINAL is
     * the report as issued; AMENDED and CORRECTED say the wording was changed
     * after issue and the reader should take the newer text; DRAFT and
     * PRELIMINARY say the radiologist has not released it. Anything outside the
     * enum falls back to the shared reader, and to no pill at all when that
     * does not recognise it either.
     */
    private function reportTone(?string $status): ?string
    {
        return match (strtolower(trim((string) $status))) {
            'final'                 => 'success',
            'amended', 'corrected'  => 'caution',
            'draft', 'preliminary'  => 'warning',
            default                 => $this->statusTone($status),
        };
    }
}
