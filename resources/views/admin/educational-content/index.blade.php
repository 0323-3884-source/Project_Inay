@extends('layouts.admin')

@section('title', 'Educational Content Management - Project INAY')

@push('styles')
    <style>
        .edu-shell { display: grid; gap: 18px; }
        .edu-topbar { display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; }
        .edu-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .edu-workspace { display: grid; grid-template-columns: minmax(320px, 420px) minmax(0, 1fr); gap: 18px; align-items: start; }
        .edu-panel { background: #ffffff; border: 1px solid var(--admin-line); border-radius: 8px; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.07); overflow: hidden; }
        .edu-panel-head { padding: 18px; border-bottom: 1px solid #edf2f7; }
        .edu-panel-head h2 { margin: 0; font-size: 18px; font-weight: 900; }
        .edu-panel-head p { margin: 7px 0 0; color: var(--admin-muted); font-size: 13px; line-height: 1.45; }
        .edu-form { display: grid; gap: 14px; padding: 18px; }
        .edu-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .edu-field { display: grid; gap: 7px; }
        .edu-field.is-wide { grid-column: 1 / -1; }
        .edu-label { color: #64748b; font-size: 11px; font-weight: 900; text-transform: uppercase; }
        .edu-input, .edu-textarea, .edu-select { width: 100%; border: 1px solid #cbd8ea; border-radius: 8px; background: #ffffff; color: #071127; font-size: 14px; outline: none; }
        .edu-input, .edu-select { min-height: 44px; padding: 0 12px; }
        .edu-input[type=file] { min-height: auto; padding: 11px 12px; }
        .edu-textarea { min-height: 92px; padding: 12px; resize: vertical; }
        .edu-input:focus, .edu-textarea:focus, .edu-select:focus { border-color: var(--admin-green); box-shadow: 0 0 0 3px rgba(0, 133, 106, 0.13); }
        .edu-check { display: flex; align-items: center; gap: 10px; padding: 12px; background: #f8fafc; border: 1px solid #dbe5f1; border-radius: 8px; color: #334155; font-size: 13px; font-weight: 900; }
        .edu-check input { width: 18px; height: 18px; accent-color: var(--admin-green); }
        .edu-button { display: inline-flex; min-height: 42px; align-items: center; justify-content: center; gap: 8px; padding: 0 14px; border: 1px solid var(--admin-green); border-radius: 8px; background: var(--admin-green); color: #ffffff; font-size: 13px; font-weight: 900; text-decoration: none; cursor: pointer; }
        .edu-button:hover { background: var(--admin-green-dark); text-decoration: none; }
        .edu-button.is-light { color: #334155; background: #ffffff; border-color: #cbd8ea; }
        .edu-button.is-light:hover { color: var(--admin-green); background: #ecfdf5; border-color: #c9f2df; }
        .edu-button.is-danger { color: #ffffff; background: #dc174d; border-color: #dc174d; }
        .edu-button.is-danger:hover { background: #b90f3e; }
        .edu-button.is-pink { background: var(--admin-pink); border-color: var(--admin-pink); }
        .edu-button.is-pink:hover { background: #c60075; }
        .edu-preview { display: grid; gap: 10px; padding: 14px; background: #f8fafc; border: 1px dashed #cbd8ea; border-radius: 8px; }
        .edu-preview strong { color: #071127; font-size: 15px; font-weight: 900; }
        .edu-preview p { margin: 0; color: #52627d; font-size: 13px; line-height: 1.45; }
        .edu-preview-media { display: grid; place-items: center; min-height: 132px; overflow: hidden; background: #071127; border-radius: 8px; color: #ffffff; font-size: 12px; font-weight: 900; text-align: center; }
        .edu-preview-media iframe, .edu-preview-media video, .edu-preview-media img { display: block; width: 100%; max-width: 100%; border: 0; border-radius: 8px; aspect-ratio: 16 / 9; object-fit: cover; }
        .edu-list { display: grid; gap: 14px; }
        .edu-content-card { display: grid; gap: 14px; padding: 18px; background: #ffffff; border: 1px solid var(--admin-line); border-radius: 8px; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06); }
        .edu-card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
        .edu-card-head h2 { margin: 0; color: #071127; font-size: 18px; font-weight: 900; line-height: 1.2; }
        .edu-meta { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 9px; }
        .edu-badge { display: inline-flex; min-height: 26px; align-items: center; padding: 0 9px; border: 1px solid #dbe5f1; border-radius: 999px; background: #f8fafc; color: #52627d; font-size: 11px; font-weight: 900; }
        .edu-badge.is-live { color: #007f5f; background: #ecfdf5; border-color: #86efc2; }
        .edu-badge.is-draft { color: #9f1239; background: #fff1f2; border-color: #fecdd3; }
        .edu-card-body { display: grid; grid-template-columns: minmax(0, 1fr) minmax(280px, 360px); gap: 16px; align-items: start; }
        .edu-copy { display: grid; gap: 8px; color: #334155; font-size: 13px; line-height: 1.45; }
        .edu-copy p { margin: 0; }
        .edu-media-stack { display: grid; gap: 10px; }
        .edu-media { overflow: hidden; background: #071127; border: 1px solid #1d2a44; border-radius: 8px; }
        .edu-media iframe, .edu-media video, .edu-media img { display: block; width: 100%; max-width: 100%; border: 0; aspect-ratio: 16 / 9; object-fit: cover; }
        .edu-edit { border-top: 1px solid #edf2f7; padding-top: 14px; }
        .edu-card-actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .edu-card-actions form { margin: 0; }
        .edu-empty { display: grid; min-height: 220px; place-items: center; padding: 28px; border: 1px dashed #cbd8ea; border-radius: 8px; background: #ffffff; color: #64748b; text-align: center; font-weight: 800; }
        .edu-error { margin-top: 4px; color: #be123c; font-size: 12px; font-weight: 800; }
        @media (max-width: 1180px) { .edu-workspace, .edu-card-body { grid-template-columns: 1fr; } }
        @media (max-width: 760px) { .edu-topbar { flex-direction: column; } .edu-grid { grid-template-columns: 1fr; } .edu-content-card, .edu-panel-head, .edu-form { padding: 14px; } .edu-button { width: 100%; } }
    </style>
@endpush

@section('content')
    <section class="edu-shell">
        <div class="edu-topbar">
            <div>
                <p class="admin-kicker">Admin / Educational Content</p>
                <h1 class="admin-page-title">Educational Content Management</h1>
                <p class="admin-page-copy">Create learning videos and infographics for the Pregnancy to Newborn Care Path. Mothers only see content after it is published.</p>
            </div>
            <div class="edu-actions" aria-label="Content summary">
                <span class="edu-badge is-live">{{ $contents->where('is_published', true)->count() }} Published</span>
                <span class="edu-badge is-draft">{{ $contents->where('is_published', false)->count() }} Drafts</span>
            </div>
        </div>

        @if (session('status'))
            <div class="admin-alert is-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="admin-alert is-error">
                Please review the highlighted fields. Uploaded videos can be MP4, MOV, WEBM, or OGG up to 50 MB. Infographics can be JPG, PNG, or WEBP up to 5 MB.
            </div>
        @endif

        <div class="edu-workspace">
            <section class="edu-panel" aria-labelledby="create-content-title">
                <div class="edu-panel-head">
                    <h2 id="create-content-title">Create Content</h2>
                    <p>Save as a draft first when you want to preview the lesson before publishing it to mothers.</p>
                </div>
                <form class="edu-form" method="POST" action="{{ route('admin.educational-content.store') }}" enctype="multipart/form-data" data-edu-preview-form>
                    @csrf
                    <div class="edu-grid">
                        <label class="edu-field">
                            <span class="edu-label">Care Stage</span>
                            <select class="edu-select" name="stage_key" data-preview-stage required>
                                @foreach ($stageOptions as $stageKey => $stageLabel)
                                    <option value="{{ $stageKey }}" @selected(old('stage_key') === $stageKey)>{{ $stageLabel }}</option>
                                @endforeach
                            </select>
                            @error('stage_key') <span class="edu-error">{{ $message }}</span> @enderror
                        </label>

                        <label class="edu-field">
                            <span class="edu-label">Month</span>
                            <select class="edu-select" name="month" data-preview-month>
                                <option value="">Whole stage</option>
                                @foreach ($monthOptions as $month)
                                    <option value="{{ $month }}" @selected((string) old('month') === (string) $month)>Month {{ $month }}</option>
                                @endforeach
                            </select>
                            @error('month') <span class="edu-error">{{ $message }}</span> @enderror
                        </label>

                        <label class="edu-field is-wide">
                            <span class="edu-label">Title</span>
                            <input class="edu-input" type="text" name="title" value="{{ old('title') }}" maxlength="180" data-preview-title required>
                            @error('title') <span class="edu-error">{{ $message }}</span> @enderror
                        </label>

                        <label class="edu-field is-wide">
                            <span class="edu-label">Infographic Category</span>
                            <input class="edu-input" name="category" value="{{ old('category') }}" maxlength="100" placeholder="e.g. Nutrition, Maternal Care, Vaccination">
                            @error('category') <span class="edu-error">{{ $message }}</span> @enderror
                        </label>
                        <label class="edu-field is-wide">
                            <span class="edu-label">Health Campaign Month (optional)</span>
                            <select class="edu-select" name="calendar_month">
                                <option value="">Any time of year</option>
                                @foreach(range(1, 12) as $calendarMonth)
                                    <option value="{{ $calendarMonth }}" @selected((int) old('calendar_month') === $calendarMonth)>{{ \Carbon\Carbon::createFromDate(2000, $calendarMonth, 1)->format('F') }}</option>
                                @endforeach
                            </select>
                            @error('calendar_month') <span class="edu-error">{{ $message }}</span> @enderror
                        </label>
                        <label class="edu-field is-wide">
                            <span class="edu-label">Description</span>
                            <textarea class="edu-textarea" name="description" data-preview-description>{{ old('description') }}</textarea>
                            @error('description') <span class="edu-error">{{ $message }}</span> @enderror
                        </label>

                        <label class="edu-field is-wide">
                            <span class="edu-label">Output Information</span>
                            <textarea class="edu-textarea" name="output_description" data-preview-output>{{ old('output_description') }}</textarea>
                            @error('output_description') <span class="edu-error">{{ $message }}</span> @enderror
                        </label>

                        <label class="edu-field">
                            <span class="edu-label">Display Order</span>
                            <input class="edu-input" type="number" name="display_order" min="0" max="9999" value="{{ old('display_order', 0) }}" required>
                            @error('display_order') <span class="edu-error">{{ $message }}</span> @enderror
                        </label>

                        <label class="edu-field">
                            <span class="edu-label">YouTube Link</span>
                            <input class="edu-input" type="url" name="youtube_url" value="{{ old('youtube_url') }}" placeholder="https://www.youtube.com/watch?v=..." data-preview-youtube>
                            @error('youtube_url') <span class="edu-error">{{ $message }}</span> @enderror
                        </label>

                        <label class="edu-field">
                            <span class="edu-label">Upload Video</span>
                            <input class="edu-input" type="file" name="video_file" accept=".mp4,.mov,.webm,.ogg,video/mp4,video/quicktime,video/webm,video/ogg" data-preview-video-file>
                            @error('video_file') <span class="edu-error">{{ $message }}</span> @enderror
                        </label>

                        <label class="edu-field">
                            <span class="edu-label">Upload Infographic</span>
                            <input class="edu-input" type="file" name="infographic_file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-preview-image-file>
                            @error('infographic_file') <span class="edu-error">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <label class="edu-check">
                        <input type="checkbox" name="is_published" value="1" @checked(old('is_published'))>
                        Publish immediately
                    </label>

                    <section class="edu-preview" aria-label="Draft preview">
                        <span class="edu-badge">Preview</span>
                        <div class="edu-preview-media" data-preview-media>No video or infographic selected</div>
                        <strong data-preview-title-output>Draft title</strong>
                        <p data-preview-meta-output>1st Trimester - Whole stage</p>
                        <p data-preview-description-output>Description will appear here.</p>
                        <p data-preview-output-output>Output information will appear here.</p>
                    </section>

                    <button class="edu-button" type="submit">Save Educational Content</button>
                </form>
            </section>

            <section class="edu-list" aria-label="Saved educational content">
                @forelse ($contents as $content)
                    <article class="edu-content-card">
                        <div class="edu-card-head">
                            <div>
                                <h2>{{ $content->title }}</h2>
                                <div class="edu-meta">
                                    <span class="edu-badge">{{ $stageOptions[$content->stage_key] ?? $content->stage_key }}</span>
                                    <span class="edu-badge">{{ $content->month ? 'Month '.$content->month : 'Whole stage' }}</span>
                                    <span class="edu-badge">Order {{ $content->display_order }}</span>
                                    <span class="edu-badge {{ $content->is_published ? 'is-live' : 'is-draft' }}">{{ $content->is_published ? 'Published' : 'Draft' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="edu-card-body">
                            <div class="edu-copy">
                                @if ($content->description)
                                    <p>{{ $content->description }}</p>
                                @endif
                                @if ($content->output_description)
                                    <p><strong>Output:</strong> {{ $content->output_description }}</p>
                                @endif
                                @if (! $content->description && ! $content->output_description)
                                    <p>No description has been added yet.</p>
                                @endif
                            </div>

                            <div class="edu-media-stack" aria-label="Content preview">
                                @if ($content->youtube_embed_url)
                                    <div class="edu-media">
                                        <iframe src="{{ $content->youtube_embed_url }}" title="{{ $content->title }} YouTube preview" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                                    </div>
                                @endif

                                @if ($content->uploaded_video_url)
                                    <div class="edu-media">
                                        <video controls preload="metadata">
                                            <source src="{{ $content->uploaded_video_url }}" type="{{ $content->uploaded_video_mime_type ?: 'video/mp4' }}">
                                            Your browser does not support the video tag.
                                        </video>
                                    </div>
                                @endif

                                @if ($content->infographic_url)
                                    <div class="edu-media">
                                        <img src="{{ $content->infographic_url }}" alt="{{ $content->title }} infographic">
                                    </div>
                                @endif

                                @if (! $content->youtube_embed_url && ! $content->uploaded_video_url && ! $content->infographic_url)
                                    <div class="edu-preview-media">Text-only draft preview</div>
                                @endif
                            </div>
                        </div>

                        <details class="edu-edit">
                            <summary class="edu-button is-light">Edit Content</summary>
                            <form class="edu-form" method="POST" action="{{ route('admin.educational-content.update', $content) }}" enctype="multipart/form-data">
                                @csrf
                                @method('PATCH')
                                <div class="edu-grid">
                                    <label class="edu-field">
                                        <span class="edu-label">Care Stage</span>
                                        <select class="edu-select" name="stage_key" required>
                                            @foreach ($stageOptions as $stageKey => $stageLabel)
                                                <option value="{{ $stageKey }}" @selected($content->stage_key === $stageKey)>{{ $stageLabel }}</option>
                                            @endforeach
                                        </select>
                                    </label>

                                    <label class="edu-field">
                                        <span class="edu-label">Month</span>
                                        <select class="edu-select" name="month">
                                            <option value="">Whole stage</option>
                                            @foreach ($monthOptions as $month)
                                                <option value="{{ $month }}" @selected((int) $content->month === $month)>Month {{ $month }}</option>
                                            @endforeach
                                        </select>
                                    </label>

                                    <label class="edu-field is-wide">
                                        <span class="edu-label">Title</span>
                                        <input class="edu-input" type="text" name="title" value="{{ $content->title }}" maxlength="180" required>
                                    </label>

                                    <label class="edu-field is-wide">
                                        <span class="edu-label">Infographic Category</span>
                                        <input class="edu-input" name="category" value="{{ $content->category }}" maxlength="100">
                                    </label>
                                    <label class="edu-field is-wide">
                                        <span class="edu-label">Health Campaign Month (optional)</span>
                                        <select class="edu-select" name="calendar_month">
                                            <option value="">Any time of year</option>
                                            @foreach(range(1, 12) as $calendarMonth)
                                                <option value="{{ $calendarMonth }}" @selected($content->calendar_month === $calendarMonth)>{{ \Carbon\Carbon::createFromDate(2000, $calendarMonth, 1)->format('F') }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label class="edu-field is-wide">
                                        <span class="edu-label">Description</span>
                                        <textarea class="edu-textarea" name="description">{{ $content->description }}</textarea>
                                    </label>

                                    <label class="edu-field is-wide">
                                        <span class="edu-label">Output Information</span>
                                        <textarea class="edu-textarea" name="output_description">{{ $content->output_description }}</textarea>
                                    </label>

                                    <label class="edu-field">
                                        <span class="edu-label">Display Order</span>
                                        <input class="edu-input" type="number" name="display_order" min="0" max="9999" value="{{ $content->display_order }}" required>
                                    </label>

                                    <label class="edu-field">
                                        <span class="edu-label">YouTube Link</span>
                                        <input class="edu-input" type="url" name="youtube_url" value="{{ $content->youtube_url }}" placeholder="https://www.youtube.com/watch?v=...">
                                    </label>

                                    <label class="edu-field">
                                        <span class="edu-label">Replace Video</span>
                                        <input class="edu-input" type="file" name="video_file" accept=".mp4,.mov,.webm,.ogg,video/mp4,video/quicktime,video/webm,video/ogg">
                                    </label>

                                    <label class="edu-field">
                                        <span class="edu-label">Replace Infographic</span>
                                        <input class="edu-input" type="file" name="infographic_file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                    </label>
                                </div>

                                <label class="edu-check">
                                    <input type="checkbox" name="is_published" value="1" @checked($content->is_published)>
                                    Published on Mother portal
                                </label>

                                <button class="edu-button" type="submit">Save Changes</button>
                            </form>
                        </details>

                        <div class="edu-card-actions">
                            @if ($content->is_published)
                                <form method="POST" action="{{ route('admin.educational-content.unpublish', $content) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="edu-button is-light" type="submit">Unpublish</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.educational-content.publish', $content) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="edu-button is-pink" type="submit">Publish</button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('admin.educational-content.destroy', $content) }}" onsubmit="return confirm('Delete this educational content permanently?');">
                                @csrf
                                @method('DELETE')
                                <button class="edu-button is-danger" type="submit">Delete</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="edu-empty">
                        <div>
                            <strong>No educational content yet.</strong>
                            <p>Create a draft, preview it, then publish when it is ready for mothers.</p>
                        </div>
                    </div>
                @endforelse
            </section>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        const youtubeIdFromUrl = (value) => {
            try {
                const url = new URL(value);
                const host = url.hostname.replace(/^www\./, '').toLowerCase();

                if (host === 'youtu.be') return url.pathname.split('/').filter(Boolean)[0] || '';
                if (!['youtube.com', 'm.youtube.com', 'music.youtube.com'].includes(host)) return '';

                const watchId = url.searchParams.get('v');
                if (watchId) return watchId;

                const parts = url.pathname.split('/').filter(Boolean);
                if (['embed', 'shorts', 'live'].includes(parts[0])) return parts[1] || '';
            } catch (error) {
                return '';
            }

            return '';
        };

        document.querySelectorAll('[data-edu-preview-form]').forEach((form) => {
            const title = form.querySelector('[data-preview-title]');
            const stage = form.querySelector('[data-preview-stage]');
            const month = form.querySelector('[data-preview-month]');
            const description = form.querySelector('[data-preview-description]');
            const output = form.querySelector('[data-preview-output]');
            const youtube = form.querySelector('[data-preview-youtube]');
            const video = form.querySelector('[data-preview-video-file]');
            const image = form.querySelector('[data-preview-image-file]');
            const mediaTarget = form.querySelector('[data-preview-media]');
            const titleTarget = form.querySelector('[data-preview-title-output]');
            const metaTarget = form.querySelector('[data-preview-meta-output]');
            const descriptionTarget = form.querySelector('[data-preview-description-output]');
            const outputTarget = form.querySelector('[data-preview-output-output]');

            const refreshPreview = () => {
                titleTarget.textContent = title.value.trim() || 'Draft title';
                metaTarget.textContent = `${stage.options[stage.selectedIndex]?.text || 'Care stage'} - ${month.value ? `Month ${month.value}` : 'Whole stage'}`;
                descriptionTarget.textContent = description.value.trim() || 'Description will appear here.';
                outputTarget.textContent = output.value.trim() || 'Output information will appear here.';

                const imageFile = image.files?.[0] || null;
                const videoFile = video.files?.[0] || null;
                const youtubeId = youtubeIdFromUrl(youtube.value.trim());

                if (imageFile) {
                    mediaTarget.innerHTML = '';
                    const previewImage = document.createElement('img');
                    previewImage.alt = imageFile.name;
                    previewImage.src = URL.createObjectURL(imageFile);
                    mediaTarget.appendChild(previewImage);
                    return;
                }

                if (videoFile) {
                    mediaTarget.innerHTML = '';
                    const previewVideo = document.createElement('video');
                    previewVideo.controls = true;
                    previewVideo.preload = 'metadata';
                    previewVideo.src = URL.createObjectURL(videoFile);
                    mediaTarget.appendChild(previewVideo);
                    return;
                }

                if (youtubeId) {
                    mediaTarget.innerHTML = '';
                    const frame = document.createElement('iframe');
                    frame.src = `https://www.youtube.com/embed/${youtubeId}`;
                    frame.title = 'YouTube preview';
                    frame.loading = 'lazy';
                    frame.allowFullscreen = true;
                    frame.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
                    mediaTarget.appendChild(frame);
                    return;
                }

                mediaTarget.textContent = 'No video or infographic selected';
            };

            form.addEventListener('input', refreshPreview);
            form.addEventListener('change', refreshPreview);
            refreshPreview();
        });
    </script>
@endpush
