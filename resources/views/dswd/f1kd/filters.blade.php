@if($errors->any())<ul role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
<form method="get" class="dswd-filters">
@if(!isset($report))<label>Search<input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name or beneficiary ID"></label>@endif
@foreach(['barangay'=>'Barangay','municipality_city'=>'Municipality / City'] as $field=>$label)
<label>{{ $label }}<select name="{{ $field }}"><option value="">All areas</option>@foreach($options[$field] as $value)<option value="{{ $value }}" @selected(($filters[$field] ?? '')===$value)>{{ $value }}</option>@endforeach</select></label>
@endforeach
<label>Classification<select name="classification"><option value="">All classifications</option>@foreach(\App\Support\F1kdCompliance::CLASSES as $value=>$label)<option value="{{ $value }}" @selected(($filters['classification'] ?? '')===$value)>{{ $label }}</option>@endforeach</select></label>
<label>Reporting month<input type="month" name="month" value="{{ $filters['month'] }}" max="{{ now()->format('Y-m') }}" required></label>
<label>Compliance status<select name="status"><option value="">All statuses</option>@foreach(\App\Support\F1kdCompliance::STATUSES as $value=>$label)<option value="{{ $value }}" @selected(($filters['status'] ?? '')===$value)>{{ $label }}</option>@endforeach</select></label>
<div class="dswd-actions"><button class="dswd-button">Apply filters</button><a class="dswd-button secondary" href="{{ url()->current() }}">Reset</a></div>
</form>
