@extends('layouts.admin')
@section('title', 'Clinical Records - Project INAY')
@section('content')
    <header class="admin-topbar"><div><p class="admin-kicker">CHO ADMIN CONSOLE</p><h1 class="admin-page-title">{{ $record->full_name }}</h1><p class="admin-page-copy">{{ $record->barangay }} · {{ $record->pregnancy_status }}</p></div><a class="department-action" href="{{ route('admin.cho.module', 'mothers') }}">Back to Mothers and Children</a></header>
    @foreach($sections as $title => $section)
        <section class="admin-card" style="margin-bottom:20px"><div class="admin-card-head"><h2>{{ $title }}</h2></div>@include('admin.departments.table', ['headers' => $section['headers'], 'records' => $section['records']])</section>
    @endforeach
@endsection
