<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\TriageVitalSign;
use App\Support\VitalBandResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Mobile Patient API — Health Vitals.
 *
 * NOTHING NEW IS STORED HERE. The platform already persists vital signs in two
 * places and this controller only reads them:
 *
 *   1. `triage_vital_signs` (App\Models\TriageVitalSign) — carries `patient_id`
 *      and `recorded_at` directly. Written by the OpesCareLite offline-sync
 *      path (App\Modules\OpesCareLite\Services\OpesCareLiteService).
 *   2. `vital_signs` (App\Models\VitalSign) — the live triage write path
 *      (App\Modules\Triage\Services\TriageService). It has NO patient column,
 *      so the patient is reached through `triage_records -> visits.patient_id`,
 *      and it has no `recorded_at`, so `created_at` is the honest reading time.
 *
 * Blood sugar has no vitals column anywhere in the schema; the only real
 * source is a published laboratory result (`lab_results`), so that is where it
 * is read from — including the laboratory's own `flag`, which is a better
 * interpretation than any range this controller could hardcode.
 *
 * THE BANDS ARE NOT THIS FILE'S TO DECIDE. Every `status` word below comes from
 * App\Support\VitalBandResolver and from nowhere else. The bands used to live
 * here as private methods reachable by nothing, so the Health Passport's vitals
 * page — reading the same two tables for the same patient — could show no
 * interpretation at all rather than copy them and become a second,
 * independently drifting reader. That is the exact failure
 * App\Support\ResultFlagResolver was created to close for laboratory results.
 * This controller now asks the shared authority and prints its answer; it
 * performs no comparison of its own, anywhere.
 *
 * Clinical honesty rules this endpoint obeys:
 *   - Every measure carries its OWN `recorded_at`. A vital taken three weeks
 *     ago is never presented as if it were taken this morning.
 *   - A measure that has never been recorded is ABSENT from the payload. It is
 *     never zero-filled, never averaged, never carried forward.
 *   - `status` is advisory only (`normal|low|high|critical|abnormal|unknown`).
 *     It flags a value for a human to look at; it is not a diagnosis, and
 *     `unknown` is returned rather than a guess whenever the unit or the
 *     clinical context (fasting vs random glucose, adult vs paediatric) makes
 *     a range unsafe to apply.
 *   - `status_source` travels WITH the status, because a band this platform
 *     derived must never be presented as a laboratory's or a clinician's own
 *     verdict. `adult_bands` says this platform derived it from ADULT bands —
 *     so a child's reading is banded as an adult's and the payload admits it
 *     rather than hiding it; `lab` says a laboratory recorded the flag itself;
 *     and it is null when the status is `unknown`, because nobody said
 *     anything.
 *
 * Isolation: `patient_id` comes only from `$request->attributes`, set by the
 * AuthenticateMobilePatient middleware from the bearer token. It is never read
 * from route, query or body input, so one patient can never address another's
 * vitals.
 */
class MobileVitalsController extends Controller
{
    /** How many recent rows per source are scanned for the newest non-null value. */
    private const SCAN_LIMIT = 50;

