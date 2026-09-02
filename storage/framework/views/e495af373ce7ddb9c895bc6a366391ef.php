<?php $__env->startSection('title', __('messaging.compose_title') . ' — OpesCare'); ?>

<?php $__env->startSection('breadcrumb_home', __('messaging.breadcrumb_home')); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('messaging.compose_title')); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('messaging.compose_title')); ?></h1>
        <p class="page-subtitle"><?php echo e(__('messaging.compose_subtitle')); ?></p>
    </div>
    <a href="<?php echo e(route('portals.patient.messages')); ?>" class="btn btn-secondary">
        <i data-lucide="arrow-left"></i> <?php echo e(__('messaging.thread_breadcrumb')); ?>

    </a>
</div>

<?php if(session('error')): ?>
<div class="alert alert-danger mb-4"><i data-lucide="alert-circle"></i><div><?php echo e(session('error')); ?></div></div>
<?php endif; ?>

<?php if (! ($hasTeam)): ?>
<div class="alert alert-info mb-4"><i data-lucide="info"></i><div><?php echo e(__('messaging.no_care_team')); ?></div></div>
<?php endif; ?>

<div class="panel" style="max-width:680px;">
    <div class="panel-body">
        <form method="POST" action="<?php echo e(route('portals.patient.messages.store')); ?>">
            <?php echo csrf_field(); ?>

            <div style="margin-bottom:1rem;">
                <label class="form-label" for="title"><?php echo e(__('messaging.compose_subject')); ?></label>
                <input type="text" id="title" name="title" class="form-control" maxlength="150"
                       value="<?php echo e(old('title')); ?>" required>
                <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="page-subtitle" style="color:#b91c1c;margin:.35rem 0 0;"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div style="margin-bottom:1rem;">
                <label class="form-label" for="context_type"><?php echo e(__('messaging.compose_about')); ?></label>
                <select id="context_type" name="context_type" class="form-control" onchange="ocFilterCtx(this.value)">
                    <option value=""><?php echo e(__('messaging.compose_about_none')); ?></option>
                    <option value="appointment"><?php echo e(__('messaging.ctx_appt')); ?></option>
                    <option value="lab_result"><?php echo e(__('messaging.ctx_lab')); ?></option>
                    <option value="prescription"><?php echo e(__('messaging.ctx_rx')); ?></option>
                    <option value="visit"><?php echo e(__('messaging.ctx_visit')); ?></option>
                    <option value="insurance_policy"><?php echo e(__('messaging.ctx_insurance')); ?></option>
                </select>
                <p class="page-subtitle" style="margin:.4rem 0 0;font-size:.82rem;"><?php echo e(__('messaging.compose_about_hint')); ?></p>
            </div>

            <div id="ctx-item-wrap" style="margin-bottom:1rem;display:none;">
                <label class="form-label" for="context_id"><?php echo e(__('messaging.compose_select_item')); ?></label>
                <select id="context_id" name="context_id" class="form-control">
                    <option value="">—</option>
                    <?php $__currentLoopData = $catalog; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type => $items): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($item['id']); ?>" data-ctx="<?php echo e($type); ?>" style="display:none;"><?php echo e($item['label']); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div style="margin-bottom:1rem;">
                <label class="form-label" for="body"><?php echo e(__('messaging.compose_message')); ?></label>
                <textarea id="body" name="body" class="form-control" rows="5" maxlength="5000"
                          placeholder="<?php echo e(__('messaging.reply_placeholder')); ?>" required><?php echo e(old('body')); ?></textarea>
                <?php $__errorArgs = ['body'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="page-subtitle" style="color:#b91c1c;margin:.35rem 0 0;"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <button type="submit" class="btn btn-primary" <?php echo e($hasTeam ? '' : 'disabled'); ?>>
                <i data-lucide="send"></i> <?php echo e(__('messaging.compose_send')); ?>

            </button>
        </form>
    </div>
</div>

<script>
function ocFilterCtx(type) {
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