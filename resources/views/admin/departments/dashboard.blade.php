@extends('layouts.admin')
@section('title', strtoupper($scope).' Admin - Project INAY')
@section('content')
    <header class="admin-topbar">
        <div><p class="admin-kicker">{{ strtoupper($scope) }} ADMIN CONSOLE</p><h1 class="admin-page-title">{{ !empty($reportMode) ? ($scope === 'dswd' ? 'Reports and Statistics' : 'Health Statistics and Reports') : 'Dashboard' }}</h1>
            <p class="admin-page-copy">{{ $scope === 'dswd' ? '4Ps beneficiaries, F1KD monitoring, and monthly health attendance.' : 'Maternal and child health, clinical records, and healthcare personnel.' }}</p></div>
        <span class="admin-user-chip">{{ session('admin_username') }}</span>
    </header>
    <form class="department-filter" method="GET">
        <label>Reporting month<input type="month" name="month" value="{{ $month }}" max="{{ now()->format('Y-m') }}"></label>
        <button type="submit">Update</button>
        <a class="department-action" href="{{ route('admin.'.$scope.'.reports', ['month' => $month, 'download' => 1]) }}">Download CSV Report</a>
    </form>
    <p class="department-note">{{ $scope === 'dswd' ? 'Attendance and F1KD population use the selected reporting month and existing F1KD eligibility rules. Registered beneficiary and staff totals reflect current records. Pending attendance is not counted as missed.' : 'Registered totals reflect current records. Pending screenings count active pregnancies awaiting a first record or whose latest record requires review, interpretation, or urgent referral. The trend ends in the selected month.' }}</p>
    <section class="admin-summary-grid" aria-label="Dashboard statistics">
        @foreach ($cards as $label => $count)
            <article class="admin-summary-card"><span class="admin-summary-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V5M4 19h16M8 16V9M12 16V6M16 16v-4"/></svg></span><div><span>{{ $label }}</span><strong>{{ number_format($count) }}</strong></div></article>
        @endforeach
    </section>
    <div class="admin-grid">
        @foreach ($charts as $title => $values)
            <section class="admin-card"><div class="admin-card-head"><h2>{{ $title }}</h2></div>
                @if (count($values))
                    <div class="department-chart" role="img" aria-label="{{ $title }}: {{ collect($values)->map(fn($count, $label) => $label.': '.$count)->implode(', ') }}">
                        @foreach ($values as $label => $count)
                            <div class="department-chart-row"><div><span>{{ $label }}</span><strong>{{ number_format($count) }}</strong></div><div class="department-chart-track"><div class="department-chart-fill" style="width: {{ max($values) > 0 ? round($count / max($values) * 100, 2) : 0 }}%"></div></div></div>
                        @endforeach
                    </div>
                @else
                    <div class="admin-empty">No records available.</div>
                @endif
            </section>
        @endforeach
    </div>
@endsection
