@extends('layouts.admin')

@section('title', 'Admin Messages - Project INAY')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/consultation.css') }}?v={{ filemtime(public_path('css/consultation.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/staff-coordination.css') }}?v={{ filemtime(public_path('css/staff-coordination.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/admin-staff-messages.css') }}?v={{ filemtime(public_path('css/admin-staff-messages.css')) }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/admin-staff-messages.js') }}?v={{ filemtime(public_path('js/admin-staff-messages.js')) }}" defer></script>
@endpush

@php
    $iconPhone = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.4 19.4 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.4 2.1L8.1 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.6 1.9Z"/></svg>';
    $iconSms = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/><path d="M8 8h8"/><path d="M8 12h5"/></svg>';
    $iconSearch = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>';
    $iconPlus = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg>';
    $iconSmile = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><path d="M9 9h.01"/><path d="M15 9h.01"/></svg>';
    $iconSend = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4 20-7Z"/><path d="M22 2 11 13"/></svg>';
    $iconBack = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>';
    $iconInfo = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>';
    $iconMessage = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></svg>';
@endphp

@section('content')
    <header class="admin-topbar">
        <div>
            <p class="admin-kicker">Admin / Program Staff Messages</p>
            <h1 class="admin-page-title">Admin Messages</h1>
            <p class="admin-page-copy">Chat directly with Program Staff for account, verification, and coordination follow-ups.</p>
        </div>
        <span class="admin-user-chip">
            {!! $iconMessage !!}
            {{ $adminUsername }}
        </span>
    </header>

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
        data-selected-name="Select Program Staff"
        data-selected-role="Program Staff"
        data-selected-initials="PS"
        data-thread-empty-title="No Program Staff yet."
        data-thread-empty-text="Program Staff accounts will appear here."
        data-message-empty-title="No admin messages yet."
        data-message-empty-text="Send the first admin note to this Program Staff account."
        data-preview-fallback="No admin message yet"
        data-send-empty-error="Type an admin message before sending."
    >
        <div class="admin-staff-message-note">
            {!! $iconInfo !!}
            <span>Admin Messages are limited to Admin and Program Staff accounts only.</span>
        </div>

        <div class="consultation-workspace">
            <aside class="consultation-sidebar" aria-label="Program Staff admin message threads">
                <div class="consultation-sidebar-head">
                    <strong>PROGRAM STAFF</strong>
                    <span data-total-unread>0</span>
                </div>
                <label class="consultation-search">
                    {!! $iconSearch !!}
                    <input type="search" placeholder="Search Program Staff" data-thread-search>
                </label>
                <div class="consultation-list" data-thread-list>
                    <div class="consultation-loading">Loading Program Staff...</div>
                </div>
            </aside>

            <section class="consultation-chat" aria-label="Selected Program Staff admin message thread">
                <header class="consultation-chat-head staff-coordination-head">
                    <button class="consultation-mobile-back" type="button" data-mobile-back aria-label="Back to Program Staff list">{!! $iconBack !!}</button>
                    <span class="consultation-avatar" data-selected-initials>PS</span>
                    <div class="consultation-selected-copy">
                        <h2 data-selected-name>Select Program Staff</h2>
                        <p><span data-selected-role>Program Staff</span> <b>&middot;</b> <span data-selected-status>Offline</span></p>
                    </div>
                    <div class="consultation-call-actions">
                        <button type="button" data-contact-action="tel" aria-label="Call contact" title="Call contact" disabled>{!! $iconPhone !!}</button>
                        <button type="button" data-contact-action="sms" aria-label="Send SMS" title="Send SMS" disabled>{!! $iconSms !!}</button>
                    </div>
                </header>

                <div class="consultation-messages" data-message-list>
                    <div class="consultation-empty">
                        <strong>Select Program Staff</strong>
                        <span>Admin messages will appear here.</span>
                    </div>
                </div>

                <div class="consultation-composer-wrap">
                    <div class="consultation-tools staff-coordination-actions">
                        <button type="button" data-quick-text="Please review your account information and update anything that has changed.">Request profile review</button>
                        <button type="button" data-quick-text="Please confirm receipt of this admin note when available.">Request confirmation</button>
                        <button type="button" data-quick-text="For follow-up: please coordinate with the admin office about this item.">Admin follow-up</button>
                    </div>

                    <form class="consultation-composer" data-message-form>
                        <button class="consultation-attach" type="button" disabled aria-label="Attachments are disabled">{!! $iconPlus !!}</button>
                        <div class="consultation-input-shell">
                            <textarea name="message" maxlength="1000" rows="1" placeholder="Type an admin message..." data-message-input></textarea>
                            <div class="consultation-input-meta">
                                <span>Admin to Program Staff only</span>
                                <span><span data-character-count>0</span>/1000</span>
                            </div>
                        </div>
                        <button class="consultation-emoji" type="button" data-emoji-button aria-label="Insert text smile">{!! $iconSmile !!}</button>
                        <button class="consultation-send" type="submit" data-send-button disabled aria-label="Send admin message">{!! $iconSend !!}</button>
                    </form>
                    <p class="consultation-error" data-error-message hidden></p>
                </div>
            </section>
        </div>
    </section>
@endsection
