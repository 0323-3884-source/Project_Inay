(() => {
    const dialog = document.getElementById('dswd-document-dialog');
    if (!dialog) return;
    const status = document.getElementById('dswd-preview-status');
    const content = document.getElementById('dswd-preview-content');
    const openLink = document.getElementById('dswd-preview-open');
    let timer, trigger;
    document.getElementById('dswd-month-select').addEventListener('change', event => {
        document.querySelectorAll('[data-dswd-month]').forEach(panel => {
            panel.hidden = panel.dataset.dswdMonth !== event.target.value;
        });
    });
    const clearPreview = () => {
        clearTimeout(timer);
        content.replaceChildren();
        openLink.hidden = true;
        openLink.removeAttribute('href');
    };
    document.querySelectorAll('[data-dswd-preview]').forEach(link => {
        link.addEventListener('click', event => {
            event.preventDefault();
            clearPreview();
            trigger = link;
            document.getElementById('dswd-document-title').textContent = link.dataset.title;
            status.textContent = 'Opening document…';
            status.hidden = false;
            openLink.href = link.href;
            openLink.hidden = false;
            dialog.showModal();
            document.body.classList.add('dswd-preview-is-open');
            // Use the authenticated URL directly so the browser can stream PDFs
            // and request byte ranges without waiting for a complete JS blob.
            const preview = document.createElement('iframe');
            preview.title = link.dataset.title;
            preview.addEventListener('load', () => {
                if (!dialog.open || !preview.isConnected) return;
                clearTimeout(timer);
                // An HTML response here is a login/error page, not a document.
                try {
                    if (preview.contentDocument?.contentType === 'text/html') {
                        status.textContent = 'Unable to preview this document. Open it in a new tab to check access or sign in again.';
                        return;
                    }
                } catch (_) { /* The browser PDF viewer owns its own document. */ }
                status.hidden = true;
            });
            preview.addEventListener('error', () => {
                clearTimeout(timer);
                status.hidden = false;
                status.textContent = 'Preview unavailable. Try opening the document in a new tab.';
            });
            preview.src = link.href;
            content.append(preview);
            timer = setTimeout(() => {
                status.hidden = false;
                status.textContent = 'Taking longer than expected? Use “Open document in a new tab” below.';
            }, 12000);
        });
    });
    document.getElementById('dswd-preview-close').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => {
        const bounds = dialog.getBoundingClientRect();
        if (event.target === dialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) dialog.close();
    });
    dialog.addEventListener('close', () => {
        clearPreview();
        document.body.classList.remove('dswd-preview-is-open');
        trigger?.focus();
    });
})();
