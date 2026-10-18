@extends('layouts.portal')

@php $l = app()->getLocale(); @endphp

@section('title', __('prescriber.counter_title', [], $l))

@section('head')
    <link rel="stylesheet" href="{{ asset('css/od-pharmacy.css') }}?v={{ @filemtime(public_path('css/od-pharmacy.css')) ?: '1' }}">
@endsection

@section('content')
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('prescriber.counter_title', [], $l) }}</h1>
        <p class="od-page__sub">{{ __('prescriber.counter_sub', [], $l) }}</p>
    </div>
    <a href="{{ route('portals.pharmacy.prescriptions') }}" class="od-btn od-btn--secondary">
        <x-od-icon name="rx" />
        <span>{{ __('prescriber.title', [], $l) }}</span>
    </a>
</div>

@if(session('success'))
    <div class="odp-notice od-tint--green"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="odp-notice od-tint--red"><i data-lucide="alert-triangle"></i><div>{{ session('error') }}</div></div>
@endif

{{--
    One box, two kinds of handle. Whichever the patient is carrying — a phone
    showing the Health ID or a printed script with a number on it — goes in
    here; the controller works out which was typed. Exact match either way:
    patient names are never searchable and a reference resolves to one
    prescription or to none, never to a list.
--}}
<section class="od-card">
    <header class="od-card__head"><h2 class="od-card__title">{{ __('prescriber.counter_query', [], $l) }}</h2></header>
    <div class="od-card__body">
        <form method="GET" action="{{ route('portals.pharmacy.prescriptions.lookup') }}" class="odp-toolbar">
            <label class="od-field" for="q">
                <x-od-icon name="barcode" />
                <input type="text" id="q" name="q" class="od-input" aria-label="{{ __('prescriber.counter_query', [], $l) }}"
                       value="{{ $query }}" autocomplete="off" autocapitalize="characters"
                       spellcheck="false" required
                       placeholder="CM-HID-XXXX-XXXX-XXXX  /  RX-XXXX-XXXX">
            </label>
            <button type="submit" class="od-btn od-btn--primary">
                <i data-lucide="search"></i>
                <span>{{ __('prescriber.counter_lookup', [], $l) }}</span>
            </button>
        </form>
        <p class="odp-hint">{{ __('prescriber.counter_query_hint', [], $l) }}</p>
    </div>
</section>

{{-- Nothing found. The two paths fail differently, so they say different things. --}}
@if($searched && $results->isEmpty() && $mode === 'reference')
    <div class="odp-notice od-tint--red">
        <i data-lucide="file-question"></i>
        <div>{{ __('prescriber.counter_norx', ['reference' => $reference], $l) }}</div>
    </div>
