@extends('layouts.portal')

@section('title', __('lab_desk.order_title'))
@section('breadcrumb_home', __('lab_desk.title'))
@section('breadcrumb_home_url', route('portals.lab.index'))
@section('breadcrumb_section', __('lab_desk.order_title'))

@php
    $p     = $order->patient;
    $pName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));

    $flagPill = fn (string $severity) => match ($severity) {
        'critical' => 'od-pill--critical',
        'warning'  => 'od-pill--warning',
        'success'  => 'od-pill--ok',
        default    => 'od-pill--neutral',
    };
    $urgency     = $order->deskUrgency();
    $urgencyPill = match ($urgency) { 'stat' => 'od-pill--stat', 'urgent' => 'od-pill--urgent', default => 'od-pill--normal' };
    $statusPill  = match ($status) {
        'collected'   => 'od-pill--received',
        'in_progress' => 'od-pill--pending',
        'resulted', 'validated' => 'od-pill--validated',
        'cancelled'   => 'od-pill--urgent',
        default       => 'od-pill--neutral',
    };

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

@section('head')
    @include('portals.lab.partials._head')
@endsection

@section('content')
<div class="od-page odl">

<header class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ $order->test_name }}</h1>
        <p class="od-page__sub odl-pills">
            <span class="od-pill {{ $statusPill }}">{{ __('lab_desk.status.' . $status) }}</span>
            <span class="od-pill od-pill--dot {{ $urgencyPill }}">{{ __('lab_desk.urgency.' . $urgency) }}</span>
        </p>
    </div>
    <div class="od-row">
        <a href="{{ route('portals.lab.index', ['status' => $status === 'cancelled' ? 'ordered' : $status]) }}" class="od-btn od-btn--secondary">
            <i data-lucide="arrow-left"></i> {{ __('lab_desk.back_to_worklist') }}
        </a>
        <a href="{{ route('documents.new.lab_request', $order->id) }}" class="od-btn od-btn--secondary" target="_blank" rel="noopener"><i data-lucide="file-text"></i> {{ __('doc_new_docs.btn_lab_request') }}</a>
        @if($released)
        {{-- The patient's own Secure sheet, generated now — for the walk-in patient without a phone. --}}
        <a href="{{ route('portals.lab_manager.report', $order->id) }}" class="od-btn od-btn--primary" title="{{ __('lab_manager.print_report_hint') }}">
            <i data-lucide="printer"></i> {{ __('lab_manager.print_report') }}
        </a>
        @endif
    </div>
</header>

@if(session('success'))<div class="od-flash od-flash--ok" role="status"><i data-lucide="check-circle"></i><span>{{ session('success') }}</span></div>@endif
@if(session('error'))<div class="od-flash od-flash--err" role="alert"><i data-lucide="alert-circle"></i><span>{{ session('error') }}</span></div>@endif
@if($errors->any())
<div class="od-flash od-flash--err" role="alert"><i data-lucide="alert-circle"></i><ul class="odl-errlist">@foreach($errors->all() as $m)<li>{{ $m }}</li>@endforeach</ul></div>
@endif

@if($validated)
<div class="od-flash od-flash--info" role="status"><i data-lucide="shield-check"></i><span>{{ __('lab_desk.validated_note') }}</span></div>
@endif

<div class="od-grid od-grid--main">
<div class="od-stack">

{{-- ── The order ─────────────────────────────────────────────────────── --}}
<section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title">{{ __('lab_desk.order_title') }}</h2></div>
    <div class="od-card__body od-detail">
        <div class="od-detail__head">
            @include('portals.lab.partials._avatar', ['name' => $pName, 'size' => 'lg'])
            <div class="od-detail__main">
                <h3 class="od-detail__name">{{ $pName !== '' ? $pName : '—' }}</h3>
                <div class="od-detail__ref">
                    <span>{{ $p?->health_id ?? '—' }}</span>
                    @if($age !== null)<span>{{ __('lab_desk.age_years', ['years' => $age]) }}</span>@endif
                    @if($p?->sex)<span>{{ $p->sex }}</span>@endif
                </div>
                <div class="od-detail__facts">
                    <span class="od-detail__fact"><i data-lucide="calendar-clock"></i> {{ $order->ordered_at?->translatedFormat('d M Y H:i') ?? '—' }}</span>
                    <span class="od-detail__fact"><i data-lucide="user-round"></i> {{ $order->orderedBy?->name ?? __('lab_desk.unknown') }}</span>
                    @if($validated)<span class="od-chip od-chip--ok"><i data-lucide="badge-check"></i> {{ __('lab_desk.status.validated') }}</span>@endif
                </div>
            </div>
        </div>

        <dl class="odl-facts">
            <div><dt>{{ __('lab_desk.test') }}</dt><dd><strong>{{ $order->test_name }}</strong><span>{{ __('lab_desk.test_code') }}: {{ $order->test_code ?: '—' }}</span></dd></div>
            <div><dt>{{ __('lab_desk.loinc') }}</dt><dd>@if($loinc)<strong>{{ $loinc }}</strong>@if($term)<span>{{ $term->label }}</span>@endif @else<span>{{ __('lab_desk.loinc_none') }}</span>@endif</dd></div>
            @if($term && ($term->specimen || $term->scale || $term->example_units))
            <div><dt>{{ __('lab_desk.dictionary') }}</dt><dd>
                @if($term->specimen)<span>{{ __('lab_desk.dictionary_specimen') }}: {{ $term->specimen }}</span>@endif
                @if($term->scale)<span>{{ __('lab_desk.dictionary_scale') }}: {{ $term->scale }}</span>@endif
                @if($term->example_units)<span>{{ __('lab_desk.dictionary_units') }}: {{ $term->example_units }}</span>@endif
            </dd></div>
            @endif
            <div><dt>{{ __('lab_desk.ordered_by') }}</dt><dd><strong>{{ $order->orderedBy?->name ?? __('lab_desk.unknown') }}</strong><span>{{ $order->ordered_at?->translatedFormat('d M Y H:i') ?? '—' }}</span></dd></div>
            <div><dt>{{ __('lab_desk.collected_at') }}</dt><dd><strong>{{ $order->collected_at?->translatedFormat('d M Y H:i') ?? __('lab_desk.not_yet') }}</strong></dd></div>
            <div><dt>{{ __('lab_desk.resulted_at') }}</dt><dd><strong>{{ $released ? ($order->resulted_at?->translatedFormat('d M Y H:i') ?? '—') : __('lab_desk.not_yet') }}</strong></dd></div>
            <div><dt>{{ __('lab_desk.validated_by') }}</dt><dd><strong>{{ $validated ? ($order->verifiedBy?->name ?? __('lab_desk.unknown')) : __('lab_desk.not_yet') }}</strong></dd></div>
            @if($order->clinical_indication)
            <div><dt>{{ __('lab_desk.indication') }}</dt><dd><span>{{ $order->clinical_indication }}</span></dd></div>
            @endif
            @if($order->notes)
            <div class="odl-facts__wide"><dt>{{ __('lab_desk.order_notes') }}</dt><dd><span>{!! nl2br(e($order->notes)) !!}</span></dd></div>
            @endif
        </dl>
    </div>
</section>

{{-- ── Results ───────────────────────────────────────────────────────── --}}
<section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title">{{ __('lab_desk.results_title') }} <span class="od-card__count">({{ $rows->count() }})</span></h2></div>
    <div class="od-card__body">
    @if($rows->isEmpty())
        <div class="od-empty">
            <span class="od-empty__icon"><i data-lucide="flask-conical"></i></span>
            <p class="od-empty__title">{{ __('lab_desk.empty_results') }}</p>
        </div>
    @else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead>
                    <tr>
                        <th>{{ __('lab_desk.col_analyte') }}</th>
                        <th>{{ __('lab_desk.col_value') }}</th>
                        <th>{{ __('lab_desk.col_reference') }}</th>
                        <th>{{ __('lab_desk.col_flag') }}</th>
                        <th>{{ __('lab_desk.col_entered_by') }}</th>
                        <th>{{ __('lab_desk.col_resulted_at') }}</th>
                        @if($open)<th class="od-table__actions">{{ __('lab_desk.col_actions') }}</th>@endif
                    </tr>
                </thead>
                <tbody>
                @foreach($rows as $row)
                    @php $r = $row['result']; $alert = $row['alert']; @endphp
                    <tr>
                        <td data-label="{{ __('lab_desk.col_analyte') }}">
                            <div class="od-table__name">{{ $r->parameter_name }}</div>
                            @if($r->loinc_code)<div class="od-table__sub">{{ __('lab_desk.loinc') }} {{ $r->loinc_code }}</div>@endif
                            @if($row['superseded'])<span class="od-pill od-pill--neutral">{{ __('lab_desk.superseded') }}</span>@endif
                            @if($row['corrects'])<span class="od-pill od-pill--info">{{ __('lab_desk.corrects') }}</span>@endif
                        </td>
                        <td data-label="{{ __('lab_desk.col_value') }}">
                            <span class="od-strong {{ $row['severity'] === 'critical' ? 'is-alert' : '' }}">{{ $r->value }}</span>
                            <span class="od-muted">{{ $r->unit ?: __('lab_desk.no_unit') }}</span>
                        </td>
                        <td data-label="{{ __('lab_desk.col_reference') }}" class="od-muted">
                            @if($r->reference_range)
                                {{ $r->reference_range }}
                            @elseif($row['ref_low'] !== null || $row['ref_high'] !== null)
                                {{ $num($row['ref_low']) }}–{{ $num($row['ref_high']) }}
                            @else
                                {{ __('lab_desk.no_range') }}
                            @endif
                        </td>
                        <td data-label="{{ __('lab_desk.col_flag') }}">
                            <span class="od-pill {{ $flagPill($row['severity']) }}">{{ __('lab_desk.flag.' . $row['flag']) }}</span>
                            @if($row['flag_source'])
                                <div class="od-table__sub">{{ __('lab_desk.flag_source_' . $row['flag_source']) }}</div>
                            @endif
                            @if($alert)
                                <div class="od-table__sub odl-alertline">
                                    <i data-lucide="siren"></i> {{ __('lab_desk.alert_title') }} &middot;
                                    {{ __('lab_desk.alert_threshold', ['threshold' => $alert->critical_threshold]) }}<br>
                                    @if($alert->acknowledged)
                                        {{ __('lab_desk.alert_acknowledged', ['name' => $alert->acknowledgedBy?->name ?? __('lab_desk.unknown'), 'at' => $alert->acknowledged_at?->translatedFormat('d M Y H:i') ?? '—']) }}
                                    @else
                                        <span class="od-pill od-pill--pending">{{ __('lab_desk.alert_pending') }}</span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td data-label="{{ __('lab_desk.col_entered_by') }}" class="od-muted">{{ $row['entered_by'] ?? $row['released_by'] ?? __('lab_desk.unknown') }}</td>
                        <td data-label="{{ __('lab_desk.col_resulted_at') }}" class="od-muted odl-nowrap">
                            {{ $r->resulted_at?->translatedFormat('d M Y H:i') ?? '—' }}
                            @if($r->notes)<div>{{ $r->notes }}</div>@endif
                        </td>
                        @if($open)
                        <td class="od-table__actions" data-label="{{ __('lab_desk.col_actions') }}">
                            @if(! $row['superseded'])
                            <details class="odl-details">
                                <summary class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="pencil-line"></i> {{ __('lab_desk.correct') }}</summary>
                                <form method="POST" action="{{ route('portals.lab.results.correct', [$order->id, $r->id]) }}" class="odl-form">
                                    @csrf
                                    <p class="od-muted odl-small">{{ __('lab_desk.correct_hint') }}</p>
                                    <label class="odl-label" for="correct-value-{{ $r->id }}">{{ __('lab_desk.value') }}</label>
                                    <input type="text" id="correct-value-{{ $r->id }}" name="value" class="od-input" required maxlength="100">
                                    <label class="odl-label" for="correct-unit-{{ $r->id }}">{{ __('lab_desk.unit') }}</label>
                                    <input type="text" id="correct-unit-{{ $r->id }}" name="unit" class="od-input" value="{{ $r->unit }}" maxlength="50">
                                    <label class="odl-label" for="correct-reason-{{ $r->id }}">{{ __('lab_desk.reason') }}</label>
                                    <input type="text" id="correct-reason-{{ $r->id }}" name="reason" class="od-input" required maxlength="1000">
                                    <button type="submit" class="od-btn od-btn--primary"><i data-lucide="save"></i> {{ __('lab_desk.submit_correction') }}</button>
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
            <p class="od-muted odl-small odl-foot">{{ __('lab_desk.alert_note') }}</p>
        @endif
    @endif
    </div>
</section>

</div>{{-- /left column --}}
<div class="od-stack">

{{-- ── Release / validate ────────────────────────────────────────────── --}}
@if($canRelease || $canDoValidate || ($open && $status === 'resulted'))
<section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title">{{ __('lab_desk_ui.sign_off') }}</h2></div>
    <div class="od-card__body odl-signoff">
        @if($canRelease)
            <form method="POST" action="{{ route('portals.lab.release', $order->id) }}">
                @csrf
                <button type="submit" class="od-btn od-btn--primary od-btn--block"><i data-lucide="send"></i> {{ __('lab_desk.release') }}</button>
                <p class="od-muted odl-small">{{ __('lab_desk.release_hint') }}</p>
            </form>
        @endif
        @if($canDoValidate)
            <form method="POST" action="{{ route('portals.lab.validate', $order->id) }}">
                @csrf
                <button type="submit" class="od-btn od-btn--primary od-btn--block"><i data-lucide="shield-check"></i> {{ __('lab_desk.validate') }}</button>
                <p class="od-muted odl-small">{{ __('lab_desk.validate_hint') }}</p>
            </form>
        @elseif($open && $status === 'resulted')
            <p class="od-muted odl-small odl-inline"><i data-lucide="lock"></i> {{ __('lab_desk.validation_locked') }}</p>
        @endif
    </div>
</section>
@endif

{{-- ── Enter a result ────────────────────────────────────────────────── --}}
@if($canEnter)
<section class="od-card">
    <div class="od-card__head"><h2 class="od-card__title">{{ __('lab_desk.entry_title') }}</h2></div>
    <div class="od-card__body">
        <p class="od-muted odl-small">{{ __('lab_desk.entry_hint') }}</p>

        <div class="od-flash {{ $published ? 'od-flash--info' : 'od-flash--warn' }}">
            <i data-lucide="{{ $published ? 'ruler' : 'circle-help' }}"></i>
            <span>
                @if($published)
                    <strong>{{ __('lab_desk.range_preview') }}:</strong>
                    {{ $num($published->normal_low) ?? '' }}–{{ $num($published->normal_high) ?? '' }} {{ $published->unit }}
                    @if($published->critical_low !== null || $published->critical_high !== null)
                        <br>{{ __('lab_desk.range_critical', ['low' => $num($published->critical_low) ?? '—', 'high' => $num($published->critical_high) ?? '—']) }}
                    @endif
                @else
                    {{ __('lab_desk.range_preview_none') }}
                @endif
            </span>
        </div>

        <form method="POST" action="{{ route('portals.lab.results.store', $order->id) }}" class="odl-form">
            @csrf
            <label class="odl-label" for="entry-parameter">{{ __('lab_desk.parameter_name') }}</label>
            <input type="text" id="entry-parameter" name="parameter_name" class="od-input" value="{{ old('parameter_name', $order->test_name) }}" maxlength="200">
            <p class="od-muted odl-small">{{ __('lab_desk.parameter_hint') }}</p>

            <label class="odl-label" for="entry-value">{{ __('lab_desk.value') }} <span class="odl-req" aria-hidden="true">*</span></label>
            <input type="text" id="entry-value" name="value" class="od-input" value="{{ old('value') }}" required maxlength="100" autocomplete="off">
            <p class="od-muted odl-small">{{ __('lab_desk.value_hint') }}</p>

            <label class="odl-label" for="entry-unit">{{ __('lab_desk.unit') }}</label>
            <input type="text" id="entry-unit" name="unit" class="od-input" value="{{ old('unit') }}" maxlength="50"
                   @if($term?->example_units) list="entry-unit-options" @endif>
            @if($term?->example_units)
                <datalist id="entry-unit-options">
                    @foreach(preg_split('/\s*[;|]\s*/', (string) $term->example_units) as $u)
                        @if(trim($u) !== '')<option value="{{ trim($u) }}">@endif
                    @endforeach
                </datalist>
                <p class="od-muted odl-small">{{ __('lab_desk.unit_suggested', ['units' => $term->example_units]) }}</p>
            @else
                <p class="od-muted odl-small">{{ __('lab_desk.unit_hint') }}</p>
            @endif

            <label class="odl-label" for="entry-notes">{{ __('lab_desk.notes') }}</label>
            <input type="text" id="entry-notes" name="notes" class="od-input" value="{{ old('notes') }}" maxlength="1000">

            <button type="submit" class="od-btn od-btn--primary od-btn--block"><i data-lucide="save"></i> {{ __('lab_desk.submit_result') }}</button>
        </form>
    </div>
</section>
@endif

{{-- ── Reject the sample ─────────────────────────────────────────────── --}}
@if($canReject)
<section class="od-card">
    <div class="od-card__body odl-pad-top">
        <details class="odl-details odl-details--block">
            <summary class="od-btn od-btn--secondary od-btn--block"><i data-lucide="ban"></i> {{ __('lab_manager.reject') }}</summary>
            <form method="POST" action="{{ route('portals.lab_manager.reject', $order->id) }}" class="odl-form">
                @csrf
                <p class="od-muted odl-small">{{ __('lab_manager.reject_hint') }}</p>
                <label class="odl-label" for="reject-reason">{{ __('lab_manager.reject_reason') }} <span class="odl-req" aria-hidden="true">*</span></label>
                <input type="text" id="reject-reason" name="reason" class="od-input" required minlength="3" maxlength="1000" value="{{ old('reason') }}">
                <button type="submit" class="od-btn od-btn--danger"><i data-lucide="ban"></i> {{ __('lab_manager.submit_reject') }}</button>
            </form>
        </details>
    </div>
</section>
@endif

</div>{{-- /right column --}}
</div>

</div>
@endsection
