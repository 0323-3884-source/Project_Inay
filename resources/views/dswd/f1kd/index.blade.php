@extends('layouts.dswd')
@section('heading', 'F1KD Monitoring')
@section('content')
<header class="account-heading"><div><p class="account-kicker">DSWD / 4Ps</p><h1>F1KD Monitoring</h1><p>Shared monthly monitoring for registered 4Ps mothers and children aged 0-24 months.</p></div></header>
<section class="admin-card">@include('dswd.f1kd.filters')
<p class="dswd-note">Program Staff and DSWD use the same monthly attendance records. Open a beneficiary to update their Entry ID or verify attendance. Mothers and children each have their own Entry ID, separate from their Household No. Historical rosters show saved records only.</p>
</section>
@foreach($sections as $section)
@if(empty($filters['classification']) || $filters['classification'] === $section['classification'])
<section class="admin-card" aria-labelledby="f1kd-{{ $section['classification'] }}-heading">
<h2 id="f1kd-{{ $section['classification'] }}-heading">{{ $section['title'] }}</h2>
<p class="dswd-note">{{ $section['classification'] === 'pregnant' ? 'All registered 4Ps mothers and saved maternal monitoring records.' : 'Children aged 0–24 months in registered 4Ps households and saved child monitoring records.' }} {{ $section['beneficiaries']->total() }} beneficiary record(s).</p>
@include('dswd.f1kd.beneficiary-attendance-table', ['beneficiaries' => $section['beneficiaries'], 'showMother' => true])
@include('dswd.partials.pagination', ['paginator'=>$section['beneficiaries']])</section>
@endif
@endforeach
@endsection
