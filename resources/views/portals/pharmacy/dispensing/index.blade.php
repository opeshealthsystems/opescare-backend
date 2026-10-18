@extends('layouts.portal')
@php
    $l = app()->getLocale();
    $initials = function ($first, $last): string {
        $s = mb_strtoupper(mb_substr(trim((string) $first), 0, 1) . mb_substr(trim((string) $last), 0, 1));
        return $s !== '' ? $s : '—';
    };
@endphp
@section('title', __('pharmacy_desk.reg_title', [], $l))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label', [], $l))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home', [], $l))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_desk.reg_breadcrumb', [], $l))

@section('content')

<div class="page-head">
    <h2>{{ __('pharmacy_desk.reg_title', [], $l) }}</h2>
    <p class="page-subtitle">{{ __('pharmacy_desk.reg_subtitle', [], $l) }}</p>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.pharmacy.dispensing.export', request()->query()) }}" class="btn btn-secondary btn-sm"><i data-lucide="download"></i> {{ __('pharmacy_desk.btn_export', [], $l) }}</a>
</div>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="clipboard-check"></i> {{ __('pharmacy_desk.reg_count', ['count' => $entries->total()], $l) }}</h3>
    </div>

    <div class="ph-tools">
        <form method="GET" class="ph-toolbar">
            <label class="form-group">
                <span class="form-label">{{ __('pharmacy_desk.f_from', [], $l) }}</span>
                <input type="date" name="from" class="form-control" value="{{ $filters['from'] }}">
            </label>
            <label class="form-group">
                <span class="form-label">{{ __('pharmacy_desk.f_to', [], $l) }}</span>
                <input type="date" name="to" class="form-control" value="{{ $filters['to'] }}">
            </label>
            <select name="pharmacist" class="filter-select" aria-label="{{ __('pharmacy_desk.f_pharmacist', [], $l) }}" data-autosubmit>
                <option value="">{{ __('pharmacy_desk.f_any_pharmacist', [], $l) }}</option>
                @foreach($dispensers as $u)
                <option value="{{ $u->id }}" {{ $filters['pharmacist'] === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
            <label class="ph-check"><input type="checkbox" name="controlled" value="1" {{ $filters['controlled'] ? 'checked' : '' }} data-autosubmit> {{ __('pharmacy_desk.f_controlled', [], $l) }}</label>
            <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="filter"></i> {{ __('pharmacy_desk.btn_filter', [], $l) }}</button>
            @if(request()->hasAny(['from', 'to', 'pharmacist', 'controlled']))
                <a href="{{ route('portals.pharmacy.dispensing') }}" class="btn btn-ghost btn-sm">{{ __('pharmacy_desk.btn_clear', [], $l) }}</a>
            @endif
        </form>
    </div>

    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('pharmacy_desk.col_date', [], $l) }}</th>
                <th>{{ __('pharmacy_desk.col_patient', [], $l) }}</th>
                <th>{{ __('pharmacy_desk.col_items', [], $l) }}</th>
                <th>{{ __('pharmacy_desk.col_pharmacist', [], $l) }}</th>
                <th>{{ __('pharmacy_desk.col_controlled', [], $l) }}</th>
                <th class="row-actions"></th>
            </tr></thead>
            <tbody>
            @forelse($entries as $rx)
            @php
                $done = $rx->items->filter(fn ($it) => $it->isDispensed());
                $controlled = $done->contains(fn ($it) => $service->isControlledItem($it));
                $dispenser = $rx->dispensed_by ? \App\Models\User::query()->find($rx->dispensed_by, ['id', 'name']) : null;
                $url = route('portals.pharmacy.prescriptions.show', $rx->id);
            @endphp
            <tr>
                <td data-label="{{ __('pharmacy_desk.col_date', [], $l) }}" class="td-muted">{{ ($rx->dispensed_at ?? $done->max('dispensed_at'))?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                <td data-label="{{ __('pharmacy_desk.col_patient', [], $l) }}">
                    <div class="ph-cell">
                        <span class="ph-avatar ph-avatar--green" aria-hidden="true">{{ $initials($rx->patient?->first_name, $rx->patient?->last_name) }}</span>
                        <div>
                            <span class="ph-cell__title">{{ \App\Services\Pharmacy\PharmacyDispensingService::maskName($rx->patient?->first_name, $rx->patient?->last_name) }}</span>
                            <div class="ph-cell__sub"><a href="{{ $url }}" class="mono">{{ $rx->reference ?: '—' }}</a> @if($rx->patient?->health_id)&middot; <span class="mono">{{ $rx->patient->health_id }}</span>@endif &middot; {{ __('pharmacy_desk.items_count', ['count' => $done->count()], $l) }}</div>
                        </div>
                    </div>
                </td>
                <td data-label="{{ __('pharmacy_desk.col_items', [], $l) }}" class="ph-lines">
                    @foreach($done->take(2) as $it)<div>{{ $it->drug_name }} {{ $it->dose }}</div>@endforeach
                    @if($done->count() > 2)<div class="td-muted">{{ __('pharmacy_desk.more_items', ['count' => $done->count() - 2], $l) }}</div>@endif
                </td>
                <td data-label="{{ __('pharmacy_desk.col_pharmacist', [], $l) }}">{{ $dispenser?->name ?? '—' }}</td>
                <td data-label="{{ __('pharmacy_desk.col_controlled', [], $l) }}">
                    @if($controlled)<span class="badge badge-danger badge-sm">{{ __('pharmacy_desk.badge_controlled', [], $l) }}</span>@else<span class="td-muted">—</span>@endif
                </td>
                <td class="row-actions" data-label=""><a href="{{ $url }}" class="btn btn-primary btn-sm"><i data-lucide="chevron-right"></i> {{ __('pharmacy_desk.btn_open_rx', [], $l) }}</a></td>
            </tr>
            @empty
            <tr><td colspan="6" class="empty-cell">
                <div class="empty-state">
                    <i data-lucide="clipboard-list" class="empty-state-icon"></i>
                    <p>{{ __('pharmacy_desk.reg_empty', [], $l) }}</p>
                    <p class="td-muted">{{ __('pharmacy_desk.reg_empty_hint', [], $l) }}</p>
                    <a href="{{ route('portals.pharmacy.prescriptions.lookup') }}" class="btn btn-primary btn-sm"><i data-lucide="scan-line"></i> {{ __('pharmacy_desk.btn_lookup', [], $l) }}</a>
                </div>
            </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-footer">{{ $entries->links() }}</div>
</div>

@endsection
