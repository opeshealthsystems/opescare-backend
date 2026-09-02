@extends('layouts.portal')

@section('title', __('public.pat_sub_pending_title') . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.pat_sub_pending_title'))

@section('content')

<div class="panel" style="max-width:560px;margin-top:1rem;">
    <div class="panel-body" style="text-align:center;padding:2.5rem 1.5rem;">
        <div id="state-pending">
            <div class="spinner" style="width:44px;height:44px;border:4px solid #e2e8f0;border-top-color:#0F4C81;border-radius:50%;margin:0 auto 1.25rem;animation:ocspin .9s linear infinite;"></div>
            <h2 style="font-size:1.25rem;font-weight:800;margin:0 0 .5rem;">{{ __('public.pat_sub_pending_title') }}</h2>
            <p class="page-subtitle" style="margin:0 auto;max-width:420px;">{{ __('public.pat_sub_pending_body') }}</p>
        </div>

        <div id="state-success" style="display:none;">
            <i data-lucide="check-circle" style="width:48px;height:48px;color:#16a34a;"></i>
            <h2 style="font-size:1.25rem;font-weight:800;margin:.75rem 0 .5rem;">{{ __('public.pat_sub_pending_success') }}</h2>
            <p class="page-subtitle">{{ __('public.pat_sub_pending_redirect') }}</p>
        </div>

        <div id="state-failed" style="display:none;">
            <i data-lucide="x-circle" style="width:48px;height:48px;color:#dc2626;"></i>
            <h2 style="font-size:1.25rem;font-weight:800;margin:.75rem 0 .5rem;">{{ __('public.pat_sub_pending_failed') }}</h2>
            <p class="page-subtitle" style="margin-bottom:1rem;">{{ __('public.pat_sub_pending_failed_body') }}</p>
            <a href="{{ route('portals.patient.subscription') }}" class="btn btn-primary">{{ __('public.pat_sub_pending_back') }}</a>
        </div>
    </div>
</div>

<style>@keyframes ocspin{to{transform:rotate(360deg)}}</style>

<script>
(function () {
    var statusUrl = @json(route('portals.patient.subscription.status', ['subscription' => $subscription->id]));
    var doneUrl   = @json(route('portals.patient.subscription'));
    var attempts  = 0, maxAttempts = 40; // ~2 min at 3s

    function show(id) {
        ['pending', 'success', 'failed'].forEach(function (s) {
            document.getElementById('state-' + s).style.display = (s === id) ? '' : 'none';
        });
        if (window.lucide) { window.lucide.createIcons(); }
    }

    function poll() {
        attempts++;
        fetch(statusUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.status === 'successful') {
                    show('success');
                    setTimeout(function () { window.location.href = doneUrl; }, 1800);
                } else if (d.status === 'failed' || attempts >= maxAttempts) {
                    show('failed');
                } else {
                    setTimeout(poll, 3000);
                }
            })
            .catch(function () {
                if (attempts >= maxAttempts) { show('failed'); }
                else { setTimeout(poll, 3000); }
            });
    }

    setTimeout(poll, 2500);
})();
</script>

@endsection
