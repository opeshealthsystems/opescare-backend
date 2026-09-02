<?php

namespace App\Console\Commands;

use App\Mail\OpesCareNotificationMail;
use App\Models\OrganizationSubscription;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Subscription lifecycle messaging automation (no live payments involved — this
 * is messaging only). Two passes per run:
 *
 *   1. Renewal reminders — active, auto-renewing PREMIUM patient subscriptions
 *      whose current period ends within the next 7 days and which have not yet
 *      been reminded for the current period.
 *   2. Win-back — patient subscriptions that recently lapsed (expired/cancelled,
 *      or downgraded to a free plan) with a period end 1–14 days ago and no
 *      win-back message sent yet.
 *
 * Dedupe is enforced via organization_subscriptions.renewal_reminded_at /
 * winback_sent_at. Every send is wrapped in try/catch so a single failure never
 * aborts the run.
 */
class RunSubscriptionLifecycle extends Command
{
    protected $signature = 'subscriptions:lifecycle';

    protected $description = 'Queue subscription renewal reminders and win-back messages for patient subscriptions';

    public function handle(): int
    {
        $reminders = $this->sendRenewalReminders();
        $winbacks  = $this->sendWinbacks();

        $this->info("Subscription lifecycle complete: {$reminders} renewal reminder(s), {$winbacks} win-back(s) queued.");
        Log::info('subscriptions:lifecycle run complete', [
            'renewal_reminders' => $reminders,
            'winbacks'          => $winbacks,
        ]);

        return self::SUCCESS;
    }

    private function sendRenewalReminders(): int
    {
        $now       = now();
        $windowEnd = $now->copy()->addDays(7);
        $sent      = 0;

        $subs = OrganizationSubscription::query()
            ->with('plan')
            ->where('subscriber_type', 'patient')
            ->where('status', 'active')
            ->where('auto_renew', true)
            ->whereNotNull('current_period_end')
            ->whereBetween('current_period_end', [$now->copy()->startOfDay(), $windowEnd])
            ->get();

        foreach ($subs as $sub) {
            // Skip free plans — there is nothing to renew/charge.
            if (! $sub->plan || $sub->plan->isFree()) {
                continue;
            }

            // Dedupe: only remind if never reminded, or the last reminder predates
            // the current period start (i.e. it was for a previous period).
            if ($sub->renewal_reminded_at !== null
                && $sub->current_period_start !== null
                && $sub->renewal_reminded_at->gte($sub->current_period_start)) {
                continue;
            }

            if ($this->queueMessage($sub, 'renewal')) {
                $sub->forceFill(['renewal_reminded_at' => $now])->save();
                $sent++;
            }
        }

        return $sent;
    }

    private function sendWinbacks(): int
    {
        $now   = now();
        $from  = $now->copy()->subDays(14)->startOfDay();
        $to    = $now->copy()->subDay()->endOfDay();
        $sent  = 0;

        $subs = OrganizationSubscription::query()
            ->with('plan')
            ->where('subscriber_type', 'patient')
            ->whereNull('winback_sent_at')
            ->whereNotNull('current_period_end')
            ->whereBetween('current_period_end', [$from, $to])
            ->get();

        foreach ($subs as $sub) {
            // Lapsed = expired/cancelled status OR downgraded to a free plan.
            $lapsed = in_array($sub->status, ['expired', 'cancelled'], true)
                || ($sub->plan && $sub->plan->isFree());

            if (! $lapsed) {
                continue;
            }

            if ($this->queueMessage($sub, 'winback')) {
                $sub->forceFill(['winback_sent_at' => $now])->save();
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Resolve recipient + locale and queue the localized message.
     * Returns true if queued, false if skipped (missing email / error).
     */
    private function queueMessage(OrganizationSubscription $sub, string $type): bool
    {
        try {
            $patient = $this->resolvePatient($sub);
            $email   = $this->resolveEmail($sub, $patient);

            if (! $email) {
                Log::warning('subscriptions:lifecycle skipped — no recipient email', [
                    'subscription_id' => $sub->id,
                    'type'            => $type,
                ]);
                return false;
            }

            $locale   = $this->resolveLocale($patient);
            $planName = $sub->plan?->name ?? 'Premium';
            $name     = $patient?->first_name ?: 'OpesCare';
            $date     = optional($sub->current_period_end)->format('Y-m-d') ?? '';
            $link     = route('portals.patient.subscription');

            $subject = __("lifecycle.{$type}.subject", ['plan' => $planName], $locale);
            $body    = __("lifecycle.{$type}.body", [
                'name' => $name,
                'plan' => $planName,
                'date' => $date,
                'link' => $link,
            ], $locale);

            Mail::to($email)->queue(new OpesCareNotificationMail(
                mailSubject: $subject,
                bodyText: $body,
            ));

            return true;
        } catch (\Throwable $e) {
            Log::error('subscriptions:lifecycle send failed', [
                'subscription_id' => $sub->id,
                'type'            => $type,
                'error'           => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function resolvePatient(OrganizationSubscription $sub): ?Patient
    {
        if ($sub->subscriber_type !== 'patient' || ! $sub->subscriber_id) {
            return null;
        }
        return Patient::find($sub->subscriber_id);
    }

    private function resolveEmail(OrganizationSubscription $sub, ?Patient $patient): ?string
    {
        if ($patient && ! empty($patient->email)) {
            return $patient->email;
        }

        if ($patient) {
            $user = User::where('patient_id', $patient->id)->first();
            if ($user && ! empty($user->email)) {
                return $user->email;
            }
        }

        // Fall back to the billing email captured on the subscription itself.
        return ! empty($sub->billing_email) ? $sub->billing_email : null;
    }

    private function resolveLocale(?Patient $patient): string
    {
        $default = config('app.locale', 'en');

        $prefs = $patient?->privacy_preferences;
        if (is_array($prefs) && ! empty($prefs['locale'])) {
            return $prefs['locale'];
        }

        return $default;
    }
}
