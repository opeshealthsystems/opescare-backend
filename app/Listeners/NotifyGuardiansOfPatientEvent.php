<?php
namespace App\Listeners;

use App\Models\FamilyLink;
use App\Notifications\FamilyEventNotification;

class NotifyGuardiansOfPatientEvent
{
    public function handleLabResult(object $labResult): void
    {
        $this->dispatch(
            $labResult->patient_id,
            'lab_result',
            'A new lab result is available for your dependent.'
        );
    }

    public function handleAppointment(object $appointment): void
    {
        $this->dispatch(
            $appointment->patient_id,
            'appointment',
            'An appointment has been scheduled for your dependent.'
        );
    }

    public function handleAppointmentUpdated(object $appointment): void
    {
        if (!$appointment->wasChanged('status')) {
            return;
        }
        $this->dispatch(
            $appointment->patient_id,
            'appointment',
            'An appointment for your dependent has been updated.'
        );
    }

    public function handleConsentRequest(object $consentRequest): void
    {
        $this->dispatch(
            $consentRequest->patient_id,
            'consent_request',
            'A consent request is pending approval for your dependent.'
        );
    }

    private function dispatch(string $patientId, string $eventKey, string $description): void
    {
        $links = FamilyLink::active()
            ->where('dependent_patient_id', $patientId)
            ->with('guardianUser', 'dependentPatient')
            ->get();

        foreach ($links as $link) {
            $link->guardianUser?->notify(
                new FamilyEventNotification($link, $eventKey, $description)
            );
        }
    }
}
