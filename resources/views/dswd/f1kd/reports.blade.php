@extends('layouts.dswd')
@section('heading', 'Monthly 4Ps Reports')
@section('content')
<header class="account-heading"><div><p class="account-kicker">DSWD / 4Ps</p><h1>Monthly 4Ps Reports</h1><p>Shared F1KD attendance for {{ $filters['month'] }}.</p></div></header>
<section class="admin-card">@include('dswd.f1kd.filters', ['report'=>true])<div class="dswd-actions"><button type="button" class="dswd-button secondary" onclick="window.print()">Print report</button><a class="dswd-button" href="{{ route('dswd.f1kd.reports.download', $filters) }}">Export CSV</a></div>
<p class="dswd-note">Selected filters: Barangay {{ $filters['barangay'] ?? 'All' }}; Municipality {{ $filters['municipality_city'] ?? 'All' }}; Classification {{ \App\Support\F1kdCompliance::CLASSES[$filters['classification'] ?? ''] ?? 'All' }}; Status {{ \App\Support\F1kdCompliance::STATUSES[$filters['status'] ?? ''] ?? 'All' }}. Historical reports use saved monthly records. Attendance determines monthly compliance.</p></section>
@include('dswd.f1kd.summary')
<section class="admin-card"><h2>Municipality / Barangay breakdown</h2><div class="dswd-table-wrap"><table class="dswd-table"><thead><tr>@foreach(['Municipality / Barangay','Total','4Ps Mothers','Children 0-24 months','Compliant','For Verification','Non-Compliant'] as $label)<th scope="col">{{ $label }}</th>@endforeach</tr></thead><tbody>@forelse($breakdown as $area=>$counts)<tr><td>{{ $area }}</td>@foreach($counts as $count)<td>{{ $count }}</td>@endforeach</tr>@empty<tr><td colspan="7">No monitoring records for these filters.</td></tr>@endforelse</tbody></table></div></section>
<section class="admin-card"><h2>Monthly beneficiary attendance</h2>
<p class="dswd-note">The same records used by Program Staff. Open a beneficiary to review history or verify attendance.</p>
<div class="dswd-table-wrap"><table class="dswd-table"><thead><tr><th>Beneficiary / ID</th><th>Household / Staff</th><th>Classification</th><th>Attendance</th><th>Remarks</th><th>Verification</th><th>Action</th></tr></thead><tbody>
@forelse($beneficiaries as $beneficiary)<tr>
<td>{{ $beneficiary->name }}<br>{{ $beneficiary->beneficiary_id }}</td>
<td>{{ $beneficiary->household }}<br>{{ $beneficiary->assigned_staff }}</td>
<td>{{ \App\Support\F1kdCompliance::CLASSES[$beneficiary->classification] }}</td>
<td>{{ $beneficiary->attendance_status === 'attended' ? '○ Attended' : ($beneficiary->attendance_status === 'did_not_attend' ? '● Did Not Attend' : 'Not Yet Recorded') }}</td>
<td>{{ \App\Support\F1kdCompliance::REMARKS[$beneficiary->remark_code ?? ''] ?? '—' }}</td>
<td>{{ $beneficiary->dswd_verified_at ? 'Verified' : 'Not yet verified' }}</td>
<td><a href="{{ route('dswd.f1kd.show', ['subject'=>$beneficiary->key, 'month'=>$filters['month']]) }}">View / Verify</a></td>
</tr>@empty<tr><td colspan="7">No beneficiaries for this period and filters.</td></tr>@endforelse
</tbody></table></div>{{ $beneficiaries->links() }}</section>
@endsection
