<?php $__env->startSection('title', __('team.join_page_title') . ' — OpesCare'); ?>

<?php $__env->startSection('breadcrumb_home', __('professional.page_title')); ?>
<?php $__env->startSection('breadcrumb_home_url', route('professional.profile')); ?>
<?php $__env->startSection('breadcrumb_section', __('team.join_page_title')); ?>

<?php $__env->startSection('content'); ?>

<?php
    /* A role label may legitimately fall outside the invitable allow-list, so
       fall back to the raw name rather than printing a translation key. */
    $roleLabel = function (?string $name): string {
        if (! $name) {
            return '—';
        }

        $label = __('team.roles.' . $name);

        return is_string($label) && $label !== 'team.roles.' . $name ? $label : $name;
    };
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('team.join_page_title')); ?></h1>
        <p class="page-subtitle"><?php echo e(__('team.join_page_subtitle')); ?></p>
    </div>
</div>

<div class="alert alert-info mb-4" role="status">
    <i data-lucide="shield-check"></i>
    <div><?php echo e(__('team.join_role_notice')); ?></div>
</div>

<?php if(session('success')): ?>
<div class="alert alert-success mb-4" role="alert"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div>
<?php endif; ?>
<?php if(session('error')): ?>
<div class="alert alert-danger mb-4" role="alert"><i data-lucide="alert-circle"></i><div><?php echo e(session('error')); ?></div></div>
<?php endif; ?>
<?php if($errors->any()): ?>
<div class="alert alert-danger mb-4" role="alert">
    <i data-lucide="alert-triangle"></i>
    <div><ul class="alert-list"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul></div>
</div>
<?php endif; ?>


<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="building-2"></i> <?php echo e(__('team.join_teams_title')); ?></h3>
    </div>
    <div class="panel-body">
        <?php if($currentTeams->isEmpty() && ! $user->primary_facility_id): ?>
            <div class="empty-state">
                <i data-lucide="building-2"></i>
                <p><?php echo e(__('team.join_teams_empty')); ?></p>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?php echo e(__('team.join_col_facility')); ?></th>
                        <th><?php echo e(__('team.col_role')); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php $__currentLoopData = $currentTeams; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $team): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td data-label="<?php echo e(__('team.join_col_facility')); ?>"><?php echo e($team->facility?->name ?? '—'); ?></td>
                        <td data-label="<?php echo e(__('team.col_role')); ?>"><?php echo e($roleLabel($team->role?->name)); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </div>
</div>


