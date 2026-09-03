@section('sidebar_role_badge')
<div class="sidebar-role-badge sidebar-role-badge--primary">
    <i data-lucide="shield-check"></i>
    {{ __('public.adm_cc_sidebar_super_admin') }}
</div>
@endsection
@section('sidebar_user_role', __('public.adm_cc_sidebar_super_admin'))

@section('sidebar_nav')
@include('portals.admin._admin_nav')
@endsection
