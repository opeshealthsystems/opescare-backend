<?php $__env->startSection('title', __('notifications.title') . ' — OpesCare'); ?>

<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('notifications.title')); ?>

<?php $__env->startSection('head'); ?>
    <?php echo $__env->make('portals.patient._finder_head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="pf">

    <div class="pf-head">
        <div>
            <h1><?php echo e(__('notifications.title')); ?>

                <?php if($unread > 0): ?><span class="f-badge"><?php echo e($unread); ?> <?php echo e(__('notifications.unread')); ?></span><?php endif; ?>
            </h1>
            <p class="pf-sub"><?php echo e(__('notifications.subtitle')); ?></p>
        </div>
        <div class="pf-actions">
            <a href="<?php echo e(route('portals.patient.notifications.preferences')); ?>" class="f-btn">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'filter', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo e(__('notifications.preferences')); ?>

            </a>
            <?php if($unread > 0): ?>
            <form method="POST" action="<?php echo e(route('portals.patient.notifications.read')); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" class="f-btn f-btn--primary">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'check', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo e(__('notifications.mark_all_read')); ?>

                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if($events->isEmpty()): ?>
    <div class="f-card" style="text-align:center;padding:40px 20px;">
        <div class="pf-qa__icon" style="margin:0 auto 12px;width:48px;height:48px;">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'bell', 'size' => 24], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
        <h2 style="font-size:1.125rem;font-weight:750;margin:0 0 6px;"><?php echo e(__('notifications.empty_title')); ?></h2>
        <p class="f-muted" style="margin:0;"><?php echo e(__('notifications.empty_body')); ?></p>
    </div>
    <?php else: ?>
    <div class="pf-rows">
        <?php $__currentLoopData = $events; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ev): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $p = json_decode($ev->payload_json ?? '{}', true) ?: [];
                $msg = $p['message'] ?? $p['body'] ?? $p['title'] ?? ucfirst(str_replace('_', ' ', $ev->event_type));
                $createdAt = $ev->created_at ? \Illuminate\Support\Carbon::parse($ev->created_at) : null;
                $isUnread = $seenAt === null || ($createdAt && $createdAt->gt($seenAt));
            ?>
            <div class="f-card pf-row <?php echo e($isUnread ? 'pf-unread' : ''); ?>">
                <div class="pf-row__body">
                    <div class="pf-row__title">
                        <?php echo e(\App\Support\Enums::label($ev->event_type)); ?>
                        <?php if($isUnread): ?><span class="f-badge"><?php echo e(__('notifications.new')); ?></span><?php endif; ?>
                        <?php if(($ev->priority ?? 'normal') !== 'normal'): ?><span class="f-badge f-badge--warn"><?php echo e(\App\Support\Enums::label($ev->priority, 'priority')); ?></span><?php endif; ?>
                    </div>
                    <p class="f-small f-muted" style="margin:2px 0 0;white-space:normal;"><?php echo e(\Illuminate\Support\Str::limit($msg, 180)); ?></p>
                </div>
                <div class="pf-row__aside">
                    <span><?php echo e($createdAt?->isoFormat('D MMM, HH:mm')); ?></span>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <div class="pf-pagination"><?php echo e($events->links()); ?></div>
    <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\notifications.blade.php ENDPATH**/ ?>