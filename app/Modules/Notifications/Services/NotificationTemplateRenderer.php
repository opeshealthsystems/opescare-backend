<?php

namespace App\Modules\Notifications\Services;

class NotificationTemplateRenderer
{
    public function render(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $template = str_replace("{{ $key }}", (string)$value, $template);
                $template = str_replace("{{$key}}", (string)$value, $template);
            }
        }
        return $template;
    }
}
