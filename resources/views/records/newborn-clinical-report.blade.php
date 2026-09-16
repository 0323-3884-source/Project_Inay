@php
    $generatedAt = now()->timezone(config('app.timezone'));
    $images = collect(['letterhead', 'footer'])->mapWithKeys(fn ($part) => [
        $part => 'data:image/jpeg;base64,'.base64_encode(file_get_contents(public_path('assets/images/mother-record/template-'.$part.'.jpg'))),
    ])->all();
    $value = fn ($item) => $item === null || $item === '' ? 'Not recorded' : $item;
    $latest = $selectedNewborn['latest_growth'] ?? null;
@endphp
<head>
    <meta charset="utf-8">
    <title>{{ $selectedNewborn['code'] ?? 'Project INAY' }} - Newborn Clinical Monitoring Report</title>
    <style>
        {!! file_get_contents(resource_path('css/mother-care-record.css')) !!}
        .newborn-information, .growth-chart { break-inside: avoid; page-break-inside: avoid; }
        .growth-chart { margin: 12pt 0; }
        .growth-chart svg { display: block; width: 100%; height: auto; }
        .history { font-size: 8pt; }
        .history td, .history th { overflow-wrap: anywhere; white-space: normal; }
        .document-title h2 { font-size: 11pt; }
    </style>
</head>
<body>
@include('records.partials.branding')
<div class="document-title">
    <h1>PROJECT INAY</h1>
    <h2>NEWBORN / NEONATAL CLINICAL MONITORING REPORT</h2>
    <p>Date generated: {{ $generatedAt->format('d M Y, h:i A T') }}<br>
        Program staff: {{ $staff->full_name }} · {{ $staff->role_label }}</p>
</div>
@if(! $selectedNewborn)
    <p>No newborn is selected. No newborn clinical records are available for this report.</p>
@else
    <section class="newborn-information">
        <h2 class="section-title">Newborn Information</h2>
        @include('records.partials.fields', ['fields' => [
            'Child / newborn name' => $selectedNewborn['name'],
            'Child record ID' => $selectedNewborn['code'],
            'Mother name' => $selectedNewborn['mother']->full_name,
            'Birth date' => $selectedNewborn['birth_date_label'],
            'Age' => $selectedNewborn['age_months'] === null ? 'Not recorded' : $selectedNewborn['age_months'].' months',
            'Sex' => $selectedNewborn['sex'],
            'Facility' => $selectedNewborn['infant']->facility,
            'Birth weight' => $formatNumber($selectedNewborn['birth_weight'], ' kg'),
            'Birth length' => $formatNumber($selectedNewborn['birth_height'], ' cm'),
            'Latest head circumference' => $formatNumber($latest?->head_circumference, ' cm'),
            'Latest weight' => $formatNumber($latest?->weight, ' kg'),
            'Latest length / height' => $formatNumber($latest?->height, ' cm'),
            'Latest measurement date' => $latest?->measured_at?->format('d M Y'),
            'Clinical / growth status' => $selectedNewborn['status_label'].' / '.$selectedNewborn['growth_label'],
        ]])
    </section>

    <section class="growth-chart">
        <h2 class="section-title">Longitudinal Weight Growth</h2>
        @if($selectedWeightChart['has'])
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 230" role="img" aria-label="Longitudinal weight growth in kilograms by measurement date">
                <g fill="none" stroke="#bbb" stroke-width="1">
                    <path d="M65 35H550 M65 105H550 M65 175H550"/>
                    <path d="M65 25V180H550" stroke="#222"/>
                </g>
                <g fill="#222" font-family="sans-serif" font-size="12">
                    <text x="8" y="17">Weight (kg)</text>
                    <text x="55" y="39" text-anchor="end">{{ $selectedWeightChart['max'] }}</text>
                    <text x="55" y="109" text-anchor="end">{{ $selectedWeightChart['mid'] }}</text>
                    <text x="55" y="179" text-anchor="end">{{ $selectedWeightChart['min'] }}</text>
                    <text x="300" y="225" text-anchor="middle">Measurement date (chronological observations)</text>
                </g>
                @php
                    $chartPoints = $selectedWeightChart['points']->map(fn ($point) => [
                        'x' => 65 + ($point['x'] - 14) / 76 * 485,
                        'y' => 35 + ($point['y'] - 24) / 54 * 140,
                        'label' => $point['label'],
                    ]);
                    $labelEvery = max(1, (int) ceil(($chartPoints->count() - 1) / 5));
                @endphp
                @if($chartPoints->count() > 1)
                    <polyline fill="none" stroke="#222" stroke-width="2" points="{{ $chartPoints->map(fn ($point) => $point['x'].','.$point['y'])->implode(' ') }}"/>
                @endif
                @foreach($chartPoints as $point)
                    <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="3" fill="#fff" stroke="#222" stroke-width="2"/>
                    @if($loop->index % $labelEvery === 0 || ($loop->last && $loop->index % $labelEvery >= $labelEvery / 2))
                        <text x="{{ $point['x'] }}" y="200" text-anchor="middle" font-family="sans-serif" font-size="12" fill="#222">{{ $point['label'] }}</text>
                    @endif
                @endforeach
            </svg>
        @else
            <p>No weight records available for this newborn yet.</p>
        @endif
    </section>

    <h2 class="section-title">Newborn Growth Monitoring Logs</h2>
    <table class="history">
        <thead><tr><th>Observation date</th><th style="width:9%">Age</th><th>Weight / length</th><th>Head circumference / temperature</th><th>Recorded by</th></tr></thead>
        <tbody>
        @forelse($selectedGrowthLogs->sortByDesc('measured_at') as $record)
            <tr>
                <td>{{ $record->measured_at?->format('d M Y') ?? 'Not recorded' }}</td>
                <td>{{ $record->age_months === null ? 'Not recorded' : $record->age_months.' mo' }}</td>
                <td>{{ $formatNumber($record->weight, ' kg') }} / {{ $formatNumber($record->height, ' cm') }}</td>
                <td>{{ $formatNumber($record->head_circumference, ' cm') }} / {{ $formatNumber($record->temperature, ' °C') }}</td>
                <td>{{ $record->recorder?->full_name ?? 'Not recorded' }}</td>
            </tr>
        @empty
            <tr><td colspan="5">No neonatal growth monitoring logs recorded yet.</td></tr>
        @endforelse
        </tbody>
    </table>

    <h2 class="section-title">Newborn Vaccine Monitoring Logs</h2>
    <table class="history">
        <colgroup><col style="width:24%"><col style="width:13%"><col style="width:15%"><col style="width:16%"><col style="width:18%"><col style="width:14%"></colgroup>
        <thead><tr><th>Vaccine</th><th>Dose</th><th>Due date</th><th>Administration date</th><th>Facility</th><th>Status</th></tr></thead>
        <tbody>
        @forelse($selectedVaccineLogs as $vaccine)
            <tr>
                <td>{{ $vaccine->vaccine_name }}<br>{{ $vaccine->vaccine_group }}</td>
                <td>{{ $value($vaccine->dose_label) }}</td>
                <td>{{ $vaccine->due_date?->format('d M Y') ?? 'Not scheduled' }}</td>
                <td>{{ $vaccine->administered_at?->format('d M Y') ?? 'Not recorded' }}</td>
                <td>{{ $value($vaccine->facility) }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $vaccine->status)) }}</td>
            </tr>
        @empty
            <tr><td colspan="6">No vaccine monitoring logs recorded yet.</td></tr>
        @endforelse
        </tbody>
    </table>
@endif
</body>
