@extends('layouts.app')
@section('title', 'Documents | Project INAY')
@section('portal_title', 'My Documents')
@section('content')
<link rel="stylesheet" href="{{ asset('css/documents.css') }}?v={{ filemtime(public_path('css/documents.css')) }}">
<section class="documents-page">
    <header class="documents-heading">
        <div><p class="document-eyebrow">MOTHER CARE</p><h1>My Documents</h1><p>Keep your checkup records, prescriptions, and receipts together.</p></div>
        <button type="button" class="document-primary" data-upload-open aria-haspopup="dialog" aria-controls="document-upload-dialog">+ Upload document</button>
    </header>
    <p class="document-info">Upload once, find it here and in <strong>INAY Kaalaman</strong>. Your program staff can view the same files in both document tabs of your casefile.</p>
    @include('partials.document-library', ['documentAudience' => 'mother'])
</section>
<dialog id="document-upload-dialog" class="document-dialog" aria-labelledby="document-upload-title" data-has-errors="{{ $errors->any() ? 'true' : 'false' }}">
    <header><h2 id="document-upload-title">Upload a document</h2><button type="button" data-upload-close aria-label="Close upload dialog">&times;</button></header>
    <p>Add a clear photo or file of your record or receipt. Choose the month it belongs to so your program staff can find it easily.</p>
    @if ($errors->any())<div class="document-errors" role="alert"><strong>Please check your upload.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('mother.documents.store') }}" enctype="multipart/form-data" data-document-upload-form>
        @csrf
        <label>Care month<select name="month" required><option value="">Choose a month</option>@foreach (range(1, 10) as $month)<option value="{{ $month }}" @selected((string) old('month') === (string) $month)>{{ $month === 10 ? 'Delivery / newborn care (Month 10)' : 'Month '.$month }}</option>@endforeach</select></label>
        <label>Document type<select name="record_type" required><option value="">Choose a type</option>@foreach (['Prenatal Records and Receipts', 'Checkup Records', 'Prescription', 'Receipts', 'Certificate', 'Other Documents'] as $type)<option value="{{ $type }}" @selected(old('record_type') === $type)>{{ $type }}</option>@endforeach</select></label>
        <label>Choose a photo or document<input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required aria-describedby="document-file-help"></label>
        <p id="document-file-help">PDF, JPG, PNG, or Word · Maximum 5 MB per file. Check that names, dates, and amounts are readable.</p>
        <p data-upload-status role="status"></p>
        <div class="document-actions"><button class="document-primary" type="submit">Upload and share with staff</button><button type="button" data-upload-close>Cancel</button></div>
    </form>
</dialog>
<dialog id="document-preview-dialog" class="document-dialog document-preview-dialog" aria-labelledby="document-preview-title">
    <header><h2 id="document-preview-title">Document details</h2><button type="button" data-document-close aria-label="Close document preview">&times;</button></header>
    <p data-document-details></p><p data-document-status role="status"></p>
    <div class="document-preview-content" data-document-content></div>
    <p><a data-document-download>Download file</a></p>
</dialog>
<script src="{{ asset('js/document-library.js') }}?v={{ filemtime(public_path('js/document-library.js')) }}" defer></script>
<script src="{{ asset('js/document-preview.js') }}?v={{ filemtime(public_path('js/document-preview.js')) }}" defer></script>
@endsection
