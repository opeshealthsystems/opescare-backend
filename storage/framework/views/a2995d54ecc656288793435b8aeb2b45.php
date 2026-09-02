<?php $__env->startSection('title', $facility->name); ?>
<?php echo $__env->make('portals.admin.control_center._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->startSection('breadcrumb_home', 'Admin'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.admin')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.adm_fac_breadcrumb')); ?>
<?php $__env->startSection('content'); ?>

<div class="breadcrumb">
    <a href="<?php echo e(route('admin.facilities.index')); ?>"><?php echo e(__('public.adm_fac_breadcrumb')); ?></a>
    <i data-lucide="chevron-right"></i>
    <span><?php echo e($facility->name); ?></span>
</div>

<div class="entity-head">
    <div class="entity-head__icon"><i data-lucide="building-2"></i></div>
    <h2 class="entity-head__title"><?php echo e($facility->name); ?></h2>
    <?php if(($facility->status ?? '') === 'active'): ?><span class="badge badge-success"><?php echo e(__('public.adm_fac_badge_active')); ?></span>
    <?php elseif(($facility->status ?? '') === 'suspended'): ?><span class="badge badge-danger"><?php echo e(__('public.adm_fac_badge_suspended')); ?></span>
    <?php elseif(($facility->status ?? '') === 'pending_approval'): ?><span class="badge badge-warning"><?php echo e(__('public.adm_fac_badge_pending')); ?></span>
    <?php else: ?><span class="badge badge-neutral"><?php echo e(\App\Support\Enums::label($facility->status ?? 'pending')); ?></span><?php endif; ?>
    <div class="entity-head__spacer"></div>
    <a href="#edit" class="btn btn-secondary"><i data-lucide="pencil"></i> <?php echo e(__('public.adm_fac_show_btn_edit')); ?></a>
    <?php if(($facility->status ?? '') === 'pending_approval' || ($facility->status ?? '') === 'suspended'): ?>
    <form method="POST" action="<?php echo e(route('admin.facilities.approve', $facility->id)); ?>" class="inline-form"><?php echo csrf_field(); ?>
        <button type="submit" class="btn btn-success"><i data-lucide="check-circle"></i> <?php echo e(__('public.adm_fac_show_btn_approve')); ?></button>
    </form>
    <?php endif; ?>
</div>

<?php if(session('success')): ?><div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div><?php endif; ?>
<?php if(session('error')): ?><div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div><?php echo e(session('error')); ?></div></div><?php endif; ?>

<div class="tabs">
    <span class="tab active"><?php echo e(__('public.adm_fac_show_tab_overview')); ?></span>
</div>

<div class="field-grid mb-6">
    <div class="stat-card">
        <div class="stat-card__label"><?php echo e(__('public.adm_fac_show_lbl_type')); ?></div>
        <div class="stat-card__value"><?php echo e(ucfirst($facility->type ?? '—')); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label"><?php echo e(__('public.adm_fac_show_lbl_status')); ?></div>
        <div class="stat-card__value"><?php echo e(\App\Support\Enums::label($facility->status ?? 'pending')); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label"><?php echo e(__('public.adm_fac_show_lbl_license')); ?></div>
        <div class="stat-card__value"><?php echo e($facility->license_number ?? '—'); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label"><?php echo e(__('public.adm_fac_show_lbl_region')); ?></div>
        <div class="stat-card__value"><?php echo e($facility->region ?? '—'); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label"><?php echo e(__('public.adm_fac_show_lbl_staff')); ?></div>
        <div class="stat-card__value"><?php echo e(number_format($staffCount ?? 0)); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label"><?php echo e(__('public.adm_fac_show_lbl_patients')); ?></div>
        <div class="stat-card__value"><?php echo e(number_format($patientCount ?? 0)); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label"><?php echo e(__('public.adm_fac_show_lbl_created')); ?></div>
        <div class="stat-card__value"><?php echo e($facility->created_at?->format('d M Y') ?? '—'); ?></div>
    </div>
</div>

