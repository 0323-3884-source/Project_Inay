<section class="admin-card">
    <div class="admin-card-head"><h2>{{ $title }}</h2></div>
    @forelse($values as $label => $count)
        <div class="dswd-chart-row"><span>{{ $label }}</span><div class="dswd-track" aria-hidden="true"><div class="dswd-fill" style="width:{{ round($count / max(1, max($values)) * 100, 1) }}%"></div></div><strong>{{ number_format($count) }}</strong></div>
    @empty<div class="admin-empty">No beneficiaries match these filters.</div>@endforelse
</section>
