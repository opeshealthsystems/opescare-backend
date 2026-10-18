{{--
    One identity pair — the card the desk (mpi/index) repeats and the pair page
    (mpi/show) renders alone. Expects $c (MpiCandidate with sourcePatient /
    targetPatient / reviewer / merge loaded) and $footprint (per-patient
    counts from AdminIdentityReviewController::footprint()).
--}}
    @php
        $a = $c->sourcePatient;
        $b = $c->targetPatient;
        $fa = $footprint[(string) $c->source_patient_id] ?? ['prescriptions' => 0, 'appointments' => 0, 'encounters' => 0, 'logins' => []];
        $fb = $footprint[(string) $c->target_patient_id] ?? ['prescriptions' => 0, 'appointments' => 0, 'encounters' => 0, 'logins' => []];
        $tierKey = $c->tier();
        $tierBadge = ['deterministic' => 'od-pill--urgent', 'probable' => 'od-pill--pending', 'possible' => 'od-pill--neutral'][$tierKey];
    @endphp
    <section class="od-card oda-ga-mpi">
        <div class="od-card__head oda-ga-mpi__head">
            <a href="{{ route('portals.admin.mpi.candidates.show', $c->id) }}" class="oda-ga-mpi__score" aria-label="{{ __('admin_desk.mpi_open_pair') }}"><i data-lucide="percent"></i>{{ number_format((float) $c->match_score, 0) }}</a>
            <span class="od-pill {{ $tierBadge }}">{{ __('mpi.tier_' . $tierKey) }}</span>
            <span class="oda-ga-muted">
                {{ __('mpi.detected_' . ($c->detected_by ?: 'scan')) }} · {{ $c->created_at?->diffForHumans() }}
                @if($c->reviewer) · {{ __('mpi.reviewed_by', ['name' => $c->reviewer->name]) }} @endif
            </span>
            <div class="oda-ga-mpi__evidence">
                @foreach($c->match_reasons ?? [] as $reason)
                    @php [$code, $suffix] = array_pad(explode(':', $reason, 2), 2, null); $against = str_ends_with($code, '_differs'); @endphp
                    <span class="od-pill {{ $against ? 'od-pill--urgent' : 'od-pill--ready' }}">{{ __('mpi.reason_' . $code) }}@if($suffix) ({{ $suffix }})@endif</span>
                @endforeach
            </div>
        </div>

        <div class="od-card__body oda-ga-mpi__compare">
            @foreach([[$a, $fa, $c->source_patient_id], [$b, $fb, $c->target_patient_id]] as [$p, $fp, $pid])
                <div>
                    <p class="oda-ga-mpi__colhead">
                        @if($c->status === 'merged' && (string) $c->survivor_patient_id === (string) $pid)
                            <i data-lucide="crown"></i> {{ __('mpi.col_survivor') }}
                        @elseif($c->status === 'merged')
                            <i data-lucide="archive"></i> {{ __('mpi.col_retired') }}
                        @else
                            <i data-lucide="user"></i> {{ __('mpi.col_record') }}
                        @endif
                    </p>
                    @if(! $p)
                        <p class="oda-ga-muted">{{ __('mpi.record_missing') }}</p>
                    @else
                        <dl class="oda-dl">
                            <dt class="oda-dl__k">{{ __('mpi.f_health_id') }}</dt><dd class="oda-dl__v oda-mono">{{ $p->health_id }}</dd>
                            <dt class="oda-dl__k">{{ __('mpi.f_name') }}</dt><dd class="oda-dl__v">{{ trim($p->first_name . ' ' . ($p->middle_name ? $p->middle_name . ' ' : '') . $p->last_name) }}</dd>
                            <dt class="oda-dl__k">{{ __('mpi.f_dob') }}</dt>
                                @php $dob = rescue(fn () => $p->date_of_birth?->translatedFormat('d M Y'), null, false); @endphp
                                <dd class="oda-dl__v {{ $dob ? '' : 'is-missing' }}">{{ $dob ?: __('mpi.f_missing') }}@if($p->is_dob_estimated) <span class="oda-ga-muted">({{ __('mpi.f_estimated') }})</span>@endif</dd>
                            <dt class="oda-dl__k">{{ __('mpi.f_sex') }}</dt><dd class="oda-dl__v {{ $p->sex ? '' : 'is-missing' }}">{{ $p->sex ? __('mpi.sex_' . ($p->sex instanceof \BackedEnum ? $p->sex->value : $p->sex)) : __('mpi.f_missing') }}</dd>
                            <dt class="oda-dl__k">{{ __('mpi.f_phone') }}</dt><dd class="oda-dl__v {{ $p->phone_number_hash ? '' : 'is-missing' }}">{{ $p->phone_number_hash ? __('mpi.f_on_file') : __('mpi.f_missing') }}</dd>
                            <dt class="oda-dl__k">{{ __('mpi.f_national_id') }}</dt><dd class="oda-dl__v {{ $p->national_id_number ? '' : 'is-missing' }}">{{ $p->national_id_number ? __('mpi.f_on_file') : __('mpi.f_missing') }}</dd>
                            <dt class="oda-dl__k">{{ __('mpi.f_logins') }}</dt><dd class="oda-dl__v {{ $fp['logins'] ? '' : 'is-missing' }}">{{ $fp['logins'] ? implode(', ', $fp['logins']) : __('mpi.f_none') }}</dd>
                            <dt class="oda-dl__k">{{ __('mpi.f_history') }}</dt><dd class="oda-dl__v">{{ __('mpi.f_history_counts', ['rx' => $fp['prescriptions'], 'appt' => $fp['appointments'], 'enc' => $fp['encounters']]) }}</dd>
                            <dt class="oda-dl__k">{{ __('mpi.f_created') }}</dt><dd class="oda-dl__v">{{ $p->created_at?->translatedFormat('d M Y') }}</dd>
                            <dt class="oda-dl__k">{{ __('mpi.f_status') }}</dt><dd class="oda-dl__v">@enum($p->identity_status instanceof \BackedEnum ? $p->identity_status->value : $p->identity_status)</dd>
                        </dl>
                    @endif
                </div>
            @endforeach
        </div>

        @if($c->status === 'pending_review' && $a && $b)
            <div class="oda-ga-mpi__actions">
                {{--
                    A merge needs three things a click cannot supply: which
                    record survives, why, and a moment's pause. The survivor
                    keeps its Health ID; the other becomes an alias that still
                    resolves. Nothing is deleted, and the whole thing can be
                    undone from the Merged tab.
                --}}
                <form method="POST" action="{{ route('portals.admin.mpi.candidates.merge', $c->id) }}"
                      data-confirm="{{ __('mpi.confirm_merge') }}" class="oda-form">
                    @csrf
                    <div class="oda-label oda-form__full">
                        <span class="oda-label__text">{{ __('mpi.survivor_label') }}</span>
                        <div class="oda-ga-mpi__radios">
                            <label class="oda-check"><input type="radio" name="survivor_patient_id" value="{{ $a->id }}" required> {{ $a->health_id }} — {{ __('mpi.survivor_hint', ['n' => $fa['prescriptions'] + $fa['appointments'] + $fa['encounters']]) }}</label>
                            <label class="oda-check"><input type="radio" name="survivor_patient_id" value="{{ $b->id }}" required> {{ $b->health_id }} — {{ __('mpi.survivor_hint', ['n' => $fb['prescriptions'] + $fb['appointments'] + $fb['encounters']]) }}</label>
                        </div>
                    </div>
                    <label class="oda-label oda-form__full" for="reason-{{ $c->id }}">
                        <span class="oda-label__text">{{ __('mpi.reason_label') }}</span>
                        <textarea id="reason-{{ $c->id }}" name="reason" class="oda-textarea" rows="2" minlength="10" maxlength="1000" required
                                  placeholder="{{ __('mpi.reason_placeholder') }}"></textarea>
                    </label>
                    <div class="oda-form__actions">
                        <button type="submit" class="od-btn od-btn--danger od-btn--sm"><i data-lucide="git-merge"></i> {{ __('mpi.btn_merge') }}</button>
                    </div>
                </form>

                <form method="POST" action="{{ route('portals.admin.mpi.candidates.reject', $c->id) }}" class="oda-ga-rowform" data-confirm="{{ __('admin_desk.confirm_reject_pair') }}">
                    @csrf
                    <input type="text" name="reason" class="od-input" maxlength="1000" placeholder="{{ __('mpi.reject_placeholder') }}" aria-label="{{ __('mpi.reject_placeholder') }}">
                    <button type="submit" class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="user-x"></i> {{ __('mpi.btn_reject') }}</button>
                </form>
            </div>
        @elseif($c->status === 'merged' && $c->merge && $c->merge->isMerged())
            <div class="oda-ga-mpi__actions">
                <p class="oda-ga-prose">
                    {{ __('mpi.merge_summary', ['moved' => $c->merge->movedRowCount(), 'kept' => $c->merge->keptRowCount(), 'filled' => count($c->merge->survivorship ?? [])]) }}
                    @if($c->review_reason) — <em>{{ $c->review_reason }}</em>@endif
                </p>
                <details>
                    <summary class="oda-ga-summary"><span class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="undo-2"></i> {{ __('mpi.btn_unmerge') }}</span></summary>
                    <form method="POST" action="{{ route('portals.admin.mpi.merges.unmerge', $c->merge->id) }}" class="oda-form"
                          data-confirm="{{ __('mpi.confirm_unmerge') }}">
                        @csrf
                        <label class="oda-label oda-form__full" for="unmerge-{{ $c->id }}">
                            <span class="oda-label__text">{{ __('mpi.unmerge_reason_label') }}</span>
                            <textarea id="unmerge-{{ $c->id }}" name="reason" class="oda-textarea" rows="2" minlength="10" maxlength="1000" required></textarea>
                        </label>
                        <div class="oda-form__actions">
                            <button type="submit" class="od-btn od-btn--danger od-btn--sm"><i data-lucide="undo-2"></i> {{ __('mpi.btn_unmerge_confirm') }}</button>
                        </div>
                    </form>
                </details>
            </div>
        @elseif($c->review_reason)
            <div class="oda-ga-mpi__actions"><p class="oda-ga-prose"><em>{{ $c->review_reason }}</em></p></div>
        @endif
    </section>
