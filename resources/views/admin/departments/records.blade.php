@extends('layouts.admin')
@section('title', $title.' - Project INAY')
@section('content')
    <header class="admin-topbar"><div><p class="admin-kicker">{{ strtoupper($scope) }} ADMIN CONSOLE</p><h1 class="admin-page-title">{{ $scope === 'dswd' ? 'F1KD Monitoring' : $title }}</h1></div></header>
    @if($scope === 'dswd')
        @include('admin.departments.f1kd-navigation')
    @endif
    @if(session('status'))<p class="alert">{{ session('status') }}</p>@endif
    @if($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
    <form class="department-filter" method="GET"><label>Search<input type="search" name="q" value="{{ $search }}" placeholder="Search by name"></label><button type="submit">Search</button></form>
    <section class="admin-card">@include('admin.departments.table')</section>
    {{ $records->links('pagination.portal') }}
@endsection
