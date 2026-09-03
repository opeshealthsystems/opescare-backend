<!DOCTYPE html>
<html lang="<?php echo e(app()->getLocale()); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e(__('public.portal.invite_accept_title')); ?></title>
    <meta name="theme-color" content="#0F4C81">
    <link rel="stylesheet" href="<?php echo e(asset('assets/finder.css')); ?>">
</head>
<body>
<main style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px;">
    <div class="f-card" style="max-width:480px;width:100%;text-align:center;">
        <?php if($error): ?>
            <div style="display:flex;gap:10px;align-items:flex-start;text-align:left;padding:12px 14px;border-radius:var(--f-r);background:var(--f-danger-wash);color:var(--f-danger);margin-bottom:16px;">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <p style="margin:0;"><?php echo e($error); ?></p>
            </div>
            <a href="<?php echo e(route('login')); ?>" class="f-btn f-btn--primary" style="min-height:48px;"><?php echo e(__('public.portal.invite_accept_go_login')); ?></a>
        <?php else: ?>
            <div style="width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:var(--f-wash);color:var(--f-brand);margin:0 auto 12px;">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'users', 'size' => 24], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
            <h1 style="font-size:1.25rem;font-weight:800;margin:0 0 10px;"><?php echo e(__('public.portal.invite_accept_h3')); ?></h1>
            <p style="color:var(--f-ink-soft);margin:0 0 20px;">
                <strong><?php echo e($link->guardianUser->name ?? $link->guardianUser->email); ?></strong> <?php echo e(__('public.portal.invite_accept_wants')); ?>

                <strong><?php echo e($link->dependentPatient->first_name); ?> <?php echo e($link->dependentPatient->last_name); ?></strong><?php echo e(__('public.portal.invite_accept_records')); ?>

                <strong><?php echo e(\App\Support\Enums::label($link->relationship)); ?></strong>
                <?php echo e(__('public.portal.invite_accept_with')); ?> <strong><?php echo e($link->access_level === 'full' ? __('public.portal.invite_accept_full') : __('public.portal.invite_accept_readonly')); ?></strong> <?php echo e(__('public.portal.invite_accept_access')); ?>

            </p>
            <form method="POST" action="<?php echo e(route('portals.patient.family.invite.confirm', $token)); ?>">
                <?php echo csrf_field(); ?>
                <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
                    <button type="submit" class="f-btn f-btn--primary" style="min-height:48px;">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'check', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php echo e(__('public.portal.invite_accept_btn')); ?>

                    </button>
                    <a href="<?php echo e(route('login')); ?>" class="f-btn"><?php echo e(__('public.portal.invite_accept_decline')); ?></a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\family\invite-accept.blade.php ENDPATH**/ ?>