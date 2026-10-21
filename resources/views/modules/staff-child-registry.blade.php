@extends('layouts.app')
@section('title', 'Child Case Files - Project INAY')
@section('portal_title', '0–2 Children & Vaccines')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/child-registry.css') }}?v={{ filemtime(public_path('css/child-registry.css')) }}">
@endpush
@section('content')
<section class="child-registry">
    <header class="child-registry-heading"><p>Patient Registry · 0–2 Children & Vaccines</p><h1>Child Case Files</h1><p>Growth and vaccination monitoring for children ages 0–2 years.</p></header>
    @if(session('status'))<p role="status">{{ session('status') }}</p>@endif
    @if($errors->any())<div role="alert">{{ $errors->first() }}</div>@endif
    <div class="child-registry-stats">
        <article><span>Registered Children · Ages 0–2</span><strong>{{ $registeredCount }}</strong></article>
        <article><span>Matching Records</span><strong>{{ $registryChildren->total() }}</strong></article>
        <a class="child-registry-export" href="{{ route('staff.mothers', ['export' => 'all_csv']) }}">Export All Beneficiaries (CSV)</a>
    </div>
    <form class="child-registry-filters" method="GET" action="{{ route('staff.neonatal') }}">
        <label class="child-registry-search">Child Name<input type="search" name="q" value="{{ $search }}" placeholder="Search child name"></label>
        <label>4Ps Status<select name="four_ps"><option value="">All 4Ps statuses</option><option value="yes" @selected($fourPs === 'yes')>4Ps Beneficiary</option><option value="no" @selected($fourPs === 'no')>Non-4Ps</option></select></label>
        <button type="submit">Apply Filters</button>
        @if($search !== '' || $fourPs !== '')<a class="child-registry-clear" href="{{ route('staff.neonatal') }}">Clear</a>@endif
    </form>
    <section class="child-registry-card" aria-label="Registered children"><div class="child-registry-scroll"><table>
        <thead><tr><th>Case No.</th><th>Child Name</th><th>Age</th><th>Sex</th><th>Birth Date</th><th>Mother</th><th>Barangay</th><th>Action</th></tr></thead>
        <tbody>@forelse($registryChildren as $child)
            <tr><td data-label="Case No.">CH-{{ str_pad($child->id, 5, '0', STR_PAD_LEFT) }}</td><td data-label="Child Name"><strong>{{ $child->full_name }}</strong></td><td data-label="Age">{{ \App\Support\ChildProfileDisplay::age($child->getRawOriginal('birth_date')) }}</td><td data-label="Sex">{{ ucfirst($child->sex) }}</td><td data-label="Birth Date">{{ $child->birth_date->format('M j, Y') }}</td><td data-label="Mother">{{ $child->mother?->full_name }}</td><td data-label="Barangay">{{ $child->mother?->barangay ?? 'Not recorded' }}</td><td data-label="Action"><a class="child-registry-view" href="{{ route('staff.neonatal', ['child' => $child->id, 'from' => 'registry']) }}">View Case File</a></td></tr>
        @empty<tr><td colspan="8">{{ $search !== '' ? 'No children match your search.' : 'No children ages 0–2 registered in your casefiles yet.' }}</td></tr>@endforelse</tbody>
    </table></div><div class="child-registry-pagination">{{ $registryChildren->links() }}</div></section>
</section>
@endsection
