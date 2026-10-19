@extends('layouts.portal')

@section('title', __('blog.admin.title') . ' — OpesCare')

@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
<div class="od-page">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('blog.admin.title') }}</h1>
        <p class="od-page__sub">{{ __('blog.admin.subtitle') }}</p>
    </div>
    <div class="od-row">
        <a href="{{ route('blog.index') }}" class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="newspaper"></i> {{ __('blog.title') }}</a>
    </div>
</div>

@if(session('status'))
    <div class="oda-gb-alert oda-gb-alert--ok"><i data-lucide="check-circle"></i><div>{{ session('status') }}</div></div>
@endif

<nav class="od-row" style="margin-bottom:1rem;flex-wrap:wrap">
    @foreach(\App\Models\BlogComment::STATUSES as $s)
        <a href="{{ route('portals.admin.blog.comments', ['status' => $s]) }}"
           class="od-btn od-btn--sm {{ $status === $s ? 'od-btn--primary' : 'od-btn--secondary' }}"
           @if($status === $s) aria-current="page" @endif>{{ __('blog.admin.status_' . $s) }} ({{ $counts[$s] ?? 0 }})</a>
    @endforeach
</nav>

<section class="od-card">
    <div class="od-card__body od-card__body--flush">
    @if($comments->isEmpty())
        <div class="od-empty">
            <div class="od-empty__icon"><i data-lucide="message-circle"></i></div>
            <p class="od-empty__text">{{ __('blog.admin.empty') }}</p>
        </div>
    @else
    <div class="od-table-wrap">
        <table class="od-table">
            <thead><tr>
                <th>{{ __('blog.admin.col_post') }}</th>
                <th>{{ __('blog.admin.col_author') }}</th>
                <th>{{ __('blog.admin.col_comment') }}</th>
                <th>{{ __('blog.admin.col_date') }}</th>
                <th>{{ __('blog.admin.col_actions') }}</th>
            </tr></thead>
            <tbody>
            @foreach($comments as $c)
                <tr>
                    <td>
                        <a href="{{ route('blog.show', $c->post_slug) }}{{ $c->locale === 'en' ? '' : '?lang=' . $c->locale }}#comments">{{ $titles[$c->locale . ':' . $c->post_slug] ?? $c->post_slug }}</a>
                        <div class="od-muted">{{ strtoupper($c->locale) }} @if($c->parent_id)· {{ __('blog.admin.reply_badge') }}@endif</div>
                    </td>
                    <td>{{ $c->author_name }}<div class="od-muted">{{ $c->author_email }}</div></td>
                    <td style="max-width:420px;white-space:pre-line;overflow-wrap:anywhere">{{ $c->body }}</td>
                    <td>{{ $c->created_at->translatedFormat('j M Y H:i') }}</td>
                    <td>
                        <div class="od-row" style="flex-wrap:wrap;gap:.35rem">
                        @foreach(['approved' => 'approve', 'rejected' => 'reject', 'spam' => 'spam', 'pending' => 'restore'] as $to => $label)
                            @if($c->status !== $to)
                            <form method="POST" action="{{ route('portals.admin.blog.comments.status', $c->id) }}" class="oda-inline-form">@csrf
                                <input type="hidden" name="status" value="{{ $to }}">
                                <button type="submit" class="od-btn od-btn--sm {{ $to === 'approved' ? 'od-btn--primary' : 'od-btn--secondary' }}">{{ __('blog.admin.' . $label) }}</button>
                            </form>
                            @endif
                        @endforeach
                        <form method="POST" action="{{ route('portals.admin.blog.comments.destroy', $c->id) }}" class="oda-inline-form" data-confirm="{{ __('blog.admin.delete_confirm') }}">@csrf @method('DELETE')
                            <button type="submit" class="od-btn od-btn--sm od-btn--danger"><i data-lucide="trash-2"></i> {{ __('blog.admin.delete') }}</button>
                        </form>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div style="padding:1rem">{{ $comments->links() }}</div>
    @endif
    </div>
</section>
</div>
@endsection
