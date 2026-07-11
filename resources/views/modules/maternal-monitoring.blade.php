@extends('layouts.app')

@section('title', 'Maternal Monitoring - Project INAY')
@section('portal_title', 'Maternal Monitoring')

@php
    $hasRecord = (bool) $latestRecord;
    $recordCount = $records->count();
    $weightRecords = $records->filter(fn ($record) => $record->weight !== null)->values();
    $bpRecords = $records->filter(fn ($record) => $record->bp_systolic !== null && $record->bp_diastolic !== null)->values();
    $recordedDate = ($latestRecord?->recorded_at ?? $latestRecord?->created_at)?->format('M j, Y') ?? 'Awaiting record';
    $pregnancyWeek = $latestRecord?->pregnancy_week;
    $pregnancyMonth = $latestRecord?->pregnancy_month;
    $syncBadge = $pregnancyWeek && $pregnancyMonth ? "Week {$pregnancyWeek} - Month {$pregnancyMonth}" : 'Monitoring pending';

    $formatNumber = function ($value, int $decimals = 0) {
        if ($value === null) return 'N/A';

        $formatted = number_format((float) $value, $decimals);
        return $decimals > 0 ? rtrim(rtrim($formatted, '0'), '.') : $formatted;
    };
    $withUnit = fn ($value, string $unit, int $decimals = 0) => $value === null ? 'N/A' : $formatNumber($value, $decimals).' '.$unit;

    $bpSystolic = $latestRecord?->bp_systolic;
    $bpDiastolic = $latestRecord?->bp_diastolic;
    $hasBp = $bpSystolic !== null && $bpDiastolic !== null;
    $bpValue = $hasBp ? "{$bpSystolic}/{$bpDiastolic}" : 'N/A';
    $bpNormal = $hasBp ? ($bpSystolic < 130 && $bpDiastolic < 85) : null;

    $bloodSugar = $latestRecord?->blood_sugar;
    $bloodSugarNormal = $bloodSugar !== null ? ((float) $bloodSugar >= 70 && (float) $bloodSugar <= 140) : null;

    $weight = $latestRecord?->weight;
    $previousWeight = $weightRecords->get(1)?->weight;
    $oldestWeight = $weightRecords->last()?->weight;
    $totalGain = $weight !== null && $oldestWeight !== null && $weightRecords->count() > 1 ? (float) $weight - (float) $oldestWeight : null;
    $gainStatus = $totalGain === null ? 'Awaiting baseline' : ($totalGain >= 11 && $totalGain <= 16 ? 'Within recommended range' : 'Review needed');

    $hemoglobin = $latestRecord?->hemoglobin;
    $hemoglobinNormal = $hemoglobin !== null ? (float) $hemoglobin >= 11.5 : null;

    $riskLevel = strtolower($latestRecord?->risk_level ?? 'pending');
    $riskTitle = match ($riskLevel) {
        'low' => 'Low Risk',
        'medium' => 'Needs Review',
        'high' => 'High Risk',
        default => 'Awaiting Record',
    };
    $riskCopy = match ($riskLevel) {
        'low' => 'All maternal indicators are within healthy pregnancy thresholds. Continue taking prenatal vitamins and maintain healthy hydration. Attend your next prenatal checkup and keep weight logs updated.',
        'medium' => 'Some indicators need Program Staff review. Keep monitoring your symptoms and bring your latest records to your next checkup.',
        'high' => 'High-risk protocol may be needed. Contact your Program Staff or nearest clinic for immediate guidance.',
        default => 'Program Staff monitoring records will appear here once synced.',
    };

    $statusLabel = fn ($status) => $status === null ? 'Awaiting Record' : ($status ? 'Normal' : 'Needs Review');
    $statusClass = fn ($status) => $status === null ? 'is-neutral' : ($status ? 'is-good' : 'is-warning');

    $iconPulse = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>';
    $iconHeart = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 5.6a5.4 5.4 0 0 0-7.6 0L12 6.8l-1.2-1.2a5.4 5.4 0 1 0-7.6 7.6L12 22l8.8-8.8a5.4 5.4 0 0 0 0-7.6Z"/></svg>';
    $iconScale = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 16 8 8"/><path d="m8 16 8-8"/><path d="M12 3v18"/><path d="M4 7h16"/><path d="M5 7l-3 7h6L5 7Z"/><path d="m19 7-3 7h6l-3-7Z"/></svg>';
    $iconEye = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
    $iconCheck = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>';
    $iconCalendar = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="5" width="18" height="17" rx="2"/><path d="M3 10h18"/></svg>';
    $iconBell = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 7 3 9H3c0-2 3-2 3-9"/><path d="M10 21h4"/></svg>';
    $iconTrend = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 17 6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>';
    $iconHistory = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 3v6h6"/><path d="M12 7v5l3 2"/></svg>';
    $iconAlert = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m21.7 18-8-14a2 2 0 0 0-3.4 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>';
