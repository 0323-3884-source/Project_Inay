@extends('layouts.dswd')
@section('heading', 'Reports')
@section('content')
@include('dswd.partials.filters')
<section class="admin-card">
    <h2>{{ \App\Http\Controllers\DswdController::REPORTS[$type] }}</h2>
    <p class="dswd-note">Filters: @forelse(array_filter($filters, fn ($value) => $value !== null && $value !== '') as $key => $value) {{ ucwords(str_replace('_', ' ', $key)) }}: {{ $value === '__unrecorded__' ? 'Not recorded' : $value }}{{ $loop->last ? '' : ' ·' }} @empty All registered 4Ps beneficiaries @endforelse</p>
    <p class="dswd-note">Generated {{ now()->format('F j, Y g:i A') }}. Date filters use beneficiary registration dates. Children are counted at 0–24 completed months.</p>
    <div class="dswd-actions"><a class="dswd-button" href="{{ route('dswd.reports.download', array_merge($filters, ['report' => $type])) }}">Download CSV</a><button class="dswd-button secondary" type="button" onclick="window.print()">Print report</button></div>
    <table class="dswd-table"><thead><tr><th scope="col">Category</th><th scope="col">Count</th></tr></thead><tbody>@forelse($rows as $label => $count)<tr><td>{{ $label }}</td><td>{{ number_format($count) }}</td></tr>@empty<tr><td colspan="2">No beneficiaries match these filters.</td></tr>@endforelse</tbody></table>
</section>
@endsection
