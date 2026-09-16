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
    const phoneButton = root.querySelector('[data-phone-button]');
    const smsButton = root.querySelector('[data-sms-button]');
    const mobileBack = root.querySelector('[data-mobile-back]');

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

        if (phoneButton) {
            phoneButton.disabled = !participant.sms_url;
            phoneButton.title = participant.sms_url ? `Call ${participant.name}` : 'Phone number is unavailable for this contact';
        }

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

    const stopStream = (stream) => {
        stream?.getTracks().forEach((track) => track.stop());
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

    phoneButton?.addEventListener('click', () => {
        const smsUrl = selectedConversation()?.participant?.sms_url;
        if (smsUrl) window.location.href = smsUrl.replace(/^sms:/, 'tel:');
    });

    smsButton?.addEventListener('click', () => {
        const participant = selectedConversation()?.participant || {};

        if (!participant.sms_url) {
            setError('SMS number is not available for this contact.');
            return;
        }

        window.location.href = participant.sms_url;
    });

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
            return;
        }

        refresh();
        startPolling();
    });

    window.addEventListener('beforeunload', () => {
        stopPolling();
        closeRecorder();
    });

    setComposerEnabled();
    refresh().then(() => {
        startPolling();
    }).catch((error) => {
        setError(error.message);
        startPolling();
    });
})();
