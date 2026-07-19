@extends('layouts.app')

@section('title', 'Staff Coordination - Project INAY')
@section('portal_title', 'Staff Coordination')
@section('body_class', 'consultation-body staff-coordination-body')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/consultation.css') }}?v={{ filemtime(public_path('css/consultation.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/staff-coordination.css') }}?v={{ filemtime(public_path('css/staff-coordination.css')) }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/staff-coordination.js') }}?v={{ filemtime(public_path('js/staff-coordination.js')) }}" defer></script>
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
        class="consultation-page staff-coordination-page"
        data-staff-coordination-root
        data-initial-thread-id="{{ (int) request('thread') }}"
        data-threads-url="{{ route('staff-coordination.threads.index') }}"
        data-messages-url-template="{{ route('staff-coordination.threads.messages.index', ['thread' => '__THREAD__']) }}"
        data-send-url-template="{{ route('staff-coordination.threads.messages.store', ['thread' => '__THREAD__']) }}"
        data-read-url-template="{{ route('staff-coordination.threads.read', ['thread' => '__THREAD__']) }}"
        data-unsend-url-template="{{ route('staff-coordination.messages.unsend', ['message' => '__MESSAGE__']) }}"
        data-csrf="{{ csrf_token() }}"
    >
        <header class="consultation-page-heading consultation-page-heading--compact">
            <p>PROGRAM STAFF PORTAL</p>
            <span>Share internal updates with other Program Staff without mixing mother consultations.</span>
        </header>

        <div class="staff-coordination-note">
            {!! $iconInfo !!}
            <span>For coordination notes, endorsements, and internal follow-up information only.</span>
        </div>

        <div class="consultation-workspace staff-coordination-workspace">
            <aside class="consultation-sidebar" aria-label="Program Staff coordination threads">
                <div class="consultation-sidebar-head">
                    <strong>PROGRAM STAFF</strong>
                    <span data-total-unread>0</span>
                </div>
                <label class="consultation-search">
                    {!! $iconSearch !!}
                    <input type="search" placeholder="Search staff" data-thread-search>
                </label>
                <div class="consultation-list" data-thread-list>
                    <div class="consultation-loading">Loading staff threads...</div>
                </div>
            </aside>

            <section class="consultation-chat" aria-label="Selected staff coordination thread">
                <header class="consultation-chat-head staff-coordination-head">
                    <button class="consultation-mobile-back" type="button" data-mobile-back aria-label="Back to staff list">{!! $iconBack !!}</button>
                    <span class="consultation-avatar" data-selected-initials>PS</span>
                    <div class="consultation-selected-copy">
                        <h2 data-selected-name>Select Program Staff</h2>
                        <p><span data-selected-role>Program Staff</span> <b>&middot;</b> <span data-selected-status>Offline</span></p>
                    </div>
                    <span class="staff-coordination-chip">Info only</span>
                </header>

                <div class="consultation-messages" data-message-list>
                    <div class="consultation-empty">
                        <strong>Select Program Staff to coordinate.</strong>
                        <span>Internal staff messages will appear here.</span>
                    </div>
                </div>

                <div class="consultation-composer-wrap">
                    <div class="consultation-tools staff-coordination-actions">
                        <button type="button" data-quick-text="For endorsement: please review this case update when available.">Endorse case update</button>
                        <button type="button" data-quick-text="FYI: I added a follow-up note for monitoring continuity.">Share FYI note</button>
                        <button type="button" data-quick-text="Please confirm if you can cover this follow-up schedule.">Request coverage</button>
                    </div>

                    <form class="consultation-composer" data-message-form>
                        <button class="consultation-attach" type="button" disabled aria-label="Attachments are disabled">{!! $iconPlus !!}</button>
                        <div class="consultation-input-shell">
                            <textarea name="message" maxlength="1000" rows="1" placeholder="Type an internal staff update..." data-message-input></textarea>
                            <div class="consultation-input-meta">
                                <span>Internal staff coordination</span>
                                <span><span data-character-count>0</span>/1000</span>
                            </div>
                        </div>
                        <button class="consultation-emoji" type="button" data-emoji-button aria-label="Insert emoji">{!! $iconSmile !!}</button>
                        <button class="consultation-send" type="submit" data-send-button disabled aria-label="Send staff update">{!! $iconSend !!}</button>
                    </form>
                    <p class="consultation-error" data-error-message hidden></p>
                </div>
            </section>
        </div>
    </section>
@endsection
