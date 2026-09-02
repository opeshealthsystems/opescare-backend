<?php $__env->startSection('title', __('public.portal.family_title', [], app()->getLocale()) ?: 'My Family'); ?>
<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.portal.family_breadcrumb', [], app()->getLocale()) ?: 'My Family'); ?>

<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>
<div class="page-head">
    <h2><?php echo e(__('public.portal.family_title', [], $l) ?: 'My Family'); ?></h2>
    <div class="page-head__spacer"></div>
    <a href="<?php echo e(route('portals.patient.family.add')); ?>" class="btn btn-primary">
        <i data-lucide="user-plus"></i> <?php echo e(__('public.portal.btn_add_dependent', [], $l) ?: 'Add Dependent'); ?>

    </a>
    <a href="<?php echo e(route('portals.patient.family.invite')); ?>" class="btn btn-secondary">
        <i data-lucide="mail"></i> <?php echo e(__('public.portal.btn_invite_member', [], $l) ?: 'Invite Member'); ?>

    </a>
</div>

<?php if(session('success')): ?>
<div class="alert alert-success mb-6">
    <i data-lucide="check-circle"></i> <?php echo e(session('success')); ?>

</div>
<?php endif; ?>

<?php if($links->isEmpty()): ?>
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="users"></i></div>
        <h3><?php echo e(__('public.portal.no_family_title', [], $l) ?: 'No family members linked yet'); ?></h3>
        <p><?php echo e(__('public.portal.no_family_desc', [], $l) ?: 'Add a dependent or invite an existing patient to link their records to yours.'); ?></p>
    </div>
</div>
<?php else: ?>
<div class="card-grid">
    <?php $__currentLoopData = $links; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="panel">
        <div class="panel-body">
            <div class="entity-head mb-4">
                <div class="entity-head__icon"><?php echo e(strtoupper(substr($link->dependentPatient->first_name ?? 'D', 0, 1))); ?></div>
                <div>
                    <div class="td-strong"><?php echo e($link->dependentPatient->first_name); ?> <?php echo e($link->dependentPatient->last_name); ?></div>
                    <div class="td-muted"><?php echo e($link->dependentPatient->health_id); ?></div>
                </div>
                <div class="entity-head__spacer"></div>
                <?php if($link->status === 'pending_invite'): ?>
                <span class="badge badge-warning"><?php echo e(__('public.portal.lbl_pending', [], $l) ?: 'Pending'); ?></span>
                <?php else: ?>
                <span class="badge badge-success"><?php echo e(__('public.portal.lbl_active', [], $l) ?: 'Active'); ?></span>
                <?php endif; ?>
            </div>
            <div class="text-sm text-muted mb-4">
                <?php echo e(\App\Support\Enums::label($link->relationship)); ?> &middot;
                <?php echo e($link->access_level === 'full' ? (__('public.portal.lbl_full_access', [], $l) ?: 'Full access') : (__('public.portal.lbl_read_only', [], $l) ?: 'Read only')); ?>

            </div>
            <?php if($link->isExpiredByAge()): ?>
            <div class="alert alert-warning mb-3">
                <i data-lucide="alert-triangle"></i>
                <?php echo e(__('public.portal.lbl_grace_period', [], $l) ?: 'Access in grace period — expires'); ?> <?php echo e($link->age_transition_expires_at->format('M d, Y')); ?>

            </div>
            <?php endif; ?>
            <div class="row-actions">
                <?php if($link->status === 'active'): ?>
                <form method="POST" action="<?php echo e(route('portals.patient.family.switch', $link->dependent_patient_id)); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn btn-primary btn-sm"><?php echo e(__('public.portal.btn_view_records', [], $l) ?: 'View Records'); ?></button>
                </form>
                <?php endif; ?>
                <a href="<?php echo e(route('portals.patient.family.edit', $link->id)); ?>" class="btn btn-secondary btn-sm"><?php echo e(__('public.portal.btn_edit', [], $l) ?: 'Edit'); ?></a>
                <button type="button" class="btn btn-danger btn-sm" onclick="opOpenModal('remove-link-<?php echo e($link->id); ?>')"><?php echo e(__('public.portal.btn_remove', [], $l) ?: 'Remove'); ?></button>
            </div>
        </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php endif; ?>

