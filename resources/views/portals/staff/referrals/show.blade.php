@extends('layouts.portal')

@section('title', __('public.stf_ref_show_title'))

@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()) ?: 'Staff Portal')
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('public.stf_ref_show_breadcrumb_section'))

@section('content')

@php
    $stCls = match($referral->status ?? 'draft') {
        'accepted'  => 'badge-success',
        'completed' => 'badge-teal',
        'sent'      => 'badge-primary',
        'rejected'  => 'badge-danger',
        'cancelled' => 'badge-neutral',
        'expired'   => 'badge-neutral',
        default     => 'badge-warning',
    };
    $prCls = match($referral->urgency ?? 'routine') {
        'emergency' => 'badge-critical',
        'urgent'    => 'badge-danger',
        default     => 'badge-neutral',
    };
    $isExpired = $referral->expires_at && \Carbon\Carbon::parse($referral->expires_at)->isPast();
@endphp

<div class="breadcrumb">
    <a href="{{ route('portals.staff.referrals') }}">{{ __('public.stf_ref_show_breadcrumb_referrals') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ __('public.stf_ref_show_breadcrumb_detail') }}</span>
</div>

<div class="page-head">
    <h2>{{ __('public.stf_ref_show_page_heading') }}</h2>
    <span class="badge {{ $stCls }}">@enum($referral->status ?? 'draft')</span>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.staff.referrals') }}" class="btn btn-ghost btn-sm">
        <i data-lucide="arrow-left"></i> {{ __('public.stf_ref_show_btn_back') }}
    </a>
</div>
<p class="page-subtitle mb-4">{{ __('public.stf_ref_show_id_label') }} <span class="mono">{{ $referral->id }}</span></p>

