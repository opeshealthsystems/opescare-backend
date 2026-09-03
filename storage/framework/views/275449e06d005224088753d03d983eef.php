<?php $__env->startSection('title', __('finder_shell.nav.find_medicine')); ?>
<?php $__env->startSection('description', __('public.network_medicine.meta_description')); ?>

<?php $__env->startPush('schema'); ?>
<?php
    // json_encode lives in a @php block on purpose: Blade compiles
    // directives before echos, so a literal "@context" inside {!! !!}
    // is eaten by Blade's @context directive and the schema key becomes
    // garbage while the JSON stays syntactically valid.
    $serviceJsonLd = json_encode([
    '@context'      => 'https://schema.org',
    '@type'         => 'Service',
    '@id'           => rtrim(url('/'), '/') . '/#MedicineFinder',
    'name'          => __('public.network_medicine.hero_title'),
    'description'   => __('public.network_medicine.meta_description'),
    'serviceType'   => 'Health information network service',
    'provider'      => ['@id' => rtrim(url('/'), '/') . '/#organization'],
    'areaServed'    => ['@type' => 'Country', 'name' => 'Cameroon'],
    'availableChannel' => [
        '@type'             => 'ServiceChannel',
        'serviceUrl'        => route('public.network.medicine-finder'),
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
        <span class="f-eyebrow"><?php echo $__env->make('partials.finder.icon', ['name' => 'pill', 'size' => 14], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> <?php echo e(__('public.network_medicine.badge')); ?></span>
        <h1 class="f-h1"><?php echo e(__('public.network_medicine.hero_title')); ?></h1>
        <p class="f-lead" style="margin-top:10px;"><?php echo e(__('finder_search.medicine.intro')); ?></p>

        <form method="GET" action="<?php echo e(route('public.network.medicine-finder')); ?>" role="search" style="margin-top:24px;max-width:640px;">
            <label class="f-label" for="medicine-q"><?php echo e(__('finder_search.medicine.search_label')); ?></label>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <input class="f-input" style="flex:1 1 240px;" id="medicine-q" name="q" type="search"
                       value="<?php echo e($query); ?>" placeholder="<?php echo e(__('finder_search.medicine.search_placeholder')); ?>"
                       minlength="2" maxlength="120" required autocomplete="off"
                       <?php if($query === ''): ?> autofocus <?php endif; ?>>
                <button class="f-btn f-btn--primary" type="submit" style="flex:0 0 auto;">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'search', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo e(__('finder_search.common.search')); ?>

                </button>
            </div>

            <input type="hidden" name="lat" id="finder-lat" value="<?php echo e($lat); ?>">
            <input type="hidden" name="lng" id="finder-lng" value="<?php echo e($lng); ?>">

            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:12px;">
                <button class="f-chip" type="button" id="finder-locate">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'navigation', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo e(__('finder_search.common.use_my_location')); ?>

                </button>
                <span class="f-small f-muted" id="finder-locate-status" role="status" aria-live="polite">
                    <?php if($lat !== null && $lng !== null): ?><?php echo e(__('finder_search.common.location_active')); ?><?php endif; ?>
                </span>
            </div>
        </form>
    </div>
</section>

<?php if($results !== null): ?>
<section class="f-section--tight" id="results">
    <div class="f-container">
        <?php if($results->isEmpty()): ?>

            
            <div class="f-card" style="text-align:center;padding:36px 20px;max-width:560px;margin-inline:auto;">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'pill', 'size' => 36, 'class' => 'f-muted'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <h2 class="f-h2" style="margin-top:12px;"><?php echo e(__('finder_search.medicine.empty_title')); ?></h2>
                <p class="f-muted" style="margin-top:10px;line-height:1.6;"><?php echo e(__('finder_search.medicine.empty_body')); ?></p>
                <div style="margin-top:20px;">
                    <a class="f-btn f-btn--primary" href="<?php echo e(route('public.care-map', ['facility_type' => 'pharmacy'])); ?>">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'map-pin', 'size' => 18], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php echo e(__('finder_search.medicine.empty_cta')); ?>

                    </a>
                </div>
            </div>

        <?php else: ?>

            <p style="font-weight:650;"><?php echo e(trans_choice('finder_search.medicine.results_count', $results->count(), ['count' => $results->count()])); ?></p>

            
            <p class="f-small f-muted" style="display:flex;gap:8px;align-items:flex-start;margin-top:6px;max-width:62ch;">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <span><?php echo e(__('finder_search.medicine.before_you_travel')); ?></span>
            </p>

            <?php $shown = $results->take(30); ?>
            <div style="display:grid;gap:12px;margin-top:16px;">
                <?php $__currentLoopData = $shown; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $stock    = $f->matched_stock;
                        $phone    = $f->dialablePhone();
                        $address  = collect([$f->address, $f->city, $f->region])->filter()->implode(', ');
                        $dirUrl   = ($f->latitude && $f->longitude)
                            ? 'https://www.google.com/maps/dir/?api=1&destination=' . $f->latitude . ',' . $f->longitude
                            : null;
                        $reportedAt = !empty($stock['last_updated_at'])
                            ? \Illuminate\Support\Carbon::parse($stock['last_updated_at'])->locale(app()->getLocale())
                            : null;
                        // 'reported_available' comes from the live stock table,
                        // 'available' from the directory formulary — same claim.
                        [$statusKey, $statusClass] = match ($stock['availability_status'] ?? 'unknown') {
                            'reported_available', 'available' => ['finder_search.medicine.status_in_stock', 'f-badge--good'],
                            'low_stock'                       => ['finder_search.medicine.status_low', 'f-badge--warn'],
                            'out_of_stock'                    => ['finder_search.medicine.status_out', 'f-badge--danger'],
                            default                           => ['finder_search.medicine.status_unknown', ''],
                        };
                        $medLabel = collect([$stock['medicine_name'] ?? null, $stock['strength'] ?? null, $stock['form'] ?? null])
                            ->filter()->implode(' · ');
                    ?>
                    <article class="f-card">
                        <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                            <h3 style="font-size:1.0625rem;font-weight:700;line-height:1.35;"><?php echo e($f->facility_name); ?></h3>
                            <?php if($f->distance !== null): ?>
                                <span class="f-badge" style="flex-shrink:0;"><?php echo e(__('finder_search.common.distance_km', ['distance' => round($f->distance, 1)])); ?></span>
                            <?php endif; ?>
                        </div>

                        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-top:8px;">
                            <span class="f-badge <?php echo e($statusClass); ?>"><?php echo e(__($statusKey)); ?></span>
                            <?php if($medLabel !== ''): ?><span class="f-small f-muted"><?php echo e($medLabel); ?></span><?php endif; ?>
                        </div>

                        <div class="f-small" style="display:grid;gap:5px;margin-top:10px;color:var(--f-ink-soft);">
                            <?php if(!empty($stock['price'])): ?>
                                <span style="font-weight:700;color:var(--f-ink);">
                                    <?php echo e(__('finder_search.medicine.price_fcfa', ['price' => number_format((float) $stock['price'], 0, '.', ' ')])); ?>

                                    <?php if(!empty($stock['pack_size'])): ?> <span class="f-muted" style="font-weight:400;">— <?php echo e(__('finder_search.medicine.pack_of', ['size' => $stock['pack_size']])); ?></span><?php endif; ?>
                                </span>
                            <?php endif; ?>
                            <?php if($reportedAt): ?>
                                <span style="display:flex;gap:6px;align-items:center;">
                                    <?php echo $__env->make('partials.finder.icon', ['name' => 'clock', 'size' => 14], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <?php echo e(__('finder_search.medicine.reported_line', ['time' => $reportedAt->diffForHumans()])); ?>

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
            <p class="f-small" style="font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:var(--f-brand);margin-bottom:6px;"><?php echo e(__('public.network_medicine.publish_label')); ?></p>
            <p class="f-small" style="color:var(--f-ink-soft);line-height:1.6;"><?php echo e(__('public.network_medicine.publish_body')); ?></p>
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
    var qEl    = document.getElementById('medicine-q');
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
            if (qEl.value.trim().length >= 2) { qEl.form.submit(); }
        }, function () {
            status.textContent = L.failed;
        }, { timeout: 10000, maximumAge: 300000 });
    });
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.finder', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\opescare-finder\resources\views\care_map\medicine_finder.blade.php ENDPATH**/ ?>