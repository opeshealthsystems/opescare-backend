<?php

namespace Tests\Feature\Documents;

use App\Models\BloodRequest;
use App\Models\CareFacility;
use App\Models\ClinicalNote;
use App\Models\ConsentGrant;
use App\Models\Diagnosis;
use App\Models\DocumentVerification;
use App\Models\Encounter;
use App\Models\Facility;
use App\Models\ImagingOrder;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Receipt;
use App\Models\ReferralCase;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Facility-side documents from the missing-documents pass (specs 20-23, 27-30):
 * each renders a sealed PDF with its key fields, and refuses the wrong role
 * (403) and another facility (404).
 */
class NewFacilityDocumentsTest extends TestCase
{
    use NewDocumentSupport;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBasics();
    }

    private function grant(Patient $patient, Facility $facility): void
    {
        ConsentGrant::create([
            'patient_id' => $patient->id, 'facility_id' => $facility->id, 'authorizing_actor' => 'patient',
            'scope' => ['full'], 'status' => 'active', 'expires_at' => now()->addMonth(),
        ]);
    }

    private function assertSealed(Patient $patient, string $type): void
    {
        $row = DocumentVerification::where('patient_id', $patient->id)->where('document_type', $type)->latest('issued_at')->first();
        $this->assertNotNull($row, "{$type}: no verification row");
        $this->assertSame(64, strlen((string) $row->sha256), "{$type}: not sealed");
    }

    public function test_referral_letter_with_counter_referral(): void
    {
        [$doctor, $csi]  = $this->staff('doctor');
        [, $district]    = $this->staff('doctor', null);
        $patient         = $this->patient();
        $this->grant($patient, $csi);

        $referral = ReferralCase::create([
            'patient_id' => $patient->id, 'referring_facility_id' => $csi->id, 'receiving_facility_id' => $district->id,
            'referring_provider_id' => $doctor->id, 'created_by_id' => $doctor->id,
            'urgency' => 'urgent', 'status' => 'completed', 'reason' => 'Suspected appendicitis',
            'clinical_summary' => 'RLQ pain 12h', 'feedback' => 'Appendicectomy done, review in 7 days',
            'completed_at' => now(),
        ]);

        $this->capture('documents.new.referral-letter');
        $this->assertPdfResponse($this->at($doctor, $csi)->get(route('documents.new.referral', $referral->id)));
        $html = $this->renderedHtml('documents.new.referral-letter');
        foreach (['Suspected appendicitis', 'RLQ pain 12h', 'Appendicectomy done', $patient->health_id, $district->name] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        $this->assertSealed($patient, 'referral_letter');

        // A facility that is not a party: 404. A front-desk role: 403.
        [$stranger, $other] = $this->staff('doctor');
        $this->at($stranger, $other)->get(route('documents.new.referral', $referral->id))->assertNotFound();
        [$cashier] = $this->staff('cashier', $csi);
        $this->at($cashier, $csi)->get(route('documents.new.referral', $referral->id))->assertForbidden();
    }

    public function test_referral_letter_needs_a_relationship_basis(): void
    {
        [$doctor, $csi] = $this->staff('doctor');
        [, $district]   = $this->staff('doctor');
        $patient        = $this->patient(['privacy_preferences' => ['require_consent_for_full_record' => true]]);

        $referral = ReferralCase::create([
            'patient_id' => $patient->id, 'referring_facility_id' => $district->id, 'receiving_facility_id' => $csi->id,
            'urgency' => 'routine', 'status' => 'draft', 'reason' => 'x',
        ]);

        // Party to the referral, but no consent / episode / care-team basis here.
        $this->at($doctor, $csi)->get(route('documents.new.referral', $referral->id))->assertForbidden();
    }

    public function test_encounter_report(): void
    {
        [$doctor, $facility] = $this->staff('doctor');
        $patient = $this->patient();
        $this->grant($patient, $facility);

        $visit = Visit::create(['patient_id' => $patient->id, 'facility_id' => $facility->id, 'provider_id' => $doctor->id,
            'visit_type' => 'outpatient', 'status' => 'completed', 'started_at' => now()->subHour()]);
        $encounter = Encounter::create(['visit_id' => $visit->id, 'patient_id' => $patient->id, 'facility_id' => $facility->id,
            'encounter_type' => 'consultation', 'status' => 'completed', 'attending_provider_id' => $doctor->id,
            'reason_for_encounter' => 'Fever for three days', 'started_at' => now()->subHour(), 'ended_at' => now()]);
        Diagnosis::create(['patient_id' => $patient->id, 'visit_id' => $visit->id, 'provider_id' => $doctor->id,
            'code_system' => 'ICD-10', 'code' => 'B54', 'display_name' => 'Unspecified malaria', 'status' => 'confirmed', 'is_primary' => true]);
        ClinicalNote::create(['visit_id' => $visit->id, 'patient_id' => $patient->id, 'provider_id' => $doctor->id,
            'examination_findings' => 'Temp 39.2, no neck stiffness', 'treatment_plan' => 'ACT and review', 'status' => 'signed', 'signed_at' => now()]);

        $this->capture('documents.new.encounter-report');
        $this->assertPdfResponse($this->at($doctor, $facility)->get(route('documents.new.encounter', $encounter->id)));
        $html = $this->renderedHtml('documents.new.encounter-report');
        foreach (['Fever for three days', 'Unspecified malaria', 'B54', 'Temp 39.2', 'ACT and review'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        $this->assertSealed($patient, 'encounter_report');

        [$other, $elsewhere] = $this->staff('doctor');
        $this->at($other, $elsewhere)->get(route('documents.new.encounter', $encounter->id))->assertNotFound();
        [$cashier] = $this->staff('cashier', $facility);
        $this->at($cashier, $facility)->get(route('documents.new.encounter', $encounter->id))->assertForbidden();
    }

    /** @return array{0: Invoice, 1: Receipt} */
    private function paidInvoice(Facility $facility, Patient $patient, $cashier): array
    {
        $account = \App\Models\BillingAccount::create(['patient_id' => $patient->id, 'facility_id' => $facility->id,
            'status' => 'active', 'outstanding_balance_amount' => 0]);
        $invoice = Invoice::create([
            'billing_account_id' => $account->id, 'patient_id' => $patient->id, 'facility_id' => $facility->id, 'invoice_number' => 'INV-NEW-0001',
            'status' => 'paid', 'subtotal_amount' => 12500, 'discount_amount' => 0, 'insurance_covered_amount' => 0,
            'patient_responsibility_amount' => 12500, 'paid_amount' => 12500, 'refunded_amount' => 0, 'balance_amount' => 0,
            'issued_at' => now(), 'paid_at' => now(),
        ]);
        InvoiceItem::create(['invoice_id' => $invoice->id, 'service_code' => 'CONS-01', 'description' => 'Consultation générale',
            'quantity' => 1, 'unit_price' => 12500, 'discount_amount' => 0, 'line_total_amount' => 12500]);
        $payment = Payment::create(['invoice_id' => $invoice->id, 'patient_id' => $patient->id, 'facility_id' => $facility->id,
            'cashier_id' => $cashier->id, 'payment_reference' => 'PAY-NEW-0001', 'method' => 'cash', 'status' => 'completed',
            'amount' => 12500, 'refunded_amount' => 0, 'currency' => 'XAF', 'payer_phone' => '+237690000000']);
        $receipt = Receipt::create(['payment_id' => $payment->id, 'invoice_id' => $invoice->id, 'receipt_number' => 'RCT-NEW-0001',
            'amount' => 12500, 'issued_at' => now()]);

        return [$invoice, $receipt];
    }

    public function test_invoice_and_receipt_in_whole_francs(): void
    {
        [$cashier, $facility] = $this->staff('cashier');
        $patient = $this->patient();
        [$invoice, $receipt] = $this->paidInvoice($facility, $patient, $cashier);

        $this->capture('documents.new.invoice');
        $this->assertPdfResponse($this->at($cashier, $facility)->get(route('documents.new.invoice', $invoice->id)));
        $html = $this->renderedHtml('documents.new.invoice');
        $this->assertStringContainsString('INV-NEW-0001', $html);
        $this->assertStringContainsString('Consultation générale', $html);
        $this->assertStringContainsString('12 500 FCFA', $html);
        $this->assertStringNotContainsString('12 500,00', $html);
        $this->assertSealed($patient, 'invoice');

        $this->capture('documents.new.receipt');
        $this->assertPdfResponse($this->at($cashier, $facility)->get(route('documents.new.receipt', $receipt->id)));
        $html = $this->renderedHtml('documents.new.receipt');
        $this->assertStringContainsString('RCT-NEW-0001', $html);
        $this->assertStringContainsString('PAY-NEW-0001', $html);
        $this->assertStringNotContainsString('690000000', $html, 'payer phone must not be printed');
        $this->assertSealed($patient, 'payment_receipt');

        [$otherCashier, $elsewhere] = $this->staff('cashier');
        $this->at($otherCashier, $elsewhere)->get(route('documents.new.invoice', $invoice->id))->assertNotFound();
        $this->at($otherCashier, $elsewhere)->get(route('documents.new.receipt', $receipt->id))->assertNotFound();
        [$pharmacist] = $this->staff('pharmacist', $facility);
        $this->at($pharmacist, $facility)->get(route('documents.new.invoice', $invoice->id))->assertForbidden();
    }

    public function test_dispensing_labels_for_dispensed_items_only(): void
    {
        [$pharmacist, $pharmacy] = $this->staff('pharmacist', null, 'pharmacy');
        $patient = $this->patient();
        $rx = Prescription::create(['id' => (string) Str::uuid(), 'patient_id' => $patient->id, 'facility_id' => $pharmacy->id,
            'reference' => 'RX-NEW-0001', 'status' => 'dispensed', 'prescribed_at' => now()]);
        PrescriptionItem::create(['prescription_id' => $rx->id, 'drug_name' => 'Amoxicillin 500mg', 'dose' => '500 mg',
            'frequency' => '3 times daily', 'duration_days' => 7, 'quantity' => 21, 'status' => 'dispensed', 'dispensed_at' => now()]);
        PrescriptionItem::create(['prescription_id' => $rx->id, 'drug_name' => 'Undispensed Drug', 'dose' => '1',
            'frequency' => 'daily', 'status' => 'pending']);

        $this->capture('documents.new.dispensing-label');
        $this->assertPdfResponse($this->at($pharmacist, $pharmacy)->get(route('documents.new.labels', $rx->id)));
        $html = $this->renderedHtml('documents.new.dispensing-label');
        $this->assertStringContainsString('Amoxicillin 500mg', $html);
        $this->assertStringContainsString('RX-NEW-0001', $html);
        $this->assertStringNotContainsString('Undispensed Drug', $html);

        [$doctor] = $this->staff('doctor', $pharmacy);
        $this->assertContains($this->at($doctor, $pharmacy)->get(route('documents.new.labels', $rx->id))->getStatusCode(), [302, 403]);
    }

    public function test_lab_and_imaging_request_forms(): void
    {
        [$doctor, $facility] = $this->staff('doctor');
        $patient = $this->patient();

        $lab = LabOrder::create(['id' => (string) Str::uuid(), 'patient_id' => $patient->id, 'facility_id' => $facility->id,
            'ordered_by' => $doctor->id, 'test_name' => 'Full Blood Count', 'test_code' => 'FBC', 'urgency' => 'routine',
            'status' => 'ordered', 'clinical_indication' => 'Pallor and fatigue', 'ordered_at' => now()]);
        $img = ImagingOrder::create(['patient_id' => $patient->id, 'facility_id' => $facility->id, 'ordered_by' => $doctor->id,
            'modality' => 'XR', 'body_part' => 'Chest', 'clinical_indication' => 'Chronic cough', 'urgency' => 'routine',
            'status' => 'pending', 'accession_number' => 'ACC-NEW-0001', 'ordered_at' => now()]);

        $this->capture('documents.new.lab-request');
        $this->assertPdfResponse($this->at($doctor, $facility)->get(route('documents.new.lab_request', $lab->id)));
        $html = $this->renderedHtml('documents.new.lab-request');
        $this->assertStringContainsString('Full Blood Count', $html);
        $this->assertStringContainsString('Pallor and fatigue', $html);
        $this->assertSealed($patient, 'lab_request');

        $this->capture('documents.new.imaging-request');
        $this->assertPdfResponse($this->at($doctor, $facility)->get(route('documents.new.imaging_request', $img->id)));
        $html = $this->renderedHtml('documents.new.imaging-request');
        $this->assertStringContainsString('Chest', $html);
        $this->assertStringContainsString('Chronic cough', $html);
        $this->assertStringContainsString('ACC-NEW-0001', $html);
        $this->assertSealed($patient, 'imaging_request');

        [$other, $elsewhere] = $this->staff('doctor');
        $this->at($other, $elsewhere)->get(route('documents.new.lab_request', $lab->id))->assertNotFound();
        $this->at($other, $elsewhere)->get(route('documents.new.imaging_request', $img->id))->assertNotFound();
        [$cashier] = $this->staff('cashier', $facility);
        $this->at($cashier, $facility)->get(route('documents.new.lab_request', $lab->id))->assertForbidden();
        $this->at($cashier, $facility)->get(route('documents.new.imaging_request', $img->id))->assertForbidden();
    }

    public function test_blood_request_slip(): void
    {
        [$doctor, $facility] = $this->staff('doctor');
        $patient = $this->patient();
        $listing = CareFacility::create(['facility_id' => $facility->id, 'facility_name' => 'Banque de sang Test',
            'facility_type' => 'blood_bank', 'country_code' => 'CM', 'city' => 'Douala', 'region' => 'Littoral', 'address' => 'Rue Joss', 'latitude' => 4.05, 'longitude' => 9.7, 'phone_primary' => '+237600000001']);

        $request = BloodRequest::create(['reference' => 'BR-NEW-0001', 'patient_id' => $patient->id, 'care_facility_id' => $listing->id,
            'blood_group' => 'O+', 'component_type' => 'whole_blood', 'quantity' => 2, 'urgency' => 'emergency',
            'status' => 'confirmed', 'patient_note' => 'Post-partum haemorrhage']);

        $this->capture('documents.new.blood-request');
        $this->assertPdfResponse($this->at($doctor, $facility)->get(route('documents.new.blood_request', $request->id)));
        $html = $this->renderedHtml('documents.new.blood-request');
        foreach (['BR-NEW-0001', 'O+', 'Post-partum haemorrhage', 'crossmatch'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        $this->assertSealed($patient, 'blood_request');

        [$other, $elsewhere] = $this->staff('doctor');
        $this->at($other, $elsewhere)->get(route('documents.new.blood_request', $request->id))->assertNotFound();
        [$pharmacist] = $this->staff('pharmacist', $facility);
        $this->at($pharmacist, $facility)->get(route('documents.new.blood_request', $request->id))->assertForbidden();
    }
}
