@extends('layouts.app')

@section('title', $mother->full_name.' Casefile - Project INAY')
@section('portal_title', 'Mother Care Summary')

@php
    $iconArrowLeft = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>';
    $iconChevron = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>';
    $iconPhone = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.4 19.4 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.4 2.1L8.1 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.6 1.9Z"/></svg>';
    $iconMap = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>';
    $iconUser = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 21a7 7 0 0 0-14 0"/><circle cx="12" cy="7" r="4"/></svg>';
    $iconShield = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>';
    $iconFile = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>';
    $iconPulse = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>';
    $iconCalendar = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="5" width="18" height="17" rx="2"/><path d="M3 10h18"/></svg>';
    $iconClock = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>';
    $iconHeart = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 1 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/></svg>';
    $iconEdit = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>';
    $iconPrinter = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>';
    $iconDownload = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>';
    $iconTrend = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 17 6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>';
    $iconHistory = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 3v6h6"/><path d="M12 7v5l3 2"/></svg>';
    $iconAlert = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>';

    $statusLabels = [
        'pregnant' => 'Pregnant',
        'postpartum' => 'Postpartum',
        'planning' => 'Planning pregnancy',
        'not_pregnant' => 'Not pregnant',
    ];
    $riskLabels = [
        'low' => 'Low Risk',
        'medium' => 'Needs Review',
        'high' => 'High Risk',
    ];

    $riskValue = strtolower((string) ($latestRecord?->risk_level ?? ''));
    $riskLabel = $riskLabels[$riskValue] ?? 'Pending';
    $riskClass = in_array($riskValue, ['low', 'medium', 'high'], true) ? 'is-'.$riskValue : 'is-pending';
    $initials = strtoupper(substr($mother->first_name, 0, 1).substr($mother->last_name, 0, 1));
    $caseId = 'MAT-RHU-'.str_pad((string) $mother->id, 3, '0', STR_PAD_LEFT);
    $pregnancyWeek = $latestRecord?->pregnancy_week;
    $pregnancyMonth = $latestRecord?->pregnancy_month;
    $trimester = $pregnancyWeek ? ($pregnancyWeek >= 28 ? 'Third Trimester' : ($pregnancyWeek >= 14 ? 'Second Trimester' : 'First Trimester')) : 'Not provided';
    $bpValue = $latestRecord?->bp_systolic && $latestRecord?->bp_diastolic ? $latestRecord->bp_systolic.'/'.$latestRecord->bp_diastolic.' mmHg' : 'Not logged';
    $weightNumber = $latestRecord?->weight;
    $weightValue = $weightNumber === null ? 'Not logged' : rtrim(rtrim(number_format((float) $weightNumber, 2), '0'), '.').' kg';
    $sugarValue = $latestRecord?->blood_sugar === null ? 'Not logged' : rtrim(rtrim(number_format((float) $latestRecord->blood_sugar, 1), '0'), '.').' mg/dL';
    $hemoValue = $latestRecord?->hemoglobin === null ? 'Not logged' : rtrim(rtrim(number_format((float) $latestRecord->hemoglobin, 1), '0'), '.').' g/dL';
    $latestDate = ($latestRecord?->recorded_at ?? $latestRecord?->created_at);
    $completion = min(100, ($records->count() * 5) + ($uploads->count() * 5));
    $checkupUploads = $uploads->filter(fn ($upload) => str_contains(strtolower($upload->record_type), 'checkup'))->count();
    $prescriptionUploads = $uploads->filter(fn ($upload) => str_contains(strtolower($upload->record_type), 'prescription'))->count();
    $weightRecords = $records->filter(fn ($record) => $record->weight !== null)->values();
    $bpRecords = $records->filter(fn ($record) => $record->bp_systolic !== null && $record->bp_diastolic !== null)->values();
    $bpPointX = 52;
    $bpSystolicY = $latestRecord?->bp_systolic ? max(12, min(84, 100 - (($latestRecord->bp_systolic - 70) / 70 * 90))) : 52;
    $bpDiastolicY = $latestRecord?->bp_diastolic ? max(12, min(84, 100 - (($latestRecord->bp_diastolic - 60) / 70 * 90))) : 76;
    $weightPointY = $weightNumber ? max(12, min(84, 100 - (((float) $weightNumber - 70) / 10 * 90))) : 54;
    $initialRecordDate = $latestDate?->format('Y-m-d') ?? now()->toDateString();
@endphp

