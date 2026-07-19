<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EducationalContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EducationalContentController extends Controller
{
    private const STAGES = [
        'first-trimester' => '1st Trimester',
        'second-trimester' => '2nd Trimester',
        'third-trimester' => '3rd Trimester',
        'labor-delivery' => 'Labor & Delivery',
        'postpartum-care' => 'Postpartum Care',
        'neonatal-care' => 'Neonatal Care',
    ];

    private const STAGE_MONTHS = [
        'first-trimester' => [1, 2, 3],
        'second-trimester' => [4, 5, 6],
        'third-trimester' => [7, 8, 9],
        'labor-delivery' => [10],
        'postpartum-care' => [],
        'neonatal-care' => [],
    ];

    public function index(): View
    {
        $contents = EducationalContent::query()
            ->orderBy('stage_key')
            ->orderBy('month')
            ->orderBy('display_order')
            ->orderBy('title')
            ->get();

        return view('admin.educational-content.index', [
            'contents' => $contents,
            'stageOptions' => self::STAGES,
            'monthOptions' => range(1, 10),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedContent($request);
        $content = new EducationalContent($this->contentPayload($validated, $request));
        $content->created_by_admin_id = $this->adminId($request);
        $content->updated_by_admin_id = $this->adminId($request);
        $content->save();

        return redirect()
            ->route('admin.educational-content.index')
            ->with('status', $content->is_published ? 'Educational content published.' : 'Educational content saved as draft.');
    }

    public function update(Request $request, EducationalContent $educationalContent): RedirectResponse
    {
        $validated = $this->validatedContent($request);
        $payload = $this->contentPayload($validated, $request, $educationalContent);

        $educationalContent->fill($payload);
        $educationalContent->updated_by_admin_id = $this->adminId($request);
        $educationalContent->save();

        return redirect()
            ->route('admin.educational-content.index')
            ->with('status', 'Educational content updated.');
    }

    public function publish(Request $request, EducationalContent $educationalContent): RedirectResponse
    {
        $educationalContent->forceFill([
            'is_published' => true,
            'published_at' => $educationalContent->published_at ?? now(),
            'updated_by_admin_id' => $this->adminId($request),
        ])->save();

        return redirect()
            ->route('admin.educational-content.index')
            ->with('status', 'Educational content published.');
    }

    public function unpublish(Request $request, EducationalContent $educationalContent): RedirectResponse
    {
        $educationalContent->forceFill([
            'is_published' => false,
            'published_at' => null,
            'updated_by_admin_id' => $this->adminId($request),
        ])->save();

        return redirect()
            ->route('admin.educational-content.index')
            ->with('status', 'Educational content unpublished.');
    }

    public function destroy(EducationalContent $educationalContent): RedirectResponse
    {
        $this->deleteMediaFiles($educationalContent);
        $educationalContent->delete();

        return redirect()
            ->route('admin.educational-content.index')
            ->with('status', 'Educational content deleted.');
    }

    private function validatedContent(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'stage_key' => ['required', Rule::in(array_keys(self::STAGES))],
            'month' => ['nullable', 'integer', 'between:1,10'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2500'],
            'output_description' => ['nullable', 'string', 'max:2500'],
            'display_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'youtube_url' => ['nullable', 'url', 'max:255'],
            'video_file' => ['nullable', 'file', 'mimes:mp4,mov,webm,ogg', 'max:51200'],
            'infographic_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($request->filled('youtube_url') && ! EducationalContent::youtubeVideoId($request->input('youtube_url'))) {
                $validator->errors()->add('youtube_url', 'Enter a valid YouTube video link.');
            }

            $stageKey = (string) $request->input('stage_key');
            $month = $request->input('month');

            if ($stageKey !== '' && $month !== null && $month !== '') {
                $allowedMonths = self::STAGE_MONTHS[$stageKey] ?? [];

                if (! in_array((int) $month, $allowedMonths, true)) {
                    $validator->errors()->add('month', 'Choose a month that belongs to the selected care stage, or leave it as a whole-stage lesson.');
                }
            }
        });

        return $validator->validate();
    }

    private function contentPayload(array $validated, Request $request, ?EducationalContent $content = null): array
    {
        $published = $request->boolean('is_published');

        $payload = [
            'stage_key' => $validated['stage_key'],
            'month' => $validated['month'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'output_description' => $validated['output_description'] ?? null,
            'display_order' => $validated['display_order'],
            'youtube_url' => $validated['youtube_url'] ?? null,
            'is_published' => $published,
            'published_at' => $published ? ($content?->published_at ?? now()) : null,
        ];

        if ($request->hasFile('video_file')) {
            if ($content?->uploaded_video_path) {
                Storage::disk('public')->delete($content->uploaded_video_path);
            }

            $video = $request->file('video_file');
            $payload['uploaded_video_path'] = $this->storeUploadedFile($video, 'educational-content/videos');
            $payload['uploaded_video_original_name'] = $video->getClientOriginalName();
            $payload['uploaded_video_mime_type'] = $video->getClientMimeType();
            $payload['uploaded_video_size'] = $video->getSize() ?: 0;
        }

        if ($request->hasFile('infographic_file')) {
            if ($content?->infographic_path) {
                Storage::disk('public')->delete($content->infographic_path);
            }

            $infographic = $request->file('infographic_file');
            $payload['infographic_path'] = $this->storeUploadedFile($infographic, 'educational-content/infographics');
            $payload['infographic_original_name'] = $infographic->getClientOriginalName();
            $payload['infographic_mime_type'] = $infographic->getClientMimeType();
            $payload['infographic_size'] = $infographic->getSize() ?: 0;
        }

        return $payload;
    }

    private function storeUploadedFile(UploadedFile $file, string $directory): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?: 'bin';

        return $file->storeAs($directory, Str::uuid().'.'.$extension, 'public');
    }

    private function deleteMediaFiles(EducationalContent $content): void
    {
        Storage::disk('public')->delete(array_filter([
            $content->uploaded_video_path,
            $content->infographic_path,
        ]));
    }

    private function adminId(Request $request): ?int
    {
        $adminId = (int) $request->session()->get('admin_id');

        return $adminId > 0 ? $adminId : null;
    }
}
