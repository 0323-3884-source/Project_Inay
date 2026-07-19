@extends('layouts.app')

@section('title', 'Admin Messages - Project INAY')
@section('portal_title', 'Admin Messages')
@section('body_class', 'consultation-body staff-coordination-body')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/consultation.css') }}?v={{ filemtime(public_path('css/consultation.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/staff-coordination.css') }}?v={{ filemtime(public_path('css/staff-coordination.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/admin-staff-messages.css') }}?v={{ filemtime(public_path('css/admin-staff-messages.css')) }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/admin-staff-messages.js') }}?v={{ filemtime(public_path('js/admin-staff-messages.js')) }}" defer></script>
@endpush

@php
    $iconSearch = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>';
    $iconPlus = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg>';
    $iconSmile = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><path d="M9 9h.01"/><path d="M15 9h.01"/></svg>';
    $iconSend = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4 20-7Z"/><path d="M22 2 11 13"/></svg>';
    $iconBack = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>';
    $iconInfo = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>';
@endphp

@section('content')
    <section
        class="consultation-page admin-staff-message-page"
        data-admin-staff-messages-root
        data-initial-thread-id="{{ $selectedThreadId }}"
        data-threads-url="{{ route('admin-staff-messages.threads.index') }}"
        data-messages-url-template="{{ route('admin-staff-messages.threads.messages.index', ['thread' => '__THREAD__']) }}"
        data-send-url-template="{{ route('admin-staff-messages.threads.messages.store', ['thread' => '__THREAD__']) }}"
        data-read-url-template="{{ route('admin-staff-messages.threads.read', ['thread' => '__THREAD__']) }}"
        data-unsend-url-template="{{ route('admin-staff-messages.messages.unsend', ['message' => '__MESSAGE__']) }}"
        data-csrf="{{ csrf_token() }}"
        data-selected-name="Select Admin"
        data-selected-role="Admin"
        data-selected-initials="AD"
        data-thread-empty-title="No admin thread yet."
        data-thread-empty-text="Admin messages will appear here after an admin account is available."
        data-message-empty-title="No admin messages yet."
        data-message-empty-text="Send a reply or wait for an admin note."
        data-preview-fallback="No admin message yet"
        data-send-empty-error="Type a reply before sending."
    >
        <header class="consultation-page-heading consultation-page-heading--compact">
            <p>PROGRAM STAFF PORTAL</p>
            <span>Read and reply to direct admin messages without mixing mother consultations.</span>
        </header>

        <div class="admin-staff-message-note">
            {!! $iconInfo !!}
            <span>This workspace is only for direct messages between Admin and Program Staff.</span>
        </div>

        <div class="consultation-workspace staff-coordination-workspace">
            <aside class="consultation-sidebar" aria-label="Admin message threads">
                <div class="consultation-sidebar-head">
                    <strong>ADMIN</strong>
                    <span data-total-unread>0</span>
                </div>
                <label class="consultation-search">
                    {!! $iconSearch !!}
                    <input type="search" placeholder="Search admin" data-thread-search>
                </label>
                <div class="consultation-list" data-thread-list>
                    <div class="consultation-loading">Loading admin threads...</div>
                </div>
            </aside>

            <section class="consultation-chat" aria-label="Selected admin message thread">
                <header class="consultation-chat-head staff-coordination-head">
                    <button class="consultation-mobile-back" type="button" data-mobile-back aria-label="Back to admin list">{!! $iconBack !!}</button>
                    <span class="consultation-avatar" data-selected-initials>AD</span>
                    <div class="consultation-selected-copy">
                        <h2 data-selected-name>Select Admin</h2>
                        <p><span data-selected-role>Admin</span> <b>&middot;</b> <span data-selected-status>Offline</span></p>
                    </div>
                    <span class="admin-staff-message-chip">Admin only</span>
                </header>

                <div class="consultation-messages" data-message-list>
                    <div class="consultation-empty">
                        <strong>Select Admin</strong>
                        <span>Direct admin messages will appear here.</span>
                    </div>
                </div>

                <div class="consultation-composer-wrap">
                    <div class="consultation-tools staff-coordination-actions">
                        <button type="button" data-quick-text="Noted, admin. I will review this and follow up.">Confirm receipt</button>
                        <button type="button" data-quick-text="I have updated my staff information for review.">Profile updated</button>
                        <button type="button" data-quick-text="May I request clarification about this admin note?">Request clarification</button>
                    </div>

                    <form class="consultation-composer" data-message-form>
                        <button class="consultation-attach" type="button" disabled aria-label="Attachments are disabled">{!! $iconPlus !!}</button>
                        <div class="consultation-input-shell">
                            <textarea name="message" maxlength="1000" rows="1" placeholder="Type a reply to admin..." data-message-input></textarea>
                            <div class="consultation-input-meta">
                                <span>Program Staff to Admin only</span>
                                <span><span data-character-count>0</span>/1000</span>
                            </div>
                        </div>
                        <button class="consultation-emoji" type="button" data-emoji-button aria-label="Insert text smile">{!! $iconSmile !!}</button>
                        <button class="consultation-send" type="submit" data-send-button disabled aria-label="Send admin reply">{!! $iconSend !!}</button>
                    </form>
                    <p class="consultation-error" data-error-message hidden></p>
                </div>
            </section>
        </div>
    </section>
@endsection
