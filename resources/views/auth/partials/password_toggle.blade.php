{{--
    Password visibility toggle button. Sits inside a .f-auth-pass wrapper next
    to the password input it controls. Wired by the [data-f-toggle] listener in
    layouts/auth.blade.php — no icon-font, no external JS.

    Usage: @include('auth.partials.password_toggle', ['for' => 'password'])

    The eye / eye-off glyphs come from partials/finder/icon (added to the
    shared set at integration).
--}}
<button type="button" class="f-auth-toggle" data-f-toggle="{{ $for }}" aria-pressed="false"
        aria-label="{{ __('finder_auth.toggle_password') }}">
    <span class="f-auth-toggle__show" aria-hidden="true">@include('partials.finder.icon', ['name' => 'eye', 'size' => 20])</span>
    <span class="f-auth-toggle__hide" aria-hidden="true">@include('partials.finder.icon', ['name' => 'eye-off', 'size' => 20])</span>
</button>
