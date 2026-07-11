@extends('layouts.app')

@section('title', 'INAY Kaalaman - Project INAY')
@section('portal_title', 'INAY Kaalaman')

@php
    $trimesterCards = [
        1 => ['label' => 'Months 1-3', 'title' => '1st Trimester', 'read' => '0/3', 'videos' => '0/6', 'tasks' => '0/6', 'focus' => 'Month 1 (Weeks 1-4)'],
        2 => ['label' => 'Months 4-6', 'title' => '2nd Trimester', 'read' => '0/3', 'videos' => '0/6', 'tasks' => '0/6', 'focus' => 'Month 4 (Weeks 13-16)'],
        3 => ['label' => 'Months 7-10', 'title' => '3rd Trimester', 'read' => '0/4', 'videos' => '0/8', 'tasks' => '0/8', 'focus' => 'Month 7 (Weeks 25-28)'],
    ];

    $months = [
        ['month' => 1, 'trimester' => 1, 'title' => 'Conception & New Beginnings', 'weeks' => 'Weeks 1-4', 'current' => true, 'baby' => 'Conception occurs, and the fertilized egg travels to the uterus. Cell division begins rapidly, forming an embryo. By week 4, the foundation of the heart, nervous system, and organs are starting to take shape.', 'mother' => 'You might not look pregnant yet, but internally your hormone levels rise quickly. You may begin experiencing breast tenderness, subtle nausea, and increased fatigue.', 'symptoms' => 'Frequent urination, light fatigue, breast sensitivity, mild cramps or bloating.', 'nutrition' => 'Start taking 400-800 mcg of folic acid daily. Include folate-rich foods like spinach, broccoli, fortified cereals, citrus fruits, and legumes.', 'risk' => 'Heavy bleeding or severe abdominal pain', 'videos' => [['title' => 'Early Pregnancy And Prenatal Care', 'tag' => 'Prenatal Care', 'time' => '7 min'], ['title' => 'Conception & New Beginnings Nutrition Tips', 'tag' => 'Nutrition', 'time' => '5 min']]],
        ['month' => 2, 'trimester' => 1, 'title' => 'The Tiny Heart Beats', 'weeks' => 'Weeks 5-8', 'current' => false, 'baby' => "Baby's heart starts beating. Limb buds appear, and the brain begins developing quickly.", 'mother' => 'Morning sickness may peak. Breasts continue to grow and become more tender.', 'symptoms' => 'Nausea, vomiting, smell sensitivity, mood changes, food cravings, and frequent urination.', 'nutrition' => 'Eat small frequent meals. Choose crackers, ginger tea, bananas, rice, soup, and water when nausea is strong.', 'risk' => 'Severe vomiting or dehydration', 'videos' => [['title' => 'The Tiny Heart Beats: What To Expect', 'tag' => 'Baby Development', 'time' => '6 min'], ['title' => 'Morning Sickness Care At Home', 'tag' => 'Maternal Care', 'time' => '5 min']]],
        ['month' => 3, 'trimester' => 1, 'title' => 'First Trimester Milestones', 'weeks' => 'Weeks 9-12', 'current' => false, 'baby' => 'Tiny fingers, toes, and facial features become clearer. Major organs continue to mature.', 'mother' => 'Energy may slowly return, though nausea can still happen. Continue prenatal checkups and supplements.', 'symptoms' => 'Fatigue, mild headaches, changing appetite, and tender breasts.', 'nutrition' => 'Add protein, iron-rich food, fruits, and vegetables. Avoid alcohol and unsafe medicines.', 'risk' => 'Fever, bleeding, or persistent abdominal pain', 'videos' => [['title' => 'First Trimester Safety Reminders', 'tag' => 'Safety', 'time' => '6 min'], ['title' => 'Healthy Plate For Pregnancy', 'tag' => 'Nutrition', 'time' => '5 min']]],
        ['month' => 4, 'trimester' => 2, 'title' => 'Energy Returns', 'weeks' => 'Weeks 13-16', 'current' => true, 'baby' => 'Baby grows longer and begins making small movements, even if you cannot feel them yet.', 'mother' => 'Appetite may improve. Your abdomen may start showing more clearly.', 'symptoms' => 'Round ligament pain, clearer appetite, less nausea, and skin changes.', 'nutrition' => 'Continue iron and calcium sources. Add milk, malunggay, fish, eggs, beans, and leafy vegetables.', 'risk' => 'Strong cramps, fever, or unusual discharge', 'videos' => [['title' => 'Second Trimester Changes', 'tag' => 'Maternal Care', 'time' => '6 min'], ['title' => 'Safe Movement And Rest', 'tag' => 'Wellness', 'time' => '4 min']]],
        ['month' => 5, 'trimester' => 2, 'title' => 'Feeling Baby Move', 'weeks' => 'Weeks 17-20', 'current' => false, 'baby' => 'You may begin feeling gentle flutters. Baby can hear sounds and continues growing stronger.', 'mother' => 'Back discomfort and leg cramps may appear. Gentle stretching and hydration can help.', 'symptoms' => 'Quickening, backache, leg cramps, and increased appetite.', 'nutrition' => 'Prioritize calcium, protein, iron, and water. Bring your record to your scheduled checkup.', 'risk' => 'No fetal movement after it has become regular', 'videos' => [['title' => 'Understanding Baby Movements', 'tag' => 'Baby Development', 'time' => '6 min'], ['title' => 'Comfort Tips For Back Pain', 'tag' => 'Wellness', 'time' => '5 min']]],
        ['month' => 6, 'trimester' => 2, 'title' => 'Growth And Screening', 'weeks' => 'Weeks 21-24', 'current' => false, 'baby' => 'Baby gains weight and practices breathing movements. Regular monitoring becomes more important.', 'mother' => 'You may notice swelling, stronger appetite, and more visible belly growth.', 'symptoms' => 'Swollen feet, heartburn, backache, and stronger fetal movement.', 'nutrition' => 'Reduce salty food, stay hydrated, and follow your clinic schedule for screening.', 'risk' => 'Severe headache, blurred vision, or sudden swelling', 'videos' => [['title' => 'Screening And Checkup Reminders', 'tag' => 'Checkup', 'time' => '7 min'], ['title' => 'Eating Well In Month Six', 'tag' => 'Nutrition', 'time' => '5 min']]],
        ['month' => 7, 'trimester' => 3, 'title' => 'Preparing For The Final Stretch', 'weeks' => 'Weeks 25-28', 'current' => true, 'baby' => 'Baby opens the eyes, gains more weight, and responds to sound and light. Brain and lung development continue quickly.', 'mother' => 'You may feel stronger kicks, back pressure, and more frequent heartburn. Begin preparing birth plans and records.', 'symptoms' => 'Back pain, heartburn, leg cramps, stronger baby movement, and mild swelling.', 'nutrition' => 'Continue iron, calcium, protein, and water. Limit salty food and keep checkup records updated.', 'risk' => 'Contractions, leaking fluid, bleeding, or severe headache', 'videos' => [['title' => 'Third Trimester Preparation', 'tag' => 'Birth Readiness', 'time' => '7 min'], ['title' => 'Warning Signs Before Delivery', 'tag' => 'Safety', 'time' => '6 min']]],
        ['month' => 8, 'trimester' => 3, 'title' => 'Monitoring Baby Position', 'weeks' => 'Weeks 29-32', 'current' => false, 'baby' => 'Baby gains fat and may begin settling into a head-down position. Movements should be monitored daily.', 'mother' => 'Shortness of breath, pelvic pressure, and sleep discomfort may increase.', 'symptoms' => 'Pelvic pressure, frequent urination, sleep changes, and Braxton Hicks contractions.', 'nutrition' => 'Choose nutrient-dense meals, water, fruits, vegetables, fish, eggs, and iron-rich food.', 'risk' => 'Reduced baby movement or signs of preterm labor', 'videos' => [['title' => 'Counting Baby Kicks', 'tag' => 'Monitoring', 'time' => '5 min'], ['title' => 'Comfortable Sleep Positions', 'tag' => 'Wellness', 'time' => '4 min']]],
        ['month' => 9, 'trimester' => 3, 'title' => 'Birth Readiness', 'weeks' => 'Weeks 33-36', 'current' => false, 'baby' => 'Baby continues gaining weight and the lungs mature further. Delivery preparations become more important.', 'mother' => 'You may feel heavier and more tired. Prepare transport, emergency contacts, and your birth bag.', 'symptoms' => 'Pelvic heaviness, back pain, sleep discomfort, and stronger contractions.', 'nutrition' => 'Eat small meals, stay hydrated, and keep energy-giving foods ready.', 'risk' => 'Regular painful contractions, water breaking, bleeding, or high blood pressure symptoms', 'videos' => [['title' => 'What To Pack For Delivery', 'tag' => 'Birth Plan', 'time' => '6 min'], ['title' => 'When To Go To The Clinic', 'tag' => 'Safety', 'time' => '5 min']]],
        ['month' => 10, 'trimester' => 3, 'title' => 'Safe Delivery And Newborn Care', 'weeks' => 'Weeks 37-40', 'current' => false, 'baby' => 'Baby is considered full term and ready for delivery. Newborn care planning begins.', 'mother' => 'Watch for true labor signs and keep communication open with your Program Staff or clinic.', 'symptoms' => 'Lower belly pressure, mucus plug, contractions, and nesting energy.', 'nutrition' => 'Eat easy-to-digest meals and drink water. Prepare postpartum food and support.', 'risk' => 'No baby movement, bleeding, severe headache, or prolonged labor pain', 'videos' => [['title' => 'Safe Delivery Reminders', 'tag' => 'Delivery', 'time' => '7 min'], ['title' => 'Newborn Care Basics', 'tag' => 'Newborn', 'time' => '6 min']]],
    ];

    $kaalamanUploads = collect($kaalamanUploads ?? []);

    $iconBook = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/></svg>';
    $iconVideo = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m10 8 6 4-6 4Z"/><rect x="3" y="5" width="18" height="14" rx="2"/></svg>';
    $iconTask = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 11l2 2 4-4"/><rect x="4" y="3" width="16" height="18" rx="2"/></svg>';
    $iconHeart = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 5.6a5.4 5.4 0 0 0-7.6 0L12 6.8l-1.2-1.2a5.4 5.4 0 1 0-7.6 7.6L12 22l8.8-8.8a5.4 5.4 0 0 0 0-7.6Z"/></svg>';
    $iconPulse = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>';
    $iconChevron = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
    $iconAlert = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m21.7 18-8-14a2 2 0 0 0-3.4 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>';
    $iconFile = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>';
    $iconUpload = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12"/><path d="m7 8 5-5 5 5"/><path d="M5 21h14"/></svg>';
    $iconShield = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>';
