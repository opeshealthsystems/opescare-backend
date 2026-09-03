<?php $__env->startSection('title', __('onboarding.facility_selector.title')); ?>

<?php $__env->startSection('content'); ?>
    <div class="f-auth-col">
        <div class="f-auth-card">
            <div class="f-auth-badge">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'building-2', 'size' => 22], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
            <h1 class="f-auth-title"><?php echo e(__('onboarding.facility_selector.title')); ?></h1>
            <p class="f-auth-sub"><?php echo e(__('onboarding.facility_selector.subtitle')); ?></p>

            <?php if(session('error')): ?>
                <div class="f-auth-alert f-auth-alert--danger" role="alert">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <div><?php echo e(session('error')); ?></div>
                </div>
            <?php endif; ?>

            
            <form action="<?php echo e(route('select-facility.submit')); ?>" method="POST" id="facility-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="facility" id="selected-facility" value="">

                <?php $__currentLoopData = $facilities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $facility): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $cardId      = 'card-fac-' . $index;
                        $isSuspended = ($facility['status'] ?? '') === 'suspended';
                        $facilityVal = $isSuspended ? 'suspended' : $facility['id'];
                    ?>
                    <div class="facility-card<?php echo e($isSuspended ? ' disabled' : ''); ?>"
                         id="<?php echo e($cardId); ?>"
                         onclick="<?php echo e($isSuspended ? '' : "selectFacility('{$facilityVal}', '{$cardId}')"); ?>">
                        <div class="facility-card__icon">
                            <?php echo $__env->make('partials.finder.icon', ['name' => 'building-2', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                        <div class="facility-card__body">
                            <div class="facility-card__name"><?php echo e($facility['name']); ?></div>
                            <div class="facility-card__meta">
                                <?php echo e($facility['branch'] ?? ''); ?>

                                <?php if(!empty($facility['branch'])): ?> &middot; <?php endif; ?>
                                <?php echo e(__('onboarding.facility_selector.role_label')); ?>: <?php echo e($facility['role']); ?>

                            </div>
                        </div>
                        <?php if($isSuspended): ?>
                            <span class="f-badge f-badge--danger"><?php echo e(__('onboarding.facility_selector.status_suspended')); ?></span>
                        <?php else: ?>
                            <span class="f-badge f-badge--good"><?php echo e(__('onboarding.facility_selector.status_active')); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                <button type="submit" class="f-btn f-btn--primary f-btn--block" id="submit-btn" disabled>
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'log-in', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo e(__('onboarding.facility_selector.cta_btn')); ?>

                </button>
            </form>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
    function selectFacility(facilityVal, cardId) {
        document.getElementById('selected-facility').value = facilityVal;

        document.querySelectorAll('.facility-card').forEach(function(card) {
            card.classList.remove('active');
        });

        document.getElementById(cardId).classList.add('active');

        var submitBtn = document.getElementById('submit-btn');
        submitBtn.removeAttribute('disabled');
    }
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\auth\select_facility.blade.php ENDPATH**/ ?>