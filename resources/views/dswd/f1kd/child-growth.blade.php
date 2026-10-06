@push('styles')
<link rel="stylesheet" href="{{ asset('css/child-profile.css') }}?v={{ filemtime(public_path('css/child-profile.css')) }}">
<style>
.dswd-growth-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
.dswd-growth-card { min-width:0; padding:18px; }
.dswd-growth-head { display:flex; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:16px; }
.dswd-growth-head h2 { margin:0; font-size:20px; }
.dswd-growth-head p { margin:8px 0 0; color:#52627a; }
.dswd-growth-head small { color:#8797ae; font-weight:800; }
.dswd-growth-card .child-growth-chart { min-width:0; width:100%; padding:12px; box-sizing:border-box; background:#f8fafc; border:1px solid #e5edf6; border-radius:8px; }
@media(max-width:760px) { .dswd-growth-grid { grid-template-columns:1fr; } }
</style>
@endpush
@push('scripts')
<script src="{{ asset('js/child-profile.js') }}?v={{ filemtime(public_path('js/child-profile.js')) }}" defer></script>
@endpush
@php($growthNumber = fn ($value, $unit) => $value === null ? 'Not recorded' : rtrim(rtrim(number_format((float) $value, 2), '0'), '.').' '.$unit)
<div class="dswd-growth-grid">
@foreach([['Weight Progress','kg','weight',''], ['Height Progress','cm','height','is-purple']] as [$title,$unit,$field,$tone])
    <section class="admin-card dswd-growth-card {{ $tone }}">
        <div class="dswd-growth-head"><div><h2>{{ $title }}</h2><p>Latest: {{ $growthNumber($childGrowth[$field]['latest'], $unit) }}</p></div><small>UNIT: {{ strtoupper($unit) }}</small></div>
        @include('partials.child-growth-chart', ['chart'=>$childGrowth[$field], 'title'=>$title, 'unit'=>$unit])
    </section>
@endforeach
</div>
<section class="admin-card" id="growth-history">
    <div class="dswd-growth-head"><h2>Growth History</h2><small>{{ $childGrowth['history']->total() }} RECORDS</small></div>
    <p class="dswd-note">Saved measurements through {{ $beneficiary->month }}. Updated by Program Staff / BHW.</p>
    <div class="dswd-table-wrap"><table class="dswd-table">
        <thead><tr><th scope="col">Age</th><th scope="col">Weight</th><th scope="col">Height</th><th scope="col">Date</th><th scope="col">Recorded by</th></tr></thead>
        <tbody>@forelse($childGrowth['history'] as $growth)
            <tr><td>{{ $growth->age === null ? 'Not recorded' : $growth->age.' mo' }}</td><td>{{ $growthNumber($growth->weight, 'kg') }}</td><td>{{ $growthNumber($growth->height, 'cm') }}</td><td>{{ $growth->date->format('M j, Y') }}</td><td>{{ $growth->recorder }}</td></tr>
        @empty
            <tr><td colspan="5">No child growth measurements recorded for this period.</td></tr>
        @endforelse</tbody>
    </table></div>
    @if($childGrowth['history']->hasPages())
        <nav class="dswd-actions" aria-label="Growth history pages">
            @if($childGrowth['history']->currentPage() > 1)
                <a class="dswd-button secondary" href="{{ $childGrowth['history']->previousPageUrl() }}">Recent History</a>
            @endif
            <span class="dswd-note">Page {{ $childGrowth['history']->currentPage() }} of {{ $childGrowth['history']->lastPage() }}</span>
            @if($childGrowth['history']->hasMorePages())
                <a class="dswd-button secondary" href="{{ $childGrowth['history']->nextPageUrl() }}">Previous History</a>
            @endif
        </nav>
    @endif
</section>
