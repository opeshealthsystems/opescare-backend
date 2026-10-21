{{--
    /download "Inside the app" — scripted walk-throughs of the patient app in an
    HTML/CSS phone. Each flow mirrors a real screen in opescare-finder-mobile:
      consent  components/consent/ConsentPromptSheet.tsx + app/privacy/access.tsx
      access   app/privacy/access-logs.tsx
      rx       app/prescriptions/index.tsx + [id].tsx (Show to pharmacy share code)
      results  app/labs/index.tsx + [id].tsx (value, reference range, flag)
      hid      app/(tabs)/health-id.tsx + components/health-id/SecureCredentialCard.tsx, PresentationMode.tsx
      appts    app/appointments/index.tsx + book.tsx
      family   app/family/index.tsx + [id].tsx
    Sample data only (fictional names, CM-HID 0000...). Driven by
    public/js/download-page.js; without JS the first screen of the first flow shows.
--}}
@php
    $fx = fn (string $k, array $r = []) => __('public_download.flows.' . $k, $r);
    $nm = fn (string $k) => __('public_download.flows.names.' . $k);
    $fxQr = \App\Support\QrSvg::brandedSvg('OPESCARE SAMPLE - NOT A REAL RECORD');
    $fxFlows = [
        'consent' => ['icon' => 'key-round',     'tab' => 'home'],
        'access'  => ['icon' => 'eye',           'tab' => 'profile'],
        'rx'      => ['icon' => 'pill',          'tab' => 'home'],
        'results' => ['icon' => 'flask-conical', 'tab' => 'home'],
        'hid'     => ['icon' => 'id-card',       'tab' => 'health_id'],
        'appts'   => ['icon' => 'calendar',      'tab' => 'home'],
        'family'  => ['icon' => 'users',         'tab' => 'profile'],
    ];
