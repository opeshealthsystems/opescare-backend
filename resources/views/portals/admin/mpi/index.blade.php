@extends('layouts.portal')
@section('title', __('mpi.title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', 'Admin')
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('mpi.title'))

@section('head')
<style>
  .dr-tabs{display:flex;gap:.5rem;border-bottom:1px solid var(--p-border);margin-bottom:var(--p-space-5)}
  .dr-tab{display:inline-flex;align-items:center;gap:.4rem;padding:.6rem 1rem;font-size:.875rem;font-weight:600;color:var(--p-text-muted);text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-1px}
  .dr-tab.active{color:var(--p-primary);border-bottom-color:var(--p-primary)}
  .dr-tab-count{display:inline-flex;align-items:center;justify-content:center;min-width:1.35rem;padding:0 .35rem;height:1.35rem;border-radius:999px;background:var(--p-surface-2);font-size:.7rem;font-weight:700}
  .dr-tab.active .dr-tab-count{background:rgba(15,76,129,.12);color:var(--p-primary)}
  .dr-reasonbar{display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:var(--p-space-4)}
  .dr-reason-chip{padding:.3rem .7rem;border:1px solid var(--p-border);border-radius:999px;font-size:.75rem;font-weight:600;color:var(--p-text-2);text-decoration:none}
  .dr-reason-chip.active{background:var(--p-primary);border-color:var(--p-primary);color:#fff}
  .dr-card{border:1px solid var(--p-border);border-radius:var(--p-radius,8px);margin-bottom:var(--p-space-4);overflow:hidden}
  .dr-card-head{display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;padding:.75rem 1rem;background:var(--p-surface-2);border-bottom:1px solid var(--p-border)}
  .dr-card-title{font-weight:700;font-size:.95rem}
  .dr-card-sub{font-size:.75rem;color:var(--p-text-muted)}
  .dr-compare{display:grid;grid-template-columns:1fr 1fr;gap:0}
  .dr-col{padding:.85rem 1rem}
  .dr-col + .dr-col{border-left:1px solid var(--p-border)}
  .dr-col-head{display:flex;align-items:center;gap:.5rem;font-size:.7rem;letter-spacing:.06em;text-transform:uppercase;font-weight:700;color:var(--p-text-muted);margin-bottom:.5rem}
  .dr-field{display:flex;gap:.5rem;font-size:.8rem;padding:.18rem 0}
  .dr-field dt{flex:0 0 7rem;color:var(--p-text-muted)}
  .dr-field dd{margin:0;font-weight:600;word-break:break-word}
  .dr-field dd.is-missing{font-weight:400;color:var(--p-text-muted);font-style:italic}
  .dr-actions{padding:.7rem 1rem;border-top:1px solid var(--p-border);background:var(--p-surface-1)}
  .dr-actions .form-label{font-size:.8rem}
  .dr-actions textarea{font-size:.8rem}
  .dr-actions-row{display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;margin-top:.5rem}
  .dr-score{display:inline-flex;align-items:center;gap:.35rem;font-variant-numeric:tabular-nums;font-weight:700}
  .dr-evidence{display:flex;gap:.3rem;flex-wrap:wrap;margin-top:.35rem}
  .dr-ev{font-size:.7rem;padding:.15rem .5rem;border-radius:999px;border:1px solid var(--p-border)}
  .dr-ev--for{background:rgba(22,163,74,.08);border-color:rgba(22,163,74,.35);color:#166534}
  .dr-ev--against{background:rgba(220,38,38,.08);border-color:rgba(220,38,38,.35);color:#991b1b}
  .dr-note{font-size:.8rem;color:var(--p-text-muted);margin-bottom:var(--p-space-4)}
  .dr-survivor label{display:flex;align-items:center;gap:.4rem;font-size:.8rem;font-weight:600;cursor:pointer}
  @media (max-width:860px){.dr-compare{grid-template-columns:1fr}.dr-col + .dr-col{border-left:0;border-top:1px solid var(--p-border)}}
</style>
@endsection

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('mpi.title') }}</h1>
        <p class="page-subtitle">{{ __('mpi.subtitle', ['count' => number_format($registerSize)]) }}</p>
    </div>
    <form method="POST" action="{{ route('portals.admin.mpi.detect') }}" class="inline-form">
        @csrf
        <button type="submit" class="btn btn-secondary">
            <i data-lucide="scan-search"></i> {{ __('mpi.btn_detect') }}
        </button>
    </form>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ $errors->first() }}</div></div>@endif

