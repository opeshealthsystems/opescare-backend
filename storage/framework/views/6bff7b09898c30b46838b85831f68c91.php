<?php $__env->startSection('title', $certification->integration_name . ' — Certification'); ?>
<?php $__env->startSection('sidebar'); ?> <?php echo $__env->make('portals.admin.control_center._sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="breadcrumb">
    <a href="<?php echo e(route('portals.admin.certifications.index')); ?>"><?php echo e(__('public.adm_cert_show_breadcrumb_parent')); ?></a>
    <i data-lucide="chevron-right"></i>
    <span><?php echo e($certification->integration_name); ?></span>
</div>

<div class="entity-head">
    <div class="entity-head__icon"><i data-lucide="shield-check"></i></div>
    <div>
        <h2 class="entity-head__title"><?php echo e($certification->integration_name); ?></h2>
        <div class="entity-head__sub">
            <span class="badge <?php echo e($certification->statusBadgeClass()); ?> badge-sm"><?php echo e(\App\Support\Enums::label($certification->status)); ?></span>
            <span class="badge badge--info badge-sm"><?php echo e(strtoupper($certification->integration_type)); ?></span>
            <?php if($certification->certification_level): ?>
            <span class="badge <?php echo e($certification->levelBadgeClass()); ?> badge-sm"><?php echo e(ucfirst($certification->certification_level)); ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if(session('success')): ?><div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div><?php endif; ?>
<?php if(session('error')): ?><div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div><?php echo e(session('error')); ?></div></div><?php endif; ?>

<div class="grid-2">

    
    <div>
        
        <div class="panel mb-6">
            <div class="panel-header"><h3 class="panel-title"><i data-lucide="info"></i> <?php echo e(__('public.adm_cert_show_panel_details')); ?></h3></div>
            <div class="panel-body">
                <table class="kv-table">
                    <tr><td><?php echo e(__('public.adm_cert_show_kv_vendor')); ?></td><td class="kv-strong"><?php echo e($certification->vendor_name ?? '—'); ?></td></tr>
                    <tr><td><?php echo e(__('public.adm_cert_show_kv_contact')); ?></td><td class="kv-strong"><?php echo e($certification->vendor_contact ?? '—'); ?></td></tr>
                    <tr><td><?php echo e(__('public.adm_cert_show_kv_version')); ?></td><td class="kv-strong"><?php echo e($certification->version ?? '—'); ?></td></tr>
                    <tr><td><?php echo e(__('public.adm_cert_show_kv_submitted')); ?></td><td class="kv-strong"><?php echo e($certification->submitted_at?->format('d M Y') ?? '—'); ?></td></tr>
                    <tr><td><?php echo e(__('public.adm_cert_show_kv_certified')); ?></td><td class="kv-strong"><?php echo e($certification->certified_at?->format('d M Y') ?? '—'); ?></td></tr>
                    <tr><td><?php echo e(__('public.adm_cert_show_kv_expires')); ?></td><td class="kv-strong">
                        <?php echo e($certification->expires_at?->format('d M Y') ?? __('public.adm_cert_show_kv_no_expiry')); ?>

                        <?php if($certification->isExpiringSoon()): ?>
                            <span class="badge badge-warning badge-sm"><i data-lucide="alert-triangle"></i> <?php echo e(__('public.adm_cert_show_kv_expiring_soon')); ?></span>
                        <?php endif; ?>
                    </td></tr>
                </table>
                <?php if($certification->scope_description): ?>
                <div class="detail-divider">
                    <div class="kv-strong mb-3"><?php echo e(__('public.adm_cert_show_kv_scope')); ?></div>
                    <div class="td-muted"><?php echo e($certification->scope_description); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        
        <?php if($certification->badge): ?>
        <div class="panel">
            <div class="panel-header"><h3 class="panel-title"><i data-lucide="award"></i> <?php echo e(__('public.adm_cert_show_panel_active_badge')); ?></h3></div>
            <div class="panel-body">
                <div class="badge-showcase">
                    <i data-lucide="<?php echo e($certification->badge->levelIcon()); ?>" class="badge-showcase__icon" style="color: <?php echo e($certification->badge->levelColor()); ?>;"></i>
                    <div class="badge-showcase__level"><?php echo e(ucfirst($certification->badge->certification_level)); ?></div>
                    <div class="mono code-token"><?php echo e($certification->badge->badge_code); ?></div>
                    <div class="td-muted text-sm">
                        <?php echo e(__('public.adm_cert_show_badge_issued')); ?> <?php echo e($certification->badge->issued_at->format('d M Y')); ?>

                        <?php if($certification->badge->expires_at): ?> <?php echo e(__('public.adm_cert_show_badge_expires')); ?> <?php echo e($certification->badge->expires_at->format('d M Y')); ?> <?php endif; ?>
                    </div>
                </div>
                <form method="POST" action="<?php echo e(route('portals.admin.certifications.badge.revoke', $certification->badge)); ?>"
                      class="mt-6" onsubmit="return confirm(<?php echo e(Illuminate\Support\Js::from(__('public.adm_cert_show_js_confirm_revoke'))); ?>)">
                    <?php echo csrf_field(); ?>
                    <div class="form-group mb-3">
                        <input type="text" name="revoke_reason" class="form-control" placeholder="<?php echo e(__('public.adm_cert_show_ph_revoke_reason')); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-danger btn-block"><?php echo e(__('public.adm_cert_show_btn_revoke')); ?></button>
                </form>
            </div>
        </div>
        <?php elseif($certification->isPassed()): ?>
        <div class="panel">
            <div class="panel-header"><h3 class="panel-title"><i data-lucide="award"></i> <?php echo e(__('public.adm_cert_show_panel_issue_badge')); ?></h3></div>
            <div class="panel-body">
                <form method="POST" action="<?php echo e(route('portals.admin.certifications.badge.issue', $certification)); ?>">
                    <?php echo csrf_field(); ?>
                    <div class="form-group mb-3">
                        <label class="form-label"><?php echo e(__('public.adm_cert_show_lbl_level')); ?></label>
                        <select name="certification_level" class="form-control" required>
                            <option value="bronze"><?php echo e(\App\Support\Enums::label('bronze', 'level')); ?></option>
                            <option value="silver"><?php echo e(\App\Support\Enums::label('silver', 'level')); ?></option>
                            <option value="gold"><?php echo e(\App\Support\Enums::label('gold', 'level')); ?></option>
                            <option value="platinum"><?php echo e(\App\Support\Enums::label('platinum', 'level')); ?></option>
                        </select>
                    </div>
                    <div class="form-group mb-4">
                        <label class="form-label"><?php echo e(__('public.adm_cert_show_lbl_valid_months')); ?></label>
                        <input type="number" name="expires_months" value="12" min="1" max="36" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><?php echo e(__('public.adm_cert_show_btn_issue')); ?></button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    
    <div>
        
        <div class="panel mb-6">
            <div class="panel-header"><h3 class="panel-title"><i data-lucide="clipboard-check"></i> <?php echo e(__('public.adm_cert_show_panel_record_run')); ?></h3></div>
            <div class="panel-body">
                <?php if($requirements->isEmpty()): ?>
                <p class="td-muted">
                    <?php echo e(__('public.adm_cert_show_no_requirements')); ?>

                    <a href="<?php echo e(route('portals.admin.certifications.seed')); ?>" onclick="this.closest('form')?.submit();return false;"><?php echo e(__('public.adm_cert_show_seed_link')); ?></a> <?php echo e(__('public.adm_cert_show_seed_first')); ?>

                </p>
                <?php else: ?>
                <form method="POST" action="<?php echo e(route('portals.admin.certifications.test_run', $certification)); ?>">
                    <?php echo csrf_field(); ?>
                    <div class="form-group mb-3">
                        <label class="form-label"><?php echo e(__('public.adm_cert_show_lbl_run_label')); ?></label>
                        <input type="text" name="run_label" class="form-control" placeholder="<?php echo e(__('public.adm_cert_show_ph_run_label')); ?>">
                    </div>
                    <div class="scroll-table mb-3">
                        <table class="data-table">
                            <thead><tr>
                                <th><?php echo e(__('public.adm_cert_show_col_requirement')); ?></th>
                                <th><?php echo e(__('public.adm_cert_show_col_result')); ?></th>
                            </tr></thead>
                            <tbody>
                            <?php $__currentLoopData = $requirements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $req): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td data-label="<?php echo e(__('public.adm_cert_show_col_requirement')); ?>">
                                    <input type="hidden" name="results[<?php echo e($i); ?>][requirement_id]" value="<?php echo e($req->id); ?>">
                                    <span class="td-strong"><?php echo e($req->name); ?></span>
                                    <span class="badge <?php echo e($req->severityBadgeClass()); ?> badge-sm"><?php echo e($req->severity); ?></span>
                                </td>
                                <td data-label="<?php echo e(__('public.adm_cert_show_col_result')); ?>">
                                    <select name="results[<?php echo e($i); ?>][result]" class="form-control">
                                        <option value="passed"><?php echo e(__('public.adm_cert_show_opt_pass')); ?></option>
                                        <option value="failed"><?php echo e(__('public.adm_cert_show_opt_fail')); ?></option>
                                        <option value="skipped"><?php echo e(__('public.adm_cert_show_opt_skip')); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label"><?php echo e(__('public.adm_cert_show_lbl_run_notes')); ?></label>
                        <textarea name="run_notes" rows="2" class="form-control"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><?php echo e(__('public.adm_cert_show_btn_record')); ?></button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        
        <?php if($certification->testRuns->isNotEmpty()): ?>
        <div class="panel">
            <div class="panel-header"><h3 class="panel-title"><i data-lucide="history"></i> <?php echo e(__('public.adm_cert_show_panel_history')); ?></h3></div>
            <div class="panel-body panel-body--flush">
                <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr>
                        <th><?php echo e(__('public.adm_cert_show_col_run')); ?></th><th><?php echo e(__('public.adm_cert_show_col_pass_rate')); ?></th><th><?php echo e(__('public.adm_cert_show_col_status')); ?></th><th><?php echo e(__('public.adm_cert_show_col_date')); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php $__currentLoopData = $certification->testRuns->sortByDesc('started_at'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $run): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td data-label="<?php echo e(__('public.adm_cert_show_col_run')); ?>"><?php echo e($run->run_label ?: __('public.adm_cert_show_run_default')); ?></td>
                        <td data-label="<?php echo e(__('public.adm_cert_show_col_pass_rate')); ?>">
                            <span class="badge <?php echo e($run->isPassed() ? 'badge-success' : 'badge-danger'); ?> badge-sm"><?php echo e($run->passRate()); ?>%</span>
                            <span class="td-muted code-muted">(<?php echo e($run->passed_count); ?>/<?php echo e($run->total_requirements); ?>)</span>
                        </td>
                        <td data-label="<?php echo e(__('public.adm_cert_show_col_status')); ?>"><span class="badge <?php echo e($run->statusBadgeClass()); ?> badge-sm"><?php echo e(\App\Support\Enums::label($run->status)); ?></span></td>
                        <td data-label="<?php echo e(__('public.adm_cert_show_col_date')); ?>" class="td-muted"><?php echo e($run->started_at?->format('d M Y')); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\admin\certifications\show.blade.php ENDPATH**/ ?>