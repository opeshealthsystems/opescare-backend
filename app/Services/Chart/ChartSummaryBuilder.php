<?php

namespace App\Services\Chart;

use App\Models\AccessLog;
use App\Models\CareTeamMember;
use App\Models\ConsentGrant;
use App\Models\FamilyLink;
use App\Models\Patient;
use App\Models\ReferralCase;
use App\Models\User;
use App\Modules\Governance\Services\ConsentService;
use App\Support\BodyFigure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The Summary tab of the web chart: everything about the patient on one page,
 * EVERY section gated exactly like the tab it summarises.
 *
 * ── THE GATE ──────────────────────────────────────────────────────────────
 *
 * Each section names the access-log data category it discloses (SECTIONS).
 * A section is built only when PatientChartAccess::covers() says yes for that
 * category, which is the same answer the section's own tab gets:
 *
 *   preview scope (the patient's "keep my record behind consent" switch is on
 *   and this facility holds no grant) → identity, allergies, current medicines
 *   and nothing else, as the summary always did.
 *
 *   full scope (a grant, or the switch is off and a basis holds) → all of it.
 *
 * Every category actually shown is returned in `disclosed` and the controller
 * writes one access_logs row per category, so the patient's own access log
 * (GET /api/mobile/access-logs) lists every part of the record that was read.
 *
 * ── FACILITY LINES ───────────────────────────────────────────────────────
 *
 * Patient-reported material (pre-consult answers, check-in pain sites) and
 * the facility's own trail (referrals it is party to, its access-log rows) are
 * read for THE ACTIVE FACILITY only: a disclosure made to one facility is not
 * re-disclosed to another through this summary. Clinical history (encounters,
 * vitals, results, immunisations) is portable and crosses facilities exactly
 * as the tabs already do.
 */
final class ChartSummaryBuilder
{
    /** Days of vitals drawn in the trend chart. */
    public const TREND_DAYS = 14;

    /** section → access-log data category. */
    public const SECTIONS = [
        'allergies'     => 'allergies',
        'medicines'     => 'medications',
        'vitals'        => 'vitals',
        'labs'          => 'lab_results',
        'encounters'    => 'encounters',
        'problems'      => 'problem_list',
        'diagnoses'     => 'diagnoses',
        'referrals'     => 'referrals',
        'documents'     => 'documents',
        'immunizations' => 'immunization',
        'preconsult'    => 'patient_reported_intake',
        'pain'          => 'pain_sites',
        'care_team'     => 'care_team',
        'family'        => 'family_links',
        'access'        => 'access_log',
        'partner'       => 'partner_reported',
    ];

    public function __construct(
        private readonly PatientChartService $chart,
        private readonly PainSiteService $pain,
        private readonly ConsentService $consents,
        private readonly PartnerReadingsService $partner,
    ) {
    }

