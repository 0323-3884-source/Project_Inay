@extends('layouts.admin')

@section('title', 'Admin Statistics - Project INAY')

@push('styles')
    <style>
        .admin-stat-card { min-height: 420px; }
        .admin-bar-chart { display: flex; align-items: stretch; gap: 10px; min-height: 260px; overflow-x: auto; padding: 10px 4px 0; }
        .admin-bar-item { display: grid; min-width: 76px; grid-template-rows: minmax(160px, 1fr) auto; gap: 10px; text-align: center; }
        .admin-bar-track { display: flex; align-items: end; justify-content: center; height: 200px; padding: 0 12px; border-bottom: 1px solid #dbe5f1; }
        .admin-bar-fill { width: 100%; min-height: 8px; border-radius: 8px 8px 0 0; background: linear-gradient(180deg, #10b981 0%, #00856a 100%); box-shadow: 0 8px 18px rgba(0, 133, 106, 0.16); transition: height .2s ease; }
        .admin-bar-label { display: grid; gap: 4px; color: #64748b; font-size: 11px; font-weight: 800; line-height: 1.25; }
        .admin-bar-label strong { color: #071127; font-size: 13px; }
        .admin-donut-wrap { display: grid; grid-template-columns: 220px minmax(0, 1fr); gap: 24px; align-items: center; min-height: 250px; }
        .admin-donut { position: relative; display: grid; width: 210px; height: 210px; place-items: center; border-radius: 999px; }
        .admin-donut::after { content: ""; position: absolute; inset: 35px; background: #ffffff; border-radius: inherit; box-shadow: inset 0 0 0 1px #e2e8f0; }
        .admin-donut-center { position: relative; z-index: 1; text-align: center; }
        .admin-donut-center strong { display: block; font-size: 36px; line-height: 1; font-weight: 900; }
        .admin-donut-center span { display: block; margin-top: 7px; color: #64748b; font-size: 12px; font-weight: 900; text-transform: uppercase; }
        .admin-legend { display: grid; gap: 12px; }
        .admin-legend-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; }
        .admin-legend-name { display: inline-flex; align-items: center; gap: 9px; color: #334155; font-weight: 900; }
        .admin-dot { width: 11px; height: 11px; border-radius: 999px; background: var(--dot); }
        .admin-legend-row strong { font-size: 18px; }
        .admin-pregnancy-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 12px; }
        .admin-pregnancy-tile { padding: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; }
        .admin-pregnancy-tile span { display: block; color: #64748b; font-size: 11px; font-weight: 900; text-transform: uppercase; }
        .admin-pregnancy-tile strong { display: block; margin-top: 10px; font-size: 27px; line-height: 1; font-weight: 900; }
        .admin-line-chart { width: 100%; min-height: 300px; }
        .admin-line-chart svg { display: block; width: 100%; height: 300px; stroke-width: 1.8; }
        .admin-line-chart .grid { stroke: #dbeafe; stroke-dasharray: 5 7; }
        .admin-line-chart .axis { stroke: #b8c7dc; }
        .admin-line-chart .line { fill: none; stroke: #00856a; stroke-width: 2.3; }
        .admin-line-chart .dot { fill: #ffffff; stroke: #ec008c; stroke-width: 2.2; }
        .admin-line-chart .label { fill: #64748b; font-size: 3.2px; font-weight: 700; stroke: none; }
        .admin-ranking { display: grid; gap: 12px; }
        .admin-rank-row { display: grid; grid-template-columns: 34px minmax(0, 1fr) 56px; gap: 12px; align-items: center; }
        .admin-rank-number { display: grid; width: 34px; height: 34px; place-items: center; color: #007f5f; background: #ecfdf5; border: 1px solid #c9f2df; border-radius: 8px; font-weight: 900; }
        .admin-rank-label { display: flex; justify-content: space-between; gap: 10px; margin-bottom: 6px; color: #334155; font-size: 13px; font-weight: 900; }
        .admin-rank-track { height: 12px; overflow: hidden; background: #e2e8f0; border-radius: 999px; }
        .admin-rank-fill { height: 100%; background: linear-gradient(90deg, #00856a, #2dd4bf); border-radius: inherit; }
        .admin-rank-count { color: #071127; font-size: 16px; font-weight: 900; text-align: right; }
        @media (max-width: 980px) { .admin-donut-wrap { grid-template-columns: 1fr; justify-items: center; } .admin-pregnancy-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 620px) { .admin-pregnancy-grid { grid-template-columns: 1fr; } .admin-rank-row { grid-template-columns: 30px minmax(0, 1fr); } .admin-rank-count { grid-column: 2; text-align: left; } }
    </style>
@endpush

@section('content')
    @php
        $dashboardIcons = [
            'stats' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 16V9"/><path d="M12 16V6"/><path d="M16 16v-4"/></svg>',
            'users' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/></svg>',
            'heart' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 5.6a5.4 5.4 0 0 0-7.6 0L12 6.8l-1.2-1.2a5.4 5.4 0 1 0-7.6 7.6l1.2 1.2L12 22l7.6-7.6 1.2-1.2a5.4 5.4 0 0 0 0-7.6Z"/></svg>',
            'shield' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>',
            'map' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3V6Z"/><path d="M9 3v15"/><path d="M15 6v15"/></svg>',
            'alert' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>',
            'activity' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>',
        ];
        $lineCount = max(1, $monthlyRegistrations->count());
        $monthlyPoints = $monthlyRegistrations->map(function (array $row, int $index) use ($lineCount, $maxMonthlyCount): array {
            $x = $lineCount > 1 ? 8 + (($index / ($lineCount - 1)) * 84) : 50;
            $y = 86 - (($row['total'] / max(1, $maxMonthlyCount)) * 62);

            return [
                'x' => round($x, 2),
                'y' => round($y, 2),
                'total' => $row['total'],
                'label' => $row['label'],
                'short_label' => $row['short_label'],
            ];
        });
        $linePath = $monthlyPoints->map(fn (array $point, int $index): string => ($index === 0 ? 'M ' : 'L ').$point['x'].' '.$point['y'])->implode(' ');
        $beneficiaryPercent = $beneficiaryStats['percentage_4ps'];
        $donutBackground = 'conic-gradient(#00856a 0 '.$beneficiaryPercent.'%, #e2e8f0 '.$beneficiaryPercent.'% 100%)';
    @endphp

    <header class="admin-topbar">
        <div>
            <p class="admin-kicker">Admin / Statistics</p>
            <h1 class="admin-page-title">Statistics Dashboard</h1>
            <p class="admin-page-copy">Live maternal health indicators from registered Project INAY records.</p>
        </div>
        <span class="admin-user-chip">
            {!! $dashboardIcons['shield'] !!}
            {{ $adminUsername }}
        </span>
    </header>

    <section class="admin-summary-grid" aria-label="Admin summary cards">
        @foreach($summaryCards as $card)
            <article class="admin-summary-card">
                <span class="admin-summary-icon">{!! $dashboardIcons[$card['icon']] ?? $dashboardIcons['stats'] !!}</span>
                <div>
                    <span>{{ $card['title'] }}</span>
                    <strong>{{ number_format($card['count']) }}</strong>
                </div>
            </article>
        @endforeach
    </section>

    <section class="admin-grid">
        <article class="admin-card admin-stat-card">
            <div class="admin-card-head">
                <div>
                    <h2>Pregnant Mothers by Barangay</h2>
                    <p>Registered pregnant mothers grouped by barangay.</p>
                </div>
            </div>
            @if($pregnantByBarangay->isEmpty())
                <div class="admin-empty">No pregnant mother records yet.</div>
            @else
                <div class="admin-bar-chart" role="img" aria-label="Bar chart of pregnant mothers by barangay">
                    @foreach($pregnantByBarangay as $row)
                        @php $height = max(8, ($row['total'] / max(1, $maxBarangayCount)) * 100); @endphp
                        <div class="admin-bar-item">
                            <div class="admin-bar-track"><div class="admin-bar-fill" style="height: {{ $height }}%"></div></div>
                            <span class="admin-bar-label"><strong>{{ $row['total'] }}</strong>{{ $row['barangay'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </article>

        <article class="admin-card admin-stat-card">
            <div class="admin-card-head">
                <div>
                    <h2>4Ps Beneficiaries</h2>
                    <p>Coverage among all registered mothers.</p>
                </div>
            </div>
            <div class="admin-donut-wrap">
                <div class="admin-donut" style="background: {{ $donutBackground }}">
                    <div class="admin-donut-center">
                        <strong>{{ $beneficiaryStats['percentage_4ps'] }}%</strong>
                        <span>4Ps</span>
                    </div>
                </div>
                <div class="admin-legend">
                    <div class="admin-legend-row">
                        <span class="admin-legend-name"><i class="admin-dot" style="--dot:#00856a"></i>Total Mothers</span>
                        <strong>{{ number_format($beneficiaryStats['total_mothers']) }}</strong>
                    </div>
                    <div class="admin-legend-row">
                        <span class="admin-legend-name"><i class="admin-dot" style="--dot:#10b981"></i>4Ps Beneficiaries</span>
                        <strong>{{ number_format($beneficiaryStats['total_4ps']) }}</strong>
                    </div>
                    <div class="admin-legend-row">
                        <span class="admin-legend-name"><i class="admin-dot" style="--dot:#cbd5e1"></i>Non-4Ps</span>
                        <strong>{{ number_format($beneficiaryStats['total_non_4ps']) }}</strong>
                    </div>
                </div>
            </div>
        </article>

        <article class="admin-card">
            <div class="admin-card-head">
                <div>
                    <h2>Pregnancy Statistics</h2>
                    <p>Current maternal status and latest risk classification.</p>
                </div>
            </div>
            <div class="admin-pregnancy-grid">
                <article class="admin-pregnancy-tile"><span>Total Pregnant</span><strong>{{ number_format($pregnancyStats['total_pregnant']) }}</strong></article>
                <article class="admin-pregnancy-tile"><span>Active</span><strong>{{ number_format($pregnancyStats['active']) }}</strong></article>
                <article class="admin-pregnancy-tile"><span>Completed</span><strong>{{ number_format($pregnancyStats['completed']) }}</strong></article>
                <article class="admin-pregnancy-tile"><span>High Risk</span><strong>{{ number_format($pregnancyStats['high_risk']) }}</strong></article>
                <article class="admin-pregnancy-tile"><span>Low Risk</span><strong>{{ number_format($pregnancyStats['low_risk']) }}</strong></article>
            </div>
        </article>

        <article class="admin-card">
            <div class="admin-card-head">
                <div>
                    <h2>Monthly Registrations</h2>
                    <p>Newly registered pregnant mothers over the last 12 months.</p>
                </div>
            </div>
            <div class="admin-line-chart" role="img" aria-label="Line chart of monthly pregnant mother registrations">
                <svg viewBox="0 0 100 100" preserveAspectRatio="none">
                    <path class="grid" d="M8 24H94M8 55H94M8 86H94"/>
                    <path class="axis" d="M8 18V88H94"/>
                    @if($monthlyPoints->count() > 1)
                        <path class="line" d="{{ $linePath }}"/>
                    @endif
                    @foreach($monthlyPoints as $point)
                        <circle class="dot" cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="1.6">
                            <title>{{ $point['label'] }} - {{ $point['total'] }}</title>
                        </circle>
                        <text class="label" x="{{ $point['x'] }}" y="96" text-anchor="middle">{{ $point['short_label'] }}</text>
                    @endforeach
                </svg>
            </div>
        </article>

        <article class="admin-card">
            <div class="admin-card-head">
                <div>
                    <h2>Barangay Ranking</h2>
                    <p>Top barangays with the highest number of registered pregnant mothers.</p>
                </div>
            </div>
            @if($topBarangays->isEmpty())
                <div class="admin-empty">No barangay ranking available yet.</div>
            @else
                <div class="admin-ranking">
                    @foreach($topBarangays as $index => $row)
                        @php $width = max(7, ($row['total'] / max(1, $maxTopCount)) * 100); @endphp
                        <div class="admin-rank-row">
                            <span class="admin-rank-number">{{ $index + 1 }}</span>
                            <div>
                                <div class="admin-rank-label"><span>{{ $row['barangay'] }}</span></div>
                                <div class="admin-rank-track"><div class="admin-rank-fill" style="width: {{ $width }}%"></div></div>
                            </div>
                            <span class="admin-rank-count">{{ $row['total'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </article>
    </section>
@endsection
