{{-- A grid of feature cards from $ns.$group.<key>.title/desc. Expects $ns, $group, $items (key => Lucide icon), $class. --}}
@php
    $exIcon = fn (string $name, int $size = 20) => view('partials.finder.icon', ['name' => $name, 'size' => $size])->render();
@endphp
<ul class="{{ $class }}" role="list">
    @foreach ($items as $k => $icon)
        <li class="ex-feat">
            <span class="hm-ic">{!! $exIcon($icon, 20) !!}</span>
            <h3>{{ __($ns . '.' . $group . '.' . $k . '.title') }}</h3>
            <p>{{ __($ns . '.' . $group . '.' . $k . '.desc') }}</p>
        </li>
    @endforeach
</ul>
