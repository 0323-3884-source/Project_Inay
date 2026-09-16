@extends('layouts.app')

@section('title', 'Dynamic Reports - Project INAY')
@section('portal_title', 'Dynamic Reports')

@php
    $iconReport = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 17v-4"/><path d="M12 17v-7"/><path d="M16 17v-2"/></svg>';
    $iconSearch = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>';
    $iconMother = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-8 0v2"/><circle cx="12" cy="7" r="4"/><path d="M18 11c1.7.6 3 2.2 3 4v2"/><path d="M6 11c-1.7.6-3 2.2-3 4v2"/></svg>';
    $iconBaby = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 12h.01"/><path d="M15 12h.01"/><path d="M10 16c.5.3 1.2.5 2 .5s1.5-.2 2-.5"/><path d="M19 12a7 7 0 1 1-14 0c0-2.2 1-4.1 2.6-5.4"/></svg>';
    $iconTrend = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 17 6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>';
    $iconPrint = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>';
    $iconOpen = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>';
    $formatNumber = fn ($value, $suffix = '') => $value === null ? 'No data' : rtrim(rtrim(number_format((float) $value, 2), '0'), '.').$suffix;
    $riskTone = fn ($risk) => match ($risk) {
        'urgent_referral_recommended' => 'is-high',
        'for_review' => 'is-medium',
        'for_professional_interpretation' => 'is-info',
        'within_reference_range' => 'is-low',
        default => 'is-pending',
    };
    $statusTone = fn ($status) => match ($status) {
        'at_risk' => 'is-high',
        'watch' => 'is-medium',
        default => 'is-low',
    };
    $buildWeightChart = function ($records) {
        $values = $records->filter(fn ($record) => $record->weight !== null)->sortBy('measured_at')->values();

        if ($values->isEmpty()) {
            return ['has' => false, 'points' => collect(), 'path' => '', 'min' => null, 'mid' => null, 'max' => null];
        }

        $weights = $values->map(fn ($record) => (float) $record->weight);
        $padding = max(0.5, ($weights->max() - $weights->min()) * 0.25);
        $min = floor(($weights->min() - $padding) * 10) / 10;
        $max = ceil(($weights->max() + $padding) * 10) / 10;

        if ($min === $max) {
            $min -= 1;
            $max += 1;
        }

        $points = $values->map(function ($record, $index) use ($values, $min, $max) {
            $steps = max(1, $values->count() - 1);
            $weight = (float) $record->weight;

            return [
                'x' => round(14 + (($index / $steps) * 76), 2),
                'y' => round(78 - ((($weight - $min) / max(1, $max - $min)) * 54), 2),
                'label' => $record->measured_at?->format('M j') ?? 'No date',
                'value' => $weight,
            ];
        });

        return [
            'has' => true,
            'points' => $points,
            'path' => $points->map(fn ($point) => $point['x'].','.$point['y'])->implode(' '),
            'min' => $min,
            'mid' => round(($min + $max) / 2, 1),
            'max' => $max,
        ];
    };
    $selectedWeightChart = $buildWeightChart($selectedGrowthLogs);
@endphp

