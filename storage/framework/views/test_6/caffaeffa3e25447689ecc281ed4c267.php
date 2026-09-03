<?php $__env->startSection('title', __('team.page_title') . ' — OpesCare'); ?>

<?php $__env->startSection('breadcrumb_home', __('team.breadcrumb_home')); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.admin')); ?>
<?php $__env->startSection('breadcrumb_section', __('team.page_title')); ?>

<?php $__env->startSection('head'); ?>
<?php echo $__env->make('portals.pharmacy._supply_styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php
    /* A staff member may legitimately hold a role outside the invitable
       allow-list (the facility admin themselves, for one), so fall back to the
       role name rather than printing a raw translation key. */
    $orRaw = function (string $key, string $raw): string {
        $label = __($key);

        return is_string($label) && $label !== $key ? $label : $raw;
    };

    $roleLabel = function (?string $name) use ($orRaw): string {
        return $name ? $orRaw('team.roles.' . $name, $name) : '—';
    };

    $statusLabel = function (?string $status) use ($orRaw): string {
        $status = $status ?: 'active';

        return $orRaw('team.user_status_' . $status, $status);
    };
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('team.page_title')); ?></h1>
        <p class="page-subtitle"><?php echo e(__('team.page_subtitle')); ?></p>
    </div>
</div>

<?php if(session('success')): ?>
<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div>
<?php endif; ?>
<?php if(session('error')): ?>
<div class="alert alert-danger mb-4"><i data-lucide="alert-circle"></i><div><?php echo e(session('error')); ?></div></div>
<?php endif; ?>

<?php if(session('invite_link')): ?>

<div class="panel mb-4" style="border-left:4px solid #0F4C81;">
    <div class="panel-body">
        <h3 class="panel-title" style="margin:0 0 .5rem;">
            <i data-lucide="link"></i> <?php echo e(__('team.invite_link_title')); ?>

        </h3>
        <p class="page-subtitle" style="margin:0 0 .75rem;">
            <?php echo e(__('team.invite_link_help', ['email' => session('invite_link_for')])); ?>

        </p>
        <input type="text" class="form-control" readonly onclick="this.select()"
               value="<?php echo e(session('invite_link')); ?>"
               aria-label="<?php echo e(__('team.invite_link_title')); ?>">
    </div>
</div>
<?php endif; ?>

