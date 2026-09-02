<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Payments\MtnMomoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * PatientBillingController — patient billing history + pay-a-facility-invoice
 * via MTN Mobile Money. Reuses the same MoMo collection flow as subscriptions:
 * create a pending Payment, initiate the collection, poll for confirmation,
 * then settle the invoice. No money moves without a valid MoMo Collections key.
 */
class PatientBillingController extends Controller
{
    private function patient()
    {
        $p = Auth::user()?->patient;
        abort_if($p === null, 403);
        return $p;
    }

    /** Amount still owed by the patient on an invoice, in major currency units. */
    private function amountDue(Invoice $invoice): float
    {
        $balance = (float) $invoice->balance_amount;
        if ($balance > 0) {
            return round($balance, 2);
        }
        return round(max(0, (float) $invoice->patient_responsibility_amount - (float) $invoice->paid_amount), 2);
    }

    /** Billing history — the patient's invoices, newest first. */
    public function index(Request $request): View
    {
        $patient = $this->patient();

        $invoices = Invoice::where('patient_id', $patient->id)
            ->orderByDesc('issued_at')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('portals.patient.billing', compact('patient', 'invoices'));
    }

    /** Pay form — confirm the amount due and collect the MoMo phone number. */
    public function payForm(Request $request, string $invoice): View
    {
        $patient = $this->patient();
        $inv = Invoice::where('id', $invoice)->where('patient_id', $patient->id)->firstOrFail();

        abort_if($this->amountDue($inv) <= 0, 422, 'This invoice has no outstanding balance.');

        return view('portals.patient.billing_pay', [
            'patient' => $patient,
            'invoice' => $inv,
            'amount'  => $this->amountDue($inv),
        ]);
    }

    /** Initiate the MoMo collection for an invoice. */
    public function pay(Request $request, string $invoice): RedirectResponse
    {
        $patient = $this->patient();
        $inv = Invoice::where('id', $invoice)->where('patient_id', $patient->id)->firstOrFail();

        $due = $this->amountDue($inv);
        abort_if($due <= 0, 422, 'This invoice has no outstanding balance.');

        $validated = $request->validate(['phone' => 'required|string|max:20']);

        $paymentId = (string) Str::uuid();
        $momo = app(MtnMomoService::class);
        $result = $momo->requestPayment($validated['phone'], $due, 'XAF', $paymentId, 'OpesCare invoice ' . $inv->invoice_number);

        if (empty($result['success'])) {
            return redirect()->route('portals.patient.billing')->with('error', __('billing.pay_failed'));
        }

        $payment = Payment::create([
            'id'                => $paymentId,
            'invoice_id'        => $inv->id,
            'patient_id'        => $patient->id,
            'facility_id'       => $inv->facility_id,
            'payment_reference' => $result['reference_id'],
            'method'            => 'mtn_momo',
            'status'            => 'pending',
            'amount'            => $due,
        ]);

        return redirect()->route('portals.patient.billing.pending', ['payment' => $payment->id]);
    }

    /** Pending page that polls for the collection result. */
    public function pending(Request $request, string $payment): View
    {
        $patient = $this->patient();
        $pay = Payment::where('id', $payment)->where('patient_id', $patient->id)->firstOrFail();

        return view('portals.patient.billing_pending', ['payment' => $pay]);
    }

    /** JSON poll — confirm the collection, settle the invoice on success. */
    public function status(Request $request, string $payment)
    {
        $patient = $this->patient();
        $pay = Payment::where('id', $payment)->where('patient_id', $patient->id)->firstOrFail();

        if ($pay->status === 'successful') {
            return response()->json(['status' => 'successful']);
        }
        if (in_array($pay->status, ['failed', 'cancelled'], true)) {
            return response()->json(['status' => 'failed']);
        }

        $status = strtoupper(app(MtnMomoService::class)->checkStatus($pay->payment_reference)['status'] ?? 'UNKNOWN');

        if ($status === 'SUCCESSFUL') {
            DB::transaction(function () use ($pay) {
                $pay->update(['status' => 'successful']);

                $inv = Invoice::lockForUpdate()->find($pay->invoice_id);
                if ($inv) {
                    $paid    = (float) $inv->paid_amount + (float) $pay->amount;
                    $balance = max(0, (float) $inv->patient_responsibility_amount - $paid);
                    $inv->update([
                        'paid_amount'    => $paid,
                        'balance_amount' => $balance,
                        'status'         => $balance <= 0 ? 'paid' : $inv->status,
                        'paid_at'        => $balance <= 0 ? now() : $inv->paid_at,
                    ]);
                }
            });
            return response()->json(['status' => 'successful']);
        }

        if ($status === 'FAILED') {
            $pay->update(['status' => 'failed']);
            return response()->json(['status' => 'failed']);
        }

        return response()->json(['status' => 'pending']);
    }
}
