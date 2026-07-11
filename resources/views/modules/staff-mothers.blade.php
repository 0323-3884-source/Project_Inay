@extends('layouts.app')

@section('title', 'Mothers Casefiles - Project INAY')
@section('portal_title', 'Mothers Casefiles')

@php
    $iconSearch = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>';
    $iconUsers = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/></svg>';
    $iconPlusUser = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M19 8v6"/><path d="M22 11h-6"/></svg>';
    $iconInfo = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>';
    $iconX = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>';
    $iconPhone = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.4 19.4 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.4 2.1L8.1 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.6 1.9Z"/></svg>';
    $iconArrow = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>';
    $iconMap = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>';
    $iconPerson = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 21a7 7 0 0 0-14 0"/><circle cx="12" cy="7" r="4"/></svg>';

    $statusLabels = [
        'all' => 'All Delivery Statuses',
        'pregnant' => 'Pregnant',
        'postpartum' => 'Postpartum',
        'planning' => 'Planning pregnancy',
        'not_pregnant' => 'Not pregnant',
    ];
    $riskLabels = [
        'all' => 'All Risk Ratings',
        'low' => 'Low Risk',
        'medium' => 'Needs Review',
        'high' => 'High Risk',
    ];
    $pregnancyLabel = fn ($value) => $statusLabels[$value ?: ''] ?? 'Not provided';
    $riskLabel = fn ($value) => $riskLabels[$value ?: ''] ?? 'Pending';
    $riskClass = fn ($value) => in_array($value, ['low', 'medium', 'high'], true) ? 'is-'.$value : 'is-pending';
    $initials = function ($mother) {
        return strtoupper(substr($mother->first_name, 0, 1).substr($mother->last_name, 0, 1));
    };
@endphp