    /**
     * @return array<string, mixed>  view data + `disclosed` (list of categories)
     */
    public function build(Patient $patient, PatientChartAccess $access, string $facilityId): array
    {
        $open = fn (string $section) => $access->covers(self::SECTIONS[$section]);

        $data = [
            'open'     => [],
            'figure'   => BodyFigure::for($patient->sex, $patient->date_of_birth),
            'grant'    => $this->grant($patient, $facilityId),
        ];

        $disclosed = [];
        $mark = function (string $section) use (&$data, &$disclosed) {
            $data['open'][$section] = true;
            $disclosed[] = self::SECTIONS[$section];
        };

        // The safety minimum: open on every holding verdict.
        if ($open('allergies')) {
            $data['allergies'] = $this->chart->allergies($patient);
            $mark('allergies');
        }
        if ($open('medicines')) {
            $data['medicines'] = $this->chart->currentMedicines($patient);
            $mark('medicines');
        }

        if ($open('vitals')) {
            $rows = $this->chart->facilityVitals($patient)['rows'];
            $data['kpis']  = $this->kpis($rows);
            $data['trend'] = $this->trend($rows);
            $mark('vitals');
        }

        if ($open('labs')) {
            $data['labs'] = $this->chart->labResults($patient)->take(6)->values();
            $mark('labs');
        }

        if ($open('encounters')) {
            $all = $this->chart->encounters($patient);
            $data['encounters'] = $all->take(5)->values();
            $data['careMix']    = $this->careMix($all);
            $mark('encounters');
        }

        if ($open('problems')) {
            $data['problems'] = $this->chart->problems($patient)->take(8)->values();
            $mark('problems');
        }

        if ($open('diagnoses')) {
            $data['diagnoses'] = $this->chart->diagnoses($patient)->take(6)->values();
            $mark('diagnoses');
        }

        if ($open('referrals')) {
            $data['referrals'] = $this->referrals($patient, $facilityId);
            $mark('referrals');
        }

        if ($open('documents')) {
            $data['documents']     = $this->chart->documents($patient)->take(5)->values();
            $data['verifications'] = $this->verifications($patient);
            $mark('documents');
        }

        if ($open('immunizations')) {
            $all = $this->chart->immunizations($patient);
            $data['immunizationCount'] = $all->count();
            $data['immunizations']     = $all->take(3)->values();
            $mark('immunizations');
        }

        if ($open('preconsult')) {
            $data['preconsult'] = $this->chart->preconsult($patient, $facilityId)->first();
            $mark('preconsult');
        }

        if ($open('pain')) {
            $data['painSites'] = $this->pain->activeAt($patient->id, $facilityId);
            $mark('pain');
        }

        if ($open('care_team')) {
            $data['careTeam'] = $this->careTeam($patient);
            $mark('care_team');
        }

        if ($open('family')) {
            $data['guardians']        = $this->guardians($patient);
            $data['emergencyContact'] = $this->emergencyContact($patient);
            $mark('family');
        }

        // Partner-reported: a count and the newest few, in their own card.
        // Never passed to kpis()/trend() — those are facility readings only.
        if ($open('partner')) {
            $partner = $this->partner->forPatient($patient->id, 1, 3);
            $data['partnerReadings'] = $partner['items'];
            $data['partnerCount']    = $partner['total'];
            $mark('partner');
        }

        if ($open('access')) {
            $data['accessLog'] = $this->accessLog($patient, $facilityId);
            $mark('access');
        }

        $data['disclosed'] = $disclosed;

        return $data;
    }

    // ── consent ───────────────────────────────────────────────────────────

    /**
     * The facility's active grant, presented. Reading whether a grant exists
     * is the gate's own question, so it is shown on every holding verdict.
     *
     * @return array<string, mixed>|null
     */
    private function grant(Patient $patient, string $facilityId): ?array
    {
        $grant = $this->consents->activeGrantFor($patient->id, $facilityId);

        if (! $grant instanceof ConsentGrant) {
            return null;
        }

        $granted = $grant->created_at;
        $minutes = ($granted && $grant->expires_at) ? (int) round($granted->diffInMinutes($grant->expires_at, true)) : null;

        return [
            'by'         => (string) $grant->authorizing_actor,
            'granted_at' => $granted,
            'expires_at' => $grant->expires_at,
            'minutes'    => $minutes,
            'scope'      => array_values(array_filter((array) $grant->scope, 'is_string')),
        ];
    }

    // ── vitals ────────────────────────────────────────────────────────────

    /**
     * The newest RECORDED value of each measure, with the time it was taken.
     * A measure never recorded stays null: the tile then says so.
     *
     * @param  Collection<int, array<string, mixed>>  $rows  newest first
     * @return array<string, array{value: mixed, secondary?: mixed, at: ?Carbon}|null>
     */
    private function kpis(Collection $rows): array
    {
        // Decimal columns arrive as "39.40": show 39.4, and 128.00 as 128.
        $clean = static fn ($v) => (is_numeric($v) && str_contains((string) $v, '.'))
            ? rtrim(rtrim((string) $v, '0'), '.')
            : $v;

        $first = function (string $field, ?string $secondary = null) use ($rows, $clean): ?array {
            foreach ($rows as $row) {
                if ($row[$field] !== null && $row[$field] !== '') {
                    return [
                        'value'     => $clean($row[$field]),
                        'secondary' => $secondary ? $clean($row[$secondary]) : null,
                        'at'        => $row['recorded_at'],
                    ];
                }
            }

            return null;
        };

        return [
            'bp'          => $first('systolic_bp', 'diastolic_bp'),
            'pulse'       => $first('pulse_rate'),
            'spo2'        => $first('oxygen_saturation'),
            'temperature' => $first('temperature'),
            'weight'      => $first('weight_kg'),
            'height'      => $first('height_cm'),
        ];
    }

