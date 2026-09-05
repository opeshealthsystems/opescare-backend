
<?php
    $mapId  = $mapId  ?? 'finder-map';
    $height = $height ?? '420px';
    $single = $single ?? false;
    $l = app()->getLocale();

    $points = collect($facilities)
        ->map(function ($f) {
            $lat = $f->latitude  ?? null;
            $lng = $f->longitude ?? null;
            if ($lat === null || $lng === null) {
                return null;
            }
            $phone = method_exists($f, 'dialablePhone') ? $f->dialablePhone() : ($f->phone ?? null);
            $id    = $f->id ?? null;

            return [
                'id'       => (string) $id,
                'name'     => $f->facility_name ?? $f->name ?? '',
                'lat'      => (float) $lat,
                'lng'      => (float) $lng,
                'city'     => $f->city ?? null,
                'type'     => strtolower(str_replace(' ', '_', $f->facility_type ?? $f->type ?? 'other')),
                'url'      => $id ? route('public.care-map.profile', array_filter(['id' => $id, 'lang' => request('lang')])) : null,
                'phone'    => $phone,
                'distance' => isset($f->distance) ? round((float) $f->distance, 1) : null,
            ];
        })
        ->filter()
        ->values();
?>

<?php if (! $__env->hasRenderedOnce('2b22ccb8-ec3a-48d1-b342-0b8ffef80352')): $__env->markAsRenderedOnce('2b22ccb8-ec3a-48d1-b342-0b8ffef80352'); ?>
    
    <link rel="stylesheet" href="<?php echo e(asset('css/leaflet.css')); ?>">
    <script src="<?php echo e(asset('js/leaflet.js')); ?>" defer></script>
    <script src="<?php echo e(asset('js/finder-map.js')); ?>" defer></script>
    <script>
            window.FINDER_MAP_I18N = {
                view: <?php echo json_encode(__('finder_map.view', [], $l) ?: 'View') ?>,
                call: <?php echo json_encode(__('finder_map.call', [], $l) ?: 'Call') ?>,
                directions: <?php echo json_encode(__('finder_map.directions', [], $l) ?: 'Directions') ?>,
                locate: <?php echo json_encode(__('finder_map.locate', [], $l) ?: 'Show my location') ?>,
                you: <?php echo json_encode(__('finder_map.you', [], $l) ?: 'You are here') ?>,
                distance: <?php echo json_encode(__('finder_map.distance', [], $l) ?: 'Distance') ?>,
                straightLine: <?php echo json_encode(__('finder_map.straight_line', [], $l) ?: 'straight line') ?>,
                geoDenied: <?php echo json_encode(__('finder_map.geo_denied', [], $l) ?: 'Location unavailable. Please allow location access.') ?>,
            };
        </script>
        <style>
            .finder-map-shell{position:relative;width:100%;height:var(--fm-h,420px);border-radius:14px;overflow:hidden;border:1px solid rgba(15,76,129,.12);background:#eef2f6}
            .finder-map-shell [data-finder-map]{width:100%;height:100%}
            .finder-map-locate{width:34px;height:34px;display:flex;align-items:center;justify-content:center;background:#fff;color:#0F4C81;border:0;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.2);cursor:pointer}
            .finder-map-locate:hover{background:#f1f5f9}
            .finder-user-dot{width:22px;height:22px;position:relative}
            .finder-user-dot span{position:absolute;inset:5px;background:#2563EB;border:2px solid #fff;border-radius:50%;box-shadow:0 0 0 4px rgba(37,99,235,.25)}
            .finder-map-popup{padding:.7rem;font-family:Inter,system-ui,sans-serif;min-width:170px}
            .finder-map-popup .fmp-name{font-size:.85rem;font-weight:700;margin-bottom:.15rem;color:#0f172a}
            .finder-map-popup .fmp-city{font-size:.72rem;color:#64748b;margin-bottom:.5rem}
            .finder-map-popup .fmp-actions{display:flex;gap:.75rem;flex-wrap:wrap}
            .finder-map-popup .fmp-link{font-size:.75rem;font-weight:600;color:#0F4C81;text-decoration:none;background:none;border:0;padding:0;cursor:pointer}
            .finder-map-popup .fmp-link:hover{text-decoration:underline}
    </style>
<?php endif; ?>

<div class="finder-map-shell" style="--fm-h: <?php echo e($height); ?>">
    <div data-finder-map id="<?php echo e($mapId); ?>" role="application" aria-label="<?php echo e(__('finder_map.aria', [], $l) ?: 'Facility map'); ?>"></div>
    <script type="application/json" class="finder-map-data"><?php echo json_encode(['single' => (bool) $single, 'facilities' => $points], 512) ?></script>
</div>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views/partials/finder/map.blade.php ENDPATH**/ ?>