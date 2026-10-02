<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $recordNumber }} - Mother Care Record</title>
    <style>{!! file_get_contents(resource_path('css/mother-care-record.css')) !!}</style>
</head>
<body>
@php
    $value = fn ($item, $fallback = 'Not provided') => $item === null || (is_string($item) && trim($item) === '') ? $fallback : $item;
    $label = fn ($item) => $item === null || trim((string) $item) === '' ? 'Not provided' : ucfirst(str_replace('_', ' ', $item));
    $date = fn ($item) => $item?->format('d M Y') ?? 'Not provided';
    $measure = fn ($item, $unit) => $item === null ? 'Not provided' : $item.' '.$unit;
    $week = $latestRecord?->pregnancy_week;
    $trimester = $mother->pregnancy_status === 'pregnant'
        ? ($week ? ($week <= 13 ? '1st Trimester' : ($week <= 27 ? '2nd Trimester' : '3rd Trimester')) : 'Not provided')
        : ($mother->pregnancy_status ? 'Not applicable' : 'Not provided');
    $sugarTypes = \App\Support\MaternalVitalScreening::bloodSugarTestTypes();
@endphp

@include('records.partials.branding')

<div class="document-title">
    <h1>PROJECT INAY</h1>
    <h2>MOTHER CARE RECORD - {{ $sectionTitle }}</h2>
    <p>{{ $recordNumber }} · Generated: {{ $generatedAt->format('d M Y, h:i A T') }}</p>
</div>

<h2 class="section-title">MOTHER IDENTIFICATION</h2>
@include('records.partials.fields', ['fields' => [
    'Mother ID / record number' => $recordNumber,
    'Casefile ID' => 'MAT-RHU-'.str_pad((string) $mother->id, 3, '0', STR_PAD_LEFT),
    'Full name' => $mother->full_name,
    'Age' => $measure($mother->age, 'years'),
    'Contact number' => $mother->contact_number,
    'Barangay' => $mother->barangay,
    'Civil status' => $label($mother->civil_status),
    'Blood type' => $mother->blood_type,
    '4Ps beneficiary' => $mother->is_4ps_beneficiary === null ? 'Not provided' : ($mother->is_4ps_beneficiary ? 'Yes' : 'No'),
]])
<p><strong>Assigned Program Staff:</strong>
    {{ $value($mother->casefileStaff->map(fn ($assigned) => $assigned->full_name.' ('.$assigned->role_label.')')->join('; ')) }}
</p>

@if($section === 'overview')
<h2 class="section-title">OVERVIEW / MATERNAL INFORMATION</h2>
@include('records.partials.fields', ['fields' => [
    'Pregnancy status' => $label($mother->pregnancy_status),
    'Current trimester' => $trimester,
    'Gravidity' => $mother->gravidity,
    'Parity' => $mother->parity,
    'Obstetric history' => $mother->gravidity === null && $mother->parity === null ? 'Not provided' : 'G'.$value($mother->gravidity).' P'.$value($mother->parity),
    'Maternal age risk' => $mother->maternal_age_risk,
    'Latest recorded pregnancy week' => $week,
    'Latest recorded pregnancy month' => $latestRecord?->pregnancy_month,
    'Latest recorded screening status' => $latestRecord?->screening_summary_status ?? $latestRecord?->risk_level,
    'Latest monitoring date' => $date($latestRecord?->recorded_at),
]])

<h3>Latest maternal vital signs</h3>
@include('records.partials.fields', ['fields' => [
    'Blood pressure' => $latestRecord?->bp_systolic && $latestRecord?->bp_diastolic ? $latestRecord->bp_systolic.'/'.$latestRecord->bp_diastolic.' mmHg' : 'Not provided',
    'Weight' => $measure($latestRecord?->weight, 'kg'),
    'Height' => $measure($latestRecord?->height_cm, 'cm'),
    'Blood sugar' => $measure($latestRecord?->blood_sugar, 'mg/dL'),
    'Temperature' => $measure($latestRecord?->temperature, 'degrees C'),
    'Heart rate' => $measure($latestRecord?->heart_rate, 'bpm'),
    'Hemoglobin' => $measure($latestRecord?->hemoglobin, 'g/dL'),
]])
@foreach(($latestRecord?->screening_explanations ?? []) as $key => $explanation)
    @if(is_string($explanation) && $explanation !== '')
        <p><strong>{{ $label($key) }}:</strong> {{ $explanation }}</p>
    @endif
@endforeach
<h3>Maternal health progress</h3>
@include('records.partials.fields', ['fields' => [
    'Registration date' => $date($mother->created_at),
    'Prenatal visit completion' => min($records->count(), 8).'/8',
    'Care completion' => $careCompletion.'%',
    'Maternal vaccination status' => 'No maternal vaccine record available',
]])
<h3>Weight and blood pressure trends</h3>
<table class="history">
    <thead><tr><th>Date</th><th>Pregnancy week</th><th>Weight (kg)</th><th>Blood pressure (mmHg)</th></tr></thead>
    <tbody>
    @forelse($records as $entry)
        <tr><td>{{ $date($entry->recorded_at) }}</td><td>{{ $value($entry->pregnancy_week) }}</td><td>{{ $value($entry->weight) }}</td><td>{{ $value($entry->bp_systolic) }} / {{ $value($entry->bp_diastolic) }}</td></tr>
    @empty
        <tr><td colspan="4">No measurements recorded.</td></tr>
    @endforelse
    </tbody>
</table>
@endif
@if($section === 'monitoring')
<h2 class="section-title">MATERNAL VITAL SIGNS / MONITORING HISTORY</h2>
<p class="caption">{{ $records->count() }} monitoring record(s), oldest to newest. Values and screening results are reproduced as recorded.</p>
@if($records->isEmpty())
    <p>No maternal monitoring records available.</p>
@else
    <table class="history vitals">
        <thead><tr><th style="width:15%">Date / record</th><th style="width:9%">Week / month</th><th style="width:13%">BP<br>mmHg</th><th style="width:13%">Blood sugar<br>mg/dL</th><th>Weight<br>kg</th><th>Height<br>cm</th><th>Temp.<br>°C</th><th>Heart rate<br>bpm</th><th>Hb<br>g/dL</th></tr></thead>
        <tbody>
        @foreach($records as $record)
            <tr><td>{{ $date($record->recorded_at) }}<br>#{{ $record->id }}</td><td>{{ $value($record->pregnancy_week) }} / {{ $value($record->pregnancy_month) }}</td>
                <td>{{ $value($record->bp_systolic) }} / {{ $value($record->bp_diastolic) }}</td><td>{{ $value($record->blood_sugar) }}</td><td>{{ $value($record->weight) }}</td><td>{{ $value($record->height_cm) }}</td><td>{{ $value($record->temperature) }}</td><td>{{ $value($record->heart_rate) }}</td><td>{{ $value($record->hemoglobin) }}</td></tr>
        @endforeach
        </tbody>
    </table>
    <h3>Pregnancy weight and screening history</h3>
    <table class="history">
        <thead><tr><th style="width:15%">Date / record</th><th>Blood sugar test</th><th>Pre-pregnancy weight (kg)</th><th>Stored pre-pregnancy BMI</th><th>Weight change (kg)</th><th style="width:23%">Screening status</th></tr></thead>
        <tbody>
        @foreach($records as $record)
            <tr><td>{{ $date($record->recorded_at) }}<br>#{{ $record->id }}</td><td>{{ $sugarTypes[$record->blood_sugar_test_type] ?? $label($record->blood_sugar_test_type) }}</td><td>{{ $value($record->pre_pregnancy_weight) }}</td><td>{{ $value($record->pre_pregnancy_bmi) }}</td><td>{{ $value($record->weight_change_from_previous) }}</td><td>{{ $value($record->screening_summary_status ?? $record->risk_level) }}</td></tr>
        @endforeach
        </tbody>
    </table>
    <h3>Monitoring notes and recorded screening details</h3>
    @foreach($records as $record)
        <h4>{{ $date($record->recorded_at) }} · Record #{{ $record->id }}</h4>
        <p><strong>Recorded by:</strong> {{ $value($record->recorder?->full_name) }}
            @if($record->recorder) ({{ $record->recorder->role_label }}) @endif
        </p>
        <p><strong>Recorded risk level:</strong> {{ $value($record->risk_level) }}</p>
        <p><strong>Notes:</strong> {{ $value($record->notes) }}</p>
        @foreach(['bp_status' => 'Blood pressure', 'blood_sugar_status' => 'Blood sugar', 'weight_status' => 'Weight', 'hemoglobin_status' => 'Hemoglobin', 'temperature_status' => 'Temperature', 'heart_rate_status' => 'Heart rate'] as $field => $heading)
            @if($record->{$field} !== null)<p class="detail"><strong>{{ $heading }} screening:</strong> {{ $record->{$field} }}</p>@endif
        @endforeach
        @foreach(($record->screening_explanations ?? []) as $key => $explanation)
            @if(is_string($explanation) && $explanation !== '')<p class="detail"><strong>{{ $label($key) }}:</strong> {{ $explanation }}</p>@endif
        @endforeach
        @if($record->confirmed_unusual_at)
            <p><strong>Unusual measurement confirmed:</strong> {{ $date($record->confirmed_unusual_at) }} · {{ $value($record->unusualConfirmer?->full_name) }}</p>
        @endif
    @endforeach
@endif

@endif
@if($section === 'learning-documents')
@if($mother->inayKaalamanCheckups->isNotEmpty())
    <h3>Prenatal checkup records</h3>
    <table class="history">
        <thead><tr><th>Date</th><th>Month</th><th>Healthcare worker</th><th>Facility</th><th>Verified by / date</th></tr></thead>
        <tbody>
        @foreach($mother->inayKaalamanCheckups as $checkup)
            <tr><td>{{ $date($checkup->checkup_date) }}</td><td>{{ $value($checkup->month) }}</td><td>{{ $value($checkup->healthcare_worker_name) }}</td><td>{{ $value($checkup->facility_name) }}</td><td>{{ $value($checkup->verifiedByStaff?->full_name) }}<br>{{ $date($checkup->verified_at) }}</td></tr>
        @endforeach
        </tbody>
    </table>
    @foreach($mother->inayKaalamanCheckups as $checkup)
        <h4>Checkup #{{ $checkup->id }} · {{ $date($checkup->checkup_date) }}</h4>
        <p><strong>Recorded by:</strong> {{ $value($checkup->recordedByStaff?->full_name) }} · {{ $date($checkup->recorded_at) }}</p>
        <p><strong>Notes:</strong> {{ $value($checkup->notes) }}</p>
        @if($checkup->verification_notes)<p><strong>Verification notes:</strong> {{ $checkup->verification_notes }}</p>@endif
    @endforeach
@endif

<h2 class="section-title">INAY KAALAMAN / LEARNING &amp; DOCUMENTS</h2>
<p>Completed: <strong>{{ $learning['completed_months'] }} / {{ $learning['total_months'] }} months</strong> ·
    Learning activity progress: <strong>{{ $learning['percentage'] }}%</strong><br>
    Completed learning activities: {{ $learning['completed_required'] }} / {{ $learning['total_required'] }} ·
    Supporting records uploaded: {{ $learning['files_uploaded'] }}</p>

@foreach($learningSummary['months'] as $month)
    <h3>Month {{ $month['month'] }}: {{ $month['title'] }}</h3>
    <p>{{ $month['status'] }} &middot; {{ $month['percentage'] }}% complete</p>
    <p>Reading: {{ $month['reading']['label'] }} &middot; Infographic: {{ $month['infographic']['label'] }}</p>
    @foreach($month['videos'] as $video)
        <p>{{ $video['title'] }}: {{ $video['label'] }} {{ $video['completed_at'] }}</p>
    @endforeach
    <p>Documents: {{ $month['uploaded_required_documents'] }}/{{ $month['required_documents'] }} required documents uploaded</p>
    @forelse($mother->inayKaalamanUploads->where('month', $month['month']) as $upload)
        <p>{{ $label($upload->record_type) }}: {{ $upload->original_name }} &middot; Uploaded {{ $date($upload->created_at) }}</p>
    @empty
        <p>No documents uploaded for this month.</p>
    @endforelse
@endforeach
@endif
@if($section === 'documents')
<h2>Submitted Documents</h2>
<table><thead><tr><th>Month</th><th>Document type</th><th>File</th><th>Uploaded</th></tr></thead><tbody>
@forelse($mother->inayKaalamanUploads->sortByDesc('created_at') as $upload)
<tr><td>{{ $upload->month }}</td><td>{{ $upload->record_type }}</td><td>{{ $upload->original_name }}</td><td>{{ $upload->created_at->format('M j, Y') }}</td></tr>
@empty
<tr><td colspan="4">No documents received yet.</td></tr>
@endforelse
</tbody></table>
@endif

@if($section === 'notes')
<h2 class="section-title">CLINICAL NOTES</h2>
<p class="clinical-notes">{{ $latestRecord?->notes ?: 'No staff notes recorded for this patient yet.' }}</p>
@endif
<section class="signature">
    <p>Prepared / Generated by:</p>
    <div class="signature-line">{{ $staff->full_name }}</div>
    <div>{{ $staff->role_label }}</div>
    <p>Date Generated: {{ $generatedAt->format('d M Y, h:i A T') }}</p>
</section>
</body>
</html>
