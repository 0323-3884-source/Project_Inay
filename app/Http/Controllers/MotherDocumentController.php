<?php

namespace App\Http\Controllers;

use App\Models\InayKaalamanUpload;
use App\Models\Mother;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MotherDocumentController extends Controller
{
    private function mother(Request $request): Mother
    {
        abort_unless($request->session()->get('auth_role') === 'mother', 403);

        return Mother::findOrFail($request->session()->get('auth_id'));
    }

    public function index(Request $request)
    {
        $mother = $this->mother($request);

        return view('modules.mother-documents', [
            'mother' => $mother,
            'uploads' => InayKaalamanUpload::where('mother_id', $mother->id)->latest()->orderByDesc('id')->get(),
        ]);
    }

    public function file(Request $request, InayKaalamanUpload $upload)
    {
        abort_unless((int) $upload->mother_id === (int) $this->mother($request)->id, 403);
        $disk = Storage::disk('public');
        abort_unless($disk->exists($upload->path), 404);
        if ($request->routeIs('mother.documents.download')) {
            return $disk->download($upload->path, basename(str_replace('\\', '/', $upload->original_name)), ['Cache-Control' => 'private, no-store']);
        }
        $mime = $disk->mimeType($upload->path);
        abort_unless(in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'], true), 415);

        return $disk->response($upload->path, 'document-preview', [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }
}
