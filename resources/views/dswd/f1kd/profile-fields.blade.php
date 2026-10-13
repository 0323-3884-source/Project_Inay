    <label>Entry ID (DSWD Entry No.)
        <input type="text" name="dswd_entry_number" inputmode="numeric" pattern="[0-9]+" maxlength="30" placeholder="e.g. 422258060" value="{{ old('dswd_entry_number', $beneficiary->dswd_entry_number) }}">
    </label>
    <p class="dswd-note">Enter this {{ $beneficiary->classification === 'child' ? 'child' : 'mother' }}'s own DSWD entry number. Each mother and child has a separate Entry ID; the Household No. identifies their household.</p>
@if($beneficiary->classification !== 'child')
    <label>Last Menstrual Period (LMP)
        <input type="date" name="last_menstrual_period" max="{{ now()->toDateString() }}" value="{{ old('last_menstrual_period', $beneficiary->last_menstrual_period?->format('Y-m-d')) }}">
    </label>
@endif
<p class="dswd-note">These profile details are shared with Program Staff and DSWD Staff across reporting periods.</p>
