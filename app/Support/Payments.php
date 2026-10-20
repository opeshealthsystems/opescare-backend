<?php

namespace App\Support;

use App\Services\Payments\MobileMoneyProviderRegistry;

/**
 * The one answer to "can this host take a payment online?" (owner decision
 * 2026-10-20). True when a live MTN MoMo / Orange Money provider is configured,
 * or when the simulator is permitted for this environment (local, testing,
 * demo). On production with no live credentials it is false, and every
 * pay-online control is hidden behind a "pay at the facility" note.
 *
 * It never gates care: cash and manual recording at the desk keep working,
 * and nothing clinical reads this (CareIsNeverGatedOnPaymentTest).
 */
final class Payments
{
    public static function onlineAvailable(): bool
    {
        return app(MobileMoneyProviderRegistry::class)->isAvailable();
    }
}
