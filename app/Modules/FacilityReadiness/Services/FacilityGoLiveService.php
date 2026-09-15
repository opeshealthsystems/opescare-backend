<?php

namespace App\Modules\FacilityReadiness\Services;

use App\Models\AuditEvent;
use App\Models\Facility;
use App\Models\FacilityGoLiveReadiness;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Imported from the platform (docs/PLATFORM_IMPORTS.md, group D).
 *
 * Finder adaptations:
 *  - the manual checklist keeps only items that mean something for a Finder
 *    tenant. Dropped (removed modules / not applicable): departments_configured,
 *    services_configured (replaced by the automatic catalogue check),
 *    document_templates_active, notification_channels_configured,
 *    audit_logs_active (always on), data_import_completed_if_required.
 *  - approval additionally requires every automatic readiness check
 *    (FacilityReadinessScoringService) to pass.
 *  - labels are i18n keys (onboarding.admin_portal.item_*), not English.
 *  - a stored checklist is normalised to the current keys, so rows written with
 *    the platform's 13 keys neither block approval nor show dropped items.
 */
class FacilityGoLiveService
{
    public const CHECKLIST = [
        'facility_verified' => 'Facility verified',
        'admin_account_created' => 'Admin account created',
        'staff_roles_assigned' => 'Staff roles assigned',
        'privacy_training_completed' => 'Privacy training completed',
        'demo_training_completed' => 'Demo/training completed',
        'support_contact_defined' => 'Support contact defined',
        'go_live_approval_recorded' => 'Go-live approval recorded',
    ];

    public function __construct(private readonly FacilityReadinessScoringService $scoring) {}

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
        $checklist = $this->checklistOf($readiness);

        if (! array_key_exists($item, $checklist) || $item === 'go_live_approval_recorded') {
            throw new InvalidArgumentException('FACILITY_GO_LIVE_CHECKLIST_ITEM_UNKNOWN');
        }

        $before = $readiness->toArray();
        $checklist[$item] = $complete;

        $ready = $this->approvalReady($checklist);

        $readiness->forceFill([
            'checklist_json' => $checklist,
            'can_go_live' => $readiness->status === 'approved',
            'status' => $readiness->status === 'approved'
                ? 'approved'
                : ($ready ? 'ready_for_approval' : 'pending'),
        ])->save();

        $this->audit($readiness, 'update_checklist', $actorId, $before, $readiness->fresh()->toArray());

        return $readiness->fresh();
    }

    public function approveGoLive(FacilityGoLiveReadiness $readiness, string $actorId, string $approvalNote): FacilityGoLiveReadiness
    {
        return DB::transaction(function () use ($readiness, $actorId, $approvalNote) {
            $readiness = $readiness->fresh();
            $checklist = $this->checklistOf($readiness);

            if (! $this->approvalReady($checklist)) {
                throw new RuntimeException('FACILITY_GO_LIVE_CHECKLIST_INCOMPLETE');
            }

            if ($this->scoring->evaluate($readiness->facility_id)['missing'] !== []) {
                throw new RuntimeException('FACILITY_GO_LIVE_READINESS_CHECKS_FAILING');
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

            Facility::withoutGlobalScopes()->whereKey($readiness->facility_id)->update(['status' => 'active']);

            $this->audit($readiness, 'approve', $actorId, $before, $readiness->fresh()->toArray());

            return $readiness->fresh();
        });
    }

    public function missingItems(FacilityGoLiveReadiness $readiness): array
    {
        return array_keys(array_filter(
            $this->checklistOf($readiness),
            fn (bool $complete) => ! $complete
        ));
    }

    /** Missing items as translated sentences. */
    public function risks(FacilityGoLiveReadiness $readiness): array
    {
        return array_map(
            fn (string $item) => __('onboarding.admin_portal.risk_missing', ['item' => __('onboarding.admin_portal.item_'.$item)]),
            $this->missingItems($readiness)
        );
    }

    /** @return array<string,string> key => translated label */
    public function checklistLabels(): array
    {
        $labels = [];
        foreach (array_keys(self::CHECKLIST) as $key) {
            $labels[$key] = __('onboarding.admin_portal.item_'.$key);
        }

        return $labels;
    }

    /** The stored checklist restricted to (and completed with) the current keys. */
    public function checklistOf(FacilityGoLiveReadiness $readiness): array
    {
        $stored = is_array($readiness->checklist_json) ? $readiness->checklist_json : [];
        $checklist = $this->defaultChecklist();
        foreach (array_keys($checklist) as $key) {
            $checklist[$key] = ($stored[$key] ?? false) === true;
        }

        return $checklist;
    }

    private function defaultChecklist(): array
    {
        return array_fill_keys(array_keys(self::CHECKLIST), false);
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
            'actor_role' => 'platform_admin',
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
