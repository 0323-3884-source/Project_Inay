<section class="clinic-panel appointment-table-panel" data-appointment-table aria-labelledby="{{ $tableId }}-heading">
    <div class="appointment-table-heading">
        <h2 id="{{ $tableId }}-heading">{{ $tableTitle }}</h2>
        <span class="clinic-count">{{ $tableRecords->count() }}</span>
    </div>
    <div class="appointment-table-tools">
        <label><span class="sr-only">Search {{ $tableTitle }}</span><input type="search" placeholder="Search patient or appointment" data-table-search></label>
        <label><span class="sr-only">Filter {{ $tableTitle }} by status</span><select data-table-status>
            <option value="">All statuses</option>
            @foreach ($tableRecords->pluck('status')->unique() as $tableStatus)
                <option value="{{ $tableStatus }}">{{ $statusLabels[$tableStatus] ?? ucfirst(str_replace('_', ' ', $tableStatus)) }}</option>
            @endforeach
        </select></label>
    </div>
    <div class="appointment-table-scroll" role="region" aria-label="{{ $tableTitle }} table" tabindex="0">
        <table class="appointment-table">
            <thead><tr>
                @foreach ($isReschedule ? ['Patient', 'Original Date', 'Requested Date', 'Reason', 'Status', 'Action'] : ['Patient', 'Appointment Type', 'Date & Time', 'Healthcare Worker', 'Status', 'Action'] as $column)
                    <th scope="col">{{ $column }}</th>
                @endforeach
            </tr></thead>
            <tbody>
                @foreach ($tableRecords as $appointment)
                    @include('partials.staff-appointment-row')
                @endforeach
                <tr data-table-empty @if($tableRecords->isNotEmpty()) hidden @endif><td colspan="6">No matching records found.</td></tr>
            </tbody>
        </table>
    </div>
    <footer class="appointment-table-footer">
        <span data-table-summary role="status"></span>
        <nav aria-label="{{ $tableTitle }} pagination" data-table-pages></nav>
    </footer>
</section>
