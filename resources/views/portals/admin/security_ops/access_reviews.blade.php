{{-- Group D import: web page over the platform's AccessReviewService (the platform exposed it as JSON only). --}}
@extends('layouts.portal')
@section('title', __('security_ops.ar_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('security_ops.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('security_ops.ar_title'))

@section('content')
<div class="page-head">
    <h2>{{ __('security_ops.ar_title') }}</h2>
    @if($overdue > 0)<span class="badge badge-warning">{{ __('security_ops.ar_overdue_schedules', ['count' => $overdue]) }}</span>@endif
</div>
<p class="page-subtitle mb-6">{{ __('security_ops.ar_subtitle') }}</p>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>@endif

<div class="panel mb-6">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="user-search"></i> {{ __('security_ops.ar_initiate') }}</h3></div>
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.admin.security.access_review.store') }}">
            @csrf
            <div class="form-group mb-3">
                <label class="form-label form-label-required" for="ar_email">{{ __('security_ops.ar_lbl_email') }}</label>
                <input id="ar_email" type="email" name="email" required class="form-control" value="{{ old('email') }}">
            </div>
            <div class="form-group mb-3">
                <label class="form-label form-label-required" for="ar_reason">{{ __('security_ops.ar_lbl_reason') }}</label>
                <textarea id="ar_reason" name="reason" rows="2" required class="form-control">{{ old('reason') }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="play"></i> {{ __('security_ops.ar_btn_initiate') }}</button>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-body panel-body--flush">
        @if($reviews->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="shield-check"></i></div>
                <h3>{{ __('security_ops.ar_empty') }}</h3>
            </div>
        @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('security_ops.ar_col_subject') }}</th>
                    <th>{{ __('security_ops.ar_col_reason') }}</th>
                    <th>{{ __('security_ops.ar_col_status') }}</th>
                    <th>{{ __('security_ops.ar_col_outcome') }}</th>
                    <th>{{ __('security_ops.ar_col_when') }}</th>
                    <th>{{ __('security_ops.ar_col_actions') }}</th>
                </tr></thead>
                <tbody>
                @foreach($reviews as $review)
                    <tr>
                        <td data-label="{{ __('security_ops.ar_col_subject') }}" class="td-strong">{{ $subjects[$review->reviewed_subject_id]->email ?? substr($review->reviewed_subject_id, 0, 12).'…' }}</td>
                        <td data-label="{{ __('security_ops.ar_col_reason') }}">{{ \Illuminate\Support\Str::limit($review->findings ?? '—', 80) }}</td>
                        <td data-label="{{ __('security_ops.ar_col_status') }}"><span class="{{ $review->statusBadgeClass() }} badge-sm">{{ __('security_ops.ar_status_'.$review->status) }}</span></td>
                        <td data-label="{{ __('security_ops.ar_col_outcome') }}">{{ $review->outcome ? __('security_ops.ar_outcome_'.$review->outcome) : '—' }}</td>
                        <td data-label="{{ __('security_ops.ar_col_when') }}" class="td-muted">{{ ($review->completed_at ?? $review->created_at)?->format('d/m/Y H:i') }}</td>
                        <td data-label="{{ __('security_ops.ar_col_actions') }}">
                            @unless($review->isComplete())
                            <form method="POST" action="{{ route('portals.admin.security.access_review.complete', $review->id) }}" class="inline-form">
                                @csrf
                                <select name="outcome" class="filter-select" aria-label="{{ __('security_ops.ar_col_outcome') }}">
                                    @foreach($outcomes as $o)<option value="{{ $o }}">{{ __('security_ops.ar_outcome_'.$o) }}</option>@endforeach
                                </select>
                                <input type="text" name="notes" class="form-control" placeholder="{{ __('security_ops.ar_ph_notes') }}" aria-label="{{ __('security_ops.ar_ph_notes') }}">
                                <button type="submit" class="btn btn-secondary btn-sm">{{ __('security_ops.ar_btn_complete') }}</button>
                            </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
<div class="mt-4">{{ $reviews->links() }}</div>
@endsection
