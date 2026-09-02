<?php

namespace App\Modules\Notifications\Providers;

interface WhatsAppProvider
{
    public function send(string $to, string $body, array $metadata = []): array;
}
