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
// Mail deliverability. Every message this platform sent bounced for an unknown
// stretch because the sending domain published a null MX, and nothing said so:
// the MTA accepts a message before it tries to deliver it, so the application
// saw a success every time. A daily check is how that stops being invisible.
Schedule::command('mail:health')->dailyAt('08:00')->withoutOverlapping();
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

// Master Patient Index — queue likely duplicates for a human. Only what
// arrived in the last two days is scanned, against the whole register; it
// never merges. 03:00 is after retention and deletions have run.
Schedule::command('mpi:detect --since="2 days"')->dailyAt('03:00')->withoutOverlapping();
// ...and once a week the whole register, so a night the timer missed cannot
// leave a registration unscanned for good.
Schedule::command('mpi:detect --limit=5000')->weeklyOn(0, '03:30')->withoutOverlapping();

// OpesCare: appointment waitlist entries whose preferred window has passed
// stop waiting. Nothing is deleted — the row reads `expired`.
Schedule::command('waitlist:expire')->dailyAt('04:00')->withoutOverlapping();

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

// OpesCare: patient push receipts.
//
// Expo answers a send with a TICKET ("queued"); whether the handset was
// reached is only known from the RECEIPT, published a few minutes later and
// kept for about a day. This pass reads the receipts for every pending
// push_deliveries row, marks it ok/error, and retires the device token when
// Expo says DeviceNotRegistered — the only way an uninstalled app stops being
// pushed to forever. Every 15 minutes: fast enough that a dead token is
// retired within the hour, slow enough that receipts have had time to appear.
// Silent when EXPO_PUSH_ENABLED=false. See docs/push-delivery-backend.md.
Schedule::command('push:check-receipts')->everyFifteenMinutes()->withoutOverlapping();

// OpesCare: the facility service catalogue → the public lab finder.
//
// Every catalogue write projects on its own (LabCatalogueProjector); this
// nightly pass is for the listing an admin linked AFTER the lab filled its
// catalogue — the link does not itself trigger a projection — and is the
// backfill for catalogues curated before the projector existed. Idempotent,
// nothing deleted. Runs after the blood/MPI night jobs.
Schedule::command('lab:publish-catalogue')->dailyAt('04:30')->withoutOverlapping();

// Group E (DHIS2, docs/PLATFORM_IMPORTS.md): the platform pushed last month's
// aggregate on the 1st at 04:00. The Finder only GENERATES the drafts then;
// a platform admin reviews, approves and pushes from /admin/public-health/dhis2.
Schedule::command('opescare:push-dhis2')->monthlyOn(1, '04:00')->withoutOverlapping();
