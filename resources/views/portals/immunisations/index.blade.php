@extends('layouts.portal')

@section('title', __('immunisation_desk.title'))
@section('breadcrumb_home', __('chart.title'))
@section('breadcrumb_home_url', route('portals.chart.index'))
@section('breadcrumb_section', __('immunisation_desk.title'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('immunisation_desk.title') }} &middot; {{ $identity['name'] !== '' ? $identity['name'] : '—' }}</h1>
        <p class="page-subtitle">
            {{ __('immunisation_desk.health_id') }}: <span class="td-strong">{{ $identity['health_id'] ?? '—' }}</span>
            &middot; {{ __('immunisation_desk.subtitle') }}
        </p>
    </div>
    <a href="{{ route('portals.chart.show', ['id' => $patient->id, 'tab' => 'immunizations']) }}" class="btn btn-secondary">
        <i data-lucide="arrow-left"></i> {{ __('immunisation_desk.back_to_chart') }}
    </a>
</div>

@if(session('success'))<div class="alert alert-success mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-4"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())
<div class="alert alert-danger mb-4" role="alert"><i data-lucide="alert-circle"></i><div>@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div></div>
@endif

@if($consentRequired)
    <div class="alert alert-warning mb-4" role="alert">
        <i data-lucide="lock"></i>
        <div><strong>{{ __('immunisation_desk.consent_required_title') }}</strong><br>{{ __('immunisation_desk.consent_required') }}</div>
    </div>
@else

{{-- ── Record a dose ─────────────────────────────────────────────────── --}}
<div class="panel form-panel mb-4">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="syringe"></i> {{ __('immunisation_desk.form_title') }}</h2>
    </div>
    <div class="panel-body">
        @if($vaccines->isEmpty())
            <p class="td-muted">{{ __('immunisation_desk.no_vaccines') }}</p>
        @else
        <form method="POST" action="{{ route('portals.immunisations.store', $patient->id) }}">
            @csrf
            <div class="form-group mb-4">
                <label class="form-label form-label-required" for="imm-vaccine">{{ __('immunisation_desk.vaccine') }}</label>
                <select id="imm-vaccine" name="vaccine_code" class="form-control" required>
                    <option value="">{{ __('immunisation_desk.vaccine_choose') }}</option>
                    @foreach($vaccines as $v)
                        <option value="{{ $v->code }}" @selected(old('vaccine_code') === $v->code)>{{ $v->display }} ({{ $v->code }})</option>
                    @endforeach
                </select>
                <div class="form-hint">{{ __('immunisation_desk.vaccine_hint') }}</div>
            </div>

            <div class="form-grid mb-4">
                <div class="form-group">
                    <label class="form-label form-label-required" for="imm-date">{{ __('immunisation_desk.administered_on') }}</label>
                    <input type="date" id="imm-date" name="administered_on" class="form-control" required
                           max="{{ now()->toDateString() }}" value="{{ old('administered_on', now()->toDateString()) }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="imm-dose">{{ __('immunisation_desk.dose_number') }}</label>
                    <input type="number" id="imm-dose" name="dose_number" class="form-control" min="1" max="20" value="{{ old('dose_number') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="imm-lot">{{ __('immunisation_desk.lot_number') }}</label>
                    <input type="text" id="imm-lot" name="lot_number" class="form-control" maxlength="100" value="{{ old('lot_number') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="imm-site">{{ __('immunisation_desk.site') }}</label>
                    <input type="text" id="imm-site" name="site" class="form-control" maxlength="100" value="{{ old('site') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="imm-route">{{ __('immunisation_desk.route') }}</label>
                    <select id="imm-route" name="route" class="form-control">
                        <option value="">{{ __('immunisation_desk.route_choose') }}</option>
                        @foreach($routes as $r)
                            <option value="{{ $r }}" @selected(old('route') === $r)>{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required" for="imm-status">{{ __('immunisation_desk.status') }}</label>
                    <select id="imm-status" name="status" class="form-control" required>
                        @foreach($statuses as $s)
                            <option value="{{ $s }}" @selected(old('status', 'completed') === $s)>{{ __('immunisation_desk.status_' . $s) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group mb-4">
                <label class="form-label" for="imm-not-done">{{ __('immunisation_desk.not_done_reason') }}</label>
                <textarea id="imm-not-done" name="not_done_reason" class="form-control" rows="2" maxlength="2000">{{ old('not_done_reason') }}</textarea>
            </div>

            <div class="form-group mb-4">
                <label class="checkbox-label"><input type="checkbox" name="is_historical" value="1" @checked(old('is_historical'))> {{ __('immunisation_desk.is_historical') }}</label>
                @if(session('duplicate'))
                    <label class="checkbox-label"><input type="checkbox" name="confirm_duplicate" value="1"> {{ __('immunisation_desk.confirm_duplicate') }}</label>
                @endif
            </div>

            <p class="form-hint mb-4">{{ __('immunisation_desk.immutable_note') }}</p>

            <div class="row-actions-inline">
                <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> {{ __('immunisation_desk.save') }}</button>
            </div>
        </form>
        @endif
    </div>
</div>

{{-- ── The card ──────────────────────────────────────────────────────── --}}
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="book-open"></i> {{ __('immunisation_desk.card_title') }}</h2>
    </div>
    <div class="panel-body">
        @if($records->isEmpty())
            <p class="td-muted">{{ __('immunisation_desk.none') }}</p>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>{{ __('immunisation_desk.col_administered') }}</th>
                        <th>{{ __('immunisation_desk.col_vaccine') }}</th>
                        <th>{{ __('immunisation_desk.col_dose_number') }}</th>
                        <th>{{ __('immunisation_desk.col_lot') }}</th>
                        <th>{{ __('immunisation_desk.col_site') }}</th>
                        <th>{{ __('immunisation_desk.col_facility') }}</th>
                        <th>{{ __('immunisation_desk.col_status') }}</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($records as $rec)
                        <tr>
                            <td class="td-muted">{{ $rec->administered_at?->translatedFormat('d M Y') ?? '—' }}</td>
                            <td class="td-strong">
                                {{ $rec->vaccine_name ?: '—' }}
                                @if($rec->vaccine_code)<br><small class="td-muted">{{ $rec->vaccine_code }}</small>@endif
                            </td>
                            <td>{{ $rec->dose_number ?? '—' }}</td>
                            <td>{{ $rec->lot_number ?: '—' }}</td>
                            <td>{{ $rec->site ?: '—' }}</td>
                            <td>{{ $rec->facility?->name ?: '—' }}</td>
                            <td>
                                @if($rec->status)@enum($rec->status)@else — @endif
                                @if($rec->is_historical)<br><small class="td-muted">{{ __('immunisation_desk.historical') }}</small>@endif
                                @if($rec->status === 'entered_in_error' && $rec->not_done_reason)<br><small class="td-muted">{{ __('immunisation_desk.void_reason') }} {{ $rec->not_done_reason }}</small>@endif
                            </td>
                            <td>
                                @if($rec->status !== 'entered_in_error' && $rec->facility_id === $facilityId)
                                    <form method="POST" action="{{ route('portals.immunisations.void', [$patient->id, $rec->id]) }}">
                                        @csrf
                                        <input type="text" name="reason" class="form-control mb-2" required maxlength="2000"
                                               aria-label="{{ __('immunisation_desk.void_reason_label') }}" placeholder="{{ __('immunisation_desk.void_reason_label') }}">
                                        <button type="submit" class="btn btn-secondary"><i data-lucide="undo-2"></i> {{ __('immunisation_desk.void') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endif

@endsection
