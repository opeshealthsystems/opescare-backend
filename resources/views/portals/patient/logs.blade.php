@extends('layouts.portal')

@section('title', __('family.log_title') . ' — OpesCare')
@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('family.log_title'))

@section('head')
    @include('portals.patient._finder_head')
@endsection

@section('patient_banner')
    @include('portals.patient._viewing_banner')
@endsection

@php
    $viewing  = request()->attributes->get('viewing_patient');
    $logCount = method_exists($logs, 'total') ? $logs->total() : $logs->count();
@endphp

@section('content')
<div class="pf pf--wide">

    <div class="pf-head">
        <div>
            <h1>{{ __('family.log_title') }}</h1>
            <p class="pf-sub">
                @if($viewing){{ __('family.record_of', ['name' => trim($viewing->first_name . ' ' . $viewing->last_name)]) }} &middot; @endif
                {{ __('family.log_subtitle') }}
            </p>
        </div>
        <a href="{{ route('portals.patient') }}" class="f-btn">
            @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 18])
            {{ __('family.back_to_dashboard') }}
        </a>
    </div>

    @if(!$patient || $logCount === 0)
    <div class="f-card" style="text-align:center;padding:40px 20px;" data-section="access-logs-empty">
        <div class="pf-qa__icon" style="margin:0 auto 12px;width:48px;height:48px;">
            <i data-lucide="history" aria-hidden="true" style="width:24px;height:24px;"></i>
        </div>
        <p class="f-muted" style="margin:0;">{{ __('family.log_empty') }}</p>
    </div>
    @else
    <div class="f-card" data-section="access-logs">
        <div class="pf-table-wrap">
            <table class="pf-table">
                <thead>
                    <tr>
                        <th>{{ __('family.col_when') }}</th>
                        <th>{{ __('family.col_who') }}</th>
                        <th>{{ __('family.col_type') }}</th>
                        <th>{{ __('family.col_purpose') }}</th>
                        <th>{{ __('family.col_result') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($logs as $log)
                    <tr>
                        <td style="white-space:nowrap;">{{ $log->created_at?->translatedFormat('d M Y, H:i') ?? '—' }}</td>
                        <td>{{ $log->actor_type ? ucfirst(str_replace('_', ' ', (string) $log->actor_type)) : '—' }}</td>
                        <td>@enum($log->access_type)</td>
                        <td>{{ $log->purpose ? ucfirst(str_replace('_', ' ', $log->purpose)) : '—' }}</td>
                        <td><span class="f-badge {{ in_array(strtolower((string) $log->result), ['success', 'granted', 'allowed'], true) ? 'f-badge--good' : 'pf-badge--muted' }}">@enum($log->result)</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if(method_exists($logs, 'links'))
        <div style="margin-top:12px;">{{ $logs->links() }}</div>
        @endif
    </div>
    @endif
</div>
@endsection
