<?php $__env->startSection('title', __('public.adm_appt_page_title')); ?>
<?php echo $__env->make('portals.admin.control_center._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->startSection('breadcrumb_home', __('public.adm_breadcrumb_home')); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.admin')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.adm_appt_breadcrumb')); ?>
<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('public.adm_appt_page_title')); ?></h1>
        <p class="page-subtitle"><?php echo e(__('public.adm_appt_subtitle')); ?></p>
    </div>
</div>

<?php if(session('success')): ?><div class="auth-alert auth-alert-success" style="margin-bottom:1rem;"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div><?php endif; ?>
<?php if(session('error')): ?><div class="auth-alert auth-alert-danger" style="margin-bottom:1rem;"><i data-lucide="alert-circle"></i><div><?php echo e(session('error')); ?></div></div><?php endif; ?>

<div class="kpi-grid" style="margin-bottom:1.5rem;">
    <div class="kpi-card"><div class="kpi-icon blue"><i data-lucide="calendar"></i></div><div class="kpi-body"><div class="kpi-label"><?php echo e(__('public.adm_appt_kpi_total')); ?></div><div class="kpi-value"><?php echo e($stats['total'] ?? 0); ?></div></div></div>
    <div class="kpi-card"><div class="kpi-icon teal"><i data-lucide="check-circle"></i></div><div class="kpi-body"><div class="kpi-label"><?php echo e(__('public.adm_appt_kpi_confirmed')); ?></div><div class="kpi-value text-teal"><?php echo e($stats['confirmed'] ?? 0); ?></div></div></div>
    <div class="kpi-card"><div class="kpi-icon danger"><i data-lucide="ban"></i></div><div class="kpi-body"><div class="kpi-label"><?php echo e(__('public.adm_appt_kpi_cancelled')); ?></div><div class="kpi-value text-danger"><?php echo e($stats['cancelled'] ?? 0); ?></div></div></div>
    <div class="kpi-card"><div class="kpi-icon warning"><i data-lucide="user-x"></i></div><div class="kpi-body"><div class="kpi-label"><?php echo e(__('public.adm_appt_kpi_noshow')); ?></div><div class="kpi-value text-warning"><?php echo e($stats['no_show'] ?? 0); ?></div></div></div>
</div>

<form method="GET" action="<?php echo e(route('portals.admin.appointments.index')); ?>" class="panel" style="padding:1rem;margin-bottom:1rem;">
    <div style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:1;min-width:180px;"><label class="filter-label"><?php echo e(__('public.adm_appt_filter_search_lbl')); ?></label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="<?php echo e(__('public.adm_appt_filter_search_ph')); ?>" value="<?php echo e(request('search')); ?>">
        </div>
        <div><label class="filter-label"><?php echo e(__('public.adm_appt_filter_status_lbl')); ?></label>
            <select name="status" class="form-control form-control-sm">
                <option value=""><?php echo e(__('public.adm_appt_opt_all')); ?></option>
                <?php $__currentLoopData = ['scheduled','confirmed','cancelled','no_show','completed']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($s); ?>" <?php if(request('status')===$s): echo 'selected'; endif; ?>><?php echo e(\App\Support\Enums::label($s)); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div><label class="filter-label"><?php echo e(__('public.adm_appt_filter_facility_lbl')); ?></label>
            <select name="facility_id" class="form-control form-control-sm">
                <option value=""><?php echo e(__('public.adm_appt_opt_all_facilities')); ?></option>
                <?php $__currentLoopData = $facilities??[]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $facility): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($facility->id); ?>" <?php if(request('facility_id')==$facility->id): echo 'selected'; endif; ?>><?php echo e($facility->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div><label class="filter-label"><?php echo e(__('public.adm_appt_filter_from_lbl')); ?></label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo e(request('date_from')); ?>">
        </div>
        <div><label class="filter-label"><?php echo e(__('public.adm_appt_filter_to_lbl')); ?></label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo e(request('date_to')); ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><?php echo e(__('public.adm_appt_btn_filter')); ?></button>
        <a href="<?php echo e(route('portals.admin.appointments.index')); ?>" class="btn btn-ghost btn-sm"><?php echo e(__('public.adm_appt_btn_reset')); ?></a>
    </div>
</form>

