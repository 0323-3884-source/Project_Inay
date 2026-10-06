<section class="admin-card">
    <h2>Monthly attendance history</h2>
    <div class="dswd-table-wrap"><table class="dswd-table">
        <thead><tr><th scope="col">Reporting Month</th><th scope="col">Attendance</th><th scope="col">Remarks</th><th scope="col">Recorded / remarked by</th><th scope="col">Action</th></tr></thead>
        <tbody>
        @forelse($history as $record)
            <tr>
                <td>{{ $record->reporting_month->format('F Y') }}</td>
                <td>@include('dswd.f1kd.attendance', ['attendance' => $record->attendance_status])</td>
                <td>{{ \App\Support\F1kdCompliance::REMARKS[$record->remark_code] ?? '—' }}</td>
                <td>
                    @if($record->dswd_verified_at && $record->verifiedByDswdStaff)
                        @if(session('auth_role') === 'staff' && $record->verifiedByDswdStaff->is_active)
                            <a href="{{ route('staff.consultation', ['dswd_staff' => $record->verifiedByDswdStaff->id]) }}" aria-label="Chat with {{ $record->verifiedByDswdStaff->name }}">{{ $record->verifiedByDswdStaff->name }}</a>
                        @else
                            {{ $record->verifiedByDswdStaff->name }}
                        @endif
                        <div class="dswd-note">DSWD Staff</div>
                    @elseif($record->recordedByStaff)
                        @if(session('auth_role') === 'dswd_staff')
                            <a href="{{ route('dswd.messaging', ['staff' => $record->recordedByStaff->id]) }}" aria-label="Chat with {{ $record->recordedByStaff->full_name }}">{{ $record->recordedByStaff->full_name }}</a>
                        @else
                            {{ $record->recordedByStaff->full_name }}
                        @endif
                        <div class="dswd-note">{{ $record->recordedByStaff->role ?: 'Program Staff' }}</div>
                    @else
                        Not recorded
                    @endif
                </td>
                <td><a href="{{ route($historyRoute, ['subject' => $beneficiary->key, 'month' => $record->reporting_month->format('Y-m')]) }}">View period</a></td>
            </tr>
        @empty
            <tr><td colspan="5">No monthly attendance records saved.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    @include('dswd.partials.pagination', ['paginator' => $history])
</section>
