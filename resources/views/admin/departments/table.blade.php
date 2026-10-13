<div class="department-table-wrap">
    <table class="department-table">
        <thead><tr>@foreach($headers as $heading)<th scope="col">{{ $heading }}</th>@endforeach @if(!empty($editBeneficiary))<th scope="col">Manage Program Information</th>@endif @if(!empty($viewClinicalRecord))<th scope="col">Clinical Records</th>@endif</tr></thead>
        <tbody>
            @forelse($records as $row)
                <tr>@foreach($row['cells'] as $cell)<td>{{ $cell ?? 'Not recorded' }}</td>@endforeach
                    @if(!empty($editBeneficiary))
                        <td><details><summary>Edit</summary><form class="department-edit" method="POST" action="{{ route('admin.dswd.beneficiaries.update', $row['id']) }}">@csrf @method('PATCH')
                            <label>Household ID<input name="four_ps_household_number" value="{{ $row['cells'][1] }}" maxlength="255"></label>
                            <label>Barangay<input name="barangay" value="{{ $row['cells'][2] }}" maxlength="255"></label>
                            <label>Municipality / City<input name="municipality_city" value="{{ $row['cells'][3] }}" maxlength="255"></label>
                            <button class="department-action" type="submit">Save</button></form></details></td>
                    @endif
                    @if(!empty($viewClinicalRecord))<td><a href="{{ route('admin.cho.mothers.show', $row['id']) }}">View records</a></td>@endif
                </tr>
            @empty
                <tr><td colspan="{{ count($headers) + (!empty($editBeneficiary) ? 1 : 0) + (!empty($viewClinicalRecord) ? 1 : 0) }}">No records available.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
