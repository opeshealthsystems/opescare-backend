<?php $__env->startSection('title', __('caremap_claim.page_title')); ?>
<?php $__env->startSection('description', __('caremap_claim.claim_intro')); ?>

<?php $__env->startPush('head'); ?>
<style>
  /* Claim flow — official-but-simple, on foundation tokens only. */
  .clm-wrap{max-width:720px;margin:0 auto;padding:2.5rem 1rem 4rem}
  .clm-back{display:inline-flex;align-items:center;gap:.35rem;min-height:44px;font-size:.875rem;color:var(--f-muted);text-decoration:none;margin-bottom:.75rem}
  .clm-back:hover{color:var(--f-brand)}
  .clm-head h1{font-size:clamp(1.45rem,3vw + .9rem,1.75rem);font-weight:800;letter-spacing:-.015em;color:var(--f-ink);margin-bottom:.35rem}
  .clm-head p{font-size:.9375rem;color:var(--f-ink-soft);line-height:1.6;margin:0}
  .clm-steps{display:grid;grid-template-columns:1fr;gap:.5rem;margin-top:1.25rem}
  @media(min-width:640px){.clm-steps{grid-template-columns:repeat(3,1fr)}}
  .clm-step{display:flex;align-items:center;gap:.6rem;padding:.7rem .85rem;background:var(--f-surface);border:1px solid var(--f-line);border-radius:var(--f-r);font-size:.8125rem;font-weight:600;color:var(--f-ink-soft)}
  .clm-step__n{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:999px;background:var(--f-wash);color:var(--f-brand);font-size:.75rem;font-weight:750;flex-shrink:0}
  .clm-card{background:var(--f-surface);border:1px solid var(--f-line);border-radius:var(--f-r);padding:1.5rem;margin-top:1.25rem;box-shadow:0 1px 2px rgba(15,23,42,.04)}
  .clm-facility{display:flex;align-items:flex-start;gap:.9rem;padding-bottom:1.1rem;margin-bottom:1.25rem;border-bottom:1px solid var(--f-line)}
  .clm-facility-icon{width:44px;height:44px;border-radius:.7rem;background:var(--f-wash);color:var(--f-brand);display:flex;align-items:center;justify-content:center;flex-shrink:0}
  .clm-facility-name{font-size:1rem;font-weight:700;color:var(--f-ink)}
  .clm-facility-meta{font-size:.8125rem;color:var(--f-muted);margin-top:.15rem}
  .clm-notice{display:flex;gap:.6rem;padding:.85rem 1rem;border-radius:.6rem;background:var(--f-warn-wash);border:1px solid var(--f-warn);color:var(--f-warn);font-size:.8125rem;line-height:1.55;margin-bottom:1.25rem}
  .clm-notice .f-icon{flex-shrink:0;margin-top:.15rem}
  .clm-alert{display:flex;gap:.6rem;padding:.85rem 1rem;border-radius:.6rem;font-size:.875rem;margin-bottom:1.25rem;line-height:1.55}
  .clm-alert .f-icon{flex-shrink:0;margin-top:.15rem}
  .clm-alert--error{background:var(--f-danger-wash);border:1px solid var(--f-danger);color:var(--f-danger)}
  .clm-alert--info{background:var(--f-wash);border:1px solid var(--f-brand);color:var(--f-brand-ink)}
  .clm-actions{display:flex;gap:.6rem;align-items:center;margin-top:1.5rem;flex-wrap:wrap}
  .clm-row{display:grid;grid-template-columns:1fr;gap:0 1rem}
  @media(min-width:640px){.clm-row{grid-template-columns:1fr 1fr}}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="clm-wrap">

  <a href="<?php echo e(route('public.care-map.profile', $facility->id)); ?>" class="clm-back">
    <?php echo $__env->make('partials.finder.icon', ['name' => 'chevron-left', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php echo e(__('caremap_claim.back_to_listing')); ?>

  </a>

  <div class="clm-head">
    <h1><?php echo e(__('caremap_claim.claim_heading')); ?></h1>
    <p><?php echo e(__('caremap_claim.claim_subheading', ['facility' => $facility->facility_name])); ?></p>
  </div>

  
  <div class="clm-steps" aria-label="<?php echo e(__('finder_supply.claim_how_title')); ?>">
    <div class="clm-step"><span class="clm-step__n">1</span> <?php echo e(__('finder_supply.claim_step_submit')); ?></div>
    <div class="clm-step"><span class="clm-step__n">2</span> <?php echo e(__('finder_supply.claim_step_review')); ?></div>
    <div class="clm-step"><span class="clm-step__n">3</span> <?php echo e(__('finder_supply.claim_step_edit')); ?></div>
  </div>

  <div class="clm-card">

    <div class="clm-facility">
      <div class="clm-facility-icon"><?php echo $__env->make('partials.finder.icon', ['name' => 'building-2', 'size' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></div>
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
        <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><div><?php echo e(session('error')); ?></div>
      </div>
    <?php endif; ?>

    <?php if($existing && $existing->claim_status->isOpen()): ?>
      <div class="clm-alert clm-alert--info">
        <?php echo $__env->make('partials.finder.icon', ['name' => 'clock', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><div><?php echo e(__('caremap_claim.already_pending')); ?></div>
      </div>
      <div class="clm-actions">
        <a href="<?php echo e(route('portals.listing.claims')); ?>" class="f-btn f-btn--primary">
          <?php echo $__env->make('partials.finder.icon', ['name' => 'check', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php echo e(__('caremap_claim.my_claims_title')); ?>

        </a>
      </div>
    <?php elseif($existing && $existing->claim_status->grantsEditAccess()): ?>
      <div class="clm-alert clm-alert--info">
        <?php echo $__env->make('partials.finder.icon', ['name' => 'check', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><div><?php echo e(__('caremap_claim.already_approved')); ?></div>
      </div>
      <div class="clm-actions">
        <a href="<?php echo e(route('portals.listing.edit')); ?>" class="f-btn f-btn--primary">
          <?php echo $__env->make('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php echo e(__('caremap_claim.btn_manage_listing')); ?>

        </a>
      </div>
    <?php else: ?>

      <p class="f-hint" style="margin-bottom:1.1rem"><?php echo e(__('caremap_claim.claim_intro')); ?></p>

      <div class="clm-notice">
        <?php echo $__env->make('partials.finder.icon', ['name' => 'shield-check', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div><?php echo e(__('caremap_claim.claim_review_notice')); ?></div>
      </div>

      <form method="POST" action="<?php echo e(route('public.care-map.claim.store', $facility->id)); ?>">
        <?php echo csrf_field(); ?>

        <div class="clm-row">
          <div class="f-field">
            <label class="f-label" for="claimant_name"><?php echo e(__('caremap_claim.field_name')); ?></label>
            <input type="text" id="claimant_name" name="claimant_name" class="f-input" maxlength="150"
                   value="<?php echo e(old('claimant_name', auth()->user()->name ?? '')); ?>" required>
            <?php $__errorArgs = ['claimant_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="f-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>

          <div class="f-field">
            <label class="f-label" for="claimant_role"><?php echo e(__('caremap_claim.field_role')); ?></label>
            <select id="claimant_role" name="claimant_role" class="f-input" required>
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
$message = $__bag->first($__errorArgs[0]); ?><div class="f-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>
        </div>

        <div class="clm-row">
          <div class="f-field">
            <label class="f-label" for="claimant_email"><?php echo e(__('caremap_claim.field_email')); ?></label>
            <input type="email" id="claimant_email" name="claimant_email" class="f-input" maxlength="150"
                   value="<?php echo e(old('claimant_email', auth()->user()->email ?? '')); ?>" required>
            <?php $__errorArgs = ['claimant_email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="f-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>

          <div class="f-field">
            <label class="f-label" for="claimant_phone"><?php echo e(__('caremap_claim.field_phone')); ?></label>
            <input type="tel" id="claimant_phone" name="claimant_phone" class="f-input" maxlength="40" inputmode="tel"
                   value="<?php echo e(old('claimant_phone')); ?>" required>
            <?php $__errorArgs = ['claimant_phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="f-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>
        </div>

        <div class="f-field">
          <label class="f-label" for="claim_reason">
            <?php echo e(__('caremap_claim.field_reason')); ?>

            <span class="f-muted" style="font-weight:400">(<?php echo e(__('caremap_claim.field_optional')); ?>)</span>
          </label>
          <textarea id="claim_reason" name="claim_reason" class="f-input" maxlength="2000"><?php echo e(old('claim_reason')); ?></textarea>
          <div class="f-hint"><?php echo e(__('caremap_claim.field_reason_hint')); ?></div>
          <?php $__errorArgs = ['claim_reason'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="f-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="f-hint"><?php echo e(__('caremap_claim.hint_false_claim')); ?></div>

        <div class="clm-actions">
          <button type="submit" class="f-btn f-btn--primary">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'shield-check', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php echo e(__('caremap_claim.btn_submit_claim')); ?>

          </button>
          <a href="<?php echo e(route('public.care-map.profile', $facility->id)); ?>" class="f-btn">
            <?php echo e(__('caremap_claim.btn_cancel')); ?>

          </a>
        </div>
      </form>

    <?php endif; ?>

  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.finder', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/care_map/claim.blade.php ENDPATH**/ ?>