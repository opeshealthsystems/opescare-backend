<?php $__env->startSection('title', __('public.staff_portal.page_heading_search', [], app()->getLocale()) ?: 'Global Search'); ?>
<?php $__env->startSection('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()) ?: 'Staff Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.staff')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.staff_portal.breadcrumb_section_search', [], app()->getLocale()) ?: 'Global Search'); ?>

<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('public.staff_portal.page_heading_search', [], $l) ?: 'Global Search'); ?></h1>
        <p class="page-subtitle"><?php echo e(__('public.staff_portal.page_subtitle_search', [], $l) ?: 'Search across patients, facilities, medicines, lab tests, documents, and more.'); ?></p>
    </div>
</div>


<form method="GET" class="filter-bar">
    <label class="filter-search">
        <i data-lucide="search"></i>
        <input type="text" name="q" value="<?php echo e($query); ?>"
            placeholder="<?php echo e(__('public.staff_portal.ph_search_global', [], $l) ?: 'Search patients, medicines, facilities, documents…'); ?>"
            aria-label="<?php echo e(__('public.staff_portal.search_empty_title', [], $l) ?: 'Search the platform'); ?>"
            autofocus>
    </label>
    <button type="submit" class="btn btn-primary btn-sm"><?php echo e(__('public.staff_portal.btn_search', [], $l) ?: 'Search'); ?></button>
    <?php if($query): ?>
            <?php endif; ?>
</form>

<?php if($query === ''): ?>
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="search"></i></div>
        <h3><?php echo e(__('public.staff_portal.search_empty_title', [], $l) ?: 'Search the platform'); ?></h3>
        <p><?php echo e(__('public.staff_portal.search_empty_desc', [], $l) ?: 'Enter a keyword above to search across facilities, medicines, lab tests, documents, and partners.'); ?></p>
        <div class="row-actions-inline mt-6">
            <?php $__currentLoopData = [
                __('public.staff_portal.search_cat_facilities', [], $l) ?: 'Facilities',
                __('public.staff_portal.search_cat_medicines', [], $l) ?: 'Medicines',
                __('public.staff_portal.search_cat_lab_tests', [], $l) ?: 'Lab Tests',
                __('public.staff_portal.search_cat_documents', [], $l) ?: 'Documents',
                __('public.staff_portal.search_cat_partners', [], $l) ?: 'Partners',
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <span class="badge badge-neutral"><?php echo e($cat); ?></span>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>

<?php elseif($total === 0): ?>
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="search-x"></i></div>
        <h3><?php echo e(__('public.staff_portal.search_no_results_title', [], $l) ?: 'No results for'); ?> "<?php echo e($query); ?>"</h3>
        <p><?php echo e(__('public.staff_portal.search_no_results_desc', [], $l) ?: 'Try different keywords or check your spelling.'); ?></p>
    </div>

<?php else: ?>
    <div class="filter-bar">
        <span class="td-muted">
            <strong class="kv-strong"><?php echo e($total); ?></strong> <?php echo e(__('public.staff_portal.lbl_results_for', [], $l) ?: 'result(s) for'); ?>

            <strong class="kv-strong">"<?php echo e($query); ?>"</strong>
        </span>
        <?php $__currentLoopData = $counts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type => $cnt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <span class="badge badge-neutral badge-sm"><?php echo e(ucfirst(str_replace('_',' ',$type))); ?>: <?php echo e($cnt); ?></span>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <?php
        $typeIcon = [
            'patient'   => 'user',
            'facility'  => 'building-2',
            'document'  => 'file-text',
            'medicine'  => 'pill',
            'lab_test'  => 'flask-conical',
            'partner'   => 'handshake',
            'message'   => 'message-square',
        ];
        $typeBadge = [
            'patient'   => 'badge-primary',
            'facility'  => 'badge-neutral',
            'document'  => 'badge-warning',
            'medicine'  => 'badge-success',
            'lab_test'  => 'badge-neutral',
            'partner'   => 'badge-neutral',
            'message'   => 'badge-neutral',
        ];
    ?>

    <?php $__currentLoopData = $grouped; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type => $items): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="panel mb-4">
        <div class="panel-header">
            <h3 class="panel-title">
                <i data-lucide="<?php echo e($typeIcon[$type] ?? 'circle'); ?>"></i>
                <?php echo e(ucfirst($type)); ?>

                <span class="badge badge-neutral badge-sm"><?php echo e($items->count()); ?></span>
            </h3>
        </div>
        <div class="panel-body panel-body--flush">
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr>
                        <th><?php echo e(__('public.staff_portal.col_title', [], $l) ?: 'Title'); ?></th>
                        <th><?php echo e(__('public.staff_portal.col_details', [], $l) ?: 'Details'); ?></th>
                        <th><?php echo e(__('public.staff_portal.col_metadata', [], $l) ?: 'Metadata'); ?></th>
                    </tr></thead>
                    <tbody>
                        <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td data-label="<?php echo e(__('public.staff_portal.col_title', [], $l) ?: 'Title'); ?>">
                                <span class="cell-with-icon">
                                    <i data-lucide="<?php echo e($typeIcon[$type] ?? 'circle'); ?>"></i>
                                    <span class="td-strong"><?php echo e($item['title']); ?></span>
                                </span>
                            </td>
                            <td data-label="<?php echo e(__('public.staff_portal.col_details', [], $l) ?: 'Details'); ?>" class="td-muted"><?php echo e($item['subtitle'] ?? '—'); ?></td>
                            <td data-label="<?php echo e(__('public.staff_portal.col_metadata', [], $l) ?: 'Metadata'); ?>">
                                <?php $__currentLoopData = $item['metadata'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mk => $mv): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php if($mv !== null && $mv !== ''): ?>
                                        <span class="td-muted"><?php echo e($mk); ?>:</span>
                                        <code class="mono"><?php echo e(is_array($mv) ? json_encode($mv) : $mv); ?></code>
                                    <?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\staff\search.blade.php ENDPATH**/ ?>