@extends('layouts.portal')

@section('title', __('lab_desk.order_title'))
@section('breadcrumb_home', __('lab_desk.title'))
@section('breadcrumb_home_url', route('portals.lab.index'))
@section('breadcrumb_section', __('lab_desk.order_title'))

@php
    $p     = $order->patient;
    $pName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));

    $flagBadge = fn (string $severity) => 'badge badge-' . match ($severity) {
        'critical' => 'danger',
        'warning'  => 'warning',
        'success'  => 'success',
        default    => 'neutral',
    };
    $urgency      = $order->deskUrgency();
    $urgencyBadge = 'badge badge-' . match ($urgency) { 'stat' => 'danger', 'urgent' => 'warning', default => 'neutral' };

    // A number is printed as the resolver holds it; nothing is rounded into
    // existence.
    $num = fn ($v) => $v === null ? null : rtrim(rtrim(number_format((float) $v, 3, '.', ''), '0'), '.');

    $open      = ! $order->isTerminal();
    $released  = $order->isReleased();
    $validated = $status === 'validated';
    $canEnter  = $open && ! $validated;
    $canRelease = $open && ! $released && $order->results->isNotEmpty();
    $canDoValidate = $open && $status === 'resulted' && $canValidate;
    // A sample is refused only while nothing has been entered (routes/lab_manager.php).
    $canReject = $open && ! $released && $order->results->isEmpty();

    try { $dob = $p?->date_of_birth; } catch (\Throwable) { $dob = null; }
    $age = $dob ? \Illuminate\Support\Carbon::parse($dob)->age : null;
@endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $order->test_name }}</h1>
        <p class="page-subtitle">
            <span class="badge badge-info">{{ __('lab_desk.status.' . $status) }}</span>
            <span class="{{ $urgencyBadge }}">{{ __('lab_desk.urgency.' . $urgency) }}</span>
        </p>
    </div>
    <div class="row-actions-inline">
        @if($released)
        {{-- The patient's own Secure sheet, generated now — for the walk-in patient without a phone. --}}
        <a href="{{ route('portals.lab_manager.report', $order->id) }}" class="btn btn-primary" title="{{ __('lab_manager.print_report_hint') }}">
            <i data-lucide="printer"></i> {{ __('lab_manager.print_report') }}
        </a>
        @endif
        <a href="{{ route('portals.lab.index', ['status' => $status === 'cancelled' ? 'ordered' : $status]) }}" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i> {{ __('lab_desk.back_to_worklist') }}
        </a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())
<div class="alert alert-danger mb-6" role="alert"><i data-lucide="alert-circle"></i><div><ul class="alert-list">@foreach($errors->all() as $m)<li>{{ $m }}</li>@endforeach</ul></div></div>
@endif

@if($validated)
<div class="alert alert-info mb-6"><i data-lucide="shield-check"></i><div>{{ __('lab_desk.validated_note') }}</div></div>
@endif

{{-- ── The order ─────────────────────────────────────────────────────── --}}
<div class="panel mb-4">
    <div class="panel-header"><h2 class="panel-title"><i data-lucide="clipboard-list"></i> {{ __('lab_desk.order_title') }}</h2></div>
    <div class="panel-body">
        <div class="form-grid">
            <div class="form-group">
                <div class="form-label">{{ __('lab_desk.patient') }}</div>
                <div class="td-strong">{{ $pName !== '' ? $pName : '—' }}</div>
                <div class="td-muted">
                    {{ $p?->health_id ?? '—' }}
                    @if($age !== null) &middot; {{ __('lab_desk.age_years', ['years' => $age]) }} @endif
                    @if($p?->sex) &middot; {{ $p->sex }} @endif
                </div>
            </div>
            <div class="form-group">
                <div class="form-label">{{ __('lab_desk.test') }}</div>
                <div class="td-strong">{{ $order->test_name }}</div>
                <div class="td-muted">{{ __('lab_desk.test_code') }}: {{ $order->test_code ?: '—' }}</div>
            </div>
            <div class="form-group">
                <div class="form-label">{{ __('lab_desk.loinc') }}</div>
                @if($loinc)
                    <div class="td-strong">{{ $loinc }}</div>
                    @if($term)<div class="td-muted">{{ $term->label }}</div>@endif
                @else
                    <div class="td-muted">{{ __('lab_desk.loinc_none') }}</div>
                @endif
            </div>
            @if($term && ($term->specimen || $term->scale || $term->example_units))
            <div class="form-group">
                <div class="form-label">{{ __('lab_desk.dictionary') }}</div>
                <div class="td-muted">
                    @if($term->specimen){{ __('lab_desk.dictionary_specimen') }}: {{ $term->specimen }}<br>@endif
                    @if($term->scale){{ __('lab_desk.dictionary_scale') }}: {{ $term->scale }}<br>@endif
                    @if($term->example_units){{ __('lab_desk.dictionary_units') }}: {{ $term->example_units }}@endif
                </div>
            </div>
            @endif
            <div class="form-group">
                <div class="form-label">{{ __('lab_desk.ordered_by') }}</div>
                <div>{{ $order->orderedBy?->name ?? __('lab_desk.unknown') }}</div>
                <div class="td-muted">{{ $order->ordered_at?->format('d M Y H:i') ?? '—' }}</div>
            </div>
            <div class="form-group">
                <div class="form-label">{{ __('lab_desk.collected_at') }}</div>
                <div>{{ $order->collected_at?->format('d M Y H:i') ?? __('lab_desk.not_yet') }}</div>
            </div>
            <div class="form-group">
                <div class="form-label">{{ __('lab_desk.resulted_at') }}</div>
                <div>{{ $released ? ($order->resulted_at?->format('d M Y H:i') ?? '—') : __('lab_desk.not_yet') }}</div>
            </div>
            <div class="form-group">
                <div class="form-label">{{ __('lab_desk.validated_by') }}</div>
                <div>{{ $validated ? ($order->verifiedBy?->name ?? __('lab_desk.unknown')) : __('lab_desk.not_yet') }}</div>
            </div>
            @if($order->clinical_indication)
            <div class="form-group">
                <div class="form-label">{{ __('lab_desk.indication') }}</div>
                <div>{{ $order->clinical_indication }}</div>
            </div>
            @endif
            @if($order->notes)
            <div class="form-group">
                <div class="form-label">{{ __('lab_desk.order_notes') }}</div>
                <div class="td-muted">{!! nl2br(e($order->notes)) !!}</div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ── Results ───────────────────────────────────────────────────────── --}}
