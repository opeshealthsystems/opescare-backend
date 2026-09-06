<?php

namespace App\Console\Commands;

use App\Models\HealthIdAlias;
use App\Models\Patient;
use App\Models\PushDelivery;
use App\Models\PushDeviceToken;
use App\Services\ExpoPushService;
use App\Support\PatientPush;
use App\Support\PushChannels;
use Illuminate\Console\Command;

/**
 * opescare:push-test — send one real push to one patient's phones and say,
 * honestly, what happened.
 *
 * The whole delivery chain is exercised: the patient's active device rows,
 * the type → channel → sound map, the patient's own preference switch, the
 * Expo request, the tickets, and the push_deliveries ledger. Each step that
 * can stop the push prints why it did ("push disabled", "no active token",
 * "suppressed by push_messages") instead of a cheerful "sent".
 *
 * Refuses non-demo patients unless --force: a test push on a real person's
 * lock screen is a real notification.
 */
class PushTest extends Command
{
    protected $signature = 'opescare:push-test
                            {health_id : The patient\'s Health ID (an active alias is followed)}
                            {--type=message : data.type — one of the types in config/push_channels.php}
                            {--force : Allow a patient who is not a demo record}
                            {--receipts : Ask Expo for the receipts straight after the send (usually not available yet)}';

    protected $description = 'Send one test push to a patient\'s registered phones through PatientPush and print the tickets.';

    public function handle(ExpoPushService $expo): int
    {
        $type    = (string) $this->option('type');
        $patient = $this->findPatient((string) $this->argument('health_id'));

        if ($patient === null) {
            $this->error('No patient holds that Health ID (live or as an active alias).');

            return self::FAILURE;
        }

        $this->line(sprintf('Patient %s (%s)', $patient->health_id, $patient->is_demo ? 'demo record' : 'REAL record'));

        if (! $patient->is_demo && ! $this->option('force')) {
            $this->error('Refusing: this is not a demo patient. A test push lands on a real person\'s lock screen. Pass --force if that is intended.');

            return self::FAILURE;
        }

        if (! $expo->isConfigured()) {
            $this->warn('push disabled — EXPO_PUSH_ENABLED=false on this host. Nothing was sent.');

            return self::FAILURE;
        }

        $devices = PushDeviceToken::query()
            ->where('patient_id', $patient->getKey())
            ->active()
            ->orderByDesc('registered_at')
            ->get();

        if ($devices->isEmpty()) {
            $this->warn('no active token — this patient has no registered device. Nothing was sent.');

            return self::FAILURE;
        }

        $this->table(
            ['Platform', 'Registered', 'Push token', 'Deliverable'],
            $devices->map(fn (PushDeviceToken $d) => [
                $d->platform,
                $d->registered_at?->toDateTimeString() ?? '-',
                PushDeviceToken::mask($d->push_token),
                $d->isDeliverable() ? 'yes' : 'no (fingerprint-only)',
            ])->all(),
        );

        if ($devices->filter(fn (PushDeviceToken $d) => $d->isDeliverable())->isEmpty()) {
            $this->warn('no active token — every registered device is fingerprint-only (no Expo push token). Nothing was sent.');

            return self::FAILURE;
        }

        if (! array_key_exists($type, PushChannels::types())) {
            $this->warn("Type '{$type}' is not in config/push_channels.php; it will ride the 'default' channel.");
        }

        $preference = PatientPush::preferenceFor($type);
        $this->line(sprintf(
            'Type %s → channel %s, sound %s%s',
            $type,
            PushChannels::channelFor($type),
            PushChannels::soundForType($type),
            $preference === null ? ' (never suppressed)' : " (governed by mobile_app_settings.{$preference})",
        ));

        $locale    = PatientPush::localeFor($patient);
        $startedAt = now();

        $result = PatientPush::send(
            $patient,
            $type,
            __('push.test.title', [], $locale),
            __('push.test.body', ['when' => now()->locale($locale)->isoFormat('LLL')], $locale),
            ['action_url' => '/settings', 'sent_by' => 'opescare:push-test'],
        );

        if ($result->suppressed()) {
            $this->warn(sprintf(
                'suppressed — the patient switched %s off in the app. consent_request, family_invitation and emergency_access are never suppressed; every other type honours its switch.',
                $result->suppressedBy,
            ));

            return self::FAILURE;
        }

        if ($result->transportError !== null) {
            $this->error('Expo could not be reached: '.$result->transportError);
        }

        $this->line(sprintf(
            'Expo: %d accepted, %d rejected, %d skipped (no token), %d token(s) retired.',
            $result->accepted,
            $result->rejected,
            $result->skippedNoToken,
            $result->retired,
        ));

        foreach ($result->errors as $error) {
            $this->warn('  ticket error: '.$error);
        }

        $this->printLedger($patient, $startedAt);

        if ($this->option('receipts') && $result->ticketIds !== []) {
            $this->line('');
            $this->line('Asking Expo for receipts now…');
            $failures = $expo->checkReceipts($result->ticketIds);

            $rows = $this->ledgerRows($patient, $startedAt);
            $this->printLedger($patient, $startedAt, $rows);

            if ($rows->where('status', PushDelivery::STATUS_PENDING)->isNotEmpty()) {
                $this->line('Receipt not available yet — Expo publishes receipts a few minutes after the send. push:check-receipts runs every 15 minutes and will resolve these rows.');
            }

            foreach ($failures as $ticket => $code) {
                $this->warn("  receipt {$ticket}: {$code}");
            }
        }

        return $result->queued() ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Health ID → patient, across the demo partition.
     *
     * Patient carries the IsDemoRecord global scope, which shows either demo
     * or real records depending on OPESCARE_DEMO_MODE — never both. A test
     * command has to see the whole register to say "that is a real record,
     * refusing" instead of "not found".
     */
    private function findPatient(string $healthId): ?Patient
    {
        $key = strtoupper(trim($healthId));

        if ($key === '' || strlen($key) > 64) {
            return null;
        }

        $patient = Patient::query()->withoutGlobalScope('isolate_demo')->where('health_id', $key)->first();

        if ($patient !== null) {
            return $patient;
        }

        $alias = HealthIdAlias::query()->where('alias_value', $key)->where('status', 'active')->first();

        return $alias === null
            ? null
            : Patient::query()->withoutGlobalScope('isolate_demo')->whereKey($alias->patient_id)->first();
    }

    private function ledgerRows(Patient $patient, \DateTimeInterface $since)
    {
        return PushDelivery::query()
            ->where('patient_id', $patient->getKey())
            ->where('sent_at', '>=', $since)
            ->orderBy('sent_at')
            ->get();
    }

    private function printLedger(Patient $patient, \DateTimeInterface $since, $rows = null): void
    {
        $rows ??= $this->ledgerRows($patient, $since);

        if ($rows->isEmpty()) {
            $this->line('No push_deliveries rows were written for this send.');

            return;
        }

        $this->table(
            ['Platform', 'Ticket', 'Status', 'Error', 'Receipt checked'],
            $rows->map(fn (PushDelivery $d) => [
                $d->platform ?? '-',
                $d->ticket_id ?? '-',
                $d->status,
                $d->error_code ?? '',
                $d->receipt_checked_at?->toDateTimeString() ?? '-',
            ])->all(),
        );
    }
}
