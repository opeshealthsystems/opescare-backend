<?php $__env->startSection('title', $invalid ?? false ? __('passwords.invalid_link.title') : __('onboarding.forgot.reset_title')); ?>

<?php $__env->startSection('content'); ?>
    <div class="f-auth-col">
        <div class="f-auth-card">

            <?php if($invalid ?? false): ?>

                <div class="f-auth-badge f-auth-badge--danger">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>

                <h1 class="f-auth-title"><?php echo e(__('passwords.invalid_link.title')); ?></h1>
                <p class="f-auth-sub" style="margin-bottom:16px"><?php echo e(__('passwords.invalid_link.body', ['minutes' => $ttl ?? 60])); ?></p>
                <p class="f-auth-sub"><?php echo e(__('passwords.invalid_link.next')); ?></p>

                <a href="<?php echo e(route('password.request')); ?>" class="f-btn f-btn--primary f-btn--block">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'refresh-cw', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo e(__('passwords.invalid_link.cta')); ?>

                </a>

                <div class="f-auth-alt f-auth-alt--divide" style="margin-top:16px">
                    <p><a href="<?php echo e(route('login')); ?>"><?php echo e(__('onboarding.selector.signin')); ?></a></p>
                </div>

            <?php else: ?>

                <h1 class="f-auth-title"><?php echo e(__('onboarding.forgot.reset_title')); ?></h1>
                <p class="f-auth-sub"><?php echo e(__('passwords.form.intro', ['email' => $email])); ?></p>

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

                <form action="<?php echo e(route('password.update', $token)); ?>" method="POST" novalidate>
                    <?php echo csrf_field(); ?>

                    
                    <input type="hidden" name="email" value="<?php echo e($email); ?>">

                    <div class="f-field">
                        <label for="password" class="f-label"><?php echo e(__('onboarding.forgot.new_pass')); ?></label>
                        <div class="f-auth-pass">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="f-input<?php echo e($errors->has('password') ? ' f-input--invalid' : ''); ?>"
                                required
                                minlength="8"
                                autocomplete="new-password"
                            >
                            <?php echo $__env->make('auth.partials.password_toggle', ['for' => 'password'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
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
                        <label for="password_confirmation" class="f-label"><?php echo e(__('onboarding.forgot.confirm_new')); ?></label>
                        <div class="f-auth-pass">
                            <input
                                type="password"
                                id="password_confirmation"
                                name="password_confirmation"
                                class="f-input<?php echo e($errors->has('password_confirmation') ? ' f-input--invalid' : ''); ?>"
                                required
                                minlength="8"
                                autocomplete="new-password"
                            >
                            <?php echo $__env->make('auth.partials.password_toggle', ['for' => 'password_confirmation'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                        <?php $__errorArgs = ['password_confirmation'];
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

                    <div class="f-auth-fine" style="margin:0 0 16px">
                        <strong><?php echo e(__('passwords.form.policy_title')); ?></strong>
                        <ul style="margin:6px 0 0;padding-left:18px">
                            <li><?php echo e(__('passwords.form.policy_length')); ?></li>
                            <li><?php echo e(__('passwords.form.policy_unique')); ?></li>
                            <li><?php echo e(__('passwords.form.policy_private')); ?></li>
                        </ul>
                    </div>

                    <button type="submit" class="f-btn f-btn--primary f-btn--block">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'check', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php echo e(__('onboarding.forgot.reset_cta')); ?>

                    </button>
                </form>

                <div class="f-auth-alt f-auth-alt--divide">
                    <p><a href="<?php echo e(route('login')); ?>"><?php echo e(__('onboarding.selector.signin')); ?></a></p>
                </div>

            <?php endif; ?>

        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\auth\reset_password.blade.php ENDPATH**/ ?>