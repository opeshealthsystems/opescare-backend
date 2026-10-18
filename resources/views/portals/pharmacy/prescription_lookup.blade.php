@extends('layouts.portal')

@php $l = app()->getLocale(); @endphp

@section('title', __('prescriber.counter_title', [], $l))

@section('content')
<div class="page-head">
    <h2>{{ __('prescriber.counter_title', [], $l) }}</h2>
    <p class="page-subtitle">{{ __('prescriber.counter_sub', [], $l) }}</p>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.pharmacy.prescriptions') }}" class="btn btn-secondary btn-sm">
        <i data-lucide="list"></i>
        <span>{{ __('prescriber.title', [], $l) }}</span>
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-6"><i data-lucide="alert-triangle"></i><div>{{ session('error') }}</div></div>
@endif

{{--
    One box, two kinds of handle. Whichever the patient is carrying — a phone
    showing the Health ID or a printed script with a number on it — goes in
    here; the controller works out which was typed. Exact match either way:
    patient names are never searchable and a reference resolves to one
    prescription or to none, never to a list.
--}}
<div class="panel mb-6">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="scan-line"></i> {{ __('prescriber.counter_title', [], $l) }}</h3></div>
    <div class="panel-body">
        <form method="GET" action="{{ route('portals.pharmacy.prescriptions.lookup') }}" class="ph-lookup">
            <div class="form-group">
                <label class="form-label" for="q">{{ __('prescriber.counter_query', [], $l) }}</label>
                <input type="text" id="q" name="q" class="form-control"
                       value="{{ $query }}" autocomplete="off" autocapitalize="characters"
                       spellcheck="false" required
                       placeholder="CM-HID-XXXX-XXXX-XXXX  /  RX-XXXX-XXXX">
                <p class="form-hint">{{ __('prescriber.counter_query_hint', [], $l) }}</p>
            </div>
            <div class="form-group ph-lookup__go">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="search"></i>
                    <span>{{ __('prescriber.counter_lookup', [], $l) }}</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Nothing found. The two paths fail differently, so they say different things. --}}
@if($searched && $results->isEmpty() && $mode === 'reference')
    <div class="alert alert-danger mb-6">
        <i data-lucide="file-question"></i>
        <div>{{ __('prescriber.counter_norx', ['reference' => $reference], $l) }}</div>
    </div>
@elseif($searched && ! $patient)
    <div class="alert alert-danger mb-6">
        <i data-lucide="user-x"></i>
        <div>{{ __('prescriber.counter_nopat', [], $l) }}</div>
    </div>
@endif

