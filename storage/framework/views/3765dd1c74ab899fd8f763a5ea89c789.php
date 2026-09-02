<?php $__env->startSection('title', __('onboarding.selector.title')); ?>

<?php $__env->startSection('content'); ?>
    <div class="auth-card auth-card--wide">
        <div class="auth-card__head auth-card__head--center">
            <h1 class="auth-card__title"><?php echo e(__('onboarding.selector.title')); ?></h1>
            <p class="auth-card__sub"><?php echo e(__('onboarding.selector.subtitle')); ?></p>
        </div>

        <div class="onboarding-grid">

            <!-- Patient -->
            <a href="<?php echo e(route('register.patient')); ?>" class="onboarding-card">
                <div>
                    <div class="onboarding-card-header">
                        <div class="onboarding-card-icon">
                            <i data-lucide="heart-pulse"></i>
                        </div>
                        <h3><?php echo e(__('onboarding.selector.cards.patient_title')); ?></h3>
                    </div>
                    <p><?php echo e(__('onboarding.selector.cards.patient_desc')); ?></p>
                </div>
                <div class="onboarding-card-cta">
                    <span><?php echo e(__('onboarding.selector.cards.patient_cta')); ?></span>
                    <i data-lucide="arrow-right"></i>
                </div>
            </a>

            <!-- Guardian / Caregiver -->
            <a href="<?php echo e(route('register.guardian')); ?>" class="onboarding-card">
                <div>
                    <div class="onboarding-card-header">
                        <div class="onboarding-card-icon">
                            <i data-lucide="users"></i>
                        </div>
                        <h3><?php echo e(__('onboarding.selector.cards.guardian_title')); ?></h3>
                    </div>
                    <p><?php echo e(__('onboarding.selector.cards.guardian_desc')); ?></p>
                </div>
                <div class="onboarding-card-cta">
                    <span><?php echo e(__('onboarding.selector.cards.guardian_cta')); ?></span>
                    <i data-lucide="arrow-right"></i>
                </div>
            </a>

            <!-- Hospital / Clinic -->
            <a href="<?php echo e(route('register.organization', ['type' => 'hospital'])); ?>" class="onboarding-card">
                <div>
                    <div class="onboarding-card-header">
                        <div class="onboarding-card-icon">
                            <i data-lucide="hospital"></i>
                        </div>
                        <h3><?php echo e(__('onboarding.selector.cards.hospital_title')); ?></h3>
                    </div>
                    <p><?php echo e(__('onboarding.selector.cards.hospital_desc')); ?></p>
                </div>
                <div class="onboarding-card-cta">
                    <span><?php echo e(__('onboarding.selector.cards.hospital_cta')); ?></span>
                    <i data-lucide="arrow-right"></i>
                </div>
            </a>

            <!-- Pharmacy -->
            <a href="<?php echo e(route('register.organization', ['type' => 'pharmacy'])); ?>" class="onboarding-card">
                <div>
                    <div class="onboarding-card-header">
                        <div class="onboarding-card-icon">
                            <i data-lucide="pill"></i>
                        </div>
                        <h3><?php echo e(__('onboarding.selector.cards.pharmacy_title')); ?></h3>
                    </div>
                    <p><?php echo e(__('onboarding.selector.cards.pharmacy_desc')); ?></p>
                </div>
                <div class="onboarding-card-cta">
                    <span><?php echo e(__('onboarding.selector.cards.pharmacy_cta')); ?></span>
                    <i data-lucide="arrow-right"></i>
                </div>
            </a>

            <!-- Laboratory -->
            <a href="<?php echo e(route('register.organization', ['type' => 'laboratory'])); ?>" class="onboarding-card">
                <div>
                    <div class="onboarding-card-header">
                        <div class="onboarding-card-icon">
                            <i data-lucide="microscope"></i>
                        </div>
                        <h3><?php echo e(__('onboarding.selector.cards.laboratory_title')); ?></h3>
                    </div>
                    <p><?php echo e(__('onboarding.selector.cards.laboratory_desc')); ?></p>
                </div>
                <div class="onboarding-card-cta">
                    <span><?php echo e(__('onboarding.selector.cards.laboratory_cta')); ?></span>
                    <i data-lucide="arrow-right"></i>
                </div>
            </a>

            <!-- Insurer -->
            <a href="<?php echo e(route('register.organization', ['type' => 'insurance_company'])); ?>" class="onboarding-card">
                <div>
                    <div class="onboarding-card-header">
                        <div class="onboarding-card-icon">
                            <i data-lucide="heart-handshake"></i>
                        </div>
                        <h3><?php echo e(__('onboarding.selector.cards.insurer_title')); ?></h3>
                    </div>
                    <p><?php echo e(__('onboarding.selector.cards.insurer_desc')); ?></p>
                </div>
                <div class="onboarding-card-cta">
                    <span><?php echo e(__('onboarding.selector.cards.insurer_cta')); ?></span>
                    <i data-lucide="arrow-right"></i>
                </div>
            </a>

            <!-- Developer API Access -->
            <a href="<?php echo e(route('register.developer')); ?>" class="onboarding-card">
                <div>
                    <div class="onboarding-card-header">
                        <div class="onboarding-card-icon">
                            <i data-lucide="code-2"></i>
                        </div>
                        <h3><?php echo e(__('onboarding.selector.cards.developer_title')); ?></h3>
                    </div>
                    <p><?php echo e(__('onboarding.selector.cards.developer_desc')); ?></p>
                </div>
                <div class="onboarding-card-cta">
                    <span><?php echo e(__('onboarding.selector.cards.developer_cta')); ?></span>
                    <i data-lucide="arrow-right"></i>
                </div>
            </a>

            <!-- Public Health / Research -->
            <a href="<?php echo e(route('register.organization', ['type' => 'public_health'])); ?>" class="onboarding-card">
                <div>
                    <div class="onboarding-card-header">
                        <div class="onboarding-card-icon">
                            <i data-lucide="globe"></i>
                        </div>
                        <h3><?php echo e(__('onboarding.selector.cards.public_health_title')); ?></h3>
                    </div>
                    <p><?php echo e(__('onboarding.selector.cards.public_health_desc')); ?></p>
                </div>
                <div class="onboarding-card-cta">
                    <span><?php echo e(__('onboarding.selector.cards.public_health_cta')); ?></span>
                    <i data-lucide="arrow-right"></i>
                </div>
            </a>

        </div>

        <div class="auth-footer-links auth-footer-links--bordered">
            <p><?php echo e(__('onboarding.selector.already_have')); ?>

                <a href="<?php echo e(route('login')); ?>" class="auth-footer-link">
                    <?php echo e(__('onboarding.selector.signin')); ?>

                </a>
            </p>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\auth\register.blade.php ENDPATH**/ ?>