<div class="panel mb-6" id="edit">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="pencil"></i> <?php echo e(__('public.adm_fac_show_edit_title')); ?></h3></div>
    <div class="panel-body">
        <form method="POST" action="<?php echo e(route('admin.facilities.update', $facility->id)); ?>">
            <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label form-label-required"><?php echo e(__('public.adm_fac_show_lbl_name')); ?></label>
                    <input type="text" name="name" class="form-control" value="<?php echo e(old('name', $facility->name)); ?>" required>
                    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-hint"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required"><?php echo e(__('public.adm_fac_show_lbl_type')); ?></label>
                    <select name="type" class="form-control" required>
                        <?php $__currentLoopData = ['hospital','clinic','pharmacy','laboratory','radiology','specialist','other']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($t); ?>" <?php if(old('type',$facility->type)===$t): echo 'selected'; endif; ?>><?php echo e(ucfirst($t)); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo e(__('public.adm_fac_show_lbl_region')); ?></label>
                    <input type="text" name="region" class="form-control" value="<?php echo e(old('region', $facility->region)); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo e(__('public.adm_fac_show_lbl_country')); ?></label>
                    <input type="text" name="country_code" class="form-control" value="<?php echo e(old('country_code', $facility->country_code)); ?>" maxlength="3">
                </div>
            </div>
            <div class="mt-6">
                <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> <?php echo e(__('public.adm_fac_show_btn_save')); ?></button>
            </div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="alert-triangle"></i> <?php echo e(__('public.adm_fac_show_danger_title')); ?></h3></div>
    <div class="panel-body">
        <div class="page-head">
            <?php if(($facility->status ?? '') !== 'suspended'): ?>
            <button type="button" class="btn btn-warning" onclick="opOpenModal('suspend-modal')"><i data-lucide="pause-circle"></i> <?php echo e(__('public.adm_fac_show_btn_suspend')); ?></button>
            <?php endif; ?>
            <button type="button" class="btn btn-danger" onclick="opOpenModal('delete-modal')"><i data-lucide="trash-2"></i> <?php echo e(__('public.adm_fac_show_btn_delete')); ?></button>
        </div>
    </div>
</div>

<div id="suspend-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="suspend-modal-title">
        <h3 class="modal__title" id="suspend-modal-title"><i data-lucide="pause-circle"></i> <?php echo e(__('public.adm_fac_show_modal_suspend_title')); ?></h3>
        <form method="POST" action="<?php echo e(route('admin.facilities.suspend', $facility->id)); ?>">
            <?php echo csrf_field(); ?>
            <div class="modal__body">
                <p><?php echo e(__('public.adm_fac_show_modal_suspend_body', ['name' => $facility->name])); ?></p>
                <textarea name="reason" class="form-control" rows="3" placeholder="<?php echo e(__('public.adm_fac_show_modal_suspend_ph')); ?>" required></textarea>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('suspend-modal')"><?php echo e(__('public.adm_fac_show_btn_cancel')); ?></button>
                <button type="submit" class="btn btn-warning"><?php echo e(__('public.adm_fac_show_btn_suspend_confirm')); ?></button>
            </div>
        </form>
    </div>
</div>

<div id="delete-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title">
        <h3 class="modal__title" id="delete-modal-title"><i data-lucide="alert-triangle"></i> <?php echo e(__('public.adm_fac_show_modal_delete_title')); ?></h3>
        <form method="POST" action="<?php echo e(route('admin.facilities.destroy', $facility->id)); ?>">
            <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
            <div class="modal__body">
                <p><?php echo e(__('public.adm_fac_show_modal_delete_body', ['name' => $facility->name])); ?></p>
                <textarea name="reason" class="form-control" rows="3" placeholder="<?php echo e(__('public.adm_fac_show_modal_delete_ph')); ?>" required></textarea>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('delete-modal')"><?php echo e(__('public.adm_fac_show_btn_cancel')); ?></button>
                <button type="submit" class="btn btn-danger"><?php echo e(__('public.adm_fac_show_btn_delete_confirm')); ?></button>
            </div>
        </form>
    </div>
</div>

<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
<script>
function opOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function opCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\admin\facilities\show.blade.php ENDPATH**/ ?>