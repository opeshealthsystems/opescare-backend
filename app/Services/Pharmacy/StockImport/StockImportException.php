<?php

namespace App\Services\Pharmacy\StockImport;

use RuntimeException;

/** A file the importer refuses, carrying the `pharmacy_stock_import.*` key that explains why. */
class StockImportException extends RuntimeException
{
    /** @param array<string, mixed> $params */
    public function __construct(public readonly string $key, public readonly array $params = [])
    {
        parent::__construct($key);
    }

    public function userMessage(): string
    {
        return __('pharmacy_stock_import.' . $this->key, $this->params);
    }
}
