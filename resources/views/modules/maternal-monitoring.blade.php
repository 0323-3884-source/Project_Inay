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
    $latestBpEntry = collect($maternalVitalsPayload['blood_pressure_history'] ?? [])->last();
    $bpStatusState = function (?string $status, bool $hasRecord): string {
        if (! $hasRecord) {
            return 'is-empty';
        }

        $rawStatus = strtolower(trim((string) $status));
        $statusKey = \App\Support\MaternalVitalScreening::statusKey($status);

        if ($statusKey === 'urgent_referral_recommended' || in_array($rawStatus, ['high risk', 'high-risk', 'critical'], true)) {
            return 'is-high-risk';
        }

        if ($statusKey === 'within_reference_range' || in_array($rawStatus, ['normal / stable', 'normal', 'stable'], true)) {
            return 'is-normal';
        }

        return 'is-monitoring';
    };
    $bpStatusLabelFor = function (?string $status, bool $hasRecord) use ($bpStatusState): string {
        return match ($bpStatusState($status, $hasRecord)) {
            'is-normal' => 'Normal / Stable',
            'is-high-risk' => 'High Risk',
            'is-empty' => 'No Record',
            default => 'Needs Monitoring',
        };
    };
    $bpStatusExplanationFor = function (?string $status, bool $hasRecord) use ($bpStatusState): string {
        return match ($bpStatusState($status, $hasRecord)) {
            'is-normal' => 'Latest recorded blood pressure is within the configured screening review range.',
            'is-high-risk' => 'A high-risk blood pressure status was recorded. Professional clinical assessment is required.',
            'is-empty' => 'No blood pressure record available yet.',
            default => 'Blood pressure requires further monitoring and professional assessment.',
        };
    };
    $latestBpStatusSource = $latestBpEntry['raw_status'] ?? $latestBpEntry['status'] ?? null;
    $latestBpStatusState = $bpStatusState($latestBpStatusSource, $latestBpEntry !== null);
    $latestBpStatusLabel = $bpStatusLabelFor($latestBpStatusSource, $latestBpEntry !== null);
    $latestBpStatusExplanation = $latestBpEntry['explanation'] ?? $bpStatusExplanationFor($latestBpStatusSource, $latestBpEntry !== null);
    $latestBpDisplay = $latestBpEntry ? $latestBpEntry['systolic'].' / '.$latestBpEntry['diastolic'] : '-- / --';
    $latestBpContext = $latestBpEntry
        ? ($latestBpEntry['pregnancy_week'] ? 'Pregnancy week '.$latestBpEntry['pregnancy_week'] : 'Recorded '.($latestBpEntry['recorded_label'] ?? 'Date not recorded'))
        : 'No blood pressure record available yet.';

    $bloodSugar = $latestRecord?->blood_sugar;
    $latestVitals = $maternalVitalsPayload['latest'] ?? null;
    $bloodSugarTestTypeLabel = $latestVitals['blood_sugar_test_type_label'] ?? 'Test type not recorded';

    $weight = $latestRecord?->weight;
    $previousWeight = $latestVitals['previous_weight'] ?? $weightRecords->get(1)?->weight;
    $oldestWeight = $weightRecords->last()?->weight;
    $weightChange = $latestVitals['weight_change_from_previous'] ?? ($weight !== null && $previousWeight !== null ? (float) $weight - (float) $previousWeight : null);
    $totalGain = $weight !== null && $oldestWeight !== null && $weightRecords->count() > 1 ? (float) $weight - (float) $oldestWeight : null;

    $temperature = $latestRecord?->temperature;
    $heartRate = $latestRecord?->heart_rate;

    $screeningStatus = $latestVitals['screening_summary_status'] ?? \App\Support\MaternalVitalScreening::STATUS_LOGGED;
    $screeningStatusClass = 'is-'.\App\Support\MaternalVitalScreening::statusSlug($screeningStatus);
    $screeningCopy = $hasRecord
        ? 'Latest readings are threshold-screened for monitoring support and should be interpreted by a qualified healthcare professional.'
        : 'Program Staff monitoring records will appear here once synced.';

    $vitalStatus = fn (string $key) => $latestVitals['statuses'][$key] ?? \App\Support\MaternalVitalScreening::STATUS_LOGGED;
    $vitalStatusClass = fn (string $key) => 'is-'.\App\Support\MaternalVitalScreening::statusSlug($vitalStatus($key));
    $vitalExplanation = fn (string $key) => $latestVitals['explanations'][$key] ?? 'No screening explanation available yet.';
    $vitalGuideline = fn (string $key) => $latestVitals['guidelines'][$key] ?? ['name' => 'Facility-configurable maternal vital screening rule', 'version' => 'Pending partner validation', 'source_url' => null];
    $clinicalReferences = \App\Support\MaternalVitalScreening::references();
    $safetyNotice = $maternalVitalsPayload['safety_notice'] ?? 'Project INAY provides threshold-based screening alerts for monitoring purposes only. Results must be verified and interpreted by a qualified healthcare professional. The system does not provide a medical diagnosis.';

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
    $vitalCards = [
        ['key' => 'blood_pressure', 'title' => 'Blood Pressure', 'icon' => $iconPulse, 'tone' => 'is-pink', 'value' => $bpValue, 'unit' => 'mmHg', 'test_type' => 'Not applicable', 'date' => ($hasBp && $hasRecord) ? 'Recorded '.$recordedDate : 'No measurement available'],
        ['key' => 'blood_sugar', 'title' => 'Blood Sugar', 'icon' => $iconHeart, 'tone' => 'is-pink', 'value' => $bloodSugar === null ? 'N/A' : $formatNumber($bloodSugar), 'unit' => 'mg/dL', 'test_type' => $bloodSugarTestTypeLabel, 'date' => $bloodSugar === null ? 'No measurement available' : 'Recorded '.$recordedDate],
        ['key' => 'weight', 'title' => 'Weight', 'icon' => $iconScale, 'tone' => 'is-green', 'value' => $weight === null ? 'N/A' : $formatNumber($weight), 'unit' => 'kg', 'test_type' => 'Not applicable', 'date' => $weight === null ? 'No measurement available' : 'Recorded '.$recordedDate],
        ['key' => 'temperature', 'title' => 'Temperature', 'icon' => $iconPulse, 'tone' => 'is-blue', 'value' => $temperature === null ? 'N/A' : $formatNumber($temperature, 1), 'unit' => 'C', 'test_type' => 'Not applicable', 'date' => $temperature === null ? 'No measurement available' : 'Recorded '.$recordedDate],
        ['key' => 'heart_rate', 'title' => 'Heart Rate', 'icon' => $iconHeart, 'tone' => 'is-green', 'value' => $heartRate === null ? 'N/A' : $formatNumber($heartRate), 'unit' => 'bpm', 'test_type' => 'Not applicable', 'date' => $heartRate === null ? 'No measurement available' : 'Recorded '.$recordedDate],
    ];
