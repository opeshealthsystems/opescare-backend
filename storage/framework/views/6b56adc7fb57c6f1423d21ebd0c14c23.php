<!DOCTYPE html>
<html lang="<?php echo e(app()->getLocale()); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e(__('public.portal.invite_accept_title')); ?></title>
    <link rel="stylesheet" href="<?php echo e(asset('css/portal.css')); ?>">
</head>
<body class="portal-body" style="display:flex;align-items:center;justify-content:center;min-height:100vh;">
<div class="panel" style="max-width:480px;width:100%;margin:2rem;">
    <div class="panel-body" style="text-align:center;">
        <?php if($error): ?>
        <div class="alert alert-danger mb-6">
            <i data-lucide="alert-circle"></i> <?php echo e($error); ?>

        </div>
        <a href="<?php echo e(route('login')); ?>" class="btn btn-primary"><?php echo e(__('public.portal.invite_accept_go_login')); ?></a>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i data-lucide="users"></i></div>
            <h3><?php echo e(__('public.portal.invite_accept_h3')); ?></h3>
            <p>
                <strong><?php echo e($link->guardianUser->name ?? $link->guardianUser->email); ?></strong> <?php echo e(__('public.portal.invite_accept_wants')); ?>

                <strong><?php echo e($link->dependentPatient->first_name); ?> <?php echo e($link->dependentPatient->last_name); ?></strong><?php echo e(__('public.portal.invite_accept_records')); ?>

                <strong><?php echo e(\App\Support\Enums::label($link->relationship)); ?></strong>
                <?php echo e(__('public.portal.invite_accept_with')); ?> <strong><?php echo e($link->access_level === 'full' ? __('public.portal.invite_accept_full') : __('public.portal.invite_accept_readonly')); ?></strong> <?php echo e(__('public.portal.invite_accept_access')); ?>

            </p>
        </div>
        <form method="POST" action="<?php echo e(route('portals.patient.family.invite.confirm', $token)); ?>">
            <?php echo csrf_field(); ?>
            <div class="row-actions" style="justify-content:center;">
                <button type="submit" class="btn btn-primary"><?php echo e(__('public.portal.invite_accept_btn')); ?></button>
                <a href="<?php echo e(route('login')); ?>" class="btn btn-secondary"><?php echo e(__('public.portal.invite_accept_decline')); ?></a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>
<script src="<?php echo e(asset('js/lucide.min.js')); ?>"></script>
<script>if (typeof lucide !== 'undefined') lucide.createIcons();</script>
</body>
</html>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\family\invite-accept.blade.php ENDPATH**/ ?>