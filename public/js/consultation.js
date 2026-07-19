(() => {
    const root = document.querySelector('[data-consultation-root]');
    if (!root || root.dataset.consultationBooted === 'true') return;
    root.dataset.consultationBooted = 'true';

    const state = {
        conversations: [],
        messages: [],
        selectedId: Number(root.dataset.initialConversationId || 0) || null,
        sending: false,
        pollId: null,
        incomingPollId: null,
        incomingBusy: false,
        activeCall: null,
        pendingCallType: 'video',
        callPhase: 'idle',
        callStarting: false,
        answering: false,
        callActionLocked: false,
        callEnding: false,
        callPollId: null,
        callStatusText: '',
        callWindow: null,
        callWindowRoot: null,
        callWindowActiveSizeApplied: false,
        isCallModalOpen: false,
        renderedCallId: null,
        renderedCallPhase: 'idle',
        endedCallIds: new Set(),
        suppressCallWindowClose: false,
        localStream: null,
        remoteStream: null,
        peer: null,
        peerCallId: null,
        remoteTrackIds: new Set(),
        remoteOfferKey: null,
        remoteAnswerKey: null,
        pendingIceCandidates: [],
        seenIceCandidates: new Set(),
        failedDescriptionKeys: new Set(),
        facingMode: 'user',
        audioMuted: false,
        videoOff: false,
        durationTimer: null,
        drag: null,
        recorder: {
            open: false,
            stream: null,
            mediaRecorder: null,
            chunks: [],
            blob: null,
            previewUrl: '',
            mimeType: '',
            timerId: null,
            maxTimerId: null,
            startedAt: null,
            elapsedMs: 0,
            durationSeconds: 0,
            paused: false,
            uploading: false,
            error: '',
        },
    };

    const csrf = root.dataset.csrf || '';
    const currentRole = root.dataset.currentRole || 'mother';
    const hasStaffTools = root.dataset.staffTools === 'true';
    const list = root.querySelector('[data-conversation-list]');
    const searchInput = root.querySelector('[data-conversation-search]');
    const totalUnread = root.querySelector('[data-total-unread]');
    const messageList = root.querySelector('[data-message-list]');
    const selectedInitials = root.querySelector('[data-selected-initials]');
    const selectedName = root.querySelector('[data-selected-name]');
    const selectedRole = root.querySelector('[data-selected-role]');
    const selectedStatus = root.querySelector('[data-selected-status]');
    const form = root.querySelector('[data-message-form]');
    const textarea = root.querySelector('[data-message-input]');
    const messageType = root.querySelector('[data-message-type]');
    const attachmentInput = root.querySelector('[data-attachment-input]');
    const attachmentButton = root.querySelector('[data-attachment-button]');
    const attachmentName = root.querySelector('[data-attachment-name]');
    const characterCount = root.querySelector('[data-character-count]');
    const sendButton = root.querySelector('[data-send-button]');
    const emojiButton = root.querySelector('[data-emoji-button]');
    const progress = root.querySelector('[data-upload-progress]');
    const progressBar = progress ? progress.querySelector('span') : null;
    const errorMessage = root.querySelector('[data-error-message]');
    const quickActionsToggle = root.querySelector('[data-quick-actions-toggle]');
    const quickActions = root.querySelector('[data-quick-actions]');
    const iecSelect = root.querySelector('[data-iec-select]');
    const callPopover = root.querySelector('[data-call-popover]');
    const smsButton = root.querySelector('[data-sms-button]');
    const mobileBack = root.querySelector('[data-mobile-back]');

    const terminalCallStatuses = ['declined', 'cancelled', 'missed', 'expired', 'ended'];
    const activeCallStatuses = ['ringing', 'accepted'];
    const recorderLimitMs = 120000;

    const templateUrl = (template, value) => template.replace(/__CONVERSATION__|__MESSAGE__|__CALL__/g, value);

    const makeEl = (tag, className, text) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    };

    const friendlyError = (status, fallback) => {
        if (status === 401) return 'Your session expired. Please sign in again.';
        if (status === 403) return fallback || 'The selected conversation is invalid.';
        if (status === 409) return fallback || 'User is already in another call.';
        if (status === 413) return 'The attachment is too large.';
        if (status === 422) return fallback || 'Please review the message and try again.';
        return fallback || 'Message could not be sent. Please try again.';
    };

    const fetchJson = async (url, options = {}) => {
        const headers = {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(options.headers || {}),
        };

        if ((options.method || 'GET').toUpperCase() !== 'GET') {
            headers['X-CSRF-TOKEN'] = csrf;
        }

        if (options.body && !(options.body instanceof FormData) && !headers['Content-Type']) {
            headers['Content-Type'] = 'application/json';
        }

        const response = await fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers,
        });
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            const error = new Error(friendlyError(response.status, data.message));
            error.status = response.status;
            error.payload = data;
            throw error;
        }

        return data;
    };

    const selectedConversation = () => state.conversations.find((conversation) => Number(conversation.id) === Number(state.selectedId));

    const setError = (message = '') => {
        if (!errorMessage) return;
        errorMessage.textContent = message;
        errorMessage.hidden = message === '';
    };

    const setComposerEnabled = () => {
        const hasConversation = Boolean(state.selectedId);
        const value = textarea.value || '';
        const hasMessage = value.trim().length > 0;
        const hasAttachment = attachmentInput.files && attachmentInput.files.length > 0;

        characterCount.textContent = String(value.length);
        sendButton.disabled = !hasConversation || state.sending || (!hasMessage && !hasAttachment);
        sendButton.classList.toggle('is-loading', state.sending);
        textarea.disabled = !hasConversation || state.sending;
        attachmentButton.disabled = !hasConversation || state.sending;
        emojiButton.disabled = !hasConversation || state.sending;
    };

    const resizeTextarea = () => {
        textarea.style.height = 'auto';
        textarea.style.height = `${Math.min(textarea.scrollHeight, 130)}px`;
    };

    const insertMessage = (text, type = 'text') => {
        textarea.value = text;
        messageType.value = type;
        textarea.focus();
        setError('');
        resizeTextarea();
        setComposerEnabled();
    };

    const appendAtCursor = (text) => {
        const start = textarea.selectionStart || textarea.value.length;
        const end = textarea.selectionEnd || textarea.value.length;
        textarea.value = `${textarea.value.slice(0, start)}${text}${textarea.value.slice(end)}`;
        textarea.selectionStart = start + text.length;
        textarea.selectionEnd = start + text.length;
        textarea.focus();
        setError('');
        resizeTextarea();
        setComposerEnabled();
    };

    const scheduleTypeOptions = [
        ['prenatal_checkup', 'Prenatal Checkup'],
        ['postnatal_checkup', 'Postnatal Checkup'],
        ['child_checkup', 'Child Checkup'],
        ['vaccination', 'Vaccination'],
        ['follow_up_consultation', 'Follow-up Consultation'],
        ['video_consultation', 'Video Consultation'],
        ['other', 'Other'],
    ];

    const scheduleMeetingOptions = [
        ['in_person', 'In-person'],
        ['video_consultation', 'Video consultation'],
    ];

    const todayValue = () => {
        const today = new Date();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        return `${today.getFullYear()}-${month}-${day}`;
    };

    const ensureScheduleModal = () => {
        let modal = root.querySelector('[data-consultation-schedule-modal]');
        if (modal) return modal;

        modal = makeEl('div', 'clinic-modal');
        modal.hidden = true;
        modal.dataset.consultationScheduleModal = 'true';
        modal.dataset.clinicModal = 'true';

        const backdrop = makeEl('button', 'clinic-backdrop');
        backdrop.type = 'button';
        backdrop.dataset.scheduleClose = 'true';
        backdrop.setAttribute('aria-label', 'Close appointment form');

        const dialog = makeEl('section', 'clinic-dialog');
        dialog.setAttribute('role', 'dialog');
        dialog.setAttribute('aria-modal', 'true');
        dialog.setAttribute('aria-labelledby', 'consultation-schedule-title');

        const form = document.createElement('form');
        form.dataset.consultationScheduleForm = 'true';

        const header = makeEl('header', 'clinic-dialog-head');
        const title = makeEl('h2', null, 'Schedule Checkup');
        title.id = 'consultation-schedule-title';
        const close = makeEl('button', 'clinic-close', 'x');
        close.type = 'button';
        close.dataset.scheduleClose = 'true';
        close.setAttribute('aria-label', 'Close appointment form');
        header.append(title, close);

        const grid = makeEl('div', 'clinic-form-grid');
        const hiddenMother = document.createElement('input');
        hiddenMother.type = 'hidden';
        hiddenMother.name = 'mother_id';
        const hiddenConversation = document.createElement('input');
        hiddenConversation.type = 'hidden';
        hiddenConversation.name = 'conversation_id';
        const hiddenSource = document.createElement('input');
        hiddenSource.type = 'hidden';
        hiddenSource.name = 'from_consultation';
        hiddenSource.value = '1';

        const motherLabel = makeEl('label');
        motherLabel.textContent = 'Mother';
        const motherDisplay = document.createElement('input');
        motherDisplay.type = 'text';
        motherDisplay.readOnly = true;
        motherDisplay.dataset.scheduleMotherName = 'true';
        motherLabel.append(motherDisplay);

        const typeLabel = makeEl('label');
        typeLabel.textContent = 'Appointment type';
        const typeSelect = document.createElement('select');
        typeSelect.name = 'appointment_type';
        scheduleTypeOptions.forEach(([value, label]) => {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = label;
            typeSelect.append(option);
        });
        typeLabel.append(typeSelect);

        const dateLabel = makeEl('label');
        dateLabel.textContent = 'Date';
        const dateInput = document.createElement('input');
        dateInput.type = 'date';
        dateInput.name = 'appointment_date';
        dateInput.required = true;
        dateLabel.append(dateInput);

        const meetingLabel = makeEl('label');
        meetingLabel.textContent = 'Meeting type';
        const meetingSelect = document.createElement('select');
        meetingSelect.name = 'meeting_type';
        scheduleMeetingOptions.forEach(([value, label]) => {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = label;
            meetingSelect.append(option);
        });
        meetingLabel.append(meetingSelect);

        const startLabel = makeEl('label');
        startLabel.textContent = 'Start time';
        const startInput = document.createElement('input');
        startInput.type = 'time';
        startInput.name = 'start_time';
        startInput.required = true;
        startLabel.append(startInput);

        const endLabel = makeEl('label');
        endLabel.textContent = 'End time';
        const endInput = document.createElement('input');
        endInput.type = 'time';
        endInput.name = 'end_time';
        endInput.required = true;
        endLabel.append(endInput);

        const locationLabel = makeEl('label');
        locationLabel.textContent = 'Location';
        const locationInput = document.createElement('input');
        locationInput.type = 'text';
        locationInput.name = 'location';
        locationInput.maxLength = 255;
        locationInput.placeholder = 'Clinic room, health center, or video room';
        locationLabel.append(locationInput);

        const notesLabel = makeEl('label', 'clinic-wide');
        notesLabel.textContent = 'Notes';
        const notesInput = document.createElement('textarea');
        notesInput.name = 'notes';
        notesInput.maxLength = 2000;
        notesInput.placeholder = 'Add preparation notes or instructions';
        notesLabel.append(notesInput);

        grid.append(hiddenMother, hiddenConversation, hiddenSource, motherLabel, typeLabel, dateLabel, meetingLabel, startLabel, endLabel, locationLabel, notesLabel);

        const footer = makeEl('footer', 'clinic-dialog-footer');
        const cancel = makeEl('button', 'clinic-secondary', 'Cancel');
        cancel.type = 'button';
        cancel.dataset.scheduleClose = 'true';
        const submit = makeEl('button', 'clinic-primary', 'Save Appointment');
        submit.type = 'submit';
        footer.append(cancel, submit);

        form.append(header, grid, footer);
        dialog.append(form);
        modal.append(backdrop, dialog);
        root.append(modal);

        modal.addEventListener('click', (event) => {
            if (!event.target.closest('[data-schedule-close]')) return;
            modal.hidden = true;
            document.body.classList.remove('has-clinic-modal');
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!root.dataset.appointmentStoreUrl) return;

            const button = form.querySelector('button[type="submit"]');
            button.disabled = true;
            button.textContent = 'Saving...';

            try {
                const data = Object.fromEntries(new FormData(form).entries());
                const payload = await fetchJson(root.dataset.appointmentStoreUrl, {
                    method: 'POST',
                    body: JSON.stringify(data),
                });

                if (payload.message) {
                    applySentPayload({
                        message: payload.message,
                        conversation: payload.conversation,
                    });
                }

                modal.hidden = true;
                document.body.classList.remove('has-clinic-modal');
                setError('');
            } catch (error) {
                setError(error.message || 'Appointment could not be scheduled.');
            } finally {
                button.disabled = false;
                button.textContent = 'Save Appointment';
            }
        });

        return modal;
    };

    const openScheduleAppointmentModal = () => {
        const conversation = selectedConversation();

        if (!conversation || !conversation.mother?.id) {
            setError('Select a mother before scheduling a checkup.');
            return;
        }

        const modal = ensureScheduleModal();
        const form = modal.querySelector('[data-consultation-schedule-form]');
        form.reset();
        form.querySelector('[name="mother_id"]').value = conversation.mother.id;
        form.querySelector('[name="conversation_id"]').value = conversation.id;
        form.querySelector('[data-schedule-mother-name]').value = conversation.mother.name || conversation.participant?.name || 'Selected mother';
        form.querySelector('[name="appointment_date"]').min = todayValue();
        form.querySelector('[name="appointment_type"]').value = 'prenatal_checkup';
        modal.hidden = false;
        document.body.classList.add('has-clinic-modal');
        form.querySelector('[name="appointment_date"]').focus();
    };

    const renderConversations = () => {
        list.replaceChildren();

        if (state.conversations.length === 0) {
            const empty = makeEl('div', 'consultation-empty');
            const strong = makeEl('strong', null, hasStaffTools ? 'No assigned mothers yet.' : 'No assigned Program Staff yet.');
            const span = makeEl('span', null, hasStaffTools ? 'Add mothers to your casefiles to begin consultation.' : 'Your assigned staff will appear here.');
            empty.append(strong, span);
            list.append(empty);
            totalUnread.textContent = '0';
            renderHeader();
            renderMessages();
            setComposerEnabled();
            return;
        }

        const unreadTotal = state.conversations.reduce((sum, conversation) => sum + Number(conversation.unread_count || 0), 0);
        totalUnread.textContent = String(unreadTotal);

        state.conversations.forEach((conversation) => {
            const participant = conversation.participant || {};
            const button = makeEl('button', 'consultation-thread');
            button.type = 'button';
            button.dataset.conversationId = conversation.id;
            button.dataset.searchText = [
                participant.name,
                participant.role_label,
                conversation.latest_message,
                conversation.risk?.label,
            ].filter(Boolean).join(' ').toLowerCase();

            if (Number(conversation.id) === Number(state.selectedId)) {
                button.classList.add('is-active');
            }

            const avatar = makeEl('span', 'consultation-avatar', participant.initials || 'IN');
            const presence = makeEl('span', `consultation-presence ${participant.online ? 'is-online' : ''}`);
            avatar.append(presence);

            const main = makeEl('span', 'consultation-thread-main');
            const top = makeEl('span', 'consultation-thread-top');
            top.append(makeEl('span', 'consultation-thread-name', participant.name || 'Consultation'));

            const meta = makeEl('span', 'consultation-thread-meta');
            if (currentRole === 'program_staff') {
                meta.append(makeEl('span', `consultation-risk is-${conversation.risk?.level || 'pending'}`, conversation.risk?.label || 'Pending'));
            }
            meta.append(
                makeEl('span', 'consultation-thread-role', participant.role_label || ''),
                makeEl('span', null, '-'),
                makeEl('span', 'consultation-thread-status', participant.status_text || 'Offline')
            );

            const preview = makeEl('span', 'consultation-thread-preview', conversation.latest_message || 'No messages yet');
            main.append(top, meta, preview);

            const side = makeEl('span', 'consultation-thread-side');
            const time = makeEl('span', 'consultation-thread-time', conversation.latest_message_time || '');
            const unreadCount = Number(conversation.unread_count || 0);
            const unread = makeEl('span', `consultation-unread ${unreadCount === 0 ? 'is-zero' : ''}`, String(unreadCount));
            side.append(time, unread);

            button.append(avatar, main, side);
            list.append(button);
        });

        filterConversations();
    };

    const renderHeader = () => {
        const conversation = selectedConversation();
        const participant = conversation?.participant || {};

        selectedInitials.textContent = participant.initials || 'IN';
        selectedName.textContent = participant.name || (hasStaffTools ? 'Select a mother' : 'Select Program Staff');
        selectedRole.textContent = participant.role_label || (hasStaffTools ? 'Mother' : 'Program Staff');
        selectedStatus.textContent = participant.status_text || 'Offline';

        root.querySelectorAll('[data-call-type]').forEach((button) => {
            button.disabled = !conversation || state.callPhase !== 'idle' || state.callActionLocked;
        });

        if (smsButton) {
            const canSms = Boolean(conversation && participant.sms_url);
            smsButton.disabled = !canSms;
            smsButton.title = canSms ? `Send SMS to ${participant.name || 'participant'}` : 'SMS is unavailable for this contact';
            smsButton.setAttribute('aria-label', canSms ? `Send SMS to ${participant.name || 'participant'}` : 'SMS unavailable');
        }
    };

    const isNearBottom = () => messageList.scrollHeight - messageList.scrollTop - messageList.clientHeight < 120;

    const renderMessages = () => {
        const conversation = selectedConversation();
        const shouldScroll = isNearBottom();
        messageList.replaceChildren();
        renderHeader();

        if (!conversation) {
            const empty = makeEl('div', 'consultation-empty');
            empty.append(
                makeEl('strong', null, hasStaffTools ? 'Select a mother to start consultation.' : 'Select Program Staff to start consultation.'),
                makeEl('span', null, 'Messages will appear here.')
            );
            messageList.append(empty);
            setComposerEnabled();
            return;
        }

        if (state.messages.length === 0) {
            const empty = makeEl('div', 'consultation-empty');
            empty.append(makeEl('strong', null, 'No messages yet.'), makeEl('span', null, 'Send the first secure consultation message.'));
            messageList.append(empty);
            setComposerEnabled();
            return;
        }

        state.messages.forEach((message) => {
            const item = makeEl('article', `consultation-message ${message.is_own ? 'is-own' : 'is-other'} ${message.is_unsent ? 'is-unsent' : ''}`);

            if (!message.is_own) {
                item.append(makeEl('span', 'consultation-message-sender', message.sender_name || message.sender_role_label));
            }

            const bubble = makeEl('div', 'consultation-bubble');

            if (message.message) {
                bubble.append(document.createTextNode(message.message));
            }

            if (message.attachment_url) {
                const attachment = makeEl('div', 'consultation-attachment');

                if (message.message_type === 'image') {
                    const image = document.createElement('img');
                    image.src = message.attachment_url;
                    image.alt = message.attachment_name || 'Attached image';
                    attachment.append(image);
                } else if (message.message_type === 'video') {
                    const video = document.createElement('video');
                    video.controls = true;
                    video.playsInline = true;
                    video.preload = 'metadata';
                    video.src = message.attachment_url;
                    attachment.append(video);
                } else {
                    const link = document.createElement('a');
                    link.href = message.attachment_url;
                    link.target = '_blank';
                    link.rel = 'noopener';
                    link.textContent = message.attachment_name || 'Open attachment';
                    attachment.append(link);
                }

                bubble.append(attachment);
            }

            if (!message.message && !message.attachment_url) {
                bubble.textContent = message.is_unsent ? 'This message was unsent.' : '';
            }

            const meta = makeEl('div', 'consultation-message-meta');
            meta.append(makeEl('span', null, message.created_time || ''));

            if (message.is_own && !message.is_unsent) {
                meta.append(makeEl('span', null, message.is_read ? 'Read' : 'Sent'));
            }

            if (message.can_unsend) {
                const unsend = makeEl('button', 'consultation-unsend', 'Unsend');
                unsend.type = 'button';
                unsend.dataset.unsendMessage = message.id;
                meta.append(unsend);
            }

            item.append(bubble, meta);
            messageList.append(item);
        });

        if (shouldScroll) {
            messageList.scrollTo({ top: messageList.scrollHeight, behavior: 'smooth' });
        }

        setComposerEnabled();
    };

    const filterConversations = () => {
        const term = (searchInput.value || '').trim().toLowerCase();
        list.querySelectorAll('[data-conversation-id]').forEach((button) => {
            button.hidden = term !== '' && !button.dataset.searchText.includes(term);
        });
    };

    const updateConversationInState = (conversation) => {
        if (!conversation) return;
        const index = state.conversations.findIndex((item) => Number(item.id) === Number(conversation.id));
        if (index >= 0) {
            state.conversations[index] = conversation;
            state.conversations.sort((a, b) => new Date(b.updated_at || 0) - new Date(a.updated_at || 0));
        } else {
            state.conversations.unshift(conversation);
        }
    };

    const applySentPayload = (payload) => {
        if (payload.conversation) {
            updateConversationInState(payload.conversation);
        }

        if (payload.message && Number(payload.message.conversation_id) === Number(state.selectedId)) {
            const exists = state.messages.some((message) => Number(message.id) === Number(payload.message.id));
            if (!exists) state.messages.push(payload.message);
        }

        renderConversations();
        renderMessages();
        window.setTimeout(() => messageList.scrollTo({ top: messageList.scrollHeight, behavior: 'smooth' }), 30);
    };

    const loadConversations = async () => {
        const url = state.selectedId
            ? `${root.dataset.conversationsUrl}?selected=${encodeURIComponent(state.selectedId)}`
            : root.dataset.conversationsUrl;
        const data = await fetchJson(url);
        const seen = new Set();
        state.conversations = (data.conversations || []).filter((conversation) => {
            if (seen.has(Number(conversation.id))) return false;
            seen.add(Number(conversation.id));
            return true;
        });

        const selectedStillExists = state.conversations.some((conversation) => Number(conversation.id) === Number(state.selectedId));
        if (!selectedStillExists) {
            state.selectedId = data.selected_conversation_id || state.conversations[0]?.id || null;
        }

        renderConversations();
    };

    const loadMessages = async () => {
        if (!state.selectedId) {
            state.messages = [];
            renderMessages();
            return;
        }

        const data = await fetchJson(templateUrl(root.dataset.messagesUrlTemplate, state.selectedId));
        state.messages = data.messages || [];
        updateConversationInState(data.conversation);
        renderConversations();
        renderMessages();
    };

    const selectConversation = async (id, fromClick = false) => {
        state.selectedId = Number(id);
        if (fromClick) root.classList.add('is-chat-open');
        state.messages = [];
        renderConversations();
        renderMessages();
        setError('');
        hideAttachmentMenu();
        await loadMessages().catch((error) => setError(error.message));
    };

    const resetComposer = () => {
        form.reset();
        messageType.value = 'text';
        attachmentName.textContent = '';
        textarea.style.height = 'auto';
        if (progress) progress.hidden = true;
        if (progressBar) progressBar.style.width = '0%';
        setComposerEnabled();
    };

    const submitMessageData = (data) => new Promise((resolve, reject) => {
        if (state.sending || !state.selectedId) {
            reject(new Error('The selected conversation is invalid.'));
            return;
        }

        state.sending = true;
        setError('');
        setComposerEnabled();

        const xhr = new XMLHttpRequest();
        xhr.open('POST', templateUrl(root.dataset.sendUrlTemplate, state.selectedId));
        xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        if (progress && progressBar) {
            progress.hidden = false;
            progressBar.style.width = '0%';
            xhr.upload.onprogress = (event) => {
                if (!event.lengthComputable) return;
                progressBar.style.width = `${Math.round((event.loaded / event.total) * 100)}%`;
            };
        }

        xhr.onload = () => {
            state.sending = false;
            setComposerEnabled();
            if (progress) progress.hidden = true;

            let payload = {};
            try {
                payload = JSON.parse(xhr.responseText || '{}');
            } catch (error) {
                payload = {};
            }

            if (xhr.status >= 200 && xhr.status < 300) {
                resolve(payload);
                return;
            }

            reject(new Error(friendlyError(xhr.status, payload.message)));
        };

        xhr.onerror = () => {
            state.sending = false;
            setComposerEnabled();
            if (progress) progress.hidden = true;
            reject(new Error('Message could not be sent. Please try again.'));
        };

        xhr.send(data);
    });

    const sendMessage = async () => {
        if (state.sending || !state.selectedId) return;

        const message = textarea.value.trim();
        const hasAttachment = attachmentInput.files && attachmentInput.files.length > 0;

        if (!message && !hasAttachment) {
            setError('Type a message or attach a file before sending.');
            return;
        }

        const data = new FormData(form);
        data.set('message', message);

        try {
            const payload = await submitMessageData(data);
            resetComposer();
            applySentPayload(payload);
        } catch (error) {
            setError(error.message);
            setComposerEnabled();
        }
    };

    const unsendMessage = async (messageId) => {
        setError('');
        try {
            const data = await fetchJson(templateUrl(root.dataset.unsendUrlTemplate, messageId), {
                method: 'POST',
                body: JSON.stringify({}),
            });
            if (data.message) {
                const index = state.messages.findIndex((message) => Number(message.id) === Number(data.message.id));
                if (index >= 0) state.messages[index] = data.message;
            }
            updateConversationInState(data.conversation);
            renderConversations();
            renderMessages();
        } catch (error) {
            setError(error.message);
        }
    };

    let attachmentMenu = null;

    const ensureAttachmentMenu = () => {
        if (attachmentMenu) return attachmentMenu;
        attachmentMenu = makeEl('div', 'consultation-attachment-menu');
        attachmentMenu.hidden = true;

        [
            ['Upload Image', 'image', 'image/*'],
            ['Upload Video', 'video', 'video/mp4,video/webm,video/quicktime,video/x-msvideo'],
            ['Record Video Message', 'record', ''],
            ['Upload File', 'file', '.pdf,.doc,.docx,.xls,.xlsx,.txt,.csv'],
        ].forEach(([label, type, accept]) => {
            const button = makeEl('button', null, label);
            button.type = 'button';
            button.dataset.attachmentChoice = type;
            if (accept) button.dataset.accept = accept;
            attachmentMenu.append(button);
        });

        root.querySelector('.consultation-composer-wrap')?.append(attachmentMenu);
        return attachmentMenu;
    };

    const hideAttachmentMenu = () => {
        if (attachmentMenu) attachmentMenu.hidden = true;
    };

    const toggleAttachmentMenu = () => {
        if (!state.selectedId) {
            setError('The selected conversation is invalid.');
            return;
        }

        const menu = ensureAttachmentMenu();
        menu.hidden = !menu.hidden;
    };

    const chooseFileAttachment = (accept) => {
        hideAttachmentMenu();
        attachmentInput.value = '';
        attachmentInput.accept = accept || '.jpg,.jpeg,.png,.gif,.webp,.mp4,.mov,.avi,.webm,.pdf,.doc,.docx,.xls,.xlsx,.txt,.csv';
        attachmentInput.click();
    };

    const participantForCall = () => {
        if (!state.activeCall) return selectedConversation()?.participant || {};
        return state.activeCall.is_receiver ? state.activeCall.caller : state.activeCall.receiver;
    };

    const callTitle = () => {
        if (state.callPhase === 'incoming-ringing') return state.pendingCallType === 'video' ? 'Incoming video call' : 'Incoming voice call';
        if (state.callPhase === 'active') return 'Video call';
        return state.pendingCallType === 'video' ? 'Video call' : 'Voice call';
    };

    const callStatus = () => {
        if (state.callStatusText) return state.callStatusText;
        if (state.callPhase === 'outgoing-preview') return 'Ready to start';
        if (state.callPhase === 'outgoing-ringing') return 'Calling...';
        if (state.callPhase === 'incoming-ringing') return 'Incoming video call';
        if (state.callPhase === 'active') return 'Connected';
        return '';
    };

    const appendAvatar = (parent, participant) => {
        if (participant?.avatar_url) {
            const img = document.createElement('img');
            img.src = participant.avatar_url;
            img.alt = participant.name || 'Participant';
            parent.append(img);
            return;
        }

        parent.textContent = participant?.initials || 'IN';
    };

    const logCallError = (label, details = {}) => {
        console.error(`[Project INAY video call] ${label}`, details);
    };

    const publicCallError = () => 'Video call could not connect. Please try again.';

    const friendlyCallError = (error) => {
        if (error?.status === 409) return 'User is already in another call.';
        if (error?.status === 401) return 'Your session expired. Please sign in again.';
        if (error?.status === 422) return 'This call is no longer available.';
        if (error?.message === 'Camera or microphone permission was denied.') return error.message;
        if (error?.message === 'Camera or microphone is not available in this browser.') return error.message;
        return publicCallError();
    };

    const descriptionKey = (description) => {
        if (!description || typeof description.sdp !== 'string') return '';
        return `${description.type || ''}:${description.sdp.length}:${description.sdp.slice(0, 48)}:${description.sdp.slice(-48)}`;
    };

    const validDescription = (description, expectedType) => {
        if (!description || typeof description !== 'object') return false;
        if (description.type !== expectedType) return false;
        return typeof description.sdp === 'string' && description.sdp.trim().length > 0;
    };

    const rtcDescription = (description, expectedType) => {
        if (!validDescription(description, expectedType)) {
            logCallError('Invalid remote description payload', { expectedType, description });
            return null;
        }

        return new RTCSessionDescription({
            type: description.type,
            sdp: description.sdp,
        });
    };

    const candidateKey = (candidate) => {
        if (!candidate) return '';
        return [
            candidate.candidate || '',
            candidate.sdpMid ?? '',
            candidate.sdpMLineIndex ?? '',
        ].join('|');
    };

    const candidateForCurrentCall = (entry) => {
        if (!entry || entry.is_own) return false;
        if (entry.call_id && state.activeCall && Number(entry.call_id) !== Number(state.activeCall.id)) return false;
        return Boolean(entry.candidate?.candidate);
    };

    const currentCallId = () => state.activeCall?.id ? Number(state.activeCall.id) : null;

    const rememberEndedCall = (callId) => {
        if (!callId) return;
        state.endedCallIds.add(Number(callId));

        if (state.endedCallIds.size > 40) {
            state.endedCallIds.delete(state.endedCallIds.values().next().value);
        }
    };

    const callWasLocallyEnded = (callId) => callId && state.endedCallIds.has(Number(callId));

    const normalizeCallWindow = () => {
        if (state.callWindow?.closed) {
            state.callWindow = null;
            state.callWindowRoot = null;
            state.callWindowActiveSizeApplied = false;
        }
    };

    const allCallContainers = () => {
        normalizeCallWindow();
        return [callPopover, state.callWindowRoot].filter(Boolean);
    };

    const activeCallContainer = () => {
        normalizeCallWindow();

        if (state.callPhase === 'incoming-ringing') {
            return callPopover;
        }

        if (state.callWindowRoot) {
            return state.callWindowRoot;
        }

        return null;
    };

    const callContainers = () => {
        const container = activeCallContainer();
        return container ? [container] : [];
    };

    const clearVideoElements = (container) => {
        container?.querySelectorAll('video').forEach((video) => {
            video.pause();
            video.srcObject = null;
        });
    };

    const hideCallPopover = () => {
        if (!callPopover) return;
        clearVideoElements(callPopover);
        callPopover.replaceChildren();
        callPopover.className = 'consultation-call-popover';
        callPopover.hidden = true;
    };

    const clearInactiveCallSurface = (activeContainer) => {
        allCallContainers().forEach((container) => {
            if (!container || container === activeContainer) return;
            clearVideoElements(container);
            container.replaceChildren();
            if (container === callPopover) {
                callPopover.className = 'consultation-call-popover';
                callPopover.hidden = true;
            }
        });
    };

    const fitCallWindowToPhase = () => {
        if (!state.callWindow || state.callWindow.closed) return;

        if (state.callPhase !== 'active') {
            state.callWindowActiveSizeApplied = false;
            return;
        }

        if (state.callWindowActiveSizeApplied) return;

        try {
            state.callWindow.resizeTo(980, 720);
            state.callWindow.moveTo(120, 80);
        } catch (error) {
            // Some browsers ignore popup resizing; the CSS still switches to the full call layout.
        }

        state.callWindowActiveSizeApplied = true;
    };

    const closeCallWindowOnly = () => {
        state.suppressCallWindowClose = true;

        if (state.callWindow && !state.callWindow.closed) {
            state.callWindow.close();
        }

        state.callWindow = null;
        state.callWindowRoot = null;
        state.callWindowActiveSizeApplied = false;
        window.setTimeout(() => {
            state.suppressCallWindowClose = false;
        }, 0);
    };

    const ensureCallWindow = () => {
        if (state.callWindow && !state.callWindow.closed && state.callWindowRoot) {
            state.callWindow.focus();
            return true;
        }

        const popup = window.open('', 'project-inay-video-call', 'popup=yes,width=460,height=560,left=120,top=80');

        if (!popup) {
            setError('Please allow popups for this site to open the video call window.');
            return false;
        }

        const cssHref = document.querySelector('link[href*="consultation.css"]')?.href || '/css/consultation.css';

        popup.document.open();
        popup.document.write(`<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Project INAY Video Call</title>
    <link rel="stylesheet" href="${cssHref}">
    <style>
        html, body { width: 100%; height: 100%; margin: 0; overflow: hidden; background: #020617; }
        body { font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .consultation-call-window-root { display: grid; width: 100%; height: 100%; place-items: center; background: #020617; }
        .consultation-call-window-root .consultation-call-dialog { width: min(420px, calc(100vw - 24px)); max-height: calc(100vh - 24px); grid-template-rows: auto minmax(0, 1fr) auto; }
        .consultation-call-window-root .consultation-call-dialog.is-active { width: 100%; height: 100%; max-height: none; border: 0; border-radius: 0; box-shadow: none; }
        .consultation-call-window-root .consultation-video-preview,
        .consultation-call-window-root .consultation-call-stage { height: auto; min-height: 0; }
    </style>
</head>
<body>
    <main class="consultation-call-window-root" data-call-window-root></main>
</body>
</html>`);
        popup.document.close();

        state.callWindow = popup;
        state.callWindowRoot = popup.document.querySelector('[data-call-window-root]');

        popup.document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-call-command]');
            if (!button) return;
            handleCallCommand(button.dataset.callCommand);
        });
        popup.document.addEventListener('pointerdown', handleLocalTilePointerDown);
        popup.document.addEventListener('pointermove', handleLocalTilePointerMove);
        popup.document.addEventListener('pointerup', handleLocalTilePointerUp);
        popup.addEventListener('beforeunload', () => {
            if (state.suppressCallWindowClose || state.callPhase === 'idle') return;

            const action = state.activeCall?.status === 'accepted'
                ? 'end'
                : (state.activeCall?.is_receiver ? 'decline' : 'cancel');

            const closingCallId = state.activeCall?.id;
            rememberEndedCall(closingCallId);
            if (state.activeCall && activeCallStatuses.includes(state.activeCall.status)) {
                fetchJson(templateUrl(root.dataset.callUpdateUrlTemplate, closingCallId), {
                    method: 'PATCH',
                    body: JSON.stringify({ action }),
                }).catch(() => {});
            }

            stopCallPolling();
            cleanupCallMedia();
            state.activeCall = null;
            state.callPhase = 'idle';
            state.callStarting = false;
            state.isCallModalOpen = false;
            state.renderedCallId = null;
            state.renderedCallPhase = 'idle';
            hideCallPopover();
            renderHeader();
        });

        popup.focus();
        return true;
    };

    const setVideoStream = (video, stream) => {
        if (video.srcObject !== stream) video.srcObject = stream || null;
    };

    const syncLocalVideoElements = () => {
        callContainers().forEach((container) => {
            container.querySelectorAll('[data-local-video]').forEach((video) => {
                setVideoStream(video, state.localStream);
            });
        });
    };

    const syncRemoteVideoElements = () => {
        const remoteStream = state.remoteStream && state.remoteStream !== state.localStream
            ? state.remoteStream
            : null;

        callContainers().forEach((container) => {
            container.querySelectorAll('[data-remote-video]').forEach((video) => {
                setVideoStream(video, remoteStream);
            });
        });
    };

    const syncVideoElements = () => {
        syncLocalVideoElements();
        syncRemoteVideoElements();
    };

    const renderCallModal = () => {
        if (!callPopover) return;

        if (state.callPhase === 'idle') {
            state.isCallModalOpen = false;
            state.renderedCallId = null;
            state.renderedCallPhase = 'idle';
            hideCallPopover();
            closeCallWindowOnly();
            renderHeader();
            return;
        }

        const callId = currentCallId();
        if (callId && callWasLocallyEnded(callId)) {
            closeCallModal();
            return;
        }

        if (state.callPhase !== 'outgoing-preview' && !state.activeCall) {
            closeCallModal();
            return;
        }

        const useWindow = state.callPhase !== 'incoming-ringing' && Boolean(activeCallContainer());
        const container = useWindow ? state.callWindowRoot : (state.callPhase === 'incoming-ringing' ? callPopover : null);

        if (!container) {
            logCallError('Call modal render skipped because no active call surface is available', {
                call: callId,
                phase: state.callPhase,
            });
            hideCallPopover();
            renderHeader();
            return;
        }

        const participant = participantForCall();
        clearInactiveCallSurface(container);
        container.replaceChildren();
        state.isCallModalOpen = true;
        state.renderedCallId = callId;
        state.renderedCallPhase = state.callPhase;

        if (useWindow) {
            hideCallPopover();
            fitCallWindowToPhase();
            state.callWindow.document.title = `${callTitle()} - ${participant?.name || 'Project INAY'}`;
        } else {
            callPopover.className = `consultation-call-popover is-${state.callPhase}`;
            callPopover.hidden = false;
        }

        const backdrop = makeEl('button', 'consultation-call-backdrop');
        backdrop.type = 'button';
        backdrop.dataset.callCommand = state.callPhase === 'outgoing-preview' ? 'close' : '';
        backdrop.setAttribute('aria-label', 'Close call popup');

        const dialog = makeEl('section', `consultation-call-dialog ${state.callPhase === 'active' ? 'is-active' : ''}`);
        dialog.setAttribute('role', 'dialog');
        dialog.setAttribute('aria-modal', 'true');

        const top = makeEl('header', 'consultation-call-top');
        const avatar = makeEl('span', 'consultation-call-avatar');
        appendAvatar(avatar, participant);
        const copy = makeEl('div', 'consultation-call-copy');
        copy.append(makeEl('strong', null, participant?.name || 'Consultation participant'), makeEl('span', null, callStatus()));
        top.append(avatar, copy);

        if (state.callPhase === 'active') {
            const duration = makeEl('span', 'consultation-call-duration', '00:00');
            duration.dataset.callDuration = 'true';
            top.append(duration);
        }

        const close = makeEl('button', 'consultation-call-close', 'x');
        close.type = 'button';
        close.dataset.callCommand = state.callPhase === 'outgoing-preview' ? 'close' : (state.callPhase === 'active' ? 'end' : 'cancel');
        close.setAttribute('aria-label', state.callPhase === 'active' ? 'End call' : 'Close call popup');
        top.append(close);
        dialog.append(top);

        if (state.callPhase === 'active') {
            const stage = makeEl('div', 'consultation-call-stage');
            const remoteVideo = document.createElement('video');
            remoteVideo.id = 'remoteVideo';
            remoteVideo.autoplay = true;
            remoteVideo.playsInline = true;
            remoteVideo.setAttribute('autoplay', '');
            remoteVideo.setAttribute('playsinline', '');
            remoteVideo.dataset.remoteVideo = 'true';
            stage.append(remoteVideo, makeEl('div', 'consultation-remote-placeholder', 'Waiting for remote video'));

            const localTile = makeEl('div', 'consultation-local-tile');
            localTile.dataset.localTile = 'true';
            const localVideo = document.createElement('video');
            localVideo.id = 'localVideo';
            localVideo.autoplay = true;
            localVideo.muted = true;
            localVideo.defaultMuted = true;
            localVideo.playsInline = true;
            localVideo.setAttribute('autoplay', '');
            localVideo.setAttribute('muted', '');
            localVideo.setAttribute('playsinline', '');
            localVideo.dataset.localVideo = 'true';
            localTile.append(localVideo);
            stage.append(localTile);
            dialog.append(stage);
        } else {
            const preview = makeEl('div', 'consultation-video-preview');
            const localVideo = document.createElement('video');
            localVideo.autoplay = true;
            localVideo.muted = true;
            localVideo.defaultMuted = true;
            localVideo.playsInline = true;
            localVideo.setAttribute('autoplay', '');
            localVideo.setAttribute('muted', '');
            localVideo.setAttribute('playsinline', '');
            localVideo.dataset.localVideo = 'true';
            preview.append(localVideo);
            preview.append(makeEl('span', null, state.pendingCallType === 'video'
                ? (state.localStream ? 'Camera preview' : 'Camera preview appears after Start Call')
                : 'Microphone starts after Start Call'));
            dialog.append(preview);
        }

        const controls = makeEl('div', 'consultation-call-controls');

        const controlButton = (label, command, variant = '') => {
            const button = makeEl('button', variant, label);
            button.type = 'button';
            button.dataset.callCommand = command;
            return button;
        };

        if (state.callPhase === 'outgoing-preview') {
            controls.append(
                controlButton(state.callStarting ? 'Starting...' : `Start ${callTitle()}`, 'start', 'is-primary'),
                controlButton('Cancel', 'close', 'is-muted')
            );
        } else if (state.callPhase === 'incoming-ringing') {
            controls.append(
                controlButton('Accept', 'accept', 'is-accept'),
                controlButton('Decline', 'decline', 'is-danger')
            );
        } else {
            controls.append(
                controlButton(state.audioMuted ? 'Unmute' : 'Mute', 'toggle-audio'),
                controlButton(state.videoOff ? 'Camera on' : 'Camera off', 'toggle-video'),
                controlButton('Switch', 'switch-camera'),
                controlButton(state.callPhase === 'active' ? 'End' : 'Cancel', state.callPhase === 'active' ? 'end' : 'cancel', 'is-danger')
            );
        }

        dialog.append(controls);
        if (!useWindow && state.callPhase !== 'incoming-ringing') {
            container.append(backdrop);
        }
        container.append(dialog);
        syncVideoElements();
        updateDurationText();
        renderHeader();
    };

    const stopStream = (stream) => {
        stream?.getTracks().forEach((track) => track.stop());
    };

    const resetPeer = () => {
        if (state.peer) {
            state.peer.onicecandidate = null;
            state.peer.ontrack = null;
            state.peer.onconnectionstatechange = null;
            state.peer.close();
        }
        state.peer = null;
        state.remoteStream = null;
        state.peerCallId = null;
        state.remoteTrackIds.clear();
        state.remoteOfferKey = null;
        state.remoteAnswerKey = null;
        state.pendingIceCandidates = [];
        state.seenIceCandidates.clear();
        state.failedDescriptionKeys.clear();
    };

    const cleanupCallMedia = () => {
        const localStream = state.localStream;
        const remoteStream = state.remoteStream;

        allCallContainers().forEach(clearVideoElements);
        resetPeer();
        stopStream(localStream);
        stopStream(remoteStream);
        state.localStream = null;
        state.remoteStream = null;
        state.audioMuted = false;
        state.videoOff = false;
        state.callStatusText = '';
        state.callActionLocked = false;
        state.callEnding = false;
        state.drag = null;
        if (state.durationTimer) window.clearInterval(state.durationTimer);
        state.durationTimer = null;
    };

    const closeCallModal = () => {
        stopCallPolling();
        cleanupCallMedia();
        state.activeCall = null;
        state.callPhase = 'idle';
        state.callStarting = false;
        state.answering = false;
        state.callActionLocked = false;
        state.callEnding = false;
        state.isCallModalOpen = false;
        state.renderedCallId = null;
        state.renderedCallPhase = 'idle';
        state.callWindowActiveSizeApplied = false;
        closeCallWindowOnly();
        renderCallModal();
    };

    const ensureMedia = async (callType = 'video') => {
        if (state.localStream) return state.localStream;
        if (!navigator.mediaDevices?.getUserMedia) {
            throw new Error('Camera or microphone is not available in this browser.');
        }

        try {
            state.localStream = await navigator.mediaDevices.getUserMedia({
                audio: true,
                video: callType === 'video' ? { facingMode: state.facingMode } : false,
            });
            state.audioMuted = false;
            state.videoOff = false;
            syncVideoElements();
            return state.localStream;
        } catch (error) {
            throw new Error('Camera or microphone permission was denied.');
        }
    };

    const signalCall = async (payload, callId = currentCallId()) => {
        if (!state.activeCall || !callId || Number(state.activeCall.id) !== Number(callId) || callWasLocallyEnded(callId)) return null;
        const data = await fetchJson(templateUrl(root.dataset.callSignalUrlTemplate, callId), {
            method: 'POST',
            body: JSON.stringify(payload),
        });
        if (state.activeCall && Number(state.activeCall.id) === Number(callId) && !callWasLocallyEnded(callId)) {
            state.activeCall = data.call;
        }
        return data.call;
    };

    const createPeer = async () => {
        if (state.peer && state.peerCallId === currentCallId()) return state.peer;

        resetPeer();
        const callId = currentCallId();
        state.remoteStream = null;
        state.peerCallId = callId;

        state.peer = new RTCPeerConnection({
            iceServers: [{ urls: 'stun:stun.l.google.com:19302' }],
        });

        state.localStream?.getTracks().forEach((track) => {
            state.peer.addTrack(track, state.localStream);
        });

        state.peer.ontrack = (event) => {
            if (!callId || state.peerCallId !== callId || currentCallId() !== callId || callWasLocallyEnded(callId)) return;

            const stream = event.streams?.[0] || null;
            if (stream && stream === state.localStream) {
                logCallError('Ignoring local stream received as remote track', { call: callId, track: event.track?.id });
                return;
            }

            if (event.track?.id && state.remoteTrackIds.has(event.track.id)) {
                syncRemoteVideoElements();
                return;
            }

            if (event.track?.id) state.remoteTrackIds.add(event.track.id);

            if (stream) {
                state.remoteStream = stream;
            } else if (event.track) {
                if (!state.remoteStream) state.remoteStream = new MediaStream();
                if (!state.remoteStream.getTracks().some((track) => track.id === event.track.id)) {
                    state.remoteStream.addTrack(event.track);
                }
            }

            syncRemoteVideoElements();
        };

        state.peer.onicecandidate = (event) => {
            if (!event.candidate || !state.activeCall || Number(state.activeCall.id) !== Number(callId) || callWasLocallyEnded(callId)) return;
            const candidate = event.candidate.toJSON ? event.candidate.toJSON() : {
                candidate: event.candidate.candidate,
                sdpMid: event.candidate.sdpMid,
                sdpMLineIndex: event.candidate.sdpMLineIndex,
            };
            signalCall({ candidate: { call_id: callId, ...candidate } }, callId).catch((error) => {
                logCallError('Unable to send ICE candidate', error);
                state.callStatusText = publicCallError();
                renderCallModal();
            });
        };

        state.peer.onconnectionstatechange = () => {
            if (state.peer?.connectionState === 'failed') {
                logCallError('Peer connection failed', { state: state.peer.connectionState, call: state.activeCall?.id });
                state.callStatusText = publicCallError();
                renderCallModal();
                cleanupCallMedia();
            } else if (state.peer?.connectionState === 'disconnected') {
                state.callStatusText = 'Reconnecting...';
                renderCallModal();
            }
        };

        syncVideoElements();
        return state.peer;
    };

    const flushQueuedIceCandidates = async () => {
        if (!state.peer?.remoteDescription) return;

        const queued = [...state.pendingIceCandidates];
        state.pendingIceCandidates = [];

        for (const entry of queued) {
            await addIceCandidateEntry(entry);
        }
    };

    const addIceCandidateEntry = async (entry) => {
        if (!candidateForCurrentCall(entry)) return;

        const key = entry.fingerprint || candidateKey(entry.candidate);
        if (!key || state.seenIceCandidates.has(key)) return;

        if (!state.peer?.remoteDescription) {
            if (state.pendingIceCandidates.some((pending) => (pending.fingerprint || candidateKey(pending.candidate)) === key)) return;
            state.pendingIceCandidates.push(entry);
            return;
        }

        state.seenIceCandidates.add(key);

        try {
            await state.peer.addIceCandidate(new RTCIceCandidate(entry.candidate));
        } catch (error) {
            logCallError('ICE candidate failure', { error, entry, call: state.activeCall?.id });
        }
    };

    const applyRemoteDescriptionOnce = async (description, expectedType) => {
        const remoteDescription = rtcDescription(description, expectedType);
        if (!remoteDescription || !state.peer) {
            state.callStatusText = publicCallError();
            renderCallModal();
            return false;
        }

        const key = descriptionKey(description);
        if (state.failedDescriptionKeys.has(`${expectedType}:${key}`)) return false;

        if (expectedType === 'offer') {
            if (state.remoteOfferKey === key) return true;
            if (state.remoteOfferKey || state.peer.remoteDescription) {
                logCallError('Duplicate remote offer ignored', { call: state.activeCall?.id });
                return true;
            }
        }

        if (expectedType === 'answer') {
            if (state.remoteAnswerKey === key) return true;
            if (state.remoteAnswerKey || state.peer.remoteDescription) {
                logCallError('Duplicate remote answer ignored', { call: state.activeCall?.id });
                return true;
            }
        }

        try {
            await state.peer.setRemoteDescription(remoteDescription);
            if (expectedType === 'offer') state.remoteOfferKey = key;
            if (expectedType === 'answer') state.remoteAnswerKey = key;
            await flushQueuedIceCandidates();
            return true;
        } catch (error) {
            logCallError('Invalid SDP or remote description failure', { error, expectedType, description, call: state.activeCall?.id });
            state.failedDescriptionKeys.add(`${expectedType}:${key}`);
            state.callStatusText = publicCallError();
            renderCallModal();
            return false;
        }
    };

    const processSignaling = async (call) => {
        if (!call || !state.peer) return;
        const callId = Number(call.id);
        if (!callId || currentCallId() !== callId || callWasLocallyEnded(callId)) return;
        const signaling = call.signaling || {};

        if (call.is_receiver && signaling.offer) {
            const offerApplied = await applyRemoteDescriptionOnce(signaling.offer, 'offer');
            if (offerApplied && !state.peer.localDescription && !state.answering) {
                state.answering = true;
                try {
                    const answer = await state.peer.createAnswer();
                    await state.peer.setLocalDescription(answer);
                    await signalCall({
                        answer: {
                            type: state.peer.localDescription.type,
                            sdp: state.peer.localDescription.sdp,
                        },
                    }, callId);
                } finally {
                    state.answering = false;
                }
            }
        }

        if (call.is_caller && signaling.answer) {
            await applyRemoteDescriptionOnce(signaling.answer, 'answer');
        }

        for (const entry of signaling.ice_candidates || []) {
            await addIceCandidateEntry(entry);
        }
    };

    const updateDurationText = () => {
        if (!state.activeCall?.answered_at) return;
        const elapsed = Math.max(0, Math.floor((Date.now() - new Date(state.activeCall.answered_at).getTime()) / 1000));
        const minutes = String(Math.floor(elapsed / 60)).padStart(2, '0');
        const seconds = String(elapsed % 60).padStart(2, '0');
        callContainers().forEach((container) => {
            container.querySelectorAll('[data-call-duration]').forEach((duration) => {
                duration.textContent = `${minutes}:${seconds}`;
            });
        });
    };

    const startDurationTimer = () => {
        if (state.durationTimer) return;
        updateDurationText();
        state.durationTimer = window.setInterval(updateDurationText, 1000);
    };

    const stopCallPolling = () => {
        if (!state.callPollId) return;
        window.clearInterval(state.callPollId);
        state.callPollId = null;
    };

    const finishRemoteCall = (message, callId = currentCallId()) => {
        state.callStatusText = message;
        renderCallModal();
        rememberEndedCall(callId);
        stopCallPolling();
        cleanupCallMedia();
        window.setTimeout(closeCallModal, 1400);
    };

    const pollActiveCall = async () => {
        if (!state.activeCall || !root.dataset.callShowUrlTemplate) return;
        const requestedCallId = currentCallId();
        if (!requestedCallId || callWasLocallyEnded(requestedCallId)) return;

        try {
            const data = await fetchJson(templateUrl(root.dataset.callShowUrlTemplate, requestedCallId));
            const call = data.call;
            if (!call || Number(call.id) !== requestedCallId || callWasLocallyEnded(requestedCallId)) return;
            if (!state.activeCall || currentCallId() !== requestedCallId) return;
            state.activeCall = call;

            if (terminalCallStatuses.includes(call.status)) {
                finishRemoteCall(call.status === 'declined' ? 'Call declined.' : 'Call ended.', requestedCallId);
                return;
            }

            if (call.status === 'accepted' && state.callPhase !== 'active') {
                state.callPhase = 'active';
                state.callStatusText = 'Connected';
                startDurationTimer();
            }

            await processSignaling(call);
            if (state.activeCall && currentCallId() === requestedCallId && !callWasLocallyEnded(requestedCallId)) {
                renderCallModal();
            }
        } catch (error) {
            logCallError('Active call polling failed', error);
            state.callStatusText = publicCallError();
            renderCallModal();
        }
    };

    const startCallPolling = () => {
        if (state.callPollId) return;
        pollActiveCall();
        state.callPollId = window.setInterval(pollActiveCall, 700);
    };

    const openOutgoingPreview = (callType) => {
        if (!state.selectedId) {
            setError('The selected conversation is invalid.');
            return;
        }

        if (state.callPhase !== 'idle') return;
        if (!ensureCallWindow()) return;
        state.callActionLocked = true;
        state.pendingCallType = callType;
        state.callPhase = 'outgoing-preview';
        state.callStatusText = '';
        renderCallModal();
        window.setTimeout(() => {
            state.callActionLocked = false;
            renderHeader();
        }, 700);
    };

    const startOutgoingCall = async () => {
        if (state.callStarting || state.activeCall || !state.selectedId) return;
        state.callStarting = true;
        state.callStatusText = 'Calling...';
        renderCallModal();

        try {
            await ensureMedia(state.pendingCallType);
            if (state.callPhase !== 'outgoing-preview') {
                cleanupCallMedia();
                return;
            }
            const data = await fetchJson(templateUrl(root.dataset.callUrlTemplate, state.selectedId), {
                method: 'POST',
                body: JSON.stringify({ call_type: state.pendingCallType }),
            });
            if (state.callPhase !== 'outgoing-preview') {
                rememberEndedCall(data.call?.id);
                if (data.call?.id) {
                    fetchJson(templateUrl(root.dataset.callUpdateUrlTemplate, data.call.id), {
                        method: 'PATCH',
                        body: JSON.stringify({ action: 'cancel' }),
                    }).catch(() => {});
                }
                return;
            }
            state.activeCall = data.call;
            const callId = currentCallId();
            state.callPhase = 'outgoing-ringing';
            state.callStatusText = 'Calling...';
            await createPeer();
            const offer = await state.peer.createOffer();
            await state.peer.setLocalDescription(offer);
            await signalCall({
                offer: {
                    type: state.peer.localDescription.type,
                    sdp: state.peer.localDescription.sdp,
                },
            }, callId);
            state.callStarting = false;
            renderCallModal();
            startCallPolling();
        } catch (error) {
            logCallError('Outgoing call failed', error);
            const message = friendlyCallError(error);
            setError(message);
            state.callStatusText = message;
            state.callStarting = false;
            if (state.activeCall?.status === 'ringing') {
                updateCall('cancel').catch(() => {});
            }
            cleanupCallMedia();
            renderCallModal();
        }
    };

    const updateCall = async (action) => {
        const callId = currentCallId();
        if (!state.activeCall || !callId || callWasLocallyEnded(callId)) return null;
        const data = await fetchJson(templateUrl(root.dataset.callUpdateUrlTemplate, callId), {
            method: 'PATCH',
            body: JSON.stringify({ action }),
        });
        if (state.activeCall && currentCallId() === callId && !callWasLocallyEnded(callId)) {
            state.activeCall = data.call;
        }
        return data.call;
    };

    const acceptIncomingCall = async () => {
        if (!state.activeCall || state.callStarting || state.peer) return;
        if (!ensureCallWindow()) return;
        state.callStarting = true;
        state.callStatusText = 'Starting camera...';
        renderCallModal();

        try {
            await ensureMedia(state.activeCall.call_type);
            const acceptedCallId = currentCallId();
            const call = await updateCall('accept');
            if (!call || !acceptedCallId || currentCallId() !== acceptedCallId || callWasLocallyEnded(acceptedCallId)) return;
            state.activeCall = call;
            state.callPhase = 'active';
            state.callStatusText = 'Connected';
            await createPeer();
            await processSignaling(call);
            startDurationTimer();
            startCallPolling();
            state.callStarting = false;
            renderCallModal();
        } catch (error) {
            logCallError('Accept call failed', error);
            const message = friendlyCallError(error);
            setError(message);
            state.callStatusText = message;
            state.callStarting = false;
            renderCallModal();
        }
    };

    const cancelOrEndCall = (action) => {
        if (!state.activeCall) {
            closeCallModal();
            return;
        }

        if (state.callEnding) return;
        state.callEnding = true;
        const callId = state.activeCall.id;
        const url = templateUrl(root.dataset.callUpdateUrlTemplate, callId);

        rememberEndedCall(callId);
        closeCallModal();
        fetchJson(url, {
            method: 'PATCH',
            body: JSON.stringify({ action }),
        }).catch((error) => {
            logCallError('Background call status update failed', { action, callId, error });
        });
    };

    const toggleAudio = () => {
        state.audioMuted = !state.audioMuted;
        state.localStream?.getAudioTracks().forEach((track) => {
            track.enabled = !state.audioMuted;
        });
        renderCallModal();
    };

    const toggleVideo = () => {
        state.videoOff = !state.videoOff;
        state.localStream?.getVideoTracks().forEach((track) => {
            track.enabled = !state.videoOff;
        });
        renderCallModal();
    };

    const switchCamera = async () => {
        if (!state.localStream || state.pendingCallType !== 'video') return;
        state.facingMode = state.facingMode === 'user' ? 'environment' : 'user';

        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                audio: false,
                video: { facingMode: state.facingMode },
            });
            const newTrack = stream.getVideoTracks()[0];
            const oldTrack = state.localStream.getVideoTracks()[0];
            const sender = state.peer?.getSenders().find((item) => item.track?.kind === 'video');
            if (sender && newTrack) await sender.replaceTrack(newTrack);
            if (oldTrack) {
                state.localStream.removeTrack(oldTrack);
                oldTrack.stop();
            }
            if (newTrack) state.localStream.addTrack(newTrack);
            syncVideoElements();
        } catch (error) {
            setError('Unable to switch camera on this device.');
        }
    };

    const pollIncomingCalls = async () => {
        if (state.incomingBusy || state.callPhase !== 'idle') return;
        state.incomingBusy = true;
        try {
            const data = await fetchJson(root.dataset.incomingCallsUrl);
            const incoming = (data.calls || []).find((call) => !callWasLocallyEnded(call.id));
            if (incoming) {
                state.activeCall = incoming;
                state.pendingCallType = incoming.call_type;
                state.callPhase = 'incoming-ringing';
                state.callStatusText = incoming.call_type === 'video' ? 'Incoming video call' : 'Incoming voice call';
                renderCallModal();
            }
        } catch (error) {
            // Incoming call polling stays quiet until the user performs an action.
        } finally {
            state.incomingBusy = false;
        }
    };

    const currentRecordingMs = () => {
        if (!state.recorder.startedAt || state.recorder.paused) return state.recorder.elapsedMs;
        return state.recorder.elapsedMs + (Date.now() - state.recorder.startedAt);
    };

    const formatDuration = (ms) => {
        const seconds = Math.floor(ms / 1000);
        return `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
    };

    const supportedRecorderMime = () => {
        if (!window.MediaRecorder) return '';
        return [
            'video/webm;codecs=vp9,opus',
            'video/webm;codecs=vp8,opus',
            'video/webm',
            'video/mp4',
        ].find((type) => MediaRecorder.isTypeSupported(type)) || '';
    };

    let recorderModal = null;

    const renderRecorder = () => {
        if (!state.recorder.open) {
            recorderModal?.remove();
            recorderModal = null;
            return;
        }

        if (!recorderModal) {
            recorderModal = makeEl('div', 'consultation-recorder-modal');
            root.append(recorderModal);
        }

        recorderModal.replaceChildren();
        const backdrop = makeEl('button', 'consultation-recorder-backdrop');
        backdrop.type = 'button';
        backdrop.dataset.recorderAction = 'cancel';
        backdrop.setAttribute('aria-label', 'Close video recorder');

        const dialog = makeEl('section', 'consultation-recorder-dialog');
        dialog.setAttribute('role', 'dialog');
        dialog.setAttribute('aria-modal', 'true');

        const header = makeEl('header', null);
        const title = makeEl('div');
        title.append(makeEl('strong', null, 'Record Video Message'), makeEl('span', null, state.recorder.blob ? 'Preview before sending' : 'Camera and microphone are active'));
        const timer = makeEl('span', 'consultation-recorder-timer', formatDuration(currentRecordingMs()));
        timer.dataset.recorderTimer = 'true';
        header.append(title, timer);
        dialog.append(header);

        const stage = makeEl('div', 'consultation-recorder-stage');
        if (state.recorder.blob) {
            const preview = document.createElement('video');
            preview.controls = true;
            preview.playsInline = true;
            preview.src = state.recorder.previewUrl;
            stage.append(preview);
        } else {
            const live = document.createElement('video');
            live.autoplay = true;
            live.muted = true;
            live.playsInline = true;
            live.srcObject = state.recorder.stream;
            stage.append(live);
        }
        if (state.recorder.mediaRecorder?.state === 'recording') {
            stage.append(makeEl('span', 'consultation-recording-dot', 'Recording'));
        }
        dialog.append(stage);

        if (state.recorder.error) {
            dialog.append(makeEl('p', 'consultation-recorder-error', state.recorder.error));
        }

        const controls = makeEl('div', 'consultation-recorder-controls');
        const button = (label, action, className = '') => {
            const node = makeEl('button', className, label);
            node.type = 'button';
            node.dataset.recorderAction = action;
            return node;
        };

        const recorderState = state.recorder.mediaRecorder?.state || 'inactive';
        if (!state.recorder.blob) {
            if (recorderState === 'inactive') controls.append(button('Start Recording', 'start', 'is-primary'));
            if (recorderState === 'recording') controls.append(button('Pause', 'pause'), button('Stop Recording', 'stop', 'is-danger'));
            if (recorderState === 'paused') controls.append(button('Resume', 'resume', 'is-primary'), button('Stop Recording', 'stop', 'is-danger'));
        } else {
            controls.append(button('Retake', 'retake'), button(state.recorder.uploading ? 'Sending...' : 'Send Video', 'send', 'is-primary'));
        }
        controls.append(button('Cancel', 'cancel', 'is-muted'));
        dialog.append(controls);
        recorderModal.append(backdrop, dialog);
    };

    const tickRecorder = () => {
        recorderModal?.querySelector('[data-recorder-timer]')?.replaceChildren(document.createTextNode(formatDuration(currentRecordingMs())));
        if (currentRecordingMs() >= recorderLimitMs) stopRecording();
    };

    const openRecorder = async () => {
        if (!state.selectedId) {
            setError('The selected conversation is invalid.');
            return;
        }
        if (state.recorder.open) return;
        if (!window.MediaRecorder || !supportedRecorderMime()) {
            setError('Video recording is not supported in this browser.');
            return;
        }

        hideAttachmentMenu();
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
            state.recorder = {
                ...state.recorder,
                open: true,
                stream,
                mediaRecorder: null,
                chunks: [],
                blob: null,
                previewUrl: '',
                mimeType: supportedRecorderMime(),
                timerId: null,
                maxTimerId: null,
                startedAt: null,
                elapsedMs: 0,
                durationSeconds: 0,
                paused: false,
                uploading: false,
                error: '',
            };
            renderRecorder();
        } catch (error) {
            setError('Camera or microphone permission was denied.');
        }
    };

    const startRecording = () => {
        if (!state.recorder.stream || state.recorder.mediaRecorder?.state === 'recording') return;
        state.recorder.chunks = [];
        state.recorder.error = '';
        const options = state.recorder.mimeType ? { mimeType: state.recorder.mimeType } : {};
        state.recorder.mediaRecorder = new MediaRecorder(state.recorder.stream, options);

        state.recorder.mediaRecorder.ondataavailable = (event) => {
            if (event.data && event.data.size > 0) state.recorder.chunks.push(event.data);
        };

        state.recorder.mediaRecorder.onstop = () => {
            const type = state.recorder.mimeType || state.recorder.chunks[0]?.type || 'video/webm';
            state.recorder.blob = new Blob(state.recorder.chunks, { type });
            state.recorder.durationSeconds = Math.max(1, Math.ceil(state.recorder.elapsedMs / 1000));
            state.recorder.previewUrl = URL.createObjectURL(state.recorder.blob);
            state.recorder.startedAt = null;
            if (state.recorder.timerId) window.clearInterval(state.recorder.timerId);
            if (state.recorder.maxTimerId) window.clearTimeout(state.recorder.maxTimerId);
            state.recorder.timerId = null;
            state.recorder.maxTimerId = null;
            renderRecorder();
        };

        state.recorder.startedAt = Date.now();
        state.recorder.elapsedMs = 0;
        state.recorder.paused = false;
        state.recorder.mediaRecorder.start(1000);
        state.recorder.timerId = window.setInterval(tickRecorder, 250);
        state.recorder.maxTimerId = window.setTimeout(stopRecording, recorderLimitMs);
        renderRecorder();
    };

    const pauseRecording = () => {
        if (state.recorder.mediaRecorder?.state !== 'recording') return;
        state.recorder.elapsedMs = currentRecordingMs();
        state.recorder.startedAt = null;
        state.recorder.paused = true;
        state.recorder.mediaRecorder.pause();
        renderRecorder();
    };

    const resumeRecording = () => {
        if (state.recorder.mediaRecorder?.state !== 'paused') return;
        state.recorder.startedAt = Date.now();
        state.recorder.paused = false;
        state.recorder.mediaRecorder.resume();
        renderRecorder();
    };

    const stopRecording = () => {
        if (!state.recorder.mediaRecorder || state.recorder.mediaRecorder.state === 'inactive') return;
        state.recorder.elapsedMs = currentRecordingMs();
        state.recorder.mediaRecorder.stop();
    };

    const retakeRecording = () => {
        if (state.recorder.previewUrl) URL.revokeObjectURL(state.recorder.previewUrl);
        state.recorder.blob = null;
        state.recorder.previewUrl = '';
        state.recorder.chunks = [];
        state.recorder.mediaRecorder = null;
        state.recorder.elapsedMs = 0;
        state.recorder.durationSeconds = 0;
        state.recorder.error = '';
        renderRecorder();
    };

    const closeRecorder = () => {
        if (state.recorder.mediaRecorder && state.recorder.mediaRecorder.state !== 'inactive') {
            state.recorder.mediaRecorder.ondataavailable = null;
            state.recorder.mediaRecorder.onstop = null;
            state.recorder.mediaRecorder.stop();
        }
        if (state.recorder.timerId) window.clearInterval(state.recorder.timerId);
        if (state.recorder.maxTimerId) window.clearTimeout(state.recorder.maxTimerId);
        stopStream(state.recorder.stream);
        if (state.recorder.previewUrl) URL.revokeObjectURL(state.recorder.previewUrl);
        state.recorder = {
            open: false,
            stream: null,
            mediaRecorder: null,
            chunks: [],
            blob: null,
            previewUrl: '',
            mimeType: '',
            timerId: null,
            maxTimerId: null,
            startedAt: null,
            elapsedMs: 0,
            durationSeconds: 0,
            paused: false,
            uploading: false,
            error: '',
        };
        renderRecorder();
    };

    const sendRecordedVideo = async () => {
        if (!state.recorder.blob || state.recorder.uploading) return;
        state.recorder.uploading = true;
        state.recorder.error = '';
        renderRecorder();

        const extension = state.recorder.blob.type.includes('mp4') ? 'mp4' : 'webm';
        const data = new FormData();
        data.set('message', '');
        data.set('message_type', 'text');
        data.set('attachment_duration', String(state.recorder.durationSeconds || Math.ceil(currentRecordingMs() / 1000) || 1));
        data.append('attachment', state.recorder.blob, `video-message-${Date.now()}.${extension}`);

        try {
            const payload = await submitMessageData(data);
            applySentPayload(payload);
            closeRecorder();
        } catch (error) {
            state.recorder.uploading = false;
            state.recorder.error = error.message;
            setError(error.message);
            renderRecorder();
        }
    };

    const refresh = async () => {
        if (document.hidden) return;

        try {
            await loadConversations();
            if (state.selectedId) await loadMessages();
            await pollIncomingCalls();
        } catch (error) {
            setError(error.message);
        }
    };

    const startPolling = () => {
        if (state.pollId || document.hidden) return;
        state.pollId = window.setInterval(refresh, 4000);
    };

    const stopPolling = () => {
        if (!state.pollId) return;
        window.clearInterval(state.pollId);
        state.pollId = null;
    };

    const startIncomingPolling = () => {
        if (state.incomingPollId || document.hidden) return;
        pollIncomingCalls();
        state.incomingPollId = window.setInterval(pollIncomingCalls, 1000);
    };

    const stopIncomingPolling = () => {
        if (!state.incomingPollId) return;
        window.clearInterval(state.incomingPollId);
        state.incomingPollId = null;
    };

    list.addEventListener('click', (event) => {
        const button = event.target.closest('[data-conversation-id]');
        if (!button) return;
        selectConversation(button.dataset.conversationId, true);
    });

    searchInput.addEventListener('input', filterConversations);

    mobileBack?.addEventListener('click', () => {
        root.classList.remove('is-chat-open');
    });

    textarea.addEventListener('input', () => {
        if (messageType.value !== 'medical_template' && messageType.value !== 'iec_material') {
            messageType.value = 'text';
        }
        setError('');
        resizeTextarea();
        setComposerEnabled();
    });

    textarea.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' || event.shiftKey || event.isComposing) return;
        event.preventDefault();
        sendMessage();
    });

    attachmentButton.addEventListener('click', toggleAttachmentMenu);

    ensureAttachmentMenu().addEventListener('click', (event) => {
        const button = event.target.closest('[data-attachment-choice]');
        if (!button) return;
        if (button.dataset.attachmentChoice === 'record') {
            openRecorder();
            return;
        }
        chooseFileAttachment(button.dataset.accept);
    });

    attachmentInput.addEventListener('change', () => {
        attachmentName.textContent = attachmentInput.files[0]?.name || '';
        setError('');
        setComposerEnabled();
    });

    emojiButton.addEventListener('click', () => appendAtCursor(String.fromCodePoint(0x1F642)));

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        sendMessage();
    });

    messageList.addEventListener('click', (event) => {
        const button = event.target.closest('[data-unsend-message]');
        if (!button) return;
        unsendMessage(button.dataset.unsendMessage);
    });

    function handleCallCommand(command) {
        if (!command) return;
        if (command === 'close') closeCallModal();
        if (command === 'start') startOutgoingCall();
        if (command === 'accept') acceptIncomingCall();
        if (command === 'decline') cancelOrEndCall('decline');
        if (command === 'cancel') cancelOrEndCall(state.activeCall?.is_receiver ? 'decline' : 'cancel');
        if (command === 'end') cancelOrEndCall('end');
        if (command === 'toggle-audio') toggleAudio();
        if (command === 'toggle-video') toggleVideo();
        if (command === 'switch-camera') switchCamera();
    }

    function handleLocalTilePointerDown(event) {
        const tile = event.target.closest('[data-local-tile]');
        if (!tile) return;
        state.drag = {
            tile,
            startX: event.clientX,
            startY: event.clientY,
            left: tile.offsetLeft,
            top: tile.offsetTop,
        };
        tile.setPointerCapture?.(event.pointerId);
    }

    function handleLocalTilePointerMove(event) {
        if (!state.drag) return;
        const parent = state.drag.tile.parentElement;
        const width = parent.clientWidth - state.drag.tile.offsetWidth;
        const height = parent.clientHeight - state.drag.tile.offsetHeight;
        const left = Math.max(10, Math.min(width - 10, state.drag.left + event.clientX - state.drag.startX));
        const top = Math.max(10, Math.min(height - 10, state.drag.top + event.clientY - state.drag.startY));
        state.drag.tile.style.left = `${left}px`;
        state.drag.tile.style.top = `${top}px`;
        state.drag.tile.style.right = 'auto';
        state.drag.tile.style.bottom = 'auto';
    }

    function handleLocalTilePointerUp() {
        state.drag = null;
    }

    root.querySelectorAll('[data-call-type]').forEach((button) => {
        button.addEventListener('click', () => openOutgoingPreview(button.dataset.callType));
    });

    smsButton?.addEventListener('click', () => {
        const participant = selectedConversation()?.participant || {};

        if (!participant.sms_url) {
            setError('SMS number is not available for this contact.');
            return;
        }

        window.location.href = participant.sms_url;
    });

    callPopover?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-call-command]');
        if (!button) return;
        handleCallCommand(button.dataset.callCommand);
    });

    document.addEventListener('pointerdown', handleLocalTilePointerDown);
    document.addEventListener('pointermove', handleLocalTilePointerMove);
    document.addEventListener('pointerup', handleLocalTilePointerUp);

    document.addEventListener('click', (event) => {
        if (attachmentMenu && !attachmentMenu.hidden && !event.target.closest('.consultation-attachment-menu') && !event.target.closest('[data-attachment-button]')) {
            hideAttachmentMenu();
        }
    });

    if (hasStaffTools) {
        quickActionsToggle?.addEventListener('click', () => {
            const collapsed = !quickActions?.hidden;
            if (quickActions) quickActions.hidden = collapsed;
            quickActionsToggle.setAttribute('aria-expanded', String(!collapsed));
        });

        quickActions?.addEventListener('click', (event) => {
            if (event.target.closest('[data-schedule-checkup]')) {
                openScheduleAppointmentModal();
                return;
            }

            const button = event.target.closest('[data-quick-text]');
            if (!button) return;
            insertMessage(button.dataset.quickText || '', 'text');
        });

        iecSelect?.addEventListener('change', () => {
            const option = iecSelect.selectedOptions[0];
            const text = option?.dataset.message || '';
            if (text) {
                insertMessage(text, 'iec_material');
                iecSelect.value = '';
            }
        });
    }

    root.addEventListener('click', (event) => {
        const action = event.target.closest('[data-recorder-action]')?.dataset.recorderAction;
        if (!action) return;
        if (action === 'start') startRecording();
        if (action === 'pause') pauseRecording();
        if (action === 'resume') resumeRecording();
        if (action === 'stop') stopRecording();
        if (action === 'retake') retakeRecording();
        if (action === 'send') sendRecordedVideo();
        if (action === 'cancel') closeRecorder();
    });

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopPolling();
            stopIncomingPolling();
            return;
        }

        refresh();
        startPolling();
        startIncomingPolling();
    });

    window.addEventListener('beforeunload', () => {
        stopPolling();
        stopIncomingPolling();
        stopCallPolling();
        if (state.activeCall && activeCallStatuses.includes(state.activeCall.status)) {
            const action = state.activeCall.status === 'accepted' ? 'end' : (state.activeCall.is_receiver ? 'decline' : 'cancel');
            rememberEndedCall(state.activeCall.id);
            fetch(templateUrl(root.dataset.callUpdateUrlTemplate, state.activeCall.id), {
                method: 'PATCH',
                keepalive: true,
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ action }),
            }).catch(() => {});
        }
        cleanupCallMedia();
        closeRecorder();
    });

    setComposerEnabled();
    refresh().then(() => {
        startPolling();
        startIncomingPolling();
    }).catch((error) => {
        setError(error.message);
        startPolling();
        startIncomingPolling();
    });
})();