@endphp

@section('content')
    <section class="kaalaman-shell" aria-label="INAY Kaalaman learning module">
        <div class="kaalaman-hero">
            <div>
                <h1>{!! $iconBook !!} INAY Kaalaman (IEC Learning Hub)</h1>
                <p>Ang iyong gabay bawat buwan ng pagbubuntis. Alamin ang pagbabago sa iyong katawan, paglaki ni baby, wastong nutrisyon, at mga mahalagang paalala upang mapanatili silang ligtas.</p>
            </div>
            <div class="kaalaman-focus-card">
                <span class="kaalaman-eyebrow">Current Month Focus</span>
                <strong data-kaalaman-focus>Month 1 (Weeks 1-4)</strong>
                <small>Completed 0/10 months</small>
            </div>
        </div>

        <div class="kaalaman-trimester-grid" role="tablist" aria-label="Trimester progress">
            @foreach ($trimesterCards as $trimester => $card)
                <button class="kaalaman-trimester-card @if($trimester === 1) is-selected @endif" type="button" role="tab" aria-selected="{{ $trimester === 1 ? 'true' : 'false' }}" data-trimester-button="{{ $trimester }}" data-focus="{{ $card['focus'] }}">
                    <span class="kaalaman-pill @if($trimester !== 1) is-muted @endif">{{ $card['label'] }}</span>
                    <span class="kaalaman-trimester-title">{{ $card['title'] }}</span>
                    <span class="kaalaman-trimester-subtitle">Trimester learning progress</span>
                    <span class="kaalaman-metric-list">
                        <span class="kaalaman-metric"><span>{!! $iconBook !!} Nabasa (Read)</span><strong>{{ $card['read'] }}</strong></span>
                        <span class="kaalaman-metric"><span>{!! $iconVideo !!} Napanood (Videos)</span><strong>{{ $card['videos'] }}</strong></span>
                        <span class="kaalaman-metric"><span>{!! $iconTask !!} Medical Tasks</span><strong>{{ $card['tasks'] }}</strong></span>
                    </span>
                </button>
            @endforeach
        </div>

        <div class="kaalaman-months" aria-live="polite">
            @foreach ($months as $month)
                @php
                    $monthUploads = collect($kaalamanUploads->get($month['month'], []));
                    $hasCheckupUpload = $monthUploads->contains(fn ($upload) => strcasecmp($upload->record_type, 'Checkup Records') === 0);
                    $hasPrescriptionUpload = $monthUploads->contains(fn ($upload) => strcasecmp($upload->record_type, 'Prescription') === 0);
                    $uploadedCount = $monthUploads->count();
                    $requirementsComplete = $hasCheckupUpload && $hasPrescriptionUpload;
                @endphp
                <details class="kaalaman-month @if($month['trimester'] !== 1) is-hidden @endif" data-trimester-month="{{ $month['trimester'] }}" @if($month['month'] === 1) open @endif>
                    <summary class="kaalaman-month-summary">
                        <span class="kaalaman-month-badge">Month {{ $month['month'] }}</span>
                        <span>
                            <span class="kaalaman-month-title">{{ $month['title'] }} <span>({{ $month['weeks'] }})</span></span>
                            <span class="kaalaman-month-hint">{{ $month['current'] ? 'Ikaw ay nandito - kasalukuyang buwan' : 'Tap to open' }}</span>
                        </span>
                        <span class="kaalaman-chevron">{!! $iconChevron !!}</span>
                    </summary>

                    <div class="kaalaman-month-body">
                        <div class="kaalaman-info-grid">
                            <article class="kaalaman-info-card"><h3>{!! $iconHeart !!} Paglaki ni Baby (Baby Development)</h3><p>{{ $month['baby'] }}</p></article>
                            <article class="kaalaman-info-card is-blue"><h3>{!! $iconPulse !!} Maternal Changes (Pagbabago sa Katawan)</h3><p>{{ $month['mother'] }}</p></article>
                            <article class="kaalaman-info-card"><h3>Inaasahang Sintomas Noong Buwang Ito</h3><p>{{ $month['symptoms'] }}</p></article>
                            <article class="kaalaman-info-card is-blue"><h3>Gabay sa Wastong Nutrisyon (Nutritional Guidance)</h3><p>{{ $month['nutrition'] }}</p></article>
                        </div>

                        <section class="kaalaman-read-panel">
                            <div>
                                <h3>{!! $iconBook !!} Gabay sa Pagbabasa para sa Buwan {{ $month['month'] }}</h3>
                                <p>I-marka bilang nabasa kapag tapos mo nang suriin ang babasahin sa buwang ito.</p>
                            </div>
                            <button class="kaalaman-button" type="button" data-toggle-done="Marked as Read">Mark as Read</button>
                        </section>

                        <div class="kaalaman-divider"></div>

                        <h3 class="kaalaman-section-title">{!! $iconVideo !!} Mga Kakabit na Video at Edukasyon ng Barangay</h3>
                        <div class="kaalaman-video-grid">
                            @foreach ($month['videos'] as $video)
                                @php
                                    $videoUrl = $video['url'] ?? 'https://www.youtube.com/results?search_query='.urlencode($video['title'].' pregnancy education');
                                @endphp
                                <article class="kaalaman-video-card is-clickable" role="button" tabindex="0" data-video-card data-video-title="{{ $video['title'] }}" data-video-meta="{{ $video['tag'] }} - {{ $video['time'] }}" data-video-url="{{ $videoUrl }}">
                                    <div class="kaalaman-video-top">
                                        <span class="kaalaman-video-play">{!! $iconVideo !!}</span>
                                        <button class="kaalaman-video-action" type="button" data-toggle-done="Watched">Mark as Watched</button>
                                    </div>
                                    <h3>{{ $video['title'] }}</h3>
                                    <div class="kaalaman-video-meta"><strong>{{ $video['tag'] }}</strong><span>{{ $video['time'] }}</span></div>
                                </article>
                            @endforeach
                        </div>

                        <a class="kaalaman-button kaalaman-more" href="{{ route('inay-kaalaman.videos', ['month' => $month['month']]) }}">More Videos</a>

                        <section class="kaalaman-status-panel">
                            <h3 class="kaalaman-section-title">{!! $iconTask !!} Documentation Status</h3>
                            <p>This shows read, video, and uploaded record progress for this month.</p>
                            <div class="kaalaman-status-grid">
                                <div class="kaalaman-status-card"><span class="kaalaman-status-mini-icon">{!! $iconBook !!}</span><div><span>Reading</span><strong>Pending</strong></div></div>
                                <div class="kaalaman-status-card"><span class="kaalaman-status-mini-icon is-blue">{!! $iconVideo !!}</span><div><span>Videos</span><strong>0/2</strong></div></div>
                                <div class="kaalaman-status-card {{ $hasCheckupUpload ? 'is-complete' : 'is-needed' }}"><span class="kaalaman-status-mini-icon {{ $hasCheckupUpload ? 'is-success' : 'is-warning' }}">{!! $iconTask !!}</span><div><span>Checkup</span><strong>{{ $hasCheckupUpload ? 'Uploaded' : 'Needed' }}</strong></div></div>
                                <div class="kaalaman-status-card {{ $hasPrescriptionUpload ? 'is-complete' : 'is-needed' }}"><span class="kaalaman-status-mini-icon {{ $hasPrescriptionUpload ? 'is-success' : 'is-warning' }}">{!! $iconFile !!}</span><div><span>Prescription</span><strong>{{ $hasPrescriptionUpload ? 'Uploaded' : 'Needed' }}</strong></div></div>
                            </div>
                        </section>

                        <div class="kaalaman-divider"></div>

                        <section class="kaalaman-risk-panel">
                            <h3>{!! $iconAlert !!} Importanteng Babala at Panganib (Risk Alerts & Consequences)</h3>
                            <div class="kaalaman-risk-item">
                                <span class="kaalaman-risk-icon">{!! $iconAlert !!}</span>
                                <p class="kaalaman-risk-copy"><strong class="kaalaman-risk-name">{{ $month['risk'] }}</strong><strong>Consequence:</strong> May indicate an urgent condition. <br><strong>Rekomendasyon:</strong> Seek immediate care at the nearest Barangay Health Station, RHU, or hospital.</p>
                            </div>
                        </section>

                        <div class="kaalaman-lower-grid">
                            <section>
                                <h3 class="kaalaman-task-title">{!! $iconTask !!} Mga Bakuna at Medical Tasks Ngayong Buwan</h3>
                                <div class="kaalaman-task-list">
                                    <article class="kaalaman-task-card">
                                        <span class="kaalaman-task-icon">{!! $iconFile !!}</span>
                                        <div><h4>Routine prenatal checkup and vital signs</h4><p><strong>Timing: Within month {{ $month['month'] }}</strong><br>Importance: Confirms maternal health, fetal growth, and early warning signs.</p></div>
                                    </article>
                                    <article class="kaalaman-task-card">
                                        <span class="kaalaman-task-icon">{!! $iconFile !!}</span>
                                        <div><h4>Supplement prescription or refill record</h4><p><strong>Timing: Bring your latest prescription</strong><br>Importance: Helps your Program Staff confirm needed medicine.</p></div>
                                    </article>
                                </div>
                            </section>

                            <section>
                                <h3 class="kaalaman-task-title">{!! $iconBook !!} Impormasyong Infographics</h3>
                                <article class="kaalaman-infographic-card is-clickable" role="button" tabindex="0" data-infographic-card data-infographic-title="{{ $month['title'] }} Checklist" data-infographic-meta="Maternal Care - Month {{ $month['month'] }}" data-pdf-url="{{ route('inay-kaalaman.infographic.pdf', ['month' => $month['month']]) }}">
                                    <span class="kaalaman-infographic-icon">{!! $iconFile !!}</span>
                                    <div><strong>Maternal Care</strong><h4>{{ $month['title'] }} Checklist</h4></div>
                                    <span class="kaalaman-review-link">Suriin -></span>
                                </article>
                            </section>
                        </div>

                        <form class="kaalaman-upload-panel" method="POST" action="{{ route('inay-kaalaman.upload') }}" enctype="multipart/form-data" data-upload-form>
                            @csrf
                            <input type="hidden" name="month" value="{{ $month['month'] }}">
                            <h3 class="kaalaman-upload-title">{!! $iconUpload !!} Upload Prenatal Records & Receipts</h3>
                            <div class="kaalaman-upload-row">
                                <div class="kaalaman-upload-field"><label>Type of Record</label><select name="record_type" required><option>Checkup Records</option><option>Prescription</option><option>Receipts</option></select></div>
                                <div class="kaalaman-upload-field">
                                    <label>Select Document</label>
                                    <label class="kaalaman-file-picker">
                                        <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required data-upload-input>
                                        <span>Choose file</span>
                                        <strong data-upload-file>No file chosen</strong>
                                    </label>
                                </div>
                                <div class="kaalaman-upload-actions">
                                    <button class="kaalaman-button kaalaman-upload-button" type="submit">{!! $iconUpload !!} I-upload sa Records</button>
                                    <button class="kaalaman-button secondary kaalaman-upload-cancel" type="button" data-upload-cancel hidden>Cancel</button>
                                </div>
                            </div>
                        </form>

                        <section class="kaalaman-note-panel">
                            <h3>{!! $iconAlert !!} Program Staff Notes (Payo ng Barangay Midwife)</h3>
                            <p>Great job. Your upcoming checkups are scheduled every 2 weeks starting this month. Keep practicing regular deep-breathing exercises.</p>
                        </section>

                        <section class="kaalaman-complete-panel">
                            <span class="kaalaman-complete-icon">{!! $iconShield !!}</span>
                            <div><h3>{{ $requirementsComplete ? 'Month requirements uploaded' : 'Complete the month requirements' }}</h3><p>{{ $requirementsComplete ? 'Checkup and prescription records are saved for this month.' : 'Watch all videos and upload both checkup record and prescription.' }} {{ $uploadedCount }} saved upload{{ $uploadedCount === 1 ? '' : 's' }}.</p></div>
                        </section>
                    </div>
                </details>
            @endforeach
        </div>
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

    <div class="kaalaman-video-modal" data-infographic-modal hidden>
        <div class="kaalaman-video-backdrop" data-infographic-close></div>
        <section class="kaalaman-video-dialog" role="dialog" aria-modal="true" aria-labelledby="kaalaman-infographic-title">
            <button class="kaalaman-video-close" type="button" aria-label="Close infographic" data-infographic-close>&times;</button>
            <span class="kaalaman-infographic-icon is-large">{!! $iconFile !!}</span>
            <p class="kaalaman-eyebrow">Impormasyong Infographics</p>
            <h2 id="kaalaman-infographic-title" data-infographic-modal-title>Checklist</h2>
            <p data-infographic-modal-meta></p>
            <p class="kaalaman-video-modal-copy">Review this checklist in a printable PDF format for your monthly maternal care reminders.</p>
            <div class="kaalaman-modal-actions">
                <a class="kaalaman-button" href="#" target="_blank" rel="noopener" data-infographic-modal-link>Open PDF</a>
                <button class="kaalaman-button secondary" type="button" data-infographic-close>Close</button>
            </div>
        </section>
    </div>

    <script>
        document.querySelectorAll('[data-toggle-done]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.stopPropagation();
                button.classList.toggle('is-done');
                button.textContent = button.classList.contains('is-done')
                    ? button.dataset.toggleDone
                    : (button.classList.contains('kaalaman-video-action') ? 'Mark as Watched' : 'Mark as Read');
            });
        });

        const trimesterButtons = document.querySelectorAll('[data-trimester-button]');
        const monthPanels = document.querySelectorAll('[data-trimester-month]');
        const focusLabel = document.querySelector('[data-kaalaman-focus]');

        trimesterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const trimester = button.dataset.trimesterButton;

                trimesterButtons.forEach((item) => {
                    const isSelected = item === button;
                    item.classList.toggle('is-selected', isSelected);
                    item.setAttribute('aria-selected', isSelected ? 'true' : 'false');
                    item.querySelector('.kaalaman-pill')?.classList.toggle('is-muted', !isSelected);
                });

                let openedFirstMonth = false;
                monthPanels.forEach((panel) => {
                    const isVisible = panel.dataset.trimesterMonth === trimester;
                    panel.classList.toggle('is-hidden', !isVisible);
                    panel.open = isVisible && !openedFirstMonth;
                    if (isVisible && !openedFirstMonth) openedFirstMonth = true;
                });

                if (focusLabel) focusLabel.textContent = button.dataset.focus;
            });
        });

        monthPanels.forEach((panel) => {
            panel.addEventListener('toggle', () => {
                if (!panel.open) return;

                monthPanels.forEach((otherPanel) => {
                    if (otherPanel !== panel && otherPanel.dataset.trimesterMonth === panel.dataset.trimesterMonth) {
                        otherPanel.open = false;
                    }
                });
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

        const infographicModal = document.querySelector('[data-infographic-modal]');
        const infographicModalTitle = document.querySelector('[data-infographic-modal-title]');
        const infographicModalMeta = document.querySelector('[data-infographic-modal-meta]');
        const infographicModalLink = document.querySelector('[data-infographic-modal-link]');

        const openInfographicModal = (card) => {
            if (!infographicModal || !infographicModalTitle || !infographicModalMeta || !infographicModalLink) return;

            infographicModalTitle.textContent = card.dataset.infographicTitle || 'Maternal care checklist';
            infographicModalMeta.textContent = card.dataset.infographicMeta || '';
            infographicModalLink.href = card.dataset.pdfUrl || '#';
            infographicModal.hidden = false;
            document.body.classList.add('has-kaalaman-modal');
            infographicModalLink.focus();
        };

        const closeInfographicModal = () => {
            if (!infographicModal) return;

            infographicModal.hidden = true;
            document.body.classList.remove('has-kaalaman-modal');
        };

        document.querySelectorAll('[data-infographic-card]').forEach((card) => {
            card.addEventListener('click', () => openInfographicModal(card));

            card.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter' && event.key !== ' ') return;
                event.preventDefault();
                openInfographicModal(card);
            });
        });

        document.querySelectorAll('[data-infographic-close]').forEach((button) => {
            button.addEventListener('click', closeInfographicModal);
        });

        document.querySelectorAll('[data-upload-form]').forEach((form) => {
            const input = form.querySelector('[data-upload-input]');
            const filename = form.querySelector('[data-upload-file]');
            const cancel = form.querySelector('[data-upload-cancel]');

            const refreshUploadState = () => {
                const file = input?.files?.[0] || null;
                if (filename) filename.textContent = file ? file.name : 'No file chosen';
                if (cancel) cancel.hidden = !file;
                form.classList.toggle('has-file', Boolean(file));
            };

            input?.addEventListener('change', refreshUploadState);
            cancel?.addEventListener('click', () => {
                if (input) input.value = '';
                refreshUploadState();
            });

            refreshUploadState();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeVideoModal();
                closeInfographicModal();
            }
        });
    </script>
@endsection
