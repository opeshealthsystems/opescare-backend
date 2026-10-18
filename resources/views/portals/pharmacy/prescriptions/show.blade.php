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
    $tone = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($patientName !== '' ? $patientName : (string) $rx->id) % 6];
    $statusPill = match ($rxStatus) {
        'active'              => 'pending',
        'partially_dispensed' => 'verifying',
        'dispensed'           => 'ready',
        'cancelled', 'expired', 'voided', 'entered_in_error' => 'urgent',
        default               => 'neutral',
    };
    $identity = $patient?->identity_status instanceof \BackedEnum ? $patient->identity_status->value : (string) ($patient?->identity_status ?? '');
    $verified = in_array($identity, ['verified', 'verified_by_facility'], true);
@endphp
@section('title', __('pharmacy_desk.rx_title', ['reference' => $rx->reference ?? '—'], $l))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label', [], $l))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home', [], $l))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_desk.rx_breadcrumb', [], $l))

@section('head')
    <link rel="stylesheet" href="{{ asset('css/od-pharmacy.css') }}?v={{ @filemtime(public_path('css/od-pharmacy.css')) ?: '1' }}">
@endsection

@section('content')

@if(session('success'))<div class="alert alert-success"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger"><i data-lucide="alert-triangle"></i><div>{{ session('error') }}</div></div>@endif

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ $rx->reference ?: '—' }} <span class="od-pill od-pill--{{ $statusPill }}">@enum($rxStatus)</span></h1>
        <p class="od-page__sub">{{ __('pharmacy_desk.rx_subtitle', [], $l) }}</p>
    </div>
    <div class="odp-head-actions">
        <a href="{{ route('portals.pharmacy.prescriptions') }}" class="od-btn od-btn--ghost"><i data-lucide="arrow-left"></i> {{ __('pharmacy_desk.btn_back_queue', [], $l) }}</a>
        <a href="{{ route('portals.pharmacy.prescriptions.lookup') }}" class="od-btn od-btn--secondary"><x-od-icon name="barcode" /> {{ __('pharmacy_desk.btn_lookup', [], $l) }}</a>
        @if($issuedHere)
        @capability('prescribe')
        <a href="{{ route('portals.prescriber.print', $rx->id) }}" class="od-btn od-btn--secondary" target="_blank" rel="noopener"><i data-lucide="file-text"></i> {{ __('pharmacy_desk.btn_print_sheet', [], $l) }}</a>
        @endcapability
        @endif
        <button type="button" class="od-btn od-btn--secondary" data-print><i data-lucide="printer"></i> {{ __('pharmacy_desk.btn_print', [], $l) }}</button>
        @if($rx->items->contains(fn ($i) => $i->dispensed_at !== null))<a href="{{ route('documents.new.labels', $rx->id) }}" class="od-btn od-btn--secondary" target="_blank" rel="noopener"><i data-lucide="tag"></i> {{ __('doc_new_docs.btn_labels') }}</a>@endif
    </div>
</div>

