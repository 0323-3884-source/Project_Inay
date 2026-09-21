(() => {
    const dialog = document.getElementById('document-preview-dialog');
    if (!dialog) return;
    const content = dialog.querySelector('[data-document-content]');
    const status = dialog.querySelector('[data-document-status]');
    let controller, objectUrl, trigger;
    const clear = () => {
        controller?.abort();
        content.replaceChildren();
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = null;
    };
    document.querySelectorAll('[data-document-card]').forEach(card => {
        card.addEventListener('click', async () => {
            clear();
            trigger = card;
            dialog.querySelector('#document-preview-title').textContent = card.dataset.name;
            dialog.querySelector('[data-document-details]').textContent = card.dataset.details;
            dialog.querySelector('[data-document-download]').href = card.dataset.download;
            status.textContent = 'Loading preview…';
            dialog.showModal();
            document.body.classList.add('has-document-preview');
            controller = new AbortController();
            const request = controller;
            try {
                const response = await fetch(card.dataset.preview, { signal: request.signal, credentials: 'same-origin', headers: { Accept: 'application/pdf, image/*' } });
                if (request.signal.aborted) return;
                if (response.status === 415) {
                    status.textContent = 'Preview is not available for this file type. Download the file to open it.';
                    return;
                }
                if (!response.ok) throw new Error('Preview unavailable');
                const blob = await response.blob();
                if (request.signal.aborted) return;
                const isImage = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(blob.type);
                if (!isImage && blob.type !== 'application/pdf') throw new Error('Unsupported preview');
                objectUrl = URL.createObjectURL(blob);
                const preview = document.createElement(isImage ? 'img' : 'iframe');
                if (isImage) preview.alt = card.dataset.name;
                else preview.title = 'PDF preview: ' + card.dataset.name;
                preview.src = objectUrl;
                preview.onerror = () => { status.textContent = 'Unable to display the preview. Download the file to open it.'; };
                content.appendChild(preview);
                status.textContent = isImage ? '' : 'If the PDF does not display in your browser, use Download file.';
            } catch (error) {
                if (!request.signal.aborted) status.textContent = 'Unable to load this preview. Try again or download the file.';
            }
        });
    });
    dialog.querySelector('[data-document-close]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => {
        if (event.target !== dialog) return;
        const bounds = dialog.getBoundingClientRect();
        if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
    });
    dialog.addEventListener('close', () => {
        clear();
        document.body.classList.remove('has-document-preview');
        trigger?.focus();
    });
    window.addEventListener('pagehide', clear);
})();