    /**
     * GET /api/mobile/vitals/latest
     *
     * The most recent reading of each measure the patient actually has, newest
     * first. Returns an empty `measures` array (not zeros) when there is none.
     */
    public function latest(Request $request): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);

        /** @var array<string, array<string, mixed>> $measures */
        $measures = [];

        foreach ($this->fromTriageVitalSigns($patientId) as $measure) {
            $this->keepNewest($measures, $measure);
        }

        foreach ($this->fromLegacyVitalSigns($patientId) as $measure) {
            $this->keepNewest($measures, $measure);
        }

        foreach ($this->fromLabResults($patientId) as $measure) {
            $this->keepNewest($measures, $measure);
        }

        // Stable clinical ordering — the card must not reshuffle between loads.
        $order = [
            'heart_rate'        => 0,
            'blood_pressure'    => 1,
            'blood_sugar'       => 2,
            'oxygen_saturation' => 3,
            'temperature'       => 4,
            'respiratory_rate'  => 5,
        ];
        uasort($measures, fn ($a, $b) => ($order[$a['key']] ?? 99) <=> ($order[$b['key']] ?? 99));

        $recordedAt = collect($measures)
            ->pluck('recorded_at')
            ->filter()
            ->sort()
            ->last();

        return response()->json([
            'data' => [
                'measures'    => array_values($measures),
                'recorded_at' => $recordedAt,
            ],
            'meta' => [
                'count' => count($measures),
            ],
        ]);
    }

    // ── Sources ──────────────────────────────────────────────────────────────

    /**
     * `triage_vital_signs` — patient-scoped, carries a real `recorded_at`.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fromTriageVitalSigns(string $patientId): array
    {
        $rows = TriageVitalSign::query()
            ->where('patient_id', $patientId)
            ->orderByDesc('recorded_at')
            ->limit(self::SCAN_LIMIT)
            ->get();

        $out = [];

        foreach ($rows as $row) {
            $at = optional($row->recorded_at)->toIso8601String() ?? optional($row->created_at)->toIso8601String();
            if ($at === null) {
                continue;
            }

            $out[] = $this->numeric('heart_rate', $row->pulse_rate, 'bpm', $at, 'vitals');
            $out[] = $this->bloodPressure($row->systolic_bp, $row->diastolic_bp, $at, 'vitals');
            $out[] = $this->numeric('oxygen_saturation', $row->oxygen_saturation, '%', $at, 'vitals');
            $out[] = $this->numeric('temperature', $row->temperature, '°C', $at, 'vitals', 1);
            $out[] = $this->numeric('respiratory_rate', $row->respiratory_rate, '/min', $at, 'vitals');
        }

        return array_values(array_filter($out));
    }

    /**
     * `vital_signs` — the live triage write path. No patient column, so the
     * patient is reached through triage_records -> visits, and no recorded_at,
     * so `created_at` is used and reported as the reading time.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fromLegacyVitalSigns(string $patientId): array
    {
        $rows = DB::table('vital_signs as vs')
            ->join('triage_records as tr', 'tr.id', '=', 'vs.triage_record_id')
            ->join('visits as v', 'v.id', '=', 'tr.visit_id')
            ->where('v.patient_id', $patientId)
            ->orderByDesc('vs.created_at')
            ->limit(self::SCAN_LIMIT)
            ->get([
                'vs.created_at',
                'vs.pulse',
                'vs.blood_pressure_systolic',
                'vs.blood_pressure_diastolic',
                'vs.oxygen_saturation',
                'vs.temperature',
                'vs.respiratory_rate',
            ]);

        $out = [];

        foreach ($rows as $row) {
            if ($row->created_at === null) {
                continue;
            }
            $at = Carbon::parse($row->created_at)->toIso8601String();

            $out[] = $this->numeric('heart_rate', $row->pulse, 'bpm', $at, 'vitals');
            $out[] = $this->bloodPressure($row->blood_pressure_systolic, $row->blood_pressure_diastolic, $at, 'vitals');
            $out[] = $this->numeric('oxygen_saturation', $row->oxygen_saturation, '%', $at, 'vitals');
            $out[] = $this->numeric('temperature', $row->temperature, '°C', $at, 'vitals', 1);
            $out[] = $this->numeric('respiratory_rate', $row->respiratory_rate, '/min', $at, 'vitals');
        }

        return array_values(array_filter($out));
    }

    /**
     * Blood sugar from a published laboratory result. The laboratory's own
     * `flag` wins over any range this code could invent; the unit-aware
     * fallback below is only used when the lab published no flag.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fromLabResults(string $patientId): array
    {
        $row = DB::table('lab_results')
            ->where('patient_id', $patientId)
            ->where(function ($q) {
                foreach (['%glucose%', '%glycemia%', '%glycémie%', '%blood sugar%', '%glyc%mie%'] as $pattern) {
                    $q->orWhere('parameter_name', 'ilike', $pattern);
                }
            })
            ->orderByDesc('resulted_at')
            ->first(['value', 'unit', 'flag', 'resulted_at']);

        if ($row === null || $row->value === null || trim((string) $row->value) === '') {
            return [];
        }

        $at = $row->resulted_at ? Carbon::parse($row->resulted_at)->toIso8601String() : null;
        if ($at === null) {
            return [];
        }

        // The resolver holds the precedence: the bench's own flag first, then —
        // and only then — the adult random-glucose band, unit-aware.
        $verdict = VitalBandResolver::forLabGlucose($row->flag, (string) $row->value, $row->unit);

        return [[
            'key'           => 'blood_sugar',
            'value'         => trim((string) $row->value),
            'unit'          => $row->unit !== null ? trim((string) $row->unit) : null,
            'status'        => $verdict['band'],
            'status_source' => $verdict['source'],
            'recorded_at'   => $at,
            'source'        => 'lab',
        ]];
    }

    // ── Shaping ──────────────────────────────────────────────────────────────

    /**
     * Keep the newest reading per measure key. Sources are scanned in an
     * arbitrary order, so the timestamp — never the source — decides.
     *
     * @param  array<string, array<string, mixed>>  $measures
     * @param  array<string, mixed>  $candidate
     */
    private function keepNewest(array &$measures, array $candidate): void
    {
        $key = $candidate['key'];
        $existing = $measures[$key] ?? null;

        if ($existing === null || strcmp($candidate['recorded_at'], $existing['recorded_at']) > 0) {
            $measures[$key] = $candidate;
        }
    }

    /**
     * One single-number measure. Returns null when the column is null so the
     * measure stays absent rather than being zero-filled.
     *
     * @return array<string, mixed>|null
     */
    private function numeric(string $key, mixed $raw, string $unit, string $at, string $source, int $decimals = 0): ?array
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $value = (float) $raw;

        // The unit is handed over, not assumed: the resolver returns no opinion
        // rather than a band read in the wrong unit.
        $verdict = VitalBandResolver::resolve($key, $value, null, $unit);

        return [
            'key'           => $key,
            'value'         => $decimals > 0 ? number_format($value, $decimals, '.', '') : (string) (int) round($value),
            'unit'          => $unit,
            'status'        => $verdict['band'],
            'status_source' => $verdict['source'],
            'recorded_at'   => $at,
            'source'        => $source,
        ];
    }

    /**
     * Blood pressure is one measure with two numbers. Both are required — a
     * lone systolic is not a blood pressure and is not rendered as one.
     *
     * @return array<string, mixed>|null
     */
    private function bloodPressure(mixed $systolic, mixed $diastolic, string $at, string $source): ?array
    {
        if ($systolic === null || $diastolic === null) {
            return null;
        }

        $sys = (int) $systolic;
        $dia = (int) $diastolic;

        $verdict = VitalBandResolver::resolve(VitalBandResolver::BLOOD_PRESSURE, $sys, $dia, 'mmHg');

        return [
            'key'           => 'blood_pressure',
            'value'         => "{$sys}/{$dia}",
            'unit'          => 'mmHg',
            'status'        => $verdict['band'],
            'status_source' => $verdict['source'],
            'recorded_at'   => $at,
            'source'        => $source,
        ];
    }

    // ── Auth ─────────────────────────────────────────────────────────────────

    /**
     * The authenticated patient, and only ever the authenticated patient.
     * Set by AuthenticateMobilePatient from the bearer token.
     */
    private function resolvePatientId(Request $request): string
    {
        $patientId = $request->attributes->get('patient_id');

        abort_if(! is_string($patientId) || $patientId === '', 401, 'Unauthenticated.');

        return $patientId;
    }
}
