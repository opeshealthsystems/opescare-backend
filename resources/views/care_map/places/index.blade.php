{{-- /care-map/places — hub of every qualifying type × city page (CarePlaces::combos()). --}}
@extends('layouts.finder')
@section('title', __('care_places.hub.title'))
@section('description', \Illuminate\Support\Str::limit(__('care_places.hub.description'), 155, ''))
@push('head')
@include('public.partials._od_pages_css')
<style>
.cp-city { margin-top: 20px; }
.cp-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
</style>
@endpush
@php $cpLang = request('lang') === 'fr' ? ['lang' => 'fr'] : []; @endphp

@section('content')
<div class="odp">
<section class="f-section--tight odp-pad-y">
    <div class="f-container">
        <nav aria-label="breadcrumb" class="f-small f-muted">
            <a href="{{ route('public.care-map', $cpLang) }}">{{ __('finder_seo.breadcrumbs.care_map') }}</a>
        </nav>
        <h1 class="f-h2 odp-title">{{ __('care_places.hub.h1') }}</h1>
        <p class="odp-mt-16">{{ __('care_places.hub.intro', ['combos' => $comboCount, 'cities' => count($byCity), 'min' => \App\Support\CarePlaces::MIN_LISTINGS]) }}</p>

        @forelse ($byCity as $city)
            <div class="cp-city">
                <h2 class="f-h3">
                    @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 18])
                    {{ $city['city'] }}
                    <span class="f-small f-muted">{{ __('care_places.hub.listings', ['count' => $city['total']]) }}</span>
                </h2>
                <div class="cp-chips">
                    @foreach ($city['combos'] as $c)
                        <a class="f-btn f-btn--ghost" href="{{ route('public.care-map.place', ['type' => $c['type_slug'], 'city' => $c['city_slug']] + $cpLang) }}">{{ __('care_places.place.title', ['types' => __('care_places.types.' . $c['type']), 'city' => $c['city']]) }} ({{ $c['count'] }})</a>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="odp-mt-16">{{ __('care_places.hub.empty') }}</p>
        @endforelse
    </div>
</section>
</div>
@endsection
