<?php

namespace App\Services\Chart;

use App\Models\IntegrationClient;
use App\Models\PartnerDiagnosticReport;
use App\Models\PartnerImmunization;
use App\Models\PartnerObservation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Partner-reported readings: what an external system pushed in over FHIR R4
 * (`fhir_partner_observations`, `fhir_partner_diagnostic_reports`,
 * `fhir_partner_immunizations`), read for display.
 *
 * ── OWNER DECISION (2026-10-22) ──────────────────────────────────────────
 * Shown to the clinician (chart tab "Partner-reported") and to the patient
 * (GET /api/mobile/records/partner-readings), ALWAYS labelled with the
 * sending facility and the sending system, and ALWAYS apart from readings
 * taken at a facility. Nothing here is merged into PatientChartService's
 * facility vitals / labs / immunisations, and nothing here is fed to a
 * trend chart or to this platform's band or flag resolvers: the partner's
 * own interpretation and reference range are passed through as the
 * partner's claim, never re-judged.
 *
 * ── WHERE THE LABELS COME FROM ───────────────────────────────────────────
 *   sending facility  facility_id written at ingest from the partner token
 *                     (FhirIngestGuard), never from the posted resource.
 *   sending system    `source_system`, written at ingest as the integration
 *                     client's name (FhirIngestController::sourceSystem), or
 *                     the client id when the client had no name. When the
 *                     stored value is only the client id, the client's
 *                     current name is looked up as a fallback.
 *
 * Superseded (`amended`) and retracted (`entered-in-error`) rows are history
 * and are not listed. Every method is a read.
 */
final class PartnerReadingsService
{
    public const PROVENANCE = 'partner_reported';

    public const DEFAULT_PER_PAGE = 20;
    public const MAX_PER_PAGE = 50;
    /** Deepest page served: bounds the merged read at MAX_PAGE × per_page per table. */
    public const MAX_PAGE = 20;

    private const RETIRED_STATUSES = ['entered-in-error', 'amended'];

    /**
     * Newest first across the three partner tables, bounded.
     *
     * @return array{items: Collection<int,array<string,mixed>>, page: int, per_page: int, total: int, has_more: bool}
     */
    public function forPatient(string $patientId, int $page = 1, int $perPage = self::DEFAULT_PER_PAGE): array
    {
        $page    = max(1, min(self::MAX_PAGE, $page));
        $perPage = max(1, min(self::MAX_PER_PAGE, $perPage));
        $window  = $page * $perPage;

        // Partner rows are cross-facility by nature: the patient's record is
        // portable, and the facility on each row is the SENDER, shown as such.
        $observations = PartnerObservation::acrossFacilities()
            ->where('patient_id', $patientId)
            ->whereNotIn('status', self::RETIRED_STATUSES)
            ->orderByDesc('effective_at')->orderByDesc('created_at')
            ->limit($window)->get();

        $reports = PartnerDiagnosticReport::acrossFacilities()
            ->where('patient_id', $patientId)
            ->whereNotIn('status', self::RETIRED_STATUSES)
            ->orderByDesc('effective_at')->orderByDesc('created_at')
            ->limit($window)->get();

        $immunizations = PartnerImmunization::acrossFacilities()
            ->where('patient_id', $patientId)
            ->whereNotIn('status', self::RETIRED_STATUSES)
            ->orderByDesc('occurrence_at')->orderByDesc('created_at')
            ->limit($window)->get();

        $total = PartnerObservation::acrossFacilities()->where('patient_id', $patientId)->whereNotIn('status', self::RETIRED_STATUSES)->count()
            + PartnerDiagnosticReport::acrossFacilities()->where('patient_id', $patientId)->whereNotIn('status', self::RETIRED_STATUSES)->count()
            + PartnerImmunization::acrossFacilities()->where('patient_id', $patientId)->whereNotIn('status', self::RETIRED_STATUSES)->count();

        $all = $observations->concat($reports)->concat($immunizations);

        $facilities = $this->facilityNames($all->pluck('facility_id')->filter()->unique()->values()->all());
        $clients    = $this->clientNames($all->pluck('integration_client_id')->filter()->unique()->values()->all());

        $rows = collect()
            ->concat($observations->map(fn (PartnerObservation $o) => $this->observation($o, $facilities, $clients)))
            ->concat($reports->map(fn (PartnerDiagnosticReport $r) => $this->report($r, $facilities, $clients)))
            ->concat($immunizations->map(fn (PartnerImmunization $i) => $this->immunization($i, $facilities, $clients)))
            ->sortByDesc(fn (array $row) => ($row['effective_at'] ?? '') . '|' . ($row['received_at'] ?? ''))
            ->values();

        $items = $rows->slice(($page - 1) * $perPage, $perPage)->values();

        return [
            'items'    => $items,
            'page'     => $page,
            'per_page' => $perPage,
            'total'    => $total,
            'has_more' => $page < self::MAX_PAGE && $total > $page * $perPage,
        ];
    }

    // ── presenters ────────────────────────────────────────────────────────

