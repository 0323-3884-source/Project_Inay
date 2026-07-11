@extends('layouts.app')

@section('title', $monthData['title'].' Videos - Project INAY')
@section('portal_title', 'INAY Kaalaman')

@php
    $iconArrow = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>';
    $iconBook = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/></svg>';
    $iconVideo = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m10 8 6 4-6 4Z"/><rect x="3" y="5" width="18" height="14" rx="2"/></svg>';
    $iconClock = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>';
@endphp

@section('content')
    <section class="kaalaman-library-shell" aria-label="{{ $monthData['title'] }} video library">
        <a class="kaalaman-library-back" href="{{ route('inay-kaalaman') }}">{!! $iconArrow !!} Back to INAY Kaalaman</a>

        <header class="kaalaman-library-heading">
            <span class="kaalaman-library-icon">{!! $iconBook !!}</span>
            <div>
                <p class="kaalaman-library-kicker">{{ $monthData['kicker'] }}</p>
                <h1>{{ $monthData['title'] }}</h1>
                <p>{{ $monthData['weeks'] }} educational guides and previous video versions.</p>
            </div>
        </header>

        <section class="kaalaman-library-section">
            <h2 class="kaalaman-section-title">{!! $iconVideo !!} Current Educational Videos</h2>
            <div class="kaalaman-library-grid">
                @foreach ($monthData['videos'] as $video)
                    <article class="kaalaman-video-card kaalaman-library-card is-clickable" role="button" tabindex="0" data-video-card data-video-title="{{ $video['title'] }}" data-video-meta="{{ $video['tag'] }} - {{ $video['time'] }}" data-video-url="{{ $video['url'] }}">
                        <div class="kaalaman-video-top">
                            <span class="kaalaman-video-play">{!! $iconVideo !!}</span>
                            <button class="kaalaman-video-action" type="button" data-toggle-done="Watched">Mark as Watched</button>
                        </div>
                        <h3>{{ $video['title'] }}</h3>
                        <div class="kaalaman-video-meta"><strong>{{ $video['tag'] }}</strong><span>{!! $iconClock !!} {{ $video['time'] }}</span></div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="kaalaman-library-section">
            <h2 class="kaalaman-section-title">{!! $iconVideo !!} Previous Uploaded Videos</h2>
            <div class="kaalaman-library-grid">
                @foreach ($monthData['archive'] as $video)
                    <article class="kaalaman-video-card kaalaman-library-card kaalaman-archive-card is-clickable" role="button" tabindex="0" data-video-card data-video-title="{{ $video['title'] }}" data-video-meta="{{ $video['tag'] }} - {{ $video['time'] }}" data-video-url="{{ $video['url'] }}">
                        <div class="kaalaman-video-top">
                            <span class="kaalaman-video-play">{!! $iconVideo !!}</span>
                            <button class="kaalaman-video-action" type="button" data-toggle-done="Watched">Mark as Watched</button>
                        </div>
                        <h3>{{ $video['title'] }}</h3>
                        <div class="kaalaman-video-meta"><strong>{{ $video['tag'] }}</strong><span>{!! $iconClock !!} {{ $video['time'] }}</span></div>
                    </article>
                @endforeach
            </div>
        </section>
    </section>

    <div class="kaalaman-video-modal" data-video-modal hidden>
        <div class="kaalaman-video-backdrop" data-video-close></div>
        <section class="kaalaman-video-dialog" role="dialog" aria-modal="true" aria-labelledby="kaalaman-video-title">
            <button class="kaalaman-video-close" type="button" aria-label="Close video" data-video-close>&times;</button>
            <span class="kaalaman-video-play is-large">{!! $iconVideo !!}</span>
            <p class="kaalaman-eyebrow">Educational Video</p>
            <h2 id="kaalaman-video-title" data-video-modal-title>Video</h2>
            <p data-video-modal-meta></p>
            <p class="kaalaman-video-modal-copy">Open the selected educational video on YouTube. Uploaded video previews can use this same popup once a direct video file or embed link is available.</p>
            <div class="kaalaman-modal-actions">
                <a class="kaalaman-button" href="#" target="_blank" rel="noopener" data-video-modal-link>Open YouTube</a>
                <button class="kaalaman-button secondary" type="button" data-video-close>Close</button>
            </div>
        </section>
    </div>

    <script>
        document.querySelectorAll('[data-toggle-done]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.stopPropagation();
                button.classList.toggle('is-done');
                button.textContent = button.classList.contains('is-done') ? button.dataset.toggleDone : 'Mark as Watched';
            });
        });

        const videoModal = document.querySelector('[data-video-modal]');
        const videoModalTitle = document.querySelector('[data-video-modal-title]');
        const videoModalMeta = document.querySelector('[data-video-modal-meta]');
        const videoModalLink = document.querySelector('[data-video-modal-link]');

        const openVideoModal = (card) => {
            if (!videoModal || !videoModalTitle || !videoModalMeta || !videoModalLink) return;

            videoModalTitle.textContent = card.dataset.videoTitle || 'Educational video';
            videoModalMeta.textContent = card.dataset.videoMeta || '';
            videoModalLink.href = card.dataset.videoUrl || '#';
            videoModal.hidden = false;
            document.body.classList.add('has-kaalaman-modal');
            videoModalLink.focus();
        };

        const closeVideoModal = () => {
            if (!videoModal) return;

            videoModal.hidden = true;
            document.body.classList.remove('has-kaalaman-modal');
        };

        document.querySelectorAll('[data-video-card]').forEach((card) => {
            card.addEventListener('click', (event) => {
                if (event.target.closest('[data-toggle-done]')) return;
                openVideoModal(card);
            });

            card.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter' && event.key !== ' ') return;
                event.preventDefault();
                openVideoModal(card);
            });
        });

        document.querySelectorAll('[data-video-close]').forEach((button) => {
            button.addEventListener('click', closeVideoModal);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeVideoModal();
        });
    </script>
@endsection
