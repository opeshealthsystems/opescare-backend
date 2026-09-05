<?php

namespace App\Services\Payments\Domain;

/**
 * The mobile money rails a patient can choose.
 *
 * Cameroon has exactly two consumer mobile money schemes worth wiring, and the
 * platform convention names both: **MTN Mobile Money** and **Orange Money**.
 * There is no card rail here and there is not going to be one — the field
 * reality is that a Cameroonian patient pays from a handset wallet.
 *
 * The prefix tables below are the real Cameroonian numbering plan (CM, country
 * code +237, nine-digit national numbers since the 2016 renumbering). They are
 * used to warn a patient who has typed an Orange number under the MTN tab —
 * not to reject them, because MNP (mobile number portability) means a prefix is
 * a strong hint and never a proof.
 */
enum PaymentMethod: string
{
    case MtnMomo     = 'mtn_momo';
    case OrangeMoney = 'orange_money';

    /** i18n key under lang/{en,fr}/payments.php. */
    public function labelKey(): string
    {
        return 'payments.method.'.$this->value;
    }

    public function label(): string
    {
        return __($this->labelKey());
    }

    /**
     * Cameroonian national prefixes normally issued to this operator.
     *
     * MTN CM: 67, 650-654, 680-689.
     * Orange CM: 69, 655-659, 640-649.
     *
     * @return list<string>
     */
    public function nationalPrefixes(): array
    {
        return match ($this) {
            self::MtnMomo => [
                '67',
                '650', '651', '652', '653', '654',
                '680', '681', '682', '683', '684', '685', '686', '687', '688', '689',
            ],
            self::OrangeMoney => [
                '69',
                '655', '656', '657', '658', '659',
                '640', '641', '642', '643', '644', '645', '646', '647', '648', '649',
            ],
        };
    }

    /**
     * Does this national number look like it belongs to this operator?
     *
     * A `false` is a WARNING the UI shows, never a rejection. Number
     * portability makes any prefix table advisory, and a patient who really has
     * ported their number must still be able to pay.
     */
    public function looksLikeOwnNumber(string $nationalNumber): bool
    {
        foreach ($this->nationalPrefixes() as $prefix) {
            if (str_starts_with($nationalNumber, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $m) => $m->value, self::cases());
    }
}
