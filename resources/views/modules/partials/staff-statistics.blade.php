<div class="staff-statistics">
    <p>Clinical statistics from your assigned casefiles. Generated {{ now()->format('d M Y, h:i A') }}.</p>
    <div class="staff-stat-summary">
        @foreach($statistics['summary'] as $label => $count)
            <article class="report-card staff-stat-tile"><span>{{ $label }}</span><strong>{{ $count }}</strong></article>
        @endforeach
    </div>
    <div class="staff-stat-panels">
        @foreach($statistics['sections'] as $section)
            <section class="report-card staff-stat-panel">
                <h2>{{ $section['title'] }}</h2>
                <p>{{ $section['note'] }} Total: {{ $section['total'] }}.</p>
                @if($section['donut'] ?? false)
                    <div class="staff-stat-donut" style="--share:{{ $statistics['fourPsPercentage'] }}%" role="img" aria-label="{{ $statistics['fourPsPercentage'] }} percent of assigned mothers are 4Ps beneficiaries">
                        <div><strong>{{ $statistics['fourPsPercentage'] }}%</strong><span>4Ps coverage</span></div>
                    </div>
                @endif
                @forelse($section['rows'] as $label => $count)
                    @php $percentage = $section['total'] > 0 ? round($count / $section['total'] * 100, 1) : 0; @endphp
                    <div class="staff-stat-row">
                        <div><span>{{ $label }}</span><strong>{{ $count }} <small>({{ $percentage }}%)</small></strong></div>
                        <div class="staff-stat-track" aria-hidden="true"><i style="width:{{ $percentage }}%"></i></div>
                    </div>
                @empty
                    <p>No records available for this distribution.</p>
                @endforelse
            </section>
        @endforeach
    </div>
</div>
