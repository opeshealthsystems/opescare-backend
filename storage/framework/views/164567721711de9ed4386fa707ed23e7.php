<?php $__env->startSection('title', __('public.adm_roles_idx_title')); ?>
<?php echo $__env->make('portals.admin.control_center._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->startSection('breadcrumb_home', 'Admin'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.admin')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.adm_roles_idx_breadcrumb')); ?>
<?php $__env->startSection('content'); ?>

<div class="breadcrumb">
    <a href="<?php echo e(route('portals.admin.roles.index')); ?>"><?php echo e(__('public.adm_roles_idx_breadcrumb')); ?></a>
    <i data-lucide="chevron-right"></i>
    <span><?php echo e(__('public.adm_roles_idx_breadcrumb_sub')); ?></span>
</div>

<div class="page-head">
    <h2><?php echo e(__('public.adm_roles_idx_breadcrumb')); ?></h2>
    <div class="page-head__spacer"></div>
    <button type="button" class="btn btn-primary" onclick="opOpenModal('create-role-modal')">
        <i data-lucide="plus"></i> <?php echo e(__('public.adm_roles_idx_btn_create')); ?>

    </button>
</div>

<?php if(session('success')): ?><div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div><?php endif; ?>
<?php if(session('error')): ?><div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div><?php echo e(session('error')); ?></div></div><?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="shield"></i> <?php echo e(__('public.adm_roles_idx_panel_title')); ?></h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?php echo e(__('public.adm_roles_idx_col_role_name')); ?></th>
                    <th><?php echo e(__('public.adm_roles_idx_col_tier')); ?></th>
                    <th><?php echo e(__('public.adm_roles_idx_col_description')); ?></th>
                    <th><?php echo e(__('public.adm_roles_idx_col_portal')); ?></th>
                    <th><?php echo e(__('public.adm_roles_idx_col_users')); ?></th>
                    <th class="row-actions"><?php echo e(__('public.adm_roles_idx_col_actions')); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $platformPortals = ['admin','finance'];
                    $isPlatform = in_array($role->portal ?? '', $platformPortals, true);
                    $pColors=['patient_family'=>'badge-primary','clinical'=>'badge-success','admin'=>'badge-danger','finance'=>'badge-warning','connect'=>'badge-teal','public'=>'badge-neutral'];
                    $pc=$pColors[$role->portal??'']??'badge-neutral';
                ?>
                <tr>
                    <td data-label="<?php echo e(__('public.adm_roles_idx_col_role_name')); ?>">
                        <span class="td-strong"><?php echo e($role->name); ?></span>
                        <?php if($role->is_protected): ?><span class="badge badge-neutral"><?php echo e(__('public.adm_roles_idx_badge_protected')); ?></span><?php endif; ?>
                    </td>
                    <td data-label="<?php echo e(__('public.adm_roles_idx_col_tier')); ?>">
                        <?php if($isPlatform): ?><span class="badge badge-primary"><?php echo e(__('public.adm_roles_idx_badge_platform')); ?></span>
                        <?php else: ?><span class="badge badge-teal"><?php echo e(__('public.adm_roles_idx_badge_facility')); ?></span><?php endif; ?>
                    </td>
                    <td data-label="<?php echo e(__('public.adm_roles_idx_col_description')); ?>" class="td-muted"><?php echo e($role->description ?? '—'); ?></td>
                    <td data-label="<?php echo e(__('public.adm_roles_idx_col_portal')); ?>"><span class="badge <?php echo e($pc); ?>"><?php echo e(ucfirst(str_replace('_',' ',$role->portal??'—'))); ?></span></td>
                    <td data-label="<?php echo e(__('public.adm_roles_idx_col_users')); ?>"><?php echo e($role->users_count ?? 0); ?></td>
                    <td class="row-actions" data-label="<?php echo e(__('public.adm_roles_idx_col_actions')); ?>">
                        <a href="<?php echo e(route('portals.admin.roles.users', $role)); ?>" class="icon-btn" aria-label="<?php echo e(__('public.adm_roles_idx_aria_view_users')); ?>" title="<?php echo e(__('public.adm_roles_idx_aria_view_users')); ?>"><i data-lucide="users"></i></a>
                        <button type="button" class="icon-btn" aria-label="<?php echo e(__('public.adm_roles_idx_aria_edit')); ?>" title="<?php echo e(__('public.adm_roles_idx_aria_edit')); ?>"
                            onclick="openEditRole('<?php echo e($role->id); ?>','<?php echo e(addslashes($role->name)); ?>','<?php echo e(addslashes($role->description ?? '')); ?>','<?php echo e($role->portal); ?>','<?php echo e($role->is_protected ? '1':'0'); ?>')">
                            <i data-lucide="pencil"></i>
                        </button>
                        <?php if(!$role->is_protected): ?>
                        <button type="button" class="icon-btn" aria-label="<?php echo e(__('public.adm_roles_idx_aria_delete')); ?>" title="<?php echo e(__('public.adm_roles_idx_aria_delete')); ?>" onclick="opOpenModal('delete-role-<?php echo e($role->id); ?>')"><i data-lucide="trash-2"></i></button>
                        <div id="delete-role-<?php echo e($role->id); ?>" class="modal-backdrop mt-6" hidden>
                            <div class="modal" role="dialog" aria-modal="true">
                                <h3 class="modal__title"><i data-lucide="alert-triangle"></i> <?php echo e(__('public.adm_roles_idx_modal_delete_title')); ?></h3>
                                <form method="POST" action="<?php echo e(route('portals.admin.roles.destroy', $role)); ?>"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                    <div class="modal__body"><p><?php echo e(__('public.adm_roles_idx_modal_delete_body_before')); ?> <strong><?php echo e($role->name); ?></strong>? <?php echo e(__('public.adm_roles_idx_cannot_undo')); ?></p></div>
                                    <div class="modal__footer">
                                        <button type="button" class="btn btn-ghost" onclick="opCloseModal('delete-role-<?php echo e($role->id); ?>')"><?php echo e(__('public.adm_roles_idx_btn_cancel')); ?></button>
                                        <button type="submit" class="btn btn-danger"><?php echo e(__('public.adm_roles_idx_btn_delete')); ?></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="6" class="td-muted empty-cell"><?php echo e(__('public.adm_roles_idx_empty')); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="panel-body"><?php echo e($roles->links()); ?></div>
