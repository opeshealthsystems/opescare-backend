@if(session('success'))<div class="oda-gb-alert oda-gb-alert--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-gb-alert oda-gb-alert--bad"><i data-lucide="alert-triangle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())<div class="oda-gb-alert oda-gb-alert--bad"><i data-lucide="alert-triangle"></i><div>{{ $errors->first() }}</div></div>@endif