<div class="dr-tabs">
    @foreach(['pending' => 'git-merge', 'merged' => 'check-check', 'rejected' => 'x-circle'] as $key => $icon)
        <a href="{{ route('portals.admin.mpi.index', ['tab' => $key]) }}" class="dr-tab {{ $tab === $key ? 'active' : '' }}">
            <i data-lucide="{{ $icon }}"></i>
            <span>{{ __('mpi.tab_' . $key) }}</span>
            <span class="dr-tab-count">{{ $counts[$key] }}</span>
        </a>
    @endforeach
</div>

@if($tab === 'pending')
    <div class="alert alert-warning mb-6">
        <i data-lucide="shield-alert"></i>
        <div>{{ __('mpi.merge_warning') }}</div>
    </div>

    {{--
        The patient at the counter holding two cards is evidence the scan
        cannot see. An operator names both Health IDs and the pair lands on
        the desk whatever it scores; the chips then show where the machine
        agrees and where it does not.
    --}}
    <details class="panel mb-6" {{ old('health_id_a') ? 'open' : '' }}>
        <summary class="btn btn-secondary btn-sm"><i data-lucide="git-compare"></i> {{ __('mpi.raise_title') }}</summary>
        <form method="POST" action="{{ route('portals.admin.mpi.candidates.raise') }}" class="dr-actions-row" style="padding:.75rem 1rem;align-items:flex-end">
            @csrf
            <div>
                <label class="form-label" for="raise-a">{{ __('mpi.raise_a') }}</label>
                <input id="raise-a" name="health_id_a" class="form-control" value="{{ old('health_id_a') }}" placeholder="CM-HID-XXXX-XXXX-XXXX" maxlength="64" required style="font-family:monospace">
            </div>
            <div>
                <label class="form-label" for="raise-b">{{ __('mpi.raise_b') }}</label>
                <input id="raise-b" name="health_id_b" class="form-control" value="{{ old('health_id_b') }}" placeholder="CM-HID-XXXX-XXXX-XXXX" maxlength="64" required style="font-family:monospace">
            </div>
            <div style="flex:1;min-width:14rem">
                <label class="form-label" for="raise-note">{{ __('mpi.raise_note') }}</label>
                <input id="raise-note" name="note" class="form-control" value="{{ old('note') }}" maxlength="1000" placeholder="{{ __('mpi.raise_note_placeholder') }}">
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="git-compare"></i> {{ __('mpi.btn_raise') }}</button>
        </form>
    </details>

    <div class="dr-reasonbar">
        <a href="{{ route('portals.admin.mpi.index', ['tab' => 'pending']) }}" class="dr-reason-chip {{ $tier ? '' : 'active' }}">{{ __('mpi.tier_all') }}</a>
        @foreach(['deterministic', 'probable', 'possible'] as $t)
            <a href="{{ route('portals.admin.mpi.index', ['tab' => 'pending', 'tier' => $t]) }}" class="dr-reason-chip {{ $tier === $t ? 'active' : '' }}">
                {{ __('mpi.tier_' . $t) }} ({{ $tierCounts[$t] }})
            </a>
        @endforeach
    </div>
@endif

@if($candidates->isEmpty())
    <div class="panel">
        <div class="empty-state">
            <i data-lucide="{{ $tab === 'pending' ? 'check-circle' : 'inbox' }}"></i>
            <p>{{ __('mpi.empty_' . $tab) }}</p>
        </div>
    </div>
