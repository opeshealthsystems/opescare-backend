<?php $__env->startSection('title', __('prescriber.title')); ?>
<?php $__env->startSection('breadcrumb_home', __('prescriber.title')); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.prescriber.index')); ?>
<?php $__env->startSection('breadcrumb_section', __('prescriber.nav')); ?>

<?php
    // The statuses a prescription can actually carry, in the order a prescriber
    // scans them. Labels come from prescriber.st_*; colours follow the house
    // badge palette (info = live, success = done, warning = part-way, neutral = closed).
    $rxStatuses = ['active', 'dispensed', 'partially_dispensed', 'voided', 'expired', 'cancelled'];
    $rxColor = fn ($s) => match ($s) {
        'active'              => 'info',
        'dispensed'           => 'success',
        'partially_dispensed' => 'warning',
        default               => 'neutral',
    };
?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('prescriber.title')); ?></h1>
        <p class="page-subtitle"><?php echo e(__('prescriber.subtitle')); ?></p>
    </div>
    <a href="<?php echo e(route('portals.prescriber.create')); ?>" class="btn btn-primary">
        <i data-lucide="plus"></i> <?php echo e(__('prescriber.new')); ?>

    </a>
</div>

<?php if(session('success')): ?><div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div><?php endif; ?>
<?php if(session('error')): ?><div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div><?php echo e(session('error')); ?></div></div><?php endif; ?>

<div class="filter-bar">
    <a href="<?php echo e(route('portals.prescriber.index')); ?>"
       class="btn btn-sm <?php echo e($status ? 'btn-secondary' : 'btn-primary'); ?>"
       <?php if(! $status): ?> aria-current="page" <?php endif; ?>><?php echo e(__('prescriber.filter_all')); ?></a>
    <?php $__currentLoopData = $rxStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rxStatus): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(route('portals.prescriber.index', ['status' => $rxStatus])); ?>"
           class="btn btn-sm <?php echo e($status === $rxStatus ? 'btn-primary' : 'btn-secondary'); ?>"
           <?php if($status === $rxStatus): ?> aria-current="page" <?php endif; ?>><?php echo e(__('prescriber.st_' . $rxStatus)); ?></a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="panel">
    <?php if($prescriptions->isEmpty()): ?>
        <div class="panel-body">
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="file-text"></i></div>
                <h3><?php echo e(__('prescriber.empty')); ?></h3>
                <p><?php echo e(__('prescriber.empty_desc')); ?></p>
            </div>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?php echo e(__('prescriber.col_patient')); ?></th>
                        <th><?php echo e(__('prescriber.col_items')); ?></th>
                        <th><?php echo e(__('prescriber.col_status')); ?></th>
                        <th><?php echo e(__('prescriber.col_issued')); ?></th>
                        <th class="row-actions"><?php echo e(__('prescriber.view')); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php $__currentLoopData = $prescriptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $rxPatient = $rx->patient;
                        $rxName    = trim(($rxPatient->first_name ?? '') . ' ' . ($rxPatient->last_name ?? ''));
                        $rxFirst   = $rx->items->first();
                        $rxExtra   = max($rx->items->count() - 1, 0);
                    ?>
                    <tr>
                        <td data-label="<?php echo e(__('prescriber.col_patient')); ?>">
                            <span class="td-strong"><?php echo e($rxName !== '' ? $rxName : '—'); ?></span>
                            <?php if($rxPatient?->health_id): ?>
                                <div class="td-muted"><?php echo e($rxPatient->health_id); ?></div>
                            <?php endif; ?>
                        </td>
                        <td data-label="<?php echo e(__('prescriber.col_items')); ?>">
                            <?php if($rxFirst): ?>
                                <div><?php echo e($rxFirst->drug_name); ?></div>
                                <?php if($rxExtra > 0): ?>
                                    <div class="td-muted">+<?php echo e($rxExtra); ?> <?php echo e(__('public.pharmacy_portal.lbl_items')); ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="td-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="<?php echo e(__('prescriber.col_status')); ?>">
                            <span class="badge badge-<?php echo e($rxColor($rx->status)); ?>">
                                <?php if(\Illuminate\Support\Facades\Lang::has('prescriber.st_' . $rx->status)): ?>
                                    <?php echo e(__('prescriber.st_' . $rx->status)); ?>

                                <?php else: ?>
                                    <?php echo e(\App\Support\Enums::label($rx->status)); ?>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td data-label="<?php echo e(__('prescriber.col_issued')); ?>" class="td-muted">
                            <?php echo e($rx->prescribed_at?->format('d M Y') ?? '—'); ?>

                        </td>
                        <td class="row-actions" data-label="<?php echo e(__('prescriber.view')); ?>">
                            <a href="<?php echo e(route('portals.prescriber.show', $rx->id)); ?>" class="btn btn-secondary btn-sm">
                                <i data-lucide="eye"></i> <?php echo e(__('prescriber.view')); ?>

                            </a>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <?php if($prescriptions->hasPages()): ?>
            <div class="panel-body"><?php echo e($prescriptions->links()); ?></div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/prescriber/index.blade.php ENDPATH**/ ?>