    /**
     * Polyline points for the last TREND_DAYS days, on one 40–200 axis.
     * Temperature is a KPI only: plotting it on a pulse axis would mean
     * inventing a scale factor.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function trend(Collection $rows): array
    {
        $end   = now();
        $start = now()->subDays(self::TREND_DAYS);

        $in = $rows
            ->filter(fn ($r) => $r['recorded_at'] instanceof \DateTimeInterface && $r['recorded_at'] >= $start)
            ->sortBy(fn ($r) => $r['recorded_at']->getTimestamp())
            ->values();

        // Plot box inside a 560 x 190 viewBox.
        [$x0, $x1, $yTop, $yBot, $vMin, $vMax] = [44, 550, 16, 160, 40, 200];
        $span = max(1, $end->getTimestamp() - $start->getTimestamp());

        $series = [];
        foreach (['systolic_bp', 'diastolic_bp', 'pulse_rate', 'oxygen_saturation'] as $field) {
            $pts = [];
            foreach ($in as $r) {
                if ($r[$field] === null || $r[$field] === '') {
                    continue;
                }
                $v = max($vMin, min($vMax, (float) $r[$field]));
                $x = $x0 + ($x1 - $x0) * (($r['recorded_at']->getTimestamp() - $start->getTimestamp()) / $span);
                $y = $yBot - ($yBot - $yTop) * (($v - $vMin) / ($vMax - $vMin));
                $pts[] = ['x' => round($x, 1), 'y' => round($y, 1), 'v' => $r[$field], 'at' => $r['recorded_at']];
            }
            if ($pts !== []) {
                $series[$field] = $pts;
            }
        }

        $ticks = [];
        foreach ([200, 160, 120, 80, 40] as $v) {
            $ticks[] = ['v' => $v, 'y' => round($yBot - ($yBot - $yTop) * (($v - $vMin) / ($vMax - $vMin)), 1)];
        }

        return [
            'series' => $series,
            'ticks'  => $ticks,
            'start'  => $start,
            'end'    => $end,
            'mid'    => $start->copy()->addDays(intdiv(self::TREND_DAYS, 2)),
            'count'  => $in->count(),
            'box'    => compact('x0', 'x1', 'yTop', 'yBot'),
        ];
    }

    // ── encounters ────────────────────────────────────────────────────────

    /**
     * Encounters in the last 12 months by type, and how many facilities they
     * span. The same rows the encounters tab lists, counted.
     *
     * @param  Collection<int, array<string, mixed>>  $encounters
     * @return array<string, mixed>
     */
    private function careMix(Collection $encounters): array
    {
        $since = now()->subMonths(12);
        $recent = $encounters->filter(fn ($e) => $e['started_at'] && $e['started_at'] >= $since);

        $byType = $recent->groupBy(fn ($e) => (string) ($e['type'] ?: 'other'))
            ->map->count()
            ->sortDesc();

        if ($byType->count() > 4) {
            $top   = $byType->take(3);
            $byType = $top->put('other', $byType->slice(3)->sum());
        }

        $total = $recent->count();
        $circ  = 2 * M_PI * 44;
        $offset = 0.0;
        $slices = [];
        $colours = ['var(--od-primary)', 'var(--od-teal)', 'var(--od-pending)', 'var(--od-sky)'];

        foreach ($byType->values() as $i => $count) {
            $len = $total > 0 ? $circ * $count / $total : 0;
            $slices[] = [
                'type'   => $byType->keys()[$i],
                'count'  => $count,
                'colour' => $colours[$i % 4],
                'dash'   => round($len, 2) . ' ' . round($circ, 2),
                'offset' => round(-$offset, 2),
            ];
            $offset += $len;
        }

        return [
            'total'      => $total,
            'facilities' => $recent->pluck('facility_name')->filter()->unique()->count(),
            'slices'     => $slices,
        ];
    }

    // ── referrals, documents ──────────────────────────────────────────────

    /** Referrals THIS facility sent or received, newest first. */
    private function referrals(Patient $patient, string $facilityId): Collection
    {
        $rows = ReferralCase::query()
            ->where('patient_id', $patient->id)
            ->where(fn ($q) => $q->where('referring_facility_id', $facilityId)->orWhere('receiving_facility_id', $facilityId))
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $names = $this->facilityNames($rows->pluck('receiving_facility_id')->merge($rows->pluck('referring_facility_id'))->filter()->unique()->all());

        return $rows->map(fn (ReferralCase $r) => [
            'specialty' => $r->receiving_specialty,
            'to'        => $names[$r->receiving_facility_id] ?? null,
            'outgoing'  => $r->referring_facility_id === $facilityId,
            'status'    => (string) $r->status,
            'urgency'   => (string) $r->urgency,
            'at'        => $r->created_at,
        ]);
    }

