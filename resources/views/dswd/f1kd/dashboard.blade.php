@extends('layouts.dswd')
@section('heading', 'DSWD Dashboard')
@section('content')
<header class="account-heading"><div><p class="account-kicker">DSWD / 4Ps</p><h1>Monthly 4Ps Overview</h1><p>Shared maternal and child health attendance for {{ $filters['month'] }}.</p></div></header>
<section class="admin-card"><form method="get" class="dswd-filters"><label>Reporting month<input type="month" name="month" value="{{ $filters['month'] }}" max="{{ now()->format('Y-m') }}" required></label><div class="dswd-actions"><button class="dswd-button">View month</button></div></form></section>
@include('dswd.f1kd.summary')
<section class="admin-card"><h2>Monitoring and reports</h2><div class="dswd-actions">
<a class="dswd-button" href="{{ route('dswd.f1kd.index', ['month'=>$filters['month'], 'classification'=>'pregnant']) }}">Maternal Monitoring</a>
<a class="dswd-button" href="{{ route('dswd.f1kd.index', ['month'=>$filters['month'], 'classification'=>'child']) }}">Children 0–2 Years</a>
<a class="dswd-button secondary" href="{{ route('dswd.f1kd.reports', $filters) }}">Monthly 4Ps Reports</a>
</div><p class="dswd-note">Program Staff and DSWD use the same beneficiary profiles and attendance history.</p></section>
<p class="dswd-note">Current-month totals include eligible beneficiaries awaiting verification. Previous months use saved monitoring records only; zero means no saved records, not confirmed absence of beneficiaries. Children are eligible through 24 completed calendar months, excluding future or missing birth dates.</p>
@endsection
