@extends('layouts.dswd')
@section('heading', '4Ps Statistics')
@section('content')
@include('dswd.partials.filters')
@include('dswd.partials.summary')
<p class="dswd-note">As of {{ today()->format('F j, Y') }}. Date filters apply to beneficiary registration. Child counts include 0–24 completed months and exclude unrecorded or future birth dates.</p>
<div class="admin-grid">
    @include('dswd.partials.chart', ['title' => 'Beneficiaries per barangay', 'values' => $summary['groups']['barangay']])
    @include('dswd.partials.chart', ['title' => 'Beneficiaries per municipality/city', 'values' => $summary['groups']['municipality_city']])
    <section class="admin-card">
        <div class="admin-card-head"><h2>Pregnancy status distribution</h2></div>
        @php($percentage = $summary['pregnant'] / max(1, $summary['total']) * 100)
        <div class="dswd-donut" aria-hidden="true" style="background:conic-gradient(#ce337e {{ $percentage }}%, #e8dee5 0)"><span>{{ $summary['pregnant'] }}<small>Pregnant</small></span></div>
        <p class="dswd-note">Pink: pregnant. Gray: other or unrecorded status.</p>
        <table class="dswd-table"><thead><tr><th scope="col">Status</th><th scope="col">Count</th></tr></thead><tbody>@foreach(\App\Support\DswdStatistics::PREGNANCY as $key => $label)<tr><td>{{ $label }}</td><td>{{ $summary['pregnancy'][$key] }}</td></tr>@endforeach</tbody></table>
    </section>
    @include('dswd.partials.chart', ['title' => 'Children by age', 'values' => $summary['ages']])
</div>
@endsection