@section('content')
    <style>
        .reports-desk {
            --pink: #ec008c;
            --navy: #071127;
            --muted: #52627d;
            --line: #dbe5f1;
            --soft: #f8fafc;
            --green: #00856a;
            --blue: #2563eb;
            --amber: #b45309;
            display: grid;
            gap: 18px;
            max-width: 1480px;
            margin: 0 auto;
            color: var(--navy);
        }

        .reports-desk *,
        .reports-desk *::before,
        .reports-desk *::after {
            box-sizing: border-box;
            letter-spacing: 0;
        }

        .reports-desk svg {
            width: 18px;
            height: 18px;
            fill: none;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2;
        }

        .report-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            padding-bottom: 18px;
            border-bottom: 1px solid #d7e1ee;
        }

        .report-kicker {
            margin: 0 0 8px;
            color: #8aa0bd;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .report-heading h1 {
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 0;
            color: var(--navy);
            font-size: clamp(24px, 3vw, 32px);
            line-height: 1.12;
            font-weight: 900;
        }

        .report-heading p {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 14px;
            font-weight: 700;
            line-height: 1.45;
        }

        .report-button {
            display: inline-flex;
            min-height: 42px;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 15px;
            color: #ffffff;
            background: #17233b;
            border: 1px solid #17233b;
            border-radius: 8px;
            font-weight: 900;
            text-decoration: none;
            cursor: pointer;
            white-space: nowrap;
        }

        .report-button.is-light {
            color: #17233b;
            background: #ffffff;
            border-color: #cbd8ea;
        }

        .report-tabs {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            border-bottom: 1px solid #dbe5f1;
            -webkit-overflow-scrolling: touch;
        }

        .report-tab {
            display: inline-flex;
            min-height: 48px;
            align-items: center;
            gap: 8px;
            padding: 0 14px;
            color: #8aa0bd;
            border-bottom: 3px solid transparent;
            font-size: 13px;
            font-weight: 900;
            text-decoration: none;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .report-tab.is-active {
            color: var(--pink);
            border-bottom-color: var(--pink);
        }

        .report-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
        }

        .report-card {
            background: #ffffff;
            border: 1px solid var(--line);
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
        }

        .report-stat {
            min-height: 104px;
            padding: 16px;
        }

        .report-stat span,
        .report-filter label,
        .report-table th,
        .newborn-panel small,
        .newborn-metric span {
            color: #8aa0bd;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .report-stat strong {
            display: block;
            margin-top: 9px;
            font-size: 28px;
            line-height: 1;
            font-weight: 900;
        }

        .report-stat p {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
        }

        .report-stat.is-high {
            color: #be123c;
            background: #fff7fa;
            border-color: #fecdd3;
        }

        .report-stat.is-medium {
            color: #b45309;
            background: #fffbeb;
            border-color: #fde68a;
        }

        .report-stat.is-low {
            color: var(--green);
            background: #f0fdf4;
            border-color: #bbf7d0;
        }

        .report-stat.is-info {
            color: #17233b;
            background: #f8fbff;
        }

        .report-filter {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 220px auto;
            gap: 12px;
            align-items: end;
            padding: 16px;
        }

        .report-search,
        .report-select {
            display: flex;
            min-width: 0;
            min-height: 44px;
            align-items: center;
            gap: 10px;
            color: #8aa0bd;
            background: #ffffff;
            border: 1px solid #cbd8ea;
            border-radius: 8px;
            padding: 0 12px;
        }

        .report-search input,
        .report-select select {
            width: 100%;
            min-width: 0;
            color: var(--navy);
            background: transparent;
            border: 0;
            outline: 0;
            font-size: 14px;
            font-weight: 750;
        }

        .report-table-wrap {
            overflow-x: auto;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #ffffff;
            -webkit-overflow-scrolling: touch;
        }

        .report-table {
            width: 100%;
            min-width: 920px;
            border-collapse: collapse;
        }

        .report-table th {
            padding: 13px 16px;
            text-align: left;
            background: #f8fafc;
            border-bottom: 1px solid var(--line);
        }

        .report-table td {
            padding: 14px 16px;
            color: #17233b;
            border-bottom: 1px solid #edf2f7;
            font-size: 14px;
            font-weight: 700;
            vertical-align: middle;
        }

        .report-table tbody tr:hover {
            background: #fff7fb;
        }

        .report-code {
            display: block;
            margin-top: 3px;
            color: #8aa0bd;
            font-size: 12px;
            font-weight: 900;
        }

        .report-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .report-pill.is-high {
            color: #be123c;
            background: #fff1f2;
            border: 1px solid #fecdd3;
        }

        .report-pill.is-medium {
            color: #b45309;
            background: #fffbeb;
            border: 1px solid #fde68a;
        }

        .report-pill.is-low {
            color: var(--green);
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
        }

        .report-pill.is-pending {
            color: #52627d;
            background: #f8fafc;
            border: 1px solid #dbe5f1;
        }

        .report-empty {
            display: grid;
            min-height: 170px;
            place-items: center;
            padding: 24px;
            color: #64748b;
            background: #f8fafc;
            border: 1px dashed #d5deea;
            border-radius: 8px;
            text-align: center;
            font-weight: 800;
        }

        .newborn-layout {
            display: grid;
            grid-template-columns: minmax(280px, 360px) minmax(0, 1fr);
            gap: 16px;
            align-items: start;
        }

        .newborn-panel {
            padding: 16px;
        }

        .newborn-list {
            display: grid;
            gap: 9px;
            margin-top: 12px;
        }

        .newborn-row {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            gap: 11px;
            align-items: center;
            padding: 12px;
            color: inherit;
            background: #ffffff;
            border: 1px solid #dbe5f1;
            border-radius: 8px;
            text-decoration: none;
        }

        .newborn-row:hover,
        .newborn-row.is-active {
            background: #fff4fa;
            border-color: #ff9bd1;
        }

        .newborn-avatar {
            display: grid;
            width: 44px;
            height: 44px;
            place-items: center;
            color: #ffffff;
            background: #7c2dff;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 900;
        }

        .newborn-row strong {
            display: block;
            overflow-wrap: anywhere;
        }

        .newborn-row span {
            color: #64748b;
            font-size: 12px;
            font-weight: 750;
        }

        .newborn-detail {
            display: grid;
            gap: 16px;
        }

        .newborn-profile {
            padding: 18px;
        }

        .newborn-profile-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
            padding-bottom: 14px;
            border-bottom: 1px solid #edf2f7;
        }

        .newborn-profile h2,
        .report-section h2 {
            margin: 0;
            color: var(--navy);
            font-size: 19px;
            font-weight: 900;
        }

        .newborn-profile p {
            margin: 5px 0 0;
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
        }

        .newborn-metrics {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-top: 14px;
        }

        .newborn-metric {
            min-height: 86px;
            padding: 13px;
            background: #f8fbff;
            border: 1px solid #dbe5f1;
            border-radius: 8px;
        }

        .newborn-metric strong {
            display: block;
            margin-top: 8px;
            color: var(--navy);
            font-size: 16px;
            font-weight: 900;
            overflow-wrap: anywhere;
        }

        .report-section {
            padding: 18px;
        }

        .chart-panel {
            height: 300px;
            margin-top: 14px;
            padding: 16px;
            background: #f8fafc;
            border: 1px solid #e5edf6;
            border-radius: 8px;
        }

        .chart-panel svg {
            display: block;
            width: 100%;
            height: 100%;
            stroke-width: 1;
        }

        .chart-panel .grid {
            stroke: #dbeafe;
            stroke-dasharray: 4 7;
        }

        .chart-panel .axis {
            stroke: #b8c7dc;
        }

        .chart-panel .line {
            fill: none;
            stroke: var(--pink);
            stroke-width: 1.6;
        }

        .chart-panel .dot {
            fill: #ffffff;
            stroke: var(--pink);
            stroke-width: 1.8;
        }

        .chart-panel .label,
        .chart-panel .axis-title {
            fill: #8090ad;
            stroke: none;
            font-size: 3.2px;
            font-weight: 600;
        }

        .report-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        @media (max-width: 1180px) {
            .report-heading,
            .newborn-profile-head {
                align-items: flex-start;
                flex-direction: column;
            }

            .report-summary-grid,
            .newborn-metrics {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .newborn-layout,
            .report-filter {
                grid-template-columns: 1fr;
            }

            .report-actions,
            .report-button {
                width: 100%;
            }
        }

        @media (max-width: 640px) {
            .reports-desk {
                gap: 14px;
            }

            .report-summary-grid,
            .newborn-metrics {
                grid-template-columns: 1fr;
            }

            .report-card,
            .report-table-wrap,
            .newborn-row,
            .report-empty {
                border-radius: 8px;
            }

            .chart-panel {
                height: 240px;
                padding: 12px;
            }
        }
        @if($activeTab === 'newborn')
        /* Screen only: allow nested grid items to shrink around scrollable tables. */
        @media screen {
            .reports-desk {
                box-sizing: border-box;
                width: 100%;
                max-width: 100%;
                min-width: 0;
                grid-template-columns: minmax(0, 1fr);
            }

            .reports-desk :where(.report-heading, .report-heading > div, .report-tabs,
                .report-summary-grid, .report-card, .newborn-layout, .newborn-detail,
                .newborn-profile-head, .newborn-profile-head > div, .newborn-metrics,
                .newborn-metric, .newborn-list, .newborn-row, .newborn-row > span,
                .report-actions, .chart-panel, .report-table-wrap) {
                min-width: 0;
                max-width: 100%;
            }

            .newborn-detail { grid-template-columns: minmax(0, 1fr); }
            .report-summary-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }

            .report-heading, .newborn-profile-head { flex-wrap: wrap; }
            .report-heading > div { flex: 1 1 480px; }
            .newborn-profile-head > div:first-child { flex: 1 1 320px; }
            .reports-desk :where(h1, h2, p, .newborn-metric, .newborn-row > span) {
                overflow-wrap: anywhere;
            }

            .reports-desk .report-button {
                max-width: 100%;
                white-space: normal;
                overflow-wrap: anywhere;
                text-align: center;
            }

            .report-table-wrap {
                width: 100%;
                overflow-x: auto;
            }

            .chart-panel, .chart-panel svg { width: 100%; max-width: 100%; }
            .chart-panel svg { height: 100%; }

            /* Compact screen typography; the printable document has its own styles. */
            .report-heading h1 {
                font-size: clamp(24px, 2vw, 28px);
                line-height: 1.2;
            }

            .newborn-profile h2, .report-section h2 {
                font-size: clamp(17px, 1.4vw, 19px);
                line-height: 1.35;
            }

            .report-stat strong {
                font-size: clamp(22px, 1.8vw, 26px);
                line-height: 1.2;
            }

            .report-stat span, .newborn-metric span, .newborn-panel small,
            .report-table th {
                font-size: 12px;
                line-height: 1.4;
                overflow-wrap: anywhere;
            }

            .newborn-metric strong { font-size: 15px; line-height: 1.4; }
            .report-heading p, .report-table td, .report-button,
            .newborn-panel .report-search input, .newborn-row strong {
                font-size: 13px;
                line-height: 1.45;
            }

            .report-table {
                width: 100%;
                min-width: 0;
                table-layout: fixed;
            }
            .report-table th, .report-table td {
                padding: 12px 8px;
                white-space: normal;
                overflow-wrap: anywhere;
                vertical-align: top;
            }
            .newborn-growth-table th:nth-child(1) { width: 17%; }
            .newborn-growth-table th:nth-child(2) { width: 9%; }
            .newborn-growth-table th:nth-child(3),
            .newborn-growth-table th:nth-child(4) { width: 17%; }
            .newborn-growth-table th:nth-child(5),
            .newborn-growth-table th:nth-child(6) { width: 20%; }
            .newborn-vaccine-table th:nth-child(1) { width: 23%; }
            .newborn-vaccine-table th:nth-child(2) { width: 12%; }
            .newborn-vaccine-table th:nth-child(3) { width: 15%; }
            .newborn-vaccine-table th:nth-child(4) { width: 17%; }
            .newborn-vaccine-table th:nth-child(5) { width: 18%; }
            .newborn-vaccine-table th:nth-child(6) { width: 15%; }
            .newborn-row span, .report-code { line-height: 1.45; }
            .report-pill {
                max-width: 100%;
                padding: 4px 6px;
                line-height: 1.35;
                white-space: normal;
                overflow-wrap: anywhere;
                text-align: center;
            }
            .newborn-metrics { gap: 10px; }
            .newborn-layout, .newborn-detail { gap: 14px; }
            .newborn-profile, .report-section { padding: 16px; }
        }

        @media screen and (max-width: 1599px) {
            .newborn-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media screen and (max-width: 1399px) {
            .newborn-layout { grid-template-columns: minmax(0, 1fr); }
        }

        @media screen and (max-width: 1199px) {
            .report-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .newborn-layout { grid-template-columns: minmax(0, 1fr); }
            .report-heading, .newborn-profile-head { flex-direction: column; align-items: stretch; }
            .report-heading > div, .newborn-profile-head > div:first-child { flex-basis: auto; }
        }

        @media screen and (max-width: 767px) {
            .report-table { min-width: 600px; }
            .report-table th, .report-table td { padding: 10px 6px; }
            .report-summary-grid, .newborn-metrics { grid-template-columns: minmax(0, 1fr); }
            .newborn-row { grid-template-columns: auto minmax(0, 1fr); }
            .newborn-row > .report-pill { grid-column: 2; justify-self: start; white-space: normal; }
        }
        @endif
        @if($activeTab === 'statistics')
            .reports-desk { width:100%; max-width:100%; min-width:0; grid-template-columns:minmax(0,1fr); }
            .reports-desk > *, .report-heading > div { min-width:0; }
            .report-heading { flex-wrap:wrap; }
            .report-heading > div { flex:1 1 480px; }
            .report-heading h1 { font-size:clamp(24px,2vw,28px); overflow-wrap:anywhere; }
            .report-button { max-width:100%; white-space:normal; }
            {!! file_get_contents(resource_path('css/staff-statistics.css')) !!}
        @endif
    </style>

    <section class="reports-desk" aria-label="Dynamic clinical reports">
        <header class="report-heading">
            <div>
                <p class="report-kicker">Program Staff / Reports</p>
                <h1>{!! $iconReport !!} Regional Clinical Surveillance Reports Desk</h1>
                <p>Live reports connected to assigned mother casefiles, maternal monitoring records, newborn growth logs, and vaccine surveillance.</p>
            </div>
            <button class="report-button" type="button" @if($activeTab === 'newborn') data-newborn-report-print @elseif($activeTab === 'statistics') data-statistics-report-print @else onclick="window.print()" @endif>{!! $iconPrint !!} Export / Print Report</button>
        </header>

        <nav class="report-tabs" aria-label="Report sections">
            <a class="report-tab {{ $activeTab === 'maternal' ? 'is-active' : '' }}" href="{{ route('staff.dynamic-reports', ['tab' => 'maternal']) }}">{!! $iconMother !!} Maternal Screening Registry ({{ $maternalRows->count() }})</a>
            <a class="report-tab {{ $activeTab === 'newborn' ? 'is-active' : '' }}" href="{{ route('staff.dynamic-reports', ['tab' => 'newborn']) }}">{!! $iconBaby !!} Newborn Monitoring Logs ({{ $newbornRows->count() }})</a>
            <a class="report-tab {{ $activeTab === 'statistics' ? 'is-active' : '' }}" href="{{ route('staff.dynamic-reports', ['tab' => 'statistics']) }}">{!! $iconTrend !!} Statistics</a>
        </nav>

        @if($activeTab === 'statistics')
            @include('modules.partials.staff-statistics')
        @elseif($activeTab === 'maternal')
            <div class="report-summary-grid">
                <article class="report-card report-stat is-high"><span>Urgent Referral Recommended</span><strong>{{ $maternalSummary['urgent_referral_recommended'] }}</strong><p>Needs prompt professional follow-up</p></article>
                <article class="report-card report-stat is-medium"><span>For Review</span><strong>{{ $maternalSummary['for_review'] }}</strong><p>Screening threshold reached</p></article>
                <article class="report-card report-stat is-info"><span>Professional Interpretation</span><strong>{{ $maternalSummary['for_professional_interpretation'] }}</strong><p>Needs clinical context</p></article>
                <article class="report-card report-stat is-low"><span>Within Reference Range</span><strong>{{ $maternalSummary['within_reference_range'] }}</strong><p>Latest screening status</p></article>
                <article class="report-card report-stat is-info"><span>Mean Blood Sugar</span><strong>{{ $maternalSummary['mean_blood_sugar'] === null ? 'No data' : $maternalSummary['mean_blood_sugar'].' mg/dL' }}</strong><p>Latest casefile records</p></article>
            </div>

            <form class="report-card report-filter" method="GET" action="{{ route('staff.dynamic-reports') }}">
                <input type="hidden" name="tab" value="maternal">
                <label>
                    Search registry
                    <span class="report-search">{!! $iconSearch !!}<input name="maternal_q" type="search" value="{{ $maternalSearch }}" placeholder="Search patient name, code, age, or barangay"></span>
                </label>
                <label>
                    Screening status
                    <span class="report-select">
                        <select name="risk">
                            <option value="all" @selected($maternalRisk === 'all')>All screening statuses</option>
                            <option value="logged" @selected($maternalRisk === 'logged')>Logged</option>
                            <option value="within_reference_range" @selected($maternalRisk === 'within_reference_range')>Within Reference Range</option>
                            <option value="for_review" @selected($maternalRisk === 'for_review')>For Review</option>
                            <option value="for_professional_interpretation" @selected($maternalRisk === 'for_professional_interpretation')>For Professional Interpretation</option>
                            <option value="urgent_referral_recommended" @selected($maternalRisk === 'urgent_referral_recommended')>Urgent Referral Recommended</option>
                        </select>
                    </span>
                </label>
                <button class="report-button" type="submit">Apply Filter</button>
            </form>

            @if($filteredMaternalRows->isEmpty())
                <div class="report-empty">No maternal screening records match the current filter.</div>
            @else
                <div class="report-table-wrap">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Patient Code / Full Name</th>
                                <th>Age</th>
                                <th>Blood Pressure</th>
                                <th>Blood Sugar</th>
                                <th>Test Type</th>
                                <th>Temp & HR</th>
                                <th>Barangay</th>
                                <th>Screening Status</th>
                                <th>Casefile</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($filteredMaternalRows as $row)
                                <tr>
                                    <td><strong>{{ $row['name'] }}</strong><span class="report-code">{{ $row['code'] }} / {{ $row['recorded_label'] }}</span></td>
                                    <td>{{ $row['age'] ? $row['age'].' y/o' : 'Not provided' }}</td>
                                    <td>{{ $row['blood_pressure'] }}</td>
                                    <td>{{ $row['blood_sugar'] }}</td>
                                    <td>{{ $row['blood_sugar_test_type'] }}</td>
                                    <td>{{ $row['temperature'] }} / {{ $row['heart_rate'] }}</td>
                                    <td>{{ $row['barangay'] }}</td>
                                    <td><span class="report-pill {{ $riskTone($row['risk_level']) }}">{{ $row['risk_label'] }}</span></td>
                                    <td><a class="report-button is-light" href="{{ $row['casefile_url'] }}">{!! $iconOpen !!} Open</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @else
            <div class="report-summary-grid">
                <article class="report-card report-stat is-info"><span>Linked Newborns</span><strong>{{ $newbornSummary['total'] }}</strong><p>From assigned mother casefiles</p></article>
                <article class="report-card report-stat is-high"><span>At Risk</span><strong>{{ $newbornSummary['at_risk'] }}</strong><p>Growth, alert, or vaccine review</p></article>
                <article class="report-card report-stat is-medium"><span>Baseline Needed</span><strong>{{ $newbornSummary['baseline_needed'] }}</strong><p>No growth baseline yet</p></article>
                <article class="report-card report-stat is-low"><span>Completed Doses</span><strong>{{ $newbornSummary['completed_vaccines'] }}</strong><p>Total completed vaccine records</p></article>
            </div>

            <div class="newborn-layout">
                <aside class="report-card newborn-panel">
                    <small>Infant Registry Lookup</small>
                    <form method="GET" action="{{ route('staff.dynamic-reports') }}" style="margin-top: 12px;">
                        <input type="hidden" name="tab" value="newborn">
                        <label class="report-search">{!! $iconSearch !!}<input name="newborn_q" type="search" value="{{ $newbornSearch }}" placeholder="Search monitored newborns"></label>
                    </form>
                    <div class="newborn-list">
                        @forelse($filteredNewbornRows as $row)
                            @php $initials = collect(preg_split('/\s+/', trim($row['name']), -1, PREG_SPLIT_NO_EMPTY) ?: [])->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('') ?: 'NB'; @endphp
                            <a class="newborn-row {{ $selectedNewborn && $selectedNewborn['id'] === $row['id'] ? 'is-active' : '' }}" href="{{ route('staff.dynamic-reports', ['tab' => 'newborn', 'newborn' => $row['id'], 'newborn_q' => $newbornSearch]) }}">
                                <span class="newborn-avatar">{{ $initials }}</span>
                                <span><strong>{{ $row['name'] }}</strong><span>Mother: {{ $row['mother']->full_name }} / {{ $row['age_months'] === null ? 'Age unknown' : $row['age_months'].' mo' }}</span></span>
                                <span class="report-pill {{ $statusTone($row['status']) }}">{{ $row['status_label'] }}</span>
                            </a>
                        @empty
                            <div class="report-empty">No newborn records match the current search.</div>
                        @endforelse
                    </div>
                </aside>

                <div class="newborn-detail">
                    @if(! $selectedNewborn)
                        <div class="report-empty">No newborn profile is linked to assigned mother casefiles yet.</div>
                    @else
                        <section class="report-card newborn-profile">
                            <div class="newborn-profile-head">
                                <div>
                                    <h2>Newborn Baseline Data File - {{ $selectedNewborn['name'] }}</h2>
                                    <p>Mother: {{ $selectedNewborn['mother']->full_name }} / Birth date: {{ $selectedNewborn['birth_date_label'] }}</p>
                                </div>
                                <div class="report-actions">
                                    <span class="report-pill {{ $statusTone($selectedNewborn['status']) }}">{{ $selectedNewborn['status_label'] }}</span>
                                    <a class="report-button is-light" href="{{ $selectedNewborn['profile_url'] }}">{!! $iconOpen !!} Open Neonatal Record</a>
                                </div>
                            </div>
                            <div class="newborn-metrics">
                                <article class="newborn-metric"><span>Birth Weight</span><strong>{{ $formatNumber($selectedNewborn['birth_weight'], ' kg') }}</strong></article>
                                <article class="newborn-metric"><span>Birth Length</span><strong>{{ $formatNumber($selectedNewborn['birth_height'], ' cm') }}</strong></article>
                                <article class="newborn-metric"><span>Head Circumference</span><strong>{{ $formatNumber($selectedNewborn['head_circumference'], ' cm') }}</strong></article>
                                <article class="newborn-metric"><span>Vaccine Status</span><strong>{{ $selectedNewborn['completed_vaccines'] }} completed / {{ $selectedNewborn['overdue_vaccines'] }} overdue</strong></article>
                            </div>
                        </section>

                        <section class="report-card report-section">
                            <h2>{!! $iconTrend !!} Longitudinal Weight Growth</h2>
                            <p class="report-kicker" style="margin-top: 6px;">Actual growth records from neonatal monitoring</p>
                            <div class="chart-panel">
                                @if($selectedWeightChart['has'])
                                    <svg viewBox="0 0 100 100" role="img" aria-label="Weight growth chart">
                                        <path class="grid" d="M14 24H90M14 51H90M14 78H90"/>
                                        <path class="axis" d="M14 18V82H90"/>
                                        <text class="label" x="4" y="25">{{ $selectedWeightChart['max'] }}</text>
                                        <text class="label" x="4" y="52">{{ $selectedWeightChart['mid'] }}</text>
                                        <text class="label" x="4" y="79">{{ $selectedWeightChart['min'] }}</text>
                                        <text class="axis-title" x="52" y="96" text-anchor="middle">Growth record date</text>
                                        @if($selectedWeightChart['points']->count() > 1)
                                            <polyline class="line" points="{{ $selectedWeightChart['path'] }}"/>
                                        @endif
                                        @foreach($selectedWeightChart['points'] as $point)
                                            <circle class="dot" cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="2">
                                                <title>{{ $point['label'] }} - {{ $formatNumber($point['value'], ' kg') }}</title>
                                            </circle>
                                            <text class="label" x="{{ $point['x'] }}" y="90" text-anchor="middle">{{ $point['label'] }}</text>
                                        @endforeach
                                    </svg>
                                @else
                                    <div class="report-empty">No weight records available for this newborn yet.</div>
                                @endif
                            </div>
                        </section>

                        <section class="report-card report-section">
                            <h2>Newborn Growth Monitoring Logs</h2>
                            @if($selectedGrowthLogs->isEmpty())
                                <div class="report-empty" style="margin-top: 14px;">No neonatal growth monitoring logs recorded yet.</div>
                            @else
                                <div class="report-table-wrap" style="margin-top: 14px;">
                                    <table class="report-table newborn-growth-table">
                                        <thead>
                                            <tr>
                                                <th>Observation Date</th>
                                                <th>Age</th>
                                                <th>Weight / Length</th>
                                                <th>Head / Temp</th>
                                                <th>Recorded By</th>
                                                <th>Clinical Indicator</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($selectedGrowthLogs->sortByDesc('measured_at') as $record)
                                                <tr>
                                                    <td>{{ $record->measured_at?->format('M j, Y') ?? 'No date' }}</td>
                                                    <td>{{ $record->age_months }} mo</td>
                                                    <td>{{ $formatNumber($record->weight, ' kg') }} / {{ $formatNumber($record->height, ' cm') }}</td>
                                                    <td>{{ $formatNumber($record->head_circumference, ' cm') }} / {{ $formatNumber($record->temperature, ' C') }}</td>
                                                    <td>{{ $record->recorder?->full_name ?? $staff->full_name }}</td>
                                                    <td><span class="report-pill {{ $statusTone($selectedNewborn['status']) }}">{{ $selectedNewborn['growth_label'] }}</span></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </section>

                        <section class="report-card report-section">
                            <h2>Newborn Vaccine Monitoring Logs</h2>
                            @if($selectedVaccineLogs->isEmpty())
                                <div class="report-empty" style="margin-top: 14px;">No vaccine monitoring logs recorded yet.</div>
                            @else
                                <div class="report-table-wrap" style="margin-top: 14px;">
                                    <table class="report-table newborn-vaccine-table">
                                        <thead>
                                            <tr>
                                                <th>Vaccine</th>
                                                <th>Dose</th>
                                                <th>Due Date</th>
                                                <th>Administration Date</th>
                                                <th>Facility</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($selectedVaccineLogs as $vaccine)
                                                @php
                                                    $vaccineTone = in_array($vaccine->status, ['overdue', 'missed'], true) ? 'is-high' : ($vaccine->status === 'completed' ? 'is-low' : 'is-medium');
                                                @endphp
                                                <tr>
                                                    <td><strong>{{ $vaccine->vaccine_name }}</strong><span class="report-code">{{ $vaccine->vaccine_group ?: 'Vaccine record' }}</span></td>
                                                    <td>{{ $vaccine->dose_label }}</td>
                                                    <td>{{ $vaccine->due_date?->format('M j, Y') ?? 'Not scheduled' }}</td>
                                                    <td>{{ $vaccine->administered_at?->format('M j, Y') ?? 'Not recorded' }}</td>
                                                    <td>{{ $vaccine->facility ?: 'Not recorded' }}</td>
                                                    <td><span class="report-pill {{ $vaccineTone }}">{{ ucfirst(str_replace('_', ' ', $vaccine->status)) }}</span></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </section>
                    @endif
                </div>
            </div>
        @endif
    </section>
    @if($activeTab === 'newborn')
        <template id="newborn-clinical-report">
            @include('records.newborn-clinical-report')
        </template>
        <script src="{{ asset('js/newborn-clinical-report.js') }}?v={{ filemtime(public_path('js/newborn-clinical-report.js')) }}" defer></script>
    @endif
    @if($activeTab === 'statistics')
        <template id="statistics-clinical-report">@include('records.staff-statistics')</template>
        <script src="{{ asset('js/newborn-clinical-report.js') }}?v={{ filemtime(public_path('js/newborn-clinical-report.js')) }}" defer></script>
    @endif
@endsection
