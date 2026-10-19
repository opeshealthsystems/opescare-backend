@extends('layouts.portal')

@section('title', __('lab_desk.collection_title'))

@php
    $urgencyPill = fn (string $u) => match ($u) { 'stat' => 'od-pill--stat', 'urgent' => 'od-pill--urgent', default => 'od-pill--normal' };
@endphp

@section('head')
    @include('portals.lab.partials._head')
@endsection

@section('content')
<div class="od-page odl">

<header class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('lab_desk.collection_title') }}</h1>
        <p class="od-page__sub">{{ __('lab_desk.collection_subtitle') }}</p>
    </div>
    @capability('lab_results')
    <a href="{{ route('portals.lab.index') }}" class="od-btn od-btn--secondary">
        <i data-lucide="arrow-left"></i> {{ __('lab_desk.back_to_worklist') }}
    </a>
    @endcapability
</header>

@if(session('success'))<div class="od-flash od-flash--ok" role="status"><i data-lucide="check-circle"></i><span>{{ session('success') }}</span></div>@endif
@if(session('error'))<div class="od-flash od-flash--err" role="alert"><i data-lucide="alert-circle"></i><span>{{ session('error') }}</span></div>@endif
@if($errors->any())
<div class="od-flash od-flash--err" role="alert"><i data-lucide="alert-circle"></i><ul class="odl-errlist">@foreach($errors->all() as $m)<li>{{ $m }}</li>@endforeach</ul></div>
@endif

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('lab_desk_ui.collection_queue') }} <span class="od-card__count">({{ $orders->total() }})</span></h2>
    </div>
    <div class="od-card__body">
    @if($orders->isEmpty())
        <div class="od-empty">
            <span class="od-empty__icon"><i data-lucide="test-tube-2"></i></span>
            <p class="od-empty__title">{{ __('lab_desk.empty_collection') }}</p>
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
                        <th>{{ __('lab_desk.col_ordered_at') }}</th>
                        <th>{{ __('lab_desk.specimen_type') }}</th>
                        <th class="od-table__actions">{{ __('lab_desk.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($orders as $order)
                    @php
                        $p        = $order->patient;
                        $pName    = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
                        $u        = $order->deskUrgency();
                        $specimen = $order->getAttribute('dictionary_specimen');
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
                        <td data-label="{{ __('lab_desk.col_urgency') }}"><span class="od-pill {{ $urgencyPill($u) }} od-pill--dot">{{ __('lab_desk.urgency.' . $u) }}</span></td>
                        <td data-label="{{ __('lab_desk.col_ordered_at') }}" class="od-muted">{{ $order->ordered_at?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                        <td data-label="{{ __('lab_desk.specimen_type') }}">
                            <input type="text" form="collect-{{ $order->id }}" name="specimen_type" class="od-input"
                                   value="{{ old('specimen_type', $specimen) }}" maxlength="120"
                                   aria-label="{{ __('lab_desk.specimen_type') }}"
                                   placeholder="{{ $specimen ? '' : __('lab_desk.specimen_unknown') }}">
                        </td>
                        <td class="od-table__actions odl-rowactions" data-label="{{ __('lab_desk.col_actions') }}">
                            <form id="collect-{{ $order->id }}" method="POST" action="{{ route('portals.lab.collect', $order->id) }}">
                                @csrf
                                <button type="submit" class="od-btn od-btn--row">{{ __('lab_desk.mark_collected') }}</button>
                            </form>
                            {{-- The collector who sees the unusable tube refuses it here (routes/lab_manager.php). --}}
                            <details class="odl-details">
                                <summary class="od-kebab" aria-label="{{ __('lab_manager.reject') }}" title="{{ __('lab_manager.reject') }}"><i data-lucide="ban"></i></summary>
                                <form method="POST" action="{{ route('portals.lab_manager.reject', $order->id) }}" class="odl-form">
                                    @csrf
                                    <label class="odl-label" for="reject-reason-{{ $order->id }}">{{ __('lab_manager.reject_reason') }}</label>
                                    <input type="text" id="reject-reason-{{ $order->id }}" name="reason" class="od-input" required minlength="3" maxlength="1000">
                                    <button type="submit" class="od-btn od-btn--danger"><i data-lucide="ban"></i> {{ __('lab_manager.submit_reject') }}</button>
                                </form>
                            </details>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <p class="od-muted odl-small odl-foot">{{ __('lab_desk.specimen_hint') }}</p>
        @if($orders->hasPages())
            <div class="odl-pager">{{ $orders->links() }}</div>
        @endif
    @endif
    </div>
</section>

</div>
@endsection
