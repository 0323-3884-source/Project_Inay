<fieldset class="neo-simple-date" data-vaccine-date-field>
    <legend>{{ $dateLabel }}</legend>
    <input type="hidden" name="{{ $dateName }}" @if($dateName === 'due_date') data-vaccine-due-date @else data-vaccine-date @endif data-latest-date="{{ $dateName === 'administered_at' ? now()->toDateString() : '' }}">
    <div class="neo-date-parts">
        <select aria-label="{{ $dateLabel }} month" data-date-month>
            <option value="">Month</option>
            @foreach(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] as $month)
                <option value="{{ $loop->iteration }}">{{ $month }}</option>
            @endforeach
        </select>
        <select aria-label="{{ $dateLabel }} day" data-date-day>
            <option value="">Day</option>
            @for($day = 1; $day <= 31; $day++)<option value="{{ $day }}">{{ $day }}</option>@endfor
        </select>
        <input type="number" min="1" max="9999" step="1" placeholder="Year" aria-label="{{ $dateLabel }} year" data-date-year>
        <button type="button" data-date-today>Today</button>
    </div>
</fieldset>
