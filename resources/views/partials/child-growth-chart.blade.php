<div class="child-growth-chart" data-child-growth-chart data-chart="{{ json_encode($chart) }}" data-unit="{{ $unit }}" data-title="{{ $title }}">
    @if($chart['has'])
        <svg aria-label="{{ $title }} by age in months"></svg>
        <div class="child-growth-tooltip" role="status" hidden></div>
        <noscript>Enable JavaScript to view the chart. Recorded measurements are available in Growth History.</noscript>
    @else
        <p class="child-growth-empty">No dated {{ $unit === 'kg' ? 'weight' : 'height' }} measurements available for this birth date.</p>
    @endif
</div>
