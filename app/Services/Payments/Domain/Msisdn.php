<?php

namespace App\Services\Payments\Domain;

use Illuminate\Support\Facades\Config;
use InvalidArgumentException;

/**
 * The payer's phone number — the one value in this whole subsystem that must
 * never reach a log, a database column, or an error message intact.
 *
 * A payment log is read by more people, in more places, for longer, than
 * almost any other record: support tickets, Sentry issues, a `tail -f` on a
 * shared screen, a CSV a finance intern exports. A full MSISDN sitting in one
 * is a direct line to a patient — worse here than elsewhere, because the same
 * number that receives the payment prompt also receives the OTP.
 *
 * So this object holds the number for exactly as long as it takes to hand it
 * to a provider, and everything that outlives the request is one of:
 *
 *   masked()  '+2376*****21'  — enough for a patient to recognise their own
 *                               number on a receipt, useless to anyone else.
 *   hash()    64 hex chars    — HMAC-SHA256 keyed on APP_KEY. Lets support
 *                               answer "did this number pay?" without the
 *                               column ever being reversible. Keyed, not a
 *                               bare SHA-256: the space of Cameroonian numbers
 *                               is ~10^8, which an unkeyed digest enumerates
 *                               in seconds.
 *
 * `__toString`, `jsonSerialize`, `__debugInfo` and `__set_state` are all
 * deliberately overridden or absent so that `var_dump`, `json_encode`,
 * `Log::info([$msisdn])` and a stack-trace argument dump all print the MASKED
 * form. Leaking the number has to be a thing someone typed on purpose.
 */
final class Msisdn
{
    /** Cameroon: +237, nine-digit national numbers since the 2016 renumbering. */
    private const DEFAULT_COUNTRY_CODE = '237';
    private const CM_NATIONAL_LENGTH = 9;

    private function __construct(
        /** E.164 WITHOUT the leading '+': e.g. '237670000001'. Never persisted. */
        private readonly string $e164,
        private readonly string $countryCode,
        private readonly string $nationalNumber,
    ) {
    }

    /**
     * Parse anything a patient might type: `670 00 00 01`, `+237 670000001`,
     * `00237670000001`, `237-670-000-001`.
     *
     * @throws InvalidArgumentException when it is not a possible CM mobile number.
     */
    public static function parse(string $raw, string $defaultCountryCode = self::DEFAULT_COUNTRY_CODE): self
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '') {
            throw new InvalidArgumentException('A mobile money number is required.');
        }

        // '00237…' international prefix → drop it.
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        // Already carries the country code.
        if (str_starts_with($digits, $defaultCountryCode)
            && strlen($digits) === strlen($defaultCountryCode) + self::CM_NATIONAL_LENGTH) {
            $national = substr($digits, strlen($defaultCountryCode));
        } elseif (strlen($digits) === self::CM_NATIONAL_LENGTH) {
            $national = $digits;
        } else {
            throw new InvalidArgumentException('That is not a valid Cameroonian mobile number.');
        }

        // CM mobile numbers all start with 6.
        if (! str_starts_with($national, '6')) {
            throw new InvalidArgumentException('That is not a valid Cameroonian mobile number.');
        }

        return new self($defaultCountryCode.$national, $defaultCountryCode, $national);
    }

    /** True when the raw input is parseable — for validation without a try/catch. */
    public static function isParseable(string $raw): bool
    {
        try {
            self::parse($raw);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * E.164 without '+', the form MTN MoMo's `payer.partyId` and Orange's
     * `subscriber` field both want. Call this ONLY when handing the number to
     * a provider — never to build a log line, a response body, or a DB write.
     */
    public function forProvider(): string
    {
        return $this->e164;
    }

    public function nationalNumber(): string
    {
        return $this->nationalNumber;
    }

    /**
     * '+2376*****01' — first two national digits and last two kept. Everything
     * a patient needs to recognise it, nothing an attacker can dial.
     */
    public function masked(): string
    {
        $n = $this->nationalNumber;
        $head = substr($n, 0, 2);
        $tail = substr($n, -2);
        $stars = str_repeat('*', max(1, strlen($n) - 4));

        return '+'.$this->countryCode.$head.$stars.$tail;
    }

    /**
     * HMAC-SHA256(APP_KEY, E.164). One-way and key-bound: two deployments
     * produce different hashes for the same number, so a leaked column from one
     * host cannot be matched against another.
     */
    public function hash(): string
    {
        $key = (string) Config::get('app.key', '');

        return hash_hmac('sha256', $this->e164, $key !== '' ? $key : 'opescare-msisdn-fallback');
    }

    /** Everything that stringifies this object gets the mask, not the number. */
    public function __toString(): string
    {
        return $this->masked();
    }

    /** `var_dump`, `dd`, and stack-trace argument dumps get the mask too. */
    public function __debugInfo(): array
    {
        return ['masked' => $this->masked()];
    }
}
