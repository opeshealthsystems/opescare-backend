<?php

namespace App\Modules\OperationalFlow\Services;

use App\Models\Appointment;
use App\Models\AuditEvent;
use App\Models\DocumentTemplate;
use App\Models\Facility;
use App\Models\OfficialDocument;
use App\Models\Patient;
use App\Models\Receipt;
use App\Modules\Appointments\Services\AppointmentService;
use App\Modules\Billing\Services\BillingService;
use App\Modules\Billing\Services\PaymentService;
use App\Modules\EncounterManagement\Services\ConsultationService;
use App\Modules\Notifications\Services\NotificationService;
use App\Modules\Queue\Services\QueueService;
use App\Services\Documents\DocumentNumberService;
use App\Services\Documents\DocumentVerificationService;
use Illuminate\Support\Facades\DB;

class PatientJourneyService
{
    public function __construct(
        private AppointmentService $appointmentService,
        private QueueService $queueService,
        private ConsultationService $consultationService,
        private BillingService $billingService,
        private PaymentService $paymentService,
        private NotificationService $notificationService,
        private DocumentNumberService $documentNumberService,
        private DocumentVerificationService $documentVerificationService,
    ) {
    }

    public function runPilotJourney(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $appointment = $this->appointmentService->book([
                'patient_id' => $data['patient_id'],
                'facility_id' => $data['facility_id'],
                'provider_id' => $data['provider_id'] ?? null,
                'appointment_slot_id' => $data['appointment_slot_id'],
                'appointment_type' => $data['appointment_type'],
                'booked_by_type' => 'staff',
                'booked_by_id' => $data['actor_id'] ?? null,
            ]);

            $appointment = $this->appointmentService->checkIn($appointment, $data['actor_id'] ?? null);
            $visit = $appointment->visit_id ? \App\Models\Visit::findOrFail($appointment->visit_id) : null;

            $queueTicket = $this->queueService->checkInWalkIn([
                'patient_id' => $data['patient_id'],
                'facility_id' => $data['facility_id'],
                'visit_id' => $visit->id,
                'destination_queue' => $data['queue_name'],
                'visit_type' => $data['appointment_type'],
                'appointment_id' => $appointment->id,
                'actor_id' => $data['actor_id'] ?? null,
                'check_in_type' => 'appointment',
            ]);

            $clinicalNote = $this->consultationService->saveClinicalNote([
                'visit_id' => $visit->id,
                'provider_id' => $data['provider_id'] ?? $data['actor_id'] ?? null,
                'history_of_present_illness' => $data['consultation']['history_of_present_illness'] ?? null,
                'examination_findings' => $data['consultation']['examination_findings'] ?? null,
                'treatment_plan' => $data['consultation']['treatment_plan'] ?? null,
                'status' => 'signed',
            ], $data['actor_id'] ?? null);

            $invoice = $this->billingService->createInvoice([
                'patient_id' => $data['patient_id'],
                'facility_id' => $data['facility_id'],
                'visit_id' => $visit->id,
                'items' => $data['invoice_items'],
                'actor_id' => $data['actor_id'] ?? null,
            ]);

            $payment = $this->paymentService->recordPayment($invoice, [
                'amount' => $data['payment']['amount'],
                'method' => $data['payment']['method'],
                'cashier_id' => $data['actor_id'] ?? null,
            ]);
            $invoice = $invoice->fresh();
            $receipt = Receipt::where('payment_id', $payment->id)->firstOrFail();
            $document = $this->issueInvoiceDocument($invoice, $receipt, $payment, $data['lab_result_summary'] ?? null, $data['actor_id'] ?? null);

            $this->notificationService->sendNotification(
                $data['patient_id'],
                'invoice_document_ready',
                [
                    'body' => 'A new billing document is available in OpesCare. Log in securely to view it.',
                    'document_number' => $document->document_number,
                ],
                'normal',
                'account_and_security'
            );

            AuditEvent::create([
                'actor_id' => $data['actor_id'] ?? null,
                'facility_id' => $data['facility_id'],
                'patient_id' => $data['patient_id'],
                'encounter_id' => $visit->id,
                'action_type' => 'complete',
                'resource_type' => 'operational_journey',
                'resource_id' => $appointment->id,
                'reason' => 'Appointment-to-billing-to-document pilot journey completed.',
                'after_state' => [
                    'appointment_id' => $appointment->id,
                    'queue_ticket_id' => $queueTicket->id,
                    'invoice_id' => $invoice->id,
                    'payment_id' => $payment->id,
                    'receipt_id' => $receipt->id,
                    'document_id' => $document->id,
                ],
            ]);

            return [
                'appointment' => $appointment->fresh(),
                'visit' => $visit,
                'queue_ticket' => $queueTicket->fresh(),
                'clinical_note' => $clinicalNote,
                'invoice' => $invoice->fresh(),
                'payment' => $payment->fresh(),
                'receipt' => $receipt,
                'document' => $document,
            ];
        });
    }

    private function issueInvoiceDocument($invoice, Receipt $receipt, $payment, ?string $labResultSummary, ?string $actorId): OfficialDocument
    {
        $patient = Patient::findOrFail($invoice->patient_id);
        $facility = Facility::findOrFail($invoice->facility_id);
        $template = DocumentTemplate::firstOrCreate([
            'template_code' => 'INV',
        ], [
            'document_type' => 'INV',
            'language' => 'en',
            'status' => 'published',
            'version' => '1.0',
            'html_template' => '<div>Invoice</div>',
        ]);

        if ($template->status !== 'published') {
            $template->update(['status' => 'published', 'published_at' => now()]);
        }

        $identifiers = $this->documentNumberService->generateIdentifiers('INV');
        $payload = [
            'invoice_id' => $invoice->id,
            'receipt_id' => $receipt->id,
            'payment_id' => $payment->id,
            'patient_name' => trim($patient->first_name.' '.$patient->last_name),
            'facility_name' => $facility->name,
            'facility_license' => $facility->license_number,
            'invoice_number' => $invoice->invoice_number,
            'receipt_number' => $receipt->receipt_number,
            'amount_paid' => (float) $payment->amount,
            'balance_amount' => (float) $invoice->balance_amount,
            'lab_result_summary' => $labResultSummary,
            'issuer_name' => 'OpesCare Cashier',
            'issuer_role' => 'Cashier',
        ];

        $document = OfficialDocument::create([
            'document_type' => 'INV',
            'document_number' => $identifiers['document_number'],
            'verification_code' => $identifiers['verification_code'],
            'patient_id' => $patient->id,
            'health_id' => $patient->health_id,
            'facility_id' => $facility->id,
            'issuer_user_id' => $actorId,
            'template_id' => $template->id,
            'template_version' => $template->version,
            'status' => 'issued',
            'version' => '1.0',
            'title' => 'Invoice and Receipt',
            'payload_json' => $payload,
            'payload_hash' => hash('sha256', json_encode($payload)),
            'issued_at' => now(),
            'released_at' => now(),
        ]);

        $this->documentVerificationService->issueToken($document->id, $identifiers['verification_token']);

        AuditEvent::create([
            'actor_id' => $actorId,
            'facility_id' => $facility->id,
            'patient_id' => $patient->id,
            'action_type' => 'issue',
            'resource_type' => 'document',
            'resource_id' => $document->id,
            'reason' => 'Invoice document generated from completed pilot journey.',
        ]);

        return $document;
    }
}
