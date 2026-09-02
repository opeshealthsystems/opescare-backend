@extends('layouts.portal')

@section('title', __('messaging.compose_title') . ' — OpesCare')

@section('breadcrumb_home', __('messaging.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('messaging.compose_title'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('messaging.compose_title') }}</h1>
        <p class="page-subtitle">{{ __('messaging.compose_subtitle') }}</p>
    </div>
    <a href="{{ route('portals.patient.messages') }}" class="btn btn-secondary">
        <i data-lucide="arrow-left"></i> {{ __('messaging.thread_breadcrumb') }}
    </a>
</div>

@if(session('error'))
<div class="alert alert-danger mb-4"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>
@endif

@unless($hasTeam)
<div class="alert alert-info mb-4"><i data-lucide="info"></i><div>{{ __('messaging.no_care_team') }}</div></div>
@endunless

<div class="panel" style="max-width:680px;">
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.patient.messages.store') }}">
            @csrf

            <div style="margin-bottom:1rem;">
                <label class="form-label" for="title">{{ __('messaging.compose_subject') }}</label>
                <input type="text" id="title" name="title" class="form-control" maxlength="150"
                       value="{{ old('title') }}" required>
                @error('title')<p class="page-subtitle" style="color:#b91c1c;margin:.35rem 0 0;">{{ $message }}</p>@enderror
            </div>

            <div style="margin-bottom:1rem;">
                <label class="form-label" for="context_type">{{ __('messaging.compose_about') }}</label>
                <select id="context_type" name="context_type" class="form-control" onchange="ocFilterCtx(this.value)">
                    <option value="">{{ __('messaging.compose_about_none') }}</option>
                    <option value="appointment">{{ __('messaging.ctx_appt') }}</option>
                    <option value="lab_result">{{ __('messaging.ctx_lab') }}</option>
                    <option value="prescription">{{ __('messaging.ctx_rx') }}</option>
                    <option value="visit">{{ __('messaging.ctx_visit') }}</option>
                    <option value="insurance_policy">{{ __('messaging.ctx_insurance') }}</option>
                </select>
                <p class="page-subtitle" style="margin:.4rem 0 0;font-size:.82rem;">{{ __('messaging.compose_about_hint') }}</p>
            </div>

            <div id="ctx-item-wrap" style="margin-bottom:1rem;display:none;">
                <label class="form-label" for="context_id">{{ __('messaging.compose_select_item') }}</label>
                <select id="context_id" name="context_id" class="form-control">
                    <option value="">—</option>
                    @foreach($catalog as $type => $items)
                        @foreach($items as $item)
                            <option value="{{ $item['id'] }}" data-ctx="{{ $type }}" style="display:none;">{{ $item['label'] }}</option>
                        @endforeach
                    @endforeach
                </select>
            </div>

            <div style="margin-bottom:1rem;">
                <label class="form-label" for="body">{{ __('messaging.compose_message') }}</label>
                <textarea id="body" name="body" class="form-control" rows="5" maxlength="5000"
                          placeholder="{{ __('messaging.reply_placeholder') }}" required>{{ old('body') }}</textarea>
                @error('body')<p class="page-subtitle" style="color:#b91c1c;margin:.35rem 0 0;">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="btn btn-primary" {{ $hasTeam ? '' : 'disabled' }}>
                <i data-lucide="send"></i> {{ __('messaging.compose_send') }}
            </button>
        </form>
    </div>
</div>

<script>
function ocFilterCtx(type) {
    var wrap = document.getElementById('ctx-item-wrap');
    var sel  = document.getElementById('context_id');
    if (!type) { wrap.style.display = 'none'; sel.value = ''; return; }
    wrap.style.display = '';
    sel.value = '';
    Array.prototype.forEach.call(sel.options, function (o) {
        if (!o.value) { return; }
        o.style.display = (o.getAttribute('data-ctx') === type) ? '' : 'none';
    });
}
</script>

@endsection
