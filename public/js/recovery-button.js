(() => {
    document.querySelectorAll('[data-recovery-form]').forEach((form) => {
        const button = form.querySelector('button[type="submit"]');
        const password = form.querySelector('[name="password"]');
        const confirmation = form.querySelector('[name="password_confirmation"]');
        const fields = Array.from(form.querySelectorAll('input:not([type="hidden"]), select'));

        const updateButton = () => {
            if (confirmation && password) {
                confirmation.setCustomValidity(confirmation.value && confirmation.value !== password.value
                    ? 'Please enter the same password in both fields.' : '');
            }
            const ready = fields.every((field) => field.validity.valid &&
                (!field.required || field.value.trim() !== '') &&
                (!(field.minLength > 0) || field.value.length >= field.minLength));
            button?.classList.toggle('is-ready', ready);
        };

        form.addEventListener('input', updateButton);
        form.addEventListener('change', updateButton);
        form.addEventListener('focusin', updateButton);
        window.addEventListener('pageshow', updateButton);
        form.addEventListener('submit', (event) => {
            updateButton();
            if (!form.reportValidity()) event.preventDefault();
        });
        updateButton();
    });
})();
