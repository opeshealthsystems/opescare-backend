@extends('layouts.portal')

@php $l = app()->getLocale(); @endphp

@section('title', __('prescriber.counter_title', [], $l))

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('prescriber.counter_title', [], $l) }}</h1>
        <p class="page-subtitle">{{ __('prescriber.counter_sub', [], $l) }}</p>
    </div>
    <a href="{{ route('portals.pharmacy.prescriptions') }}" class="btn btn-secondary">
        <i data-lucide="list"></i>
        <span>{{ __('prescriber.title', [], $l) }}</span>
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

{{--
    One box, two kinds of handle. Whichever the patient is carrying — a phone
    showing the Health ID or a printed script with a number on it — goes in
    here; the controller works out which was typed. Exact match either way:
    patient names are never searchable and a reference resolves to one
    prescription or to none, never to a list.
--}}
<div class="panel">
    <div class="panel-body">
        <form method="GET" action="{{ route('portals.pharmacy.prescriptions.lookup') }}" class="form-row">
            <div class="form-group" style="flex:1 1 300px">
                <label class="form-label" for="q">{{ __('prescriber.counter_query', [], $l) }}</label>
                <input type="text" id="q" name="q" class="form-control"
                       value="{{ $query }}" autocomplete="off" autocapitalize="characters"
                       spellcheck="false" required
                       placeholder="CM-HID-XXXX-XXXX-XXXX  /  RX-XXXX-XXXX">
                <p class="form-hint">{{ __('prescriber.counter_query_hint', [], $l) }}</p>
            </div>
            <div class="form-group" style="align-self:flex-start">
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
    <div class="alert alert-danger">
        <i data-lucide="file-question"></i>
        <div>{{ __('prescriber.counter_norx', ['reference' => $reference], $l) }}</div>
    </div>
@elseif($searched && ! $patient)
    <div class="alert alert-danger">
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
                <i data-lucide="user"></i>
                {{ $patientName !== '' ? $patientName : (__('prescriber.counter_nopatrow', [], $l)) }}
            </div>
            <span class="badge badge-neutral mono">{{ $patient?->health_id ?? '—' }}</span>
        </div>

        @if($results->isEmpty())
            <div class="panel-body">
                <div class="empty-state">
                    <i data-lucide="file-x" style="width:32px;height:32px"></i>
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

                <div class="panel-body" style="border-top:1px solid var(--p-border,#e5e7eb)">
                    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start">
                        <div style="min-width:0">
                            <div style="font-weight:600;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                                <span class="mono">{{ $rx->reference ?: '—' }}</span>
                                <span class="badge {{ $rxBadge }} badge-sm">{{ $label($rxStatus) }}</span>
                                {{-- A script can sit at 'active' and still be long lapsed; say so next to the status. --}}
                                @if($expired && ! $terminal && $rxStatus !== 'expired')
                                    <span class="badge badge-danger badge-sm">{{ __('prescriber.st_expired', [], $l) }}</span>
                                @endif
                            </div>

                            <table class="kv-table" style="margin-top:8px">
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
                                <div class="td-muted" style="font-size:.85rem;margin-top:8px">{{ $rx->notes }}</div>
                            @endif
                        </div>

                        @if($canDispense)
                            <form method="POST" action="{{ route('portals.pharmacy.dispense', $rx->id) }}"
                                  style="display:flex;gap:8px;align-items:flex-start;flex-wrap:wrap">
                                @csrf
                                @php $owed = $rx->items->filter(fn ($it) => $it->isOutstanding()); @endphp
                                @if($owed->count() > 1)
                                    <fieldset style="flex-basis:100%;border:0;padding:0;margin:0">
                                        <legend class="form-hint">{{ __('pharmacy_ops.dispense_lines_hint') }}</legend>
                                        @foreach($owed as $it)
                                            <label style="display:inline-flex;gap:6px;align-items:center;margin-right:12px;min-height:44px">
                                                <input type="checkbox" name="item_ids[]" value="{{ $it->id }}">
                                                <span>{{ $it->drug_name ?: '—' }}</span>
                                            </label>
                                        @endforeach
                                    </fieldset>
                                @endif
                                <input type="text" name="dispense_notes" class="form-control" style="min-width:180px"
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
                        <div class="alert {{ $rxStatus === 'dispensed' ? 'alert-warning' : 'alert-danger' }}" style="margin-top:10px">
                            <i data-lucide="{{ $rxStatus === 'dispensed' ? 'check-circle' : 'shield-alert' }}"></i>
                            <div>
                                <strong>{{ $rxStatus === 'dispensed'
                                    ? (__('prescriber.counter_blocked_title_done', [], $l))
                                    : (__('prescriber.counter_blocked_title', [], $l)) }}</strong>
                                <div>{{ $refusal }}</div>
                                @if(filled($rx->void_reason))
                                    <div class="td-muted" style="font-size:.85rem">
                                        {{ __('prescriber.void_reason', [], $l) }}: {{ $rx->void_reason }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="table-wrapper" style="margin-top:10px">
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
                                                <div class="td-muted" style="font-size:.8rem">{{ $item->dispensed_at->translatedFormat('d M Y H:i') }}</div>
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
