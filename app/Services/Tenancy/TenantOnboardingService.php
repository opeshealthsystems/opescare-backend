<?php
namespace App\Services\Tenancy;

use App\Models\TenantOnboardingCheckpoint;

class TenantOnboardingService
{
    private array $defaultSteps = [
        ['key' => 'facility_profile_complete', 'label' => 'Complete facility profile',    'order' => 1, 'required' => true],
        ['key' => 'staff_roles_configured',    'label' => 'Configure staff roles',        'order' => 2, 'required' => true],
        ['key' => 'billing_configured',        'label' => 'Set up billing settings',      'order' => 3, 'required' => true],
        ['key' => 'first_provider_added',      'label' => 'Add first provider',           'order' => 4, 'required' => true],
        ['key' => 'test_appointment_booked',   'label' => 'Book a test appointment',      'order' => 5, 'required' => false],
        ['key' => 'insurance_configured',      'label' => 'Configure insurance links',    'order' => 6, 'required' => false],
        ['key' => 'notification_tested',       'label' => 'Test notification channel',   'order' => 7, 'required' => false],
    ];

    public function initializeOnboarding(string $facilityId): void
    {
        foreach ($this->defaultSteps as $step) {
            TenantOnboardingCheckpoint::firstOrCreate(
                ['facility_id' => $facilityId, 'step_key' => $step['key']],
                [
                    'step_label' => $step['label'],
                    'step_order' => $step['order'],
                    'required'   => $step['required'],
                    'completed'  => false,
                ]
            );
        }
    }

    public function completeStep(string $facilityId, string $stepKey): TenantOnboardingCheckpoint
    {
        $checkpoint = TenantOnboardingCheckpoint::where('facility_id', $facilityId)
            ->where('step_key', $stepKey)
            ->firstOrFail();

        $checkpoint->completed    = true;
        $checkpoint->completed_at = now();
        $checkpoint->saveQuietly();

        return $checkpoint;
    }

    public function getProgress(string $facilityId): array
    {
        $checkpoints = TenantOnboardingCheckpoint::where('facility_id', $facilityId)
            ->orderBy('step_order')
            ->get();

        $total     = $checkpoints->count();
        $completed = $checkpoints->where('completed', true)->count();
        $percent   = $total > 0 ? round($completed / $total * 100) : 0;

        return [
            'total_steps'      => $total,
            'completed_steps'  => $completed,
            'percent_complete' => $percent,
            'is_complete'      => $checkpoints->where('required', true)->count() > 0
                && $checkpoints->where('required', true)->where('completed', false)->isEmpty(),
            'steps'            => $checkpoints->values()->toArray(),
        ];
    }
}
