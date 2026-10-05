<h2>F1KD 4Ps Beneficiary Summary</h2>
<p class="casefile-panel-note">{{ $mother->full_name }} · Household reference: INAY-{{ str_pad($mother->id, 5, '0', STR_PAD_LEFT) }} · {{ \Carbon\CarbonImmutable::parse($f1kdPeriod.'-01')->format('F Y') }}</p>
<form method="get" action="{{ route('staff.mothers.show', $mother) }}">
    <label for="casefile-f1kd-month">Reporting period</label>
    <input id="casefile-f1kd-month" type="month" name="f1kd_month" value="{{ $f1kdPeriod }}" required>
    <button class="account-button" type="submit">View Period</button>
</form>
<p class="casefile-panel-note">Saved attendance and remarks are shared with DSWD for the same beneficiary and reporting month. Historical lists contain saved records only.</p>
<p class="casefile-panel-note">Monthly health attendance for pregnant women and children aged 0–24 months in this registered 4Ps household.</p>
<div style="overflow-x:auto">
    <table class="f1kd-casefile-table">
        <thead><tr><th scope="col">Beneficiary</th><th scope="col">Beneficiary ID</th><th scope="col">Classification</th><th scope="col">Attendance</th><th scope="col">Remarks</th><th scope="col">Action</th></tr></thead>
        <tbody>
        @forelse($f1kdBeneficiaries as $beneficiary)
            <tr>
                <td>{{ $beneficiary->name }}@if($beneficiary->classification === 'child')<br><small>Mother: {{ $beneficiary->mother_name }}</small>@endif</td>
                <td>{{ $beneficiary->beneficiary_id }}</td>
                <td>{{ \App\Support\F1kdCompliance::CLASSES[$beneficiary->classification] }}</td>
                <td><span class="f1kd-casefile-attendance is-{{ $beneficiary->status }}">
                    @if($beneficiary->attendance_status === 'attended')<span aria-hidden="true">○</span> Attended
                    @elseif($beneficiary->attendance_status === 'did_not_attend')<span aria-hidden="true">●</span> Did Not Attend
                    @else Not Yet Recorded
                    @endif
                </span></td>
                <td>{{ \App\Support\F1kdCompliance::REMARKS[$beneficiary->remark_code] ?? '—' }}</td>
                <td>@if($staff->approval_status === 'approved')<a class="account-button" href="{{ route('staff.f1kd.edit', ['subject'=>$beneficiary->key, 'month'=>$beneficiary->month]) }}">View / Record Attendance</a>@else Approved Program Staff record attendance.@endif</td>
            </tr>
        @empty
            <tr><td colspan="6">No eligible pregnant beneficiary or child, and no saved F1KD record, for this reporting month.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<p class="casefile-panel-note">○ Attended = Compliant. ● Did Not Attend = Non-Compliant. Missing attendance = For Verification. Open a beneficiary to select another reporting period and review history.</p>
<style>
.f1kd-casefile-table { width:100%; border-collapse:collapse; font-size:13px; }
.f1kd-casefile-table th,.f1kd-casefile-table td { padding:12px; text-align:left; border-bottom:1px solid #e2e8f0; vertical-align:top; }
.f1kd-casefile-table th { background:#fff2f8; }
.f1kd-casefile-attendance { display:inline-block; padding:6px 10px; border-radius:8px; background:#fff3d8; color:#765000; }
.f1kd-casefile-attendance.is-compliant { background:#e6f6ed; color:#17643b; }
.f1kd-casefile-attendance.is-non_compliant { background:#fde9ec; color:#9d2638; }
</style>
