@if($beneficiary->classification !== 'child')<br><small>Last Menstrual Period: {{ $beneficiary->last_menstrual_period?->format('M j, Y') ?? 'Not recorded' }}</small>@endif
