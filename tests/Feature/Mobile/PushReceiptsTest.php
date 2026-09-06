<?php

namespace Tests\Feature\Mobile;

use App\Models\Patient;
use App\Models\PushDelivery;
use App\Models\PushDeviceToken;
use App\Support\PatientPush;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The push receipt ledger.
 *
 * A ticket from Expo means "queued", nothing more. What this pins:
 *
 *  1. Every message put on the wire leaves a push_deliveries row naming the
 *     patient, the device and the ticket — or the ticket-time error.
 *  2. `push:check-receipts` reads Expo's receipts onto those rows: ok, or
 *     error with the code. Only DeviceNotRegistered retires the device
 *     token; every other code keeps it and records why.
 *  3. A ticket Expo never reports on is closed once its receipt window has
 *     passed, so nothing stays pending forever.
 *  4. The scheduler runs the command.
 *
 * Expo is never called: every request to exp.host is faked.
 */
class PushReceiptsTest extends TestCase
{
    use RefreshDatabase;

    private const SEND = 'https://exp.host/--/api/v2/push/send';

    private const RECEIPTS = 'https://exp.host/--/api/v2/push/getReceipts';

    private const TOKEN = 'ExponentPushToken[receipts-test-device-0001]';

    private Patient $patient;

    private PushDeviceToken $device;

    protected function setUp(): void
    {
        parent::setUp();

        config(['push_channels.expo.enabled' => true]);

        $this->patient = Patient::create([
            'health_id'     => 'OC-TST-8700-0001-01',
            'first_name'    => 'Rita',
            'last_name'     => 'Receipt',
            'sex'           => 'female',
            'date_of_birth' => '1991-03-03',
        ]);

        $this->device = PushDeviceToken::create([
            'patient_id'         => $this->patient->id,
            'device_fingerprint' => substr(md5(self::TOKEN), 0, 32),
            'platform'           => 'android',
            'push_token'         => self::TOKEN,
            'is_active'          => true,
            'registered_at'      => now(),
        ]);
    }

    /** @param  array<string, mixed>  $receipts  ticket id => receipt */
    private function fakeExpo(mixed $send = null, array $receipts = []): void
    {
        Http::fake([
            self::SEND     => $send ?? Http::response(['data' => [['status' => 'ok', 'id' => 'ticket-0001']]], 200),
            self::RECEIPTS => Http::response(['data' => $receipts], 200),
            '*'            => Http::response([], 200),
        ]);
    }

    private function pendingDelivery(string $ticket = 'ticket-0001', int $minutesAgo = 20): PushDelivery
    {
        return PushDelivery::create([
            'patient_id'           => $this->patient->id,
            'push_device_token_id' => $this->device->id,
            'platform'             => 'android',
            'type'                 => 'message',
            'ticket_id'            => $ticket,
            'status'               => PushDelivery::STATUS_PENDING,
            'sent_at'              => now()->subMinutes($minutesAgo),
        ]);
    }

    private function receiptRequests(): int
    {
        return count(Http::recorded(fn (ClientRequest $r) => $r->url() === self::RECEIPTS));
    }

    private function sendRequests(): int
    {
        return count(Http::recorded(fn (ClientRequest $r) => $r->url() === self::SEND));
    }

    // ── the send writes the ledger ──────────────────────────────────────

    public function test_a_send_persists_one_pending_delivery_per_message(): void
    {
        $this->fakeExpo();

        $result = PatientPush::send($this->patient, 'message', 'New message', 'You have a new message.', ['thread_id' => 't-1']);

        $this->assertTrue($result->queued());
        $this->assertSame(['ticket-0001'], $result->ticketIds);

        $row = PushDelivery::sole();
        $this->assertSame($this->patient->id, $row->patient_id);
        $this->assertSame($this->device->id, $row->push_device_token_id);
        $this->assertSame('android', $row->platform);
        $this->assertSame('message', $row->type);
        $this->assertSame('ticket-0001', $row->ticket_id);
        $this->assertSame(PushDelivery::STATUS_PENDING, $row->status);
        $this->assertNull($row->error_code);
        $this->assertNotNull($row->sent_at);
        $this->assertNull($row->receipt_checked_at);
    }

