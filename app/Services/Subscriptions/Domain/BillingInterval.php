<?php

namespace App\Services\Subscriptions\Domain;

use App\Models\SubscriptionPlan;
use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * How long one paid period lasts.
 *
 * ── This enum does not decide who may choose what ──────────────────────────
 * A patient may pay by day, by month or by year; a facility is billed yearly.
 * That rule is NOT written here and must never be: it lives in the catalogue,
 * as which of a plan's price columns are non-null, and is read back through
 * `SubscriptionPlan::intervalsOffered()`. A hardcoded "facilities are annual"
 * in code is a rule that the owner cannot change from the admin panel, and it
 * silently disagrees with the price list the moment somebody edits one.
 *
 * What lives here is the only thing an interval means once it has been paid
 * for: how far forward the period runs.
 *
 * ── The values are the plan's values ───────────────────────────────────────
 * `SubscriptionPlan::INTERVALS` is the catalogue's list and this enum is the
 * billing side of the same vocabulary. They are asserted equal by a test
 * rather than by a comment, so a fourth interval added to one side cannot
 * quietly go missing on the other.
 */
enum BillingInterval: string
{
    case Daily   = 'daily';
    case Monthly = 'monthly';
    case Annual  = 'annual';

    /** i18n key under lang/{en,fr}/subscriptions.php. Never a hardcoded word. */
    public function labelKey(): string
    {
        return 'subscriptions.interval.'.$this->value;
    }

    public function label(): string
    {
        return __($this->labelKey());
    }

    /**
     * The day the period paid for on `$start` runs out.
     *
     * End-exclusive: a daily subscription bought today ends tomorrow, so the
     * subscriber has the whole of today. `current_period_end` is cast to a
     * date, so `isPast()` on it is true from midnight — an end of *today*
     * would expire a day the subscriber has already paid for.
     *
     * `…NoOverflow` on purpose: `addMonth()` on 31 January gives 3 March, so a
     * monthly subscription bought on the 31st would silently be sold two extra
     * days, and one bought on 29 February would land on 1 March a year later.
     */
    public function endFrom(DateTimeInterface $start): Carbon
    {
        // `DateTimeInterface` rather than a Carbon type: the caller's value
        // comes off a model cast, and pinning this to one Carbon class (there
        // are two — Carbon\Carbon and Illuminate\Support\Carbon) is how a
        // perfectly good date gets rejected by a type hint at settlement time,
        // which is the worst possible moment to throw.
        $from = Carbon::instance($start)->startOfDay();

        return match ($this) {
            self::Daily   => $from->copy()->addDay(),
            self::Monthly => $from->copy()->addMonthNoOverflow(),
            self::Annual  => $from->copy()->addYearNoOverflow(),
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $i) => $i->value, self::cases());
    }

    /**
     * The catalogue's vocabulary, as a list — so nothing needs to repeat it.
     *
     * @return list<string>
     */
    public static function catalogueValues(): array
    {
        return SubscriptionPlan::INTERVALS;
    }
}