@endphp

@section('content')
    <section class="maternal-monitoring-shell" aria-label="Maternal monitoring">
        <header class="maternal-monitoring-heading">
            <div>
                <h1>Maternal Vitals Overview</h1>
                <p>Review weight and blood pressure records updated by Program Staff.</p>
            </div>
            <div class="maternal-heading-actions">
                <span class="maternal-chip is-pink">{{ $syncBadge }}</span>
                <span class="maternal-chip">{!! $iconBell !!} Synced monitoring</span>
            </div>
        </header>

        <section class="maternal-panel maternal-vitals-panel">
            <div class="maternal-panel-title">
                <div>
                    <h2>Maternal Vital Signs</h2>
                    <p>{{ $mother->full_name }}'s pregnancy threshold indicators</p>
                </div>
                <span class="maternal-risk-badge {{ $riskLevel === 'high' ? 'is-high' : ($riskLevel === 'medium' ? 'is-medium' : 'is-low') }}">{{ strtoupper($riskTitle) }}</span>
            </div>

            <div class="maternal-vitals-grid">
                <article class="maternal-vital-card">
                    <div class="maternal-vital-top"><span class="maternal-vital-icon is-pink">{!! $iconPulse !!}</span><span class="maternal-mini-badge {{ $statusClass($bpNormal) }}">{{ $statusLabel($bpNormal) }}</span></div>
                    <p>Blood Pressure</p>
                    <strong>{{ $bpValue }} <span>mmHg</span></strong>
                    <div><span>Healthy Range</span><b>&lt; 130/85 mmHg</b></div>
                </article>
                <article class="maternal-vital-card">
                    <div class="maternal-vital-top"><span class="maternal-vital-icon is-pink">{!! $iconHeart !!}</span><span class="maternal-mini-badge {{ $statusClass($bloodSugarNormal) }}">{{ $statusLabel($bloodSugarNormal) }}</span></div>
                    <p>Blood Sugar</p>
                    <strong>{{ $bloodSugar === null ? 'N/A' : $formatNumber($bloodSugar) }} @if($bloodSugar !== null)<span>mg/dL</span>@endif</strong>
                    <div><span>Healthy Range</span><b>70 - 140 mg/dL</b></div>
                </article>
                <article class="maternal-vital-card">
                    <div class="maternal-vital-top"><span class="maternal-vital-icon is-green">{!! $iconScale !!}</span><span class="maternal-mini-badge {{ $totalGain === null ? 'is-neutral' : ($gainStatus === 'Within recommended range' ? 'is-good' : 'is-warning') }}">{{ strtoupper($gainStatus) }}</span></div>
                    <p>Weight</p>
                    <strong>{{ $weight === null ? 'N/A' : $formatNumber($weight) }} @if($weight !== null)<span>kg</span>@endif</strong>
                    <div><span>Healthy Range</span><b>Target gain: 11 kg to 16 kg</b></div>
                </article>
                <article class="maternal-vital-card">
                    <div class="maternal-vital-top"><span class="maternal-vital-icon is-blue">{!! $iconEye !!}</span><span class="maternal-mini-badge {{ $statusClass($hemoglobinNormal) }}">{{ $statusLabel($hemoglobinNormal) }}</span></div>
                    <p>Hemoglobin</p>
                    <strong>{{ $hemoglobin === null ? 'N/A' : $formatNumber($hemoglobin, 1) }} @if($hemoglobin !== null)<span>g/dL</span>@endif</strong>
                    <div><span>Healthy Range</span><b>11.5 g/dL and above</b></div>
                </article>
            </div>

            <div class="maternal-good-banner">{!! $iconCheck !!} {{ $hasRecord ? 'Maternal indicators are synced from Program Staff monitoring records.' : 'No maternal monitoring record has been synced yet.' }}</div>
        </section>

        <div class="maternal-dashboard-grid">
            <section class="maternal-column">
                <div class="maternal-section-heading">
                    <div><span>Weight Progress</span><h2>Maternal Weight Progress Tracker</h2><p>Synced from Program Staff monitoring records.</p></div>
                    <span class="maternal-chip">{!! $iconEye !!} View Only</span>
                </div>

                <div class="maternal-stat-grid">
                    <article class="maternal-stat-card is-pink"><span>Current Weight</span><strong>{{ $withUnit($weight, 'kg') }}</strong><p>Latest recorded weight</p></article>
                    <article class="maternal-stat-card"><span>Previous Weight</span><strong>{{ $withUnit($previousWeight, 'kg') }}</strong><p>{{ $previousWeight === null ? 'Awaiting another record' : 'Previous monitoring record' }}</p></article>
                    <article class="maternal-stat-card is-green"><span>Total Gained</span><strong>{{ $withUnit($totalGain, 'kg') }}</strong><p>11 kg to 16 kg expected gain</p></article>
                    <article class="maternal-stat-card is-blue"><span>Expected Range</span><strong>11-16 kg</strong><p>Current healthy gain window</p></article>
                    <article class="maternal-stat-card"><span>Latest Date</span><strong>{{ $recordedDate }}</strong><p>{{ $hasRecord ? 'More data needed' : 'Awaiting Program Staff record' }}</p></article>
                </div>

                <div class="maternal-progress-card">
                    <div><span>Total gain progress</span><b>{{ strtoupper($gainStatus) }}</b></div>
                    <div class="maternal-progress-track"><span style="width: {{ $totalGain === null ? 0 : min(100, max(8, ($totalGain / 16) * 100)) }}%"></span></div>
                    <div class="maternal-progress-labels"><span>0 kg</span><span>11 kg target min</span><span>16 kg target max</span></div>
                </div>

                <article class="maternal-chart-card">
                    <span>Trend Chart</span>
                    <h3>Weight Progression</h3>
                    <i>{!! $iconTrend !!}</i>
                    <div class="maternal-chart-area" data-mother-chart="weight"></div>
                    <div class="maternal-chart-legend"><span class="is-pink"></span> Weight (kg)</div>
                </article>

                <button type="button" class="monitoring-history-trigger maternal-history-link" data-history-open="weight">
                    {!! $iconHistory !!}
                    <span>View Weight History</span>
                    <b data-history-count="weight">{{ $weightRecords->count() }} Record{{ $weightRecords->count() === 1 ? '' : 's' }}</b>
                </button>
            </section>

            <section class="maternal-column">
                <div class="maternal-section-heading">
                    <div><span>Blood Pressure Trend</span><h2>Blood Pressure Trend</h2><p>Systolic and diastolic readings update from recorded maternal vitals.</p></div>
                </div>

                <div class="maternal-stat-grid is-three">
                    <article class="maternal-stat-card is-red"><span>Latest Reading</span><strong>{{ $hasBp ? $bpValue.' mmHg' : 'N/A' }}</strong><p>{{ $recordedDate }}</p></article>
                    <article class="maternal-stat-card"><span>Systolic</span><strong>{{ $withUnit($bpSystolic, 'mmHg') }}</strong><p>Upper pressure reading</p></article>
                    <article class="maternal-stat-card is-green"><span>Current Status</span><strong>{{ $statusLabel($bpNormal) }}</strong><p>Diastolic {{ $withUnit($bpDiastolic, 'mmHg') }}</p></article>
                </div>

                <article class="maternal-chart-card">
                    <span>Trend Chart</span>
                    <h3>Blood Pressure Trends</h3>
                    <i>{!! $iconTrend !!}</i>
                    <div class="maternal-chart-area" data-mother-chart="bp"></div>
                    <div class="maternal-chart-legend"><span class="is-red"></span> Systolic <span class="is-blue"></span> Diastolic</div>
                </article>

                <button type="button" class="monitoring-history-trigger maternal-history-link" data-history-open="bp">
                    {!! $iconHistory !!}
                    <span>View Blood Pressure History</span>
                    <b data-history-count="bp">{{ $bpRecords->count() }} Record{{ $bpRecords->count() === 1 ? '' : 's' }}</b>
                </button>
            </section>
        </div>

        <div class="maternal-bottom-grid">
            <section class="maternal-panel">
                <h2>Pregnancy Risk Indicator</h2>
                <div class="maternal-risk-box {{ $riskLevel === 'high' ? 'is-high' : ($riskLevel === 'medium' ? 'is-medium' : 'is-low') }}">
                    <span>Risk Level</span>
                    <strong>{{ $riskTitle }}</strong>
                    <p>{{ $riskCopy }}</p>
                </div>
            </section>

            <section class="maternal-panel">
                <h2>Range Medical Guidelines</h2>
                <div class="maternal-guidelines-grid">
                    <article><span>Blood Pressure</span><p><b>Optimal:</b> &lt; 120/80 mmHg</p><p><b>Warning:</b> &gt;= 140/90 mmHg</p></article>
                    <article><span>Blood Sugar</span><p><b>Optimal:</b> &lt; 95 mg/dL fasting</p><p><b>Warning:</b> Post-meal must stay &lt; 140 mg/dL</p></article>
                    <article><span>Hemoglobin</span><p><b>Optimal:</b> &gt; 11.5 g/dL</p><p><b>Warning:</b> Anemia risk below 11 g/dL</p></article>
                    <article><span>Weight Gain</span><p><b>Optimal:</b> 11 - 16 kg total gain</p><p><b>Warning:</b> Review if outside recommended range</p></article>
                </div>
            </section>
        </div>

        <div class="maternal-warning-banner">{!! $iconAlert !!} High-risk pregnancy protocol activates automatically when Program Staff records warning values.</div>

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
    </section>

    <script>
        (() => {
            const payload = @json($maternalVitalsPayload);
            const historyModal = document.querySelector('[data-history-modal]');
            const historyTitle = document.querySelector('[data-history-title]');
            const historyDescription = document.querySelector('[data-history-description]');
            const historySearch = document.querySelector('[data-history-search]');
            const historySort = document.querySelector('[data-history-sort]');
            const historyCountCopy = document.querySelector('[data-history-count-copy]');
            const historyContent = document.querySelector('[data-history-content]');
            let activeHistoryType = 'weight';
            let historySortDirection = 'desc';

            const escapeHtml = (value) => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const number = (value, decimals = 0) => {
                if (value === null || value === undefined || value === '') return null;
                const parsed = Number(value);
                if (!Number.isFinite(parsed)) return null;
                return decimals ? parsed.toFixed(decimals).replace(/\.0+$/, '').replace(/(\.\d*[1-9])0+$/, '$1') : String(Math.round(parsed));
            };

            const recordCountLabel = (count) => `${count} Record${count === 1 ? '' : 's'}`;

            const valueText = (value, unit, decimals = 0) => {
                const formatted = number(value, decimals);
                return formatted === null ? 'N/A' : `${formatted} ${unit}`;
            };

            const setAllText = (selector, value) => {
                document.querySelectorAll(selector).forEach((node) => {
                    node.textContent = value;
                });
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
                    rows: payload.weight_history || [],
                    headers: ['Date', 'Pregnancy Week', 'Weight', 'Status'],
                }
                : {
                    title: 'Blood Pressure History',
                    description: 'Complete systolic and diastolic readings from recorded maternal vitals.',
                    empty: 'No blood pressure history available yet.',
                    rows: payload.blood_pressure_history || [],
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

                const tableRows = rows.map((item) => {
                    if (activeHistoryType === 'weight') {
                        const status = weightStatus(item.weight);
                        return `
                            <tr>
                                <td class="is-main">${escapeHtml(item.recorded_label || 'Date not recorded')}</td>
                                <td>Week ${escapeHtml(item.pregnancy_week || 'N/A')}</td>
                                <td class="is-main">${escapeHtml(valueText(item.weight, 'kg', 1))}</td>
                                <td><span class="history-status ${status.className}">${status.label}</span></td>
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
                        </tr>
                    `;
                }).join('');

                historyContent.innerHTML = `
                    <div class="history-table-scroll">
                        <table class="history-table">
                            <thead><tr>${config.headers.map((header) => `<th>${header}</th>`).join('')}</tr></thead>
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
            const point = (index, count, value, min, max) => {
                const left = 76;
                const right = 48;
                const top = 30;
                const bottom = 42;
                const width = 640;
                const height = 260;
                return {
                    x: count <= 1 ? (width - right + left) / 2 : left + (index * ((width - left - right) / (count - 1))),
                    y: top + ((max - value) / (max - min)) * (height - top - bottom),
                };
            };
            const emptyChart = (message) => `
                <svg viewBox="0 0 640 260" role="img" aria-label="${escapeHtml(message)}">
                    <path d="M76 30v188h516" class="axis"/>
                    <path d="M76 62h516M76 112h516M76 162h516M76 212h516" class="grid"/>
                    <text x="250" y="132" class="empty">${escapeHtml(message)}</text>
                </svg>
            `;
            const renderWeight = () => {
                const target = document.querySelector('[data-mother-chart="weight"]');
                const history = payload.weight_history || [];
                if (!target) return;
                if (!history.length) {
                    target.innerHTML = emptyChart('No weight record yet');
                    return;
                }
                const values = history.map((item) => Number(item.weight));
                const { min, max } = chartScales(values, 70, 80);
                const labels = [max, Math.round((max + min) / 2), min];
                const points = history.map((item, index) => ({ ...item, ...point(index, history.length, Number(item.weight), min, max) }));
                target.innerHTML = `
                    <svg viewBox="0 0 640 260" role="img" aria-label="Weight progression chart">
                        <path d="M76 30v188h516" class="axis"/>
                        <path d="M76 62h516M76 112h516M76 162h516M76 212h516" class="grid"/>
                        ${labels.map((label, index) => `<text x="24" y="${66 + index * 74}">${label} kg</text>`).join('')}
                        ${points.length > 1 ? `<polyline points="${points.map((item) => `${item.x},${item.y}`).join(' ')}" class="weight-line"/>` : ''}
                        ${points.map((item) => `<circle cx="${item.x}" cy="${item.y}" r="5" class="weight-dot"><title>${escapeHtml(item.tooltip)}</title></circle>`).join('')}
                        ${points.map((item) => `<text x="${item.x - 18}" y="244">${escapeHtml(item.label)}</text>`).join('')}
                    </svg>
                `;
            };
            const renderBp = () => {
                const target = document.querySelector('[data-mother-chart="bp"]');
                const history = payload.blood_pressure_history || [];
                if (!target) return;
                if (!history.length) {
                    target.innerHTML = emptyChart('No blood pressure record yet');
                    return;
                }
                const values = history.flatMap((item) => [Number(item.systolic), Number(item.diastolic)]);
                const { min, max } = chartScales(values, 70, 130);
                const labels = [max, Math.round((max + min) / 2), min];
                const systolic = history.map((item, index) => ({ ...item, ...point(index, history.length, Number(item.systolic), min, max) }));
                const diastolic = history.map((item, index) => ({ ...item, ...point(index, history.length, Number(item.diastolic), min, max) }));
                target.innerHTML = `
                    <svg viewBox="0 0 640 260" role="img" aria-label="Blood pressure trends chart">
                        <path d="M76 30v188h516" class="axis"/>
                        <path d="M76 62h516M76 112h516M76 162h516M76 212h516" class="grid"/>
                        ${labels.map((label, index) => `<text x="32" y="${66 + index * 74}">${label}</text>`).join('')}
                        ${systolic.length > 1 ? `<polyline points="${systolic.map((item) => `${item.x},${item.y}`).join(' ')}" class="systolic-line"/>` : ''}
                        ${diastolic.length > 1 ? `<polyline points="${diastolic.map((item) => `${item.x},${item.y}`).join(' ')}" class="diastolic-line"/>` : ''}
                        ${systolic.map((item) => `<circle cx="${item.x}" cy="${item.y}" r="5" class="systolic-dot"><title>${escapeHtml(item.tooltip)}</title></circle>`).join('')}
                        ${diastolic.map((item) => `<circle cx="${item.x}" cy="${item.y}" r="5" class="diastolic-dot"><title>${escapeHtml(item.tooltip)}</title></circle>`).join('')}
                        ${systolic.map((item) => `<text x="${item.x - 18}" y="244">${escapeHtml(item.label)}</text>`).join('')}
                    </svg>
                `;
            };
            setAllText('[data-history-count="weight"]', recordCountLabel((payload.weight_history || []).length));
            setAllText('[data-history-count="bp"]', recordCountLabel((payload.blood_pressure_history || []).length));
            document.addEventListener('click', (event) => {
                const historyButton = event.target.closest('[data-history-open]');
                if (historyButton) {
                    event.preventDefault();
                    openHistoryModal(historyButton.dataset.historyOpen);
                }
            });
            historyModal?.querySelectorAll('[data-history-close]').forEach((button) => button.addEventListener('click', closeHistoryModal));
            historySearch?.addEventListener('input', renderHistoryModal);
            historySort?.addEventListener('click', () => {
                historySortDirection = historySortDirection === 'desc' ? 'asc' : 'desc';
                renderHistoryModal();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && historyModal && !historyModal.hidden) closeHistoryModal();
            });
            renderWeight();
            renderBp();
        })();
    </script>
@endsection
