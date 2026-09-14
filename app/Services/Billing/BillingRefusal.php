<?php

namespace App\Services\Billing;

use RuntimeException;

/**
 * The desk said no, and can say why in the cashier's language.
 *
 * `$key` is a key under lang/{en,fr}/billing_desk.php (`refuse.*`); the
 * controller turns it into a flash message. `$params` are the `:placeholders`
 * of that key, so an amount the desk refused is shown to the cashier as the
 * exact figure it compared against — never rounded, never approximated.
 */
final class BillingRefusal extends RuntimeException
{
    /** @param array<string, string|int> $params */
    public function __construct(
        public readonly string $key,
        public readonly array $params = [],
    ) {
        parent::__construct($key);
    }

    public function messageFor(): string
    {
        return __('billing_desk.refuse.'.$this->key, $this->params);
    }
}
