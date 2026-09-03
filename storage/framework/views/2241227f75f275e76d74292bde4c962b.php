<?php $__env->startSection('title', __('finder_shell.nav.find_blood')); ?>
<?php $__env->startSection('description', __('public.network_blood.meta_description')); ?>

<?php $__env->startPush('head'); ?>
<style>
/* Blood-group tap chips: the radio is visually hidden, the label is the
   ≥48px target. :has() drives the selected/focus state in modern browsers;
   the server also renders the selected state inline, so older browsers still
   see the active group after submit. */
.blood-chip { position: relative; min-height: 48px; justify-content: center; font-size: 1.0625rem; font-weight: 700; }
.blood-chip input { position: absolute; opacity: 0; pointer-events: none; }
.blood-chip:has(input:checked) { background: var(--f-wash); border-color: var(--f-brand); color: var(--f-brand-ink); }
.blood-chip:has(input:focus-visible) { outline: 2px solid var(--f-brand); outline-offset: 2px; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('schema'); ?>
<?php
    // json_encode lives in a @php block on purpose: Blade compiles
    // directives before echos, so a literal "@context" inside {!! !!}
    // is eaten by Blade's @context directive and the schema key becomes
    // garbage while the JSON stays syntactically valid.
    $serviceJsonLd = json_encode([
    '@context'      => 'https://schema.org',
    '@type'         => 'Service',
    '@id'           => rtrim(url('/'), '/') . '/#BloodFinder',
    'name'          => __('public.network_blood.hero_title'),
    'description'   => __('public.network_blood.meta_description'),
    'serviceType'   => 'Health information network service',
    'provider'      => ['@id' => rtrim(url('/'), '/') . '/#organization'],
    'areaServed'    => ['@type' => 'Country', 'name' => 'Cameroon'],
    'availableChannel' => [
        '@type'             => 'ServiceChannel',
        'serviceUrl'        => route('public.network.blood-finder'),
        'availableLanguage' => ['en', 'fr'],
    ],
    'inLanguage'    => app()->getLocale(),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
<script type="application/ld+json">
<?php echo $serviceJsonLd; ?>

</script>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>

<section class="f-section--wash f-section--tight">
    <div class="f-container">
        <span class="f-eyebrow"><?php echo $__env->make('partials.finder.icon', ['name' => 'droplet', 'size' => 14], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php echo e(__('public.network_blood.badge')); ?></span>
        <h1 class="f-h1"><?php echo e(__('public.network_blood.hero_title')); ?></h1>
        <p class="f-lead" style="margin-top:10px;"><?php echo e(__('finder_search.blood.intro')); ?></p>

        <form method="GET" action="<?php echo e(route('public.network.blood-finder')); ?>" style="margin-top:24px;max-width:640px;">
            <fieldset style="border:0;padding:0;margin:0;">
                <legend class="f-label"><?php echo e(__('finder_search.blood.group_label')); ?></legend>
                
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;max-width:420px;">
                    <?php $__currentLoopData = \App\Enums\BloodGroup::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <label class="f-chip blood-chip" <?php if($group === $g->value): ?> style="background:var(--f-wash);border-color:var(--f-brand);color:var(--f-brand-ink);" <?php endif; ?>>
                            <input type="radio" name="group" value="<?php echo e($g->value); ?>" required <?php if($group === $g->value): echo 'checked'; endif; ?>>
                            <?php echo e($g->label()); ?>

                        </label>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </fieldset>

            <div class="f-field" style="margin-top:16px;max-width:420px;">
                <label class="f-label" for="blood-component"><?php echo e(__('finder_search.blood.component_label')); ?></label>
                <select class="f-input" id="blood-component" name="component">
                    <?php $__currentLoopData = \App\Enums\BloodComponentType::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($c->value); ?>" <?php if($component === $c->value): echo 'selected'; endif; ?>><?php echo e(__('finder_search.blood.component_' . $c->value)); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <input type="hidden" name="lat" id="finder-lat" value="<?php echo e($lat); ?>">
            <input type="hidden" name="lng" id="finder-lng" value="<?php echo e($lng); ?>">

            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:4px;">
                <button class="f-btn f-btn--primary" type="submit">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'search', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo e(__('finder_search.blood.search_button')); ?>

                </button>
                <button class="f-chip" type="button" id="finder-locate">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'navigation', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo e(__('finder_search.common.use_my_location')); ?>

                </button>
            </div>
            <p class="f-small f-muted" id="finder-locate-status" role="status" aria-live="polite" style="margin-top:8px;">
                <?php if($lat !== null && $lng !== null): ?><?php echo e(__('finder_search.common.location_active')); ?><?php endif; ?>
            </p>
        </form>
    </div>
</section>

<?php if($results !== null): ?>
<section class="f-section--tight" id="results">
    <div class="f-container">

        
        <p class="f-small" style="display:flex;gap:8px;align-items:flex-start;max-width:62ch;color:var(--f-warn);background:var(--f-warn-wash);border-radius:var(--f-r);padding:10px 14px;">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <span><?php echo e(__('finder_search.blood.before_you_travel')); ?></span>
        </p>

        <?php if($results->isEmpty()): ?>

            <div class="f-card" style="text-align:center;padding:36px 20px;max-width:560px;margin-inline:auto;margin-top:16px;">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'droplet', 'size' => 36, 'class' => 'f-muted'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <h2 class="f-h2" style="margin-top:12px;"><?php echo e(__('finder_search.blood.empty_title', ['group' => $group])); ?></h2>
                <p class="f-muted" style="margin-top:10px;line-height:1.6;"><?php echo e(__('finder_search.blood.empty_body')); ?></p>
                <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-top:20px;">
                    <a class="f-btn f-btn--primary" href="<?php echo e(route('public.care-map.emergency')); ?>">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'heart-pulse', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php echo e(__('finder_search.blood.empty_emergency_cta')); ?>

                    </a>
                    <a class="f-btn" href="<?php echo e(route('public.care-map', ['facility_type' => 'blood_bank'])); ?>">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'map-pin', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php echo e(__('finder_search.common.directory_link')); ?>

                    </a>
                </div>
            </div>

        <?php else: ?>

            <p style="font-weight:650;margin-top:16px;"><?php echo e(trans_choice('finder_search.blood.results_count', $results->count(), ['count' => $results->count(), 'group' => $group])); ?></p>

            <?php $shown = $results->take(30); ?>
            <div style="display:grid;gap:12px;margin-top:12px;">
                <?php $__currentLoopData = $shown; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $match    = $f->matched_blood;
                        $phone    = $f->dialablePhone();
                        $address  = collect([$f->address, $f->city, $f->region])->filter()->implode(', ');
                        $dirUrl   = ($f->latitude && $f->longitude)
                            ? 'https://www.google.com/maps/dir/?api=1&destination=' . $f->latitude . ',' . $f->longitude
                            : null;
                        $reportedAt = $match->last_updated_at?->locale(app()->getLocale());
                    ?>
                    <article class="f-card">
                        <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                            <h3 style="font-size:1.0625rem;font-weight:700;line-height:1.35;"><?php echo e($f->facility_name); ?></h3>
                            <?php if($f->distance !== null): ?>
                                <span class="f-badge" style="flex-shrink:0;"><?php echo e(__('finder_search.common.distance_km', ['distance' => round($f->distance, 1)])); ?></span>
                            <?php endif; ?>
                        </div>

                        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-top:8px;">
                            <span class="f-badge f-badge--good"><?php echo e($match->blood_group); ?> · <?php echo e(__('finder_search.blood.component_' . $match->component_type)); ?></span>
                            <?php if($match->units_available_range): ?>
                                <span class="f-small" style="color:var(--f-ink-soft);"><?php echo e(__('finder_search.blood.units_reported', ['range' => $match->units_available_range])); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="f-small" style="display:grid;gap:5px;margin-top:10px;color:var(--f-ink-soft);">
                            <?php if($reportedAt): ?>
                                <span style="display:flex;gap:6px;align-items:center;">
                                    <?php echo $__env->make('partials.finder.icon', ['name' => 'clock', 'size' => 14], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <?php echo e(__('finder_search.blood.reported_line', ['time' => $reportedAt->diffForHumans()])); ?>

                                </span>
                            <?php endif; ?>
                            <?php if($address !== ''): ?>
                                <span style="display:flex;gap:6px;align-items:center;">
                                    <?php echo $__env->make('partials.finder.icon', ['name' => 'map-pin', 'size' => 14], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <?php echo e($address); ?>

                                </span>
                            <?php endif; ?>
                        </div>

                        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px;">
                            <?php if($phone): ?>
                                <a class="f-btn f-btn--primary" href="tel:<?php echo e($phone); ?>">
                                    <?php echo $__env->make('partials.finder.icon', ['name' => 'phone', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <?php echo e(__('finder_search.common.call')); ?>

                                </a>
                            <?php else: ?>
                                <span class="f-small f-muted" style="align-self:center;"><?php echo e(__('finder_search.common.no_phone')); ?></span>
                            <?php endif; ?>
                            <?php if($dirUrl): ?>
                                <a class="f-btn" href="<?php echo e($dirUrl); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php echo $__env->make('partials.finder.icon', ['name' => 'navigation', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <?php echo e(__('finder_search.common.directions')); ?>

                                </a>
                            <?php endif; ?>
                            <a class="f-btn f-btn--ghost" href="<?php echo e(route('public.care-map.profile', $f->id)); ?>">
                                <?php echo e(__('finder_search.common.view_profile')); ?>

                            </a>
                        </div>
                    </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            <?php if($results->count() > $shown->count()): ?>
                <p class="f-small f-muted" style="margin-top:12px;"><?php echo e(__('finder_search.common.showing_first', ['count' => $shown->count()])); ?></p>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</section>
<?php endif; ?>


<section class="f-section--tight f-section--surface">
    <div class="f-container">
        <div class="f-card" style="max-width:640px;">
            <h2 class="f-h2" style="font-size:1.25rem;"><?php echo e(__('finder_search.blood.request_title')); ?></h2>
            <p class="f-small" style="color:var(--f-ink-soft);line-height:1.6;margin-top:8px;"><?php echo e(__('finder_search.blood.request_body')); ?></p>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px;">
                <a class="f-btn f-btn--primary" href="<?php echo e(route('public.care-map.emergency')); ?>">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'heart-pulse', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo e(__('finder_search.blood.empty_emergency_cta')); ?>

                </a>
                <a class="f-btn" href="<?php echo e(route('public.solutions.patients')); ?>">
                    <?php echo e(__('finder_search.blood.request_app_cta')); ?>

                </a>
            </div>
            <p class="f-small f-muted" style="margin-top:14px;line-height:1.6;"><?php echo e(__('finder_search.blood.clinical_note')); ?></p>
        </div>
    </div>
</section>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    var btn    = document.getElementById('finder-locate');
    var status = document.getElementById('finder-locate-status');
    var latEl  = document.getElementById('finder-lat');
    var lngEl  = document.getElementById('finder-lng');
    if (!btn) return;
    var L = {
        locating: <?php echo json_encode(__('finder_search.common.locating'), 15, 512) ?>,
        failed:   <?php echo json_encode(__('finder_search.common.location_failed'), 15, 512) ?>,
        active:   <?php echo json_encode(__('finder_search.common.location_active'), 15, 512) ?>
    };
    btn.addEventListener('click', function () {
        if (!navigator.geolocation) { status.textContent = L.failed; return; }
        status.textContent = L.locating;
        navigator.geolocation.getCurrentPosition(function (pos) {
            latEl.value = pos.coords.latitude.toFixed(5);
            lngEl.value = pos.coords.longitude.toFixed(5);
            status.textContent = L.active;
            var checked = document.querySelector('input[name="group"]:checked');
            if (checked) { checked.form.submit(); }
        }, function () {
            status.textContent = L.failed;
        }, { timeout: 10000, maximumAge: 300000 });
    });
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.finder', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\care_map\blood_finder.blade.php ENDPATH**/ ?>