<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuditEventCreated
{
    use Dispatchable, SerializesModels;

    public string $action;
    public string $clientId;
    public ?string $patientId;
    public string $purpose;
    public string $correlationId;
    public array $metadata;
    public int $timestamp;

    public function __construct(
        string $action,
        string $clientId,
        ?string $patientId,
        string $purpose,
        string $correlationId,
        array $metadata = []
    ) {
        $this->action = $action;
        $this->clientId = $clientId;
        $this->patientId = $patientId;
        $this->purpose = $purpose;
        $this->correlationId = $correlationId;
        $this->metadata = $metadata;
        $this->timestamp = time();
    }
}
