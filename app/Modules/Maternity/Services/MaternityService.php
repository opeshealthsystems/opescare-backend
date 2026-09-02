<?php
namespace App\Modules\Maternity\Services;

use App\Models\AntenatalVisit;
use App\Models\DeliveryRecord;
use App\Models\PostnatalVisit;
use App\Models\PregnancyRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MaternityService
{
    /** Recommended ANC visit schedule in gestational weeks (WHO-aligned) */
    private const RECOMMENDED_VISIT_WEEKS = [4, 8, 12, 16, 20, 24, 28, 30, 32, 34, 36, 38, 40];

    public function registerPregnancy(array $data): PregnancyRecord
    {
        return DB::transaction(function () use ($data) {
            return PregnancyRecord::create($data);
        });
    }

    public function recordAntenatalVisit(string $pregnancyRecordId, array $data): AntenatalVisit
    {
        $record = PregnancyRecord::findOrFail($pregnancyRecordId);
        $data['pregnancy_record_id'] = $record->id;

        $visit = AntenatalVisit::create($data);

        // Recompute high-risk flag based on new visit data
        if ($this->isHighRisk($record->refresh())) {
            $record->update(['high_risk' => true]);
        }

        return $visit;
    }

    /**
     * Record a postnatal care visit.
     *
     * MaternityController::recordPostnatalVisit() has called this since the
     * module landed, but the method was never written — so the endpoint threw
     * BadMethodCallException on every request. The controller validates and
     * supplies the whole payload (including facility_id, taken from the auth
     * middleware attributes, never the request body), so there is nothing to
     * derive here.
     *
     * Unlike an antenatal visit this does not hang off a PregnancyRecord: care
     * continues after a pregnancy record is closed, and the controller
     * deliberately keys on patient_id rather than a pregnancy id.
     */
    public function recordPostnatalVisit(array $data): PostnatalVisit
    {
        return PostnatalVisit::create($data);
    }

    public function recordDelivery(string $pregnancyRecordId, array $data): DeliveryRecord
    {
        return DB::transaction(function () use ($pregnancyRecordId, $data) {
            $record = PregnancyRecord::findOrFail($pregnancyRecordId);
            $data['pregnancy_record_id'] = $record->id;

            $delivery = DeliveryRecord::create($data);

            $status = match ($data['neonatal_outcome'] ?? 'live') {
                'stillbirth'           => 'stillbirth',
                'early_neonatal_death' => 'delivered',
                default                => 'delivered',
            };
            $record->update(['pregnancy_status' => $status]);

            return $delivery;
        });
    }

    public function getAntenatalSchedule(string $pregnancyRecordId): array
    {
        PregnancyRecord::findOrFail($pregnancyRecordId);
        return self::RECOMMENDED_VISIT_WEEKS;
    }

    public function isHighRisk(PregnancyRecord $record): bool
    {
        if ($record->high_risk) {
            return true;
        }

        $riskFactors = $record->risk_factors ?? [];
        if (in_array('previous_caesarean', $riskFactors, true)) {
            return true;
        }

        $patient = $record->patient;
        if ($patient && $patient->date_of_birth) {
            $age = Carbon::parse($patient->date_of_birth)->age;
            if ($age > 35) {
                return true;
            }
        }

        return $record->antenatalVisits()
            ->where(function ($q) {
                $q->where('bp_systolic', '>=', 140)
                    ->orWhere('bp_diastolic', '>=', 90)
                    ->orWhereIn('oedema', ['moderate', 'severe']);
            })
            ->exists();
    }

    public function calculateGestationalAge(string $lmp): array
    {
        $lmpDate       = Carbon::parse($lmp)->startOfDay();
        $today         = Carbon::today();
        $totalDays     = (int) $lmpDate->diffInDays($today);
        $weeks         = intdiv($totalDays, 7);
        $remainingDays = $totalDays % 7;

        return ['weeks' => $weeks, 'days' => $remainingDays];
    }
}
