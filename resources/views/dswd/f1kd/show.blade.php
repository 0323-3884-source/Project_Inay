@extends('layouts.dswd')
@section('heading', 'F1KD Beneficiary')
@section('content')
<header class="account-heading"><div><p class="account-kicker">F1KD BENEFICIARY</p><h1>{{ $beneficiary->name }}</h1><p>F1KD Monthly Monitoring</p></div><a class="dswd-button secondary" href="{{ route('dswd.f1kd.index', ['month'=>$beneficiary->month]) }}">Back to Monitoring</a></header>
@include('dswd.f1kd.identity')
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
@if($errors->any())<ul role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
<section class="admin-card">
    <h2>Monthly Health Attendance</h2>
    <p>{{ \Carbon\CarbonImmutable::parse($beneficiary->month.'-01')->format('F Y') }}</p>
    <p>@include('dswd.f1kd.attendance', ['attendance'=>$beneficiary->attendance_status])</p>
    <p>Remark: {{ \App\Support\F1kdCompliance::REMARKS[$beneficiary->remark_code] ?? '—' }}</p>
    <p class="dswd-note">Shared with Program Staff. ○ Attended; ● Did Not Attend. Missing attendance remains For Verification.</p>
    @if($beneficiary->month <= now()->format('Y-m'))
    <form method="post" action="{{ route('dswd.f1kd.update', ['subject'=>$beneficiary->key]) }}" class="dswd-form">
        @csrf @method('PUT')
        <input type="hidden" name="month" value="{{ $beneficiary->month }}">
        <fieldset class="f1kd-attendance-options"><legend>Attendance to verify</legend>
            @foreach(\App\Support\F1kdCompliance::ATTENDANCE as $value=>$label)
            <label class="f1kd-attendance-choice"><input type="radio" name="attendance_status" value="{{ $value }}" @checked(old('attendance_status', $beneficiary->attendance_status)===$value) required><span>{{ $value==='attended' ? '○' : '●' }} {{ $label }}</span></label>
            @endforeach
        </fieldset>
        <label>Remarks (optional)<select name="remark_code"><option value="">No remark</option>@foreach(\App\Support\F1kdCompliance::REMARKS as $code=>$label)<option value="{{ $code }}" @selected(old('remark_code', $beneficiary->remark_code)===$code)>{{ $label }}</option>@endforeach</select></label>
        <button class="dswd-button">Save &amp; Verify</button>
    </form>
    @endif
</section>
@include('dswd.f1kd.history', ['historyRoute'=>'dswd.f1kd.show'])
@endsection