</div>


<div id="create-role-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="create-role-title">
        <h3 class="modal__title" id="create-role-title"><i data-lucide="plus"></i> <?php echo e(__('public.adm_roles_idx_modal_create_title')); ?></h3>
        <form method="POST" action="<?php echo e(route('portals.admin.roles.store')); ?>">
            <?php echo csrf_field(); ?>
            <div class="modal__body">
                <div class="form-group mb-6">
                    <label class="form-label form-label-required"><?php echo e(__('public.adm_roles_idx_lbl_role_name')); ?></label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. senior_nurse">
                    <div class="form-hint"><?php echo e(__('public.adm_roles_idx_hint_role_name')); ?></div>
                </div>
                <div class="form-group mb-6">
                    <label class="form-label"><?php echo e(__('public.adm_roles_idx_lbl_description')); ?></label>
                    <textarea name="description" class="form-control" rows="2" placeholder="<?php echo e(__('public.adm_roles_idx_ph_description')); ?>"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required"><?php echo e(__('public.adm_roles_idx_lbl_portal')); ?></label>
                    <select name="portal" class="form-control" required>
                        <option value=""><?php echo e(__('public.adm_roles_idx_ph_select_portal')); ?></option>
                        <option value="patient_family"><?php echo e(__('public.adm_roles_idx_opt_patient_family')); ?></option>
                        <option value="clinical"><?php echo e(__('public.adm_roles_idx_opt_clinical')); ?></option>
                        <option value="admin"><?php echo e(__('public.adm_roles_idx_opt_admin')); ?></option>
                        <option value="finance"><?php echo e(__('public.adm_roles_idx_opt_finance')); ?></option>
                        <option value="connect"><?php echo e(__('public.adm_roles_idx_opt_connect')); ?></option>
                        <option value="public"><?php echo e(__('public.adm_roles_idx_opt_public')); ?></option>
                    </select>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('create-role-modal')"><?php echo e(__('public.adm_roles_idx_btn_cancel')); ?></button>
                <button type="submit" class="btn btn-primary"><?php echo e(__('public.adm_roles_idx_modal_create_title')); ?></button>
            </div>
        </form>
    </div>
</div>


<div id="edit-role-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="edit-role-title">
        <h3 class="modal__title" id="edit-role-title"><i data-lucide="pencil"></i> <?php echo e(__('public.adm_roles_idx_modal_edit_title')); ?></h3>
        <form method="POST" id="edit-role-form" action="">
            <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
            <div class="modal__body">
                <div class="form-group mb-6">
                    <label class="form-label"><?php echo e(__('public.adm_roles_idx_lbl_role_name')); ?></label>
                    <input type="text" id="edit-role-name" class="form-control" disabled>
                    <div id="edit-role-note" class="form-hint"></div>
                </div>
                <div class="form-group mb-6">
                    <label class="form-label"><?php echo e(__('public.adm_roles_idx_lbl_description')); ?></label>
                    <textarea name="description" id="edit-role-desc" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required"><?php echo e(__('public.adm_roles_idx_lbl_portal')); ?></label>
                    <select name="portal" id="edit-role-portal" class="form-control" required>
                        <option value="patient_family"><?php echo e(__('public.adm_roles_idx_opt_patient_family')); ?></option>
                        <option value="clinical"><?php echo e(__('public.adm_roles_idx_opt_clinical')); ?></option>
                        <option value="admin"><?php echo e(__('public.adm_roles_idx_opt_admin')); ?></option>
                        <option value="finance"><?php echo e(__('public.adm_roles_idx_opt_finance')); ?></option>
                        <option value="connect"><?php echo e(__('public.adm_roles_idx_opt_connect')); ?></option>
                        <option value="public"><?php echo e(__('public.adm_roles_idx_opt_public')); ?></option>
                    </select>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('edit-role-modal')"><?php echo e(__('public.adm_roles_idx_btn_cancel')); ?></button>
                <button type="submit" class="btn btn-primary"><?php echo e(__('public.adm_roles_idx_btn_save_changes')); ?></button>
            </div>
        </form>
    </div>
</div>

<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
<script>
function opOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function opCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
function openEditRole(id, name, description, portal, isProtected) {
    document.getElementById('edit-role-name').value    = name;
    document.getElementById('edit-role-desc').value    = description;
    document.getElementById('edit-role-portal').value  = portal;
    const protectedEl = document.getElementById('edit-role-portal');
    const noteEl = document.getElementById('edit-role-note');
    if (isProtected === '1') {
        protectedEl.disabled = true;
        noteEl.textContent = '<?php echo e(__('public.adm_roles_idx_protected_note')); ?>';
    } else {
        protectedEl.disabled = false;
        noteEl.textContent = '';
    }
    document.getElementById('edit-role-form').action = '/portals/admin/roles/' + id;
    opOpenModal('edit-role-modal');
}
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\admin\roles\index.blade.php ENDPATH**/ ?>