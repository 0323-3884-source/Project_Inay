@extends('layouts.admin')
@section('title', $title.' - Project INAY')
@section('content')
    <header class="admin-topbar"><div><p class="admin-kicker">DSWD ADMIN CONSOLE</p><h1 class="admin-page-title">F1KD Monitoring</h1><p class="admin-page-copy">Monthly monitoring for 4Ps mothers and children.</p></div></header>
    @include('admin.departments.f1kd-navigation')
    <form class="department-filter" method="GET"><label>Reporting month<input type="month" name="month" value="{{ $month }}" max="{{ now()->format('Y-m') }}"></label><label>Search<input type="search" name="q" value="{{ $search }}"></label><button type="submit">Update</button></form>
    <p class="department-note">{{ $month }} · Attendance submitted by Program Staff or verified by DSWD Staff. A missing submission remains “Not yet recorded” until attendance is recorded.</p>
    @foreach($groups as $group => $records)
        <section class="admin-card" style="margin-bottom: 20px"><div class="admin-card-head"><h2>{{ $group }}</h2><span>{{ $records->count() }} beneficiaries</span></div>@include('admin.departments.table')</section>
    @endforeach
@endsection
