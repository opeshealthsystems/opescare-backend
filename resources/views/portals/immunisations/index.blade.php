@extends('layouts.portal')

@section('title', __('immunisation_desk.title'))
@section('breadcrumb_home', __('chart.title'))
@section('breadcrumb_home_url', route('portals.chart.index'))
@section('breadcrumb_section', __('immunisation_desk.title'))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')
@php
    $immName = $identity['name'] ?? '';
    $immInit = collect(preg_split('/\s+/', trim((string) $immName)) ?: [])->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    $immTone = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($immName !== '' ? $immName : (string) $patient->id) % 6];
    $recPill = fn (?string $s): string => match ((string) $s) {
        'completed'        => 'ready',
        'not_done'         => 'pending',
        'entered_in_error' => 'low',
        default            => 'neutral',
    };
@endphp
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('immunisation_desk.title') }}</h1>
        <p class="od-page__sub">{{ __('immunisation_desk.subtitle') }}</p>
    </div>
    <a href="{{ route('portals.chart.show', ['id' => $patient->id, 'tab' => 'immunizations']) }}" class="od-btn od-btn--secondary"><i data-lucide="arrow-left"></i> {{ __('immunisation_desk.back_to_chart') }}</a>
</div>

@if(session('success'))<div class="odc-note odc-note--ok" role="status"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="odc-note odc-note--err" role="alert"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())
<div class="odc-note odc-note--err" role="alert"><i data-lucide="alert-circle"></i><div>@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div></div>
@endif

<section class="od-card">
    <div class="od-card__head">
        <div class="odc-patient">
            <span class="od-avatar od-avatar--lg od-tint--{{ $immTone }}" aria-hidden="true">{{ $immInit !== '' ? $immInit : '—' }}</span>
            <div class="odc-patient__main">
                <div class="od-detail__name">{{ $immName !== '' ? $immName : '—' }}</div>
                <div class="od-detail__ref"><span>{{ __('immunisation_desk.health_id') }}: {{ $identity['health_id'] ?? '—' }}</span></div>
            </div>
        </div>
        @unless($consentRequired)
        <span class="od-pill od-pill--normal">{{ trans_choice('hospital_desk.d_imm_doses', $records->count(), ['count' => $records->count()]) }}</span>
        @endunless
    </div>
</section>

@if($consentRequired)
    <div class="odc-note odc-note--warn" role="alert">
        <i data-lucide="lock"></i>
        <div><strong>{{ __('immunisation_desk.consent_required_title') }}</strong><br>{{ __('immunisation_desk.consent_required') }}</div>
    </div>
@else

