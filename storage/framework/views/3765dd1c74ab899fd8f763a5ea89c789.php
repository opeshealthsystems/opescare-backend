<?php $__env->startSection('title', __('onboarding.selector.title')); ?>

<?php $__env->startSection('content'); ?>
    <div class="f-auth-col f-auth-col--wide">
        <div class="f-auth-card">
            <div class="f-auth-card--center">
                <h1 class="f-auth-title"><?php echo e(__('onboarding.selector.title')); ?></h1>
                <p class="f-auth-sub"><?php echo e(__('onboarding.selector.subtitle')); ?></p>
            </div>

            <div class="f-auth-grid">

                
                <a href="<?php echo e(route('register.patient')); ?>" class="f-auth-choice">
                    <span class="f-auth-choice__icon"><?php echo $__env->make('partials.finder.icon', ['name' => 'heart-pulse', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                    <span class="f-auth-choice__body">
                        <span class="f-auth-choice__title"><?php echo e(__('onboarding.selector.cards.patient_title')); ?></span>
                        <span class="f-auth-choice__desc"><?php echo e(__('onboarding.selector.cards.patient_desc')); ?></span>
                    </span>
                    <span class="f-auth-choice__go"><?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-right', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                </a>

                
                <a href="<?php echo e(route('register.guardian')); ?>" class="f-auth-choice">
                    <span class="f-auth-choice__icon"><?php echo $__env->make('partials.finder.icon', ['name' => 'users', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                    <span class="f-auth-choice__body">
                        <span class="f-auth-choice__title"><?php echo e(__('onboarding.selector.cards.guardian_title')); ?></span>
                        <span class="f-auth-choice__desc"><?php echo e(__('onboarding.selector.cards.guardian_desc')); ?></span>
                    </span>
                    <span class="f-auth-choice__go"><?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-right', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                </a>

                
                <a href="<?php echo e(route('register.organization', ['type' => 'hospital'])); ?>" class="f-auth-choice">
                    <span class="f-auth-choice__icon"><?php echo $__env->make('partials.finder.icon', ['name' => 'building-2', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                    <span class="f-auth-choice__body">
                        <span class="f-auth-choice__title"><?php echo e(__('onboarding.selector.cards.hospital_title')); ?></span>
                        <span class="f-auth-choice__desc"><?php echo e(__('onboarding.selector.cards.hospital_desc')); ?></span>
                    </span>
                    <span class="f-auth-choice__go"><?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-right', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                </a>

                
                <a href="<?php echo e(route('register.organization', ['type' => 'pharmacy'])); ?>" class="f-auth-choice">
                    <span class="f-auth-choice__icon"><?php echo $__env->make('partials.finder.icon', ['name' => 'pill', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                    <span class="f-auth-choice__body">
                        <span class="f-auth-choice__title"><?php echo e(__('onboarding.selector.cards.pharmacy_title')); ?></span>
                        <span class="f-auth-choice__desc"><?php echo e(__('onboarding.selector.cards.pharmacy_desc')); ?></span>
                    </span>
                    <span class="f-auth-choice__go"><?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-right', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                </a>

                
                <a href="<?php echo e(route('register.organization', ['type' => 'laboratory'])); ?>" class="f-auth-choice">
                    <span class="f-auth-choice__icon"><?php echo $__env->make('partials.finder.icon', ['name' => 'search', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                    <span class="f-auth-choice__body">
                        <span class="f-auth-choice__title"><?php echo e(__('onboarding.selector.cards.laboratory_title')); ?></span>
                        <span class="f-auth-choice__desc"><?php echo e(__('onboarding.selector.cards.laboratory_desc')); ?></span>
                    </span>
                    <span class="f-auth-choice__go"><?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-right', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                </a>

                
                <a href="<?php echo e(route('register.organization', ['type' => 'insurance_company'])); ?>" class="f-auth-choice">
                    <span class="f-auth-choice__icon"><?php echo $__env->make('partials.finder.icon', ['name' => 'shield-check', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                    <span class="f-auth-choice__body">
                        <span class="f-auth-choice__title"><?php echo e(__('onboarding.selector.cards.insurer_title')); ?></span>
                        <span class="f-auth-choice__desc"><?php echo e(__('onboarding.selector.cards.insurer_desc')); ?></span>
                    </span>
                    <span class="f-auth-choice__go"><?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-right', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                </a>

                
                <a href="<?php echo e(route('register.developer')); ?>" class="f-auth-choice">
                    <span class="f-auth-choice__icon"><?php echo $__env->make('partials.finder.icon', ['name' => 'external-link', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                    <span class="f-auth-choice__body">
                        <span class="f-auth-choice__title"><?php echo e(__('onboarding.selector.cards.developer_title')); ?></span>
                        <span class="f-auth-choice__desc"><?php echo e(__('onboarding.selector.cards.developer_desc')); ?></span>
                    </span>
                    <span class="f-auth-choice__go"><?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-right', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                </a>

                
                <a href="<?php echo e(route('register.organization', ['type' => 'public_health'])); ?>" class="f-auth-choice">
                    <span class="f-auth-choice__icon"><?php echo $__env->make('partials.finder.icon', ['name' => 'globe', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                    <span class="f-auth-choice__body">
                        <span class="f-auth-choice__title"><?php echo e(__('onboarding.selector.cards.public_health_title')); ?></span>
                        <span class="f-auth-choice__desc"><?php echo e(__('onboarding.selector.cards.public_health_desc')); ?></span>
                    </span>
                    <span class="f-auth-choice__go"><?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-right', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                </a>

            </div>

            <div class="f-auth-alt f-auth-alt--divide">
                <p><?php echo e(__('onboarding.selector.already_have')); ?>

                    <a href="<?php echo e(route('login')); ?>"><?php echo e(__('onboarding.selector.signin')); ?></a>
                </p>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\auth\register.blade.php ENDPATH**/ ?>