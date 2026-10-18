@extends('layouts.portal')
@php
    $l  = app()->getLocale();
    $rx = $prescription;
    $patient = $rx->patient;
    $patientName = $patient ? trim((string) ($patient->first_name ?? '') . ' ' . (string) ($patient->last_name ?? '')) : '';
    $patientInitials = $patient ? mb_strtoupper(mb_substr((string) ($patient->first_name ?? ''), 0, 1) . mb_substr((string) ($patient->last_name ?? ''), 0, 1)) : '';
    $rxStatus = (string) ($rx->status ?? '');
    $canDispense = $rx->isDispensable();
    $owed = $rx->items->filter(fn ($it) => $it->isOutstanding());
    $issuedHere = (string) $rx->facility_id === (string) $facility->id;
@endphp
@section('title', __('pharmacy_desk.rx_title', ['reference' => $rx->reference ?? '—'], $l))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label', [], $l))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home', [], $l))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_desk.rx_breadcrumb', [], $l))

@section('content')

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-triangle"></i><div>{{ session('error') }}</div></div>@endif

<div class="page-head">
    <h2>
        <span class="mono">{{ $rx->reference ?: '—' }}</span>
        <span class="badge badge-{{ $rx->statusColor() }}">@enum($rxStatus)</span>
    </h2>
    <p class="page-subtitle">{{ __('pharmacy_desk.rx_subtitle', [], $l) }}</p>
    <div class="page-head__spacer"></div>
    <div class="ph-actions">
        <a href="{{ route('portals.pharmacy.prescriptions') }}" class="btn btn-ghost btn-sm"><i data-lucide="arrow-left"></i> {{ __('pharmacy_desk.btn_back_queue', [], $l) }}</a>
        <a href="{{ route('portals.pharmacy.prescriptions.lookup') }}" class="btn btn-secondary btn-sm"><i data-lucide="scan-line"></i> {{ __('pharmacy_desk.btn_lookup', [], $l) }}</a>
        @if($issuedHere)
        @capability('prescribe')
        <a href="{{ route('portals.prescriber.print', $rx->id) }}" class="btn btn-secondary btn-sm" target="_blank" rel="noopener"><i data-lucide="file-text"></i> {{ __('pharmacy_desk.btn_print_sheet', [], $l) }}</a>
        @endcapability
        @endif
        <button type="button" class="btn btn-secondary btn-sm" data-print><i data-lucide="printer"></i> {{ __('pharmacy_desk.btn_print', [], $l) }}</button>
    </div>
</div>

