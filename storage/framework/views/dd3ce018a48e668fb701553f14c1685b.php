<?php $__env->startSection('title', __('public.adm_cc_mnt_title')); ?>
<?php echo $__env->make('portals.admin.control_center._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->startSection('breadcrumb_home', __('public.adm_cc_mnt_breadcrumb_home')); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.admin')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.adm_cc_mnt_breadcrumb_section')); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('public.adm_cc_mnt_heading')); ?></h1>
        <p class="page-subtitle"><?php echo e(__('public.adm_cc_mnt_subtitle')); ?></p>
    </div>
    <div class="page-head__spacer"></div>
    <button type="button" class="btn btn-primary btn-sm" onclick="openCreateModal()">
        <i data-lucide="plus"></i> <?php echo e(__('public.adm_cc_mnt_btn_schedule')); ?>

    </button>
</div>

<?php if(session('success')): ?>
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div><?php echo e(session('error')); ?></div></div>
<?php endif; ?>


<?php $active = $windows->firstWhere('is_active', true); ?>
<?php if($active && $active->isCurrentlyActive()): ?>
<div class="banner banner--danger">
    <i data-lucide="wrench"></i>
    <div>
        <strong><?php echo e(__('public.adm_cc_mnt_banner_active')); ?></strong> <?php echo e($active->title); ?>

        <span class="td-muted text-sm">Until <?php echo e(\Carbon\Carbon::parse($active->ends_at)->format('M d, Y H:i')); ?></span>
    </div>
</div>
<?php endif; ?>

<div class="panel">
    <div class="panel-body panel-body--flush">
        <?php if($windows->count() === 0): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="wrench"></i></div>
                <h3><?php echo e(__('public.adm_cc_mnt_empty_heading')); ?></h3>
                <p><?php echo e(__('public.adm_cc_mnt_empty_body')); ?></p>
            </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th><?php echo e(__('public.adm_cc_mnt_col_title')); ?></th><th><?php echo e(__('public.adm_cc_mnt_col_starts')); ?></th><th><?php echo e(__('public.adm_cc_mnt_col_ends')); ?></th><th><?php echo e(__('public.adm_cc_mnt_col_status')); ?></th><th><?php echo e(__('public.adm_cc_mnt_col_emergency')); ?></th><th><?php echo e(__('public.adm_cc_mnt_col_created_by')); ?></th><th><?php echo e(__('public.adm_cc_mnt_col_actions')); ?></th>
                </tr></thead>
                <tbody>
                    <?php $__currentLoopData = $windows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $win): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $now = now();
                        $starts = \Carbon\Carbon::parse($win->starts_at);
                        $ends   = \Carbon\Carbon::parse($win->ends_at);
                        $live   = $win->is_active && $now->between($starts, $ends);
                        $upcoming = $win->is_active && $now->lt($starts);
                    ?>
                    <tr>
                        <td data-label="Title" class="td-strong">
                            <?php echo e($win->title); ?>

                            <?php if($win->message): ?>
                                <div class="td-muted text-sm"><?php echo e(Str::limit($win->message, 60)); ?></div>
                            <?php endif; ?>
                        </td>
                        <td data-label="Starts"><?php echo e($starts->format('M d, Y H:i')); ?></td>
                        <td data-label="Ends"><?php echo e($ends->format('M d, Y H:i')); ?></td>
                        <td data-label="Status">
                            <?php if($live): ?>
                                <span class="badge badge-danger badge-sm"><?php echo e(__('public.adm_cc_mnt_badge_live')); ?></span>
                            <?php elseif($upcoming): ?>
                                <span class="badge badge-warning badge-sm"><?php echo e(__('public.adm_cc_mnt_badge_upcoming')); ?></span>
                            <?php elseif(!$win->is_active): ?>
                                <span class="badge badge-neutral badge-sm"><?php echo e(__('public.adm_cc_mnt_badge_inactive')); ?></span>
                            <?php else: ?>
                                <span class="badge badge-neutral badge-sm"><?php echo e(__('public.adm_cc_mnt_badge_expired')); ?></span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Emergency Access">
                            <?php if($win->allow_emergency_access): ?>
                                <span class="badge badge-success badge-sm"><?php echo e(__('public.adm_cc_mnt_badge_allowed')); ?></span>
                            <?php else: ?>
                                <span class="badge badge-neutral badge-sm"><?php echo e(__('public.adm_cc_mnt_badge_blocked')); ?></span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Created By" class="td-muted"><?php echo e($win->created_by ?? '—'); ?></td>
                        <td class="row-actions">
                            <form method="POST" action="<?php echo e(route('portals.admin.cc.maintenance.toggle', $win->id)); ?>" class="inline-form">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="active" value="<?php echo e($win->is_active ? '0' : '1'); ?>">
                                <label class="switch">
                                    <input type="checkbox" <?php echo e($win->is_active ? 'checked' : ''); ?> onchange="this.form.submit()" aria-label="Toggle <?php echo e($win->title); ?>">
                                    <span class="switch__track"></span>
                                </label>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>


