@php
    $assignedMidwife = $staff->assignedMidwife;
    $selectedMidwife = old('midwife_selection', $assignedMidwife
        ? ($assignedMidwife->program_staff_id ? 'staff:'.$assignedMidwife->program_staff_id : 'profile:'.$assignedMidwife->id)
        : '');
@endphp
<fieldset class="clinic-midwife" data-midwife-form>
    <legend>Midwife Information</legend>
    <div class="clinic-midwife-bar">
        <strong data-midwife-current>No assigned midwife</strong>
        <button class="clinic-secondary" type="button" data-midwife-toggle aria-controls="midwife-editor" aria-expanded="false">Add Midwife</button>
    </div>
    <div id="midwife-editor" data-midwife-editor @if(!$errors->any() && $selectedMidwife !== 'new') hidden @endif>
    <div class="clinic-midwife-picker">
    <label>
        Select midwife
        <select name="midwife_selection" data-midwife-selection>
            <option value="">No assigned midwife</option>
            @foreach ($midwifeOptions as $option)
                <option value="{{ $option['value'] }}" @selected($selectedMidwife === $option['value'])>
                    {{ $option['name'] }} &middot; {{ $option['facility'] ?: 'Facility not set' }}
                </option>
            @endforeach
            <option value="new" hidden @selected($selectedMidwife === 'new')>New midwife</option>
        </select>
    </label>
    <button class="clinic-secondary" type="button" data-midwife-add>Add Midwife</button>
    </div>
    @error('midwife_selection') <p class="clinic-midwife-error" role="alert">{{ $message }}</p> @enderror
    <script type="application/json" data-midwife-options>@json($midwifeOptions)</script>
    <div class="clinic-midwife-summary" data-midwife-summary hidden aria-live="polite"></div>
    @if ($assignedMidwife && ! $assignedMidwife->program_staff_id && (int) $assignedMidwife->created_by_staff_id === (int) $staff->id)
        <label data-midwife-existing-status="profile:{{ $assignedMidwife->id }}">
            Availability status
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
        <input type="hidden" name="midwife_barangay" value="{{ old('assigned_barangay', $staff->assigned_barangay) }}">
        <input type="hidden" name="midwife_facility" value="{{ old('assigned_facility', $staff->assigned_facility) }}">
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
    <p class="clinic-panel-copy">Uses your barangay and facility. Click Save Profile to apply changes.</p>
    </div>
</fieldset>
