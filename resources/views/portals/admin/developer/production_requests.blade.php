@extends('layouts.portal')
@section('title', __('public.adm_dev_prod_title'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
@php
    $gdTones = ['blue','amber','teal','rose','sky','green'];
    $gdTone  = fn ($k) => $gdTones[crc32((string) $k) % count($gdTones)];
    $gdInit  = fn ($n) => mb_strtoupper(collect(preg_split('/\s+/', trim((string) $n)))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('')) ?: '—';
    $gdPill  = fn ($s) => match ($s) { 'approved' => 'od-pill--ready', 'pending' => 'od-pill--pending', 'under_review' => 'od-pill--normal', 'rejected' => 'od-pill--urgent', default => 'od-pill--neutral' };
@endphp
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('public.adm_dev_prod_heading') }}</h1>
        <p class="od-page__sub">{{ __('public.adm_dev_prod_breadcrumb_section') }} · {{ __('public.adm_dev_prod_subtitle') }}</p>
    </div>
</div>

@if(session('success'))<div class="oda-gd-flash oda-gd-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-gd-flash oda-gd-flash--err"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

{{-- Stats strip --}}
<div class="od-kpis">
    <div class="od-kpi od-tint--amber"><span class="od-kpi__icon"><i data-lucide="hourglass"></i></span><span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_dev_prod_stat_pending') }}</span><span class="od-kpi__value">{{ $stats['pending'] }}</span></span></div>
    <div class="od-kpi od-tint--blue"><span class="od-kpi__icon"><i data-lucide="search"></i></span><span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_dev_prod_stat_under_review') }}</span><span class="od-kpi__value">{{ $stats['under_review'] }}</span></span></div>
    <div class="od-kpi od-tint--green"><span class="od-kpi__icon"><i data-lucide="check-circle"></i></span><span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_dev_prod_stat_approved') }}</span><span class="od-kpi__value">{{ $stats['approved'] }}</span></span></div>
    <div class="od-kpi od-tint--rose"><span class="od-kpi__icon"><i data-lucide="x-circle"></i></span><span class="od-kpi__body"><span class="od-kpi__label">{{ __('public.adm_dev_prod_stat_rejected') }}</span><span class="od-kpi__value">{{ $stats['rejected'] }}</span></span></div>
</div>

<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('public.adm_dev_prod_heading') }}</h2>
        <span class="od-card__count">{{ method_exists($requests, 'total') ? $requests->total() : $requests->count() }}</span>
    </div>
    {{-- Filter tabs --}}
    <div class="od-card__body">
        <nav class="od-tabs">
            @foreach(['all'=>__('public.adm_dev_prod_tab_all'),'pending'=>__('public.adm_dev_prod_tab_pending'),'under_review'=>__('public.adm_dev_prod_tab_under_review'),'approved'=>__('public.adm_dev_prod_tab_approved'),'rejected'=>__('public.adm_dev_prod_tab_rejected')] as $val=>$label)
            @php $target = $val === 'all' ? null : $val; $isActive = request('status', $target) === $target; @endphp
            <a href="{{ request()->fullUrlWithQuery(['status'=>$target]) }}" class="od-tab {{ $isActive ? 'is-active' : '' }}">{{ $label }}</a>
            @endforeach
        </nav>
    </div>
@if($requests->isEmpty())
    <div class="od-card__body">
        <div class="od-empty">
            <span class="od-empty__icon"><i data-lucide="clipboard-list"></i></span>
            <p class="od-empty__text">{{ __('public.adm_dev_prod_empty') }}</p>
        </div>
    </div>
</div>
@else
    <div class="od-card__body od-card__body--flush">
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th>{{ __('public.adm_dev_prod_col_developer') }}</th>
                    <th>{{ __('public.adm_dev_prod_col_use_case') }}</th>
                    <th>{{ __('public.adm_dev_prod_col_scopes') }}</th>
                    <th>{{ __('public.adm_dev_prod_col_patient_data') }}</th>
                    <th>{{ __('public.adm_dev_prod_col_status') }}</th>
                    <th>{{ __('public.adm_dev_prod_col_submitted') }}</th>
                    <th>{{ __('public.adm_dev_prod_col_actions') }}</th>
                </tr></thead>
                <tbody>
                @foreach($requests as $req)
                @php $devName = $req->developerAccount->display_name ?? $req->developerAccount->email ?? '—'; @endphp
                <tr>
                    <td data-label="{{ __('public.adm_dev_prod_col_developer') }}">
                        <div class="od-table__person">
                            <span class="od-avatar od-avatar--sm od-tint--{{ $gdTone($req->developer_account_id ?? $devName) }}">{{ $gdInit($devName) }}</span>
                            <div>
                                <div class="od-table__name">{{ $devName }}</div>
                                @if($req->integration_client_id)<div class="od-table__sub oda-mono">{{ Str::limit($req->integration_client_id, 28) }}</div>@endif
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('public.adm_dev_prod_col_use_case') }}">
                        <div>{{ Str::limit($req->use_case, 55) }}</div>
                        @if($req->technical_description)<div class="od-table__sub">{{ Str::limit($req->technical_description, 60) }}</div>@endif
                    </td>
                    <td data-label="{{ __('public.adm_dev_prod_col_scopes') }}"><span class="od-pill od-pill--neutral">{{ count((array)$req->requested_scopes) }} {{ __('public.adm_dev_prod_scopes_label') }}</span></td>
                    <td data-label="{{ __('public.adm_dev_prod_col_patient_data') }}">
                        @if($req->handles_patient_data)
                        <span class="od-pill od-pill--pending">{{ __('public.adm_dev_prod_badge_yes') }}</span>
                        @else
                        <span class="od-pill od-pill--neutral">{{ __('public.adm_dev_prod_badge_no') }}</span>
                        @endif
                    </td>
                    <td data-label="{{ __('public.adm_dev_prod_col_status') }}">
                        <span class="od-pill {{ $gdPill($req->status) }}">@enum($req->status)</span>
                        @if($req->reviewed_at)<div class="od-table__sub">{{ $req->reviewed_at->translatedFormat('d M Y') }}</div>@endif
                    </td>
                    <td data-label="{{ __('public.adm_dev_prod_col_submitted') }}">
                        {{ $req->created_at->translatedFormat('d M Y') }}
                        @if($req->estimated_daily_requests)<div class="od-table__sub">{{ $req->estimated_daily_requests }} {{ __('public.adm_dev_prod_req_per_day') }}</div>@endif
                    </td>
                    <td data-label="{{ __('public.adm_dev_prod_col_actions') }}">
                        @if(in_array($req->status, ['pending','under_review']))
                        <div class="od-table__actions">
                            <button type="button" class="od-btn od-btn--row" data-call="opOpenModal" data-args="[&quot;approve-{{ $req->id }}&quot;]"><i data-lucide="check"></i> {{ __('public.adm_dev_prod_btn_approve') }}</button>
                            <button type="button" class="od-btn od-btn--danger od-btn--sm" data-call="opOpenModal" data-args="[&quot;reject-{{ $req->id }}&quot;]"><i data-lucide="x"></i> {{ __('public.adm_dev_prod_btn_reject') }}</button>
                        </div>
                        @elseif($req->status === 'approved')
                            <span class="od-pill od-pill--ready">{{ __('public.adm_dev_prod_status_approved') }}</span>
                            @if($req->review_notes)<div class="od-table__sub">{{ Str::limit($req->review_notes, 30) }}</div>@endif
                        @else
                            <span class="od-pill od-pill--urgent">{{ __('public.adm_dev_prod_status_rejected') }}</span>
                            @if($req->rejected_reason)<div class="od-table__sub">{{ Str::limit($req->rejected_reason, 30) }}</div>@endif
                        @endif
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="od-card__foot">{{ $requests->links() }}</div>
</div>

