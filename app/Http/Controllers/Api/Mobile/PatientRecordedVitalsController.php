<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\SelfReportedVitalMeasure;
use App\Enums\VitalProvenance;
use App\Http\Controllers\Api\Mobile\Concerns\InteractsWithPatientWrites;
use App\Http\Controllers\Controller;
use App\Models\PatientRecordedVital;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

/**
 * Mobile Patient API — the patient's OWN vitals log (write path).
 *
 * Companion to MobileVitalsController, which is read-only over the clinical
 * tables. That controller is untouched: this one never writes to
 * `triage_vital_signs`, `vital_signs` or `lab_results`, and never will. It owns
 * `patient_recorded_vitals` and nothing else.
 *
 * ── The one rule this file exists to enforce ────────────────────────────────
 *
 * A patient-recorded vital is NOT a clinical measurement. Every reading this
 * endpoint returns carries `provenance: "self_reported"`, a human sentence
 * explaining what that means, and `clinically_verified: false`. There is no
 * response shape in which a reading arrives without its provenance, and no
 * request shape in which a caller can set it.
 *
 * ── Refusing rather than clamping ───────────────────────────────────────────
 *
 * A blood pressure of 900/600 is a typo or a broken cuff. Storing 300/200
 * instead would invent a reading the patient never took AND hide the fact that
 * their device is wrong. So the value is refused, nothing is stored, and the
 * response names the field, the value submitted, the bounds, and why — in the
 * patient's language. See the bounds and their justification on
 * App\Enums\SelfReportedVitalMeasure.
 *
 * ── Isolation ───────────────────────────────────────────────────────────────
 *
 * `patient_id` comes only from `$request->attributes`, set by
 * AuthenticateMobilePatient from the bearer token. No validator in this file
 * declares a `patient_id` key, so one supplied in the body is not overridden —
 * it is never read.
 */
class PatientRecordedVitalsController extends Controller
{
    use InteractsWithPatientWrites;

    /** Most recent readings returned by a single list call. */
    private const DEFAULT_LIMIT = 50;

    private const MAX_LIMIT = 200;

    /** Tolerated clock skew between the handset and the server. */
    private const FUTURE_SKEW_MINUTES = 5;

    /** No reading older than this is accepted — it is a mistyped year. */
    private const EARLIEST_MEASURED_AT = '2000-01-01';

    // ── Options ──────────────────────────────────────────────────────────────

    /**
     * GET /api/mobile/patient-vitals/options
     *
     * What may be logged, in what units, within what bounds. The client shows
     * the bounds so a patient is warned BEFORE they submit; the server refuses
     * regardless, because a client-side range is a courtesy and not a control.
     */
    public function options(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);

        $measures = [];

        foreach (SelfReportedVitalMeasure::cases() as $measure) {
            $units = [];
            foreach ($measure->units() as $unit) {
                [$min, $max] = $measure->primaryBounds($unit);
                $secondary = $measure->secondaryBounds($unit);

                $units[] = [
                    'unit'             => $unit,
                    'is_default'       => $unit === $measure->defaultUnit(),
                    'decimals'         => $measure->decimalsFor($unit),
                    'primary_bounds'   => ['min' => $min, 'max' => $max],
                    'secondary_bounds' => $secondary === null
                        ? null
                        : ['min' => $secondary[0], 'max' => $secondary[1]],
                ];
            }

            $contexts = [];
            foreach ($measure->contexts() as $context) {
                $contexts[] = [
                    'value' => $context,
                    'label' => $this->say("patient_writes.contexts.{$context}", [], $locale),
                ];
            }

            $measures[] = [
                'measure'          => $measure->value,
                'label'            => $this->say($measure->labelKey(), [], $locale),
                'has_two_values'   => $measure->hasSecondaryValue(),
                'primary_label'    => $this->say("patient_writes.measures.{$measure->value}.primary", [], $locale),
                'secondary_label'  => $measure->hasSecondaryValue()
                    ? $this->say("patient_writes.measures.{$measure->value}.secondary", [], $locale)
                    : null,
                'units'            => $units,
                'contexts'         => $contexts,
            ];
        }