@endphp
<section class="hm-s dl-fxs" aria-labelledby="dl-flows-title" data-dl-flows>
    <div class="hm-wrap">
        <div class="hm-head">
            <p class="hm-eyebrow">{{ $fx('eyebrow') }}</p>
            <h2 id="dl-flows-title">{{ $fx('title') }}</h2>
            <p class="hm-sub">{{ $fx('sub') }}</p>
        </div>

        <div class="dl-fx">
            <div class="dl-fx__pick" role="tablist" aria-label="{{ $fx('picker_label') }}">
                @foreach ($fxFlows as $k => $f)
                    <button type="button" class="dl-fx__tab" role="tab" id="dl-tab-{{ $k }}" aria-controls="dl-flow-{{ $k }}"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? '0' : '-1' }}" data-flow="{{ $k }}">
                        <span class="dl-fx__ic">{!! $exIcon($f['icon'], 18) !!}</span>
                        <span class="dl-fx__txt"><b>{{ $fx($k . '.label') }}</b><small>{{ $fx($k . '.desc') }}</small></span>
                        <span class="dl-fx__bar" aria-hidden="true"><i></i></span>
                    </button>
                @endforeach
            </div>

            <div class="dl-fx__stage">
                <div class="dl-phone">
                    <div class="dl-phone__screen">
                        <div class="dl-sb" aria-hidden="true"><span>9:41</span><span class="dl-sb__r"><i></i><i></i><i></i></span></div>

                        {{-- Consent ─────────────────────────────── --}}
                        <div class="dl-flow is-on" id="dl-flow-consent" role="tabpanel" aria-labelledby="dl-tab-consent" data-flow="consent">
                            <div class="dl-scr is-on" data-ms="2600">
                                <div class="dl-app">
                                    <p class="dl-app__hi">{{ $fx('consent.greeting') }}</p>
                                    <div class="dl-card dl-card--navy"><b>{{ $fx('hid.card_label') }}</b><span>{{ $nm('hid') }}</span></div>
                                    <div class="dl-skel"></div><div class="dl-skel dl-skel--s"></div><div class="dl-skel"></div>
                                </div>
                                <div class="dl-notif dl-anim-drop dl-tap" style="--d:.5s;--t:1.6s">
                                    <span class="dl-notif__ic">{!! $exIcon('key-round', 16) !!}</span>
                                    <span><b>{{ $fx('consent.notif_title') }}</b><small>{{ $fx('consent.notif_body') }}</small></span>
                                </div>
                            </div>
                            <div class="dl-scr" data-ms="4200">
                                <div class="dl-app dl-app--dim"><p class="dl-app__hi">{{ $fx('consent.greeting') }}</p><div class="dl-skel"></div><div class="dl-skel"></div></div>
                                <div class="dl-sheet dl-anim-up">
                                    <span class="dl-sheet__grip"></span>
                                    <p class="dl-over">{{ $fx('consent.overline') }}</p>
                                    <h4 class="dl-h">{{ $fx('consent.title') }}</h4>
                                    <dl class="dl-kv">
                                        <div><dt>{{ $fx('consent.requested_by') }}</dt><dd>{{ $nm('doctor') }} · {{ $fx('consent.requester') }}</dd></div>
                                        <div><dt>{{ $fx('consent.facility') }}</dt><dd>{{ $nm('hospital') }}</dd></div>
                                        <div><dt>{{ $fx('consent.purpose') }}</dt><dd>{{ $fx('consent.purpose_value') }}</dd></div>
                                        <div><dt>{{ $fx('consent.scope') }}</dt><dd>{{ $fx('consent.scope_value') }}</dd></div>
                                        <div><dt>{{ $fx('consent.duration') }}</dt><dd>{{ $fx('consent.duration_value') }}</dd></div>
                                    </dl>
                                    <p class="dl-foot">{{ $fx('consent.nothing_shared') }}</p>
                                    <span class="dl-btn dl-btn--pri dl-tap" style="--t:3.2s">{{ $fx('consent.approve') }}</span>
                                    <span class="dl-btn">{{ $fx('consent.deny') }}</span>
                                </div>
                            </div>
                            <div class="dl-scr" data-ms="3200">
                                <div class="dl-app">
                                    <div class="dl-done dl-anim-pop">
                                        <span class="dl-done__ic">{!! $exIcon('circle-check', 30) !!}</span>
                                        <b>{{ $fx('consent.granted') }}</b>
                                        <p>{{ $fx('consent.granted_body', ['facility' => $nm('hospital')]) }}</p>
                                    </div>
                                </div>
                            </div>
                            @include('public._download_flows_tabbar', ['active' => 'home'])
                        </div>

                        {{-- Access log ─────────────────────────── --}}
                        <div class="dl-flow" id="dl-flow-access" role="tabpanel" aria-labelledby="dl-tab-access" data-flow="access" hidden>
                            <div class="dl-scr is-on" data-ms="5600">
                                <div class="dl-app">
                                    <h4 class="dl-title">{{ $fx('access.title') }}</h4>
                                    <p class="dl-subt">{{ $fx('access.subtitle') }}</p>
                                    <p class="dl-assure">{!! $exIcon('lock', 14) !!}{{ $fx('access.assurance') }}</p>
                                    <ul class="dl-list" role="list">
                                        @foreach (__('public_download.flows.access.entries') as $i => $e)
                                            <li class="dl-row dl-anim-in" style="--d:{{ 0.5 + $i * 0.9 }}s">
                                                <span class="dl-row__ic">{!! $exIcon($i === 3 ? 'shield-alert' : 'eye', 15) !!}</span>
                                                <span class="dl-row__m"><b>{{ $i === 2 ? $nm('pharmacy') : ($i === 1 ? $nm('clinic') : $nm('hospital')) }}</b><small>{{ $e['role'] }} · {{ $e['section'] }}</small></span>
                                                <span class="dl-row__r"><small>{{ $e['time'] }}</small><em class="dl-chip {{ $i === 3 ? 'dl-chip--warn' : 'dl-chip--info' }}">{{ $e['basis'] }}</em></span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                            @include('public._download_flows_tabbar', ['active' => 'profile'])
                        </div>

                        {{-- Prescriptions ─────────────────────── --}}
                        <div class="dl-flow" id="dl-flow-rx" role="tabpanel" aria-labelledby="dl-tab-rx" data-flow="rx" hidden>
                            <div class="dl-scr is-on" data-ms="2800">
                                <div class="dl-app">
                                    <h4 class="dl-title">{{ $fx('rx.title') }}</h4>
                                    <div class="dl-item dl-anim-in dl-tap" style="--d:1s;--t:2.1s">
                                        <span class="dl-row__ic">{!! $exIcon('pill', 15) !!}</span>
                                        <span class="dl-row__m"><b>{{ $fx('rx.medicine') }}</b><small>{{ $nm('hospital') }}</small></span>
                                        <em class="dl-chip dl-chip--good">{{ $fx('rx.status_active') }}</em>
                                    </div>
                                    <div class="dl-skel"></div><div class="dl-skel dl-skel--s"></div>
                                </div>
                                <div class="dl-notif dl-anim-drop" style="--d:.3s">
                                    <span class="dl-notif__ic">{!! $exIcon('pill', 16) !!}</span>
                                    <span><b>{{ $fx('rx.notif_title') }}</b><small>{{ $fx('rx.notif_body', ['doctor' => $nm('doctor')]) }}</small></span>
                                </div>
                            </div>
                            <div class="dl-scr" data-ms="3400">
                                <div class="dl-app">
                                    <h4 class="dl-title">{{ $fx('rx.detail_title') }}</h4>
                                    <dl class="dl-kv dl-kv--card">
                                        <div><dt>{{ $fx('rx.issued_by') }}</dt><dd>{{ $nm('doctor') }} · {{ $nm('hospital') }}</dd></div>
                                    </dl>
                                    <p class="dl-over">{{ $fx('rx.medications') }}</p>
                                    <div class="dl-med">
                                        <b>{{ $fx('rx.medicine') }}</b>
                                        <span>{{ $fx('rx.dose') }}</span>
                                        <span>{!! $exIcon('clock', 13) !!}{{ $fx('rx.duration') }}</span>
                                    </div>
                                    <span class="dl-btn dl-btn--pri dl-tap" style="--t:2.4s">{!! $exIcon('qr-code', 15) !!}{{ $fx('rx.show') }}</span>
                                </div>
                            </div>
                            <div class="dl-scr" data-ms="3200">
                                <div class="dl-app dl-app--center">
                                    <h4 class="dl-title">{{ $fx('rx.show') }}</h4>
                                    <span class="dl-fqr dl-anim-pop" role="img" aria-label="{{ $fx('sample') }}">{!! $fxQr !!}</span>
                                    <p class="dl-over">{{ $fx('rx.ref_label') }}</p>
                                    <p class="dl-ref">{{ $fx('rx.ref') }}</p>
                                    <p class="dl-foot">{{ $fx('rx.scan_hint') }}</p>
                                    <em class="dl-chip dl-chip--info">{{ $fx('rx.expires') }}</em>
                                </div>
                            </div>
                            <div class="dl-scr" data-ms="2800">
                                <div class="dl-app">
                                    <h4 class="dl-title">{{ $fx('rx.title') }}</h4>
                                    <div class="dl-item">
                                        <span class="dl-row__ic">{!! $exIcon('pill', 15) !!}</span>
                                        <span class="dl-row__m"><b>{{ $fx('rx.medicine') }}</b><small>{{ $fx('rx.dispensed_at', ['place' => $nm('pharmacy')]) }}</small></span>
                                        <em class="dl-chip dl-chip--done dl-anim-pop" style="--d:.5s">{!! $exIcon('check', 12) !!}{{ $fx('rx.status_dispensed') }}</em>
                                    </div>
                                    <div class="dl-skel"></div><div class="dl-skel dl-skel--s"></div>
                                </div>
                            </div>
                            @include('public._download_flows_tabbar', ['active' => 'home'])
                        </div>

                        {{-- Results ───────────────────────────── --}}
                        <div class="dl-flow" id="dl-flow-results" role="tabpanel" aria-labelledby="dl-tab-results" data-flow="results" hidden>
                            <div class="dl-scr is-on" data-ms="2800">
                                <div class="dl-app">
                                    <h4 class="dl-title">{{ $fx('results.title') }}</h4>
                                    <div class="dl-item dl-anim-in dl-tap" style="--d:1s;--t:2.1s">
                                        <span class="dl-row__ic">{!! $exIcon('flask-conical', 15) !!}</span>
                                        <span class="dl-row__m"><b>{{ $fx('results.test') }}</b><small>{{ $nm('lab') }}</small></span>
                                        <em class="dl-chip dl-chip--good">{{ $fx('results.ready') }}</em>
                                    </div>
                                    <div class="dl-skel"></div><div class="dl-skel dl-skel--s"></div>
                                </div>
                                <div class="dl-notif dl-anim-drop" style="--d:.3s">
                                    <span class="dl-notif__ic">{!! $exIcon('flask-conical', 16) !!}</span>
                                    <span><b>{{ $fx('results.notif_title') }}</b><small>{{ $fx('results.notif_body') }}</small></span>
                                </div>
                            </div>
                            <div class="dl-scr" data-ms="4200">
                                <div class="dl-app">
                                    <h4 class="dl-title">{{ $fx('results.test') }}</h4>
                                    <p class="dl-subt">{{ $nm('lab') }}</p>
                                    @foreach (__('public_download.flows.results.rows') as $i => $r)
                                        <div class="dl-res dl-anim-in {{ $i === 0 ? 'dl-res--flag' : '' }}" style="--d:{{ 0.3 + $i * 0.5 }}s">
                                            <div class="dl-res__top"><b>{{ $r['name'] }}</b><em class="dl-chip {{ $i === 0 ? 'dl-chip--warn' : 'dl-chip--good' }}">{{ $r['flag'] }}</em></div>
                                            <div class="dl-res__val">{{ $r['value'] }}</div>
                                            <small>{{ $fx('results.range') }}: {{ $r['range'] }}</small>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @include('public._download_flows_tabbar', ['active' => 'home'])
                        </div>

                        {{-- Health ID ─────────────────────────── --}}
                        <div class="dl-flow" id="dl-flow-hid" role="tabpanel" aria-labelledby="dl-tab-hid" data-flow="hid" hidden>
                            <div class="dl-scr is-on" data-ms="3400">
                                <div class="dl-app">
                                    <div class="dl-idc dl-anim-pop">
                                        <div class="dl-idc__l">
                                            <span class="dl-idc__lbl">{{ $fx('hid.card_label') }}</span>
                                            <b class="dl-idc__nm">{{ $nm('patient') }}</b>
                                            <span class="dl-idc__num">{{ $nm('hid') }}</span>
                                            <span class="dl-idc__meta">{{ $fx('hid.blood') }} · O+</span>
                                            <em class="dl-chip dl-chip--good">{!! $exIcon('shield-check', 12) !!}{{ $fx('hid.status') }}</em>
                                        </div>
                                        <img class="dl-idc__body" src="{{ asset('images/health-id/living/adult-female-base.png') }}" alt="" width="61" height="144" loading="lazy">
                                    </div>
                                    <span class="dl-btn dl-btn--pri dl-tap" style="--t:2.4s">{!! $exIcon('qr-code', 15) !!}{{ $fx('hid.present') }}</span>
                                    <div class="dl-skel"></div><div class="dl-skel dl-skel--s"></div>
                                </div>
                            </div>
                            <div class="dl-scr" data-ms="3200">
                                <div class="dl-app dl-app--center dl-app--navy">
                                    <h4 class="dl-title">{{ $fx('hid.present_title') }}</h4>
                                    <span class="dl-fqr dl-fqr--lg dl-anim-pop" role="img" aria-label="{{ $fx('sample') }}">{!! $fxQr !!}</span>
                                    <b class="dl-idc__nm">{{ $nm('patient') }}</b>
                                    <span class="dl-idc__num">{{ $nm('hid') }}</span>
                                    <p class="dl-over">{{ $fx('hid.scan_title') }}</p>
                                    <p class="dl-foot">{{ $fx('hid.scan_body') }}</p>
                                </div>
                            </div>
                            @include('public._download_flows_tabbar', ['active' => 'health_id'])
                        </div>

                        {{-- Appointments ──────────────────────── --}}
                        <div class="dl-flow" id="dl-flow-appts" role="tabpanel" aria-labelledby="dl-tab-appts" data-flow="appts" hidden>
                            <div class="dl-scr is-on" data-ms="2400">
                                <div class="dl-app">
                                    <h4 class="dl-title">{{ $fx('appts.title') }}</h4>
                                    <p class="dl-over">{{ $fx('appts.upcoming') }}</p>
                                    <div class="dl-skel"></div><div class="dl-skel dl-skel--s"></div>
                                    <span class="dl-btn dl-btn--pri dl-tap" style="--t:1.6s">{!! $exIcon('calendar-plus', 15) !!}{{ $fx('appts.book') }}</span>
                                </div>
                            </div>
                            <div class="dl-scr" data-ms="3000">
                                <div class="dl-app">
                                    <h4 class="dl-title">{{ $fx('appts.book') }}</h4>
                                    <dl class="dl-kv dl-kv--card"><div><dt>{{ $nm('clinic') }}</dt><dd>{{ $fx('appts.service') }}</dd></div></dl>
                                    <p class="dl-over">{{ $fx('appts.pick') }} · {{ $fx('appts.day') }}</p>
                                    <div class="dl-slots">
                                        @foreach (__('public_download.flows.appts.slots') as $i => $s)
                                            <span class="dl-slot {{ $i === 1 ? 'dl-tap dl-slot--pick' : '' }}" @if ($i === 1) style="--t:1.6s" @endif>{{ $s }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="dl-scr" data-ms="2800">
                                <div class="dl-app">
                                    <h4 class="dl-title">{{ $fx('appts.title') }}</h4>
                                    <p class="dl-over">{{ $fx('appts.upcoming') }}</p>
                                    <div class="dl-item dl-anim-in" style="--d:.3s">
                                        <span class="dl-row__ic">{!! $exIcon('calendar-clock', 15) !!}</span>
                                        <span class="dl-row__m"><b>{{ $fx('appts.day') }} · {{ __('public_download.flows.appts.slots')[1] }}</b><small>{{ $nm('clinic') }}</small></span>
                                        <em class="dl-chip dl-chip--good">{{ $fx('appts.booked') }}</em>
                                    </div>
                                    <div class="dl-skel"></div>
                                </div>
                            </div>
                            @include('public._download_flows_tabbar', ['active' => 'home'])
                        </div>

                        {{-- Family ────────────────────────────── --}}
                        <div class="dl-flow" id="dl-flow-family" role="tabpanel" aria-labelledby="dl-tab-family" data-flow="family" hidden>
                            <div class="dl-scr is-on" data-ms="2800">
                                <div class="dl-app">
                                    <h4 class="dl-title">{{ $fx('family.title') }}</h4>
                                    <p class="dl-over">{{ $fx('family.circle') }}</p>
                                    <div class="dl-item dl-anim-in dl-tap" style="--d:.3s;--t:2s">
                                        <span class="dl-row__ic">{!! $exIcon('baby', 15) !!}</span>
                                        <span class="dl-row__m"><b>{{ $nm('child') }}</b><small>{{ $fx('family.child_rel') }}</small></span>
                                        {!! $exIcon('chevron-right', 16) !!}
                                    </div>
                                    <div class="dl-item dl-anim-in" style="--d:.7s">
                                        <span class="dl-row__ic">{!! $exIcon('user-round', 15) !!}</span>
                                        <span class="dl-row__m"><b>{{ $nm('parent') }}</b><small>{{ $fx('family.parent_rel') }}</small></span>
                                        {!! $exIcon('chevron-right', 16) !!}
                                    </div>
                                </div>
                            </div>
                            <div class="dl-scr" data-ms="3000">
                                <div class="dl-app">
                                    <div class="dl-idc dl-anim-pop">
                                        <div class="dl-idc__l">
                                            <span class="dl-idc__lbl">{{ $fx('hid.card_label') }}</span>
                                            <b class="dl-idc__nm">{{ $nm('child') }}</b>
                                            <span class="dl-idc__num">CM-HID 0000 0000 0001</span>
                                            <em class="dl-chip dl-chip--good">{!! $exIcon('shield-check', 12) !!}{{ $fx('hid.status') }}</em>
                                        </div>
                                        <img class="dl-idc__body" src="{{ asset('images/health-id/living/child-boy-base.png') }}" alt="" width="61" height="144" loading="lazy">
                                    </div>
                                    <span class="dl-btn dl-btn--pri">{!! $exIcon('qr-code', 15) !!}{{ $fx('family.present', ['name' => $nm('child')]) }}</span>
                                </div>
                            </div>
                            @include('public._download_flows_tabbar', ['active' => 'profile'])
                        </div>
                    </div>
                </div>
                <div class="dl-fx__meta">
                    <span class="dl-sample">{{ $fx('sample') }}</span>
                    <button type="button" class="dl-fx__pp" data-dl-pp data-play="{{ $fx('play') }}" data-pause="{{ $fx('pause') }}" hidden>{{ $fx('pause') }}</button>
                </div>
                <p class="dl-fx__note">{{ $fx('sample_note') }}</p>
            </div>
        </div>
    </div>
</section>
