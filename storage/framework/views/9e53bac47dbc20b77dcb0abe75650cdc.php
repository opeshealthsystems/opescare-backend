<?php $__env->startSection('title', __('public.adm_legal_page_title')); ?>
<?php $__env->startSection('sidebar'); ?> <?php echo $__env->make('portals.admin.control_center._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="breadcrumb">
        <i data-lucide="chevron-right"></i>
    <span><?php echo e(__('public.adm_legal_breadcrumb_docs')); ?></span>
</div>

<div class="page-head">
    <h2><?php echo e(__('public.adm_legal_h2')); ?></h2>
    <div class="page-head__spacer"></div>
</div>

<p class="td-muted mb-6"><?php echo e(__('public.adm_legal_subtitle')); ?></p>


<div class="stat-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__head"><i data-lucide="file-text"></i><span class="stat-card__label"><?php echo e(__('public.adm_legal_stat_documents')); ?></span></div>
        <div class="stat-card__value"><?php echo e($stats['total_documents']); ?></div>
    </div>
    <div class="stat-card stat-card--primary">
        <div class="stat-card__head"><i data-lucide="layers"></i><span class="stat-card__label"><?php echo e(__('public.adm_legal_stat_versions')); ?></span></div>
        <div class="stat-card__value"><?php echo e($stats['total_versions']); ?></div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__head"><i data-lucide="check-circle"></i><span class="stat-card__label"><?php echo e(__('public.adm_legal_stat_user_acceptances')); ?></span></div>
        <div class="stat-card__value"><?php echo e(number_format($stats['user_acceptances'])); ?></div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__head"><i data-lucide="handshake"></i><span class="stat-card__label"><?php echo e(__('public.adm_legal_stat_partner_agreements')); ?></span></div>
        <div class="stat-card__value"><?php echo e(number_format($stats['partner_acceptances'])); ?></div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="scale"></i> <?php echo e(__('public.adm_legal_panel_title')); ?></h3>
        <div class="row-actions-inline">
                                                <a href="<?php echo e(route('public.legal')); ?>" target="_blank" class="btn btn-secondary btn-sm">
                <i data-lucide="external-link"></i> <?php echo e(__('public.adm_legal_btn_public_view')); ?>

            </a>
        </div>
    </div>
    <div class="panel-body panel-body--flush">
        <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr><th><?php echo e(__('public.adm_legal_col_title')); ?></th><th><?php echo e(__('public.adm_legal_col_type')); ?></th><th><?php echo e(__('public.adm_legal_col_language')); ?></th><th><?php echo e(__('public.adm_legal_col_current_version')); ?></th><th><?php echo e(__('public.adm_legal_col_status')); ?></th><th class="row-actions"></th></tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php $ver = $doc->versions->first(); ?>
                    <tr>
                        <td data-label="<?php echo e(__('public.adm_legal_col_title')); ?>" class="td-strong"><?php echo e($doc->title); ?></td>
                        <td data-label="<?php echo e(__('public.adm_legal_col_type')); ?>">
                            <span class="badge badge-primary badge-sm"><?php echo e(str_replace('_', ' ', ucfirst($doc->document_type))); ?></span>
                        </td>
                        <td data-label="<?php echo e(__('public.adm_legal_col_language')); ?>"><?php echo e(strtoupper($doc->language)); ?></td>
                        <td data-label="<?php echo e(__('public.adm_legal_col_current_version')); ?>">
                            <?php if($ver): ?>
                                <span class="mono kv-strong">v<?php echo e($ver->version); ?></span>
                                <?php if($ver->requires_reacceptance): ?>
                                    <span class="badge badge-warning badge-sm"><?php echo e(__('public.adm_legal_reaccept_req')); ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="td-muted"><?php echo e(__('public.adm_legal_no_version')); ?></span>
                            <?php endif; ?>
                        </td>
                        <td data-label="<?php echo e(__('public.adm_legal_col_status')); ?>">
                            <span class="badge <?php echo e($doc->is_active ? 'badge-success' : 'badge-neutral'); ?> badge-sm">
                                <?php echo e($doc->is_active ? __('public.adm_legal_status_active') : __('public.adm_legal_status_inactive')); ?>

                            </span>
                        </td>
                        <td class="row-actions">
                                                    </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="6" class="td-muted empty-cell">
                        <?php echo e(__('public.adm_legal_empty_docs')); ?>

                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>


<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
<script>
function opOpenModal(id){ document.getElementById(id).classList.add('open'); }
function opCloseModal(id){ document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-fixed').forEach(function(m){
    m.addEventListener('click', function(e){ if(e.target===m) m.classList.remove('open'); });
});
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.modal-fixed').forEach(function(m){ m.classList.remove('open'); }); }
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\admin\legal\index.blade.php ENDPATH**/ ?>