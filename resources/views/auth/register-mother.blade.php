@extends('layouts.app')

@section('title', 'Mother Registration - Project INAY')
@section('body_class', 'auth-body')
@section('auth_screen', 'true')

@php
    $fullName = old('full_name', trim(implode(' ', array_filter([
        old('first_name'),
        old('middle_name'),
        old('last_name'),
    ]))));

    $barangayOptions = $barangays ?? [];
@endphp

@section('content')
    <header class="auth-header">
        <span class="auth-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24">
                <path d="M12 21s-7.5-4.8-9.6-9.1C.7 8.4 2.8 4.5 6.7 4.5c2 0 3.6 1 4.5 2.5.9-1.5 2.5-2.5 4.5-2.5 3.9 0 6 3.9 4.3 7.4C19.5 16.2 12 21 12 21z" />
            </svg>
        </span>
        <h1 class="auth-title">Project INAY</h1>
        <div class="auth-kicker">MNCH INNOVATIVE MATERNAL PROGRAM</div>
        <p class="auth-subtitle">Innovative Nanay Building Strengthening Maternal, Neonatal and Child Health Program</p>
    </header>

    <section class="auth-card">
        <h2 class="auth-card-title">Mag-sign up sa platform</h2>
        <p class="auth-card-subtitle">Pumili ng tungkulin (Role-based Portal)</p>

        @if ($errors->any())
            <div class="alert error auth-error">Please fix the highlighted fields.</div>
        @endif

        <form method="POST" action="{{ route('mother.register.store') }}">
            @csrf

            <div class="role-grid" aria-label="Choose account role">
                <a class="role-option is-active" href="{{ route('mother.register') }}" aria-current="page">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M20.8 5.8a5.5 5.5 0 0 0-7.8 0L12 6.8l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 22l7.8-7.4 1-1a5.5 5.5 0 0 0 0-7.8z" />
                    </svg>
                    <span>Mother/User Portal</span>
                </a>

                <a class="role-option" href="{{ route('staff.register') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                    </svg>
                    <span>Program Staff</span>
                </a>
            </div>

            <div class="auth-field">
                <label class="auth-label" for="full_name">Buong Pangalan (Full Name)</label>
                <input class="auth-input" id="full_name" name="full_name" value="{{ $fullName }}" placeholder="e.g. Juan dela Cruz" required>
                @error('full_name')<span class="field-error">{{ $message }}</span>@enderror
                @error('first_name')<span class="field-error">{{ $message }}</span>@enderror
                @error('last_name')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <div class="auth-field">
                <label class="auth-label" for="email">Email Address</label>
                <input class="auth-input" id="email" type="email" name="email" value="{{ old('email') }}" placeholder="maria.santos@inayhealth.org" required>
                @error('email')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <div class="auth-field">
                <label class="auth-label" for="password">Password</label>
                <input class="auth-input" id="password" type="password" name="password" placeholder="Password" required>
                @error('password')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <div class="auth-field">
                <label class="auth-label" for="barangay">Barangay (San Pablo City, Laguna)</label>
                <input
                    class="auth-input"
                    id="barangay"
                    type="search"
                    name="barangay"
                    value="{{ old('barangay') }}"
                    list="san-pablo-barangays"
                    placeholder="Type to search barangay"
                    autocomplete="off"
                    required
                >
                <datalist id="san-pablo-barangays">
                    @foreach ($barangayOptions as $barangay)
                        <option value="{{ $barangay }}"></option>
                    @endforeach
                </datalist>
                <p class="auth-help">Type a few letters to search the 80 official San Pablo City barangays, then choose a match.</p>
                @error('barangay')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <div class="auth-field">
                <label class="auth-label" for="contact_number">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.3 19.3 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.7.6 2.5a2 2 0 0 1-.5 2.1L8 9.5a16 16 0 0 0 6.5 6.5l1.2-1.2a2 2 0 0 1 2.1-.5c.8.3 1.6.5 2.5.6A2 2 0 0 1 22 16.9z" />
                    </svg>
                    Contact Number
                </label>
                <input class="auth-input" id="contact_number" name="contact_number" value="{{ old('contact_number') }}" placeholder="e.g. 09171234567" required>
                <p class="auth-help">Use PH mobile format: 09XXXXXXXXX or +639XXXXXXXXX.</p>
                @error('contact_number')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <div class="auth-field-row">
                <div class="auth-field">
                    <label class="auth-label" for="age">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="18" rx="2" />
                            <path d="M16 2v4M8 2v4M3 10h18" />
                        </svg>
                        Age
                    </label>
                    <input class="auth-input" id="age" type="number" name="age" min="10" max="60" value="{{ old('age') }}" placeholder="e.g. 28">
                    @error('age')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="auth-field">
                    <label class="auth-label" for="blood_type">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 2s6 6.5 6 11a6 6 0 0 1-12 0c0-4.5 6-11 6-11z" />
                        </svg>
                        Blood Type
                    </label>
                    <select class="auth-select" id="blood_type" name="blood_type">
                        <option value="">Pumili ng blood type</option>
                        @foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Unknown'] as $bloodType)
                            <option value="{{ $bloodType }}" @selected(old('blood_type') === $bloodType)>{{ $bloodType }}</option>
                        @endforeach
                    </select>
                    @error('blood_type')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="auth-field">
                <label class="auth-label" for="civil_status">Civil Status</label>
                <select class="auth-select" id="civil_status" name="civil_status">
                    <option value="">Pumili ng civil status</option>
                    @foreach (['Single', 'Married', 'Widowed', 'Separated'] as $civilStatus)
                        <option value="{{ $civilStatus }}" @selected(old('civil_status') === $civilStatus)>{{ $civilStatus }}</option>
                    @endforeach
                </select>
                @error('civil_status')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <div class="auth-field-row">
                <div class="auth-field">
                    <label class="auth-label" for="gravidity">Gravidity (Number of Pregnancies)</label>
                    <input class="auth-input" id="gravidity" type="number" name="gravidity" min="0" step="1" value="{{ old('gravidity') }}" placeholder="e.g. 2" inputmode="numeric">
                    <p class="auth-help">Enter the total number of pregnancies, including the current pregnancy.</p>
                    @error('gravidity')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="auth-field">
                    <label class="auth-label" for="parity">Parity (Pregnancies Reaching Viability)</label>
                    <input class="auth-input" id="parity" type="number" name="parity" min="0" step="1" value="{{ old('parity') }}" placeholder="e.g. 1" inputmode="numeric" aria-describedby="parityHelp parityClientError">
                    <p class="auth-help" id="parityHelp">Enter the number of pregnancies reaching the project parity threshold.</p>
                    <span class="field-error" id="parityClientError" hidden>Parity cannot be greater than Gravidity.</span>
                    @error('parity')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="auth-field">
                <label class="auth-label" for="pregnancy_status">Pregnancy Status</label>
                <select class="auth-select" id="pregnancy_status" name="pregnancy_status">
                    <option value="not_pregnant" @selected(old('pregnancy_status', 'not_pregnant') === 'not_pregnant')>Not pregnant</option>
                    <option value="pregnant" @selected(old('pregnancy_status') === 'pregnant')>Pregnant</option>
                    <option value="postpartum" @selected(old('pregnancy_status') === 'postpartum')>Postpartum</option>
                    <option value="planning" @selected(old('pregnancy_status') === 'planning')>Planning pregnancy</option>
                </select>
                @error('pregnancy_status')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <div class="auth-field">
                <label class="auth-label" for="is_4ps_beneficiary">4Ps Beneficiary</label>
                <select class="auth-select" id="is_4ps_beneficiary" name="is_4ps_beneficiary" required>
                    <option value="">Pumili ng sagot</option>
                    <option value="yes" @selected(old('is_4ps_beneficiary') === 'yes')>Yes, 4Ps beneficiary</option>
                    <option value="no" @selected(old('is_4ps_beneficiary') === 'no')>No, not a 4Ps beneficiary</option>
                </select>
                @error('is_4ps_beneficiary')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <div class="location-box">
                <div>
                    <p class="location-title">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0z" />
                            <circle cx="12" cy="10" r="3" />
                        </svg>
                        <span>Location for Health<br>Services</span>
                    </p>
                    <p class="location-copy" id="locationStatus">Optional: use live location to guide you to nearby listed hospitals and clinics.</p>
                </div>
                <button class="location-button" id="useLocation" type="button">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="12" cy="12" r="3" />
                        <path d="M12 2v4M12 18v4M2 12h4M18 12h4" />
                    </svg>
                    Use Location
                </button>
            </div>

            <input id="location_latitude" type="hidden" name="location_latitude" value="{{ old('location_latitude') }}">
            <input id="location_longitude" type="hidden" name="location_longitude" value="{{ old('location_longitude') }}">
            <input id="location_accuracy" type="hidden" name="location_accuracy" value="{{ old('location_accuracy') }}">
            @error('location_latitude')<span class="field-error">{{ $message }}</span>@enderror
            @error('location_longitude')<span class="field-error">{{ $message }}</span>@enderror
            @error('location_accuracy')<span class="field-error">{{ $message }}</span>@enderror

            <label class="consent-box" for="privacy_policy">
                <input id="privacy_policy" type="checkbox" name="privacy_policy" value="1" @checked(old('privacy_policy')) required>
                <span>I have read and agree to the <button type="button" class="privacy-policy-link" data-open-privacy-policy aria-haspopup="dialog" aria-controls="privacyPolicyDialog">Privacy Policy</button>.</span>
            </label>
            @error('privacy_policy')<span class="field-error">{{ $message }}</span>@enderror

            <button class="auth-submit requires-consent" id="motherSubmit" type="submit">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M19 8v6M22 11h-6" />
                </svg>
                Register / Add Mother Portal
            </button>
        </form>

        <div class="auth-footer-link">
            <a href="{{ route('login') }}">Mayroon nang account? Log in</a>
        </div>
    </section>

    @include('auth.partials.privacy-policy')

    <script>
        const locationButton = document.getElementById('useLocation');
        const locationStatus = document.getElementById('locationStatus');
        const latitudeInput = document.getElementById('location_latitude');
        const longitudeInput = document.getElementById('location_longitude');
        const accuracyInput = document.getElementById('location_accuracy');
        const privacyPolicy = document.getElementById('privacy_policy');
        const motherSubmit = document.getElementById('motherSubmit');
        const gravidityInput = document.getElementById('gravidity');
        const parityInput = document.getElementById('parity');
        const parityClientError = document.getElementById('parityClientError');

        const syncObstetricValidation = () => {
            if (!gravidityInput || !parityInput) return;

            const gravidity = gravidityInput.value === '' ? null : Number(gravidityInput.value);
            const parity = parityInput.value === '' ? null : Number(parityInput.value);
            const invalid = gravidity !== null && parity !== null && parity > gravidity;

            parityInput.setCustomValidity(invalid ? 'Parity cannot be greater than Gravidity.' : '');
            parityClientError.hidden = !invalid;
        };

        const syncMotherSubmit = () => {
            motherSubmit.classList.toggle('is-ready', privacyPolicy.checked);
        };

        syncMotherSubmit();
        privacyPolicy.addEventListener('change', syncMotherSubmit);

        gravidityInput?.addEventListener('input', syncObstetricValidation);
        parityInput?.addEventListener('input', syncObstetricValidation);
        syncObstetricValidation();

        locationButton.addEventListener('click', () => {
            if (!navigator.geolocation) {
                locationStatus.textContent = 'Location is unavailable on this browser.';
                return;
            }

            locationStatus.textContent = 'Getting your current location...';
            navigator.geolocation.getCurrentPosition((position) => {
                latitudeInput.value = position.coords.latitude.toFixed(7);
                longitudeInput.value = position.coords.longitude.toFixed(7);
                accuracyInput.value = Math.round(position.coords.accuracy);
                locationStatus.textContent = 'Location added for nearby health services.';
            }, () => {
                locationStatus.textContent = 'Location permission was not allowed.';
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000,
            });
        });
    </script>
@endsection
