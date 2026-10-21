{{--
    Embedded in-app map. Include with a collection of facilities:
        @include('partials.finder.map', ['facilities' => $facilities, 'single' => false])
    Optional: 'mapId', 'height', 'single'. Facilities may be models or raw rows;
    field names are normalised here (facility_name|name, facility_type|type, …).
    Self-contained: loads Leaflet + finder-map.js once, no external map app.

    Pick mode (a settings form choosing ONE position instead of showing many):
        @include('partials.finder.map', ['pick' => ['lat' => 'latitude', 'lng' => 'longitude'], 'pickType' => 'pharmacy'])
    'pick' names the two input ids; a draggable pin writes them on every
    click/drag and follows them when typed. 'facilities' is not needed.
--}}
@php
    // Optional: 'numbered' => true labels pins 1..N in list order.
    $mapId  = $mapId  ?? 'finder-map';
    $height = $height ?? '420px';
    $single = $single ?? false;
    $pick   = $pick   ?? null;
    $pickType = $pickType ?? 'other';
    $facilities = $facilities ?? [];
    $l = app()->getLocale();

    $numbered = $numbered ?? false;
    $points = collect($facilities)
        ->values()
        ->map(function ($f, $i) use ($numbered) {
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
                // Numbered from the list order BEFORE coordinate filtering, so
                // pin N is always card N even when a card has no coordinates.
                'n'        => $numbered ? $i + 1 : null,
            ];
        })
        ->filter()
        ->values();
@endphp

@once
    {{-- Inline + @once: loads exactly once per page wherever the first map sits,
         with no dependency on the layout exposing a @stack. --}}
    <link rel="stylesheet" href="{{ asset('css/leaflet.css') }}">
    <script src="{{ asset('js/leaflet.js') }}" defer></script>
    <script src="{{ asset('js/finder-map.js') }}?v={{ @filemtime(public_path('js/finder-map.js')) ?: 1 }}" defer></script>
    <script @cspNonce>
            window.FINDER_MAP_I18N = {
                view: @json(__('finder_map.view', [], $l)),
                call: @json(__('finder_map.call', [], $l)),
                directions: @json(__('finder_map.directions', [], $l)),
                locate: @json(__('finder_map.locate', [], $l)),
                you: @json(__('finder_map.you', [], $l)),
                distance: @json(__('finder_map.distance', [], $l)),
                straightLine: @json(__('finder_map.straight_line', [], $l)),
                geoDenied: @json(__('finder_map.geo_denied', [], $l)),
            };
        </script>
        <style>
            .finder-map-shell{position:relative;width:100%;height:var(--fm-h,420px);border-radius:14px;overflow:hidden;border:1px solid rgba(15,76,129,.12);background:#eef2f6}
            .finder-map-shell [data-finder-map],.finder-map-shell [data-finder-pick]{width:100%;height:100%}
            .finder-map-locate{width:34px;height:34px;display:flex;align-items:center;justify-content:center;background:#fff;color:#0F4C81;border:0;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.2);cursor:pointer}
            .finder-map-locate:hover{background:#f1f5f9}
            .finder-user-dot{width:22px;height:22px;position:relative}
            .finder-user-dot span{position:absolute;inset:5px;background:#2563EB;border:2px solid #fff;border-radius:50%;box-shadow:0 0 0 4px rgba(37,99,235,.25)}
            .finder-num-pin{display:grid;place-items:center;width:30px;height:30px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);background:var(--pin,#0F4C81);border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.25)}
            .finder-num-pin b{transform:rotate(45deg);color:#fff;font:700 12px Inter,system-ui,sans-serif}
            .finder-map-popup{padding:.7rem;font-family:Inter,system-ui,sans-serif;min-width:170px}
            .finder-map-popup .fmp-name{font-size:.85rem;font-weight:700;margin-bottom:.15rem;color:#0f172a}
            .finder-map-popup .fmp-city{font-size:.72rem;color:#64748b;margin-bottom:.5rem}
            .finder-map-popup .fmp-actions{display:flex;gap:.75rem;flex-wrap:wrap}
            .finder-map-popup .fmp-link{font-size:.75rem;font-weight:600;color:#0F4C81;text-decoration:none;background:none;border:0;padding:0;cursor:pointer}
            .finder-map-popup .fmp-link:hover{text-decoration:underline}
    </style>
@endonce

@if($pick)
<div class="finder-map-shell" style="--fm-h: {{ $height }}">
    <div data-finder-pick="{{ $pick['lat'] }},{{ $pick['lng'] }}" data-finder-pick-type="{{ $pickType }}" id="{{ $mapId }}" role="application" aria-label="{{ __('finder_map.aria', [], $l) }}"></div>
</div>
@else
<div class="finder-map-shell" style="--fm-h: {{ $height }}">
    <div data-finder-map id="{{ $mapId }}" role="application" aria-label="{{ __('finder_map.aria', [], $l) }}"></div>
    <script type="application/json" class="finder-map-data">@json(['single' => (bool) $single, 'facilities' => $points, 'pinColor' => $pinColor ?? null])</script>
</div>
@endif
