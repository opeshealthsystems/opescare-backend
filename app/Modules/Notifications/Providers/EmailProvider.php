<?php

namespace App\Modules\Notifications\Providers;

interface EmailProvider
{
    public function send(string $to, string $subject, string $body, array $metadata = []): array;
}
