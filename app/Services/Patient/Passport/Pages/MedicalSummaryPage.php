<?php

namespace App\Services\Patient\Passport\Pages;

use App\Models\AllergyRecord;
use App\Models\CareFacility;
use App\Models\CarePlan;
use App\Models\Diagnosis;
use App\Models\Encounter;
use App\Models\Facility;
use App\Models\ImmunizationRecord;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Services\Patient\Passport\PageSource;
use Illuminate\Support\Collection;

/**
 * Page 04 — Medical Summary: the one page a clinician reads first.
 *
 * It is the widest page in the passport, and the only one that crosses domains:
 * the problem list, what the patient is taking, what they must not be given,
 * what they are protected against, where they have been seen, and what the plan
 * says happens next. Every other page is a single domain in depth; this one is
 * the handover.
 *
 * ── WHY THERE IS NO PROSE ON THIS PAGE ────────────────────────────────────
 *
 * The approved sheet opens with an "Overall summary" paragraph reading like a
 * clinician's impression. Nothing in this record holds such an impression, and
 * writing one here would mean a machine composing a clinical judgement about a
 * patient it has never examined — which someone would then act on. The callout
 * therefore carries COUNTED FACTS about the record ("Active conditions: 2 ·
 * Allergies: 2"), never a narrative, and the "Care plan and recommendations"
 * card is nothing but the text a clinician actually recorded on a CarePlan: its
 * title, its open goals and its live interventions. Not one line of advice on
 * this page is composed here.
 *
 * ── WHAT THE SHEET SHOWS THAT THIS DEPLOYMENT CANNOT ──────────────────────
 *
 *  • SURGICAL HISTORY — there is no procedure record here. `perioperative_records`
 *    exists as a table and a model, but nothing in the platform writes to it and
 *    it holds no rows; HealthPassportBuilder likewise lists 'procedures' among
 *    the pages this deployment does not source. A surgical block would therefore
 *    always be either empty or invented, so it is left out entirely.
 *  • Condition NOTES, medication INDICATION, allergy REACTION and vaccine
 *    NEXT DUE are columns on the sheet with no column behind them in this
 *    schema. They are dropped rather than filled with a plausible guess.
 *  • SINCE / YEAR is the date the condition was recorded — the visit it was
 *    diagnosed at, or failing that when the row was written. `diagnoses` has no
 *    onset date, and a passport must not imply one it does not hold.
 */
class MedicalSummaryPage extends PageSource
{
    /** A retracted diagnosis is not a condition at all — it never reaches the page. */
    private const RETRACTED = ['entered_in_error', 'entered-in-error', 'error', 'refuted', 'voided'];

    /** Statuses that put a condition behind the patient rather than in front of them. */
    private const PAST = ['resolved', 'inactive', 'remission', 'past', 'cured', 'closed'];

    /** A prescription the patient is still on. */
    private const CURRENT_PRESCRIPTIONS = ['active', 'partially_dispensed'];

    /** Care-plan lines that are finished: recorded history, not a recommendation. */
    private const CLOSED_PLAN_ITEM = ['completed', 'cancelled', 'stopped', 'abandoned', 'achieved', 'met'];

    /** How many rows the page reads before it partitions or groups them. */
    private const WINDOW = 30;

    /**
     * Blocks where cutting at eight would hide something a clinician needs:
     * a full childhood schedule runs past eight vaccines, and one prescription
     * routinely carries three drug lines.
     */
    private const LONG_LIST = 12;

