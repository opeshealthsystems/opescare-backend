@extends('layouts.portal')

@section('title', __('catalogue.page_title') . ' — OpesCare')

@section('breadcrumb_home', __('catalogue.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('catalogue.page_title'))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')

<div class="od-page">

<header class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('catalogue.page_title') }}</h1>
        <p class="od-page__sub">{{ __('catalogue.page_subtitle') }}</p>
    </div>
    <a href="{{ route('portals.facility.catalogue.browse') }}" class="od-btn od-btn--primary">
        <i data-lucide="plus"></i> {{ __('catalogue.action_browse') }}
    </a>
</header>

@if(session('success'))
<div class="odc-note" role="status"><i data-lucide="check-circle-2"></i><div class="odc-note__text">{{ session('success') }}</div></div>
@endif
@if(session('error'))
<div class="odc-note odc-note--error" role="alert"><i data-lucide="alert-circle"></i><div class="odc-note__text">{{ session('error') }}</div></div>
@endif

{{-- Lab tests on this list are published to the public lab finder through
     the facility's public listing. With no listing linked they reach no
     patient, and the page must say so rather than let the manager believe
     the prices are out there. Linking is an admin decision, never done here. --}}
@if(! empty($unlinked))
<div class="odc-note odc-note--error">
    <i data-lucide="unlink"></i>
    <div class="odc-note__text">
        <span class="odc-strong">{{ __('catalogue.unlinked_title') }}</span><br>
        {{ __('catalogue.unlinked_body') }}
    </div>
</div>
@endif

{{-- A centre that offers imaging exams as well as lab tests publishes both
     from this one list, to two different public finders. Say how many reach
     each, from the published rows themselves. --}}
@if(! empty($mix) && $mix['imaging_catalogue'] > 0)
<div class="odc-note odc-note--info" data-testid="service-mix">
    <i data-lucide="scan-line"></i>
    <div class="odc-note__text">
        <span class="odc-strong">{{ __('catalogue.mix_title') }}</span><br>
        {{ __('catalogue.mix_lab', ['catalogue' => $mix['lab_catalogue'], 'published' => $mix['lab_published']]) }}
        @if(Route::has('public.network.lab-test-finder'))
            <a href="{{ route('public.network.lab-test-finder') }}" class="od-card__link">{{ __('catalogue.mix_view_finder') }}</a>
        @endif
        <br>
        {{ __('catalogue.mix_imaging', ['catalogue' => $mix['imaging_catalogue'], 'published' => $mix['imaging_published']]) }}
        @if(Route::has('public.network.imaging-finder'))
            <a href="{{ route('public.network.imaging-finder') }}" class="od-card__link">{{ __('catalogue.mix_view_finder') }}</a>
        @endif
    </div>
</div>
@endif

