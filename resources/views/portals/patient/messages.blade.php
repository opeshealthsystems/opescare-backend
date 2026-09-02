@extends('layouts.portal')

@section('title', __('messaging.inbox_title') . ' — OpesCare')

@section('breadcrumb_home', __('messaging.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('messaging.thread_breadcrumb'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('messaging.inbox_title') }}</h1>
        <p class="page-subtitle">{{ __('messaging.inbox_subtitle') }}</p>
    </div>
    <a href="{{ route('portals.patient.messages.compose') }}" class="btn btn-primary">
        <i data-lucide="plus"></i> {{ __('messaging.compose_btn') }}
    </a>
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
                <p>{{ __('messaging.empty_body') }}</p>
            </div>
        @else
            <ul style="list-style:none;padding:0;margin:0;display:grid;gap:.5rem;">
                @foreach($threads as $row)
                    <li>
                        <a href="{{ route('portals.patient.messages.show', $row['uuid']) }}"
                           class="panel"
                           style="display:block;text-decoration:none;color:inherit;margin:0;padding:1rem;border-left:4px solid {{ $row['unread'] ? '#0F4C81' : 'transparent' }};">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;">
                                <div style="min-width:0;">
                                    <div style="display:flex;align-items:center;gap:.5rem;">
                                        <strong style="font-size:1.05rem;">{{ $row['title'] ?: __('messaging.no_subject') }}</strong>
                                        @if($row['unread'])
                                            <span class="badge badge-primary">{{ __('messaging.unread') }}</span>
                                        @endif
                                        <span class="badge badge-{{ $row['status'] === 'closed' ? 'muted' : 'success' }}">
                                            {{ $row['status'] === 'closed' ? __('messaging.status_closed') : __('messaging.status_open') }}
                                        </span>
                                        @if(!empty($row['context_type']))
                                            @php $ctxKey = ['lab_result'=>'lab','prescription'=>'rx','appointment'=>'appt','visit'=>'visit','insurance_policy'=>'insurance'][$row['context_type']] ?? 'generic'; @endphp
                                            <span class="badge"><i data-lucide="paperclip" style="width:.8rem;height:.8rem;"></i> {{ __('messaging.ctx_' . $ctxKey) }}</span>
                                        @endif
                                    </div>
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