<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="search"></i> <?php echo e(__('team.join_search_label')); ?></h3>
    </div>
    <div class="panel-body">
        <form method="GET" action="<?php echo e(route('professional.join')); ?>" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end;">
            <div style="flex:1 1 260px;">
                <label class="form-label" for="q"><?php echo e(__('team.join_search_label')); ?></label>
                <input type="search" id="q" name="q" class="form-control" maxlength="120"
                       placeholder="<?php echo e(__('team.join_search_placeholder')); ?>" value="<?php echo e($query); ?>">
            </div>
            <div>
                <button type="submit" class="btn btn-secondary fs-tap">
                    <i data-lucide="search"></i> <?php echo e(__('team.join_search_submit')); ?>

                </button>
            </div>
        </form>

        <?php if($query === ''): ?>
            <p class="page-subtitle" style="margin:1rem 0 0;"><?php echo e(__('team.join_search_prompt')); ?></p>
        <?php elseif($facilities->isEmpty()): ?>
            <div class="empty-state" style="margin-top:1rem;">
                <i data-lucide="search-x"></i>
                <p><?php echo e(__('team.join_search_empty')); ?></p>
            </div>
        <?php else: ?>
            <form method="POST" action="<?php echo e(route('professional.join.store')); ?>" style="margin-top:1.25rem;">
                <?php echo csrf_field(); ?>

                <fieldset style="border:0;padding:0;margin:0 0 1rem;">
                    <legend class="form-label" style="padding:0;"><?php echo e(__('team.join_search_label')); ?></legend>
                    <div style="display:grid;gap:.5rem;max-height:22rem;overflow:auto;">
                        <?php $__currentLoopData = $facilities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $facility): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <label style="display:flex;gap:.6rem;align-items:center;padding:.6rem .75rem;border:1px solid var(--p-border,#e5e7eb);border-radius:.5rem;cursor:pointer;">
                                <input type="radio" name="facility_id" value="<?php echo e($facility->id); ?>"
                                       <?php if(old('facility_id') === $facility->id): echo 'checked'; endif; ?> required>
                                <span>
                                    <strong><?php echo e($facility->name); ?></strong>
                                    <?php if($facility->type): ?>
                                        <span class="page-subtitle" style="display:block;margin:0;"><?php echo e($facility->type); ?></span>
                                    <?php endif; ?>
                                </span>
                            </label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <p class="page-subtitle" style="margin:.5rem 0 0;"><?php echo e(__('team.join_search_hint', ['limit' => $pickerLimit])); ?></p>
                </fieldset>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1rem;">
                    <div>
                        <label class="form-label" for="requested_role"><?php echo e(__('team.join_field_requested_role')); ?></label>
                        <input type="text" id="requested_role" name="requested_role" class="form-control" maxlength="120"
                               placeholder="<?php echo e(__('team.join_field_requested_role_ph')); ?>" value="<?php echo e(old('requested_role')); ?>">
                        <p class="page-subtitle" style="margin:.35rem 0 0;"><?php echo e(__('team.join_field_requested_role_help')); ?></p>
                    </div>
                    <div>
                        <label class="form-label" for="message"><?php echo e(__('team.join_field_message')); ?></label>
                        <textarea id="message" name="message" class="form-control" rows="3" maxlength="1000"
                                  placeholder="<?php echo e(__('team.join_field_message_ph')); ?>"><?php echo e(old('message')); ?></textarea>
                    </div>
                </div>

                <div style="margin-top:1rem;">
                    <button type="submit" class="btn btn-primary fs-tap">
                        <i data-lucide="send"></i> <?php echo e(__('team.join_submit')); ?>

                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>


<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="clipboard-list"></i> <?php echo e(__('team.join_my_requests_title')); ?></h3>
    </div>
    <div class="panel-body">
        <?php if($requests->isEmpty()): ?>
            <div class="empty-state">
                <i data-lucide="clipboard-list"></i>
                <p><?php echo e(__('team.join_my_requests_empty')); ?></p>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?php echo e(__('team.join_col_facility')); ?></th>
                        <th><?php echo e(__('team.join_col_requested')); ?></th>
                        <th><?php echo e(__('team.join_col_granted')); ?></th>
                        <th><?php echo e(__('team.col_status')); ?></th>
                        <th><?php echo e(__('team.join_col_submitted')); ?></th>
                        <th><?php echo e(__('team.col_actions')); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php $__currentLoopData = $requests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $req): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td data-label="<?php echo e(__('team.join_col_facility')); ?>"><?php echo e($req->facility?->name ?? '—'); ?></td>
                        <td data-label="<?php echo e(__('team.join_col_requested')); ?>"><?php echo e($req->requested_role ?: '—'); ?></td>
                        <td data-label="<?php echo e(__('team.join_col_granted')); ?>"><?php echo e($roleLabel($req->grantedRole?->name)); ?></td>
                        <td data-label="<?php echo e(__('team.col_status')); ?>">
                            <span class="badge <?php echo e($req->status->badgeClass()); ?>"><?php echo e(__($req->status->labelKey())); ?></span>
                        </td>
                        <td data-label="<?php echo e(__('team.join_col_submitted')); ?>"><?php echo e($req->created_at?->isoFormat('LLL')); ?></td>
                        <td data-label="<?php echo e(__('team.col_actions')); ?>" style="white-space:nowrap;">
                            <?php if($req->isPending()): ?>
                                <form method="POST" action="<?php echo e(route('professional.join.withdraw', $req->id)); ?>" style="display:inline;">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-secondary btn-sm fs-tap">
                                        <i data-lucide="undo-2"></i> <?php echo e(__('team.join_action_withdraw')); ?>

                                    </button>
                                </form>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/professional/facilities.blade.php ENDPATH**/ ?>