<div class="ph-split">

    {{-- Left column: items + dispense form, then the dispensing history --}}
    <div class="ph-stack">

        {{-- Interaction alerts, only when there is one to show --}}
        @if($interactions !== [])
        <div class="panel" id="alerts">
            <div class="panel-header"><h3 class="panel-title"><i data-lucide="alert-octagon"></i> {{ __('pharmacy_desk.sec_interactions', [], $l) }} <span class="ph-count-inline">{{ __('pharmacy_desk.count_paren', ['count' => count($interactions)], $l) }}</span></h3></div>
            <div class="panel-body ph-alert-stack">
                @foreach($interactions as $alert)
                @php $sev = (string) $alert['rule']->severity; @endphp
                <div class="banner banner--{{ in_array($sev, ['major', 'contraindicated'], true) ? 'danger' : 'warning' }}">
                    <i data-lucide="alert-triangle"></i>
                    <div>
                        <strong>{{ __('pharmacy_desk.interaction_pair', ['a' => $alert['a'], 'b' => $alert['b']], $l) }}</strong>
                        <span class="badge badge-sm ph-sev--{{ $sev }}">{{ __('pharmacy_desk.severity.' . $sev, [], $l) }}</span><br>
                        {{ $alert['rule']->interaction_description }}
                        @if(filled($alert['rule']->management))<br><em>{{ __('pharmacy_desk.management', [], $l) }}:</em> {{ $alert['rule']->management }}@endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Items + dispense form. One form: the table's checkboxes are the item_ids[]. --}}
        <form method="POST" action="{{ route('portals.pharmacy.dispense', $rx->id) }}" class="panel" id="prescription" data-confirm="{{ __('pharmacy_desk.confirm_dispense', [], $l) }}">
            @csrf
            <input type="hidden" name="return_to" value="detail">
            <div class="panel-header">
                <h3 class="panel-title"><i data-lucide="pill"></i> {{ __('pharmacy_desk.sec_items', [], $l) }} <span class="ph-count-inline">{{ __('pharmacy_desk.count_paren', ['count' => $rx->items->count()], $l) }}</span></h3>
                <span class="td-muted">{{ __('pharmacy_desk.items_count', ['count' => $rx->items->count()], $l) }}</span>
            </div>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr>
                        @if($canDispense)<th class="row-actions">{{ __('pharmacy_desk.col_select', [], $l) }}</th>@endif
                        <th>{{ __('pharmacy_desk.col_drug', [], $l) }}</th>
                        <th>{{ __('pharmacy_desk.col_dose', [], $l) }}</th>
                        <th>{{ __('pharmacy_desk.col_qty', [], $l) }}</th>
                        <th>{{ __('pharmacy_desk.col_batch', [], $l) }}</th>
                        <th>{{ __('pharmacy_desk.col_on_hand', [], $l) }}</th>
                        <th>{{ __('pharmacy_desk.col_status', [], $l) }}</th>
                    </tr></thead>
                    <tbody>
                    @forelse($lines as $line)
                    @php $it = $line['item']; @endphp
                    <tr>
                        @if($canDispense)
                        <td class="row-actions" data-label="{{ __('pharmacy_desk.col_select', [], $l) }}">
                            @if($it->isOutstanding())
                            <label class="ph-check"><input type="checkbox" name="item_ids[]" value="{{ $it->id }}" checked aria-label="{{ $it->drug_name }}"></label>
                            @else —
                            @endif
                        </td>
                        @endif
                        <td data-label="{{ __('pharmacy_desk.col_drug', [], $l) }}">
                            <span class="td-strong">{{ $it->drug_name ?: '—' }}</span>
                            @if($line['controlled'])<span class="badge badge-danger badge-sm">{{ __('pharmacy_desk.badge_controlled', [], $l) }}</span>@endif
                            <div class="td-muted">{{ collect([$it->frequency, $it->route, $it->duration_days ? __('pharmacy_desk.days', ['count' => $it->duration_days], $l) : null])->filter()->implode(' · ') }}</div>
                        </td>
                        <td data-label="{{ __('pharmacy_desk.col_dose', [], $l) }}">{{ $it->dose ?: '—' }}</td>
                        <td data-label="{{ __('pharmacy_desk.col_qty', [], $l) }}" class="fs-mono">{{ $it->quantity ?? '—' }}</td>
                        <td data-label="{{ __('pharmacy_desk.col_batch', [], $l) }}">
                            @if(! $line['stock'])
                                <span class="td-muted">{{ __('pharmacy_desk.batch_no_ledger', [], $l) }}</span>
                            @elseif($line['batches']->isEmpty())
                                <span class="badge badge-warning badge-sm">{{ __('pharmacy_desk.batch_none', [], $l) }}</span>
                            @else
                                @foreach($line['batches']->take(3) as $b)
                                <span class="ph-batch"><span class="fs-mono">{{ $b->lot_number ?? $b->batch_number ?? '—' }}</span>
                                    · {{ $b->expiry_date ? __('pharmacy_desk.batch_expiry', ['date' => $b->expiry_date->translatedFormat('M Y')], $l) : __('pharmacy_desk.batch_undated', [], $l) }}
                                    · {{ __('pharmacy_desk.packs', ['count' => $b->quantity_available], $l) }}</span>
                                @endforeach
                            @endif
                        </td>
                        <td data-label="{{ __('pharmacy_desk.col_on_hand', [], $l) }}" class="fs-mono">{{ $line['stock'] ? $line['usable'] : '—' }}</td>
                        <td data-label="{{ __('pharmacy_desk.col_status', [], $l) }}">
                            @if($it->isDispensed())
                                <span class="badge badge-success badge-sm">{{ __('pharmacy_desk.line_done', ['date' => $it->dispensed_at?->translatedFormat('d M') ?? '—'], $l) }}</span>
                            @elseif($it->isClosed())
                                <span class="badge badge-neutral badge-sm">{{ __('pharmacy_desk.line_closed', [], $l) }}</span>
                            @else
                                @php $lineStatus = (string) ($it->status ?: 'active'); @endphp
                                <span class="badge badge-info badge-sm">@enum($lineStatus)</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="{{ $canDispense ? 7 : 6 }}" class="td-muted empty-cell">{{ __('pharmacy_desk.no_items', [], $l) }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="panel-body ph-dispense">
                @if($canDispense && $owed->isNotEmpty())
                    <p class="form-hint">{{ __('pharmacy_desk.dispense_hint', [], $l) }}</p>
                    <div class="ph-dispense-row">
                        <div class="form-group">
                            <label class="form-label" for="dispense_notes">{{ __('pharmacy_desk.dispense_notes', [], $l) }}</label>
                            <input type="text" id="dispense_notes" name="dispense_notes" class="form-control" maxlength="500" placeholder="{{ __('pharmacy_desk.dispense_notes_ph', [], $l) }}">
                        </div>
                        <button type="submit" class="btn btn-primary"><i data-lucide="check"></i> {{ __('pharmacy_desk.btn_dispense', [], $l) }}</button>
                    </div>
                @elseif($rxStatus === 'dispensed' || ($owed->isEmpty() && $rx->items->isNotEmpty()))
                    <div class="ph-status-line"><i data-lucide="check-circle-2"></i> {{ __('pharmacy_desk.nothing_outstanding', [], $l) }}</div>
                @else
                    <div class="ph-status-line"><i data-lucide="ban"></i> {{ __('pharmacy_desk.not_dispensable', [], $l) }}</div>
                @endif
            </div>
        </form>

        {{-- Dispensing history: who, when, which line, with its note --}}
        <div class="panel" id="history">
            <div class="panel-header"><h3 class="panel-title"><i data-lucide="history"></i> {{ __('pharmacy_desk.sec_history', [], $l) }}</h3></div>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr>
                        <th>{{ __('pharmacy_desk.col_when', [], $l) }}</th>
                        <th>{{ __('pharmacy_desk.col_drug', [], $l) }}</th>
                        <th>{{ __('pharmacy_desk.col_who', [], $l) }}</th>
                        <th>{{ __('pharmacy_desk.col_note', [], $l) }}</th>
                    </tr></thead>
                    <tbody>
                    @forelse($history as $h)
                    <tr>
                        <td data-label="{{ __('pharmacy_desk.col_when', [], $l) }}">{{ $h['at']?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                        <td data-label="{{ __('pharmacy_desk.col_drug', [], $l) }}" class="td-strong">{{ __('pharmacy_desk.history_line', ['drug' => $h['item']->drug_name, 'qty' => $h['item']->quantity ?? '—'], $l) }}</td>
                        <td data-label="{{ __('pharmacy_desk.col_who', [], $l) }}">{{ $dispenser?->name ?? '—' }}</td>
                        <td data-label="{{ __('pharmacy_desk.col_note', [], $l) }}" class="td-muted">{{ $h['item']->dispense_notes ?: '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="empty-cell">
                        <div class="empty-state"><i data-lucide="package-open" class="empty-state-icon"></i><p>{{ __('pharmacy_desk.history_empty', [], $l) }}</p></div>
                    </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Right column: patient & prescription detail panel. Name, Health ID,
         age band. Never the date of birth. --}}
    <div class="panel ph-detail">
        <div class="panel-header">
            <h3 class="panel-title"><i data-lucide="user"></i> {{ __('pharmacy_desk.sec_patient_rx', [], $l) }}</h3>
            <span class="badge badge-sm {{ $canDispense ? 'badge-success' : 'badge-neutral' }}">{{ $canDispense ? __('pharmacy_desk.status_ok', [], $l) : __('pharmacy_desk.status_blocked', [], $l) }}</span>
        </div>

        @if($patient)
        <div class="ph-detail__head">
            <span class="ph-avatar ph-detail__avatar" aria-hidden="true">{{ $patientInitials !== '' ? $patientInitials : '—' }}</span>
            <div>
                <div class="ph-detail__name">{{ $patientName !== '' ? $patientName : '—' }}</div>
                <div class="ph-detail__meta">
                    <span class="mono">{{ $rx->reference ?: '—' }}</span>
                    <span>&middot;</span>
                    <span>{{ ($rx->prescribed_at ?? $rx->created_at)?->translatedFormat('d M Y H:i') ?? '—' }}</span>
                </div>
            </div>
        </div>
        <div class="ph-chips">
            <span class="ph-chip" title="{{ __('pharmacy_desk.lbl_age_band', [], $l) }}"><i data-lucide="calendar"></i> {{ $ageBand ? __('pharmacy_desk.age_bands.' . $ageBand, [], $l) : __('pharmacy_desk.not_recorded', [], $l) }}</span>
            <span class="ph-chip" title="{{ __('pharmacy_desk.lbl_sex', [], $l) }}"><i data-lucide="user"></i> {{ filled($patient->sex) ? \App\Support\Enums::label((string) $patient->sex) : __('pharmacy_desk.not_recorded', [], $l) }}</span>
            <span class="ph-chip mono" title="{{ __('pharmacy_desk.lbl_health_id', [], $l) }}"><i data-lucide="id-card"></i> {{ $patient->health_id ?? '—' }}</span>
        </div>
        @else
        <div class="panel-body">
            <div class="empty-state"><i data-lucide="user-x" class="empty-state-icon"></i><p>{{ __('pharmacy_desk.patient_hidden', [], $l) }}</p></div>
        </div>
        @endif

        <nav class="ph-tabs" aria-label="{{ __('pharmacy_desk.rx_breadcrumb', [], $l) }}">
            <a href="#prescription" class="ph-pill active">{{ __('pharmacy_desk.tab_prescription', [], $l) }} <span class="ph-count-inline">{{ __('pharmacy_desk.count_paren', ['count' => $rx->items->count()], $l) }}</span></a>
            <a href="#history" class="ph-pill">{{ __('pharmacy_desk.tab_history', [], $l) }}</a>
            @if($interactions !== [])
            <a href="#alerts" class="ph-pill"><span class="ph-dot ph-dot--danger"></span> {{ __('pharmacy_desk.tab_alerts', [], $l) }} <span class="ph-count-inline">{{ __('pharmacy_desk.count_paren', ['count' => count($interactions)], $l) }}</span></a>
            @endif
        </nav>

        {{-- Prescriber + issuing facility; prescription type --}}
        <div class="ph-infocards">
            <div class="ph-infocard">
                <div class="ph-infocard__icon"><i data-lucide="stethoscope"></i></div>
                <div>
                    <div class="ph-infocard__k">{{ __('pharmacy_desk.lbl_prescriber', [], $l) }}</div>
                    <div class="ph-infocard__v">{{ $rx->prescriber?->name ?? '—' }}</div>
                    <div class="ph-infocard__k">{{ $rx->facility?->name ?? '—' }}</div>
                </div>
            </div>
            <div class="ph-infocard">
                <div class="ph-infocard__icon"><i data-lucide="file-text"></i></div>
                <div>
                    <div class="ph-infocard__k">{{ __('pharmacy_desk.lbl_type', [], $l) }}</div>
                    <div class="ph-infocard__v">{{ $issuedHere ? __('pharmacy_desk.type_issued_here', [], $l) : __('pharmacy_desk.type_external', [], $l) }}</div>
                    <div class="ph-infocard__k">
                        @if($rx->expires_at)
                            @if($rx->expires_at->isPast())<span class="badge badge-danger badge-sm">{{ __('pharmacy_desk.lbl_expires', [], $l) }} {{ $rx->expires_at->translatedFormat('d M Y') }}</span>
                            @else{{ __('pharmacy_desk.valid_until', ['date' => $rx->expires_at->translatedFormat('d M Y')], $l) }}@endif
                        @else
                            {{ __('pharmacy_desk.lbl_expires', [], $l) }}: —
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="ph-section">
            <div class="ph-section__title"><i data-lucide="info"></i> {{ __('pharmacy_desk.sec_prescriber', [], $l) }}</div>
            <table class="kv-table">
                <tr><td>{{ __('pharmacy_desk.lbl_prescriber', [], $l) }}</td><td class="kv-strong">{{ $rx->prescriber?->name ?? '—' }}</td></tr>
                <tr><td>{{ __('pharmacy_desk.lbl_facility', [], $l) }}</td><td class="kv-strong">{{ $rx->facility?->name ?? '—' }}</td></tr>
                <tr><td>{{ __('pharmacy_desk.lbl_prescribed', [], $l) }}</td><td class="kv-strong">{{ ($rx->prescribed_at ?? $rx->created_at)?->translatedFormat('d M Y H:i') ?? '—' }}</td></tr>
                <tr><td>{{ __('pharmacy_desk.lbl_expires', [], $l) }}</td><td class="kv-strong">
                    @if($rx->expires_at)
                        @if($rx->expires_at->isPast())<span class="badge badge-danger badge-sm">{{ $rx->expires_at->translatedFormat('d M Y') }}</span>@else{{ $rx->expires_at->translatedFormat('d M Y') }}@endif
                    @else —
                    @endif
                </td></tr>
                @if($dispenser)
                <tr><td>{{ __('pharmacy_desk.lbl_dispenser', [], $l) }}</td><td class="kv-strong">{{ $dispenser->name }}</td></tr>
                @endif
                @if($patient)
                <tr><td>{{ __('pharmacy_desk.lbl_name', [], $l) }}</td><td class="kv-strong">{{ $patientName !== '' ? $patientName : '—' }}</td></tr>
                <tr><td>{{ __('pharmacy_desk.lbl_health_id', [], $l) }}</td><td class="kv-strong mono">{{ $patient->health_id ?? '—' }}</td></tr>
                <tr><td>{{ __('pharmacy_desk.lbl_age_band', [], $l) }}</td><td class="kv-strong">{{ $ageBand ? __('pharmacy_desk.age_bands.' . $ageBand, [], $l) : __('pharmacy_desk.not_recorded', [], $l) }}</td></tr>
                <tr><td>{{ __('pharmacy_desk.lbl_sex', [], $l) }}</td><td class="kv-strong">{{ filled($patient->sex) ? \App\Support\Enums::label((string) $patient->sex) : __('pharmacy_desk.not_recorded', [], $l) }}</td></tr>
                @endif
            </table>
            @if(filled($rx->notes))
            <p class="ph-note"><strong>{{ __('pharmacy_desk.lbl_notes', [], $l) }}:</strong> {{ $rx->notes }}</p>
            @endif
        </div>

        {{-- Status timeline: only what happened carries a date --}}
        <div class="ph-section">
            <div class="ph-section__title"><i data-lucide="route"></i> {{ __('pharmacy_desk.sec_timeline', [], $l) }}</div>
            <ul class="ph-timeline">
                @foreach($timeline as $step)
                <li class="{{ $step['done'] ? 'is-done' : 'is-pending' }}">
                    <span class="ph-timeline__dot {{ $step['done'] ? '' : 'ph-timeline__dot--pending' }}"></span>
                    <div>
                        <div class="ph-timeline__label">{{ __('pharmacy_desk.step.' . $step['key'], [], $l) }}</div>
                        <div class="ph-timeline__when">{{ $step['done'] ? ($step['at']?->translatedFormat('d M Y H:i') ?? '—') : __('pharmacy_desk.step_pending', [], $l) }}</div>
                    </div>
                </li>
                @endforeach
            </ul>
        </div>
    </div>

</div>

@endsection
