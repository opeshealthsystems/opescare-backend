<?php $__env->startSection('title', __('public.adm_cc_audit_title')); ?>
<?php echo $__env->make('portals.admin.control_center._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->startSection('breadcrumb_home', __('public.adm_cc_audit_breadcrumb_home')); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.admin')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.adm_cc_audit_breadcrumb_section')); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('public.adm_cc_audit_heading')); ?></h1>
        <p class="page-subtitle"><?php echo e(__('public.adm_cc_audit_subtitle')); ?></p>
    </div>
</div>

<div class="panel">
    <div class="panel-body panel-body--flush">
        <?php if($logs->count() === 0): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="scroll-text"></i></div>
                <h3><?php echo e(__('public.adm_cc_audit_empty_heading')); ?></h3>
                <p><?php echo e(__('public.adm_cc_audit_empty_body')); ?></p>
            </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th><?php echo e(__('public.adm_cc_audit_col_action')); ?></th>
                    <th><?php echo e(__('public.adm_cc_audit_col_resource')); ?></th>
                    <th><?php echo e(__('public.adm_cc_audit_col_resource_id')); ?></th>
                    <th><?php echo e(__('public.adm_cc_audit_col_actor')); ?></th>
                    <th><?php echo e(__('public.adm_cc_audit_col_ip')); ?></th>
                    <th><?php echo e(__('public.adm_cc_audit_col_when')); ?></th>
                    <th class="row-actions"></th>
                </tr></thead>
                <tbody>
                    <?php $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td data-label="<?php echo e(__('public.adm_cc_audit_col_action')); ?>"><span class="code-token"><?php echo e($log->action); ?></span></td>
                        <td data-label="<?php echo e(__('public.adm_cc_audit_col_resource')); ?>">
                            <?php if($log->resource_type): ?>
                                <span class="badge badge-neutral badge-sm"><?php echo e($log->resource_type); ?></span>
                            <?php else: ?>
                                <span class="td-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="<?php echo e(__('public.adm_cc_audit_col_resource_id')); ?>" class="td-muted">
                            <?php if($log->resource_id): ?>
                                <span class="code-muted"><?php echo e(Str::limit($log->resource_id, 12)); ?></span>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td data-label="<?php echo e(__('public.adm_cc_audit_col_actor')); ?>"><?php echo e($log->actor_id); ?></td>
                        <td data-label="<?php echo e(__('public.adm_cc_audit_col_ip')); ?>" class="td-muted"><?php echo e($log->ip_address ?? '—'); ?></td>
                        <td data-label="<?php echo e(__('public.adm_cc_audit_col_when')); ?>" class="td-muted">
                            <?php echo e(\Carbon\Carbon::parse($log->occurred_at)->format('M d, Y H:i')); ?>

                            <div class="code-muted"><?php echo e(\Carbon\Carbon::parse($log->occurred_at)->diffForHumans()); ?></div>
                        </td>
                        <td class="row-actions">
                            <?php if($log->after || $log->before): ?>
                                <button type="button" class="icon-btn" aria-label="<?php echo e(__('public.aria_view_diff')); ?>"
                                    onclick="showDiff(<?php echo e(json_encode($log->before)); ?>, <?php echo e(json_encode($log->after)); ?>, '<?php echo e(addslashes($log->action)); ?>')">
                                    <i data-lucide="eye"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>

        
        <?php if(method_exists($logs, 'links')): ?>
        <div class="panel-footer">
            <?php echo e($logs->links()); ?>

        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>


<div id="diff-modal" class="modal-fixed">
    <div class="modal-fixed__panel">
        <div class="modal-fixed__head">
            <h3 id="diff-title" class="modal-fixed__title"></h3>
            <button type="button" class="icon-btn" aria-label="<?php echo e(__('public.aria_close')); ?>" onclick="closeDiff()">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="diff-grid">
            <div>
                <div class="diff-label"><?php echo e(__('public.adm_cc_audit_diff_label_before')); ?></div>
                <pre id="diff-before" class="diff-pre"></pre>
            </div>
            <div>
                <div class="diff-label"><?php echo e(__('public.adm_cc_audit_diff_label_after')); ?></div>
                <pre id="diff-after" class="diff-pre"></pre>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
    var _ag = {
        diffTitlePrefix: <?php echo json_encode(__('public.adm_cc_audit_js_diff_title_prefix'), 15, 512) ?>,
        empty: <?php echo json_encode(__('public.adm_cc_audit_js_empty'), 15, 512) ?>,
    };
    function showDiff(before, after, action) {
        document.getElementById('diff-title').textContent = _ag.diffTitlePrefix + action;
        document.getElementById('diff-before').textContent = before ? JSON.stringify(before, null, 2) : _ag.empty;
        document.getElementById('diff-after').textContent  = after  ? JSON.stringify(after,  null, 2) : _ag.empty;
        document.getElementById('diff-modal').classList.add('open');
    }
    function closeDiff() { document.getElementById('diff-modal').classList.remove('open'); }
    document.getElementById('diff-modal').addEventListener('click', function(e) { if (e.target === this) closeDiff(); });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\admin\control_center\audit_log.blade.php ENDPATH**/ ?>