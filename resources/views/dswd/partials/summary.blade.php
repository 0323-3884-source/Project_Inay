<div class="admin-summary-grid">
@foreach ([['total', 'Total 4Ps Mothers', 'users'], ['pregnant', 'Pregnant 4Ps Beneficiaries', 'users'], ['mothers_with_children', '4Ps Mothers with Children Aged 0–2', 'users'], ['children', 'Total 4Ps Children Aged 0–2', 'baby']] as [$key, $label, $icon])
    <article class="admin-summary-card"><span class="admin-summary-icon">@include('dswd.partials.icon', ['icon' => $icon])</span><div><span>{{ $label }}</span><strong>{{ number_format($summary[$key]) }}</strong></div></article>
@endforeach
</div>
