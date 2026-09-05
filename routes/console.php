<?php

// OpesCare Finder extraction — surface reduced from the platform on 2026-09-02;
// the platform's sealed console.php remains authoritative for the platform.
//
// Removed schedule lines whose commands/surfaces left with their modules:
// health-id:notify-expiring, health-id:generate-minsante-report,
// health-id:archive-audit-logs, health-id:purge-bulk-exports (FHIR),
// subscriptions:lifecycle. Note: blood:expire-requests and other Finder
// schedules are registered in bootstrap/app.php's withSchedule().

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// OpesCare: automated encrypted backup schedule (PR-10 Task 2)
Schedule::command('backup:run')->dailyAt('01:00');
Schedule::command('backup:monitor')->dailyAt('09:00');
Schedule::command('backup:clean')->daily();

// OpesCare: data retention enforcement (PR-9 Task 2)
Schedule::command('opescare:enforce-data-retention')->dailyAt('02:00');

// OpesCare: consent grants whose validity has lapsed.
//
// `consent_grants.status` was written at approval and never swept, so a grant
// long past its expires_at still read `active`. No access decision was ever
// wrong because of it — every gate checks the DATE as well as the column — but
// GenerateMinsanteMonthlyReport groups by status alone, so lapsed consents were
// reported to the health ministry as live. One writer, so the column stops
// disagreeing with reality.
Schedule::command('consent:expire-lapsed')->hourly()->withoutOverlapping();

// OpesCare: execute account closures whose 30-day grace period has run out.
// The grace period is the whole safety property here — a patient who taps
// delete by mistake has a month to take it back, and the account stays usable
// throughout because locking it would remove the session needed to cancel.
Schedule::command('opescare:execute-account-deletions')->dailyAt('02:30')->withoutOverlapping();

// OpesCare: mobile-money charges abandoned mid-flight.
//
// A charge still self-closes on the next poll or the patient's next attempt;
// this just means it happens without needing a visitor to trigger it, so an
// abandoned charge cannot sit `pending` against an invoice indefinitely.
Schedule::call(fn () => app(\App\Services\Payments\PaymentChargeService::class)->expireLapsed())
    ->everyFiveMinutes()
    ->name('payments:expire-lapsed-charges')
    ->withoutOverlapping();

// OpesCare: JWT JTI blacklist cleanup — Connect partner tokens can be revoked;
// entries past their exp timestamp are meaningless and can be pruned.
Schedule::command('health-id:purge-revoked-tokens')->dailyAt('00:30');

// OpesCare: Maintenance window auto-activation / auto-expiry
// Runs every minute; activates windows whose starts_at has arrived and
// expires windows whose ends_at has passed. Flushes the maintenance cache
// so enforcement is near-real-time without waiting for the 5-min TTL.
Schedule::command('maintenance:process')->everyMinute()->withoutOverlapping();

// OpesCare: Appointment reminders
// Runs every 5 minutes; dispatches SMS/push/email for reminders whose
// scheduled_at has arrived. Uses withoutOverlapping() to prevent pile-up
// if a run takes longer than the interval.
Schedule::command('opescare:send-appointment-reminders')->everyFiveMinutes()->withoutOverlapping();

// OpesCare: Provider credential expiry notifications
// Runs daily at 07:30; alerts administrators of credentials expiring within 30 days.
Schedule::command('opescare:notify-expiring-credentials')->dailyAt('07:30');
