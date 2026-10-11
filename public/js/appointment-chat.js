(() => {
    const root = document.querySelector('[data-mother-appointments]');
    const dialog = root?.querySelector('[data-appointment-chat]');
    if (!dialog || dialog.dataset.booted) return;
    dialog.dataset.booted = 'true';
    const doctors = JSON.parse(root.querySelector('[data-doctor-json]').textContent);
    const form = dialog.querySelector('[data-chat-form]');
    const input = form.elements.message;
    const send = form.querySelector('button');
    const messages = dialog.querySelector('[data-chat-messages]');
    const status = dialog.querySelector('[data-chat-status]');
    let conversationId = null;
    let version = 0;
    let timer;
    let sending = false;
    let signature = '';
    const url = (id) => root.dataset.chatMessagesUrl.replace('__CONVERSATION__', id);
    const request = async (endpoint, options = {}) => {
        const response = await fetch(endpoint, {
            credentials: 'same-origin', ...options,
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': root.dataset.csrf, ...options.headers },
        });
        const data = await response.json().catch(() => {
            throw new Error('Chat could not be loaded. Please refresh the page and try again.');
        });
        if (!response.ok) throw new Error(data.message || 'Chat could not be loaded. Please try again.');
        return data;
    };
    const render = (items) => {
        const nextSignature = JSON.stringify(items);
        if (signature === nextSignature) return;
        const atBottom = messages.scrollHeight - messages.scrollTop - messages.clientHeight < 60;
        const initial = !signature;
        signature = nextSignature;
        messages.replaceChildren(...items.map((item) => {
            const bubble = document.createElement('div');
            bubble.className = `appointment-chat-bubble${item.is_own ? ' is-own' : ''}`;
            const text = document.createElement('p');
            text.textContent = item.message || '';
            bubble.append(text);
            if (item.attachment_url) {
                const attachment = document.createElement('a');
                attachment.href = item.attachment_url;
                attachment.target = '_blank';
                attachment.rel = 'noopener';
                attachment.textContent = item.attachment_name || 'View attachment';
                bubble.append(attachment);
            }
            const time = document.createElement('small');
            time.textContent = item.created_label || item.created_time || '';
            bubble.append(time);
            return bubble;
        }));
        if (initial || atBottom) messages.scrollTop = messages.scrollHeight;
    };
    const load = async (currentVersion) => {
        const data = await request(url(conversationId));
        if (currentVersion !== version || !dialog.open) return;
        render(data.messages || []);
        status.textContent = data.messages?.length ? '' : 'No messages yet. Start your conversation below.';
    };
    const open = async (doctor) => {
        clearInterval(timer);
        const currentVersion = ++version;
        conversationId = null;
        signature = '';
        sending = false;
        input.value = '';
        input.disabled = send.disabled = true;
        messages.replaceChildren();
        dialog.querySelector('[data-chat-name]').textContent = doctor.name;
        dialog.querySelector('[data-chat-role]').textContent = doctor.role || 'Healthcare worker';
        status.textContent = 'Loading conversation…';
        if (!dialog.open) dialog.showModal();
        try {
            const data = await request(root.dataset.chatConversationsUrl);
            if (currentVersion !== version || !dialog.open) return;
            const conversation = data.conversations?.find((item) => item.participant?.role === 'program_staff' && String(item.participant.id) === String(doctor.id));
            if (!conversation) {
                status.textContent = 'Book an appointment with this healthcare worker first to start your conversation.';
                return;
            }
            conversationId = conversation.id;
            await load(currentVersion);
            if (currentVersion !== version || !dialog.open) return;
            input.disabled = send.disabled = false;
            input.focus();
            timer = setInterval(() => {
                if (!sending) load(currentVersion).catch((error) => {
                    if (currentVersion === version) status.textContent = error.message;
                });
            }, 5000);
        } catch (error) {
            if (currentVersion === version) status.textContent = error.message;
        }
    };
    root.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-open-chat], [data-detail-chat]');
        if (!trigger) return;
        const id = trigger.dataset.openChat || new URL(trigger.href, location.href).searchParams.get('staff');
        const doctor = doctors.find((item) => String(item.id) === String(id));
        if (!doctor) return;
        event.preventDefault();
        open(doctor);
    });
    dialog.querySelector('[data-chat-close]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (event) => {
        const rect = dialog.getBoundingClientRect();
        if (event.target === dialog && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)) dialog.close();
    });
    dialog.addEventListener('close', () => { ++version; clearInterval(timer); conversationId = null; });
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const message = input.value.trim();
        if (!message || !conversationId || sending) return;
        const currentVersion = version;
        sending = true;
        send.disabled = input.disabled = true;
        status.textContent = 'Sending…';
        try {
            await request(url(conversationId), { method: 'POST', body: JSON.stringify({ message, message_type: 'text' }) });
            if (currentVersion !== version) return;
            input.value = '';
            await load(currentVersion);
        } catch (error) {
            if (currentVersion === version) status.textContent = error.message;
        } finally {
            if (currentVersion === version && dialog.open) {
                sending = false;
                send.disabled = input.disabled = false;
                input.focus();
            }
        }
    });
})();
