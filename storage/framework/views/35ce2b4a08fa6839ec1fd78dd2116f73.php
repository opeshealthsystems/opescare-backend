<?php $__env->startSection('title', __('messaging.inbox_title') . ' — OpesCare'); ?>

<?php $__env->startSection('breadcrumb_home', __('messaging.breadcrumb_home')); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('messaging.thread_breadcrumb')); ?>

<?php $__env->startSection('head'); ?>
    <?php echo $__env->make('portals.patient._finder_head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="pf">

    <div class="pf-head">
        <div>
            <h1><?php echo e(__('messaging.inbox_title')); ?></h1>
            <p class="pf-sub"><?php echo e(__('messaging.inbox_subtitle')); ?></p>
        </div>
        <a href="<?php echo e(route('portals.patient.messages.compose')); ?>" class="f-btn f-btn--primary">
            <?php echo e(__('messaging.compose_btn')); ?>

            <?php echo $__env->make('partials.finder.icon', ['name' => 'send', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </a>
    </div>

    <?php if($threads->isEmpty()): ?>
    <div class="f-card" style="text-align:center;padding:40px 20px;">
        <div class="pf-qa__icon" style="margin:0 auto 12px;width:48px;height:48px;">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'message-square', 'size' => 24], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
        <h2 style="font-size:1.125rem;font-weight:750;margin:0 0 6px;"><?php echo e(__('messaging.empty_title')); ?></h2>
        <p class="f-muted" style="margin:0 0 18px;"><?php echo e(__('messaging.empty_body')); ?></p>
        <a href="<?php echo e(route('portals.patient.messages.compose')); ?>" class="f-btn f-btn--primary" style="min-height:48px;">
            <?php echo e(__('messaging.compose_btn')); ?>

        </a>
    </div>
    <?php else: ?>
    <div class="pf-rows">
        <?php $__currentLoopData = $threads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(route('portals.patient.messages.show', $row['uuid'])); ?>"
           class="f-card pf-row <?php echo e($row['unread'] ? 'pf-unread' : ''); ?>"
           aria-label="<?php echo e(__('finder_patient.open_conversation')); ?>">
            <div class="pf-row__body">
                <div class="pf-row__title">
                    <?php echo e($row['title'] ?: __('messaging.no_subject')); ?>

                    <?php if($row['unread']): ?>
                        <span class="f-badge"><?php echo e(__('messaging.unread')); ?></span>
                    <?php endif; ?>
                    <span class="f-badge <?php echo e($row['status'] === 'closed' ? 'pf-badge--muted' : 'f-badge--good'); ?>">
                        <?php echo e($row['status'] === 'closed' ? __('messaging.status_closed') : __('messaging.status_open')); ?>

                    </span>
                    <?php if(!empty($row['context_type'])): ?>
                        <?php $ctxKey = ['lab_result'=>'lab','prescription'=>'rx','appointment'=>'appt','visit'=>'visit','insurance_policy'=>'insurance'][$row['context_type']] ?? 'generic'; ?>
                        <span class="f-badge pf-badge--muted"><?php echo e(__('messaging.ctx_' . $ctxKey)); ?></span>
                    <?php endif; ?>
                </div>
                <?php if($row['snippet']): ?>
                    <p class="pf-row__snippet"><?php echo e($row['snippet']); ?></p>
                <?php endif; ?>
            </div>
            <div class="pf-row__aside">
                <?php if($row['last_at']): ?>
                    <span><?php echo e($row['last_at']->isoFormat('D MMM, HH:mm')); ?></span>
                <?php endif; ?>
                <?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-right', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\messages.blade.php ENDPATH**/ ?>