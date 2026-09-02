<?php $__env->startSection('title', __('caremap_claim.edit_title')); ?>
<?php $__env->startSection('breadcrumb_section', __('caremap_claim.edit_title')); ?>

<?php
    use App\Http\Controllers\CareMap\FacilityListingController as Listing;

    $dayLabels  = Listing::dayLabels();
    $categories = Listing::serviceCategories();
    $statuses   = Listing::availabilityStatuses();

    // 'N/A' is a placeholder the registry left behind, not a number. It must
    // never be pre-filled into a claimant's form as though it were theirs.
    $phonePrimary = \App\Models\CareFacility::realValue($listing->phone_primary);
?>

<?php $__env->startSection('head'); ?>
<style>
  .lst-badges{display:flex;gap:.45rem;flex-wrap:wrap;margin-bottom:var(--p-space-3)}
  .lst-badge{display:inline-flex;align-items:center;gap:.3rem;padding:.25rem .65rem;border-radius:999px;font-size:.72rem;font-weight:700}
  .lst-badge i{width:12px;height:12px}
  .lst-badge--claimed{background:rgba(15,76,129,.1);color:#0F4C81;border:1px solid rgba(15,76,129,.25)}
  .lst-badge--unverified{background:#F1F5F9;color:#64748B;border:1px solid #E2E8F0}
  .lst-note{font-size:.8rem;color:var(--p-text-muted);line-height:1.55;margin-bottom:var(--p-space-4)}
  .lst-hours-table td{padding:.4rem .5rem;vertical-align:middle}
  .lst-hours-table .form-control{padding:.35rem .5rem;font-size:.8rem}
  .lst-check{display:inline-flex;align-items:center;gap:.35rem;font-size:.8rem;color:var(--p-text-2);white-space:nowrap}
  .lst-svc-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:var(--p-space-4)}
  .lst-svc-flags{display:flex;gap:1rem;flex-wrap:wrap;margin-top:var(--p-space-3)}
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="page-head">
    <h2><?php echo e(__('caremap_claim.edit_title')); ?></h2>
    <div class="page-head__spacer"></div>
    <a href="<?php echo e(route('public.care-map.profile', $listing->id)); ?>" class="btn btn-secondary btn-sm" target="_blank" rel="noopener">
        <i data-lucide="external-link"></i> <?php echo e(__('caremap_claim.view_listing')); ?>

    </a>
</div>

<div class="lst-badges">
    <span class="lst-badge lst-badge--claimed"><i data-lucide="shield-check"></i> <?php echo e(__('caremap_claim.badge_claimed')); ?></span>
    <span class="lst-badge lst-badge--unverified"><i data-lucide="circle-dashed"></i> <?php echo e(__('caremap_claim.badge_not_verified')); ?></span>
</div>
<p class="lst-note"><?php echo e(__('caremap_claim.edit_subtitle', ['facility' => $listing->facility_name])); ?></p>
<p class="lst-note"><?php echo e(__('caremap_claim.not_verified_note')); ?></p>

<?php if(session('success')): ?><div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div><?php endif; ?>
<?php if(session('error')): ?><div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div><?php echo e(session('error')); ?></div></div><?php endif; ?>


<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="phone"></i> <?php echo e(__('caremap_claim.section_contact')); ?></h3>
    </div>
    <div class="panel-body">
        <p class="lst-note"><?php echo e(__('caremap_claim.audit_note')); ?></p>

        <form method="POST" action="<?php echo e(route('portals.listing.update')); ?>">
            <?php echo csrf_field(); ?>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label form-label-required" for="phone_primary"><?php echo e(__('caremap_claim.label_phone_primary')); ?></label>
                    <input type="tel" id="phone_primary" name="phone_primary" class="form-control" maxlength="40"
                           value="<?php echo e(old('phone_primary', $phonePrimary)); ?>">
                    <?php if($phonePrimary === null): ?>
                        <div class="form-hint"><?php echo e(__('caremap_claim.hint_phone_placeholder')); ?></div>
                    <?php endif; ?>
                    <?php $__errorArgs = ['phone_primary'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-hint" style="color:var(--p-danger)"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone_secondary"><?php echo e(__('caremap_claim.label_phone_secondary')); ?></label>
                    <input type="tel" id="phone_secondary" name="phone_secondary" class="form-control" maxlength="40"
                           value="<?php echo e(old('phone_secondary', \App\Models\CareFacility::realValue($listing->phone_secondary))); ?>">
                    <?php $__errorArgs = ['phone_secondary'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-hint" style="color:var(--p-danger)"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            <div class="form-row" style="margin-top:var(--p-space-4)">
                <div class="form-group">
                    <label class="form-label" for="email"><?php echo e(__('caremap_claim.label_email')); ?></label>
                    <input type="email" id="email" name="email" class="form-control" maxlength="150"
                           value="<?php echo e(old('email', $listing->email)); ?>">
                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-hint" style="color:var(--p-danger)"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="website"><?php echo e(__('caremap_claim.label_website')); ?></label>
                    <input type="url" id="website" name="website" class="form-control" maxlength="255"
                           value="<?php echo e(old('website', $listing->website)); ?>" placeholder="https://">
                    <?php $__errorArgs = ['website'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-hint" style="color:var(--p-danger)"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            <div class="form-group" style="margin-top:var(--p-space-4)">
                <label class="form-label" for="description"><?php echo e(__('caremap_claim.label_description')); ?></label>
                <textarea id="description" name="description" class="form-control" maxlength="2000"><?php echo e(old('description', $listing->description)); ?></textarea>
                <div class="form-hint"><?php echo e(__('caremap_claim.hint_description')); ?></div>
                <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-hint" style="color:var(--p-danger)"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div style="margin-top:var(--p-space-5)">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i> <?php echo e(__('caremap_claim.btn_save')); ?>

                </button>
            </div>
        </form>
    </div>
</div>


<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="clock"></i> <?php echo e(__('caremap_claim.section_hours')); ?></h3>
    </div>
    <div class="panel-body">
        <p class="lst-note"><?php echo e(__('caremap_claim.hours_intro')); ?></p>

        <form method="POST" action="<?php echo e(route('portals.listing.hours.update')); ?>">
            <?php echo csrf_field(); ?>
            <div class="table-wrapper">
                <table class="data-table lst-hours-table">
                    <thead>
                        <tr>
                            <th><?php echo e(__('caremap_claim.hours_day')); ?></th>
                            <th><?php echo e(__('caremap_claim.hours_open')); ?></th>
                            <th><?php echo e(__('caremap_claim.hours_close')); ?></th>
                            <th><?php echo e(__('caremap_claim.hours_24')); ?></th>
                            <th><?php echo e(__('caremap_claim.hours_closed')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $__currentLoopData = $dayLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php $h = $hours->get($day); ?>
                        <tr>
                            <td data-label="<?php echo e(__('caremap_claim.hours_day')); ?>" class="td-strong"><?php echo e($label); ?></td>
                            <td data-label="<?php echo e(__('caremap_claim.hours_open')); ?>">
                                <input type="time" name="hours[<?php echo e($day); ?>][opens_at]" class="form-control"
                                       value="<?php echo e($h && $h->opens_at ? substr($h->opens_at, 0, 5) : ''); ?>">
                            </td>
                            <td data-label="<?php echo e(__('caremap_claim.hours_close')); ?>">
                                <input type="time" name="hours[<?php echo e($day); ?>][closes_at]" class="form-control"
                                       value="<?php echo e($h && $h->closes_at ? substr($h->closes_at, 0, 5) : ''); ?>">
                            </td>
                            <td data-label="<?php echo e(__('caremap_claim.hours_24')); ?>">
                                <label class="lst-check">
                                    <input type="checkbox" name="hours[<?php echo e($day); ?>][is_24_hours]" value="1" <?php if($h?->is_24_hours): echo 'checked'; endif; ?>>
                                </label>
                            </td>
                            <td data-label="<?php echo e(__('caremap_claim.hours_closed')); ?>">
                                <label class="lst-check">
                                    <input type="checkbox" name="hours[<?php echo e($day); ?>][is_closed]" value="1" <?php if($h?->is_closed): echo 'checked'; endif; ?>>
                                </label>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top:var(--p-space-5)">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i> <?php echo e(__('caremap_claim.btn_save_hours')); ?>

                </button>
            </div>
        </form>
    </div>
</div>


<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="stethoscope"></i> <?php echo e(__('caremap_claim.section_services')); ?></h3>
    </div>
    <div class="panel-body">
        <p class="lst-note"><?php echo e(__('caremap_claim.svc_intro')); ?></p>

        <?php if($services->isEmpty()): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="list-plus"></i></div>
                <p><?php echo e(__('caremap_claim.empty_services')); ?></p>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><?php echo e(__('caremap_claim.svc_name')); ?></th>
                            <th><?php echo e(__('caremap_claim.svc_category')); ?></th>
                            <th><?php echo e(__('caremap_claim.svc_specialty')); ?></th>
                            <th><?php echo e(__('caremap_claim.svc_availability')); ?></th>
                            <th class="row-actions">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $__currentLoopData = $services; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $svc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td data-label="<?php echo e(__('caremap_claim.svc_name')); ?>">
                                <span class="td-strong"><?php echo e($svc->service_name); ?></span>
                                <div class="td-muted">
                                    <?php if($svc->appointment_required): ?> <span><?php echo e(__('caremap_claim.svc_appointment')); ?></span> <?php endif; ?>
                                    <?php if($svc->walk_in_allowed): ?> <span>&bull; <?php echo e(__('caremap_claim.svc_walkin')); ?></span> <?php endif; ?>
                                    <?php if($svc->telemedicine_available): ?> <span>&bull; <?php echo e(__('caremap_claim.svc_telemedicine')); ?></span> <?php endif; ?>
                                </div>
                            </td>
                            <td data-label="<?php echo e(__('caremap_claim.svc_category')); ?>" class="td-muted">
                                <?php echo e($categories[$svc->service_category] ?? $svc->service_category); ?>

                            </td>
                            <td data-label="<?php echo e(__('caremap_claim.svc_specialty')); ?>" class="td-muted"><?php echo e($svc->specialty ?: '—'); ?></td>
                            <td data-label="<?php echo e(__('caremap_claim.svc_availability')); ?>" class="td-muted">
                                <?php echo e($statuses[$svc->availability_status] ?? $svc->availability_status); ?>

                            </td>
                            <td class="row-actions">
                                <form method="POST" action="<?php echo e(route('portals.listing.services.destroy', $svc->id)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn btn-ghost btn-sm">
                                        <i data-lucide="trash-2"></i> <?php echo e(__('caremap_claim.btn_remove')); ?>

                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo e(route('portals.listing.services.store')); ?>" style="margin-top:var(--p-space-5)">
            <?php echo csrf_field(); ?>
            <div class="lst-svc-grid">
                <div class="form-group">
                    <label class="form-label form-label-required" for="service_name"><?php echo e(__('caremap_claim.svc_name')); ?></label>
                    <input type="text" id="service_name" name="service_name" class="form-control" maxlength="150" required>
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required" for="service_category"><?php echo e(__('caremap_claim.svc_category')); ?></label>
                    <select id="service_category" name="service_category" class="form-control" required>
                        <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>"><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="specialty"><?php echo e(__('caremap_claim.svc_specialty')); ?></label>
                    <input type="text" id="specialty" name="specialty" class="form-control" maxlength="120">
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required" for="availability_status"><?php echo e(__('caremap_claim.svc_availability')); ?></label>
                    <select id="availability_status" name="availability_status" class="form-control" required>
                        <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>"><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
            </div>

            <div class="lst-svc-flags">
                <label class="lst-check"><input type="checkbox" name="appointment_required" value="1"> <?php echo e(__('caremap_claim.svc_appointment')); ?></label>
                <label class="lst-check"><input type="checkbox" name="walk_in_allowed" value="1" checked> <?php echo e(__('caremap_claim.svc_walkin')); ?></label>
                <label class="lst-check"><input type="checkbox" name="telemedicine_available" value="1"> <?php echo e(__('caremap_claim.svc_telemedicine')); ?></label>
            </div>

            <div style="margin-top:var(--p-space-5)">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="plus"></i> <?php echo e(__('caremap_claim.btn_add_service')); ?>

                </button>
            </div>
        </form>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\care_map\listing\edit.blade.php ENDPATH**/ ?>