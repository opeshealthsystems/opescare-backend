@extends('layouts.public')

@section('title', __('public.faq.page_title'))
@section('meta_description', __('public.faq.meta_description'))

{{--
    FAQPage schema. This is the single highest-value structured-data block
    on the site for answer engines: ChatGPT, Perplexity and AI Overviews
    lift Q&A pairs almost verbatim when they are marked up, and infer them
    unreliably when they are not. Built from the same lang array the page
    renders, so the markup can never drift from the visible answers.
--}}
@push('schema')
@php
    $faqEntities = [];
    foreach (__('public.faq.categories', [], app()->getLocale()) as $faqCat) {
        foreach ($faqCat['items'] ?? [] as $faqItem) {
            $faqEntities[] = [
                '@type'          => 'Question',
                'name'           => $faqItem['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faqItem['a']],
            ];
        }
    }
@endphp
<script type="application/ld+json">
{!! json_encode([
    '@context'   => 'https://schema.org',
    '@type'      => 'FAQPage',
    'inLanguage' => app()->getLocale(),
    'mainEntity' => $faqEntities,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')

<section class="content-header" style="background:linear-gradient(135deg,#0F2744 0%,#0F4C81 100%);padding:4rem 0 3rem;color:#fff;">
    <div class="container" style="text-align:center;">
        <div style="display:inline-flex;align-items:center;gap:0.5rem;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);border-radius:2rem;padding:0.35rem 1rem;font-size:0.75rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#BAE6FD;margin-bottom:1.5rem;">
            <i data-lucide="help-circle" style="width:0.875rem;height:0.875rem;"></i>
            {{ __('public.faq.badge') }}
        </div>
        <h1 style="font-size:clamp(2rem,4vw,3rem);font-weight:900;margin:0 0 1rem;">{{ __('public.faq.heading') }}</h1>
        <p style="font-size:1.125rem;color:#BAE6FD;max-width:560px;margin:0 auto;">{{ __('public.faq.subheading') }}</p>
    </div>
</section>

<section style="padding:4rem 0;">
    <div class="container" style="max-width:820px;">

        @foreach(__('public.faq.categories', [], app()->getLocale()) as $cat)
        <div style="margin-bottom:3rem;">
            <h2 style="display:flex;align-items:center;gap:0.625rem;font-size:1.0625rem;font-weight:800;color:var(--color-text-primary);margin:0 0 1.25rem;padding-bottom:0.75rem;border-bottom:2px solid var(--color-border);">
                <i data-lucide="{{ $cat['icon'] }}" style="width:1.125rem;height:1.125rem;color:#0F4C81;flex-shrink:0;"></i>
                {{ $cat['title'] }}
            </h2>
            <div style="display:flex;flex-direction:column;gap:0.75rem;">
                @foreach($cat['items'] as $i => $item)
                <details style="background:var(--color-surface);border:1px solid var(--color-border);border-radius:0.75rem;overflow:hidden;" {{ $loop->first && $loop->parent->first ? 'open' : '' }}>
                    <summary style="display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.125rem 1.25rem;cursor:pointer;font-size:0.9375rem;font-weight:700;color:var(--color-text-primary);list-style:none;user-select:none;">
                        {{ $item['q'] }}
                        <i data-lucide="chevron-down" style="width:1rem;height:1rem;flex-shrink:0;color:var(--color-text-muted);transition:transform .2s;" class="faq-chevron"></i>
                    </summary>
                    <div style="padding:0 1.25rem 1.25rem;font-size:0.9rem;color:var(--color-text-muted);line-height:1.7;border-top:1px solid var(--color-border);">
                        <p style="margin:1rem 0 0;">{{ $item['a'] }}</p>
                    </div>
                </details>
                @endforeach
            </div>
        </div>
        @endforeach

        <!-- Still need help CTA -->
        <div style="margin-top:3rem;background:linear-gradient(135deg,#EFF6FF 0%,#F0FDF9 100%);border:1px solid #BFDBFE;border-radius:1rem;padding:2.5rem;text-align:center;">
            <div style="width:3rem;height:3rem;border-radius:50%;background:#0F4C81;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                <i data-lucide="headset" style="width:1.25rem;height:1.25rem;color:#fff;"></i>
            </div>
            <h3 style="font-size:1.25rem;font-weight:800;color:var(--color-text-primary);margin:0 0 0.5rem;">{{ __('public.faq.cta_title') }}</h3>
            <p style="font-size:0.9rem;color:var(--color-text-muted);margin:0 0 1.5rem;">{{ __('public.faq.cta_body') }}</p>
            <a href="{{ route('public.contact') }}" style="display:inline-flex;align-items:center;gap:0.5rem;background:#0F4C81;color:#fff;font-size:0.875rem;font-weight:700;border-radius:0.5rem;padding:0.75rem 1.75rem;text-decoration:none;transition:background .2s;" onmouseover="this.style.background='#0E3F6C'" onmouseout="this.style.background='#0F4C81'">
                <i data-lucide="send" style="width:1rem;height:1rem;"></i>
                {{ __('public.faq.cta_button') }}
            </a>
        </div>

    </div>
</section>

@endsection

@section('footer_scripts')
<script>
document.querySelectorAll('details').forEach(function(el) {
    el.addEventListener('toggle', function() {
        var chevron = el.querySelector('.faq-chevron');
        if (chevron) chevron.style.transform = el.open ? 'rotate(180deg)' : '';
    });
});
</script>
@endsection
