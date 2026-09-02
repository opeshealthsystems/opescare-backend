@extends('layouts.portal')
@section('title', __('public.adm_connect_widget_title'))
@section('sidebar') @include('portals.admin.connect._sidebar') @endsection

@section('content')

<div class="page-head">
    <h2><i data-lucide="layout-panel-left"></i> {{ __('public.adm_connect_widget_heading') }}</h2>
    <div class="page-head__spacer"></div>
</div>
<p class="td-muted mb-6">{{ __('public.adm_connect_widget_subtitle') }}</p>

<div class="field-grid">

    {{-- Configuration Panel --}}
    <div class="panel">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="settings"></i> {{ __('public.adm_connect_widget_config_title') }}</h3></div>
        <div class="panel-body">
            <div class="form-group">
                <label class="form-label">{{ __('public.adm_connect_widget_lbl_type') }}</label>
                <select id="widgetType" class="form-control" onchange="regenerate()">
                    <option value="appointment">{{ __('public.adm_connect_widget_opt_appointment') }}</option>
                    <option value="health_id_verify">{{ __('public.adm_connect_widget_opt_health_id') }}</option>
                    <option value="patient_summary">{{ __('public.adm_connect_widget_opt_patient_summary') }}</option>
                    <option value="queue_status">{{ __('public.adm_connect_widget_opt_queue') }}</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('public.adm_connect_widget_lbl_client') }}</label>
                <select id="clientId" class="form-control" onchange="regenerate()">
                    <option value="">{{ __('public.adm_connect_widget_select_client_ph') }}</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->client_id }}">{{ $client->name }} ({{ $client->environment }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('public.adm_connect_widget_lbl_facility') }}</label>
                <input type="text" id="facilityId" class="form-control" placeholder="{{ __('public.adm_connect_widget_ph_facility') }}" oninput="regenerate()">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('public.adm_connect_widget_lbl_theme') }}</label>
                <select id="widgetTheme" class="form-control" onchange="regenerate()">
                    <option value="light">{{ __('public.adm_connect_widget_opt_light') }}</option>
                    <option value="dark">{{ __('public.adm_connect_widget_opt_dark') }}</option>
                    <option value="auto">{{ __('public.adm_connect_widget_opt_auto') }}</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('public.adm_connect_widget_lbl_lang') }}</label>
                <select id="widgetLang" class="form-control" onchange="regenerate()">
                    <option value="en">English</option>
                    <option value="fr">French</option>
                    <option value="ha">Hausa</option>
                    <option value="yo">Yoruba</option>
                    <option value="ig">Igbo</option>
                    <option value="sw">Swahili</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('public.adm_connect_widget_lbl_color') }}</label>
                <input type="color" id="widgetColor" class="form-control" value="#0891b2" onchange="regenerate()">
            </div>
        </div>
    </div>

    {{-- Preview & Code --}}
    <div>
        <div class="panel mb-6">
            <div class="panel-header">
                <h3 class="panel-title"><i data-lucide="code-2"></i> {{ __('public.adm_connect_widget_script_title') }}</h3>
                <button class="btn btn-ghost btn-sm" onclick="copyCode('scriptCode')"><i data-lucide="copy"></i> {{ __('public.adm_connect_widget_btn_copy') }}</button>
            </div>
            <pre id="scriptCode" class="code-block"></pre>
        </div>

        <div class="panel mb-6">
            <div class="panel-header">
                <h3 class="panel-title"><i data-lucide="code"></i> {{ __('public.adm_connect_widget_embed_title') }}</h3>
                <button class="btn btn-ghost btn-sm" onclick="copyCode('embedCode')"><i data-lucide="copy"></i> {{ __('public.adm_connect_widget_btn_copy') }}</button>
            </div>
            <pre id="embedCode" class="code-block"></pre>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h3 class="panel-title"><i data-lucide="braces"></i> {{ __('public.adm_connect_widget_js_title') }}</h3>
                <button class="btn btn-ghost btn-sm" onclick="copyCode('jsCode')"><i data-lucide="copy"></i> {{ __('public.adm_connect_widget_btn_copy') }}</button>
            </div>
            <pre id="jsCode" class="code-block"></pre>
        </div>
    </div>

