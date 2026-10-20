{{-- Footer links to the OpesCare facts page and the human site map. Include inside a footer <ul>:
     @includeIf('partials.seo.footer-facts-link') --}}
<li><a class="f-footer__link" href="{{ route('public.facts') }}">{{ __('facts.footer_link') }}</a></li>
<li><a class="f-footer__link" href="{{ route('public.sitemap') }}">{{ __('sitemap.footer_link') }}</a></li>
