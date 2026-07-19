@extends('layouts.app')

@section('title', 'Program Staff Dashboard - Project INAY')

@push('styles')
    <style>
        .staff-dashboard-profile { display: grid; grid-template-columns: minmax(0, 1fr) 280px; gap: 18px; align-items: start; }
        .staff-id-card { display: grid; gap: 12px; padding: 14px; background: #f8fafc; border: 1px solid #dbe5f1; border-radius: 8px; }
        .staff-id-card h2 { margin: 0; font-size: 17px; font-weight: 900; }
        .staff-id-preview { display: grid; min-height: 170px; place-items: center; color: #64748b; background: #ffffff; border: 1px dashed #cbd5e1; border-radius: 8px; overflow: hidden; text-align: center; }
        .staff-id-preview img { width: 100%; height: 100%; object-fit: contain; }
        .staff-id-status { display: inline-flex; align-items: center; width: fit-content; min-height: 30px; padding: 0 10px; border-radius: 999px; font-size: 11px; font-weight: 900; text-transform: uppercase; }
        .staff-id-status.is-verified { color: #007f5f; background: #ecfdf5; border: 1px solid #86efc2; }
        .staff-id-status.is-pending { color: #c2410c; background: #fff7ed; border: 1px solid #fed7aa; }
        .staff-id-status.is-missing { color: #64748b; background: #f1f5f9; border: 1px solid #cbd5e1; }
        @media (max-width: 860px) { .staff-dashboard-profile { grid-template-columns: 1fr; } }
    </style>
@endpush

@section('content')
    <section class="card">
        <h1>Program Staff Dashboard</h1>
        <p>Welcome, {{ $staff->full_name }}.</p>

        @php
            $idPhotoUrl = $staff->healthcare_worker_id_photo_url;
            $idStatusClass = $staff->healthcare_worker_id_verified_at && $idPhotoUrl ? 'is-verified' : ($idPhotoUrl ? 'is-pending' : 'is-missing');
            $idStatusText = $staff->healthcare_worker_id_verified_at && $idPhotoUrl ? 'ID Verified' : ($idPhotoUrl ? 'Pending Admin Verification' : 'ID Image Unavailable');
        @endphp

        <div class="staff-dashboard-profile">
            <div class="details">
                <div class="detail-item">
                    <span class="detail-label">Email</span>
                    {{ $staff->email }}
                </div>
                <div class="detail-item">
                    <span class="detail-label">Healthcare Worker ID</span>
                    {{ $staff->staff_id }}
                </div>
                <div class="detail-item">
                    <span class="detail-label">Role</span>
                    {{ $staff->role_label }}
                </div>
                <div class="detail-item">
                    <span class="detail-label">Contact Number</span>
                    {{ $staff->contact_number }}
                </div>
            </div>

            <aside class="staff-id-card" aria-label="Healthcare worker ID status">
                <h2>Healthcare Worker ID</h2>
                <div class="staff-id-preview">
                    @if($idPhotoUrl)
                        <img src="{{ $idPhotoUrl }}" alt="{{ $staff->full_name }} healthcare worker ID">
                    @else
                        <span>ID image unavailable</span>
                    @endif
                </div>
                <span class="staff-id-status {{ $idStatusClass }}">{{ $idStatusText }}</span>
            </aside>
        </div>

        <div class="actions">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Logout</button>
            </form>
        </div>
    </section>
@endsection
