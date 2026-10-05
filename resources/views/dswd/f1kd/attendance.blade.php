<span class="dswd-badge f1kd-{{ \App\Support\F1kdCompliance::attendanceStatus($attendance) }}">
    @if($attendance === 'did_not_attend')<span aria-hidden="true">●</span> Did Not Attend
    @elseif($attendance === 'attended')<span aria-hidden="true">○</span> Attended
    @else Not Yet Recorded / For Verification
    @endif
</span>
