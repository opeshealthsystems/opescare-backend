<?php

namespace App\Services\Pharmacy;

use RuntimeException;

/**
 * A refusal the pharmacy desk can explain to the app.
 *
 * Carries the HTTP status and the machine-readable `error_code` the mobile
 * client switches on, plus a message already translated in the request's
 * locale. Thrown by PharmacyDeskService, rendered by the desk controllers —
 * so every endpoint refuses in exactly the same shape:
 *
 *     { "message": "...", "error_code": "invalid_transition" }
 *
 * "Not found" and "not yours" are deliberately the same answer (404). A
 * prescription routed to another pharmacy, or a reservation placed at another
 * pharmacy, is simply not at this counter; saying more would confirm that a
 * record exists to someone with no right to it.
 */
class PharmacyDeskException extends RuntimeException
{
    public const NOT_FOUND_PRESCRIPTION  = 'prescription_not_found';
    public const NOT_FOUND_RESERVATION   = 'reservation_not_found';
    public const NOT_FOUND_STOCK         = 'stock_not_found';
    public const INVALID_TRANSITION      = 'invalid_transition';
    public const NOT_DISPENSABLE         = 'not_dispensable';
    public const ITEM_NOT_ON_PRESCRIPTION = 'item_not_on_prescription';
    public const STOCK_NEGATIVE          = 'stock_negative';
    public const LISTING_UNLINKED        = 'listing_unlinked';
    public const COLLECT_IS_FOR_PICKUP   = 'collect_is_for_pickup';
    public const DISPATCH_IS_FOR_DELIVERY = 'dispatch_is_for_delivery';

    public function __construct(
        public readonly string $errorCode,
        public readonly int $status,
        string $message,
    ) {
        parent::__construct($message);
    }

    /** @param  'prescription'|'reservation'|'stock'  $what */
    public static function notFound(string $what): self
    {
        $code = match ($what) {
            'reservation' => self::NOT_FOUND_RESERVATION,
            'stock'       => self::NOT_FOUND_STOCK,
            default       => self::NOT_FOUND_PRESCRIPTION,
        };

        return new self($code, 404, __('pharmacy_api.errors.' . $code));
    }

    public static function invalidTransition(): self
    {
        return new self(self::INVALID_TRANSITION, 409, __('pharmacy_api.errors.invalid_transition'));
    }

    public static function notDispensable(): self
    {
        return new self(self::NOT_DISPENSABLE, 409, __('pharmacy_api.errors.not_dispensable'));
    }

    public static function itemNotOnPrescription(): self
    {
        return new self(self::ITEM_NOT_ON_PRESCRIPTION, 422, __('pharmacy_api.errors.item_not_on_prescription'));
    }

    public static function stockNegative(int $quantity, int $delta): self
    {
        return new self(self::STOCK_NEGATIVE, 422, __('pharmacy_api.errors.stock_negative', [
            'quantity' => $quantity,
            'delta'    => $delta,
        ]));
    }

    public static function listingUnlinked(): self
    {
        return new self(self::LISTING_UNLINKED, 409, __('pharmacy_api.errors.listing_unlinked'));
    }

    public static function collectIsForPickup(): self
    {
        return new self(self::COLLECT_IS_FOR_PICKUP, 409, __('pharmacy_api.errors.collect_is_for_pickup'));
    }

    public static function dispatchIsForDelivery(): self
    {
        return new self(self::DISPATCH_IS_FOR_DELIVERY, 409, __('pharmacy_api.errors.dispatch_is_for_delivery'));
    }
}