<div class="panel mb-4">
    <div class="panel-header"><h2 class="panel-title"><i data-lucide="flask-conical"></i> {{ __('lab_desk.results_title') }}</h2></div>
    @if($rows->isEmpty())
        <div class="panel-body"><div class="empty-state"><h3>{{ __('lab_desk.empty_results') }}</h3></div></div>
    @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('lab_desk.col_analyte') }}</th>
                        <th>{{ __('lab_desk.col_value') }}</th>
                        <th>{{ __('lab_desk.col_reference') }}</th>
                        <th>{{ __('lab_desk.col_flag') }}</th>
                        <th>{{ __('lab_desk.col_entered_by') }}</th>
                        <th>{{ __('lab_desk.col_resulted_at') }}</th>
                        @if($open)<th class="row-actions">{{ __('lab_desk.col_actions') }}</th>@endif
                    </tr>
                </thead>
                <tbody>
                @foreach($rows as $row)
                    @php $r = $row['result']; $alert = $row['alert']; @endphp
                    <tr>
                        <td data-label="{{ __('lab_desk.col_analyte') }}">
                            <span class="td-strong">{{ $r->parameter_name }}</span>
                            @if($r->loinc_code)<div class="td-muted">{{ __('lab_desk.loinc') }} {{ $r->loinc_code }}</div>@endif
                            @if($row['superseded'])<span class="badge badge-neutral">{{ __('lab_desk.superseded') }}</span>@endif
                            @if($row['corrects'])<span class="badge badge-info">{{ __('lab_desk.corrects') }}</span>@endif
                        </td>
                        <td data-label="{{ __('lab_desk.col_value') }}">
                            <span class="td-strong">{{ $r->value }}</span>
                            <span class="td-muted">{{ $r->unit ?: __('lab_desk.no_unit') }}</span>
                        </td>
                        <td data-label="{{ __('lab_desk.col_reference') }}" class="td-muted">
                            @if($r->reference_range)
                                {{ $r->reference_range }}
                            @elseif($row['ref_low'] !== null || $row['ref_high'] !== null)
                                {{ $num($row['ref_low']) }}–{{ $num($row['ref_high']) }}
                            @else
                                {{ __('lab_desk.no_range') }}
                            @endif
                        </td>
                        <td data-label="{{ __('lab_desk.col_flag') }}">
                            <span class="{{ $flagBadge($row['severity']) }}">{{ __('lab_desk.flag.' . $row['flag']) }}</span>
                            @if($row['flag_source'])
                                <div class="td-muted">{{ __('lab_desk.flag_source_' . $row['flag_source']) }}</div>
                            @endif
                            @if($alert)
                                <div class="td-muted">
                                    <i data-lucide="siren"></i> {{ __('lab_desk.alert_title') }} &middot;
                                    {{ __('lab_desk.alert_threshold', ['threshold' => $alert->critical_threshold]) }}<br>
                                    @if($alert->acknowledged)
                                        {{ __('lab_desk.alert_acknowledged', ['name' => $alert->acknowledgedBy?->name ?? __('lab_desk.unknown'), 'at' => $alert->acknowledged_at?->format('d M Y H:i') ?? '—']) }}
                                    @else
                                        <span class="badge badge-warning">{{ __('lab_desk.alert_pending') }}</span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td data-label="{{ __('lab_desk.col_entered_by') }}" class="td-muted">{{ $row['entered_by'] ?? $row['released_by'] ?? __('lab_desk.unknown') }}</td>
                        <td data-label="{{ __('lab_desk.col_resulted_at') }}" class="td-muted">
                            {{ $r->resulted_at?->format('d M Y H:i') ?? '—' }}
                            @if($r->notes)<div>{{ $r->notes }}</div>@endif
                        </td>
                        @if($open)
                        <td class="row-actions" data-label="{{ __('lab_desk.col_actions') }}">
                            @if(! $row['superseded'])
                            <details>
                                <summary class="btn btn-secondary btn-sm"><i data-lucide="pencil-line"></i> {{ __('lab_desk.correct') }}</summary>
                                <form method="POST" action="{{ route('portals.lab.results.correct', [$order->id, $r->id]) }}" class="form-grid">
                                    @csrf
                                    <p class="form-hint">{{ __('lab_desk.correct_hint') }}</p>
                                    <div class="form-group">
                                        <label class="form-label form-label-required" for="correct-value-{{ $r->id }}">{{ __('lab_desk.value') }}</label>
                                        <input type="text" id="correct-value-{{ $r->id }}" name="value" class="form-control" required maxlength="100">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="correct-unit-{{ $r->id }}">{{ __('lab_desk.unit') }}</label>
                                        <input type="text" id="correct-unit-{{ $r->id }}" name="unit" class="form-control" value="{{ $r->unit }}" maxlength="50">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label form-label-required" for="correct-reason-{{ $r->id }}">{{ __('lab_desk.reason') }}</label>
                                        <input type="text" id="correct-reason-{{ $r->id }}" name="reason" class="form-control" required maxlength="1000">
                                    </div>
                                    <div class="row-actions-inline">
                                        <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="save"></i> {{ __('lab_desk.submit_correction') }}</button>
                                    </div>
                                </form>
                            </details>
                            @endif
                        </td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($rows->contains(fn ($row) => $row['alert'] !== null))
            <div class="panel-body"><p class="form-hint">{{ __('lab_desk.alert_note') }}</p></div>
        @endif
    @endif
