<section class="admin-card">
    <dl class="dswd-details">
        @foreach(['household'=>'Household reference','beneficiary_id'=>'Beneficiary ID','sex'=>'Sex','barangay'=>'Barangay','municipality_city'=>'Municipality / City'] as $field=>$label)
            <div><dt>{{ $label }}</dt><dd>{{ $beneficiary->$field ?: 'Not recorded' }}</dd></div>
        @endforeach
        <div><dt>Classification</dt><dd>{{ \App\Support\F1kdCompliance::CLASSES[$beneficiary->classification] }}</dd></div>
        @if($beneficiary->classification === 'child')<div><dt>Mother / household beneficiary</dt><dd>{{ $beneficiary->mother_name }}</dd></div>@endif
        <div><dt>Reporting period</dt><dd>{{ \Carbon\CarbonImmutable::parse($beneficiary->month.'-01')->format('F Y') }}</dd></div>
        <div><dt>Monthly status</dt><dd>@include('dswd.f1kd.badge', ['status'=>$beneficiary->status])</dd></div>
        <div><dt>4Ps status</dt><dd>{{ $beneficiary->four_ps_status }}</dd></div>
        <div><dt>Assigned Program Staff</dt><dd>{{ $beneficiary->assigned_staff }}</dd></div>
        <div><dt>Registered children</dt><dd>@forelse($beneficiary->registered_children as $child){{ $child->name }} (CHILD-{{ $child->id }})@if(!$loop->last)<br>@endif @empty None registered @endforelse</dd></div>
        <div><dt>DSWD verification</dt><dd>{{ $beneficiary->dswd_verified_at?->format('M j, Y H:i') ?? 'Not yet verified' }}</dd></div>
    </dl>
    <p class="dswd-note">Household reference uses the Project INAY registration ID. Last updated: {{ $beneficiary->updated_at?->format('M j, Y H:i') ?? 'Not yet recorded' }}.</p>
    <form method="get" class="dswd-actions">
        <label for="f1kd-period">Reporting period <input id="f1kd-period" type="month" name="month" value="{{ $beneficiary->month }}" required></label>
        <button class="dswd-button secondary">View Period</button>
    </form>
</section>
