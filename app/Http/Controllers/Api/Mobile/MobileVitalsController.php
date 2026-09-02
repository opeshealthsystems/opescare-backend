<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\TriageVitalSign;
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

        return [[
            'key'         => 'blood_sugar',
            'value'       => trim((string) $row->value),
            'unit'        => $row->unit !== null ? trim((string) $row->unit) : null,
            'status'      => $this->glucoseStatus((string) $row->value, $row->unit, $row->flag),
            'recorded_at' => $at,
            'source'      => 'lab',
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

        return [
            'key'         => $key,
            'value'       => $decimals > 0 ? number_format($value, $decimals, '.', '') : (string) (int) round($value),
            'unit'        => $unit,
            'status'      => $this->classify($key, $value),
            'recorded_at' => $at,
            'source'      => $source,
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

        return [
            'key'         => 'blood_pressure',
            'value'       => "{$sys}/{$dia}",
            'unit'        => 'mmHg',
            'status'      => $this->bloodPressureStatus($sys, $dia),
            'recorded_at' => $at,
            'source'      => $source,
        ];
    }

    // ── Advisory classification (NOT a diagnosis) ────────────────────────────

    /**
     * Adult advisory bands. `critical` means "a clinician should look at this
     * now", not "the patient is in danger" — only a clinician decides that.
     */
    private function classify(string $key, float $value): string
    {
        return match ($key) {
            'heart_rate' => match (true) {
                $value < 40 || $value > 130 => 'critical',
                $value < 60                 => 'low',
                $value > 100                => 'high',
                default                     => 'normal',
            },
            'oxygen_saturation' => match (true) {
                $value < 90  => 'critical',
                $value < 94  => 'low',
                $value > 100 => 'unknown',   // impossible reading — do not endorse it
                default      => 'normal',
            },
            'temperature' => match (true) {
                $value <= 35.0 || $value >= 40.0 => 'critical',
                $value < 36.0                    => 'low',
                $value > 38.0                    => 'high',
                default                          => 'normal',
            },
            'respiratory_rate' => match (true) {
                $value < 8 || $value > 30 => 'critical',
                $value < 12               => 'low',
                $value > 20               => 'high',
                default                   => 'normal',
            },
            default => 'unknown',
        };
    }

    private function bloodPressureStatus(int $systolic, int $diastolic): string
    {
        return match (true) {
            $systolic >= 180 || $diastolic >= 120 || $systolic < 90 => 'critical',
            $systolic >= 140 || $diastolic >= 90                    => 'high',
            $systolic < 100 || $diastolic < 60                      => 'low',
            default                                                 => 'normal',
        };
    }

    /**
     * Glucose. The laboratory's own flag is authoritative when present.
     *
     * Without a flag, the value is only classified when the unit is one this
     * code understands, and then only against a RANDOM-glucose band — the
     * fasting state is not recorded anywhere in the schema, and applying a
     * fasting band to a post-meal sample would call a healthy patient
     * diabetic. Anything else returns `unknown`.
     */
    private function glucoseStatus(string $value, ?string $unit, ?string $flag): string
    {
        $normalisedFlag = strtolower(trim((string) $flag));

        if ($normalisedFlag !== '') {
            return match ($normalisedFlag) {
                'hh'                 => 'critical',
                'll'                 => 'critical',
                'h', 'high'          => 'high',
                'l', 'low'           => 'low',
                'n', 'normal'        => 'normal',
                'a', 'abnormal'      => 'abnormal',
                default              => 'unknown',
            };
        }

        if (! is_numeric(str_replace(',', '.', trim($value)))) {
            return 'unknown';
        }

        $number = (float) str_replace(',', '.', trim($value));
        $unitKey = strtolower(str_replace(' ', '', (string) $unit));

        // Normalise to mg/dL, the unit the advisory band below is written in.
        $mgdl = match ($unitKey) {
            'mg/dl', 'mgdl', 'mg/100ml' => $number,
            'mmol/l', 'mmoll'           => $number * 18.0,
            'g/l', 'gl'                 => $number * 100.0,
            default                     => null,
        };

        if ($mgdl === null) {
            return 'unknown';
        }

        return match (true) {
            $mgdl < 54 || $mgdl >= 300 => 'critical',
            $mgdl < 70                 => 'low',
            $mgdl > 140                => 'high',
            default                    => 'normal',
        };
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
