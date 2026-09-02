<?php $__env->startSection('title', __('public.portal.consent_title', [], app()->getLocale()) ?: 'Consent Requests'); ?>
<?php $__env->startSection('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal'); ?>
<?php $__env->startSection('breadcrumb_home_url', route('portals.patient')); ?>
<?php $__env->startSection('breadcrumb_section', __('public.portal.consent_breadcrumb', [], app()->getLocale()) ?: 'Consent Requests'); ?>

<?php $__env->startSection('patient_banner'); ?>
    <?php echo $__env->make('partials.guardian-context-banner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $l = app()->getLocale(); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo e(__('public.portal.consent_title', [], $l) ?: 'Consent Requests'); ?></h1>
        <p class="page-subtitle"><?php echo e(__('public.portal.consent_subtitle', [], $l) ?: 'Review and manage access requests from healthcare providers.'); ?></p>
    </div>
</div>

<?php if(session('success')): ?>
<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div><?php echo e(session('success')); ?></div></div>
<?php endif; ?>

<?php if(!$patient): ?>
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="alert-circle"></i></div>
        <h3><?php echo e(__('public.portal.no_profile_found_title', [], $l) ?: 'No Patient Profile Found'); ?></h3>
        <p><?php echo e(__('public.portal.no_profile_found_desc', [], $l) ?: 'Your patient profile could not be loaded. Please contact support.'); ?></p>
    </div>
</div>
<?php elseif($consentRequests->isEmpty()): ?>
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="shield-check"></i></div>
        <h3><?php echo e(__('public.portal.no_consent_title', [], $l) ?: 'No Consent Requests'); ?></h3>
        <p><?php echo e(__('public.portal.no_consent_desc', [], $l) ?: 'You have no pending or past access requests.'); ?></p>
    </div>
</div>
<?php else: ?>
<?php $__currentLoopData = $consentRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $req): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<div class="panel mb-4">
    <div class="panel-body">
        <div class="flex-between" style="align-items:flex-start;gap:var(--p-space-4);">
            <div style="flex:1;">
                <div class="td-strong"><?php echo e($req->requestingFacility?->name ?? __('public.portal.unknown_facility', [], $l) ?: 'Unknown Facility'); ?></div>
                <div class="text-muted mb-3"><?php echo e($req->purpose ?? __('public.portal.lbl_access_request', [], $l) ?: 'Access request'); ?></div>
                <div class="gap-2" style="display:flex;flex-wrap:wrap;">
                    <?php $__currentLoopData = ($req->requested_scope ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $scope): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <span class="badge badge-neutral"><?php echo e($scope); ?></span>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <div class="text-sm text-muted mt-1">
                    <?php echo e(__('public.portal.lbl_requested', [], $l) ?: 'Requested'); ?> <?php echo e($req->created_at->diffForHumans()); ?>

                    <?php if($req->duration_minutes): ?>
                     · <?php echo e(__('public.portal.lbl_valid_for', [], $l) ?: 'Valid for'); ?> <?php echo e(round($req->duration_minutes / 60, 1)); ?> <?php echo e(__('public.portal.lbl_hours', [], $l) ?: 'hours'); ?>

                    <?php endif; ?>
                </div>
            </div>
            <div class="row-actions" style="flex-direction:column;align-items:flex-end;">
                <?php if($req->status === 'pending'): ?>
                <span class="badge badge-warning"><?php echo e(\App\Support\Enums::label($req->status)); ?></span>
                <?php else: ?>
                <span class="badge <?php echo e($req->status === 'approved' ? 'badge-success' : 'badge-neutral'); ?>"><?php echo e(\App\Support\Enums::label($req->status)); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<?php if(method_exists($consentRequests, 'links') && $consentRequests->hasPages()): ?>
<div class="mt-3">
    <?php echo e($consentRequests->links()); ?>

</div>
<?php endif; ?>



<?php endif; ?>

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

<?php echo $__env->make('layouts.portal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\portals\patient\consent.blade.php ENDPATH**/ ?>