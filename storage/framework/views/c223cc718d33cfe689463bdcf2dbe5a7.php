<?php $__env->startSection('title', __('public.adm_users_idx_title')); ?>
<?php echo $__env->make('portals.admin.control_center._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->startSection('breadcrumb_home', __('admin_extra.breadcrumb_admin', [], app()->getLocale()) ?: 'Admin'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.admin')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.adm_users_idx_breadcrumb_section')); ?>
<?php $__env->startSection('content'); ?>

<div class="breadcrumb">
    <a href="<?php echo e(route('admin.users.index')); ?>"><?php echo e(__('public.adm_users_idx_breadcrumb_users')); ?></a>
    <i data-lucide="chevron-right"></i>
    <span><?php echo e(__('public.adm_users_idx_breadcrumb_dir')); ?></span>
</div>

<div class="page-head">
    <h2><?php echo e(__('public.adm_users_idx_heading')); ?></h2>
    <div class="page-head__spacer"></div>
</div>

<?php if(session('success')): ?><div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div><?php endif; ?>
<?php if(session('error')): ?><div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div><?php echo e(session('error')); ?></div></div><?php endif; ?>

<form method="GET" action="<?php echo e(route('admin.users.index')); ?>" class="filter-bar">
    <label class="filter-search">
        <i data-lucide="search"></i>
        <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="<?php echo e(__('public.adm_users_idx_ph_search')); ?>" aria-label="<?php echo e(__('public.aria_search_users')); ?>">
    </label>
    <select name="role_id" class="filter-select" aria-label="<?php echo e(__('admin_extra.aria_role', [], app()->getLocale()) ?: 'Role'); ?>" onchange="this.form.submit()">
        <option value=""><?php echo e(__('public.adm_users_idx_filter_all_roles')); ?></option>
        <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($role->id); ?>" <?php echo e(request('role_id') == $role->id ? 'selected' : ''); ?>><?php echo e($role->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <select name="status" class="filter-select" aria-label="<?php echo e(__('public.aria_status')); ?>" onchange="this.form.submit()">
        <option value=""><?php echo e(__('public.adm_users_idx_filter_all')); ?></option>
        <option value="active" <?php echo e(request('status')==='active' ? 'selected' : ''); ?>><?php echo e(__('public.adm_users_idx_filter_active')); ?></option>
        <option value="pending" <?php echo e(request('status')==='pending' ? 'selected' : ''); ?>><?php echo e(__('public.adm_users_idx_filter_pending')); ?></option>
        <option value="suspended" <?php echo e(request('status')==='suspended' ? 'selected' : ''); ?>><?php echo e(__('public.adm_users_idx_filter_suspended')); ?></option>
    </select>
    <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="filter"></i> <?php echo e(__('public.adm_users_idx_btn_filter')); ?></button>
    <a href="<?php echo e(route('admin.users.index')); ?>" class="btn btn-ghost btn-sm"><?php echo e(__('public.adm_users_idx_btn_reset')); ?></a>
</form>

