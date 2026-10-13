<nav class="department-f1kd-tabs" aria-label="F1KD Monitoring views">
    @php($attendanceView = in_array(request()->route('module'), ['f1kd', 'attendance'], true))
    <a href="{{ route('admin.dswd.module', ['module' => 'f1kd'] + request()->only('month', 'q')) }}" @if($attendanceView) aria-current="page" @endif>Monthly Attendance</a>
    <a href="{{ route('admin.dswd.module', ['module' => 'beneficiaries'] + request()->only('q')) }}" @if(!$attendanceView) aria-current="page" @endif>4Ps Beneficiaries</a>
</nav>
