{{-- 32px kebab with a native <details> menu (no script). $links: [[href, label, lucide-icon], …] --}}
<details class="os-kebab">
    <summary class="os-kebab__btn" aria-label="{{ __('pharmacy_stock_desk.more_actions') }}"><i data-lucide="more-horizontal"></i></summary>
    <div class="os-kebab__menu" role="menu">
        @foreach($links as [$href, $label, $icon])
        <a href="{{ $href }}" role="menuitem"><i data-lucide="{{ $icon }}"></i>{{ $label }}</a>
        @endforeach
    </div>
</details>
