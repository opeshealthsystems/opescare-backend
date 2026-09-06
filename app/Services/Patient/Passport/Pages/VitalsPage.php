<?php

namespace App\Services\Patient\Passport\Pages;

use App\Enums\SelfReportedVitalMeasure;
use App\Enums\VitalProvenance;
use App\Models\Patient;
use App\Models\PatientRecordedVital;
use App\Services\Patient\Passport\PageSource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

/**
 * Page 25 — Vital Signs.
 *
 * ── TWO SOURCES, AND THEY ARE NEVER MIXED ─────────────────────────────────
 *
 * A blood pressure of 148/94 means one thing when a nurse took it on a
 * calibrated cuff during a review, and something else when the patient took it
 * on a supermarket cuff at home before breakfast. The NUMBER is
 * indistinguishable — App\Enums\VitalProvenance exists precisely because of
 * that, and its docblock states the rule this page obeys: "a reading whose
 * origin is unknown is a reading that must not be shown next to one whose
 * origin is known."
 *
 * So this page never renders one undifferentiated table. It renders one block
 * per provenance, titled with the platform's own approved wording for that
 * provenance (patient_writes.provenance.*), read straight off the enum:
 *
 *   FACILITY-MEASURED — `triage_vital_signs` and `vital_signs`. These are the
 *     two tables VitalProvenance::FacilityMeasured names, and they are BOTH
 *     read here for the same reason MobileVitalsController reads both: they
 *     are two live write paths for the same act. `triage_vital_signs` is
 *     written by the OpesCareLite offline-sync path and carries `patient_id`
 *     and a real `recorded_at`; `vital_signs` is written by the triage module,
 *     has no patient column (the patient is reached through
 *     triage_records → visits) and no `recorded_at` (so `created_at` is the
 *     honest reading time). Reading only one of them would blank this page for
 *     every patient triaged through the other.
 *
 *   SELF-REPORTED — `patient_recorded_vitals`. A separate table by design, that
 *     no clinical reader queries, with `provenance` pinned by a database CHECK
 *     constraint. Retracted entries are excluded through the model's own
 *     `active()` scope: a reading the patient took back is history, not a
 *     current value.
 *
 * The self-reported block is preceded by the enum's own one-line explanation —
 * "It has not been checked by a health worker and is not a clinical
 * measurement" — because the title alone can be skimmed past and that sentence
 * cannot.
 *
 * ── THIS PAGE FLAGS NOTHING, AND THAT IS DELIBERATE ───────────────────────
 *
 * No value on this page carries a high / low / normal word or a coloured pill.
 * There is no vital-sign equivalent of App\Support\ResultFlagResolver in this
 * codebase: the only adult advisory bands that exist are PRIVATE methods on
 * App\Http\Controllers\Api\Mobile\MobileVitalsController (`classify()`,
 * `bloodPressureStatus()`), reachable by nothing else. Copying those numbers
 * into this file would make the passport a second, independently drifting
 * reader of the same values — which is the exact failure ResultFlagResolver
 * was created to close, after three readers of `lab_results` classified the
 * same potassium three different ways.
 *
 * The other candidates are worse. SelfReportedVitalMeasure::primaryBounds()
 * looks like a range and is not one — its own docblock says these are "no
 * living human ever measured this" bounds, so a systolic of 210 sits happily
 * inside them. TriageVitalSign::hasFeverFlag() and hasHypoxiaFlag() are real
 * advisory helpers, but they cover temperature and saturation only; using them
 * would put a red pill on a temperature and NOTHING beside a blood pressure of
 * 196/124, which a reader would take as "the blood pressure was looked at and
 * was fine". Partial flagging is more dangerous than none.
 *
 * So the page prints the value, the unit, when it was taken and who took it,
 * and leaves the reading of it to the clinician — which is the one thing that
 * is certainly correct. If this platform later grows a shared vitals-band
 * authority, this page should ask it and print its answer; it must never do
 * the arithmetic itself.
 *
 * ── WHAT THE APPROVED SHEETS SHOW THAT THIS DEPLOYMENT CANNOT SUPPLY ──────
 *
 * The measurement sheets carry a per-analyte trend chart, a status donut and a
 * counted-tile header. The charts are a rendering the passport block API has no
 * shape for, and the tiles would have to count "normal" against "abnormal" —
 * a classification this page has just refused to make, so counting it would
 * smuggle it back in. `passport.stats.*` has no key for a measurement count in
 * any case. All three are left out rather than approximated.
 */
