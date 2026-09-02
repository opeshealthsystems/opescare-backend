@extends('layouts.portal')

@section('title', __('messaging.inbox_title') . ' — OpesCare')

@section('breadcrumb_home', __('messaging.staff_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('messaging.thread_breadcrumb'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('messaging.inbox_title') }}</h1>
        <p class="page-subtitle">{{ __('messaging.staff_inbox_subtitle') }}</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
<div class="alert alert-danger mb-4"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>
@endif

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="messages-square"></i> {{ __('messaging.panel_title') }}</h3>
    </div>
    <div class="panel-body">
        @if($threads->isEmpty())
            <div class="empty-state">
                <i data-lucide="message-square"></i>
                <h3>{{ __('messaging.empty_title') }}</h3>
                <p>{{ __('messaging.staff_empty_body') }}</p>
            </div>
        @else
            <ul style="list-style:none;padding:0;margin:0;display:grid;gap:.5rem;">
                @foreach($threads as $row)
                    <li>
                        <a href="{{ route('portals.staff.messages.show', $row['uuid']) }}"
                           class="panel"
                           style="display:block;text-decoration:none;color:inherit;margin:0;padding:1rem;border-left:4px solid {{ $row['unread'] ? '#0F4C81' : 'transparent' }};">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;">
                                <div style="min-width:0;">
                                    <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;">
                                        <strong style="font-size:1.05rem;">{{ $row['title'] ?: __('messaging.no_subject') }}</strong>
                                        @if($row['unread'])
                                            <span class="badge badge-primary">{{ __('messaging.unread') }}</span>
                                        @endif
                                        <span class="badge badge-{{ $row['status'] === 'closed' ? 'muted' : 'success' }}">
                                            {{ $row['status'] === 'closed' ? __('messaging.status_closed') : __('messaging.status_open') }}
                                        </span>
                                    </div>
                                    @if($row['patient_name'])
                                        <p class="page-subtitle" style="margin:.35rem 0 0;">
                                            <i data-lucide="user" style="width:.85rem;height:.85rem;vertical-align:middle;"></i>
                                            {{ __('messaging.patient_label') }}: {{ $row['patient_name'] }}
                                            @if($row['health_id'])<span style="opacity:.7;">· {{ $row['health_id'] }}</span>@endif
                                        </p>
                                    @endif
                                    @if($row['snippet'])
                                        <p class="page-subtitle" style="margin:.35rem 0 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $row['snippet'] }}</p>
                                    @endif
                                </div>
                                <div style="text-align:right;white-space:nowrap;">
                                    @if($row['last_at'])
                                        <span class="page-subtitle" style="font-size:.8rem;">{{ $row['last_at']->isoFormat('LLL') }}</span>
                                    @endif
                                    <div><i data-lucide="chevron-right"></i></div>
                                </div>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

@endsection
