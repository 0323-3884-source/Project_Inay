@extends('layouts.app')

@section('title', 'Mother Case File - Project INAY')
@section('portal_title', 'Mother Case File')

@php
    $iconSearch = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>';
    $iconFilter = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 5h18"/><path d="M7 12h10"/><path d="M10 19h4"/></svg>';
    $iconEye = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
    $iconChevronLeft = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>';
    $iconChevronRight = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>';

    $fourPsLabels = [
        'all' => 'All Patients',
        'beneficiary' => '4Ps Beneficiary',
        'non_4ps' => 'Non-4Ps',
    ];
    $pregnancyStatusLabels = [
        'pregnant' => 'Pregnant',
        'postpartum' => 'Postpartum',
        'planning' => 'Planning',
        'not_pregnant' => 'Not pregnant',
    ];
    $screeningStatusLabel = fn ($record) => \App\Support\MaternalVitalScreening::normalizeStatus($record?->screening_summary_status ?? $record?->risk_level);
    $screeningStatusClass = fn (?string $status) => 'is-'.\App\Support\MaternalVitalScreening::statusSlug($status);
    $patientNumber = fn ($mother) => 'INAY-'.str_pad((string) $mother->id, 5, '0', STR_PAD_LEFT);
    $initials = fn ($mother) => strtoupper(substr($mother->first_name, 0, 1).substr($mother->last_name, 0, 1));
    $trimesterForWeek = function (?int $week): ?string {
        if (! $week || $week < 1) {
            return null;
        }

        if ($week <= 13) {
            return '1st Trimester';
        }

        if ($week <= 27) {
            return '2nd Trimester';
        }

        return '3rd Trimester';
    };
    $pregnancyBadge = function ($mother) use ($pregnancyStatusLabels, $trimesterForWeek): array {
        $latestWeek = (int) ($mother->maternalMonitoringRecords->first()?->pregnancy_week ?? 0);
        $trimester = $mother->pregnancy_status === 'pregnant' ? $trimesterForWeek($latestWeek) : null;

        if ($trimester) {
            return [
                'label' => $trimester.', Week '.$latestWeek,
                'class' => 'is-trimester',
            ];
        }

        return [
            'label' => $pregnancyStatusLabels[$mother->pregnancy_status] ?? 'Status pending',
            'class' => 'is-'.str_replace('_', '-', $mother->pregnancy_status ?: 'pending'),
        ];
    };
    $isFiltered = $search !== '' || $fourPs !== 'all';
@endphp

