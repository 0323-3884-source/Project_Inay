(() => {
    const root = document.querySelector('[data-clinic-schedule-root]');
    if (!root || root.dataset.clinicScheduleBooted === 'true') return;
    root.dataset.clinicScheduleBooted = 'true';

    const body = document.body;

    const openModal = (modal) => {
        if (!modal) return;
        modal.hidden = false;
        body.classList.add('has-clinic-modal');
        const firstField = modal.querySelector('input:not([type="hidden"]), select, textarea, button');
        firstField?.focus();
    };

    const closeModal = (modal) => {
        if (!modal) return;
        modal.hidden = true;
        body.classList.remove('has-clinic-modal');
    };

    const appointmentModal = root.querySelector('[data-appointment-modal]');
    const appointmentForm = root.querySelector('[data-appointment-form]');
    const methodInput = appointmentForm?.querySelector('[data-method-input]');
    const statusRow = appointmentForm?.querySelector('[data-status-row]');
    const statusField = appointmentForm?.querySelector('[name="status"]');
    const modalTitle = appointmentModal?.querySelector('[data-appointment-modal-title]');

    const setField = (name, value = '') => {
        const field = appointmentForm?.querySelector(`[name="${name}"]`);
        if (field) field.value = value ?? '';
    };

    const resetAppointmentForm = () => {
        if (!appointmentForm) return;
        appointmentForm.reset();
        appointmentForm.action = appointmentForm.dataset.storeUrl;
        if (methodInput) {
            methodInput.disabled = true;
            methodInput.value = 'PATCH';
        }
        if (statusRow) statusRow.hidden = true;
        if (statusField) {
            statusField.disabled = true;
            statusField.value = 'pending';
        }
        if (modalTitle) modalTitle.textContent = 'Create Appointment';
        setField('conversation_id', '');
    };

    root.querySelectorAll('[data-open-appointment-modal]').forEach((button) => {
        button.addEventListener('click', () => {
            resetAppointmentForm();
            openModal(appointmentModal);
        });
    });

    root.querySelectorAll('[data-edit-appointment]').forEach((button) => {
        button.addEventListener('click', () => {
            resetAppointmentForm();
            if (!appointmentForm) return;

            appointmentForm.action = button.dataset.updateUrl;
            if (methodInput) methodInput.disabled = false;
            if (statusRow) statusRow.hidden = false;
            if (statusField) statusField.disabled = false;
            if (modalTitle) modalTitle.textContent = 'Edit Appointment';

            [
                'mother_id',
                'conversation_id',
                'appointment_type',
                'meeting_type',
                'appointment_date',
                'start_time',
                'end_time',
                'location',
                'notes',
                'status',
            ].forEach((name) => {
                const key = name.replace(/_([a-z])/g, (_, letter) => letter.toUpperCase());
                setField(name, button.dataset[key] || '');
            });

            openModal(appointmentModal);
        });
    });

    const detailsModal = root.querySelector('[data-details-modal]');
    root.querySelectorAll('[data-open-details]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!detailsModal) return;
            detailsModal.querySelectorAll('[data-detail]').forEach((node) => {
                node.textContent = button.dataset[node.dataset.detail] || 'Not provided';
            });
            openModal(detailsModal);
        });
    });

    const rescheduleModal = root.querySelector('[data-reschedule-modal]');
    const rescheduleForm = root.querySelector('[data-reschedule-form]');
    root.querySelectorAll('[data-open-reschedule]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!rescheduleForm) return;
            rescheduleForm.action = button.dataset.rescheduleUrl;
            const title = rescheduleModal?.querySelector('[data-reschedule-title]');
            if (title) title.textContent = button.dataset.rescheduleTitle || 'Request Reschedule';
            openModal(rescheduleModal);
        });
    });

    root.querySelectorAll('[data-decline-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const input = form.querySelector('[name="decline_reason"]');
            if (!input || input.value) return;
            const reason = window.prompt('Optional: add a reason for declining this appointment.');
            if (reason === null) {
                event.preventDefault();
                return;
            }
            input.value = reason.trim();
        });
    });

    root.querySelectorAll('[data-clinic-modal-close], .clinic-backdrop').forEach((button) => {
        button.addEventListener('click', () => closeModal(button.closest('[data-clinic-modal]')));
    });

    root.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        root.querySelectorAll('[data-clinic-modal]:not([hidden])').forEach(closeModal);
    });

    root.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', () => {
            form.querySelectorAll('button[type="submit"]').forEach((button) => {
                button.disabled = true;
                button.dataset.originalText = button.textContent;
                button.textContent = 'Saving...';
            });
        });
    });
})();
