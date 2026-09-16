(() => {
    const root = document.querySelector('[data-clinic-schedule-root]');
    if (!root || root.dataset.clinicScheduleBooted === 'true') return;
    root.dataset.clinicScheduleBooted = 'true';

    // Only the staff Clinic Schedule view opts into these page tabs.
    if (root.hasAttribute('data-staff-tabs')) {
        const tabs = Array.from(root.querySelectorAll('[data-clinic-page-tab]'));
        const panels = root.querySelectorAll('[data-clinic-tab-panel]');
        const storageKey = `clinic-schedule-tab:${root.dataset.staffTabs}`;
        const selectTab = (name, updateUrl = false) => {
            if (!tabs.some((tab) => tab.dataset.clinicPageTab === name)) return;
            tabs.forEach((tab) => {
                const selected = tab.dataset.clinicPageTab === name;
                tab.setAttribute('aria-selected', String(selected));
                tab.tabIndex = selected ? 0 : -1;
            });
            panels.forEach((panel) => { panel.hidden = panel.dataset.clinicTabPanel !== name; });
            try { sessionStorage.setItem(storageKey, name); } catch (_) { /* Storage may be disabled. */ }
            if (updateUrl) {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', name);
                window.history.replaceState(window.history.state, '', url);
            }
        };

        const params = new URLSearchParams(window.location.search);
        if (!['tab', 'q', 'status'].some((key) => params.has(key))) {
            try { selectTab(sessionStorage.getItem(storageKey)); } catch (_) { /* Use the rendered default. */ }
        }
        tabs.forEach((tab, index) => {
            tab.addEventListener('click', (event) => {
                if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
                event.preventDefault();
                selectTab(tab.dataset.clinicPageTab, true);
            });
            tab.addEventListener('keydown', (event) => {
                let next;
                if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
                if (event.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
                if (event.key === 'Home') next = 0;
                if (event.key === 'End') next = tabs.length - 1;
                if (event.key === ' ') next = index;
                if (next === undefined) return;
                event.preventDefault();
                selectTab(tabs[next].dataset.clinicPageTab, true);
                tabs[next].focus();
            });
        });
        // Remember the visible panel before existing forms redirect back to this page.
        root.addEventListener('submit', () => {
            const selected = tabs.find((tab) => tab.getAttribute('aria-selected') === 'true');
            if (selected) selectTab(selected.dataset.clinicPageTab);
        });
    }

    const body = document.body;
    const monthNames = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December',
    ];

    const makeEl = (tag, className = '', text = '') => {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (text !== '') element.textContent = text;
        return element;
    };

    const todayValue = () => {
        const today = new Date();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        return `${today.getFullYear()}-${month}-${day}`;
    };

    const dateFromValue = (value) => {
        if (!value) return null;
        const [year, month, day] = value.split('-').map(Number);
        if (!year || !month || !day) return null;
        return new Date(year, month - 1, day);
    };

    const valueFromDate = (date) => {
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${date.getFullYear()}-${month}-${day}`;
    };

    const monthValue = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;

    const weekdayForDate = (value) => {
        const date = dateFromValue(value);
        if (!date) return null;
        const weekday = date.getDay();
        return weekday === 0 ? 7 : weekday;
    };

    const fetchJson = async (url, options = {}) => {
        const response = await fetch(url, {
            ...options,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.headers || {}),
            },
        });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            const firstError = payload.errors ? Object.values(payload.errors).flat()[0] : null;
            throw new Error(firstError || payload.message || 'Request failed.');
        }

        return payload;
    };

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

    const syncAvailabilitySelect = (form, requiredType = '') => {
        if (!form) return;
        const select = form.querySelector('[data-availability-select]');
        if (!select) return;

        const dateValue = form.querySelector('[name="appointment_date"]')?.value || '';
        const typeValue = requiredType || form.querySelector('[name="appointment_type"]')?.value || '';
        const day = weekdayForDate(dateValue);
        let availableCount = 0;

        Array.from(select.options).forEach((option, index) => {
            if (index === 0) return;
            const matchesDay = !day || Number(option.dataset.day) === day;
            const matchesType = !typeValue || option.dataset.appointmentType === typeValue;
            const isAvailable = matchesDay && matchesType;
            option.hidden = !isAvailable;
            option.disabled = !isAvailable;
            if (isAvailable) availableCount += 1;
        });

        if (select.selectedOptions[0]?.disabled) {
            select.value = '';
        }

        const placeholder = select.options[0];
        if (placeholder) {
            placeholder.textContent = !dateValue
                ? 'Choose a date first'
                : (availableCount > 0 ? 'Select a saved slot' : 'No matching saved slots');
        }
    };

    const applyAvailabilitySelection = (form) => {
        const select = form?.querySelector('[data-availability-select]');
        const option = select?.selectedOptions?.[0];
        if (!option || !option.value) return;

        const fieldMap = {
            appointment_type: option.dataset.appointmentType,
            meeting_type: option.dataset.meetingType,
            start_time: option.dataset.startTime,
            end_time: option.dataset.endTime,
            location: option.dataset.location || '',
        };

        Object.entries(fieldMap).forEach(([name, value]) => {
            const field = form.querySelector(`[name="${name}"]`);
            if (field && value !== undefined) field.value = value;
        });
    };

    const initStaffModals = () => {
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
            setField('staff_availability_id', '');
            setField('conversation_id', '');
            syncAvailabilitySelect(appointmentForm);
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
                    'staff_availability_id',
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

                syncAvailabilitySelect(appointmentForm);
                openModal(appointmentModal);
            });
        });

        if (appointmentForm) {
            ['appointment_date', 'appointment_type'].forEach((name) => {
                appointmentForm.querySelector(`[name="${name}"]`)?.addEventListener('change', () => {
                    syncAvailabilitySelect(appointmentForm);
                });
            });
            appointmentForm.querySelector('[data-availability-select]')?.addEventListener('change', () => {
                applyAvailabilitySelection(appointmentForm);
                syncAvailabilitySelect(appointmentForm);
            });
            syncAvailabilitySelect(appointmentForm);
        }

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

        const staffRescheduleModal = root.querySelector('[data-staff-reschedule-modal]');
        const staffRescheduleForm = root.querySelector('[data-staff-reschedule-form]');
        root.querySelectorAll('[data-open-staff-reschedule]').forEach((button) => {
            button.addEventListener('click', () => {
                if (!staffRescheduleForm) return;
                staffRescheduleForm.reset();
                staffRescheduleForm.action = button.dataset.suggestUrl;
                staffRescheduleForm.dataset.requiredType = button.dataset.suggestAppointmentType || '';
                const title = staffRescheduleModal?.querySelector('[data-staff-reschedule-title]');
                if (title) title.textContent = button.dataset.suggestTitle || 'Suggest New Schedule';
                const dateField = staffRescheduleForm.querySelector('[name="appointment_date"]');
                if (dateField) dateField.min = todayValue();
                syncAvailabilitySelect(staffRescheduleForm, staffRescheduleForm.dataset.requiredType);
                openModal(staffRescheduleModal);
            });
        });

        if (staffRescheduleForm) {
            staffRescheduleForm.querySelector('[name="appointment_date"]')?.addEventListener('change', () => {
                syncAvailabilitySelect(staffRescheduleForm, staffRescheduleForm.dataset.requiredType || '');
            });
            staffRescheduleForm.querySelector('[data-availability-select]')?.addEventListener('change', () => {
                syncAvailabilitySelect(staffRescheduleForm, staffRescheduleForm.dataset.requiredType || '');
            });
        }
    };

    const initMotherScheduler = () => {
        const form = root.querySelector('[data-worker-search-form]');
        if (!form || !('motherScheduler' in root.dataset)) return;

        const workerResults = root.querySelector('[data-worker-results]');
        const workerMessage = root.querySelector('[data-worker-search-message]');
        const workerCount = root.querySelector('[data-worker-count]');
        const calendarGrid = root.querySelector('[data-calendar-grid]');
        const calendarTitle = root.querySelector('[data-calendar-title]');
        const selectedDateLabel = root.querySelector('[data-selected-date-label]');
        const statusBox = root.querySelector('[data-booking-status]');
        const timeSlotsBox = root.querySelector('[data-time-slots]');
        const nearestBox = root.querySelector('[data-nearest-dates]');
        const suggestionsBox = root.querySelector('[data-booking-suggestions]');
        const reviewModal = root.querySelector('[data-booking-review-modal]');
        const confirmButton = reviewModal?.querySelector('[data-booking-confirm]');
        const notesField = reviewModal?.querySelector('[data-booking-notes]');
        const dateField = form.querySelector('[name="appointment_date"]');
        let currentMonth = new Date();
        let selectedDate = dateField?.value || todayValue();
        let selectedWorkerId = '';
        let selectedSlot = null;
        let availableDates = new Map();
        let slotController = null;
        let workerController = null;

        if (dateField) {
            dateField.min = todayValue();
            if (!dateField.value) dateField.value = selectedDate;
        }

        const filters = () => {
            const data = new FormData(form);
            const values = {};
            ['q', 'barangay', 'role', 'meeting_type', 'appointment_type'].forEach((name) => {
                const value = String(data.get(name) || '').trim();
                if (value) values[name] = value;
            });
            values.accepting_only = form.querySelector('[name="accepting_only"]')?.checked ? '1' : '0';
            if (selectedWorkerId) values.staff_id = selectedWorkerId;
            return values;
        };

        const urlWithParams = (baseUrl, params) => {
            const url = new URL(baseUrl, window.location.origin);
            Object.entries(params).forEach(([key, value]) => {
                if (value !== '' && value !== null && value !== undefined) {
                    url.searchParams.set(key, value);
                }
            });
            return url.toString();
        };

        const setStatus = (message, state = '') => {
            if (!statusBox) return;
            statusBox.textContent = message;
            statusBox.dataset.state = state;
        };

        const resetSelectedSlot = () => {
            selectedSlot = null;
            if (confirmButton) confirmButton.disabled = true;
            root.querySelectorAll('[data-slot-button].is-selected').forEach((button) => {
                button.classList.remove('is-selected');
            });
        };

        const renderCalendar = () => {
            if (!calendarGrid || !calendarTitle) return;
            calendarGrid.textContent = '';
            calendarTitle.textContent = `${monthNames[currentMonth.getMonth()]} ${currentMonth.getFullYear()}`;
            const first = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), 1);
            const start = new Date(first);
            start.setDate(1 - first.getDay());

            for (let index = 0; index < 42; index += 1) {
                const date = new Date(start);
                date.setDate(start.getDate() + index);
                const value = valueFromDate(date);
                const isPast = value < todayValue();
                const isCurrentMonth = date.getMonth() === currentMonth.getMonth();
                const hasOpenSlot = availableDates.has(value);
                const button = makeEl('button', 'clinic-date-button', String(date.getDate()));
                button.type = 'button';
                button.dataset.date = value;
                if (!isCurrentMonth) button.classList.add('is-muted');
                if (hasOpenSlot) button.classList.add('has-open');
                if (value === selectedDate) button.classList.add('is-selected');
                if (isPast || !hasOpenSlot) button.classList.add('is-unavailable');
                button.disabled = isPast || !hasOpenSlot;
                button.addEventListener('click', () => {
                    selectedDate = value;
                    if (dateField) dateField.value = value;
                    currentMonth = new Date(date.getFullYear(), date.getMonth(), 1);
                    loadSlots(value);
                });
                calendarGrid.append(button);
            }
        };

        const renderNearestDates = (nearestDates = []) => {
            if (!nearestBox) return;
            nearestBox.textContent = '';
            nearestBox.hidden = nearestDates.length === 0;
            if (!nearestDates.length) return;

            nearestBox.append(makeEl('p', '', 'Nearest available dates'));
            const row = makeEl('div', 'clinic-nearest-row');
            nearestDates.forEach((date) => {
                const button = makeEl('button', 'clinic-date-chip', `${date.date_label} - ${date.first_time_label}`);
                button.type = 'button';
                button.addEventListener('click', () => {
                    selectedDate = date.date;
                    if (dateField) dateField.value = date.date;
                    const parsed = dateFromValue(date.date);
                    if (parsed) currentMonth = new Date(parsed.getFullYear(), parsed.getMonth(), 1);
                    loadSlots(date.date);
                });
                row.append(button);
            });
            nearestBox.append(row);
        };

        const renderSuggestions = (payload) => {
            if (!suggestionsBox) return;
            suggestionsBox.textContent = '';
            const workers = payload.other_workers || [];
            const barangays = payload.nearby_barangays || [];
            suggestionsBox.hidden = workers.length === 0 && barangays.length === 0;
            if (suggestionsBox.hidden) return;

            if (workers.length) {
                suggestionsBox.append(makeEl('p', 'clinic-suggestion-title', 'Other available healthcare workers'));
                workers.forEach((worker) => {
                    const row = makeEl('button', 'clinic-suggestion-row', `${worker.name} - ${worker.barangay} - ${worker.time_label}`);
                    row.type = 'button';
                    row.addEventListener('click', () => {
                        selectedWorkerId = String(worker.staff_id);
                        selectedDate = worker.date;
                        if (dateField) dateField.value = worker.date;
                        loadSlots(worker.date);
                    });
                    suggestionsBox.append(row);
                });
            }

            if (barangays.length) {
                suggestionsBox.append(makeEl('p', 'clinic-suggestion-title', 'Barangays with open slots'));
                barangays.forEach((barangay) => {
                    const row = makeEl('button', 'clinic-suggestion-row', `${barangay.barangay} - ${barangay.open_slots} slot(s)`);
                    row.type = 'button';
                    row.addEventListener('click', () => {
                        selectedWorkerId = '';
                        const barangayField = form.querySelector('[name="barangay"]');
                        if (barangayField) barangayField.value = barangay.barangay;
                        searchWorkers();
                        loadSlots(selectedDate);
                    });
                    suggestionsBox.append(row);
                });
            }
        };

        const fillReview = (slot) => {
            const values = {
                staff: slot.staff_name || 'Healthcare worker',
                role: slot.staff_role || 'Healthcare Worker',
                facility: slot.facility || 'Official facility not set',
                barangay: slot.barangay || 'Barangay not set',
                date: `${slot.day_label}, ${slot.date_label}`,
                time: slot.time_label || '',
                type: slot.appointment_type_label || '',
                meeting: slot.meeting_type_label || '',
            };

            Object.entries(values).forEach(([key, value]) => {
                const node = reviewModal?.querySelector(`[data-review-${key}]`);
                if (node) node.textContent = value;
            });
            if (notesField) notesField.value = '';
            if (confirmButton) confirmButton.disabled = false;
        };

        const renderSlots = (slots = []) => {
            if (!timeSlotsBox) return;
            timeSlotsBox.textContent = '';
            resetSelectedSlot();

            if (selectedDateLabel) {
                const parsed = dateFromValue(selectedDate);
                selectedDateLabel.textContent = parsed
                    ? parsed.toLocaleDateString('en-PH', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })
                    : 'Select a date to view available time slots.';
            }

            if (!slots.length) {
                return;
            }

            slots.forEach((slot) => {
                const button = makeEl('button', 'clinic-time-card');
                button.type = 'button';
                button.dataset.slotButton = 'true';
                button.append(
                    makeEl('strong', '', slot.time_label || 'Open time'),
                    makeEl('span', '', slot.staff_name || 'Healthcare worker'),
                    makeEl('small', '', `${slot.staff_role || 'Healthcare Worker'} | ${slot.meeting_type_label || 'Consultation'} | ${slot.barangay || 'Barangay not set'}`),
                );
                button.addEventListener('click', () => {
                    resetSelectedSlot();
                    selectedSlot = slot;
                    button.classList.add('is-selected');
                    fillReview(slot);
                    openModal(reviewModal);
                });
                timeSlotsBox.append(button);
            });
        };

        const loadSlots = async (dateValue = selectedDate, options = {}) => {
            if (!root.dataset.availabilityUrl) return;
            selectedDate = dateValue;
            if (dateField) dateField.value = dateValue;
            slotController?.abort();
            slotController = new AbortController();
            if (options.renderSlots !== false) setStatus('Searching available time slots...', 'loading');

            try {
                const params = {
                    ...filters(),
                    appointment_date: dateValue,
                    calendar_month: monthValue(currentMonth),
                };
                const payload = await fetchJson(urlWithParams(root.dataset.availabilityUrl, params), {
                    signal: slotController.signal,
                });
                availableDates = new Map((payload.available_dates || []).map((date) => [date.date, date]));
                renderCalendar();

                if (options.renderSlots !== false) {
                    renderSlots(payload.slots || []);
                    renderNearestDates(payload.nearest_dates || []);
                    renderSuggestions(payload);
                    setStatus(payload.message || 'Available consultation slots found.', (payload.slots || []).length ? 'success' : 'empty');
                }
            } catch (error) {
                if (error.name !== 'AbortError') {
                    setStatus(error.message || 'Unable to search slots right now.', 'error');
                }
            }
        };

        const renderWorkerCards = (workers = []) => {
            if (!workerResults) return;
            workerResults.textContent = '';
            if (workerCount) workerCount.textContent = `${workers.length} found`;

            if (!workers.length) {
                workerResults.append(makeEl('div', 'clinic-empty', 'No healthcare workers matched your filters.'));
                return;
            }

            workers.forEach((worker) => {
                const card = makeEl('article', 'clinic-worker-card');
                const nearest = worker.nearest_available_date;
                const days = (worker.working_days || []).join(', ') || 'No active days';
                const types = (worker.consultation_types || []).join(', ') || 'No active types';
                const top = makeEl('div', 'clinic-card-top');
                const copy = makeEl('div');
                copy.append(makeEl('h3', '', worker.full_name), makeEl('p', '', worker.role));
                top.append(copy, makeEl('span', `clinic-pill ${worker.accepting_appointments ? 'is-confirmed' : 'is-cancelled'}`, worker.accepting_appointments ? 'Accepting' : 'Paused'));

                const facts = makeEl('div', 'clinic-facts');
                [
                    ['Facility', worker.facility],
                    ['Barangay', worker.barangay],
                    ['Consultation types', types],
                    ['Working days', days],
                    ['Nearest available', nearest ? `${nearest.full_date_label} at ${nearest.time_label}` : 'No open dates in the next 30 days'],
                ].forEach(([label, value]) => {
                    const fact = makeEl('div', label === 'Nearest available' ? 'clinic-fact clinic-wide' : 'clinic-fact');
                    fact.append(makeEl('span', '', label), makeEl('strong', '', value || 'Not provided'));
                    facts.append(fact);
                });
                card.append(top, facts);

                const actions = makeEl('div', 'clinic-actions');
                const viewButton = makeEl('button', 'clinic-secondary', 'View Availability');
                viewButton.type = 'button';
                viewButton.addEventListener('click', () => {
                    selectedWorkerId = String(worker.id);
                    if (nearest) {
                        selectedDate = nearest.date;
                        if (dateField) dateField.value = nearest.date;
                        const parsed = dateFromValue(nearest.date);
                        if (parsed) currentMonth = new Date(parsed.getFullYear(), parsed.getMonth(), 1);
                    }
                    loadSlots(selectedDate);
                });

                const bookButton = makeEl('button', 'clinic-primary', 'Book Appointment');
                bookButton.type = 'button';
                bookButton.disabled = !nearest;
                bookButton.addEventListener('click', () => {
                    selectedWorkerId = String(worker.id);
                    if (nearest) {
                        selectedDate = nearest.date;
                        if (dateField) dateField.value = nearest.date;
                    }
                    loadSlots(selectedDate);
                    document.querySelector('.clinic-calendar-panel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });

                actions.append(viewButton, bookButton);
                card.append(actions);
                workerResults.append(card);
            });
        };

        const searchWorkers = async () => {
            if (!root.dataset.workersUrl) return;
            selectedWorkerId = '';
            workerController?.abort();
            workerController = new AbortController();
            if (workerMessage) workerMessage.textContent = 'Searching healthcare workers...';

            try {
                const params = filters();
                if (dateField?.value) params.appointment_date = dateField.value;
                const payload = await fetchJson(urlWithParams(root.dataset.workersUrl, params), {
                    signal: workerController.signal,
                });
                renderWorkerCards(payload.workers || []);
                if (workerMessage) workerMessage.textContent = payload.message || 'Healthcare workers found.';
            } catch (error) {
                if (error.name !== 'AbortError') {
                    renderWorkerCards([]);
                    if (workerMessage) workerMessage.textContent = error.message || 'Unable to search healthcare workers.';
                }
            }
        };

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            selectedDate = dateField?.value || todayValue();
            const parsed = dateFromValue(selectedDate);
            if (parsed) currentMonth = new Date(parsed.getFullYear(), parsed.getMonth(), 1);
            searchWorkers();
            loadSlots(selectedDate);
        });

        root.querySelector('[data-worker-search-reset]')?.addEventListener('click', () => {
            form.reset();
            selectedWorkerId = '';
            selectedDate = todayValue();
            currentMonth = new Date();
            if (dateField) dateField.value = selectedDate;
            searchWorkers();
            loadSlots(selectedDate);
        });

        ['appointment_type', 'meeting_type', 'barangay'].forEach((name) => {
            form.querySelector(`[name="${name}"]`)?.addEventListener('change', () => {
                selectedWorkerId = '';
                selectedDate = dateField?.value || selectedDate;
                searchWorkers();
                loadSlots(selectedDate);
            });
        });

        dateField?.addEventListener('change', () => {
            if (!dateField.value || dateField.value < todayValue()) {
                dateField.value = todayValue();
            }
            selectedDate = dateField.value;
            const parsed = dateFromValue(selectedDate);
            if (parsed) currentMonth = new Date(parsed.getFullYear(), parsed.getMonth(), 1);
            searchWorkers();
            loadSlots(selectedDate);
        });

        root.querySelector('[data-calendar-prev]')?.addEventListener('click', () => {
            currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() - 1, 1);
            const monthStart = valueFromDate(currentMonth);
            loadSlots(monthStart < todayValue() ? todayValue() : monthStart, { renderSlots: false });
        });

        root.querySelector('[data-calendar-next]')?.addEventListener('click', () => {
            currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 1);
            loadSlots(valueFromDate(currentMonth), { renderSlots: false });
        });

        confirmButton?.addEventListener('click', async () => {
            if (!selectedSlot || !root.dataset.bookingUrl) return;
            confirmButton.disabled = true;
            const originalText = confirmButton.textContent;
            confirmButton.textContent = 'Booking...';

            try {
                const payload = await fetchJson(root.dataset.bookingUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': root.dataset.csrf || '',
                    },
                    body: JSON.stringify({
                        staff_availability_id: selectedSlot.availability_id,
                        appointment_date: selectedSlot.date,
                        appointment_type: selectedSlot.appointment_type,
                        meeting_type: selectedSlot.meeting_type,
                        notes: notesField?.value || '',
                    }),
                });
                closeModal(reviewModal);
                setStatus(`${payload.status || 'Appointment request sent.'} Reference: ${payload.reference_number || payload.appointment?.reference_number || 'INAY-APT'}`, 'success');
                await loadSlots(selectedDate);
            } catch (error) {
                setStatus(error.message || 'Unable to book this appointment.', 'error');
            } finally {
                confirmButton.textContent = originalText;
                confirmButton.disabled = !selectedSlot;
            }
        });

        renderCalendar();
        searchWorkers();
        loadSlots(selectedDate);
    };

    const initMotherReschedule = () => {
        const rescheduleModal = root.querySelector('[data-reschedule-modal]');
        const rescheduleForm = root.querySelector('[data-reschedule-form]');
        root.querySelectorAll('[data-open-reschedule]').forEach((button) => {
            button.addEventListener('click', () => {
                if (!rescheduleForm) return;
                rescheduleForm.reset();
                rescheduleForm.action = button.dataset.rescheduleUrl;
                const title = rescheduleModal?.querySelector('[data-reschedule-title]');
                if (title) title.textContent = button.dataset.rescheduleTitle || 'Request Reschedule';
                const dateField = rescheduleForm.querySelector('[name="preferred_date"]');
                if (dateField) dateField.min = todayValue();
                openModal(rescheduleModal);
            });
        });
    };

    initStaffModals();
    initMotherScheduler();
    initMotherReschedule();

    root.querySelectorAll('[data-decline-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const input = form.querySelector('[name="decline_reason"]');
            if (!input || input.value) return;
            const reason = window.prompt('Optional: add a reason.');
            if (reason === null) {
                event.preventDefault();
                return;
            }
            input.value = reason.trim();
        });
    });

    root.querySelectorAll('[data-reject-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const input = form.querySelector('[name="decline_reason"]');
            if (!input || input.value) return;
            const reason = window.prompt('Optional: add a reason for rejecting this request.');
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

    root.querySelectorAll('form:not([data-worker-search-form])').forEach((form) => {
        form.addEventListener('submit', () => {
            form.querySelectorAll('button[type="submit"]').forEach((button) => {
                button.disabled = true;
                button.dataset.originalText = button.textContent;
                button.textContent = 'Saving...';
            });
        });
    });
})();
