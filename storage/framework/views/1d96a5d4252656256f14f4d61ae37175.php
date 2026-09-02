<?php $__env->startSection('title', __('public.adm_dev_acc_title')); ?>
<?php $__env->startSection('sidebar'); ?> <?php echo $__env->make('portals.admin.connect._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="breadcrumb">
        <i data-lucide="chevron-right"></i>
    <span><?php echo e(__('public.adm_dev_acc_breadcrumb_section')); ?></span>
</div>

<div class="page-head">
    <h2><?php echo e(__('public.adm_dev_acc_heading')); ?></h2>
    <div class="page-head__spacer"></div>
</div>
<p class="td-muted mb-6"><?php echo e(__('public.adm_dev_acc_subtitle')); ?></p>

<?php if(session('success')): ?><div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div><?php endif; ?>
<?php if(session('error')): ?><div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div><?php echo e(session('error')); ?></div></div><?php endif; ?>


<div class="stat-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value"><?php echo e($stats['total']); ?></div>
        <div class="stat-card__label"><?php echo e(__('public.adm_dev_acc_stat_total')); ?></div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value"><?php echo e($stats['active']); ?></div>
        <div class="stat-card__label"><?php echo e(__('public.adm_dev_acc_stat_active')); ?></div>
    </div>
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value"><?php echo e($stats['sandbox_only']); ?></div>
        <div class="stat-card__label"><?php echo e(__('public.adm_dev_acc_stat_sandbox')); ?></div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__value"><?php echo e($stats['suspended']); ?></div>
        <div class="stat-card__label"><?php echo e(__('public.adm_dev_acc_stat_suspended')); ?></div>
    </div>
</div>

<?php if($accounts->isEmpty()): ?>
<div class="empty-state">
    <div class="empty-state-icon"><i data-lucide="users"></i></div>
    <p><?php echo e(__('public.adm_dev_acc_empty')); ?></p>
</div>
<?php else: ?>
<div class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th><?php echo e(__('public.adm_dev_acc_col_developer')); ?></th>
                <th><?php echo e(__('public.adm_dev_acc_col_email')); ?></th>
                <th><?php echo e(__('public.adm_dev_acc_col_company')); ?></th>
                <th><?php echo e(__('public.adm_dev_acc_col_apps')); ?></th>
                <th><?php echo e(__('public.adm_dev_acc_col_access')); ?></th>
                <th><?php echo e(__('public.adm_dev_acc_col_status')); ?></th>
                <th><?php echo e(__('public.adm_dev_acc_col_joined')); ?></th>
                <th class="row-actions"><?php echo e(__('public.adm_dev_acc_col_actions')); ?></th>
            </tr></thead>
            <tbody>
            <?php $__currentLoopData = $accounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $account): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td data-label="<?php echo e(__('public.adm_dev_acc_col_developer')); ?>">
                    <div class="td-strong"><?php echo e($account->display_name ?? '—'); ?></div>
                    <?php if($account->website_url): ?>
                    <div class="td-muted"><a href="<?php echo e($account->website_url); ?>" target="_blank"><?php echo e(Str::limit($account->website_url, 30)); ?></a></div>
                    <?php endif; ?>
                </td>
                <td data-label="<?php echo e(__('public.adm_dev_acc_col_email')); ?>"><span class="code-token"><?php echo e($account->email); ?></span></td>
                <td data-label="<?php echo e(__('public.adm_dev_acc_col_company')); ?>"><?php echo e($account->company_name ?? '—'); ?></td>
                <td data-label="<?php echo e(__('public.adm_dev_acc_col_apps')); ?>"><span class="badge badge-neutral"><?php echo e($account->integrationClients_count ?? 0); ?></span></td>
                <td data-label="<?php echo e(__('public.adm_dev_acc_col_access')); ?>">
                    <?php if($account->sandbox_only): ?>
                    <span class="badge badge-primary"><?php echo e(__('public.adm_dev_acc_badge_sandbox')); ?></span>
                    <?php else: ?>
                    <span class="badge badge-success"><?php echo e(__('public.adm_dev_acc_badge_production')); ?></span>
                    <?php endif; ?>
                </td>
                <td data-label="<?php echo e(__('public.adm_dev_acc_col_status')); ?>">
                    <span class="<?php echo e($account->statusBadgeClass()); ?>"><?php echo e(\App\Support\Enums::label($account->status)); ?></span>
                    <?php if($account->status === 'suspended' && $account->suspend_reason): ?>
                    <div class="td-muted"><?php echo e(Str::limit($account->suspend_reason, 30)); ?></div>
                    <?php endif; ?>
                </td>
                <td data-label="<?php echo e(__('public.adm_dev_acc_col_joined')); ?>">
                    <?php echo e($account->created_at->format('d M Y')); ?>

                    <?php if($account->api_terms_accepted): ?><div class="td-muted"><?php echo e(__('public.adm_dev_acc_terms_accepted')); ?></div><?php endif; ?>
                </td>
                <td class="row-actions" data-label="<?php echo e(__('public.adm_dev_acc_col_actions')); ?>">
                    <?php if($account->status !== 'suspended'): ?>
                    <button type="button" class="btn btn-danger btn-sm" onclick="opOpenModal('suspend-<?php echo e($account->id); ?>')"><?php echo e(__('public.adm_dev_acc_btn_suspend')); ?></button>
                    <?php else: ?>
                    <span class="td-muted"><?php echo e(__('public.adm_dev_acc_label_suspended')); ?></span>
                    <?php if($account->suspended_at): ?><div class="td-muted"><?php echo e($account->suspended_at->format('d M Y')); ?></div><?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
    <div class="panel-body"><?php echo e($accounts->links()); ?></div>
</div>


<?php $__currentLoopData = $accounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $account): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php if($account->status !== 'suspended'): ?>
    <div id="suspend-<?php echo e($account->id); ?>" class="modal-backdrop mt-6" hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="suspend-<?php echo e($account->id); ?>-title">
            <h3 class="modal__title" id="suspend-<?php echo e($account->id); ?>-title"><i data-lucide="ban"></i> <?php echo e(__('public.adm_dev_acc_modal_heading')); ?></h3>
            <form method="POST" action="<?php echo e(route('portals.admin.developer.accounts.suspend', $account->id)); ?>">
                <?php echo csrf_field(); ?>
                <div class="modal__body">
                    <p><?php echo e(__('public.adm_dev_acc_modal_body')); ?></p>
                    <textarea name="reason" rows="3" required placeholder="<?php echo e(__('public.adm_dev_acc_modal_ph_reason')); ?>" class="form-control"></textarea>
                </div>
                <div class="modal__footer">
                    <button type="button" class="btn btn-ghost" onclick="opCloseModal('suspend-<?php echo e($account->id); ?>')"><?php echo e(__('public.adm_dev_acc_modal_btn_cancel')); ?></button>
                    <button type="submit" class="btn btn-danger"><?php echo e(__('public.adm_dev_acc_modal_btn_confirm')); ?></button>
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
<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\admin\developer\accounts.blade.php ENDPATH**/ ?>