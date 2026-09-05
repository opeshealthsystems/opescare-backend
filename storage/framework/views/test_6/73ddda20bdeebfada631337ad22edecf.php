
<div class="form-group mt-6">
    <label class="form-label" for="primary_facility_id"><?php echo e(__('admin_extra.users_lbl_facility')); ?></label>
    <select name="primary_facility_id" id="primary_facility_id" class="form-control">
        <option value=""><?php echo e(__('admin_extra.users_ph_facility')); ?></option>
        <?php $__currentLoopData = $facilityOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $facilityOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($facilityOption->id); ?>" <?php if(old('primary_facility_id', $selected ?? null) == $facilityOption->id): echo 'selected'; endif; ?>><?php echo e($facilityOption->name); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <?php $__errorArgs = ['primary_facility_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-hint"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    <div class="form-hint">
        <?php echo e(__('admin_extra.users_facility_help')); ?>

        <?php if(! $facilityPickerOpen): ?>
            <?php echo e(__('admin_extra.users_facility_locked')); ?>

        <?php elseif($facilityOptions->isEmpty()): ?>
            <?php echo e(__('admin_extra.users_facility_no_match')); ?>

        <?php elseif($facilityOptions->count() >= $facilityPickerCap): ?>
            <?php echo e(__('admin_extra.users_facility_hint', ['n' => $facilityPickerCap])); ?>

        <?php endif; ?>
    </div>
</div>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/admin/users/_facility_picker.blade.php ENDPATH**/ ?>