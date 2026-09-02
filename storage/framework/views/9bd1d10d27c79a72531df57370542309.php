<?php $__env->startSection('title', __('leads.admin.page_title')); ?>
<?php echo $__env->make('portals.admin.control_center._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->startSection('breadcrumb_home', 'Admin'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.admin')); ?>
<?php $__env->startSection('breadcrumb_section', __('leads.admin.breadcrumb')); ?>
<?php $__env->startSection('content'); ?>

<div class="page-head">
    <h2><?php echo e(__('leads.admin.heading')); ?></h2>
    <div class="page-head__spacer"></div>
</div>
<p class="td-muted mb-6"><?php echo e(__('leads.admin.description')); ?></p>

<?php if(session('success')): ?><div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div><?php endif; ?>
<?php if(session('error')): ?><div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div><?php echo e(session('error')); ?></div></div><?php endif; ?>

<div class="stat-grid mb-6">
    <div class="stat-card stat-card--primary"><div class="stat-card__head"><i data-lucide="inbox"></i></div><div class="stat-card__label"><?php echo e(__('leads.admin.stat_total')); ?></div><div class="stat-card__value"><?php echo e($totalCount); ?></div></div>
    <div class="stat-card stat-card--danger"><div class="stat-card__head"><i data-lucide="sparkles"></i></div><div class="stat-card__label"><?php echo e(__('leads.statuses.new')); ?></div><div class="stat-card__value"><?php echo e($counts['new'] ?? 0); ?></div></div>
    <div class="stat-card stat-card--warning"><div class="stat-card__head"><i data-lucide="phone"></i></div><div class="stat-card__label"><?php echo e(__('leads.statuses.contacted')); ?></div><div class="stat-card__value"><?php echo e($counts['contacted'] ?? 0); ?></div></div>
    <div class="stat-card stat-card--success"><div class="stat-card__head"><i data-lucide="trophy"></i></div><div class="stat-card__label"><?php echo e(__('leads.statuses.won')); ?></div><div class="stat-card__value"><?php echo e($counts['won'] ?? 0); ?></div></div>
</div>

<form method="GET" action="<?php echo e(route('portals.admin.leads')); ?>" class="filter-bar">
    <select name="status" class="filter-select" aria-label="<?php echo e(__('leads.admin.filter_label')); ?>">
        <option value=""><?php echo e(__('leads.admin.filter_all')); ?></option>
        <?php $__currentLoopData = \App\Models\Lead::STATUSES; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($s); ?>" <?php if($activeStatus===$s): echo 'selected'; endif; ?>><?php echo e(__('leads.statuses.'.$s)); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="filter"></i> <?php echo e(__('leads.admin.filter_apply')); ?></button>
    <a href="<?php echo e(route('portals.admin.leads')); ?>" class="btn btn-ghost btn-sm"><?php echo e(__('leads.admin.filter_reset')); ?></a>
</form>

<div class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?php echo e(__('leads.admin.col_organization')); ?></th>
                    <th><?php echo e(__('leads.admin.col_type')); ?></th>
                    <th><?php echo e(__('leads.admin.col_contact')); ?></th>
                    <th><?php echo e(__('leads.admin.col_source')); ?></th>
                    <th><?php echo e(__('leads.admin.col_status')); ?></th>
                    <th><?php echo e(__('leads.admin.col_date')); ?></th>
                    <th class="row-actions"><?php echo e(__('leads.admin.col_actions')); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $leads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lead): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                $statusBadge = match($lead->status){'new'=>'badge-danger','contacted'=>'badge-warning','qualified'=>'badge-primary','won'=>'badge-success','lost'=>'badge-neutral',default=>'badge-neutral'};
                ?>
                <tr>
                    <td data-label="<?php echo e(__('leads.admin.col_organization')); ?>"><span class="td-strong"><?php echo e($lead->organization_name ?: '—'); ?></span></td>
                    <td data-label="<?php echo e(__('leads.admin.col_type')); ?>"><span class="badge badge-neutral"><?php echo e($lead->organization_type ? __('leads.org_types.'.$lead->organization_type) : '—'); ?></span></td>
                    <td data-label="<?php echo e(__('leads.admin.col_contact')); ?>">
                        <div class="td-strong"><?php echo e($lead->name); ?></div>
                        <div class="td-muted"><?php echo e($lead->email); ?></div>
                    </td>
                    <td data-label="<?php echo e(__('leads.admin.col_source')); ?>"><span class="badge badge-neutral"><?php echo e($lead->source); ?></span></td>
                    <td data-label="<?php echo e(__('leads.admin.col_status')); ?>"><span class="badge <?php echo e($statusBadge); ?>"><?php echo e(__('leads.statuses.'.$lead->status)); ?></span></td>
                    <td data-label="<?php echo e(__('leads.admin.col_date')); ?>"><?php echo e($lead->created_at?->format('d M Y')); ?></td>
                    <td class="row-actions" data-label="<?php echo e(__('leads.admin.col_actions')); ?>">
                        <button type="button" class="btn btn-ghost btn-sm" onclick="opOpenModal('lead-<?php echo e($lead->id); ?>')"><i data-lucide="pencil"></i> <?php echo e(__('leads.admin.action_update')); ?></button>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="7" class="td-muted empty-cell"><?php echo e(__('leads.admin.empty')); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if($leads->hasPages()): ?>
    <div class="panel-body"><?php echo e($leads->withQueryString()->links()); ?></div>
    <?php endif; ?>
</div>


<?php $__currentLoopData = $leads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lead): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<div id="lead-<?php echo e($lead->id); ?>" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="lead-<?php echo e($lead->id); ?>-title">
        <h3 class="modal__title" id="lead-<?php echo e($lead->id); ?>-title"><i data-lucide="pencil"></i> <?php echo e(__('leads.admin.modal_title')); ?></h3>
        <form method="POST" action="<?php echo e(route('portals.admin.leads.status', $lead)); ?>">
            <?php echo csrf_field(); ?>
            <div class="modal__body">
                <p class="td-strong mb-2"><?php echo e($lead->organization_name ?: $lead->name); ?></p>
                <label class="demo-label" for="status-<?php echo e($lead->id); ?>"><?php echo e(__('leads.admin.modal_status')); ?></label>
                <select name="status" id="status-<?php echo e($lead->id); ?>" class="filter-select" style="width:100%;margin-bottom:1rem;">
                    <?php $__currentLoopData = \App\Models\Lead::STATUSES; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($s); ?>" <?php if($lead->status===$s): echo 'selected'; endif; ?>><?php echo e(__('leads.statuses.'.$s)); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <label class="demo-label" for="note-<?php echo e($lead->id); ?>"><?php echo e(__('leads.admin.modal_note')); ?></label>
                <textarea name="note" id="note-<?php echo e($lead->id); ?>" rows="3" class="filter-select" style="width:100%;" placeholder="<?php echo e(__('leads.admin.modal_note_ph')); ?>"></textarea>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('lead-<?php echo e($lead->id); ?>')"><?php echo e(__('leads.admin.modal_cancel')); ?></button>
                <button type="submit" class="btn btn-primary"><?php echo e(__('leads.admin.modal_save')); ?></button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

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

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\admin\leads\index.blade.php ENDPATH**/ ?>