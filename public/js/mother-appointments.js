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

    const defaultSlots = [
        ['08:00', '09:00', '08:00 AM'],
        ['09:00', '10:00', '09:00 AM'],
        ['10:00', '11:00', '10:00 AM'],
        ['11:00', '12:00', '11:00 AM'],
        ['12:30', '13:30', '12:30 PM'],
        ['13:30', '14:30', '01:30 PM'],
        ['14:30', '15:30', '02:30 PM'],
        ['15:30', '16:30', '03:30 PM'],
        ['16:30', '17:30', '04:30 PM'],
        ['17:30', '18:30', '05:30 PM'],
    ];

    const today = () => {
        const value = new Date();
        value.setHours(0, 0, 0, 0);
        return value;
    };

    const nowTimeValue = () => {
        const value = new Date();
        return `${String(value.getHours()).padStart(2, '0')}:${String(value.getMinutes()).padStart(2, '0')}`;
    };

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
    };

    const doctorExperience = (doctor) => {
        const category = String(doctor.category_label || 'maternal care').toLowerCase();
        return `${doctor.name} provides ${category} support for mothers and families through ${doctor.location}. Appointments focus on clear assessment, practical guidance, and coordinated follow-up care.`;
    };

    const doctorSpecialtyItems = (doctor) => {
        const itemsByCategory = {
            ob_gyn: ['Prenatal and postnatal checkups', 'Pregnancy risk review', 'Maternal wellness counseling'],
            pediatrician: ['Child checkups', 'Growth and development review', 'Vaccination guidance'],
            midwife: ['Prenatal monitoring', 'Birth preparedness', 'Postpartum support'],
            general_doctor: ['General consultation', 'Primary care assessment', 'Follow-up care'],
            program_staff: ['DSWD and program guidance', 'Mother case coordination', 'Community support referral'],
            barangay_health_worker: ['Barangay health follow-up', 'Home visit coordination', 'Community care navigation'],
        };
        const baseItems = itemsByCategory[doctor.category_key] || itemsByCategory.general_doctor;
        return [...baseItems, doctor.consultation_type, doctor.schedule].filter(Boolean).slice(0, 5);
    };

    const openDetail = (doctor) => {
        if (!detailModal) return;
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
    };

    const closeDetail = () => {
        if (!detailModal) return;
        detailModal.hidden = true;
        document.body.classList.remove('has-detail-modal');
    };

    const availabilityForDate = (doctor, date) => {
        const day = isoWeekday(date);
        const dateText = dateValue(date);
        const isToday = dateText === dateValue(today());
        const currentTime = nowTimeValue();

        return (doctor?.availabilities || [])
            .filter((availability) => Number(availability.day_of_week) === day)
            .filter((availability) => !isToday || String(availability.start_time) > currentTime);
    };

    const nextAvailableDate = (doctor) => {
        const start = today();
        const availableDays = new Set((doctor.available_days || []).map(Number));

        if (availableDays.size === 0) return start;

        for (let offset = 0; offset < 60; offset += 1) {
            const candidate = new Date(start);
            candidate.setDate(start.getDate() + offset);

            if (availableDays.has(isoWeekday(candidate)) && availabilityForDate(doctor, candidate).length > 0) {
                return candidate;
            }
        }

        return start;
    };

    const uniqueSlotTemplates = (doctor) => {
        const seen = new Set();
        const slots = [];

        (doctor?.availabilities || []).forEach((availability) => {
            const key = `${availability.start_time}-${availability.end_time}`;
            if (seen.has(key)) return;
            seen.add(key);
            slots.push({
                start_time: availability.start_time,
                end_time: availability.end_time,
                time_label: availability.time_label,
            });
        });

        if (slots.length > 0) {
            return slots.sort((left, right) => String(left.start_time).localeCompare(String(right.start_time)));
        }

        return defaultSlots.map(([start, end, label]) => ({
            start_time: start,
            end_time: end,
            time_label: label,
        }));
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
        });
    };

    const renderSlots = () => {
        resetBookingFields();
        slotGrid.replaceChildren();

        if (!state.doctor || !state.selectedDate) return;

        const date = state.selectedDate;
        const available = availabilityForDate(state.doctor, date);
        const availableByTime = new Map(available.map((availability) => [`${availability.start_time}-${availability.end_time}`, availability]));
        dateInput.value = dateValue(date);
        dateLabel.textContent = formatSelectedDate(date);

        uniqueSlotTemplates(state.doctor).forEach((template) => {
            const key = `${template.start_time}-${template.end_time}`;
            const availability = availableByTime.get(key);
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'booking-slot';
            button.textContent = template.time_label;
            button.disabled = !availability;

            if (availability) {
                button.addEventListener('click', () => selectAvailability(availability, button));
            }

            slotGrid.append(button);
        });
    };

    const renderCalendar = () => {
        calendarGrid.replaceChildren();
        monthLabel.textContent = formatMonth(state.visibleMonth);

        const firstOfMonth = new Date(state.visibleMonth.getFullYear(), state.visibleMonth.getMonth(), 1);
        const start = new Date(firstOfMonth);
        start.setDate(firstOfMonth.getDate() - firstOfMonth.getDay());

        for (let index = 0; index < 42; index += 1) {
            const date = new Date(start);
            date.setDate(start.getDate() + index);

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'booking-day';
            button.textContent = String(date.getDate());

            if (date.getMonth() !== state.visibleMonth.getMonth()) button.classList.add('is-muted');
            if (sameDay(date, today())) button.classList.add('is-today');
            if (state.selectedDate && sameDay(date, state.selectedDate)) button.classList.add('is-selected');
            if (date < today()) button.disabled = true;

            button.addEventListener('click', () => {
                state.selectedDate = date;
                resetBookingFields();
                renderCalendar();
                renderSlots();
            });

            calendarGrid.append(button);
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
        state.selectedDate = nextAvailableDate(doctor);
        state.visibleMonth = new Date(state.selectedDate.getFullYear(), state.selectedDate.getMonth(), 1);
        state.selectedAvailability = null;
        form.reset();
        setError('');
        doctorLabel.textContent = `${doctor.name} - ${doctor.role}`;
        modal.hidden = false;
        document.body.classList.add('has-booking-modal');
        renderCalendar();
        renderSlots();
    };

    const closeBooking = () => {
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
        renderCalendar();
    });

    root.querySelector('[data-calendar-next]')?.addEventListener('click', () => {
        state.visibleMonth = new Date(state.visibleMonth.getFullYear(), state.visibleMonth.getMonth() + 1, 1);
        renderCalendar();
    });

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
                    closeBooking();
                    setAlert(payload.message || payload.active_appointment.message || 'You already have an active appointment.', true);
                    return;
                }

                throw new Error(payload.message || Object.values(payload.errors || {})[0]?.[0] || 'Appointment could not be saved.');
            }

            if (payload.active_appointment) {
                setActiveAppointment(payload.active_appointment);
            }

            closeBooking();
            setAlert(payload.status || 'Appointment request sent. Please wait for the healthcare worker to confirm your appointment.');
        } catch (error) {
            setError(error.message || 'Appointment could not be saved.');
        } finally {
            state.submitting = false;
            submitButton.textContent = 'Confirm Appointment';
            submitButton.disabled = !state.selectedAvailability;
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        if (detailModal && !detailModal.hidden) closeDetail();
        if (filterModal && !filterModal.hidden) closeFilter();
        if (!modal.hidden) closeBooking();
    });

    renderBarangayOptions();
    setActiveAppointment(activeAppointment);
})();
