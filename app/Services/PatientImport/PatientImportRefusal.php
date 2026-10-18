<?php

namespace App\Services\PatientImport;

/** A whole import refused — rendered by the controller as a 422 with error_code. */
class PatientImportRefusal extends \RuntimeException
{
    public function __construct(public readonly string $errorCode, string $message)
    {
        parent::__construct($message);
    }
}