@if($searched && ($patient || $results->isNotEmpty()))
    @php
        /**
         * Identity, resolved defensively. On the reference path the patient row
         * can legitimately come back null — merged away, or filtered out by the
         * demo-isolation scope — and the script still has to be readable.
         */
        $patientName = $patient
            ? trim(((string) ($patient->first_name ?? '')) . ' ' . ((string) ($patient->last_name ?? '')))
            : '';

        /**
         * Status label from the prescriber namespace, falling back to the shared
         * enum labeller for values that have no prescriber.st_* key of their own.
         */
        $label = function (?string $value) use ($l): string {
            $value = (string) $value;
            if ($value === '') {
                return '—';
            }
            $key  = 'prescriber.st_' . $value;
            $text = __($key, [], $l);

            return $text !== $key ? $text : \App\Support\Enums::label($value);
        };
    @endphp

    <div class="panel">
        <div class="panel-header">
            <div class="panel-title">
                <span class="ph-avatar" aria-hidden="true">{{ $patientName !== '' ? mb_strtoupper(mb_substr((string) ($patient->first_name ?? ''), 0, 1) . mb_substr((string) ($patient->last_name ?? ''), 0, 1)) : '—' }}</span>
                {{ $patientName !== '' ? $patientName : (__('prescriber.counter_nopatrow', [], $l)) }}
            </div>
            <span class="badge badge-neutral mono">{{ $patient?->health_id ?? '—' }}</span>
            <span class="badge badge-info">{{ __('pharmacy_desk.count_paren', ['count' => $results->count()], $l) }}</span>
        </div>

        @if($results->isEmpty())
            <div class="panel-body">
                <div class="empty-state">
                    <i data-lucide="file-x"></i>
                    <p>{{ __('prescriber.counter_none', [], $l) }}</p>
                </div>
            </div>
        @else
            @foreach($results as $rx)
                @php
                    $rxStatus = (string) ($rx->status ?? '');

                    /**
                     * Whether this script may still be filled — and, when it may
                     * not, why. `isDispensable()` is the model's own rule and the
                     * same one PrescriptionService re-checks on POST, so the
                     * button and the write path cannot disagree.
                     *
                     * Expiry is a date check independent of the stored status: a
                     * script can sit at 'active' and still be long lapsed.
                     */
                    $expired     = ($rx->expires_at && $rx->expires_at->isPast()) || $rxStatus === 'expired';
                    $terminal    = $rx->isTerminal();
                    $canDispense = $rx->isDispensable();

                    /**
                     * Driven off isDispensable() rather than off the status alone,
                     * so no refusable state can slip through un-explained: a stored
                     * 'expired' with no expiry date, or any status added later,
                     * still lands on a refusal instead of a silent missing button.
                     */
                    $refusal = match (true) {
                        $canDispense              => null,
                        $rxStatus === 'dispensed' => __('prescriber.counter_blocked_dispensed', [], $l),
                        $expired                  => __('prescriber.counter_blocked_expired', ['date' => $rx->expires_at?->translatedFormat('d M Y') ?? '—'], $l),
                        default                   => __('prescriber.counter_blocked_terminal', ['status' => $label($rxStatus)], $l),
                    };

                    $rxBadge = match ($rxStatus) {
                        'active'              => 'badge-info',
                        'dispensed'           => 'badge-success',
                        'partially_dispensed' => 'badge-warning',
                        'voided',
                        'entered_in_error',
                        'cancelled'           => 'badge-danger',
                        'amended'             => 'badge-warning',
                        default               => 'badge-neutral',
                    };

                    $prescriberName = trim((string) ($rx->prescriber->name ?? ''));
                @endphp

                <div class="panel-body ph-result">
                    <div class="ph-result__head">
                        <div class="ph-result__body">
                            <div class="ph-result__title">
                                <a href="{{ route('portals.pharmacy.prescriptions.show', $rx->id) }}" class="mono">{{ $rx->reference ?: '—' }}</a>
                                <span class="badge {{ $rxBadge }} badge-sm">{{ $label($rxStatus) }}</span>
                                <a href="{{ route('portals.pharmacy.prescriptions.show', $rx->id) }}" class="btn {{ $canDispense ? 'btn-ghost' : 'btn-primary' }} btn-sm"><i data-lucide="chevron-right"></i> {{ __('pharmacy_desk.btn_open_rx', [], $l) }}</a>
                                {{-- A script can sit at 'active' and still be long lapsed; say so next to the status. --}}
                                @if($expired && ! $terminal && $rxStatus !== 'expired')
                                    <span class="badge badge-danger badge-sm">{{ __('prescriber.st_expired', [], $l) }}</span>
                                @endif
                            </div>

                            <table class="kv-table">
                                <tr>
                                    <td>{{ __('prescriber.counter_from', [], $l) }}</td>
                                    <td class="kv-strong">{{ $rx->facility->name ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td>{{ __('prescriber.col_prescriber', [], $l) }}</td>
                                    <td class="kv-strong">{{ $prescriberName !== '' ? $prescriberName : '—' }}</td>
                                </tr>
                                <tr>
                                    <td>{{ __('prescriber.issued_at', [], $l) }}</td>
                                    <td class="kv-strong">{{ $rx->prescribed_at?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td>{{ __('prescriber.expires_at', [], $l) }}</td>
                                    <td class="kv-strong">
                                        @if($rx->expires_at)
                                            @if($expired)
                                                <span class="badge badge-danger badge-sm">{{ $rx->expires_at->translatedFormat('d M Y') }}</span>
                                            @else
                                                {{ $rx->expires_at->translatedFormat('d M Y') }}
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                                @if($rx->dispensed_at)
                                <tr>
                                    <td>{{ __('prescriber.dispensed_at', [], $l) }}</td>
                                    <td class="kv-strong">{{ $rx->dispensed_at->translatedFormat('d M Y H:i') }}</td>
                                </tr>
                                @endif
                            </table>

                            @if(filled($rx->notes))
                                <div class="ph-note">{{ $rx->notes }}</div>
                            @endif
                        </div>

                        @if($canDispense)
                            <form method="POST" action="{{ route('portals.pharmacy.dispense', $rx->id) }}" class="ph-result__form">
                                @csrf
                                @php $owed = $rx->items->filter(fn ($it) => $it->isOutstanding()); @endphp
                                @if($owed->count() > 1)
                                    <fieldset>
                                        <legend class="form-hint">{{ __('pharmacy_ops.dispense_lines_hint') }}</legend>
                                        @foreach($owed as $it)
                                            <label class="ph-check">
                                                <input type="checkbox" name="item_ids[]" value="{{ $it->id }}">
                                                <span>{{ $it->drug_name ?: '—' }}</span>
                                            </label>
                                        @endforeach
                                    </fieldset>
                                @endif
                                <input type="text" name="dispense_notes" class="form-control"
                                       placeholder="{{ __('prescriber.counter_notes', [], $l) }}">
                                <button type="submit" class="btn btn-primary">
                                    <i data-lucide="check"></i>
                                    <span>{{ __('prescriber.counter_dispense', [], $l) }}</span>
                                </button>
                            </form>
                        @endif
                    </div>

                    {{--
                        The refusal. A retracted or lapsed script must be
                        unmistakable at a glance and must offer nothing to click:
                        handing out medicine against a voided order is the one
                        mistake this screen exists to prevent.
                    --}}
                    @if($refusal)
                        <div class="alert {{ $rxStatus === 'dispensed' ? 'alert-warning' : 'alert-danger' }}">
                            <i data-lucide="{{ $rxStatus === 'dispensed' ? 'check-circle' : 'shield-alert' }}"></i>
                            <div>
                                <strong>{{ $rxStatus === 'dispensed'
                                    ? (__('prescriber.counter_blocked_title_done', [], $l))
                                    : (__('prescriber.counter_blocked_title', [], $l)) }}</strong>
                                <div>{{ $refusal }}</div>
                                @if(filled($rx->void_reason))
                                    <div class="ph-note">
                                        {{ __('prescriber.void_reason', [], $l) }}: {{ $rx->void_reason }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>{{ __('prescriber.drug_name', [], $l) }}</th>
                                    <th>{{ __('prescriber.dose', [], $l) }}</th>
                                    <th>{{ __('prescriber.frequency', [], $l) }}</th>
                                    <th>{{ __('prescriber.route', [], $l) }}</th>
                                    <th>{{ __('prescriber.quantity', [], $l) }}</th>
                                    <th>{{ __('prescriber.item_status', [], $l) }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rx->items as $item)
                                    @php
                                        $itemStatus = (string) ($item->status ?? '');
                                        $itemBadge  = match ($itemStatus) {
                                            'dispensed' => 'badge-success',
                                            'pending'   => 'badge-info',
                                            default     => 'badge-neutral',
                                        };
                                    @endphp
                                    <tr>
                                        <td data-label="{{ __('prescriber.drug_name', [], $l) }}" class="td-strong">{{ $item->drug_name ?: '—' }}</td>
                                        <td data-label="{{ __('prescriber.dose', [], $l) }}">{{ $item->dose ?: '—' }}</td>
                                        <td data-label="{{ __('prescriber.frequency', [], $l) }}">{{ $item->frequency ?: '—' }}</td>
                                        <td data-label="{{ __('prescriber.route', [], $l) }}">{{ $item->route ? \App\Support\Enums::label($item->route, 'route') : '—' }}</td>
                                        <td data-label="{{ __('prescriber.quantity', [], $l) }}">{{ $item->quantity !== null && $item->quantity !== '' ? $item->quantity : '—' }}</td>
                                        <td data-label="{{ __('prescriber.item_status', [], $l) }}">
                                            <span class="badge {{ $itemBadge }} badge-sm">{{ $label($itemStatus) }}</span>
                                            @if($item->dispensed_at)
                                                <div class="ph-cell__sub">{{ $item->dispensed_at->translatedFormat('d M Y H:i') }}</div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="td-muted empty-cell">—</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
@endif
@endsection
