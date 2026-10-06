@extends('layouts.dswd')
@section('heading', '4Ps Beneficiary Profile')
@section('content')
<section class="admin-card">
    <h2>{{ $beneficiary->full_name }}</h2>
    <dl class="dswd-details">
        @foreach(['Beneficiary ID' => 'INAY-'.$beneficiary->id, 'Household ID' => $beneficiary->four_ps_household_number ?: 'Not recorded', 'Barangay' => $beneficiary->barangay ?: 'Not recorded', 'Municipality/City' => $beneficiary->municipality_city ?: 'Not recorded', '4Ps Status' => '4Ps beneficiary (self-reported)', 'Pregnancy Status' => \App\Support\DswdStatistics::PREGNANCY[$beneficiary->pregnancy_status] ?? 'Not recorded', 'Has Child Aged 0–2' => $beneficiary->young_children_count ? 'Yes' : 'No', 'Number of Children Aged 0–2' => $beneficiary->young_children_count, 'Registration Date' => $beneficiary->created_at?->format('F j, Y') ?? 'Not recorded', 'Status' => 'Registered'] as $label => $value)
        <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
        @endforeach
    </dl>
    <p class="dswd-note">Child counts use recorded birth dates and include 0–24 completed months as of {{ today()->format('F j, Y') }}.</p>
    <a class="dswd-button secondary" href="{{ route('dswd.beneficiaries') }}">Back to beneficiaries</a>
</section>
@include('dswd.partials.journey')
@endsection