    public function build(Patient $patient): array
    {
        $conditions       = $this->conditions($patient);
        $activeConditions = $conditions->reject(fn (Diagnosis $d) => $this->isPast($d->status))->values();
        $pastConditions   = $conditions->filter(fn (Diagnosis $d) => $this->isPast($d->status))->values();

        $allergies  = $this->allergies($patient);
        $medicines  = $this->medicines($patient);
        $vaccines   = $this->vaccines($patient);
        $encounters = $this->encounters($patient);
        $plans      = CarePlan::with(['goals', 'interventions'])
            ->where('patient_id', $patient->id)
            ->orderByDesc('start_date')
            ->limit(2)
            ->get();

        // The four counts this page has always opened with — conditions,
        // medicines, visits, allergies — now drawn as the sheet's tile row.
        //
        // Two of them are counted more carefully than before, because a tile
        // that disagrees with the table under it is read as an error in the
        // record rather than in the passport: a retracted diagnosis is not
        // counted as a condition, and "medicines" counts the drug lines a
        // patient is actually taking rather than the prescriptions holding
        // them (one prescription routinely carries three).
        $counts = [
            'conditions' => Diagnosis::where('patient_id', $patient->id)
                ->where(fn ($q) => $q->whereNull('status')->orWhereNotIn('status', self::RETRACTED))
                ->count(),
            'medicines'  => PrescriptionItem::whereIn(
                'prescription_id',
                Prescription::where('patient_id', $patient->id)
                    ->whereIn('status', self::CURRENT_PRESCRIPTIONS)
                    ->select('id')
            )->count(),
            'encounters' => Encounter::where('patient_id', $patient->id)->count(),
            'allergies'  => AllergyRecord::where('patient_id', $patient->id)->count(),
        ];

        $vaccineCount = ImmunizationRecord::where('patient_id', $patient->id)->distinct()->count('vaccine_name');
        $planCount    = CarePlan::where('patient_id', $patient->id)->count();

        if (array_sum($counts) + $vaccineCount + $planCount === 0) {
            return [];
        }

        $sections = [];

        // Four zeroes would be a row of tiles saying nothing at all — on a
        // record that plainly holds something, since the page got this far.
        if (array_sum($counts) > 0) {
            $sections[] = $this->stats(__('passport.sections.summary'), [
                ['value' => $counts['conditions'], 'label' => __('passport.stats.conditions')],
                ['value' => $counts['medicines'], 'label' => __('passport.stats.medicines')],
                ['value' => $counts['encounters'], 'label' => __('passport.stats.encounters')],
                ['value' => $counts['allergies'], 'label' => __('passport.stats.allergies')]
                    + ($counts['allergies'] > 0 ? ['tone' => 'warning'] : []),
            ], 'blue', 'document-text');
        }

        $summary = $this->countedFacts($activeConditions->count(), $pastConditions->count(), $counts['allergies'], $vaccineCount, $planCount);

        if ($summary !== '') {
            $sections[] = $this->callout(__('passport.sections.overall_summary'), $summary, 'green', 'check-circle');
        }

        if ($activeConditions->isNotEmpty()) {
            $sections[] = $this->table(
                __('passport.sections.active_conditions'),
                [__('passport.columns.condition'), __('passport.columns.since'), __('passport.columns.status')],
                $activeConditions->take(self::RECENT_LIMIT)->map(fn (Diagnosis $d) => [
                    $this->cell($this->conditionName($d)),
                    $this->cell($this->year($this->recordedAt($d))),
                    $this->cell($this->statusLabel($d->status), $this->statusTone($d->status)),
                ])->all(),
                'blue',
                'activity'
            );
        }

        if ($pastConditions->isNotEmpty()) {
            $sections[] = $this->table(
                __('passport.sections.past_conditions'),
                [__('passport.columns.condition'), __('passport.columns.year'), __('passport.columns.status')],
                $pastConditions->take(self::RECENT_LIMIT)->map(fn (Diagnosis $d) => [
                    $this->cell($this->conditionName($d)),
                    $this->cell($this->year($this->recordedAt($d))),
                    $this->cell($this->statusLabel($d->status), $this->statusTone($d->status)),
                ])->all(),
                'blue',
                'calendar-check'
            );
        }

        if ($allergies->isNotEmpty()) {
            $sections[] = $this->table(
                __('passport.sections.allergies_intolerances'),
                [__('passport.columns.allergen'), __('passport.columns.severity'), __('passport.columns.status')],
                $allergies->map(fn (AllergyRecord $a) => [
                    $this->cell((string) $a->substance),
                    $this->cell($this->statusLabel($a->severity), $this->severityTone($a->severity)),
                    $this->cell($this->statusLabel($a->status), $this->statusTone($a->status)),
                ])->all(),
                'red',
                'alert-triangle'
            );
        }

        if ($medicines !== []) {
            $sections[] = $this->table(
                // The sheet heads this block "Current medications", and that is
                // the page-6 title word for word — reused rather than settling
                // for the shorter `sections.medications` ("Medicines").
                __('passport.pages.current-medications.title'),
                [__('passport.columns.medication'), __('passport.columns.dose'), __('passport.columns.frequency')],
                $medicines,
                'green',
                'pill'
            );
        }

        if ($vaccines->isNotEmpty()) {
            $sections[] = $this->table(
                __('passport.sections.immunisation_status'),
                [__('passport.columns.vaccine'), __('passport.columns.status'), __('passport.columns.last_dose')],
                $vaccines->map(fn (ImmunizationRecord $i) => [
                    $this->cell((string) ($i->vaccine_name ?: $i->vaccine_code)),
                    $this->cell($this->statusLabel($i->status), $this->statusTone($i->status)),
                    $this->cell($this->date($i->administered_at)),
                ])->all(),
                'green',
                'shield-check'
            );
        }

        if ($encounters->isNotEmpty()) {
            $facilities = $this->facilityNames($encounters->pluck('facility_id')->all());

            $sections[] = $this->table(
                __('passport.sections.recent_encounters'),
                [
                    __('passport.columns.date'),
                    __('passport.columns.type'),
                    __('passport.columns.facility'),
                    __('passport.columns.reason'),
                ],
                $encounters->map(fn (Encounter $e) => [
                    $this->cell($this->date($e->started_at)),
                    $this->cell($this->statusLabel($e->encounter_type)),
                    $this->cell((string) ($facilities[(string) $e->facility_id] ?? '')),
                    $this->cell((string) $e->reason_for_encounter),
                ])->all(),
                'blue',
                'calendar-check'
            );
        }

        $recommendations = $this->recommendations($plans);

        if ($recommendations !== []) {
            $sections[] = $this->bullets(
                __('passport.sections.care_recommendations'),
                $recommendations,
                'purple',
                'clipboard'
            );
        }

        return $sections;
    }