@section('content')
    <section class="casefiles-shell" aria-label="Program staff mother casefiles">
        <header class="casefiles-heading">
            <div>
                <p>Barangay Cohort <strong>&middot;</strong> My Patients</p>
                <h1>Laguna Maternal Patient Register</h1>
                <span>Triage and manage registered mothers according to risk level and clinical timeline.</span>
            </div>
            <button class="casefile-add-button" type="button" data-casefile-modal-open>
                {!! $iconPlusUser !!}
                Add Another Patient
            </button>
        </header>

        <form class="casefiles-filter-form" method="GET" action="{{ route('staff.mothers') }}">
            <label class="casefiles-search">
                {!! $iconSearch !!}
                <input type="search" name="q" value="{{ $search }}" placeholder="Search patients by name or phone...">
            </label>
            <select name="status" aria-label="Filter by delivery status">
                @foreach ($statusLabels as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="risk" aria-label="Filter by risk rating">
                @foreach ($riskLabels as $value => $label)
                    <option value="{{ $value }}" @selected($risk === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @if ($search !== '' || $status !== 'all' || $risk !== 'all')
                <a href="{{ route('staff.mothers') }}">Clear</a>
            @endif
        </form>

        @if ($mothers->isEmpty())
            <section class="casefiles-empty">
                <strong>No patients in your casefiles yet.</strong>
                <p>Press Add Another Patient to search registered mother accounts and add them to this list.</p>
            </section>
        @else
            <div class="casefiles-grid">
                @foreach ($mothers as $mother)
                    @php
                        $latestRecord = $mother->maternalMonitoringRecords->first();
                        $riskValue = strtolower((string) ($latestRecord?->risk_level ?? ''));
                        $latestWeight = $latestRecord?->weight;
                        $nextVisit = 'Not scheduled';
                    @endphp

                    <article class="casefile-card">
                        <div class="casefile-card-top">
                            <span class="casefile-avatar">{{ $initials($mother) }}</span>
                            <div>
                                <h2>{{ $mother->full_name }}</h2>
                                <p>{{ $mother->age ? $mother->age.' years old' : 'Age not provided' }} &middot; {{ $mother->blood_type ?: 'Unknown' }}</p>
                            </div>
                            <span class="casefile-risk {{ $riskClass($riskValue) }}">{{ $riskLabel($riskValue) }}</span>
                        </div>

                        <dl class="casefile-facts">
                            <div><dt>Current Status:</dt><dd>{{ $pregnancyLabel($mother->pregnancy_status) }}</dd></div>
                            <div><dt>Next Scheduled Visit:</dt><dd>{{ $nextVisit }}</dd></div>
                            <div><dt>Last Weight Logged:</dt><dd>{{ $latestWeight === null ? 'Not logged' : rtrim(rtrim(number_format((float) $latestWeight, 2), '0'), '.').' kg' }}</dd></div>
                        </dl>

                        <div class="casefile-card-actions">
                            <a class="casefile-primary-action" href="{{ route('staff.mothers.show', $mother) }}">View Detail Record</a>
                            @if ($mother->contact_number)
                                <a class="casefile-icon-action" href="tel:{{ $mother->contact_number }}" aria-label="Call {{ $mother->full_name }}">{!! $iconPhone !!}</a>
                            @else
                                <span class="casefile-icon-action is-disabled" aria-label="No phone number">{!! $iconPhone !!}</span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        <div class="casefile-modal" data-casefile-modal hidden>
            <div class="casefile-modal-backdrop" data-casefile-modal-close></div>
            <form class="casefile-modal-dialog" method="POST" action="{{ route('staff.mothers.store') }}" aria-label="Add patient to my list">
                @csrf
                <div class="casefile-modal-header">
                    <div>
                        <div class="casefile-modal-title">
                            {!! $iconPlusUser !!}
                            <h2>Add Patient to My List</h2>
                        </div>
                        <p>Search and select registered mothers to add to your patient list</p>
                    </div>
                    <button class="casefile-modal-close" type="button" data-casefile-modal-close aria-label="Close add patient modal">{!! $iconX !!}</button>
                </div>

                <label class="casefile-modal-search">
                    {!! $iconSearch !!}
                    <input type="search" placeholder="Search by name or phone number..." data-casefile-patient-search>
                </label>

                <div class="casefile-modal-info">
                    {!! $iconInfo !!}
                    <p><strong>How it works:</strong> These are mothers who have already registered in the system. Search for a patient and select them to add to your patient list.</p>
                </div>

                <div class="casefile-patient-list" data-casefile-patient-list>
                    @forelse ($allRegisteredMothers as $mother)
                        @php
                            $latestRecord = $mother->maternalMonitoringRecords->first();
                            $riskValue = strtolower((string) ($latestRecord?->risk_level ?? ''));
                            $isAssigned = in_array((int) $mother->id, $assignedMotherIds, true);
                            $searchText = strtolower(implode(' ', array_filter([
                                $mother->full_name,
                                $mother->first_name,
                                $mother->middle_name,
                                $mother->last_name,
                                $mother->email,
                                $mother->contact_number,
                                $mother->barangay,
                            ])));
                        @endphp

                        <label class="casefile-patient-row @if($isAssigned) is-added @endif" data-casefile-patient-row data-search-text="{{ $searchText }}">
                            <span class="casefile-avatar">{{ $initials($mother) }}</span>
                            <span class="casefile-patient-content">
                                <span class="casefile-patient-name">
                                    {{ $mother->full_name }}
                                    @if ($isAssigned)
                                        <em>Already in your list</em>
                                    @endif
                                </span>
                                <span class="casefile-patient-meta">
                                    <span>{!! $iconPerson !!} {{ $mother->age ? $mother->age.' years old' : 'Age not provided' }}</span>
                                    <span>{!! $iconPhone !!} {{ $mother->contact_number ?: 'Phone not provided' }}</span>
                                    <span>{!! $iconMap !!} {{ $mother->barangay ?: 'Address not provided' }}</span>
                                </span>
                                <span class="casefile-patient-tags">
                                    <span>{{ $pregnancyLabel($mother->pregnancy_status) }}</span>
                                    <span>Due: Not scheduled</span>
                                    <span>Blood: {{ $mother->blood_type ?: 'Not provided' }}</span>
                                    <span>Registered: {{ $mother->created_at?->format('M j, Y') ?? 'Unknown' }}</span>
                                </span>
                            </span>
                            <input type="checkbox" name="mother_ids[]" value="{{ $mother->id }}" @disabled($isAssigned) data-casefile-patient-checkbox>
                        </label>
                    @empty
                        <p class="casefile-modal-empty">No registered mothers are available yet.</p>
                    @endforelse
                    <p class="casefile-modal-empty" data-casefile-modal-empty hidden>No registered mother matched your search.</p>
                </div>

                <div class="casefile-modal-footer">
                    <strong data-casefile-selected-count>0 patients selected</strong>
                    <div>
                        <button class="casefile-modal-secondary" type="button" data-casefile-modal-close>Cancel</button>
                        <button class="casefile-modal-submit" type="submit" disabled>
                            {!! $iconPlusUser !!}
                            Add Selected Patients
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <script>
        (() => {
            const modal = document.querySelector('[data-casefile-modal]');
            if (!modal) return;

            const openers = document.querySelectorAll('[data-casefile-modal-open]');
            const closers = modal.querySelectorAll('[data-casefile-modal-close]');
            const search = modal.querySelector('[data-casefile-patient-search]');
            const rows = Array.from(modal.querySelectorAll('[data-casefile-patient-row]'));
            const checkboxes = Array.from(modal.querySelectorAll('[data-casefile-patient-checkbox]'));
            const submit = modal.querySelector('.casefile-modal-submit');
            const count = modal.querySelector('[data-casefile-selected-count]');
            const empty = modal.querySelector('[data-casefile-modal-empty]');

            const updateSelection = () => {
                const selected = checkboxes.filter((box) => box.checked && !box.disabled).length;
                count.textContent = `${selected} patient${selected === 1 ? '' : 's'} selected`;
                submit.disabled = selected === 0;
            };

            const filterRows = () => {
                const term = (search.value || '').trim().toLowerCase();
                let visible = 0;

                rows.forEach((row) => {
                    const isVisible = !term || row.dataset.searchText.includes(term);
                    row.hidden = !isVisible;
                    if (isVisible) visible += 1;
                });

                if (empty) empty.hidden = visible > 0;
            };

            const openModal = () => {
                modal.hidden = false;
                document.body.classList.add('has-casefile-modal');
                search.focus();
                filterRows();
                updateSelection();
            };

            const closeModal = () => {
                modal.hidden = true;
                document.body.classList.remove('has-casefile-modal');
            };

            openers.forEach((button) => button.addEventListener('click', openModal));
            closers.forEach((button) => button.addEventListener('click', closeModal));
            search.addEventListener('input', filterRows);
            checkboxes.forEach((box) => box.addEventListener('change', updateSelection));
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.hidden) closeModal();
            });

            updateSelection();
        })();
    </script>
@endsection
