<?php $__env->startSection('title', __('notifications_ui.center_title', [], app()->getLocale()) ?: 'Notifications'); ?>
<?php $__env->startSection('breadcrumb_section', __('notifications_ui.center_title', [], app()->getLocale()) ?: 'Notifications'); ?>

<?php $__env->startSection('head'); ?>
<style>
    /* Whole-row link overlay. .timeline-item is already position:relative,
       so the anchor stretches across the item and the entire row navigates. */
    .notif-stretch { position: absolute; inset: 0; z-index: 3; border-radius: var(--p-radius-md, 8px); }
    .notif-stretch:focus-visible { outline: 2px solid var(--p-primary); outline-offset: 2px; }
    .notif-item-link:hover .timeline-title { color: var(--p-primary); }
    .notif-sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <?php echo e(__('notifications_ui.center_title', [], app()->getLocale()) ?: 'Notifications'); ?>

            <?php if($unread > 0): ?>
                <span class="badge badge-danger badge-sm"><?php echo e($unread); ?></span>
            <?php endif; ?>
        </h1>
        <p class="page-subtitle"><?php echo e(__('notifications_ui.center_subtitle', [], app()->getLocale()) ?: 'Everything that needs your attention.'); ?></p>
    </div>

    <?php if($events->total() > 0): ?>
    <form method="POST" action="<?php echo e(route('notifications.read')); ?>">
        <?php echo csrf_field(); ?>
        <button type="submit" class="btn btn-secondary btn-sm">
            <i data-lucide="check-check"></i>
            <?php echo e(__('notifications_ui.mark_all_read', [], app()->getLocale()) ?: 'Mark all as read'); ?>

        </button>
    </form>
    <?php endif; ?>
</div>

<?php if(session('success')): ?>
<div class="alert alert-success mb-6">
    <i data-lucide="check-circle"></i>
    <div><?php echo e(session('success')); ?></div>
</div>
<?php endif; ?>

<?php if($events->total() === 0): ?>

    <div class="panel">
        <div class="empty-state">
            <div class="empty-state-icon"><i data-lucide="bell-off"></i></div>
            <h3><?php echo e(__('notifications_ui.empty', [], app()->getLocale()) ?: 'You have no notifications yet.'); ?></h3>
            <p><?php echo e(__('notifications_ui.empty_desc', [], app()->getLocale()) ?: 'Orders, alerts and updates will appear here.'); ?></p>
        </div>
    </div>

<?php else: ?>

    <div class="panel">
        <div class="panel-body">
            <div class="timeline">
                <?php $__currentLoopData = $events; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $p = json_decode($e->payload_json ?? '{}', true) ?: [];

                        $category = $e->communication_type ?: 'general';
                        $known    = ['blood', 'pharmacy', 'appointment', 'account', 'admin', 'general'];
                        $catKey   = in_array($category, $known, true) ? $category : 'general';

                        $icon = match ($category) {
                            'blood'       => 'droplet',
                            'pharmacy'    => 'pill',
                            'appointment' => 'calendar-check-2',
                            'admin'       => 'shield',
                            'account'     => 'user',
                            default       => 'bell',
                        };

                        // Dot colour keyed to category (empty = brand navy default).
                        $dot = match ($category) {
                            'blood'    => 'danger',
                            'pharmacy' => 'teal',
                            'admin'    => 'warning',
                            'account'  => 'neutral',
                            default    => '',
                        };

                        $title = $p['title'] ?? \Illuminate\Support\Str::headline($e->event_type);
                        $body  = $p['body'] ?? ($p['message'] ?? '');
                        $url   = $p['url'] ?? null;

                        $isUnread = $seenAt === null || $e->created_at > $seenAt;
                        $time     = \Illuminate\Support\Carbon::parse($e->created_at)->diffForHumans();
                    ?>

                    <div class="timeline-item <?php echo e($url ? 'notif-item-link' : ''); ?>">
                        <div class="timeline-dot <?php echo e($dot); ?>">
                            <i data-lucide="<?php echo e($icon); ?>"></i>
                        </div>
                        <div class="timeline-body min-w-0">
                            <div class="flex-between">
                                <span class="timeline-title"><?php echo e($title); ?></span>
                                <?php if($isUnread): ?>
                                    <span class="badge badge-danger badge-sm"><?php echo e(__('notifications_ui.new_badge', [], app()->getLocale()) ?: 'New'); ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if(trim((string) $body) !== ''): ?>
                                <div class="timeline-desc"><?php echo e($body); ?></div>
                            <?php endif; ?>

                            <div class="flex items-center gap-2" style="margin-top: 0.4rem;">
                                <span class="badge badge-neutral badge-sm"><?php echo e(__('notifications_ui.category_' . $catKey, [], app()->getLocale()) ?: \Illuminate\Support\Str::ucfirst($catKey)); ?></span>
                                <span class="timeline-time"><?php echo e($time); ?></span>
                            </div>
                        </div>

                        <?php if($url): ?>
                            <a href="<?php echo e($url); ?>" class="notif-stretch" aria-label="<?php echo e($title); ?>"><span class="notif-sr-only"><?php echo e($title); ?></span></a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>

        <?php if($events->hasPages()): ?>
            <div class="panel-body"><?php echo e($events->links()); ?></div>
        <?php endif; ?>
    </div>

<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/shared/notifications.blade.php ENDPATH**/ ?>