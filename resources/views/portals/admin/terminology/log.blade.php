@extends('layouts.portal')
@section('title', __('terminology.log_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('terminology.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('terminology.log_title'))

@section('content')
<div class="page-head">
    <h2>{{ __('terminology.log_title') }}</h2>
    <a href="{{ route('portals.admin.terminology.index') }}" class="btn btn-ghost btn-sm">
        <i data-lucide="arrow-left"></i> {{ __('terminology.btn_back') }}
    </a>
</div>
{{-- This is what makes an applied code answerable. Every row carries the
     mapping, the actor and the state the record was in before the write, so a
     code on a result can always be traced back — and put back. --}}
<p class="page-subtitle mb-6">{{ __('terminology.log_subtitle') }}</p>

<div class="panel">
    <div class="panel-body panel-body--flush">
        @if($events->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="scroll-text"></i></div>
                <h3>{{ __('terminology.log_empty_heading') }}</h3>
                <p>{{ __('terminology.log_empty_body') }}</p>
            </div>
        @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('terminology.col_when') }}</th>
                    <th>{{ __('terminology.col_action') }}</th>
                    <th>{{ __('terminology.col_actor') }}</th>
                    <th>{{ __('terminology.col_mapping') }}</th>
                    <th>{{ __('terminology.col_before') }}</th>
                    <th>{{ __('terminology.col_after') }}</th>
                </tr></thead>
                <tbody>
                @foreach($events as $ev)
                <tr>
                    <td data-label="{{ __('terminology.col_when') }}">{{ $ev->created_at?->toDayDateTimeString() }}</td>
                    <td data-label="{{ __('terminology.col_action') }}"><code>{{ $ev->action_type }}</code></td>
                    <td data-label="{{ __('terminology.col_actor') }}">{{ $ev->actor_role ?: '—' }}</td>
                    <td data-label="{{ __('terminology.col_mapping') }}">
                        @if($ev->resource_id)
                            <a href="{{ route('portals.admin.terminology.show', $ev->resource_id) }}">
                                <code>{{ substr($ev->resource_id, 0, 8) }}</code>
                            </a>
                        @else
                            —
                        @endif
                    </td>
                    <td data-label="{{ __('terminology.col_before') }}" class="td-muted">{{ json_encode($ev->before_state) }}</td>
                    <td data-label="{{ __('terminology.col_after') }}" class="td-muted">{{ json_encode($ev->after_state) }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

<div class="mt-4">{{ $events->links() }}</div>
@endsection
