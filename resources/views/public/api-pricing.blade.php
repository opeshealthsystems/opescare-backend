@extends('layouts.public')

@section('title', __('public.api_pricing.page_title'))
@section('meta_description', __('public.api_pricing.meta_description'))

@section('content')

    {{-- Hero --}}
    <header class="content-header" style="padding:3rem 0 2rem;">
        <div class="container">
            <h1>{{ __('public.api_pricing.hero_title') }}</h1>
            <p class="text-muted">{{ __('public.api_pricing.hero_subtitle') }}</p>
        </div>
    </header>

    <section class="content-body">
        <div class="container" style="max-width:1000px;">

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem;align-items:stretch;">
                @foreach($plans as $plan)
                @php $popular = $plan->sort === 2; @endphp
                <div style="border:1.5px solid {{ $popular ? '#0F4C81' : '#e2e8f0' }};border-radius:1.25rem;padding:1.75rem;background:#fff;display:flex;flex-direction:column;position:relative;{{ $popular ? 'box-shadow:0 10px 30px rgba(15,76,129,.12);' : '' }}">
                    @if($popular)
                    <span style="position:absolute;top:-0.75rem;left:50%;transform:translateX(-50%);background:#0F4C81;color:#fff;font-size:.6875rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding:.25rem .75rem;border-radius:999px;">{{ __('public.api_pricing.popular') }}</span>
                    @endif

                    <h3 style="font-size:1.375rem;margin:0 0 .25rem;color:#0F2744;">{{ $plan->name }}</h3>

                    <div style="margin:.75rem 0 1.25rem;">
                        @if($plan->price_xaf === 0)
                            <span style="font-size:2rem;font-weight:800;color:#0F4C81;">{{ __('public.api_pricing.free') }}</span>
                        @else
                            <span style="font-size:2rem;font-weight:800;color:#0F4C81;">{{ number_format($plan->price_xaf, 0, ',', ' ') }}</span>
                            <span style="font-size:.875rem;color:#64748b;font-weight:600;"> FCFA / {{ __('public.api_pricing.per_month') }}</span>
                        @endif
                    </div>

                    <div style="display:flex;flex-direction:column;gap:.4rem;font-size:.8125rem;color:#475569;margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid #f1f5f9;">
                        <div><strong style="color:#0F2744;">{{ number_format($plan->rate_limit_per_min, 0, ',', ' ') }}</strong> {{ __('public.api_pricing.rate_per_min') }}</div>
                        <div>
                            <strong style="color:#0F2744;">{{ $plan->isUnlimited() ? __('public.api_pricing.unlimited') : number_format($plan->monthly_request_quota, 0, ',', ' ') }}</strong>
                            {{ $plan->isUnlimited() ? '' : __('public.api_pricing.requests_month') }}
                        </div>
                        <div>{{ __('public.api_pricing.support_label') }}: <strong style="color:#0F2744;">{{ __('public.api_pricing.support_' . $plan->support_level) }}</strong></div>
                    </div>

                    <ul style="list-style:none;padding:0;margin:0 0 1.5rem;display:flex;flex-direction:column;gap:.6rem;flex:1;">
                        @foreach(($plan->features ?? []) as $feat)
                        <li style="display:flex;align-items:flex-start;gap:.5rem;font-size:.875rem;color:#334155;">
                            <i data-lucide="check" style="width:1rem;height:1rem;color:#10b981;flex-shrink:0;margin-top:.15rem;"></i>
                            <span>{{ __('public.api_pricing.' . $feat) }}</span>
                        </li>
                        @endforeach
                    </ul>

                    <a href="{{ route('register.developer') }}" class="btn {{ $popular ? 'btn-primary' : 'btn-secondary' }}" style="width:100%;text-align:center;justify-content:center;">
                        {{ $plan->price_xaf === 0 ? __('public.api_pricing.btn_start') : __('public.api_pricing.btn_get_started') }}
                    </a>
                </div>
                @endforeach
            </div>

            <p style="text-align:center;font-size:.8125rem;color:#94a3b8;margin-top:2rem;">
                <i data-lucide="info" style="width:.875rem;height:.875rem;vertical-align:-2px;"></i>
                {{ __('public.api_pricing.payment_note') }}
                · <a href="{{ route('public.sla') }}" style="color:#0F4C81;">{{ __('public.api_pricing.sla_link') }}</a>
            </p>

        </div>
    </section>

@endsection
