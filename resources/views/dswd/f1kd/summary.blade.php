<div class="admin-summary-grid f1kd-summary">
@foreach (['total'=>'Total 4Ps F1KD beneficiaries','pregnant'=>'Pregnant 4Ps beneficiaries','children'=>'Children 0-24 months','compliant'=>'Attended / Compliant','verification'=>'Not Yet Recorded / For Verification','non_compliant'=>'Did Not Attend / Non-Compliant'] as $key=>$label)
<article class="admin-summary-card"><span class="admin-summary-icon">@include('dswd.partials.icon', ['icon'=>'users'])</span><div><span>{{ $label }}</span><strong>{{ number_format($summary[$key]) }}</strong></div></article>
@endforeach
</div>
<p class="dswd-note">Monthly status is based on recorded attendance: Attended = Compliant; Did Not Attend = Non-Compliant; no attendance recorded = For Verification. Legacy checklists do not establish attendance.</p>
