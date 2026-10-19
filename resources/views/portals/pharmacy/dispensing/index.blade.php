@extends('layouts.portal')
@php
    $l = app()->getLocale();
    $initials = function ($first, $last): string {
        $s = mb_strtoupper(mb_substr(trim((string) $first), 0, 1) . mb_substr(trim((string) $last), 0, 1));
        return $s !== '' ? $s : '—';
    };
    $tone = fn (string $key): string => ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($key) % 6];
@endphp
@section('title', __('pharmacy_desk.reg_title', [], $l))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label', [], $l))

@section('head')
    <link rel="stylesheet" href="{{ asset('css/od-pharmacy.css') }}?v={{ @filemtime(public_path('css/od-pharmacy.css')) ?: '1' }}">
@endsection

@section('content')

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('pharmacy_desk.reg_title', [], $l) }}</h1>
        <p class="od-page__sub">{{ __('pharmacy_desk.reg_subtitle', [], $l) }}</p>
    </div>
    <a href="{{ route('portals.pharmacy.dispensing.export', request()->query()) }}" class="od-btn od-btn--secondary"><i data-lucide="download"></i> {{ __('pharmacy_desk.btn_export', [], $l) }}</a>
</div>

<section class="od-card odp-queue">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_desk.reg_count', ['count' => $entries->total()], $l) }}</h2>
    </header>

    <div class="od-card__body odp-queue__body">
        <form method="GET" class="odp-filters odp-toolbar">
            <label>
                <span>{{ __('pharmacy_desk.f_from', [], $l) }}</span>
                <input type="date" name="from" class="od-input" value="{{ $filters['from'] }}">
            </label>
            <label>
                <span>{{ __('pharmacy_desk.f_to', [], $l) }}</span>
                <input type="date" name="to" class="od-input" value="{{ $filters['to'] }}">
            </label>
            <label>
                <span>{{ __('pharmacy_desk.f_pharmacist', [], $l) }}</span>
                <select name="pharmacist" class="od-select" data-autosubmit>
                    <option value="">{{ __('pharmacy_desk.f_any_pharmacist', [], $l) }}</option>
                    @foreach($dispensers as $u)
                    <option value="{{ $u->id }}" {{ $filters['pharmacist'] === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </label>
            <span class="odp-check"><input type="checkbox" class="od-check" id="f-controlled" name="controlled" value="1" {{ $filters['controlled'] ? 'checked' : '' }} data-autosubmit> <label for="f-controlled">{{ __('pharmacy_desk.f_controlled', [], $l) }}</label></span>
            <button type="submit" class="od-btn od-btn--secondary"><i data-lucide="filter"></i> {{ __('pharmacy_desk.btn_filter', [], $l) }}</button>
            @if(request()->hasAny(['from', 'to', 'pharmacist', 'controlled']))
                <a href="{{ route('portals.pharmacy.dispensing') }}" class="od-btn od-btn--ghost">{{ __('pharmacy_desk.btn_clear', [], $l) }}</a>
            @endif
        </form>

        @if($entries->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><x-od-icon name="capsule" /></span>
                <div class="od-empty__title">{{ __('pharmacy_desk.reg_empty', [], $l) }}</div>
                <p class="od-empty__text">{{ __('pharmacy_desk.reg_empty_hint', [], $l) }}</p>
                <a href="{{ route('portals.pharmacy.prescriptions.lookup') }}" class="od-btn od-btn--primary"><x-od-icon name="barcode" /> {{ __('pharmacy_desk.btn_lookup', [], $l) }}</a>
            </div>
        @else
        <div class="od-table-wrap">
            <table class="od-table odp-stack-sm">
                <thead><tr>
                    <th class="od-table__num">#</th>
                    <th>{{ __('pharmacy_desk.col_date', [], $l) }}</th>
                    <th>{{ __('pharmacy_desk.col_patient', [], $l) }}</th>
                    <th>{{ __('pharmacy_desk.col_items', [], $l) }}</th>
                    <th>{{ __('pharmacy_desk.col_pharmacist', [], $l) }}</th>
                    <th>{{ __('pharmacy_desk.col_controlled', [], $l) }}</th>
                    <th></th>
                </tr></thead>
                <tbody>
                @foreach($entries as $i => $rx)
                @php
                    $done = $rx->items->filter(fn ($it) => $it->isDispensed());
                    $controlled = $done->contains(fn ($it) => $service->isControlledItem($it));
                    $dispenser = $rx->dispensed_by ? \App\Models\User::query()->find($rx->dispensed_by, ['id', 'name']) : null;
                    $url = route('portals.pharmacy.prescriptions.show', $rx->id);
                    $masked = \App\Services\Pharmacy\PharmacyDispensingService::maskName($rx->patient?->first_name, $rx->patient?->last_name);
                @endphp
                <tr>
                    <td class="od-table__num">{{ $entries->firstItem() + $i }}</td>
                    <td data-label="{{ __('pharmacy_desk.col_date', [], $l) }}" class="od-muted">{{ ($rx->dispensed_at ?? $done->max('dispensed_at'))?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                    <td data-label="{{ __('pharmacy_desk.col_patient', [], $l) }}">
                        <div class="od-table__person">
                            <span class="od-avatar od-tint--{{ $tone((string) $rx->id) }}" aria-hidden="true">{{ $initials($rx->patient?->first_name, $rx->patient?->last_name) }}</span>
                            <div>
                                <span class="od-table__name">{{ $masked }}</span>
                                <span class="od-table__sub"><a href="{{ $url }}">{{ $rx->reference ?: '—' }}</a> @if($rx->patient?->health_id)&middot; {{ $rx->patient->health_id }}@endif &middot; {{ __('pharmacy_desk.items_count', ['count' => $done->count()], $l) }}</span>
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_desk.col_items', [], $l) }}">
                        <div class="odp-lines">
                        @foreach($done->take(2) as $it)<span>{{ $it->drug_name }} {{ $it->dose }}</span>@endforeach
                        @if($done->count() > 2)<span class="od-table__sub">{{ __('pharmacy_desk.more_items', ['count' => $done->count() - 2], $l) }}</span>@endif
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_desk.col_pharmacist', [], $l) }}">{{ $dispenser?->name ?? '—' }}</td>
                    <td data-label="{{ __('pharmacy_desk.col_controlled', [], $l) }}">
                        @if($controlled)<span class="od-pill od-pill--urgent">{{ __('pharmacy_desk.badge_controlled', [], $l) }}</span>@else<span class="od-muted">—</span>@endif
                    </td>
                    <td class="od-table__actions" data-label=""><a href="{{ $url }}" class="od-btn od-btn--row">{{ __('pharmacy_desk.btn_open_rx', [], $l) }}</a></td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @if($entries->hasPages())<footer class="od-card__foot">{{ $entries->links() }}</footer>@endif
</section>

@endsection
