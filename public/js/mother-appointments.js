(() => {
    const root = document.querySelector('[data-mother-appointments]');
    if (!root || root.dataset.booted === 'true') return;
    root.dataset.booted = 'true';

    const doctors = JSON.parse(root.querySelector('[data-doctor-json]')?.textContent || '[]');
    let activeAppointment = JSON.parse(root.querySelector('[data-active-appointment-json]')?.textContent || 'null');
    const doctorById = new Map(doctors.map((doctor) => [String(doctor.id), doctor]));
    const modal = root.querySelector('[data-booking-modal]');
    const filterModal = root.querySelector('[data-filter-modal]');
    const detailModal = root.querySelector('[data-detail-modal]');
    const form = root.querySelector('[data-booking-form]');
    const filterForm = root.querySelector('[data-filter-form]');
    const alertBox = root.querySelector('[data-appointment-alert]');
    const errorBox = root.querySelector('[data-booking-error]');
    const monthLabel = root.querySelector('[data-calendar-month]');
    const calendarGrid = root.querySelector('[data-calendar-grid]');
    const slotGrid = root.querySelector('[data-time-slots]');
    const dateLabel = root.querySelector('[data-selected-date-label]');
    const doctorLabel = root.querySelector('[data-booking-doctor]');
    const submitButton = root.querySelector('[data-booking-submit]');
    const availabilityInput = root.querySelector('[data-booking-availability]');
    const dateInput = root.querySelector('[data-booking-date]');
    const typeInput = root.querySelector('[data-booking-type]');
    const meetingInput = root.querySelector('[data-booking-meeting]');
    const barangayInput = root.querySelector('[data-barangay-value]');
    const barangaySearch = root.querySelector('[data-barangay-search]');
    const barangayOptions = [...root.querySelectorAll('[data-barangay-option]')];
    const filterEmpty = root.querySelector('[data-filter-empty]');
    const detailPhoto = root.querySelector('[data-detail-photo]');
    const detailInitials = root.querySelector('[data-detail-initials]');
    const detailName = root.querySelector('[data-detail-name]');
    const detailRole = root.querySelector('[data-detail-role]');
    const detailLocation = root.querySelector('[data-detail-location]');
    const detailSchedule = root.querySelector('[data-detail-schedule]');
    const detailExperience = root.querySelector('[data-detail-experience]');
    const detailSpecialties = root.querySelector('[data-detail-specialties]');
    const detailBook = root.querySelector('[data-detail-book]');
    const detailChat = root.querySelector('[data-detail-chat]');
    const activeNotice = root.querySelector('[data-active-appointment-notice]');
    const activeStatusBadge = root.querySelector('[data-active-status-badge]');
    const activeDoctor = root.querySelector('[data-active-doctor]');
    const activeDateTime = root.querySelector('[data-active-date-time]');
    const activeViewButton = root.querySelector('[data-view-active-appointment]');
    const activeCancelForm = root.querySelector('[data-active-cancel-form]');

    const state = {
        doctor: null,
        selectedDate: null,
        visibleMonth: null,
        selectedAvailability: null,
        submitting: false,
        detailDoctor: null,
    };

    let calendarRequest;
    let calendarVersion = 0;
    let returnFocus;
    const today = () => parseDate(root.dataset.today);

    const parseDate = (value) => {
        const [year, month, day] = String(value).split('-').map(Number);
        return new Date(year, month - 1, day);
    };

    const dateValue = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    const isoWeekday = (date) => {
        const day = date.getDay();
        return day === 0 ? 7 : day;
    };

    const sameDay = (left, right) => dateValue(left) === dateValue(right);

    const formatMonth = (date) => date.toLocaleDateString('en-US', {
        month: 'long',
        year: 'numeric',
    });

    const formatSelectedDate = (date) => {
        const weekday = date.toLocaleDateString('en-US', { weekday: 'short' });
        const month = date.toLocaleDateString('en-US', { month: 'long' });
        return `${weekday}, ${date.getDate()} ${month} ${date.getFullYear()}`;
    };

    const setError = (message = '') => {
        if (!errorBox) return;
        errorBox.textContent = message;
        errorBox.hidden = message === '';
    };

    const setAlert = (message, isError = false) => {
        if (!alertBox) return;
        alertBox.textContent = message;
        alertBox.hidden = false;
        alertBox.classList.toggle('is-error', isError);
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    const normalize = (value) => String(value || '').trim().toLowerCase();

    const statusToneClasses = ['is-waiting', 'is-confirmed', 'is-cancelled', 'is-completed', 'is-neutral'];

    const statusFromAppointment = (appointment) => {
        if (!appointment) return null;

        return {
            id: appointment.id,
            status: appointment.status,
            label: appointment.status_display || appointment.status_label || 'Appointment',
            tone: appointment.status_tone || 'neutral',
            date_time_label: appointment.date_time_label || `${appointment.date_label || ''} ${appointment.time_label || ''}`.trim(),
            is_active: true,
        };
    };

    const setStatusBadge = (badge, status) => {
        if (!badge) return;

        statusToneClasses.forEach((className) => badge.classList.remove(className));

        if (!status) {
            badge.hidden = true;
            badge.textContent = '';
            badge.classList.add('is-neutral');
            return;
        }

        badge.hidden = false;
        badge.textContent = status.label || 'Appointment';
        badge.classList.add(`is-${status.tone || 'neutral'}`);
    };

    const doctorHasSlots = (doctor) => Array.isArray(doctor?.availabilities) && doctor.availabilities.length > 0;

    const buttonStateForDoctor = (doctor) => {
        if (!doctorHasSlots(doctor)) {
            return { disabled: true, label: 'No Slots', className: '' };
        }

        if (!activeAppointment) {
            return { disabled: false, label: 'Book Now', className: '' };
        }

        const isActiveDoctor = String(activeAppointment.staff_id) === String(doctor.id);

        if (isActiveDoctor && activeAppointment.status === 'pending') {
            return { disabled: true, label: 'Appointment Pending', className: 'is-pending' };
        }

        if (isActiveDoctor && ['confirmed', 'rescheduled', 'reschedule_requested'].includes(activeAppointment.status)) {
            return { disabled: true, label: 'Appointment Confirmed', className: 'is-confirmed' };
        }

        return { disabled: true, label: 'Active Appointment', className: 'is-locked' };
    };

    const setBookButtonState = (button, stateValue) => {
        if (!button || !stateValue) return;

        button.disabled = stateValue.disabled;
        button.textContent = stateValue.label;
        button.classList.remove('is-pending', 'is-confirmed', 'is-locked');
        if (stateValue.className) button.classList.add(stateValue.className);
    };

    const currentStatusForDoctor = (doctor) => {
        if (activeAppointment && String(activeAppointment.staff_id) === String(doctor.id)) {
            return statusFromAppointment(activeAppointment);
        }

        return doctor.appointment_status || null;
    };

    const updateDoctorCards = () => {
        root.querySelectorAll('[data-doctor-card]').forEach((card) => {
            const doctor = doctorById.get(String(card.dataset.doctorId));
            if (!doctor) return;

            const isActiveDoctor = activeAppointment && String(activeAppointment.staff_id) === String(doctor.id);
            card.classList.toggle('is-active-appointment', Boolean(isActiveDoctor));
            setStatusBadge(card.querySelector('[data-doctor-status-badge]'), currentStatusForDoctor(doctor));
            setBookButtonState(card.querySelector('[data-book-doctor]'), buttonStateForDoctor(doctor));
        });

        if (state.detailDoctor) {
            setBookButtonState(detailBook, buttonStateForDoctor(state.detailDoctor));
        }
    };

    const renderActiveAppointmentNotice = () => {
        if (!activeNotice) return;

        if (!activeAppointment) {
            activeNotice.hidden = true;
            return;
        }

        activeNotice.hidden = false;
        if (activeDoctor) activeDoctor.textContent = activeAppointment.doctor_name || activeAppointment.staff_name || 'your healthcare worker';
        if (activeDateTime) activeDateTime.textContent = activeAppointment.date_time_label || `${activeAppointment.date_label || ''} ${activeAppointment.time_label || ''}`.trim();
        setStatusBadge(activeStatusBadge, statusFromAppointment(activeAppointment));

        if (activeViewButton) {
            activeViewButton.href = activeAppointment.view_anchor ? `#${activeAppointment.view_anchor}` : '#';
        }

        if (activeCancelForm) {
            activeCancelForm.hidden = !activeAppointment.can_cancel;
            if (activeAppointment.cancel_url) activeCancelForm.action = activeAppointment.cancel_url;
        }
    };

    const setActiveAppointment = (appointment) => {
        activeAppointment = appointment || null;

        if (activeAppointment) {
            const doctor = doctorById.get(String(activeAppointment.staff_id));
            if (doctor) {
                doctor.appointment_status = statusFromAppointment(activeAppointment);
                doctor.is_active_appointment_doctor = true;
            }
        }

        doctors.forEach((doctor) => {
            doctor.is_active_appointment_doctor = Boolean(activeAppointment && String(activeAppointment.staff_id) === String(doctor.id));
        });

        renderActiveAppointmentNotice();
        updateDoctorCards();
    };

    const renderBarangayOptions = () => {
        const query = normalize(barangaySearch?.value || '');
        let visibleCount = 0;

        barangayOptions.forEach((button) => {
            const text = normalize(button.textContent);
            const matches = query === '' || text.includes(query);
            button.hidden = !matches;
            if (matches) visibleCount += 1;
            button.classList.toggle('is-selected', button.dataset.barangayOption === barangayInput?.value);
        });

        if (filterEmpty) filterEmpty.hidden = visibleCount > 0;
    };

    const openFilter = () => {
        if (!filterModal) return;
        filterModal.returnFocus = document.activeElement;
        filterModal.hidden = false;
        document.body.classList.add('has-filter-modal');
        if (barangaySearch) {
            barangaySearch.value = '';
            renderBarangayOptions();
            window.setTimeout(() => barangaySearch.focus(), 0);
        }
    };

    const closeFilter = () => {
        if (!filterModal) return;
        filterModal.hidden = true;
        document.body.classList.remove('has-filter-modal');
        filterModal.returnFocus?.focus();
    };

    const doctorExperience = (doctor) => doctor.name + ' is listed as ' + doctor.role + '. Facility: ' + doctor.location + '. Contact the healthcare worker to confirm which services are offered.';

    const doctorSpecialtyItems = (doctor) => [...new Set((doctor.availabilities || []).map(slot => slot.appointment_type_label)), doctor.consultation_type, doctor.schedule].filter(Boolean);

    const openDetail = (doctor) => {
        if (!detailModal) return;
        detailModal.returnFocus = document.activeElement;
        state.detailDoctor = doctor;

        detailName.textContent = doctor.name || 'Healthcare worker';
        detailRole.textContent = doctor.role || doctor.category_label || 'Healthcare worker';
        detailLocation.textContent = doctor.location || 'Clinic location';
        detailSchedule.textContent = doctor.schedule || 'Schedule pending';
        detailExperience.textContent = doctorExperience(doctor);
        detailChat.href = doctor.message_url || detailChat.href;
        setBookButtonState(detailBook, buttonStateForDoctor(doctor));
        detailSpecialties.replaceChildren(...doctorSpecialtyItems(doctor).map((item) => {
            const node = document.createElement('li');
            node.textContent = item;
            return node;
        }));

        if (doctor.photo_url) {
            detailPhoto.src = doctor.photo_url;
            detailPhoto.hidden = false;
            detailInitials.hidden = true;
        } else {
            detailPhoto.removeAttribute('src');
            detailPhoto.hidden = true;
            detailInitials.textContent = doctor.initials || 'IN';
            detailInitials.hidden = false;
        }

        detailModal.hidden = false;
        document.body.classList.add('has-detail-modal');
        detailModal.querySelector('.detail-close').focus();
    };

    const closeDetail = () => {
        if (!detailModal) return;
        detailModal.hidden = true;
        document.body.classList.remove('has-detail-modal');
        detailModal.returnFocus?.focus();
    };

    const resetBookingFields = () => {
        state.selectedAvailability = null;
        availabilityInput.value = '';
        typeInput.value = '';
        meetingInput.value = '';
        submitButton.disabled = true;
    };

    const selectAvailability = (availability, button) => {
        state.selectedAvailability = availability;
        availabilityInput.value = availability.id;
        typeInput.value = availability.appointment_type;
        meetingInput.value = availability.meeting_type;
        submitButton.disabled = false;

        slotGrid.querySelectorAll('.booking-slot').forEach((slotButton) => {
            slotButton.classList.toggle('is-selected', slotButton === button);
            slotButton.setAttribute('aria-pressed', String(slotButton === button));
        });
    };

    const renderSlots = () => {
        resetBookingFields();
        slotGrid.replaceChildren();
        dateLabel.textContent = state.selectedDate ? formatSelectedDate(state.selectedDate) : 'Select an available date';
        if (!state.selectedDate) return;
        const key = dateValue(state.selectedDate);
        dateInput.value = key;
        const labels = {booked: 'Already booked', full: 'Daily limit reached', unavailable: 'Unavailable', past: 'Time passed'};
        (state.days?.[key]?.slots || []).forEach(availability => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'booking-slot is-' + availability.status;
            button.disabled = availability.status !== 'available' || state.loading || state.submitting;
            button.setAttribute('aria-pressed', 'false');
            const time = document.createElement('span');
            time.textContent = availability.time_label;
            const description = document.createElement('small');
            description.textContent = labels[availability.status] || availability.appointment_type_label + ' · ' + availability.meeting_type_label;
            button.append(time, description);
            button.addEventListener('click', () => selectAvailability(availability, button));
            slotGrid.append(button);
        });
    };

    const renderCalendar = () => {
        calendarGrid.replaceChildren();
        monthLabel.textContent = formatMonth(state.visibleMonth);
        const start = new Date(state.visibleMonth.getFullYear(), state.visibleMonth.getMonth(), 1);
        start.setDate(start.getDate() - start.getDay());
        const firstAllowed = new Date(today().getFullYear(), today().getMonth(), 1);
        const lastAllowed = new Date(firstAllowed.getFullYear(), firstAllowed.getMonth() + 12, 1);
        root.querySelector('[data-calendar-prev]').disabled = state.visibleMonth <= firstAllowed || state.submitting;
        root.querySelector('[data-calendar-next]').disabled = state.visibleMonth >= lastAllowed || state.submitting;
        for (let index = 0; index < 42; index++) {
            const date = new Date(start);
            date.setDate(start.getDate() + index);
            const key = dateValue(date);
            const status = state.days?.[key]?.status || 'unavailable';
            const selected = Boolean(state.selectedDate && sameDay(date, state.selectedDate));
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'booking-day is-' + status;
            button.textContent = date.getDate();
            button.dataset.date = key;
            button.disabled = state.loading || state.submitting || status !== 'available';
            button.setAttribute('aria-label', formatSelectedDate(date) + ': ' + status);
            button.setAttribute('aria-pressed', String(selected));
            button.classList.toggle('is-selected', selected);
            if (date.getMonth() !== state.visibleMonth.getMonth()) button.classList.add('is-muted');
            if (sameDay(date, today())) { button.classList.add('is-today'); button.setAttribute('aria-current', 'date'); }
            button.addEventListener('click', () => { state.selectedDate = date; renderCalendar(); renderSlots(); });
            calendarGrid.append(button);
        }
    };

    const loadCalendar = async () => {
        calendarRequest?.abort();
        calendarRequest = new AbortController();
        const version = ++calendarVersion;
        state.loading = true;
        state.days = {};
        resetBookingFields();
        const status = root.querySelector('[data-calendar-status]');
        const retry = root.querySelector('[data-calendar-retry]');
        status.textContent = 'Checking live availability…';
        retry.hidden = true;
        renderCalendar();
        renderSlots();
        try {
            const url = new URL(root.dataset.calendarUrl, location.origin);
            url.searchParams.set('staff_id', state.doctor.id);
            url.searchParams.set('month', dateValue(state.visibleMonth).slice(0, 7));
            const response = await fetch(url, {signal: calendarRequest.signal, credentials: 'same-origin', cache: 'no-store', headers: {Accept: 'application/json'}});
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'Availability could not be loaded.');
            if (version !== calendarVersion) return;
            state.days = payload.days;
            if (!state.selectedDate || state.days[dateValue(state.selectedDate)]?.status !== 'available') {
                const first = Object.keys(state.days).find(key => key.slice(0, 7) === dateValue(state.visibleMonth).slice(0, 7) && state.days[key].status === 'available');
                state.selectedDate = first ? parseDate(first) : null;
            }
            status.textContent = state.selectedDate ? 'Choose an available time below.' : 'No available appointments this month. Try the next month.';
        } catch (error) {
            if (error.name === 'AbortError' || version !== calendarVersion) return;
            status.textContent = 'Could not check availability. Please retry.';
            retry.hidden = false;
            state.selectedDate = null;
        } finally {
            if (version === calendarVersion) { state.loading = false; renderCalendar(); renderSlots(); }
        }
    };

    const openBooking = (doctor) => {
        if (activeAppointment) {
            renderActiveAppointmentNotice();
            updateDoctorCards();
            setAlert(activeAppointment.message || 'You already have an active appointment.', true);
            return;
        }

        if (!doctorHasSlots(doctor)) {
            setAlert('This healthcare worker has no available schedule yet.', true);
            return;
        }

        state.doctor = doctor;
        returnFocus = document.activeElement;
        state.selectedDate = today();
        state.visibleMonth = new Date(state.selectedDate.getFullYear(), state.selectedDate.getMonth(), 1);
        state.selectedAvailability = null;
        form.reset();
        setError('');
        doctorLabel.textContent = `${doctor.name} - ${doctor.role}`;
        modal.hidden = false;
        document.body.classList.add('has-booking-modal');
        loadCalendar();
        modal.querySelector('.booking-close').focus();
    };

    const closeBooking = () => {
        if (state.submitting) return;
        calendarRequest?.abort();
        ++calendarVersion;
        returnFocus?.focus();
        modal.hidden = true;
        document.body.classList.remove('has-booking-modal');
        setError('');
    };

    root.addEventListener('click', (event) => {
        if (event.target.closest('[data-filter-open]')) {
            openFilter();
            return;
        }

        const bookButton = event.target.closest('[data-book-doctor]');
        if (bookButton) {
            const doctor = doctorById.get(String(bookButton.dataset.bookDoctor));
            if (doctor) openBooking(doctor);
            return;
        }

        const detailButton = event.target.closest('[data-open-detail]');
        if (detailButton) {
            const doctor = doctorById.get(String(detailButton.dataset.openDetail));
            if (doctor) openDetail(doctor);
        }
    });

    root.querySelectorAll('[data-booking-close]').forEach((button) => {
        button.addEventListener('click', closeBooking);
    });

    root.querySelectorAll('[data-filter-close]').forEach((button) => {
        button.addEventListener('click', closeFilter);
    });

    root.querySelectorAll('[data-detail-close]').forEach((button) => {
        button.addEventListener('click', closeDetail);
    });

    detailBook?.addEventListener('click', () => {
        if (!state.detailDoctor || detailBook.disabled) return;
        const doctor = state.detailDoctor;
        closeDetail();
        openBooking(doctor);
    });

    activeViewButton?.addEventListener('click', (event) => {
        if (!activeAppointment?.view_anchor) return;

        const target = document.getElementById(activeAppointment.view_anchor);
        if (!target) return;

        event.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        target.classList.add('is-active-appointment');
    });

    barangaySearch?.addEventListener('input', renderBarangayOptions);

    barangayOptions.forEach((button) => {
        button.addEventListener('click', () => {
            if (barangayInput) barangayInput.value = button.dataset.barangayOption || '';
            renderBarangayOptions();
        });
    });

    root.querySelector('[data-filter-clear]')?.addEventListener('click', () => {
        if (barangayInput) barangayInput.value = '';
        renderBarangayOptions();
        filterForm?.submit();
    });

    root.querySelector('[data-calendar-prev]')?.addEventListener('click', () => {
        state.visibleMonth = new Date(state.visibleMonth.getFullYear(), state.visibleMonth.getMonth() - 1, 1);
        state.selectedDate = null;
        loadCalendar();
    });

    root.querySelector('[data-calendar-next]')?.addEventListener('click', () => {
        state.visibleMonth = new Date(state.visibleMonth.getFullYear(), state.visibleMonth.getMonth() + 1, 1);
        state.selectedDate = null;
        loadCalendar();
    });

    root.querySelector('[data-calendar-retry]')?.addEventListener('click', loadCalendar);

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (activeAppointment) {
            closeBooking();
            renderActiveAppointmentNotice();
            updateDoctorCards();
            setAlert(activeAppointment.message || 'You already have an active appointment.', true);
            return;
        }

        if (!state.selectedAvailability || state.submitting) {
            setError('Choose an available time slot before confirming.');
            return;
        }

        state.submitting = true;
        modal.querySelectorAll('button').forEach(button => { button.disabled = true; });
        submitButton.disabled = true;
        submitButton.textContent = 'Saving...';
        setError('');

        try {
            const response = await fetch(root.dataset.bookingUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': root.dataset.csrf || '',
                },
                body: JSON.stringify(Object.fromEntries(new FormData(form).entries())),
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                if (payload.active_appointment) {
                    setActiveAppointment(payload.active_appointment);
                    state.submitting = false;
                    closeBooking();
                    setAlert(payload.message || payload.active_appointment.message || 'You already have an active appointment.', true);
                    return;
                }

                throw new Error(payload.message || Object.values(payload.errors || {})[0]?.[0] || 'Appointment could not be saved.');
            }

            if (payload.active_appointment) {
                setActiveAppointment(payload.active_appointment);
            }

            state.submitting = false;
            closeBooking();
            setAlert(payload.status || 'Appointment request sent. Please wait for the healthcare worker to confirm your appointment.');
            // Reload the server-rendered appointment and its saved care team after booking.
            window.location.reload();
        } catch (error) {
            setError(error.message || 'Appointment could not be saved.');
            await loadCalendar();
        } finally {
            state.submitting = false;
            submitButton.textContent = 'Confirm Appointment';
            root.querySelectorAll('[data-booking-close]').forEach(button => { button.disabled = false; });
            root.querySelector('[data-calendar-retry]').disabled = false;
            renderCalendar();
            renderSlots();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (root.querySelector('[data-appointment-chat]')?.open) return;
        const dialog = [modal, filterModal, detailModal].find(item => item && !item.hidden);
        if (event.key === 'Tab' && dialog) {
            const focusable = [...dialog.querySelectorAll('button:not(:disabled), a[href], input:not(:disabled), select:not(:disabled), textarea:not(:disabled)')]
                .filter(item => item.getClientRects().length && !item.className.includes('backdrop'));
            const first = focusable[0];
            const last = focusable.at(-1);
            if (event.shiftKey && (document.activeElement === first || !dialog.contains(document.activeElement))) {
                event.preventDefault(); last?.focus();
            } else if (!event.shiftKey && (document.activeElement === last || !dialog.contains(document.activeElement))) {
                event.preventDefault(); first?.focus();
            }
        }
        if (event.key !== 'Escape') return;
        if (detailModal && !detailModal.hidden) closeDetail();
        if (filterModal && !filterModal.hidden) closeFilter();
        if (!modal.hidden) closeBooking();
    });

    renderBarangayOptions();
    setActiveAppointment(activeAppointment);
})();
