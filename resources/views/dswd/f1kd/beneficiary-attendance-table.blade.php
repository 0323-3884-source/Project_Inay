<div class="dswd-table-wrap"><table class="dswd-table"><thead><tr><th>Beneficiary / ID</th><th>Household ID</th><th>Classification</th><th>Attendance</th><th>Remarks</th><th>Recorded / verified by</th><th>Action</th></tr></thead><tbody>
@forelse($beneficiaries as $beneficiary)<tr>
<td>{{ $beneficiary->name }}<br>{{ $beneficiary->beneficiary_id }}@if(($showMother ?? false) && $beneficiary->classification === 'child')<br><small>Mother: {{ $beneficiary->mother_name }}</small>@endif</td>
<td>{{ $beneficiary->household_id ?: 'Not recorded' }}</td>
<td>{{ \App\Support\F1kdCompliance::CLASSES[$beneficiary->classification] }}</td>
<td>{{ $beneficiary->attendance_status === 'attended' ? '○ Attended' : ($beneficiary->attendance_status === 'did_not_attend' ? '● Did Not Attend' : 'Not Yet Recorded') }}</td>
<td>{{ \App\Support\F1kdCompliance::REMARKS[$beneficiary->remark_code ?? ''] ?? '—' }}</td>
<td>
    @if($beneficiary->attendance_author)
        @if(!$beneficiary->attendance_author->is_dswd)
            <a href="{{ route('dswd.messaging', ['staff' => $beneficiary->attendance_author->id]) }}" aria-label="Chat with {{ $beneficiary->attendance_author->name }}">{{ $beneficiary->attendance_author->name }}</a>
        @else
            {{ $beneficiary->attendance_author->name }}
        @endif
        <div class="dswd-note">{{ $beneficiary->attendance_author->role }}</div>
    @else
        Not recorded
    @endif
</td>
<td><a href="{{ route('dswd.f1kd.show', ['subject'=>$beneficiary->key, 'month'=>$beneficiary->month]) }}">View / Verify</a></td>
</tr>@empty<tr><td colspan="7">No beneficiaries for this period and filters.</td></tr>@endforelse
</tbody></table></div>
