@extends('layouts.app')

@section('title', 'INAY Kaalaman - Project INAY')
@section('portal_title', 'INAY Kaalaman')

@php
    $learningStages = [
        'first-trimester' => ['label' => 'Months 1-3', 'title' => '1st Trimester', 'icon' => 'sprout', 'progress' => 0, 'focus' => 'Month 1 (Weeks 1-4)', 'output' => 'Early pregnancy foundation, prenatal checkup readiness, nutrition and warning signs.'],
        'second-trimester' => ['label' => 'Months 4-6', 'title' => '2nd Trimester', 'icon' => 'mother', 'progress' => 0, 'focus' => 'Month 4 (Weeks 13-16)', 'output' => 'Growth screening, movement awareness, supplement continuity and safer daily routines.'],
        'third-trimester' => ['label' => 'Months 7-9', 'title' => '3rd Trimester', 'icon' => 'baby', 'progress' => 0, 'focus' => 'Month 7 (Weeks 25-28)', 'output' => 'Birth readiness, final checkups, danger sign monitoring and emergency planning.'],
        'labor-delivery' => ['label' => 'Birth Process', 'title' => 'Labor & Delivery', 'icon' => 'facility', 'progress' => 0, 'focus' => 'Birth Process', 'output' => 'Prepared birth plan, hospital bag, delivery options and immediate newborn care.'],
        'postpartum-care' => ['label' => 'Birth to 6 Weeks After Delivery', 'title' => 'Postpartum Care', 'icon' => 'recovery', 'progress' => 0, 'focus' => 'Birth to 6 Weeks After Delivery', 'output' => 'Mother recovery checklist, breastfeeding support, family planning and follow-up care.'],
        'neonatal-care' => ['label' => '0-28 Days After Birth', 'title' => 'Neonatal Care', 'icon' => 'newborn', 'progress' => 0, 'focus' => '0-28 Days After Birth', 'output' => 'Newborn screening, immunization, feeding, cord care and growth monitoring.'],
    ];

    $continuumPanels = [
        'labor-delivery' => [
            'title' => 'Labor & Delivery',
            'label' => 'Birth Process',
            'icon' => 'facility',
            'summary' => 'Prepare for safe birth by reviewing labor signs, hospital readiness, delivery options, pain support, and the first newborn care steps.',
            'topics' => ['Signs of labor', 'Hospital preparation', 'Normal and Cesarean delivery', 'Pain management', 'Delivery process', 'Immediate newborn care'],
            'tasks' => ['Prepare birth bag, records, and emergency contacts.', 'Review transport plan and nearest facility with your Program Staff.', 'Know when to seek urgent care during labor.'],
        ],
        'postpartum-care' => [
            'title' => 'Postpartum Care',
            'label' => 'Birth to 6 Weeks After Delivery',
            'icon' => 'recovery',
            'summary' => 'Support the mother after delivery with recovery guidance, feeding support, mental health checks, family planning, danger signs, and follow-up care.',
            'topics' => ["Mother's recovery", 'Nutrition', 'Breastfeeding', 'Mental health', 'Family planning', 'Postpartum danger signs', 'Follow-up checkups'],
            'tasks' => ['Track bleeding, fever, pain, mood, and recovery warning signs.', 'Attend postpartum checkups and family planning counseling.', 'Ask for help early when breastfeeding or emotional health feels difficult.'],
        ],
        'neonatal-care' => [
            'title' => 'Neonatal Care',
            'label' => '0-28 Days After Birth',
            'icon' => 'newborn',
            'summary' => 'Guide newborn care during the first 28 days with feeding, hygiene, immunization, screening, danger signs, and growth monitoring reminders.',
            'topics' => ['Essential newborn care', 'Breastfeeding', 'Cord care', 'Bathing', 'Newborn danger signs', 'Immunization (BCG, Hepatitis B, OPV)', 'Growth monitoring', 'Newborn screening', 'Follow-up visits'],
            'tasks' => ['Complete newborn screening, immunization, and follow-up visits.', 'Monitor feeding, temperature, breathing, cord condition, and jaundice signs.', 'Bring the newborn for urgent care if danger signs appear.'],
        ],
    ];

    $months = [
        ['month' => 1, 'stage' => 'first-trimester', 'title' => 'Conception & New Beginnings', 'weeks' => 'Weeks 1-4', 'current' => true, 'baby' => 'Conception occurs, and the fertilized egg travels to the uterus. Cell division begins rapidly, forming an embryo. By week 4, the foundation of the heart, nervous system, and organs are starting to take shape.', 'mother' => 'You might not look pregnant yet, but internally your hormone levels rise quickly. You may begin experiencing breast tenderness, subtle nausea, and increased fatigue.', 'symptoms' => 'Frequent urination, light fatigue, breast sensitivity, mild cramps or bloating.', 'nutrition' => 'Start taking 400-800 mcg of folic acid daily. Include folate-rich foods like spinach, broccoli, fortified cereals, citrus fruits, and legumes.', 'risk' => 'Heavy bleeding or severe abdominal pain', 'videos' => [['title' => 'Early Pregnancy And Prenatal Care', 'tag' => 'Prenatal Care', 'time' => '7 min'], ['title' => 'Conception & New Beginnings Nutrition Tips', 'tag' => 'Nutrition', 'time' => '5 min']]],
        ['month' => 2, 'stage' => 'first-trimester', 'title' => 'The Tiny Heart Beats', 'weeks' => 'Weeks 5-8', 'current' => false, 'baby' => "Baby's heart starts beating. Limb buds appear, and the brain begins developing quickly.", 'mother' => 'Morning sickness may peak. Breasts continue to grow and become more tender.', 'symptoms' => 'Nausea, vomiting, smell sensitivity, mood changes, food cravings, and frequent urination.', 'nutrition' => 'Eat small frequent meals. Choose crackers, ginger tea, bananas, rice, soup, and water when nausea is strong.', 'risk' => 'Severe vomiting or dehydration', 'videos' => [['title' => 'The Tiny Heart Beats: What To Expect', 'tag' => 'Baby Development', 'time' => '6 min'], ['title' => 'Morning Sickness Care At Home', 'tag' => 'Maternal Care', 'time' => '5 min']]],
        ['month' => 3, 'stage' => 'first-trimester', 'title' => 'First Trimester Milestones', 'weeks' => 'Weeks 9-12', 'current' => false, 'baby' => 'Tiny fingers, toes, and facial features become clearer. Major organs continue to mature.', 'mother' => 'Energy may slowly return, though nausea can still happen. Continue prenatal checkups and supplements.', 'symptoms' => 'Fatigue, mild headaches, changing appetite, and tender breasts.', 'nutrition' => 'Add protein, iron-rich food, fruits, and vegetables. Avoid alcohol and unsafe medicines.', 'risk' => 'Fever, bleeding, or persistent abdominal pain', 'videos' => [['title' => 'First Trimester Safety Reminders', 'tag' => 'Safety', 'time' => '6 min'], ['title' => 'Healthy Plate For Pregnancy', 'tag' => 'Nutrition', 'time' => '5 min']]],
        ['month' => 4, 'stage' => 'second-trimester', 'title' => 'Energy Returns', 'weeks' => 'Weeks 13-16', 'current' => true, 'baby' => 'Baby grows longer and begins making small movements, even if you cannot feel them yet.', 'mother' => 'Appetite may improve. Your abdomen may start showing more clearly.', 'symptoms' => 'Round ligament pain, clearer appetite, less nausea, and skin changes.', 'nutrition' => 'Continue iron and calcium sources. Add milk, malunggay, fish, eggs, beans, and leafy vegetables.', 'risk' => 'Strong cramps, fever, or unusual discharge', 'videos' => [['title' => 'Second Trimester Changes', 'tag' => 'Maternal Care', 'time' => '6 min'], ['title' => 'Safe Movement And Rest', 'tag' => 'Wellness', 'time' => '4 min']]],
        ['month' => 5, 'stage' => 'second-trimester', 'title' => 'Feeling Baby Move', 'weeks' => 'Weeks 17-20', 'current' => false, 'baby' => 'You may begin feeling gentle flutters. Baby can hear sounds and continues growing stronger.', 'mother' => 'Back discomfort and leg cramps may appear. Gentle stretching and hydration can help.', 'symptoms' => 'Quickening, backache, leg cramps, and increased appetite.', 'nutrition' => 'Prioritize calcium, protein, iron, and water. Bring your record to your scheduled checkup.', 'risk' => 'No fetal movement after it has become regular', 'videos' => [['title' => 'Understanding Baby Movements', 'tag' => 'Baby Development', 'time' => '6 min'], ['title' => 'Comfort Tips For Back Pain', 'tag' => 'Wellness', 'time' => '5 min']]],
        ['month' => 6, 'stage' => 'second-trimester', 'title' => 'Growth And Screening', 'weeks' => 'Weeks 21-24', 'current' => false, 'baby' => 'Baby gains weight and practices breathing movements. Regular monitoring becomes more important.', 'mother' => 'You may notice swelling, stronger appetite, and more visible belly growth.', 'symptoms' => 'Swollen feet, heartburn, backache, and stronger fetal movement.', 'nutrition' => 'Reduce salty food, stay hydrated, and follow your clinic schedule for screening.', 'risk' => 'Severe headache, blurred vision, or sudden swelling', 'videos' => [['title' => 'Screening And Checkup Reminders', 'tag' => 'Checkup', 'time' => '7 min'], ['title' => 'Eating Well In Month Six', 'tag' => 'Nutrition', 'time' => '5 min']]],
        ['month' => 7, 'stage' => 'third-trimester', 'title' => 'Preparing For The Final Stretch', 'weeks' => 'Weeks 25-28', 'current' => true, 'baby' => 'Baby opens the eyes, gains more weight, and responds to sound and light. Brain and lung development continue quickly.', 'mother' => 'You may feel stronger kicks, back pressure, and more frequent heartburn. Begin preparing birth plans and records.', 'symptoms' => 'Back pain, heartburn, leg cramps, stronger baby movement, and mild swelling.', 'nutrition' => 'Continue iron, calcium, protein, and water. Limit salty food and keep checkup records updated.', 'risk' => 'Contractions, leaking fluid, bleeding, or severe headache', 'videos' => [['title' => 'Third Trimester Preparation', 'tag' => 'Birth Readiness', 'time' => '7 min'], ['title' => 'Warning Signs Before Delivery', 'tag' => 'Safety', 'time' => '6 min']]],
        ['month' => 8, 'stage' => 'third-trimester', 'title' => 'Monitoring Baby Position', 'weeks' => 'Weeks 29-32', 'current' => false, 'baby' => 'Baby gains fat and may begin settling into a head-down position. Movements should be monitored daily.', 'mother' => 'Shortness of breath, pelvic pressure, and sleep discomfort may increase.', 'symptoms' => 'Pelvic pressure, frequent urination, sleep changes, and Braxton Hicks contractions.', 'nutrition' => 'Choose nutrient-dense meals, water, fruits, vegetables, fish, eggs, and iron-rich food.', 'risk' => 'Reduced baby movement or signs of preterm labor', 'videos' => [['title' => 'Counting Baby Kicks', 'tag' => 'Monitoring', 'time' => '5 min'], ['title' => 'Comfortable Sleep Positions', 'tag' => 'Wellness', 'time' => '4 min']]],
        ['month' => 9, 'stage' => 'third-trimester', 'title' => 'Birth Readiness', 'weeks' => 'Weeks 33-36', 'current' => false, 'baby' => 'Baby continues gaining weight and the lungs mature further. Delivery preparations become more important.', 'mother' => 'You may feel heavier and more tired. Prepare transport, emergency contacts, and your birth bag.', 'symptoms' => 'Pelvic heaviness, back pain, sleep discomfort, and stronger contractions.', 'nutrition' => 'Eat small meals, stay hydrated, and keep energy-giving foods ready.', 'risk' => 'Regular painful contractions, water breaking, bleeding, or high blood pressure symptoms', 'videos' => [['title' => 'What To Pack For Delivery', 'tag' => 'Birth Plan', 'time' => '6 min'], ['title' => 'When To Go To The Clinic', 'tag' => 'Safety', 'time' => '5 min']]],
        ['month' => 10, 'stage' => 'labor-delivery', 'title' => 'Safe Delivery And Newborn Care', 'weeks' => 'Weeks 37-40', 'current' => false, 'baby' => 'Baby is considered full term and ready for delivery. Newborn care planning begins.', 'mother' => 'Watch for true labor signs and keep communication open with your Program Staff or clinic.', 'symptoms' => 'Lower belly pressure, mucus plug, contractions, and nesting energy.', 'nutrition' => 'Eat easy-to-digest meals and drink water. Prepare postpartum food and support.', 'risk' => 'No baby movement, bleeding, severe headache, or prolonged labor pain', 'videos' => [['title' => 'Safe Delivery Reminders', 'tag' => 'Delivery', 'time' => '7 min'], ['title' => 'Newborn Care Basics', 'tag' => 'Newborn', 'time' => '6 min']]],
    ];

    $kaalamanUploads = collect($kaalamanUploads ?? []);
    $kaalamanMonthlyProgress = $kaalamanMonthlyProgress ?? ['months' => [], 'overall' => ['completed_months' => 0, 'total_months' => 10, 'percentage' => 0]];
    $kaalamanOverallProgress = $kaalamanOverallProgress ?? ($kaalamanMonthlyProgress['overall'] ?? ['completed_months' => 0, 'total_months' => 10, 'percentage' => 0]);
    $publishedEducationalContentByStage = collect($publishedEducationalContentByStage ?? []);
    $publishedEducationalContentByMonth = collect($publishedEducationalContentByMonth ?? []);

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
    $iconCheck = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>';
    $stageIcons = [
        'sprout' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21v-8"/><path d="M12 13c-4.6 0-7-2.4-7-7 4.6 0 7 2.4 7 7Z"/><path d="M12 13c0-4.6 2.4-7 7-7 0 4.6-2.4 7-7 7Z"/></svg>',
        'mother' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="6" r="3"/><path d="M9 21v-5a3 3 0 0 1 6 0v5"/><path d="M7 13c.8-3 2.4-4.5 5-4.5s4.2 1.5 5 4.5"/><path d="M9.5 16.5h5"/></svg>',
        'baby' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3"/><path d="M6.5 15.5c1.3-2.2 3.1-3.3 5.5-3.3s4.2 1.1 5.5 3.3"/><path d="M8 18c1.1 1.3 2.5 2 4 2s2.9-.7 4-2"/><path d="M10 8h.01"/><path d="M14 8h.01"/></svg>',
        'facility' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21V7l8-4 8 4v14"/><path d="M9 21v-7h6v7"/><path d="M12 7v5"/><path d="M9.5 9.5h5"/></svg>',
        'recovery' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 5.6a5.4 5.4 0 0 0-7.6 0L12 6.8l-1.2-1.2a5.4 5.4 0 1 0-7.6 7.6L12 22l8.8-8.8a5.4 5.4 0 0 0 0-7.6Z"/><path d="M8 12h2.5l1.2-2.4 2.1 5L15 12h1"/></svg>',
        'newborn' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 10.5a5 5 0 0 1 10 0v2.5a5 5 0 0 1-10 0Z"/><path d="M9 18.5 8 22"/><path d="m15 18.5 1 3.5"/><path d="M10 10h.01"/><path d="M14 10h.01"/><path d="M10.5 14c.9.7 2.1.7 3 0"/></svg>',
    ];
