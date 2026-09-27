@extends('layouts.admin')

@section('title', $staff->full_name.' - Program Staff - Project INAY')

@php
    $staffAdminIcons = [
        'id-card' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2.4"/><path d="M6 16c.8-1.6 1.9-2.4 3-2.4s2.2.8 3 2.4"/><path d="M14 9h4"/><path d="M14 13h4"/><path d="M14 17h3"/></svg>',
        'message' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></svg>',
    ];
@endphp

@push('styles')
    <style>
        .staff-detail-grid { display: grid; grid-template-columns: minmax(280px, .8fr) minmax(0, 1.2fr); gap: 18px; align-items: start; }
        .staff-id-panel, .staff-edit-panel { background: #ffffff; border: 1px solid var(--admin-line); border-radius: 8px; box-shadow: 0 12px 30px rgba(15, 23, 42, 0.07); overflow: hidden; }
        .staff-id-preview { display: grid; min-height: 340px; place-items: center; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
        .staff-id-preview img { width: 100%; height: 100%; max-height: 420px; object-fit: contain; background: #ffffff; }
        .staff-id-placeholder { display: grid; gap: 12px; justify-items: center; padding: 28px; color: #64748b; text-align: center; }
        .staff-id-placeholder svg { width: 44px; height: 44px; }
        .staff-id-placeholder strong { color: #334155; font-size: 17px; font-weight: 900; }
        .staff-id-copy { display: grid; gap: 12px; padding: 18px; }
        .staff-status { display: inline-flex; align-items: center; width: fit-content; min-height: 34px; padding: 0 11px; border-radius: 999px; font-size: 11px; font-weight: 900; text-transform: uppercase; }
        .staff-status.is-verified { color: #007f5f; background: #ecfdf5; border: 1px solid #86efc2; }
        .staff-status.is-approved { color: #007f5f; background: #ecfdf5; border: 1px solid #86efc2; }
        .staff-status.is-pending { color: #c2410c; background: #fff7ed; border: 1px solid #fed7aa; }
        .staff-status.is-rejected { color: #be123c; background: #fff1f2; border: 1px solid #fecdd3; }
        .staff-status.is-missing { color: #64748b; background: #f1f5f9; border: 1px solid #cbd5e1; }
        .staff-status-row { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .staff-id-copy dl { display: grid; gap: 10px; margin: 0; }
        .staff-id-copy dl > div { display: grid; gap: 4px; padding: 11px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; }
        .staff-id-copy dt { color: #64748b; font-size: 11px; font-weight: 900; text-transform: uppercase; }
        .staff-id-copy dd { margin: 0; color: #071127; font-weight: 900; }
        .staff-verify-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .staff-verify-actions form, .staff-decision-actions form { margin: 0; }
        .staff-verify-actions button, .staff-decision-actions button, .staff-back-link, .staff-save { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 44px; padding: 0 14px; border-radius: 8px; font-weight: 900; text-decoration: none; cursor: pointer; }
        .staff-verify-actions button, .staff-save { color: #ffffff; background: var(--admin-green); border: 1px solid var(--admin-green); }
        .staff-verify-actions .is-light, .staff-back-link { color: #334155; background: #f8fafc; border: 1px solid #cbd8ea; }
        .staff-detail-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; justify-content: flex-end; }
        .staff-back-link.is-chat { color: #ffffff; background: var(--admin-green); border-color: var(--admin-green); }
        .staff-verify-actions button:disabled { color: #94a3b8; background: #f1f5f9; border-color: #cbd5e1; cursor: not-allowed; }
        .staff-decision-panel { display: grid; gap: 14px; padding: 16px; background: #ffffff; border: 1px solid #dbe5f1; border-radius: 8px; box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05); }
        .staff-decision-panel.is-approved { border-color: #86efc2; background: #f6fffb; }
        .staff-decision-panel.is-pending { border-color: #fed7aa; background: #fffaf5; }
        .staff-decision-panel.is-rejected { border-color: #fecdd3; background: #fff7f8; }
        .staff-decision-copy span { display: block; color: #64748b; font-size: 11px; font-weight: 900; text-transform: uppercase; }
        .staff-decision-copy strong { display: block; margin-top: 6px; color: #071127; font-size: 16px; font-weight: 900; }
        .staff-decision-copy p { margin: 7px 0 0; color: #52627d; font-size: 13px; line-height: 1.5; }
        .staff-decision-actions { display: grid; gap: 10px; }
        .staff-decision-button-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .staff-decision-actions button { min-width: 190px; color: #ffffff; background: var(--admin-green); border: 1px solid var(--admin-green); }
        .staff-reject-form { display: grid; gap: 9px; }
        .staff-reject-form textarea { width: 100%; min-height: 78px; padding: 11px 12px; color: #071127; background: #ffffff; border: 1px solid #cbd8ea; border-radius: 8px; outline: none; resize: vertical; }
        .staff-decision-actions .staff-reject-button { background: #be123c; border-color: #be123c; }
        .admin-alert.is-warning { color: #9a3412; background: #fff7ed; border: 1px solid #fed7aa; }
        .staff-edit-panel { padding: 20px; }
        .staff-edit-panel h2 { margin: 0; font-size: 19px; font-weight: 900; }
        .staff-edit-panel p { margin: 7px 0 18px; color: #64748b; font-size: 13px; }
        .staff-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .staff-form-grid label { display: grid; gap: 7px; color: #64748b; font-size: 11px; font-weight: 900; text-transform: uppercase; }
        .staff-form-grid label.is-wide { grid-column: 1 / -1; }
        .staff-form-grid input, .staff-form-grid select { width: 100%; min-width: 0; height: 44px; padding: 0 12px; color: #071127; background: #ffffff; border: 1px solid #cbd8ea; border-radius: 8px; outline: none; }
        .staff-form-grid input[type=file] { height: auto; padding: 10px 12px; }
        .staff-save { margin-top: 16px; }
        .staff-danger-zone { display: grid; gap: 9px; margin-top: 18px; padding: 14px; background: #fff7f8; border: 1px solid #fecdd3; border-radius: 8px; }
        .staff-danger-zone strong { color: #9f1239; font-size: 14px; font-weight: 900; }
        .staff-danger-zone p { margin: 0; color: #9f1239; font-size: 12px; line-height: 1.5; }
        .staff-delete-button { display: inline-flex; width: fit-content; min-height: 42px; align-items: center; justify-content: center; padding: 0 14px; color: #ffffff; background: #be123c; border: 1px solid #be123c; border-radius: 8px; font-weight: 900; cursor: pointer; }
        @media (max-width: 980px) { .staff-detail-grid, .staff-form-grid { grid-template-columns: 1fr; } .staff-form-grid label.is-wide { grid-column: auto; } }
        @media (max-width: 620px) { .staff-decision-button-row { display: grid; grid-template-columns: 1fr; } .staff-decision-actions button { width: 100%; } }
    </style>
@endpush

@section('content')
    @php
        $idPhotoUrl = $staff->healthcare_worker_id_photo_url;
        $hasIdPhoto = $idPhotoUrl !== null;
        $statusClass = $staff->healthcare_worker_id_verified_at && $hasIdPhoto ? 'is-verified' : ($hasIdPhoto ? 'is-pending' : 'is-missing');
        $statusText = $staff->healthcare_worker_id_verified_at && $hasIdPhoto ? 'Verified' : ($hasIdPhoto ? 'Pending review' : 'Image unavailable');
        $approvalClass = match ($staff->approval_status) {
            'approved' => 'is-approved',
            'rejected' => 'is-rejected',
            default => 'is-pending',
        };
        $decisionTitle = match ($staff->approval_status) {
            'approved' => 'Account approved',
            'rejected' => 'Account rejected',
            default => 'Awaiting admin decision',
        };
        $decisionCopy = match ($staff->approval_status) {
            'approved' => 'This Program Staff account can log in. Resend the confirmation email only if the staff member did not receive it.',
            'rejected' => 'This Program Staff account cannot log in. You may approve the account later if the submitted information is corrected.',
            default => 'Review the staff information and ID photo before approving access to the Program Staff portal.',
        };
        $approveButtonText = $staff->approval_status === 'approved' ? 'Resend Approval Email' : 'Approve Account & Send Email';
        $rejectButtonText = $staff->approval_status === 'rejected' ? 'Resend Rejection Email' : 'Reject Account & Send Email';
    @endphp

    <header class="admin-topbar">
        <div>
            <p class="admin-kicker">Admin / Program Staff Detail</p>
            <h1 class="admin-page-title">{{ $staff->full_name }}</h1>
            <p class="admin-page-copy">{{ $staff->staff_id }} &middot; {{ $staff->role_label }} &middot; {{ $staff->contact_number ?: 'No contact number' }}</p>
        </div>
        <div class="staff-detail-actions">
            <a class="staff-back-link is-chat" href="{{ route('admin.staff-messages.index', ['staff' => $staff->id]) }}">{!! $staffAdminIcons['message'] !!} Chat Staff</a>
            <a class="staff-back-link" href="{{ route('admin.program-staff.index') }}">Back to Staff</a>
        </div>
    </header>

    @if (session('status'))
        <div class="admin-alert is-success">{{ session('status') }}</div>
    @endif

    @if (session('warning'))
        <div class="admin-alert is-warning">{{ session('warning') }}</div>
    @endif

    @if ($errors->any())
        <div class="admin-alert is-error">Please fix the highlighted fields.</div>
    @endif

    <section class="staff-detail-grid">
        <aside class="staff-id-panel" aria-label="Healthcare worker ID verification">
            <div class="staff-id-preview">
                @if($idPhotoUrl)
                    <img src="{{ $idPhotoUrl }}" alt="{{ $staff->full_name }} healthcare worker ID front image">
                @else
                    <div class="staff-id-placeholder">
                        {!! $staffAdminIcons['id-card'] !!}
                        <strong>ID image unavailable</strong>
                        <span>Upload a JPG, PNG, or WEBP front image from the edit form before verification.</span>
                    </div>
                @endif
            </div>

            <div class="staff-id-copy">
                <div class="staff-status-row">
                    <span class="staff-status {{ $approvalClass }}">{{ $staff->approval_status_label }}</span>
                    <span class="staff-status {{ $statusClass }}">ID {{ $statusText }}</span>
                </div>
                <dl>
                    <div><dt>Email</dt><dd>{{ $staff->email }}</dd></div>
                    <div><dt>Contact Number</dt><dd>{{ $staff->contact_number ?: 'Not provided' }}</dd></div>
                    <div><dt>Role</dt><dd>{{ $staff->role_label }}</dd></div>
                    <div><dt>Account Approved By</dt><dd>{{ $staff->approvedByAdmin?->username ?? 'Not approved' }}</dd></div>
                    <div><dt>Account Approved At</dt><dd>{{ $staff->approved_at?->format('M j, Y g:i A') ?? 'Not approved' }}</dd></div>
                    @if($staff->approval_status === 'rejected')
                        <div><dt>Rejected At</dt><dd>{{ $staff->rejected_at?->format('M j, Y g:i A') ?? 'Not recorded' }}</dd></div>
                        <div><dt>Rejection Reason</dt><dd>{{ $staff->rejection_reason ?: 'No reason provided' }}</dd></div>
                    @endif
                    <div><dt>Verified By</dt><dd>{{ $staff->verifiedByAdmin?->username ?? 'Not verified' }}</dd></div>
                    <div><dt>Verified At</dt><dd>{{ $staff->healthcare_worker_id_verified_at?->format('M j, Y g:i A') ?? 'Not verified' }}</dd></div>
                </dl>

                <div class="staff-verify-actions">
                    @if($staff->healthcare_worker_id_verified_at)
                        <form method="POST" action="{{ route('admin.program-staff.unverify', $staff) }}">
                            @csrf
                            @method('PATCH')
                            <button class="is-light" type="submit">Clear Verification</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.program-staff.verify', $staff) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" @disabled(! $hasIdPhoto)>Verify ID</button>
                        </form>
                    @endif
                </div>
                @error('healthcare_worker_id_photo')<span class="admin-field-error">{{ $message }}</span>@enderror

                <section class="staff-decision-panel {{ $approvalClass }}" aria-label="Program staff account approval">
                    <div class="staff-decision-copy">
                        <span>Account Decision</span>
                        <strong>{{ $decisionTitle }}</strong>
                        <p>{{ $decisionCopy }}</p>
                    </div>

                    <div class="staff-decision-actions">
                        <form id="staff-approve-form-{{ $staff->id }}" method="POST" action="{{ route('admin.program-staff.approve', $staff) }}">
                            @csrf
                            @method('PATCH')
                        </form>

                        <form id="staff-reject-form-{{ $staff->id }}" class="staff-reject-form" method="POST" action="{{ route('admin.program-staff.reject', $staff) }}">
                            @csrf
                            @method('PATCH')
                            <textarea name="rejection_reason" maxlength="500" placeholder="Optional reason for rejection">{{ old('rejection_reason', $staff->approval_status === 'rejected' ? $staff->rejection_reason : '') }}</textarea>
                            @error('rejection_reason')<span class="admin-field-error">{{ $message }}</span>@enderror
                        </form>

                        <div class="staff-decision-button-row">
                            <button type="submit" form="staff-approve-form-{{ $staff->id }}">{{ $approveButtonText }}</button>
                            <button class="staff-reject-button" type="submit" form="staff-reject-form-{{ $staff->id }}">{{ $rejectButtonText }}</button>
                        </div>
                    </div>
                </section>
            </div>
        </aside>

        <article class="staff-edit-panel">
            <h2>Edit Program Staff Information</h2>
            <p>Updates here sync to the Program Staff portal and admin records.</p>

            <form method="POST" action="{{ route('admin.program-staff.update', $staff) }}" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                <div class="staff-form-grid">
                    <label>
                        First Name
                        <input name="first_name" value="{{ old('first_name', $staff->first_name) }}" required>
                        @error('first_name')<span class="admin-field-error">{{ $message }}</span>@enderror
                    </label>
                    <label>
                        Middle Name
                        <input name="middle_name" value="{{ old('middle_name', $staff->middle_name) }}">
                        @error('middle_name')<span class="admin-field-error">{{ $message }}</span>@enderror
                    </label>
                    <label>
                        Last Name
                        <input name="last_name" value="{{ old('last_name', $staff->last_name) }}" required>
                        @error('last_name')<span class="admin-field-error">{{ $message }}</span>@enderror
                    </label>
                    <label>
                        Email
                        <input type="email" name="email" value="{{ old('email', $staff->email) }}" required>
                        @error('email')<span class="admin-field-error">{{ $message }}</span>@enderror
                    </label>
                    <label>
                        Healthcare Worker ID
                        <input name="staff_id" value="{{ old('staff_id', $staff->staff_id) }}" required>
                        @error('staff_id')<span class="admin-field-error">{{ $message }}</span>@enderror
                    </label>
                    <label>
                        Contact Number
                        <input name="contact_number" value="{{ old('contact_number', $staff->contact_number) }}" required>
                        @error('contact_number')<span class="admin-field-error">{{ $message }}</span>@enderror
                    </label>
                    <label class="is-wide">
                        Role
                        <select name="role" required>
                            <option value="" disabled @selected(!in_array(old('role', $staff->role_label), $roleOptions, true))>Select a role</option>
                            @foreach($roleOptions as $roleOption)
                                <option value="{{ $roleOption }}" @selected(old('role', $staff->role_label) === $roleOption)>{{ $roleOption }}</option>
                            @endforeach
                        </select>
                        @error('role')<span class="admin-field-error">{{ $message }}</span>@enderror
                    </label>
                    <label class="is-wide">
                        Healthcare Worker ID Photo
                        <input type="file" name="healthcare_worker_id_photo" accept="image/png,image/jpeg,image/webp">
                        @error('healthcare_worker_id_photo')<span class="admin-field-error">{{ $message }}</span>@enderror
                    </label>
                </div>

                <button class="staff-save" type="submit">Save Staff Information</button>
            </form>

            <section class="staff-danger-zone" aria-label="Delete Program Staff account">
                <strong>Delete Program Staff Account</strong>
                <p>This permanently removes the staff account from the admin console and Program Staff portal.</p>
                <form method="POST" action="{{ route('admin.program-staff.destroy', $staff) }}" onsubmit="return confirm('Delete this Program Staff account permanently?');">
                    @csrf
                    @method('DELETE')
                    <button class="staff-delete-button" type="submit">Delete Program Staff</button>
                </form>
            </section>
        </article>
    </section>
@endsection
