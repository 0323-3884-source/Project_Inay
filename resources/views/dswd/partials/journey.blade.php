<section class="admin-card dswd-journey" aria-labelledby="dswd-journey-heading">
    <h2 id="dswd-journey-heading">Pregnancy Journey</h2>
    <p class="dswd-note">Pregnancy status: <strong>{{ \App\Support\DswdStatistics::PREGNANCY[$beneficiary->pregnancy_status] ?? 'Not recorded' }}</strong>. The stages below describe the journey; learning activity and document submissions do not confirm a clinical stage.</p>
    <div class="dswd-journey-stages">
        @foreach (['Registration' => $beneficiary->created_at?->format('M j, Y') ?? 'Date not recorded', 'First Trimester' => 'Weeks 1–13', 'Second Trimester' => 'Weeks 14–27', 'Third Trimester' => 'Weeks 28–40', 'Delivery' => 'Birth and delivery', 'Postpartum Care' => 'After delivery'] as $stage => $description)
        <article class="{{ $stage === 'Registration' ? 'is-complete' : ($stage === 'Postpartum Care' && $beneficiary->pregnancy_status === 'postpartum' ? 'is-current' : '') }}">
            <span class="dswd-stage-number" aria-hidden="true">{{ $loop->iteration }}</span><strong>{{ $stage }}</strong><small>{{ $description }}</small>
            @if($stage === 'Registration')<span class="dswd-badge">Registered</span>@elseif($stage === 'Postpartum Care' && $beneficiary->pregnancy_status === 'postpartum')<span class="dswd-badge">Current status</span>@else<span class="dswd-note">Stage not confirmed</span>@endif
        </article>
        @endforeach
    </div>
</section>
<section class="admin-card" aria-labelledby="dswd-learning-heading">
    <h2 id="dswd-learning-heading">Learning &amp; Documents</h2>
    <p class="dswd-note">Choose a month to see learning progress and view submitted documents.</p>
    @php
        $selectedMonth = $journeyDocuments->keys()->sort()->first() ?? $journeyLearning->keys()->sort()->first() ?? 1;
    @endphp
    <label class="dswd-month-picker" for="dswd-month-select">Month
        <select id="dswd-month-select">@foreach(range(1, 9) as $month)<option value="{{ $month }}" @selected((int) $selectedMonth === $month)>Month {{ $month }}{{ $journeyDocuments->has($month) ? ' · '.$journeyDocuments->get($month)->count().' document(s)' : '' }}</option>@endforeach</select>
    </label>
    <div class="dswd-month-grid">
    @foreach(range(1, 9) as $month)
        @php
            $upload = $journeyUploads->get($month);
            $activities = $journeyLearning->get($month, collect());
            $completed = $activities->filter(fn ($activity) => $activity->status === match ($activity->activity_type) { 'reading' => 'read', 'video' => 'watched', 'infographic' => 'reviewed', default => null })->count();
        @endphp
        <section class="dswd-month" data-dswd-month="{{ $month }}" aria-label="Month {{ $month }}" @if((int) $selectedMonth !== $month) hidden @endif>
            <p class="dswd-note">{{ $activities->isEmpty() ? 'No learning activity yet.' : $completed.' learning activities completed.' }} <span class="dswd-badge">{{ $upload ? $upload->total.' file(s) received' : 'No documents received' }}</span></p>
            @forelse($journeyDocuments->get($month, collect()) as $document)
                <div class="dswd-document">
                    <div>
                    <strong>{{ $document->original_name }}</strong>
                    <p class="dswd-note">{{ $document->record_type }} · {{ $document->created_at?->format('M j, Y') }}</p>
                    </div>
                    <a class="dswd-button secondary" data-dswd-preview data-title="{{ $document->original_name }}" href="{{ route('dswd.documents.preview', ['beneficiary' => $beneficiary->id, 'document' => $document->id]) }}" aria-haspopup="dialog">View document</a>
                </div>
            @empty
                <p class="dswd-document-empty">No documents for this month yet.</p>
            @endforelse
        </section>
    @endforeach
    </div>
