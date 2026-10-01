@extends('layouts.dswd')
@section('heading', 'Dashboard Overview')
@section('content')
    <header class="account-heading"><div><p class="account-kicker">DSWD / 4Ps PORTAL</p><h1>Welcome, {{ ($staff ?? request()->attributes->get('dswd_staff'))?->name ?? session('auth_name', 'DSWD Staff') }}.</h1><p>Your beneficiaries, statistics, and reports in one place.</p></div><a class="account-button" href="{{ route('dswd.profile') }}">My Profile</a></header>
    <section class="account-welcome" style="margin-bottom:24px"><div><h2>Stay connected to your 4Ps community</h2><p>Explore beneficiary information and community statistics with the Project INAY 4Ps portal.</p></div><a class="account-button is-primary" href="{{ route('dswd.beneficiaries') }}">View 4Ps Beneficiaries</a></section>
    @include('dswd.partials.summary')
    <section class="admin-card"><h2>Welcome to the 4Ps portal</h2><p class="dswd-note">View registered 4Ps beneficiaries and explore community statistics as of {{ today()->format('F j, Y') }}.</p><div class="dswd-actions"><a class="dswd-button" href="{{ route('dswd.beneficiaries') }}">View beneficiaries</a><a class="dswd-button secondary" href="{{ route('dswd.statistics') }}">Explore statistics</a><a class="dswd-button secondary" href="{{ route('dswd.reports') }}">Generate a report</a></div><p class="dswd-note">Children aged 0–2 means 0–24 completed months. Counts use recorded birth dates. 4Ps membership reflects the beneficiary's Project INAY registration.</p></section>
@endsection
