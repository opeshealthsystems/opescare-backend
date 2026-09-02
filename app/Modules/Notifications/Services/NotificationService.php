<?php

namespace App\Modules\Notifications\Services;

use App\Modules\Notifications\Models\NotificationTemplate;
use App\Modules\Notifications\Services\NotificationTemplateRenderer;
use App\Modules\Notifications\Services\NotificationPreferenceService;
use App\Modules\Communications\Services\CommunicationRouterService;

class NotificationService
{
    private NotificationTemplateRenderer $renderer;
    private NotificationPreferenceService $preferenceService;
    private CommunicationRouterService $routerService;

    public function __construct(
        NotificationTemplateRenderer $renderer,
        NotificationPreferenceService $preferenceService,
        CommunicationRouterService $routerService
    ) {
        $this->renderer = $renderer;
        $this->preferenceService = $preferenceService;
        $this->routerService = $routerService;
    }

    public function sendNotification(
        string $recipientUserId,
        string $eventType,
        array $data,
        string $priority = 'normal',
        string $category = 'health_updates'
    ): array {
        // 1. Resolve user preferred language
        $prefs = $this->preferenceService->getPreferences($recipientUserId, $category);
        $lang = $prefs->language ?? 'en';

        // 2. Fetch the corresponding template
        $template = NotificationTemplate::where('event_type', $eventType)
            ->where('language', $lang)
            ->first();

                // 3. Fallback to basic stub if template is missing from DB
        $subject = $template->subject ?? "OpesCare Notification: {$eventType}";
        $title = $template->title ?? "Notification";
        $body = $template->body ?? "Hello, you have a new notification.";

        // Enforce strict privacy gate: block sensitive medical values from external templates
        if (isset($data['result_value']) || isset($data['diagnosis']) || isset($data['sensitive'])) {
            $body = "A new health update is available in OpesCare. Log in securely to view it.";
            unset($data['result_value']);
            unset($data['diagnosis']);
            unset($data['sensitive']);
        }

        // 4. Render template placeholders
        $renderedSubject = $this->renderer->render($subject, $data);
        $renderedTitle = $this->renderer->render($title, $data);
        $renderedBody = $this->renderer->render($body, $data);

        // 5. Build payload
        $payload = [
            'subject' => $renderedSubject,
            'title' => $renderedTitle,
            'body' => $renderedBody,
            'cta_label' => $template->cta_label ?? 'View Details'
        ];

        // 6. Route the payload
        return $this->routerService->route($eventType, $recipientUserId, $payload, $priority, $category);
    }
}
