(() => {
    const root = document.querySelector('[data-global-call-root]');
    if (!root || root.dataset.globalCallBooted === 'true') return;
    root.dataset.globalCallBooted = 'true';

    const incomingUrl = root.dataset.incomingUrl || '';
    const updateTemplate = root.dataset.callUpdateUrlTemplate || '';
    const consultationUrl = root.dataset.consultationUrl || '';
    const csrf = root.dataset.csrf || '';

    if (!incomingUrl || !updateTemplate || !consultationUrl) return;

    const state = {
        activeCall: null,
        busy: false,
        pollId: null,
        declinedIds: new Set(JSON.parse(sessionStorage.getItem('projectInayDeclinedCalls') || '[]')),
    };

    const style = document.createElement('style');
    style.textContent = `
        .global-call-root {
            position: fixed;
            top: 92px;
            right: 22px;
            z-index: 160;
            width: min(390px, calc(100vw - 28px));
            pointer-events: none;
        }

        .global-call-card {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 14px;
            padding: 16px;
            color: #071127;
            background: #ffffff;
            border: 1px solid #f9c4df;
            border-left: 4px solid #ec008c;
            border-radius: 10px;
            box-shadow: 0 18px 44px rgba(15, 23, 42, 0.18);
            pointer-events: auto;
        }

        .global-call-avatar {
            display: grid;
            width: 52px;
            height: 52px;
            place-items: center;
            color: #ffffff;
            background: #ec008c;
            border-radius: 999px;
            font-size: 17px;
            font-weight: 900;
        }

        .global-call-body {
            min-width: 0;
        }

        .global-call-label {
            margin: 0 0 4px;
            color: #be185d;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .global-call-title {
            margin: 0;
            color: #071127;
            font-size: 17px;
            font-weight: 900;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        .global-call-meta {
            margin: 4px 0 0;
            color: #52627d;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.4;
        }

        .global-call-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 13px;
        }

        .global-call-actions button {
            display: inline-flex;
            min-height: 40px;
            align-items: center;
            justify-content: center;
            border: 1px solid #cbd8ea;
            border-radius: 8px;
            padding: 0 14px;
            font-weight: 900;
            cursor: pointer;
            transition: background 160ms ease, border-color 160ms ease, color 160ms ease, transform 160ms ease;
        }

        .global-call-actions button:hover {
            transform: translateY(-1px);
        }

        .global-call-answer {
            color: #ffffff;
            background: #ec008c;
            border-color: #ec008c;
        }

        .global-call-decline {
            color: #be123c;
            background: #fff1f2;
            border-color: #fecdd3;
        }

        @media (max-width: 640px) {
            .global-call-root {
                top: auto;
                right: 12px;
                bottom: 86px;
                left: 12px;
                width: auto;
            }

            .global-call-card {
                grid-template-columns: 1fr;
            }

            .global-call-avatar {
                width: 46px;
                height: 46px;
            }

            .global-call-actions button {
                flex: 1 1 130px;
            }
        }
    `;
    document.head.append(style);
    root.classList.add('global-call-root');

    const templateUrl = (template, value) => template.replace('__CALL__', value);

    const fetchJson = async (url, options = {}) => {
        const method = (options.method || 'GET').toUpperCase();
        const headers = {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(options.headers || {}),
        };

        if (method !== 'GET') {
            headers['Content-Type'] = 'application/json';
            headers['X-CSRF-TOKEN'] = csrf;
        }

        const response = await fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers,
        });
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            const error = new Error(data.message || 'Call request failed.');
            error.status = response.status;
            throw error;
        }

        return data;
    };

    const saveDeclinedIds = () => {
        sessionStorage.setItem('projectInayDeclinedCalls', JSON.stringify([...state.declinedIds].slice(-20)));
    };

    const callTypeLabel = (call) => call?.call_type === 'voice' ? 'voice call' : 'video call';

    const consultationHref = (call) => {
        const url = new URL(consultationUrl, window.location.origin);
        url.searchParams.set('conversation', call.conversation_id);
        url.searchParams.set('incoming_call', call.id);
        return url.toString();
    };

    const hidePrompt = () => {
        state.activeCall = null;
        root.replaceChildren();
    };

    const declineCall = async () => {
        const call = state.activeCall;
        if (!call) return;

        state.declinedIds.add(Number(call.id));
        saveDeclinedIds();
        hidePrompt();

        try {
            await fetchJson(templateUrl(updateTemplate, call.id), {
                method: 'PATCH',
                body: JSON.stringify({ action: 'decline' }),
            });
        } catch (error) {
            // Keep the global prompt quiet if the call has already ended elsewhere.
        }
    };

    const openConsultation = () => {
        if (!state.activeCall) return;
        window.location.href = consultationHref(state.activeCall);
    };

    const renderPrompt = (call) => {
        const caller = call.caller || {};
        const callerName = caller.name || 'Consultation participant';
        const initials = caller.initials || callerName.split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0]?.toUpperCase()).join('') || 'IN';

        root.replaceChildren();

        const card = document.createElement('section');
        card.className = 'global-call-card';
        card.setAttribute('role', 'dialog');
        card.setAttribute('aria-live', 'assertive');
        card.setAttribute('aria-label', 'Incoming consultation call');

        const avatar = document.createElement('span');
        avatar.className = 'global-call-avatar';
        avatar.textContent = initials;

        const body = document.createElement('div');
        body.className = 'global-call-body';

        const label = document.createElement('p');
        label.className = 'global-call-label';
        label.textContent = `Incoming ${callTypeLabel(call)}`;

        const title = document.createElement('h2');
        title.className = 'global-call-title';
        title.textContent = `${callerName} is calling`;

        const meta = document.createElement('p');
        meta.className = 'global-call-meta';
        meta.textContent = 'Open the consultation workspace to answer securely.';

        const actions = document.createElement('div');
        actions.className = 'global-call-actions';

        const answer = document.createElement('button');
        answer.type = 'button';
        answer.className = 'global-call-answer';
        answer.textContent = 'Answer';
        answer.addEventListener('click', openConsultation);

        const decline = document.createElement('button');
        decline.type = 'button';
        decline.className = 'global-call-decline';
        decline.textContent = 'Decline';
        decline.addEventListener('click', declineCall);

        actions.append(answer, decline);
        body.append(label, title, meta, actions);
        card.append(avatar, body);
        root.append(card);
    };

    const pollIncoming = async () => {
        if (state.busy) return;
        state.busy = true;

        try {
            const data = await fetchJson(incomingUrl);
            const incoming = (data.calls || []).find((call) => !state.declinedIds.has(Number(call.id)));

            if (!incoming) {
                hidePrompt();
                return;
            }

            if (!state.activeCall || Number(state.activeCall.id) !== Number(incoming.id)) {
                state.activeCall = incoming;
                renderPrompt(incoming);
            }
        } catch (error) {
            if (error.status === 401 || error.status === 403) {
                stopPolling();
            }
        } finally {
            state.busy = false;
        }
    };

    function startPolling() {
        if (state.pollId || document.hidden) return;
        pollIncoming();
        state.pollId = window.setInterval(pollIncoming, 1400);
    }

    function stopPolling() {
        if (!state.pollId) return;
        window.clearInterval(state.pollId);
        state.pollId = null;
    }

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopPolling();
            return;
        }

        startPolling();
    });

    startPolling();
})();
