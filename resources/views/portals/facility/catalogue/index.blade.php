@extends('layouts.portal')

@section('title', __('catalogue.page_title') . ' — OpesCare')

@section('breadcrumb_home', __('catalogue.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('catalogue.page_title'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('catalogue.page_title') }}</h1>
        <p class="page-subtitle">{{ __('catalogue.page_subtitle') }}</p>
    </div>
    <a href="{{ route('portals.facility.catalogue.browse') }}" class="btn btn-primary">
        <i data-lucide="plus"></i> {{ __('catalogue.action_browse') }}
    </a>
</div>

@if(session('success'))
<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
<div class="alert alert-danger mb-4"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>
@endif

{{-- Lab tests on this list are published to the public lab finder through
     the facility's public listing. With no listing linked they reach no
     patient, and the page must say so rather than let the manager believe
     the prices are out there. Linking is an admin decision, never done here. --}}
@if(! empty($unlinked))
<div class="banner banner--danger mb-4">
    <i data-lucide="unlink"></i>
    <div>
        <strong>{{ __('catalogue.unlinked_title') }}</strong><br>
        {{ __('catalogue.unlinked_body') }}
    </div>
</div>
@endif

{{-- A centre that offers imaging exams as well as lab tests publishes both
     from this one list, to two different public finders. Say how many reach
     each, from the published rows themselves. --}}
@if(! empty($mix) && $mix['imaging_catalogue'] > 0)
<div class="banner banner--info mb-4" data-testid="service-mix">
    <i data-lucide="scan-line"></i>
    <div>
        <strong>{{ __('catalogue.mix_title') }}</strong><br>
        {{ __('catalogue.mix_lab', ['catalogue' => $mix['lab_catalogue'], 'published' => $mix['lab_published']]) }}
        @if(Route::has('public.network.lab-test-finder'))
            <a href="{{ route('public.network.lab-test-finder') }}">{{ __('catalogue.mix_view_finder') }}</a>
        @endif
        <br>
        {{ __('catalogue.mix_imaging', ['catalogue' => $mix['imaging_catalogue'], 'published' => $mix['imaging_published']]) }}
        @if(Route::has('public.network.imaging-finder'))
            <a href="{{ route('public.network.imaging-finder') }}">{{ __('catalogue.mix_view_finder') }}</a>
        @endif
    </div>
</div>
@endif

{{-- Retired entries are one click away, not hidden. A manager who cannot see
     that a service was retired will simply add it again. --}}
<div class="filter-bar mb-4">
    <a href="{{ route('portals.facility.catalogue', ['system' => $system]) }}"
       class="btn btn-sm {{ $show === 'active' ? 'btn-primary' : 'btn-ghost' }}">
        <i data-lucide="list-checks"></i>
        {{ __('catalogue.tab_active', ['count' => number_format((int) ($counts->active ?? 0))]) }}
    </a>
    <a href="{{ route('portals.facility.catalogue', ['show' => 'retired', 'system' => $system]) }}"
       class="btn btn-sm {{ $show === 'retired' ? 'btn-primary' : 'btn-ghost' }}">
        <i data-lucide="archive"></i>
        {{ __('catalogue.tab_retired', ['count' => number_format((int) ($counts->retired ?? 0))]) }}
    </a>

    @if($systems->count() > 1)
    <form method="GET" action="{{ route('portals.facility.catalogue') }}">
        <input type="hidden" name="show" value="{{ $show }}">
        <select name="system" class="form-control">
            <option value="">{{ __('catalogue.all_systems') }}</option>
            @foreach($systems as $s)
            <option value="{{ $s }}" @selected($system === $s)>{{ $s }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-ghost btn-sm">
            <i data-lucide="filter"></i> {{ __('catalogue.filter_apply') }}
        </button>
    </form>
    @endif
</div>

<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>{{ __('catalogue.col_code') }}</th>
                <th>{{ __('catalogue.col_name') }}</th>
                <th>{{ __('catalogue.col_price') }}</th>
                <th>{{ __('catalogue.col_turnaround') }}</th>
                <th>{{ __('catalogue.col_actions') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($items as $item)
            @php $entry = $dictionary[$item->dictionaryKey()] ?? null; @endphp
            <tr>
                <td>
                    <code>{{ $item->code }}</code>
                    <div><span class="pill pill--muted">{{ $item->system }}</span></div>
                    {{-- Which public finder this row reaches: lab, imaging
                         (a LOINC imaging term OR an ICHI diagnostic-imaging
                         stem), or none. A retired row reaches neither. --}}
                    @php $finder = $item->is_active ? \App\Services\DiagnosticCentre\ServiceMix::finderFor($item->system, $entry, $item->code) : null; @endphp
                    @if($finder === \App\Services\DiagnosticCentre\ServiceMix::IMAGING)
                        <div class="text-muted text-sm" data-finder="imaging"><i data-lucide="scan-line"></i> {{ __('catalogue.mix_badge_imaging') }}</div>
                    @elseif($finder === \App\Services\DiagnosticCentre\ServiceMix::LAB)
                        <div class="text-muted text-sm" data-finder="lab"><i data-lucide="flask-conical"></i> {{ __('catalogue.mix_badge_lab') }}</div>
                    @else
                        <div class="text-muted text-sm" data-finder="none"><i data-lucide="eye-off"></i> {{ __('diagnostic_desk.badge_not_published') }}</div>
                    @endif
                </td>
                <td>
                    {{ $item->displayName($entry) }}
                    @if($entry)
                        {{-- The published label stays visible under the local
                             name: the counter says "Test VIH", the wire carries
                             the code, and hiding one from the other is how the
                             two quietly stop meaning the same thing. --}}
                        <div class="text-muted text-sm">{{ $entry->label }}</div>
                    @else
                        {{-- Not hidden. The facility did offer this; what we
                             cannot do is name it from a dictionary that does
                             not currently hold the code. --}}
                        <div class="text-muted text-sm">
                            <i data-lucide="help-circle"></i> {{ __('catalogue.unresolved_code') }}
                        </div>
                    @endif
                    @if($item->notes)
                        <div class="text-muted text-sm">{{ $item->notes }}</div>
                    @endif
                    @if(! $item->is_active && $item->deactivated_at)
                        <div class="text-muted text-sm">
                            {{ __('catalogue.retired_on', ['date' => $item->deactivated_at->format('d/m/Y')]) }}
                        </div>
                    @endif
                </td>
                <td>
                    @if($item->price_xaf === null)
                        {{-- Absent is not zero, and the screen has to say which
                             one it is. --}}
                        <span class="text-muted" title="{{ __('catalogue.price_none_hint') }}">{{ __('catalogue.price_none') }}</span>
                    @else
                        {{ number_format($item->price_xaf, 0, ',', ' ') }} FCFA
                    @endif
                </td>
                <td>{{ $item->turnaround ?: '—' }}</td>
                <td>
                    <details>
                        <summary class="btn btn-ghost btn-sm"><i data-lucide="pencil"></i></summary>
                        <form method="POST" action="{{ route('portals.facility.catalogue.update', $item->id) }}" class="stack mt-2">
                            @csrf
                            {{-- What this form saw: a save made by another role
                                 after it was opened is not overwritten. --}}
                            <input type="hidden" name="seen_updated_at" value="{{ $item->updated_at?->getTimestamp() }}">
                            {{-- No facility_id field, on purpose: the facility
                                 comes from the session context, and a field here
                                 would be a field to tamper with. The system and
                                 the code are not editable either — they are the
                                 published identity of the service. --}}
                            <label class="form-label" for="local_name_{{ $item->id }}">{{ __('catalogue.field_local_name') }}</label>
                            <input id="local_name_{{ $item->id }}" type="text" name="local_name" maxlength="200"
                                   value="{{ $item->local_name }}" class="form-control">

                            <label class="form-label" for="price_{{ $item->id }}">{{ __('catalogue.field_price') }}</label>
                            {{-- step=1: whole francs. XAF has no minor unit. --}}
                            <input id="price_{{ $item->id }}" type="number" name="price_xaf" min="0" step="1"
                                   value="{{ $item->price_xaf }}" class="form-control">
                            <span class="text-muted text-sm">{{ __('catalogue.field_price_hint') }}</span>

                            <label class="form-label" for="turnaround_{{ $item->id }}">{{ __('catalogue.field_turnaround') }}</label>
                            <input id="turnaround_{{ $item->id }}" type="text" name="turnaround" maxlength="120"
                                   value="{{ $item->turnaround }}" class="form-control">

                            <label class="form-label" for="notes_{{ $item->id }}">{{ __('catalogue.field_notes') }}</label>
                            <textarea id="notes_{{ $item->id }}" name="notes" maxlength="2000" class="form-control">{{ $item->notes }}</textarea>

                            <button type="submit" class="btn btn-primary btn-sm">
                                <i data-lucide="save"></i> {{ __('catalogue.action_save') }}
                            </button>
                        </form>
                    </details>

                    @if($item->is_active)
                    <form method="POST" action="{{ route('portals.facility.catalogue.retire', $item->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-sm">
                            <i data-lucide="circle-slash"></i> {{ __('catalogue.action_retire') }}
                        </button>
                    </form>
                    @else
                    <form method="POST" action="{{ route('portals.facility.catalogue.restore', $item->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-sm">
                            <i data-lucide="rotate-ccw"></i> {{ __('catalogue.action_restore') }}
                        </button>
                    </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center text-muted">
                    {{ $show === 'retired' ? __('catalogue.empty_retired') : __('catalogue.empty_active') }}
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">{{ $items->links() }}</div>
@endsection
