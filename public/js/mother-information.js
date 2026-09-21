(() => {
    const button = document.querySelector('[data-mother-edit]');
    const form = document.getElementById('mother-information-form');
    const dialog = document.getElementById('mother-information-dialog');
    if (!button || !form || !dialog) return;

    const close = () => {
        dialog.close();
    };

    const open = () => {
        if (dialog.open) return;
        dialog.showModal();
        document.body.classList.add('has-mother-information-dialog');
        button.setAttribute('aria-expanded', 'true');
        form.querySelector('input:not([type="hidden"])')?.focus();
    };

    button.addEventListener('click', open);
    dialog.addEventListener('close', () => {
        document.body.classList.remove('has-mother-information-dialog');
        button.setAttribute('aria-expanded', 'false');
        button.focus();
    });
    form.querySelector('[data-mother-edit-close]').addEventListener('click', close);
    form.querySelector('[data-mother-edit-cancel]').addEventListener('click', () => {
        form.reset();
        close();
    });
    dialog.addEventListener('click', (event) => {
        if (event.target !== dialog) return;
        const bounds = dialog.getBoundingClientRect();
        if (event.clientX < bounds.left || event.clientX > bounds.right ||
            event.clientY < bounds.top || event.clientY > bounds.bottom) close();
    });

    if (dialog.dataset.reopen === 'true') open();
})();
