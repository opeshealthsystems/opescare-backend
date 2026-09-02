<?php $__env->startSection('title', __('caremap_claim.page_title') . ' — OpesCare'); ?>
<?php $__env->startSection('meta_description', __('caremap_claim.claim_intro')); ?>

<?php $__env->startSection('head_scripts'); ?>
<style>
  .clm-wrap{max-width:720px;margin:0 auto;padding:2.5rem 1.25rem 4rem}
  .clm-back{display:inline-flex;align-items:center;gap:.4rem;font-size:.85rem;color:#475569;text-decoration:none;margin-bottom:1.25rem}
  .clm-back:hover{color:#0F4C81}
  .clm-back i{width:14px;height:14px}
  .clm-head h1{font-family:'Inter',sans-serif;font-size:1.65rem;font-weight:800;color:#0F172A;margin-bottom:.35rem}
  .clm-head p{font-size:.9rem;color:#475569;line-height:1.6}
  .clm-card{background:#fff;border:1.5px solid #E2E8F0;border-radius:1rem;padding:1.5rem;margin-top:1.5rem}
  .clm-facility{display:flex;align-items:flex-start;gap:.9rem;padding-bottom:1.1rem;margin-bottom:1.25rem;border-bottom:1px solid #E2E8F0}
  .clm-facility-icon{width:44px;height:44px;border-radius:.7rem;background:#EFF6FF;color:#0F4C81;display:flex;align-items:center;justify-content:center;flex-shrink:0}
  .clm-facility-icon i{width:20px;height:20px}
  .clm-facility-name{font-size:1rem;font-weight:700;color:#0F172A}
  .clm-facility-meta{font-size:.82rem;color:#64748B;margin-top:.15rem}
  .clm-notice{display:flex;gap:.6rem;padding:.85rem 1rem;border-radius:.6rem;background:#FFFBEB;border:1px solid #FDE68A;color:#92400E;font-size:.83rem;line-height:1.55;margin-bottom:1.25rem}
  .clm-notice i{width:16px;height:16px;flex-shrink:0;margin-top:.1rem}
  .clm-alert{display:flex;gap:.6rem;padding:.85rem 1rem;border-radius:.6rem;font-size:.85rem;margin-bottom:1.25rem;line-height:1.55}
  .clm-alert--error{background:#FEF2F2;border:1px solid #FECACA;color:#B91C1C}
  .clm-alert--info{background:#EFF6FF;border:1px solid #BFDBFE;color:#1E40AF}
  .clm-field{margin-bottom:1.05rem}
  .clm-label{display:block;font-size:.82rem;font-weight:600;color:#334155;margin-bottom:.4rem}
  .clm-optional{font-weight:400;color:#94A3B8}
  .clm-input,.clm-select,.clm-textarea{width:100%;padding:.6rem .75rem;border:1.5px solid #E2E8F0;border-radius:.5rem;font-size:.875rem;font-family:inherit;color:#0F172A;background:#fff}
  .clm-input:focus,.clm-select:focus,.clm-textarea:focus{outline:none;border-color:#0F4C81;box-shadow:0 0 0 3px rgba(15,76,129,.1)}
  .clm-textarea{min-height:6rem;resize:vertical}
  .clm-hint{font-size:.78rem;color:#94A3B8;margin-top:.3rem;line-height:1.5}
  .clm-error{font-size:.78rem;color:#DC2626;margin-top:.3rem}
  .clm-actions{display:flex;gap:.6rem;align-items:center;margin-top:1.5rem;flex-wrap:wrap}
  .clm-btn{display:inline-flex;align-items:center;gap:.4rem;padding:.65rem 1.15rem;border-radius:.5rem;font-size:.875rem;font-weight:600;font-family:inherit;border:1.5px solid transparent;cursor:pointer;text-decoration:none}
  .clm-btn--primary{background:#0F4C81;color:#fff}
  .clm-btn--primary:hover{background:#0a3560}
  .clm-btn--ghost{background:transparent;color:#475569;border-color:#E2E8F0}
  .clm-btn--ghost:hover{border-color:#CBD5E1;color:#0F172A}
  .clm-btn i{width:15px;height:15px}
  .clm-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
  @media(max-width:620px){.clm-row{grid-template-columns:1fr}}
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="clm-wrap">

  <a href="<?php echo e(route('public.care-map.profile', $facility->id)); ?>" class="clm-back">
    <i data-lucide="arrow-left"></i> <?php echo e(__('caremap_claim.back_to_listing')); ?>

  </a>

  <div class="clm-head">
    <h1><?php echo e(__('caremap_claim.claim_heading')); ?></h1>
    <p><?php echo e(__('caremap_claim.claim_subheading', ['facility' => $facility->facility_name])); ?></p>
  </div>

  <div class="clm-card">

    <div class="clm-facility">
      <div class="clm-facility-icon"><i data-lucide="hospital"></i></div>
      <div>
        <div class="clm-facility-name"><?php echo e($facility->facility_name); ?></div>
        <div class="clm-facility-meta">
          <?php echo e($facility->city); ?><?php if($facility->region): ?>, <?php echo e($facility->region); ?><?php endif; ?>
          <?php if($facility->dialablePhone()): ?> &bull; <?php echo e($facility->dialablePhone()); ?> <?php endif; ?>
        </div>
      </div>
    </div>

    <?php if(session('error')): ?>
      <div class="clm-alert clm-alert--error">
        <i data-lucide="alert-circle"></i><div><?php echo e(session('error')); ?></div>
      </div>
    <?php endif; ?>

    <?php if($existing && $existing->claim_status->isOpen()): ?>
      <div class="clm-alert clm-alert--info">
        <i data-lucide="clock"></i><div><?php echo e(__('caremap_claim.already_pending')); ?></div>
      </div>
      <div class="clm-actions">
        <a href="<?php echo e(route('portals.listing.claims')); ?>" class="clm-btn clm-btn--primary">
          <i data-lucide="list-checks"></i> <?php echo e(__('caremap_claim.my_claims_title')); ?>

        </a>
      </div>
    <?php elseif($existing && $existing->claim_status->grantsEditAccess()): ?>
      <div class="clm-alert clm-alert--info">
        <i data-lucide="check-circle"></i><div><?php echo e(__('caremap_claim.already_approved')); ?></div>
      </div>
      <div class="clm-actions">
        <a href="<?php echo e(route('portals.listing.edit')); ?>" class="clm-btn clm-btn--primary">
          <i data-lucide="pencil"></i> <?php echo e(__('caremap_claim.btn_manage_listing')); ?>

        </a>
      </div>
    <?php else: ?>

      <p class="clm-hint" style="margin-bottom:1.1rem"><?php echo e(__('caremap_claim.claim_intro')); ?></p>

      <div class="clm-notice">
        <i data-lucide="shield-alert"></i>
        <div><?php echo e(__('caremap_claim.claim_review_notice')); ?></div>
      </div>

      <form method="POST" action="<?php echo e(route('public.care-map.claim.store', $facility->id)); ?>">
        <?php echo csrf_field(); ?>

        <div class="clm-row">
          <div class="clm-field">
            <label class="clm-label" for="claimant_name"><?php echo e(__('caremap_claim.field_name')); ?></label>
            <input type="text" id="claimant_name" name="claimant_name" class="clm-input" maxlength="150"
                   value="<?php echo e(old('claimant_name', auth()->user()->name ?? '')); ?>" required>
            <?php $__errorArgs = ['claimant_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="clm-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>

          <div class="clm-field">
            <label class="clm-label" for="claimant_role"><?php echo e(__('caremap_claim.field_role')); ?></label>
            <select id="claimant_role" name="claimant_role" class="clm-select" required>
              <option value=""><?php echo e(__('caremap_claim.role_select')); ?></option>
              <option value="owner" <?php if(old('claimant_role') === 'owner'): echo 'selected'; endif; ?>><?php echo e(__('caremap_claim.role_owner')); ?></option>
              <option value="manager" <?php if(old('claimant_role') === 'manager'): echo 'selected'; endif; ?>><?php echo e(__('caremap_claim.role_manager')); ?></option>
              <option value="authorized_rep" <?php if(old('claimant_role') === 'authorized_rep'): echo 'selected'; endif; ?>><?php echo e(__('caremap_claim.role_authorized_rep')); ?></option>
              <option value="admin_staff" <?php if(old('claimant_role') === 'admin_staff'): echo 'selected'; endif; ?>><?php echo e(__('caremap_claim.role_admin_staff')); ?></option>
            </select>
            <?php $__errorArgs = ['claimant_role'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="clm-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>
        </div>

        <div class="clm-row">
          <div class="clm-field">
            <label class="clm-label" for="claimant_email"><?php echo e(__('caremap_claim.field_email')); ?></label>
            <input type="email" id="claimant_email" name="claimant_email" class="clm-input" maxlength="150"
                   value="<?php echo e(old('claimant_email', auth()->user()->email ?? '')); ?>" required>
            <?php $__errorArgs = ['claimant_email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="clm-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>

          <div class="clm-field">
            <label class="clm-label" for="claimant_phone"><?php echo e(__('caremap_claim.field_phone')); ?></label>
            <input type="tel" id="claimant_phone" name="claimant_phone" class="clm-input" maxlength="40"
                   value="<?php echo e(old('claimant_phone')); ?>" required>
            <?php $__errorArgs = ['claimant_phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="clm-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>
        </div>

        <div class="clm-field">
          <label class="clm-label" for="claim_reason">
            <?php echo e(__('caremap_claim.field_reason')); ?>

            <span class="clm-optional">(<?php echo e(__('caremap_claim.field_optional')); ?>)</span>
          </label>
          <textarea id="claim_reason" name="claim_reason" class="clm-textarea" maxlength="2000"><?php echo e(old('claim_reason')); ?></textarea>
          <div class="clm-hint"><?php echo e(__('caremap_claim.field_reason_hint')); ?></div>
          <?php $__errorArgs = ['claim_reason'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="clm-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="clm-hint"><?php echo e(__('caremap_claim.hint_false_claim')); ?></div>

        <div class="clm-actions">
          <button type="submit" class="clm-btn clm-btn--primary">
            <i data-lucide="shield-check"></i> <?php echo e(__('caremap_claim.btn_submit_claim')); ?>

          </button>
          <a href="<?php echo e(route('public.care-map.profile', $facility->id)); ?>" class="clm-btn clm-btn--ghost">
            <?php echo e(__('caremap_claim.btn_cancel')); ?>

          </a>
        </div>
      </form>

    <?php endif; ?>

  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\care_map\claim.blade.php ENDPATH**/ ?>