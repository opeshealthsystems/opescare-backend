@extends('layouts.portal')

@section('title', __('catalogue.browse_title') . ' — OpesCare')


@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')

<div class="od-page">

<header class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('catalogue.browse_title') }}</h1>
        <p class="od-page__sub">{{ __('catalogue.browse_subtitle') }}</p>
    </div>
    <a href="{{ route('portals.facility.catalogue') }}" class="od-btn od-btn--secondary">
        <i data-lucide="arrow-left"></i> {{ __('catalogue.back_to_list') }}
    </a>
</header>

@if(session('success'))
<div class="odc-note" role="status"><i data-lucide="check-circle-2"></i><div class="odc-note__text">{{ session('success') }}</div></div>
@endif
@if(session('error'))
<div class="odc-note odc-note--error" role="alert"><i data-lucide="alert-circle"></i><div class="odc-note__text">{{ session('error') }}</div></div>
@endif

@if($dictionaryTotal === 0)
{{-- An empty dictionary and a search with no matches are different facts and
     must not read the same. Nothing here can be added until an importer runs. --}}
<div class="odc-note odc-note--warn">
    <i data-lucide="triangle-alert"></i>
    <div class="odc-note__text">{{ __('catalogue.browse_never_imported') }}</div>
</div>
@endif

<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('catalogue.browse_title') }}</h2>
        @if($dictionaryTotal !== 0)
            <span class="od-card__count">{{ __('catalogue.browse_total', ['count' => number_format($dictionaryTotal)]) }}</span>
        @endif
    </header>

    <div class="od-card__body">
        <form method="GET" action="{{ route('portals.facility.catalogue.browse') }}" class="odc-toolbar">
            <label class="od-field">
                <i data-lucide="search"></i>
                <input type="search" name="q" value="{{ request('q') }}" class="od-input"
                       placeholder="{{ __('catalogue.browse_placeholder') }}" aria-label="{{ __('catalogue.browse_placeholder') }}">
            </label>

            {{-- Built from what is actually loaded. A hardcoded pair of options would
                 have to be edited the day the imaging codes land. --}}
            <select name="system" class="od-select odc-toolbar__select" aria-label="{{ __('catalogue.all_systems') }}">
                <option value="">{{ __('catalogue.all_systems') }}</option>
                @foreach($systems as $s)
                <option value="{{ $s }}" @selected($system === $s)>{{ $s }}</option>
                @endforeach
            </select>

            <label class="odc-check">
                <input type="checkbox" name="common" value="1" @checked(request()->boolean('common'))>
                {{ __('catalogue.browse_only_common') }}
            </label>

            <button type="submit" class="od-btn od-btn--primary">
                <i data-lucide="search"></i> {{ __('catalogue.browse_search') }}
            </button>
        </form>
    </div>

    <div class="od-card__body od-card__body--flush">
    <div class="od-table-wrap">
        <table class="od-table">
            <thead>
                <tr>
                    <th class="od-table__num">#</th>
                    <th>{{ __('catalogue.col_code') }}</th>
                    <th>{{ __('catalogue.col_name') }}</th>
                    <th>{{ __('catalogue.action_add') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($codes as $code)
                @php $already = $existing[$code->system . '|' . $code->code] ?? null; @endphp
                <tr>
                    <td class="od-table__num">{{ ($codes->firstItem() ?? 1) + $loop->index }}</td>
                    <td>
                        <span class="od-table__name">{{ $code->code }}</span>
                        <span class="od-pill od-pill--neutral">{{ $code->system }}</span>
                    </td>
                    <td>
                        <span class="od-table__name">{{ $code->label }}</span>
                        @if($code->label !== $code->display)
                            <span class="od-table__sub">{{ $code->display }}</span>
                        @endif
                    </td>
                    <td>
                        @if($already && $already->is_active)
                            <span class="od-pill od-pill--ready">{{ __('catalogue.browse_already') }}</span>
                        @else
                            {{-- One form per row. It posts the system and the code —
                                 both checked against the dictionary server-side — and
                                 the facility's own fields. It does NOT post a
                                 facility_id: that comes from the session context and
                                 a field here would be a field to tamper with. --}}
                            <form method="POST" action="{{ route('portals.facility.catalogue.store') }}" class="odc-addrow">
                                @csrf
                                <input type="hidden" name="system" value="{{ $code->system }}">
                                <input type="hidden" name="code" value="{{ $code->code }}">

                                <input type="text" name="local_name" maxlength="200" class="od-input"
                                       placeholder="{{ __('catalogue.field_local_name') }}"
                                       aria-label="{{ __('catalogue.field_local_name') }}">

                                {{-- step=1: whole francs, XAF has no minor unit.
                                     Empty means "no published price", which is not
                                     zero and is never a condition of care. --}}
                                <input type="number" name="price_xaf" min="0" step="1" class="od-input"
                                       placeholder="{{ __('catalogue.field_price') }}"
                                       aria-label="{{ __('catalogue.field_price') }}">

                                <input type="text" name="turnaround" maxlength="120" class="od-input"
                                       placeholder="{{ __('catalogue.field_turnaround') }}"
                                       aria-label="{{ __('catalogue.field_turnaround') }}">

                                <button type="submit" class="od-btn od-btn--row">
                                    {{ $already ? __('catalogue.action_restore') : __('catalogue.action_add') }}
                                </button>
                                @if($already)
                                <span class="od-pill od-pill--pending">{{ __('catalogue.browse_already_retired') }}</span>
                                @endif
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">
                        <div class="od-empty">
                            <span class="od-empty__icon"><i data-lucide="search-x"></i></span>
                            <p class="od-empty__text">{{ __('catalogue.browse_no_results') }}</p>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    </div>
    @if($codes->hasPages())
    <footer class="od-card__foot">{{ $codes->links() }}</footer>
    @endif
</section>

</div>
@endsection
