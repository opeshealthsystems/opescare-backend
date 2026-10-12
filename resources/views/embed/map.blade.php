<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Map</title>
    <link rel="stylesheet" href="{{ asset('css/leaflet.css') }}">
    <style>
        html, body { height: 100%; margin: 0; padding: 0; background: #eef2f6; }
        #embed-shell { position: absolute; inset: 0; }
        #embed-map { width: 100%; height: 100%; }
        /* A thumbnail is ~110pt square. OpenStreetMap's credit is a licence
           condition and stays, but it has to stay INSIDE the box: on three
           wrapped lines it spilled out under the rounded corner and sat
           beneath the host's own "Map" button. */
        body.is-static .leaflet-control-attribution {
            font-size: 9px; line-height: 12px; padding: 0 4px;
            max-width: 100%; white-space: nowrap;
            overflow: hidden; text-overflow: ellipsis;
        }
        .finder-map-locate { width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; background: #fff; color: #0F4C81; border: 0; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.25); cursor: pointer; }
        /* The reference's pin label: a small white plate under the pin. */
        .finder-pin-label.leaflet-tooltip {
            background: #fff; color: #0f172a; border: 0; border-radius: 5px;
            box-shadow: 0 1px 4px rgba(0,0,0,.28); padding: 2px 6px;
            font-family: -apple-system, system-ui, Roboto, sans-serif;
            font-size: 11px; font-weight: 600; line-height: 14px;
            /* Natural width up to a cap: without it Leaflet sizes the
               plate to its narrowest word and "Hopital Central de Yaounde"
               came out one word per line. */
            width: max-content; max-width: 150px;
            white-space: normal; text-align: center;
        }
        .finder-pin-label.leaflet-tooltip::before { display: none; }
        .finder-user-dot { width: 22px; height: 22px; position: relative; }
        .finder-user-dot span { position: absolute; inset: 5px; background: #2563EB; border: 2px solid #fff; border-radius: 50%; box-shadow: 0 0 0 4px rgba(37,99,235,.25); }
        .finder-map-popup { padding: .7rem; font-family: -apple-system, system-ui, Roboto, sans-serif; min-width: 160px; }
        .finder-map-popup .fmp-name { font-size: .9rem; font-weight: 700; margin-bottom: .15rem; color: #0f172a; }
        .finder-map-popup .fmp-city { font-size: .75rem; color: #64748b; margin-bottom: .5rem; }
        .finder-map-popup .fmp-actions { display: flex; gap: .8rem; flex-wrap: wrap; }
        .finder-map-popup .fmp-link { font-size: .8rem; font-weight: 600; color: #0F4C81; text-decoration: none; background: none; border: 0; padding: 0; cursor: pointer; }
    </style>
</head>
<body @class(['is-static' => request()->query('ui') === 'static'])>
@php
    // Accept EITHER ?markers=<url-encoded JSON array of {lat,lng,name,city,distance,id}>
    // OR a single facility via ?lat=&lng=&name=&city=. Everything is optional and
    // defensively coerced — a bad param just yields an empty map centred on Cameroon.
    $raw = request('markers');
    $list = [];
    if ($raw) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $list = $decoded;
        }
    } elseif (request('lat') !== null && request('lng') !== null) {
        $list = [[
            'lat'  => request('lat'),
            'lng'  => request('lng'),
            'name' => request('name', ''),
            'city' => request('city', ''),
            'id'   => request('id', '0'),
        ]];
    }

    $points = collect($list)->map(function ($m, $i) {
        $lat = is_array($m) ? ($m['lat'] ?? null) : null;
        $lng = is_array($m) ? ($m['lng'] ?? null) : null;
        if ($lat === null || $lng === null || !is_numeric($lat) || !is_numeric($lng)) {
            return null;
        }
        return [
            'id'       => (string) ($m['id'] ?? $i),
            'name'     => (string) ($m['name'] ?? ''),
            'lat'      => (float) $lat,
            'lng'      => (float) $lng,
            'city'     => $m['city'] ?? null,
            'type'     => strtolower((string) ($m['type'] ?? 'other')),
            'phone'    => $m['phone'] ?? null,
            // No 'url' on purpose: inside an app WebView we do NOT want the marker
            // popup to navigate the frame away to a full web page.
            'distance' => isset($m['distance']) && is_numeric($m['distance']) ? round((float) $m['distance'], 1) : null,
        ];
    })->filter()->values();

    $single = $points->count() === 1;
    $l = app()->getLocale();
@endphp

<div id="embed-shell">
    <div data-finder-map id="embed-map" role="application"></div>
    <script type="application/json" class="finder-map-data">@json(['single' => $single, 'facilities' => $points])</script>
</div>

<script @cspNonce>
    window.FINDER_MAP_I18N = {
        call: @json(__('finder_map.call', [], $l)),
        directions: @json(__('finder_map.directions', [], $l)),
        locate: @json(__('finder_map.locate', [], $l)),
        you: @json(__('finder_map.you', [], $l)),
        distance: @json(__('finder_map.distance', [], $l)),
        straightLine: @json(__('finder_map.straight_line', [], $l)),
        geoDenied: @json(__('finder_map.geo_denied', [], $l)),
    };
    // Auto-offer directions on load when the host asks for it (?directions=1).
    @if(request('directions'))
    window.addEventListener('load', function () {
        setTimeout(function () {
            var inst = window.FinderMap && window.FinderMap.instances && window.FinderMap.instances[0];
            if (inst) inst.locate();
        }, 400);
    });
    @endif
</script>
<script src="{{ asset('js/leaflet.js') }}"></script>
<script src="{{ asset('js/finder-map.js') }}?v={{ @filemtime(public_path('js/finder-map.js')) ?: 1 }}"></script>
</body>
</html>
