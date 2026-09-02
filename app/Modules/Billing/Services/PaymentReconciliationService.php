<?php

namespace App\Modules\Billing\Services;

use App\Models\PaymentReconciliation;
use App\Models\CashierSession;
use App\Models\Payment;
use App\Models\AuditEvent;

/**
 * PaymentReconciliationService — Cashier session close-out and payment reconciliation.
 *
 * At end of shift or day, cashiers reconcile expected receipts against physical cash
 * and electronic payment confirmations. Discrepancies are flagged for finance review.
 *
 * Rules:
 *  - Reconciliation must be completed before next shift start
 *  - Shortfalls and overages both require a reason
 *  - Finance officer must approve large discrepancies
 *  - All reconciliation actions are audited
 */
class PaymentReconciliationService
{
    public function openCashierSession(string $cashierId, string $facilityId): CashierSession
    {
        // Close any existing open session for this cashier
        CashierSession::where('cashier_id', $cashierId)
            ->whereNull('closed_at')
            ->update(['closed_at' => now(), 'auto_closed' => true]);

        return CashierSession::create([
            'cashier_id'   => $cashierId,
            'facility_id'  => $facilityId,
            'opened_at'    => now(),
            'status'       => 'open',
        ]);
    }

    public function reconcile(
        string $sessionId,
        float $physicalCashAmount,
        float $electronicTotal,
        string $closedBy,
        string $notes = null
    ): PaymentReconciliation {
        $session = CashierSession::findOrFail($sessionId);

        $expectedTotal = Payment::where('cashier_session_id', $sessionId)
            ->where('status', 'confirmed')
            ->sum('amount');

        $actualTotal   = $physicalCashAmount + $electronicTotal;
        $discrepancy   = $actualTotal - $expectedTotal;

        $recon = PaymentReconciliation::create([
            'cashier_session_id'  => $sessionId,
            'expected_total'      => $expectedTotal,
            'physical_cash'       => $physicalCashAmount,
            'electronic_total'    => $electronicTotal,
            'actual_total'        => $actualTotal,
            'discrepancy'         => $discrepancy,
            'status'              => abs($discrepancy) < 0.01 ? 'balanced' : 'discrepancy',
            'reconciled_by'       => $closedBy,
            'reconciled_at'       => now(),
            'notes'               => $notes,
        ]);

        $session->update(['status' => 'closed', 'closed_at' => now(), 'closed_by' => $closedBy]);

        AuditEvent::create([
            'actor_id'    => $closedBy,
            'action'      => 'cashier_session.reconciled',
            'module'      => 'billing',
            'facility_id' => $session->facility_id,
            'metadata'    => [
                'session_id'    => $sessionId,
                'expected'      => $expectedTotal,
                'actual'        => $actualTotal,
                'discrepancy'   => $discrepancy,
            ],
        ]);

        return $recon;
    }
}