</div>

{{-- ── Enter a result ────────────────────────────────────────────────── --}}
@if($canEnter)
<div class="panel form-panel mb-4">
    <div class="panel-header"><h2 class="panel-title"><i data-lucide="plus"></i> {{ __('lab_desk.entry_title') }}</h2></div>
    <div class="panel-body">
        <p class="form-hint">{{ __('lab_desk.entry_hint') }}</p>

        <div class="alert {{ $published ? 'alert-info' : 'alert-warning' }} mb-4">
            <i data-lucide="{{ $published ? 'ruler' : 'circle-help' }}"></i>
            <div>
                @if($published)
                    <strong>{{ __('lab_desk.range_preview') }}:</strong>
                    {{ $num($published->normal_low) ?? '' }}–{{ $num($published->normal_high) ?? '' }} {{ $published->unit }}
                    @if($published->critical_low !== null || $published->critical_high !== null)
                        <br>{{ __('lab_desk.range_critical', ['low' => $num($published->critical_low) ?? '—', 'high' => $num($published->critical_high) ?? '—']) }}
                    @endif
                @else
                    {{ __('lab_desk.range_preview_none') }}
                @endif
            </div>
        </div>

        <form method="POST" action="{{ route('portals.lab.results.store', $order->id) }}" class="form-grid">
            @csrf
            <div class="form-group">
                <label class="form-label" for="entry-parameter">{{ __('lab_desk.parameter_name') }}</label>
                <input type="text" id="entry-parameter" name="parameter_name" class="form-control" value="{{ old('parameter_name', $order->test_name) }}" maxlength="200">
                <div class="form-hint">{{ __('lab_desk.parameter_hint') }}</div>
            </div>
            <div class="form-group">
                <label class="form-label form-label-required" for="entry-value">{{ __('lab_desk.value') }}</label>
                <input type="text" id="entry-value" name="value" class="form-control" value="{{ old('value') }}" required maxlength="100" autocomplete="off">
                <div class="form-hint">{{ __('lab_desk.value_hint') }}</div>
            </div>
            <div class="form-group">
                <label class="form-label" for="entry-unit">{{ __('lab_desk.unit') }}</label>
                <input type="text" id="entry-unit" name="unit" class="form-control" value="{{ old('unit') }}" maxlength="50"
                       @if($term?->example_units) list="entry-unit-options" @endif>
                @if($term?->example_units)
                    <datalist id="entry-unit-options">
                        @foreach(preg_split('/\s*[;|]\s*/', (string) $term->example_units) as $u)
                            @if(trim($u) !== '')<option value="{{ trim($u) }}">@endif
                        @endforeach
                    </datalist>
                    <div class="form-hint">{{ __('lab_desk.unit_suggested', ['units' => $term->example_units]) }}</div>
                @else
                    <div class="form-hint">{{ __('lab_desk.unit_hint') }}</div>
                @endif
            </div>
            <div class="form-group">
                <label class="form-label" for="entry-notes">{{ __('lab_desk.notes') }}</label>
                <input type="text" id="entry-notes" name="notes" class="form-control" value="{{ old('notes') }}" maxlength="1000">
            </div>
            <div class="row-actions-inline">
                <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> {{ __('lab_desk.submit_result') }}</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- ── Release / validate ────────────────────────────────────────────── --}}
