@php
    $assignedMidwife = $staff->assignedMidwife;
    $selectedMidwife = old('midwife_selection', $assignedMidwife
        ? ($assignedMidwife->program_staff_id ? 'staff:'.$assignedMidwife->program_staff_id : 'profile:'.$assignedMidwife->id)
        : '');
@endphp
<fieldset class="clinic-midwife" data-midwife-form>
    <legend>Midwife Information</legend>
    <p class="clinic-panel-copy">Assign a midwife from the same barangay and facility to new appointments. Existing appointments keep their saved care team.</p>
    <label>
        Select midwife
        <select name="midwife_selection" data-midwife-selection>
            <option value="">No assigned midwife</option>
            @foreach ($midwifeOptions as $option)
                <option value="{{ $option['value'] }}" @selected($selectedMidwife === $option['value'])>
                    {{ $option['name'] }} — {{ $option['facility'] ?: 'Facility not set' }} ({{ $option['source'] }})
                </option>
            @endforeach
            <option value="new" @selected($selectedMidwife === 'new')>Enter a midwife not yet registered</option>
        </select>
    </label>
    @error('midwife_selection') <p class="clinic-midwife-error" role="alert">{{ $message }}</p> @enderror
    <script type="application/json" data-midwife-options>@json($midwifeOptions)</script>
    <div class="clinic-midwife-summary" data-midwife-summary hidden aria-live="polite"></div>
    @if ($assignedMidwife && ! $assignedMidwife->program_staff_id && (int) $assignedMidwife->created_by_staff_id === (int) $staff->id)
        <label data-midwife-existing-status="profile:{{ $assignedMidwife->id }}">
            Availability status for this personnel record
            <select name="existing_midwife_availability_status">
                <option value="available" @selected(old('existing_midwife_availability_status', $assignedMidwife->availability_status) === 'available')>Available</option>
                <option value="unavailable" @selected(old('existing_midwife_availability_status', $assignedMidwife->availability_status) === 'unavailable')>Unavailable</option>
            </select>
        </label>
    @endif
    <div class="clinic-midwife-fields" data-midwife-new @if($selectedMidwife !== 'new') hidden @endif>
        <label>Midwife full name
            <input name="midwife_full_name" value="{{ old('midwife_full_name') }}" maxlength="255" data-midwife-required>
        </label>
        <label>Professional role
            <input value="Midwife" readonly aria-label="Professional role">
        </label>
        <label>Assigned barangay
            <select name="midwife_barangay" data-midwife-required>
                <option value="">Select barangay</option>
                @foreach ($barangays as $barangay)
                    <option value="{{ $barangay }}" @selected(old('midwife_barangay', $staff->assigned_barangay) === $barangay)>{{ $barangay }}</option>
                @endforeach
            </select>
        </label>
        <label>Assigned healthcare facility
            <input name="midwife_facility" value="{{ old('midwife_facility', $staff->assigned_facility) }}" maxlength="255" data-midwife-required>
        </label>
        <label>Contact number (optional)
            <input type="tel" name="midwife_contact_number" value="{{ old('midwife_contact_number') }}" maxlength="30">
        </label>
        <label>Availability status
            <select name="midwife_availability_status">
                <option value="available" @selected(old('midwife_availability_status') !== 'unavailable')>Available</option>
                <option value="unavailable" @selected(old('midwife_availability_status') === 'unavailable')>Unavailable</option>
            </select>
        </label>
    </div>
    <p class="clinic-panel-copy">Unavailable midwives are not assigned to new bookings. Registered midwives manage their own hours and availability. A matching existing record is reused without replacing its details.</p>
</fieldset>
