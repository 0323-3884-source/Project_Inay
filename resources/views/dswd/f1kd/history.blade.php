<section class="admin-card">
    <h2>Monthly attendance history</h2>
    <div class="dswd-table-wrap"><table class="dswd-table">
        <thead><tr><th scope="col">Reporting Month</th><th scope="col">Attendance</th><th scope="col">Remarks</th><th scope="col">Action</th></tr></thead>
        <tbody>
        @forelse($history as $record)
            <tr>
                <td>{{ $record->reporting_month->format('F Y') }}</td>
                <td>@include('dswd.f1kd.attendance', ['attendance' => $record->attendance_status])</td>
                <td>{{ \App\Support\F1kdCompliance::REMARKS[$record->remark_code] ?? '—' }}</td>
                <td><a href="{{ route($historyRoute, ['subject' => $beneficiary->key, 'month' => $record->reporting_month->format('Y-m')]) }}">View period</a></td>
            </tr>
        @empty
            <tr><td colspan="4">No monthly attendance records saved.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    @include('dswd.partials.pagination', ['paginator' => $history])
</section>
