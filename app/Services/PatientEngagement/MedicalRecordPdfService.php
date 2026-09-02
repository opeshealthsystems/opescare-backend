<?php
namespace App\Services\PatientEngagement;

use App\Models\LabResult;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\VitalSign;
use Barryvdh\DomPDF\Facade\Pdf;

class MedicalRecordPdfService
{
    /**
     * Generate a PDF medical record summary for a patient.
     * Returns the raw PDF binary string.
     * Stream with: response($pdf)->header('Content-Type', 'application/pdf')
     */
    public function generateSummary(string $patientId): string
    {
        $patient      = Patient::findOrFail($patientId);
        $vitals       = VitalSign::whereHas('triageRecord.visit', fn ($q) => $q->where('patient_id', $patientId))
                            ->latest()->take(5)->get();
        $labResults   = LabResult::where('patient_id', $patientId)->latest()->take(10)->get();
        $prescriptions = Prescription::where('patient_id', $patientId)->latest()->take(10)->get();

        $pdf = Pdf::loadView('pdf.medical-record-summary', compact(
            'patient', 'vitals', 'labResults', 'prescriptions'
        ));

        return $pdf->output();
    }
}