        return response()->json([
            'data' => [
                'measures'   => $measures,
                'provenance' => $this->provenancePayload(VitalProvenance::SelfReported, $locale),
            ],
        ]);
    }

    // ── Read ─────────────────────────────────────────────────────────────────

    /**
     * GET /api/mobile/patient-vitals
     *
     * The patient's own log, newest reading first. Voided entries are excluded
     * but counted, so a retraction is visible as a fact rather than a silent
     * disappearance.
     *
     * This returns SELF-REPORTED readings only. Facility measurements are a
     * different provenance from a different table and are served by
     * GET /api/mobile/vitals/latest; the client shows them in a separate,
     * visually distinct section rather than interleaving the two.
     */
    public function index(Request $request): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        $locale = $this->resolveLocale($request);

        $validator = Validator::make($request->query(), [
            'measure' => ['nullable', 'string', 'in:' . implode(',', SelfReportedVitalMeasure::values())],
            'limit'   => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_LIMIT],
        ]);

        if ($validator->fails()) {
            return $this->refuse(
                $this->say('patient_writes.errors.invalid_filter', [], $locale),
                'INVALID_FILTER',
                $this->rejectionsFromValidator($validator->errors()->toArray()),
            );
        }

        $limit = (int) ($request->query('limit') ?? self::DEFAULT_LIMIT);
        $measure = $request->query('measure');

        $query = PatientRecordedVital::query()
            ->forPatient($patientId)
            ->active()
            ->orderByDesc('measured_at')
            ->orderByDesc('created_at')
            ->limit($limit);

        if (is_string($measure) && $measure !== '') {
            $query->where('measure', $measure);
        }

        $readings = $query->get();

        $voidedCount = PatientRecordedVital::query()
            ->forPatient($patientId)
            ->whereNotNull('voided_at')
            ->count();

        return response()->json([
            'data' => $readings->map(fn (PatientRecordedVital $r) => $this->payload($r, $locale))->all(),
            'meta' => [
                'count'        => $readings->count(),
                'voided_count' => $voidedCount,
                // Repeated on the envelope as well as on every reading: a
                // client that renders a list header cannot claim these are
                // clinical values without ignoring both.
                'provenance'   => $this->provenancePayload(VitalProvenance::SelfReported, $locale),
            ],
        ]);
    }

    // ── Write ────────────────────────────────────────────────────────────────

    /**
     * POST /api/mobile/patient-vitals
     *
     * Log one reading the patient took themselves.
     *
     * Body: measure, then either `value` (weight / temperature / blood sugar)
     * or `systolic` + `diastolic` (blood pressure); optional `unit`,
     * `measured_at`, `measurement_context`, `device_label`, `note`.
     *
     * Any `patient_id` in the body is ignored — it is not declared by the
     * validator and never read; the patient is the token's patient.
     */
    public function store(Request $request): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        $locale = $this->resolveLocale($request);

        // ── Step 1: is this a shape we understand at all? ────────────────────
        $shape = Validator::make($request->all(), [
            'measure'             => ['required', 'string', 'in:' . implode(',', SelfReportedVitalMeasure::values())],
            'unit'                => ['nullable', 'string', 'max:16'],
            'measured_at'         => ['nullable', 'date'],
            'measurement_context' => ['nullable', 'string', 'max:32'],
            'device_label'        => ['nullable', 'string', 'max:120'],
            'note'                => ['nullable', 'string', 'max:500'],
            'value'               => ['nullable', 'numeric'],
            'systolic'            => ['nullable', 'numeric'],
            'diastolic'           => ['nullable', 'numeric'],
        ]);

        if ($shape->fails()) {
            return $this->refuse(
                $this->say('patient_writes.errors.reading_rejected', [], $locale),
                'INVALID_READING',
                $this->rejectionsFromValidator($shape->errors()->toArray()),
            );
        }

        $measure = SelfReportedVitalMeasure::from((string) $request->input('measure'));

        // ── Step 2: the unit must be one this measure is recorded in ─────────
        $unit = (string) ($request->input('unit') ?: $measure->defaultUnit());

        if (! in_array($unit, $measure->units(), true)) {
            return $this->refuse(
                $this->say('patient_writes.errors.reading_rejected', [], $locale),
                'UNSUPPORTED_UNIT',
                [[
                    'field'       => 'unit',
                    'submitted'   => $unit,
                    'reason_code' => 'unsupported_unit',
                    'reason'      => $this->say('patient_writes.errors.unsupported_unit', [
                        'measure' => $this->say($measure->labelKey(), [], $locale),
                        'units'   => implode(', ', $measure->units()),
                    ], $locale),
                ]],
            );
        }

        // ── Step 3: the numbers this measure needs, and only those ───────────
        $missing = $this->missingValues($request, $measure, $locale);
        if ($missing !== []) {
            return $this->refuse(
                $this->say('patient_writes.errors.reading_rejected', [], $locale),
                'MISSING_VALUE',
                $missing,
            );
        }

        $primaryField = $measure->hasSecondaryValue() ? 'systolic' : 'value';
        $primary = (float) $request->input($primaryField);
        $secondary = $measure->hasSecondaryValue() ? (float) $request->input('diastolic') : null;

        // ── Step 4: refuse the physiologically impossible ────────────────────
        $rejected = $this->outOfRange($measure, $unit, $primary, $secondary, $locale);

        if ($rejected !== []) {
            return $this->refuse(
                $this->say('patient_writes.errors.out_of_range_title', [], $locale),
                'VALUE_OUT_OF_RANGE',
                $rejected,
            );
        }

        // ── Step 5: when was it actually taken? ──────────────────────────────
        $measuredAt = $this->resolveMeasuredAt($request);
        if ($measuredAt === null) {
            return $this->refuse(
                $this->say('patient_writes.errors.reading_rejected', [], $locale),
                'INVALID_MEASURED_AT',
                [[
                    'field'       => 'measured_at',
                    'submitted'   => (string) $request->input('measured_at'),
                    'reason_code' => 'not_a_real_time',
                    'reason'      => $this->say('patient_writes.errors.measured_at_range', [], $locale),
                ]],
            );
        }

        // ── Step 6: the context, where the measure has one ───────────────────
        $context = $request->input('measurement_context');
        $context = is_string($context) && $context !== '' ? $context : null;

        if ($context !== null && ! in_array($context, $measure->contexts(), true)) {
            return $this->refuse(
                $this->say('patient_writes.errors.reading_rejected', [], $locale),
                'UNSUPPORTED_CONTEXT',
                [[
                    'field'       => 'measurement_context',
                    'submitted'   => $context,
                    'reason_code' => 'unsupported_context',
                    'reason'      => $measure->contexts() === []
                        ? $this->say('patient_writes.errors.context_not_applicable', [
                            'measure' => $this->say($measure->labelKey(), [], $locale),
                        ], $locale)
                        : $this->say('patient_writes.errors.unsupported_context', [
                            'contexts' => implode(', ', $measure->contexts()),
                        ], $locale),
                ]],
            );
        }

        // ── Store. `provenance` and `source_system` are database defaults and
        //    are not fillable: this write cannot claim to be anything but
        //    self-reported, and the table's CHECK constraint agrees.
        $reading = PatientRecordedVital::create([
            'patient_id'          => $patientId,
            'measure'             => $measure->value,
            'value_primary'       => $primary,
            'value_secondary'     => $secondary,
            'unit'                => $unit,
            'measurement_context' => $context,
            'device_label'        => $request->input('device_label') ?: null,
            'note'                => $request->input('note') ?: null,
            'measured_at'         => $measuredAt,
            'entered_by_user_id'  => $this->resolvePatientUserId($request),
        ]);

        $this->audit(
            $request,
            'patient.vital.self_reported.recorded',
            'patient_recorded_vital',
            $reading->id,
            [
                'measure'     => $measure->value,
                'value'       => $reading->displayValue(),
                'unit'        => $unit,
                'context'     => $context,
                'measured_at' => $measuredAt->toIso8601String(),
                'provenance'  => VitalProvenance::SelfReported->value,
            ],
        );

        return response()->json([
            'data' => $this->payload($reading->refresh(), $locale),
        ], 201);
    }

    /**
     * POST /api/mobile/patient-vitals/{id}/void
     *
     * Retract a reading. There is deliberately no update endpoint: a mistyped
     * value is voided with a reason and re-entered, so the log shows both what
     * was said and that it was taken back — the same amend/void discipline the
     * clinical modules follow.
     */
    public function void(Request $request, string $id): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        $locale = $this->resolveLocale($request);

        $validator = Validator::make($request->all(), [
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return $this->refuse(
                $this->say('patient_writes.errors.reading_rejected', [], $locale),
                'INVALID_REASON',
                $this->rejectionsFromValidator($validator->errors()->toArray()),
            );
        }

        // Scoped to the patient in the WHERE clause, not checked afterwards:
        // another patient's id simply does not exist as far as this query is
        // concerned, and the 404 says nothing about whether it exists at all.
        $reading = PatientRecordedVital::query()
            ->forPatient($patientId)
            ->find($id);

        if ($reading === null) {
            return response()->json([
                'message'    => $this->say('patient_writes.errors.reading_not_found', [], $locale),
                'error_code' => 'READING_NOT_FOUND',
            ], 404);
        }

        if ($reading->isVoided()) {
            return response()->json([
                'message'    => $this->say('patient_writes.errors.already_voided', [], $locale),
                'error_code' => 'ALREADY_VOIDED',
                'data'       => $this->payload($reading, $locale),
            ], 409);
        }

        $reason = $request->input('reason') ?: null;
        $reading->void($reason);

        $this->audit(
            $request,
            'patient.vital.self_reported.voided',
            'patient_recorded_vital',
            $reading->id,
            ['voided_at' => $reading->voided_at?->toIso8601String()],
            $reason,
        );

        return response()->json(['data' => $this->payload($reading->refresh(), $locale)]);
    }

    // ── Shaping ──────────────────────────────────────────────────────────────

    /**
     * One reading, as the client sees it.
     *
     * `provenance` is not optional and not a flag buried in a detail view — it
     * is a first-class object on every reading, carrying the badge label, the
     * sentence that explains it, and `clinically_verified: false`.
     *
     * @return array<string, mixed>
     */
    private function payload(PatientRecordedVital $reading, string $locale): array
    {
        $measure = $reading->measure;

        return [
            'id'          => $reading->id,
            'measure'     => $measure->value,
            'label'       => $this->say($measure->labelKey(), [], $locale),
            'value'       => $reading->displayValue(),
            'primary'     => (float) $reading->value_primary,
            'secondary'   => $reading->value_secondary === null ? null : (float) $reading->value_secondary,
            'unit'        => $reading->unit,
            'context'     => $reading->measurement_context,
            'context_label' => $reading->measurement_context
                ? $this->say("patient_writes.contexts.{$reading->measurement_context}", [], $locale)
                : null,
            'device_label' => $reading->device_label,
            'note'         => $reading->note,
            'measured_at'  => $reading->measured_at?->toIso8601String(),
            'recorded_at'  => $reading->created_at?->toIso8601String(),
            'voided_at'    => $reading->voided_at?->toIso8601String(),
            'void_reason'  => $reading->void_reason,
            'provenance'   => $this->provenancePayload($reading->provenance(), $locale),
            'advisory'     => $this->advisory($reading, $locale),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function provenancePayload(VitalProvenance $provenance, string $locale): array
    {
        return [
            'value'               => $provenance->value,
            'label'               => $this->say($provenance->labelKey(), [], $locale),
            'description'         => $this->say($provenance->descriptionKey(), [], $locale),
            'clinically_verified' => $provenance->isClinicallyVerified(),
        ];
    }

    /**
     * A safety net, and nothing more.
     *
     * This is NOT a clinical grading. There are exactly two outcomes: null
     * (nothing to say) and `seek_care` — the value is outside the range most
     * people can safely wait on, so the patient is told to contact a health
     * worker. It never says "high", never names a condition, and never implies
     * the reading was interpreted by anyone.
     *
     * Grading a self-reported number the way a clinical result is graded is
     * exactly the confusion this whole feature is built to avoid, so the band
     * is deliberately coarse and one-directional.
     *
     * @return array<string, mixed>|null
     */
    private function advisory(PatientRecordedVital $reading, string $locale): ?array
    {
        $unit = (string) $reading->unit;
        $primary = (float) $reading->value_primary;
        $secondary = $reading->value_secondary === null ? null : (float) $reading->value_secondary;

        $seekCare = match ($reading->measure) {
            SelfReportedVitalMeasure::BloodPressure =>
                $primary >= 180 || ($secondary !== null && $secondary >= 120) || $primary < 90,
            SelfReportedVitalMeasure::Temperature =>
                $primary >= 39.5 || $primary <= 35.0,
            SelfReportedVitalMeasure::BloodSugar => $unit === 'mmol/L'
                ? ($primary < 3.9 || $primary >= 16.7)
                : ($primary < 70 || $primary >= 300),
            SelfReportedVitalMeasure::Weight => false,
        };

        if (! $seekCare) {
            return null;
        }

        return [
            'action'                   => 'seek_care',
            'message'                  => $this->say('patient_writes.advisory.seek_care', [], $locale),
            // Said out loud so no client can render this as a lab-style flag.
            'is_clinical_interpretation' => false,
        ];
    }

    // ── Validation helpers ───────────────────────────────────────────────────

    /**
     * The numbers this measure requires. Blood pressure needs both; a lone
     * systolic is not a blood pressure and is not stored as one.
     *
     * @return array<int, array<string, mixed>>
     */
    private function missingValues(Request $request, SelfReportedVitalMeasure $measure, string $locale): array
    {
        $required = $measure->hasSecondaryValue() ? ['systolic', 'diastolic'] : ['value'];
        $missing = [];

        foreach ($required as $field) {
            $raw = $request->input($field);
            if ($raw === null || $raw === '' || ! is_numeric($raw)) {
                $missing[] = [
                    'field'       => $field,
                    'submitted'   => is_scalar($raw) ? (string) $raw : null,
                    'reason_code' => 'missing_value',
                    'reason'      => $this->say('patient_writes.errors.missing_value', [
                        'field' => $this->say("patient_writes.fields.{$field}", [], $locale),
                    ], $locale),
                ];
            }
        }

        return $missing;
    }

    /**
     * Everything the stored record must not contain, with the reason it is
     * being refused. Nothing here clamps: an out-of-range value produces a
     * refusal, never a substituted one.
     *
     * @return array<int, array<string, mixed>>
     */
    private function outOfRange(
        SelfReportedVitalMeasure $measure,
        string $unit,
        float $primary,
        ?float $secondary,
        string $locale,
    ): array {
        $rejected = [];

        [$primaryMin, $primaryMax] = $measure->primaryBounds($unit);
        $primaryField = $measure->hasSecondaryValue() ? 'systolic' : 'value';

        if ($primary < $primaryMin || $primary > $primaryMax) {
            $rejected[] = $this->rangeRejection($primaryField, $primary, $primaryMin, $primaryMax, $unit, $locale);
        }

        $secondaryBounds = $measure->secondaryBounds($unit);

        if ($secondary !== null && $secondaryBounds !== null) {
            [$secondaryMin, $secondaryMax] = $secondaryBounds;

            if ($secondary < $secondaryMin || $secondary > $secondaryMax) {
                $rejected[] = $this->rangeRejection('diastolic', $secondary, $secondaryMin, $secondaryMax, $unit, $locale);
            }
        }

        // A diastolic at or above the systolic is not a survivable reading; it
        // is two numbers entered the wrong way round, and guessing which way
        // the patient meant them would be inventing their blood pressure.
        if ($measure->hasSecondaryValue() && $secondary !== null && $rejected === [] && $secondary >= $primary) {
            $rejected[] = [
                'field'       => 'diastolic',
                'submitted'   => $this->formatNumber($secondary, $measure->decimalsFor($unit)),
                'unit'        => $unit,
                'reason_code' => 'diastolic_not_below_systolic',
                'reason'      => $this->say('patient_writes.errors.diastolic_not_below_systolic', [], $locale),
            ];
        }

        return $rejected;
    }

    /**
     * @return array<string, mixed>
     */
    private function rangeRejection(
        string $field,
        float $submitted,
        float $min,
        float $max,
        string $unit,
        string $locale,
    ): array {
        $decimals = $this->boundDecimals($min, $max);

        return [
            'field'       => $field,
            'submitted'   => $this->formatNumber($submitted, $decimals),
            'min'         => $min,
            'max'         => $max,
            'unit'        => $unit,
            'reason_code' => 'out_of_range',
            'reason'      => $this->say('patient_writes.errors.out_of_range', [
                'field'     => $this->say("patient_writes.fields.{$field}", [], $locale),
                'min'       => $this->formatNumber($min, $decimals),
                'max'       => $this->formatNumber($max, $decimals),
                'unit'      => $unit,
                'submitted' => $this->formatNumber($submitted, $decimals),
            ], $locale),
        ];
    }

    /**
     * Laravel's own validator messages, reshaped into the same `rejected`
     * envelope so a client has one error format to render, not two.
     *
     * @param  array<string, array<int, string>>  $errors
     * @return array<int, array<string, mixed>>
     */
    private function rejectionsFromValidator(array $errors): array
    {
        $rejected = [];

        foreach ($errors as $field => $messages) {
            $rejected[] = [
                'field'       => $field,
                'reason_code' => 'invalid',
                'reason'      => (string) ($messages[0] ?? ''),
            ];
        }

        return $rejected;
    }

    /**
     * When the reading was taken. Defaults to now; never accepts the future
     * (beyond handset clock skew) and never a year that is obviously a typo.
     */
    private function resolveMeasuredAt(Request $request): ?Carbon
    {
        $raw = $request->input('measured_at');

        if ($raw === null || $raw === '') {
            return Carbon::now();
        }

        try {
            $at = Carbon::parse((string) $raw);
        } catch (\Throwable) {
            return null;
        }

        if ($at->greaterThan(Carbon::now()->addMinutes(self::FUTURE_SKEW_MINUTES))) {
            return null;
        }

        if ($at->lessThan(Carbon::parse(self::EARLIEST_MEASURED_AT))) {
            return null;
        }

        return $at;
    }

    private function boundDecimals(float $min, float $max): int
    {
        return (floor($min) === $min && floor($max) === $max) ? 0 : 1;
    }

    private function formatNumber(float $value, int $decimals): string
    {
        return number_format($value, $decimals, '.', '');
    }
}
