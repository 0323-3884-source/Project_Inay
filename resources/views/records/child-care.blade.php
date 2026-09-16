<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $recordNumber }} - Child Care Record</title>
    <style>{!! file_get_contents(resource_path('css/mother-care-record.css')) !!}</style>
</head>
<body>
@php
    $value = fn ($item, $fallback = 'Not provided') => $item === null || (is_string($item) && trim($item) === '') ? $fallback : $item;
    $label = fn ($item) => $item === null || trim((string) $item) === '' ? 'Not provided' : ucfirst(str_replace('_', ' ', $item));
    $date = fn ($item) => $item?->format('d M Y') ?? 'Not provided';
    $measure = fn ($item, $unit) => $item === null ? 'Not provided' : $item.' '.$unit;
    $latestGrowth = $infant->growthRecords->last();
    $vaccineStatus = function ($vaccine) use ($generatedAt, $label) {
        if (in_array($vaccine->status, ['completed', 'cancelled', 'missed', 'overdue'], true)) return $label($vaccine->status);
        if ($vaccine->due_date?->isSameDay($generatedAt)) return 'Due Today';
        if ($vaccine->due_date?->lt($generatedAt->copy()->startOfDay())) return 'Overdue';
        return $label($vaccine->status);
    };
@endphp
@include('records.partials.branding')
<div class="document-title">
    <h1>PROJECT INAY</h1><h2>NEONATAL / CHILD RECORD</h2>
    <p>{{ $recordNumber }} &middot; Generated: {{ $generatedAt->format('d M Y, h:i A T') }}</p>
</div>
<h2 class="section-title">CHILD / NEONATAL INFORMATION</h2>

    <h3>{{ $infant->full_name }} · Child #{{ $infant->id }}</h3>
    @include('records.partials.fields', ['fields' => [
        'Full name' => $infant->full_name,
        'Mother name' => $infant->mother->full_name,
        'Current weight' => $measure($latestGrowth?->weight, 'kg'),
        'Current height' => $measure($latestGrowth?->height, 'cm'),
        'Last measurement date' => $date($latestGrowth?->measured_at),
        'Sex' => $label($infant->sex),
        'Date of birth' => $date($infant->birth_date),
        'Age at generation' => $infant->birth_date && $infant->birth_date->lte($generatedAt) ? (int) $infant->birth_date->diffInMonths($generatedAt).' completed months' : 'Not provided',
        'Birth weight' => $measure($infant->birth_weight, 'kg'),
        'Birth length' => $measure($infant->birth_height, 'cm'),
        'Blood type' => $infant->blood_type,
        'Birth facility' => $infant->facility,
    ]])
    <p><strong>Neonatal notes:</strong> {{ $value($infant->notes) }}</p>
    <h4>Growth monitoring · {{ $infant->full_name }}</h4>
    @if($infant->growthRecords->isEmpty())
        <p>No growth monitoring records available.</p>
    @else
        <table class="history">
            <thead><tr><th>Date</th><th>Age (months)</th><th>Weight (kg)</th><th>Length (cm)</th><th>Head circumference (cm)</th><th>Temp. (°C)</th><th>Recorded by</th></tr></thead>
            <tbody>
            @foreach($infant->growthRecords as $growth)
                <tr><td>{{ $date($growth->measured_at) }}</td><td>{{ $value($growth->age_months) }}</td><td>{{ $value($growth->weight) }}</td><td>{{ $value($growth->height) }}</td><td>{{ $value($growth->head_circumference) }}</td><td>{{ $value($growth->temperature) }}</td><td>{{ $value($growth->recorder?->full_name) }}</td></tr>
            @endforeach
            </tbody>
        </table>
        @foreach($infant->growthRecords as $growth)
            @if($growth->remarks)<p><strong>Growth remarks ({{ $date($growth->measured_at) }}):</strong> {{ $growth->remarks }}</p>@endif
        @endforeach
    @endif
    @if($infant->healthAlerts->isNotEmpty())
        <h4>Recorded child health alerts</h4>
        @foreach($infant->healthAlerts->sortBy('created_at') as $alert)
            <p><strong>{{ $date($alert->created_at) }} · {{ $alert->title }}</strong><br>
                {{ $label($alert->alert_type) }} · Status: {{ $label($alert->status) }} · Resolved: {{ $date($alert->resolved_at) }}<br>
                {{ $value($alert->notes) }}</p>
        @endforeach
    @endif


@if($infant->vaccineRecords->isNotEmpty())
    <h2 class="section-title">VACCINATION RECORDS</h2>
            <h3>{{ $infant->full_name }} · Child #{{ $infant->id }}</h3>
            <table class="history">
                <thead><tr><th style="width:24%">Vaccine / group</th><th>Dose</th><th>Scheduled date</th><th>Administered date</th><th>Status</th><th>Facility</th></tr></thead>
                <tbody>
                @foreach($infant->vaccineRecords as $vaccine)
                    <tr><td>{{ $value($vaccine->vaccine_name) }}<br>{{ $value($vaccine->vaccine_group) }}</td><td>{{ $value($vaccine->dose_label) }}</td><td>{{ $date($vaccine->due_date) }}</td><td>{{ $date($vaccine->administered_at) }}</td><td>{{ $vaccineStatus($vaccine) }}</td><td>{{ $value($vaccine->facility) }}</td></tr>
                @endforeach
                </tbody>
            </table>
            @foreach($infant->vaccineRecords as $vaccine)
                <p><strong>{{ $vaccine->vaccine_name }} · {{ $vaccine->dose_label }}:</strong>
                    Lot: {{ $value($vaccine->lot_number) }} · Vaccinator: {{ $value($vaccine->vaccinator) }} · Recorded by: {{ $value($vaccine->recorder?->full_name) }}</p>
                @if($vaccine->remarks)<p><strong>Remarks:</strong> {{ $vaccine->remarks }}</p>@endif
            @endforeach
@else
    <p>No vaccination records available.</p>
@endif

<h2 class="section-title">CURRENT GROWTH STATUS</h2>
<p><strong>{{ $assessment['label'] }}</strong></p>
@foreach($assessment['alerts'] as $alert)
    <p>{{ $alert }}</p>
@endforeach
<section class="signature">
    <p>Prepared / Generated by:</p>
    <div class="signature-line">{{ $staff->full_name }}</div>
    <div>{{ $staff->role_label }}</div>
    <p>Date Generated: {{ $generatedAt->format('d M Y, h:i A T') }}</p>
</section>
</body>
</html>