<div class="od-grid od-grid--split">

    {{-- Left: the reference detail panel at full page — patient, tabs, items + dispense form --}}
    <div class="od-stack">
        <section class="od-card" id="prescription">
            <header class="od-card__head">
                <h2 class="od-card__title">{{ __('pharmacy_desk.sec_patient_rx', [], $l) }}</h2>
                @if($verified)
                    <span class="od-chip od-chip--ok"><i data-lucide="shield-check"></i> {{ __('pharmacy_desk.od_verified', [], $l) }}</span>
                @else
                    <span class="od-chip {{ $canDispense ? 'od-chip--ok' : '' }}">{{ $canDispense ? __('pharmacy_desk.status_ok', [], $l) : __('pharmacy_desk.status_blocked', [], $l) }}</span>
                @endif
            </header>
            <div class="od-card__body od-detail">
                @if($patient)
                <div class="od-detail__head">
                    <span class="od-avatar od-avatar--lg od-tint--{{ $tone }}" aria-hidden="true">{{ $patientInitials !== '' ? $patientInitials : '—' }}</span>
                    <div class="od-detail__main">
                        <h3 class="od-detail__name">{{ $patientName !== '' ? $patientName : '—' }}</h3>
                        <div class="od-detail__ref">
                            <span>{{ $rx->reference ?: '—' }}</span>
                            <span>{{ ($rx->prescribed_at ?? $rx->created_at)?->translatedFormat('d M Y') ?? '—' }}</span>
                            <span>{{ ($rx->prescribed_at ?? $rx->created_at)?->format('H:i') }}</span>
                        </div>
                        <div class="od-detail__facts">
                            <span class="od-detail__fact" title="{{ __('pharmacy_desk.lbl_age_band', [], $l) }}"><i data-lucide="calendar"></i> {{ $ageBand ? __('pharmacy_desk.age_bands.' . $ageBand, [], $l) : __('pharmacy_desk.not_recorded', [], $l) }}</span>
                            <span class="od-detail__fact" title="{{ __('pharmacy_desk.lbl_sex', [], $l) }}"><i data-lucide="user"></i> {{ filled($patient->sex) ? \App\Support\Enums::label((string) $patient->sex) : __('pharmacy_desk.not_recorded', [], $l) }}</span>
                            <span class="od-detail__fact" title="{{ __('pharmacy_desk.lbl_health_id', [], $l) }}"><i data-lucide="id-card"></i> {{ $patient->health_id ?? '—' }}</span>
                        </div>
                    </div>
                </div>
                @else
                <div class="od-empty">
                    <span class="od-empty__icon"><i data-lucide="user-x"></i></span>
                    <div class="od-empty__title">{{ __('pharmacy_desk.patient_hidden', [], $l) }}</div>
                </div>
                @endif

                <nav class="od-tabs od-tabs--inner" aria-label="{{ __('pharmacy_desk.rx_breadcrumb', [], $l) }}">
                    <a href="#prescription" class="od-tab is-active">{{ __('pharmacy_desk.tab_prescription', [], $l) }} <span class="od-tab__count">({{ $rx->items->count() }})</span></a>
                    <a href="#history" class="od-tab">{{ __('pharmacy_desk.tab_history', [], $l) }}</a>
                    <a href="#alerts" class="od-tab">@if($interactions !== [])<span class="od-tab__dot od-tab__dot--urgent"></span>@endif {{ __('pharmacy_desk.tab_alerts', [], $l) }} <span class="od-tab__count">({{ count($interactions) }})</span></a>
                    <a href="#notes" class="od-tab">{{ __('pharmacy_desk.od_tab_notes', [], $l) }}</a>
                </nav>

                {{-- Items + dispense form. One form: the table's checkboxes are the item_ids[]. --}}
                <form method="POST" action="{{ route('portals.pharmacy.dispense', $rx->id) }}" class="od-stack" data-confirm="{{ __('pharmacy_desk.confirm_dispense', [], $l) }}">
                    @csrf
                    <input type="hidden" name="return_to" value="detail">
                    <div class="od-table-wrap">
                        <table class="od-table odp-stack-sm">
                            <thead><tr>
                                @if($canDispense)<th class="od-table__check">{{ __('pharmacy_desk.col_select', [], $l) }}</th>@endif
                                <th class="od-table__num">#</th>
                                <th>{{ __('pharmacy_desk.col_drug', [], $l) }}</th>
                                <th>{{ __('pharmacy_desk.col_dose', [], $l) }}</th>
                                <th>{{ __('pharmacy_desk.col_qty', [], $l) }}</th>
                                <th>{{ __('pharmacy_desk.col_batch', [], $l) }}</th>
                                <th>{{ __('pharmacy_desk.col_on_hand', [], $l) }}</th>
                                <th>{{ __('pharmacy_desk.col_status', [], $l) }}</th>
                            </tr></thead>
                            <tbody>
                            @forelse($lines as $n => $line)
                            @php $it = $line['item']; @endphp
                            <tr>
                                @if($canDispense)
                                <td class="od-table__check" data-label="{{ __('pharmacy_desk.col_select', [], $l) }}">
                                    @if($it->isOutstanding())
                                    <input type="checkbox" class="od-check" name="item_ids[]" value="{{ $it->id }}" checked aria-label="{{ $it->drug_name }}">
                                    @else — @endif
                                </td>
                                @endif
                                <td class="od-table__num">{{ $n + 1 }}</td>
                                <td data-label="{{ __('pharmacy_desk.col_drug', [], $l) }}">
                                    <span class="od-table__name">{{ $it->drug_name ?: '—' }} @if($line['controlled'])<span class="od-pill od-pill--urgent">{{ __('pharmacy_desk.badge_controlled', [], $l) }}</span>@endif</span>
                                    <span class="od-table__sub">{{ collect([$it->frequency, $it->route, $it->duration_days ? __('pharmacy_desk.days', ['count' => $it->duration_days], $l) : null])->filter()->implode(' · ') }}</span>
                                </td>
                                <td data-label="{{ __('pharmacy_desk.col_dose', [], $l) }}">{{ $it->dose ?: '—' }}</td>
                                <td data-label="{{ __('pharmacy_desk.col_qty', [], $l) }}" class="odp-mono">{{ $it->quantity ?? '—' }}</td>
                                <td data-label="{{ __('pharmacy_desk.col_batch', [], $l) }}">
                                    @if(! $line['stock'])
                                        <span class="od-muted">{{ __('pharmacy_desk.batch_no_ledger', [], $l) }}</span>
                                    @elseif($line['batches']->isEmpty())
                                        <span class="od-pill od-pill--reorder">{{ __('pharmacy_desk.batch_none', [], $l) }}</span>
                                    @else
                                        @foreach($line['batches']->take(3) as $b)
                                        <span class="odp-batch"><span class="od-strong">{{ $b->lot_number ?? $b->batch_number ?? '—' }}</span>
                                            · {{ $b->expiry_date ? __('pharmacy_desk.batch_expiry', ['date' => $b->expiry_date->translatedFormat('M Y')], $l) : __('pharmacy_desk.batch_undated', [], $l) }}
                                            · {{ __('pharmacy_desk.packs', ['count' => $b->quantity_available], $l) }}</span>
                                        @endforeach
                                    @endif
                                </td>
                                <td data-label="{{ __('pharmacy_desk.col_on_hand', [], $l) }}" class="odp-mono">{{ $line['stock'] ? $line['usable'] : '—' }}</td>
                                <td data-label="{{ __('pharmacy_desk.col_status', [], $l) }}">
                                    @if($it->isDispensed())
                                        <span class="od-pill od-pill--ready">{{ __('pharmacy_desk.line_done', ['date' => $it->dispensed_at?->translatedFormat('d M') ?? '—'], $l) }}</span>
                                    @elseif($it->isClosed())
                                        <span class="od-pill od-pill--neutral">{{ __('pharmacy_desk.line_closed', [], $l) }}</span>
                                    @else
                                        @php $lineStatus = (string) ($it->status ?: 'active'); @endphp
                                        <span class="od-pill od-pill--pending">@enum($lineStatus)</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="{{ $canDispense ? 8 : 7 }}" class="od-muted">{{ __('pharmacy_desk.no_items', [], $l) }}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($canDispense && $owed->isNotEmpty())
                        <p class="odp-hint">{{ __('pharmacy_desk.dispense_hint', [], $l) }}</p>
                        <div class="odp-form-row">
                            <label for="dispense_notes">{{ __('pharmacy_desk.dispense_notes', [], $l) }}
                                <input type="text" id="dispense_notes" name="dispense_notes" class="od-input" maxlength="500" placeholder="{{ __('pharmacy_desk.dispense_notes_ph', [], $l) }}">
                            </label>
                            <button type="submit" class="od-btn od-btn--primary"><x-od-icon name="capsule" /> {{ __('pharmacy_desk.btn_dispense', [], $l) }}</button>
                        </div>
                    @elseif($rxStatus === 'dispensed' || ($owed->isEmpty() && $rx->items->isNotEmpty()))
                        <div class="odp-notice od-tint--green"><i data-lucide="check-circle-2"></i> {{ __('pharmacy_desk.nothing_outstanding', [], $l) }}</div>
                    @else
                        <div class="odp-notice od-tint--red"><i data-lucide="ban"></i> {{ __('pharmacy_desk.not_dispensable', [], $l) }}</div>
                    @endif
                </form>
            </div>
        </section>

        {{-- Interaction alerts --}}
        <section class="od-card" id="alerts">
            <header class="od-card__head"><h2 class="od-card__title">{{ __('pharmacy_desk.sec_interactions', [], $l) }} <span class="od-card__count">{{ __('pharmacy_desk.count_paren', ['count' => count($interactions)], $l) }}</span></h2></header>
            <div class="od-card__body od-stack">
                @forelse($interactions as $alert)
                @php $sev = (string) $alert['rule']->severity; $major = in_array($sev, ['major', 'contraindicated'], true); @endphp
                <div class="odp-notice {{ $major ? 'od-tint--red' : 'od-tint--amber' }}">
                    <i data-lucide="alert-triangle"></i>
                    <div>
                        <strong>{{ __('pharmacy_desk.interaction_pair', ['a' => $alert['a'], 'b' => $alert['b']], $l) }}</strong>
                        <span class="od-pill {{ $major ? 'od-pill--urgent' : 'od-pill--reorder' }}">{{ __('pharmacy_desk.severity.' . $sev, [], $l) }}</span>
                        <p>{{ $alert['rule']->interaction_description }}</p>
                        @if(filled($alert['rule']->management))<p><em>{{ __('pharmacy_desk.management', [], $l) }}:</em> {{ $alert['rule']->management }}</p>@endif
                    </div>
                </div>
                @empty
                <p class="od-muted">{{ __('pharmacy_desk.interactions_none', [], $l) }}</p>
                @endforelse
            </div>
        </section>

        {{-- Dispensing history --}}
        <section class="od-card" id="history">
            <header class="od-card__head"><h2 class="od-card__title">{{ __('pharmacy_desk.sec_history', [], $l) }}</h2></header>
            @if($history === [])
                <div class="od-empty">
                    <span class="od-empty__icon"><i data-lucide="history"></i></span>
                    <p class="od-empty__text">{{ __('pharmacy_desk.history_empty', [], $l) }}</p>
                </div>
            @else
            <div class="od-card__body odp-queue__body">
            <div class="od-table-wrap">
                <table class="od-table odp-stack-sm">
                    <thead><tr>
                        <th>{{ __('pharmacy_desk.col_when', [], $l) }}</th>
                        <th>{{ __('pharmacy_desk.col_drug', [], $l) }}</th>
                        <th>{{ __('pharmacy_desk.col_who', [], $l) }}</th>
                        <th>{{ __('pharmacy_desk.col_note', [], $l) }}</th>
                    </tr></thead>
                    <tbody>
                    @foreach($history as $h)
                    <tr>
                        <td data-label="{{ __('pharmacy_desk.col_when', [], $l) }}">{{ $h['at']?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                        <td data-label="{{ __('pharmacy_desk.col_drug', [], $l) }}" class="od-strong">{{ __('pharmacy_desk.history_line', ['drug' => $h['item']->drug_name, 'qty' => $h['item']->quantity ?? '—'], $l) }}</td>
                        <td data-label="{{ __('pharmacy_desk.col_who', [], $l) }}">{{ $dispenser?->name ?? '—' }}</td>
                        <td data-label="{{ __('pharmacy_desk.col_note', [], $l) }}" class="od-muted">{{ $h['item']->dispense_notes ?: '—' }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            </div>
            @endif
        </section>
    </div>

    {{-- Right: prescriber + type cards, prescriber notes, progress --}}
    <div class="od-stack">
        <section class="od-card">
            <header class="od-card__head"><h2 class="od-card__title">{{ __('pharmacy_desk.sec_prescriber', [], $l) }}</h2></header>
            <div class="od-card__body od-detail">
                <div class="od-detail__people">
                    <div class="od-person">
                        <span class="od-person__icon"><i data-lucide="stethoscope"></i></span>
                        <div>
                            <span class="od-person__label">{{ __('pharmacy_desk.lbl_prescriber', [], $l) }}</span>
                            <span class="od-person__name">{{ $rx->prescriber?->name ?? '—' }}</span>
                            <span class="od-person__sub">{{ $rx->facility?->name ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="od-person">
                        <span class="od-person__icon"><i data-lucide="file-text"></i></span>
                        <div>
                            <span class="od-person__label">{{ __('pharmacy_desk.lbl_type', [], $l) }}</span>
                            <span class="od-person__name">{{ $issuedHere ? __('pharmacy_desk.type_issued_here', [], $l) : __('pharmacy_desk.type_external', [], $l) }}</span>
                            <span class="od-person__sub">
                                @if($rx->expires_at)
                                    @if($rx->expires_at->isPast())<span class="od-pill od-pill--urgent">{{ __('pharmacy_desk.lbl_expires', [], $l) }} {{ $rx->expires_at->translatedFormat('d M Y') }}</span>
                                    @else{{ __('pharmacy_desk.valid_until', ['date' => $rx->expires_at->translatedFormat('d M Y')], $l) }}@endif
                                @else
                                    {{ __('pharmacy_desk.lbl_expires', [], $l) }}: —
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
                <dl class="odp-dl">
                    <dt>{{ __('pharmacy_desk.lbl_prescriber', [], $l) }}</dt><dd>{{ $rx->prescriber?->name ?? '—' }}</dd>
                    <dt>{{ __('pharmacy_desk.lbl_facility', [], $l) }}</dt><dd>{{ $rx->facility?->name ?? '—' }}</dd>
                    <dt>{{ __('pharmacy_desk.lbl_prescribed', [], $l) }}</dt><dd>{{ ($rx->prescribed_at ?? $rx->created_at)?->translatedFormat('d M Y H:i') ?? '—' }}</dd>
                    <dt>{{ __('pharmacy_desk.lbl_expires', [], $l) }}</dt><dd>{{ $rx->expires_at?->translatedFormat('d M Y') ?? '—' }}</dd>
                    @if($dispenser)
                    <dt>{{ __('pharmacy_desk.lbl_dispenser', [], $l) }}</dt><dd>{{ $dispenser->name }}</dd>
                    @endif
                    @if($patient)
                    <dt>{{ __('pharmacy_desk.lbl_name', [], $l) }}</dt><dd>{{ $patientName !== '' ? $patientName : '—' }}</dd>
                    <dt>{{ __('pharmacy_desk.lbl_health_id', [], $l) }}</dt><dd class="odp-mono">{{ $patient->health_id ?? '—' }}</dd>
                    <dt>{{ __('pharmacy_desk.lbl_age_band', [], $l) }}</dt><dd>{{ $ageBand ? __('pharmacy_desk.age_bands.' . $ageBand, [], $l) : __('pharmacy_desk.not_recorded', [], $l) }}</dd>
                    <dt>{{ __('pharmacy_desk.lbl_sex', [], $l) }}</dt><dd>{{ filled($patient->sex) ? \App\Support\Enums::label((string) $patient->sex) : __('pharmacy_desk.not_recorded', [], $l) }}</dd>
                    @endif
                </dl>
            </div>
        </section>

        <section class="od-card" id="notes">
            <header class="od-card__head"><h2 class="od-card__title">{{ __('pharmacy_desk.lbl_notes', [], $l) }}</h2></header>
            <div class="od-card__body">
                @if(filled($rx->notes))<p>{{ $rx->notes }}</p>@else<p class="od-muted">{{ __('pharmacy_desk.od_notes_none', [], $l) }}</p>@endif
            </div>
        </section>

        <section class="od-card">
            <header class="od-card__head"><h2 class="od-card__title">{{ __('pharmacy_desk.sec_timeline', [], $l) }}</h2></header>
            <div class="od-card__body">
                <ul class="odp-timeline">
                    @foreach($timeline as $step)
                    <li class="{{ $step['done'] ? 'is-done' : 'is-pending' }}">
                        <span class="odp-timeline__dot"></span>
                        <div>
                            <span class="od-list__title">{{ __('pharmacy_desk.step.' . $step['key'], [], $l) }}</span>
                            <span class="od-list__sub">{{ $step['done'] ? ($step['at']?->translatedFormat('d M Y H:i') ?? '—') : __('pharmacy_desk.step_pending', [], $l) }}</span>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </section>
    </div>

</div>

@endsection