@endphp

@push('styles')
    <style>
        .maternal-vital-toggle-details {
            display: none;
        }

        .maternal-vitals-panel .maternal-vitals-grid {
            align-items: stretch;
        }

        .maternal-vitals-panel .maternal-vital-card {
            min-width: 0;
            overflow: hidden;
        }

        .maternal-vitals-panel .maternal-vital-card > .maternal-vital-details,
        .maternal-vital-detail-body .maternal-vital-details {
            display: grid !important;
            width: 100%;
            min-width: 0;
            grid-template-columns: 1fr;
            gap: 9px;
            margin-top: auto;
            padding-top: 14px;
            border-top: 1px solid #edf2f7;
        }

        .maternal-vitals-panel .maternal-vital-card > .maternal-vital-details > div,
        .maternal-vital-detail-body .maternal-vital-details > div {
            display: grid !important;
            min-width: 0;
            grid-template-columns: minmax(0, 1fr) minmax(68px, auto);
            align-items: start;
            gap: 8px;
        }

        .maternal-vitals-panel .maternal-vital-card > .maternal-vital-details span,
        .maternal-vitals-panel .maternal-vital-card > .maternal-vital-details b,
        .maternal-vital-detail-body .maternal-vital-details span,
        .maternal-vital-detail-body .maternal-vital-details b {
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .maternal-vitals-panel .maternal-vital-card > .maternal-vital-details b,
        .maternal-vital-detail-body .maternal-vital-details b {
            max-width: 156px;
            text-align: right;
        }

        .maternal-vitals-panel .maternal-vital-card > .maternal-vital-details .maternal-vital-explanation,
        .maternal-vitals-panel .maternal-vital-card > .maternal-vital-details .maternal-guideline-note,
        .maternal-vital-detail-body .maternal-vital-explanation,
        .maternal-vital-detail-body .maternal-guideline-note {
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .maternal-vitals-panel .maternal-vital-card > .maternal-vital-details .maternal-reference-link {
            max-width: 100%;
            overflow-wrap: anywhere;
        }

        .maternal-bottom-grid > .maternal-panel {
            min-width: 0;
            overflow: hidden;
        }

        .maternal-bottom-grid > .maternal-panel h2 {
            max-width: 100%;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .maternal-bottom-grid .maternal-guidelines-grid {
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        }

        .maternal-bottom-grid .maternal-guidelines-grid article {
            display: grid;
            min-width: 0;
            align-content: start;
            gap: 10px;
            overflow: hidden;
        }

        .maternal-bottom-grid .maternal-guidelines-grid span,
        .maternal-bottom-grid .maternal-guidelines-grid p,
        .maternal-bottom-grid .maternal-guidelines-grid a {
            min-width: 0;
            max-width: 100%;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .maternal-bottom-grid .maternal-guidelines-grid p {
            margin-top: 0;
        }

        .maternal-bottom-grid .maternal-guidelines-grid a {
            display: block;
            color: #a92a63;
            text-decoration: none;
        }

        .maternal-bottom-grid .maternal-guidelines-grid a:hover {
            text-decoration: underline;
        }

        .maternal-bp-monitoring {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 18px;
            min-height: 277px;
            padding: 14px 8px;
        }

        .maternal-bp-ring {
            display: grid;
            width: 158px;
            height: 158px;
            flex: 0 0 158px;
            place-items: center;
            align-content: center;
            gap: 4px;
            color: #52627a;
            background: #f8fafc;
            border: 12px solid #dbe5f0;
            border-radius: 50%;
            box-shadow: inset 0 0 0 8px #ffffff, 0 14px 24px rgba(15, 23, 42, 0.08);
            text-align: center;
        }

        .maternal-bp-ring [data-bp-status-icon] {
            display: none;
        }

        .maternal-bp-ring svg {
            display: block;
            width: 24px;
            height: 24px;
            max-width: none;
            padding: 0;
            background: transparent;
            border: 0;
            border-radius: 0;
            box-shadow: none;
            fill: none;
            stroke: currentColor;
            stroke-width: 2.2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .maternal-bp-ring strong {
            display: block;
            max-width: 126px;
            color: currentColor;
            font-size: 22px;
            font-weight: 900;
            line-height: 1;
            overflow-wrap: anywhere;
        }

        .maternal-bp-ring span {
            display: block;
            max-width: 110px;
            color: #64748b;
            font-size: 10px;
            font-weight: 900;
            line-height: 1.15;
            text-transform: uppercase;
        }

        .maternal-history-link .maternal-history-icon {
            display: inline-grid;
            width: 30px;
            height: 30px;
            flex: 0 0 30px;
            place-items: center;
            color: #ec0a78;
            background: #fff0f8;
            border: 1px solid #ffd4e7;
            border-radius: 10px;
        }

        .maternal-history-link .maternal-history-icon svg {
            display: block;
            width: 17px;
            height: 17px;
            max-width: none;
            padding: 0;
            background: transparent;
            border: 0;
            border-radius: 0;
            box-shadow: none;
            fill: none;
            stroke: currentColor;
            stroke-width: 2.2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .maternal-bp-monitoring.is-normal .maternal-bp-ring {
            color: #008a61;
            background: #ecfdf5;
            border-color: #8cecc0;
        }

        .maternal-bp-monitoring.is-monitoring .maternal-bp-ring {
            color: #c76a06;
            background: #fffbeb;
            border-color: #facc15;
        }

        .maternal-bp-monitoring.is-high-risk .maternal-bp-ring {
            color: #b42318;
            background: #fff7f7;
            border-color: #f87171;
        }

        .maternal-bp-monitoring.is-empty .maternal-bp-ring {
            color: #94a3b8;
            background: #f8fafc;
            border-color: #e2e8f0;
        }

        .maternal-bp-monitoring.is-normal [data-bp-status-icon="normal"],
        .maternal-bp-monitoring.is-monitoring [data-bp-status-icon="monitoring"],
        .maternal-bp-monitoring.is-high-risk [data-bp-status-icon="high-risk"],
        .maternal-bp-monitoring.is-empty [data-bp-status-icon="empty"] {
            display: block;
        }

        .maternal-bp-copy {
            display: flex;
            flex: 1 1 auto;
            min-width: 0;
            max-width: 520px;
            flex-direction: column;
            gap: 8px;
        }

        .maternal-bp-copy small,
        .maternal-bp-copy em {
            color: #64748b;
            font-size: 12px;
            font-style: normal;
            font-weight: 700;
            line-height: 1.45;
        }

        .maternal-bp-copy b {
            display: inline-flex;
            width: fit-content;
            min-height: 28px;
            align-items: center;
            padding: 4px 12px;
            color: #52627a;
            background: #f1f5f9;
            border: 1px solid #dbe5f0;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .maternal-bp-monitoring.is-normal .maternal-bp-copy b {
            color: #007f5f;
            background: #ecfdf5;
            border-color: #86efc2;
        }

        .maternal-bp-monitoring.is-monitoring .maternal-bp-copy b {
            color: #975a16;
            background: #fffbeb;
            border-color: #fde68a;
        }

        .maternal-bp-monitoring.is-high-risk .maternal-bp-copy b {
            color: #b42318;
            background: #fff7f7;
            border-color: #fecaca;
        }

        .maternal-bp-readings {
            display: grid;
            width: 100%;
            max-width: 420px;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        .maternal-bp-readings span {
            padding: 8px 12px;
            color: #52627a;
            background: #f8fafc;
            border: 1px solid #e5edf6;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 750;
        }

        .maternal-bp-readings strong {
            color: #061125;
            font-size: 12px;
            font-weight: 800;
        }

        .maternal-bp-copy p {
            margin: 0;
            color: #1f2937;
            font-size: 13px;
            font-weight: 750;
            line-height: 1.45;
        }

        .maternal-vital-detail-modal[hidden] {
            display: none;
        }

        .maternal-vital-detail-modal {
            position: fixed;
            inset: 0;
            z-index: 80;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding: 12px;
            background: rgba(15, 23, 42, 0.38);
        }

        .maternal-vital-detail-dialog {
            width: min(520px, 100%);
            max-height: 82vh;
            overflow-y: auto;
            padding: 18px;
            background: #ffffff;
            border: 1px solid #dbe5f0;
            border-radius: 16px;
            box-shadow: 0 22px 60px rgba(15, 23, 42, 0.25);
        }

        .maternal-vital-detail-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 14px;
            padding-bottom: 12px;
            border-bottom: 1px solid #edf2f7;
        }

        .maternal-vital-detail-head h2 {
            margin: 0;
            color: #030813;
            font-size: 18px;
            font-weight: 900;
        }

        .maternal-vital-detail-close {
            display: inline-grid;
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
            place-items: center;
            color: #40536f;
            background: #f8fafc;
            border: 1px solid #dbe5f0;
            border-radius: 999px;
            font-size: 24px;
            line-height: 1;
            cursor: pointer;
        }

        @media (max-width: 760px) {
            .maternal-vitals-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 10px;
            }

            .maternal-bottom-grid > .maternal-panel {
                padding: 12px;
            }

            .maternal-bottom-grid > .maternal-panel h2 {
                font-size: 15px;
            }

            .maternal-bottom-grid .maternal-guidelines-grid {
                grid-template-columns: 1fr !important;
                gap: 10px;
                margin-top: 12px;
            }

            .maternal-bottom-grid .maternal-guidelines-grid article {
                min-height: 0;
                padding: 12px;
            }

            .maternal-bottom-grid .maternal-guidelines-grid span {
                font-size: 8px;
                line-height: 1.35;
            }

            .maternal-bottom-grid .maternal-guidelines-grid p,
            .maternal-bottom-grid .maternal-guidelines-grid a {
                font-size: 10px;
                line-height: 1.45;
            }

            .maternal-vital-card {
                display: flex;
                min-width: 0;
                min-height: 158px;
                flex-direction: column;
                align-items: stretch;
                gap: 6px;
                padding: 11px;
                overflow: hidden;
                background: #ffffff;
                border-radius: 12px;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            }

            .maternal-vital-top {
                width: 100%;
                min-width: 0;
                align-items: flex-start;
                flex-direction: row;
                justify-content: space-between;
                gap: 6px;
            }

            .maternal-vital-icon {
                width: 30px;
                height: 30px;
                flex: 0 0 30px;
            }

            .maternal-vital-icon svg {
                width: 15px;
                height: 15px;
            }

            .maternal-mini-badge {
                min-width: 0;
                max-width: calc(100% - 36px);
                min-height: 24px;
                justify-content: center;
                padding: 4px 7px;
                font-size: 7px;
                line-height: 1.15;
                overflow-wrap: anywhere;
                text-align: center;
                white-space: normal;
            }

            .maternal-vital-card > p {
                margin-top: 1px;
                color: #6d7e96;
                font-size: 9px;
                font-weight: 850;
                line-height: 1.2;
                text-transform: uppercase;
            }

            .maternal-vital-card strong {
                min-width: 0;
                margin-top: 0;
                font-size: clamp(17px, 5vw, 21px);
                font-weight: 850;
                line-height: 1.12;
                overflow-wrap: anywhere;
            }

            .maternal-vital-card strong span {
                font-size: 9px;
            }

            .maternal-vitals-panel .maternal-vital-card > .maternal-vital-details {
                display: none !important;
            }

            .maternal-vital-toggle-details {
                display: inline-flex;
                min-height: 30px;
                align-items: center;
                justify-content: center;
                align-self: flex-start;
                margin-top: auto;
                padding: 5px 10px;
                color: #ec0a78;
                background: #fff0f8;
                border: 1px solid #ffd4e7;
                border-radius: 999px;
                font-family: inherit;
                font-size: 9px;
                font-weight: 850;
                line-height: 1.2;
                cursor: pointer;
            }

            .maternal-bp-monitoring {
                align-items: center;
                flex-direction: column;
                justify-content: center;
                min-height: 0;
                padding: 8px 0;
                text-align: center;
            }

            .maternal-bp-ring {
                width: 138px;
                height: 138px;
                flex-basis: 138px;
                border-width: 10px;
            }

            .maternal-bp-copy {
                width: 100%;
                align-items: center;
            }

            .maternal-bp-readings {
                width: 100%;
                max-width: 320px;
                grid-template-columns: 1fr;
            }

            .maternal-bp-readings span {
                text-align: center;
            }
        }
    </style>
@endpush

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
                <span class="maternal-risk-badge {{ $screeningStatusClass }}">{{ strtoupper($screeningStatus) }}</span>
            </div>

            <div class="maternal-vitals-grid">
                @foreach($vitalCards as $card)
                    @php
                        $cardStatus = $vitalStatus($card['key']);
                        $cardGuideline = $vitalGuideline($card['key']);
                    @endphp
                    <article class="maternal-vital-card">
                        <div class="maternal-vital-top">
                            <span class="maternal-vital-icon {{ $card['tone'] }}">{!! $card['icon'] !!}</span>
                            <span class="maternal-mini-badge {{ $vitalStatusClass($card['key']) }}">{{ $cardStatus }}</span>
                        </div>
                        <p>{{ $card['title'] }}</p>
                        <strong>{{ $card['value'] }} @if($card['value'] !== 'N/A')<span>{{ $card['unit'] }}</span>@endif</strong>
                        <button type="button" class="maternal-vital-toggle-details" data-maternal-vital-toggle="{{ $card['key'] }}">
                            View Details
                        </button>
                        <div class="maternal-vital-details">
                            <div><span>Measurement Date</span><b>{{ $card['date'] }}</b></div>
                            <div><span>Pregnancy Week</span><b>{{ $pregnancyWeek ?: 'N/A' }}</b></div>
                            <div><span>Test Type</span><b>{{ $card['test_type'] }}</b></div>
                            <div><span>Unit</span><b>{{ $card['unit'] }}</b></div>
                            <p class="maternal-vital-explanation">{{ $vitalExplanation($card['key']) }}</p>
                            @if(! empty($cardGuideline['source_url']))
                                <a class="maternal-reference-link" href="{{ $cardGuideline['source_url'] }}" target="_blank" rel="noopener">View Reference</a>
                            @endif
                            <small class="maternal-guideline-note">{{ $cardGuideline['name'] ?? 'Facility-configurable screening rule' }} / {{ $cardGuideline['version'] ?? 'Pending validation' }}</small>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="maternal-good-banner">{!! $iconCheck !!} {{ $hasRecord ? 'Maternal indicators are synced from Program Staff monitoring records for screening support.' : 'No maternal monitoring record has been synced yet.' }}</div>
            <div class="maternal-warning-banner">{!! $iconAlert !!} {{ $safetyNotice }}</div>
        </section>

        <div class="maternal-dashboard-grid">
            <section class="maternal-column">
                <div class="maternal-section-heading">
                    <div><span>Weight Progress</span><h2>Weight Progression</h2><p>Weight readings update from recorded maternal vitals.</p></div>
                </div>

                <article class="maternal-chart-card">
                    <span>Trend Chart</span>
                    <h3>Weight Progression</h3>
                    <i>{!! $iconTrend !!}</i>
                    <div class="maternal-chart-area" data-mother-chart="weight"></div>
                    <div class="maternal-chart-legend"><span class="is-pink"></span> Weight (kg)</div>
                </article>

                <button type="button" class="monitoring-history-trigger maternal-history-link" data-history-open="weight">
                    <span class="maternal-history-icon" aria-hidden="true">{!! $iconHistory !!}</span>
                    <span>View Weight History</span>
                    <b data-history-count="weight">{{ $weightRecords->count() }} Record{{ $weightRecords->count() === 1 ? '' : 's' }}</b>
                </button>
            </section>

            <section class="maternal-column">
                <div class="maternal-section-heading">
                    <div><span>Blood Pressure Monitoring</span><h2>Blood Pressure Status</h2><p>Systolic and diastolic readings update from recorded maternal vitals.</p></div>
                </div>

                <div class="maternal-bp-monitoring {{ $latestBpStatusState }}" data-mother-bp-status-card aria-live="polite">
                    <div class="maternal-bp-ring" data-mother-bp-status-indicator aria-label="Blood pressure status: {{ $latestBpStatusLabel }}">
                        <i data-bp-status-icon="normal">{!! $iconCheck !!}</i>
                        <i data-bp-status-icon="monitoring">{!! $iconAlert !!}</i>
                        <i data-bp-status-icon="high-risk">{!! $iconAlert !!}</i>
                        <i data-bp-status-icon="empty">{!! $iconPulse !!}</i>
                        <strong>{{ $latestBpDisplay }}</strong>
                        <span>mmHg</span>
                    </div>
                    <div class="maternal-bp-copy">
                        <small>{{ $latestBpContext }}</small>
                        <b>{{ $latestBpStatusLabel }}</b>
                        <div class="maternal-bp-readings">
                            <span>Systolic: <strong>{{ $latestBpEntry['systolic'] ?? '--' }} mmHg</strong></span>
                            <span>Diastolic: <strong>{{ $latestBpEntry['diastolic'] ?? '--' }} mmHg</strong></span>
                        </div>
                        <p>{{ $latestBpStatusExplanation }}</p>
                        <em>This status summarizes recorded monitoring information and does not replace professional clinical assessment.</em>
                    </div>
                </div>

                <button type="button" class="monitoring-history-trigger maternal-history-link" data-history-open="bp">
                    <span class="maternal-history-icon" aria-hidden="true">{!! $iconHistory !!}</span>
                    <span>View Blood Pressure History</span>
                    <b data-history-count="bp">{{ $bpRecords->count() }} Record{{ $bpRecords->count() === 1 ? '' : 's' }}</b>
                </button>
            </section>
        </div>

        <div class="maternal-bottom-grid">
            <section class="maternal-panel">
                <h2>Screening Summary</h2>
                <div class="maternal-risk-box {{ $screeningStatusClass }}">
                    <span>Screening Status</span>
                    <strong>{{ $screeningStatus }}</strong>
                    <p>{{ $screeningCopy }}</p>
                </div>
            </section>

            <section class="maternal-panel">
                <h2>Clinical Guide References</h2>
                <div class="maternal-guidelines-grid">
                    @foreach($clinicalReferences as $reference)
                        <article>
                            <span>{{ $reference['title'] }}</span>
                            <p><a href="{{ $reference['url'] }}" target="_blank" rel="noopener">{{ $reference['url'] }}</a></p>
                        </article>
                    @endforeach
                </div>
            </section>
        </div>

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

        <div class="maternal-vital-detail-modal" data-maternal-vital-modal hidden>
            <section class="maternal-vital-detail-dialog" role="dialog" aria-modal="true" aria-labelledby="maternal-vital-detail-title">
                <header class="maternal-vital-detail-head">
                    <h2 id="maternal-vital-detail-title" data-maternal-vital-title>Vital Details</h2>
                    <button type="button" class="maternal-vital-detail-close" data-maternal-vital-close aria-label="Close vital details">&times;</button>
                </header>
                <div class="maternal-vital-detail-body" data-maternal-vital-body></div>
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
            const vitalDetailModal = document.querySelector('[data-maternal-vital-modal]');
            const vitalDetailTitle = document.querySelector('[data-maternal-vital-title]');
            const vitalDetailBody = document.querySelector('[data-maternal-vital-body]');
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

            const statusSlug = (status) => String(status || 'Logged')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '') || 'logged';

            const statusInfo = (status) => {
                const label = status || 'Logged';
                return {
                    label,
                    className: `is-${statusSlug(label)}`,
                };
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
                    const status = statusInfo(item.status);
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
                        const status = statusInfo(item.status);
                        return `
                            <tr>
                                <td class="is-main">${escapeHtml(item.recorded_label || 'Date not recorded')}</td>
                                <td>Week ${escapeHtml(item.pregnancy_week || 'N/A')}</td>
                                <td class="is-main">${escapeHtml(valueText(item.weight, 'kg', 1))}</td>
                                <td><span class="history-status ${status.className}">${status.label}</span></td>
                            </tr>
                        `;
                    }

                    const status = statusInfo(item.status);
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

            const openVitalDetailModal = (button) => {
                const card = button.closest('.maternal-vital-card');
                const details = card?.querySelector('.maternal-vital-details');

                if (!card || !details || !vitalDetailModal || !vitalDetailTitle || !vitalDetailBody) {
                    return;
                }

                vitalDetailTitle.textContent = card.querySelector(':scope > p')?.textContent || 'Vital Details';
                vitalDetailBody.innerHTML = '';
                vitalDetailBody.appendChild(details.cloneNode(true));
                vitalDetailModal.hidden = false;
                document.body.classList.add('has-vital-detail-modal');
            };

            const closeVitalDetailModal = () => {
                if (!vitalDetailModal) return;
                vitalDetailModal.hidden = true;
                document.body.classList.remove('has-vital-detail-modal');
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
                const target = event.target instanceof Element ? event.target : event.target?.parentElement;
                const historyButton = target?.closest('[data-history-open]');
                if (historyButton) {
                    event.preventDefault();
                    openHistoryModal(historyButton.dataset.historyOpen);
                }

                const vitalButton = target?.closest('[data-maternal-vital-toggle]');
                if (vitalButton) {
                    event.preventDefault();
                    openVitalDetailModal(vitalButton);
                }

                if (target?.matches('[data-maternal-vital-modal]')) {
                    closeVitalDetailModal();
                }
            });
            historyModal?.querySelectorAll('[data-history-close]').forEach((button) => button.addEventListener('click', closeHistoryModal));
            vitalDetailModal?.querySelectorAll('[data-maternal-vital-close]').forEach((button) => button.addEventListener('click', closeVitalDetailModal));
            historySearch?.addEventListener('input', renderHistoryModal);
            historySort?.addEventListener('click', () => {
                historySortDirection = historySortDirection === 'desc' ? 'asc' : 'desc';
                renderHistoryModal();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && historyModal && !historyModal.hidden) closeHistoryModal();
                if (event.key === 'Escape' && vitalDetailModal && !vitalDetailModal.hidden) closeVitalDetailModal();
            });
            renderWeight();
            renderBp();
        })();
    </script>
@endsection
