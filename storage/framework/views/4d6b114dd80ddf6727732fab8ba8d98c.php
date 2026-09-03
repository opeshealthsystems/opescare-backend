<?php $__env->startSection('title', __('onboarding.forgot.title')); ?>

<?php $__env->startSection('content'); ?>
    <div class="f-auth-col">
        <div class="f-auth-card">
            <h1 class="f-auth-title"><?php echo e(__('onboarding.forgot.title')); ?></h1>
            
            <p class="f-auth-sub"><?php echo e(__('passwords.request.desc')); ?></p>

            <?php if(session('success')): ?>
                
                <div class="f-auth-alert f-auth-alert--success" role="status">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'check', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <div><?php echo e(session('success')); ?></div>
                </div>

                <a href="<?php echo e(route('login')); ?>" class="f-btn f-btn--block">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-left', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo e(__('onboarding.selector.signin')); ?>

                </a>
            <?php else: ?>
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

                <form action="<?php echo e(route('password.email')); ?>" method="POST" novalidate>
                    <?php echo csrf_field(); ?>

                    <div class="f-field">
                        <label for="email" class="f-label"><?php echo e(__('onboarding.common.email')); ?></label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="f-input<?php echo e($errors->has('email') ? ' f-input--invalid' : ''); ?>"
                            required
                            autofocus
                            autocomplete="email"
                            inputmode="email"
                            maxlength="180"
                            value="<?php echo e(old('email')); ?>"
                        >
                        <?php $__errorArgs = ['email'];
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

                    <p class="f-auth-fine" style="margin:0 0 16px"><?php echo e(__('onboarding.forgot.privacy_note')); ?></p>

                    <button type="submit" class="f-btn f-btn--primary f-btn--block">
                        <?php echo e(__('onboarding.forgot.cta')); ?>

                        <?php echo $__env->make('partials.finder.icon', ['name' => 'arrow-right', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </button>
                </form>

                <div class="f-auth-alt f-auth-alt--divide">
                    <p><a href="<?php echo e(route('login')); ?>"><?php echo e(__('onboarding.selector.signin')); ?></a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\auth\forgot_password.blade.php ENDPATH**/ ?>