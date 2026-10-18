@extends('layouts.portal')
@section('title', __('public.adm_legal_closures_page_title'))
@section('breadcrumb_home', __('public.adm_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_legal_closures_page_title'))
@section('sidebar') @include('portals.admin.control_center._sidebar') @endsection
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
@php
    $gbTints = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'];
    $gbPill = fn ($c) => match ($c) { 'warning' => 'pending', 'info' => 'normal', 'success' => 'ready', 'danger' => 'urgent', default => 'neutral' };
@endphp
<div class="od-page">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('public.adm_legal_closures_h2') }}</h1>
        <p class="od-page__sub">{{ __('public.adm_legal_closures_subtitle') }}</p>
    </div>
</div>

@if(session('success'))<div class="oda-gb-alert oda-gb-alert--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-gb-alert oda-gb-alert--bad"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('public.adm_legal_closures_breadcrumb_span') }}</h2>
        <span class="od-card__count">{{ method_exists($requests, 'total') ? $requests->total() : $requests->count() }}</span>
    </div>
    <div class="od-card__body od-card__body--flush">
        <div class="od-table-wrap">
        <table class="od-table">
            <thead>
                <tr><th>{{ __('public.adm_legal_closures_col_patient') }}</th><th>{{ __('public.adm_legal_closures_col_reason') }}</th><th>{{ __('public.adm_legal_closures_col_delete_req') }}</th><th>{{ __('public.adm_legal_closures_col_export_req') }}</th><th>{{ __('public.adm_legal_col_status') }}</th><th>{{ __('public.adm_legal_closures_col_requested') }}</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                    @php $pname = trim(($req->patient?->first_name ?? '') . ' ' . ($req->patient?->last_name ?? '')); @endphp
                    <tr>
                        <td data-label="{{ __('public.adm_legal_closures_col_patient') }}">
                            <div class="od-table__person">
                                <span class="od-avatar od-avatar--sm od-tint--{{ $gbTints[crc32($pname) % 6] }}">{{ mb_strtoupper(mb_substr($req->patient?->first_name ?? '?', 0, 1) . mb_substr($req->patient?->last_name ?? '', 0, 1)) }}</span>
                                <div>
                                    <div class="od-table__name">{{ $req->patient?->first_name }} {{ $req->patient?->last_name }}</div>
                                    <div class="od-table__sub oda-mono">{{ $req->patient?->health_id }}</div>
                                </div>
                            </div>
                        </td>
                        <td data-label="{{ __('public.adm_legal_closures_col_reason') }}">{{ \Illuminate\Support\Str::limit($req->reason, 60) ?: '—' }}</td>
                        <td data-label="{{ __('public.adm_legal_closures_col_delete_req') }}">
                            @if($req->data_delete_requested)<span class="od-pill od-pill--urgent">{{ __('admin_extra.yes', [], app()->getLocale()) }}</span>@else<span class="od-table__sub">{{ __('admin_extra.no', [], app()->getLocale()) }}</span>@endif
                        </td>
                        <td data-label="{{ __('public.adm_legal_closures_col_export_req') }}">
                            @if($req->data_export_requested)<span class="od-pill od-pill--normal">{{ __('admin_extra.yes', [], app()->getLocale()) }}</span>@else<span class="od-table__sub">{{ __('admin_extra.no', [], app()->getLocale()) }}</span>@endif
                        </td>
                        <td data-label="{{ __('public.adm_legal_col_status') }}">
                            <span class="od-pill od-pill--{{ $gbPill($req->statusColor()) }}">@enum($req->status)</span>
                        </td>
                        <td data-label="{{ __('public.adm_legal_closures_col_requested') }}"><span class="od-table__sub">{{ $req->created_at->translatedFormat('d M Y') }}</span></td>
                        {{--
                            The cell was empty: the column, the controller
                            action and both button strings all existed, and
                            nothing ever rendered a control into it.

                            Only a request still awaiting review gets buttons.
                            `account_closure_requests` is shared with the
                            patient-facing self-service deletion flow
                            (App\Services\Compliance\AccountDeletionService),
                            which moves rows through
                            pending → cancelled/completed/purged on the
                            patient's own authority. Those are not this desk's
                            to approve, and an approve button on a row the
                            patient already cancelled would be a way to
                            un-cancel it.

                            Approve is the destructive half — it is what puts a
                            person's record on the path to deletion — so it
                            confirms; reject does not.
                        --}}
                        <td>
                            @if($req->status === 'pending')
                            <div class="od-table__actions">
                                <form method="POST" action="{{ route('portals.admin.legal.closures.review', $req->id) }}" class="oda-inline-form"
                                      data-confirm="{{ __('public.adm_legal_closures_confirm_approve') }}">
                                    @csrf
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="od-btn od-btn--row">
                                        <i data-lucide="check"></i> {{ __('public.adm_legal_closures_btn_approve') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('portals.admin.legal.closures.review', $req->id) }}" class="oda-inline-form">
                                    @csrf
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="od-btn od-btn--danger od-btn--sm">
                                        <i data-lucide="x"></i> {{ __('public.adm_legal_closures_btn_reject') }}
                                    </button>
                                </form>
                            </div>
                            @else
                                <span class="od-table__sub">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">
                        <div class="od-empty">
                            <div class="od-empty__icon"><i data-lucide="user-x"></i></div>
                            <p class="od-empty__text">{{ __('public.adm_legal_closures_empty') }}</p>
                        </div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</section>
<div class="oda-gb-pager">{{ $requests->links() }}</div>
</div>
@endsection