</div>

{{-- Documentation --}}
<div class="panel mt-6">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="book-open"></i> {{ __('public.adm_connect_widget_guide_title') }}</h3></div>
    <div class="panel-body">
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-card__head"><i data-lucide="check-circle"></i> <span class="kv-strong">{{ __('public.adm_connect_widget_step1_title') }}</span></div>
                <p class="td-muted">{{ __('public.adm_connect_widget_step1_desc') }}</p>
            </div>
            <div class="stat-card">
                <div class="stat-card__head"><i data-lucide="code"></i> <span class="kv-strong">{{ __('public.adm_connect_widget_step2_title') }}</span></div>
                <p class="td-muted">{{ __('public.adm_connect_widget_step2_desc') }}</p>
            </div>
            <div class="stat-card">
                <div class="stat-card__head"><i data-lucide="key"></i> <span class="kv-strong">{{ __('public.adm_connect_widget_step3_title') }}</span></div>
                <p class="td-muted">{{ __('public.adm_connect_widget_step3_desc') }}</p>
            </div>
            <div class="stat-card">
                <div class="stat-card__head"><i data-lucide="shield-check"></i> <span class="kv-strong">{{ __('public.adm_connect_widget_security_title') }}</span></div>
                <p class="td-muted">{{ __('public.adm_connect_widget_security_desc') }}</p>
            </div>
        </div>
    </div>
</div>

@endsection
@section('scripts')
<script>
const BASE_URL = '{{ $baseUrl }}';
var _ag = {
    copied: @json(__('public.adm_connect_widget_js_copied')),
};

function regenerate() {
    const type      = document.getElementById('widgetType').value;
    const clientId  = document.getElementById('clientId').value || 'YOUR_CLIENT_ID';
    const facility  = document.getElementById('facilityId').value || 'YOUR_FACILITY_ID';
    const theme     = document.getElementById('widgetTheme').value;
    const lang      = document.getElementById('widgetLang').value;
    const color     = document.getElementById('widgetColor').value;

    // Script tag
    document.getElementById('scriptCode').textContent =
`<script src="${BASE_URL}/widget/v1/loader.js"
  data-client="${clientId}"
  data-env="${clientId.startsWith('sk_live') ? 'production' : 'sandbox'}"
  async><\/script>`;

    // Embed tag
    document.getElementById('embedCode').textContent =
`<opescare-widget
  type="${type}"
  facility-id="${facility}"
  theme="${theme}"
  lang="${lang}"
  primary-color="${color}">
</opescare-widget>`;

    // JS init
    document.getElementById('jsCode').textContent =
`// Called after user authenticates on your platform
window.OpesCareWidget?.init({
  clientId:   '${clientId}',
  facilityId: '${facility}',
  widgetType: '${type}',
  theme:      '${theme}',
  lang:       '${lang}',
  primaryColor: '${color}',
  onReady:    () => console.log('OpesCare widget ready'),
  onEvent:    (e) => console.log('Widget event:', e),
});

// Pass a server-generated session token for authenticated actions
// OpesCareWidget.setToken('<SESSION_TOKEN_FROM_YOUR_SERVER>');`;
}

function copyCode(id) {
    const text = document.getElementById(id).textContent;
    navigator.clipboard.writeText(text).then(() => {
        const btn = event.currentTarget;
        const orig = btn.innerHTML;
        btn.innerHTML = '<i data-lucide="check"></i> ' + _ag.copied;
        if (typeof lucide !== 'undefined') lucide.createIcons();
        setTimeout(() => { btn.innerHTML = orig; if (typeof lucide !== 'undefined') lucide.createIcons(); }, 2000);
    });
}

document.addEventListener('DOMContentLoaded', regenerate);
</script>
@endsection
