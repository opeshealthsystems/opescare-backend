<?php $__env->startSection('title', __('notifications.title') . ' — OpesCare'); ?>

<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('notifications.title')); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('notifications.title')); ?></h1>
        <p class="page-subtitle"><?php echo e(__('notifications.subtitle')); ?></p>
    </div>
    <div style="display:flex;gap:.5rem;">
        <a href="<?php echo e(route('portals.patient.notifications.preferences')); ?>" class="btn btn-secondary">
            <i data-lucide="sliders-horizontal"></i> <?php echo e(__('notifications.preferences')); ?>

        </a>
        <?php if($unread > 0): ?>
        <form method="POST" action="<?php echo e(route('portals.patient.notifications.read')); ?>">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn btn-primary"><i data-lucide="check-check"></i> <?php echo e(__('notifications.mark_all_read')); ?></button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php if(session('success')): ?>
<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="bell"></i> <?php echo e(__('notifications.title')); ?></h3>
        <?php if($unread > 0): ?><span class="badge badge-primary"><?php echo e($unread); ?> <?php echo e(__('notifications.unread')); ?></span><?php endif; ?>
    </div>
    <div class="panel-body">
        <?php if($events->isEmpty()): ?>
            <div class="empty-state">
                <i data-lucide="bell-off"></i>
                <h3><?php echo e(__('notifications.empty_title')); ?></h3>
                <p><?php echo e(__('notifications.empty_body')); ?></p>
            </div>
        <?php else: ?>
            <ul style="list-style:none;padding:0;margin:0;display:grid;gap:.5rem;">
                <?php $__currentLoopData = $events; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ev): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $p = json_decode($ev->payload_json ?? '{}', true) ?: [];
                        $msg = $p['message'] ?? $p['body'] ?? $p['title'] ?? ucfirst(str_replace('_', ' ', $ev->event_type));
                        $createdAt = $ev->created_at ? \Illuminate\Support\Carbon::parse($ev->created_at) : null;
                        $isUnread = $seenAt === null || ($createdAt && $createdAt->gt($seenAt));
                    ?>
                    <li class="panel" style="margin:0;padding:.85rem 1rem;border-left:4px solid <?php echo e($isUnread ? '#0F4C81' : 'transparent'); ?>;">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;">
                            <div style="min-width:0;">
                                <div style="display:flex;align-items:center;gap:.5rem;">
                                    <strong><?php echo e(\App\Support\Enums::label($ev->event_type)); ?></strong>
                                    <?php if($isUnread): ?><span class="badge badge-primary"><?php echo e(__('notifications.new')); ?></span><?php endif; ?>
                                    <?php if(($ev->priority ?? 'normal') !== 'normal'): ?><span class="badge badge-warning"><?php echo e(\App\Support\Enums::label($ev->priority, 'priority')); ?></span><?php endif; ?>
                                </div>
                                <p class="page-subtitle" style="margin:.3rem 0 0;"><?php echo e(\Illuminate\Support\Str::limit($msg, 180)); ?></p>
                            </div>
                            <span class="page-subtitle" style="font-size:.78rem;white-space:nowrap;"><?php echo e($createdAt?->isoFormat('LLL')); ?></span>
                        </div>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
            <div style="margin-top:1rem;"><?php echo e($events->links()); ?></div>
        <?php endif; ?>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\notifications.blade.php ENDPATH**/ ?>