@extends('layouts.app')

@section('title', 'Program Staff Dashboard - Project INAY')

@section('content')
    <section class="card">
        <h1>Program Staff Dashboard</h1>
        <p>Welcome, {{ $staff->full_name }}.</p>

        <div class="details">
            <div class="detail-item">
                <span class="detail-label">Email</span>
                {{ $staff->email }}
            </div>
            <div class="detail-item">
                <span class="detail-label">Staff ID</span>
                {{ $staff->staff_id }}
            </div>
            <div class="detail-item">
                <span class="detail-label">Position</span>
                {{ $staff->position }}
            </div>
            <div class="detail-item">
                <span class="detail-label">Contact number</span>
                {{ $staff->contact_number }}
            </div>
        </div>

        <div class="actions">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Logout</button>
            </form>
        </div>
    </section>
@endsection