    /** @param array<string,string> $facilities @param array<string,string> $clients */
    private function observation(PartnerObservation $o, array $facilities, array $clients): array
    {
        return $this->labelled($o, 'observation', [
            'kind'            => $o->observation_kind,
            'display'         => $o->loinc_display ?: $o->loinc_code,
            'code'            => $o->loinc_code,
            'code_system'     => 'LOINC',
            'value'           => $o->value_quantity,
            'unit'            => $o->value_unit,
            'value_text'      => $o->value_string,
            'components'      => $this->components($o->components),
            'reference_range' => $o->reference_range,
            'interpretation'  => $o->partner_interpretation,
            'conclusion'      => null,
            'status'          => $o->status,
            'effective_at'    => $this->iso($o->effective_at),
        ], $facilities, $clients);
    }

    /** @param array<string,string> $facilities @param array<string,string> $clients */
    private function report(PartnerDiagnosticReport $r, array $facilities, array $clients): array
    {
        return $this->labelled($r, 'diagnostic_report', [
            'kind'            => $r->category,
            'display'         => $r->loinc_display ?: $r->loinc_code,
            'code'            => $r->loinc_code,
            'code_system'     => 'LOINC',
            'value'           => null,
            'unit'            => null,
            'value_text'      => null,
            'components'      => null,
            'reference_range' => null,
            'interpretation'  => null,
            'conclusion'      => $r->conclusion,
            'status'          => $r->status,
            'effective_at'    => $this->iso($r->effective_at),
        ], $facilities, $clients);
    }

    /** @param array<string,string> $facilities @param array<string,string> $clients */
    private function immunization(PartnerImmunization $i, array $facilities, array $clients): array
    {
        return $this->labelled($i, 'immunization', [
            'kind'            => 'immunization',
            'display'         => $i->vaccine_display ?: $i->cvx_code,
            'code'            => $i->cvx_code,
            'code_system'     => 'CVX',
            'value'           => $i->dose_quantity !== null ? (float) $i->dose_quantity : null,
            'unit'            => $i->dose_unit,
            'value_text'      => $i->lot_number ? 'Lot ' . $i->lot_number : null,
            'components'      => null,
            'reference_range' => null,
            'interpretation'  => null,
            'conclusion'      => null,
            'status'          => $i->status,
            'effective_at'    => $this->iso($i->occurrence_at),
        ], $facilities, $clients);
    }

    /**
     * The provenance block every row carries. Not optional, not per-caller.
     *
     * @param array<string,mixed> $fields @param array<string,string> $facilities @param array<string,string> $clients
     */
    private function labelled($model, string $type, array $fields, array $facilities, array $clients): array
    {
        $clientId = (string) $model->integration_client_id;
        $stored   = (string) ($model->source_system ?? '');
        $system   = ($stored === '' || $stored === $clientId) ? ($clients[$clientId] ?? ($stored ?: $clientId)) : $stored;

        return ['id' => (string) $model->id, 'type' => $type] + $fields + [
            'received_at'      => $this->iso($model->created_at),
            'sending_facility' => [
                'id'   => (string) $model->facility_id,
                'name' => $facilities[$model->facility_id] ?? null,
            ],
            'sending_system'   => [
                'name'      => $system,
                'client_id' => $clientId,
            ],
            'provenance'       => self::PROVENANCE,
            'provenance_label' => __('partner_readings.provenance_label'),
        ];
    }

    /** Normalise stored components to the same names the row uses. */
    private function components(?array $raw): ?array
    {
        if (empty($raw)) {
            return null;
        }

        return array_values(array_map(fn ($c) => [
            'code'       => $c['loinc_code'] ?? null,
            'display'    => ($c['loinc_display'] ?? null) ?: ($c['loinc_code'] ?? null),
            'value'      => isset($c['value']) && is_numeric($c['value']) ? (float) $c['value'] : null,
            'unit'       => $c['unit'] ?? null,
            'value_text' => $c['value_string'] ?? null,
        ], array_filter($raw, 'is_array')));
    }

    private function iso($value): ?string
    {
        if ($value === null) {
            return null;
        }

        return ($value instanceof \DateTimeInterface ? Carbon::instance($value) : Carbon::parse($value))->toIso8601String();
    }

    /** @param array<int,string> $ids @return array<string,string> */
    private function facilityNames(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $names   = DB::table('facilities')->whereIn('id', $ids)->pluck('name', 'id')->all();
        $missing = array_values(array_diff($ids, array_keys($names)));

        if ($missing !== []) {
            $names += DB::table('care_facilities')->whereIn('id', $missing)->pluck('facility_name', 'id')->all();
        }

        return $names;
    }

    /** @param array<int,string> $clientIds @return array<string,string> */
    private function clientNames(array $clientIds): array
    {
        if ($clientIds === []) {
            return [];
        }

        return IntegrationClient::query()
            ->whereIn('client_id', $clientIds)
            ->whereNotNull('name')
            ->pluck('name', 'client_id')
            ->all();
    }
}
