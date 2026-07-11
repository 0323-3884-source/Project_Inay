@extends('layouts.app')

@section('title', 'Mother Dashboard - Project INAY')

@section('content')
    <section class="card">
        <h1>Mother Dashboard</h1>
        <p>Welcome, {{ $mother->full_name }}.</p>

        <div class="details">
            <div class="detail-item">
                <span class="detail-label">Email</span>
                {{ $mother->email }}
            </div>
            <div class="detail-item">
                <span class="detail-label">Barangay</span>
                {{ $mother->barangay }}
            </div>
            <div class="detail-item">
                <span class="detail-label">Contact number</span>
                {{ $mother->contact_number }}
            </div>
            <div class="detail-item">
                <span class="detail-label">4Ps beneficiary</span>
                {{ $mother->is_4ps_beneficiary ? 'Yes' : 'No' }}
            </div>
        </div>
    </section>
@endsection
