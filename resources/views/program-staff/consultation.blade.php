@extends('layouts.app')

@section('title', 'Program Staff Consultation - Project INAY')
@section('portal_title', 'Program Staff Consultation')
@section('body_class', 'consultation-body')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/consultation.css') }}?v={{ filemtime(public_path('css/consultation.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/clinic-schedule.css') }}?v={{ filemtime(public_path('css/clinic-schedule.css')) }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/consultation.js') }}?v={{ filemtime(public_path('js/consultation.js')) }}" defer></script>
@endpush

@php
    $iconSearch = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>';
    $iconPhone = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.4 19.4 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.4 2.1L8.1 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.6 1.9Z"/></svg>';
    $iconVideo = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 10.5V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2v-4.5l7 4v-11l-7 4Z"/></svg>';
    $iconMore = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg>';
    $iconPlus = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg>';
    $iconSmile = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><path d="M9 9h.01"/><path d="M15 9h.01"/></svg>';
    $iconSend = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4 20-7Z"/><path d="M22 2 11 13"/></svg>';
    $iconBack = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>';
@endphp

@section('content')
    <section
        class="consultation-page consultation-page-staff"
        data-consultation-root
        data-current-role="program_staff"
        data-staff-tools="true"
        data-initial-conversation-id="{{ (int) request('conversation') }}"
        data-appointment-store-url="{{ route('staff.clinic-schedule.store') }}"
        data-conversations-url="{{ route('consultation.conversations.index') }}"
        data-messages-url-template="{{ route('consultation.conversations.messages.index', ['conversation' => '__CONVERSATION__']) }}"
        data-send-url-template="{{ route('consultation.conversations.messages.store', ['conversation' => '__CONVERSATION__']) }}"
        data-read-url-template="{{ route('consultation.conversations.read', ['conversation' => '__CONVERSATION__']) }}"
        data-unsend-url-template="{{ route('consultation.messages.unsend', ['message' => '__MESSAGE__']) }}"
        data-call-url-template="{{ route('consultation.conversations.calls.store', ['conversation' => '__CONVERSATION__']) }}"
        data-call-show-url-template="{{ route('consultation.calls.show', ['call' => '__CALL__']) }}"
        data-call-update-url-template="{{ route('consultation.calls.update', ['call' => '__CALL__']) }}"
        data-call-signal-url-template="{{ route('consultation.calls.signal', ['call' => '__CALL__']) }}"
        data-incoming-calls-url="{{ route('consultation.calls.incoming') }}"
        data-csrf="{{ csrf_token() }}"
    >
        <header class="consultation-page-heading consultation-page-heading--compact">
            <p>PROGRAM STAFF PORTAL</p>
            <span>Respond to assigned mothers in one secure chat workspace.</span>
        </header>

        <div class="consultation-workspace">
            <aside class="consultation-sidebar" aria-label="Mother conversations">
                <div class="consultation-sidebar-head">
                    <strong>MOTHERS</strong>
                    <span data-total-unread>0</span>
                </div>
                <label class="consultation-search">
                    {!! $iconSearch !!}
                    <input type="search" placeholder="Search conversation" data-conversation-search>
                </label>
                <div class="consultation-list" data-conversation-list>
                    <div class="consultation-loading">Loading conversations...</div>
                </div>
            </aside>

            <section class="consultation-chat" aria-label="Selected consultation">
                <header class="consultation-chat-head">
                    <button class="consultation-mobile-back" type="button" data-mobile-back aria-label="Back to conversations">{!! $iconBack !!}</button>
                    <span class="consultation-avatar" data-selected-initials>IN</span>
                    <div class="consultation-selected-copy">
                        <h2 data-selected-name>Select a mother</h2>
                        <p><span data-selected-role>Mother</span> <b>&middot;</b> <span data-selected-status>Offline</span></p>
                    </div>
                    <div class="consultation-call-actions">
                        <button type="button" data-call-type="voice" aria-label="Start voice call">{!! $iconPhone !!}</button>
                        <button type="button" data-call-type="video" aria-label="Start video call">{!! $iconVideo !!}</button>
                        <button type="button" aria-label="More options">{!! $iconMore !!}</button>
                    </div>
                </header>

                <div class="consultation-messages" data-message-list>
                    <div class="consultation-empty">
                        <strong>Select a mother to start consultation.</strong>
                        <span>Assigned conversations will appear here.</span>
                    </div>
                </div>

                <div class="consultation-composer-wrap">
                    <div class="consultation-tools" data-staff-tools-panel>
                        <button class="consultation-tools-toggle" type="button" data-quick-actions-toggle aria-expanded="true">Quick Actions</button>
                        <div class="consultation-quick-actions" data-quick-actions>
                            <button type="button" data-quick-text="Thank you for your update. I will review this and respond shortly.">Review shortly</button>
                            <button type="button" data-quick-text="Please monitor your symptoms and message me right away if anything changes.">Monitor symptoms</button>
                            <button type="button" data-schedule-checkup>Schedule checkup</button>
                            <select data-iec-select aria-label="IEC Material">
                                <option value="">IEC Material</option>
                                <option value="nutrition" data-message="IEC Material: Eat a balanced meal with iron-rich food, vegetables, fruits, and enough water each day.">Pregnancy nutrition</option>
                                <option value="warning-signs" data-message="IEC Material: Seek urgent care for heavy bleeding, severe headache, fever, blurred vision, chest pain, or reduced baby movement.">Warning signs</option>
                                <option value="newborn-care" data-message="IEC Material: Keep newborn care visits updated, continue breastfeeding support, and follow the vaccine schedule.">Newborn care</option>
                            </select>
                        </div>
                    </div>

                    <form class="consultation-composer" data-message-form>
                        <input type="hidden" name="message_type" value="text" data-message-type>
                        <input type="file" name="attachment" data-attachment-input accept=".jpg,.jpeg,.png,.gif,.webp,.mp4,.mov,.avi,.webm,.pdf,.doc,.docx,.xls,.xlsx,.txt,.csv" hidden>
                        <button class="consultation-attach" type="button" data-attachment-button aria-label="Attach file">{!! $iconPlus !!}</button>
                        <div class="consultation-input-shell">
                            <textarea name="message" maxlength="1000" rows="1" placeholder="Type a message..." data-message-input></textarea>
                            <div class="consultation-input-meta">
                                <span data-attachment-name></span>
                                <span><span data-character-count>0</span>/1000</span>
                            </div>
                        </div>
                        <button class="consultation-emoji" type="button" data-emoji-button aria-label="Insert emoji">{!! $iconSmile !!}</button>
                        <button class="consultation-send" type="submit" data-send-button disabled aria-label="Send message">{!! $iconSend !!}</button>
                    </form>
                    <div class="consultation-progress" data-upload-progress hidden><span></span></div>
                    <p class="consultation-error" data-error-message hidden></p>
                </div>
            </section>
        </div>

        <div class="consultation-call-popover" data-call-popover hidden></div>
    </section>
@endsection
