<?php $__env->startSection('title', __('public.adm_dev_prod_title')); ?>
<?php $__env->startSection('sidebar'); ?> <?php echo $__env->make('portals.admin.connect._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="breadcrumb">
        <i data-lucide="chevron-right"></i>
    <span><?php echo e(__('public.adm_dev_prod_breadcrumb_section')); ?></span>
</div>

<div class="page-head">
    <h2><?php echo e(__('public.adm_dev_prod_heading')); ?></h2>
    <div class="page-head__spacer"></div>
</div>
<p class="td-muted mb-6"><?php echo e(__('public.adm_dev_prod_subtitle')); ?></p>

<?php if(session('success')): ?><div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div><?php endif; ?>
<?php if(session('error')): ?><div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div><?php echo e(session('error')); ?></div></div><?php endif; ?>


<div class="stat-grid mb-6">
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value"><?php echo e($stats['pending']); ?></div>
        <div class="stat-card__label"><?php echo e(__('public.adm_dev_prod_stat_pending')); ?></div>
    </div>
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value"><?php echo e($stats['under_review']); ?></div>
        <div class="stat-card__label"><?php echo e(__('public.adm_dev_prod_stat_under_review')); ?></div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value"><?php echo e($stats['approved']); ?></div>
        <div class="stat-card__label"><?php echo e(__('public.adm_dev_prod_stat_approved')); ?></div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value"><?php echo e($stats['rejected']); ?></div>
        <div class="stat-card__label"><?php echo e(__('public.adm_dev_prod_stat_rejected')); ?></div>
    </div>
</div>


<div class="tabs mb-6">
    <?php $__currentLoopData = ['all'=>__('public.adm_dev_prod_tab_all'),'pending'=>__('public.adm_dev_prod_tab_pending'),'under_review'=>__('public.adm_dev_prod_tab_under_review'),'approved'=>__('public.adm_dev_prod_tab_approved'),'rejected'=>__('public.adm_dev_prod_tab_rejected')]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val=>$label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php $target = $val === 'all' ? null : $val; $isActive = request('status', $target) === $target; ?>
    <a href="<?php echo e(request()->fullUrlWithQuery(['status'=>$target])); ?>" class="tab <?php echo e($isActive ? 'active' : ''); ?>"><?php echo e($label); ?></a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<?php if($requests->isEmpty()): ?>
<div class="empty-state">
    <div class="empty-state-icon"><i data-lucide="clipboard-list"></i></div>
    <p><?php echo e(__('public.adm_dev_prod_empty')); ?></p>
</div>
<?php else: ?>
<div class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th><?php echo e(__('public.adm_dev_prod_col_developer')); ?></th>
                <th><?php echo e(__('public.adm_dev_prod_col_use_case')); ?></th>
                <th><?php echo e(__('public.adm_dev_prod_col_scopes')); ?></th>
                <th><?php echo e(__('public.adm_dev_prod_col_patient_data')); ?></th>
                <th><?php echo e(__('public.adm_dev_prod_col_status')); ?></th>
                <th><?php echo e(__('public.adm_dev_prod_col_submitted')); ?></th>
                <th class="row-actions"><?php echo e(__('public.adm_dev_prod_col_actions')); ?></th>
            </tr></thead>
            <tbody>
            <?php $__currentLoopData = $requests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $req): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td data-label="<?php echo e(__('public.adm_dev_prod_col_developer')); ?>">
                    <div class="td-strong"><?php echo e($req->developerAccount->display_name ?? $req->developerAccount->email ?? '—'); ?></div>
                    <?php if($req->integration_client_id): ?><div class="code-token"><?php echo e(Str::limit($req->integration_client_id, 28)); ?></div><?php endif; ?>
                </td>
                <td data-label="<?php echo e(__('public.adm_dev_prod_col_use_case')); ?>">
                    <div><?php echo e(Str::limit($req->use_case, 55)); ?></div>
                    <?php if($req->technical_description): ?><div class="td-muted"><?php echo e(Str::limit($req->technical_description, 60)); ?></div><?php endif; ?>
                </td>
                <td data-label="<?php echo e(__('public.adm_dev_prod_col_scopes')); ?>"><span class="badge badge-neutral"><?php echo e(count((array)$req->requested_scopes)); ?> <?php echo e(__('public.adm_dev_prod_scopes_label')); ?></span></td>
                <td data-label="<?php echo e(__('public.adm_dev_prod_col_patient_data')); ?>">
                    <?php if($req->handles_patient_data): ?>
                    <span class="badge badge-warning"><?php echo e(__('public.adm_dev_prod_badge_yes')); ?></span>
                    <?php else: ?>
                    <span class="badge badge-neutral"><?php echo e(__('public.adm_dev_prod_badge_no')); ?></span>
                    <?php endif; ?>
                </td>
                <td data-label="<?php echo e(__('public.adm_dev_prod_col_status')); ?>">
                    <span class="<?php echo e($req->statusBadgeClass()); ?>"><?php echo e(\App\Support\Enums::label($req->status)); ?></span>
                    <?php if($req->reviewed_at): ?><div class="td-muted"><?php echo e($req->reviewed_at->format('d M Y')); ?></div><?php endif; ?>
                </td>
                <td data-label="<?php echo e(__('public.adm_dev_prod_col_submitted')); ?>">
                    <?php echo e($req->created_at->format('d M Y')); ?>

                    <?php if($req->estimated_daily_requests): ?><div class="td-muted"><?php echo e($req->estimated_daily_requests); ?> <?php echo e(__('public.adm_dev_prod_req_per_day')); ?></div><?php endif; ?>
                </td>
                <td class="row-actions" data-label="<?php echo e(__('public.adm_dev_prod_col_actions')); ?>">
                    <?php if(in_array($req->status, ['pending','under_review'])): ?>
                        <button type="button" class="btn btn-success btn-sm" onclick="opOpenModal('approve-<?php echo e($req->id); ?>')"><i data-lucide="check"></i> <?php echo e(__('public.adm_dev_prod_btn_approve')); ?></button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="opOpenModal('reject-<?php echo e($req->id); ?>')"><i data-lucide="x"></i> <?php echo e(__('public.adm_dev_prod_btn_reject')); ?></button>
                    <?php elseif($req->status === 'approved'): ?>
                        <span class="text-success"><?php echo e(__('public.adm_dev_prod_status_approved')); ?></span>
                        <?php if($req->review_notes): ?><div class="td-muted"><?php echo e(Str::limit($req->review_notes, 30)); ?></div><?php endif; ?>
                    <?php else: ?>
                        <span class="text-danger"><?php echo e(__('public.adm_dev_prod_status_rejected')); ?></span>
                        <?php if($req->rejected_reason): ?><div class="td-muted"><?php echo e(Str::limit($req->rejected_reason, 30)); ?></div><?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
    <div class="panel-body"><?php echo e($requests->links()); ?></div>
