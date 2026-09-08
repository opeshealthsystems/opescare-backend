@extends('layouts.portal')

@section('title', __('catalogue.browse_title') . ' — OpesCare')

@section('breadcrumb_home', __('catalogue.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('catalogue.browse_title'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('catalogue.browse_title') }}</h1>
        <p class="page-subtitle">{{ __('catalogue.browse_subtitle') }}</p>
    </div>
    <a href="{{ route('portals.facility.catalogue') }}" class="btn btn-ghost">
        <i data-lucide="arrow-left"></i> {{ __('catalogue.back_to_list') }}
    </a>
</div>

@if(session('success'))
<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
<div class="alert alert-danger mb-4"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>
@endif

@if($dictionaryTotal === 0)
{{-- An empty dictionary and a search with no matches are different facts and
     must not read the same. Nothing here can be added until an importer runs. --}}
<div class="alert alert-warning mb-4">
    <i data-lucide="triangle-alert"></i>
    <div>{{ __('catalogue.browse_never_imported') }}</div>
</div>
@else
<p class="text-muted text-sm mb-4">{{ __('catalogue.browse_total', ['count' => number_format($dictionaryTotal)]) }}</p>
@endif

<form method="GET" action="{{ route('portals.facility.catalogue.browse') }}" class="filter-bar mb-6">
    <input type="search" name="q" value="{{ request('q') }}" class="form-control"
           placeholder="{{ __('catalogue.browse_placeholder') }}">

    {{-- Built from what is actually loaded. A hardcoded pair of options would
         have to be edited the day the imaging codes land. --}}
    <select name="system" class="form-control">
        <option value="">{{ __('catalogue.all_systems') }}</option>
        @foreach($systems as $s)
        <option value="{{ $s }}" @selected($system === $s)>{{ $s }}</option>
        @endforeach
    </select>

    <label class="checkbox">
        <input type="checkbox" name="common" value="1" @checked(request()->boolean('common'))>
        {{ __('catalogue.browse_only_common') }}
    </label>

    <button type="submit" class="btn btn-primary btn-sm">
        <i data-lucide="search"></i> {{ __('catalogue.browse_search') }}
    </button>
</form>

<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>{{ __('catalogue.col_code') }}</th>
                <th>{{ __('catalogue.col_name') }}</th>
                <th>{{ __('catalogue.action_add') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($codes as $code)
            @php $already = $existing[$code->system . '|' . $code->code] ?? null; @endphp
            <tr>
                <td>
                    <code>{{ $code->code }}</code>
                    <div><span class="pill pill--muted">{{ $code->system }}</span></div>
                </td>
                <td>
                    {{ $code->label }}
                    @if($code->label !== $code->display)
                        <div class="text-muted text-sm">{{ $code->display }}</div>
                    @endif
                </td>
                <td>
                    @if($already && $already->is_active)
                        <span class="pill pill--success">
                            <i data-lucide="check"></i> {{ __('catalogue.browse_already') }}
                        </span>
                    @else
                        {{-- One form per row. It posts the system and the code —
                             both checked against the dictionary server-side — and
                             the facility's own fields. It does NOT post a
                             facility_id: that comes from the session context and
                             a field here would be a field to tamper with. --}}
                        <form method="POST" action="{{ route('portals.facility.catalogue.store') }}" class="stack">
                            @csrf
                            <input type="hidden" name="system" value="{{ $code->system }}">
                            <input type="hidden" name="code" value="{{ $code->code }}">

                            <input type="text" name="local_name" maxlength="200" class="form-control"
                                   placeholder="{{ __('catalogue.field_local_name') }}"
                                   aria-label="{{ __('catalogue.field_local_name') }}">

                            {{-- step=1: whole francs, XAF has no minor unit.
                                 Empty means "no published price", which is not
                                 zero and is never a condition of care. --}}
                            <input type="number" name="price_xaf" min="0" step="1" class="form-control"
                                   placeholder="{{ __('catalogue.field_price') }}"
                                   aria-label="{{ __('catalogue.field_price') }}">

                            <input type="text" name="turnaround" maxlength="120" class="form-control"
                                   placeholder="{{ __('catalogue.field_turnaround') }}"
                                   aria-label="{{ __('catalogue.field_turnaround') }}">

                            <button type="submit" class="btn btn-primary btn-sm">
                                <i data-lucide="plus"></i>
                                {{ $already ? __('catalogue.action_restore') : __('catalogue.action_add') }}
                            </button>
                            @if($already)
                            <span class="pill pill--warning">{{ __('catalogue.browse_already_retired') }}</span>
                            @endif
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="text-center text-muted">{{ __('catalogue.browse_no_results') }}</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">{{ $codes->links() }}</div>
@endsection