    // ── Reading the record ───────────────────────────────────────────────

    /**
     * The problem list, retractions removed.
     *
     * A diagnosis marked entered-in-error or refuted is a withdrawal, not a
     * condition, and must never appear on a summary somebody treats from.
     *
     * @return Collection<int,Diagnosis>
     */
    private function conditions(Patient $patient): Collection
    {
        return Diagnosis::with('visit')
            ->where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->limit(self::WINDOW)
            ->get()
            ->reject(fn (Diagnosis $d) => in_array($this->word($d->status), self::RETRACTED, true))
            ->values();
    }

    /** @return Collection<int,AllergyRecord> */
    private function allergies(Patient $patient): Collection
    {
        return AllergyRecord::where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->limit(self::RECENT_LIMIT)
            ->get();
    }

    /**
     * The drug lines the patient is currently on — one row per medicine, not
     * per prescription, because that is what a clinician is reading for.
     *
     * @return list<list<array{v:string,pill?:string}>>
     */
    private function medicines(Patient $patient): array
    {
        $prescriptions = Prescription::with('items')
            ->where('patient_id', $patient->id)
            ->whereIn('status', self::CURRENT_PRESCRIPTIONS)
            ->orderByDesc('prescribed_at')
            ->limit(self::RECENT_LIMIT)
            ->get();

        $rows = [];

        foreach ($prescriptions as $prescription) {
            foreach ($prescription->items as $line) {
                $rows[] = [
                    $this->cell((string) $line->drug_name),
                    $this->cell((string) $line->dose),
                    $this->cell((string) $line->frequency),
                ];

                if (count($rows) >= self::LONG_LIST) {
                    return $rows;
                }
            }
        }

        return $rows;
    }

    /**
     * Immunisation status: one row per vaccine, showing its most recent dose.
     *
     * The record holds a row per dose, and a summary listing "Penta" three
     * times says less than one line saying when the last one was given.
     *
     * @return Collection<int,ImmunizationRecord>
     */
    private function vaccines(Patient $patient): Collection
    {
        return ImmunizationRecord::where('patient_id', $patient->id)
            ->orderByDesc('administered_at')
            ->limit(self::WINDOW)
            ->get()
            ->groupBy(fn (ImmunizationRecord $i) => strtolower(trim((string) ($i->vaccine_name ?: $i->vaccine_code))))
            ->map(fn (Collection $doses) => $doses->first())
            ->values()
            ->take(self::LONG_LIST);
    }