</div>


<?php $__currentLoopData = $requests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $req): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php if(in_array($req->status, ['pending','under_review'])): ?>
    <div id="approve-<?php echo e($req->id); ?>" class="modal-backdrop mt-6" hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="approve-<?php echo e($req->id); ?>-title">
            <h3 class="modal__title" id="approve-<?php echo e($req->id); ?>-title"><i data-lucide="check-circle"></i> <?php echo e(__('public.adm_dev_prod_modal_approve_title')); ?></h3>
            <form method="POST" action="<?php echo e(route('portals.admin.developer.production_requests.approve', $req->id)); ?>">
                <?php echo csrf_field(); ?>
                <div class="modal__body"><p><?php echo e(__('public.adm_dev_prod_modal_approve_body')); ?></p></div>
                <div class="modal__footer">
                    <button type="button" class="btn btn-ghost" onclick="opCloseModal('approve-<?php echo e($req->id); ?>')"><?php echo e(__('public.adm_dev_prod_modal_btn_cancel')); ?></button>
                    <button type="submit" class="btn btn-success"><?php echo e(__('public.adm_dev_prod_btn_approve')); ?></button>
                </div>
            </form>
        </div>
    </div>
    <div id="reject-<?php echo e($req->id); ?>" class="modal-backdrop mt-6" hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="reject-<?php echo e($req->id); ?>-title">
            <h3 class="modal__title" id="reject-<?php echo e($req->id); ?>-title"><i data-lucide="x-circle"></i> <?php echo e(__('public.adm_dev_prod_modal_reject_title')); ?></h3>
            <form method="POST" action="<?php echo e(route('portals.admin.developer.production_requests.reject', $req->id)); ?>">
                <?php echo csrf_field(); ?>
                <div class="modal__body">
                    <p><?php echo e(__('public.adm_dev_prod_modal_reject_body')); ?></p>
                    <textarea name="reason" rows="3" required placeholder="<?php echo e(__('public.adm_dev_prod_modal_reject_ph')); ?>" class="form-control"></textarea>
                </div>
                <div class="modal__footer">
                    <button type="button" class="btn btn-ghost" onclick="opCloseModal('reject-<?php echo e($req->id); ?>')"><?php echo e(__('public.adm_dev_prod_modal_btn_cancel')); ?></button>
                    <button type="submit" class="btn btn-danger"><?php echo e(__('public.adm_dev_prod_modal_btn_confirm_reject')); ?></button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?>

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
<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\admin\developer\production_requests.blade.php ENDPATH**/ ?>