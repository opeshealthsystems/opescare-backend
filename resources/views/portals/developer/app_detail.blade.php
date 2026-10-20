@extends('layouts.portal')
@section('title', ($client->name ?? __('public.developer_portal.lbl_unnamed_app', [], app()->getLocale())) . ' — ' . __('public.developer_portal.lbl_view', [], app()->getLocale()))
@php $l = app()->getLocale(); @endphp

@section('head')
    <link rel="stylesheet" href="{{ asset('css/od-pro.css') }}?v={{ @filemtime(public_path('css/od-pro.css')) ?: '1' }}">
@endsection

@section('content')

    <div class="breadcrumb">
        <a href="{{ route('portals.developer.apps') }}">{{ __('public.developer_portal.lnk_my_apps', [], $l) }}</a>
        <i data-lucide="chevron-right"></i>
        <span>{{ $client->name ?? __('public.developer_portal.lbl_unnamed_app', [], $l) }}</span>
    </div>

    <div class="entity-head">
        <div class="entity-head__icon"><i data-lucide="plug"></i></div>
        <div>
            <h2 class="entity-head__title">{{ $client->name ?? __('public.developer_portal.lbl_unnamed_app', [], $l) }}</h2>
            <div class="entity-head__sub">
                <span class="badge {{ ($client->environment ?? 'sandbox') === 'production' ? 'badge-success' : 'badge-info' }}">@enum($client->environment ?? 'sandbox', 'environment')</span>
                <span class="badge {{ ($client->status ?? 'active') === 'active' ? 'badge-success' : 'badge-neutral' }}">@enum($client->status ?? 'active')</span>
            </div>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif

    @if(session('new_client_secret'))
    <div class="panel mb-6">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="key"></i> {{ __('public.developer_portal.panel_save_credentials', [], $l) }}</h3></div>
        <div class="panel-body">
            @if(session('new_client_id'))<div class="code-block mb-6"><strong>{{ __('public.developer_portal.lbl_client_id', [], $l) }}:</strong> <span class="code-token" data-copy="{{ session('new_client_id') }}">{{ session('new_client_id') }}</span></div>@endif
            <div class="code-block"><strong>{{ __('public.developer_portal.lbl_client_secret', [], $l) }}:</strong> <span class="code-token" data-copy="{{ session('new_client_secret') }}">{{ session('new_client_secret') }}</span></div>
        </div>
    </div>
    @endif

    {{-- App management: rotate secret / enable-disable --}}
    <div class="panel mb-6">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="settings"></i> {{ __('public.developer_portal.panel_manage', [], $l) }}</h3></div>
        <div class="panel-body ods-669a495">
            <form method="POST" action="{{ route('portals.developer.apps.rotate', $client->id) }}"
                  data-confirm="{{ __('public.developer_portal.rotate_confirm', [], $l) }}">
                @csrf
                <button type="submit" class="btn btn-secondary"><i data-lucide="refresh-cw"></i> {{ __('public.developer_portal.btn_rotate', [], $l) }}</button>
            </form>
            <form method="POST" action="{{ route('portals.developer.apps.toggle', $client->id) }}"
                  data-confirm="{{ __('public.developer_portal.toggle_confirm', [], $l) }}">
                @csrf
                @if(($client->status ?? 'active') === 'active')
                <button type="submit" class="btn btn-ghost btn-danger"><i data-lucide="power"></i> {{ __('public.developer_portal.btn_disable', [], $l) }}</button>
                @else
                <button type="submit" class="btn btn-primary"><i data-lucide="power"></i> {{ __('public.developer_portal.btn_enable', [], $l) }}</button>
                @endif
            </form>
        </div>
    </div>

    {{-- Plan & usage (metered API plan / EnforceApiQuota) --}}
    @php
        $quota = $plan?->monthly_request_quota;
        $pct = $quota ? min(100, (int) round($monthlyUsage / max(1, $quota) * 100)) : null;
        $barColor = $pct === null ? '#0F4C81' : ($pct >= 90 ? '#ef4444' : ($pct >= 70 ? '#f59e0b' : '#10b981'));
    @endphp
    <div class="panel mb-6">
        <div class="panel-header ods-19cba2d">
            <h3 class="panel-title"><i data-lucide="gauge"></i> {{ __('public.developer_portal.panel_plan_usage', [], $l) }}</h3>
            <a href="{{ route('public.developers.pricing') }}" class="btn btn-ghost ods-29f1e9b"><i data-lucide="arrow-up-right"></i> {{ __('public.developer_portal.btn_view_plans', [], $l) }}</a>
        </div>
        <div class="panel-body">
            <div class="ods-c3a81a0">
                <span class="ods-ea75d0f">{{ $plan?->name ?? 'Sandbox' }}</span>
                <span class="td-muted ods-29f1e9b">· {{ number_format($plan?->rate_limit_per_min ?? 60, 0, ',', ' ') }} {{ __('public.developer_portal.lbl_req_min', [], $l) }} · {{ __('public.developer_portal.lbl_quota_reset', [], $l) }}</span>
            </div>
            <div class="ods-56d1f4c">
                <span>{{ __('public.developer_portal.lbl_this_month_usage', [], $l) }}: <strong class="ods-8998dc7">{{ number_format($monthlyUsage, 0, ',', ' ') }}</strong></span>
                <span>{{ __('public.developer_portal.lbl_quota', [], $l) }}: <strong class="ods-8998dc7">{{ $quota ? number_format($quota, 0, ',', ' ') : (__('public.developer_portal.lbl_unlimited', [], $l)) }}</strong></span>
            </div>
            @if($pct !== null)
            <div class="ods-8c5a8cb">
                <div class="odpro-bar odpro-bar--{{ $pct === null ? 'blue' : ($pct >= 90 ? 'red' : ($pct >= 70 ? 'amber' : 'green')) }} ods-w-{{ max(0, min(100, (int) $pct)) }}"></div>
            </div>
            @endif
        </div>
    </div>

    {{-- Billing & invoices (ApiBillingService / MoMo + Orange) --}}
    @php
        $unpaid = ($invoices ?? collect())->first(fn ($inv) => ! in_array($inv->status, ['paid', 'void'], true));
        $invStatusStyle = ['issued' => ['#b45309', '#fffbeb'], 'paid' => ['#047857', '#ecfdf5'], 'overdue' => ['#b91c1c', '#fef2f2'], 'void' => ['#64748b', '#f1f5f9']];
    @endphp
    <div class="panel mb-6">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="receipt"></i> {{ __('public.developer_portal.panel_billing', [], $l) }}</h3></div>
        <div class="panel-body">
            @if(($invoices ?? collect())->isEmpty())
                <p class="td-muted ods-1386d50">{{ __('public.developer_portal.lbl_no_invoices', [], $l) }}</p>
            @else
                <table class="kv-table ods-6d56738">
                    <thead><tr>
                        <th>{{ __('public.developer_portal.lbl_invoice', [], $l) }}</th>
                        <th>{{ __('public.developer_portal.lbl_period', [], $l) }}</th>
                        <th>{{ __('public.developer_portal.lbl_amount', [], $l) }}</th>
                        <th>{{ __('public.developer_portal.lbl_status', [], $l) }}</th>
                    </tr></thead>
                    <tbody>
                    @foreach($invoices as $inv)
                        @php $iss = $invStatusStyle[$inv->status] ?? ['#64748b', '#f1f5f9']; @endphp
                        <tr>
                            <td class="mono">{{ $inv->number() }}</td>
                            <td>{{ optional($inv->period_start)->translatedFormat('M Y') }}</td>
                            <td><strong>{{ number_format($inv->total_xaf, 0, ',', ' ') }} FCFA</strong></td>
                            <td><span class="badge {{ ['issued' => 'badge-warning', 'paid' => 'badge-success', 'overdue' => 'badge-danger'][$inv->status] ?? 'badge-neutral' }}">{{ __('public.developer_portal.st_' . $inv->status, [], $l) ?: ucfirst($inv->status) }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

                @if($unpaid && ! \App\Support\Payments::onlineAvailable())
                <p class="ods-bf6e0d2" data-pay-offline><i data-lucide="info"></i> {{ __('public.developer_portal.pay_offline_note', [], $l) }}</p>
                @elseif($unpaid)
                <form class="ods-e3677aa" method="POST" action="{{ route('portals.developer.apps.pay', $client->id) }}">
                    @csrf
                    <input type="hidden" name="invoice_id" value="{{ $unpaid->id }}">
                    <label class="ods-bf6e0d2">{{ __('public.developer_portal.lbl_pay_invoice', [], $l) }} {{ $unpaid->number() }} · {{ number_format($unpaid->total_xaf, 0, ',', ' ') }} FCFA</label>
                    <div class="ods-2e6944a">
                        <select name="provider" class="form-control ods-0f15e8e">
                            <option value="mtn_momo">{{ __('public.developer_portal.opt_mtn', [], $l) }}</option>
                            <option value="orange_money">{{ __('public.developer_portal.opt_orange', [], $l) }}</option>
                        </select>
                        <input type="text" name="phone" class="form-control ods-0f15e8e" placeholder="6XXXXXXXX" required>
                        <button type="submit" class="btn btn-primary"><i data-lucide="smartphone"></i> {{ __('public.developer_portal.btn_pay', [], $l) }}</button>
                    </div>
                </form>
                @endif
            @endif
        </div>
    </div>

    <div class="field-grid mb-6">

        {{-- Credentials & Config --}}
        <div>
            <div class="panel mb-6">
                <div class="panel-header"><h3 class="panel-title"><i data-lucide="key"></i> {{ __('public.developer_portal.panel_credentials', [], $l) }}</h3></div>
                <div class="panel-body">
                    <table class="kv-table">
                        <tr><th>{{ __('public.developer_portal.lbl_client_id', [], $l) }}</th><td><span class="code-token" data-copy="{{ $client->client_id }}">{{ $client->client_id }}</span></td></tr>
                        <tr><th>{{ __('public.developer_portal.lbl_secret', [], $l) }}</th><td class="td-muted mono">{{ __('public.developer_portal.lbl_secret_masked', [], $l) }}</td></tr>
                        <tr><th>{{ __('public.developer_portal.lbl_environment', [], $l) }}</th><td>@enum($client->environment ?? 'sandbox', 'environment')</td></tr>
                        <tr><th>{{ __('public.developer_portal.lbl_scopes', [], $l) }}</th><td>
                            @foreach(json_decode($client->scopes ?? '[]', true) ?? [] as $scope)
                            <span class="code-token">{{ $scope }}</span>
                            @endforeach
                        </td></tr>
                        <tr><th>{{ __('public.developer_portal.lbl_created', [], $l) }}</th><td class="td-muted">{{ $client->created_at->translatedFormat('d M Y H:i') }}</td></tr>
                    </table>
                </div>
            </div>

            {{-- Integration Certification --}}
            @if($certification)
            <div class="panel">
                <div class="panel-header"><h3 class="panel-title"><i data-lucide="award"></i> {{ __('public.developer_portal.panel_integration_cert', [], $l) }}</h3></div>
                <div class="panel-body">
                    <div class="entity-head">
                        @if($certification->badge)
                        <div class="entity-head__icon"><i data-lucide="{{ $certification->badge->levelIcon() }}" class="odpro-cert odpro-cert--{{ in_array($certification->badge->certification_level, ['platinum', 'gold', 'silver'], true) ? $certification->badge->certification_level : 'bronze' }}"></i></div>
                        <div>
                            <div class="td-strong">@enum($certification->badge->certification_level, 'level') certified</div>
                            <div class="mono">{{ $certification->badge->badge_code }}</div>
                        </div>
                        @else
                        <div>
                            <span class="{{ $certification->statusBadgeClass() }}">@enum($certification->status)</span>
                            <div class="td-muted">{{ __('public.developer_portal.lbl_cert_in_progress', [], $l) }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Usage & Webhooks --}}
        <div>

            {{-- 30-day usage --}}
            <div class="panel mb-6">
                <div class="panel-header"><h3 class="panel-title"><i data-lucide="bar-chart-3"></i> {{ __('public.developer_portal.panel_api_usage', [], $l) }}</h3></div>
                @if(empty($usageSummary))
                <div class="panel-body empty-state"><p>{{ __('public.developer_portal.no_usage_yet', [], $l) }}</p></div>
                @else
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead><tr>
                            <th>{{ __('public.developer_portal.col_endpoint_group', [], $l) }}</th>
                            <th>{{ __('public.developer_portal.col_requests', [], $l) }}</th>
                            <th>{{ __('public.developer_portal.col_errors', [], $l) }}</th>
                        </tr></thead>
                        <tbody>
                        @foreach($usageSummary as $group => $stats)
                        <tr>
                            <td data-label="{{ __('public.developer_portal.col_endpoint_group', [], $l) }}" class="mono">{{ $group }}</td>
                            <td data-label="{{ __('public.developer_portal.col_requests', [], $l) }}">{{ number_format($stats['total_requests']) }}</td>
                            <td data-label="{{ __('public.developer_portal.col_errors', [], $l) }}">
                                @if($stats['total_errors'] > 0)<span class="badge badge-danger">{{ number_format($stats['total_errors']) }}</span>
                                @else<span class="badge badge-success">0</span>@endif
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

            {{-- Webhook Subscriptions --}}
            <div class="panel">
                <div class="panel-header">
                    <h3 class="panel-title"><i data-lucide="webhook"></i> {{ __('public.developer_portal.panel_webhook_subs', [], $l) }}</h3>
                    <a href="{{ route('portals.developer.webhook_deliveries', $client->id) }}" class="btn btn-ghost btn-sm">{{ __('public.developer_portal.btn_delivery_logs', [], $l) }}</a>
                </div>
                @if($webhooks->isEmpty())
                <div class="panel-body empty-state"><p>{{ __('public.developer_portal.no_webhook_subs', [], $l) }}</p></div>
                @else
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead><tr>
                            <th>{{ __('public.developer_portal.col_endpoint', [], $l) }}</th>
                            <th>{{ __('public.developer_portal.col_events', [], $l) }}</th>
                            <th>{{ __('public.portal.col_status', [], $l) }}</th>
                        </tr></thead>
                        <tbody>
                        @foreach($webhooks as $wh)
                        <tr>
                            <td data-label="{{ __('public.developer_portal.col_endpoint', [], $l) }}" class="mono">{{ $wh->callback_url }}</td>
                            <td data-label="{{ __('public.developer_portal.col_events', [], $l) }}" class="td-muted">{{ count((array)$wh->subscribed_events) }} {{ __('public.developer_portal.col_events', [], $l) }}</td>
                            <td data-label="{{ __('public.portal.col_status', [], $l) }}"><span class="badge {{ $wh->status === 'active' ? 'badge-success' : 'badge-neutral' }}">{{ $wh->status }}</span></td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

        </div>
    </div>

@endsection
@section('scripts')
<script @cspNonce>
document.addEventListener('click', function(e){
    var t = e.target.closest('.code-token[data-copy]');
    if(!t) return;
    navigator.clipboard && navigator.clipboard.writeText(t.getAttribute('data-copy'));
    var prev = t.textContent; t.textContent = @json(__('dev_extra.copied', [], $l)); setTimeout(function(){ t.textContent = prev; }, 1200);
});
</script>
@endsection
