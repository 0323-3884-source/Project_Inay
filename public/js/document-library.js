(() => {
    document.querySelectorAll('[data-document-library]').forEach(library => {
        const search = library.querySelector('[data-document-search]');
        const month = library.querySelector('[data-document-month]');
        const type = library.querySelector('[data-document-type]');
        const items = [...library.querySelectorAll('[data-document-item]')];
        const filter = () => {
            let count = 0;
            items.forEach(item => {
                item.hidden = !item.dataset.search.toLowerCase().includes(search.value.trim().toLowerCase())
                    || (month.value !== '' && item.dataset.month !== month.value)
                    || (type.value !== '' && item.dataset.type !== type.value);
                if (!item.hidden) count++;
            });
            library.querySelector('[data-document-count]').textContent = `${count} of ${items.length} documents · Newest first`;
            library.querySelector('[data-document-empty]').hidden = count !== 0;
        };
        search.addEventListener('input', filter);
        month.addEventListener('change', filter);
        type.addEventListener('change', filter);
    });
    const dialog = document.getElementById('document-upload-dialog');
    if (!dialog) return;
    const open = document.querySelector('[data-upload-open]');
    open.addEventListener('click', () => dialog.showModal());
    dialog.querySelectorAll('[data-upload-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('close', () => open.focus());
    if (dialog.dataset.hasErrors === 'true') dialog.showModal();
    const form = dialog.querySelector('form');
    const file = form.elements.document;
    file.addEventListener('change', () => {
        file.setCustomValidity(file.files[0]?.size > 5 * 1024 * 1024 ? 'Choose a file smaller than 5 MB.' : '');
        file.reportValidity();
    });
    form.addEventListener('submit', event => {
        const status = dialog.querySelector('[data-upload-status]');
        if (!navigator.onLine) {
            event.preventDefault();
            status.textContent = 'Reconnect to the internet to upload your document.';
            return;
        }
        status.textContent = 'Uploading your document… Please keep this page open.';
        form.querySelector('[type="submit"]').disabled = true;
    });
})();
