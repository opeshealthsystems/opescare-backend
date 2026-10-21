{{-- Bottom tab bar of the patient app (app/(tabs)/_layout.tsx order). --}}
<div class="dl-tb" aria-hidden="true">
    @foreach (['home' => 'activity', 'health_id' => 'id-card', 'messages' => 'message-circle', 'profile' => 'user'] as $k => $icon)
        <span class="{{ $active === $k ? 'is-on' : '' }}">@include('partials.finder.icon', ['name' => $icon, 'size' => 16]){{ __('public_download.flows.tabs.' . $k) }}</span>
    @endforeach
</div>
