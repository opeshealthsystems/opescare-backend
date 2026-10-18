{{-- Flash + validation messages in the desk style. --}}
@if(session('success'))
<div class="os-banner os-banner--ok" role="status"><i data-lucide="check-circle-2"></i><div>{{ session('success') }}</div></div>
@endif
@if($errors->any())
<div class="os-banner os-banner--danger" role="alert">
    <i data-lucide="alert-octagon"></i>
    <div><ul class="os-banner__list">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
</div>
@endif
