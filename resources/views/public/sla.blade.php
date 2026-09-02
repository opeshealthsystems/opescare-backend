@extends('layouts.public')

@section('title', __('public.sla.page_title'))
@section('meta_description', __('public.sla.meta_description'))

@section('content')

    {{-- Hero --}}
    <header class="content-header" style="padding:3rem 0 2.5rem;">
        <div class="container">
            <h1>{{ __('public.sla.hero_title') }}</h1>
            <p class="text-muted">{{ __('public.sla.hero_subtitle') }}</p>
            <p style="font-size:.8125rem;color:#94a3b8;margin-top:.5rem;">{{ __('public.sla.effective') }} {{ now()->startOfMonth()->format('d M Y') }} · {{ __('public.sla.scope') }}</p>
        </div>
    </header>

    <section class="content-body">
        <div class="container" style="max-width:820px;">

            {{-- Uptime commitment --}}
            <div style="background:linear-gradient(135deg,#0F4C81,#1565a8);border-radius:1.25rem;padding:2rem;margin-bottom:2.5rem;color:#fff;display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;">
                <div style="font-size:3rem;font-weight:800;line-height:1;">{{ __('public.sla.uptime_value') }}</div>
                <div style="flex:1;min-width:220px;">
                    <div style="font-weight:700;font-size:1.125rem;margin-bottom:.25rem;">{{ __('public.sla.uptime_title') }}</div>
                    <div style="font-size:.875rem;opacity:.9;">{{ __('public.sla.uptime_desc') }}</div>
                </div>
            </div>

            {{-- Severity & response targets --}}
            <h2 style="font-size:1.25rem;margin-bottom:1rem;">{{ __('public.sla.severity_title') }}</h2>
            <div style="border:1px solid #e2e8f0;border-radius:1rem;overflow:hidden;margin-bottom:2.5rem;">
                <div style="display:grid;grid-template-columns:1.4fr 1fr 1fr;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:.6875rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#64748b;">
                    <div style="padding:.75rem 1rem;">{{ __('public.sla.col_level') }}</div>
                    <div style="padding:.75rem 1rem;">{{ __('public.sla.col_response') }}</div>
                    <div style="padding:.75rem 1rem;">{{ __('public.sla.col_resolution') }}</div>
                </div>
                @php
                $rows = [
                    ['#ef4444', __('public.sla.sev1_name'), __('public.sla.sev1_desc'), __('public.sla.sev1_response'), __('public.sla.sev1_resolution')],
                    ['#f59e0b', __('public.sla.sev2_name'), __('public.sla.sev2_desc'), __('public.sla.sev2_response'), __('public.sla.sev2_resolution')],
                    ['#3b82f6', __('public.sla.sev3_name'), __('public.sla.sev3_desc'), __('public.sla.sev3_response'), __('public.sla.sev3_resolution')],
                    ['#64748b', __('public.sla.sev4_name'), __('public.sla.sev4_desc'), __('public.sla.sev4_response'), __('public.sla.sev4_resolution')],
                ];
                @endphp
                @foreach($rows as $i => $r)
                <div style="display:grid;grid-template-columns:1.4fr 1fr 1fr;{{ $i < count($rows)-1 ? 'border-bottom:1px solid #e2e8f0;' : '' }}">
                    <div style="padding:.9rem 1rem;">
                        <div style="display:flex;align-items:center;gap:.5rem;font-weight:700;font-size:.875rem;color:#0F2744;"><span style="width:.5rem;height:.5rem;border-radius:50%;background:{{ $r[0] }};"></span>{{ $r[1] }}</div>
                        <div style="font-size:.75rem;color:#64748b;margin-top:.2rem;">{{ $r[2] }}</div>
                    </div>
                    <div style="padding:.9rem 1rem;font-size:.875rem;color:#0F2744;align-self:center;">{{ $r[3] }}</div>
                    <div style="padding:.9rem 1rem;font-size:.875rem;color:#0F2744;align-self:center;">{{ $r[4] }}</div>
                </div>
                @endforeach
            </div>

            {{-- Scheduled maintenance --}}
            <h2 style="font-size:1.25rem;margin-bottom:.75rem;">{{ __('public.sla.maintenance_title') }}</h2>
            <p style="color:#475569;font-size:.9375rem;margin-bottom:2.5rem;">{{ __('public.sla.maintenance_desc') }}
                <a href="{{ route('public.status') }}" style="color:#0F4C81;font-weight:600;">{{ __('public.sla.maintenance_link') }}</a>.</p>

            {{-- Service credits --}}
            <h2 style="font-size:1.25rem;margin-bottom:.75rem;">{{ __('public.sla.credits_title') }}</h2>
            <p style="color:#475569;font-size:.9375rem;margin-bottom:1rem;">{{ __('public.sla.credits_desc') }}</p>
            <div style="border:1px solid #e2e8f0;border-radius:1rem;overflow:hidden;margin-bottom:2.5rem;">
                @php
                $credits = [
                    [__('public.sla.credit_1_range'), __('public.sla.credit_1_value')],
                    [__('public.sla.credit_2_range'), __('public.sla.credit_2_value')],
                    [__('public.sla.credit_3_range'), __('public.sla.credit_3_value')],
                ];
                @endphp
                @foreach($credits as $i => $c)
                <div style="display:flex;justify-content:space-between;align-items:center;padding:.85rem 1.25rem;{{ $i < count($credits)-1 ? 'border-bottom:1px solid #e2e8f0;' : '' }}">
                    <span style="font-size:.875rem;color:#475569;">{{ $c[0] }}</span>
                    <span style="font-weight:700;color:#0F4C81;">{{ $c[1] }}</span>
                </div>
                @endforeach
            </div>

            {{-- Exclusions --}}
            <h2 style="font-size:1.25rem;margin-bottom:.75rem;">{{ __('public.sla.exclusions_title') }}</h2>
            <ul style="color:#475569;font-size:.9375rem;line-height:1.8;margin-bottom:2.5rem;padding-left:1.25rem;">
                <li>{{ __('public.sla.excl_sandbox') }}</li>
                <li>{{ __('public.sla.excl_maintenance') }}</li>
                <li>{{ __('public.sla.excl_client') }}</li>
                <li>{{ __('public.sla.excl_force_majeure') }}</li>
            </ul>

            {{-- Support --}}
            <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:1.25rem;padding:1.5rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
                <i data-lucide="life-buoy" style="width:1.5rem;height:1.5rem;color:#2563EB;flex-shrink:0;"></i>
                <div style="flex:1;min-width:200px;">
                    <div style="font-weight:700;color:#1E40AF;margin-bottom:.2rem;">{{ __('public.sla.support_title') }}</div>
                    <div style="font-size:.8125rem;color:#3B82F6;">{{ __('public.sla.support_desc') }}</div>
                </div>
                <a href="{{ route('public.status') }}" class="btn btn-primary" style="font-size:.875rem;padding:.625rem 1.25rem;flex-shrink:0;">{{ __('public.sla.btn_status') }}</a>
            </div>

        </div>
    </section>

@endsection