<?php if($incomingConsent->isNotEmpty()): ?>
<div data-section="guardian-consent" class="mt-8">
    <div class="page-head">
        <h2><i data-lucide="shield-alert"></i> <?php echo e(__('public.portal.guardian_section_title', [], $l) ?: 'Guardian Access — Your Approval Needed'); ?></h2>
    </div>
    <?php $__currentLoopData = $incomingConsent; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="panel mb-4">
        <div class="panel-body">
            <p class="mb-3">
                <strong><?php echo e($cl->guardianUser?->name ?? $cl->guardianUser?->email ?? __('public.portal.unknown_guardian', [], $l) ?: 'Unknown guardian'); ?></strong>
                <?php echo e(__('public.portal.guardian_access_desc', [], $l) ?: 'has guardian access to your records. This access will expire on'); ?>

                <strong><?php echo e($cl->age_transition_expires_at->format('M d, Y')); ?></strong>
                <?php echo e(__('public.portal.guardian_approve_suffix', [], $l) ?: 'unless you approve continued access.'); ?>

            </p>
            <div class="row-actions">
                <form method="POST" action="<?php echo e(route('portals.patient.family.guardian_consent.approve', $cl->id)); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn btn-primary btn-sm"><?php echo e(__('public.portal.btn_keep_access', [], $l) ?: 'Keep Access'); ?></button>
                </form>
                <button type="button" class="btn btn-danger btn-sm" onclick="opOpenModal('deny-guardian-<?php echo e($cl->id); ?>')"><?php echo e(__('public.portal.btn_remove_access', [], $l) ?: 'Remove Access'); ?></button>
            </div>
        </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php endif; ?>

<?php $__currentLoopData = $links; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<div id="remove-link-<?php echo e($link->id); ?>" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="remove-link-<?php echo e($link->id); ?>-title">
        <h3 class="modal__title" id="remove-link-<?php echo e($link->id); ?>-title"><i data-lucide="user-x"></i> <?php echo e(__('public.portal.modal_remove_link_title', [], $l) ?: 'Remove family link'); ?></h3>
        <form method="POST" action="<?php echo e(route('portals.patient.family.revoke', $link->id)); ?>">
            <?php echo csrf_field(); ?>
            <div class="modal__body"><p><?php echo e(__('public.portal.modal_remove_link_body', [], $l) ?: 'Remove this family link?'); ?></p></div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('remove-link-<?php echo e($link->id); ?>')"><?php echo e(__('public.portal.btn_cancel', [], $l) ?: 'Cancel'); ?></button>
                <button type="submit" class="btn btn-danger"><?php echo e(__('public.portal.btn_remove', [], $l) ?: 'Remove'); ?></button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<?php $__currentLoopData = $incomingConsent; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<div id="deny-guardian-<?php echo e($cl->id); ?>" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="deny-guardian-<?php echo e($cl->id); ?>-title">
        <h3 class="modal__title" id="deny-guardian-<?php echo e($cl->id); ?>-title"><i data-lucide="shield-off"></i> <?php echo e(__('public.portal.modal_remove_guardian_title', [], $l) ?: 'Remove guardian access'); ?></h3>
        <form method="POST" action="<?php echo e(route('portals.patient.family.guardian_consent.deny', $cl->id)); ?>">
            <?php echo csrf_field(); ?>
            <div class="modal__body"><p><?php echo e(__('public.portal.modal_remove_guardian_body', [], $l) ?: "Remove this guardian's access?"); ?></p></div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('deny-guardian-<?php echo e($cl->id); ?>')"><?php echo e(__('public.portal.btn_cancel', [], $l) ?: 'Cancel'); ?></button>
                <button type="submit" class="btn btn-danger"><?php echo e(__('public.portal.btn_remove_access', [], $l) ?: 'Remove Access'); ?></button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
function opOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function opCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\family\index.blade.php ENDPATH**/ ?>