<div class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?php echo e(__('public.adm_appt_col_patient')); ?></th>
                    <th><?php echo e(__('public.adm_appt_col_facility')); ?></th>
                    <th><?php echo e(__('public.adm_appt_col_type')); ?></th>
                    <th><?php echo e(__('public.adm_appt_col_scheduled_at')); ?></th>
                    <th><?php echo e(__('public.adm_appt_col_status')); ?></th>
                    <th><?php echo e(__('public.adm_appt_col_provider')); ?></th>
                    <th style="text-align:right;"><span class="sr-only"><?php echo e(__('public.adm_appt_col_actions')); ?></span></th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $appointments??[]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $appointment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                $sBadge=match($appointment->status??''){'scheduled'=>'badge-neutral','confirmed'=>'badge-success','cancelled'=>'badge-danger','no_show'=>'badge-warning','completed'=>'badge-primary',default=>'badge-neutral'};
                ?>
                <tr>
                    <td>
                        <div style="font-weight:600;"><?php echo e($appointment->patient?->full_name ?? '—'); ?></div>
                        <div style="font-size:.78rem;font-family:monospace;color:var(--p-text-muted);"><?php echo e($appointment->patient?->health_id ?? ''); ?></div>
                    </td>
                    <td style="font-size:.85rem;"><?php echo e($appointment->facility?->name ?? '—'); ?></td>
                    <td style="font-size:.85rem;"><?php echo e(ucfirst(str_replace('_',' ',$appointment->type??'—'))); ?></td>
                    <td>
                        <div style="font-size:.85rem;"><?php echo e($appointment->scheduled_at?->format('d M Y') ?? '—'); ?></div>
                        <div style="font-size:.75rem;color:var(--p-text-muted);"><?php echo e($appointment->scheduled_at?->format('H:i') ?? ''); ?></div>
                    </td>
                    <td><span class="badge <?php echo e($sBadge); ?>"><?php echo e(\App\Support\Enums::label($appointment->status ?? '—')); ?></span></td>
                    <td style="font-size:.85rem;"><?php echo e($appointment->provider?->full_name ?? '—'); ?></td>
                    <td style="text-align:right;">
                        <div style="display:flex;gap:.35rem;justify-content:flex-end;">
                            <a href="<?php echo e(route('portals.admin.appointments.show', $appointment)); ?>" class="btn btn-ghost btn-xs" aria-label="<?php echo e(__('public.aria_view_appointment')); ?>" title="<?php echo e(__('admin_extra.title_view', [], app()->getLocale()) ?: 'View'); ?>"><i data-lucide="eye"></i></a>
                            <?php if(!in_array($appointment->status??'',['cancelled','completed'])): ?>
                            <button onclick="openCancelModal('<?php echo e($appointment->id); ?>','<?php echo e(route('portals.admin.appointments.cancel',$appointment)); ?>')" class="btn btn-warning btn-xs"><i data-lucide="ban"></i></button>
                            <?php endif; ?>
                            <form method="POST" action="<?php echo e(route('portals.admin.appointments.destroy',$appointment)); ?>" onsubmit="return confirm('<?php echo e(addslashes(__('public.adm_appt_js_confirm_delete'))); ?>')">
                                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="btn btn-danger btn-xs"><i data-lucide="trash-2"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--p-text-muted);"><?php echo e(__('public.adm_appt_empty')); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if(isset($appointments) && $appointments->hasPages()): ?>
    <div style="padding:.75rem 1.25rem;"><?php echo e($appointments->withQueryString()->links()); ?></div>
    <?php endif; ?>
</div>


<div id="cancel-modal" style="display:none;position:fixed;inset:0;z-index:60;align-items:center;justify-content:center;background:rgba(15,23,42,.7);backdrop-filter:blur(4px);padding:1rem;" role="dialog">
    <div style="background:var(--p-surface);border:1px solid var(--p-border);border-radius:var(--p-radius-xl);width:100%;max-width:480px;overflow:hidden;box-shadow:var(--p-shadow-lg);">
        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid var(--p-border);display:flex;justify-content:space-between;align-items:center;">
            <h3 style="margin:0;font-size:1rem;font-weight:700;"><?php echo e(__('public.adm_appt_modal_cancel_title')); ?></h3>
            <button onclick="document.getElementById('cancel-modal').style.display='none'" class="topbar-icon-btn"><i data-lucide="x"></i></button>
        </div>
        <form method="POST" id="cancel-form" action="">
            <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
            <div style="padding:1.5rem;">
                <div class="form-group">
                    <label class="form-label"><?php echo e(__('public.adm_appt_modal_cancel_reason_lbl')); ?> <span style="color:var(--p-danger);">*</span></label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="<?php echo e(__('public.adm_appt_modal_cancel_reason_ph')); ?>" required></textarea>
                </div>
            </div>
            <div style="padding:1rem 1.5rem;border-top:1px solid var(--p-border);display:flex;gap:.75rem;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('cancel-modal').style.display='none'" class="btn btn-ghost"><?php echo e(__('public.adm_appt_modal_close')); ?></button>
                <button type="submit" class="btn btn-danger"><i data-lucide="ban"></i> <?php echo e(__('public.adm_appt_modal_confirm_cancel')); ?></button>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
<script>
function openCancelModal(id, url) {
    document.getElementById('cancel-form').action = url;
    document.getElementById('cancel-form').querySelector('textarea').value = '';
    document.getElementById('cancel-modal').style.display = 'flex';
}
document.addEventListener('keydown',e=>{if(e.key==='Escape'){document.querySelectorAll('[id$="-modal"]').forEach(m=>m.style.display='none');}});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\admin\appointments\index.blade.php ENDPATH**/ ?>