@section('content')
    <section class="casefiles-shell casefile-summary-page" aria-label="Mother care summary">
        <header class="casefile-detail-heading">
            <div>
                <nav class="casefile-breadcrumb" aria-label="Breadcrumb">
                    <a href="{{ route('staff.mothers') }}">{!! $iconArrowLeft !!} Mothers Casefiles</a>
                    {!! $iconChevron !!}
                    <span>{{ $caseId }}</span>
                </nav>
                <h1>Mother Care Summary</h1>
                <p>A simplified view of clinical status, care progress, and patient records.</p>
            </div>
            <a class="casefile-back-link" href="{{ route('staff.mothers') }}">{!! $iconArrowLeft !!} Back to Casefiles</a>
        </header>

        <section class="casefile-profile-card">
            <div class="casefile-profile-main">
                <span class="casefile-avatar is-xl">{{ $initials }}</span>
                <div>
                    <div class="casefile-profile-title">
                        <h2>{{ $mother->full_name }}</h2>
                        <span class="casefile-risk {{ $riskClass }}" data-risk-label>{{ $riskLabel }}</span>
                    </div>
                    <strong>{{ $caseId }}</strong>
                    <div class="casefile-contact-row">
                        <span>{!! $iconPhone !!} {{ $mother->contact_number ?: 'Phone not provided' }}</span>
                        <span>{!! $iconMap !!} {{ $mother->barangay ?: 'No address recorded' }}</span>
                        <span>{!! $iconUser !!} {{ $staff->full_name }}</span>
                    </div>
                </div>
            </div>
            <div class="casefile-profile-actions">
                <button type="button">{!! $iconEdit !!} Edit Information</button>
                <button type="button">{!! $iconCalendar !!} Schedule Visit</button>
                <button type="button" data-casefile-print>{!! $iconPrinter !!} Print Record</button>
                <button type="button" class="is-dark" data-casefile-print>{!! $iconDownload !!} Export PDF</button>
            </div>
            <dl class="casefile-profile-facts">
                <div><dt>Age</dt><dd>{{ $mother->age ? $mother->age.' years old' : 'Not provided' }}</dd></div>
                <div><dt>Blood Type</dt><dd>{{ $mother->blood_type ?: 'Unknown' }}</dd></div>
                <div><dt>Pregnancy Status</dt><dd>{{ $statusLabels[$mother->pregnancy_status] ?? 'Not provided' }}</dd></div>
                <div><dt>Current Trimester</dt><dd>{{ $trimester }}</dd></div>
                <div><dt>Estimated Due Date</dt><dd>Not provided</dd></div>
                <div><dt>Next Appointment</dt><dd>Not provided</dd></div>
                <div><dt>Previous Deliveries</dt><dd>Not recorded</dd></div>
                <div><dt>Co-monitoring</dt><dd>Not provided</dd></div>
            </dl>
        </section>

        <div class="casefile-status-grid">
            <article class="is-pink"><span>Care Status</span><strong>{{ $statusLabels[$mother->pregnancy_status] ?? 'Not provided' }}</strong><small>{{ $trimester }}</small>{!! $iconHeart !!}</article>
            <article class="is-green"><span>Risk Assessment</span><strong data-summary-risk>{{ $riskLabel }}</strong><small>Updated by monitoring records</small>{!! $iconShield !!}</article>
            <article><span>Next Appointment</span><strong>Not provided</strong><small>Scheduled prenatal follow-up</small>{!! $iconClock !!}</article>
            <article class="is-green"><span>Care Completion</span><strong>{{ $completion }}%</strong><small>Monitoring, documents, and learning</small><em style="--progress: {{ $completion }}%"></em>{!! $iconPulse !!}</article>
        </div>

        <div class="casefile-tabs" role="tablist" aria-label="Casefile sections">
            <button class="is-active" type="button" data-casefile-tab="overview">Overview</button>
            <button type="button" data-casefile-tab="monitoring">Monitoring <span data-monitoring-count>{{ $records->count() }}</span></button>
            <button type="button" data-casefile-tab="learning">Learning <span>0</span></button>
            <button type="button" data-casefile-tab="documents">Documents <span>{{ $uploads->count() }}</span></button>
            <button type="button" data-casefile-tab="notes">Notes <span>{{ $latestRecord?->notes ? 1 : 0 }}</span></button>
        </div>

        <section data-casefile-panel="overview">
            <section class="casefile-panel casefile-vitals-panel">
                <div class="casefile-panel-title">
                    <div>
                        <h2>Maternal Vital Signs</h2>
                        <p>Latest pregnancy threshold indicators for {{ $mother->full_name }}</p>
                    </div>
                    <button type="button" data-vitals-open>{!! $iconPulse !!} Update Vitals</button>
                </div>
                <div class="casefile-vital-grid is-large">
                    <article><i class="is-pink">{!! $iconHeart !!}</i><span>Blood Pressure</span><strong data-vital-value="blood_pressure">{{ $bpValue }}</strong><small>Healthy Range: Target below 140/90 mmHg</small><b data-vital-status="blood_pressure">Normal</b></article>
                    <article><i class="is-pink">{!! $iconPulse !!}</i><span>Blood Sugar</span><strong data-vital-value="blood_sugar">{{ $sugarValue }}</strong><small>Healthy Range: 70 - 140 mg/dL</small><b data-vital-status="blood_sugar">Normal</b></article>
                    <article><i class="is-green">{!! $iconShield !!}</i><span>Weight</span><strong data-vital-value="weight">{{ $weightValue }}</strong><small>Healthy Range: Review gain against baseline</small><b data-vital-status="weight">Normal</b></article>
                    <article><i class="is-blue">{!! $iconShield !!}</i><span>Hemoglobin</span><strong data-vital-value="hemoglobin">{{ $hemoValue }}</strong><small>Healthy Range: 11.0 g/dL and above</small><b data-vital-status="hemoglobin">Normal</b></article>
                </div>
                <div class="casefile-safe-banner" data-vitals-banner>{!! $iconShield !!} All available maternal indicators are within healthy pregnancy thresholds.</div>
            </section>

            <section class="casefile-progress-section">
                <p>Statistics</p>
                <h2>Maternal Health Progress</h2>
                <span>Charts and indicators update from stored monitoring records and uploaded documents.</span>
                <div class="casefile-progress-cards">
                    <article><span>Prenatal Visit Completion</span><strong data-visit-count>{{ min($records->count(), 8) }}/8</strong><small data-visit-copy>{{ $records->count() > 0 ? round(min($records->count(), 8) / 8 * 100) : 0 }}% of expected visits logged</small><em data-visit-progress style="--progress: {{ min(100, $records->count() / 8 * 100) }}%"></em>{!! $iconCalendar !!}</article>
                    <article><span>Missed Appointments</span><strong>0</strong><small>No missed visit detected</small><em style="--progress: 0%"></em>{!! $iconAlert !!}</article>
                    <article><span>Vaccination Status</span><strong>Pending</strong><small>0 vaccination record(s)</small><em style="--progress: 0%"></em>{!! $iconCalendar !!}</article>
                    <article><span>Prenatal Checkups</span><strong>{{ $checkupUploads }}/8</strong><small>{{ round(min($checkupUploads, 8) / 8 * 100) }}% completion from uploaded records</small><em style="--progress: {{ min(100, $checkupUploads / 8 * 100) }}%"></em>{!! $iconCalendar !!}</article>
                </div>
            </section>

            <section class="casefile-chart-grid">
                <article class="casefile-chart-card">
                    <span>Trend Chart</span>
                    <h3>Weight Progression</h3>
                    <div class="casefile-chart-canvas" data-chart="weight"></div>
                    <div class="casefile-legend"><span class="is-pink"></span> Weight (kg)</div>
                    <button type="button" class="monitoring-history-trigger" data-history-open="weight">
                        {!! $iconHistory !!}
                        <span>View Weight History</span>
                        <b data-history-count="weight">{{ $weightRecords->count() }} Record{{ $weightRecords->count() === 1 ? '' : 's' }}</b>
                    </button>
                </article>
                <article class="casefile-chart-card">
                    <span>Trend Chart</span>
                    <h3>Blood Pressure Trends</h3>
                    <div class="casefile-chart-canvas" data-chart="bp"></div>
                    <div class="casefile-legend"><span class="is-red"></span> Systolic <span class="is-blue"></span> Diastolic</div>
                    <button type="button" class="monitoring-history-trigger" data-history-open="bp">
                        {!! $iconHistory !!}
                        <span>View Blood Pressure History</span>
                        <b data-history-count="bp">{{ $bpRecords->count() }} Record{{ $bpRecords->count() === 1 ? '' : 's' }}</b>
                    </button>
                </article>
            </section>

            <section class="casefile-panel">
                <p class="casefile-section-kicker">Pregnancy Timeline</p>
                <h2>Care Journey</h2>
                <p class="casefile-section-subtitle">Registration, trimester progression, delivery, and postpartum milestones.</p>
                <div class="casefile-journey-grid">
                    <article class="is-complete"><strong>Registration</strong><span>Patient account created</span><small>{{ $mother->created_at?->format('M j, Y') ?? 'Unknown' }}</small></article>
                    <article class="{{ $pregnancyWeek && $pregnancyWeek >= 1 ? 'is-complete' : '' }}"><strong>First Trimester</strong><span>Weeks 1-13</span><small>{{ $pregnancyWeek && $pregnancyWeek >= 14 ? 'Completed' : 'Current or pending' }}</small></article>
                    <article class="{{ $pregnancyWeek && $pregnancyWeek >= 14 ? 'is-complete' : '' }}"><strong>Second Trimester</strong><span>Weeks 14-27</span><small>{{ $pregnancyWeek && $pregnancyWeek >= 28 ? 'Completed' : 'Current or pending' }}</small></article>
                    <article class="{{ $pregnancyWeek && $pregnancyWeek >= 28 ? 'is-current' : '' }}"><strong>Third Trimester</strong><span>Weeks 28-40</span><small>{{ $trimester === 'Third Trimester' ? 'Current' : 'Future' }}</small></article>
                    <article><strong>Delivery</strong><span>Birth plan and delivery</span><small>Future</small></article>
                    <article><strong>Postpartum Care</strong><span>After delivery follow-up</span><small>Future</small></article>
                </div>
            </section>

            <section class="casefile-panel">
                <p class="casefile-section-kicker">Activity Timeline</p>
                <h2>Patient Activity</h2>
                <p class="casefile-section-subtitle">Registration, checkups, monitoring, learning, consultation, scheduling, and risk updates.</p>
                <div class="casefile-activity-list">
                    @forelse ($records->take(4) as $record)
                        <article>
                            <strong>Maternal monitoring submitted <span>Monitoring</span></strong>
                            <p>Week {{ $record->pregnancy_week ?: 'N/A' }} vitals recorded with {{ $riskLabels[strtolower((string) $record->risk_level)] ?? 'Pending' }} assessment.</p>
                            <time>{{ ($record->recorded_at ?? $record->created_at)?->format('M j, Y, g:i A') ?? 'Date not recorded' }}</time>
                        </article>
                    @empty
                        <article>
                            <strong>Patient registered <span>Registration</span></strong>
                            <p>Mother account was created in Project INAY.</p>
                            <time>{{ $mother->created_at?->format('M j, Y, g:i A') ?? 'Date not recorded' }}</time>
                        </article>
                    @endforelse
                </div>
            </section>
        </section>

        <section class="casefile-panel" data-casefile-panel="monitoring" hidden>
            <h2>Monitoring Records</h2>
            <div data-record-list>
            @forelse ($records as $record)
                <div class="casefile-record-row">
                    <strong>Week {{ $record->pregnancy_week ?: 'N/A' }} &middot; Month {{ $record->pregnancy_month ?: 'N/A' }}</strong>
                    <span>BP {{ $record->bp_systolic && $record->bp_diastolic ? $record->bp_systolic.'/'.$record->bp_diastolic.' mmHg' : 'Not logged' }}</span>
                    <span>Weight {{ $record->weight === null ? 'Not logged' : rtrim(rtrim(number_format((float) $record->weight, 2), '0'), '.').' kg' }}</span>
                    <span>{{ ($record->recorded_at ?? $record->created_at)?->format('M j, Y') ?? 'Date not recorded' }}</span>
                </div>
            @empty
                <p class="casefile-panel-note">No maternal monitoring record has been saved yet.</p>
            @endforelse
            </div>
        </section>

        <section class="casefile-panel" data-casefile-panel="learning" hidden>
            <h2>Learning Progress</h2>
            <p class="casefile-panel-note">INAY Kaalaman progress will appear here after learning activity is recorded.</p>
        </section>

        <section class="casefile-panel" data-casefile-panel="documents" hidden>
            <h2>{!! $iconFile !!} Uploaded INAY Kaalaman Records</h2>
            @if ($uploads->isEmpty())
                <p class="casefile-panel-note">No uploaded prenatal records or receipts yet.</p>
            @else
                <div class="casefile-upload-list">
                    @foreach ($uploads as $upload)
                        <article>
                            <span>{!! $iconFile !!}</span>
                            <div>
                                <strong>{{ $upload->record_type }}</strong>
                                <p>{{ $upload->original_name }} &middot; Month {{ $upload->month }} &middot; {{ $upload->created_at?->format('M j, Y') }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="casefile-panel" data-casefile-panel="notes" hidden>
            <h2>Clinical Notes</h2>
            <p class="casefile-panel-note">{{ $latestRecord?->notes ?: 'No staff notes recorded for this patient yet.' }}</p>
        </section>

        <div class="vitals-success" data-vitals-success hidden>Maternal vitals saved.</div>

        <div class="history-modal" data-history-modal hidden>
            <div class="history-modal-backdrop" data-history-close></div>
            <section class="history-dialog" role="dialog" aria-modal="true" aria-labelledby="history-modal-title">
                <header class="history-dialog-header">
                    <div>
                        <span class="history-dialog-kicker">Monitoring History</span>
                        <h2 class="history-dialog-title" id="history-modal-title" data-history-title>Weight History</h2>
                        <p class="history-dialog-copy" data-history-description>Complete weight readings from recorded maternal vitals.</p>
                    </div>
                    <button type="button" class="history-close" data-history-close aria-label="Close monitoring history">&times;</button>
                </header>
                <div class="history-dialog-controls">
                    <label class="history-search">
                        <span aria-hidden="true">&#9906;</span>
                        <input type="search" data-history-search placeholder="Search date, values, or status...">
                    </label>
                    <button type="button" class="history-sort" data-history-sort>Newest First</button>
                </div>
                <p class="history-count-copy" data-history-count-copy>Showing 0 records</p>
                <div class="history-dialog-body" data-history-content></div>
            </section>
        </div>

        <div class="vitals-modal" data-vitals-modal hidden>
            <div class="vitals-modal-backdrop" data-vitals-close></div>
            <form class="vitals-dialog" data-vitals-form novalidate>
                <input type="hidden" name="record_id" data-vitals-record-id>
                <header class="vitals-dialog-header">
                    <h2 data-vitals-title>Add Maternal Vitals</h2>
                    <button type="button" data-vitals-close aria-label="Close update vitals modal">&times;</button>
                </header>

                <div class="vitals-warning" data-vitals-warning hidden>
                    {!! $iconAlert !!}
                    <div>
                        <strong>Review before saving</strong>
                        <ul data-vitals-warning-list></ul>
                        <p>Review the flagged values before saving this maternal vital record.</p>
                    </div>
                </div>

                <div class="vitals-field-grid">
                    <label>
                        <span>Record Date</span>
                        <input type="date" name="recorded_at" value="{{ $initialRecordDate }}">
                        <small data-field-error="recorded_at"></small>
                    </label>
                    <label>
                        <span>Pregnancy Week</span>
                        <input type="number" name="pregnancy_week" min="1" max="42" value="{{ $pregnancyWeek ?: '' }}">
                        <small data-field-error="pregnancy_week"></small>
                    </label>
                    <label>
                        <span>Weight (kg)</span>
                        <input type="number" name="weight" step="0.1" min="0.1" value="{{ $weightNumber ?: '' }}">
                        <small data-field-error="weight"></small>
                    </label>
                    <label>
                        <span>Systolic BP</span>
                        <input type="number" name="bp_systolic" min="60" max="220" value="{{ $latestRecord?->bp_systolic ?: '' }}">
                        <small data-field-error="bp_systolic"></small>
                    </label>
                    <label>
                        <span>Diastolic BP</span>
                        <input type="number" name="bp_diastolic" min="40" max="140" value="{{ $latestRecord?->bp_diastolic ?: '' }}">
                        <small data-field-error="bp_diastolic"></small>
                    </label>
                    <label>
                        <span>Blood Sugar (mg/dL)</span>
                        <input type="number" name="blood_sugar" step="0.1" min="40" max="400" value="{{ $latestRecord?->blood_sugar ?: '' }}">
                        <small data-field-error="blood_sugar"></small>
                    </label>
                    <label>
                        <span>Hemoglobin (g/dL)</span>
                        <input type="number" name="hemoglobin" step="0.1" min="5" max="25" value="{{ $latestRecord?->hemoglobin ?: '' }}">
                        <small data-field-error="hemoglobin"></small>
                    </label>
                    <label>
                        <span>Temperature (C)</span>
                        <input type="number" name="temperature" step="0.1" min="34" max="43" value="{{ $latestRecord?->temperature ?: '' }}">
                        <small data-field-error="temperature"></small>
                    </label>
                    <label>
                        <span>Heart Rate</span>
                        <input type="number" name="heart_rate" min="40" max="180" value="{{ $latestRecord?->heart_rate ?: '' }}">
                        <small data-field-error="heart_rate"></small>
                    </label>
                    <label class="is-wide">
                        <span>Program Staff Notes</span>
                        <textarea name="notes" rows="4" placeholder="Document symptoms, advice, referral, or follow-up instructions...">{{ $latestRecord?->notes }}</textarea>
                        <small data-field-error="notes"></small>
                    </label>
                </div>

                <footer class="vitals-dialog-footer">
                    <button type="button" class="vitals-cancel" data-vitals-close>Cancel</button>
                    <button type="submit" class="vitals-save" data-vitals-save>
                        <span data-vitals-save-text>Save Vitals</span>
                        <span class="vitals-spinner" hidden data-vitals-spinner></span>
                    </button>
                </footer>
            </form>
        </div>
    </section>

    <script>
        (() => {
            const tabs = Array.from(document.querySelectorAll('[data-casefile-tab]'));
            const panels = Array.from(document.querySelectorAll('[data-casefile-panel]'));
            let vitalsState = @json($maternalVitalsPayload);

            const csrfToken = '{{ csrf_token() }}';
            const motherVitalsUrl = '{{ route('api.staff.maternal-vitals.store', $mother) }}';
            const updateVitalsUrl = (id) => `/api/program-staff/maternal-vitals/${id}`;
            const deleteVitalsUrl = (id) => `/api/program-staff/maternal-vitals/${id}`;
            const modal = document.querySelector('[data-vitals-modal]');
            const form = document.querySelector('[data-vitals-form]');
            const warning = document.querySelector('[data-vitals-warning]');
            const warningList = document.querySelector('[data-vitals-warning-list]');
            const saveButton = document.querySelector('[data-vitals-save]');
            const saveText = document.querySelector('[data-vitals-save-text]');
            const spinner = document.querySelector('[data-vitals-spinner]');
            const success = document.querySelector('[data-vitals-success]');
            const historyModal = document.querySelector('[data-history-modal]');
            const historyTitle = document.querySelector('[data-history-title]');
            const historyDescription = document.querySelector('[data-history-description]');
            const historySearch = document.querySelector('[data-history-search]');
            const historySort = document.querySelector('[data-history-sort]');
            const historyCountCopy = document.querySelector('[data-history-count-copy]');
            const historyContent = document.querySelector('[data-history-content]');
            let activeHistoryType = 'weight';
            let historySortDirection = 'desc';
            const touched = new Set();

            const number = (value, decimals = 0) => {
                if (value === null || value === undefined || value === '') return null;
                const parsed = Number(value);
                if (!Number.isFinite(parsed)) return null;
                return decimals ? parsed.toFixed(decimals).replace(/\.0+$/, '').replace(/(\.\d*[1-9])0+$/, '$1') : String(Math.round(parsed));
            };

            const escapeHtml = (value) => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const setText = (selector, value) => {
                const node = document.querySelector(selector);
                if (node) node.textContent = value;
            };

            const setAllText = (selector, value) => {
                document.querySelectorAll(selector).forEach((node) => {
                    node.textContent = value;
                });
            };

            const setRiskClass = (node, risk) => {
                if (!node) return;
                node.classList.remove('is-low', 'is-medium', 'is-high', 'is-pending');
                node.classList.add(['low', 'medium', 'high'].includes(risk) ? `is-${risk}` : 'is-pending');
            };

            const statusFor = (latest, key) => {
                if (!latest) return 'Pending';
                if (key === 'blood_pressure') {
                    return latest.bp_systolic < 140 && latest.bp_diastolic < 90 ? 'Normal' : 'Review';
                }
                if (key === 'blood_sugar') return latest.blood_sugar >= 70 && latest.blood_sugar <= 140 ? 'Normal' : 'Review';
                if (key === 'hemoglobin') return latest.hemoglobin >= 11 ? 'Normal' : 'Review';
                if (key === 'weight') return 'Logged';
                return 'Logged';
            };

            const recordCountLabel = (count) => `${count} Record${count === 1 ? '' : 's'}`;

            const valueText = (value, unit, decimals = 0) => {
                const formatted = number(value, decimals);
                return formatted === null ? 'N/A' : `${formatted} ${unit}`;
            };

            const weightStatus = (weight) => {
                const value = Number(weight);
                if (!Number.isFinite(value) || value <= 0) return { label: 'Warning', className: 'is-warning' };
                if (value < 35 || value > 180) return { label: 'Critical', className: 'is-critical' };
                if (value < 45 || value > 130) return { label: 'Warning', className: 'is-warning' };
                return { label: 'Normal', className: 'is-good' };
            };

            const bpStatus = (systolic, diastolic) => {
                const sys = Number(systolic);
                const dia = Number(diastolic);
                if (!Number.isFinite(sys) || !Number.isFinite(dia)) return { label: 'Warning', className: 'is-warning' };
                if (sys >= 160 || dia >= 110) return { label: 'Critical', className: 'is-critical' };
                if (sys >= 140 || dia >= 90) return { label: 'Warning', className: 'is-warning' };
                return { label: 'Normal', className: 'is-good' };
            };

            const normalizeDate = (value) => {
                if (!value) return 0;
                const timestamp = new Date(value).getTime();
                return Number.isFinite(timestamp) ? timestamp : 0;
            };

            const historyConfig = () => activeHistoryType === 'weight'
                ? {
                    title: 'Weight History',
                    description: 'Complete weight readings from recorded maternal vitals.',
                    empty: 'No weight history available yet.',
                    rows: vitalsState.weight_history || [],
                    headers: ['Date', 'Pregnancy Week', 'Weight', 'Status'],
                }
                : {
                    title: 'Blood Pressure History',
                    description: 'Complete systolic and diastolic readings from recorded maternal vitals.',
                    empty: 'No blood pressure history available yet.',
                    rows: vitalsState.blood_pressure_history || [],
                    headers: ['Date', 'Pregnancy Week', 'Systolic', 'Diastolic', 'Blood Pressure Status'],
                };

            const historyMatches = (item, status) => {
                const query = (historySearch?.value || '').trim().toLowerCase();
                if (!query) return true;
                const searchable = activeHistoryType === 'weight'
                    ? [
                        item.recorded_label,
                        item.recorded_at,
                        `week ${item.pregnancy_week || 'N/A'}`,
                        item.pregnancy_week,
                        valueText(item.weight, 'kg', 1),
                        status.label,
                    ]
                    : [
                        item.recorded_label,
                        item.recorded_at,
                        `week ${item.pregnancy_week || 'N/A'}`,
                        item.pregnancy_week,
                        item.systolic,
                        item.diastolic,
                        `${item.systolic}/${item.diastolic}`,
                        status.label,
                    ];
                return searchable.some((value) => String(value ?? '').toLowerCase().includes(query));
            };

            const sortedHistoryRows = (rows) => [...rows].sort((a, b) => {
                const diff = normalizeDate(a.recorded_at) - normalizeDate(b.recorded_at);
                return historySortDirection === 'asc' ? diff : -diff;
            });

            const renderHistoryModal = () => {
                if (!historyModal || !historyContent) return;
                const config = historyConfig();
                const rows = sortedHistoryRows(config.rows).filter((item) => {
                    const status = activeHistoryType === 'weight' ? weightStatus(item.weight) : bpStatus(item.systolic, item.diastolic);
                    return historyMatches(item, status);
                });

                historyTitle.textContent = config.title;
                historyDescription.textContent = config.description;
                historySort.textContent = historySortDirection === 'desc' ? 'Newest First' : 'Oldest First';
                historyCountCopy.textContent = `Showing ${rows.length} of ${config.rows.length} ${config.rows.length === 1 ? 'record' : 'records'}`;

                if (!config.rows.length) {
                    historyContent.innerHTML = `<p class="history-empty">${config.empty}</p>`;
                    return;
                }

                if (!rows.length) {
                    historyContent.innerHTML = '<p class="history-empty">No matching history records found.</p>';
                    return;
                }

                const actionHeader = '<th>Actions</th>';
                const tableRows = rows.map((item) => {
                    if (activeHistoryType === 'weight') {
                        const status = weightStatus(item.weight);
                        return `
                            <tr>
                                <td class="is-main">${escapeHtml(item.recorded_label || 'Date not recorded')}</td>
                                <td>Week ${escapeHtml(item.pregnancy_week || 'N/A')}</td>
                                <td class="is-main">${escapeHtml(valueText(item.weight, 'kg', 1))}</td>
                                <td><span class="history-status ${status.className}">${status.label}</span></td>
                                <td>
                                    <div class="history-row-actions">
                                        <button type="button" class="is-edit" data-history-edit-record="${item.id}">Edit</button>
                                        <button type="button" class="is-delete" data-history-delete-record="${item.id}">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    }

                    const status = bpStatus(item.systolic, item.diastolic);
                    return `
                        <tr>
                            <td class="is-main">${escapeHtml(item.recorded_label || 'Date not recorded')}</td>
                            <td>Week ${escapeHtml(item.pregnancy_week || 'N/A')}</td>
                            <td class="is-red">${escapeHtml(valueText(item.systolic, 'mmHg'))}</td>
                            <td class="is-blue">${escapeHtml(valueText(item.diastolic, 'mmHg'))}</td>
                            <td><span class="history-status ${status.className}">${status.label}</span></td>
                            <td>
                                <div class="history-row-actions">
                                    <button type="button" class="is-edit" data-history-edit-record="${item.id}">Edit</button>
                                    <button type="button" class="is-delete" data-history-delete-record="${item.id}">Delete</button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');

                historyContent.innerHTML = `
                    <div class="history-table-scroll">
                        <table class="history-table">
                            <thead><tr>${config.headers.map((header) => `<th>${header}</th>`).join('')}${actionHeader}</tr></thead>
                            <tbody>${tableRows}</tbody>
                        </table>
                    </div>
                `;
            };

            const openHistoryModal = (type) => {
                activeHistoryType = type === 'bp' ? 'bp' : 'weight';
                historySortDirection = 'desc';
                if (historySearch) historySearch.value = '';
                renderHistoryModal();
                historyModal.hidden = false;
                document.body.classList.add('has-monitoring-history-modal');
                historySearch?.focus();
            };

            const closeHistoryModal = () => {
                if (!historyModal) return;
                historyModal.hidden = true;
                document.body.classList.remove('has-monitoring-history-modal');
            };

            const renderChartEmpty = (message) => `
                <svg viewBox="0 0 640 260" role="img" aria-label="${escapeHtml(message)}">
                    <path d="M70 26v190h520" class="axis"/>
                    <path d="M70 58h520M70 102h520M70 146h520M70 190h520" class="grid"/>
                    <text x="260" y="130" class="empty">${escapeHtml(message)}</text>
                </svg>
            `;

            const chartScales = (values, fallbackMin, fallbackMax) => {
                if (!values.length) return { min: fallbackMin, max: fallbackMax };
                let min = Math.min(...values);
                let max = Math.max(...values);
                if (min === max) {
                    min -= 2;
                    max += 2;
                }
                const pad = Math.max(1, (max - min) * 0.15);
                return { min: Math.floor(min - pad), max: Math.ceil(max + pad) };
            };

            const chartPoint = (index, count, value, min, max) => {
                const left = 76;
                const right = 48;
                const top = 30;
                const bottom = 42;
                const width = 640;
                const height = 260;
                const x = count <= 1 ? (width - right + left) / 2 : left + (index * ((width - left - right) / (count - 1)));
                const y = top + ((max - value) / (max - min)) * (height - top - bottom);
                return { x, y };
            };

            const renderWeightChart = (history) => {
                const target = document.querySelector('[data-chart="weight"]');
                if (!target) return;
                if (!history.length) {
                    target.innerHTML = renderChartEmpty('No weight record yet');
                    return;
                }
                const values = history.map((item) => Number(item.weight));
                const { min, max } = chartScales(values, 70, 80);
                const labels = [max, Math.round((max + min) / 2), min];
                const points = history.map((item, index) => ({ ...item, ...chartPoint(index, history.length, Number(item.weight), min, max) }));
                const polyline = points.map((point) => `${point.x},${point.y}`).join(' ');
                target.innerHTML = `
                    <svg viewBox="0 0 640 260" role="img" aria-label="Weight progression chart">
                        <path d="M76 30v188h516" class="axis"/>
                        <path d="M76 62h516M76 112h516M76 162h516M76 212h516" class="grid"/>
                        ${labels.map((label, index) => `<text x="24" y="${66 + index * 74}">${label} kg</text>`).join('')}
                        ${points.length > 1 ? `<polyline points="${polyline}" class="weight-line"/>` : ''}
                        ${points.map((point) => `<circle cx="${point.x}" cy="${point.y}" r="5" class="weight-dot"><title>${escapeHtml(point.tooltip)}</title></circle>`).join('')}
                        ${points.map((point) => `<text x="${point.x - 18}" y="244">${escapeHtml(point.label)}</text>`).join('')}
                    </svg>
                `;
            };

            const renderBpChart = (history) => {
                const target = document.querySelector('[data-chart="bp"]');
                if (!target) return;
                if (!history.length) {
                    target.innerHTML = renderChartEmpty('No blood pressure record yet');
                    return;
                }
                const values = history.flatMap((item) => [Number(item.systolic), Number(item.diastolic)]);
                const { min, max } = chartScales(values, 70, 130);
                const labels = [max, Math.round((max + min) / 2), min];
                const systolic = history.map((item, index) => ({ ...item, ...chartPoint(index, history.length, Number(item.systolic), min, max) }));
                const diastolic = history.map((item, index) => ({ ...item, ...chartPoint(index, history.length, Number(item.diastolic), min, max) }));
                target.innerHTML = `
                    <svg viewBox="0 0 640 260" role="img" aria-label="Blood pressure trends chart">
                        <path d="M76 30v188h516" class="axis"/>
                        <path d="M76 62h516M76 112h516M76 162h516M76 212h516" class="grid"/>
                        ${labels.map((label, index) => `<text x="32" y="${66 + index * 74}">${label}</text>`).join('')}
                        ${systolic.length > 1 ? `<polyline points="${systolic.map((point) => `${point.x},${point.y}`).join(' ')}" class="systolic-line"/>` : ''}
                        ${diastolic.length > 1 ? `<polyline points="${diastolic.map((point) => `${point.x},${point.y}`).join(' ')}" class="diastolic-line"/>` : ''}
                        ${systolic.map((point) => `<circle cx="${point.x}" cy="${point.y}" r="5" class="systolic-dot"><title>${escapeHtml(point.tooltip)}</title></circle>`).join('')}
                        ${diastolic.map((point) => `<circle cx="${point.x}" cy="${point.y}" r="5" class="diastolic-dot"><title>${escapeHtml(point.tooltip)}</title></circle>`).join('')}
                        ${systolic.map((point) => `<text x="${point.x - 18}" y="244">${escapeHtml(point.label)}</text>`).join('')}
                    </svg>
                `;
            };

            const renderRecordList = (records) => {
                const list = document.querySelector('[data-record-list]');
                if (!list) return;
                if (!records.length) {
                    list.innerHTML = '<p class="casefile-panel-note">No maternal monitoring record has been saved yet.</p>';
                    return;
                }
                list.innerHTML = [...records].reverse().map((record) => `
                    <div class="casefile-record-row">
                        <strong>Week ${record.pregnancy_week || 'N/A'} &middot; Month ${record.pregnancy_month || 'N/A'}</strong>
                        <span>BP ${record.blood_pressure ? `${record.blood_pressure} mmHg` : 'Not logged'}</span>
                        <span>Weight ${record.weight === null ? 'Not logged' : `${number(record.weight, 1)} kg`}</span>
                        <span>${escapeHtml(record.recorded_label || 'Date not recorded')}</span>
                        <button type="button" data-edit-record="${record.id}">Edit</button>
                    </div>
                `).join('');
            };

            const renderVitals = (payload) => {
                vitalsState = payload;
                const latest = payload.latest;
                const risk = latest?.risk_level || 'pending';
                const riskLabel = payload.risk_label || latest?.risk_label || 'Pending';
                const recordCount = payload.records?.length || 0;
                const visitCount = Math.min(recordCount, 8);
                const visitPercent = Math.min(100, Math.round((visitCount / 8) * 100));

                setText('[data-vital-value="blood_pressure"]', latest?.blood_pressure ? `${latest.blood_pressure} mmHg` : 'Not logged');
                setText('[data-vital-value="blood_sugar"]', latest?.blood_sugar === null || !latest ? 'Not logged' : `${number(latest.blood_sugar, 1)} mg/dL`);
                setText('[data-vital-value="weight"]', latest?.weight === null || !latest ? 'Not logged' : `${number(latest.weight, 1)} kg`);
                setText('[data-vital-value="hemoglobin"]', latest?.hemoglobin === null || !latest ? 'Not logged' : `${number(latest.hemoglobin, 1)} g/dL`);
                ['blood_pressure', 'blood_sugar', 'weight', 'hemoglobin'].forEach((key) => setText(`[data-vital-status="${key}"]`, statusFor(latest, key)));
                setText('[data-risk-label]', riskLabel);
                setText('[data-summary-risk]', riskLabel);
                setRiskClass(document.querySelector('[data-risk-label]'), risk);
                setText('[data-monitoring-count]', recordCount);
                setAllText('[data-history-count="weight"]', recordCountLabel(payload.weight_history?.length || 0));
                setAllText('[data-history-count="bp"]', recordCountLabel(payload.blood_pressure_history?.length || 0));
                setText('[data-visit-count]', `${visitCount}/8`);
                setText('[data-visit-copy]', `${visitPercent}% of expected visits logged`);
                const visitProgress = document.querySelector('[data-visit-progress]');
                if (visitProgress) visitProgress.style.setProperty('--progress', `${visitPercent}%`);
                const banner = document.querySelector('[data-vitals-banner]');
                if (banner) {
                    banner.textContent = risk === 'low'
                        ? 'All available maternal indicators are within healthy pregnancy thresholds.'
                        : 'Review the latest maternal indicators and follow up as needed.';
                }
                renderWeightChart(payload.weight_history || []);
                renderBpChart(payload.blood_pressure_history || []);
                renderRecordList(payload.records || []);
                if (historyModal && !historyModal.hidden) renderHistoryModal();
            };

            const fieldRules = {
                recorded_at: (value) => value ? '' : 'Record date is required.',
                pregnancy_week: (value) => Number(value) >= 1 && Number(value) <= 42 ? '' : 'Pregnancy week must be between 1 and 42.',
                weight: (value) => Number(value) > 0 ? '' : 'Weight must be a valid positive number.',
                bp_systolic: (value) => Number(value) >= 60 && Number(value) <= 220 ? '' : 'Systolic BP must be from 60 to 220 mmHg.',
                bp_diastolic: (value, values) => {
                    if (!(Number(value) >= 40 && Number(value) <= 140)) return 'Diastolic BP must be from 40 to 140 mmHg.';
                    if (Number(value) >= Number(values.bp_systolic)) return 'Diastolic BP should be lower than systolic BP.';
                    return '';
                },
                blood_sugar: (value) => Number(value) >= 40 && Number(value) <= 400 ? '' : 'Blood sugar must be from 40 to 400 mg/dL.',
                hemoglobin: (value) => Number(value) >= 5 && Number(value) <= 25 ? '' : 'Hemoglobin must be from 5 to 25 g/dL.',
                temperature: (value) => Number(value) >= 34 && Number(value) <= 43 ? '' : 'Body temperature must be entered in Celsius from 34 to 43 C.',
                heart_rate: (value) => Number(value) >= 40 && Number(value) <= 180 ? '' : 'Heart rate must be from 40 to 180 bpm.',
            };

            const formValues = () => Object.fromEntries(new FormData(form).entries());

            const setErrors = (errors, showGlobal = false) => {
                form.querySelectorAll('[data-field-error]').forEach((node) => {
                    const name = node.dataset.fieldError;
                    const message = errors[name]?.[0] || '';
                    node.textContent = message;
                    node.closest('label')?.classList.toggle('has-error', Boolean(message));
                });

                const messages = Object.values(errors).flat();
                if (showGlobal && messages.length) {
                    warningList.innerHTML = messages.map((message) => `<li>${escapeHtml(message)}</li>`).join('');
                    warning.hidden = false;
                } else {
                    warning.hidden = true;
                }
            };

            const validateClient = (showAll = false) => {
                const values = formValues();
                const errors = {};
                Object.entries(fieldRules).forEach(([field, rule]) => {
                    const message = rule(values[field], values);
                    if (message && (showAll || touched.has(field))) {
                        errors[field] = [message];
                    }
                });
                setErrors(errors, showAll);
                return errors;
            };

            const fillForm = (record = null) => {
                form.reset();
                form.querySelector('[data-vitals-record-id]').value = record?.id || '';
                form.querySelector('[name="recorded_at"]').value = record?.recorded_at || new Date().toISOString().slice(0, 10);
                form.querySelector('[name="pregnancy_week"]').value = record?.pregnancy_week || vitalsState.latest?.pregnancy_week || '';
                ['weight', 'bp_systolic', 'bp_diastolic', 'blood_sugar', 'hemoglobin', 'temperature', 'heart_rate', 'notes'].forEach((field) => {
                    form.querySelector(`[name="${field}"]`).value = record?.[field] ?? '';
                });
                document.querySelector('[data-vitals-title]').textContent = record ? 'Edit Maternal Vitals' : 'Add Maternal Vitals';
                saveText.textContent = record ? 'Update Vitals' : 'Save Vitals';
                touched.clear();
                setErrors({});
            };

            const openModal = (record = null) => {
                fillForm(record);
                modal.hidden = false;
                document.body.classList.add('has-casefile-modal');
                form.querySelector('[name="recorded_at"]').focus();
            };

            const closeModal = () => {
                modal.hidden = true;
                document.body.classList.remove('has-casefile-modal');
                setErrors({});
                touched.clear();
            };

            const setSaving = (isSaving) => {
                saveButton.disabled = isSaving;
                spinner.hidden = !isSaving;
                saveText.textContent = isSaving ? 'Saving...' : (form.querySelector('[data-vitals-record-id]').value ? 'Update Vitals' : 'Save Vitals');
            };

            tabs.forEach((tab) => {
                tab.addEventListener('click', () => {
                    const target = tab.dataset.casefileTab;
                    tabs.forEach((item) => item.classList.toggle('is-active', item === tab));
                    panels.forEach((panel) => {
                        panel.hidden = panel.dataset.casefilePanel !== target;
                    });
                });
            });

            document.querySelectorAll('[data-casefile-print]').forEach((button) => {
                button.addEventListener('click', () => window.print());
            });

            document.querySelector('[data-vitals-open]')?.addEventListener('click', () => openModal());
            modal?.querySelectorAll('[data-vitals-close]').forEach((button) => button.addEventListener('click', closeModal));
            historyModal?.querySelectorAll('[data-history-close]').forEach((button) => button.addEventListener('click', closeHistoryModal));
            historySearch?.addEventListener('input', renderHistoryModal);
            historySort?.addEventListener('click', () => {
                historySortDirection = historySortDirection === 'desc' ? 'asc' : 'desc';
                renderHistoryModal();
            });
            form?.addEventListener('input', (event) => {
                if (event.target.name) {
                    touched.add(event.target.name);
                    validateClient(false);
                }
            });
            document.addEventListener('click', async (event) => {
                const historyButton = event.target.closest('[data-history-open]');
                if (historyButton) {
                    event.preventDefault();
                    openHistoryModal(historyButton.dataset.historyOpen);
                    return;
                }

                const historyEditButton = event.target.closest('[data-history-edit-record]');
                if (historyEditButton) {
                    const record = (vitalsState.records || []).find((item) => String(item.id) === String(historyEditButton.dataset.historyEditRecord));
                    if (record) {
                        closeHistoryModal();
                        openModal(record);
                    }
                    return;
                }

                const historyDeleteButton = event.target.closest('[data-history-delete-record]');
                if (historyDeleteButton) {
                    const recordId = historyDeleteButton.dataset.historyDeleteRecord;
                    if (!confirm('Delete this maternal vital record? This will update the latest vitals, charts, and history.')) return;

                    historyDeleteButton.disabled = true;

                    try {
                        const response = await fetch(deleteVitalsUrl(recordId), {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                        });
                        const data = await response.json();

                        if (!response.ok) {
                            alert(data.message || 'Unable to delete this maternal vital record.');
                            return;
                        }

                        renderVitals(data);
                        if (success) {
                            success.textContent = data.message || 'Maternal vitals deleted.';
                            success.hidden = false;
                            setTimeout(() => { success.hidden = true; }, 3200);
                        }
                    } catch (error) {
                        alert('Unable to reach the server. Please try again.');
                    } finally {
                        historyDeleteButton.disabled = false;
                    }
                    return;
                }

                const editButton = event.target.closest('[data-edit-record]');
                if (!editButton) return;
                const record = (vitalsState.records || []).find((item) => String(item.id) === String(editButton.dataset.editRecord));
                if (record) openModal(record);
            });
            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') return;
                if (historyModal && !historyModal.hidden) {
                    closeHistoryModal();
                    return;
                }
                if (modal && !modal.hidden) closeModal();
            });
            form?.addEventListener('submit', async (event) => {
                event.preventDefault();
                const clientErrors = validateClient(true);
                if (Object.keys(clientErrors).length) return;

                const recordId = form.querySelector('[data-vitals-record-id]').value;
                setSaving(true);

                try {
                    const response = await fetch(recordId ? updateVitalsUrl(recordId) : motherVitalsUrl, {
                        method: recordId ? 'PUT' : 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify(formValues()),
                    });
                    const data = await response.json();

                    if (!response.ok) {
                        setErrors(data.errors || { form: [data.message || 'Unable to save maternal vitals.'] }, true);
                        return;
                    }

                    renderVitals(data);
                    closeModal();
                    if (success) {
                        success.textContent = data.message || 'Maternal vitals saved.';
                        success.hidden = false;
                        setTimeout(() => { success.hidden = true; }, 3200);
                    }
                } catch (error) {
                    setErrors({ form: ['Unable to reach the server. Please try again.'] }, true);
                } finally {
                    setSaving(false);
                }
            });

            renderVitals(vitalsState);
        })();
    </script>
@endsection
