<?php

namespace App\Contracts;

interface PaymentProvider
{
    /**
     * Initiate a payment request.
     * Returns provider-specific transaction reference.
     */
    public function requestPayment(
        string $phoneNumber,
        float  $amount,
        string $currency,
        string $externalRef,
        string $description
    ): array;

    /**
     * Check the status of a transaction by provider reference.
     */
    public function checkStatus(string $transactionRef): array;

    /**
     * Get the provider name identifier.
     */
    public function getName(): string;
}
