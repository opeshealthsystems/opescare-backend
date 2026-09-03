
<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?php echo e(__('finder_seo.pages.emergency.title')); ?> — OpesCare Finder</title>
<meta name="description" content="<?php echo e(__('finder_seo.pages.emergency.description')); ?>">
<link rel="canonical" href="<?php echo e(route('public.care-map.emergency') . (app()->getLocale() === 'en' ? '' : '?lang=' . app()->getLocale())); ?>">
<link rel="alternate" hreflang="en" href="<?php echo e(route('public.care-map.emergency')); ?>">
<link rel="alternate" hreflang="fr" href="<?php echo e(route('public.care-map.emergency', ['lang' => 'fr'])); ?>">
<link rel="alternate" hreflang="x-default" href="<?php echo e(route('public.care-map.emergency')); ?>">
<?php echo $__env->make('partials.favicons', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<link rel="stylesheet" href="<?php echo e(asset('assets/finder.css')); ?>?v=<?php echo e(@filemtime(public_path('assets/finder.css')) ?: '1'); ?>">
<style>
/* Page-local styles on top of finder.css tokens. Calm, high contrast,
   list-first. --f-danger appears ONLY on the two dial actions. */
.em-header { position: sticky; top: 0; z-index: 20; background: var(--f-surface); border-bottom: 1px solid var(--f-line); }
.em-header__row { display: flex; align-items: center; justify-content: space-between; gap: 12px; min-height: 56px; }
.em-dial-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 16px; }
.em-dial {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    min-height: 72px; padding: 12px 16px;
    background: var(--f-danger); color: #FFFFFF;
    border-radius: var(--f-r); text-decoration: none;
    font-size: 1.25rem; font-weight: 800; letter-spacing: -0.01em;
    box-shadow: var(--f-shadow);
    -webkit-tap-highlight-color: transparent;
}
.em-dial:active { transform: translateY(1px); }
@media (max-width: 380px) { .em-dial-grid { grid-template-columns: 1fr; } .em-dial { min-height: 64px; } }
.em-note { display: flex; gap: 8px; align-items: flex-start; background: var(--f-wash); border-radius: var(--f-r); padding: 10px 14px; }
.em-cards { display: grid; gap: 12px; margin-top: 16px; }
/* Facility card internals as classes, not inline styles: the card repeats up
   to 40 times and every byte inside the loop is paid 40 times over. Same
   reason the per-row icons are text-only — one icon per card (Call). */
.em-fc-top { display: flex; justify-content: space-between; gap: 10px; align-items: flex-start; }
.em-fc-name { font-size: 1.0625rem; font-weight: 700; line-height: 1.35; }
.em-fc-dist { flex-shrink: 0; }
.em-fc-badges { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-top: 8px; }
.em-fc-meta { display: grid; gap: 5px; margin-top: 10px; color: var(--f-ink-soft); }
.em-fc-btns { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 14px; }
.em-fc-call { flex: 1 1 auto; }
</style>