    public function test_a_ticket_time_error_is_recorded_and_the_token_is_kept(): void
    {
        $this->fakeExpo(Http::response(['data' => [[
            'status'  => 'error',
            'message' => 'Message too big',
            'details' => ['error' => 'MessageTooBig'],
        ]]], 200));

        $result = PatientPush::send($this->patient, 'message', 'T', 'B');

        $this->assertSame(1, $result->rejected);

        $row = PushDelivery::sole();
        $this->assertSame(PushDelivery::STATUS_ERROR, $row->status);
        $this->assertSame('MessageTooBig', $row->error_code);
        $this->assertNull($row->ticket_id);
        $this->assertSame('Message too big', $row->error_details['message']);

        $this->assertTrue($this->device->fresh()->is_active, 'A message error is not a dead handset.');
    }

    public function test_a_ticket_time_device_not_registered_retires_the_token_at_once(): void
    {
        $this->fakeExpo(Http::response(['data' => [[
            'status'  => 'error',
            'message' => 'not registered',
            'details' => ['error' => 'DeviceNotRegistered'],
        ]]], 200));

        $result = PatientPush::send($this->patient, 'message', 'T', 'B');

        $this->assertSame(1, $result->retired);
        $this->assertFalse($this->device->fresh()->is_active);
        $this->assertSame('DeviceNotRegistered', PushDelivery::sole()->error_code);
    }

    public function test_a_transport_failure_is_recorded_as_an_error_row(): void
    {
        $this->fakeExpo(fn () => throw new ConnectionException('exp.host unreachable'));

        $result = PatientPush::send($this->patient, 'appointment', 'T', 'B');

        $this->assertNotNull($result->transportError);

        $row = PushDelivery::sole();
        $this->assertSame(PushDelivery::STATUS_ERROR, $row->status);
        $this->assertSame(PushDelivery::ERROR_TRANSPORT, $row->error_code);
        $this->assertSame('appointment', $row->type);
        $this->assertNull($row->ticket_id);
        $this->assertTrue($this->device->fresh()->is_active);
    }

    // ── the receipt pass ────────────────────────────────────────────────

    public function test_check_receipts_marks_a_delivered_message_ok(): void
    {
        $this->fakeExpo(receipts: ['ticket-0001' => ['status' => 'ok']]);
        $this->pendingDelivery();

        $this->artisan('push:check-receipts')
            ->expectsOutputToContain('1 ok')
            ->assertExitCode(0);

        $row = PushDelivery::sole();
        $this->assertSame(PushDelivery::STATUS_OK, $row->status);
        $this->assertNotNull($row->receipt_checked_at);
        $this->assertNull($row->error_code);
        $this->assertTrue($this->device->fresh()->is_active);

        // The receipt request carried exactly our ticket.
        Http::assertSent(fn (ClientRequest $r) => $r->url() === self::RECEIPTS && $r->data() === ['ids' => ['ticket-0001']]);
    }

    public function test_check_receipts_records_other_errors_and_keeps_the_token(): void
    {
        $this->fakeExpo(receipts: ['ticket-0001' => [
            'status'  => 'error',
            'message' => 'rate exceeded',
            'details' => ['error' => 'MessageRateExceeded'],
        ]]);
        $this->pendingDelivery();

        $this->artisan('push:check-receipts')
            ->expectsOutputToContain('1 error')
            ->assertExitCode(0);

        $row = PushDelivery::sole();
        $this->assertSame(PushDelivery::STATUS_ERROR, $row->status);
        $this->assertSame('MessageRateExceeded', $row->error_code);
        $this->assertSame('rate exceeded', $row->error_details['message']);
        $this->assertNotNull($row->receipt_checked_at);

        $this->assertTrue($this->device->fresh()->is_active, 'Only DeviceNotRegistered may retire a token.');
        $this->assertNull($this->device->fresh()->revoked_at);
    }

    public function test_device_not_registered_at_receipt_time_revokes_the_token_and_it_is_never_sent_to_again(): void
    {
        $this->fakeExpo(receipts: ['ticket-0001' => [
            'status'  => 'error',
            'message' => 'The recipient device is not registered',
            'details' => ['error' => 'DeviceNotRegistered', 'expoPushToken' => self::TOKEN],
        ]]);
        $this->pendingDelivery();

        $this->artisan('push:check-receipts')
            ->expectsOutputToContain('1 token(s) retired')
            ->assertExitCode(0);

        $device = $this->device->fresh();
        $this->assertFalse($device->is_active);
        $this->assertNotNull($device->revoked_at);
        $this->assertSame('DeviceNotRegistered', PushDelivery::sole()->error_code);

        // The proof that matters: the next push for this patient never reaches Expo.
        $before = $this->sendRequests();
        $result = PatientPush::send($this->patient, 'message', 'T', 'B');
        $this->assertSame($before, $this->sendRequests());
        $this->assertTrue($result->hadNothingToSendTo());
    }

