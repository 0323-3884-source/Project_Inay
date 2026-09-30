@extends('layouts.dswd')
@section('heading', 'System Evaluation')
@section('content')
<div class="dswd-stack"><section class="admin-card">
    <h2>Share your experience</h2><p class="dswd-note">Evaluate the ease of use of the 4Ps portal. Please keep feedback about the system and omit beneficiary names and personal information.</p>
    <form class="dswd-form" method="POST" action="{{ route('dswd.evaluation.store') }}">@csrf
        <label>Overall experience<select name="rating" required><option value="">Select a rating</option>@foreach([1 => 'Very difficult', 2 => 'Difficult', 3 => 'Neutral', 4 => 'Easy', 5 => 'Very easy'] as $value => $label)<option value="{{ $value }}" @selected((string) old('rating') === (string) $value)>{{ $value }} — {{ $label }}</option>@endforeach</select></label>
        <label>Feedback (optional)<textarea name="feedback" rows="4" maxlength="2000">{{ old('feedback') }}</textarea></label>
        <button class="dswd-button" type="submit">Submit evaluation</button>
    </form>
</section><section class="admin-card"><h2>Your submissions</h2><table class="dswd-table"><thead><tr><th>Date</th><th>Rating</th><th>Feedback</th></tr></thead><tbody>@forelse($evaluations as $evaluation)<tr><td>{{ \Illuminate\Support\Carbon::parse($evaluation->created_at)->format('M j, Y') }}</td><td>{{ $evaluation->rating }}/5</td><td>{{ $evaluation->feedback ?: '—' }}</td></tr>@empty<tr><td colspan="3">No evaluations submitted yet.</td></tr>@endforelse</tbody></table>@include('dswd.partials.pagination', ['paginator' => $evaluations])</section></div>
@endsection
