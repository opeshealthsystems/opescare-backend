@extends('layouts.portal')
@section('title', __('public.adm_dev_acc_title'))
@section('breadcrumb_home', __('public.adm_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_dev_acc_title'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
@php
    $gdTones = ['blue','amber','teal','rose','sky','green'];
    $gdTone  = fn ($k) => $gdTones[crc32((string) $k) % count($gdTones)];
    $gdInit  = fn ($n) => mb_strtoupper(collect(preg_split('/\s+/', trim((string) $n)))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('')) ?: '—';
    $gdPill  = fn ($s) => match ($s) { 'active' => 'od-pill--ready', 'pending_verification' => 'od-pill--pending', 'suspended' => 'od-pill--urgent', default => 'od-pill--neutral' };
@endphp
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('public.adm_dev_acc_heading') }}</h1>
        <p class="od-page__sub">{{ __('public.adm_dev_acc_breadcrumb_section') }} · {{ __('public.adm_dev_acc_subtitle') }}</p>
    </div>
</div>

@if(session('success'))<div class="oda-gd-flash oda-gd-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-gd-flash oda-gd-flash--err"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

{{-- Stats strip --}}
<div class="od-kpis">
    <div class="od-kpi od-tint--blue"><span class="od-kpi__icon"><i data-lucide="users"></i></span><span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_dev_acc_stat_total') }}</span><span class="od-kpi__value">{{ $stats['total'] }}</span></span></div>
    <div class="od-kpi od-tint--green"><span class="od-kpi__icon"><i data-lucide="check-circle"></i></span><span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_dev_acc_stat_active') }}</span><span class="od-kpi__value">{{ $stats['active'] }}</span></span></div>
    <div class="od-kpi od-tint--sky"><span class="od-kpi__icon"><i data-lucide="flask-conical"></i></span><span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_dev_acc_stat_sandbox') }}</span><span class="od-kpi__value">{{ $stats['sandbox_only'] }}</span></span></div>
    <div class="od-kpi od-tint--rose"><span class="od-kpi__icon"><i data-lucide="ban"></i></span><span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_dev_acc_stat_suspended') }}</span><span class="od-kpi__value">{{ $stats['suspended'] }}</span></span></div>
</div>

<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('public.adm_dev_acc_heading') }}</h2>
        <span class="od-card__count">{{ method_exists($accounts, 'total') ? $accounts->total() : $accounts->count() }}</span>
    </div>
@if($accounts->isEmpty())
    <div class="od-card__body">
        <div class="od-empty">
            <span class="od-empty__icon"><i data-lucide="users"></i></span>
            <p class="od-empty__text">{{ __('public.adm_dev_acc_empty') }}</p>
        </div>
    </div>