<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="user-plus"></i> <?php echo e(__('admin_extra.users_create_title')); ?></h3>
    </div>
    <div class="panel-body">
        <?php echo $__env->make('portals.admin.users._facility_finder', [
            'searchAction' => route('admin.users.index'),
            'carry'        => [
                'search'  => request('search'),
                'role_id' => request('role_id'),
                'status'  => request('status'),
            ],
        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <form method="POST" action="<?php echo e(route('admin.users.store')); ?>">
            <?php echo csrf_field(); ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label form-label-required" for="new_user_name"><?php echo e(__('admin_extra.users_create_name')); ?></label>
                    <input type="text" id="new_user_name" name="name" class="form-control" value="<?php echo e(old('name')); ?>" required>
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
                    <label class="form-label form-label-required" for="new_user_email"><?php echo e(__('admin_extra.users_create_email')); ?></label>
                    <input type="email" id="new_user_email" name="email" class="form-control" value="<?php echo e(old('email')); ?>" required>
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
            <div class="form-row mt-6">
                <div class="form-group">
                    <label class="form-label form-label-required" for="new_user_password"><?php echo e(__('admin_extra.users_create_pw')); ?></label>
                    <input type="password" id="new_user_password" name="password" class="form-control" required>
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
                    <label class="form-label form-label-required" for="new_user_role"><?php echo e(__('admin_extra.users_create_role')); ?></label>
                    <select id="new_user_role" name="role_id" class="form-control" required>
                        <option value=""><?php echo e(__('admin_extra.users_create_role_ph')); ?></option>
                        <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($role->id); ?>" <?php if(old('role_id') == $role->id): echo 'selected'; endif; ?>><?php echo e($role->name); ?></option>
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
            </div>

            <?php echo $__env->make('portals.admin.users._facility_picker', ['selected' => null], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <div class="mt-6">
                <button type="submit" class="btn btn-primary"><i data-lucide="user-plus"></i> <?php echo e(__('admin_extra.users_create_btn')); ?></button>
            </div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="users"></i> <?php echo e(__('admin_extra.count_users', ['n' => $users->total()], app()->getLocale()) ?: $users->total().' users'); ?></h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?php echo e(__('public.adm_users_idx_col_name')); ?></th>
                    <th><?php echo e(__('public.adm_users_idx_col_email')); ?></th>
                    <th><?php echo e(__('public.adm_users_idx_col_role')); ?></th>
                    <th><?php echo e(__('admin_extra.users_col_facility')); ?></th>
                    <th><?php echo e(__('public.adm_users_idx_col_status')); ?></th>
                    <th><?php echo e(__('public.adm_users_idx_col_created')); ?></th>
                    <th class="row-actions"><?php echo e(__('public.adm_users_idx_col_actions')); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td data-label="<?php echo e(__('public.adm_users_idx_col_name')); ?>"><span class="td-strong"><?php echo e($user->name); ?></span></td>
                    <td data-label="<?php echo e(__('public.adm_users_idx_col_email')); ?>"><?php echo e($user->email); ?></td>
                    <td data-label="<?php echo e(__('public.adm_users_idx_col_role')); ?>"><span class="badge badge-neutral"><?php echo e($user->role?->name ?? (__('admin_extra.users_role_none', [], app()->getLocale()) ?: 'none')); ?></span></td>
                    <td data-label="<?php echo e(__('admin_extra.users_col_facility')); ?>">
                        <?php if($user->primaryFacility): ?>
                            <?php echo e($user->primaryFacility->name); ?>

                        <?php else: ?>
                            <span class="badge badge-warning"><?php echo e(__('admin_extra.users_facility_none')); ?></span>
                        <?php endif; ?>
                    </td>
                    <td data-label="<?php echo e(__('public.adm_users_idx_col_status')); ?>">
                        <?php if($user->status==='active'): ?><span class="badge badge-success"><?php echo e(__('public.adm_users_idx_badge_active')); ?></span>
                        <?php elseif($user->status==='suspended'): ?><span class="badge badge-danger"><?php echo e(__('public.adm_users_idx_badge_suspended')); ?></span>
                        <?php else: ?><span class="badge badge-warning"><?php echo e(\App\Support\Enums::label($user->status)); ?></span><?php endif; ?>
                    </td>
                    <td data-label="<?php echo e(__('public.adm_users_idx_col_created')); ?>" class="td-muted"><?php echo e($user->created_at?->format('d M Y')); ?></td>
                    <td class="row-actions" data-label="<?php echo e(__('public.adm_users_idx_col_actions')); ?>">
                        <a href="<?php echo e(route('admin.users.show', $user->id)); ?>" class="icon-btn" aria-label="<?php echo e(__('public.aria_view_user')); ?>" title="<?php echo e(__('admin_extra.title_view', [], app()->getLocale()) ?: 'View'); ?>"><i data-lucide="eye"></i></a>
                        <?php if($user->status!=='active'): ?>
                        <form method="POST" action="<?php echo e(route('admin.users.activate',$user->id)); ?>" class="inline-form"><?php echo csrf_field(); ?>
                            <button type="submit" class="icon-btn" aria-label="<?php echo e(__('public.aria_activate_user')); ?>" title="<?php echo e(__('public.aria_activate')); ?>"><i data-lucide="check-circle"></i></button>
                        </form>
                        <?php endif; ?>
                        <?php if($user->status!=='suspended'): ?>
                        <form method="POST" action="<?php echo e(route('admin.users.suspend',$user->id)); ?>" class="inline-form"><?php echo csrf_field(); ?>
                            <button type="submit" class="icon-btn" aria-label="<?php echo e(__('public.aria_suspend_user')); ?>" title="<?php echo e(__('public.aria_suspend')); ?>"><i data-lucide="ban"></i></button>
                        </form>
                        <?php endif; ?>
                        <button type="button" class="icon-btn" aria-label="<?php echo e(__('public.aria_delete_user')); ?>" title="<?php echo e(__('public.aria_delete')); ?>" onclick="opOpenModal('delete-user-<?php echo e($user->id); ?>')"><i data-lucide="trash-2"></i></button>
                        <div id="delete-user-<?php echo e($user->id); ?>" class="modal-backdrop mt-6" hidden>
                            <div class="modal" role="dialog" aria-modal="true">
                                <h3 class="modal__title"><i data-lucide="alert-triangle"></i> <?php echo e(__('public.adm_users_idx_modal_delete_title')); ?></h3>
                                <form method="POST" action="<?php echo e(route('admin.users.destroy',$user->id)); ?>"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                    <div class="modal__body"><p><?php echo e(__('public.adm_users_idx_modal_delete_title')); ?> <strong><?php echo e($user->name); ?></strong>?</p></div>
                                    <div class="modal__footer">
                                        <button type="button" class="btn btn-ghost" onclick="opCloseModal('delete-user-<?php echo e($user->id); ?>')"><?php echo e(__('public.adm_users_idx_modal_delete_btn_cancel')); ?></button>
                                        <button type="submit" class="btn btn-danger"><?php echo e(__('public.adm_users_idx_modal_delete_btn_delete')); ?></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="7" class="td-muted empty-cell"><?php echo e(__('public.adm_users_idx_empty')); ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="panel-body"><?php echo e($users->links()); ?></div>
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

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\admin\users\index.blade.php ENDPATH**/ ?>