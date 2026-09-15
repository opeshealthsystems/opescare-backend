@php
    $base = \Illuminate\Support\Str::before($code, '.');
    $label = \Illuminate\Support\Facades\Lang::has('dhis2.ind_' . $base)
        ? __('dhis2.ind_' . $base, ['name' => $meta['vaccine'] ?? $meta['test'] ?? \Illuminate\Support\Str::after($code, '.')])
        : $code;
@endphp{{ $label }}
