@extends('layouts.app')

@section('title', 'Project INAY')

@section('content')
    <section class="card narrow">
        <h1>Project INAY</h1>
        <p>Maternal and Child Health Monitoring System</p>
        <p>This Laravel-only rebuild uses separate accounts for mothers and program staff.</p>

        <div class="actions">
            <a class="button" href="{{ route('login') }}">Login</a>
            <a class="button secondary" href="{{ route('mother.register') }}">Register as Mother</a>
            <a class="button secondary" href="{{ route('staff.register') }}">Register as Program Staff</a>
        </div>
    </section>
@endsection
