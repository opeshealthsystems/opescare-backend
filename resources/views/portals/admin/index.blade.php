@extends('layouts.portal')

@section('title', __('public.admin_governance.page_meta_title', [], app()->getLocale()) ?: 'Admin Governance Portal — OpesCare')

@section('breadcrumb_home', __('public.admin_governance.breadcrumb_home', [], app()->getLocale()) ?: 'Admin Portal')
@section('breadcrumb_home_url', route('portals.admin'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.admin_governance.page_title', [], app()->getLocale()) ?: 'Admin Governance Portal' }}</h1>
        <p class="page-subtitle">{{ __('public.admin_governance.page_subtitle', [], app()->getLocale()) ?: 'Manage Health IDs, review duplicate cases, and monitor security events.' }}</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-secondary" onclick="document.getElementById('duplicates').scrollIntoView({behavior:'smooth'})">
            <i data-lucide="users"></i>
            {{ __('public.admin_governance.btn_duplicate_review', [], app()->getLocale()) ?: 'Duplicate Review' }}
        </button>
        <button class="btn btn-secondary" onclick="document.getElementById('partners').scrollIntoView({behavior:'smooth'})">
            <i data-lucide="building-2"></i>
            {{ __('public.admin_governance.btn_partners', [], app()->getLocale()) ?: 'Partners' }}
        </button>
    </div>
</div>

{{-- Platform overview stats.
     Platform-wide totals, and every card links into a platform-tier page.
     A facility admin is 403'd on all six by RequirePlatformAdmin, so for
     them this whole block was six dead links AND a set of cross-tenant
     counts they have no business seeing. --}}
@platformadmin
{{-- Platform overview stats --}}
<div class="stat-grid mb-6" style="--cols:4">
  <a href="{{ route('portals.admin.facilities.index') }}" class="stat-card">
    <div class="stat-card__icon"><i data-lucide="hospital"></i></div>
    <div class="stat-card__value">{{ $platformStats['facilities'] ?? '—' }}</div>
    <div class="stat-card__label">{{ __('public.admin_governance.stat_total_facilities', [], app()->getLocale()) ?: 'Total Facilities' }}</div>
  </a>
  <a href="{{ route('portals.admin.organizations.index') }}" class="stat-card">
    <div class="stat-card__icon"><i data-lucide="building-2"></i></div>
    <div class="stat-card__value">{{ $platformStats['organizations'] ?? '—' }}</div>
    <div class="stat-card__label">{{ __('public.admin_governance.stat_organizations', [], app()->getLocale()) ?: 'Organizations' }}</div>
  </a>
  <a href="{{ route('portals.admin.subscription') }}" class="stat-card">
    <div class="stat-card__icon"><i data-lucide="credit-card"></i></div>
    <div class="stat-card__value">{{ $platformStats['active_subscriptions'] ?? '—' }}</div>
    <div class="stat-card__label">{{ __('public.admin_governance.stat_active_subscriptions', [], app()->getLocale()) ?: 'Active Subscriptions' }}</div>
  </a>
  <a href="{{ route('portals.admin.support.index') }}" class="stat-card">
    <div class="stat-card__icon"><i data-lucide="life-buoy"></i></div>
    <div class="stat-card__value">{{ $platformStats['open_tickets'] ?? '—' }}</div>
    <div class="stat-card__label">{{ __('public.admin_governance.stat_open_tickets', [], app()->getLocale()) ?: 'Open Support Tickets' }}</div>
  </a>
</div>
<div class="stat-grid mb-6" style="--cols:3">
  <a href="{{ route('portals.admin.users.index') }}" class="stat-card">
    <div class="stat-card__icon"><i data-lucide="users"></i></div>
    <div class="stat-card__value">{{ $platformStats['total_users'] ?? '—' }}</div>
    <div class="stat-card__label">{{ __('public.admin_governance.stat_total_users', [], app()->getLocale()) ?: 'Total Users' }}</div>
  </a>
  <a href="{{ route('portals.admin.onboarding') }}" class="stat-card">
    <div class="stat-card__icon"><i data-lucide="rocket"></i></div>
    <div class="stat-card__value">{{ $platformStats['pending_onboarding'] ?? '—' }}</div>
    <div class="stat-card__label">{{ __('public.admin_governance.stat_pending_onboarding', [], app()->getLocale()) ?: 'Pending Onboardings' }}</div>
  </a>
</div>
@endplatformadmin

<!-- KPI Cards -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-icon blue"><i data-lucide="id-card"></i></div>
        <div class="kpi-body">
            <div class="kpi-label">{{ __('public.admin_governance.kpi_total_health_ids', [], app()->getLocale()) ?: 'Total Health IDs' }}</div>
            <div class="kpi-value">{{ number_format($stats['total_ids'] ?? 0) }}</div>
            <div class="kpi-sub">{{ __('public.admin_governance.kpi_registered_patients', [], app()->getLocale()) ?: 'Registered patients' }}</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon teal"><i data-lucide="qr-code"></i></div>
        <div class="kpi-body">
            <div class="kpi-label">{{ __('public.admin_governance.kpi_active_tokens', [], app()->getLocale()) ?: 'Active Tokens' }}</div>
            <div class="kpi-value">{{ number_format($stats['active_tokens'] ?? 0) }}</div>
            <div class="kpi-sub">{{ __('public.admin_governance.kpi_live_tokens', [], app()->getLocale()) ?: 'Live access tokens' }}</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon blue"><i data-lucide="activity"></i></div>
        <div class="kpi-body">
            <div class="kpi-label">{{ __('public.admin_governance.kpi_total_lookups', [], app()->getLocale()) ?: 'Total Lookups' }}</div>
            <div class="kpi-value">{{ number_format($stats['total_access_logs'] ?? 0) }}</div>
            <div class="kpi-sub">{{ __('public.admin_governance.kpi_alltime_events', [], app()->getLocale()) ?: 'All-time access events' }}</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon danger"><i data-lucide="shield-x"></i></div>
        <div class="kpi-body">
            <div class="kpi-label">{{ __('public.admin_governance.kpi_denied_lookups', [], app()->getLocale()) ?: 'Denied Lookups' }}</div>
            <div class="kpi-value">{{ number_format($stats['denied_access'] ?? 0) }}</div>
            <div class="kpi-sub">{{ __('public.admin_governance.kpi_failed_denied', [], app()->getLocale()) ?: 'Failed / denied' }}</div>
        </div>
    </div>
</div>


<!-- Partner Governance -->
<div class="panel mb-6" id="partners">
    <div class="panel-header">
        <h2 class="panel-title">
            <i data-lucide="building-2"></i>
            {{ __('public.admin_governance.section_partner_governance', [], app()->getLocale()) ?: 'Partner Governance' }}
        </h2>
    </div>
    <div class="table-wrapper">
        <table class="data-table" id="partners-table" aria-label="{{ __('public.admin_governance.section_partner_governance', [], app()->getLocale()) ?: 'Partners governance' }}">
            <thead>
                <tr>
                    <th>{{ __('public.admin_governance.col_partner', [], app()->getLocale()) ?: 'Partner' }}</th>
                    <th>{{ __('public.admin_governance.col_type', [], app()->getLocale()) ?: 'Type' }}</th>
                    <th>{{ __('public.admin_governance.col_status', [], app()->getLocale()) ?: 'Status' }}</th>
                    <th>{{ __('public.admin_governance.col_trust_level', [], app()->getLocale()) ?: 'Trust Level' }}</th>
                    <th><span class="sr-only">{{ __('public.staff_portal.col_actions', [], app()->getLocale()) ?: 'Actions' }}</span></th>
                </tr>
            </thead>
            <tbody id="partners-body">
                <tr>
                    <td colspan="5" class="td-muted empty-cell">
                        <i data-lucide="loader" class="spin"></i>
                        {{ __('public.admin_governance.loading_partners', [], app()->getLocale()) ?: 'Loading partners…' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Pending Duplicate Reviews -->
<div class="panel mb-6" id="duplicates">
    <div class="panel-header">
        <h2 class="panel-title">
            <i data-lucide="users"></i>
            {{ __('public.admin_governance.section_duplicate_reviews', [], app()->getLocale()) ?: 'Pending Duplicate Reviews' }}
        </h2>
    </div>
    <div class="table-wrapper">
        <table class="data-table" id="duplicates-table" aria-label="{{ __('public.admin_governance.section_duplicate_reviews', [], app()->getLocale()) ?: 'Duplicate review queue' }}">
            <thead>
                <tr>
                    <th>{{ __('public.admin_governance.col_match_score', [], app()->getLocale()) ?: 'Match Score' }}</th>
                    <th>{{ __('public.admin_governance.col_primary_patient', [], app()->getLocale()) ?: 'Primary Patient' }}</th>
                    <th>{{ __('public.admin_governance.col_secondary_patient', [], app()->getLocale()) ?: 'Secondary Patient' }}</th>
                    <th><span class="sr-only">{{ __('public.staff_portal.col_actions', [], app()->getLocale()) ?: 'Actions' }}</span></th>
                </tr>
            </thead>
            <tbody id="duplicates-body">
                <tr>
                    <td colspan="4" class="td-muted empty-cell">
                        <i data-lucide="loader" class="spin"></i>
                        {{ __('public.admin_governance.loading_cases', [], app()->getLocale()) ?: 'Loading pending cases…' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Security Events -->
<div class="panel" id="security">
    <div class="panel-header">
        <h2 class="panel-title">
            <i data-lucide="activity"></i>
            {{ __('public.admin_governance.section_security_events', [], app()->getLocale()) ?: 'Recent Security Events' }}
        </h2>
    </div>
    <div class="table-wrapper">
        <table class="data-table" aria-label="{{ __('public.admin_governance.section_security_events', [], app()->getLocale()) ?: 'Recent security events' }}">
            <thead>
                <tr>
                    <th>{{ __('public.admin_governance.col_timestamp', [], app()->getLocale()) ?: 'Timestamp' }}</th>
                    <th>{{ __('public.admin_governance.col_event_type', [], app()->getLocale()) ?: 'Event Type' }}</th>
                    <th>{{ __('public.admin_governance.col_target_health_id', [], app()->getLocale()) ?: 'Target Health ID' }}</th>
                    <th>{{ __('public.admin_governance.col_actor', [], app()->getLocale()) ?: 'Actor' }}</th>
                    <th>{{ __('public.admin_governance.col_result', [], app()->getLocale()) ?: 'Result' }}</th>
                </tr>
            </thead>
            <tbody>
                @isset($recentLogs)
                @forelse($recentLogs as $log)
                <tr>
                    <td data-label="{{ __('public.admin_governance.col_timestamp', [], app()->getLocale()) ?: 'Timestamp' }}">
                        <span class="td-muted">{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y') }}</span>
                        <div class="td-muted code-muted">{{ \Carbon\Carbon::parse($log->created_at)->format('H:i:s') }}</div>
                    </td>
                    <td data-label="{{ __('public.admin_governance.col_event_type', [], app()->getLocale()) ?: 'Event' }}">
                        <span class="td-strong">{{ $log->access_type }}</span>
                    </td>
                    <td data-label="{{ __('public.admin_governance.col_target_health_id', [], app()->getLocale()) ?: 'Health ID' }}">
                        <span class="td-mono">{{ $log->health_id ?? __('public.admin_governance.lbl_unknown', [], app()->getLocale()) ?: 'Unknown' }}</span>
                    </td>
                    <td data-label="{{ __('public.admin_governance.col_actor', [], app()->getLocale()) ?: 'Actor' }}">
                        <span class="td-muted">{{ $log->actor_type ?? '—' }}</span>
                        @if(!empty($log->ip_address))
                        <div class="td-muted code-muted">{{ $log->ip_address }}</div>
                        @endif
                    </td>
                    <td data-label="{{ __('public.admin_governance.col_result', [], app()->getLocale()) ?: 'Result' }}">
                        @if(($log->result ?? '') === 'success')
                            <span class="badge badge-allowed">{{ __('public.admin_governance.result_success', [], app()->getLocale()) ?: 'Success' }}</span>
                        @else
                            <span class="badge badge-denied">{{ __('public.admin_governance.result_denied', [], app()->getLocale()) ?: 'Denied' }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="td-muted empty-cell">
                        {{ __('public.admin_governance.no_security_events', [], app()->getLocale()) ?: 'No security events recorded yet.' }}
                    </td>
                </tr>
                @endforelse
                @endisset
            </tbody>
        </table>
    </div>
</div>

<!-- Duplicate Review Modal -->
<div id="duplicate-modal" class="modal-fixed" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="modal-fixed__panel">
        <div class="modal-fixed__head">
            <h3 id="modal-title" class="modal-fixed__title">{{ __('public.admin_governance.modal_review_title', [], app()->getLocale()) ?: 'Review Suspected Duplicate' }}</h3>
            <button id="close-duplicate" class="icon-btn" aria-label="{{ __('public.aria_close_modal') }}">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="grid-2 mb-4">
            <!-- Primary -->
            <div class="stat-card">
                <div class="mb-3">
                    <span class="badge badge-primary">{{ __('public.admin_governance.badge_primary_record', [], app()->getLocale()) ?: 'Primary Record' }}</span>
                </div>
                <table class="kv-table">
                    <tr><td>{{ __('public.admin_governance.lbl_health_id', [], app()->getLocale()) ?: 'Health ID:' }}</td><td><span id="m-primary-id" class="kv-strong mono"></span></td></tr>
                    <tr><td>{{ __('public.admin_governance.lbl_name', [], app()->getLocale()) ?: 'Name:' }}</td><td><strong id="m-primary-name"></strong></td></tr>
                    <tr><td>{{ __('public.admin_governance.lbl_dob', [], app()->getLocale()) ?: 'DOB:' }}</td><td><span id="m-primary-dob"></span></td></tr>
                    <tr><td>{{ __('public.admin_governance.lbl_sex', [], app()->getLocale()) ?: 'Sex:' }}</td><td><span id="m-primary-sex"></span></td></tr>
                </table>
            </div>
            <!-- Secondary -->
            <div class="stat-card">
                <div class="mb-3">
                    <span class="badge badge-warning">{{ __('public.admin_governance.badge_suspected_duplicate', [], app()->getLocale()) ?: 'Suspected Duplicate' }}</span>
                </div>
                <table class="kv-table">
                    <tr><td>{{ __('public.admin_governance.lbl_health_id', [], app()->getLocale()) ?: 'Health ID:' }}</td><td><span id="m-secondary-id" class="kv-strong mono"></span></td></tr>
                    <tr><td>{{ __('public.admin_governance.lbl_name', [], app()->getLocale()) ?: 'Name:' }}</td><td><strong id="m-secondary-name"></strong></td></tr>
                    <tr><td>{{ __('public.admin_governance.lbl_dob', [], app()->getLocale()) ?: 'DOB:' }}</td><td><span id="m-secondary-dob"></span></td></tr>
                    <tr><td>{{ __('public.admin_governance.lbl_sex', [], app()->getLocale()) ?: 'Sex:' }}</td><td><span id="m-secondary-sex"></span></td></tr>
                </table>
            </div>
        </div>

        <div class="form-group mb-4">
            <label class="form-label" for="review-reason">{{ __('public.admin_governance.lbl_reviewer_notes', [], app()->getLocale()) ?: 'Reviewer Notes (Optional)' }}</label>
            <textarea id="review-reason" rows="2" class="form-control" placeholder="{{ __('public.admin_governance.lbl_reviewer_notes_ph', [], app()->getLocale()) ?: 'e.g. Verified via National ID…' }}"></textarea>
        </div>

        <div class="modal__footer">
            <button id="btn-reject-merge" class="btn btn-secondary">
                <i data-lucide="x-circle"></i>
                {{ __('public.admin_governance.btn_reject_match', [], app()->getLocale()) ?: 'Reject Match (Keep Separate)' }}
            </button>
            <button id="btn-approve-merge" class="btn btn-warning">
                <i data-lucide="merge"></i>
                {{ __('public.admin_governance.btn_confirm_merge', [], app()->getLocale()) ?: 'Confirm Merge' }}
            </button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
var _ag = {
    noPartners:        @json(__('public.admin_governance.no_partners',           [], app()->getLocale()) ?: 'No partners found.'),
    noDuplicates:      @json(__('public.admin_governance.no_duplicates',         [], app()->getLocale()) ?: 'No pending duplicate reviews.'),
    errDuplicates:     @json(__('public.admin_governance.error_loading_duplicates', [], app()->getLocale()) ?: 'Error loading duplicate cases.'),
    btnApprove:        @json(__('public.admin_governance.btn_approve',           [], app()->getLocale()) ?: 'Approve'),
    btnSuspend:        @json(__('public.admin_governance.btn_suspend',           [], app()->getLocale()) ?: 'Suspend'),
    btnReview:         @json(__('public.admin_governance.btn_review',            [], app()->getLocale()) ?: 'Review'),
    confirmApprove:    @json(__('public.admin_governance.js_confirm_approve',    [], app()->getLocale()) ?: 'Approve this partner?'),
    promptSuspension:  @json(__('public.admin_governance.js_prompt_suspension',  [], app()->getLocale()) ?: 'Enter suspension reason (min 10 chars):'),
    alertValidReason:  @json(__('public.admin_governance.js_alert_valid_reason', [], app()->getLocale()) ?: 'A valid reason of at least 10 characters is required.'),
    alertErrApproving: @json(__('public.admin_governance.js_alert_error_approving',  [], app()->getLocale()) ?: 'Error approving partner.'),
    alertErrSuspending:@json(__('public.admin_governance.js_alert_error_suspending', [], app()->getLocale()) ?: 'Error suspending partner.'),
    alertErrResolve:   @json(__('public.admin_governance.js_alert_error_resolve',    [], app()->getLocale()) ?: 'An error occurred while resolving the case.'),
    alertNetworkError: @json(__('public.admin_governance.js_alert_network_error',    [], app()->getLocale()) ?: 'Network error. Please try again.'),
    lblProcessing:     @json(__('public.admin_governance.lbl_processing',        [], app()->getLocale()) ?: 'Processing…'),
    col_match_score:      @json(__('public.admin_governance.col_match_score',      [], app()->getLocale()) ?: 'Match Score'),
    col_primary_patient:  @json(__('public.admin_governance.col_primary_patient',  [], app()->getLocale()) ?: 'Primary Patient'),
    col_secondary_patient:@json(__('public.admin_governance.col_secondary_patient',[], app()->getLocale()) ?: 'Secondary Patient'),
};
document.addEventListener('DOMContentLoaded', async function () {
    var modal         = document.getElementById('duplicate-modal');
    var closeBtn      = document.getElementById('close-duplicate');
    var btnApprove    = document.getElementById('btn-approve-merge');
    var btnReject     = document.getElementById('btn-reject-merge');
    var mergeCases    = [];
    var currentReviewId = null;

    // ── Partner Governance ──
    const loadPartners = async () => {
        try {
            const res = await fetch('/api/partner-governance/partners', {headers:{'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? ''}});
            const data = await res.json();
            const tbody = document.getElementById('partners-body');
            if (!data.data || data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="td-muted empty-cell">' + _ag.noPartners + '</td></tr>';
                return;
            }
            tbody.innerHTML = data.data.map(p => {
                const stBadge = p.status === 'active'
                    ? '<span class="badge badge-success">' + p.status + '</span>'
                    : p.status === 'suspended'
                    ? '<span class="badge badge-danger">' + p.status + '</span>'
                    : '<span class="badge badge-warning">' + p.status + '</span>';

                const actions = [
                    p.status === 'submitted' ? `<button onclick="approvePartner('${p.uuid}')" class="btn btn-teal btn-sm">${_ag.btnApprove}</button>` : '',
                    p.status !== 'suspended' && p.status !== 'submitted' ? `<button onclick="suspendPartner('${p.uuid}')" class="btn btn-danger btn-sm">${_ag.btnSuspend}</button>` : ''
                ].filter(Boolean).join(' ');

                return `<tr>
                    <td data-label="Partner"><span class="td-strong">${p.legal_name}</span><div class="td-muted code-muted">${p.uuid}</div></td>
                    <td data-label="Type"><span class="badge badge-neutral">${p.partner_type}</span></td>
                    <td data-label="Status">${stBadge}</td>
                    <td data-label="Trust">${p.trust_level.replace('level_','').replace(/_/g,' ')}</td>
                    <td data-label="Actions" class="row-actions">${actions}</td>
                </tr>`;
            }).join('');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        } catch (e) { console.error(e); }
    };

    window.approvePartner = async (id) => {
        if (!confirm(_ag.confirmApprove)) return;
        try {
            await fetch(`/api/partner-governance/partners/${id}/approve`, { method: 'POST' });
            loadPartners();
        } catch(e) { alert(_ag.alertErrApproving); }
    };

    window.suspendPartner = async (id) => {
        const reason = prompt(_ag.promptSuspension);
        if (!reason || reason.length < 10) return alert(_ag.alertValidReason);
        try {
            const res = await fetch(`/api/partner-governance/partners/${id}/suspend`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ reason })
            });
            if (res.ok) loadPartners();
            else alert((await res.json()).message);
        } catch(e) { alert(_ag.alertErrSuspending); }
    };

    loadPartners();

    // ── Duplicate Cases ──
    const loadCases = async () => {
        const tbody = document.getElementById('duplicates-body');
        try {
            const res = await fetch('/api/v1/connect/admin/merge-cases', {headers:{'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? ''}});
            const data = await res.json();
            if (data.status === 'success') {
                mergeCases = data.cases;
                if (mergeCases.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="td-muted empty-cell">' + _ag.noDuplicates + '</td></tr>';
                    return;
                }
                tbody.innerHTML = mergeCases.map(c => `<tr>
                    <td data-label="${_ag.col_match_score ?? 'Score'}"><span class="badge badge-warning">${c.match_score}%</span></td>
                    <td data-label="${_ag.col_primary_patient ?? 'Primary'}">
                        <span class="td-strong">${c.primary_patient.first_name} ${c.primary_patient.last_name}</span>
                        <div class="td-mono">${c.primary_patient.health_id}</div>
                    </td>
                    <td data-label="${_ag.col_secondary_patient ?? 'Secondary'}">
                        <span class="td-strong">${c.secondary_patient.first_name} ${c.secondary_patient.last_name}</span>
                        <div class="td-mono">${c.secondary_patient.health_id}</div>
                    </td>
                    <td data-label="Action" class="row-actions">
                        <button onclick="openReviewModal('${c.uuid}')" class="btn btn-primary btn-sm">${_ag.btnReview}</button>
                    </td>
                </tr>`).join('');
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        } catch(e) {
            tbody.innerHTML = '<tr><td colspan="4" class="td-muted empty-cell">' + _ag.errDuplicates + '</td></tr>';
        }
    };

    await loadCases();

    // ── Modal Logic ──
    window.openReviewModal = (id) => {
        currentReviewId = id;
        const c = mergeCases.find(x => x.uuid === id);
        if (!c) return;
        document.getElementById('m-primary-id').textContent   = c.primary_patient.health_id;
        document.getElementById('m-primary-name').textContent = c.primary_patient.first_name + ' ' + c.primary_patient.last_name;
        document.getElementById('m-primary-dob').textContent  = c.primary_patient.date_of_birth;
        document.getElementById('m-primary-sex').textContent  = c.primary_patient.sex;
        document.getElementById('m-secondary-id').textContent   = c.secondary_patient.health_id;
        document.getElementById('m-secondary-name').textContent = c.secondary_patient.first_name + ' ' + c.secondary_patient.last_name;
        document.getElementById('m-secondary-dob').textContent  = c.secondary_patient.date_of_birth;
        document.getElementById('m-secondary-sex').textContent  = c.secondary_patient.sex;
        document.getElementById('review-reason').value = '';
        modal.style.display = 'flex';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    };

    if (closeBtn) closeBtn.addEventListener('click', () => { modal.style.display = 'none'; });
    modal.addEventListener('click', (e) => { if (e.target === modal) modal.style.display = 'none'; });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') modal.style.display = 'none'; });

    const resolveMerge = async (resolution) => {
        const reason = document.getElementById('review-reason').value;
        const btn = resolution === 'approve' ? btnApprove : btnReject;
        const oldHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i data-lucide="loader" class="spin"></i> ' + _ag.lblProcessing;
        if (typeof lucide !== 'undefined') lucide.createIcons();

        try {
            const res = await fetch(`/api/v1/connect/admin/merge-cases/${currentReviewId}/resolve`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ resolution, review_reason: reason })
            });
            if (res.ok) {
                modal.style.display = 'none';
                await loadCases();
            } else {
                alert(_ag.alertErrResolve + ' ' + ((await res.json()).message ?? ''));
            }
        } catch(e) { alert(_ag.alertNetworkError); }
        finally {
            btn.disabled = false;
            btn.innerHTML = oldHtml;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    };

    if (btnApprove) btnApprove.addEventListener('click', () => resolveMerge('approve'));
    if (btnReject)  btnReject.addEventListener('click', () => resolveMerge('reject'));
});
</script>
@endsection