@push('styles')
    <style>
        .casefiles-table-page {
            gap: 18px;
        }

        .casefiles-table-page .casefiles-heading {
            padding-bottom: 18px;
        }

        .casefiles-table-page .casefiles-heading h1 {
            font-size: 28px;
        }

        .casefiles-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 12px;
        }

        .casefiles-summary article {
            display: grid;
            gap: 8px;
            padding: 16px;
            background: #ffffff;
            border: 1px solid #dbe5f0;
            border-radius: 8px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
        }

        .casefiles-summary span,
        .casefiles-table th,
        .casefiles-count-copy {
            color: #7b8aa1;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .casefiles-summary strong {
            color: #061125;
            font-size: 26px;
            font-weight: 900;
            line-height: 1;
        }

        .casefiles-toolbar {
            display: grid;
            grid-template-columns: minmax(260px, 1fr) minmax(210px, 260px) auto;
            gap: 12px;
            align-items: center;
        }

        .casefiles-toolbar .casefiles-search,
        .casefiles-filter-control {
            margin: 0;
            min-height: 48px;
            border-radius: 8px;
        }

        .casefiles-filter-control {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 12px;
            color: #64748b;
            background: #ffffff;
            border: 1px solid #cdd9e8;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
        }

        .casefiles-filter-control select {
            min-height: 46px;
            padding: 0;
            color: #172033;
            background: transparent;
            border: 0;
            font-size: 14px;
            font-weight: 800;
            outline: none;
        }

        .casefiles-clear-link {
            display: inline-flex;
            min-height: 48px;
            align-items: center;
            justify-content: center;
            padding: 0 16px;
            color: #40536f;
            background: #ffffff;
            border: 1px solid #cdd9e8;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 900;
            text-decoration: none;
        }

        .casefiles-clear-link:hover {
            color: #ec0a78;
            background: #fff3fa;
            border-color: #ffd4e7;
            text-decoration: none;
        }

        .casefiles-table-card {
            overflow: hidden;
            background: #ffffff;
            border: 1px solid #dbe5f0;
            border-radius: 8px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
        }

        .casefiles-loading-state {
            display: flex;
            min-height: 44px;
            align-items: center;
            justify-content: center;
            color: #40536f;
            background: #ffffff;
            border: 1px solid #dbe5f0;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 900;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.04);
        }

        .casefiles-loading-state[hidden] {
            display: none;
        }

        .casefiles-table-scroll {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .casefiles-table {
            width: 100%;
            min-width: 0;
            table-layout: fixed;
            border-collapse: separate;
            border-spacing: 0;
        }

        .casefiles-table th,
        .casefiles-table td {
            padding: 12px 13px;
            border-bottom: 1px solid #edf2f7;
            text-align: left;
            vertical-align: middle;
        }

        .casefiles-table th {
            background: #fbfcfe;
            color: #71819a;
            font-size: 10px;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }

        .casefiles-table th:nth-child(1),
        .casefiles-table td:nth-child(1) { width: 10%; }

        .casefiles-table th:nth-child(2),
        .casefiles-table td:nth-child(2) { width: 18%; }

        .casefiles-table th:nth-child(3),
        .casefiles-table td:nth-child(3) { width: 5%; }

        .casefiles-table th:nth-child(4),
        .casefiles-table td:nth-child(4) { width: 11%; }

        .casefiles-table th:nth-child(5),
        .casefiles-table td:nth-child(5) { width: 9%; }

        .casefiles-table th:nth-child(6),
        .casefiles-table td:nth-child(6) { width: 11%; }

        .casefiles-table th:nth-child(7),
        .casefiles-table td:nth-child(7) { width: 7%; }

        .casefiles-table th:nth-child(8),
        .casefiles-table td:nth-child(8) { width: 10%; }

        .casefiles-table th:nth-child(9),
        .casefiles-table td:nth-child(9) { width: 7%; }

        .casefiles-table th:last-child,
        .casefiles-table td:last-child {
            width: 12%;
        }

        .casefiles-table thead th:last-child {
            background: #fbfcfe;
        }

        .casefiles-table tbody tr:hover {
            background: #fff8fc;
        }

        .casefiles-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .casefiles-patient-code {
            color: #0f172a;
            font-size: 13px;
            font-weight: 900;
            white-space: nowrap;
        }

        .casefiles-patient-cell {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: 10px;
        }

        .casefiles-table .casefile-avatar {
            width: 38px;
            height: 38px;
            font-size: 12px;
        }

        .casefiles-patient-name {
            display: grid;
            gap: 3px;
            min-width: 0;
        }

        .casefiles-patient-name strong {
            color: #061125;
            font-size: 13px;
            font-weight: 900;
            line-height: 1.25;
        }

        .casefiles-patient-name span,
        .casefiles-muted {
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .casefiles-table td {
            color: #172033;
            font-size: 12px;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .casefiles-badge {
            display: inline-flex;
            min-height: 28px;
            align-items: center;
            justify-content: center;
            padding: 0 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 900;
            white-space: nowrap;
        }

        .casefiles-badge.is-4ps,
        .casefiles-badge.is-within-reference-range {
            color: #007f5f;
            background: #ecfdf5;
            border: 1px solid #86efc2;
        }

        .casefiles-badge.is-non-4ps,
        .casefiles-badge.is-pending,
        .casefiles-badge.is-logged {
            color: #64748b;
            background: #f8fafc;
            border: 1px solid #dbe5f0;
        }

        .casefiles-badge.is-urgent-referral-recommended {
            color: #dc2626;
            background: #fff1f2;
            border: 1px solid #fecaca;
        }

        .casefiles-badge.is-for-review,
        .casefiles-badge.is-planning {
            color: #b45309;
            background: #fff7ed;
            border: 1px solid #fed7aa;
        }

        .casefiles-badge.is-for-professional-interpretation {
            color: #1d4ed8;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
        }

        .casefiles-badge.is-trimester,
        .casefiles-badge.is-pregnant {
            color: #1d4ed8;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
        }

        .casefiles-badge.is-postpartum {
            color: #b01564;
            background: #fff0f8;
            border: 1px solid #ffd4e7;
        }

        .casefiles-badge.is-not-pregnant {
            color: #475569;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
        }

        .casefiles-view-button {
            display: inline-flex;
            width: 100%;
            min-height: 36px;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 9px;
            color: #ffffff;
            background: #ec0a78;
            border: 1px solid #ec0a78;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 900;
            text-decoration: none;
            white-space: nowrap;
        }

        .casefiles-view-button:hover {
            color: #ffffff;
            background: #d80b78;
            border-color: #d80b78;
            text-decoration: none;
        }

        .casefiles-table-empty {
            display: grid;
            gap: 8px;
            padding: 36px 18px;
            text-align: center;
        }

        .casefiles-table-empty strong {
            color: #061125;
            font-size: 18px;
            font-weight: 900;
        }

        .casefiles-table-empty span {
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
        }

        .casefiles-table-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 16px;
            border-top: 1px solid #edf2f7;
        }

        .casefiles-pagination {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .casefiles-page-link,
        .casefiles-page-gap {
            display: inline-flex;
            min-width: 34px;
            height: 34px;
            align-items: center;
            justify-content: center;
            color: #334155;
            background: #ffffff;
            border: 1px solid #dbe5f0;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 900;
            text-decoration: none;
        }

        .casefiles-page-link svg {
            width: 16px;
            height: 16px;
        }

        .casefiles-page-link.is-active {
            color: #ffffff;
            background: #ec0a78;
            border-color: #ec0a78;
        }

        .casefiles-page-link.is-disabled,
        .casefiles-page-gap {
            color: #94a3b8;
            background: #f8fafc;
            cursor: default;
        }

        .casefiles-page-link:not(.is-active):not(.is-disabled):hover {
            color: #ec0a78;
            background: #fff3fa;
            border-color: #ffd4e7;
            text-decoration: none;
        }

        @media (max-width: 980px) {
            .casefiles-toolbar {
                grid-template-columns: 1fr;
            }

            .casefiles-clear-link {
                width: 100%;
            }
        }

        @media (max-width: 1100px) {
            .casefiles-table {
                min-width: 1080px;
            }

            .casefiles-table th:last-child,
            .casefiles-table td:last-child {
                width: 148px;
                position: sticky;
                right: 0;
                z-index: 2;
                background: #ffffff;
                box-shadow: -8px 0 12px rgba(15, 23, 42, 0.06);
            }

            .casefiles-table thead th:last-child {
                z-index: 3;
                background: #fbfcfe;
            }

            .casefiles-table tbody tr:hover td:last-child {
                background: #fff8fc;
            }
        }

        @media (max-width: 680px) {
            .casefiles-table-page .casefiles-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .casefiles-table-page .casefiles-heading h1 {
                font-size: 24px;
            }

            .casefiles-table-footer {
                align-items: flex-start;
                flex-direction: column;
            }

            .casefiles-pagination {
                width: 100%;
                justify-content: flex-start;
            }
        }
    </style>
@endpush

@section('content')
    <section class="casefiles-shell casefiles-table-page" aria-label="Program staff mother casefiles">
        <header class="casefiles-heading">
            <div>
                <p>Patient Registry <strong>&middot;</strong> Registered Mothers</p>
                <h1>Mother Case File</h1>
                <span>All mothers registered in Project INAY appear here automatically.</span>
            </div>
        </header>

        <div class="casefiles-summary" aria-label="Mother case file summary">
            <article>
                <span>Registered Mothers</span>
                <strong>{{ number_format($totalMothers) }}</strong>
            </article>
            <article>
                <span>With Monitoring Records</span>
                <strong>{{ number_format($withMonitoring) }}</strong>
            </article>
        </div>

        <form class="casefiles-toolbar" method="GET" action="{{ route('staff.mothers') }}" data-casefiles-filter-form>
            <label class="casefiles-search">
                {!! $iconSearch !!}
                <input type="search" name="q" value="{{ $search }}" placeholder="Search name, case no., barangay, or contact number">
            </label>
            <label class="casefiles-filter-control">
                {!! $iconFilter !!}
                <select name="four_ps" aria-label="Filter by 4Ps status" data-casefiles-auto-submit>
                    @foreach ($fourPsLabels as $value => $label)
                        <option value="{{ $value }}" @selected($fourPs === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            @if ($isFiltered)
                <a class="casefiles-clear-link" href="{{ route('staff.mothers') }}">Clear</a>
            @endif
        </form>

        <div class="casefiles-loading-state" data-casefiles-loading hidden>Loading mother records...</div>

        <section class="casefiles-table-card" aria-label="Registered mothers table">
            <div class="casefiles-table-scroll">
                <table class="casefiles-table">
                    <thead>
                        <tr>
                            <th>Case No.</th>
                            <th>Full Name</th>
                            <th>Age</th>
                            <th>Barangay</th>
                            <th title="Contact Number">Contact</th>
                            <th>Pregnancy</th>
                            <th title="4Ps Status">4Ps Status</th>
                            <th title="Screening Status">Screening</th>
                            <th title="Date Registered">Registered</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($mothers as $mother)
                            @php
                                $latestRecord = $mother->maternalMonitoringRecords->first();
                                $riskLabel = $screeningStatusLabel($latestRecord);
                                $riskClass = $screeningStatusClass($riskLabel);
                                $pregnancy = $pregnancyBadge($mother);
                            @endphp
                            <tr>
                                <td><span class="casefiles-patient-code">{{ $patientNumber($mother) }}</span></td>
                                <td>
                                    <div class="casefiles-patient-cell">
                                        <span class="casefile-avatar">{{ $initials($mother) }}</span>
                                        <span class="casefiles-patient-name">
                                            <strong>{{ $mother->full_name }}</strong>
                                            <span>{{ $mother->email }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td>{{ $mother->age ? $mother->age : 'Not set' }}</td>
                                <td>{{ $mother->barangay ?: 'Not set' }}</td>
                                <td>{{ $mother->contact_number ?: 'Not set' }}</td>
                                <td><span class="casefiles-badge {{ $pregnancy['class'] }}">{{ $pregnancy['label'] }}</span></td>
                                <td>
                                    <span class="casefiles-badge {{ $mother->is_4ps_beneficiary ? 'is-4ps' : 'is-non-4ps' }}">
                                        {{ $mother->is_4ps_beneficiary ? '4Ps' : 'Non-4Ps' }}
                                    </span>
                                </td>
                                <td><span class="casefiles-badge {{ $riskClass }}">{{ $riskLabel }}</span></td>
                                <td>{{ $mother->created_at?->format('M j, Y') ?? 'Unknown' }}</td>
                                <td>
                                    <a class="casefiles-view-button" href="{{ route('staff.mothers.show', $mother) }}">
                                        {!! $iconEye !!}
                                        View Case File
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10">
                                    <div class="casefiles-table-empty">
                                        <strong>{{ $isFiltered ? 'No mothers match your search.' : 'No mother records found.' }}</strong>
                                        <span>{{ $isFiltered ? 'Try another search term or clear the current filter.' : 'New mother registrations will appear here automatically.' }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <footer class="casefiles-table-footer">
                <span class="casefiles-count-copy">
                    @if ($mothers->total() > 0)
                        Showing {{ $mothers->firstItem() }} to {{ $mothers->lastItem() }} of {{ $mothers->total() }} records
                    @else
                        Showing 0 records
                    @endif
                </span>

                @if ($mothers->hasPages())
                    @php
                        $currentPage = $mothers->currentPage();
                        $lastPage = $mothers->lastPage();
                        $pageStart = max(1, $currentPage - 2);
                        $pageEnd = min($lastPage, $currentPage + 2);
                    @endphp

                    <nav class="casefiles-pagination" aria-label="Mother case file pages">
                        @if ($mothers->onFirstPage())
                            <span class="casefiles-page-link is-disabled" aria-hidden="true">{!! $iconChevronLeft !!}</span>
                        @else
                            <a class="casefiles-page-link" href="{{ $mothers->previousPageUrl() }}" aria-label="Previous page">{!! $iconChevronLeft !!}</a>
                        @endif

                        @if ($pageStart > 1)
                            <a class="casefiles-page-link" href="{{ $mothers->url(1) }}">1</a>
                            @if ($pageStart > 2)
                                <span class="casefiles-page-gap">...</span>
                            @endif
                        @endif

                        @for ($page = $pageStart; $page <= $pageEnd; $page++)
                            @if ($page === $currentPage)
                                <span class="casefiles-page-link is-active" aria-current="page">{{ $page }}</span>
                            @else
                                <a class="casefiles-page-link" href="{{ $mothers->url($page) }}">{{ $page }}</a>
                            @endif
                        @endfor

                        @if ($pageEnd < $lastPage)
                            @if ($pageEnd < $lastPage - 1)
                                <span class="casefiles-page-gap">...</span>
                            @endif
                            <a class="casefiles-page-link" href="{{ $mothers->url($lastPage) }}">{{ $lastPage }}</a>
                        @endif

                        @if ($mothers->hasMorePages())
                            <a class="casefiles-page-link" href="{{ $mothers->nextPageUrl() }}" aria-label="Next page">{!! $iconChevronRight !!}</a>
                        @else
                            <span class="casefiles-page-link is-disabled" aria-hidden="true">{!! $iconChevronRight !!}</span>
                        @endif
                    </nav>
                @endif
            </footer>
        </section>
    </section>

    <script>
        (() => {
            const form = document.querySelector('[data-casefiles-filter-form]');
            const loading = document.querySelector('[data-casefiles-loading]');

            form?.addEventListener('submit', () => {
                loading?.removeAttribute('hidden');
            });

            document.querySelectorAll('[data-casefiles-auto-submit]').forEach((select) => {
                select.addEventListener('change', () => {
                    select.form?.requestSubmit();
                });
            });
        })();
    </script>
@endsection
