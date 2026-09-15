@extends('layouts.portal')

@section('title', __('referral_desk.title'))
@section('breadcrumb_home', __('referral_desk.title'))
@section('breadcrumb_home_url', route('portals.referrals.index'))
@section('breadcrumb_section', __('referral_desk.tab_' . $tab))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('referral_desk.title') }}</h1>
        <p class="page-subtitle">{{ __('referral_desk.subtitle') }}</p>
    </div>
    <a href="{{ route('portals.referrals.create') }}" class="btn btn-primary">
        <i data-lucide="plus"></i> {{ __('referral_desk.new') }}
    </a>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="panel">
    <div class="panel-header">
        <nav class="row-actions-inline" aria-label="{{ __('referral_desk.title') }}">
            @foreach(['sent', 'received'] as $t)
                <a href="{{ route('portals.referrals.index', ['tab' => $t, 'status' => $status ?: null]) }}"
                   class="btn {{ $tab === $t ? 'btn-primary' : 'btn-secondary' }}">
                    <i data-lucide="{{ $t === 'sent' ? 'send' : 'inbox' }}"></i> {{ __('referral_desk.tab_' . $t) }}
                </a>
            @endforeach
        </nav>
        <form method="GET" action="{{ route('portals.referrals.index') }}" class="row-actions-inline">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <select name="status" class="form-control" aria-label="{{ __('referral_desk.col_status') }}">
                <option value="">{{ __('referral_desk.all_statuses') }}</option>
                @foreach($statuses as $s)
                    <option value="{{ $s }}" @selected($status === $s)>@enum($s)</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary"><i data-lucide="filter"></i> {{ __('referral_desk.filter') }}</button>
        </form>
    </div>
    <div class="panel-body">
        @if($referrals->isEmpty())
            <p class="td-muted">{{ __('referral_desk.none') }}</p>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>{{ __('referral_desk.col_created') }}</th>
                        <th>{{ __('referral_desk.col_patient') }}</th>
                        <th>{{ __('referral_desk.col_from') }}</th>
                        <th>{{ __('referral_desk.col_to') }}</th>
                        <th>{{ __('referral_desk.col_urgency') }}</th>
                        <th>{{ __('referral_desk.col_status') }}</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($referrals as $r)
                        @php $current = $service->currentStatus($r); @endphp
                        <tr>
                            <td class="td-muted">{{ $r->created_at?->format('d M Y') ?? '—' }}</td>
                            <td class="td-strong">{{ $r->patient?->health_id ?? '—' }}</td>
                            <td>{{ $r->referringFacility?->name ?? '—' }}</td>
                            <td>{{ $r->receivingFacility?->name ?? ($r->receiving_specialty ?: ($r->receiving_provider_name ?: '—')) }}</td>
                            <td><span class="badge {{ $r->urgency === 'routine' ? 'badge-neutral' : 'badge-warning' }}">{{ __('referral_desk.urgency.' . $r->urgency) }}</span></td>
                            <td>@enum($current)</td>
                            <td><a href="{{ route('portals.referrals.show', $r->id) }}" class="btn btn-secondary">{{ __('referral_desk.open') }}</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $referrals->links() }}</div>
        @endif
    </div>
</div>

@endsection
