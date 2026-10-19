@extends('layouts.dswd')
@section('heading', 'Monthly 4Ps Reports')
@section('content')
<div class="dswd-monthly-report">
<header class="account-heading"><div><p class="account-kicker">DSWD / 4Ps</p><h1>Monthly 4Ps Report</h1><p>Beneficiary attendance and compliance · {{ \Carbon\Carbon::createFromFormat('Y-m', $filters['month'])->format('F Y') }}</p></div></header>
<section class="admin-card">@include('dswd.f1kd.filters', ['report'=>true])<div class="dswd-actions"><button type="button" class="dswd-button secondary" onclick="window.print()">Print report</button><a class="dswd-button" href="{{ route('dswd.f1kd.reports.download', $filters) }}">Export CSV</a></div>
<p class="dswd-note">Coverage: {{ ($filters['barangay'] ?? '') ?: 'All barangays' }} · {{ ($filters['municipality_city'] ?? '') ?: 'All municipalities' }} · {{ \App\Support\F1kdCompliance::CLASSES[$filters['classification'] ?? ''] ?? 'All classifications' }} · {{ \App\Support\F1kdCompliance::STATUSES[$filters['status'] ?? ''] ?? 'All statuses' }}.</p>
<p class="dswd-note">Previous reporting periods reflect saved monthly records.</p></section>
@include('dswd.f1kd.summary', ['simpleReport' => true])
<section class="admin-card"><h2>Attendance by location</h2><div class="dswd-table-wrap"><table class="dswd-table"><thead><tr>@foreach(['Municipality / Barangay','Total','4Ps Mothers','Children 0-24 months','Compliant','For Verification'] as $label)<th scope="col">{{ $label }}</th>@endforeach</tr></thead><tbody>@forelse($breakdown as $area=>$counts)<tr><td>{{ $area }}</td>@foreach($counts as $count)<td>{{ $count }}</td>@endforeach</tr>@empty<tr><td colspan="6">No monitoring records for these filters.</td></tr>@endforelse</tbody></table></div></section>
<section class="admin-card"><h2>Monthly beneficiary attendance</h2>
<p class="dswd-note">Attendance records are shared with Program Staff. Select a beneficiary to review attendance history.</p>
@include('dswd.f1kd.beneficiary-attendance-table'){{ $beneficiaries->links() }}</section>
</div>
@endsection
