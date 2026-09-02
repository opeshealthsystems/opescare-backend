@extends('layouts.portal')

@section('title', ($thread->title ?: __('messaging.conversation')) . ' — OpesCare')

@section('breadcrumb_home', __('messaging.staff_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('messaging.thread_breadcrumb'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $thread->title ?: __('messaging.conversation') }}</h1>
        <p class="page-subtitle">
            <span class="badge badge-{{ $thread->status === 'closed' ? 'muted' : 'success' }}">
                {{ $thread->status === 'closed' ? __('messaging.status_closed') : __('messaging.status_open') }}
            </span>
            @if($patient)
                <span style="margin-left:.5rem;">
                    <i data-lucide="user" style="width:.85rem;height:.85rem;vertical-align:middle;"></i>
                    {{ __('messaging.patient_label') }}: {{ trim($patient->first_name . ' ' . $patient->last_name) }}
                    @if($patient->health_id)<span style="opacity:.7;">· {{ $patient->health_id }}</span>@endif
                </span>
            @endif
        </p>
    </div>
    <div>
        <a href="{{ route('portals.staff.messages') }}" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i> {{ __('messaging.thread_breadcrumb') }}
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
<div class="alert alert-danger mb-4"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>
@endif

<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="messages-square"></i> {{ __('messaging.conversation') }}</h3>
    </div>
    <div class="panel-body">
        @if($messages->isEmpty())
            <div class="empty-state">
                <i data-lucide="message-square"></i>
                <p>{{ __('messaging.no_messages') }}</p>
            </div>
        @else
            <div style="display:grid;gap:1rem;">
                @foreach($messages as $msg)
                    @php $mine = $msg['sender_id'] === $userId; @endphp
                    <div style="display:flex;{{ $mine ? 'justify-content:flex-end;' : 'justify-content:flex-start;' }}">
                        <div class="panel" style="margin:0;max-width:75%;{{ $mine ? 'border-left:4px solid #0F4C81;' : '' }}">
                            <div class="panel-body" style="padding:.75rem 1rem;">
                                <div style="font-weight:700;font-size:.85rem;margin-bottom:.25rem;">
                                    {{ $mine ? __('messaging.you') : __('messaging.from_patient') }}
                                </div>
                                <div style="white-space:pre-wrap;">{{ $msg['body'] }}</div>
                                @if($msg['sent_at'])
                                    <div class="page-subtitle" style="font-size:.75rem;margin-top:.4rem;">
                                        {{ __('messaging.sent_at') }} {{ $msg['sent_at']->isoFormat('LLL') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<div class="panel">
    <div class="panel-body">
        @if($thread->status === 'closed')
            <div class="alert alert-info" style="margin:0;">
                <i data-lucide="lock"></i><div>{{ __('messaging.closed_notice') }}</div>
            </div>
        @else
            <form method="POST" action="{{ route('portals.staff.messages.send', $thread->uuid) }}">
                @csrf
                <label class="form-label" for="body">{{ __('messaging.reply_label') }}</label>
                <textarea id="body" name="body" class="form-control" rows="3" maxlength="5000"
                          placeholder="{{ __('messaging.reply_placeholder') }}" required>{{ old('body') }}</textarea>
                @error('body')
                    <p class="page-subtitle" style="color:#b91c1c;margin:.35rem 0 0;">{{ $message }}</p>
                @enderror
                <div style="margin-top:.75rem;">
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="send"></i> {{ __('messaging.send') }}
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>

@endsection
