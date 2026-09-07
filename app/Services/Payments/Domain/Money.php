<?php

namespace App\Services\Payments\Domain;

use InvalidArgumentException;

/**
 * An amount of money, held as an integer of minor units. Immutable.
 *
 * There is no float in this class, no float in the schema, and no float on any
 * path between the two. `float` cannot represent 0.1, so a currency computed
 * through one accumulates error that eventually shows up as an off-by-one franc
 * in a total — and an invoice that does not add up is an invoice a patient is
 * right to refuse.
 *
 * ── The XAF representation, stated once, explicitly ────────────────────────
 * XAF (CFA franc BEAC, the currency of Cameroon) has an ISO 4217 exponent of
 * **0**. It has no subdivision in practice: there is no "centime" in
 * circulation. So for this currency **the minor unit is the franc itself**:
 *
 *      Money::ofMinor(5000, 'XAF')  ->  "5 000 FCFA"
 *
 * NOT 50.00 FCFA. If a second currency is ever added, `EXPONENTS` below is the
 * one place that has to learn its exponent, and the DB CHECK constraint on
 * `currency` has to be widened deliberately — it cannot happen by accident.
 */
final class Money
{
    /** ISO 4217 minor-unit exponents for every currency this app accepts. */
    private const EXPONENTS = [
        'XAF' => 0,
    ];

    /** A single invoice above this is a data-entry accident, not a bill. */
    public const MAX_MINOR = 50_000_000;

    private function __construct(
        public readonly int $minor,
        public readonly string $currency,
    ) {
    }

    /**
     * Build from minor units — the ONLY constructor, on purpose. There is
     * deliberately no `fromDecimal(float)`.
     */
    public static function ofMinor(int $minor, string $currency = 'XAF'): self
    {
        $currency = strtoupper($currency);

        if (! array_key_exists($currency, self::EXPONENTS)) {
            throw new InvalidArgumentException("Unsupported currency [{$currency}].");
        }

        if ($minor <= 0) {
            throw new InvalidArgumentException('An amount must be a positive number of minor units.');
        }

        if ($minor > self::MAX_MINOR) {
            throw new InvalidArgumentException('Amount exceeds the maximum a single invoice may carry.');
        }

        return new self($minor, $currency);
    }

    public function exponent(): int
    {
        return self::EXPONENTS[$this->currency];
    }

    /**
     * The symbol Cameroonians actually read. XAF is the ISO code that belongs
     * on an API payload; **FCFA** is what belongs on a screen, and the platform
     * convention says so.
     */
    public function symbol(): string
    {
        return self::symbolFor($this->currency);
    }

    /**
     * The symbol alone. A free plan has no amount to charge and therefore no
     * Money, but it still has to be shown in a currency.
     */
    public static function symbolFor(string $currency): string
    {
        return strtoupper($currency) === 'XAF' ? 'FCFA' : strtoupper($currency);
    }

    /**
     * Display form, for a receipt or a confirm button: "5 000 FCFA".
     *
     * The group separator is a narrow no-break space (U+202F), which is correct
     * French typography and is also what Cameroonian English usage follows for
     * franc amounts. Passing this through `number_format` with a float would
     * reintroduce exactly the imprecision this class exists to avoid, so the
     * grouping is done on the integer's decimal string.
     */
    public function format(): string
    {
        $exponent = $this->exponent();

        if ($exponent === 0) {
            return $this->group((string) $this->minor).' '.$this->symbol();
        }

        $divisor = 10 ** $exponent;
        $whole = intdiv($this->minor, $divisor);
        $frac  = str_pad((string) ($this->minor % $divisor), $exponent, '0', STR_PAD_LEFT);

        return $this->group((string) $whole).'.'.$frac.' '.$this->symbol();
    }

    /**
     * Thousands grouping on a decimal STRING — never on a float.
     *
     * The reversal happens ONLY on the bare ASCII digits, before the separator
     * is introduced. Reversing the joined result instead corrupts the string:
     * U+202F is three bytes in UTF-8, `strrev` is byte-wise, and the output is
     * invalid UTF-8 that `json_encode` then rejects with "Malformed UTF-8
     * characters" — a 500 on the invoice endpoint, from the formatter.
     */
    private function group(string $digits): string
    {
        // strrev / str_split are byte-wise, which is exactly right here and
        // only here: $digits is guaranteed ASCII (it came from an int).
        $chunks = array_map('strrev', str_split(strrev($digits), 3));

        return implode("\u{202F}", array_reverse($chunks));
    }

    /**
     * The wire form. `amount_minor` is an integer, and `amount_display` is the
     * string the app shows, so the client never has to know an exponent.
     *
     * @return array{amount_minor: int, currency: string, currency_symbol: string, amount_display: string}
     */
    public function toArray(): array
    {
        return [
            'amount_minor'    => $this->minor,
            'currency'        => $this->currency,
            'currency_symbol' => $this->symbol(),
            'amount_display'  => $this->format(),
        ];
    }

    public function equals(self $other): bool
    {
        return $this->minor === $other->minor && $this->currency === $other->currency;
    }
}
