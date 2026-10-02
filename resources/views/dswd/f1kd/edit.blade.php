@extends('layouts.app')
@section('title', 'F1KD Monitoring - Project INAY')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/account-pages.css') }}">
<style>.f1kd-staff-form { display:grid; gap:16px; padding:24px; background:white; border:1px solid #eadfe4; border-radius:16px; } .f1kd-staff-form label { font-weight:700; } .f1kd-staff-form select { width:100%; max-width:440px; min-height:44px; padding:8px; border:1px solid #cbd8ea; border-radius:8px; font:inherit; } .f1kd-staff-form p { margin:0; line-height:1.6; }</style>
@endpush
@section('content')
<section class="account-page"><header class="account-heading"><div><p class="account-kicker">PROGRAM STAFF / F1KD</p><h1>{{ $beneficiary->name }}</h1><p>{{ \App\Support\F1kdCompliance::CLASSES[$beneficiary->classification] }} / {{ $beneficiary->month }}</p></div><a class="account-button" href="{{ route('staff.mothers.show', $beneficiary->mother_id) }}">Back to casefile</a></header>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
@if($errors->any())<ul role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
<form method="post" action="{{ route('staff.f1kd.update', ['subject'=>$beneficiary->key, 'month'=>$beneficiary->month]) }}" class="f1kd-staff-form">@csrf @method('PUT')
<p>Verify each applicable service from authorized health-monitoring information. Mark future services and outcomes that do not apply as Not Applicable. Use Service Unavailable when a required service cannot be provided. Do not enter diagnoses or clinical notes.</p>
@foreach($conditions as $key=>$label)<p><label for="condition-{{ $key }}">{{ $label }}</label><br><select id="condition-{{ $key }}" name="checklist[{{ $key }}]" required>@foreach(\App\Support\F1kdCompliance::STATUSES as $value=>$status)<option value="{{ $value }}" @selected(old('checklist.'.$key, $beneficiary->checklist[$key])===$value)>{{ $status }}</option>@endforeach</select></p>@endforeach
<button class="account-button is-primary">Save monthly monitoring</button></form></section>
@endsection