<?php echo $__env->make('partials.seo_head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</head>
<body>

<header class="em-header">
    <div class="f-container em-header__row">
        <a href="<?php echo e(route('public.landing')); ?>" class="f-brand" aria-label="OpesCare">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'heart-pulse', 'size' => 22, 'class' => 'f-brand__mark'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <span>OpesCare</span>
        </a>
        <a href="<?php echo e(route('public.care-map')); ?>" class="f-btn f-btn--ghost">
            <?php echo $__env->make('partials.finder.icon', ['name' => 'map-pin', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo e(__('finder_search.emergency.back_to_directory')); ?>

        </a>
    </div>
</header>

<main id="f-main">

    
    <section class="f-section--tight" style="padding-block:24px;">
        <div class="f-container">
            <h1 class="f-h2" style="font-size:1.375rem;"><?php echo e(__('public.care_map_emergency.h1')); ?></h1>
            <p style="font-weight:700;margin-top:14px;"><?php echo e(__('finder_search.emergency.call_now_title')); ?></p>
            <p class="f-small f-muted" style="margin-top:4px;"><?php echo e(__('finder_search.emergency.call_now_sub')); ?></p>

            <div class="em-dial-grid">
                <a href="tel:112" class="em-dial">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'phone', 'size' => 26], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo e(__('finder_search.emergency.dial_112')); ?>

                </a>
                <a href="tel:119" class="em-dial">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'phone', 'size' => 26], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo e(__('finder_search.emergency.dial_119')); ?>

                </a>
            </div>
            <p class="f-small f-muted" style="margin-top:8px;"><?php echo e(__('finder_search.emergency.dial_hint')); ?></p>
        </div>
    </section>

    
    <section class="f-section--tight f-section--surface" style="padding-block:24px;border-top:1px solid var(--f-line);">
        <div class="f-container">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                <div>
                    <h2 class="f-h2" style="font-size:1.25rem;"><?php echo e(__('finder_search.emergency.nearest_title')); ?></h2>
                    <p class="f-small f-muted" style="margin-top:2px;">
                        <?php echo e(count($facilities) === 1
                            ? __('public.care_map_emergency.count_hospitals_one')
                            : __('public.care_map_emergency.count_hospitals', ['count' => count($facilities)])); ?>

                    </p>
                </div>
                <button class="f-chip" type="button" id="em-locate">
                    <?php echo $__env->make('partials.finder.icon', ['name' => 'navigation', 'size' => 15], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo e(__('finder_search.common.use_my_location')); ?>

                </button>
            </div>
            <p class="f-small f-muted" role="status" aria-live="polite" id="em-locate-status" style="margin-top:6px;">
                <?php if(request()->filled('latitude') && request()->filled('longitude')): ?><?php echo e(__('finder_search.common.location_active')); ?><?php endif; ?>
            </p>

            
            <p class="em-note f-small" style="margin-top:12px;color:var(--f-ink-soft);">
                <?php echo $__env->make('partials.finder.icon', ['name' => 'alert-circle', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <span><?php echo e(__('public.care_map_emergency.capability_unknown')); ?></span>
            </p>

            <?php $shown = collect($facilities)->take(40); ?>
            <div class="em-cards">
                <?php $__empty_1 = true; $__currentLoopData = $shown; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $phone   = $f->callable_phone ?? null;
                        $dist    = isset($f->distance) ? round($f->distance, 1) : null;
                        $address = collect([$f->address, $f->city, $f->region])->filter()->implode(', ');
                        $dirUrl  = ($f->latitude && $f->longitude)
                            ? 'https://www.google.com/maps/dir/?api=1&destination=' . $f->latitude . ',' . $f->longitude
                            : null;
                    ?>
                    <article class="f-card">
                        <div class="em-fc-top">
                            <h3 class="em-fc-name"><?php echo e($f->facility_name); ?></h3>
                            <?php if($dist !== null): ?>
                                <span class="f-badge em-fc-dist"><?php echo e(__('finder_search.common.distance_km', ['distance' => $dist])); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="em-fc-badges">
                            <span class="f-badge"><?php echo e(__('public.care_map_emergency.badge_hospital')); ?></span>
                            <span class="f-badge f-badge--warn"><?php echo e(__('public.care_map_emergency.call_before_travelling')); ?></span>
                            <?php if($f->emergency_contact): ?>
                                <span class="f-badge"><?php echo e(__('public.care_map_emergency.badge_emergency_line')); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="f-small em-fc-meta">
                            <?php if($address !== ''): ?>
                                <span><?php echo e($address); ?></span>
                            <?php endif; ?>
                            <span><?php if($phone): ?><?php echo e($phone); ?><?php else: ?><em class="f-muted"><?php echo e(__('public.care_map_emergency.no_phone_on_record')); ?></em><?php endif; ?></span>
                        </div>

                        <div class="em-fc-btns">
                            <?php if($phone): ?>
                                <a class="f-btn f-btn--primary em-fc-call" href="tel:<?php echo e($phone); ?>">
                                    <?php echo $__env->make('partials.finder.icon', ['name' => 'phone', 'size' => 16], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <?php echo e(__('public.care_map_emergency.action_call')); ?>

                                </a>
                            <?php endif; ?>
                            <?php if($dirUrl): ?>
                                <a class="f-btn" href="<?php echo e($dirUrl); ?>" target="_blank" rel="noopener noreferrer"><?php echo e(__('public.care_map_emergency.action_directions')); ?></a>
                            <?php endif; ?>
                            <a class="f-btn f-btn--ghost" href="<?php echo e(route('public.care-map.profile', $f->id)); ?>"><?php echo e(__('public.care_map_emergency.action_profile')); ?></a>
                        </div>
                    </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="f-card" style="text-align:center;padding:32px 20px;">
                        <?php echo $__env->make('partials.finder.icon', ['name' => 'map-pin', 'size' => 32, 'class' => 'f-muted'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <p style="font-weight:700;margin-top:10px;"><?php echo e(__('public.care_map_emergency.empty_title')); ?></p>
                        <p class="f-small f-muted" style="margin-top:6px;"><?php echo e(__('public.care_map_emergency.empty_sub')); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if(count($facilities) > $shown->count()): ?>
                <p class="f-small f-muted" style="margin-top:12px;"><?php echo e(__('finder_search.common.showing_first', ['count' => $shown->count()])); ?></p>
            <?php endif; ?>
        </div>
    </section>

    <footer class="f-section--tight" style="padding-block:20px;">
        <div class="f-container">
            <p class="f-small f-muted"><?php echo e(__('finder_search.common.info_may_change')); ?></p>
        </div>
    </footer>
</main>

<script>
(function () {
    var btn    = document.getElementById('em-locate');
    var status = document.getElementById('em-locate-status');
    if (!btn) return;
    var L = {
        locating: <?php echo json_encode(__('finder_search.common.locating'), 15, 512) ?>,
        failed:   <?php echo json_encode(__('finder_search.common.location_failed'), 15, 512) ?>
    };
    btn.addEventListener('click', function () {
        if (!navigator.geolocation) { status.textContent = L.failed; return; }
        status.textContent = L.locating;
        navigator.geolocation.getCurrentPosition(function (pos) {
            // The server sorts and bounds by distance — reload with coords.
            var p = new URLSearchParams(window.location.search);
            p.set('latitude',  pos.coords.latitude.toFixed(5));
            p.set('longitude', pos.coords.longitude.toFixed(5));
            if (!p.has('radius')) { p.set('radius', '50'); }
            window.location.search = p.toString();
        }, function () {
            status.textContent = L.failed;
        }, { timeout: 10000, maximumAge: 300000 });
    });
})();
</script>
</body>
</html>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\care_map\emergency.blade.php ENDPATH**/ ?>