@extends('layouts.dswd')
@section('heading', 'F1KD Monitoring')
@section('content')
<header class="account-heading"><div><p class="account-kicker">DSWD / 4Ps</p><h1>F1KD Monitoring</h1><p>Review monthly compliance for pregnant women and children aged 0-24 months.</p></div></header>
<section class="admin-card">@include('dswd.f1kd.filters')
<p class="dswd-note">Read-only compliance summaries. Program Staff verify and update monthly checklists. Historical months show saved records only. Household references use Project INAY mother registration IDs, not official DSWD household numbers.</p>
<div class="dswd-table-wrap"><table class="dswd-table"><thead><tr>@foreach(['Household / Beneficiary ID','Beneficiary name','Sex','Barangay','Classification','F1KD status','Areas for verification','Reporting month','Last updated','Action'] as $label)<th scope="col">{{ $label }}</th>@endforeach</tr></thead><tbody>
@forelse($beneficiaries as $row)<tr><td>{{ $row->household }}<br>{{ $row->beneficiary_id }}</td><td>{{ $row->name }}</td><td>{{ ucfirst($row->sex ?: 'Not recorded') }}</td><td>{{ $row->barangay }}</td><td>{{ \App\Support\F1kdCompliance::CLASSES[$row->classification] }}</td><td>@include('dswd.f1kd.badge', ['status'=>$row->status])</td><td>{{ count($row->verification) }} area(s)@if($row->verification)<details><summary>Show areas</summary><ul>@foreach($row->verification as $label)<li>{{ $label }}</li>@endforeach</ul></details>@endif</td><td>{{ $row->month }}</td><td>{{ $row->updated_at?->format('M j, Y H:i') ?? 'Awaiting verification' }}</td><td><a href="{{ route('dswd.f1kd.show', ['subject'=>$row->key, 'month'=>$row->month]) }}">View<span class="sr-only"> {{ $row->name }}</span></a></td></tr>
@empty<tr><td colspan="10">No F1KD beneficiaries match these filters.</td></tr>@endforelse
</tbody></table></div>@include('dswd.partials.pagination', ['paginator'=>$beneficiaries])</section>
@endsection