    /** @return array{valid: int, revoked: int} */
    private function verifications(Patient $patient): array
    {
        $counts = DB::table('document_verifications')
            ->where('patient_id', $patient->id)
            ->selectRaw('status, count(*) as n')
            ->groupBy('status')
            ->pluck('n', 'status');

        return ['valid' => (int) ($counts['valid'] ?? 0), 'revoked' => (int) ($counts['revoked'] ?? 0)];
    }

    // ── people ────────────────────────────────────────────────────────────

    private function careTeam(Patient $patient): Collection
    {
        $rows = CareTeamMember::query()
            ->where('patient_id', $patient->id)
            ->where('is_active', true)
            ->whereNull('left_at')
            ->orderByDesc('is_primary')
            ->limit(8)
            ->get();

        $users = User::whereIn('id', $rows->pluck('provider_id')->filter()->unique()->all())->pluck('name', 'id');
        $facilities = $this->facilityNames($rows->pluck('facility_id')->filter()->unique()->all());

        return $rows->map(fn (CareTeamMember $m) => [
            'name'     => $users[$m->provider_id] ?? null,
            'role'     => $m->role,
            'primary'  => (bool) $m->is_primary,
            'facility' => $facilities[$m->facility_id] ?? null,
            'is_me'    => (string) $m->provider_id === (string) auth()->id(),
        ])->filter(fn ($m) => $m['name'] !== null)->values();
    }

    private function guardians(Patient $patient): Collection
    {
        return FamilyLink::query()
            ->where('dependent_patient_id', $patient->id)
            ->where('status', FamilyLink::STATUS_ACTIVE)
            ->with('guardianUser:id,name')
            ->limit(4)
            ->get()
            ->map(fn (FamilyLink $l) => [
                'name'         => $l->guardianUser?->name,
                'relationship' => (string) $l->relationship,
                'access_level' => (string) $l->access_level,
            ])
            ->filter(fn ($g) => $g['name'] !== null)
            ->values();
    }

    /**
     * The stored emergency contact under the spellings the estate uses
     * (as MobileDoctorChartController::nextOfKin reads it). Null when nothing
     * is stored; never invented.
     *
     * @return array{name: ?string, phone: ?string, relation: ?string}|null
     */
    private function emergencyContact(Patient $patient): ?array
    {
        $raw = $patient->emergency_contact;

        if (! is_array($raw)) {
            return null;
        }

        $pick = function (array $keys) use ($raw): ?string {
            foreach ($keys as $key) {
                if (isset($raw[$key]) && is_scalar($raw[$key]) && (string) $raw[$key] !== '') {
                    return (string) $raw[$key];
                }
            }

            return null;
        };

        $contact = [
            'name'     => $pick(['name', 'full_name', 'contact_name']),
            'phone'    => $pick(['phone', 'phone_number', 'contact_phone', 'telephone']),
            'relation' => $pick(['relation', 'relationship', 'relation_to_patient']),
        ];

        return array_filter($contact) === [] ? null : $contact;
    }

    /**
     * This facility's rows of the patient's access log, newest first: the
     * same table, and the same rows, the patient reads in the app.
     */
    private function accessLog(Patient $patient, string $facilityId): Collection
    {
        $rows = AccessLog::query()
            ->where('patient_id', $patient->id)
            ->where('facility_id', $facilityId)
            ->latest()
            ->limit(6)
            ->get();

        $names = User::whereIn('id', $rows->pluck('actor_id')->filter()->unique()->all())->pluck('name', 'id');

        return $rows->map(fn (AccessLog $l) => [
            'actor'    => $names[$l->actor_id] ?? null,
            'is_me'    => (string) $l->actor_id === (string) auth()->id(),
            'category' => (string) $l->data_category,
            'type'     => (string) $l->access_type,
            'at'       => $l->created_at,
        ]);
    }

    /** @param array<int, string> $ids @return array<string, string> */
    private function facilityNames(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $ids = array_values(array_unique($ids));
        $names = DB::table('facilities')->whereIn('id', $ids)->pluck('name', 'id')->all();
        $missing = array_values(array_diff($ids, array_keys($names)));

        if ($missing !== []) {
            $names += DB::table('care_facilities')->whereIn('id', $missing)->pluck('facility_name', 'id')->all();
        }

        return $names;
    }
}
