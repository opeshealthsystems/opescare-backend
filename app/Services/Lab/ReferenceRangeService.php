<?php

namespace App\Services\Lab;

use App\Models\ReferenceRange;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReferenceRangeService
{
    /**
     * Find the best-matching reference range for a test result.
     * Priority: facility-specific > platform default, specific age_group > 'all', specific sex > 'all'.
     */
    public function lookup(
        string $testCode,
        ?string $facilityId = null,
        ?Carbon $dateOfBirth = null,
        ?string $sex = null
    ): ?ReferenceRange {
        $ageGroup = $dateOfBirth ? $this->ageGroup($dateOfBirth) : 'all';
        $sexNorm  = $sex ? strtolower($sex) : 'all';

        // Build priority-ordered candidates
        $candidates = ReferenceRange::where('test_code', strtoupper($testCode))
            ->where('is_active', true)
            ->where(function ($q) use ($facilityId) {
                $q->whereNull('facility_id');
                if ($facilityId) {
                    $q->orWhere('facility_id', $facilityId);
                }
            })
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        // Score each candidate — higher is better
        $scored = $candidates->map(function (ReferenceRange $r) use ($facilityId, $ageGroup, $sexNorm) {
            $score = 0;
            if ($r->facility_id === $facilityId && $facilityId !== null) $score += 100;
            if ($r->age_group === $ageGroup) $score += 10;
            if ($r->sex === $sexNorm) $score += 5;
            return ['range' => $r, 'score' => $score];
        })->sortByDesc('score');

        return $scored->first()['range'];
    }

    /**
     * Apply a reference range to a numeric value, returning flag code and label.
     */
    public function apply(ReferenceRange $range, float $value): array
    {
        $flag = $range->flagCode($value);
        return [
            'flag'            => $flag,
            'flag_label'      => $this->flagLabel($flag),
            'is_critical'     => in_array($flag, ['HH', 'LL'], true),
            'is_abnormal'     => $flag !== null,
            'reference_range' => $this->formatRange($range),
        ];
    }

    /**
     * Create or update a reference range entry.
     */
    public function upsert(array $data): ReferenceRange
    {
        $existing = ReferenceRange::where('test_code', strtoupper($data['test_code']))
            ->where('age_group', $data['age_group'] ?? 'all')
            ->where('sex', $data['sex'] ?? 'all')
            ->where('facility_id', $data['facility_id'] ?? null)
            ->first();

        if ($existing) {
            $existing->update($data);
            return $existing->fresh();
        }

        $data['test_code'] = strtoupper($data['test_code']);
        return ReferenceRange::create($data);
    }

    /**
     * Get all reference ranges for a facility (platform + facility-specific).
     */
    public function getForFacility(?string $facilityId): Collection
    {
        return ReferenceRange::where('is_active', true)
            ->where(function ($q) use ($facilityId) {
                $q->whereNull('facility_id');
                if ($facilityId) {
                    $q->orWhere('facility_id', $facilityId);
                }
            })
            ->orderBy('test_name')
            ->get();
    }

    private function ageGroup(Carbon $dob): string
    {
        $days   = now()->diffInDays($dob);
        $months = now()->diffInMonths($dob);
        $years  = now()->diffInYears($dob);

        if ($days <= 28)      return 'neonate';
        if ($months <= 12)    return 'infant';
        if ($years <= 12)     return 'child';
        if ($years <= 18)     return 'adolescent';
        if ($years <= 64)     return 'adult';
        return 'geriatric';
    }

    private function flagLabel(?string $flag): string
    {
        return match ($flag) {
            'HH'    => 'Critical High',
            'H'     => 'High',
            'L'     => 'Low',
            'LL'    => 'Critical Low',
            default => 'Normal',
        };
    }

    private function formatRange(ReferenceRange $r): string
    {
        if ($r->normal_low !== null && $r->normal_high !== null) {
            return "{$r->normal_low} – {$r->normal_high} {$r->unit}";
        }
        if ($r->normal_low !== null) return "≥ {$r->normal_low} {$r->unit}";
        if ($r->normal_high !== null) return "≤ {$r->normal_high} {$r->unit}";
        return "No range defined";
    }
}
