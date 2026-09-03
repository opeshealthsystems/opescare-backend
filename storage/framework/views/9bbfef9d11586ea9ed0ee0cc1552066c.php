<?php $__env->startSection('title', ($thread->title ?: __('messaging.conversation')) . ' — OpesCare'); ?>

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
            <h1><?php echo e($thread->title ?: __('messaging.conversation')); ?></h1>
            <p class="pf-sub">
                <span class="f-badge <?php echo e($thread->status === 'closed' ? 'pf-badge--muted' : 'f-badge--good'); ?>">
                    <?php echo e($thread->status === 'closed' ? __('messaging.status_closed') : __('messaging.status_open')); ?>

                </span>
            </p>
        </div>
        <a href="<?php echo e(route('portals.patient.messages')); ?>" class="f-btn">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-left', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo e(__('messaging.thread_breadcrumb')); ?>

        </a>
    </div>

    <?php if(!empty($context)): ?>
    <div class="pf-alert">
        <?php echo $__env->make('partials.finder.icon', ['name' => 'info', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <p><span class="f-small" style="text-transform:uppercase;letter-spacing:.04em;font-weight:700;display:block;"><?php echo e(__('messaging.ctx_about')); ?></span>
        <strong><?php echo e($context['label']); ?></strong></p>
    </div>
    <?php endif; ?>

    <div class="f-card" style="margin-bottom:12px;">
        <?php if($messages->isEmpty()): ?>
            <p class="f-muted" style="text-align:center;margin:20px 0;"><?php echo e(__('messaging.no_messages')); ?></p>
        <?php else: ?>
            <div class="pf-msgs">
                <?php $__currentLoopData = $messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $msg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $mine = $msg['sender_id'] === $userId; ?>
                    <div class="pf-msg <?php echo e($mine ? 'pf-msg--mine' : ''); ?>">
                        <div class="pf-msg__who"><?php echo e($mine ? __('messaging.you') : __('messaging.participant')); ?></div>
                        <div class="pf-msg__body"><?php echo e($msg['body']); ?></div>
                        <?php if($msg['sent_at']): ?>
                            <div class="pf-msg__at"><?php echo e($msg['sent_at']->isoFormat('LLL')); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if($thread->status === 'closed'): ?>
        <div class="pf-alert">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'info', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <p><?php echo e(__('messaging.closed_notice')); ?></p>
        </div>
    <?php else: ?>
        <form method="POST" action="<?php echo e(route('portals.patient.messages.send', $thread->uuid)); ?>" class="f-card">
            <?php echo csrf_field(); ?>
            <div class="f-field" style="margin-bottom:12px;">
                <label class="f-label" for="body"><?php echo e(__('messaging.reply_label')); ?></label>
                <textarea id="body" name="body" class="f-input" rows="3" maxlength="5000"
                          placeholder="<?php echo e(__('messaging.reply_placeholder')); ?>" required><?php echo e(old('body')); ?></textarea>
                <?php $__errorArgs = ['body'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="f-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <button type="submit" class="f-btn f-btn--primary" style="min-height:48px;">
                <?php echo e(__('messaging.send')); ?>

                <?php echo $__env->make('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </button>
        </form>
    <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\messages-thread.blade.php ENDPATH**/ ?>