{{-- ── The card ──────────────────────────────────────────────────────── --}}
<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('immunisation_desk.card_title') }} <span class="od-card__count">({{ $records->count() }})</span></h2>
    </header>
    @if($records->isEmpty())
        <div class="od-empty od-empty--compact">
            <span class="od-empty__icon"><i data-lucide="syringe"></i></span>
            <p class="od-empty__text">{{ __('immunisation_desk.none') }}</p>
        </div>
    @else
    <div class="od-card__body">
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th class="od-table__num">#</th>
                    <th>{{ __('immunisation_desk.col_vaccine') }}</th>
                    <th>{{ __('immunisation_desk.col_administered') }}</th>
                    <th>{{ __('immunisation_desk.col_dose_number') }}</th>
                    <th>{{ __('immunisation_desk.col_lot') }}</th>
                    <th>{{ __('immunisation_desk.col_site') }}</th>
                    <th>{{ __('immunisation_desk.col_facility') }}</th>
                    <th>{{ __('immunisation_desk.col_status') }}</th>
                    <th></th>
                </tr></thead>
                <tbody>
                @foreach($records as $rec)
                    <tr>
                        <td class="od-table__num">{{ $loop->iteration }}</td>
                        <td>
                            <span class="od-table__name">{{ $rec->vaccine_name ?: '—' }}</span>
                            @if($rec->vaccine_code)<span class="od-table__sub">{{ $rec->vaccine_code }}</span>@endif
                        </td>
                        <td>{{ $rec->administered_at?->translatedFormat('d M Y') ?? '—' }}</td>
                        <td>{{ $rec->dose_number ?? '—' }}</td>
                        <td>{{ $rec->lot_number ?: '—' }}</td>
                        <td>{{ $rec->site ?: '—' }}</td>
                        <td>{{ $rec->facility?->name ?: '—' }}</td>
                        <td>
                            @if($rec->status)<span class="od-pill od-pill--{{ $recPill($rec->status) }}">@enum($rec->status)</span>@else — @endif
                            @if($rec->is_historical)<span class="od-table__sub">{{ __('immunisation_desk.historical') }}</span>@endif
                            @if($rec->status === 'entered_in_error' && $rec->not_done_reason)<span class="od-table__sub">{{ __('immunisation_desk.void_reason') }} {{ $rec->not_done_reason }}</span>@endif
                        </td>
                        <td>
                            @if($rec->status !== 'entered_in_error' && $rec->facility_id === $facilityId)
                                <form method="POST" action="{{ route('portals.immunisations.void', [$patient->id, $rec->id]) }}" class="odc-inline-form" data-confirm="{{ __('hospital_desk.confirm_immunisation_void') }}">
                                    @csrf
                                    <input type="text" name="reason" class="od-input" required maxlength="2000" aria-label="{{ __('immunisation_desk.void_reason_label') }}" placeholder="{{ __('immunisation_desk.void_reason_label') }}">
                                    <button type="submit" class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="undo-2"></i> {{ __('immunisation_desk.void') }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</section>

{{-- ── Record a dose ─────────────────────────────────────────────────── --}}
<section class="od-card">
    <header class="od-card__head"><h2 class="od-card__title">{{ __('immunisation_desk.form_title') }}</h2></header>
    <div class="od-card__body">
        @if($vaccines->isEmpty())
            <div class="od-empty od-empty--compact">
                <span class="od-empty__icon"><i data-lucide="syringe"></i></span>
                <p class="od-empty__text">{{ __('immunisation_desk.no_vaccines') }}</p>
            </div>
        @else
        <form method="POST" action="{{ route('portals.immunisations.store', $patient->id) }}" class="odc-form">
            @csrf
            <div class="odc-grid odc-grid--3">
                <div class="odc-field odc-field--full">
                    <label class="odc-label odc-label--req" for="imm-vaccine">{{ __('immunisation_desk.vaccine') }}</label>
                    <select id="imm-vaccine" name="vaccine_code" class="od-select" required>
                        <option value="">{{ __('immunisation_desk.vaccine_choose') }}</option>
                        @foreach($vaccines as $v)
                            <option value="{{ $v->code }}" @selected(old('vaccine_code') === $v->code)>{{ $v->display }} ({{ $v->code }})</option>
                        @endforeach
                    </select>
                    <p class="odc-hint">{{ __('immunisation_desk.vaccine_hint') }}</p>
                </div>
                <div class="odc-field">
                    <label class="odc-label odc-label--req" for="imm-date">{{ __('immunisation_desk.administered_on') }}</label>
                    <input type="date" id="imm-date" name="administered_on" class="od-input" required max="{{ now()->toDateString() }}" value="{{ old('administered_on', now()->toDateString()) }}">
                </div>
                <div class="odc-field">
                    <label class="odc-label" for="imm-dose">{{ __('immunisation_desk.dose_number') }}</label>
                    <input type="number" id="imm-dose" name="dose_number" class="od-input" min="1" max="20" value="{{ old('dose_number') }}">
                </div>
                <div class="odc-field">
                    <label class="odc-label" for="imm-lot">{{ __('immunisation_desk.lot_number') }}</label>
                    <input type="text" id="imm-lot" name="lot_number" class="od-input" maxlength="100" value="{{ old('lot_number') }}">
                </div>
                <div class="odc-field">
                    <label class="odc-label" for="imm-site">{{ __('immunisation_desk.site') }}</label>
                    <input type="text" id="imm-site" name="site" class="od-input" maxlength="100" value="{{ old('site') }}">
                </div>
                <div class="odc-field">
                    <label class="odc-label" for="imm-route">{{ __('immunisation_desk.route') }}</label>
                    <select id="imm-route" name="route" class="od-select">
                        <option value="">{{ __('immunisation_desk.route_choose') }}</option>
                        @foreach($routes as $r)
                            <option value="{{ $r }}" @selected(old('route') === $r)>{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="odc-field">
                    <label class="odc-label odc-label--req" for="imm-status">{{ __('immunisation_desk.status') }}</label>
                    <select id="imm-status" name="status" class="od-select" required>
                        @foreach($statuses as $s)
                            <option value="{{ $s }}" @selected(old('status', 'completed') === $s)>{{ __('immunisation_desk.status_' . $s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="odc-field odc-field--full">
                    <label class="odc-label" for="imm-not-done">{{ __('immunisation_desk.not_done_reason') }}</label>
                    <textarea id="imm-not-done" name="not_done_reason" class="odc-textarea" rows="2" maxlength="2000">{{ old('not_done_reason') }}</textarea>
                </div>
                <div class="odc-field odc-field--full">
                    <label class="odc-check"><input type="checkbox" name="is_historical" value="1" @checked(old('is_historical'))> {{ __('immunisation_desk.is_historical') }}</label>
                    @if(session('duplicate'))
                        <label class="odc-check"><input type="checkbox" name="confirm_duplicate" value="1"> {{ __('immunisation_desk.confirm_duplicate') }}</label>
                    @endif
                    <p class="odc-hint">{{ __('immunisation_desk.immutable_note') }}</p>
                </div>
            </div>
            <div class="odc-actions">
                <button type="submit" class="od-btn od-btn--primary"><i data-lucide="save"></i> {{ __('immunisation_desk.save') }}</button>
            </div>
        </form>
        @endif
    </div>
</section>
@endif

</div>
@endsection