@endphp

@push('styles')
    <style>
        .kaalaman-timeline-shell { gap: 12px; padding: 16px; border-radius: 14px; }
        .kaalaman-timeline-header strong { font-size: 16px; }
        .kaalaman-timeline-count { min-height: 28px; padding: 0 10px; }
        .kaalaman-trimester-grid { gap: 10px; padding-bottom: 6px; }
        .kaalaman-trimester-card { flex-basis: calc((100% - 30px) / 4); gap: 10px; padding: 13px; border-radius: 12px; box-shadow: 0 8px 16px rgba(15, 23, 42, 0.06); }
        .kaalaman-stage-head { gap: 9px; }
        .kaalaman-stage-icon { width: 36px; height: 36px; border-radius: 10px; }
        .kaalaman-stage-icon svg { width: 19px; height: 19px; max-width: 19px; max-height: 19px; }
        .kaalaman-stage-kicker { margin-bottom: 3px; font-size: 9px; }
        .kaalaman-trimester-title { margin-bottom: 6px; font-size: 14px; }
        .kaalaman-pill { min-height: 22px; padding: 4px 9px; font-size: 9px; }
        .kaalaman-stage-progress { height: 7px; }
        .kaalaman-stage-percent { margin-top: 0; font-size: 9px; }
        .kaalaman-stage-bulletin { gap: 0; }
        .kaalaman-stage-bulletin > span { grid-template-columns: 8px minmax(0, 1fr); gap: 8px; padding: 9px 10px; border-radius: 9px; font-size: 11px; }
        .kaalaman-stage-bulletin strong, .kaalaman-stage-bulletin em { grid-column: 2; }
        .kaalaman-stage-bulletin strong { font-size: 9px; }
        .kaalaman-managed-content { display: grid; gap: 12px; padding: 16px; background: #ffffff; border: 1px solid #dde6f0; border-radius: 12px; box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05); }
        .kaalaman-managed-content-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .kaalaman-managed-content-head h3 { display: flex; align-items: center; gap: 8px; margin: 0; color: #030813; font-size: 14px; font-weight: 900; line-height: 1.25; }
        .kaalaman-managed-content-head span, .kaalaman-managed-meta span { display: inline-flex; align-items: center; min-height: 24px; padding: 0 9px; color: #d80b78; background: #ffe7f3; border-radius: 999px; font-size: 10px; font-weight: 900; }
        .kaalaman-managed-content-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; }
        .kaalaman-managed-card { display: grid; gap: 12px; min-width: 0; padding: 12px; background: #f8fafc; border: 1px solid #e5edf6; border-radius: 10px; }
        .kaalaman-managed-media-stack { display: grid; gap: 10px; min-width: 0; }
        .kaalaman-managed-media, .kaalaman-managed-placeholder { overflow: hidden; background: #071127; border-radius: 8px; }
        .kaalaman-managed-media iframe, .kaalaman-managed-media video, .kaalaman-managed-media img { display: block; width: 100%; max-width: 100%; border: 0; aspect-ratio: 16 / 9; object-fit: cover; }
        .kaalaman-managed-placeholder { display: grid; min-height: 136px; place-items: center; color: #ffffff; font-size: 12px; font-weight: 900; text-align: center; }
        .kaalaman-managed-copy { display: grid; gap: 7px; min-width: 0; }
        .kaalaman-managed-copy h4 { margin: 0; color: #030813; font-size: 14px; font-weight: 900; line-height: 1.25; overflow-wrap: anywhere; }
        .kaalaman-managed-copy p { margin: 0; color: #52627d; font-size: 12px; font-weight: 700; line-height: 1.45; overflow-wrap: anywhere; }
        .kaalaman-managed-output { padding-top: 3px; color: #334155 !important; }
        .kaalaman-month-body > .kaalaman-managed-content { margin-top: 12px; }
        .kaalaman-month-complete { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; margin-left: auto; color: #008f6b; background: #dcfce7; border: 1px solid #86efac; border-radius: 999px; }
        .kaalaman-month-complete[hidden] { display: none; }
        .kaalaman-month-complete svg { width: 16px; height: 16px; stroke: currentColor; stroke-width: 3; fill: none; }
        .kaalaman-progress-status, .kaalaman-video-status-pill, .kaalaman-infographic-status { display: inline-flex; align-items: center; gap: 6px; min-height: 28px; padding: 0 10px; color: #64748b; background: #eef2f7; border: 1px solid #dde6f0; border-radius: 999px; font-size: 11px; font-weight: 900; white-space: nowrap; }
        .kaalaman-progress-status.is-in-progress, .kaalaman-video-status-pill.is-in-progress, .kaalaman-infographic-status.is-in-progress { color: #1d4ed8; background: #eff6ff; border-color: #bfdbfe; }
        .kaalaman-progress-status.is-complete, .kaalaman-video-status-pill.is-complete, .kaalaman-infographic-status.is-complete { color: #008f6b; background: #dcfce7; border-color: #86efac; }
        .kaalaman-progress-status.is-saving, .kaalaman-video-status-pill.is-saving, .kaalaman-infographic-status.is-saving { color: #b45309; background: #fffbeb; border-color: #fde68a; }
        .kaalaman-status-card.is-progress { border-color: #bfdbfe; background: #eff6ff; }
        .kaalaman-status-card.is-complete { border-color: #86efac; background: #ecfdf5; }
        .kaalaman-status-card.is-needed { border-color: #facc15; background: #fffbeb; }
        .kaalaman-status-card.is-needed strong { color: #c2410c; }
        .kaalaman-video-complete-note { margin: 0; color: #52627d; font-size: 12px; font-weight: 800; }
        .kaalaman-upload-history { display: grid; gap: 10px; padding: 14px; margin-top: 12px; background: #ffffff; border: 1px solid #dde6f0; border-radius: 10px; }
        .kaalaman-upload-history h3 { display: flex; align-items: center; gap: 8px; margin: 0; color: #030813; font-size: 14px; font-weight: 900; }
        .kaalaman-upload-history-list { display: grid; gap: 8px; }
        .kaalaman-upload-history-item { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: center; gap: 10px; padding: 10px; background: #f8fafc; border: 1px solid #e5edf6; border-radius: 8px; }
        .kaalaman-upload-history-item strong { display: block; color: #030813; font-size: 13px; font-weight: 900; overflow-wrap: anywhere; }
        .kaalaman-upload-history-item span { display: block; margin-top: 3px; color: #52627d; font-size: 12px; font-weight: 800; overflow-wrap: anywhere; }
        .kaalaman-upload-delete { min-height: 34px; padding: 0 12px; color: #b91c1c; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 8px; font-size: 11px; font-weight: 900; cursor: pointer; }
        .kaalaman-upload-error { margin: 2px 0 0; padding: 9px 11px; color: #b91c1c; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 8px; font-size: 12px; font-weight: 900; }
        .kaalaman-upload-error[hidden] { display: none; }
        @media (max-width: 1024px) { .kaalaman-trimester-card { flex-basis: calc((100% - 10px) / 2); } }
        @media (max-width: 760px) { .kaalaman-trimester-card { flex-basis: 84%; } .kaalaman-managed-content { padding: 14px; } .kaalaman-managed-content-head { align-items: flex-start; flex-direction: column; } }
        @media (max-width: 420px) { .kaalaman-trimester-card { flex-basis: 100%; } .kaalaman-managed-content-grid { grid-template-columns: 1fr; } }
    </style>
@endpush

@section('content')
    <section class="kaalaman-shell" aria-label="INAY Kaalaman learning module">
        <div class="kaalaman-hero">
            <div>
                <h1>{!! $iconBook !!} INAY Kaalaman Learning Hub</h1>
                <p>Ang iyong gabay sa bawat buwan ng pagbubuntis. Alamin ang mga pagbabago sa iyong katawan, pag-unlad ng sanggol, wastong nutrisyon, at mahahalagang paalala upang manatiling ligtas kayong mag-ina.</p>
            </div>
            <div class="kaalaman-focus-card">
                <span class="kaalaman-eyebrow">Current Learning Focus</span>
                <strong data-kaalaman-focus>Month 1 (Weeks 1-4)</strong>
                <small>{{ $kaalamanOverallProgress['completed_months'] ?? 0 }} of {{ $kaalamanOverallProgress['total_months'] ?? 10 }} months completed</small>
            </div>
        </div>

        <div class="kaalaman-timeline-shell">
            <div class="kaalaman-timeline-header">
                <div>
                    <span class="kaalaman-eyebrow">Learning Path</span>
                    <strong>Pregnancy and newborn care path</strong>
                </div>
                <span class="kaalaman-timeline-count">6 care stages</span>
            </div>

            <div class="kaalaman-trimester-grid" role="tablist" aria-label="Maternal health learning timeline" data-stage-timeline>
                @foreach ($learningStages as $stageKey => $card)
                    <button class="kaalaman-trimester-card @if($loop->first) is-selected @endif" type="button" role="tab" aria-selected="{{ $loop->first ? 'true' : 'false' }}" data-stage-button="{{ $stageKey }}" data-focus="{{ $card['focus'] }}">
                        <span class="kaalaman-stage-head">
                            <span class="kaalaman-stage-icon" aria-hidden="true">{!! $stageIcons[$card['icon']] ?? $iconBook !!}</span>
                            <span>
                                <span class="kaalaman-stage-kicker">Care stage {{ $loop->iteration }}</span>
                                <span class="kaalaman-trimester-title">{{ $card['title'] }}</span>
                                <span class="kaalaman-pill @if(! $loop->first) is-muted @endif">{{ $card['label'] }}</span>
                            </span>
                        </span>
                        <span class="kaalaman-stage-progress" aria-hidden="true"><span style="width: {{ $card['progress'] }}%"></span></span>
                        <span class="kaalaman-stage-percent">{{ $card['progress'] }}% complete</span>
                        <span class="kaalaman-stage-bulletin">
                            <span><strong>Expected outcome</strong><em>{{ $card['output'] }}</em></span>
                        </span>
                    </button>
                @endforeach
            </div>

            <div class="kaalaman-slider-pagination" aria-label="Learning stage pagination" data-stage-pagination>
                @foreach ($learningStages as $stageKey => $card)
                    <button class="kaalaman-slider-dot @if($loop->first) is-active @endif" type="button" aria-label="Go to {{ $card['title'] }}" aria-current="{{ $loop->first ? 'true' : 'false' }}" data-stage-dot="{{ $stageKey }}"></button>
                @endforeach
            </div>
        </div>

        <div class="kaalaman-months" aria-live="polite">
            @foreach ($learningStages as $stageKey => $card)
                @php
                    $stageEducationalItems = collect($publishedEducationalContentByStage->get($stageKey, []))
                        ->filter(fn ($content) => $content->month === null);
                @endphp
                <div class="@if(! $loop->first) is-hidden @endif" data-stage-detail="{{ $stageKey }}">
                    @include('modules.partials.educational-content-cards', [
                        'items' => $stageEducationalItems,
                        'heading' => $card['title'].' educational content',
                        'iconVideo' => $iconVideo,
                        'iconFile' => $iconFile,
                    ])
                </div>
            @endforeach

            @foreach ($continuumPanels as $stageKey => $panel)
                <section class="kaalaman-stage-detail is-hidden" data-stage-detail="{{ $stageKey }}">
                    <div class="kaalaman-stage-detail-head">
                        <span class="kaalaman-stage-icon is-large" aria-hidden="true">{!! $stageIcons[$panel['icon']] ?? $iconBook !!}</span>
                        <div>
                            <span class="kaalaman-pill">{{ $panel['label'] }}</span>
                            <h2>{{ $panel['title'] }}</h2>
                            <p>{{ $panel['summary'] }}</p>
                        </div>
                    </div>

                    <div class="kaalaman-topic-grid" aria-label="{{ $panel['title'] }} topics">
                        @foreach ($panel['topics'] as $topic)
                            <span>{{ $topic }}</span>
                        @endforeach
                    </div>

                    <section class="kaalaman-read-panel">
                        <div>
                            <h3>{!! $iconBook !!} Reading Checkpoint</h3>
                            <p>Review these topics to prepare for this care stage.</p>
                        </div>
                        <span class="kaalaman-progress-status">Not Started</span>
                    </section>

                    <div class="kaalaman-status-grid">
                        <div class="kaalaman-status-card"><span class="kaalaman-status-mini-icon">{!! $iconBook !!}</span><div><span>Reading</span><strong>Pending</strong></div></div>
                        <div class="kaalaman-status-card"><span class="kaalaman-status-mini-icon is-blue">{!! $iconVideo !!}</span><div><span>Videos</span><strong>0/{{ count($panel['topics']) }}</strong></div></div>
                        <div class="kaalaman-status-card is-needed"><span class="kaalaman-status-mini-icon is-warning">{!! $iconTask !!}</span><div><span>Medical Tasks</span><strong>Needed</strong></div></div>
                    </div>

                    <div class="kaalaman-task-list">
                        @foreach ($panel['tasks'] as $task)
                            <article class="kaalaman-task-card">
                                <span class="kaalaman-task-icon">{!! $iconFile !!}</span>
                                <div><h4>{{ $task }}</h4><p><strong>Timing: {{ $panel['label'] }}</strong><br>Importance: Keeps mother and baby care aligned with the next health visit.</p></div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach

            @foreach ($months as $month)
                @php
                    $monthUploads = collect($kaalamanUploads->get($month['month'], []));
                    $monthEducationalItems = collect($publishedEducationalContentByMonth->get($month['month'], []));
                    $uploadedCount = $monthUploads->count();
                    $monthProgress = $kaalamanMonthlyProgress['months'][$month['month']] ?? [];
                    $readingProgress = $monthProgress['reading'] ?? ['status' => 'not_started', 'label' => 'Not Started', 'completed_at' => null];
                    $videoProgressItems = collect($monthProgress['videos'] ?? []);
                    $infographicProgress = $monthProgress['infographic'] ?? ['status' => 'not_started', 'label' => 'Not Started', 'completed_at' => null];
                    $prenatalDocumentProgress = collect($monthProgress['documents'] ?? [])->firstWhere('type', 'Prenatal Records and Receipts') ?? ['uploaded' => false, 'label' => 'Prenatal Records and Receipts'];
                    $hasPrenatalUpload = (bool) ($prenatalDocumentProgress['uploaded'] ?? false);
                    $requirementsComplete = (bool) ($monthProgress['is_complete'] ?? false);
                    $monthStatusClass = $requirementsComplete ? 'is-complete' : ((($monthProgress['completed_count'] ?? 0) > 0) ? 'is-progress' : 'is-needed');
                @endphp
                <details class="kaalaman-month @if($month['stage'] !== 'first-trimester') is-hidden @endif" data-stage-detail="{{ $month['stage'] }}" data-month="{{ $month['month'] }}" @if($month['month'] === 1) open @endif>
                    <summary class="kaalaman-month-summary">
                        <span class="kaalaman-month-badge">Month {{ $month['month'] }}</span>
                        <span>
                            <span class="kaalaman-month-title">{{ $month['title'] }} <span>({{ $month['weeks'] }})</span></span>
                            <span class="kaalaman-month-hint">{{ $month['current'] ? 'Kasalukuyang buwan mo ito' : 'Open details' }}</span>
                        </span>
                        <span class="kaalaman-month-complete" data-month-complete @if(! $requirementsComplete) hidden @endif>{!! $iconCheck !!}</span>
                        <span class="kaalaman-chevron">{!! $iconChevron !!}</span>
                    </summary>

                    <div class="kaalaman-month-body">
                        <div class="kaalaman-info-grid">
                            <article class="kaalaman-info-card"><h3>{!! $iconHeart !!} Pag-unlad ng Sanggol (Baby Development)</h3><p>{{ $month['baby'] }}</p></article>
                            <article class="kaalaman-info-card is-blue"><h3>{!! $iconPulse !!} Pagbabago sa Katawan ng Ina (Maternal Changes)</h3><p>{{ $month['mother'] }}</p></article>
                            <article class="kaalaman-info-card"><h3>Inaasahang Sintomas sa Buwang Ito</h3><p>{{ $month['symptoms'] }}</p></article>
                            <article class="kaalaman-info-card is-blue"><h3>Gabay sa Wastong Nutrisyon (Nutritional Guidance)</h3><p>{{ $month['nutrition'] }}</p></article>
                        </div>

                        <section class="kaalaman-read-panel" data-reading-panel data-month="{{ $month['month'] }}" data-item-key="month-{{ $month['month'] }}-reading" data-current-status="{{ $readingProgress['status'] }}">
                            <div>
                                <h3>{!! $iconBook !!} Gabay sa Pagbabasa para sa Buwan {{ $month['month'] }}</h3>
                                <p data-reading-copy>{{ $readingProgress['status'] === 'read' ? 'Reading guide completed and saved.' : 'I-click ang Mark as Read kapag natapos mo nang basahin ang gabay sa buwang ito.' }}</p>
                            </div>
                            <button class="kaalaman-button {{ $readingProgress['status'] === 'read' ? 'is-done' : '' }}" type="button" data-reading-button @if($readingProgress['status'] === 'read') disabled @endif>{{ $readingProgress['status'] === 'read' ? 'Marked as Read' : 'Mark as Read' }}</button>
                        </section>

                        @include('modules.partials.educational-content-cards', [
                            'items' => $monthEducationalItems,
                            'heading' => 'Published Lessons for Month '.$month['month'],
                            'iconVideo' => $iconVideo,
                            'iconFile' => $iconFile,
                        ])

                        <div class="kaalaman-divider"></div>

                        <h3 class="kaalaman-section-title">{!! $iconVideo !!} Mga Kaugnay na Video at Edukasyon mula sa Barangay</h3>
                        <div class="kaalaman-video-grid">
                            @foreach ($month['videos'] as $video)
                                @php
                                    $videoIndex = $loop->index;
                                    $videoProgress = $videoProgressItems->firstWhere('key', "month-{$month['month']}-video-{$videoIndex}") ?? ['status' => 'not_started', 'label' => 'Not Started', 'completed_at' => null];
                                    $videoUrl = $video['url'] ?? 'https://www.youtube.com/results?search_query='.urlencode($video['title'].' pregnancy education');
                                @endphp
                                <article class="kaalaman-video-card is-clickable" role="button" tabindex="0" data-video-card data-month="{{ $month['month'] }}" data-video-key="month-{{ $month['month'] }}-video-{{ $videoIndex }}" data-current-status="{{ $videoProgress['status'] }}" data-video-title="{{ $video['title'] }}" data-video-meta="{{ $video['tag'] }} - {{ $video['time'] }}" data-video-url="{{ $videoUrl }}">
                                    <div class="kaalaman-video-top">
                                        <span class="kaalaman-video-play">{!! $iconVideo !!}</span>
                                        <span class="kaalaman-video-status-pill {{ $videoProgress['status'] === 'watched' ? 'is-complete' : ($videoProgress['status'] === 'in_progress' ? 'is-in-progress' : '') }}" data-video-status>{{ $videoProgress['label'] }}</span>
                                    </div>
                                    <h3>{{ $video['title'] }}</h3>
                                    <div class="kaalaman-video-meta"><strong>{{ $video['tag'] }}</strong><span>{{ $video['time'] }}</span></div>
                                </article>
                            @endforeach
                        </div>

                        <a class="kaalaman-button kaalaman-more" href="{{ route('inay-kaalaman.videos', ['month' => $month['month']]) }}">View More Videos</a>

                        <section class="kaalaman-status-panel">
                            <h3 class="kaalaman-section-title">{!! $iconTask !!} Documentation Status</h3>
                            <p>This shows reading, video, and uploaded-document progress for this month.</p>
                            <div class="kaalaman-status-grid">
                                <div class="kaalaman-status-card {{ $readingProgress['status'] === 'read' ? 'is-complete' : ($readingProgress['status'] === 'in_progress' ? 'is-progress' : '') }}"><span class="kaalaman-status-mini-icon">{!! $iconBook !!}</span><div><span>Reading</span><strong data-status-reading>{{ $readingProgress['label'] }}</strong></div></div>
                                <div class="kaalaman-status-card {{ ($monthProgress['watched_videos'] ?? 0) >= ($monthProgress['total_videos'] ?? 2) ? 'is-complete' : (($monthProgress['watched_videos'] ?? 0) > 0 ? 'is-progress' : '') }}"><span class="kaalaman-status-mini-icon is-blue">{!! $iconVideo !!}</span><div><span>Videos</span><strong data-status-videos>{{ $monthProgress['watched_videos'] ?? 0 }}/{{ $monthProgress['total_videos'] ?? 2 }} Watched</strong></div></div>
                                <div class="kaalaman-status-card {{ $infographicProgress['status'] === 'reviewed' ? 'is-complete' : ($infographicProgress['status'] === 'in_progress' ? 'is-progress' : 'is-needed') }}"><span class="kaalaman-status-mini-icon {{ $infographicProgress['status'] === 'reviewed' ? 'is-success' : 'is-warning' }}">{!! $iconFile !!}</span><div><span>Infographic</span><strong data-status-infographic>{{ $infographicProgress['label'] }}</strong></div></div>
                                <div class="kaalaman-status-card {{ $hasPrenatalUpload ? 'is-complete' : 'is-needed' }}"><span class="kaalaman-status-mini-icon {{ $hasPrenatalUpload ? 'is-success' : 'is-warning' }}">{!! $iconTask !!}</span><div><span>Prenatal Records and Receipts</span><strong>{{ $hasPrenatalUpload ? 'Uploaded' : 'Needed' }}</strong></div></div>
                            </div>
                        </section>

                        <div class="kaalaman-divider"></div>

                        <section class="kaalaman-risk-panel">
                            <h3>{!! $iconAlert !!} Mahahalagang Babala at Panganib (Risk Alerts & Consequences)</h3>
                            <div class="kaalaman-risk-item">
                                <span class="kaalaman-risk-icon">{!! $iconAlert !!}</span>
                                <p class="kaalaman-risk-copy"><strong class="kaalaman-risk-name">{{ $month['risk'] }}</strong><strong>Possible consequence:</strong> This may indicate an urgent condition. <br><strong>Recommendation:</strong> Seek immediate care at the nearest Barangay Health Station, RHU, or hospital.</p>
                            </div>
                        </section>

                        <div class="kaalaman-lower-grid">
                            <section>
                                <h3 class="kaalaman-task-title">{!! $iconTask !!} Mga Bakuna at Gawaing Medikal para sa Buwang Ito</h3>
                                <div class="kaalaman-task-list">
                                    <article class="kaalaman-task-card">
                                        <span class="kaalaman-task-icon">{!! $iconFile !!}</span>
                                        <div><h4>Routine Prenatal Checkup and Vital Signs</h4><p><strong>Timing: Complete within Month {{ $month['month'] }}</strong><br>Importance: Confirms maternal health, baby growth, and early warning signs.</p></div>
                                    </article>
                                    <article class="kaalaman-task-card">
                                        <span class="kaalaman-task-icon">{!! $iconFile !!}</span>
                                        <div><h4>Supplement Prescription or Refill Record</h4><p><strong>Timing: Bring your latest prescription to your checkup</strong><br>Importance: Helps your Program Staff confirm the medicine or supplements you need.</p></div>
                                    </article>
                                </div>
                            </section>

                            <section>
                                <h3 class="kaalaman-task-title">{!! $iconBook !!} Mga Infographic</h3>
                                <article class="kaalaman-infographic-card is-clickable" role="button" tabindex="0" data-infographic-card data-month="{{ $month['month'] }}" data-infographic-key="month-{{ $month['month'] }}-infographic" data-current-status="{{ $infographicProgress['status'] }}" data-infographic-title="{{ $month['title'] }} Checklist" data-infographic-meta="Maternal Care - Month {{ $month['month'] }}" data-pdf-url="{{ route('inay-kaalaman.infographic.pdf', ['month' => $month['month']]) }}">
                                    <span class="kaalaman-infographic-icon">{!! $iconFile !!}</span>
                                    <div><strong>Maternal Care</strong><h4>{{ $month['title'] }} Checklist</h4></div>
                                    <span class="kaalaman-infographic-status {{ $infographicProgress['status'] === 'reviewed' ? 'is-complete' : ($infographicProgress['status'] === 'in_progress' ? 'is-in-progress' : '') }}" data-infographic-status>{{ $infographicProgress['label'] }}</span>
                                </article>
                            </section>
                        </div>

                        <form class="kaalaman-upload-panel" method="POST" action="{{ route('inay-kaalaman.upload') }}" enctype="multipart/form-data" data-upload-form novalidate>
                            @csrf
                            <input type="hidden" name="month" value="{{ $month['month'] }}">
                            <h3 class="kaalaman-upload-title">{!! $iconUpload !!} Upload Prenatal Records and Receipts</h3>
                            <div class="kaalaman-upload-row">
                                <div class="kaalaman-upload-field"><label>Record Type</label><select name="record_type" required><option value="Prenatal Records and Receipts">Prenatal Records and Receipts</option><option value="Certificate">Certificate</option><option value="Other Documents">Other Supporting Document</option></select></div>
                                <div class="kaalaman-upload-field">
                                    <label>Select Document</label>
                                    <label class="kaalaman-file-picker">
                                        <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required data-upload-input>
                                        <span>Choose file</span>
                                        <strong data-upload-file>No file chosen</strong>
                                    </label>
                                </div>
                                <div class="kaalaman-upload-actions">
                                    <button class="kaalaman-button kaalaman-upload-button" type="submit">{!! $iconUpload !!} Upload to Records</button>
                                    <button class="kaalaman-button secondary kaalaman-upload-cancel" type="button" data-upload-cancel hidden>Cancel</button>
                                </div>
                            </div>
                            <p class="kaalaman-upload-error" data-upload-error hidden>Please select a file before uploading.</p>
                        </form>

                        <section class="kaalaman-upload-history">
                            <h3>{!! $iconFile !!} Prenatal Records and Receipts History</h3>
                            @if ($monthUploads->isEmpty())
                                <p class="kaalaman-video-complete-note">No prenatal records or receipts have been uploaded for this month yet.</p>
                            @else
                                <div class="kaalaman-upload-history-list">
                                    @foreach ($monthUploads as $upload)
                                        <article class="kaalaman-upload-history-item">
                                            <div>
                                                <strong>{{ $upload->original_name }}</strong>
                                                <span>{{ in_array($upload->record_type, ['Checkup Records', 'Prescription', 'Receipts'], true) ? 'Prenatal Records and Receipts' : $upload->record_type }} &middot; Uploaded {{ $upload->created_at?->format('M j, Y, g:i A') ?? 'date not recorded' }}</span>
                                            </div>
                                            <form method="POST" action="{{ route('inay-kaalaman.upload.delete', $upload) }}" onsubmit="return confirm('Delete this uploaded record?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="kaalaman-upload-delete" type="submit">Delete</button>
                                            </form>
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                        </section>

                        <section class="kaalaman-note-panel">
                            <h3>{!! $iconAlert !!} Program Staff Notes (Payo mula sa Barangay Midwife)</h3>
                            <p>Great job. Your upcoming checkups are scheduled every two weeks starting this month. Keep practicing regular deep-breathing exercises.</p>
                        </section>

                        <section class="kaalaman-complete-panel">
                            <span class="kaalaman-complete-icon">{!! $iconShield !!}</span>
                            <div><h3 data-completion-title>{{ $requirementsComplete ? 'Month Completed' : 'Complete This Month\'s Requirements' }}</h3><p data-completion-copy>Reading: {{ $readingProgress['label'] }}. Videos: {{ $monthProgress['watched_videos'] ?? 0 }}/{{ $monthProgress['total_videos'] ?? 2 }} watched. Infographic: {{ $infographicProgress['label'] }}. Documents: {{ $monthProgress['uploaded_required_documents'] ?? 0 }}/{{ $monthProgress['required_documents'] ?? 2 }} uploaded. Overall Status: {{ $monthProgress['status'] ?? 'Not Started' }}. {{ $uploadedCount }} saved upload{{ $uploadedCount === 1 ? '' : 's' }}.</p></div>
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
            <p class="kaalaman-video-complete-note" data-video-complete-note>When you finish watching, save the video as watched.</p>
            <div class="kaalaman-modal-actions">
                <a class="kaalaman-button" href="#" target="_blank" rel="noopener" data-video-modal-link>Open YouTube</a>
                <button class="kaalaman-button" type="button" data-video-complete>Save as Watched</button>
                <button class="kaalaman-button secondary" type="button" data-video-close>Close</button>
            </div>
        </section>
    </div>

    <div class="kaalaman-video-modal" data-infographic-modal hidden>
        <div class="kaalaman-video-backdrop" data-infographic-close></div>
        <section class="kaalaman-video-dialog" role="dialog" aria-modal="true" aria-labelledby="kaalaman-infographic-title">
            <button class="kaalaman-video-close" type="button" aria-label="Close infographic" data-infographic-close>&times;</button>
            <span class="kaalaman-infographic-icon is-large">{!! $iconFile !!}</span>
            <p class="kaalaman-eyebrow">Monthly Infographic</p>
            <h2 id="kaalaman-infographic-title" data-infographic-modal-title>Checklist</h2>
            <p data-infographic-modal-meta></p>
            <p class="kaalaman-video-modal-copy">Review this checklist in a printable PDF format for your monthly maternal care reminders.</p>
            <div class="kaalaman-modal-actions">
                <a class="kaalaman-button" href="#" target="_blank" rel="noopener" data-infographic-modal-link>Open PDF</a>
                <button class="kaalaman-button" type="button" data-infographic-complete>Save as Reviewed</button>
                <button class="kaalaman-button secondary" type="button" data-infographic-close>Close</button>
            </div>
        </section>
    </div>

    <script>
        const kaalamanProgressUrl = @json(route('inay-kaalaman.progress'));
        const kaalamanCsrfToken = @json(csrf_token());
        const kaalamanStatusLabels = {
            not_started: 'Not Started',
            in_progress: 'In Progress',
            saving: 'Saving...',
            read: 'Read',
            watched: 'Watched',
            reviewed: 'Reviewed',
        };

        const statusClass = (status) => {
            if (['read', 'watched', 'reviewed'].includes(status)) return 'is-complete';
            if (status === 'in_progress') return 'is-in-progress';
            if (status === 'saving') return 'is-saving';

            return '';
        };

        const setStatusPill = (element, status, label = null) => {
            if (!element) return;

            element.classList.remove('is-complete', 'is-in-progress', 'is-saving');
            const nextClass = statusClass(status);
            if (nextClass) element.classList.add(nextClass);
            element.textContent = label || kaalamanStatusLabels[status] || status;
        };

        const saveKaalamanProgress = async (payload) => {
            const response = await fetch(kaalamanProgressUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': kaalamanCsrfToken,
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                let message = 'Could not save INAY Kaalaman progress.';
                try {
                    const error = await response.json();
                    message = error.message || message;
                } catch (error) {
                    // Keep the generic message when the server returns non-JSON.
                }

                throw new Error(message);
            }

            return response.json();
        };

        const updateMonthSummary = (summary) => {
            if (!summary?.month) return;

            const monthPanel = document.querySelector(`[data-month="${summary.month}"]`);
            if (!monthPanel) return;

            const completeBadge = monthPanel.querySelector('[data-month-complete]');
            if (completeBadge) completeBadge.hidden = !summary.is_complete;

            const readingStatus = monthPanel.querySelector('[data-status-reading]');
            if (readingStatus) readingStatus.textContent = summary.reading?.label || 'Not Started';

            const readingButton = monthPanel.querySelector('[data-reading-button]');
            if (readingButton && summary.reading?.status === 'read') {
                readingButton.textContent = 'Marked as Read';
                readingButton.disabled = true;
                readingButton.classList.add('is-done');
            }

            const readingCopy = monthPanel.querySelector('[data-reading-copy]');
            if (readingCopy && summary.reading?.status === 'read') {
                readingCopy.textContent = 'Reading guide completed and saved.';
            }

            const videoStatus = monthPanel.querySelector('[data-status-videos]');
            if (videoStatus) videoStatus.textContent = `${summary.watched_videos || 0}/${summary.total_videos || 0} Watched`;

            const infographicStatus = monthPanel.querySelector('[data-status-infographic]');
            if (infographicStatus) infographicStatus.textContent = summary.infographic?.label || 'Not Started';

            const title = monthPanel.querySelector('[data-completion-title]');
            if (title) title.textContent = summary.is_complete ? 'Month Completed' : 'Complete This Month\'s Requirements';

            const copy = monthPanel.querySelector('[data-completion-copy]');
            if (copy) {
                const uploadedCount = summary.uploaded_documents?.length || 0;
                copy.textContent = `Reading: ${summary.reading?.label || 'Not Started'}. Videos: ${summary.watched_videos || 0}/${summary.total_videos || 0} watched. Infographic: ${summary.infographic?.label || 'Not Started'}. Documents: ${summary.uploaded_required_documents || 0}/${summary.required_documents || 0} uploaded. Overall Status: ${summary.status || 'Not Started'}. ${uploadedCount} saved upload${uploadedCount === 1 ? '' : 's'}.`;
            }
        };

        document.querySelectorAll('[data-reading-panel]').forEach((panel) => {
            const button = panel.querySelector('[data-reading-button]');
            const copy = panel.querySelector('[data-reading-copy]');
            let currentStatus = panel.dataset.currentStatus || 'not_started';

            if (!button) return;

            button.addEventListener('click', async () => {
                if (currentStatus === 'read') return;

                const previousText = button.textContent;
                button.disabled = true;
                button.textContent = 'Saving...';

                try {
                    const data = await saveKaalamanProgress({
                        month: Number(panel.dataset.month),
                        activity_type: 'reading',
                        item_key: panel.dataset.itemKey,
                        item_title: `Month ${panel.dataset.month} Reading Guide`,
                        status: 'read',
                    });

                    currentStatus = 'read';
                    panel.dataset.currentStatus = 'read';
                    button.textContent = 'Marked as Read';
                    button.classList.add('is-done');
                    if (copy) copy.textContent = 'Reading guide completed and saved.';
                    updateMonthSummary(data.month_summary);
                } catch (error) {
                    button.disabled = false;
                    button.textContent = previousText;
                    window.alert(error.message);
                }
            });
        });

        const stageButtons = Array.from(document.querySelectorAll('[data-stage-button]'));
        const stageDots = Array.from(document.querySelectorAll('[data-stage-dot]'));
        const stageTimeline = document.querySelector('[data-stage-timeline]');
        const stageDetails = document.querySelectorAll('[data-stage-detail]');
        const focusLabel = document.querySelector('[data-kaalaman-focus]');
        let stageIgnoreScrollUntil = 0;

        const scrollStageIntoPlace = (button) => {
            if (!stageTimeline || !button) return;

            const maxScroll = Math.max(0, stageTimeline.scrollWidth - stageTimeline.clientWidth);
            const nextScroll = button.offsetLeft - ((stageTimeline.clientWidth - button.offsetWidth) / 2);
            const clampedScroll = Math.min(Math.max(nextScroll, 0), maxScroll);

            stageTimeline.scrollTo({
                left: clampedScroll,
                behavior: 'smooth',
            });
        };

        const selectLearningStage = (button, shouldSlide = true) => {
            if (!button) return;

            const stage = button.dataset.stageButton;

            stageButtons.forEach((item) => {
                const isSelected = item === button;
                item.classList.toggle('is-selected', isSelected);
                item.setAttribute('aria-selected', isSelected ? 'true' : 'false');
                item.querySelector('.kaalaman-pill')?.classList.toggle('is-muted', !isSelected);
            });

            stageDots.forEach((dot) => {
                const isSelected = dot.dataset.stageDot === stage;
                dot.classList.toggle('is-active', isSelected);
                dot.setAttribute('aria-current', isSelected ? 'true' : 'false');
            });

            let openedFirstDetail = false;
            stageDetails.forEach((panel) => {
                const isVisible = panel.dataset.stageDetail === stage;
                const isDetails = panel.tagName.toLowerCase() === 'details';

                panel.classList.toggle('is-hidden', !isVisible);

                if (isDetails) {
                    panel.open = isVisible && !openedFirstDetail;
                    if (isVisible && !openedFirstDetail) openedFirstDetail = true;
                }
            });

            if (focusLabel) focusLabel.textContent = button.dataset.focus;
            if (shouldSlide) {
                stageIgnoreScrollUntil = Date.now() + 700;
                scrollStageIntoPlace(button);
            }
        };

        stageButtons.forEach((button) => {
            button.addEventListener('click', () => selectLearningStage(button));
        });

        stageDots.forEach((dot) => {
            dot.addEventListener('click', () => {
                const button = stageButtons.find((item) => item.dataset.stageButton === dot.dataset.stageDot);
                selectLearningStage(button);
            });
        });

        let stageScrollFrame = null;
        stageTimeline?.addEventListener('scroll', () => {
            if (Date.now() < stageIgnoreScrollUntil) return;
            if (stageScrollFrame) return;

            stageScrollFrame = window.requestAnimationFrame(() => {
                stageScrollFrame = null;

                const timelineRect = stageTimeline.getBoundingClientRect();
                const timelineCenter = timelineRect.left + (timelineRect.width / 2);
                const nearestButton = stageButtons.reduce((nearest, button) => {
                    const currentRect = button.getBoundingClientRect();
                    const nearestRect = nearest.getBoundingClientRect();
                    const currentDistance = Math.abs((currentRect.left + (currentRect.width / 2)) - timelineCenter);
                    const nearestDistance = Math.abs((nearestRect.left + (nearestRect.width / 2)) - timelineCenter);

                    return currentDistance < nearestDistance ? button : nearest;
                }, stageButtons[0]);

                selectLearningStage(nearestButton, false);
            });
        }, { passive: true });

        stageDetails.forEach((panel) => {
            if (panel.tagName.toLowerCase() !== 'details') return;

            panel.addEventListener('toggle', () => {
                if (!panel.open) return;

                stageDetails.forEach((otherPanel) => {
                    if (otherPanel !== panel && otherPanel.tagName.toLowerCase() === 'details' && otherPanel.dataset.stageDetail === panel.dataset.stageDetail) {
                        otherPanel.open = false;
                    }
                });
            });
        });

        const videoModal = document.querySelector('[data-video-modal]');
        const videoModalTitle = document.querySelector('[data-video-modal-title]');
        const videoModalMeta = document.querySelector('[data-video-modal-meta]');
        const videoModalLink = document.querySelector('[data-video-modal-link]');
        const videoCompleteButton = document.querySelector('[data-video-complete]');
        const videoCompleteNote = document.querySelector('[data-video-complete-note]');
        let activeVideoCard = null;

        const setVideoCardStatus = (card, status) => {
            if (!card) return;

            card.dataset.currentStatus = status;
            setStatusPill(card.querySelector('[data-video-status]'), status);
        };

        const saveVideoCardStatus = async (card, status) => {
            if (!card) return;

            const previousStatus = card.dataset.currentStatus || 'not_started';

            try {
                if (status === 'watched') {
                    setStatusPill(card.querySelector('[data-video-status]'), 'saving');
                    if (videoCompleteButton) videoCompleteButton.disabled = true;
                } else {
                    setVideoCardStatus(card, 'in_progress');
                }

                const data = await saveKaalamanProgress({
                    month: Number(card.dataset.month),
                    activity_type: 'video',
                    item_key: card.dataset.videoKey,
                    item_title: card.dataset.videoTitle || 'Educational video',
                    status,
                });

                setVideoCardStatus(card, data.progress?.status || status);
                updateMonthSummary(data.month_summary);
            } catch (error) {
                setVideoCardStatus(card, previousStatus);
                window.alert(error.message);
            } finally {
                if (videoCompleteButton) videoCompleteButton.disabled = false;
            }
        };

        const openVideoModal = (card) => {
            if (!videoModal || !videoModalTitle || !videoModalMeta || !videoModalLink) return;

            activeVideoCard = card;
            videoModalTitle.textContent = card.dataset.videoTitle || 'Educational video';
            videoModalMeta.textContent = card.dataset.videoMeta || '';
            videoModalLink.href = card.dataset.videoUrl || '#';
            if (videoCompleteButton) videoCompleteButton.disabled = card.dataset.currentStatus === 'watched';
            if (videoCompleteNote) {
                videoCompleteNote.textContent = card.dataset.currentStatus === 'watched'
                    ? 'This video is already saved as watched.'
                    : 'When you finish watching, save the video as watched.';
            }
            videoModal.hidden = false;
            document.body.classList.add('has-kaalaman-modal');
            videoModalLink.focus();

            if (card.dataset.currentStatus !== 'watched' && card.dataset.currentStatus !== 'in_progress') {
                saveVideoCardStatus(card, 'in_progress');
            }
        };

        const closeVideoModal = () => {
            if (!videoModal) return;

            videoModal.hidden = true;
            document.body.classList.remove('has-kaalaman-modal');
        };

        videoCompleteButton?.addEventListener('click', () => {
            if (!activeVideoCard) return;
            saveVideoCardStatus(activeVideoCard, 'watched');
            if (videoCompleteNote) videoCompleteNote.textContent = 'Video progress saved as watched.';
        });

        document.querySelectorAll('[data-video-card]').forEach((card) => {
            card.addEventListener('click', (event) => {
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
        const infographicCompleteButton = document.querySelector('[data-infographic-complete]');
        let activeInfographicCard = null;

        const setInfographicStatus = (card, status) => {
            if (!card) return;

            card.dataset.currentStatus = status;
            setStatusPill(card.querySelector('[data-infographic-status]'), status);
        };

        const saveInfographicStatus = async (card, status) => {
            if (!card) return;

            const previousStatus = card.dataset.currentStatus || 'not_started';

            try {
                if (status === 'reviewed') {
                    setStatusPill(card.querySelector('[data-infographic-status]'), 'saving');
                    if (infographicCompleteButton) infographicCompleteButton.disabled = true;
                } else {
                    setInfographicStatus(card, 'in_progress');
                }

                const data = await saveKaalamanProgress({
                    month: Number(card.dataset.month),
                    activity_type: 'infographic',
                    item_key: card.dataset.infographicKey,
                    item_title: card.dataset.infographicTitle || 'Monthly infographic',
                    status,
                });

                setInfographicStatus(card, data.progress?.status || status);
                updateMonthSummary(data.month_summary);
            } catch (error) {
                setInfographicStatus(card, previousStatus);
                window.alert(error.message);
            } finally {
                if (infographicCompleteButton) infographicCompleteButton.disabled = false;
            }
        };

        const openInfographicModal = (card) => {
            if (!infographicModal || !infographicModalTitle || !infographicModalMeta || !infographicModalLink) return;

            activeInfographicCard = card;
            infographicModalTitle.textContent = card.dataset.infographicTitle || 'Maternal care checklist';
            infographicModalMeta.textContent = card.dataset.infographicMeta || '';
            infographicModalLink.href = card.dataset.pdfUrl || '#';
            if (infographicCompleteButton) infographicCompleteButton.disabled = card.dataset.currentStatus === 'reviewed';
            infographicModal.hidden = false;
            document.body.classList.add('has-kaalaman-modal');
            infographicModalLink.focus();

            if (card.dataset.currentStatus !== 'reviewed' && card.dataset.currentStatus !== 'in_progress') {
                saveInfographicStatus(card, 'in_progress');
            }
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

        infographicCompleteButton?.addEventListener('click', () => {
            if (!activeInfographicCard) return;
            saveInfographicStatus(activeInfographicCard, 'reviewed');
        });

        document.querySelectorAll('[data-upload-form]').forEach((form) => {
            const input = form.querySelector('[data-upload-input]');
            const filename = form.querySelector('[data-upload-file]');
            const cancel = form.querySelector('[data-upload-cancel]');
            const error = form.querySelector('[data-upload-error]');

            const refreshUploadState = () => {
                const file = input?.files?.[0] || null;
                if (filename) filename.textContent = file ? file.name : 'No file chosen';
                if (cancel) cancel.hidden = !file;
                if (error) error.hidden = true;
                form.classList.toggle('has-file', Boolean(file));
            };

            input?.addEventListener('change', refreshUploadState);
            cancel?.addEventListener('click', () => {
                if (input) input.value = '';
                refreshUploadState();
            });

            form.addEventListener('submit', (event) => {
                const file = input?.files?.[0] || null;

                if (file) return;

                event.preventDefault();
                if (error) {
                    error.hidden = false;
                    error.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
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