@if($canRelease || $canDoValidate || ($open && $status === 'resulted'))
<div class="panel">
    <div class="panel-body row-actions-inline">
        @if($canRelease)
            <form method="POST" action="{{ route('portals.lab.release', $order->id) }}">
                @csrf
                <button type="submit" class="btn btn-primary"><i data-lucide="send"></i> {{ __('lab_desk.release') }}</button>
                <div class="form-hint">{{ __('lab_desk.release_hint') }}</div>
            </form>
        @endif
        @if($canDoValidate)
            <form method="POST" action="{{ route('portals.lab.validate', $order->id) }}">
                @csrf
                <button type="submit" class="btn btn-primary"><i data-lucide="shield-check"></i> {{ __('lab_desk.validate') }}</button>
                <div class="form-hint">{{ __('lab_desk.validate_hint') }}</div>
            </form>
        @elseif($open && $status === 'resulted')
            <p class="form-hint"><i data-lucide="lock"></i> {{ __('lab_desk.validation_locked') }}</p>
        @endif
    </div>
</div>
@endif

{{-- ── Reject the sample ─────────────────────────────────────────────── --}}
@if($canReject)
<div class="panel form-panel mt-4">
    <div class="panel-body">
        <details>
            <summary class="btn btn-secondary"><i data-lucide="ban"></i> {{ __('lab_manager.reject') }}</summary>
            <form method="POST" action="{{ route('portals.lab_manager.reject', $order->id) }}" class="form-grid">
                @csrf
                <p class="form-hint">{{ __('lab_manager.reject_hint') }}</p>
                <div class="form-group">
                    <label class="form-label form-label-required" for="reject-reason">{{ __('lab_manager.reject_reason') }}</label>
                    <input type="text" id="reject-reason" name="reason" class="form-control" required minlength="3" maxlength="1000" value="{{ old('reason') }}">
                </div>
                <div class="row-actions-inline">
                    <button type="submit" class="btn btn-danger"><i data-lucide="ban"></i> {{ __('lab_manager.submit_reject') }}</button>
                </div>
            </form>
        </details>
    </div>
</div>
@endif

@endsection
