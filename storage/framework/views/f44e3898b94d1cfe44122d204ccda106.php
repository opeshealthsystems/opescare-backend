<?php $__env->startSection('title', 'User: ' . $user->name); ?>
<?php echo $__env->make('portals.admin.control_center._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->startSection('breadcrumb_home', 'Admin'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.admin')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.adm_users_idx_breadcrumb_section')); ?>
<?php $__env->startSection('content'); ?>

<div class="breadcrumb">
    <a href="<?php echo e(route('admin.users.index')); ?>"><?php echo e(__('public.adm_users_show_breadcrumb_users')); ?></a>
    <i data-lucide="chevron-right"></i>
    <span><?php echo e($user->name); ?></span>
</div>

<div class="entity-head">
    <div class="entity-head__icon"><i data-lucide="user"></i></div>
    <h2 class="entity-head__title"><?php echo e($user->name); ?></h2>
    <?php if(($user->status??'')==='active'): ?><span class="badge badge-success"><?php echo e(__('public.adm_users_show_badge_active')); ?></span>
    <?php elseif(($user->status??'')==='suspended'): ?><span class="badge badge-danger"><?php echo e(__('public.adm_users_show_badge_suspended')); ?></span>
    <?php else: ?><span class="badge badge-warning"><?php echo e(\App\Support\Enums::label($user->status ?? 'pending')); ?></span><?php endif; ?>
    <div class="entity-head__spacer"></div>
    <?php if(($user->status??'')==='suspended'): ?>
    <form method="POST" action="<?php echo e(route('admin.users.activate', $user)); ?>" class="inline-form"><?php echo csrf_field(); ?>
        <button class="btn btn-success"><i data-lucide="check-circle"></i> <?php echo e(__('public.adm_users_show_btn_activate')); ?></button>
    </form>
    <?php else: ?>
    <button type="button" class="btn btn-warning" onclick="opOpenModal('suspend-modal')"><i data-lucide="ban"></i> <?php echo e(__('public.adm_users_show_btn_suspend')); ?></button>
    <?php endif; ?>
    <button type="button" class="btn btn-danger" onclick="opOpenModal('delete-modal')"><i data-lucide="trash-2"></i> <?php echo e(__('public.adm_users_show_btn_delete')); ?></button>
</div>

<?php if(session('success')): ?><div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div><?php endif; ?>
<?php if(session('error')): ?><div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div><?php echo e(session('error')); ?></div></div><?php endif; ?>

<div class="tabs">
    <span class="tab active"><?php echo e(__('public.adm_users_show_tab_overview')); ?></span>
</div>

<div class="field-grid mb-6">
    <div class="stat-card">
        <div class="stat-card__label"><?php echo e(__('public.adm_users_show_stat_email')); ?></div>
        <div class="stat-card__value"><?php echo e($user->email); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label"><?php echo e(__('public.adm_users_show_stat_role')); ?></div>
        <div class="stat-card__value"><?php echo e($user->role?->name ?? 'no role'); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label"><?php echo e(__('admin_extra.users_stat_facility')); ?></div>
        <div class="stat-card__value"><?php echo e($user->primaryFacility?->name ?? __('admin_extra.users_facility_none')); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label"><?php echo e(__('public.adm_users_show_stat_status')); ?></div>
        <div class="stat-card__value"><?php echo e(\App\Support\Enums::label($user->status ?? 'pending')); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label"><?php echo e(__('public.adm_users_show_stat_joined')); ?></div>
        <div class="stat-card__value"><?php echo e($user->created_at?->format('M d, Y') ?? '—'); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label"><?php echo e(__('public.adm_users_show_stat_last_login')); ?></div>
        <div class="stat-card__value"><?php echo e(isset($user->last_login_at) ? $user->last_login_at->diffForHumans() : __('public.adm_users_show_stat_never')); ?></div>
    </div>
</div>

