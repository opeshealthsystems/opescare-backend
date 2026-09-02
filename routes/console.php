<?php

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

// OpesCare: Health ID expiry notifications (Wave 2 — Health ID module hardening)
// Runs daily at 08:00; patients with IDs expiring within 90 days get an in-app alert.
Schedule::command('health-id:notify-expiring')->dailyAt('08:00');

// OpesCare: MINSANTE monthly compliance report (Wave 3 — data rights & audit)
// Runs on the 1st of each month at 06:00; covers the previous complete month.
Schedule::command('health-id:generate-minsante-report')->monthlyOn(1, '06:00');

// OpesCare: Audit log archival (Wave 3 — retention policy)
// Runs on the 2nd of each month at 03:00; archives rows older than 12 months
// to storage/app/audit-archive/ and prunes the hot table.
Schedule::command('health-id:archive-audit-logs')->monthlyOn(2, '03:00');

// OpesCare: JWT JTI blacklist cleanup (Migration Sprint — Item 2)
// Runs nightly at 00:30; deletes expired rows from revoked_tokens where
// expires_at < now(). Tokens expire naturally, so revocation entries past their
// exp timestamp are meaningless and can be pruned.
Schedule::command('health-id:purge-revoked-tokens')->dailyAt('00:30');

// OpesCare: FHIR bulk export job cleanup (Migration Sprint — Item 4)
// Runs nightly at 01:00; deletes expired BulkExportJob rows and removes
// NDJSON output files from storage/app/fhir-exports/{jobId}/.
Schedule::command('health-id:purge-bulk-exports')->dailyAt('01:00');

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

// OpesCare: Subscription lifecycle messaging (renewal reminders + win-back)
// Runs daily at 08:00; queues renewal reminders for premium patient subscriptions
// expiring within 7 days and win-back messages for recently lapsed subscriptions.
// Messaging only — no payments are charged. Dedupe via renewal_reminded_at /
// winback_sent_at so reruns never double-send.
Schedule::command('subscriptions:lifecycle')->dailyAt('08:00')->withoutOverlapping();
