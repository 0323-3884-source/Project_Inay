<form method="GET" class="admin-card dswd-filters" action="{{ url()->current() }}">
    @if(request()->routeIs('dswd.beneficiaries'))<label>Search beneficiaries<input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name, ID or barangay" maxlength="100"></label>@endif
    @foreach (['barangay' => 'Barangay', 'municipality_city' => 'Municipality/City'] as $key => $label)
        <label>{{ $label }}<select name="{{ $key }}"><option value="">All</option><option value="__unrecorded__" @selected(($filters[$key] ?? '') === '__unrecorded__')>Not recorded</option>@foreach($options[$key] as $value)<option value="{{ $value }}" @selected(($filters[$key] ?? '') === $value)>{{ $value }}</option>@endforeach</select></label>
    @endforeach
    <label>Pregnancy status<select name="pregnancy_status"><option value="">All</option>@foreach(\App\Support\DswdStatistics::PREGNANCY as $key => $label)<option value="{{ $key }}" @selected(($filters['pregnancy_status'] ?? '') === $key)>{{ $label }}</option>@endforeach</select></label>
    <label>Registration date from<input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></label>
    <label>Registration date to<input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></label>
    @if(isset($type))<label>Report<select name="report">@foreach(\App\Http\Controllers\DswdController::REPORTS as $key => $label)<option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>@endforeach</select></label>@endif
    <div class="dswd-actions"><button class="dswd-button" type="submit">Apply filters</button><a class="dswd-button secondary" href="{{ url()->current() }}">Reset</a></div>
</form>
