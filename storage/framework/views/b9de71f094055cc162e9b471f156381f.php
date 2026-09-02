<?php $__env->startSection('title', __('public.portal.documents_title', [], app()->getLocale()) ?: 'My Documents'); ?>
<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.portal.documents_breadcrumb', [], app()->getLocale()) ?: 'Documents'); ?>

<?php $__env->startSection('patient_banner'); ?>
    <?php echo $__env->make('partials.guardian-context-banner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('public.portal.documents_title', [], $l) ?: 'My Documents'); ?></h1>
        <p class="page-subtitle"><?php echo e(__('public.portal.documents_subtitle', [], $l) ?: 'Official documents and certificates issued to you.'); ?></p>
    </div>
</div>

<?php if(session('success')): ?>
<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div>
<?php endif; ?>
<?php if(session('warning')): ?>
<div class="alert alert-warning mb-4"><i data-lucide="alert-circle"></i><div><?php echo e(session('warning')); ?></div></div>
<?php endif; ?>

<?php if(!$patient): ?>
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="alert-circle"></i></div>
        <h3><?php echo e(__('public.portal.no_profile_found_title', [], $l) ?: 'No Patient Profile Found'); ?></h3>
        <p><?php echo e(__('public.portal.no_profile_found_desc', [], $l) ?: 'Your patient profile could not be loaded. Please contact support.'); ?></p>
    </div>
</div>
<?php elseif($documents->isEmpty()): ?>
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="file-text"></i></div>
        <h3><?php echo e(__('public.portal.no_documents_title', [], $l) ?: 'No Documents'); ?></h3>
        <p><?php echo e(__('public.portal.no_documents_desc', [], $l) ?: 'You have no official documents issued at this time.'); ?></p>
    </div>
</div>
<?php else: ?>
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="file-text"></i> <?php echo e(__('public.portal.panel_official_documents', [], $l) ?: 'Official Documents'); ?></h2>
        <span class="badge badge-primary"><?php echo e(method_exists($documents, 'total') ? $documents->total() : $documents->count()); ?></span>
    </div>
    <div class="table-wrapper">
        <table class="data-table" aria-label="<?php echo e(__('public.portal.panel_official_documents', [], $l) ?: 'Documents list'); ?>">
            <thead>
                <tr>
                    <th><?php echo e(__('public.portal.col_document', [], $l) ?: 'Document'); ?></th>
                    <th><?php echo e(__('public.portal.col_type', [], $l) ?: 'Type'); ?></th>
                    <th><?php echo e(__('public.portal.col_number', [], $l) ?: 'Number'); ?></th>
                    <th><?php echo e(__('public.portal.col_status', [], $l) ?: 'Status'); ?></th>
                    <th><?php echo e(__('public.portal.col_issued', [], $l) ?: 'Issued'); ?></th>
                    <th><?php echo e(__('public.portal.col_expires', [], $l) ?: 'Expires'); ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td data-label="<?php echo e(__('public.portal.col_document', [], $l) ?: 'Document'); ?>">
                        <span class="td-strong"><?php echo e($doc->title ?? __('public.portal.lbl_untitled_document', [], $l) ?: 'Untitled Document'); ?></span>
                    </td>
                    <td data-label="<?php echo e(__('public.portal.col_type', [], $l) ?: 'Type'); ?>">
                        <span class="td-muted"><?php echo e(\App\Support\Enums::label($doc->document_type)); ?></span>
                    </td>
                    <td data-label="<?php echo e(__('public.portal.col_number', [], $l) ?: 'Number'); ?>">
                        <span class="td-mono"><?php echo e($doc->document_number ?? '—'); ?></span>
                    </td>
                    <td data-label="<?php echo e(__('public.portal.col_status', [], $l) ?: 'Status'); ?>">
                        <?php
                            $statusCls = match($doc->status) { 'released' => 'badge-success', 'revoked' => 'badge-danger', default => 'badge-neutral' };
                        ?>
                        <span class="badge <?php echo e($statusCls); ?>"><?php echo e(\App\Support\Enums::label($doc->status)); ?></span>
                    </td>
                    <td data-label="<?php echo e(__('public.portal.col_issued', [], $l) ?: 'Issued'); ?>">
                        <span class="td-muted"><?php echo e($doc->issued_at?->format('d M Y') ?? '—'); ?></span>
                    </td>
                    <td data-label="<?php echo e(__('public.portal.col_expires', [], $l) ?: 'Expires'); ?>">
                        <span class="td-muted"><?php echo e($doc->expires_at?->format('d M Y') ?? (__('public.portal.lbl_no_expiry', [], $l) ?: 'No expiry')); ?></span>
                    </td>
                    <td class="row-actions">
                        <?php if($doc->status === 'released' && $doc->pdf_path): ?>
                                                <?php elseif($doc->status === 'released'): ?>
                        <span class="td-muted"><?php echo e(__('public.portal.lbl_processing', [], $l) ?: 'Processing…'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
    <?php if(method_exists($documents, 'links') && $documents->hasPages()): ?>
    <div class="panel-body">
        <?php echo e($documents->links()); ?>

    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\documents.blade.php ENDPATH**/ ?>