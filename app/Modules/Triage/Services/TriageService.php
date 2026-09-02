<?php

namespace App\Modules\Triage\Services;

use App\Models\TriageRecord;
use App\Models\Visit;
use App\Models\VitalSign;
use App\Models\AuditEvent;
use Exception;
use Illuminate\Support\Facades\DB;

class TriageService
{
    /**
     * Records triage and vital signs. Validates critical values.
     */
    public function recordTriage(array $data, ?string $actorId = null): TriageRecord
    {
        DB::beginTransaction();
        try {
            $triage = TriageRecord::create([
                'visit_id' => $data['visit_id'],
                'nurse_id' => $data['nurse_id'] ?? $actorId,
                'presenting_complaint' => $data['presenting_complaint'] ?? null,
                'pain_score' => $data['pain_score'] ?? null,
                'pregnancy_status' => $data['pregnancy_status'] ?? null,
                'acuity_score' => $data['acuity_score'] ?? null,
            ]);

            if (isset($data['vitals'])) {
                // Validation against impossible or dangerous units
                $vitalsData = $data['vitals'];

                if (isset($vitalsData['temperature']) && ($vitalsData['temperature'] < 20 || $vitalsData['temperature'] > 45)) {
                    throw new Exception("Temperature value is out of logical human bounds (Celsius).");
                }

                $vitalSigns = VitalSign::create(array_merge($vitalsData, [
                    'triage_record_id' => $triage->id,
                ]));

                $alerts = self::assessVitals($vitalsData);
                $criticalAlerts = array_filter($alerts, fn($a) => $a['status'] === 'critical');
                $warningAlerts  = array_filter($alerts, fn($a) => $a['status'] === 'warning');

                if (!empty($criticalAlerts)) {
                    $triage->update(['acuity_score' => 'critical']);
                } elseif (!empty($warningAlerts) && !in_array($triage->acuity_score, ['critical', 'resuscitation'])) {
                    $triage->update(['acuity_score' => 'urgent']);
                }
            }

            AuditEvent::create([
                'actor_id' => $actorId ?? $data['nurse_id'] ?? null,
                'facility_id' => $data['facility_id'] ?? null,
                'patient_id' => $data['patient_id'] ?? null,
                'encounter_id' => $data['visit_id'],
                'action_type' => 'create',
                'resource_type' => 'triage_record',
                'resource_id' => $triage->id,
                'reason' => 'Triage recorded',
            ]);

            DB::commit();
            return $triage;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Escalate a visit to emergency status.
     * Sets acuity_score = 'resuscitation' on latest triage, marks visit emergency.
     */
    public function escalateEmergency(string $visitId, string $reason, ?string $actorId = null): TriageRecord
    {
        DB::beginTransaction();
        try {
            $visit = Visit::findOrFail($visitId);

            // Upsert triage record with emergency acuity
            $triage = TriageRecord::where('visit_id', $visitId)
                ->latest()
                ->first();

            if ($triage) {
                $triage->update(['acuity_score' => 'resuscitation']);
            } else {
                $triage = TriageRecord::create([
                    'visit_id'             => $visitId,
                    'nurse_id'             => $actorId,
                    'presenting_complaint' => $reason,
                    'acuity_score'         => 'resuscitation',
                ]);
            }

            // Advance visit to emergency status if not already closed
            if (!in_array($visit->status, ['completed', 'cancelled'])) {
                $visit->update(['status' => 'emergency']);
            }

            AuditEvent::create([
                'actor_id'      => $actorId,
                'patient_id'    => $visit->patient_id,
                'encounter_id'  => $visitId,
                'action_type'   => 'emergency_escalation',
                'resource_type' => 'triage_record',
                'resource_id'   => $triage->id,
                'reason'        => $reason,
                'after_state'   => ['acuity_score' => 'resuscitation', 'escalation_reason' => $reason],
            ]);

            DB::commit();
            return $triage;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reassess an existing triage record (additive).
     * Optionally records new vitals and updates the acuity score.
     */
    public function reassess(string $triageId, array $data, ?string $actorId = null): TriageRecord
    {
        DB::beginTransaction();
        try {
            $triage = TriageRecord::findOrFail($triageId);

            if (isset($data['vitals'])) {
                $vitalsData = $data['vitals'];

                if (isset($vitalsData['temperature']) && ($vitalsData['temperature'] < 20 || $vitalsData['temperature'] > 45)) {
                    throw new Exception("Temperature value is out of logical human bounds (Celsius).");
                }

                VitalSign::create(array_merge($vitalsData, [
                    'triage_record_id' => $triage->id,
                ]));

                $alerts = self::assessVitals($vitalsData);
                $criticalAlerts = array_filter($alerts, fn($a) => $a['status'] === 'critical');

                if (!empty($criticalAlerts) && !in_array($triage->acuity_score, ['critical', 'resuscitation'])) {
                    $triage->update(['acuity_score' => 'critical']);
                }
            }

            if (!empty($data['new_acuity_score'])) {
                $triage->update(['acuity_score' => $data['new_acuity_score']]);
            }

            AuditEvent::create([
                'actor_id'      => $actorId,
                'encounter_id'  => $triage->visit_id,
                'action_type'   => 'update',
                'resource_type' => 'triage_record',
                'resource_id'   => $triage->id,
                'reason'        => $data['reason'] ?? 'Triage reassessment',
            ]);

            DB::commit();
            return $triage->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * List active triage records for a facility (additive).
     * Active = triage records attached to visits at the facility that are not closed.
     */
    public function listActiveForFacility(string $facilityId): \Illuminate\Database\Eloquent\Collection
    {
        return TriageRecord::whereHas('visit', function ($query) use ($facilityId) {
                $query->where('facility_id', $facilityId)
                    ->whereNotIn('status', ['completed', 'cancelled']);
            })
            ->with(['visit', 'vitalSigns'])
            ->latest()
            ->get();
    }

    /**
     * Vital sign range assessment for clinical alerts.
     * Returns array of ['vital', 'value', 'status' => critical|warning|ok, 'note']
     */
    public static function assessVitals(array $vitals): array
    {
        $alerts = [];

        if (isset($vitals['oxygen_saturation'])) {
            $spo2 = (float) $vitals['oxygen_saturation'];
            if ($spo2 < 90)      $alerts[] = ['vital' => 'SpO₂', 'value' => $spo2 . '%', 'status' => 'critical', 'note' => 'Severe hypoxia — immediate oxygen'];
            elseif ($spo2 < 95)  $alerts[] = ['vital' => 'SpO₂', 'value' => $spo2 . '%', 'status' => 'warning', 'note' => 'Low oxygen saturation'];
        }

        if (isset($vitals['pulse'])) {
            $p = (int) $vitals['pulse'];
            if ($p < 50 || $p > 150) $alerts[] = ['vital' => 'Pulse', 'value' => $p . ' bpm', 'status' => 'critical', 'note' => $p < 50 ? 'Bradycardia' : 'Tachycardia'];
            elseif ($p < 60 || $p > 100) $alerts[] = ['vital' => 'Pulse', 'value' => $p . ' bpm', 'status' => 'warning', 'note' => 'Outside normal range'];
        }

        if (isset($vitals['blood_pressure_systolic'])) {
            $sys = (int) $vitals['blood_pressure_systolic'];
            if ($sys < 90 || $sys > 180) $alerts[] = ['vital' => 'BP Systolic', 'value' => $sys . ' mmHg', 'status' => 'critical', 'note' => $sys < 90 ? 'Hypotension' : 'Severe hypertension'];
            elseif ($sys < 100 || $sys > 140) $alerts[] = ['vital' => 'BP Systolic', 'value' => $sys . ' mmHg', 'status' => 'warning', 'note' => 'Elevated or low blood pressure'];
        }

        if (isset($vitals['temperature'])) {
            $t = (float) $vitals['temperature'];
            if ($t < 35 || $t > 40)       $alerts[] = ['vital' => 'Temperature', 'value' => $t . '°C', 'status' => 'critical', 'note' => $t < 35 ? 'Hypothermia' : 'High-grade fever'];
            elseif ($t < 36 || $t > 38.5) $alerts[] = ['vital' => 'Temperature', 'value' => $t . '°C', 'status' => 'warning', 'note' => 'Abnormal temperature'];
        }

        if (isset($vitals['respiratory_rate'])) {
            $rr = (int) $vitals['respiratory_rate'];
            if ($rr < 8 || $rr > 30) $alerts[] = ['vital' => 'Resp. Rate', 'value' => $rr . '/min', 'status' => 'critical', 'note' => 'Respiratory distress'];
            elseif ($rr < 12 || $rr > 20) $alerts[] = ['vital' => 'Resp. Rate', 'value' => $rr . '/min', 'status' => 'warning', 'note' => 'Abnormal respiratory rate'];
        }

        return $alerts;
    }
}
