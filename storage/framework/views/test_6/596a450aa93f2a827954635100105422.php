
<?php if($facilityPickerOpen): ?>
<form method="GET" action="<?php echo e($searchAction); ?>" class="filter-bar">
    <?php $__currentLoopData = ($carry ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $carryKey => $carryValue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if($carryValue !== null && $carryValue !== ''): ?>
        <input type="hidden" name="<?php echo e($carryKey); ?>" value="<?php echo e($carryValue); ?>">
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <label class="filter-search">
        <i data-lucide="search"></i>
        <input type="text" name="facility_q" value="<?php echo e($facilityQuery); ?>"
               placeholder="<?php echo e(__('admin_extra.users_facility_find_ph')); ?>"
               aria-label="<?php echo e(__('admin_extra.users_facility_find')); ?>">
    </label>
    <button type="submit" class="btn btn-secondary btn-sm">
        <i data-lucide="search"></i> <?php echo e(__('admin_extra.users_facility_find_btn')); ?>

    </button>
</form>
<?php endif; ?>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/admin/users/_facility_finder.blade.php ENDPATH**/ ?>