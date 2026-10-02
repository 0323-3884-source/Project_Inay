@extends('layouts.dswd')
@section('heading', 'F1KD Dashboard')
@section('content')
<header class="account-heading"><div><p class="account-kicker">DSWD / 4Ps / FIRST 1,000 DAYS</p><h1>F1KD compliance overview</h1><p>4Ps beneficiary compliance and service availability for {{ $filters['month'] }}.</p></div><a class="account-button is-primary" href="{{ route('dswd.f1kd.index') }}">F1KD Monitoring</a></header>
@include('dswd.f1kd.summary')
<div class="f1kd-charts">@foreach($charts as $title=>$values) @include('dswd.partials.chart', compact('title','values')) @endforeach</div>
<p class="dswd-note">Current-month totals include eligible beneficiaries awaiting verification. Previous months use saved monitoring records only; zero means no saved records, not confirmed absence of beneficiaries. Children are eligible through 24 completed calendar months, excluding future or missing birth dates.</p>
@endsection