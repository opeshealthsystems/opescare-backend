@extends('layouts.portal')

@section('title', __('immunisation_desk.desk_title'))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')
@php
    $immPerson = function ($patient): array {
        $name = trim(($patient?->first_name ?? '') . ' ' . ($patient?->last_name ?? ''));
        return [$name !== '' ? $name : '—', $patient?->health_id];
    };
    $rows = match ($tab) {
        'overdue' => $overdue,
        'recent'  => $recent,
        default   => $due_today,
    };
@endphp
<div class="od-page odc-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('immunisation_desk.desk_title') }}</h1>
        <p class="od-page__sub">{{ __('immunisation_desk.desk_subtitle') }}</p>
    </div>
</div>

@if(session('error'))<div class="odc-note odc-note--urgent" role="alert"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())
<div class="odc-note odc-note--urgent" role="alert"><i data-lucide="alert-circle"></i><div>@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div></div>
@endif

<div class="od-kpis">
    <div class="od-kpi od-tint--green">
        <span class="od-kpi__icon"><i data-lucide="syringe"></i></span>
        <span class="od-kpi__body"><span class="od-kpi__label">{{ __('immunisation_desk.kpi_given_today') }}</span><span class="od-kpi__value">{{ $kpis['given_today'] }}</span></span>
    </div>
    <div class="od-kpi od-tint--blue">
        <span class="od-kpi__icon"><i data-lucide="calendar-check"></i></span>
        <span class="od-kpi__body"><span class="od-kpi__label">{{ __('immunisation_desk.kpi_due_today') }}</span><span class="od-kpi__value">{{ $kpis['due_today'] }}</span></span>
    </div>
    <div class="od-kpi od-tint--rose">
        <span class="od-kpi__icon"><i data-lucide="calendar-x"></i></span>
        <span class="od-kpi__body"><span class="od-kpi__label">{{ __('immunisation_desk.kpi_overdue') }}</span><span class="od-kpi__value">{{ $kpis['overdue'] }}</span></span>
    </div>
    <div class="od-kpi od-tint--teal">
        <span class="od-kpi__icon"><i data-lucide="calendar-range"></i></span>
        <span class="od-kpi__body"><span class="od-kpi__label">{{ __('immunisation_desk.kpi_given_week') }}</span><span class="od-kpi__value">{{ $kpis['given_week'] }}</span></span>
    </div>
</div>

<section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title">{{ __('immunisation_desk.search_title') }}</h2></div>
    <form method="GET" action="{{ route('portals.immunisations.find') }}" class="odc-form">
        <label for="imm-health-id" class="od-label">{{ __('immunisation_desk.health_id') }}</label>
        <div class="odc-inline">
            <input id="imm-health-id" name="health_id" type="text" class="od-input" maxlength="64" required value="{{ old('health_id') }}" autocomplete="off">
            <button type="submit" class="od-btn od-btn--primary"><i data-lucide="search"></i> {{ __('immunisation_desk.search_submit') }}</button>
        </div>
        <p class="od-help">{{ __('immunisation_desk.search_help') }}</p>
    </form>
</section>

<nav class="od-tabs" aria-label="{{ __('immunisation_desk.desk_title') }}">
    <a href="{{ route('portals.immunisations.desk', ['tab' => 'due']) }}" class="od-tab {{ $tab === 'due' ? 'is-active' : '' }}">
        <i data-lucide="calendar-check"></i><span>{{ __('immunisation_desk.tab_due') }}</span><span class="od-tab__count">{{ $kpis['due_today'] }}</span>
    </a>
    <a href="{{ route('portals.immunisations.desk', ['tab' => 'overdue']) }}" class="od-tab {{ $tab === 'overdue' ? 'is-active' : '' }}">
        <i data-lucide="calendar-x"></i><span>{{ __('immunisation_desk.tab_overdue') }}</span><span class="od-tab__count">{{ $kpis['overdue'] }}</span>
    </a>
    <a href="{{ route('portals.immunisations.desk', ['tab' => 'recent']) }}" class="od-tab {{ $tab === 'recent' ? 'is-active' : '' }}">
        <i data-lucide="history"></i><span>{{ __('immunisation_desk.tab_recent') }}</span><span class="od-tab__count">{{ $recent->count() }}</span>
    </a>
</nav>

<section class="od-card">
@if($rows->isEmpty())
    <div class="od-empty">
        <div class="od-empty__icon"><i data-lucide="syringe"></i></div>
        <div class="od-empty__title">{{ __('immunisation_desk.empty_' . $tab) }}</div>
        @if($tab !== 'recent')<p class="od-empty__text">{{ __('immunisation_desk.empty_schedule_note') }}</p>@endif
    </div>
@else
    <div class="od-table-wrap">
        <table class="od-table">
            <thead>
                <tr>
                    <th>{{ __('immunisation_desk.col_patient') }}</th>
                    <th>{{ __('immunisation_desk.col_vaccine') }}</th>
                    <th>{{ __('immunisation_desk.col_dose_number') }}</th>
                    <th>{{ $tab === 'recent' ? __('immunisation_desk.col_given_on') : __('immunisation_desk.col_due_on') }}</th>
                    <th class="od-table__actions"></th>
                </tr>
            </thead>
            <tbody>
            @foreach($rows as $row)
                @php [$pName, $pHid] = $immPerson($row->patient); @endphp
                <tr>
                    <td data-label="{{ __('immunisation_desk.col_patient') }}">
                        <div class="od-table__person"><div><span class="od-table__name">{{ $pName }}</span>@if($pHid)<span class="od-table__sub">{{ $pHid }}</span>@endif</div></div>
                    </td>
                    <td data-label="{{ __('immunisation_desk.col_vaccine') }}">{{ $row->vaccine_name ?: $row->vaccine_code }}</td>
                    <td data-label="{{ __('immunisation_desk.col_dose_number') }}">{{ $row->dose_number ?? '—' }}</td>
                    <td data-label="{{ $tab === 'recent' ? __('immunisation_desk.col_given_on') : __('immunisation_desk.col_due_on') }}">
                        {{ $tab === 'recent' ? $row->administered_at?->format('Y-m-d') : $row->due_date?->format('Y-m-d') }}
                    </td>
                    <td class="od-table__actions">
                        <a href="{{ route('portals.immunisations.index', $row->patient_id) }}" class="od-btn od-btn--secondary od-btn--sm">
                            <i data-lucide="{{ $tab === 'recent' ? 'eye' : 'syringe' }}"></i> {{ $tab === 'recent' ? __('immunisation_desk.open_card') : __('immunisation_desk.record_dose') }}
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
</section>

</div>
@endsection
