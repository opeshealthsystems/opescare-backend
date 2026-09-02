<?php $__env->startSection('title', ($thread->title ?: __('messaging.conversation')) . ' — OpesCare'); ?>

<?php $__env->startSection('breadcrumb_home', __('messaging.staff_breadcrumb_home')); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.staff')); ?>
<?php $__env->startSection('breadcrumb_section', __('messaging.thread_breadcrumb')); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e($thread->title ?: __('messaging.conversation')); ?></h1>
        <p class="page-subtitle">
            <span class="badge badge-<?php echo e($thread->status === 'closed' ? 'muted' : 'success'); ?>">
                <?php echo e($thread->status === 'closed' ? __('messaging.status_closed') : __('messaging.status_open')); ?>

            </span>
            <?php if($patient): ?>
                <span style="margin-left:.5rem;">
                    <i data-lucide="user" style="width:.85rem;height:.85rem;vertical-align:middle;"></i>
                    <?php echo e(__('messaging.patient_label')); ?>: <?php echo e(trim($patient->first_name . ' ' . $patient->last_name)); ?>

                    <?php if($patient->health_id): ?><span style="opacity:.7;">· <?php echo e($patient->health_id); ?></span><?php endif; ?>
                </span>
            <?php endif; ?>
        </p>
    </div>
    <div>
        <a href="<?php echo e(route('portals.staff.messages')); ?>" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i> <?php echo e(__('messaging.thread_breadcrumb')); ?>

        </a>
    </div>
</div>

<?php if(session('success')): ?>
<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div>
<?php endif; ?>
<?php if(session('error')): ?>
<div class="alert alert-danger mb-4"><i data-lucide="alert-circle"></i><div><?php echo e(session('error')); ?></div></div>
<?php endif; ?>

<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="messages-square"></i> <?php echo e(__('messaging.conversation')); ?></h3>
    </div>
    <div class="panel-body">
        <?php if($messages->isEmpty()): ?>
            <div class="empty-state">
                <i data-lucide="message-square"></i>
                <p><?php echo e(__('messaging.no_messages')); ?></p>
            </div>
        <?php else: ?>
            <div style="display:grid;gap:1rem;">
                <?php $__currentLoopData = $messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $msg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $mine = $msg['sender_id'] === $userId; ?>
                    <div style="display:flex;<?php echo e($mine ? 'justify-content:flex-end;' : 'justify-content:flex-start;'); ?>">
                        <div class="panel" style="margin:0;max-width:75%;<?php echo e($mine ? 'border-left:4px solid #0F4C81;' : ''); ?>">
                            <div class="panel-body" style="padding:.75rem 1rem;">
                                <div style="font-weight:700;font-size:.85rem;margin-bottom:.25rem;">
                                    <?php echo e($mine ? __('messaging.you') : __('messaging.from_patient')); ?>

                                </div>
                                <div style="white-space:pre-wrap;"><?php echo e($msg['body']); ?></div>
                                <?php if($msg['sent_at']): ?>
                                    <div class="page-subtitle" style="font-size:.75rem;margin-top:.4rem;">
                                        <?php echo e(__('messaging.sent_at')); ?> <?php echo e($msg['sent_at']->isoFormat('LLL')); ?>

                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="panel">
    <div class="panel-body">
        <?php if($thread->status === 'closed'): ?>
            <div class="alert alert-info" style="margin:0;">
                <i data-lucide="lock"></i><div><?php echo e(__('messaging.closed_notice')); ?></div>
            </div>
        <?php else: ?>
            <form method="POST" action="<?php echo e(route('portals.staff.messages.send', $thread->uuid)); ?>">
                <?php echo csrf_field(); ?>
                <label class="form-label" for="body"><?php echo e(__('messaging.reply_label')); ?></label>
                <textarea id="body" name="body" class="form-control" rows="3" maxlength="5000"
                          placeholder="<?php echo e(__('messaging.reply_placeholder')); ?>" required><?php echo e(old('body')); ?></textarea>
                <?php $__errorArgs = ['body'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="page-subtitle" style="color:#b91c1c;margin:.35rem 0 0;"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <div style="margin-top:.75rem;">
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="send"></i> <?php echo e(__('messaging.send')); ?>

                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\staff\messages-thread.blade.php ENDPATH**/ ?>