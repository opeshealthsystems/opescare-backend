<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives asynchronous payment confirmation callbacks from MTN MoMo
 * and Orange Money. The callback URL is sent to the provider at initiation
 * time via X-Callback-Url / callbackUrl headers.
 *
 * MTN MoMo POST body:
 *   { "referenceId": "...", "status": "SUCCESSFUL|FAILED", ... }
 *
 * Orange Money POST body:
 *   { "txnid": "...", "status": "SUCCESS|FAILED", "message": "..." }
 *
 * Both endpoints are public (no Bearer auth) — the provider pushes to us.
 * We verify authenticity by matching the reference to an existing pending
 * Payment row rather than relying on a signature header (MTN sandbox omits
 * the X-Callback-Signature; wire in signature verification once live creds
 * are available via config('services.mtn_momo.callback_secret')).
 */
class MobileMoneyCallbackController extends Controller
{
    // ── MTN MoMo ─────────────────────────────────────────────────

    public function mtnCallback(Request $request): \Illuminate\Http\JsonResponse
    {
        if (!$this->verifyCallbackSignature($request, config('services.mtn_momo.callback_secret'))) {
            return response()->json(['error' => __('api.invalid_callback_signature')], 401);
        }

        $referenceId = $request->input('referenceId') ?? $request->input('externalId');
        $status      = strtoupper((string) $request->input('status', ''));

        Log::info('MTN MoMo callback received', ['ref' => $referenceId, 'status' => $status]);

        if (!$referenceId) {
            return response()->json(['error' => __('api.missing_reference_id')], 422);
        }

        $this->finalizePayment($referenceId, $status === 'SUCCESSFUL', 'mtn_momo');

        return response()->json(['received' => true]);
    }

    // ── Orange Money ──────────────────────────────────────────────

    public function orangeCallback(Request $request): \Illuminate\Http\JsonResponse
    {
        if (!$this->verifyCallbackSignature($request, config('services.orange_money.callback_secret'))) {
            return response()->json(['error' => __('api.invalid_callback_signature')], 401);
        }

        $txnId  = $request->input('txnid') ?? $request->input('pay_token');
        $status = strtoupper((string) $request->input('status', ''));

        Log::info('Orange Money callback received', ['txn' => $txnId, 'status' => $status]);

        if (!$txnId) {
            return response()->json(['error' => __('api.missing_txnid')], 422);
        }

        $this->finalizePayment($txnId, $status === 'SUCCESS', 'orange_money');

        return response()->json(['received' => true]);
    }

    // ── Shared finalization ───────────────────────────────────────

    private function verifyCallbackSignature(Request $request, ?string $secret): bool
    {
        if (!$secret) {
            return !app()->isProduction();
        }

        $signature = (string) $request->header('X-Callback-Signature', '');
        if ($signature === '') {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }

    private function finalizePayment(string $reference, bool $success, string $provider): void
    {
        $payment = Payment::where('payment_reference', $reference)
            ->where('method', $provider)
            ->where('status', 'pending')
            ->first();

        if (!$payment) {
            Log::warning("MobileMoney callback: no pending payment found for ref={$reference} provider={$provider}");
            return;
        }

        $payment->status         = $success ? 'successful' : 'failed';
        $payment->gateway        = $provider;
        $payment->gateway_status = $success ? 'SUCCESSFUL' : 'FAILED';
        if ($success) {
            $payment->confirmed_at = now();
        } else {
            $payment->failed_at = now();
        }
        $payment->gateway_metadata = array_merge(
            (array) ($payment->gateway_metadata ?? []),
            ['callback_payload' => request()->all(), 'callback_at' => now()->toIso8601String()]
        );
        $payment->save();

        if ($success) {
            // Mark the associated invoice as paid if all payments cover the balance
            $invoice = $payment->invoice;
            if ($invoice) {
                $paid = Payment::where('invoice_id', $invoice->id)
                    ->where('status', 'successful')
                    ->sum('amount');

                if ($paid >= $invoice->total_amount) {
                    $invoice->update(['status' => 'paid', 'paid_at' => now()]);
                }
            }
        }
    }
}
