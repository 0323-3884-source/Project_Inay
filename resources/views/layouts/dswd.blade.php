@extends('layouts.app')
@section('title', 'DSWD / 4Ps Staff - Project INAY')
@section('body_class', 'dswd-shell')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/account-pages.css') }}?v={{ filemtime(public_path('css/account-pages.css')) }}">
    @include('dswd.partials.styles')
    @include('dswd.partials.portal-styles')
@endpush
