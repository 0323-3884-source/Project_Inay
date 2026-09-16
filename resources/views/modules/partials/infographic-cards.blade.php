@if($infographics->isNotEmpty())
    <section class="inay-infographic-library" aria-label="Mga Infographic">
        <h3 class="kaalaman-task-title">{!! $iconBook !!} <span>MGA INFOGRAPHIC</span></h3>
        <p class="inay-library-intro">Basahin sa iyong sariling bilis. Awtomatikong mase-save ang iyong progreso.</p>
        <div class="inay-infographic-grid">
            @foreach($infographics as $infographic)
                @php
                    $key = $infographic->infographic_progress_key;
                    $progressStatus = $infographicProgressByKey->get($key)?->status ?? 'not_started';
                    $statusText = match ($progressStatus) {
                        'reviewed' => 'Natapos',
                        'in_progress' => 'Kasalukuyang Ginagawa',
                        default => 'Hindi pa nasisimulan',
                    };
                    $calendarLabel = $infographic->calendar_month
                        ? \Carbon\Carbon::createFromDate(2000, $infographic->calendar_month, 1)->format('F') : null;
                @endphp
                <article class="inay-infographic-card" data-learning-infographic data-month="{{ $infographic->infographic_progress_month }}" data-key="{{ $key }}" data-title="{{ $infographic->title }}" data-status="{{ $progressStatus }}">
                    <button type="button" class="inay-infographic-open" data-infographic-open aria-haspopup="dialog" aria-controls="inayInfographicViewer" aria-label="View Infographic: {{ $infographic->title }}">
                        @if($infographic->infographic_url)
                            <div class="inay-infographic-preview"><img src="{{ $infographic->infographic_url }}" alt="Preview: {{ $infographic->title }}" loading="lazy"></div>
                        @else
                            <div class="inay-infographic-preview inay-infographic-preview-designed" aria-hidden="true">
                                <div><span class="inay-preview-brand">PROJECT INAY</span><strong>{{ $infographic->category ?: 'Maternal Care' }}</strong><span class="inay-preview-caption">Gabay sa kalusugan</span></div>
                                <img src="{{ asset('assets/infographics/mother-care.svg') }}" alt="" loading="lazy" width="160" height="160">
                                <div class="inay-preview-checks"><span>✓ Alamin</span><span>✓ Alagaan</span><span>✓ Maghanda</span></div>
                            </div>
                        @endif
                        <div class="inay-infographic-card-body">
                            <span class="inay-infographic-category">{{ $infographic->category ?: 'Maternal Care' }}@if($calendarLabel) · {{ $calendarLabel }}@endif</span>
                            <h4>{{ $infographic->title }}</h4>
                            <p>{{ $infographic->description }}</p>
                            <span class="inay-infographic-view-button">View Infographic <span aria-hidden="true">↗</span></span>
                        </div>
                    </button>
                    <div class="inay-infographic-card-footer"><span class="inay-infographic-badge" data-infographic-badge data-status="{{ $progressStatus }}">@if($progressStatus === 'reviewed')<span aria-hidden="true">✓ </span>@endif{{ $statusText }}</span></div>
                    <template data-infographic-template>
                        <article class="inay-infographic-sheet">
                            <header class="inay-sheet-header">
                                <img src="{{ asset('assets/infographics/mother-care.svg') }}" alt="" width="110" height="110">
                                <span>PROJECT INAY · {{ $infographic->category ?: 'Maternal Care' }}</span>
                                <h3>{{ $infographic->title }}</h3>
                                <p>{{ $infographic->description }}</p>
                            </header>
                            @if($infographic->infographic_url)
                                <img class="inay-infographic-full-image" src="{{ $infographic->infographic_url }}" alt="{{ $infographic->title }}" data-infographic-image>
                                <p class="inay-infographic-image-error" data-image-error hidden>Hindi ma-load ang larawan. Isara at buksan muli upang subukan ulit.</p>
                                <a class="inay-infographic-original" href="{{ $infographic->infographic_url }}" target="_blank" rel="noopener">Buksan ang orihinal na larawan ↗</a>
                            @endif
                            @if($infographic->infographic_sections)
                                <ol class="inay-infographic-steps">
                                    @foreach($infographic->infographic_sections as $step)
                                        <li><span class="inay-infographic-step-number" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><div><h4>{{ $step[0] }}</h4><p>{{ $step[1] }}</p></div></li>
                                    @endforeach
                                </ol>
                            @endif
                            <aside class="inay-infographic-reminder"><strong>Tandaan, Nanay</strong><p>Kung may kakaibang sintomas o emergency, agad kumonsulta sa pinakamalapit na Barangay Health Station, RHU, health center, o ospital.</p></aside>
                            <p class="inay-infographic-disclaimer">Para sa edukasyon lamang. Hindi ito kapalit ng propesyonal na payong medikal, pagsusuri, o paggamot. Sundin ang payo ng iyong healthcare provider.</p>
                            @if($infographic->infographic_sections)
                                <p class="inay-infographic-sources">Mga sanggunian: <a href="https://www.who.int/publications/i/item/9789241549912" target="_blank" rel="noopener">WHO prenatal care</a> · <a href="https://www.who.int/publications/i/item/9789240045989" target="_blank" rel="noopener">WHO mother and newborn care</a></p>
                            @endif
                            <div class="inay-infographic-end" data-infographic-end>♥ Nakarating ka na sa dulo ng infographic.</div>
                        </article>
                    </template>
                </article>
            @endforeach
        </div>
    </section>
@endif
