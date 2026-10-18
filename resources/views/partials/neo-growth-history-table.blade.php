<div class="neo-table-wrap">
                                <table class="neo-table">
                                    <thead>
                                        <tr>
                                            <th>Age</th>
                                            <th>Weight</th>
                                            <th>Height</th>
                                            <th>Head / Temp</th>
                                            <th>Date</th>
                                            <th>Recorded By</th>
                                            <th class="neo-table-action-head">Edit Growth</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($growthHistoryRows as $record)
                                            <tr>
                                                <td>{{ $record->age_months }} mo</td>
                                                <td>{{ $formatNumber($record->weight, ' kg') }}</td>
                                                <td>{{ $formatNumber($record->height, ' cm') }}</td>
                                                <td>{{ $formatNumber($record->head_circumference, ' cm') }} / {{ $formatNumber($record->temperature, ' C') }}</td>
                                                <td>{{ $record->measured_at?->format('M j, Y') }}</td>
                                                <td>{{ $record->recorder?->full_name ?? $staff->full_name }}</td>
                                                <td class="neo-table-action-cell">
                                                    <button
                                                        class="neo-table-action"
                                                        type="button"
                                                        data-neo-growth
                                                        data-action="{{ route('staff.neonatal.growth.update', $record) }}"
                                                        data-date="{{ $record->measured_at?->toDateString() }}"
                                                        data-age="{{ $record->age_months }}"
                                                        data-weight="{{ $record->weight }}"
                                                        data-height="{{ $record->height }}"
                                                        data-head="{{ $record->head_circumference }}"
                                                        data-temp="{{ $record->temperature }}"
                                                        data-remarks="{{ $record->remarks }}"
                                                        aria-label="Edit growth record from {{ $record->measured_at?->format('M j, Y') }}"
                                                    >
                                                        {!! $iconEdit !!} Edit
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
