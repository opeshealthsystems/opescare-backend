<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('title', __('public.pat_careplan_title', [], $l) . ' — OpesCare'); ?>

<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], $l) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.pat_careplan_breadcrumb', [], $l)); ?>

<?php $__env->startSection('patient_banner'); ?>
    <?php echo $__env->make('partials.guardian-context-banner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('public.pat_careplan_title', [], $l)); ?></h1>
        <p class="page-subtitle"><?php echo e(__('public.pat_careplan_subtitle', [], $l)); ?></p>
    </div>
</div>

<?php if(!$patient): ?>
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="alert-circle"></i></div>
        <h3><?php echo e(__('public.portal.no_profile_found_title', [], $l) ?: 'No Patient Profile Found'); ?></h3>
        <p><?php echo e(__('public.portal.no_profile_found_desc', [], $l) ?: 'Your patient profile could not be loaded. Please contact support.'); ?></p>
    </div>
</div>
<?php elseif($carePlans->isEmpty()): ?>
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="clipboard-list"></i></div>
        <h3><?php echo e(__('public.pat_careplan_empty_title', [], $l)); ?></h3>
        <p><?php echo e(__('public.pat_careplan_empty_desc', [], $l)); ?></p>
    </div>
</div>
<?php else: ?>
<?php $__currentLoopData = $carePlans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<?php
    $totalGoals    = $plan->goals->count();
    $achievedGoals = $plan->goals->where('status', 'achieved')->count();
    $progressPct   = $totalGoals > 0 ? (int) round(($achievedGoals / $totalGoals) * 100) : 0;
?>
<div class="panel mb-6">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="clipboard-list"></i> <?php echo e($plan->title ?: '—'); ?></h2>
        <span class="badge badge-primary"><?php echo e($progressPct); ?>%</span>
    </div>
    <div class="panel-body">
        <?php if($plan->description): ?>
        <p class="page-subtitle" style="margin:0 0 .75rem;"><?php echo e($plan->description); ?></p>
        <?php endif; ?>
        <p class="td-muted" style="margin:0 0 1rem;">
            <i data-lucide="calendar" style="width:.9rem;height:.9rem;display:inline;vertical-align:middle;"></i>
            <?php echo e(__('public.pat_careplan_period', [], $l)); ?>:
            <?php echo e($plan->start_date?->isoFormat('LL') ?? '—'); ?> —
            <?php echo e($plan->end_date?->isoFormat('LL') ?? __('public.pat_careplan_ongoing', [], $l)); ?>

        </p>

        <h3 style="font-size:.95rem;font-weight:700;margin:1rem 0 .5rem;color:#0F4C81;">
            <i data-lucide="target" style="width:1rem;height:1rem;display:inline;vertical-align:middle;"></i>
            <?php echo e(__('public.pat_careplan_goals', [], $l)); ?> (<?php echo e($totalGoals); ?>)
        </h3>
        <?php if($plan->goals->isEmpty()): ?>
            <p class="td-muted" style="margin:0 0 1rem;"><?php echo e(__('public.pat_careplan_no_goals', [], $l)); ?></p>
        <?php else: ?>
            <ul style="list-style:none;margin:0 0 1rem;padding:0;display:grid;gap:.4rem;">
                <?php $__currentLoopData = $plan->goals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $goal): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $goalBadge = match($goal->status) {
                        'achieved'    => 'badge-success',
                        'in_progress' => 'badge-info',
                        'cancelled'   => 'badge-danger',
                        default       => 'badge',
                    };
                ?>
                <li style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem;">
                    <span class="badge <?php echo e($goalBadge); ?>"><?php echo e(\App\Support\Enums::label($goal->status ?? 'pending')); ?></span>
                    <span class="td-strong"><?php echo e($goal->goal_text ?: '—'); ?></span>
                    <?php if($goal->target_date): ?>
                        <span class="td-muted">· <?php echo e(__('public.pat_careplan_target', [], $l)); ?>: <?php echo e($goal->target_date->isoFormat('LL')); ?></span>
                    <?php endif; ?>
                </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        <?php endif; ?>

        <h3 style="font-size:.95rem;font-weight:700;margin:1rem 0 .5rem;color:#0F4C81;">
            <i data-lucide="list-checks" style="width:1rem;height:1rem;display:inline;vertical-align:middle;"></i>
            <?php echo e(__('public.pat_careplan_interventions', [], $l)); ?> (<?php echo e($plan->interventions->count()); ?>)
        </h3>
        <?php if($plan->interventions->isEmpty()): ?>
            <p class="td-muted" style="margin:0;"><?php echo e(__('public.pat_careplan_no_interventions', [], $l)); ?></p>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><?php echo e(__('public.pat_careplan_interventions', [], $l)); ?></th>
                            <th><?php echo e(__('public.pat_careplan_frequency', [], $l)); ?></th>
                            <th><?php echo e(__('public.pat_careplan_responsible', [], $l)); ?></th>
                            <th><?php echo e(__('public.pat_referral_col_status', [], $l)); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $plan->interventions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $iv): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td data-label="<?php echo e(__('public.pat_careplan_interventions', [], $l)); ?>">
                                <span class="td-strong"><?php echo e($iv->description ?: ($iv->intervention_type ?? '—')); ?></span>
                            </td>
                            <td data-label="<?php echo e(__('public.pat_careplan_frequency', [], $l)); ?>"><span class="td-muted"><?php echo e($iv->frequency ?? '—'); ?></span></td>
                            <td data-label="<?php echo e(__('public.pat_careplan_responsible', [], $l)); ?>"><span class="td-muted"><?php echo e($iv->responsible_party ?? '—'); ?></span></td>
                            <td data-label="<?php echo e(__('public.pat_referral_col_status', [], $l)); ?>">
                                <span class="badge"><?php echo e(\App\Support\Enums::label($iv->status ?? 'active')); ?></span>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\care-plans.blade.php ENDPATH**/ ?>