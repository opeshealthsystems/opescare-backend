<?php

namespace App\Modules\Notifications\Providers;

interface VoiceProvider
{
    public function makeCall(string $to, string $speechText, array $metadata = []): array;
}
