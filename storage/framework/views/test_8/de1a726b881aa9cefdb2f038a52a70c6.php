<?php $__env->startSection('title', __('onboarding.invite.title')); ?>

<?php $__env->startSection('content'); ?>
    <div class="f-auth-col">
        <div class="f-auth-card">
            <?php if(isset($error)): ?>
                <div class="f-auth-card--center" style="padding:8px 0">
                    <div class="f-auth-badge f-auth-badge--danger f-auth-badge--center">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>

                    <?php if($error === 'expired'): ?>
                        <h1 class="f-auth-title"><?php echo e(__('onboarding.invite.errors.expired')); ?></h1>
                    <?php elseif($error === 'used'): ?>
                        <h1 class="f-auth-title"><?php echo e(__('onboarding.invite.errors.used')); ?></h1>
                    <?php elseif($error === 'revoked'): ?>
                        <h1 class="f-auth-title"><?php echo e(__('onboarding.invite.errors.revoked')); ?></h1>
                    <?php endif; ?>

                    <p class="f-auth-sub"><?php echo e(__('team.invite_error_help')); ?></p>

                    <a href="<?php echo e(route('login')); ?>" class="f-btn f-btn--block">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-left', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php echo e(__('onboarding.selector.signin')); ?>

                    </a>
                </div>
            <?php else: ?>
                <div class="f-auth-badge">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'building-2', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
                <h1 class="f-auth-title"><?php echo e(__('onboarding.invite.title')); ?></h1>
                <p class="f-auth-sub"><?php echo e(__('onboarding.invite.subtitle')); ?></p>

                <div class="f-auth-meta">
                    <p class="f-auth-meta__title"><?php echo e(__('onboarding.invite.sec_details')); ?></p>
                    <div class="f-auth-meta__row">
                        <span class="f-auth-meta__label"><?php echo e(__('team.invite_email_lbl')); ?></span>
                        <span class="f-auth-meta__value"><?php echo e($email); ?></span>
                    </div>
                    <div class="f-auth-meta__row">
                        <span class="f-auth-meta__label"><?php echo e(__('onboarding.invite.facility_lbl')); ?></span>
                        <span class="f-auth-meta__value"><?php echo e($facility_name); ?></span>
                    </div>
                    <div class="f-auth-meta__row">
                        <span class="f-auth-meta__label"><?php echo e(__('onboarding.invite.role_lbl')); ?></span>
                        <span class="f-auth-meta__value f-auth-meta__value--brand"><?php echo e($role_name); ?></span>
                    </div>
                    <div class="f-auth-meta__row">
                        <span class="f-auth-meta__label"><?php echo e(__('onboarding.invite.invited_by_lbl')); ?></span>
                        <span class="f-auth-meta__value"><?php echo e($invited_by); ?></span>
                    </div>
                    <div class="f-auth-meta__row">
                        <span class="f-auth-meta__label"><?php echo e(__('onboarding.invite.expiry_lbl')); ?></span>
                        <span class="f-auth-meta__value f-auth-meta__value--warn"><?php echo e($expiry); ?></span>
                    </div>
                </div>

                <?php if(session('error')): ?>
                    <div class="f-auth-alert f-auth-alert--danger" role="alert">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <div><?php echo e(session('error')); ?></div>
                    </div>
                <?php endif; ?>

                <?php if($errors->any()): ?>
                    <div class="f-auth-alert f-auth-alert--danger" role="alert">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <ul>
                            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?php echo e(route('invite.accept.submit', $token)); ?>" method="POST" novalidate>
                    <?php echo csrf_field(); ?>

                    <p class="f-auth-section" style="margin-top:0"><?php echo e(__('onboarding.invite.sec_profile')); ?></p>

                    <div class="f-field">
                        <label for="name" class="f-label"><?php echo e(__('onboarding.common.full_name')); ?></label>
                        <input type="text" id="name" name="name" autocomplete="name"
                               class="f-input<?php echo e($errors->has('name') ? ' f-input--invalid' : ''); ?>"
                               required value="<?php echo e(old('name')); ?>">
                        <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="f-error f-auth-error"><?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="f-field">
                        <label for="phone" class="f-label"><?php echo e(__('onboarding.common.phone')); ?></label>
                        <input type="tel" id="phone" name="phone" autocomplete="tel" inputmode="tel"
                               class="f-input<?php echo e($errors->has('phone') ? ' f-input--invalid' : ''); ?>"
                               required value="<?php echo e(old('phone')); ?>">
                        <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="f-error f-auth-error"><?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="f-field">
                        <label for="password" class="f-label"><?php echo e(__('onboarding.common.password')); ?></label>
                        <div class="f-auth-pass">
                            <input type="password" id="password" name="password" minlength="8"
                                   autocomplete="new-password"
                                   class="f-input<?php echo e($errors->has('password') ? ' f-input--invalid' : ''); ?>" required>
                            <?php echo $__env->make('auth.partials.password_toggle', ['for' => 'password'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                        <span class="f-hint"><?php echo e(__('team.password_hint')); ?></span>
                        <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="f-error f-auth-error"><?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="f-field">
                        <label for="confirm_password" class="f-label"><?php echo e(__('onboarding.common.confirm_password')); ?></label>
                        <div class="f-auth-pass">
                            <input type="password" id="confirm_password" name="confirm_password"
                                   autocomplete="new-password"
                                   class="f-input<?php echo e($errors->has('confirm_password') ? ' f-input--invalid' : ''); ?>" required>
                            <?php echo $__env->make('auth.partials.password_toggle', ['for' => 'confirm_password'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                        <?php $__errorArgs = ['confirm_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="f-error f-auth-error"><?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php echo e($message); ?></span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="f-auth-check">
                        <input type="checkbox" id="accept_terms" name="accept_terms" required>
                        <label for="accept_terms"><?php echo e(__('onboarding.invite.terms_label')); ?></label>
                    </div>
                    <?php $__errorArgs = ['accept_terms'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <span class="f-error f-auth-error" style="margin:-8px 0 12px"><?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php echo e($message); ?></span>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                    <button type="submit" class="f-btn f-btn--primary f-btn--block">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'check', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php echo e(__('onboarding.invite.cta_btn')); ?>

                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/auth/invite.blade.php ENDPATH**/ ?>