</section>
<dialog id="dswd-document-dialog" aria-labelledby="dswd-document-title">
    <header class="dswd-preview-header"><h2 id="dswd-document-title">Document preview</h2><button type="button" id="dswd-preview-close" class="dswd-button secondary" autofocus>Close</button></header>
    <p id="dswd-preview-status" role="status">Loading document…</p>
    <div id="dswd-preview-content"></div>
    <footer class="dswd-preview-footer"><a id="dswd-preview-open" target="_blank" rel="noopener" hidden>Open document in a new tab</a></footer>
</dialog>
@push('scripts')
    <script src="{{ asset('js/dswd-documents.js') }}?v={{ filemtime(public_path('js/dswd-documents.js')) }}" defer></script>
@endpush
<style>
.dswd-journey-stages { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:12px; }
.dswd-journey-stages article { display:flex; flex-direction:column; align-items:flex-start; gap:10px; padding:18px 12px; border:1px solid var(--inay-line); border-radius:12px; }
.dswd-journey-stages article.is-complete { background:#f0fdf8; border-color:#b6e8d2; }
.dswd-journey-stages article.is-current { background:var(--inay-soft-pink); border-color:#f3add0; }
.dswd-stage-number { display:grid; place-items:center; width:32px; height:32px; border-radius:50%; background:var(--inay-soft-pink); color:var(--inay-pink); font-weight:700; }
.dswd-journey-stages small { color:var(--inay-muted); }
.dswd-month-grid { display:block; }
.dswd-month { padding:18px; border:1px solid var(--inay-line); border-radius:12px; min-width:0; }
.dswd-month summary { cursor:pointer; }
.dswd-month summary .dswd-badge { display:block; margin-top:10px; }
.dswd-month .dswd-details { gap:12px; }
.dswd-document { display:flex; align-items:center; justify-content:space-between; gap:16px; border-top:1px solid var(--inay-line); padding:16px 0; overflow-wrap:anywhere; }
.dswd-document>div { min-width:0; }
.dswd-document .dswd-button { flex-shrink:0; }
.dswd-month-picker { display:flex; align-items:center; gap:12px; font-weight:600; margin:20px 0; }
.dswd-month-picker select { padding:12px; border:1px solid var(--inay-line); border-radius:10px; background:white; font:inherit; max-width:100%; }
.dswd-document-empty { padding:28px 10px; text-align:center; color:var(--inay-muted); }
#dswd-document-dialog { width:min(1000px,94vw); max-height:92dvh; padding:0; border:1px solid var(--inay-line); border-radius:16px; color:var(--inay-text); }
#dswd-document-dialog::backdrop { background:rgba(17,26,50,.65); }
.dswd-preview-header { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 22px; border-bottom:1px solid var(--inay-line); }
.dswd-preview-header h2 { font-size:18px; margin:0; overflow-wrap:anywhere; min-width:0; }
#dswd-preview-status { padding:16px 22px; }
#dswd-preview-content { background:#f5f7fa; text-align:center; }
#dswd-preview-content iframe { display:block; width:100%; height:65dvh; border:0; }
#dswd-preview-content img { display:block; max-width:100%; max-height:65dvh; object-fit:contain; margin:auto; }
.dswd-preview-footer { padding:14px 22px; font-size:13px; }
body.dswd-preview-is-open { overflow:hidden; }
@media(max-width:620px) { .dswd-document { align-items:flex-start; flex-direction:column; } .dswd-preview-header { padding:14px; } }
@media(max-width:1200px) { .dswd-journey-stages { grid-template-columns:repeat(3,minmax(0,1fr)); } .dswd-month-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media(max-width:620px) { .dswd-journey-stages { grid-template-columns:repeat(2,minmax(0,1fr)); } .dswd-month-grid { grid-template-columns:1fr; } }
</style>
