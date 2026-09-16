(() => {
    const root = document.querySelector('[data-admin-staff-messages-root]');
    if (!root || root.dataset.adminStaffMessagesBooted === 'true') return;
    root.dataset.adminStaffMessagesBooted = 'true';

    const state = {
        threads: [],
        messages: [],
        selectedId: Number(root.dataset.initialThreadId || 0) || null,
        sending: false,
        pollId: null,
    };

    const copy = {
        fetchError: root.dataset.fetchError || 'Admin messages could not be updated.',
        loadingThreads: root.dataset.loadingThreads || 'Loading conversations...',
        loadingMessages: root.dataset.loadingMessages || 'Loading messages...',
        selectedName: root.dataset.selectedName || 'Select conversation',
        selectedRole: root.dataset.selectedRole || 'Admin Messages',
        selectedStatus: root.dataset.selectedStatus || 'Offline',
        selectedInitials: root.dataset.selectedInitials || 'AM',
        threadEmptyTitle: root.dataset.threadEmptyTitle || 'No conversations yet.',
        threadEmptyText: root.dataset.threadEmptyText || 'Message threads will appear here.',
        noMatchText: root.dataset.noMatchText || 'No matching conversation found.',
        messageEmptyTitle: root.dataset.messageEmptyTitle || 'No messages yet.',
        messageEmptyText: root.dataset.messageEmptyText || 'Send the first message for this thread.',
        sendEmptyError: root.dataset.sendEmptyError || 'Type a message before sending.',
        previewFallback: root.dataset.previewFallback || 'No message yet',
        youLabel: root.dataset.youLabel || 'You',
    };

    const csrf = root.dataset.csrf || '';
    const list = root.querySelector('[data-thread-list]');
    const searchInput = root.querySelector('[data-thread-search]');
    const totalUnread = root.querySelector('[data-total-unread]');
    const messageList = root.querySelector('[data-message-list]');
    const selectedInitials = root.querySelector('[data-selected-initials]');
    const selectedName = root.querySelector('[data-selected-name]');
    const selectedRole = root.querySelector('[data-selected-role]');
    const selectedStatus = root.querySelector('[data-selected-status]');
    const form = root.querySelector('[data-message-form]');
    const textarea = root.querySelector('[data-message-input]');
    const characterCount = root.querySelector('[data-character-count]');
    const sendButton = root.querySelector('[data-send-button]');
    const emojiButton = root.querySelector('[data-emoji-button]');
    const errorMessage = root.querySelector('[data-error-message]');
    const mobileBack = root.querySelector('[data-mobile-back]');

    const templateUrl = (template, value) => template.replace(/__THREAD__|__MESSAGE__/g, value);

    const makeEl = (tag, className, text) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
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

        if (options.body && !headers['Content-Type']) {
            headers['Content-Type'] = 'application/json';
        }

        const response = await fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers,
        });
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(data.message || copy.fetchError);
        }

        return data;
    };

    const selectedThread = () => state.threads.find((thread) => Number(thread.id) === Number(state.selectedId));

    const setError = (message = '') => {
        if (!errorMessage) return;
        errorMessage.textContent = message;
        errorMessage.hidden = message === '';
    };

    const resizeTextarea = () => {
        textarea.style.height = 'auto';
        textarea.style.height = `${Math.min(textarea.scrollHeight, 130)}px`;
    };

    const setComposerEnabled = () => {
        const hasThread = Boolean(state.selectedId);
        const value = textarea.value || '';

        characterCount.textContent = String(value.length);
        sendButton.disabled = !hasThread || state.sending || value.trim().length === 0;
        sendButton.classList.toggle('is-loading', state.sending);
        textarea.disabled = !hasThread || state.sending;
        emojiButton.disabled = !hasThread || state.sending;
    };

    const insertMessage = (text) => {
        textarea.value = text;
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

    const contactNumber = () => {
        const raw = String(selectedThread()?.participant?.contact_number || '').trim();
        const digits = raw.replace(/\D/g, '');
        return digits.length >= 7 ? (raw.startsWith('+') ? '+' : '') + digits : '';
    };
    const contactButtons = root.querySelectorAll('[data-contact-action]');
    contactButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const number = contactNumber();
            if (number) window.location.href = `${button.dataset.contactAction}:${number}`;
        });
    });

    const updateSelectedHeader = () => {
        const thread = selectedThread();
        const participant = thread?.participant;
        contactButtons.forEach((button) => {
            button.disabled = !contactNumber();
            button.title = contactNumber()
                ? `${button.dataset.contactAction === 'tel' ? 'Call' : 'Send SMS to'} ${participant.name}`
                : 'Phone number is unavailable for this contact';
        });

        if (!participant) {
            selectedInitials.textContent = copy.selectedInitials;
            selectedName.textContent = copy.selectedName;
            selectedRole.textContent = copy.selectedRole;
            selectedStatus.textContent = copy.selectedStatus;
            return;
        }

        selectedInitials.textContent = participant.initials || copy.selectedInitials;
        selectedName.textContent = participant.name || copy.selectedName;
        selectedRole.textContent = participant.role_label || copy.selectedRole;
        selectedStatus.textContent = participant.status_text || copy.selectedStatus;
    };

    const renderThreads = () => {
        list.innerHTML = '';
        const query = (searchInput.value || '').trim().toLowerCase();
        let visible = 0;
        let unread = 0;

        state.threads.forEach((thread) => {
            const participant = thread.participant || {};
            const searchable = [
                participant.name || '',
                participant.role_label || '',
                participant.staff_id || '',
                participant.contact_number || '',
                thread.latest_message || '',
            ].join(' ').toLowerCase();
            const hidden = query !== '' && !searchable.includes(query);
            unread += Number(thread.unread_count || 0);

            const button = makeEl('button', `consultation-thread${Number(thread.id) === Number(state.selectedId) ? ' is-active' : ''}`);
            button.type = 'button';
            button.hidden = hidden;
            button.dataset.threadId = thread.id;

            const avatar = makeEl('span', 'consultation-avatar', participant.initials || copy.selectedInitials);
            const presence = makeEl('i', `consultation-presence${participant.online ? ' is-online' : ''}`);
            avatar.append(presence);

            const main = makeEl('span', 'consultation-thread-main');
            const top = makeEl('span', 'consultation-thread-top');
            top.append(makeEl('strong', 'consultation-thread-name', participant.name || copy.selectedName));
            const meta = makeEl('span', 'consultation-thread-meta');
            meta.append(makeEl('span', 'consultation-thread-role', participant.role_label || copy.selectedRole));
            meta.append(makeEl('span', 'consultation-thread-status', participant.status_text || copy.selectedStatus));
            main.append(top, meta, makeEl('span', 'consultation-thread-preview', thread.latest_message || copy.previewFallback));

            const side = makeEl('span', 'consultation-thread-side');
            side.append(makeEl('span', 'consultation-thread-time', thread.latest_message_time || ''));
            side.append(makeEl('span', `consultation-unread${Number(thread.unread_count || 0) === 0 ? ' is-zero' : ''}`, String(thread.unread_count || 0)));

            button.append(avatar, main, side);
            list.append(button);

            if (!hidden) visible += 1;
        });

        totalUnread.textContent = String(unread);

        if (state.threads.length === 0) {
            list.innerHTML = `<div class="consultation-empty"><strong>${copy.threadEmptyTitle}</strong><span>${copy.threadEmptyText}</span></div>`;
        } else if (visible === 0) {
            list.append(makeEl('div', 'consultation-loading', copy.noMatchText));
        }
    };

    const renderMessages = () => {
        messageList.innerHTML = '';

        if (!state.selectedId) {
            messageList.innerHTML = `<div class="consultation-empty"><strong>${copy.selectedName}</strong><span>${copy.threadEmptyText}</span></div>`;
            return;
        }

        if (state.messages.length === 0) {
            messageList.innerHTML = `<div class="consultation-empty"><strong>${copy.messageEmptyTitle}</strong><span>${copy.messageEmptyText}</span></div>`;
            return;
        }

        state.messages.forEach((message) => {
            const item = makeEl('article', `consultation-message ${message.is_own ? 'is-own' : 'is-other'} ${message.is_unsent ? 'is-unsent' : ''}`);
            if (!message.is_own) {
                item.append(makeEl('span', 'consultation-message-sender', message.sender_name));
            }
            const meta = makeEl('div', 'consultation-message-meta');
            meta.append(makeEl('strong', null, message.is_own ? copy.youLabel : message.sender_name));
            meta.append(makeEl('span', null, message.created_time || ''));

            const bubble = makeEl('div', 'consultation-bubble', message.message || '');
            item.append(meta, bubble);

            if (message.can_unsend) {
                const actions = makeEl('div', 'consultation-message-actions');
                const unsend = makeEl('button', 'consultation-unsend', 'Unsend');
                unsend.type = 'button';
                unsend.dataset.unsendMessage = message.id;
                actions.append(unsend);
                item.append(actions);
            }

            messageList.append(item);
        });

        messageList.scrollTop = messageList.scrollHeight;
    };

    const loadMessages = async (showLoading = true) => {
        if (!state.selectedId) {
            renderMessages();
            return;
        }

        if (showLoading) {
            messageList.innerHTML = `<div class="consultation-loading">${copy.loadingMessages}</div>`;
        }

        const data = await fetchJson(templateUrl(root.dataset.messagesUrlTemplate, state.selectedId));
        state.messages = data.messages || [];

        if (data.thread) {
            const index = state.threads.findIndex((thread) => Number(thread.id) === Number(data.thread.id));
            if (index >= 0) {
                state.threads[index] = data.thread;
            }
        }

        updateSelectedHeader();
        renderThreads();
        renderMessages();
    };

    const selectThread = async (threadId) => {
        state.selectedId = Number(threadId);
        root.classList.add('is-chat-open');
        updateSelectedHeader();
        renderThreads();
        setComposerEnabled();
        await loadMessages();
    };

    const loadThreads = async () => {
        const selected = state.selectedId ? `?selected=${encodeURIComponent(state.selectedId)}` : '';
        const data = await fetchJson(`${root.dataset.threadsUrl}${selected}`);
        state.threads = data.threads || [];

        if (!state.selectedId && data.selected_thread_id) {
            state.selectedId = Number(data.selected_thread_id);
        }

        if (state.selectedId && !state.threads.some((thread) => Number(thread.id) === Number(state.selectedId))) {
            state.selectedId = state.threads[0]?.id || null;
        }

        updateSelectedHeader();
        renderThreads();
        setComposerEnabled();

        if (state.selectedId) {
            await loadMessages(false);
        } else {
            renderMessages();
        }
    };

    const sendMessage = async () => {
        if (!state.selectedId || state.sending) return;

        const message = textarea.value.trim();
        if (message === '') {
            setError(copy.sendEmptyError);
            return;
        }

        state.sending = true;
        setComposerEnabled();
        setError('');

        try {
            await fetchJson(templateUrl(root.dataset.sendUrlTemplate, state.selectedId), {
                method: 'POST',
                body: JSON.stringify({ message }),
            });

            textarea.value = '';
            resizeTextarea();
            await loadMessages(false);
        } catch (error) {
            setError(error.message);
        } finally {
            state.sending = false;
            setComposerEnabled();
        }
    };

    const unsendMessage = async (messageId) => {
        if (!messageId) return;

        try {
            await fetchJson(templateUrl(root.dataset.unsendUrlTemplate, messageId), { method: 'POST' });
            await loadMessages(false);
        } catch (error) {
            setError(error.message);
        }
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        sendMessage();
    });

    textarea.addEventListener('input', () => {
        resizeTextarea();
        setComposerEnabled();
    });

    emojiButton.addEventListener('click', () => appendAtCursor(':)'));
    searchInput.addEventListener('input', renderThreads);

    list.addEventListener('click', (event) => {
        const button = event.target.closest('[data-thread-id]');
        if (button) selectThread(button.dataset.threadId);
    });

    messageList.addEventListener('click', (event) => {
        const button = event.target.closest('[data-unsend-message]');
        if (button) unsendMessage(button.dataset.unsendMessage);
    });

    root.querySelectorAll('[data-quick-text]').forEach((button) => {
        button.addEventListener('click', () => insertMessage(button.dataset.quickText || ''));
    });

    if (mobileBack) {
        mobileBack.addEventListener('click', () => root.classList.remove('is-chat-open'));
    }

    list.innerHTML = `<div class="consultation-loading">${copy.loadingThreads}</div>`;
    loadThreads().catch((error) => {
        list.innerHTML = '<div class="consultation-loading">Admin messages could not load.</div>';
        setError(error.message);
    });

    state.pollId = window.setInterval(() => {
        loadThreads().catch(() => {});
    }, 10000);

    window.addEventListener('beforeunload', () => {
        if (state.pollId) window.clearInterval(state.pollId);
    });
})();
