@extends('layouts.portal')
@section('title', __('governance_desk.log_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('governance_desk.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('governance_desk.log_title'))

@section('content')
<div class="page-head">
    <h2>{{ __('governance_desk.log_title') }}</h2>
    <a href="{{ route('portals.admin.governance.index') }}" class="btn btn-ghost btn-sm">
        <i data-lucide="arrow-left"></i> {{ __('governance_desk.back_to_queue') }}
    </a>
</div>
<p class="page-subtitle mb-6">{{ __('governance_desk.log_subtitle') }}</p>

<div class="panel">
    <div class="panel-body panel-body--flush">
        @if($events->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="scroll-text"></i></div>
                <h3>{{ __('governance_desk.empty_log_heading') }}</h3>
                <p>{{ __('governance_desk.empty_log_body') }}</p>
            </div>
        @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('governance_desk.col_when') }}</th>
                    <th>{{ __('governance_desk.col_action') }}</th>
                    <th>{{ __('governance_desk.col_actor') }}</th>
                    <th>{{ __('governance_desk.col_reason') }}</th>
                </tr></thead>
                <tbody>
                @foreach($events as $ev)
                    <tr>
                        <td>{{ optional($ev->created_at)->format('Y-m-d H:i') }}</td>
                        <td><code>{{ $ev->action_type }}</code></td>
                        <td>{{ $ev->actor_role ?: '—' }}</td>
                        <td>{{ $ev->reason }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $events->links() }}</div>
        @endif
    </div>
</div>
@endsection
