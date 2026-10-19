<?php

namespace App\Services\Chart;

use App\Enums\PainRegion;
use App\Enums\PainSiteSource;
use App\Enums\PainTrend;
use App\Models\PatientPainSite;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * The one writer and reader of `patient_pain_sites`.
 *
 * Callers pass the facility they resolved from the session (desk) or from the
 * appointment the authenticated patient owns (check-in), never one named by
 * the request. Nothing here deletes: a site is resolved.
 */
final class PainSiteService
{
    /** At most this many sites in one submission. */
    public const MAX_PER_SUBMISSION = 8;

    /**
     * Validation rules for one site under $prefix (e.g. 'pain_sites.*.').
     *
     * @return array<string, mixed>
     */
    public static function rules(string $prefix = '', bool $required = true): array
    {
        $req = $required ? 'required' : 'nullable';

        return [
            $prefix . 'region'   => [$req, 'string', Rule::in(PainRegion::values())],
            $prefix . 'severity' => [$required ? 'required' : 'required_with:' . $prefix . 'region', 'nullable', 'integer', 'min:0', 'max:10'],
            $prefix . 'onset_at' => ['nullable', 'date', 'before_or_equal:now'],
            $prefix . 'trend'    => ['nullable', 'string', Rule::in(PainTrend::values())],
            $prefix . 'note'     => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Rows from a repeatable form where every field may be blank: a row with no
     * region is "not filled in", not an error.
     *
     * @param  array<int, mixed>|null  $rows
     * @return list<array<string, mixed>>
     */
    public static function filledRows(?array $rows): array
    {
        return array_values(array_filter(
            (array) $rows,
            fn ($row) => is_array($row) && trim((string) ($row['region'] ?? '')) !== ''
        ));
    }

    /**
     * @param  array<string, mixed>  $site   region, severity, onset_at?, trend?, note?
     * @param  array<string, ?string>  $links  encounter_id?, triage_record_id?, appointment_id?
     */
    public function record(
        string $patientId,
        string $facilityId,
        PainSiteSource $source,
        ?string $recordedBy,
        array $site,
        array $links = [],
    ): PatientPainSite {
        $note = trim((string) ($site['note'] ?? ''));

        return PatientPainSite::create([
            'patient_id'          => $patientId,
            'facility_id'         => $facilityId,
            'encounter_id'        => $links['encounter_id'] ?? null,
            'triage_record_id'    => $links['triage_record_id'] ?? null,
            'appointment_id'      => $links['appointment_id'] ?? null,
            'region'              => PainRegion::from((string) $site['region']),
            'severity'            => (int) $site['severity'],
            'onset_at'            => ! empty($site['onset_at']) ? Carbon::parse($site['onset_at']) : null,
            'trend'               => ! empty($site['trend']) ? PainTrend::from((string) $site['trend']) : null,
            'note'                => $note !== '' ? $note : null,
            'recorded_by_user_id' => $recordedBy,
            'source'              => $source,
        ]);
    }

    public function resolve(PatientPainSite $site, ?string $userId): PatientPainSite
    {
        if ($site->resolved_at === null) {
            $site->forceFill(['resolved_at' => now(), 'resolved_by_user_id' => $userId])->save();
        }

        return $site;
    }

    /**
     * Unresolved sites reported to THIS facility, worst first.
     *
     * @return Collection<int, PatientPainSite>
     */
    public function activeAt(string $patientId, string $facilityId): Collection
    {
        return PatientPainSite::query()
            ->where('patient_id', $patientId)
            ->where('facility_id', $facilityId)
            ->whereNull('resolved_at')
            ->orderByDesc('severity')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
    }

    /** @return array<string, mixed> */
    public function present(PatientPainSite $site): array
    {
        return [
            'id'           => $site->id,
            'region'       => $site->region->value,
            'region_label' => $site->region->label(),
            'severity'     => $site->severity,
            'onset_at'     => $site->onset_at?->toIso8601String(),
            'trend'        => $site->trend?->value,
            'trend_label'  => $site->trend?->label(),
            'note'         => $site->note,
            'source'       => $site->source->value,
            'source_label' => $site->source->label(),
            'resolved_at'  => $site->resolved_at?->toIso8601String(),
            'recorded_at'  => $site->created_at?->toIso8601String(),
        ];
    }
}
