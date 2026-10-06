@extends('layouts.dswd')
@section('heading', '4Ps Beneficiaries')
@section('content')
    @include('dswd.partials.filters')
    <section class="admin-card">
        <div class="dswd-table-wrap"><table class="dswd-table"><thead><tr>@foreach(['Beneficiary ID', 'Mother/Beneficiary Name', 'Household ID', 'Barangay', 'Municipality/City', '4Ps Status', 'Pregnancy Status', 'Has Child Aged 0–2', 'Number of Children Aged 0–2', 'Registration Date', 'Status', 'Action'] as $heading)<th scope="col">{{ $heading }}</th>@endforeach</tr></thead>
        <tbody>@forelse($beneficiaries as $beneficiary)<tr>
            <td>INAY-{{ $beneficiary->id }}</td><td>{{ $beneficiary->full_name }}</td><td>{{ $beneficiary->four_ps_household_number ?: 'Not recorded' }}</td><td>{{ $beneficiary->barangay ?: 'Not recorded' }}</td><td>{{ $beneficiary->municipality_city ?: 'Not recorded' }}</td><td>4Ps beneficiary</td><td>{{ \App\Support\DswdStatistics::PREGNANCY[$beneficiary->pregnancy_status] ?? 'Not recorded' }}</td><td>{{ $beneficiary->young_children_count > 0 ? 'Yes' : 'No' }}</td><td>{{ $beneficiary->young_children_count }}</td><td>{{ $beneficiary->created_at?->format('M j, Y') ?? 'Not recorded' }}</td><td>Registered</td><td><a href="{{ route('dswd.beneficiaries.show', $beneficiary->id) }}">View profile</a></td>
        </tr>@empty<tr><td colspan="12">No 4Ps beneficiaries match your search.</td></tr>@endforelse</tbody></table></div>
        @include('dswd.partials.pagination', ['paginator' => $beneficiaries])
        <p class="dswd-note">Beneficiary IDs are Project INAY IDs. “Registered” describes registration in this system; 4Ps membership is self-reported.</p>
    </section>
@endsection
