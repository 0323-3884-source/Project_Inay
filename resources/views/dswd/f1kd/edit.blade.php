@extends('layouts.app')
@section('title', 'F1KD Monthly Monitoring - Project INAY')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/account-pages.css') }}">
@include('dswd.partials.styles')
@endpush
@section('content')
<section class="account-page f1kd-staff-page">
    <header class="account-heading">
        <div><p class="account-kicker">PROGRAM STAFF / F1KD</p><h1>{{ $beneficiary->name }}</h1><p>F1KD Monthly Monitoring</p></div>
        <a class="account-button" href="{{ route('staff.mothers.show', ['mother'=>$beneficiary->mother_id, 'f1kd_month'=>$beneficiary->month]) }}">Back to F1KD summary</a>
    </header>
    @if(session('status'))<p role="status">{{ session('status') }}</p>@endif
    @if($errors->any())<ul role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
    @include('dswd.f1kd.identity')
    <section class="admin-card">
        <h2>Monthly Health Attendance</h2>
        <p>{{ \Carbon\CarbonImmutable::parse($beneficiary->month.'-01')->format('F Y') }}</p>
        <p>@include('dswd.f1kd.attendance', ['attendance'=>$beneficiary->attendance_status])</p>
        @if($beneficiary->remark_code)<p>Remark: {{ \App\Support\F1kdCompliance::REMARKS[$beneficiary->remark_code] ?? '—' }}</p>@endif
        @if($beneficiary->month <= now()->format('Y-m'))
        <form method="post" action="{{ route('staff.f1kd.update', ['subject'=>$beneficiary->key]) }}" class="dswd-form">
            @csrf @method('PUT')
            <input type="hidden" name="month" value="{{ $beneficiary->month }}">
            <fieldset class="f1kd-attendance-options">
                <legend>Attendance Status</legend>
                @foreach(\App\Support\F1kdCompliance::ATTENDANCE as $value=>$label)
                    <label class="f1kd-attendance-choice">
                        <input type="radio" name="attendance_status" value="{{ $value }}" @checked(old('attendance_status', $beneficiary->attendance_status)===$value) required>
                        <span><span aria-hidden="true">{{ $value === 'did_not_attend' ? '●' : '○' }}</span> {{ $label }}</span>
                    </label>
                @endforeach
            </fieldset>
            <label for="f1kd-remark">Remarks (optional)
                <select id="f1kd-remark" name="remark_code">
                    <option value="">Select remark if applicable</option>
                    @foreach(\App\Support\F1kdCompliance::REMARKS as $code=>$label)
                        <option value="{{ $code }}" @selected(old('remark_code', $beneficiary->remark_code)===$code)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <p class="dswd-note">○ Attended: no recorded non-attendance. ● Did Not Attend: shaded circle on the DSWD form. A missing attendance record remains For Verification.</p>
            <button class="dswd-button">Save Record</button>
        </form>
        @else
            <p class="dswd-note">Attendance can be recorded when this reporting month begins.</p>
        @endif
    </section>
    @include('dswd.f1kd.history', ['historyRoute'=>'staff.f1kd.edit'])
</section>
@endsection
