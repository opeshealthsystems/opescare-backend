<?php

namespace App\Enums;

/**
 * Availability of one medicine at one pharmacy listing.
 *
 * DB column: medicine_pharmacy_stocks.stock_status (string, backed value).
 *
 * Reported (not dispensed-from) stock: a pharmacy self-reports or a Bridge
 * agent syncs it, so `Unknown` is a first-class state — never silently render
 * unknown stock as available.
 */
enum PharmacyStockStatus: string
{
    case InStock    = 'in_stock';
    case LowStock   = 'low_stock';
    case OutOfStock = 'out_of_stock';
    case Unknown    = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::InStock    => 'In Stock',
            self::LowStock   => 'Low Stock',
            self::OutOfStock => 'Out of Stock',
            self::Unknown    => 'Stock Unknown',
        };
    }

    /** Can a patient be shown this listing as a place to get the medicine today? */
    public function isAvailable(): bool
    {
        return match ($this) {
            self::InStock, self::LowStock => true,
            self::OutOfStock, self::Unknown => false,
        };
    }

    /** Only genuinely-held stock may back a reservation. */
    public function isReservable(): bool
    {
        return $this === self::InStock || $this === self::LowStock;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $s) => $s->value, self::cases());
    }
}
