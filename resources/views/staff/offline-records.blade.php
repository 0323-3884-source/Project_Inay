@extends('layouts.app')
@section('title', 'Offline records')
@section('content')
<section id="offline-records" data-owner="{{ $owner }}" data-context="{{ url('/staff/offline-records/context') }}" data-sync="{{ url('/staff/offline-records/sync') }}" style="max-width:900px;margin:24px auto;padding:24px;background:white;border:1px solid #ddd;border-radius:12px">
    <h1>Offline records</h1>
    <p>Prepare this workspace online before a visit. Save maternal vitals and child growth entries offline, then keep this page open to synchronize when connected. Existing records are never overwritten.</p>
    <p>Choose a device passphrase of at least 12 characters. It encrypts your patient list and pending entries on this browser. You will need it again after closing the page; it cannot be recovered.</p>
    <label>Device passphrase <input type="password" id="offline-passphrase" minlength="12" autocomplete="off"></label>
    <button type="button" id="offline-unlock">Prepare / unlock</button>
    <button type="button" id="offline-lock">Lock</button>
    <p id="offline-status" role="status" aria-live="polite">Locked</p>
    <div id="offline-workspace" hidden>
        <button type="button" id="offline-refresh">Refresh patient list</button>
        <button type="button" id="offline-sync">Synchronize now</button>
        <form id="offline-entry" data-offline-entry>
            <p><label>Record type <select name="kind"><option value="maternal">Maternal vitals</option><option value="growth">Child growth</option></select></label></p>
            <p><label>Patient <select name="patient_id" required></select></label></p>
            <div id="offline-fields" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px"></div>
            <p><button type="submit">Save encrypted entry on this device</button></p>
        </form>
        <h2>Pending entries</h2>
        <p>Review entries here. For date conflicts, review the patient’s existing record online before discarding the pending entry.</p>
        <div id="offline-pending"></div>
    </div>
</section>
<script src="{{ asset('js/offline-records.js') }}?v={{ filemtime(public_path('js/offline-records.js')) }}" defer></script>
@endsection