<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('catalogue.page_title') }} <span class="od-card__count">({{ number_format((int) ($show === 'retired' ? ($counts->retired ?? 0) : ($counts->active ?? 0))) }})</span></h2>
    </header>

    {{-- Retired entries are one click away, not hidden. A manager who cannot see
         that a service was retired will simply add it again. --}}
    <div class="od-card__body">
        <nav class="od-tabs" aria-label="{{ __('catalogue.page_title') }}">
            <a href="{{ route('portals.facility.catalogue', ['system' => $system]) }}"
               class="od-tab {{ $show === 'active' ? 'is-active' : '' }}" @if($show === 'active') aria-current="page" @endif>
                <span class="od-tab__dot od-tab__dot--ready"></span>
                {{ __('catalogue.tab_active', ['count' => number_format((int) ($counts->active ?? 0))]) }}
            </a>
            <a href="{{ route('portals.facility.catalogue', ['show' => 'retired', 'system' => $system]) }}"
               class="od-tab {{ $show === 'retired' ? 'is-active' : '' }}" @if($show === 'retired') aria-current="page" @endif>
                <span class="od-tab__dot"></span>
                {{ __('catalogue.tab_retired', ['count' => number_format((int) ($counts->retired ?? 0))]) }}
            </a>
        </nav>

        @if($systems->count() > 1)
        <form method="GET" action="{{ route('portals.facility.catalogue') }}" class="odc-toolbar">
            <input type="hidden" name="show" value="{{ $show }}">
            <select name="system" class="od-select" aria-label="{{ __('catalogue.all_systems') }}">
                <option value="">{{ __('catalogue.all_systems') }}</option>
                @foreach($systems as $s)
                <option value="{{ $s }}" @selected($system === $s)>{{ $s }}</option>
                @endforeach
            </select>
            <button type="submit" class="od-btn od-btn--secondary">
                <i data-lucide="filter"></i> {{ __('catalogue.filter_apply') }}
            </button>
        </form>
        @endif
    </div>

    <div class="od-card__body od-card__body--flush">
    <div class="od-table-wrap">
        <table class="od-table">
            <thead>
                <tr>
                    <th class="od-table__num">#</th>
                    <th>{{ __('catalogue.col_code') }}</th>
                    <th>{{ __('catalogue.col_name') }}</th>
                    <th class="odc-num">{{ __('catalogue.col_price') }}</th>
                    <th>{{ __('catalogue.col_turnaround') }}</th>
                    <th>{{ __('catalogue.col_actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($items as $item)
                @php $entry = $dictionary[$item->dictionaryKey()] ?? null; @endphp
                <tr>
                    <td class="od-table__num">{{ ($items->firstItem() ?? 1) + $loop->index }}</td>
                    <td>
                        <span class="od-table__name">{{ $item->code }}</span>
                        <span class="od-pill od-pill--neutral">{{ $item->system }}</span>
                        {{-- Which public finder this row reaches: lab, imaging
                             (a LOINC imaging term OR an ICHI diagnostic-imaging
                             stem), or none. A retired row reaches neither. --}}
                        @php $finder = $item->is_active ? \App\Services\DiagnosticCentre\ServiceMix::finderFor($item->system, $entry, $item->code) : null; @endphp
                        @if($finder === \App\Services\DiagnosticCentre\ServiceMix::IMAGING)
                            <span class="od-table__sub odc-finder" data-finder="imaging"><i data-lucide="scan-line"></i> {{ __('catalogue.mix_badge_imaging') }}</span>
                        @elseif($finder === \App\Services\DiagnosticCentre\ServiceMix::LAB)
                            <span class="od-table__sub odc-finder" data-finder="lab"><i data-lucide="flask-conical"></i> {{ __('catalogue.mix_badge_lab') }}</span>
                        @else
                            <span class="od-table__sub odc-finder" data-finder="none"><i data-lucide="eye-off"></i> {{ __('diagnostic_desk.badge_not_published') }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="od-table__name">{{ $item->displayName($entry) }}</span>
                        @if($entry)
                            {{-- The published label stays visible under the local
                                 name: the counter says "Test VIH", the wire carries
                                 the code, and hiding one from the other is how the
                                 two quietly stop meaning the same thing. --}}
                            <span class="od-table__sub">{{ $entry->label }}</span>
                        @else
                            {{-- Not hidden. The facility did offer this; what we
                                 cannot do is name it from a dictionary that does
                                 not currently hold the code. --}}
                            <span class="od-table__sub odc-finder">
                                <i data-lucide="help-circle"></i> {{ __('catalogue.unresolved_code') }}
                            </span>
                        @endif
                        @if($item->notes)
                            <span class="od-table__sub">{{ $item->notes }}</span>
                        @endif
                        @if(! $item->is_active && $item->deactivated_at)
                            <span class="od-table__sub">
                                {{ __('catalogue.retired_on', ['date' => $item->deactivated_at->format('d/m/Y')]) }}
                            </span>
                        @endif
                    </td>
                    <td class="odc-num">
                        @if($item->price_xaf === null)
                            {{-- Absent is not zero, and the screen has to say which
                                 one it is. --}}
                            <span class="od-pill od-pill--neutral" title="{{ __('catalogue.price_none_hint') }}">{{ __('catalogue.price_none') }}</span>
                        @else
                            <span class="odc-strong">{{ number_format($item->price_xaf, 0, ',', ' ') }} FCFA</span>
                        @endif
                    </td>
                    <td>{{ $item->turnaround ?: '—' }}</td>
                    <td class="od-table__actions">
                        <div class="odc-row-actions">
                        <details class="odc-edit">
                            <summary class="od-btn od-btn--row">{{ __('hospital_desk.b_action_edit') }}</summary>
                            <form method="POST" action="{{ route('portals.facility.catalogue.update', $item->id) }}" class="odc-edit__form">
                                @csrf
                                {{-- What this form saw: a save made by another role
                                     after it was opened is not overwritten. --}}
                                <input type="hidden" name="seen_updated_at" value="{{ $item->updated_at?->getTimestamp() }}">
                                {{-- No facility_id field, on purpose: the facility
                                     comes from the session context, and a field here
                                     would be a field to tamper with. The system and
                                     the code are not editable either — they are the
                                     published identity of the service. --}}
                                <label class="odc-label" for="local_name_{{ $item->id }}">{{ __('catalogue.field_local_name') }}</label>
                                <input id="local_name_{{ $item->id }}" type="text" name="local_name" maxlength="200"
                                       value="{{ $item->local_name }}" class="od-input">

                                <label class="odc-label" for="price_{{ $item->id }}">{{ __('catalogue.field_price') }}</label>
                                {{-- step=1: whole francs. XAF has no minor unit. --}}
                                <input id="price_{{ $item->id }}" type="number" name="price_xaf" min="0" step="1"
                                       value="{{ $item->price_xaf }}" class="od-input">
                                <span class="odc-hint">{{ __('catalogue.field_price_hint') }}</span>

                                <label class="odc-label" for="turnaround_{{ $item->id }}">{{ __('catalogue.field_turnaround') }}</label>
                                <input id="turnaround_{{ $item->id }}" type="text" name="turnaround" maxlength="120"
                                       value="{{ $item->turnaround }}" class="od-input">

                                <label class="odc-label" for="notes_{{ $item->id }}">{{ __('catalogue.field_notes') }}</label>
                                <textarea id="notes_{{ $item->id }}" name="notes" maxlength="2000" class="od-input odc-textarea">{{ $item->notes }}</textarea>

                                <button type="submit" class="od-btn od-btn--primary od-btn--sm">
                                    <i data-lucide="save"></i> {{ __('catalogue.action_save') }}
                                </button>
                            </form>
                        </details>

                        @if($item->is_active)
                        <form method="POST" action="{{ route('portals.facility.catalogue.retire', $item->id) }}" class="odc-inline">
                            @csrf
                            <button type="submit" class="od-btn od-btn--ghost od-btn--sm">
                                <i data-lucide="circle-slash"></i> {{ __('catalogue.action_retire') }}
                            </button>
                        </form>
                        @else
                        <form method="POST" action="{{ route('portals.facility.catalogue.restore', $item->id) }}" class="odc-inline">
                            @csrf
                            <button type="submit" class="od-btn od-btn--ghost od-btn--sm">
                                <i data-lucide="rotate-ccw"></i> {{ __('catalogue.action_restore') }}
                            </button>
                        </form>
                        @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="od-empty">
                            <span class="od-empty__icon"><i data-lucide="list-plus"></i></span>
                            <p class="od-empty__text">{{ $show === 'retired' ? __('catalogue.empty_retired') : __('catalogue.empty_active') }}</p>
                            <a href="{{ route('portals.facility.catalogue.browse') }}" class="od-btn od-btn--primary"><i data-lucide="plus"></i> {{ __('catalogue.action_browse') }}</a>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    </div>
    @if($items->hasPages())
    <footer class="od-card__foot">{{ $items->links() }}</footer>
    @endif
</section>

</div>
@endsection