</div>
@else
    <div class="od-card__body od-card__body--flush">
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th>{{ __('public.adm_dev_acc_col_developer') }}</th>
                    <th>{{ __('public.adm_dev_acc_col_email') }}</th>
                    <th>{{ __('public.adm_dev_acc_col_company') }}</th>
                    <th>{{ __('public.adm_dev_acc_col_apps') }}</th>
                    <th>{{ __('public.adm_dev_acc_col_access') }}</th>
                    <th>{{ __('public.adm_dev_acc_col_status') }}</th>
                    <th>{{ __('public.adm_dev_acc_col_joined') }}</th>
                    <th>{{ __('public.adm_dev_acc_col_actions') }}</th>
                </tr></thead>
                <tbody>
                @foreach($accounts as $account)
                <tr>
                    <td data-label="{{ __('public.adm_dev_acc_col_developer') }}">
                        <div class="od-table__person">
                            <span class="od-avatar od-avatar--sm od-tint--{{ $gdTone($account->id) }}">{{ $gdInit($account->display_name ?? $account->email) }}</span>
                            <div>
                                <div class="od-table__name">{{ $account->display_name ?? '—' }}</div>
                                @if($account->website_url)
                                <div class="od-table__sub"><a href="{{ $account->website_url }}" target="_blank">{{ Str::limit($account->website_url, 30) }}</a></div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('public.adm_dev_acc_col_email') }}"><span class="oda-mono">{{ $account->email }}</span></td>
                    <td data-label="{{ __('public.adm_dev_acc_col_company') }}">{{ $account->company_name ?? '—' }}</td>
                    <td data-label="{{ __('public.adm_dev_acc_col_apps') }}"><span class="od-pill od-pill--neutral">{{ $account->integrationClients_count ?? 0 }}</span></td>
                    <td data-label="{{ __('public.adm_dev_acc_col_access') }}">
                        @if($account->sandbox_only)
                        <span class="od-pill od-pill--normal">{{ __('public.adm_dev_acc_badge_sandbox') }}</span>
                        @else
                        <span class="od-pill od-pill--ready">{{ __('public.adm_dev_acc_badge_production') }}</span>
                        @endif
                    </td>
                    <td data-label="{{ __('public.adm_dev_acc_col_status') }}">
                        <span class="od-pill {{ $gdPill($account->status) }}">@enum($account->status)</span>
                        @if($account->status === 'suspended' && $account->suspend_reason)
                        <div class="od-table__sub">{{ Str::limit($account->suspend_reason, 30) }}</div>
                        @endif
                    </td>
                    <td data-label="{{ __('public.adm_dev_acc_col_joined') }}">
                        {{ $account->created_at->translatedFormat('d M Y') }}
                        @if($account->api_terms_accepted)<div class="od-table__sub">{{ __('public.adm_dev_acc_terms_accepted') }}</div>@endif
                    </td>
                    <td data-label="{{ __('public.adm_dev_acc_col_actions') }}">
                        @if($account->status !== 'suspended')
                        <button type="button" class="od-btn od-btn--danger od-btn--sm" data-call="opOpenModal" data-args="[&quot;suspend-{{ $account->id }}&quot;]">{{ __('public.adm_dev_acc_btn_suspend') }}</button>
                        @else
                        <span class="od-table__sub">{{ __('public.adm_dev_acc_label_suspended') }}</span>
                        @if($account->suspended_at)<div class="od-table__sub">{{ $account->suspended_at->translatedFormat('d M Y') }}</div>@endif
                        @endif
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="od-card__foot">{{ $accounts->links() }}</div>
</div>

{{-- Suspend confirm modals --}}
@foreach($accounts as $account)
    @if($account->status !== 'suspended')
    <div id="suspend-{{ $account->id }}" class="modal-backdrop oda-gd-backdrop" hidden>
        <div class="od-card oda-modal" role="dialog" aria-modal="true" aria-labelledby="suspend-{{ $account->id }}-title">
            <div class="od-card__head"><h3 class="od-card__title" id="suspend-{{ $account->id }}-title"><i data-lucide="ban"></i> {{ __('public.adm_dev_acc_modal_heading') }}</h3></div>
            <form method="POST" action="{{ route('portals.admin.developer.accounts.suspend', $account->id) }}" class="od-card__body oda-form">
                @csrf
                <p class="oda-form__full oda-gd-text">{{ __('public.adm_dev_acc_modal_body') }}</p>
                <textarea name="reason" rows="3" required placeholder="{{ __('public.adm_dev_acc_modal_ph_reason') }}" class="oda-textarea oda-form__full" aria-label="{{ __('public.adm_dev_acc_modal_ph_reason') }}"></textarea>
                <div class="oda-form__actions">
                    <button type="button" class="od-btn od-btn--ghost" data-call="opCloseModal" data-args="[&quot;suspend-{{ $account->id }}&quot;]">{{ __('public.adm_dev_acc_modal_btn_cancel') }}</button>
                    <button type="submit" class="od-btn od-btn--danger">{{ __('public.adm_dev_acc_modal_btn_confirm') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endforeach
@endif

</div>
@endsection
@section('scripts')
<script @cspNonce>
function opOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function opCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
@endsection
