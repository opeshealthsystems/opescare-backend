<?php $__env->startSection('title', __('onboarding.facility_selector.title')); ?>

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
.btn-primary-full{width:100%;padding:.75rem;background:#0F4C81;color:#fff;border:none;border-radius:8px;font-size:.9375rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;transition:background .15s;margin-top:.5rem}
.btn-primary-full:hover{background:#0a3560}
.btn-primary-full:disabled{opacity:.5;cursor:not-allowed}
.alert-error{background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:.875rem 1rem;margin-bottom:1.25rem;color:#dc2626;font-size:.875rem;display:flex;gap:.5rem}
.facility-card{display:flex;align-items:center;gap:1rem;padding:1rem 1.125rem;border:1.5px solid #e2e8f0;border-radius:10px;margin-bottom:.75rem;cursor:pointer;transition:border-color .15s,background .15s;text-decoration:none;color:inherit}
.facility-card:hover{border-color:#0F4C81;background:rgba(15,76,129,.04)}
.facility-card.active{border-color:#0F4C81;background:rgba(15,76,129,.04)}
.facility-card.disabled{opacity:.55;cursor:not-allowed;pointer-events:none}
.facility-card__icon{width:40px;height:40px;border-radius:8px;background:rgba(15,76,129,.08);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.facility-card__icon i{width:18px;height:18px;color:#0F4C81}
.facility-card__name{font-weight:600;font-size:.9375rem;color:#0f172a}
.facility-card__meta{font-size:.8rem;color:#64748b}
.facility-card__badge{margin-left:auto;font-size:.75rem;font-weight:700;padding:.25rem .6rem;border-radius:999px;flex-shrink:0}
.facility-card__badge--active{background:#dcfce7;color:#15803d}
.facility-card__badge--suspended{background:#fee2e2;color:#dc2626}
</style>

<div class="auth-card">
    <div class="auth-card__icon">
        <i data-lucide="building-2"></i>
    </div>
    <h1 class="auth-card__title">Select your facility</h1>
    <p class="auth-card__sub">Choose the facility you want to work in</p>

    <?php if(session('error')): ?>
        <div class="alert-error">
            <i data-lucide="triangle-alert" style="width:16px;height:16px;flex-shrink:0;margin-top:2px"></i>
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
                    <i data-lucide="building-2"></i>
                </div>
                <div>
                    <div class="facility-card__name"><?php echo e($facility['name']); ?></div>
                    <div class="facility-card__meta">
                        <?php echo e($facility['branch'] ?? ''); ?>

                        <?php if(!empty($facility['branch'])): ?> &middot; <?php endif; ?>
                        <?php echo e(__('onboarding.facility_selector.role_label')); ?>: <?php echo e($facility['role']); ?>

                    </div>
                </div>
                <?php if($isSuspended): ?>
                    <span class="facility-card__badge facility-card__badge--suspended"><?php echo e(__('onboarding.facility_selector.status_suspended')); ?></span>
                <?php else: ?>
                    <span class="facility-card__badge facility-card__badge--active"><?php echo e(__('onboarding.facility_selector.status_active')); ?></span>
                <?php endif; ?>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        <button type="submit" class="btn-primary-full" id="submit-btn" disabled>
            <i data-lucide="shield-check"></i>
            <span><?php echo e(__('onboarding.facility_selector.cta_btn')); ?></span>
        </button>
    </form>
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

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/auth/select_facility.blade.php ENDPATH**/ ?>