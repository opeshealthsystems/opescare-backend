<?php

namespace App\Modules\Notifications\Providers;

interface PushProvider
{
    public function send(string $recipientToken, string $title, string $body, array $metadata = []): array;
}
