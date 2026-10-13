<section class="admin-card">
    @include('dswd.f1kd.photos')
    @if(request()->routeIs('dswd.f1kd.show'))
        <form class="dswd-form" method="POST" action="{{ route('dswd.f1kd.entry.update', ['subject'=>$beneficiary->key]) }}">
            @csrf @method('PATCH')
            <input type="hidden" name="month" value="{{ $beneficiary->month }}">
            <label>Entry ID<input type="text" name="dswd_entry_number" value="{{ old('dswd_entry_number', $beneficiary->dswd_entry_number) }}" inputmode="numeric" pattern="[0-9]+" maxlength="30"></label>
            <button class="dswd-button" type="submit">Save Entry ID</button>
        </form>
    @endif
    <dl class="dswd-details">
        @foreach(['dswd_entry_number'=>'Entry ID','household_id'=>'Household No.','beneficiary_id'=>'Project INAY ID','sex'=>'Sex','barangay'=>'Barangay','municipality_city'=>'Municipality / City'] as $field=>$label)
            <div><dt>{{ $label }}</dt><dd>{{ $beneficiary->$field ?: 'Not recorded' }}</dd></div>
        @endforeach
        <div><dt>Classification</dt><dd>{{ \App\Support\F1kdCompliance::CLASSES[$beneficiary->classification] }}</dd></div>
        @if($beneficiary->classification !== 'child')
            <div><dt>Last Menstrual Period (LMP)</dt><dd>{{ $beneficiary->last_menstrual_period?->format('M j, Y') ?? 'Not recorded' }}</dd></div>
        @endif
        @if($beneficiary->classification === 'child')<div><dt>Mother / household beneficiary</dt><dd>{{ $beneficiary->mother_name }}</dd></div>@endif
        <div><dt>Reporting period</dt><dd>{{ \Carbon\CarbonImmutable::parse($beneficiary->month.'-01')->format('F Y') }}</dd></div>
        <div><dt>Monthly status</dt><dd>@include('dswd.f1kd.badge', ['status'=>$beneficiary->status])</dd></div>
        <div><dt>4Ps status</dt><dd>{{ $beneficiary->four_ps_status }}</dd></div>
        <div><dt>Assigned Program Staff</dt><dd>{{ $beneficiary->assigned_staff }}</dd></div>
        <div><dt>Registered children</dt><dd>@forelse($beneficiary->registered_children as $child){{ $child->name }} (CHILD-{{ $child->id }})@if(!$loop->last)<br>@endif @empty None registered @endforelse</dd></div>
        <div><dt>DSWD verification</dt><dd>{{ $beneficiary->dswd_verified_at?->format('M j, Y H:i') ?? 'Not yet verified' }}</dd></div>
    </dl>
    <p class="dswd-note">Entry ID identifies this individual mother or child. Household No. identifies their 4Ps household. Last updated: {{ $beneficiary->updated_at?->format('M j, Y H:i') ?? 'Not yet recorded' }}.</p>
    <form method="get" class="dswd-actions">
        <label for="f1kd-period">Reporting period <input id="f1kd-period" type="month" name="month" value="{{ $beneficiary->month }}" required></label>
        <button class="dswd-button secondary">View Period</button>
    </form>
</section>
