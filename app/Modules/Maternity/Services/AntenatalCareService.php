<?php
namespace App\Modules\Maternity\Services;

use App\Models\AntenatalRecord;
use App\Models\AntenatalVisit;
use Carbon\Carbon;

class AntenatalCareService
{
    public function openRecord(
        string  $patientId,
        string  $providerId,
        string  $facilityId,
        string  $lmpDate,
        int     $gravida,
        int     $para,
        ?string $riskFactors = null
    ): AntenatalRecord {
        $edd = Carbon::parse($lmpDate)->addDays(280)->toDateString(); // Naegele's rule

        return AntenatalRecord::create([
            'patient_id'       => $patientId,
            'provider_id'      => $providerId,
            'facility_id'      => $facilityId,
            'lmp'              => $lmpDate,
            'edd'              => $edd,
            'gravida'          => $gravida,
            'para'             => $para,
            'risk_factors'     => $riskFactors ? [$riskFactors] : null,
            'pregnancy_status' => 'active',
        ]);
    }

    public function recordVisit(
        string  $recordId,
        string  $providerId,
        string  $visitDate,
        int     $gestationalAge,
        ?string $bloodPressure  = null,
        ?int    $fetalHeartRate = null,
        ?float  $weightKg       = null,
        ?float  $fundalHeight   = null,
        ?string $presentation   = null,
        ?string $notes          = null,
        ?string $nextVisitPlan  = null,
    ): AntenatalVisit {
        $record = AntenatalRecord::findOrFail($recordId);

        // Parse systolic/diastolic from "120/80" format if provided
        $systolic  = null;
        $diastolic = null;
        if ($bloodPressure && str_contains($bloodPressure, '/')) {
            [$systolic, $diastolic] = array_map('intval', explode('/', $bloodPressure));
        }

        return AntenatalVisit::create([
            'pregnancy_record_id'   => $recordId,
            'patient_id'            => $record->patient_id,
            'facility_id'           => $record->facility_id,
            'provider_id'           => $providerId,
            'visit_date'            => $visitDate,
            'gestational_age_weeks' => $gestationalAge,
            'fetal_heart_rate'      => $fetalHeartRate,
            'weight_kg'             => $weightKg,
            'fundal_height_cm'      => $fundalHeight,
            'presentation'          => $presentation,
            'bp_systolic'           => $systolic,
            'bp_diastolic'          => $diastolic,
            'notes'                 => $notes,
        ]);
    }
}