<div id="create-modal" class="modal-fixed">
    <div class="modal-fixed__panel modal-fixed__panel--md">
        <div class="modal-fixed__head">
            <h3 class="modal-fixed__title"><?php echo e(__('public.adm_cc_mnt_modal_heading')); ?></h3>
            <button type="button" class="icon-btn" aria-label="<?php echo e(__('public.aria_close')); ?>" onclick="closeCreateModal()"><i data-lucide="x"></i></button>
        </div>
        <form method="POST" action="<?php echo e(route('portals.admin.cc.maintenance.store')); ?>">
            <?php echo csrf_field(); ?>
            <div class="form-group mb-3">
                <label class="form-label form-label-required"><?php echo e(__('public.adm_cc_mnt_modal_lbl_title')); ?></label>
                <input type="text" name="title" class="form-control" required maxlength="150" placeholder="<?php echo e(__('public.adm_cc_mnt_modal_ph_title')); ?>">
            </div>
            <div class="form-group mb-3">
                <label class="form-label"><?php echo e(__('public.adm_cc_mnt_modal_lbl_message')); ?> <span class="td-muted"><?php echo e(__('public.adm_cc_mnt_modal_lbl_message_hint')); ?></span></label>
                <textarea name="message" class="form-control" rows="2" maxlength="500" placeholder="<?php echo e(__('public.adm_cc_mnt_modal_ph_message')); ?>"></textarea>
            </div>
            <div class="form-row mb-3">
                <div class="form-group">
                    <label class="form-label form-label-required"><?php echo e(__('public.adm_cc_mnt_modal_lbl_start')); ?></label>
                    <input type="datetime-local" name="starts_at" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required"><?php echo e(__('public.adm_cc_mnt_modal_lbl_end')); ?></label>
                    <input type="datetime-local" name="ends_at" class="form-control" required>
                </div>
            </div>
            <div class="form-group mb-4">
                <label class="form-check">
                    <input type="checkbox" name="allow_emergency_access" value="1">
                    <?php echo e(__('public.adm_cc_mnt_modal_allow_emergency')); ?>

                </label>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeCreateModal()"><?php echo e(__('public.adm_cc_mnt_modal_btn_cancel')); ?></button>
                <button type="submit" class="btn btn-primary btn-sm"><?php echo e(__('public.adm_cc_mnt_modal_btn_schedule')); ?></button>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
    function openCreateModal()  { document.getElementById('create-modal').classList.add('open'); }
    function closeCreateModal() { document.getElementById('create-modal').classList.remove('open'); }
    document.getElementById('create-modal').addEventListener('click', function(e) { if (e.target === this) closeCreateModal(); });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\admin\control_center\maintenance.blade.php ENDPATH**/ ?>