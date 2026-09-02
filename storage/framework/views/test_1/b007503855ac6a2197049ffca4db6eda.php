<?php $__env->startSection('title', __('messaging.inbox_title') . ' — OpesCare'); ?>

<?php $__env->startSection('breadcrumb_home', __('messaging.breadcrumb_home')); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('messaging.thread_breadcrumb')); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('messaging.inbox_title')); ?></h1>
        <p class="page-subtitle"><?php echo e(__('messaging.inbox_subtitle')); ?></p>
    </div>
    <a href="<?php echo e(route('portals.patient.messages.compose')); ?>" class="btn btn-primary">
        <i data-lucide="plus"></i> <?php echo e(__('messaging.compose_btn')); ?>

    </a>
</div>

<?php if(session('success')): ?>
<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div>
<?php endif; ?>
<?php if(session('error')): ?>
<div class="alert alert-danger mb-4"><i data-lucide="alert-circle"></i><div><?php echo e(session('error')); ?></div></div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="messages-square"></i> <?php echo e(__('messaging.panel_title')); ?></h3>
    </div>
    <div class="panel-body">
        <?php if($threads->isEmpty()): ?>
            <div class="empty-state">
                <i data-lucide="message-square"></i>
                <h3><?php echo e(__('messaging.empty_title')); ?></h3>
                <p><?php echo e(__('messaging.empty_body')); ?></p>
            </div>
        <?php else: ?>
            <ul style="list-style:none;padding:0;margin:0;display:grid;gap:.5rem;">
                <?php $__currentLoopData = $threads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li>
                        <a href="<?php echo e(route('portals.patient.messages.show', $row['uuid'])); ?>"
                           class="panel"
                           style="display:block;text-decoration:none;color:inherit;margin:0;padding:1rem;border-left:4px solid <?php echo e($row['unread'] ? '#0F4C81' : 'transparent'); ?>;">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;">
                                <div style="min-width:0;">
                                    <div style="display:flex;align-items:center;gap:.5rem;">
                                        <strong style="font-size:1.05rem;"><?php echo e($row['title'] ?: __('messaging.no_subject')); ?></strong>
                                        <?php if($row['unread']): ?>
                                            <span class="badge badge-primary"><?php echo e(__('messaging.unread')); ?></span>
                                        <?php endif; ?>
                                        <span class="badge badge-<?php echo e($row['status'] === 'closed' ? 'muted' : 'success'); ?>">
                                            <?php echo e($row['status'] === 'closed' ? __('messaging.status_closed') : __('messaging.status_open')); ?>

                                        </span>
                                        <?php if(!empty($row['context_type'])): ?>
                                            <?php $ctxKey = ['lab_result'=>'lab','prescription'=>'rx','appointment'=>'appt','visit'=>'visit','insurance_policy'=>'insurance'][$row['context_type']] ?? 'generic'; ?>
                                            <span class="badge"><i data-lucide="paperclip" style="width:.8rem;height:.8rem;"></i> <?php echo e(__('messaging.ctx_' . $ctxKey)); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if($row['snippet']): ?>
                                        <p class="page-subtitle" style="margin:.35rem 0 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo e($row['snippet']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div style="text-align:right;white-space:nowrap;">
                                    <?php if($row['last_at']): ?>
                                        <span class="page-subtitle" style="font-size:.8rem;"><?php echo e($row['last_at']->isoFormat('LLL')); ?></span>
                                    <?php endif; ?>
                                    <div><i data-lucide="chevron-right"></i></div>
                                </div>
                            </div>
                        </a>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/patient/messages.blade.php ENDPATH**/ ?>