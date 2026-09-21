<dialog id="mother-information-dialog" class="mother-information-dialog" aria-labelledby="mother-information-title" data-reopen="{{ $errors->motherInformation->any() ? 'true' : 'false' }}">
<form id="mother-information-form" method="POST" action="{{ route('staff.mothers.update', $mother) }}">
    @csrf
    @method('PATCH')
    <header class="vitals-dialog-header">
        <h2 id="mother-information-title">Edit Mother Information</h2>
        <button type="button" data-mother-edit-close aria-label="Close edit information">&times;</button>
    </header>
    @if ($errors->motherInformation->any())
        <div role="alert">
            <p>Please correct the following information:</p>
            <ul>
                @foreach ($errors->motherInformation->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="vitals-field-grid">
        @foreach (['first_name' => 'First Name', 'middle_name' => 'Middle Name', 'last_name' => 'Last Name', 'contact_number' => 'Contact Number', 'barangay' => 'Barangay'] as $field => $label)
            <label>
                <span>{{ $label }}</span>
                <input type="{{ $field === 'contact_number' ? 'tel' : 'text' }}" name="{{ $field }}" value="{{ old($field, $mother->$field) }}" maxlength="{{ $field === 'barangay' ? 255 : ($field === 'contact_number' ? 25 : 100) }}" @required($field !== 'middle_name')>
            </label>
        @endforeach
        @foreach (['age' => ['Age', 10, 65], 'gravidity' => ['Gravidity (total pregnancies)', 0, 30], 'parity' => ['Parity (total deliveries)', 0, 30]] as $field => [$label, $min, $max])
            <label>
                <span>{{ $label }}</span>
                <input type="number" name="{{ $field }}" value="{{ old($field, $mother->$field) }}" min="{{ $min }}" max="{{ $max }}" step="1">
            </label>
        @endforeach
        @foreach (['civil_status' => ['Civil Status', ['Single' => 'Single', 'Married' => 'Married', 'Widowed' => 'Widowed', 'Separated' => 'Separated']], 'blood_type' => ['Blood Type', array_combine(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Unknown'], ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Unknown'])], 'pregnancy_status' => ['Pregnancy Status', ['not_pregnant' => 'Not pregnant', 'pregnant' => 'Pregnant', 'postpartum' => 'Postpartum', 'planning' => 'Planning']]] as $field => [$label, $options])
            <label>
                <span>{{ $label }}</span>
                <select name="{{ $field }}">
                    <option value="">Not provided</option>
                    @foreach ($options as $value => $text)
                        <option value="{{ $value }}" @selected(old($field, $mother->$field) === $value)>{{ $text }}</option>
                    @endforeach
                </select>
            </label>
        @endforeach
        <label>
            <span>4Ps Status</span>
            <select name="is_4ps_beneficiary" required>
                <option value="0" @selected(! old('is_4ps_beneficiary', $mother->is_4ps_beneficiary))>Non-4Ps Beneficiary</option>
                <option value="1" @selected(old('is_4ps_beneficiary', $mother->is_4ps_beneficiary))>4Ps Beneficiary</option>
            </select>
        </label>
    </div>
    <div class="casefile-profile-actions">
        <button type="button" data-mother-edit-cancel>Cancel</button>
        <button type="submit" class="is-dark">Save Changes</button>
    </div>
</form>
</dialog>
