<?php $__env->startSection('title', __('public.portal.family_title', [], app()->getLocale()) ?: 'My Family'); ?>
<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.portal.family_breadcrumb', [], app()->getLocale()) ?: 'My Family'); ?>

<?php $__env->startSection('head'); ?>
    <?php echo $__env->make('portals.patient._finder_head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>

<div class="pf">

    <div class="pf-head">
        <div>
            <h1><?php echo e(__('public.portal.family_title', [], $l) ?: 'My Family'); ?></h1>
        </div>
        <div class="pf-actions">
            <a href="<?php echo e(route('portals.patient.family.add')); ?>" class="f-btn f-btn--primary">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'plus', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo e(__('public.portal.btn_add_dependent', [], $l) ?: 'Add Dependent'); ?>

            </a>
            <a href="<?php echo e(route('portals.patient.family.invite')); ?>" class="f-btn">
                <?php echo e(__('public.portal.btn_invite_member', [], $l) ?: 'Invite Member'); ?>

            </a>
        </div>
    </div>

    <?php if($links->isEmpty()): ?>
    <div class="f-card" style="text-align:center;padding:40px 20px;">
        <div class="pf-qa__icon" style="margin:0 auto 12px;width:48px;height:48px;">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'users', 'size' => 24], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
        <h2 style="font-size:1.125rem;font-weight:750;margin:0 0 6px;"><?php echo e(__('public.portal.no_family_title', [], $l) ?: 'No family members linked yet'); ?></h2>
        <p class="f-muted" style="margin:0;"><?php echo e(__('public.portal.no_family_desc', [], $l) ?: 'Add a dependent or invite an existing patient to link their records to yours.'); ?></p>
    </div>
    <?php else: ?>
    <div class="pf-stack">
        <?php $__currentLoopData = $links; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="f-card">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="pf-avatar"><?php echo e(strtoupper(substr($link->dependentPatient->first_name ?? 'D', 0, 1))); ?></div>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:750;color:var(--f-ink);"><?php echo e($link->dependentPatient->first_name); ?> <?php echo e($link->dependentPatient->last_name); ?></div>
                    <div class="f-small f-muted" style="font-family:ui-monospace,Menlo,Consolas,monospace;"><?php echo e($link->dependentPatient->health_id); ?></div>
                </div>
                <?php if($link->status === 'pending_invite'): ?>
                <span class="f-badge f-badge--warn"><?php echo e(__('public.portal.lbl_pending', [], $l) ?: 'Pending'); ?></span>
                <?php else: ?>
                <span class="f-badge f-badge--good"><?php echo e(__('public.portal.lbl_active', [], $l) ?: 'Active'); ?></span>
                <?php endif; ?>
            </div>
            <p class="f-small f-muted" style="margin:10px 0 0;">
                <?php echo e(\App\Support\Enums::label($link->relationship)); ?> &middot;
                <?php echo e($link->access_level === 'full' ? (__('public.portal.lbl_full_access', [], $l) ?: 'Full access') : (__('public.portal.lbl_read_only', [], $l) ?: 'Read only')); ?>

            </p>
            <?php if($link->isExpiredByAge()): ?>
            <div class="pf-alert pf-alert--warn" style="margin:10px 0 0;">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <p class="f-small"><?php echo e(__('public.portal.lbl_grace_period', [], $l) ?: 'Access in grace period — expires'); ?> <?php echo e($link->age_transition_expires_at->format('M d, Y')); ?></p>
            </div>
            <?php endif; ?>
            <div class="pf-appt__actions">
                <?php if($link->status === 'active'): ?>
                <form method="POST" action="<?php echo e(route('portals.patient.family.switch', $link->dependent_patient_id)); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="f-btn f-btn--primary"><?php echo e(__('public.portal.btn_view_records', [], $l) ?: 'View Records'); ?></button>
                </form>
                <?php endif; ?>
                <a href="<?php echo e(route('portals.patient.family.edit', $link->id)); ?>" class="f-btn"><?php echo e(__('public.portal.btn_edit', [], $l) ?: 'Edit'); ?></a>
                <button type="button" class="f-btn pf-btn--danger-ghost" onclick="pfOpenModal('remove-link-<?php echo e($link->id); ?>')"><?php echo e(__('public.portal.btn_remove', [], $l) ?: 'Remove'); ?></button>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php endif; ?>

    <?php if($incomingConsent->isNotEmpty()): ?>
    <div data-section="guardian-consent" class="pf-section" style="margin-top:24px;">
        <h2 class="pf-card-head" style="margin-bottom:10px;">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'shield-check', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo e(__('public.portal.guardian_section_title', [], $l) ?: 'Guardian Access — Your Approval Needed'); ?>

        </h2>
        <div class="pf-stack">
            <?php $__currentLoopData = $incomingConsent; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="f-card">
                <p style="margin:0 0 12px;">
                    <strong><?php echo e($cl->guardianUser?->name ?? $cl->guardianUser?->email ?? (__('public.portal.unknown_guardian', [], $l) ?: 'Unknown guardian')); ?></strong>
                    <?php echo e(__('public.portal.guardian_access_desc', [], $l) ?: 'has guardian access to your records. This access will expire on'); ?>

                    <strong><?php echo e($cl->age_transition_expires_at->format('M d, Y')); ?></strong>
                    <?php echo e(__('public.portal.guardian_approve_suffix', [], $l) ?: 'unless you approve continued access.'); ?>

                </p>
                <div class="pf-actions">
                    <form method="POST" action="<?php echo e(route('portals.patient.family.guardian_consent.approve', $cl->id)); ?>">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="f-btn f-btn--primary"><?php echo e(__('public.portal.btn_keep_access', [], $l) ?: 'Keep Access'); ?></button>
                    </form>
                    <button type="button" class="f-btn pf-btn--danger-ghost" onclick="pfOpenModal('deny-guardian-<?php echo e($cl->id); ?>')"><?php echo e(__('public.portal.btn_remove_access', [], $l) ?: 'Remove Access'); ?></button>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
    <?php endif; ?>

    <?php $__currentLoopData = $links; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div id="remove-link-<?php echo e($link->id); ?>" class="pf-modal-backdrop" hidden>
        <div class="pf-modal" role="dialog" aria-modal="true" aria-labelledby="remove-link-<?php echo e($link->id); ?>-title">
            <h3 id="remove-link-<?php echo e($link->id); ?>-title">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo e(__('public.portal.modal_remove_link_title', [], $l) ?: 'Remove family link'); ?>

            </h3>
            <form method="POST" action="<?php echo e(route('portals.patient.family.revoke', $link->id)); ?>">
                <?php echo csrf_field(); ?>
                <p><?php echo e(__('public.portal.modal_remove_link_body', [], $l) ?: 'Remove this family link?'); ?></p>
                <div class="pf-modal__actions">
                    <button type="button" class="f-btn" onclick="pfCloseModal('remove-link-<?php echo e($link->id); ?>')"><?php echo e(__('public.portal.btn_cancel', [], $l) ?: 'Cancel'); ?></button>
                    <button type="submit" class="f-btn pf-btn--danger"><?php echo e(__('public.portal.btn_remove', [], $l) ?: 'Remove'); ?></button>
                </div>
            </form>
        </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    <?php $__currentLoopData = $incomingConsent; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div id="deny-guardian-<?php echo e($cl->id); ?>" class="pf-modal-backdrop" hidden>
        <div class="pf-modal" role="dialog" aria-modal="true" aria-labelledby="deny-guardian-<?php echo e($cl->id); ?>-title">
            <h3 id="deny-guardian-<?php echo e($cl->id); ?>-title">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo e(__('public.portal.modal_remove_guardian_title', [], $l) ?: 'Remove guardian access'); ?>

            </h3>
            <form method="POST" action="<?php echo e(route('portals.patient.family.guardian_consent.deny', $cl->id)); ?>">
                <?php echo csrf_field(); ?>
                <p><?php echo e(__('public.portal.modal_remove_guardian_body', [], $l) ?: "Remove this guardian's access?"); ?></p>
                <div class="pf-modal__actions">
                    <button type="button" class="f-btn" onclick="pfCloseModal('deny-guardian-<?php echo e($cl->id); ?>')"><?php echo e(__('public.portal.btn_cancel', [], $l) ?: 'Cancel'); ?></button>
                    <button type="submit" class="f-btn pf-btn--danger"><?php echo e(__('public.portal.btn_remove_access', [], $l) ?: 'Remove Access'); ?></button>
                </div>
            </form>
        </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
function pfOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function pfCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.pf-modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/patient/family/index.blade.php ENDPATH**/ ?>