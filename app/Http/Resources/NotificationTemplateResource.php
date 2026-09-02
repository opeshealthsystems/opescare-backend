<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class NotificationTemplateResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'event_type' => $this->event_type,
            'channel' => $this->channel,
            'language' => $this->language,
            'subject' => $this->subject,
            'title' => $this->title,
            'body' => $this->body,
            'cta_label' => $this->cta_label,
            'template_html' => $this->template_html,
            'template_text' => $this->template_text,
            'priority' => $this->priority,
            'communication_class' => $this->communication_class,
            'approval_status' => $this->approval_status,
            'version' => $this->version,
            'provider_template_id' => $this->provider_template_id,
            'provider_approval_status' => $this->provider_approval_status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
