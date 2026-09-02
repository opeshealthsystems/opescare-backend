<?php $__env->startSection('title', $document->title); ?>
<?php $__env->startSection('sidebar'); ?> <?php echo $__env->make('portals.admin.control_center._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="breadcrumb">
        <i data-lucide="chevron-right"></i>
    <span><?php echo e($document->title); ?></span>
</div>

<div class="entity-head">
    <div class="entity-head__icon"><i data-lucide="scale"></i></div>
    <div>
        <h2 class="entity-head__title"><?php echo e($document->title); ?></h2>
        <div class="entity-head__sub">
            <span class="badge badge-primary badge-sm"><?php echo e(str_replace('_', ' ', ucfirst($document->document_type))); ?></span>
            <span class="td-muted text-sm"><?php echo e(strtoupper($document->language)); ?></span>
        </div>
    </div>
    <div class="entity-head__spacer"></div>
</div>

<?php if(session('success')): ?><div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div><?php endif; ?>
<?php if(session('error')): ?><div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div><?php echo e(session('error')); ?></div></div><?php endif; ?>


<div class="panel mb-6">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="layers"></i> <?php echo e(__('public.adm_legal_show_versions_title')); ?></h3></div>
    <div class="panel-body panel-body--flush">
        <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr><th><?php echo e(__('public.adm_legal_show_col_version')); ?></th><th><?php echo e(__('public.adm_legal_col_status')); ?></th><th><?php echo e(__('public.adm_legal_show_col_reaccept')); ?></th><th><?php echo e(__('public.adm_legal_show_col_published')); ?></th><th><?php echo e(__('public.adm_legal_show_col_effective')); ?></th><th><?php echo e(__('public.adm_legal_show_col_change_summary')); ?></th></tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $versions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ver): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="<?php echo e($ver->is_current ? 'row-emergency' : ''); ?>">
                        <td data-label="<?php echo e(__('public.adm_legal_show_col_version')); ?>">
                            <span class="mono kv-strong">v<?php echo e($ver->version); ?></span>
                            <?php if($ver->is_current): ?>
                                <span class="badge badge-success badge-sm"><?php echo e(__('public.adm_legal_show_status_current')); ?></span>
                            <?php endif; ?>
                        </td>
                        <td data-label="<?php echo e(__('public.adm_legal_col_status')); ?>">
                            <span class="badge <?php echo e($ver->isEffective() ? 'badge-success' : 'badge-warning'); ?> badge-sm">
                                <?php echo e($ver->isEffective() ? __('public.adm_legal_show_status_live') : __('public.adm_legal_show_status_scheduled')); ?>

                            </span>
                        </td>
                        <td data-label="<?php echo e(__('public.adm_legal_show_col_reaccept')); ?>">
                            <?php if($ver->requires_reacceptance): ?>
                                <span class="badge badge-danger badge-sm"><?php echo e(__('admin_extra.yes', [], app()->getLocale()) ?: 'Yes'); ?></span>
                            <?php else: ?>
                                <span class="td-muted"><?php echo e(__('admin_extra.no', [], app()->getLocale()) ?: 'No'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td data-label="<?php echo e(__('public.adm_legal_show_col_published')); ?>" class="td-muted">
                            <?php echo e($ver->published_at?->format('d M Y H:i') ?? '—'); ?>

                        </td>
                        <td data-label="<?php echo e(__('public.adm_legal_show_col_effective')); ?>" class="td-muted">
                            <?php echo e($ver->effective_at?->format('d M Y') ?? '—'); ?>

                        </td>
                        <td data-label="<?php echo e(__('public.adm_legal_show_col_change_summary')); ?>"><?php echo e($ver->change_summary ?: '—'); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="6" class="td-muted empty-cell"><?php echo e(__('public.adm_legal_show_empty_versions')); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>


<?php $current = $versions->where('is_current', true)->first(); ?>
<?php if($current): ?>
<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="file-text"></i> <?php echo e(__('public.adm_legal_show_content_title')); ?> (v<?php echo e($current->version); ?>)</h3>
        <a href="<?php echo e(route('public.legal.show', $document->slug)); ?>" target="_blank" class="btn btn-secondary btn-sm">
            <i data-lucide="external-link"></i> <?php echo e(__('public.adm_legal_show_btn_public_view')); ?>

        </a>
    </div>
    <div class="panel-body">
        <div class="legal-preview">
            <?php echo $current->content_html; ?>

        </div>
    </div>
</div>
<?php endif; ?>


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

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\admin\legal\show.blade.php ENDPATH**/ ?>