<section class="document-library" data-document-library aria-label="Uploaded documents">
    <div class="document-tools">
        <label>Find a document<input type="search" data-document-search placeholder="Search filename or document type"></label>
        <label>Month<select data-document-month><option value="">All months</option>@foreach (range(1, 10) as $month)<option value="{{ $month }}">{{ $month === 10 ? 'Delivery / newborn care' : 'Month '.$month }}</option>@endforeach</select></label>
        <label>Document type<select data-document-type><option value="">All types</option>@foreach ($uploads->pluck('record_type')->unique()->sort() as $type)<option value="{{ $type }}">{{ $type }}</option>@endforeach</select></label>
    </div>
    <p class="document-count" data-document-count role="status">{{ $uploads->count() }} document{{ $uploads->count() === 1 ? '' : 's' }} · Newest first</p>
    <div class="document-grid">
        @foreach ($uploads->sortByDesc('created_at') as $upload)
            @php
                $preview = $documentAudience === 'mother' ? route('mother.documents.preview', $upload) : route('staff.mothers.kaalaman-uploads.preview', [$mother, $upload]);
                $download = $documentAudience === 'mother' ? route('mother.documents.download', $upload) : route('staff.mothers.kaalaman-uploads.download', [$mother, $upload]);
                $details = $upload->record_type.' · Month '.$upload->month.' · '.$upload->created_at->format('M j, Y, g:i A').' · '.number_format($upload->size / 1024).' KB';
            @endphp
            <article class="document-item" data-document-item data-search="{{ $upload->original_name.' '.$upload->record_type }}" data-month="{{ $upload->month }}" data-type="{{ $upload->record_type }}">
                <span class="document-format">{{ strtoupper(pathinfo($upload->original_name, PATHINFO_EXTENSION)) ?: 'FILE' }}</span>
                <h3>{{ $upload->original_name }}</h3>
                <p>{{ $upload->record_type }}</p>
                <p class="document-meta">Month {{ $upload->month }} · {{ $upload->created_at->format('M j, Y') }} · {{ number_format($upload->size / 1024) }} KB</p>
                <div class="document-actions">
                    <button type="button" data-document-card data-name="{{ $upload->original_name }}" data-details="{{ $details }}" data-preview="{{ $preview }}" data-download="{{ $download }}" aria-haspopup="dialog" aria-controls="document-preview-dialog">View document</button>
                    <a href="{{ $download }}">Download</a>
                    @if ($documentAudience === 'mother')
                        <form method="POST" action="{{ route('mother.documents.destroy', $upload) }}" onsubmit="return confirm('Remove this document? It will also disappear from INAY Kaalaman and your program staff’s document list.');">
                            @csrf @method('DELETE')
                            <button class="document-remove" type="submit" aria-label="Remove {{ $upload->original_name }}">Remove</button>
                        </form>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
    <div class="document-empty" data-document-empty @if ($uploads->isNotEmpty()) hidden @endif>
        <h3>{{ $uploads->isEmpty() ? 'No documents yet' : 'No matching documents' }}</h3>
        <p>{{ $uploads->isEmpty() ? 'Records and receipts uploaded here or in INAY Kaalaman will appear in this list.' : 'Try a different search, month, or document type.' }}</p>
    </div>
</section>