class VitalsPage extends PageSource
{
    /**
     * How many facility reading-sets are shown, taken WHOLE.
     *
     * A set of vitals is recorded as one act, so it is kept together the way
     * the laboratory page keeps a panel together — a blood pressure separated
     * from the pulse taken with it is half an observation. Each set expands to
     * up to six rows, which is why this window is smaller than RECENT_LIMIT.
     */
    private const RECENT_READINGS = 3;

    /**
     * How many self-reported readings are kept PER MEASURE.
     *
     * `patient_recorded_vitals` holds one measure per row, so there is no set
     * to keep intact — the natural grouping is the measure. Capping per measure
     * rather than overall is what stops a fortnight of daily home blood
     * pressures from pushing the single weight and the glucose off the page.
     */
    private const RECENT_PER_MEASURE = 4;

    public function build(Patient $patient): array
    {
        $sections = [];

        $measured = $this->facilityRows($patient);

        if ($measured !== []) {
            $sections[] = $this->table(
                __(VitalProvenance::FacilityMeasured->labelKey()),
                [
                    __('passport.columns.measure'),
                    __('passport.columns.value'),
                    __('passport.columns.unit'),
                    __('passport.columns.measured'),
                    __('passport.columns.facility'),
                ],
                $measured,
                'blue',
                'heart-pulse'
            );
        }

        $recorded = $this->selfReportedRows($patient);

        if ($recorded !== []) {
            // The caveat comes BEFORE the numbers it qualifies, and it is the
            // last thing on the page, so there is exactly one block it can be
            // read as applying to.
            $sections[] = $this->callout(
                __(VitalProvenance::SelfReported->labelKey()),
                __(VitalProvenance::SelfReported->descriptionKey()),
                'amber',
                'alert-triangle'
            );

            $sections[] = $this->table(
                __(VitalProvenance::SelfReported->labelKey()),
                [
                    __('passport.columns.measure'),
                    __('passport.columns.value'),
                    __('passport.columns.unit'),
                    __('passport.columns.measured'),
                    __('passport.columns.notes'),
                ],
                $recorded,
                'amber',
                'clipboard'
            );
        }

        return $sections;
    }

    // ── Measured at a facility ───────────────────────────────────────────

    /**
     * The recent facility reading-sets, newest set first, each expanded into
     * one row per measure it actually carries.
     *
     * @return list<list<array{v:string,pill?:string}>>
     */
    private function facilityRows(Patient $patient): array
    {
        $readings = $this->triageReadings($patient)
            ->concat($this->legacyReadings($patient))
            ->filter(fn (array $r) => $r['at'] !== null)
            // The timestamp decides, never the table a reading came from — and
            // it is compared as a moment rather than as the string the driver
            // happened to return, because the two tables need not format their
            // timestamps identically and a lexicographic tie would silently
            // reorder a patient's history.
            ->sortByDesc(fn (array $r) => $this->sortableMoment($r['at']))
            ->take(self::RECENT_READINGS);

        $rows = [];

        foreach ($readings as $reading) {
            foreach ($this->measuresOf($reading) as [$label, $value, $unit]) {
                $rows[] = [
                    $this->cell($label),
                    $this->cell($value),
                    $this->cell($unit),
                    $this->cell($this->date($reading['at'], 'd M Y, H:i')),
                    $this->cell((string) ($reading['facility'] ?? '')),
                ];
            }
        }

        return $rows;
    }

    /**
     * `triage_vital_signs` — patient-scoped, with its own `recorded_at`.
     *
     * Read through the query builder rather than the model so the facility name
     * arrives in the same query instead of a relation load per row, and so no
     * facility scope can narrow a record the patient is entitled to see whole.
     *
     * @return Collection<int,array<string,mixed>>
     */
    private function triageReadings(Patient $patient): Collection
    {
        return DB::table('triage_vital_signs as t')
            ->leftJoin('visits as v', 'v.id', '=', 't.visit_id')
            ->leftJoin('facilities as f', 'f.id', '=', 'v.facility_id')
            ->where('t.patient_id', $patient->id)
            ->orderByRaw('COALESCE(t.recorded_at, t.created_at) DESC NULLS LAST')
            ->limit(self::RECENT_READINGS)
            ->get([
                't.recorded_at',
                't.created_at',
                't.systolic_bp',
                't.diastolic_bp',
                't.pulse_rate',
                't.oxygen_saturation',
                't.temperature',
                't.respiratory_rate',
                't.weight_kg',
                'f.name as facility_name',
            ])
            ->map(fn ($row) => [
                'at'          => $row->recorded_at ?: $row->created_at,
                'facility'    => $row->facility_name,
                'systolic'    => $row->systolic_bp,
                'diastolic'   => $row->diastolic_bp,
                'pulse'       => $row->pulse_rate,
                'spo2'        => $row->oxygen_saturation,
                'temperature' => $row->temperature,
                'respiratory' => $row->respiratory_rate,
                'weight'      => $row->weight_kg,
            ]);
    }

