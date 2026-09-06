<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Can this deployment actually deliver an email?
 *
 * WHY THIS EXISTS. On 2026-09-06 every message this platform had ever sent was
 * bouncing, and nothing anywhere said so. `opescare.cloud` published a null MX
 * (RFC 7505: "this domain accepts no mail"), so receiving servers rejected the
 * sender outright with 550 5.7.27 — and because Postfix accepts a message
 * before it tries to deliver it, Laravel reported every send as a success. A
 * patient asking for a password reset was told to check their inbox for a
 * message that had already been refused.
 *
 * That failure is invisible from inside the application, which is exactly why
 * it survived. This command looks at the things that actually decide whether
 * mail arrives — the sender's DNS — and says plainly which of them are wrong.
 *
 *   php artisan mail:health
 *   php artisan mail:health --send=someone@example.org   (also sends one probe)
 *
 * Exit code is non-zero when something would stop mail being delivered, so a
 * deploy or a monitor can treat it as a gate rather than a report nobody reads.
 */
class MailHealthCheck extends Command
{
    protected $signature = 'mail:health
        {--send= : Also send one probe message to this address}';

    protected $description = 'Check whether this deployment can actually deliver email';

    /** @var list<string> */
    private array $failures = [];

    /** @var list<string> */
    private array $warnings = [];

    public function handle(): int
    {
        $from = (string) config('mail.from.address');
        $this->line('Sending as: <info>' . ($from ?: '(not configured)') . '</info>');

        if ($from === '' || ! str_contains($from, '@')) {
            $this->blocking('mail.from.address is not a usable address.');

            return $this->report();
        }

        $domain = strtolower(substr(strrchr($from, '@'), 1));
        $this->line('Sender domain: <info>' . $domain . '</info>');
        $this->newLine();

        $this->checkNullMx($domain);
        $this->checkSpf($domain);
        $this->checkDkim($domain);
        $this->checkDmarc($domain);
        $this->checkTransport();

        if ($to = $this->option('send')) {
            $this->probe((string) $to);
        }

        return $this->report();
    }

    /**
     * The one that actually broke this platform.
     *
     * A null MX is a single "." target at priority 0. It is a deliberate
     * statement that the domain accepts no mail, and receiving servers use it
     * to reject the SENDER, not just to refuse delivery to it.
     */
    private function checkNullMx(string $domain): void
    {
        $records = @dns_get_record($domain, DNS_MX) ?: [];

        $null = array_filter(
            $records,
            fn (array $r) => (int) ($r['pri'] ?? -1) === 0 && in_array(trim((string) ($r['target'] ?? '')), ['', '.'], true)
        );

        if ($null !== []) {
            $this->blocking(
                "{$domain} publishes a NULL MX (RFC 7505), which tells every receiving server that it "
                . 'accepts no mail. They reject messages FROM it with 550 5.7.27, so nothing this platform '
                . 'sends can be delivered. Remove that record, or send from a domain that accepts mail.'
            );

            return;
        }

        $this->ok('No null MX on ' . $domain . '.');
    }

    private function checkSpf(string $domain): void
    {
        $spf = $this->txt($domain, 'v=spf1');

        if ($spf === null) {
            $this->caution('No SPF record on ' . $domain . '. Most receivers will treat the mail as suspicious.');

            return;
        }

        $this->ok('SPF present: ' . $spf);
    }

    /**
     * DKIM is checked by looking for the selectors that are actually in use;
     * there is no way to enumerate them from DNS, so this reports rather than
     * fails.
     */
    private function checkDkim(string $domain): void
    {
        foreach (['default', 'mail', 'dkim', 's1', 'k1', 'google', 'selector1'] as $selector) {
            if ($this->txt($selector . '._domainkey.' . $domain, 'v=DKIM1') !== null) {
                $this->ok('DKIM found on selector "' . $selector . '".');

                return;
            }
        }

        $this->caution(
            'No DKIM record found on the usual selectors. Gmail and Outlook now require authenticated mail '
            . 'from bulk senders, so unsigned mail is increasingly filtered.'
        );
    }

    private function checkDmarc(string $domain): void
    {
        $dmarc = $this->txt('_dmarc.' . $domain, 'v=DMARC1');

        if ($dmarc === null) {
            $this->caution('No DMARC record on ' . $domain . '.');

            return;
        }

        $this->ok('DMARC present: ' . $dmarc);
    }

    /** Is the configured SMTP host actually accepting connections? */
    private function checkTransport(): void
    {
        if (config('mail.default') !== 'smtp') {
            $this->ok('Mailer is "' . config('mail.default') . '"; no SMTP host to reach.');

            return;
        }

        $host = (string) config('mail.mailers.smtp.host');
        $port = (int) config('mail.mailers.smtp.port');

        $socket = @fsockopen($host, $port, $code, $message, 5);

        if ($socket === false) {
            $this->blocking("Cannot reach the SMTP host {$host}:{$port} — {$message}");

            return;
        }

        fclose($socket);
        $this->ok("SMTP host {$host}:{$port} is accepting connections.");
    }

    /**
     * Handing a message to the mailer proves almost nothing — that is the trap
     * this whole command exists for — so the output says so plainly.
     */
    private function probe(string $to): void
    {
        $this->newLine();

        try {
            Mail::raw(
                'Delivery probe from the OpesCare platform. If you are reading this, outbound mail works.',
                fn ($message) => $message->to($to)->subject('OpesCare delivery probe')
            );
            $this->line('  <comment>~</comment> Probe handed to the mailer for ' . $to . '.');
            $this->line('    A local MTA accepts a message before it tries to deliver it, so this is NOT');
            $this->line('    proof of delivery. Check the mail log, and check the inbox.');
        } catch (\Throwable $e) {
            $this->blocking('The mailer refused the probe outright: ' . $e->getMessage());
        }
    }

    // ── plumbing ────────────────────────────────────────────────────────

    private function txt(string $name, string $prefix): ?string
    {
        foreach (@dns_get_record($name, DNS_TXT) ?: [] as $record) {
            $value = trim((string) ($record['txt'] ?? ''));

            if (str_starts_with(strtolower($value), strtolower($prefix))) {
                return $value;
            }
        }

        return null;
    }

    private function ok(string $message): void
    {
        $this->line('  <info>OK</info>   ' . $message);
    }

    private function caution(string $message): void
    {
        $this->warnings[] = $message;
        $this->line('  <comment>WARN</comment> ' . $message);
    }

    private function blocking(string $message): void
    {
        $this->failures[] = $message;
        $this->line('  <fg=red>FAIL</> ' . $message);
    }

    private function report(): int
    {
        $this->newLine();

        if ($this->failures !== []) {
            $this->error(sprintf(
                'Mail is NOT deliverable from this host: %d blocking problem(s), %d warning(s).',
                count($this->failures),
                count($this->warnings)
            ));

            return self::FAILURE;
        }

        if ($this->warnings !== []) {
            $this->comment(sprintf('Mail should deliver, with %d warning(s) that will hurt inbox placement.', count($this->warnings)));

            return self::SUCCESS;
        }

        $this->info('Mail looks deliverable from this host.');

        return self::SUCCESS;
    }
}