{{-- Approve / Reject confirm modals --}}
@foreach($requests as $req)
    @if(in_array($req->status, ['pending','under_review']))
    <div id="approve-{{ $req->id }}" class="modal-backdrop oda-gd-backdrop" hidden>
        <div class="od-card oda-modal" role="dialog" aria-modal="true" aria-labelledby="approve-{{ $req->id }}-title">
            <div class="od-card__head"><h3 class="od-card__title" id="approve-{{ $req->id }}-title"><i data-lucide="check-circle"></i> {{ __('public.adm_dev_prod_modal_approve_title') }}</h3></div>
            <form method="POST" action="{{ route('portals.admin.developer.production_requests.approve', $req->id) }}" class="od-card__body oda-form">
                @csrf
                <p class="oda-form__full oda-gd-text">{{ __('public.adm_dev_prod_modal_approve_body') }}</p>
                {{-- D-5: a production app is always facility-bound. Exact facility name, resolved server-side. --}}
                <label class="oda-label oda-form__full" for="facility-{{ $req->id }}"><span class="oda-label__text">{{ __('developer_ext.adm_approve_lbl_facility') }}</span>
                    <input id="facility-{{ $req->id }}" type="text" name="facility_name" required maxlength="255" class="od-input" value="{{ old('facility_name') }}" placeholder="{{ __('developer_ext.adm_approve_ph_facility') }}" autocomplete="off">
                    <span class="oda-help">{{ __('developer_ext.adm_approve_help_facility') }}</span>
                    <span class="oda-help">{{ __('developer_ext.adm_approve_help_scopes', ['count' => count((array) $req->requested_scopes)]) }}</span>
                </label>
                @foreach((array) $req->requested_scopes as $scope)
                    <input type="hidden" name="approved_scopes[]" value="{{ $scope }}">
                @endforeach
                <div class="oda-form__actions">
                    <button type="button" class="od-btn od-btn--ghost" data-call="opCloseModal" data-args="[&quot;approve-{{ $req->id }}&quot;]">{{ __('public.adm_dev_prod_modal_btn_cancel') }}</button>
                    <button type="submit" class="od-btn od-btn--primary">{{ __('public.adm_dev_prod_btn_approve') }}</button>
                </div>
            </form>
        </div>
    </div>
    <div id="reject-{{ $req->id }}" class="modal-backdrop oda-gd-backdrop" hidden>
        <div class="od-card oda-modal" role="dialog" aria-modal="true" aria-labelledby="reject-{{ $req->id }}-title">
            <div class="od-card__head"><h3 class="od-card__title" id="reject-{{ $req->id }}-title"><i data-lucide="x-circle"></i> {{ __('public.adm_dev_prod_modal_reject_title') }}</h3></div>
            <form method="POST" action="{{ route('portals.admin.developer.production_requests.reject', $req->id) }}" class="od-card__body oda-form">
                @csrf
                <p class="oda-form__full oda-gd-text">{{ __('public.adm_dev_prod_modal_reject_body') }}</p>
                <textarea name="reason" rows="3" required placeholder="{{ __('public.adm_dev_prod_modal_reject_ph') }}" class="oda-textarea oda-form__full" aria-label="{{ __('public.adm_dev_prod_modal_reject_ph') }}"></textarea>
                <div class="oda-form__actions">
                    <button type="button" class="od-btn od-btn--ghost" data-call="opCloseModal" data-args="[&quot;reject-{{ $req->id }}&quot;]">{{ __('public.adm_dev_prod_modal_btn_cancel') }}</button>
                    <button type="submit" class="od-btn od-btn--danger">{{ __('public.adm_dev_prod_modal_btn_confirm_reject') }}</button>
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
