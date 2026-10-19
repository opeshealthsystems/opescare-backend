@extends('layouts.portal')

@section('title', __('lab_desk.title'))

@php
    $urgencyPill = fn (string $u) => match ($u) { 'stat' => 'od-pill--stat', 'urgent' => 'od-pill--urgent', default => 'od-pill--normal' };
    $statusTone  = fn (string $s) => match ($s) { 'ordered' => 'pending', 'collected', 'in_progress' => 'normal', 'resulted' => 'teal', default => 'ready' };
@endphp

@section('head')
    @include('portals.lab.partials._head')
@endsection

@section('content')
<div class="od-page odl">

<header class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('lab_desk.worklist_title') }}</h1>
        <p class="od-page__sub">{{ __('lab_desk.worklist_subtitle') }}</p>
    </div>
    @capability('lab_results', 'sample_collection')
    <a href="{{ route('portals.lab.collection') }}" class="od-btn od-btn--primary">
        <i data-lucide="barcode"></i> {{ __('lab_desk.nav_collection') }}
    </a>
    @endcapability
</header>

@if(session('success'))<div class="od-flash od-flash--ok" role="status"><i data-lucide="check-circle"></i><span>{{ session('success') }}</span></div>@endif
@if(session('error'))<div class="od-flash od-flash--err" role="alert"><i data-lucide="alert-circle"></i><span>{{ session('error') }}</span></div>@endif

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('lab_desk.status.' . $status) }} <span class="od-card__count">({{ $counts[$status] ?? 0 }})</span></h2>
    </div>
    <div class="od-card__body">
    {{-- ── Exact Health ID lookup. No name search on this desk. ─────────── --}}
    <form method="GET" action="{{ route('portals.lab.index') }}" class="odl-search">
        <input type="hidden" name="status" value="{{ $status }}">
        <label class="od-sr" for="lab-health-id">{{ __('lab_desk.health_id') }}</label>
        <div class="od-field odl-search__field">
            <i data-lucide="search"></i>
            <input type="text" id="lab-health-id" name="health_id" class="od-input"
                   value="{{ $healthId }}" autocomplete="off" maxlength="64" placeholder="{{ __('lab_desk.health_id') }}">
        </div>
        <button type="submit" class="od-btn od-btn--primary">{{ __('lab_desk.lookup') }}</button>
        @if($healthId)
            <a href="{{ route('portals.lab.index', ['status' => $status]) }}" class="od-btn od-btn--secondary">{{ __('lab_desk.clear') }}</a>
        @endif
    </form>
    <p class="od-muted odl-small">{{ $healthId ? __('lab_desk.filtered_by', ['health_id' => $healthId]) : __('lab_desk.health_id_hint') }}</p>

    {{-- ── The five columns, as tabs ───────────────────────────────────── --}}
    <nav class="od-tabs" aria-label="{{ __('lab_desk.worklist_title') }}">
        @foreach($statuses as $s)
            <a href="{{ route('portals.lab.index', array_filter(['status' => $s, 'health_id' => $healthId])) }}"
               class="od-tab {{ $s === $status ? 'is-active' : '' }}" @if($s === $status) aria-current="page" @endif>
                <span class="od-tab__dot od-tab__dot--{{ $statusTone($s) }}"></span>{{ __('lab_desk.status.' . $s) }} ({{ $counts[$s] ?? 0 }})
            </a>
        @endforeach
    </nav>

    @if($orders->isEmpty())
        <div class="od-empty">
            <span class="od-empty__icon"><i data-lucide="flask-conical"></i></span>
            <p class="od-empty__title">{{ __('lab_desk.empty_worklist') }}</p>
            <p class="od-empty__text">{{ __('lab_desk_ui.worklist_empty_body') }}</p>
            @capability('lab_results', 'sample_collection')
            <a href="{{ route('portals.lab.collection') }}" class="od-btn od-btn--primary">{{ __('lab_desk_ui.open_collection') }}</a>
            @endcapability
        </div>
    @else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead>
                    <tr>
                        <th class="od-table__num">#</th>
                        <th>{{ __('lab_desk.col_patient') }}</th>
                        <th>{{ __('lab_desk.col_test') }}</th>
                        <th>{{ __('lab_desk.col_urgency') }}</th>
                        <th>{{ __('lab_desk.col_ordered_by') }}</th>
                        <th>{{ __('lab_desk.col_ordered_at') }}</th>
                        <th>{{ __('lab_desk.col_results') }}</th>
                        <th class="od-table__actions">{{ __('lab_desk.col_open') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($orders as $order)
                    @php
                        $p     = $order->patient;
                        $pName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
                        $u     = $order->deskUrgency();
                    @endphp
                    <tr>
                        <td class="od-table__num" data-label="#">{{ $orders->firstItem() + $loop->index }}</td>
                        <td data-label="{{ __('lab_desk.col_patient') }}">
                            <div class="od-person">
                                @include('portals.lab.partials._avatar', ['name' => $pName])
                                <div><div class="od-table__name">{{ $pName !== '' ? $pName : '—' }}</div>@if($p?->health_id)<div class="od-table__sub">{{ $p->health_id }}</div>@endif</div>
                            </div>
                        </td>
                        <td data-label="{{ __('lab_desk.col_test') }}">
                            <div class="od-table__name">{{ $order->test_name }}</div>
                            @if($order->test_code)<div class="od-table__sub">{{ $order->test_code }}</div>@endif
                        </td>
                        <td data-label="{{ __('lab_desk.col_urgency') }}">
                            <span class="od-pill {{ $urgencyPill($u) }} od-pill--dot">{{ __('lab_desk.urgency.' . $u) }}</span>
                        </td>
                        <td data-label="{{ __('lab_desk.col_ordered_by') }}" class="od-muted">{{ $order->orderedBy?->name ?? '—' }}</td>
                        <td data-label="{{ __('lab_desk.col_ordered_at') }}" class="od-muted">{{ $order->ordered_at?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                        <td data-label="{{ __('lab_desk.col_results') }}">{{ $order->results_count }}</td>
                        <td class="od-table__actions" data-label="{{ __('lab_desk.col_open') }}">
                            <a href="{{ route('portals.lab.show', $order->id) }}" class="od-btn od-btn--row">{{ __('lab_desk.open_order') }}</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
            <div class="odl-pager">{{ $orders->links() }}</div>
        @endif
    @endif
    </div>
</section>

</div>
@endsection