<div class="grid-main-side">

    <div>

        <!-- Core Details -->
        <div class="panel mb-6">
            <div class="panel-header">
                <h2 class="panel-title"><i data-lucide="file-text"></i> {{ __('public.stf_ref_show_panel_info_title') }}</h2>
            </div>
            <div class="panel-body panel-body--flush">
                <table class="kv-table">
                    <tr>
                        <td class="kv-strong">{{ __('public.stf_ref_show_kv_patient') }}</td>
                        <td class="mono">{{ $referral->patient_id }}</td>
                    </tr>
                    <tr>
                        <td class="kv-strong">{{ __('public.stf_ref_show_kv_priority') }}</td>
                        <td><span class="badge {{ $prCls }}">@enum($referral->urgency ?? 'routine', 'urgency')</span></td>
                    </tr>
                    <tr>
                        <td class="kv-strong">{{ __('public.stf_ref_show_kv_ref_facility') }}</td>
                        <td>{{ $referral->referring_facility_id ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="kv-strong">{{ __('public.stf_ref_show_kv_recv_facility') }}</td>
                        <td>{{ $referral->receiving_facility_id ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="kv-strong">{{ __('public.stf_ref_show_kv_specialty') }}</td>
                        <td>{{ $referral->receiving_specialty ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="kv-strong">{{ __('public.stf_ref_show_kv_expires') }}</td>
                        <td>
                            {{ $referral->expires_at ? \Carbon\Carbon::parse($referral->expires_at)->format('d M Y H:i') : '—' }}
                            @if($isExpired)<span class="badge badge-danger">{{ __('public.stf_ref_show_badge_expired') }}</span>@endif
                        </td>
                    </tr>
                </table>
                @if(!empty($referral->reason) || !empty($referral->clinical_summary))
                <div class="panel-body">
                    @if(!empty($referral->reason))
                    <div class="form-group">
                        <div class="form-label">{{ __('public.stf_ref_show_lbl_reason') }}</div>
                        <p>{{ $referral->reason }}</p>
                    </div>
                    @endif
                    @if(!empty($referral->clinical_summary))
                    <div class="form-group">
                        <div class="form-label">{{ __('public.stf_ref_show_lbl_clinical') }}</div>
                        <p class="td-muted">{{ $referral->clinical_summary }}</p>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>

        <!-- Actions -->
        @if(in_array($referral->status ?? 'draft', ['draft','sent','accepted']))
        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title"><i data-lucide="zap"></i> {{ __('public.stf_ref_show_actions_title') }}</h2>
            </div>
            <div class="panel-body">
                <div class="row-actions">
                    @if(($referral->status ?? 'draft') === 'draft')
                    <form method="POST" action="{{ route('portals.staff.referrals.send', $referral->id) }}" class="inline-form">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="send"></i> {{ __('public.stf_ref_show_btn_send') }}
                        </button>
                    </form>
                    @endif

                    @if($referral->status === 'sent')
                    <form method="POST" action="{{ route('portals.staff.referrals.accept', $referral->id) }}" class="inline-form">
                        @csrf
                        <button type="submit" class="btn btn-teal">
                            <i data-lucide="check-circle-2"></i> {{ __('public.stf_ref_show_btn_accept') }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('portals.staff.referrals.reject', $referral->id) }}" class="inline-form">
                        @csrf
                        <button type="submit" class="btn btn-secondary">
                            <i data-lucide="x-circle"></i> {{ __('public.stf_ref_show_btn_reject') }}
                        </button>
                    </form>
                    @endif

                    @if($referral->status === 'accepted')
                    <form method="POST" action="{{ route('portals.staff.referrals.complete', $referral->id) }}" class="inline-form">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="check-circle-2"></i> {{ __('public.stf_ref_show_btn_complete') }}
                        </button>
                    </form>
                    @endif

                    @if(in_array($referral->status ?? 'draft', ['draft','sent']))
                    <button type="button" class="btn btn-danger" onclick="opOpenModal('cancel-modal')">
                        <i data-lucide="x"></i> {{ __('public.stf_ref_show_btn_cancel') }}
                    </button>
                    @endif
                </div>
            </div>
        </div>
        @endif

    </div>

    <!-- Sidebar Info -->
    <div>
        <div class="panel mb-6">
            <div class="panel-header">
                <h2 class="panel-title"><i data-lucide="clock"></i> {{ __('public.stf_ref_show_timeline_title') }}</h2>
            </div>
            <div class="panel-body">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-dot"><i data-lucide="plus-circle"></i></div>
                        <div class="timeline-body">
                            <div class="timeline-time">{{ $referral->created_at?->format('d M Y H:i') ?? '—' }}</div>
                            <div class="timeline-title">{{ __('public.stf_ref_show_timeline_created') }}</div>
                            <div class="timeline-desc">{{ __('public.stf_ref_show_timeline_status_draft') }}</div>
                        </div>
                    </div>
                    @if(($referral->status ?? 'draft') !== 'draft')
                    <div class="timeline-item">
                        <div class="timeline-dot teal"><i data-lucide="send"></i></div>
                        <div class="timeline-body">
                            <div class="timeline-time">{{ $referral->updated_at?->format('d M Y H:i') ?? '—' }}</div>
                            <div class="timeline-title">{{ __('public.stf_ref_show_timeline_updated') }}</div>
                            <div class="timeline-desc">@enum($referral->status ?? 'draft')</div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-body">
                <div class="alert alert-warning" role="note">
                    <i data-lucide="shield-alert"></i>
                    <div>{{ __('public.stf_ref_show_audit_note') }}</div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- Cancel confirm modal --}}
@if(in_array($referral->status ?? 'draft', ['draft','sent']))
<div id="cancel-modal" class="modal-backdrop" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="cancel-modal-title">
        <h3 class="modal__title" id="cancel-modal-title"><i data-lucide="x-circle"></i> {{ __('public.stf_ref_show_modal_cancel_title') }}</h3>
        <form method="POST" action="{{ route('portals.staff.referrals.cancel', $referral->id) }}">
            @csrf
            <div class="modal__body">
                <p>{{ __('public.stf_ref_show_modal_cancel_body') }}</p>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost" onclick="opCloseModal('cancel-modal')">{{ __('public.stf_ref_show_modal_keep') }}</button>
                <button type="submit" class="btn btn-danger">{{ __('public.stf_ref_show_modal_confirm_cancel') }}</button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection
@section('scripts')
<script>
function opOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function opCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
@endsection
