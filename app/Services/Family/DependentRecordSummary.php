<?php

namespace App\Services\Family;

use App\Models\AllergyRecord;
use App\Models\Appointment;
use App\Models\Diagnosis;
use App\Models\ImmunizationRecord;
use App\Models\LabResult;
use App\Models\Prescription;
use App\Models\Visit;

/**
 * The record-level facts a family member's detail screen shows under the
 * card: recent immunisations, the next appointments, and counts. Read-only,
 * scoped to one patient id that the caller has already proven access to.
 *
 * Statuses follow the patient's own endpoints so a guardian never sees more
 * (or less) than the dependent would: a retracted dose is not administered,
 * a cancelled appointment is not upcoming.
 */
final class DependentRecordSummary
{
    public const IMMUNIZATIONS_LIMIT = 50;
    public const UPCOMING_LIMIT      = 5;

    /**
     * Appointment statuses that no longer count as "coming up". Both the web
     * (`scheduled`) and the app (`booked` → `confirmed` → `checked_in`) write
     * open appointments, so the filter is by what is closed, not by one
     * surface's vocabulary of open.
     */
    private const CLOSED_APPOINTMENT_STATUSES = ['cancelled', 'no_show', 'completed'];

    /** @return list<array<string, mixed>> */
    public static function immunizations(string $patientId): array
    {
        return ImmunizationRecord::query()
            ->where('patient_id', $patientId)
            ->where('status', '!=', 'entered_in_error')
            ->orderByDesc('administered_at')
            ->limit(self::IMMUNIZATIONS_LIMIT)
            ->get(['id', 'vaccine_name', 'vaccine_code', 'dose_number', 'administered_at', 'status'])
            ->map(fn (ImmunizationRecord $i) => [
                'id'              => $i->id,
                'vaccine_name'    => $i->vaccine_name,
                'vaccine_code'    => $i->vaccine_code,
                'dose_number'     => $i->dose_number,
                'administered_at' => $i->administered_at?->toDateString(),
                'status'          => $i->status ?? 'completed',
            ])
            ->values()
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public static function upcomingAppointments(string $patientId): array
    {
        return Appointment::query()
            ->where('patient_id', $patientId)
            ->where('scheduled_at', '>=', now())
            ->whereNotIn('status', self::CLOSED_APPOINTMENT_STATUSES)
            ->with(['facility:id,name', 'provider:id,name'])
            ->orderBy('scheduled_at')
            ->limit(self::UPCOMING_LIMIT)
            ->get()
            ->map(fn (Appointment $a) => [
                'id'               => $a->id,
                'starts_at'        => $a->scheduled_at?->toIso8601String(),
                'facility_name'    => $a->facility?->name,
                'provider_name'    => $a->provider?->name,
                'status'           => $a->status,
                'appointment_type' => $a->appointment_type,
            ])
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    public static function counts(string $patientId): array
    {
        $lastVisit = Visit::query()
            ->where('patient_id', $patientId)
            ->orderByDesc('started_at')
            ->orderByDesc('created_at')
            ->first(['started_at', 'created_at']);

        return [
            'allergies'            => AllergyRecord::where('patient_id', $patientId)->where('status', 'active')->count(),
            'diagnoses'            => Diagnosis::where('patient_id', $patientId)->whereIn('status', ['active', 'chronic'])->count(),
            'active_prescriptions' => Prescription::where('patient_id', $patientId)->where('status', 'active')->count(),
            'lab_results'          => LabResult::where('patient_id', $patientId)->count(),
            'immunizations'        => ImmunizationRecord::where('patient_id', $patientId)->where('status', '!=', 'entered_in_error')->count(),
            'last_visit_at'        => ($lastVisit?->started_at ?? $lastVisit?->created_at)?->toIso8601String(),
        ];
    }
}
