
<?php
    $switchUser  = auth()->user();
    $switchSvc   = app(\App\Services\Portal\FacilityMembershipService::class);
    $switchShow  = $switchSvc->hasChoice($switchUser);

    // Only look the name up when a facility has actually been chosen. A
    // multi-facility clinician who has not chosen yet reaches this partial with
    // an empty session value, and `id = <empty>` against a uuid column is a
    // Postgres 22P02 — a 500 in the sidebar of every page.
    $switchActiveId = session('active_facility_id');
    $switchNow      = ($switchShow && is_string($switchActiveId) && \Illuminate\Support\Str::isUuid($switchActiveId))
        ? \App\Models\Facility::withoutGlobalScope('isolate_demo')->whereKey($switchActiveId)->value('name')
        : null;

    $switchLocale = app()->getLocale();
?>

<?php if($switchShow): ?>
    <div class="sidebar-nav-section">
        <?php if($switchNow): ?>
            <div class="sidebar-nav-label">
                <?php echo e(__('facility_switch.acting_as', [], $switchLocale)); ?>: <?php echo e($switchNow); ?>

            </div>
        <?php endif; ?>
        <a href="<?php echo e(route('select-facility')); ?>"
           class="sidebar-link <?php echo e(request()->routeIs('select-facility') ? 'active' : ''); ?>">
            <i data-lucide="repeat"></i>
            <span><?php echo e(__('facility_switch.nav', [], $switchLocale)); ?></span>
        </a>
    </div>
<?php endif; ?>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views/partials/sidebars/_switch_facility.blade.php ENDPATH**/ ?>