@elseif($searched && ! $patient)
    <div class="odp-notice od-tint--red">
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

    <section class="od-card">
        <header class="od-card__head">
            <div class="od-table__person">
                <span class="od-avatar od-tint--blue" aria-hidden="true">{{ $patientName !== '' ? mb_strtoupper(mb_substr((string) ($patient->first_name ?? ''), 0, 1) . mb_substr((string) ($patient->last_name ?? ''), 0, 1)) : '—' }}</span>
                <div>
                    <h2 class="od-card__title">{{ $patientName !== '' ? $patientName : (__('prescriber.counter_nopatrow', [], $l)) }} <span class="od-card__count">{{ __('pharmacy_desk.count_paren', ['count' => $results->count()], $l) }}</span></h2>
                    <span class="od-table__sub">{{ $patient?->health_id ?? '—' }}</span>
                </div>
            </div>
        </header>

        @if($results->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="file-x"></i></span>
                <p class="od-empty__text">{{ __('prescriber.counter_none', [], $l) }}</p>
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
                        'active'              => 'od-pill--pending',
                        'dispensed'           => 'od-pill--ready',
                        'partially_dispensed' => 'od-pill--verifying',
                        'voided',
                        'entered_in_error',
                        'cancelled'           => 'od-pill--urgent',
                        'amended'             => 'od-pill--reorder',
                        default               => 'od-pill--neutral',
                    };

                    $prescriberName = trim((string) ($rx->prescriber->name ?? ''));
                @endphp

                <div class="odp-rx-block">
                    <div class="od-grid od-grid--split">
                        <div class="od-stack">
                            <div class="od-row">
                                <a href="{{ route('portals.pharmacy.prescriptions.show', $rx->id) }}" class="od-card__title">{{ $rx->reference ?: '—' }}</a>
                                <span class="od-pill {{ $rxBadge }}">{{ $label($rxStatus) }}</span>
                                <a href="{{ route('portals.pharmacy.prescriptions.show', $rx->id) }}" class="od-btn od-btn--sm {{ $canDispense ? 'od-btn--secondary' : 'od-btn--primary' }}">{{ __('pharmacy_desk.btn_open_rx', [], $l) }}</a>
                                {{-- A script can sit at 'active' and still be long lapsed; say so next to the status. --}}
                                @if($expired && ! $terminal && $rxStatus !== 'expired')
                                    <span class="od-pill od-pill--urgent">{{ __('prescriber.st_expired', [], $l) }}</span>
                                @endif
                            </div>

                            <dl class="odp-dl">
                                <dt>{{ __('prescriber.counter_from', [], $l) }}</dt><dd>{{ $rx->facility->name ?? '—' }}</dd>
                                <dt>{{ __('prescriber.col_prescriber', [], $l) }}</dt><dd>{{ $prescriberName !== '' ? $prescriberName : '—' }}</dd>
                                <dt>{{ __('prescriber.issued_at', [], $l) }}</dt><dd>{{ $rx->prescribed_at?->translatedFormat('d M Y H:i') ?? '—' }}</dd>
                                <dt>{{ __('prescriber.expires_at', [], $l) }}</dt><dd>
                                        @if($rx->expires_at)
                                            @if($expired)
                                                <span class="od-pill od-pill--urgent">{{ $rx->expires_at->translatedFormat('d M Y') }}</span>
                                            @else
                                                {{ $rx->expires_at->translatedFormat('d M Y') }}
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </dd>
                                @if($rx->dispensed_at)
                                <dt>{{ __('prescriber.dispensed_at', [], $l) }}</dt><dd>{{ $rx->dispensed_at->translatedFormat('d M Y H:i') }}</dd>
                                @endif
                            </dl>

                            @if(filled($rx->notes))
                                <p class="od-muted">{{ $rx->notes }}</p>
                            @endif
                        </div>

                        @if($canDispense)
                            <form method="POST" action="{{ route('portals.pharmacy.dispense', $rx->id) }}" class="od-stack">
                                @csrf
                                @php $owed = $rx->items->filter(fn ($it) => $it->isOutstanding()); @endphp
                                @if($owed->count() > 1)
                                    <fieldset>
                                        <legend class="odp-hint">{{ __('pharmacy_ops.dispense_lines_hint') }}</legend>
                                        @foreach($owed as $it)
                                            <label class="odp-check">
                                                <input type="checkbox" class="od-check" name="item_ids[]" value="{{ $it->id }}">
                                                <span>{{ $it->drug_name ?: '—' }}</span>
                                            </label>
                                        @endforeach
                                    </fieldset>
                                @endif
                                <input type="text" name="dispense_notes" class="od-input"
                                       placeholder="{{ __('prescriber.counter_notes', [], $l) }}">
                                <button type="submit" class="od-btn od-btn--primary od-btn--block">
                                    <x-od-icon name="capsule" />
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
                        <div class="odp-notice {{ $rxStatus === 'dispensed' ? 'od-tint--amber' : 'od-tint--red' }}">
                            <i data-lucide="{{ $rxStatus === 'dispensed' ? 'check-circle' : 'shield-alert' }}"></i>
                            <div>
                                <strong>{{ $rxStatus === 'dispensed'
                                    ? (__('prescriber.counter_blocked_title_done', [], $l))
                                    : (__('prescriber.counter_blocked_title', [], $l)) }}</strong>
                                <div>{{ $refusal }}</div>
                                @if(filled($rx->void_reason))
                                    <div>
                                        {{ __('prescriber.void_reason', [], $l) }}: {{ $rx->void_reason }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="od-table-wrap">
                        <table class="od-table odp-stack-sm">
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
                                            'dispensed' => 'od-pill--ready',
                                            'pending'   => 'od-pill--pending',
                                            default     => 'od-pill--neutral',
                                        };
                                    @endphp
                                    <tr>
                                        <td data-label="{{ __('prescriber.drug_name', [], $l) }}" class="od-strong">{{ $item->drug_name ?: '—' }}</td>
                                        <td data-label="{{ __('prescriber.dose', [], $l) }}">{{ $item->dose ?: '—' }}</td>
                                        <td data-label="{{ __('prescriber.frequency', [], $l) }}">{{ $item->frequency ?: '—' }}</td>
                                        <td data-label="{{ __('prescriber.route', [], $l) }}">{{ $item->route ? \App\Support\Enums::label($item->route, 'route') : '—' }}</td>
                                        <td data-label="{{ __('prescriber.quantity', [], $l) }}">{{ $item->quantity !== null && $item->quantity !== '' ? $item->quantity : '—' }}</td>
                                        <td data-label="{{ __('prescriber.item_status', [], $l) }}">
                                            <span class="od-pill {{ $itemBadge }}">{{ $label($itemStatus) }}</span>
                                            @if($item->dispensed_at)
                                                <div class="od-table__sub">{{ $item->dispensed_at->translatedFormat('d M Y H:i') }}</div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="od-muted">—</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif
    </section>
@endif
@endsection
