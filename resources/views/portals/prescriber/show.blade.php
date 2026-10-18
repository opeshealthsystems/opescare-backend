@extends('layouts.portal')
@section('title', __('prescriber.detail_title'))
@section('breadcrumb_home', __('prescriber.title'))
@section('breadcrumb_home_url', route('portals.prescriber.index'))
@section('breadcrumb_section', __('prescriber.detail_title'))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')

@php
    /**
     * Everything the view needs, resolved once and defensively: the controller
     * eager-loads patient + items, but a patient row can be missing (merged,
     * or a record written before identity reconciliation) and a prescription
     * can legitimately carry zero readable lines.
     */
    $rx      = $prescription;
    $patient = $rx->patient;

    $patientName = $patient
        ? trim(((string) ($patient->first_name ?? '')) . ' ' . ((string) ($patient->last_name ?? '')))
        : '';
    $initials = $patient
        ? mb_strtoupper(mb_substr(trim((string) ($patient->first_name ?? '')), 0, 1) . mb_substr(trim((string) ($patient->last_name ?? '')), 0, 1))
        : '';
    $tone = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($patientName !== '' ? $patientName : (string) $rx->id) % 6];
    // Never print a date of birth: an age band only.
    $ageBand = $patient?->date_of_birth ? \App\Support\ResultFlagResolver::ageBand($patient->date_of_birth) : null;

    $rxStatus = (string) ($rx->status ?? '');

    // Pill colour by prescription state. Terminal-but-not-dispensed reads as grey.
    $rxPill = match ($rxStatus) {
        'active'              => 'pending',
        'dispensed'           => 'ready',
        'partially_dispensed' => 'verifying',
        default               => 'neutral',
    };

    /**
     * Status label from the prescriber namespace, falling back to the shared
     * enum labeller for values that have no prescriber.st_* key of their own.
     */
    $label = function (?string $value): string {
        $value = (string) $value;
        if ($value === '') {
            return '—';
        }
        $key  = 'prescriber.st_' . $value;
        $text = __($key);

        return $text !== $key ? $text : \App\Support\Enums::label($value);
    };

    // Void is a correction, not a reversal: once dispensed or closed there is nothing to void.
    $canVoid = ! in_array($rxStatus, ['dispensed', 'voided', 'cancelled'], true);

    $items = $rx->items ?? collect();
@endphp

<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('prescriber.detail_title') }}</h1>
        <p class="od-page__sub">@if(filled($rx->reference)){{ __('prescriber.rx_code') }}: <strong data-rx-code title="{{ __('prescriber.rx_code_hint') }}">{{ $rx->reference }}</strong>@endif</p>
    </div>
    <div class="odc-head-actions">
        <a href="{{ route('portals.prescriber.index') }}" class="od-btn od-btn--secondary"><i data-lucide="arrow-left"></i> {{ __('prescriber.back') }}</a>
        {{-- The paper the patient walks to the pharmacy with. Opens in its own tab. --}}
        <a href="{{ route('portals.prescriber.print', $rx->id) }}" class="od-btn od-btn--primary" target="_blank" rel="noopener"><i data-lucide="printer"></i> {{ __('prescriber.print_action') }}</a>
    </div>
</div>

@if(session('success'))<div class="odc-note odc-note--ok" role="status"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('warning'))<div class="odc-note odc-note--warn" role="status"><i data-lucide="triangle-alert"></i><div>{{ session('warning') }}</div></div>@endif
@if(session('error'))<div class="odc-note odc-note--err" role="alert"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="od-grid od-grid--main">

<div class="od-stack">
{{-- The lines themselves, each with its own dispense state --}}
<section class="od-card">
    <header class="od-card__head"><h2 class="od-card__title">{{ __('prescriber.medicines') }} <span class="od-card__count">({{ $items->count() }})</span></h2></header>
    <div class="od-card__body">
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th class="od-table__num">#</th>
                    <th>{{ __('prescriber.drug_name') }}</th>
                    <th>{{ __('prescriber.dose') }}</th>
                    <th>{{ __('prescriber.frequency') }}</th>
                    <th>{{ __('prescriber.duration_days') }}</th>
                    <th>{{ __('prescriber.quantity') }}</th>
                    <th>{{ __('prescriber.item_status') }}</th>
                </tr></thead>
                <tbody>
                @forelse($items as $item)
                    @php
                        $itemStatus = (string) ($item->status ?? '');
                        $itemPill   = match ($itemStatus) {
                            'dispensed' => 'ready',
                            'pending'   => 'pending',
                            default     => 'neutral',
                        };
                    @endphp
                    <tr>
                        <td class="od-table__num">{{ $loop->iteration }}</td>
                        <td>
                            <span class="od-table__name">{{ $item->drug_name ?? '—' }}</span>
                            <span class="od-table__sub">{{ $item->route ? \App\Support\Enums::label($item->route, 'route') : '—' }}@if(filled($item->dispense_notes)) · {{ $item->dispense_notes }}@endif</span>
                        </td>
                        <td>{{ $item->dose ?: '—' }}</td>
                        <td>{{ $item->frequency ?: '—' }}</td>
                        <td>{{ $item->duration_days !== null ? $item->duration_days : '—' }}</td>
                        <td>{{ $item->quantity !== null ? $item->quantity : '—' }}</td>
                        <td>
                            <span class="od-pill od-pill--{{ $itemPill }}">{{ $label($itemStatus) }}</span>
                            @if($item->dispensed_at)<span class="od-table__sub">{{ $item->dispensed_at->translatedFormat('d M Y H:i') }}</span>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="od-muted">—</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if(filled($rx->notes))
        <div class="odc-section">
            <h3>{{ __('prescriber.notes') }}</h3>
            <p>{{ $rx->notes }}</p>
        </div>
        @endif
    </div>
</section>

@if($canVoid)
{{-- Voiding keeps the clinical event and records why; it never deletes it. --}}
<section class="od-card">
    <header class="od-card__head"><h2 class="od-card__title">{{ __('prescriber.void') }}</h2></header>
    <div class="od-card__body">
        <form method="POST" action="{{ route('portals.prescriber.void', $rx->id) }}" class="odc-form">
            @csrf
            <div class="odc-field">
                <label class="odc-label odc-label--req" for="void_reason">{{ __('prescriber.void_reason') }}</label>
                <input type="text" name="reason" id="void_reason" class="od-input" maxlength="500" value="{{ old('reason') }}" required>
                @error('reason')<p class="odc-hint">{{ $message }}</p>@enderror
            </div>
            <div class="odc-actions">
                <button type="submit" class="od-btn od-btn--danger" data-confirm="{{ Illuminate\Support\Js::from(__('prescriber.void_confirm')) }}"><i data-lucide="ban"></i> {{ __('prescriber.void') }}</button>
            </div>
        </form>
    </div>
</section>
@endif
</div>

{{-- Right-hand detail panel: who it was written for, and when --}}
<section class="od-card odc-aside">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('prescriber.patient') }}</h2>
        <span class="od-pill od-pill--dot od-pill--{{ $rxPill }}">{{ $label($rxStatus) }}</span>
    </header>
    <div class="od-card__body od-detail">
        <div class="od-detail__head">
            <span class="od-avatar od-avatar--lg od-tint--{{ $tone }}" aria-hidden="true">{{ $initials !== '' ? $initials : '—' }}</span>
            <div class="od-detail__main">
                <h3 class="od-detail__name">{{ $patientName !== '' ? $patientName : '—' }}</h3>
                <div class="od-detail__ref">
                    <span>{{ $rx->reference ?: '—' }}</span>
                    @if($rx->prescribed_at)<span>{{ $rx->prescribed_at->translatedFormat('d M Y') }}</span><span>{{ $rx->prescribed_at->format('H:i') }}</span>@endif
                </div>
                @if($patient)
                <div class="od-detail__facts">
                    <span class="od-detail__fact"><i data-lucide="calendar"></i> {{ $ageBand ? __('pharmacy_desk.age_bands.' . $ageBand) : __('pharmacy_desk.not_recorded') }}</span>
                    <span class="od-detail__fact"><i data-lucide="user"></i> @if($patient->sex)@enum($patient->sex, 'sex')@else{{ __('pharmacy_desk.not_recorded') }}@endif</span>
                    <span class="od-detail__fact"><i data-lucide="id-card"></i> {{ $patient->health_id ?? '—' }}</span>
                </div>
                @endif
            </div>
        </div>

        <div class="od-detail__people">
            <div class="od-person">
                <span class="od-person__icon"><i data-lucide="clock"></i></span>
                <div>
                    <span class="od-person__label">{{ __('prescriber.issued_at') }}</span>
                    <span class="od-person__name">{{ $rx->prescribed_at?->translatedFormat('d M Y H:i') ?? '—' }}</span>
                    @if($rx->dispensed_at)<span class="od-person__sub">{{ __('prescriber.dispensed_at') }}: {{ $rx->dispensed_at->translatedFormat('d M Y H:i') }}</span>@endif
                </div>
            </div>
            <div class="od-person">
                <span class="od-person__icon"><i data-lucide="calendar-x"></i></span>
                <div>
                    <span class="od-person__label">{{ __('prescriber.expires_at') }}</span>
                    <span class="od-person__name">
                        @if($rx->expires_at)
                            @if($rx->expires_at->isPast())<span class="od-pill od-pill--low">{{ $rx->expires_at->translatedFormat('d M Y') }}</span>@else{{ $rx->expires_at->translatedFormat('d M Y') }}@endif
                        @else — @endif
                    </span>
                </div>
            </div>
        </div>
        <a href="{{ route('portals.prescriber.print', $rx->id) }}" class="od-btn od-btn--primary od-btn--block" target="_blank" rel="noopener"><i data-lucide="printer"></i> {{ __('prescriber.print_action') }}</a>
    </div>
</section>

</div>
</div>
@endsection
