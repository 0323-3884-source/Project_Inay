<div class="admin-summary-grid f1kd-summary">
@foreach ((($simpleReport ?? false) ? ['total'=>'Total beneficiaries','pregnant'=>'Registered 4Ps mothers','children'=>'Children aged 0–24 months','compliant'=>'Compliant','verification'=>'Pending verification'] : ['total'=>'Total 4Ps F1KD beneficiaries','pregnant'=>'Registered 4Ps Mothers','children'=>'Children 0-24 months','compliant'=>'Attended / Compliant','verification'=>'Not Yet Recorded / For Verification']) as $key=>$label)
<article class="admin-summary-card"><span class="admin-summary-icon">@include('dswd.partials.icon', ['icon'=>'users'])</span><div><span>{{ $label }}</span><strong>{{ number_format($summary[$key]) }}</strong></div></article>
@endforeach
</div>
<p class="dswd-note">{{ ($simpleReport ?? false) ? 'Compliance is based on recorded attendance. Beneficiaries with recorded attendance are compliant. Missing attendance requires verification.' : 'Monthly status is based on recorded attendance: Attended = Compliant; no attendance recorded = For Verification. Legacy checklists do not establish attendance.' }}</p>
