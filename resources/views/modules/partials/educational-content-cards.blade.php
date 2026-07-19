@php
    $items = collect($items ?? []);
    $heading = $heading ?? 'Published educational content';
@endphp

@if ($items->isNotEmpty())
    <section class="kaalaman-managed-content" aria-label="{{ $heading }}">
        <div class="kaalaman-managed-content-head">
            <h3>{!! $iconVideo !!} {{ $heading }}</h3>
            <span>{{ $items->count() }} published {{ $items->count() === 1 ? 'item' : 'items' }}</span>
        </div>

        <div class="kaalaman-managed-content-grid">
            @foreach ($items as $content)
                <article class="kaalaman-managed-card">
                    <div class="kaalaman-managed-media-stack">
                        @if ($content->youtube_embed_url)
                            <div class="kaalaman-managed-media">
                                <iframe src="{{ $content->youtube_embed_url }}" title="{{ $content->title }} YouTube video" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                            </div>
                        @endif

                        @if ($content->uploaded_video_url)
                            <div class="kaalaman-managed-media">
                                <video controls preload="metadata">
                                    <source src="{{ $content->uploaded_video_url }}" type="{{ $content->uploaded_video_mime_type ?: 'video/mp4' }}">
                                    Your browser does not support this video.
                                </video>
                            </div>
                        @endif

                        @if ($content->infographic_url)
                            <div class="kaalaman-managed-media">
                                <img src="{{ $content->infographic_url }}" alt="{{ $content->title }} infographic">
                            </div>
                        @endif

                        @if (! $content->youtube_embed_url && ! $content->uploaded_video_url && ! $content->infographic_url)
                            <div class="kaalaman-managed-placeholder">{!! $iconFile !!} Text-based lesson</div>
                        @endif
                    </div>

                    <div class="kaalaman-managed-copy">
                        <div class="kaalaman-managed-meta">
                            @if ($content->month)
                                <span>Month {{ $content->month }}</span>
                            @else
                                <span>Full stage</span>
                            @endif
                        </div>
                        <h4>{{ $content->title }}</h4>
                        @if ($content->description)
                            <p>{{ $content->description }}</p>
                        @endif
                        @if ($content->output_description)
                            <p class="kaalaman-managed-output"><strong>Expected output:</strong> {{ $content->output_description }}</p>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif
