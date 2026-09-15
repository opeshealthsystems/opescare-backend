@extends('layouts.portal')
@section('title', __('prescriber.detail_title'))
@section('breadcrumb_home', __('prescriber.title'))
@section('breadcrumb_home_url', route('portals.prescriber.index'))
@section('breadcrumb_section', __('prescriber.detail_title'))

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

    $rxStatus = (string) ($rx->status ?? '');

    // Pill colour by prescription state. Anything terminal-but-not-dispensed
    // (voided / cancelled / expired) reads as grey: it is history, not an alarm.
    $rxBadge = match ($rxStatus) {
        'active'              => 'badge-info',
        'dispensed'           => 'badge-success',
        'partially_dispensed' => 'badge-warning',
        default               => 'badge-neutral',
    };

    /**
     * Status label from the prescriber namespace, falling back to the shared
     * enum labeller for values that have no prescriber.st_* key of their own
     * (the model also allows entered_in_error / amended). No new keys needed.
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

    // Void is a correction, not a reversal: once a prescription is dispensed or
    // already closed there is nothing left to void.
    $canVoid = ! in_array($rxStatus, ['dispensed', 'voided', 'cancelled'], true);

    $items = $rx->items ?? collect();
@endphp

<div class="breadcrumb">
    <a href="{{ route('portals.prescriber.index') }}">{{ __('prescriber.title') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ __('prescriber.detail_title') }}</span>
</div>

<div class="entity-head">
    <div class="entity-head__icon"><i data-lucide="file-text"></i></div>
    <div>
        <h2 class="entity-head__title">{{ __('prescriber.detail_title') }}</h2>
        <div class="entity-head__sub">
            <span class="badge {{ $rxBadge }} badge-sm">{{ $label($rxStatus) }}</span>
            @if($rx->prescribed_at)
            <span class="td-muted text-sm">{{ $rx->prescribed_at->format('d M Y H:i') }}</span>
            @endif
            {{-- The code the pharmacy counter looks the script up by. --}}
            @if(filled($rx->reference))
            <span class="text-sm" title="{{ __('prescriber.rx_code_hint') }}">{{ __('prescriber.rx_code') }}: <strong class="kv-strong" data-rx-code>{{ $rx->reference }}</strong></span>
            @endif
        </div>
    </div>
    <div class="entity-head__spacer"></div>
    {{-- The paper the patient actually walks to the pharmacy with. Opens in its
         own tab so the desk keeps its place in the record behind the print sheet. --}}
    <a href="{{ route('portals.prescriber.print', $rx->id) }}" class="btn btn-primary" target="_blank" rel="noopener">
        <i data-lucide="printer"></i> {{ __('prescriber.print_action') }}
    </a>
    <a href="{{ route('portals.prescriber.index') }}" class="btn btn-secondary">
        <i data-lucide="arrow-left"></i> {{ __('prescriber.back') }}
    </a>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="grid-2">

    {{-- Who it was written for --}}
    <div class="panel mb-6">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="user"></i> {{ __('prescriber.patient') }}</h3></div>
        <div class="panel-body">
            <table class="kv-table">
                <tr>
                    <td>{{ __('prescriber.patient') }}</td>
                    <td class="kv-strong">
                        {{ $patientName !== '' ? $patientName : '—' }}
                        @if($patient && ($patient->date_of_birth || $patient->sex))
                        <div class="td-muted text-sm">@if($patient->date_of_birth){{ $patient->date_of_birth->format('d M Y') }}@endif @if($patient->date_of_birth && $patient->sex)&middot; @endif @if($patient->sex)@enum($patient->sex, 'sex')@endif</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>{{ __('prescriber.health_id') }}</td>
                    <td class="kv-strong mono">{{ $patient?->health_id ?? '—' }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- When it was issued, when it lapses, when it was filled --}}
    <div class="panel mb-6">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="clock"></i> {{ __('prescriber.status') }}</h3></div>
        <div class="panel-body">
            <table class="kv-table">
                <tr>
                    <td>{{ __('prescriber.status') }}</td>
                    <td class="kv-strong"><span class="badge {{ $rxBadge }} badge-sm">{{ $label($rxStatus) }}</span></td>
                </tr>
                <tr>
                    <td>{{ __('prescriber.issued_at') }}</td>
                    <td class="kv-strong">{{ $rx->prescribed_at?->format('d M Y H:i') ?? '—' }}</td>
                </tr>
                <tr>
                    <td>{{ __('prescriber.expires_at') }}</td>
                    <td class="kv-strong">
                        @if($rx->expires_at)
                            @if($rx->expires_at->isPast())
                            <span class="badge badge-danger badge-sm">{{ $rx->expires_at->format('d M Y') }}</span>
                            @else
                            {{ $rx->expires_at->format('d M Y') }}
                            @endif
                        @else
                            —
                        @endif
                    </td>
                </tr>
                @if($rx->dispensed_at)
                <tr>
                    <td>{{ __('prescriber.dispensed_at') }}</td>
                    <td class="kv-strong">{{ $rx->dispensed_at->format('d M Y H:i') }}</td>
                </tr>
                @endif
            </table>

            @if(filled($rx->notes))
            <div class="detail-divider">
                <div class="kv-strong mb-3">{{ __('prescriber.notes') }}</div>
                <div class="td-muted">{{ $rx->notes }}</div>
            </div>
            @endif
        </div>
    </div>

</div>

{{-- The lines themselves, each with its own dispense state --}}
<div class="panel mb-6">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="pill"></i> {{ __('prescriber.medicines') }}</h3></div>
    <div class="panel-body panel-body--flush">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('prescriber.drug_name') }}</th>
                        <th>{{ __('prescriber.dose') }}</th>
                        <th>{{ __('prescriber.frequency') }}</th>
                        <th>{{ __('prescriber.route') }}</th>
                        <th>{{ __('prescriber.duration_days') }}</th>
                        <th>{{ __('prescriber.quantity') }}</th>
                        <th>{{ __('prescriber.item_status') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($items as $item)
                    @php
                        $itemStatus = (string) ($item->status ?? '');
                        $itemBadge  = match ($itemStatus) {
                            'dispensed' => 'badge-success',
                            'pending'   => 'badge-info',
                            default     => 'badge-neutral',
                        };
                    @endphp
                    <tr>
                        <td data-label="{{ __('prescriber.drug_name') }}">
                            <span class="td-strong">{{ $item->drug_name ?? '—' }}</span>
                            @if(filled($item->dispense_notes))
                            <div class="td-muted text-sm">{{ $item->dispense_notes }}</div>
                            @endif
                        </td>
                        <td data-label="{{ __('prescriber.dose') }}">{{ $item->dose ?: '—' }}</td>
                        <td data-label="{{ __('prescriber.frequency') }}">{{ $item->frequency ?: '—' }}</td>
                        <td data-label="{{ __('prescriber.route') }}">{{ $item->route ? \App\Support\Enums::label($item->route, 'route') : '—' }}</td>
                        <td data-label="{{ __('prescriber.duration_days') }}">{{ $item->duration_days !== null ? $item->duration_days : '—' }}</td>
                        <td data-label="{{ __('prescriber.quantity') }}">{{ $item->quantity !== null ? $item->quantity : '—' }}</td>
                        <td data-label="{{ __('prescriber.item_status') }}">
                            <span class="badge {{ $itemBadge }} badge-sm">{{ $label($itemStatus) }}</span>
                            @if($item->dispensed_at)
                            <div class="td-muted text-sm">{{ $item->dispensed_at->format('d M Y H:i') }}</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="td-muted empty-cell">—</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($canVoid)
{{-- Voiding keeps the clinical event and records why; it never deletes it. --}}
<div class="panel">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="ban"></i> {{ __('prescriber.void') }}</h3></div>
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.prescriber.void', $rx->id) }}">
            @csrf
            <div class="form-group">
                <label class="form-label form-label-required" for="void_reason">{{ __('prescriber.void_reason') }}</label>
                <input type="text" name="reason" id="void_reason" class="form-control" maxlength="500" value="{{ old('reason') }}" required>
                @error('reason')<div class="form-hint">{{ $message }}</div>@enderror
            </div>
            <div class="mt-6">
                <button type="submit" class="btn btn-danger" onclick="return confirm({{ Illuminate\Support\Js::from(__('prescriber.void_confirm')) }})">
                    <i data-lucide="ban"></i> {{ __('prescriber.void') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection
