@php
    $images = collect(['letterhead', 'footer'])->mapWithKeys(fn ($part) => [
        $part => 'data:image/jpeg;base64,'.base64_encode(file_get_contents(public_path('assets/images/mother-record/template-'.$part.'.jpg'))),
    ])->all();
@endphp
<head>
    <meta charset="utf-8">
    <title>Project INAY - Program Staff Clinical Statistics</title>
    <style>
        {!! file_get_contents(resource_path('css/mother-care-record.css')) !!}
        {!! file_get_contents(resource_path('css/staff-statistics.css')) !!}
    </style>
</head>
<body>
    @include('records.partials.branding')
    <div class="document-title">
        <h1>PROJECT INAY</h1><h2>PROGRAM STAFF CLINICAL STATISTICS REPORT</h2>
        <p>Prepared by {{ $staff->full_name }} · {{ $staff->role_label }}</p>
    </div>
    @include('modules.partials.staff-statistics')
</body>