    /**
     * `vital_signs` — the triage module's own write path.
     *
     * It carries no patient column, so the patient is reached through
     * triage_records → visits exactly as MobileVitalsController reaches them,
     * and it carries no `recorded_at`, so `created_at` is the honest reading
     * time and is shown as such.
     *
     * @return Collection<int,array<string,mixed>>
     */
    private function legacyReadings(Patient $patient): Collection
    {
        return DB::table('vital_signs as vs')
            ->join('triage_records as tr', 'tr.id', '=', 'vs.triage_record_id')
            ->join('visits as v', 'v.id', '=', 'tr.visit_id')
            ->leftJoin('facilities as f', 'f.id', '=', 'v.facility_id')
            ->where('v.patient_id', $patient->id)
            ->orderByDesc('vs.created_at')
            ->limit(self::RECENT_READINGS)
            ->get([
                'vs.created_at',
                'vs.blood_pressure_systolic',
                'vs.blood_pressure_diastolic',
                'vs.pulse',
                'vs.oxygen_saturation',
                'vs.temperature',
                'vs.respiratory_rate',
                'vs.weight',
                'f.name as facility_name',
            ])
            ->map(fn ($row) => [
                'at'          => $row->created_at,
                'facility'    => $row->facility_name,
                'systolic'    => $row->blood_pressure_systolic,
                'diastolic'   => $row->blood_pressure_diastolic,
                'pulse'       => $row->pulse,
                'spo2'        => $row->oxygen_saturation,
                'temperature' => $row->temperature,
                'respiratory' => $row->respiratory_rate,
                'weight'      => $row->weight,
            ]);
    }

    /**
     * The measures one reading-set actually carries, in the order the app's own
     * vitals card orders them (MobileVitalsController), so the passport and the
     * app do not disagree about which number a reader meets first.
     *
     * A null column produces NO row. Nothing is zero-filled, averaged or
     * carried forward from an earlier reading.
     *
     * @param  array<string,mixed>  $reading
     * @return list<array{0:string,1:string,2:string}>  [label, value, unit]
     */
    private function measuresOf(array $reading): array
    {
        $out = [];

        if (($pulse = $this->number($reading['pulse'], 0)) !== null) {
            // No patient-facing measure name exists for pulse, respiratory
            // rate or saturation — a patient cannot record them at home, so
            // SelfReportedVitalMeasure has no case for them. These are the
            // triage screen's own labels for the same three numbers.
            $out[] = [__('staff_clinical.vital_pulse'), $pulse, 'bpm'];
        }

        $systolic  = $this->number($reading['systolic'], 0);
        $diastolic = $this->number($reading['diastolic'], 0);

        // Both numbers or neither: a lone systolic is not a blood pressure and
        // is never rendered as one.
        if ($systolic !== null && $diastolic !== null) {
            $out[] = [
                $this->measureName(SelfReportedVitalMeasure::BloodPressure),
                $systolic . '/' . $diastolic,
                SelfReportedVitalMeasure::BloodPressure->defaultUnit(),
            ];
        }

        if (($spo2 = $this->number($reading['spo2'], 0)) !== null) {
            $out[] = [__('staff_clinical.vital_spo2'), $spo2, '%'];
        }

        if (($temperature = $this->number($reading['temperature'], 1)) !== null) {
            $out[] = [
                $this->measureName(SelfReportedVitalMeasure::Temperature),
                $temperature,
                SelfReportedVitalMeasure::Temperature->defaultUnit(),
            ];
        }

        if (($respiratory = $this->number($reading['respiratory'], 0)) !== null) {
            $out[] = [__('staff_clinical.vital_rr'), $respiratory, '/min'];
        }

        if (($weight = $this->number($reading['weight'], 1)) !== null) {
            $out[] = [
                $this->measureName(SelfReportedVitalMeasure::Weight),
                $weight,
                SelfReportedVitalMeasure::Weight->defaultUnit(),
            ];
        }

        return $out;
    }