<div class="panel mb-6">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="edit-2"></i> <?php echo e(__('public.adm_users_show_panel_edit')); ?></h3></div>
    <div class="panel-body">
        <?php echo $__env->make('portals.admin.users._facility_finder', [
            'searchAction' => route('admin.users.show', $user->id),
            'carry'        => [],
        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        
        <form method="POST" action="<?php echo e(route('admin.users.update', $user->id)); ?>">
            <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label form-label-required"><?php echo e(__('public.adm_users_show_lbl_name')); ?></label>
                    <input type="text" name="name" class="form-control" value="<?php echo e(old('name', $user->name)); ?>" required>
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
                    <label class="form-label form-label-required"><?php echo e(__('public.adm_users_show_lbl_email')); ?></label>
                    <input type="email" name="email" class="form-control" value="<?php echo e(old('email', $user->email)); ?>" required>
                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-hint"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>
            <div class="form-group mt-6">
                <label class="form-label form-label-required"><?php echo e(__('public.adm_users_show_lbl_role')); ?></label>
                <select name="role_id" class="form-control" required>
                    <option value=""><?php echo e(__('public.adm_users_show_ph_role')); ?></option>
                    <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($role->id); ?>" <?php if(old('role_id',$user->role_id)==$role->id): echo 'selected'; endif; ?>><?php echo e($role->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['role_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-hint"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            
            <div class="form-group mt-6">
                <label class="form-label form-label-required" for="user_status"><?php echo e(__('admin_extra.users_lbl_status')); ?></label>
                <select name="status" id="user_status" class="form-control" required>
                    <?php $__currentLoopData = ['active', 'pending', 'suspended']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $statusOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($statusOption); ?>" <?php if(old('status', $user->status ?? 'pending') === $statusOption): echo 'selected'; endif; ?>><?php echo e(\App\Support\Enums::label($statusOption)); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-hint"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <?php echo $__env->make('portals.admin.users._facility_picker', ['selected' => $user->primary_facility_id], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <div class="mt-6">
                <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> <?php echo e(__('public.adm_users_show_btn_save')); ?></button>
            </div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="key"></i> <?php echo e(__('public.adm_users_show_panel_reset_pw')); ?></h3></div>
    <div class="panel-body">
        <form method="POST" action="<?php echo e(route('admin.users.reset_password', $user->id)); ?>">
            <?php echo csrf_field(); ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label form-label-required"><?php echo e(__('public.adm_users_show_lbl_new_pw')); ?></label>
                    <input type="password" name="password" class="form-control" required>
                    <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="form-hint"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required"><?php echo e(__('public.adm_users_show_lbl_confirm_pw')); ?></label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
            </div>
            <div class="mt-6">
                <button type="submit" class="btn btn-warning"><i data-lucide="key"></i> <?php echo e(__('public.adm_users_show_btn_reset_pw')); ?></button>
            </div>
        </form>
    </div>
</div>

<div id="suspend-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="suspend-modal-title">
        <h3 class="modal__title" id="suspend-modal-title"><i data-lucide="ban"></i> <?php echo e(__('public.adm_users_show_modal_suspend_title')); ?></h3>
        <form method="POST" action="<?php echo e(route('admin.users.suspend', $user->id)); ?>"><?php echo csrf_field(); ?>
            <div class="modal__body"><p><?php echo e(__('public.adm_users_show_modal_suspend_body', ['name' => $user->name])); ?></p></div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('suspend-modal')"><?php echo e(__('public.adm_users_show_modal_suspend_btn_cancel')); ?></button>
                <button type="submit" class="btn btn-warning"><i data-lucide="ban"></i> <?php echo e(__('public.adm_users_show_modal_suspend_btn_suspend')); ?></button>
            </div>
        </form>
    </div>
</div>

<div id="delete-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title">
        <h3 class="modal__title" id="delete-modal-title"><i data-lucide="alert-triangle"></i> <?php echo e(__('public.adm_users_show_modal_delete_title')); ?></h3>
        <form method="POST" action="<?php echo e(route('admin.users.destroy', $user->id)); ?>"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
            <div class="modal__body"><p><?php echo e(__('public.adm_users_show_modal_delete_body', ['name' => $user->name])); ?></p></div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('delete-modal')"><?php echo e(__('public.adm_users_show_modal_delete_btn_cancel')); ?></button>
                <button type="submit" class="btn btn-danger"><i data-lucide="trash-2"></i> <?php echo e(__('public.adm_users_show_modal_delete_btn_delete')); ?></button>
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
<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\admin\users\show.blade.php ENDPATH**/ ?>