<?php

namespace App\Services\ApiBilling;

use App\Models\ApiInvoice;
use App\Models\ApiPlan;
use App\Models\ApiUsageLog;
use App\Models\IntegrationClient;
use App\Services\Payments\MtnMomoService;
use App\Services\Payments\OrangeMoneyService;
use Illuminate\Support\Carbon;

/**
 * ApiBillingService — turns metered API usage into real, payable invoices.
 *
 * Each billing month, every active integration client on a PAID plan gets one
 * invoice: the plan base fee + any overage (usage above the plan quota ×
 * overage price). Invoices are paid via MTN MoMo / Orange Money, and an overdue
 * invoice blocks the client's API access (EnforceApiQuota).
 */
class ApiBillingService
{
    private const DUE_DAYS = 14;

    /**
     * Generate invoices for the given month (defaults to last month). Idempotent
     * — re-running updates the draft figures but never duplicates or re-bills a
     * paid invoice. Returns the number of invoices created/updated.
     */
    public function generateForMonth(?Carbon $month = null): int
    {
        $month       = ($month ?? now()->subMonth())->copy();
        $periodStart = $month->copy()->startOfMonth();
        $periodEnd   = $month->copy()->endOfMonth();
        $count       = 0;

        IntegrationClient::where('status', 'active')->chunkById(200, function ($clients) use ($periodStart, $periodEnd, &$count) {
            foreach ($clients as $client) {
                $plan = ApiPlan::forKey($client->api_plan_key ?: 'sandbox');

                // Only bill paid plans (free/sandbox never produces a charge).
                if (! $plan || ($plan->price_xaf <= 0 && (float) $plan->overage_price_xaf <= 0)) {
                    continue;
                }

                $used     = ApiUsageLog::where('integration_client_id', $client->client_id)
                    ->whereBetween('logged_at', [$periodStart->copy()->startOfDay(), $periodEnd->copy()->endOfDay()])
                    ->count();
                $included = $plan->monthly_request_quota; // null = unlimited
                $overage  = $included === null ? 0 : max(0, $used - $included);
                $overageX = (int) ceil($overage * (float) $plan->overage_price_xaf);
                $base     = (int) $plan->price_xaf;

                $existing = ApiInvoice::where('client_id', $client->client_id)
                    ->whereDate('period_start', $periodStart)
                    ->first();

                // Never touch an already-paid invoice.
                if ($existing && $existing->isPaid()) {
                    continue;
                }

                ApiInvoice::updateOrCreate(
                    ['client_id' => $client->client_id, 'period_start' => $periodStart->toDateString()],
                    [
                        'facility_id'        => $client->facility_id,
                        'plan_key'           => $plan->key,
                        'period_end'         => $periodEnd->toDateString(),
                        'included_requests'  => $included,
                        'used_requests'      => $used,
                        'overage_requests'   => $overage,
                        'base_amount_xaf'    => $base,
                        'overage_amount_xaf' => $overageX,
                        'total_xaf'          => $base + $overageX,
                        'currency'           => 'XAF',
                        'status'             => 'issued',
                        'issued_at'          => now(),
                        'due_at'             => now()->addDays(self::DUE_DAYS),
                    ]
                );
                $count++;
            }
        });

        return $count;
    }

    /**
     * Initiate payment for an invoice via MTN MoMo or Orange Money. The charge
     * is async — the invoice stays 'issued' (pending) until the provider
     * callback / confirmPayment() marks it paid. Returns the gateway result.
     */
    public function payInvoice(ApiInvoice $invoice, string $provider, string $phone): array
    {
        if ($invoice->isPaid()) {
            return ['success' => true, 'already_paid' => true];
        }

        $result = $this->gateway($provider)->requestPayment(
            $phone,
            (float) $invoice->total_xaf,
            'XAF',
            $invoice->number(),
            'OpesCare API plan ' . $invoice->plan_key . ' — ' . optional($invoice->period_start)->format('M Y')
        );

        if (! empty($result['success'])) {
            $invoice->update([
                'payment_provider'  => $provider,
                'payment_reference' => $result['reference_id'] ?? $result['provider_ref'] ?? null,
            ]);
        }

        return $result;
    }

    /** Poll the provider and mark the invoice paid if the charge succeeded. */
    public function confirmPayment(ApiInvoice $invoice): bool
    {
        if ($invoice->isPaid() || ! $invoice->payment_provider || ! $invoice->payment_reference) {
            return $invoice->isPaid();
        }

        $status = $this->gateway($invoice->payment_provider)->checkStatus($invoice->payment_reference);

        if (($status['status'] ?? null) === 'SUCCESSFUL') {
            $invoice->markPaid($invoice->payment_provider, $invoice->payment_reference);
            return true;
        }

        return false;
    }

    /** Flip past-due issued invoices to 'overdue'. Returns the count flipped. */
    public function markOverdue(): int
    {
        return ApiInvoice::where('status', 'issued')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->update(['status' => 'overdue']);
    }

    /** Does this client have an unpaid, past-due invoice? (Used for dunning.) */
    public static function clientIsPastDue(string $clientId): bool
    {
        return ApiInvoice::where('client_id', $clientId)
            ->where(function ($q) {
                $q->where('status', 'overdue')
                  ->orWhere(fn ($q2) => $q2->where('status', 'issued')->whereNotNull('due_at')->where('due_at', '<', now()));
            })
            ->exists();
    }

    private function gateway(string $provider): \App\Contracts\PaymentProvider
    {
        return match ($provider) {
            'mtn_momo'     => new MtnMomoService(),
            'orange_money' => new OrangeMoneyService(),
            default        => throw new \InvalidArgumentException("Unknown provider: {$provider}"),
        };
    }
}
