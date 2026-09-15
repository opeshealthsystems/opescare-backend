<?php

namespace App\Modules\Tasks\Services;

use App\Modules\Tasks\Models\ActionTask;
use Illuminate\Support\Str;

class TaskService
{
    public function createTask(array $data): ActionTask
    {
        return ActionTask::create([
            'uuid' => Str::uuid(),
            'task_type' => $data['task_type'],
            'title' => $data['title'],
            'description' => $data['description'],
            'assigned_to' => $data['assigned_to'] ?? null,
            'assigned_role' => $data['assigned_role'] ?? null,
            'facility_id' => $data['facility_id'] ?? null,
            'organization_id' => $data['organization_id'] ?? null,
            'patient_id' => $data['patient_id'] ?? null,
            'related_resource_type' => $data['related_resource_type'] ?? null,
            'related_resource_id' => $data['related_resource_id'] ?? null,
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'open',
            'due_at' => $data['due_at'] ?? null,
            'escalation_chain_id' => $data['escalation_chain_id'] ?? null
        ]);
    }

    public function acknowledgeTask(string $uuid, string $userId): ActionTask
    {
        $task = ActionTask::where('uuid', $uuid)->firstOrFail();
        $task->status = 'acknowledged';
        $task->acknowledged_at = now();
        $task->save();
        return $task;
    }

    public function completeTask(string $uuid): ActionTask
    {
        $task = ActionTask::where('uuid', $uuid)->firstOrFail();
        $task->status = 'completed';
        $task->completed_at = now();
        $task->save();
        return $task;
    }

    public function escalateTask(string $uuid): ActionTask
    {
        $task = ActionTask::where('uuid', $uuid)->firstOrFail();
        $task->status = 'escalated';
        $task->save();
        return $task;
    }
}
