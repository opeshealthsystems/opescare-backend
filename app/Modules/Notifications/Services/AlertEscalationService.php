<?php

namespace App\Modules\Notifications\Services;

use App\Modules\Notifications\Models\NotificationEvent;
use App\Modules\Notifications\Models\EscalationChain;
use App\Modules\Tasks\Services\TaskService;
use Illuminate\Support\Facades\Log;

class AlertEscalationService
{
    private TaskService $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    public function escalateIfUnacknowledged(NotificationEvent $event): void
    {
        if (!$event->requires_acknowledgement || $event->acknowledgement_status === 'acknowledged') {
            return;
        }

        // Fetch escalation chain
        $chain = EscalationChain::find($event->escalation_chain_id);
        if (!$chain || !$chain->active) {
            return;
        }

        $steps = json_decode($chain->steps_json, true) ?? [];
        if (empty($steps)) {
            return;
        }

        // Trigger escalation
        $event->status = 'escalated';
        $event->acknowledgement_status = 'escalated';
        $event->save();

        // Audit & Log the escalation
        Log::warning("Alert Escalation Triggered for Event {$event->uuid}. Escalating to next step.");

        // Automatically assign a backup task to the next defined escalation tier (e.g. step 2 role)
        $nextStep = $steps[1] ?? $steps[0];
        
        $this->taskService->createTask([
            'task_type' => 'escalated_clinical_alert',
            'title' => "Escalated: Critical action required",
            'description' => "Escalated critical clinical event. Original Event UUID: {$event->uuid}. Please review and act immediately.",
            'assigned_role' => $nextStep['role'] ?? 'backup_clinical_officer',
            'priority' => 'critical',
            'escalation_chain_id' => $chain->id
        ]);
    }
}
