<?php

namespace App\Modules\FacilityReadiness\Services;

use App\Models\AuditEvent;
use App\Models\Facility;
use App\Models\FacilityGoLiveReadiness;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class FacilityGoLiveService
{
    public const CHECKLIST = [
        'facility_verified' => 'Facility verified',
        'admin_account_created' => 'Admin account created',
        'staff_roles_assigned' => 'Staff roles assigned',
        'privacy_training_completed' => 'Privacy training completed',
        'departments_configured' => 'Departments configured',
        'services_configured' => 'Services configured',
        'document_templates_active' => 'Document templates active',
        'notification_channels_configured' => 'Notification channels configured',
        'audit_logs_active' => 'Audit logs active',
        'demo_training_completed' => 'Demo/training completed',
        'support_contact_defined' => 'Support contact defined',
        'data_import_completed_if_required' => 'Data import completed if required',
        'go_live_approval_recorded' => 'Go-live approval recorded',
    ];

    public function getOrCreateReadiness(string $facilityId, ?string $actorId = null): FacilityGoLiveReadiness
    {
        $readiness = FacilityGoLiveReadiness::firstOrCreate(
            ['facility_id' => $facilityId],
            [
                'checklist_json' => $this->defaultChecklist(),
                'status' => 'pending',
                'can_go_live' => false,
            ]
        );

        if ($readiness->wasRecentlyCreated) {
            $this->audit($readiness, 'create', $actorId, null, $readiness->toArray());
        }

        return $readiness;
    }

    public function markItem(FacilityGoLiveReadiness $readiness, string $item, bool $complete, ?string $actorId = null): FacilityGoLiveReadiness
    {
        $checklist = $readiness->checklist_json ?? $this->defaultChecklist();

        if (! array_key_exists($item, $checklist)) {
            throw new InvalidArgumentException('FACILITY_GO_LIVE_CHECKLIST_ITEM_UNKNOWN');
        }

        $before = $readiness->toArray();
        $checklist[$item] = $complete;

        $readiness->forceFill([
            'checklist_json' => $checklist,
            'can_go_live' => $this->allComplete($checklist),
            'status' => $readiness->status === 'approved'
                ? 'approved'
                : ($this->allComplete($checklist) ? 'ready_for_approval' : 'pending'),
        ])->save();

        $this->audit($readiness, 'update_checklist', $actorId, $before, $readiness->fresh()->toArray());

        return $readiness->fresh();
    }

    public function approveGoLive(FacilityGoLiveReadiness $readiness, string $actorId, string $approvalNote): FacilityGoLiveReadiness
    {
        return DB::transaction(function () use ($readiness, $actorId, $approvalNote) {
            $readiness = $readiness->fresh();
            $checklist = $readiness->checklist_json ?? $this->defaultChecklist();

            if (! $this->approvalReady($checklist)) {
                throw new RuntimeException('FACILITY_GO_LIVE_CHECKLIST_INCOMPLETE');
            }

            $before = $readiness->toArray();
            $checklist['go_live_approval_recorded'] = true;

            $readiness->forceFill([
                'checklist_json' => $checklist,
                'status' => 'approved',
                'can_go_live' => true,
                'approved_by' => $actorId,
                'approved_at' => Carbon::now(),
                'approval_note' => $approvalNote,
            ])->save();

            Facility::whereKey($readiness->facility_id)->update(['status' => 'active']);

            $this->audit($readiness, 'approve', $actorId, $before, $readiness->fresh()->toArray());

            return $readiness->fresh();
        });
    }

    public function missingItems(FacilityGoLiveReadiness $readiness): array
    {
        return array_keys(array_filter(
            $readiness->checklist_json ?? $this->defaultChecklist(),
            fn (bool $complete) => ! $complete
        ));
    }

    public function risks(FacilityGoLiveReadiness $readiness): array
    {
        $missingItems = $this->missingItems($readiness);

        if ($missingItems === []) {
            return [];
        }

        return array_map(
            fn (string $item) => 'Missing readiness item: '.$item,
            $missingItems
        );
    }

    public function checklistLabels(): array
    {
        return self::CHECKLIST;
    }

    private function defaultChecklist(): array
    {
        return array_fill_keys(array_keys(self::CHECKLIST), false);
    }

    private function allComplete(array $checklist): bool
    {
        return ! in_array(false, $checklist, true);
    }

    private function approvalReady(array $checklist): bool
    {
        foreach ($checklist as $item => $complete) {
            if ($item === 'go_live_approval_recorded') {
                continue;
            }

            if (! $complete) {
                return false;
            }
        }

        return true;
    }

    private function audit(
        FacilityGoLiveReadiness $readiness,
        string $action,
        ?string $actorId,
        ?array $before,
        ?array $after
    ): void {
        AuditEvent::create([
            'actor_id' => $actorId,
            'actor_role' => 'master_admin',
            'facility_id' => $readiness->facility_id,
            'action_type' => $action,
            'resource_type' => 'facility_go_live_readiness',
            'resource_id' => $readiness->id,
            'source_system' => 'opescare',
            'before_state' => $before,
            'after_state' => $after,
            'created_at' => Carbon::now(),
        ]);
    }
}
