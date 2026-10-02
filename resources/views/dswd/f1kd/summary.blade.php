<div class="admin-summary-grid f1kd-summary">
@foreach (['total'=>'Total 4Ps F1KD beneficiaries','pregnant'=>'Pregnant 4Ps beneficiaries','children'=>'Children 0-24 months','compliant'=>'F1KD compliant beneficiaries','verification'=>'Beneficiaries requiring verification','unavailable'=>'Required health service unavailable'] as $key=>$label)
<article class="admin-summary-card"><span class="admin-summary-icon">@include('dswd.partials.icon', ['icon'=>'users'])</span><div><span>{{ $label }}</span><strong>{{ number_format($summary[$key]) }}</strong></div></article>
@endforeach
</div>
<p class="dswd-note">Counts cover individual pregnant women and children in registered 4Ps households. Verification and unavailable-service counts may overlap. Overall status prioritizes Service Unavailable, then For Verification. All Not Applicable items do not imply compliance.</p>
