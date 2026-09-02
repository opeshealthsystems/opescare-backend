@extends('layouts.portal')
@section('title', __('public.adm_legal_minor_page_title'))
@section('sidebar') @include('portals.admin.control_center._sidebar') @endsection

@section('content')

<div class="breadcrumb">
    <a href="{{ route('portals.admin.legal') }}">{{ __('public.adm_legal_closures_breadcrumb') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ __('public.adm_legal_minor_breadcrumb_span') }}</span>
</div>

<div class="page-head">
    <h2>{{ __('public.adm_legal_minor_h2') }}</h2>
</div>
<p class="td-muted mb-6">{{ __('public.adm_legal_minor_subtitle') }}</p>

<div class="panel">
    <div class="panel-body panel-body--flush">
        <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr><th>{{ __('public.adm_legal_closures_col_patient') }}</th><th>{{ __('public.adm_legal_minor_col_dob') }}</th><th>{{ __('public.adm_legal_minor_col_turns18') }}</th><th>{{ __('public.adm_legal_minor_col_days_until_18') }}</th><th>{{ __('public.adm_legal_col_status') }}</th></tr>
            </thead>
            <tbody>
                @forelse($transitions as $t)
                    @php
                        $days = $t->daysUntil18();
                        $daysBadge = $days <= 30 ? 'badge-danger' : ($days <= 90 ? 'badge-warning' : 'badge-success');
                    @endphp
                    <tr>
                        <td data-label="{{ __('public.adm_legal_closures_col_patient') }}" class="td-strong">
                            {{ $t->patient?->first_name }} {{ $t->patient?->last_name }}
                            <div class="code-muted">{{ $t->patient?->health_id }}</div>
                        </td>
                        <td data-label="{{ __('public.adm_legal_minor_col_dob') }}">{{ $t->date_of_birth->format('d M Y') }}</td>
                        <td data-label="{{ __('public.adm_legal_minor_col_turns18') }}" class="td-strong">{{ $t->turns_18_on->format('d M Y') }}</td>
                        <td data-label="{{ __('public.adm_legal_minor_col_days_until_18') }}">
                            <span class="badge {{ $daysBadge }} badge-sm">
                                @if($days < 0) {{ __('public.adm_legal_minor_overdue') }} @elseif($days === 0) {{ __('public.adm_legal_minor_today') }} @else {{ $days }} {{ __('public.adm_legal_minor_days') }} @endif
                            </span>
                        </td>
                        <td data-label="{{ __('public.adm_legal_col_status') }}">
                            <span class="badge badge--{{ $t->statusColor() }} badge-sm">@enum($t->status)</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="td-muted empty-cell">{{ __('public.adm_legal_minor_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
<div class="mt-6">{{ $transitions->links() }}</div>

@endsection
