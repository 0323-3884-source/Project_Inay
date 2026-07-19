@extends('layouts.admin')

@section('title', 'Program Staff Management - Project INAY')

@php
    $staffAdminIcons = [
        'stats' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 16V9"/><path d="M12 16V6"/><path d="M16 16v-4"/></svg>',
        'users' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/></svg>',
        'shield' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>',
        'alert' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>',
        'id-card' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2.4"/><path d="M6 16c.8-1.6 1.9-2.4 3-2.4s2.2.8 3 2.4"/><path d="M14 9h4"/><path d="M14 13h4"/><path d="M14 17h3"/></svg>',
        'message' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></svg>',
    ];
@endphp

@push('styles')
    <style>
        .staff-admin-tools { display: grid; grid-template-columns: minmax(220px, 1fr) repeat(3, minmax(150px, auto)) auto auto; gap: 12px; align-items: end; padding: 16px; margin-bottom: 18px; background: #ffffff; border: 1px solid var(--admin-line); border-radius: 8px; box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06); }
        .staff-admin-tools label { display: grid; gap: 7px; color: #64748b; font-size: 11px; font-weight: 900; text-transform: uppercase; }
        .staff-admin-tools input, .staff-admin-tools select { width: 100%; height: 44px; padding: 0 12px; color: #071127; background: #ffffff; border: 1px solid #cbd8ea; border-radius: 8px; outline: none; }
        .staff-admin-tools button, .staff-admin-tools a, .staff-action { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 44px; padding: 0 14px; border-radius: 8px; font-weight: 900; text-decoration: none; }
        .staff-admin-tools button { color: #ffffff; background: var(--admin-green); border: 1px solid var(--admin-green); cursor: pointer; }
        .staff-admin-tools a, .staff-action { color: #334155; background: #f8fafc; border: 1px solid #cbd8ea; }
        .staff-action-group { display: flex; justify-content: flex-end; gap: 8px; flex-wrap: wrap; }
        .staff-action.is-chat { color: #007f5f; background: #ecfdf5; border-color: #bbf7d0; }
        .staff-admin-list { display: grid; gap: 12px; }
        .staff-admin-row { display: grid; grid-template-columns: 72px minmax(190px, 1fr) minmax(150px, .8fr) minmax(140px, .8fr) 140px auto; gap: 14px; align-items: center; padding: 14px; background: #ffffff; border: 1px solid var(--admin-line); border-radius: 8px; box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05); }
        .staff-id-thumb { display: grid; width: 58px; height: 58px; place-items: center; color: #64748b; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; overflow: hidden; }
        .staff-id-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .staff-id-thumb svg { width: 22px; height: 22px; }
        .staff-primary strong { display: block; color: #071127; font-size: 15px; font-weight: 900; line-height: 1.25; }
        .staff-primary span, .staff-meta span { display: block; margin-top: 4px; color: #64748b; font-size: 12px; font-weight: 800; }
        .staff-meta strong { display: block; color: #17233b; font-size: 14px; font-weight: 900; line-height: 1.3; }
        .staff-status { display: inline-flex; align-items: center; justify-content: center; width: fit-content; min-height: 32px; padding: 0 10px; border-radius: 999px; font-size: 11px; font-weight: 900; text-transform: uppercase; }
        .staff-status.is-verified { color: #007f5f; background: #ecfdf5; border: 1px solid #86efc2; }
        .staff-status.is-approved { color: #007f5f; background: #ecfdf5; border: 1px solid #86efc2; }
        .staff-status.is-pending { color: #c2410c; background: #fff7ed; border: 1px solid #fed7aa; }
        .staff-status.is-rejected { color: #be123c; background: #fff1f2; border: 1px solid #fecdd3; }
        .staff-status.is-missing { color: #64748b; background: #f1f5f9; border: 1px solid #cbd5e1; }
        .staff-status-stack { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .staff-pagination { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-top: 16px; color: #64748b; font-size: 13px; font-weight: 900; }
        .staff-pagination a, .staff-pagination span { display: inline-flex; align-items: center; justify-content: center; min-height: 38px; padding: 0 12px; border-radius: 8px; text-decoration: none; }
        .staff-pagination a { color: #007f5f; background: #ecfdf5; border: 1px solid #c9f2df; }
        .staff-pagination span { background: #f8fafc; border: 1px solid #e2e8f0; }
        @media (max-width: 1180px) { .staff-admin-row { grid-template-columns: 64px minmax(0, 1fr) minmax(160px, .8fr); } .staff-admin-row > :nth-child(n+4) { grid-column: 2 / -1; } }
        @media (max-width: 760px) { .staff-admin-tools, .staff-admin-row { grid-template-columns: 1fr; } .staff-admin-row > * { grid-column: auto !important; } .staff-id-thumb { width: 100%; height: 160px; } .staff-admin-tools button, .staff-admin-tools a, .staff-action { width: 100%; } }
    </style>
@endpush

@section('content')
    <header class="admin-topbar">
        <div>
            <p class="admin-kicker">Admin / Program Staff</p>
            <h1 class="admin-page-title">Program Staff Management</h1>
            <p class="admin-page-copy">Review staff contact details, healthcare roles, and front ID verification status.</p>
        </div>
        <span class="admin-user-chip">
            {!! $staffAdminIcons['id-card'] !!}
            {{ $adminUsername }}
        </span>
    </header>

    @if (session('status'))
        <div class="admin-alert is-success">{{ session('status') }}</div>
    @endif

    <section class="admin-summary-grid" aria-label="Program staff identity summary">
        <article class="admin-summary-card"><span class="admin-summary-icon">{!! $staffAdminIcons['users'] !!}</span><div><span>Total Staff</span><strong>{{ number_format($stats['total']) }}</strong></div></article>
        <article class="admin-summary-card"><span class="admin-summary-icon">{!! $staffAdminIcons['alert'] !!}</span><div><span>Pending Accounts</span><strong>{{ number_format($stats['account_pending']) }}</strong></div></article>
        <article class="admin-summary-card"><span class="admin-summary-icon">{!! $staffAdminIcons['shield'] !!}</span><div><span>Approved Accounts</span><strong>{{ number_format($stats['account_approved']) }}</strong></div></article>
        <article class="admin-summary-card"><span class="admin-summary-icon">{!! $staffAdminIcons['alert'] !!}</span><div><span>Rejected Accounts</span><strong>{{ number_format($stats['account_rejected']) }}</strong></div></article>
        <article class="admin-summary-card"><span class="admin-summary-icon">{!! $staffAdminIcons['id-card'] !!}</span><div><span>Verified IDs</span><strong>{{ number_format($stats['verified']) }}</strong></div></article>
    </section>

    <form class="staff-admin-tools" method="GET" action="{{ route('admin.program-staff.index') }}">
        <label>
            Search staff
            <input type="search" name="q" value="{{ $search }}" placeholder="Name, email, staff ID, contact">
        </label>
        <label>
            Role
            <select name="role">
                <option value="">All roles</option>
                @foreach($roleOptions as $roleOption)
                    <option value="{{ $roleOption }}" @selected($selectedRole === $roleOption)>{{ $roleOption }}</option>
                @endforeach
            </select>
        </label>
        <label>
            ID status
            <select name="verification">
                <option value="all" @selected($verification === 'all')>All</option>
                <option value="verified" @selected($verification === 'verified')>Verified</option>
                <option value="pending" @selected($verification === 'pending')>Pending review</option>
                <option value="missing" @selected($verification === 'missing')>No ID uploaded</option>
            </select>
        </label>
        <label>
            Account status
            <select name="approval">
                <option value="all" @selected($approval === 'all')>All</option>
                <option value="pending" @selected($approval === 'pending')>Pending approval</option>
                <option value="approved" @selected($approval === 'approved')>Approved</option>
                <option value="rejected" @selected($approval === 'rejected')>Rejected</option>
            </select>
        </label>
        <button type="submit">{!! $staffAdminIcons['stats'] !!} Apply</button>
        <a href="{{ route('admin.program-staff.index') }}">Clear</a>
    </form>

    @if($staffMembers->isEmpty())
        <div class="admin-empty">No Program Staff records match this view.</div>
    @else
        <section class="staff-admin-list" aria-label="Program staff list">
            @foreach($staffMembers as $staff)
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
                @endphp
                <article class="staff-admin-row">
                    <span class="staff-id-thumb">
                        @if($idPhotoUrl)
                            <img src="{{ $idPhotoUrl }}" alt="{{ $staff->full_name }} healthcare worker ID">
                        @else
                            {!! $staffAdminIcons['id-card'] !!}
                        @endif
                    </span>
                    <div class="staff-primary">
                        <strong>{{ $staff->full_name }}</strong>
                        <span>{{ $staff->email }}</span>
                    </div>
                    <div class="staff-meta">
                        <strong>{{ $staff->staff_id }}</strong>
                        <span>Healthcare Worker ID</span>
                    </div>
                    <div class="staff-meta">
                        <strong>{{ $staff->role_label }}</strong>
                        <span>{{ $staff->contact_number ?: 'No contact number' }}</span>
                    </div>
                    <div class="staff-status-stack">
                        <span class="staff-status {{ $approvalClass }}">{{ $staff->approval_status_label }}</span>
                        <span class="staff-status {{ $statusClass }}">ID {{ $statusText }}</span>
                    </div>
                    <div class="staff-action-group">
                        <a class="staff-action is-chat" href="{{ route('admin.staff-messages.index', ['staff' => $staff->id]) }}">{!! $staffAdminIcons['message'] !!} Chat</a>
                        <a class="staff-action" href="{{ route('admin.program-staff.show', $staff) }}">View / Edit</a>
                    </div>
                </article>
            @endforeach
        </section>

        @if($staffMembers->hasPages())
            <nav class="staff-pagination" aria-label="Program staff pagination">
                @if($staffMembers->onFirstPage())
                    <span>Previous</span>
                @else
                    <a href="{{ $staffMembers->previousPageUrl() }}">Previous</a>
                @endif

                <span>Page {{ $staffMembers->currentPage() }} of {{ $staffMembers->lastPage() }}</span>

                @if($staffMembers->hasMorePages())
                    <a href="{{ $staffMembers->nextPageUrl() }}">Next</a>
                @else
                    <span>Next</span>
                @endif
            </nav>
        @endif
    @endif
@endsection
