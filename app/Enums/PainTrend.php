<?php

namespace App\Enums;

/** How the pain has moved since it began, as reported. Never derived. */
enum PainTrend: string
{
    case Better = 'better';
    case Same   = 'same';
    case Worse  = 'worse';

    public function label(): string
    {
        return __('pain.trend.' . $this->value);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }
}
