<?php $__env->startSection('title', __('messaging.compose_title') . ' — OpesCare'); ?>

<?php $__env->startSection('breadcrumb_home', __('messaging.breadcrumb_home')); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('messaging.compose_title')); ?>

<?php $__env->startSection('head'); ?>
    <?php echo $__env->make('portals.patient._finder_head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="pf pf--form">

    <div class="pf-head">
        <div>
            <h1><?php echo e(__('messaging.compose_title')); ?></h1>
            <p class="pf-sub"><?php echo e(__('messaging.compose_subtitle')); ?></p>
        </div>
        <a href="<?php echo e(route('portals.patient.messages')); ?>" class="f-btn">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-left', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo e(__('messaging.thread_breadcrumb')); ?>

        </a>
    </div>

    <?php if (! ($hasTeam)): ?>
    <div class="pf-alert">
        <?php echo $__env->make('partials.finder.icon', ['name' => 'info', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <p><?php echo e(__('messaging.no_care_team')); ?></p>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(route('portals.patient.messages.store')); ?>" class="f-card">
        <?php echo csrf_field(); ?>

        <div class="f-field">
            <label class="f-label" for="title"><?php echo e(__('messaging.compose_subject')); ?></label>
            <input type="text" id="title" name="title" class="f-input" maxlength="150"
                   value="<?php echo e(old('title')); ?>" required>
            <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="f-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="f-field">
            <label class="f-label" for="context_type"><?php echo e(__('messaging.compose_about')); ?></label>
            <select id="context_type" name="context_type" class="f-input" onchange="pfFilterCtx(this.value)">
                <option value=""><?php echo e(__('messaging.compose_about_none')); ?></option>
                <option value="appointment"><?php echo e(__('messaging.ctx_appt')); ?></option>
                <option value="lab_result"><?php echo e(__('messaging.ctx_lab')); ?></option>
                <option value="prescription"><?php echo e(__('messaging.ctx_rx')); ?></option>
                <option value="visit"><?php echo e(__('messaging.ctx_visit')); ?></option>
                <option value="insurance_policy"><?php echo e(__('messaging.ctx_insurance')); ?></option>
            </select>
            <p class="f-hint"><?php echo e(__('messaging.compose_about_hint')); ?></p>
        </div>

        <div class="f-field" id="ctx-item-wrap" style="display:none;">
            <label class="f-label" for="context_id"><?php echo e(__('messaging.compose_select_item')); ?></label>
            <select id="context_id" name="context_id" class="f-input">
                <option value="">—</option>
                <?php $__currentLoopData = $catalog; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type => $items): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($item['id']); ?>" data-ctx="<?php echo e($type); ?>" style="display:none;"><?php echo e($item['label']); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>

        <div class="f-field">
            <label class="f-label" for="body"><?php echo e(__('messaging.compose_message')); ?></label>
            <textarea id="body" name="body" class="f-input" rows="5" maxlength="5000"
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

        <button type="submit" class="f-btn f-btn--primary f-btn--block" style="min-height:48px;" <?php echo e($hasTeam ? '' : 'disabled'); ?>>
            <?php echo e(__('messaging.compose_send')); ?>

            <?php echo $__env->make('partials.finder.icon', ['name' => 'send', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </button>
    </form>
</div>

<script>
function pfFilterCtx(type) {
    var wrap = document.getElementById('ctx-item-wrap');
    var sel  = document.getElementById('context_id');
    if (!type) { wrap.style.display = 'none'; sel.value = ''; return; }
    wrap.style.display = '';
    sel.value = '';
    Array.prototype.forEach.call(sel.options, function (o) {
        if (!o.value) { return; }
        o.style.display = (o.getAttribute('data-ctx') === type) ? '' : 'none';
    });
}
</script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\messages-compose.blade.php ENDPATH**/ ?>