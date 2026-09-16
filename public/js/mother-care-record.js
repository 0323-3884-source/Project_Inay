(() => {
    const buttons = [...document.querySelectorAll('[data-casefile-record]')];
    const error = document.querySelector('[data-record-error]');
    const ready = document.querySelector('[data-record-ready]');
    const open = document.querySelector('[data-record-open]');
    const status = document.querySelector('[data-record-status]');
    let busy = false;
    let objectUrl = null;
    let frame = null;

    const clearDocument = () => {
        frame?.remove();
        frame = null;
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = null;
    };

    const printDocument = (url) => new Promise((resolve, reject) => {
        frame = document.createElement('iframe');
        frame.title = 'Printable care record';
        // Keep the PDF renderer measurable without changing the dashboard layout.
        frame.style.cssText = 'position:fixed;left:-10000px;top:0;width:800px;height:1100px;border:0';
        frame.setAttribute('aria-hidden', 'true');
        const timer = setTimeout(() => reject(new Error('Print preview timed out')), 30000);
        frame.onload = () => {
            clearTimeout(timer);
            try {
                frame.contentWindow.focus();
                frame.contentWindow.print();
                resolve();
            } catch (failure) {
                reject(failure);
            }
        };
        frame.onerror = () => {
            clearTimeout(timer);
            reject(new Error('Print preview failed'));
        };
        frame.src = url;
        document.body.appendChild(frame);
    });

    buttons.forEach((button) => {
        button.addEventListener('click', async () => {
            if (busy) return;
            busy = true;
            const original = button.innerHTML;
            buttons.forEach((item) => { item.disabled = true; });
            button.setAttribute('aria-busy', 'true');
            button.textContent = button.dataset.casefileRecord === 'pdf' ? 'Generating PDF...' : 'Generating record...';
            error.hidden = true;
            ready.hidden = true;
            clearDocument();
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 120000);

            try {
                const activeTab = document.querySelector('[data-casefile-tab][aria-selected="true"]');
                const recordUrl = button.dataset.recordUrl + (activeTab ? '?section=' + encodeURIComponent(activeTab.dataset.casefileTab) : '');
                const response = await fetch(recordUrl, {
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: { Accept: 'application/pdf', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: controller.signal,
                });
                if (!response.ok || !response.headers.get('Content-Type')?.includes('application/pdf')) {
                    throw new Error('Generation failed');
                }
                const blob = await response.blob();
                if (await blob.slice(0, 5).text() !== '%PDF-') throw new Error('Invalid document');
                clearTimeout(timeout);
                objectUrl = URL.createObjectURL(blob);
                open.href = objectUrl;
                ready.hidden = false;
                status.textContent = 'Record generated.';

                if (button.dataset.casefileRecord === 'pdf') {
                    const link = document.createElement('a');
                    link.href = objectUrl;
                    link.download = response.headers.get('Content-Disposition')?.match(/filename="([^"]+)"/)?.[1] || 'mother-care-record.pdf';
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                } else if (navigator.pdfViewerEnabled === false) {
                    status.textContent = 'Record ready. Open the generated record to print from your PDF viewer.';
                } else {
                    await printDocument(objectUrl);
                }
            } catch (failure) {
                error.textContent = 'Unable to generate the ' + (button.dataset.recordKind || 'mother') + ' record. Please try again.';
                error.hidden = false;
            } finally {
                clearTimeout(timeout);
                button.innerHTML = original;
                button.removeAttribute('aria-busy');
                buttons.forEach((item) => { item.disabled = false; });
                busy = false;
            }
        });
    });

    window.addEventListener('pagehide', clearDocument);
})();
