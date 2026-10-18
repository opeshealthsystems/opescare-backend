@extends('layouts.portal')

@section('title', __('referral_desk.show_title'))
@section('breadcrumb_home', __('referral_desk.title'))
@section('breadcrumb_home_url', route('portals.referrals.index', ['tab' => $direction]))
@section('breadcrumb_section', __('referral_desk.show_title'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('referral_desk.show_title') }} &middot; @enum($status)</h1>
        <p class="page-subtitle">{{ __('referral_desk.direction_' . $direction) }}</p>
    </div>
    <a href="{{ route('portals.referrals.index', ['tab' => $direction]) }}" class="btn btn-secondary">
        <i data-lucide="arrow-left"></i> {{ __('referral_desk.back') }}
    </a>
</div>

@if(session('success'))<div class="alert alert-success mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('record_access'))<div class="alert alert-info mb-4"><i data-lucide="shield"></i><div>{{ session('record_access') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-4"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())
<div class="alert alert-danger mb-4" role="alert"><i data-lucide="alert-circle"></i><div>@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div></div>
@endif

@php $dash = __('referral_desk.not_recorded'); @endphp

<div class="panel mb-4">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="arrow-right-left"></i> {{ __('referral_desk.show_title') }}</h2>
        <span class="badge {{ $referral->urgency === 'routine' ? 'badge-neutral' : 'badge-warning' }}">{{ __('referral_desk.urgency.' . $referral->urgency) }}</span>
    </div>
    <div class="panel-body">
        <div class="field-grid">
            <div>
                <div class="td-muted">{{ __('referral_desk.patient') }}</div>
                <div class="td-strong">{{ $referral->patient?->health_id ?? '—' }}</div>
            </div>
            <div>
                <div class="td-muted">{{ __('referral_desk.from') }}</div>
                <div class="td-strong">{{ $referral->referringFacility?->name ?? '—' }}</div>
            </div>
            <div>
                <div class="td-muted">{{ __('referral_desk.to') }}</div>
                <div class="td-strong">
                    {{ $referral->receivingFacility?->name ?? '—' }}
                    @if($referral->receiving_specialty)<br><small class="td-muted">{{ $referral->receiving_specialty }}</small>@endif
                    @if($referral->receiving_provider_name)<br><small class="td-muted">{{ $referral->receiving_provider_name }}</small>@endif
                </div>
            </div>
            <div>
                <div class="td-muted">{{ __('referral_desk.col_created') }}</div>
                <div class="td-strong">{{ $referral->created_at?->translatedFormat('d M Y H:i') ?? '—' }}</div>
            </div>
            <div>
                <div class="td-muted">{{ __('referral_desk.expires_at') }}</div>
                <div class="td-strong">{{ $referral->expires_at?->translatedFormat('d M Y') ?? '—' }}</div>
            </div>
            <div>
                <div class="td-muted">{{ __('referral_desk.records_included') }}</div>
                <div class="td-strong">
                    @forelse((array) ($referral->included_record_types ?? []) as $type)
                        {{ \Illuminate\Support\Facades\Lang::has('referral_desk.record_types.' . $type) ? __('referral_desk.record_types.' . $type) : $type }}@if(! $loop->last), @endif
                    @empty
                        {{ __('referral_desk.records_none') }}
                    @endforelse
                </div>
            </div>
        </div>

        <h3 class="panel-title mt-4">{{ __('referral_desk.reason') }}</h3>
        <p>{{ $referral->reason }}</p>

        <h3 class="panel-title mt-4">{{ __('referral_desk.clinical_summary') }}</h3>
        <p class="{{ $referral->clinical_summary ? '' : 'td-muted' }}">{{ $referral->clinical_summary ?: $dash }}</p>

        @if($referral->accepted_at)<p><span class="td-muted">{{ __('referral_desk.accepted_at') }}:</span> {{ $referral->accepted_at->translatedFormat('d M Y H:i') }}</p>@endif
        @if($referral->rejected_at)
            <p><span class="td-muted">{{ __('referral_desk.rejected_at') }}:</span> {{ $referral->rejected_at->translatedFormat('d M Y H:i') }}</p>
            <p><span class="td-muted">{{ __('referral_desk.rejection_reason') }}:</span> {{ $referral->rejection_reason ?: $dash }}</p>
        @endif
        @if($referral->completed_at)
            <p><span class="td-muted">{{ __('referral_desk.completed_at') }}:</span> {{ $referral->completed_at->translatedFormat('d M Y H:i') }}</p>
            <p><span class="td-muted">{{ __('referral_desk.feedback') }}:</span> {{ $referral->feedback ?: $dash }}</p>
        @endif
        @if($referral->cancelled_at)
            <p><span class="td-muted">{{ __('referral_desk.cancelled_at') }}:</span> {{ $referral->cancelled_at->translatedFormat('d M Y H:i') }}</p>
            <p><span class="td-muted">{{ __('referral_desk.cancellation_reason') }}:</span> {{ $referral->cancellation_reason ?: $dash }}</p>
        @endif
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="list-checks"></i> {{ __('referral_desk.actions') }}</h2>
    </div>
    <div class="panel-body">
        @php
            $actionFor = ['sent' => 'send', 'accepted' => 'accept', 'rejected' => 'reject', 'completed' => 'complete', 'cancelled' => 'cancel'];
        @endphp
        @if(empty($moves))
            <p class="td-muted">{{ __('referral_desk.no_actions') }}</p>
        @else
            @foreach($moves as $move)
                @php $action = $actionFor[$move]; @endphp
                <form method="POST" action="{{ route('portals.referrals.transition', [$referral->id, $action]) }}" class="mb-4">
                    @csrf
                    @if($action === 'send')
                        <div class="form-group mb-2">
                            <label class="form-label" for="ref-expires">{{ __('referral_desk.expires_in_days') }}</label>
                            <input type="number" id="ref-expires" name="expires_in_days" class="form-control" min="1" max="180" placeholder="30">
                        </div>
                    @elseif($action === 'reject' || $action === 'cancel')
                        <div class="form-group mb-2">
                            <label class="form-label form-label-required" for="ref-reason-{{ $action }}">{{ __('referral_desk.reason') }}</label>
                            <textarea id="ref-reason-{{ $action }}" name="reason" class="form-control" rows="2" required maxlength="2000"></textarea>
                            <div class="form-hint">{{ __('referral_desk.reason_required_hint') }}</div>
                        </div>
                    @elseif($action === 'complete')
                        <div class="form-group mb-2">
                            <label class="form-label" for="ref-feedback">{{ __('referral_desk.feedback') }}</label>
                            <textarea id="ref-feedback" name="feedback" class="form-control" rows="3" maxlength="8000"></textarea>
                        </div>
                    @endif
                    <button type="submit" class="btn {{ in_array($action, ['reject', 'cancel'], true) ? 'btn-secondary' : 'btn-primary' }}" data-confirm="{{ __('hospital_desk.confirm_referral_' . $action) }}">
                        <i data-lucide="{{ match($action) { 'send' => 'send', 'accept' => 'check', 'reject' => 'x', 'complete' => 'check-check', default => 'ban' } }}"></i>
                        {{ __('referral_desk.action.' . $action) }}
                    </button>
                </form>
            @endforeach
        @endif
    </div>
</div>

@endsection
