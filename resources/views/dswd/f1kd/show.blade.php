@extends('layouts.dswd')
@section('heading', 'F1KD Beneficiary')
@section('content')
<header class="account-heading"><div><p class="account-kicker">F1KD BENEFICIARY</p><h1>{{ $beneficiary->name }}</h1><p>F1KD Monthly Monitoring</p></div><a class="dswd-button secondary" href="{{ route('dswd.f1kd.index', ['month'=>$beneficiary->month]) }}">Back to Monitoring</a></header>
@include('dswd.f1kd.identity')
<section class="admin-card">
    <h2>Monthly Health Attendance</h2>
    <p>{{ \Carbon\CarbonImmutable::parse($beneficiary->month.'-01')->format('F Y') }}</p>
    <p>@include('dswd.f1kd.attendance', ['attendance'=>$beneficiary->attendance_status])</p>
    <p>Remark: {{ \App\Support\F1kdCompliance::REMARKS[$beneficiary->remark_code] ?? '—' }}</p>
    <p class="dswd-note">Read-only record. Assigned Program Staff record monthly attendance. ○ Attended; ● Did Not Attend. Missing attendance remains For Verification.</p>
</section>
@include('dswd.f1kd.history', ['historyRoute'=>'dswd.f1kd.show'])
@endsection