@else
    @foreach($candidates as $c)
        @php
            $a = $c->sourcePatient;
            $b = $c->targetPatient;
            $fa = $footprint[(string) $c->source_patient_id] ?? ['prescriptions' => 0, 'appointments' => 0, 'encounters' => 0, 'logins' => []];
            $fb = $footprint[(string) $c->target_patient_id] ?? ['prescriptions' => 0, 'appointments' => 0, 'encounters' => 0, 'logins' => []];
            $tierKey = $c->tier();
            $tierBadge = ['deterministic' => 'danger', 'probable' => 'warning', 'possible' => 'neutral'][$tierKey];
        @endphp
        <div class="dr-card">
            <div class="dr-card-head">
                <span class="dr-score"><i data-lucide="percent"></i>{{ number_format((float) $c->match_score, 0) }}</span>
                <span class="badge badge-{{ $tierBadge }} badge-sm">{{ __('mpi.tier_' . $tierKey) }}</span>
                <span class="dr-card-sub">
                    {{ __('mpi.detected_' . ($c->detected_by ?: 'scan')) }} · {{ $c->created_at?->diffForHumans() }}
                    @if($c->reviewer) · {{ __('mpi.reviewed_by', ['name' => $c->reviewer->name]) }} @endif
                </span>
                <div class="dr-evidence" style="margin-left:auto">
                    @foreach($c->match_reasons ?? [] as $reason)
                        @php [$code, $suffix] = array_pad(explode(':', $reason, 2), 2, null); $against = str_ends_with($code, '_differs'); @endphp
                        <span class="dr-ev {{ $against ? 'dr-ev--against' : 'dr-ev--for' }}">{{ __('mpi.reason_' . $code) }}@if($suffix) ({{ $suffix }})@endif</span>
                    @endforeach
                </div>
            </div>

            <div class="dr-compare">
                @foreach([[$a, $fa, $c->source_patient_id], [$b, $fb, $c->target_patient_id]] as [$p, $fp, $pid])
                    <div class="dr-col">
                        <div class="dr-col-head">
                            @if($c->status === 'merged' && (string) $c->survivor_patient_id === (string) $pid)
                                <i data-lucide="crown"></i> {{ __('mpi.col_survivor') }}
                            @elseif($c->status === 'merged')
                                <i data-lucide="archive"></i> {{ __('mpi.col_retired') }}
                            @else
                                <i data-lucide="user"></i> {{ __('mpi.col_record') }}
                            @endif
                        </div>
                        @if(! $p)
                            <p class="td-muted">{{ __('mpi.record_missing') }}</p>
                        @else
                            <dl>
                                <div class="dr-field"><dt>{{ __('mpi.f_health_id') }}</dt><dd><code>{{ $p->health_id }}</code></dd></div>
                                <div class="dr-field"><dt>{{ __('mpi.f_name') }}</dt><dd>{{ trim($p->first_name . ' ' . ($p->middle_name ? $p->middle_name . ' ' : '') . $p->last_name) }}</dd></div>
                                <div class="dr-field"><dt>{{ __('mpi.f_dob') }}</dt>
                                    @php $dob = rescue(fn () => $p->date_of_birth?->format('d M Y'), null, false); @endphp
                                    <dd class="{{ $dob ? '' : 'is-missing' }}">{{ $dob ?: __('mpi.f_missing') }}@if($p->is_dob_estimated) <span class="td-muted">({{ __('mpi.f_estimated') }})</span>@endif</dd>
                                </div>
                                <div class="dr-field"><dt>{{ __('mpi.f_sex') }}</dt><dd class="{{ $p->sex ? '' : 'is-missing' }}">{{ $p->sex ? __('mpi.sex_' . ($p->sex instanceof \BackedEnum ? $p->sex->value : $p->sex)) : __('mpi.f_missing') }}</dd></div>
                                <div class="dr-field"><dt>{{ __('mpi.f_phone') }}</dt><dd class="{{ $p->phone_number_hash ? '' : 'is-missing' }}">{{ $p->phone_number_hash ? __('mpi.f_on_file') : __('mpi.f_missing') }}</dd></div>
                                <div class="dr-field"><dt>{{ __('mpi.f_national_id') }}</dt><dd class="{{ $p->national_id_number ? '' : 'is-missing' }}">{{ $p->national_id_number ? __('mpi.f_on_file') : __('mpi.f_missing') }}</dd></div>
                                <div class="dr-field"><dt>{{ __('mpi.f_logins') }}</dt><dd class="{{ $fp['logins'] ? '' : 'is-missing' }}">{{ $fp['logins'] ? implode(', ', $fp['logins']) : __('mpi.f_none') }}</dd></div>
                                <div class="dr-field"><dt>{{ __('mpi.f_history') }}</dt><dd>{{ __('mpi.f_history_counts', ['rx' => $fp['prescriptions'], 'appt' => $fp['appointments'], 'enc' => $fp['encounters']]) }}</dd></div>
                                <div class="dr-field"><dt>{{ __('mpi.f_created') }}</dt><dd>{{ $p->created_at?->format('d M Y') }}</dd></div>
                                <div class="dr-field"><dt>{{ __('mpi.f_status') }}</dt><dd>@enum($p->identity_status instanceof \BackedEnum ? $p->identity_status->value : $p->identity_status)</dd></div>
                            </dl>
                        @endif
                    </div>
                @endforeach
            </div>

            @if($c->status === 'pending_review' && $a && $b)
                <div class="dr-actions">
                    {{--
                        A merge needs three things a click cannot supply: which
                        record survives, why, and a moment's pause. The survivor
                        keeps its Health ID; the other becomes an alias that still
                        resolves. Nothing is deleted, and the whole thing can be
                        undone from the Merged tab.
                    --}}
                    <form method="POST" action="{{ route('portals.admin.mpi.candidates.merge', $c->id) }}"
                          onsubmit="return confirm('{{ __('mpi.confirm_merge') }}')">
                        @csrf
                        <div class="dr-survivor">
                            <span class="form-label">{{ __('mpi.survivor_label') }}</span>
                            <div class="dr-actions-row">
                                <label><input type="radio" name="survivor_patient_id" value="{{ $a->id }}" required> {{ $a->health_id }} — {{ __('mpi.survivor_hint', ['n' => $fa['prescriptions'] + $fa['appointments'] + $fa['encounters']]) }}</label>
                                <label><input type="radio" name="survivor_patient_id" value="{{ $b->id }}" required> {{ $b->health_id }} — {{ __('mpi.survivor_hint', ['n' => $fb['prescriptions'] + $fb['appointments'] + $fb['encounters']]) }}</label>
                            </div>
                        </div>
                        <label class="form-label" for="reason-{{ $c->id }}" style="margin-top:.6rem">{{ __('mpi.reason_label') }}</label>
                        <textarea id="reason-{{ $c->id }}" name="reason" class="form-control" rows="2" minlength="10" maxlength="1000" required
                                  placeholder="{{ __('mpi.reason_placeholder') }}"></textarea>
                        <div class="dr-actions-row">
                            <button type="submit" class="btn btn-danger btn-sm"><i data-lucide="git-merge"></i> {{ __('mpi.btn_merge') }}</button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('portals.admin.mpi.candidates.reject', $c->id) }}" class="dr-actions-row" style="margin-top:.75rem">
                        @csrf
                        <input type="text" name="reason" class="form-control" maxlength="1000" placeholder="{{ __('mpi.reject_placeholder') }}" style="max-width:26rem">
                        <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="user-x"></i> {{ __('mpi.btn_reject') }}</button>
                    </form>
                </div>
            @elseif($c->status === 'merged' && $c->merge && $c->merge->isMerged())
                <div class="dr-actions">
                    <p class="dr-note" style="margin-bottom:.5rem">
                        {{ __('mpi.merge_summary', ['moved' => $c->merge->movedRowCount(), 'kept' => $c->merge->keptRowCount(), 'filled' => count($c->merge->survivorship ?? [])]) }}
                        @if($c->review_reason) — <em>{{ $c->review_reason }}</em>@endif
                    </p>
                    <details>
                        <summary class="btn btn-secondary btn-sm"><i data-lucide="undo-2"></i> {{ __('mpi.btn_unmerge') }}</summary>
                        <form method="POST" action="{{ route('portals.admin.mpi.merges.unmerge', $c->merge->id) }}" style="margin-top:.6rem"
                              onsubmit="return confirm('{{ __('mpi.confirm_unmerge') }}')">
                            @csrf
                            <label class="form-label" for="unmerge-{{ $c->id }}">{{ __('mpi.unmerge_reason_label') }}</label>
                            <textarea id="unmerge-{{ $c->id }}" name="reason" class="form-control" rows="2" minlength="10" maxlength="1000" required></textarea>
                            <div class="dr-actions-row">
                                <button type="submit" class="btn btn-danger btn-sm"><i data-lucide="undo-2"></i> {{ __('mpi.btn_unmerge_confirm') }}</button>
                            </div>
                        </form>
                    </details>
                </div>
            @elseif($c->review_reason)
                <div class="dr-actions"><p class="dr-note" style="margin:0"><em>{{ $c->review_reason }}</em></p></div>
            @endif
        </div>
    @endforeach

    <div class="mt-6">{{ $candidates->links() }}</div>
@endif

@endsection