    // ── Recorded by the patient ──────────────────────────────────────────

    /**
     * The patient's own log, grouped by measure and newest first within each,
     * in the enum's declared order.
     *
     * Voided entries are excluded through the model's `active()` scope. A
     * mistyped reading that the patient took back is part of the history of the
     * record, but it is not a value anyone should treat as current, and a
     * passport shows current values.
     *
     * @return list<list<array{v:string,pill?:string}>>
     */
    private function selfReportedRows(Patient $patient): array
    {
        $readings = PatientRecordedVital::query()
            ->forPatient((string) $patient->id)
            ->active()
            ->orderByDesc('measured_at')
            ->get();

        if ($readings->isEmpty()) {
            return [];
        }

        $rows = [];

        foreach (SelfReportedVitalMeasure::cases() as $measure) {
            $ofMeasure = $readings
                ->filter(fn (PatientRecordedVital $v) => $v->measure === $measure)
                ->take(self::RECENT_PER_MEASURE);

            foreach ($ofMeasure as $reading) {
                $rows[] = [
                    $this->cell($this->recordedMeasureLabel($reading)),
                    // The model's own rendering, at the precision the measure
                    // warrants — "120/80", never "120.00/80.00".
                    $this->cell($reading->displayValue()),
                    $this->cell((string) $reading->unit),
                    $this->cell($this->date($reading->measured_at, 'd M Y, H:i')),
                    $this->cell($this->annotation($reading)),
                ];
            }
        }

        return $rows;
    }

    /**
     * The measure, carrying the state it was measured in.
     *
     * The fasting state travels with the NAME rather than in a column of its
     * own because it changes what the number means: SelfReportedVitalMeasure
     * records that applying a fasting band to a post-meal sample "would call a
     * healthy patient diabetic", and a glucose printed without it invites
     * exactly that reading. Only blood sugar carries one.
     */
    private function recordedMeasureLabel(PatientRecordedVital $reading): string
    {
        $label   = $this->measureName($reading->measure);
        $context = $this->contextLabel($reading->measurement_context);

        return $context === null ? $label : $label . ' · ' . $context;
    }

    /**
     * What the patient said about the reading — the device and their own note.
     *
     * "Omron M2 home cuff · Sitting, after 5 minutes rest" is the difference
     * between a home blood pressure worth acting on and one worth repeating,
     * and it is the patient's own words, so it is printed as given.
     */
    private function annotation(PatientRecordedVital $reading): string
    {
        $parts = array_filter([
            trim((string) $reading->device_label),
            trim((string) $reading->note),
        ], fn (string $part) => $part !== '');

        return implode(' · ', $parts);
    }

    /**
     * The context word in the patient's language, or the recorded word tidied
     * when this deployment has no translation for it.
     *
     * Falling back to the raw word rather than to nothing matters: an
     * unrecognised context is still something the patient told us about the
     * reading, and dropping it silently would hide it.
     */
    private function contextLabel(?string $context): ?string
    {
        $raw = strtolower(trim((string) $context));

        if ($raw === '') {
            return null;
        }

        $key = 'patient_writes.contexts.' . $raw;

        return Lang::has($key) ? __($key) : ucfirst(str_replace('_', ' ', $raw));
    }

    // ── Shared ───────────────────────────────────────────────────────────

    /**
     * One name per measure, wherever it was measured.
     *
     * The facility table and the patient's own log call a blood pressure the
     * same thing, because a clinician comparing the two blocks of one document
     * is entitled to assume the words mean the same thing.
     */
    private function measureName(SelfReportedVitalMeasure $measure): string
    {
        return __($measure->labelKey());
    }

    /**
     * A reading's moment as a comparable integer.
     *
     * An unparseable timestamp sorts LAST rather than first: a row whose date
     * cannot be read must not be able to present itself as the most recent
     * observation on the page.
     */
    private function sortableMoment(mixed $value): int
    {
        try {
            return Carbon::parse($value)->getTimestamp();
        } catch (\Throwable) {
            return PHP_INT_MIN;
        }
    }

    /**
     * A stored number at the precision the measure warrants, or null when the
     * column holds nothing — which keeps the measure off the page entirely
     * rather than printing a zero nobody measured.
     */
    private function number(mixed $raw, int $decimals): ?string
    {
        if ($raw === null || $raw === '' || ! is_numeric($raw)) {
            return null;
        }

        $value = (float) $raw;

        return $decimals > 0
            ? number_format($value, $decimals, '.', '')
            : (string) (int) round($value);
    }
}
