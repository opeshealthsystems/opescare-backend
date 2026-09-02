<?php $__env->startSection('title', __('onboarding.invite.title')); ?>

<?php $__env->startSection('content'); ?>
<style>
*,*::before,*::after{box-sizing:border-box}
body{margin:0;min-height:100vh;background:var(--p-bg,#f0f4f8);font-family:var(--p-font,'Inter',system-ui,sans-serif);display:flex;align-items:center;justify-content:center;padding:1.5rem}
.auth-card{background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(15,76,129,.1);padding:2.5rem 2rem;width:100%;max-width:480px}
.auth-card__icon{width:56px;height:56px;border-radius:14px;background:rgba(15,76,129,.08);display:flex;align-items:center;justify-content:center;margin-bottom:1.25rem}
.auth-card__icon i{width:26px;height:26px;color:#0F4C81}
.auth-card__title{font-size:1.375rem;font-weight:700;color:#0f172a;margin:0 0 .375rem}
.auth-card__sub{font-size:.875rem;color:#64748b;margin:0 0 1.75rem;line-height:1.5}
.form-group{margin-bottom:1.125rem}
.form-label{display:block;font-size:.8125rem;font-weight:600;color:#0f172a;margin-bottom:.375rem}
.form-input{width:100%;padding:.625rem .875rem;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.9375rem;color:#0f172a;background:#fff;outline:none;transition:border-color .15s}
.form-input:focus{border-color:#0F4C81;box-shadow:0 0 0 3px rgba(15,76,129,.12)}
.form-input--error{border-color:#dc2626}
.field-error{font-size:.8125rem;color:#dc2626;margin-top:.3rem}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:.875rem}
.btn-primary-full{width:100%;padding:.75rem;background:#0F4C81;color:#fff;border:none;border-radius:8px;font-size:.9375rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;transition:background .15s;margin-top:.5rem}
.btn-primary-full:hover{background:#0a3560}
.btn-secondary-full{width:100%;padding:.75rem;background:#fff;color:#0F4C81;border:1.5px solid #0F4C81;border-radius:8px;font-size:.9375rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;transition:background .15s,color .15s;text-decoration:none}
.btn-secondary-full:hover{background:rgba(15,76,129,.05)}
.alert-error{background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:.875rem 1rem;margin-bottom:1.25rem;color:#dc2626;font-size:.875rem;display:flex;gap:.5rem}
.status-icon{width:72px;height:72px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem}
.status-icon i{width:32px;height:32px}
.status-icon--danger{background:#fef2f2}.status-icon--danger i{color:#dc2626}
.invite-meta{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:1.125rem;margin-bottom:1.5rem}
.invite-meta__heading{font-size:.75rem;font-weight:800;color:#0F4C81;text-transform:uppercase;letter-spacing:.05em;margin:0 0 .875rem}
.invite-meta__row{display:flex;justify-content:space-between;align-items:center;padding:.375rem 0;border-bottom:1px solid #f1f5f9}
.invite-meta__row:last-child{border-bottom:none}
.invite-meta__label{font-size:.75rem;color:#64748b;font-weight:500}
.invite-meta__value{font-size:.8125rem;color:#0f172a;font-weight:600;text-align:right}
.invite-meta__value--role{color:#0F4C81;font-weight:800}
.invite-meta__value--expiry{color:#d97706}
.section-heading{font-size:.75rem;font-weight:800;color:#0F4C81;text-transform:uppercase;letter-spacing:.05em;margin:0 0 .875rem}
.checkbox-group{display:flex;align-items:flex-start;gap:.5rem;margin-top:.875rem}
.checkbox-group input[type="checkbox"]{margin-top:2px;accent-color:#0F4C81;width:16px;height:16px;flex-shrink:0}
.checkbox-group label{font-size:.875rem;color:#0f172a;line-height:1.5}
.error-center{text-align:center;padding:1rem 0}
</style>

<div class="auth-card">
    <?php if(isset($error)): ?>
        <div class="error-center">
            <div class="status-icon status-icon--danger" style="margin-bottom:1.25rem">
                <i data-lucide="shield-alert"></i>
            </div>

            <?php if($error === 'expired'): ?>
                <h1 class="auth-card__title" style="text-align:center"><?php echo e(__('onboarding.invite.errors.expired')); ?></h1>
            <?php elseif($error === 'used'): ?>
                <h1 class="auth-card__title" style="text-align:center"><?php echo e(__('onboarding.invite.errors.used')); ?></h1>
            <?php elseif($error === 'revoked'): ?>
                <h1 class="auth-card__title" style="text-align:center"><?php echo e(__('onboarding.invite.errors.revoked')); ?></h1>
            <?php endif; ?>

            <p class="auth-card__sub" style="text-align:center">
                <?php echo e(__('team.invite_error_help')); ?>

            </p>

            <a href="<?php echo e(route('login')); ?>" class="btn-secondary-full">
                <i data-lucide="arrow-left"></i>
                <span><?php echo e(__('onboarding.selector.signin')); ?></span>
            </a>
        </div>
    <?php else: ?>
        <div class="auth-card__icon">
            <i data-lucide="mail-open"></i>
        </div>
        <h1 class="auth-card__title"><?php echo e(__('onboarding.invite.title')); ?></h1>
        <p class="auth-card__sub"><?php echo e(__('onboarding.invite.subtitle')); ?></p>

        <div class="invite-meta">
            <p class="invite-meta__heading"><?php echo e(__('onboarding.invite.sec_details')); ?></p>
            <div class="invite-meta__row">
                <span class="invite-meta__label"><?php echo e(__('team.invite_email_lbl')); ?></span>
                <span class="invite-meta__value"><?php echo e($email); ?></span>
            </div>
            <div class="invite-meta__row">
                <span class="invite-meta__label"><?php echo e(__('onboarding.invite.facility_lbl')); ?></span>
                <span class="invite-meta__value"><?php echo e($facility_name); ?></span>
            </div>
            <div class="invite-meta__row">
                <span class="invite-meta__label"><?php echo e(__('onboarding.invite.role_lbl')); ?></span>
                <span class="invite-meta__value invite-meta__value--role"><?php echo e($role_name); ?></span>
            </div>
            <div class="invite-meta__row">
                <span class="invite-meta__label"><?php echo e(__('onboarding.invite.invited_by_lbl')); ?></span>
                <span class="invite-meta__value"><?php echo e($invited_by); ?></span>
            </div>
            <div class="invite-meta__row">
                <span class="invite-meta__label"><?php echo e(__('onboarding.invite.expiry_lbl')); ?></span>
                <span class="invite-meta__value invite-meta__value--expiry"><?php echo e($expiry); ?></span>
            </div>
        </div>

        <?php if(session('error')): ?>
            <div class="alert-error">
                <i data-lucide="triangle-alert" style="width:16px;height:16px;flex-shrink:0;margin-top:2px"></i>
                <div><?php echo e(session('error')); ?></div>
            </div>
        <?php endif; ?>

        <?php if($errors->any()): ?>
            <div class="alert-error">
                <i data-lucide="triangle-alert" style="width:16px;height:16px;flex-shrink:0;margin-top:2px"></i>
                <ul style="margin:0;padding-left:1.25rem;">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?php echo e(route('invite.accept.submit', $token)); ?>" method="POST">
            <?php echo csrf_field(); ?>

            <p class="section-heading"><?php echo e(__('onboarding.invite.sec_profile')); ?></p>

            <div class="form-group">
                <label for="name" class="form-label"><?php echo e(__('onboarding.common.full_name')); ?> *</label>
                <input type="text" id="name" name="name" class="form-input<?php echo e($errors->has('name') ? ' form-input--error' : ''); ?>" required value="<?php echo e(old('name')); ?>">
                <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="field-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="form-group">
                <label for="phone" class="form-label"><?php echo e(__('onboarding.common.phone')); ?> *</label>
                <input type="tel" id="phone" name="phone" class="form-input<?php echo e($errors->has('phone') ? ' form-input--error' : ''); ?>" required value="<?php echo e(old('phone')); ?>">
                <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="field-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password" class="form-label"><?php echo e(__('onboarding.common.password')); ?> *</label>
                    <input type="password" id="password" name="password" class="form-input<?php echo e($errors->has('password') ? ' form-input--error' : ''); ?>" required minlength="8" placeholder="<?php echo e(__('team.password_hint')); ?>">
                    <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="field-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="form-group">
                    <label for="confirm_password" class="form-label"><?php echo e(__('onboarding.common.confirm_password')); ?> *</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input<?php echo e($errors->has('confirm_password') ? ' form-input--error' : ''); ?>" required placeholder="••••••••">
                    <?php $__errorArgs = ['confirm_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="field-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" id="accept_terms" name="accept_terms" required>
                <label for="accept_terms"><?php echo e(__('onboarding.invite.terms_label')); ?></label>
            </div>
            <?php $__errorArgs = ['accept_terms'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="field-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

            <button type="submit" class="btn-primary-full" style="margin-top:1.25rem">
                <i data-lucide="user-check"></i>
                <span><?php echo e(__('onboarding.invite.cta_btn')); ?></span>
            </button>
        </form>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/auth/invite.blade.php ENDPATH**/ ?>