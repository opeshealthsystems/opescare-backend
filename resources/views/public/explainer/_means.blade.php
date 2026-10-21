{{-- "What it means" — patients and facilities side by side. Expects $ns, $pIcons, $fIcons. --}}
@php
    $exIcon = fn (string $name, int $size = 20) => view('partials.finder.icon', ['name' => $name, 'size' => $size])->render();
@endphp
<section class="hm-s hm-s--alt" aria-labelledby="ex-means-title">
    <div class="hm-wrap">
        <div class="hm-head">
            <p class="hm-eyebrow">{{ __($ns . '.means.eyebrow') }}</p>
            <h2 id="ex-means-title">{{ __($ns . '.means.title') }}</h2>
        </div>
        <div class="ex-two">
            @foreach (['p' => [$pIcons, 'patients', 'users'], 'f' => [$fIcons, 'facilities', 'building-2']] as $side => [$icons, $label, $head])
                <div class="ex-card">
                    <h3 class="ex-card__h"><span class="hm-ic">{!! $exIcon($head, 20) !!}</span>{{ __($ns . '.means.' . $label) }}</h3>
                    <ul class="hm-list" role="list">
                        @foreach ($icons as $k => $icon)
                            <li><span class="hm-ic hm-ic--good">{!! $exIcon($icon, 18) !!}</span><div><b>{{ __($ns . '.means.' . $side . ($k + 1) . '_title') }}</b><span>{{ __($ns . '.means.' . $side . ($k + 1) . '_desc') }}</span></div></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</section>
