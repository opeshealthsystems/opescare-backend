<?php

namespace App\Modules\Billing\Services;

use App\Models\AuditEvent;
use App\Models\BillingAccount;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BillingService
{
    public function createInvoice(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $account = BillingAccount::firstOrCreate([
                'patient_id' => $data['patient_id'],
                'facility_id' => $data['facility_id'],
            ], [
                'status' => 'active',
                'outstanding_balance_amount' => 0,
            ]);

            $items = collect($data['items'] ?? []);
            $subtotal = $items->sum(fn (array $item) => ((float) $item['quantity']) * ((float) $item['unit_price']));
            $discount = $items->sum(fn (array $item) => (float) ($item['discount_amount'] ?? 0));
            $insurance = (float) ($data['insurance_covered_amount'] ?? 0);
            $patientResponsibility = max(0, $subtotal - $discount - $insurance);

            $invoice = Invoice::create([
                'billing_account_id' => $account->id,
                'patient_id' => $data['patient_id'],
                'facility_id' => $data['facility_id'],
                'visit_id' => $data['visit_id'] ?? null,
                'invoice_number' => 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'status' => 'issued',
                'subtotal_amount' => $subtotal,
                'discount_amount' => $discount,
                'insurance_covered_amount' => $insurance,
                'patient_responsibility_amount' => $patientResponsibility,
                'paid_amount' => 0,
                'refunded_amount' => 0,
                'balance_amount' => $patientResponsibility,
                'issued_at' => now(),
            ]);

            foreach ($items as $item) {
                $lineSubtotal = ((float) $item['quantity']) * ((float) $item['unit_price']);
                $lineDiscount = (float) ($item['discount_amount'] ?? 0);
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'service_code' => $item['service_code'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_amount' => $lineDiscount,
                    'line_total_amount' => max(0, $lineSubtotal - $lineDiscount),
                ]);
            }

            $this->refreshAccountBalance($account);
            $this->audit($invoice, 'create', $data['actor_id'] ?? null, 'Invoice issued.');

            return $invoice->fresh();
        });
    }

    public function refreshInvoiceStatus(Invoice $invoice): Invoice
    {
        $balance = max(0, ((float) $invoice->patient_responsibility_amount) - ((float) $invoice->paid_amount) + ((float) $invoice->refunded_amount));
        $status = match (true) {
            $balance <= 0 && (float) $invoice->refunded_amount > 0 => 'refunded',
            $balance <= 0 => 'paid',
            (float) $invoice->paid_amount > 0 => 'partially_paid',
            default => 'issued',
        };

        $invoice->update([
            'balance_amount' => $balance,
            'status' => $status,
            'paid_at' => $status === 'paid' ? now() : $invoice->paid_at,
        ]);

        $this->refreshAccountBalance($invoice->billing_account_id);

        return $invoice->fresh();
    }

    public function refreshAccountBalance(BillingAccount|string $account): void
    {
        $account = $account instanceof BillingAccount ? $account : BillingAccount::findOrFail($account);
        $account->update([
            'outstanding_balance_amount' => Invoice::where('billing_account_id', $account->id)->sum('balance_amount'),
        ]);
    }

    private function audit(Invoice $invoice, string $action, ?string $actorId, string $reason): void
    {
        AuditEvent::create([
            'actor_id' => $actorId,
            'facility_id' => $invoice->facility_id,
            'patient_id' => $invoice->patient_id,
            'encounter_id' => $invoice->visit_id,
            'action_type' => $action,
            'resource_type' => 'invoice',
            'resource_id' => $invoice->id,
            'reason' => $reason,
            'after_state' => $invoice->toArray(),
        ]);
    }
}
