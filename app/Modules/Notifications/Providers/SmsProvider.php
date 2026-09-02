<?php

namespace App\Modules\Notifications\Providers;

interface SmsProvider
{
    public function send(string $to, string $message, array $metadata = []): array;
}