    public function test_device_not_registered_without_the_token_in_the_receipt_still_revokes_through_the_ledger(): void
    {
        $this->fakeExpo(receipts: ['ticket-0001' => [
            'status'  => 'error',
            'details' => ['error' => 'DeviceNotRegistered'],
        ]]);
        $this->pendingDelivery();

        $this->artisan('push:check-receipts')->assertExitCode(0);

        $this->assertFalse($this->device->fresh()->is_active, 'The ledger row knows its device; the receipt need not name the token.');
    }

    public function test_a_ticket_expo_never_reports_on_is_closed_after_the_receipt_window(): void
    {
        $this->fakeExpo(receipts: []);
        $old   = $this->pendingDelivery('ticket-old', 30 * 60);   // 30 hours — past Expo's 24h window
        $fresh = $this->pendingDelivery('ticket-new', 20);

        $this->artisan('push:check-receipts')
            ->expectsOutputToContain('1 closed as receipt unavailable, 1 still pending')
            ->assertExitCode(0);

        $old->refresh();
        $this->assertSame(PushDelivery::STATUS_ERROR, $old->status);
        $this->assertSame(PushDelivery::ERROR_RECEIPT_UNAVAILABLE, $old->error_code);
        $this->assertNotNull($old->receipt_checked_at);

        $fresh->refresh();
        $this->assertSame(PushDelivery::STATUS_PENDING, $fresh->status);
        $this->assertNotNull($fresh->receipt_checked_at, 'An attempt was made; the row says when.');

        $this->assertTrue($this->device->fresh()->is_active, 'Nothing is known, so nothing is retired.');
    }

    public function test_tickets_younger_than_min_age_are_not_asked_about(): void
    {
        $this->fakeExpo(receipts: ['ticket-0001' => ['status' => 'ok']]);
        $this->pendingDelivery('ticket-0001', 1);

        $this->artisan('push:check-receipts', ['--min-age' => 5])
            ->expectsOutputToContain('Checked 0 pending ticket(s)')
            ->assertExitCode(0);

        $this->assertSame(0, $this->receiptRequests());
        $this->assertSame(PushDelivery::STATUS_PENDING, PushDelivery::sole()->status);
    }

    public function test_a_failed_lookup_leaves_rows_pending_for_the_next_run(): void
    {
        Http::fake([
            self::RECEIPTS => fn () => throw new ConnectionException('exp.host unreachable'),
            '*'            => Http::response([], 200),
        ]);
        $this->pendingDelivery();

        $this->artisan('push:check-receipts')
            ->expectsOutputToContain('receipt lookup failed')
            ->assertExitCode(1);

        $this->assertSame(PushDelivery::STATUS_PENDING, PushDelivery::sole()->status);
        $this->assertTrue($this->device->fresh()->is_active);
    }

    public function test_a_switched_off_host_asks_expo_nothing(): void
    {
        config(['push_channels.expo.enabled' => false]);
        $this->fakeExpo(receipts: ['ticket-0001' => ['status' => 'ok']]);
        $this->pendingDelivery();

        $this->artisan('push:check-receipts')
            ->expectsOutputToContain('disabled')
            ->assertExitCode(0);

        $this->assertSame(0, $this->receiptRequests());
    }

    public function test_resolved_rows_older_than_the_prune_window_are_deleted_and_pending_ones_kept(): void
    {
        $this->fakeExpo(receipts: []);
        PushDelivery::create([
            'patient_id' => $this->patient->id,
            'type'       => 'message',
            'ticket_id'  => 'ticket-ancient',
            'status'     => PushDelivery::STATUS_OK,
            'sent_at'    => now()->subDays(120),
        ]);
        $pending = $this->pendingDelivery('ticket-0001', 20);

        $this->artisan('push:check-receipts', ['--prune-days' => 90])
            ->expectsOutputToContain('Pruned 1')
            ->assertExitCode(0);

        $this->assertSame(1, PushDelivery::count());
        $this->assertTrue(PushDelivery::whereKey($pending->id)->exists());
    }

    // ── the scheduler ───────────────────────────────────────────────────

    public function test_the_scheduler_runs_the_receipt_check_every_fifteen_minutes(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($event) => str_contains((string) $event->command, 'push:check-receipts'));

        $this->assertCount(1, $events, 'push:check-receipts must be scheduled exactly once (routes/console.php).');
        $this->assertSame('*/15 * * * *', $events->first()->expression);
    }
}