<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="user-plus"></i> <?php echo e(__('team.invite_form_title')); ?></h3>
    </div>
    <div class="panel-body">
        <p class="page-subtitle" style="margin:0 0 1rem;"><?php echo e(__('team.invite_form_help')); ?></p>

        <form method="POST" action="<?php echo e(route('portals.facility.team.invite')); ?>">
            <?php echo csrf_field(); ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;">
                <div>
                    <label class="form-label" for="email"><?php echo e(__('team.field_email')); ?></label>
                    <input type="email" id="email" name="email" class="form-control" required
                           maxlength="255" value="<?php echo e(old('email')); ?>">
                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="page-subtitle" style="color:var(--p-danger);margin:.35rem 0 0;"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label class="form-label" for="name"><?php echo e(__('team.field_name')); ?></label>
                    <input type="text" id="name" name="name" class="form-control"
                           maxlength="255" value="<?php echo e(old('name')); ?>">
                    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="page-subtitle" style="color:var(--p-danger);margin:.35rem 0 0;"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label class="form-label" for="role"><?php echo e(__('team.field_role')); ?></label>
                    <select id="role" name="role" class="form-control" required>
                        <option value=""><?php echo e(__('team.field_role_placeholder')); ?></option>
                        <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($role->name); ?>" <?php if(old('role') === $role->name): echo 'selected'; endif; ?>>
                                <?php echo e($roleLabel($role->name)); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['role'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="page-subtitle" style="color:var(--p-danger);margin:.35rem 0 0;"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>
            <div style="margin-top:1rem;">
                <button type="submit" class="btn btn-primary fs-tap">
                    <i data-lucide="send"></i> <?php echo e(__('team.invite_submit')); ?>

                </button>
            </div>
        </form>
    </div>
</div>

<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="mail"></i> <?php echo e(__('team.invites_title')); ?></h3>
    </div>
    <div class="panel-body">
        <?php if($invites->isEmpty()): ?>
            <div class="empty-state">
                <i data-lucide="mail"></i>
                <p><?php echo e(__('team.invites_empty')); ?></p>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?php echo e(__('team.col_email')); ?></th>
                        <th><?php echo e(__('team.col_role')); ?></th>
                        <th><?php echo e(__('team.col_status')); ?></th>
                        <th><?php echo e(__('team.col_expires')); ?></th>
                        <th><?php echo e(__('team.col_invited_by')); ?></th>
                        <th><?php echo e(__('team.col_actions')); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php $__currentLoopData = $invites; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invite): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $reason = $invite->failureReason();
                        $badge  = match ($reason) {
                            'used'    => 'badge-success',
                            'revoked' => 'badge-danger',
                            'expired' => 'badge-neutral',
                            default   => 'badge-warning',
                        };
                    ?>
                    <tr>
                        <td data-label="<?php echo e(__('team.col_email')); ?>"><?php echo e($invite->email); ?></td>
                        <td data-label="<?php echo e(__('team.col_role')); ?>"><?php echo e($roleLabel($invite->role?->name)); ?></td>
                        <td data-label="<?php echo e(__('team.col_status')); ?>"><span class="badge <?php echo e($badge); ?>"><?php echo e(__('team.status_' . ($reason ?? 'pending'))); ?></span></td>
                        <td data-label="<?php echo e(__('team.col_expires')); ?>"><?php echo e($invite->expires_at?->isoFormat('LLL')); ?></td>
                        <td data-label="<?php echo e(__('team.col_invited_by')); ?>"><?php echo e($invite->inviter?->name ?? '—'); ?></td>
                        <td data-label="<?php echo e(__('team.col_actions')); ?>" style="white-space:nowrap;">
                            <?php if (! ($invite->isAccepted())): ?>
                                <form method="POST" action="<?php echo e(route('portals.facility.team.invite.reissue', $invite->id)); ?>" style="display:inline;">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-secondary btn-sm fs-tap">
                                        <i data-lucide="refresh-cw"></i> <?php echo e(__('team.action_reissue')); ?>

                                    </button>
                                </form>
                                <?php if (! ($invite->isRevoked())): ?>
                                    <form method="POST" action="<?php echo e(route('portals.facility.team.invite.revoke', $invite->id)); ?>" style="display:inline;">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" class="btn btn-danger btn-sm fs-tap">
                                            <i data-lucide="ban"></i> <?php echo e(__('team.action_revoke')); ?>

                                        </button>
                                    </form>
                                <?php endif; ?>
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

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="users"></i> <?php echo e(__('team.staff_title')); ?></h3>
    </div>
    <div class="panel-body">
        <?php if($staff->isEmpty()): ?>
            <div class="empty-state">
                <i data-lucide="users"></i>
                <p><?php echo e(__('team.staff_empty')); ?></p>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?php echo e(__('team.col_name')); ?></th>
                        <th><?php echo e(__('team.col_email')); ?></th>
                        <th><?php echo e(__('team.col_role')); ?></th>
                        <th><?php echo e(__('team.col_status')); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php $__currentLoopData = $staff; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $member): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td data-label="<?php echo e(__('team.col_name')); ?>"><?php echo e($member->name); ?></td>
                        <td data-label="<?php echo e(__('team.col_email')); ?>"><?php echo e($member->email); ?></td>
                        <td data-label="<?php echo e(__('team.col_role')); ?>"><?php echo e($roleLabel($member->role?->name)); ?></td>
                        <td data-label="<?php echo e(__('team.col_status')); ?>">
                            <span class="badge <?php echo e($member->status === 'active' ? 'badge-success' : 'badge-neutral'); ?>">
                                <?php echo e($statusLabel($member->status)); ?>

                            </span>
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

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/facility/team.blade.php ENDPATH**/ ?>