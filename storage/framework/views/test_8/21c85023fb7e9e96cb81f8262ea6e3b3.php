<?php $__env->startSection('sidebar_role_badge'); ?>
<div class="sidebar-role-badge sidebar-role-badge--primary">
    <i data-lucide="shield-check"></i>
    <?php echo e(__('public.adm_cc_sidebar_super_admin')); ?>

</div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('sidebar_user_role', __('public.adm_cc_sidebar_super_admin')); ?>

<?php $__env->startSection('sidebar_nav'); ?>
<?php echo $__env->make('portals.admin._admin_nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/admin/control_center/_sidebar.blade.php ENDPATH**/ ?>