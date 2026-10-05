@extends('layouts.dswd')
@section('heading', 'F1KD Monitoring')
@section('content')
<header class="account-heading"><div><p class="account-kicker">DSWD / 4Ps</p><h1>F1KD Monitoring</h1><p>Shared monthly monitoring for registered 4Ps mothers and children aged 0-24 months.</p></div></header>
<section class="admin-card">@include('dswd.f1kd.filters')
<p class="dswd-note">Program Staff and DSWD use the same monthly attendance records. Open a beneficiary to update or verify attendance. Historical rosters show saved records only. Household references use Project INAY registration IDs.</p>
</section>
@foreach($sections as $section)
@if(empty($filters['classification']) || $filters['classification'] === $section['classification'])
<section class="admin-card" aria-labelledby="f1kd-{{ $section['classification'] }}-heading">
<h2 id="f1kd-{{ $section['classification'] }}-heading">{{ $section['title'] }}</h2>
<p class="dswd-note">{{ $section['classification'] === 'pregnant' ? 'All registered 4Ps mothers and saved maternal monitoring records.' : 'Children aged 0–24 months in registered 4Ps households and saved child monitoring records.' }} {{ $section['beneficiaries']->total() }} beneficiary record(s).</p>
<div class="dswd-table-wrap"><table class="dswd-table"><thead><tr>@foreach(['Household / Beneficiary ID','Beneficiary name','Sex','Barangay','Classification','Monthly status','Attendance / Remarks','Reporting month','Last updated','Action'] as $label)<th scope="col">{{ $label }}</th>@endforeach</tr></thead><tbody>
@forelse($section['beneficiaries'] as $row)<tr><td>{{ $row->household }}<br>{{ $row->beneficiary_id }}</td><td>{{ $row->name }}@if($row->classification === 'child')<br><small>Mother: {{ $row->mother_name }}</small>@endif</td><td>{{ ucfirst($row->sex ?: 'Not recorded') }}</td><td>{{ $row->barangay }}</td><td>{{ \App\Support\F1kdCompliance::CLASSES[$row->classification] }}</td><td>@include('dswd.f1kd.badge', ['status'=>$row->status])</td><td>@include('dswd.f1kd.attendance', ['attendance'=>$row->attendance_status])<br>{{ \App\Support\F1kdCompliance::REMARKS[$row->remark_code] ?? '—' }}</td><td>{{ $row->month }}</td><td>{{ $row->updated_at?->format('M j, Y H:i') ?? 'Not yet recorded' }}</td><td><a href="{{ route('dswd.f1kd.show', ['subject'=>$row->key, 'month'=>$row->month]) }}">View<span class="sr-only"> {{ $row->name }}</span></a></td></tr>
@empty<tr><td colspan="10">No F1KD beneficiaries match these filters.</td></tr>@endforelse
</tbody></table></div>@include('dswd.partials.pagination', ['paginator'=>$section['beneficiaries']])</section>
@endif
@endforeach
@endsection
