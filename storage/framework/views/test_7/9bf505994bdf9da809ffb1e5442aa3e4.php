
<button type="button" class="f-auth-toggle" data-f-toggle="<?php echo e($for); ?>" aria-pressed="false"
        aria-label="<?php echo e(__('finder_auth.toggle_password')); ?>">
    <span class="f-auth-toggle__show" aria-hidden="true"><?php echo $__env->make('partials.finder.icon', ['name' => 'eye', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
    <span class="f-auth-toggle__hide" aria-hidden="true"><?php echo $__env->make('partials.finder.icon', ['name' => 'eye-off', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
</button>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views/auth/partials/password_toggle.blade.php ENDPATH**/ ?>