    /** @return Collection<int,Encounter> */
    private function encounters(Patient $patient): Collection
    {
        return Encounter::where('patient_id', $patient->id)
            ->orderByDesc('started_at')
            ->limit(self::RECENT_LIMIT)
            ->get();
    }

    /**
     * The recommendation card — recorded care-plan text and nothing else.
     *
     * A goal that has been met and an intervention that has stopped are history
     * rather than a recommendation, so only the open lines are carried here.
     *
     * @param  Collection<int,CarePlan>  $plans
     * @return list<string>
     */
    private function recommendations(Collection $plans): array
    {
        $lines = [];

        foreach ($plans as $plan) {
            $title = trim((string) $plan->title);

            if ($title !== '') {
                $lines[] = $title;
            }

            $interventions = $plan->interventions
                ->reject(fn ($i) => in_array($this->word($i->status), self::CLOSED_PLAN_ITEM, true))
                ->take(5);

            foreach ($interventions as $intervention) {
                $text = trim((string) $intervention->description);

                if ($text === '') {
                    continue;
                }

                $frequency = trim((string) $intervention->frequency);
                $lines[]   = $frequency !== '' ? $text . ' · ' . $frequency : $text;
            }

            $goals = $plan->goals
                ->reject(fn ($g) => in_array($this->word($g->status), self::CLOSED_PLAN_ITEM, true))
                ->take(3);

            foreach ($goals as $goal) {
                $text = trim((string) $goal->goal_text);

                if ($text !== '') {
                    $lines[] = $text;
                }
            }
        }

        return $lines;
    }

    // ── Small readings ───────────────────────────────────────────────────

    /**
     * The counted facts the summary callout carries.
     *
     * Only what is actually there: a zero is left out rather than printed, so
     * the banner never reads as a reassurance nobody recorded.
     */
    private function countedFacts(int $active, int $past, int $allergies, int $vaccines, int $plans): string
    {
        // French sets a space before a colon and the rest of the app honours
        // it; the labels themselves are translated, only the punctuation
        // between them is decided here.
        $colon = str_starts_with(app()->getLocale(), 'fr') ? ' : ' : ': ';

        $counted = [
            [__('passport.sections.active_conditions'), $active],
            [__('passport.sections.past_conditions'), $past],
            [__('passport.stats.allergies'), $allergies],
            [__('passport.stats.vaccines'), $vaccines],
            [__('passport.sections.care_plans'), $plans],
        ];

        $facts = [];

        foreach ($counted as [$label, $count]) {
            if ($count > 0) {
                $facts[] = $label . $colon . $count;
            }
        }

        return implode(' · ', $facts);
    }

    private function conditionName(Diagnosis $d): string
    {
        return (string) ($d->display_name ?: $d->snomed_display ?: $d->code ?: '');
    }

    /**
     * When the condition entered the record: the visit it was diagnosed at,
     * or failing that the moment the row was written. Never an onset date —
     * this schema does not hold one.
     */
    private function recordedAt(Diagnosis $d): mixed
    {
        return optional($d->visit)->started_at ?: $d->created_at;
    }

    private function isPast(?string $status): bool
    {
        return in_array($this->word($status), self::PAST, true);
    }

    private function word(?string $value): string
    {
        return strtolower(trim((string) $value));
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
            $names = Facility::withoutGlobalScope('isolate_demo')->whereIn('id', $ids)->pluck('name', 'id')->all();
        } catch (\Throwable) {
            $names = [];
        }

        $missing = array_values(array_diff($ids, array_keys($names)));

        if ($missing !== []) {
            try {
                $names += CareFacility::whereIn('id', $missing)->pluck('facility_name', 'id')->all();
            } catch (\Throwable) {
                // One register being unavailable must not cost the reader the
                // whole encounter row; the name simply stays blank.
            }
        }

        return array_map('strval', $names);
    }
}
