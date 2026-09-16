@extends('layouts.app')

@section('title', 'INAY Kaalaman - Project INAY')
@section('portal_title', 'INAY Kaalaman')
@section('body_class', 'kaalaman-page')

@php
    $learningStages = [
        'first-trimester' => ['label' => 'Months 1-3', 'title' => '1st Trimester', 'icon' => 'sprout', 'progress' => 0, 'focus' => 'Month 1 (Weeks 1-4)', 'output' => 'Early pregnancy foundation, prenatal checkup readiness, nutrition and warning signs.'],
        'second-trimester' => ['label' => 'Months 4-6', 'title' => '2nd Trimester', 'icon' => 'mother', 'progress' => 0, 'focus' => 'Month 4 (Weeks 13-16)', 'output' => 'Growth screening, movement awareness, supplement continuity and safer daily routines.'],
        'third-trimester' => ['label' => 'Months 7-9', 'title' => '3rd Trimester', 'icon' => 'baby', 'progress' => 0, 'focus' => 'Month 7 (Weeks 28-31)', 'output' => 'Birth readiness, final checkups, danger sign monitoring and emergency planning.'],
        'labor-delivery' => ['label' => 'Reproductive Health', 'title' => 'Family Planning', 'icon' => 'planning', 'progress' => 0, 'focus' => 'Planning and Counseling', 'output' => 'Informed family planning goals, method counseling, questions for a provider and follow-up support.'],
        'postpartum-care' => ['label' => 'Birth to 6 Weeks After Delivery', 'title' => 'Postpartum Care', 'icon' => 'recovery', 'progress' => 0, 'focus' => 'Birth to 6 Weeks After Delivery', 'output' => 'Mother recovery checklist, breastfeeding support, family planning and follow-up care.'],
        'neonatal-care' => ['label' => '0-28 Days After Birth', 'title' => 'Neonatal Care', 'icon' => 'newborn', 'progress' => 0, 'focus' => '0-28 Days After Birth', 'output' => 'Newborn screening, immunization, feeding, cord care and growth monitoring.'],
    ];

    $continuumPanels = [
        'labor-delivery' => [
            'title' => 'Family Planning',
            'label' => 'Reproductive Health',
            'icon' => 'planning',
            'summary' => 'Support informed and voluntary choices by reviewing reproductive goals, available family planning methods, personal health considerations, and follow-up care with a qualified provider.',
            'topics' => ['Reproductive goals', 'Informed choice', 'Temporary methods', 'Long-acting methods', 'Permanent methods', 'Condoms and STI protection'],
            'tasks' => ['Discuss reproductive goals and health history with a qualified healthcare provider.', 'Compare available methods, benefits, possible side effects, and correct use.', 'Prepare questions and arrange a family planning counseling visit.'],
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

    $requiredTrimesterVideos = [
        1 => ['key' => 'month-1-required-trimester-video', 'title' => 'First Trimester Prenatal Care Guide', 'tag' => 'First Trimester', 'time' => 'Required video', 'url' => 'https://youtu.be/D_jxGJsEY2A?si=tZLKKH_KnEHkOY2C', 'youtube_id' => 'D_jxGJsEY2A', 'required' => true],
        4 => ['key' => 'month-4-required-trimester-video', 'title' => 'Second Trimester Prenatal Care Guide', 'tag' => 'Second Trimester', 'time' => 'Required video', 'url' => 'https://youtu.be/H6mZRds0dHo?si=jXxF1SdF5h_emTNW', 'youtube_id' => 'H6mZRds0dHo', 'required' => true],
        7 => ['key' => 'month-7-required-trimester-video', 'title' => 'Third Trimester Birth Readiness Guide', 'tag' => 'Third Trimester', 'time' => 'Required video', 'url' => 'https://youtu.be/f2dcTHQXwTI?si=YXvFxXuYWXbENi2C', 'youtube_id' => 'f2dcTHQXwTI', 'required' => true],
    ];
    $firstTrimesterSupplementalVideos = [
        1 => [
            ['key' => 'month-1-first-trimester-video-2', 'title' => 'Conception and New Beginnings Video 2', 'tag' => 'First Trimester', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/e4UPKPv7v38?si=Mwv0LmxVKzyTD9ev', 'youtube_id' => 'e4UPKPv7v38'],
        ],
        2 => [
            ['key' => 'month-2-first-trimester-video-1', 'title' => 'The Tiny Heart Beats Video 1', 'tag' => 'Month 2', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/_Ux1wPgEpJo?si=HMiCAkDgUNwr_Pi-', 'youtube_id' => '_Ux1wPgEpJo'],
            ['key' => 'month-2-first-trimester-video-2', 'title' => 'The Tiny Heart Beats Video 2', 'tag' => 'Month 2', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/jgnciIgOFmg?si=jNVfw8xB4aDof5QG', 'youtube_id' => 'jgnciIgOFmg'],
            ['key' => 'month-2-first-trimester-video-3', 'title' => 'The Tiny Heart Beats Video 3', 'tag' => 'Month 2', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/GiRnUn0ApKE?si=yuajuH0q3jbcNuEa', 'youtube_id' => 'GiRnUn0ApKE'],
            ['key' => 'month-2-first-trimester-video-4', 'title' => 'The Tiny Heart Beats Video 4', 'tag' => 'Month 2', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/gAkxR_Ept1k?si=BtSQwzVTsSQq25Rv', 'youtube_id' => 'gAkxR_Ept1k'],
        ],
        3 => [
            ['key' => 'month-3-first-trimester-video-1', 'title' => 'First Trimester Milestones Video 1', 'tag' => 'Month 3', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/yRoHw6rnntc?si=GCGN-bD8Yyg7vzAV', 'youtube_id' => 'yRoHw6rnntc'],
            ['key' => 'month-3-first-trimester-video-2', 'title' => 'First Trimester Milestones Video 2', 'tag' => 'Month 3', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/ciG1YICJrDA?si=bbbqHktM2TMMheCW', 'youtube_id' => 'ciG1YICJrDA'],
            ['key' => 'month-3-first-trimester-video-3', 'title' => 'First Trimester Milestones Video 3', 'tag' => 'Month 3', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/vtg1w7TUJ3w?si=iT69DCppncZIAWvD', 'youtube_id' => 'vtg1w7TUJ3w'],
            ['key' => 'month-3-first-trimester-video-4', 'title' => 'First Trimester Milestones Video 4', 'tag' => 'Month 3', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/_QB0qkJ4zRk?si=DKsL1i8tlr4Bq4eo', 'youtube_id' => '_QB0qkJ4zRk'],
        ],
    ];
    $secondTrimesterSupplementalVideos = [
        4 => [
            ['key' => 'month-4-second-trimester-video-1', 'title' => 'Growing and Developing Video 1', 'tag' => 'Month 4', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/IPj4dJnP85o?si=XmiOYwKz8gCOCL6u', 'youtube_id' => 'IPj4dJnP85o'],
            ['key' => 'month-4-second-trimester-video-2', 'title' => 'Growing and Developing Video 2', 'tag' => 'Month 4', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/B9xiKEWc9SM?si=Zs0G3N_otwXbvvoX', 'youtube_id' => 'B9xiKEWc9SM'],
            ['key' => 'month-4-second-trimester-video-3', 'title' => 'Growing and Developing Video 3', 'tag' => 'Month 4', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/hmWtKtbIolE?si=KpdmXsXsGywd_dge', 'youtube_id' => 'hmWtKtbIolE'],
            ['key' => 'month-4-second-trimester-video-4', 'title' => 'Growing and Developing Video 4', 'tag' => 'Month 4', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/shyWWLkA61I?si=WDZovJ13tTqkzeOj', 'youtube_id' => 'shyWWLkA61I'],
            ['key' => 'month-4-second-trimester-video-5', 'title' => 'Growing and Developing Video 5', 'tag' => 'Month 4', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/HTIV2AdFTnc?si=lxxxbyKlLLetwx41', 'youtube_id' => 'HTIV2AdFTnc'],
        ],
        5 => [
            ['key' => 'month-5-second-trimester-video-1', 'title' => 'Feeling the Baby Move Video 1', 'tag' => 'Month 5', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/tycuzmo-s34?si=41LH9sIVm8f-yK6I', 'youtube_id' => 'tycuzmo-s34'],
            ['key' => 'month-5-second-trimester-video-2', 'title' => 'Feeling the Baby Move Video 2', 'tag' => 'Month 5', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/wM7I0krDPTg?si=pNKqK_hMb0JSxkuj', 'youtube_id' => 'wM7I0krDPTg'],
            ['key' => 'month-5-second-trimester-video-3', 'title' => 'Feeling the Baby Move Video 3', 'tag' => 'Month 5', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/L-9NcufiDOo?si=iEwITUCZ9F-V6w-7', 'youtube_id' => 'L-9NcufiDOo'],
            ['key' => 'month-5-second-trimester-video-4', 'title' => 'Feeling the Baby Move Video 4', 'tag' => 'Month 5', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/MLZ0lbkKbgM?si=RVcjGCR5e5ZD_UeP', 'youtube_id' => 'MLZ0lbkKbgM'],
        ],
        6 => [
            ['key' => 'month-6-second-trimester-video-1', 'title' => 'Continued Growth Video 1', 'tag' => 'Month 6', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/umgAThOkJgw?si=BJDaKv9iLRrAmK1y', 'youtube_id' => 'umgAThOkJgw'],
            ['key' => 'month-6-second-trimester-video-2', 'title' => 'Continued Growth Video 2', 'tag' => 'Month 6', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/QctcxLDsWB0?si=60H3b3Mi3NUdviUs', 'youtube_id' => 'QctcxLDsWB0'],
            ['key' => 'month-6-second-trimester-video-3', 'title' => 'Continued Growth Video 3', 'tag' => 'Month 6', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/OmivW51zGvs?si=VirObbd4cmquFtOF', 'youtube_id' => 'OmivW51zGvs'],
            ['key' => 'month-6-second-trimester-video-4', 'title' => 'Continued Growth Video 4', 'tag' => 'Month 6', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/VYYgOi9AkHU?si=d717SRziL9sOu0bF', 'youtube_id' => 'VYYgOi9AkHU'],
            ['key' => 'month-6-second-trimester-video-5', 'title' => 'Continued Growth Video 5', 'tag' => 'Month 6', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/orLWetHISck?si=My5x5lx2RjeI9CHb', 'youtube_id' => 'orLWetHISck'],
            ['key' => 'month-6-second-trimester-video-6', 'title' => 'Continued Growth Video 6', 'tag' => 'Month 6', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/k71_-M5q_H0?si=8mKHcTgdZM2Gwr6H', 'youtube_id' => 'k71_-M5q_H0'],
            ['key' => 'month-6-second-trimester-video-7', 'title' => 'Continued Growth Video 7', 'tag' => 'Month 6', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/14kbze3sSaY?si=YoQK4OI7bcdhBhVB', 'youtube_id' => '14kbze3sSaY'],
        ],
    ];
    $thirdTrimesterSupplementalVideos = [
        7 => [
            ['key' => 'month-7-third-trimester-video-1', 'title' => 'Preparing for Birth Video 1', 'tag' => 'Month 7', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/lpDW00nQhUo?si=OyLqgLRQNw11Qo1f', 'youtube_id' => 'lpDW00nQhUo'],
            ['key' => 'month-7-third-trimester-video-2', 'title' => 'Preparing for Birth Video 2', 'tag' => 'Month 7', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/OzY_2-0NvVM?si=T49XHXyAnL_VnOQy', 'youtube_id' => 'OzY_2-0NvVM'],
            ['key' => 'month-7-third-trimester-video-3', 'title' => 'Preparing for Birth Video 3', 'tag' => 'Month 7', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/GkcPIcxGy9g?si=5pqkAB5pVReRV2S_', 'youtube_id' => 'GkcPIcxGy9g'],
            ['key' => 'month-7-third-trimester-video-4', 'title' => 'Preparing for Birth Video 4', 'tag' => 'Month 7', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/AyEm6K295iE?si=f_Q1M0bvE8hLeOMW', 'youtube_id' => 'AyEm6K295iE'],
            ['key' => 'month-7-third-trimester-video-5', 'title' => 'Preparing for Birth Video 5', 'tag' => 'Month 7', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/_pE5PqZgleI?si=UEdfFgHaUKL5z3yk', 'youtube_id' => '_pE5PqZgleI'],
        ],
        8 => [
            ['key' => 'month-8-third-trimester-video-1', 'title' => 'Birth Readiness Video 1', 'tag' => 'Month 8', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/nFnlovPk1ro?si=rept0jT9pnTTr__W', 'youtube_id' => 'nFnlovPk1ro'],
            ['key' => 'month-8-third-trimester-video-2', 'title' => 'Birth Readiness Video 2', 'tag' => 'Month 8', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/wITeiIVieao?si=lsEjtM65scvoE50E', 'youtube_id' => 'wITeiIVieao'],
            ['key' => 'month-8-third-trimester-video-3', 'title' => 'Birth Readiness Video 3', 'tag' => 'Month 8', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/9HzZeSX6b2o?si=UGlOFClSzqmSf3ru', 'youtube_id' => '9HzZeSX6b2o'],
            ['key' => 'month-8-third-trimester-video-4', 'title' => 'Birth Readiness Video 4', 'tag' => 'Month 8', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/OtG-M8pHODY?si=3VKOdwrooDyKi_XD', 'youtube_id' => 'OtG-M8pHODY'],
        ],
        9 => [
            ['key' => 'month-9-third-trimester-video-1', 'title' => 'Final Preparation Video 1', 'tag' => 'Month 9', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/eFWuydRmFQg?si=_-X7dzUI4UtsZFxo', 'youtube_id' => 'eFWuydRmFQg'],
            ['key' => 'month-9-third-trimester-video-2', 'title' => 'Final Preparation Video 2', 'tag' => 'Month 9', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/_oB6wbpWCSo?si=s738kCP-nAzhij2Q', 'youtube_id' => '_oB6wbpWCSo'],
            ['key' => 'month-9-third-trimester-video-3', 'title' => 'Final Preparation Video 3', 'tag' => 'Month 9', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/c701W2pzuyo?si=Oys7aDXLnKqa5sXz', 'youtube_id' => 'c701W2pzuyo'],
            ['key' => 'month-9-third-trimester-video-4', 'title' => 'Final Preparation Video 4', 'tag' => 'Month 9', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/ww8s7PQWWuY?si=FQ6Aat8X4u362zhD', 'youtube_id' => 'ww8s7PQWWuY'],
            ['key' => 'month-9-third-trimester-video-5', 'title' => 'Final Preparation Video 5', 'tag' => 'Month 9', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/yO6GYS3PnFY?si=x6r0pY6y-TFar1lI', 'youtube_id' => 'yO6GYS3PnFY'],
        ],
    ];

    $months = [
        ['month' => 1, 'stage' => 'first-trimester', 'title' => 'Conception and New Beginnings', 'weeks' => 'Weeks 1-4', 'current' => true, 'baby' => 'Conception occurs, and the fertilized egg travels to the uterus. Cell division begins rapidly, forming an embryo. By week 4, the foundation of the heart, nervous system, and organs are starting to take shape.', 'mother' => 'You might not look pregnant yet, but internally your hormone levels rise quickly. You may begin experiencing breast tenderness, subtle nausea, and increased fatigue.', 'symptoms' => 'Frequent urination, light fatigue, breast sensitivity, mild cramps or bloating.', 'nutrition' => 'Start taking 400-800 mcg of folic acid daily. Include folate-rich foods like spinach, broccoli, fortified cereals, citrus fruits, and legumes.', 'risk' => 'Heavy bleeding or severe abdominal pain', 'videos' => array_merge([$requiredTrimesterVideos[1]], $firstTrimesterSupplementalVideos[1])],
        ['month' => 2, 'stage' => 'first-trimester', 'title' => 'The Tiny Heart Beats', 'weeks' => 'Weeks 5-8', 'current' => false, 'baby' => "Baby's heart starts beating. Limb buds appear, and the brain begins developing quickly.", 'mother' => 'Morning sickness may peak. Breasts continue to grow and become more tender.', 'symptoms' => 'Nausea, vomiting, smell sensitivity, mood changes, food cravings, and frequent urination.', 'nutrition' => 'Eat small frequent meals. Choose crackers, ginger tea, bananas, rice, soup, and water when nausea is strong.', 'risk' => 'Severe vomiting or dehydration', 'videos' => $firstTrimesterSupplementalVideos[2]],
        ['month' => 3, 'stage' => 'first-trimester', 'title' => 'First Trimester Milestones', 'weeks' => 'Weeks 9-12', 'current' => false, 'baby' => 'Tiny fingers, toes, and facial features become clearer. Major organs continue to mature.', 'mother' => 'Energy may slowly return, though nausea can still happen. Continue prenatal checkups and supplements.', 'symptoms' => 'Fatigue, mild headaches, changing appetite, and tender breasts.', 'nutrition' => 'Add protein, iron-rich food, fruits, and vegetables. Avoid alcohol and unsafe medicines.', 'risk' => 'Fever, bleeding, or persistent abdominal pain', 'videos' => $firstTrimesterSupplementalVideos[3]],
        ['month' => 4, 'stage' => 'second-trimester', 'title' => 'Growing and Developing', 'weeks' => 'Weeks 13-16', 'current' => true, 'baby' => 'Baby grows longer and begins making small movements, even if you cannot feel them yet.', 'mother' => 'Appetite may improve. Your abdomen may start showing more clearly.', 'symptoms' => 'Round ligament pain, clearer appetite, less nausea, and skin changes.', 'nutrition' => 'Continue iron and calcium sources. Add milk, malunggay, fish, eggs, beans, and leafy vegetables.', 'risk' => 'Strong cramps, fever, or unusual discharge', 'videos' => array_merge([$requiredTrimesterVideos[4]], $secondTrimesterSupplementalVideos[4])],
        ['month' => 5, 'stage' => 'second-trimester', 'title' => 'Feeling the Baby Move', 'weeks' => 'Weeks 17-20', 'current' => false, 'baby' => 'You may begin feeling gentle flutters. Baby can hear sounds and continues growing stronger.', 'mother' => 'Back discomfort and leg cramps may appear. Gentle stretching and hydration can help.', 'symptoms' => 'Quickening, backache, leg cramps, and increased appetite.', 'nutrition' => 'Prioritize calcium, protein, iron, and water. Bring your record to your scheduled checkup.', 'risk' => 'No fetal movement after it has become regular', 'videos' => $secondTrimesterSupplementalVideos[5]],
        ['month' => 6, 'stage' => 'second-trimester', 'title' => 'Continued Growth', 'weeks' => 'Weeks 21-27', 'current' => false, 'baby' => 'Baby gains weight and practices breathing movements. Regular monitoring becomes more important.', 'mother' => 'You may notice swelling, stronger appetite, and more visible belly growth.', 'symptoms' => 'Swollen feet, heartburn, backache, and stronger fetal movement.', 'nutrition' => 'Reduce salty food, stay hydrated, and follow your clinic schedule for screening.', 'risk' => 'Severe headache, blurred vision, or sudden swelling', 'videos' => $secondTrimesterSupplementalVideos[6]],
        ['month' => 7, 'stage' => 'third-trimester', 'title' => 'Preparing for Birth', 'weeks' => 'Weeks 28-31', 'current' => true, 'baby' => 'Baby opens the eyes, gains more weight, and responds to sound and light. Brain and lung development continue quickly.', 'mother' => 'You may feel stronger kicks, back pressure, and more frequent heartburn. Begin preparing birth plans and records.', 'symptoms' => 'Back pain, heartburn, leg cramps, stronger baby movement, and mild swelling.', 'nutrition' => 'Continue iron, calcium, protein, and water. Limit salty food and keep checkup records updated.', 'risk' => 'Contractions, leaking fluid, bleeding, or severe headache', 'videos' => array_merge([$requiredTrimesterVideos[7]], $thirdTrimesterSupplementalVideos[7])],
        ['month' => 8, 'stage' => 'third-trimester', 'title' => 'Birth Readiness', 'weeks' => 'Weeks 32-35', 'current' => false, 'baby' => 'Baby gains fat and may begin settling into a head-down position. Movements should be monitored daily.', 'mother' => 'Shortness of breath, pelvic pressure, and sleep discomfort may increase.', 'symptoms' => 'Pelvic pressure, frequent urination, sleep changes, and Braxton Hicks contractions.', 'nutrition' => 'Choose nutrient-dense meals, water, fruits, vegetables, fish, eggs, and iron-rich food.', 'risk' => 'Reduced baby movement or signs of preterm labor', 'videos' => $thirdTrimesterSupplementalVideos[8]],
        ['month' => 9, 'stage' => 'third-trimester', 'title' => 'Final Preparation', 'weeks' => 'Weeks 36-40', 'current' => false, 'baby' => 'Baby continues gaining weight and the lungs mature further. Delivery preparations become more important.', 'mother' => 'You may feel heavier and more tired. Prepare transport, emergency contacts, and your birth bag.', 'symptoms' => 'Pelvic heaviness, back pain, sleep discomfort, and stronger contractions.', 'nutrition' => 'Eat small meals, stay hydrated, and keep energy-giving foods ready.', 'risk' => 'Regular painful contractions, water breaking, bleeding, or high blood pressure symptoms', 'videos' => $thirdTrimesterSupplementalVideos[9]],
        ['month' => 10, 'stage' => 'labor-delivery', 'title' => 'Family Planning and Informed Choice', 'weeks' => 'Planning and Counseling', 'current' => false, 'baby' => 'Family planning supports the right to decide freely and responsibly whether and when to have children.', 'mother' => 'Personal health history, preferences, reproductive goals, and access to follow-up care can guide a discussion with a qualified provider.', 'symptoms' => 'Prepare questions about effectiveness, correct use, possible side effects, STI protection, reversibility, and follow-up needs.', 'nutrition' => 'Bring a current medicine list and relevant health records to counseling so the provider can discuss suitable options.', 'risk' => 'Severe or concerning symptoms after starting a contraceptive method', 'videos' => []],
    ];

    $stageTranslations = [
        'first-trimester' => ['label' => 'Buwan 1-3', 'title' => 'Unang Trimester', 'focus' => 'Buwan 1 (Linggo 1-4)', 'output' => 'Matibay na simula ng pagbubuntis, kahandaan sa prenatal checkup, nutrisyon, at mga babala.'],
        'second-trimester' => ['label' => 'Buwan 4-6', 'title' => 'Ikalawang Trimester', 'focus' => 'Buwan 4 (Linggo 13-16)', 'output' => 'Pagsubaybay sa paglaki, pag-alam sa galaw ng sanggol, tuloy-tuloy na supplements, at mas ligtas na pang-araw-araw na gawain.'],
        'third-trimester' => ['label' => 'Buwan 7-9', 'title' => 'Ikatlong Trimester', 'focus' => 'Buwan 7 (Linggo 28-31)', 'output' => 'Kahandaan sa panganganak, huling checkup, pagbabantay sa danger signs, at emergency planning.'],
        'labor-delivery' => ['label' => 'Kalusugang Reproduktibo', 'title' => 'Family Planning', 'focus' => 'Pagpaplano at Counseling', 'output' => 'May sapat na kaalaman sa layunin, mga paraan, tanong para sa provider, at follow-up support sa family planning.'],
        'postpartum-care' => ['label' => 'Kapanganakan hanggang 6 na Linggo', 'title' => 'Pangangalaga Pagkapanganak', 'focus' => 'Kapanganakan hanggang 6 na Linggo', 'output' => 'Checklist sa paggaling ng ina, suporta sa breastfeeding, family planning, at follow-up care.'],
        'neonatal-care' => ['label' => '0-28 Araw Pagkapanganak', 'title' => 'Pangangalaga sa Newborn', 'focus' => '0-28 Araw Pagkapanganak', 'output' => 'Newborn screening, bakuna, pagpapakain, cord care, at pagsubaybay sa paglaki.'],
    ];
    $continuumTranslations = [
        'labor-delivery' => [
            'summary' => 'Suportahan ang malaya at may sapat na kaalamang pagpili sa pamamagitan ng pagtalakay sa reproductive goals, mga paraan ng family planning, personal na kalusugan, at follow-up care kasama ang kwalipikadong provider.',
            'topics' => ['Reproductive goals', 'May sapat na kaalamang pagpili', 'Pansamantalang paraan', 'Long-acting na paraan', 'Permanenteng paraan', 'Condom at proteksyon sa STI'],
            'tasks' => ['Talakayin ang reproductive goals at health history sa kwalipikadong healthcare provider.', 'Ihambing ang mga paraan, benepisyo, posibleng side effect, at tamang paggamit.', 'Ihanda ang mga tanong at magpa-schedule ng family planning counseling.'],
        ],
        'postpartum-care' => [
            'summary' => 'Suportahan ang ina matapos manganak sa paggaling, pagpapasuso, mental health checks, family planning, danger signs, at follow-up care.',
            'topics' => ['Paggaling ng ina', 'Nutrisyon', 'Breastfeeding', 'Mental health', 'Family planning', 'Postpartum danger signs', 'Follow-up checkups'],
            'tasks' => ['Bantayan ang pagdurugo, lagnat, pananakit, mood, at warning signs sa paggaling.', 'Dumalo sa postpartum checkups at family planning counseling.', 'Humingi agad ng tulong kapag nahihirapan sa breastfeeding o emosyonal na kalusugan.'],
        ],
        'neonatal-care' => [
            'summary' => 'Gabay sa pag-aalaga ng newborn sa unang 28 araw, kabilang ang pagpapakain, kalinisan, bakuna, screening, danger signs, at growth monitoring.',
            'topics' => ['Mahalagang newborn care', 'Breastfeeding', 'Cord care', 'Pagpapaligo', 'Newborn danger signs', 'Bakuna (BCG, Hepatitis B, OPV)', 'Growth monitoring', 'Newborn screening', 'Follow-up visits'],
            'tasks' => ['Kumpletuhin ang newborn screening, bakuna, at follow-up visits.', 'Bantayan ang pagpapakain, temperatura, paghinga, pusod, at senyales ng jaundice.', 'Dalhin agad ang newborn sa health facility kapag may danger signs.'],
        ],
    ];
    $monthTranslations = [
        1 => ['title' => 'Paglilihi at Bagong Simula', 'weeks' => 'Linggo 1-4', 'baby' => 'Nangyayari ang paglilihi at ang fertilized egg ay papunta sa matris. Mabilis ang paghahati ng cells at nagsisimula ang embryo. Pagsapit ng linggo 4, nagsisimula nang mabuo ang pundasyon ng puso, nervous system, at mga organo.', 'mother' => 'Maaaring hindi pa halata ang pagbubuntis, pero mabilis nang tumataas ang hormones. Maaaring maramdaman ang pananakit ng dibdib, banayad na pagsusuka, at pagkapagod.', 'symptoms' => 'Madalas na pag-ihi, bahagyang pagkapagod, pananakit ng dibdib, banayad na cramps o bloating.', 'nutrition' => 'Magsimulang uminom ng 400-800 mcg folic acid araw-araw ayon sa payo ng provider. Kumain ng folate-rich foods tulad ng spinach, broccoli, fortified cereals, citrus fruits, at legumes.', 'risk' => 'Malakas na pagdurugo o matinding pananakit ng tiyan', 'guidance' => ['period' => 'Pagkatapos makumpirma ang pagbubuntis', 'recommendation' => 'Makipag-ugnayan sa healthcare provider, magsimula ng prenatal health assessment, at pag-usapan ang gamot, supplements, nutrisyon, at dating medical conditions.', 'items' => ['Hikayatin ang ina na makipag-ugnayan sa healthcare provider pagkatapos makumpirma ang pagbubuntis.', 'Irekomenda ang pagsisimula ng prenatal health assessment.', 'Paalalahanan siyang talakayin ang gamot, supplements, nutrisyon, at dating medical conditions sa healthcare provider.']]],
        2 => ['title' => 'Tumitibok ang Munting Puso', 'weeks' => 'Linggo 5-8', 'baby' => 'Nagsisimula nang tumibok ang puso ng sanggol. Lumilitaw ang limb buds at mabilis na nade-develop ang utak.', 'mother' => 'Maaaring lumakas ang morning sickness. Patuloy na lumalaki at sumasakit ang dibdib.', 'symptoms' => 'Pagduduwal, pagsusuka, sensitibo sa amoy, pagbabago ng mood, cravings, at madalas na pag-ihi.', 'nutrition' => 'Kumain ng kaunti pero madalas. Pumili ng crackers, salabat, saging, kanin, sabaw, at tubig kapag malakas ang pagduduwal.', 'risk' => 'Matinding pagsusuka o dehydration', 'guidance' => ['period' => 'Unang prenatal checkup', 'recommendation' => 'Magpa-schedule o dumalo sa unang prenatal checkup at magtanong tungkol sa sintomas, nutrisyon, at alalahanin sa maagang pagbubuntis.', 'items' => ['Irekomenda ang pag-schedule o pagdalo sa unang prenatal checkup.', 'Maaaring tingnan ng provider ang medical at pregnancy history, blood pressure, timbang, at kailangang laboratory examinations.', 'Hikayatin ang ina na magtanong tungkol sa nausea, pagkapagod, nutrisyon, at iba pang alalahanin.']]],
        3 => ['title' => 'Mahahalagang Yugto ng Unang Trimester', 'weeks' => 'Linggo 9-12', 'baby' => 'Mas malinaw na ang maliliit na daliri, paa, at mukha. Patuloy na naghihinog ang mahahalagang organo.', 'mother' => 'Maaaring unti-unting bumalik ang lakas kahit may nausea pa rin. Ipagpatuloy ang prenatal checkups at supplements.', 'symptoms' => 'Pagkapagod, banayad na sakit ng ulo, pagbabago ng gana, at sensitibong dibdib.', 'nutrition' => 'Dagdagan ang protina, pagkaing may iron, prutas, at gulay. Iwasan ang alak at gamot na hindi aprubado ng provider.', 'risk' => 'Lagnat, pagdurugo, o tuloy-tuloy na pananakit ng tiyan', 'guidance' => ['period' => 'Sa loob ng unang 12 linggo', 'recommendation' => 'Gawin ang unang antenatal contact kung maaari at pag-usapan ang screening, ultrasound timing, nutrisyon, at supplements na aprubado ng provider.', 'items' => ['Ang unang antenatal contact ay mas mainam na maganap sa loob ng unang 12 linggo.', 'Pag-usapan ang screening examinations, ultrasound timing, nutrisyon, at provider-approved supplements.', 'I-report agad ang kakaiba o nakakabahalang sintomas.']]],
        4 => ['title' => 'Paglaki at Pag-develop', 'weeks' => 'Linggo 13-16', 'baby' => 'Humahaba ang sanggol at nagsisimula ng maliliit na galaw, kahit maaaring hindi mo pa ito maramdaman.', 'mother' => 'Maaaring bumuti ang gana sa pagkain. Mas maaaring magsimulang lumitaw ang tiyan.', 'symptoms' => 'Round ligament pain, mas malinaw na gana, mas kaunting nausea, at pagbabago sa balat.', 'nutrition' => 'Ipagpatuloy ang iron at calcium sources. Magdagdag ng gatas, malunggay, isda, itlog, beans, at leafy vegetables.', 'risk' => 'Matinding cramps, lagnat, o kakaibang discharge', 'guidance' => ['period' => 'Routine prenatal checkup', 'recommendation' => 'Dumalo sa routine prenatal checkup para sa vital-sign monitoring ng ina at assessment ng development ng sanggol.', 'items' => ['Irekomenda ang routine prenatal checkup.', 'Banggitin ang maternal vital-sign monitoring at assessment ng development ng sanggol.', 'Hikayatin ang pagtalakay sa physical activity, nutrisyon, vaccination history, at karaniwang discomforts.']]],
        5 => ['title' => 'Nararamdaman ang Galaw ng Sanggol', 'weeks' => 'Linggo 17-20', 'baby' => 'Maaaring maramdaman ang banayad na flutters. Naririnig na ng sanggol ang tunog at patuloy na lumalakas.', 'mother' => 'Maaaring lumitaw ang pananakit ng likod at cramps sa binti. Makakatulong ang banayad na stretching at tubig.', 'symptoms' => 'Quickening, pananakit ng likod, cramps sa binti, at dagdag na gana.', 'nutrition' => 'Unahin ang calcium, protina, iron, at tubig. Dalhin ang records sa naka-schedule na checkup.', 'risk' => 'Walang galaw ng sanggol matapos maging regular ang paggalaw', 'guidance' => ['period' => 'Bandang Linggo 20', 'recommendation' => 'Tanungin ang healthcare provider tungkol sa fetal development at tamang schedule ng ultrasound o screening.', 'items' => ['Irekomenda ang prenatal contact bandang Linggo 20.', 'Tanungin ang provider tungkol sa fetal development at tamang ultrasound o screening schedule.', 'Ang pagpansin sa galaw ng sanggol ay nakakatulong sa awareness, pero hindi ito diagnostic tool.']]],
        6 => ['title' => 'Patuloy na Paglaki', 'weeks' => 'Linggo 21-27', 'baby' => 'Tumataas ang timbang ng sanggol at nagsasanay ng breathing movements. Mas mahalaga ang regular monitoring.', 'mother' => 'Maaaring mapansin ang pamamaga, mas malakas na gana, at mas malaking tiyan.', 'symptoms' => 'Pamamaga ng paa, heartburn, pananakit ng likod, at mas malakas na galaw ng sanggol.', 'nutrition' => 'Bawasan ang maalat, uminom ng sapat na tubig, at sundin ang clinic schedule para sa screening.', 'risk' => 'Matinding sakit ng ulo, malabong paningin, o biglaang pamamaga', 'guidance' => ['period' => 'Bandang Linggo 26', 'recommendation' => 'Ipagpatuloy ang monitoring ng blood pressure, kalusugan ng ina, paglaki ng sanggol, at tests na hinihingi ng provider.', 'items' => ['Irekomenda ang isa pang prenatal contact bandang Linggo 26.', 'Banggitin ang monitoring ng blood pressure, maternal health, fetal growth, at provider-requested tests.', 'Talakayin sa provider ang pamamaga, sakit ng ulo, pananakit, o iba pang nakakabahalang pagbabago.']]],
        7 => ['title' => 'Paghahanda sa Panganganak', 'weeks' => 'Linggo 28-31', 'baby' => 'Binubuksan ng sanggol ang mga mata, nadaragdagan ang timbang, at tumutugon sa tunog at ilaw. Mabilis pa rin ang brain at lung development.', 'mother' => 'Maaaring maramdaman ang mas malalakas na sipa, pressure sa likod, at mas madalas na heartburn. Simulan ang paghahanda ng birth plan at records.', 'symptoms' => 'Pananakit ng likod, heartburn, cramps sa binti, mas malakas na galaw ng sanggol, at banayad na pamamaga.', 'nutrition' => 'Ipagpatuloy ang iron, calcium, protina, at tubig. Limitahan ang maalat at panatilihing updated ang checkup records.', 'risk' => 'Contractions, pagtagas ng tubig, pagdurugo, o matinding sakit ng ulo', 'guidance' => ['period' => 'Bandang Linggo 30', 'recommendation' => 'Pag-usapan ang fetal movement awareness, birth planning, warning signs, at schedule na ibinigay ng healthcare provider.', 'items' => ['Irekomenda ang prenatal contact bandang Linggo 30.', 'Isama ang gabay sa fetal movement awareness, birth planning, at pagkilala sa warning signs.', 'Sundin lamang ang schedule at medical instructions ng healthcare provider.']]],
        8 => ['title' => 'Kahandaan sa Panganganak', 'weeks' => 'Linggo 32-35', 'baby' => 'Nadadagdagan ang taba ng sanggol at maaaring magsimulang pumosisyon nang ulo pababa. Dapat bantayan araw-araw ang galaw.', 'mother' => 'Maaaring dumalas ang hirap sa paghinga, pelvic pressure, at hirap sa pagtulog.', 'symptoms' => 'Pelvic pressure, madalas na pag-ihi, pagbabago sa tulog, at Braxton Hicks contractions.', 'nutrition' => 'Pumili ng masustansyang pagkain, tubig, prutas, gulay, isda, itlog, at pagkaing may iron.', 'risk' => 'Bawas na galaw ng sanggol o senyales ng preterm labor', 'guidance' => ['period' => 'Bandang Linggo 34', 'recommendation' => 'Ihanda ang delivery plan, dokumento, transportasyon, emergency contacts, at hospital bag.', 'items' => ['Irekomenda ang prenatal contact bandang Linggo 34.', 'Ihanda ang delivery plan, hospital documents, transportasyon, emergency contacts, at hospital bag.', 'Maaaring suriin ng healthcare provider ang paglaki at posisyon ng sanggol.']]],
        9 => ['title' => 'Huling Paghahanda', 'weeks' => 'Linggo 36-40', 'baby' => 'Patuloy na nadadagdagan ang timbang ng sanggol at mas naghihinog ang baga. Mas mahalaga ang paghahanda sa delivery.', 'mother' => 'Maaaring mas mabigat at mas pagod ang pakiramdam. Ihanda ang transportasyon, emergency contacts, at birth bag.', 'symptoms' => 'Bigat sa balakang, pananakit ng likod, hirap sa pagtulog, at mas malalakas na contractions.', 'nutrition' => 'Kumain ng maliliit na meals, uminom ng tubig, at maghanda ng pagkaing nagbibigay-lakas.', 'risk' => 'Regular na masakit na contractions, pumutok ang panubigan, pagdurugo, o sintomas ng high blood pressure', 'guidance' => ['period' => 'Humigit-kumulang Linggo 36, 38, at 40', 'recommendation' => 'I-review ang senyales ng labor, paghahanda sa panganganak, at kung kailan tatawag sa health facility. Huwag ipagpaliban ang urgent concerns.', 'items' => ['Magpakita ng prenatal contact reminders para sa humigit-kumulang Linggo 36, 38, at 40 depende sa tagubilin ng provider.', 'Magbigay ng pangkalahatang impormasyon tungkol sa senyales ng labor, paghahanda sa delivery, at kung kailan tatawag sa health facility.', 'Ang urgent concerns ay hindi dapat maghintay sa susunod na schedule.']]],
        10 => ['title' => 'Family Planning at May Sapat na Kaalamang Pagpili', 'weeks' => 'Pagpaplano at Counseling', 'baby' => 'Sinusuportahan ng family planning ang karapatang malayang magpasya kung nais at kailan magkakaroon ng anak.', 'mother' => 'Mahalaga sa counseling ang health history, kagustuhan, reproductive goals, at access sa follow-up care.', 'symptoms' => 'Maghanda ng mga tanong tungkol sa bisa, tamang paggamit, posibleng side effect, STI protection, reversibility, at follow-up.', 'nutrition' => 'Dalhin ang listahan ng kasalukuyang gamot at mahalagang health records upang matalakay sa provider ang mga angkop na opsyon.', 'risk' => 'Malala o nakakabahalang sintomas matapos magsimula ng contraceptive method'],
    ];
    $i18n = fn (string $en, ?string $tl = null): string => 'data-i18n-en="'.e($en).'" data-i18n-tl="'.e($tl ?? $en).'"';

    $kaalamanUploads = collect($kaalamanUploads ?? []);
    $kaalamanMonthlyProgress = $kaalamanMonthlyProgress ?? ['months' => [], 'overall' => ['completed_months' => 0, 'total_months' => 10, 'percentage' => 0]];
    $kaalamanOverallProgress = $kaalamanOverallProgress ?? ($kaalamanMonthlyProgress['overall'] ?? ['completed_months' => 0, 'total_months' => 10, 'percentage' => 0]);
    $publishedEducationalContentByStage = collect($publishedEducationalContentByStage ?? []);
    $publishedEducationalContentByMonth = collect($publishedEducationalContentByMonth ?? []);
    $stageMonthMap = [
        'first-trimester' => [1, 2, 3],
        'second-trimester' => [4, 5, 6],
        'third-trimester' => [7, 8, 9],
        'labor-delivery' => [10],
        'postpartum-care' => [],
        'neonatal-care' => [],
    ];

    foreach ($learningStages as $stageKey => &$stage) {
        $stageMonths = $stageMonthMap[$stageKey] ?? [];
        $completedStageMonths = collect($stageMonths)
            ->filter(fn ($monthNumber) => (bool) ($kaalamanMonthlyProgress['months'][$monthNumber]['is_complete'] ?? false))
            ->count();
        $stage['progress'] = count($stageMonths) > 0 ? (int) round($completedStageMonths / count($stageMonths) * 100) : 0;
    }
    unset($stage);

    $iconBook = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/></svg>';
    $iconVideo = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m10 8 6 4-6 4Z"/><rect x="3" y="5" width="18" height="14" rx="2"/></svg>';
    $iconTask = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 11l2 2 4-4"/><rect x="4" y="3" width="16" height="18" rx="2"/></svg>';
    $iconHeart = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 5.6a5.4 5.4 0 0 0-7.6 0L12 6.8l-1.2-1.2a5.4 5.4 0 1 0-7.6 7.6L12 22l8.8-8.8a5.4 5.4 0 0 0 0-7.6Z"/></svg>';
    $iconPulse = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>';
    $iconChevron = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
    $iconChevronLeft = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>';
    $iconChevronRight = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>';
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
        'planning' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M16 5.2a3 3 0 0 1 4.2 4.2L16 13.5l-4.2-4.1A3 3 0 0 1 16 5.2Z"/></svg>',
        'recovery' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 5.6a5.4 5.4 0 0 0-7.6 0L12 6.8l-1.2-1.2a5.4 5.4 0 1 0-7.6 7.6L12 22l8.8-8.8a5.4 5.4 0 0 0 0-7.6Z"/><path d="M8 12h2.5l1.2-2.4 2.1 5L15 12h1"/></svg>',
        'newborn' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 10.5a5 5 0 0 1 10 0v2.5a5 5 0 0 1-10 0Z"/><path d="M9 18.5 8 22"/><path d="m15 18.5 1 3.5"/><path d="M10 10h.01"/><path d="M14 10h.01"/><path d="M10.5 14c.9.7 2.1.7 3 0"/></svg>',
    ];
@endphp

@push('styles')
    <style>
        .kaalaman-hero-side { display: grid; gap: 10px; min-width: 0; }
        .kaalaman-language-switch { display: grid; gap: 8px; padding: 12px; background: #fff5fa; border: 1px solid #ffd8e9; border-radius: 9px; box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72); }
        .kaalaman-language-switch span { color: #b91c5c; font-size: 10px; font-weight: 900; text-transform: uppercase; }
        .kaalaman-language-options { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 6px; }
        .kaalaman-language-option { min-height: 32px; padding: 0 10px; color: #9f1239; background: #ffffff; border: 1px solid #ffd8e9; border-radius: 8px; font-size: 11px; font-weight: 900; cursor: pointer; transition: background 180ms ease, border-color 180ms ease, color 180ms ease, box-shadow 180ms ease; }
        .kaalaman-language-option:hover { color: #be185d; background: #fff0f7; border-color: #ffb8d7; }
        .kaalaman-language-option.is-active { color: #ffffff; background: #ec0a78; border-color: #ec0a78; box-shadow: 0 8px 18px rgba(236, 10, 120, 0.18); }
        .kaalaman-video-modal.kaalaman-infographic-modal[hidden] { display: none; }
        .kaalaman-video-modal.kaalaman-infographic-modal:not([hidden]) { box-sizing: border-box; display: flex; align-items: center; justify-content: center; top: var(--kaalaman-modal-top, 0px); right: auto; bottom: auto; left: var(--kaalaman-modal-left, 0px); width: var(--kaalaman-modal-width, 100vw); height: var(--kaalaman-modal-height, 100dvh); padding: clamp(18px, 4vw, 32px); overflow: hidden; }
        .kaalaman-infographic-modal .kaalaman-video-dialog { box-sizing: border-box; display: grid; gap: 12px; width: min(520px, 100%); max-height: calc(var(--kaalaman-modal-height, 100dvh) - 48px); overflow-y: auto; overscroll-behavior: contain; }
        .kaalaman-infographic-modal .kaalaman-video-close { z-index: 3; }
        .kaalaman-infographic-modal .kaalaman-modal-actions { display: grid; grid-template-columns: 1fr; }
        .kaalaman-infographic-modal .kaalaman-button { width: 100%; min-width: 0; white-space: normal; text-align: center; }
        .kaalaman-timeline-shell { gap: 12px; padding: 16px; border-radius: 14px; }
        .kaalaman-timeline-header strong { font-size: 16px; }
        .kaalaman-timeline-count { min-height: 28px; padding: 0 10px; }
        .kaalaman-stage-viewport { display: grid; grid-template-columns: 42px minmax(0, 1fr) 42px; align-items: center; gap: 10px; }
        .kaalaman-stage-arrow { display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; color: #ec0b7d; background: #ffe7f3; border: 1px solid #ffcfe4; border-radius: 999px; cursor: pointer; box-shadow: 0 10px 20px rgba(236, 11, 125, 0.14); }
        .kaalaman-stage-arrow svg { width: 19px; height: 19px; stroke: currentColor; stroke-width: 3; fill: none; }
        .kaalaman-stage-arrow:disabled { opacity: .45; cursor: not-allowed; box-shadow: none; }
        .kaalaman-trimester-grid { gap: 10px; padding: 2px 0 8px; scroll-padding-inline: 0; scroll-snap-type: x mandatory; }
        .kaalaman-trimester-card { flex: 0 0 calc((100% - 30px) / 4); gap: 10px; padding: 13px; border-radius: 12px; scroll-snap-align: start; scroll-snap-stop: always; box-shadow: 0 8px 16px rgba(15, 23, 42, 0.06); }
        .kaalaman-stage-head { gap: 9px; }
        .kaalaman-stage-head > span:last-child { min-width: 0; }
        .kaalaman-stage-icon { width: 36px; height: 36px; border-radius: 10px; }
        .kaalaman-stage-icon svg { width: 19px; height: 19px; max-width: 19px; max-height: 19px; }
        .kaalaman-stage-kicker { margin-bottom: 3px; font-size: 9px; }
        .kaalaman-trimester-title { margin-bottom: 6px; font-size: 14px; overflow-wrap: anywhere; }
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
        .kaalaman-month-summary { grid-template-columns: auto minmax(0, 1fr) auto auto; min-height: 64px; gap: 12px; padding: 14px 18px; }
        .kaalaman-month-summary > span:nth-child(2) { min-width: 0; }
        .kaalaman-month-title .kaalaman-month-title-main { color: #030813; font-size: inherit; font-weight: 900; }
        .kaalaman-month-complete { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; margin-left: auto; color: #008f6b; background: #dcfce7; border: 1px solid #86efac; border-radius: 999px; }
        .kaalaman-month-complete[hidden] { display: none; }
        .kaalaman-month-complete svg { width: 16px; height: 16px; stroke: currentColor; stroke-width: 3; fill: none; }
        .kaalaman-chevron { display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; justify-self: end; }
        .kaalaman-month-title { display: inline-flex; align-items: baseline; flex-wrap: wrap; gap: 4px; max-width: 100%; line-height: 1.25; overflow-wrap: anywhere; }
        .kaalaman-progress-status, .kaalaman-video-status-pill, .kaalaman-infographic-status { display: inline-flex; align-items: center; gap: 6px; min-height: 28px; padding: 0 10px; color: #64748b; background: #eef2f7; border: 1px solid #dde6f0; border-radius: 999px; font-size: 11px; font-weight: 900; white-space: nowrap; }
        .kaalaman-progress-status.is-in-progress, .kaalaman-video-status-pill.is-in-progress, .kaalaman-infographic-status.is-in-progress { color: #1d4ed8; background: #eff6ff; border-color: #bfdbfe; }
        .kaalaman-progress-status.is-complete, .kaalaman-video-status-pill.is-complete, .kaalaman-infographic-status.is-complete { color: #008f6b; background: #dcfce7; border-color: #86efac; }
        .kaalaman-progress-status.is-saving, .kaalaman-video-status-pill.is-saving, .kaalaman-infographic-status.is-saving { color: #b45309; background: #fffbeb; border-color: #fde68a; }
        .kaalaman-status-card.is-progress { border-color: #bfdbfe; background: #eff6ff; }
        .kaalaman-status-card.is-complete { border-color: #86efac; background: #ecfdf5; }
        .kaalaman-status-card.is-needed { border-color: #facc15; background: #fffbeb; }
        .kaalaman-status-card.is-needed strong { color: #c2410c; }
        .kaalaman-video-complete-note { margin: 0; color: #52627d; font-size: 12px; font-weight: 800; }
        .kaalaman-youtube-card { display: grid; gap: 12px; padding: 12px; background: #ffffff; border: 1px solid #dde6f0; border-radius: 10px; box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05); }
        .kaalaman-youtube-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
        .kaalaman-youtube-head h4 { margin: 0; color: #030813; font-size: 14px; font-weight: 900; line-height: 1.25; }
        .kaalaman-youtube-head p { margin: 4px 0 0; color: #52627d; font-size: 12px; font-weight: 800; }
        .kaalaman-youtube-shell { position: relative; overflow: hidden; width: 100%; aspect-ratio: 16 / 9; background: #071127; border-radius: 10px; }
        .kaalaman-youtube-shell iframe { display: block; width: 100%; height: 100%; border: 0; }
        .kaalaman-video-loading { position: absolute; inset: 0; display: grid; place-items: center; gap: 10px; color: #ffffff; background: linear-gradient(135deg, rgba(3, 8, 19, .86), rgba(59, 7, 38, .76)); font-size: 12px; font-weight: 900; text-align: center; pointer-events: none; z-index: 2; }
        .kaalaman-video-loading span { width: 28px; height: 28px; border: 3px solid rgba(255, 255, 255, .35); border-top-color: #ffffff; border-radius: 999px; animation: kaalaman-spin .8s linear infinite; }
        .kaalaman-youtube-card.is-ready .kaalaman-video-loading { opacity: 0; pointer-events: none; transition: opacity .2s ease; }
        @keyframes kaalaman-spin { to { transform: rotate(360deg); } }
        .kaalaman-upload-history { display: grid; gap: 10px; padding: 14px; margin-top: 12px; background: #ffffff; border: 1px solid #dde6f0; border-radius: 10px; }
        .kaalaman-upload-history h3 { display: flex; align-items: center; gap: 8px; margin: 0; color: #030813; font-size: 14px; font-weight: 900; }
        .kaalaman-upload-history-list { display: grid; gap: 8px; }
        .kaalaman-upload-history-item { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: center; gap: 10px; padding: 10px; background: #f8fafc; border: 1px solid #e5edf6; border-radius: 8px; }
        .kaalaman-upload-history-item strong { display: block; color: #030813; font-size: 13px; font-weight: 900; overflow-wrap: anywhere; }
        .kaalaman-upload-history-item span { display: block; margin-top: 3px; color: #52627d; font-size: 12px; font-weight: 800; overflow-wrap: anywhere; }
        .kaalaman-upload-delete { min-height: 34px; padding: 0 12px; color: #b91c1c; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 8px; font-size: 11px; font-weight: 900; cursor: pointer; }
        .kaalaman-upload-error { margin: 2px 0 0; padding: 9px 11px; color: #b91c1c; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 8px; font-size: 12px; font-weight: 900; }
        .kaalaman-upload-error[hidden] { display: none; }
        @media (max-width: 1024px) {
            .kaalaman-stage-viewport { grid-template-columns: 40px minmax(0, 1fr) 40px; gap: 8px; }
            .kaalaman-stage-arrow { width: 40px; height: 40px; min-height: 40px; padding: 0; }
            .kaalaman-trimester-card { flex-basis: calc((100% - 10px) / 2); }
        }

        @media (max-width: 760px) {
            .kaalaman-shell { gap: 14px; width: 100%; max-width: 100%; overflow-x: hidden; }
            .kaalaman-hero { grid-template-columns: 1fr; gap: 14px; padding: 18px; border-radius: 14px; }
            .kaalaman-hero-side { gap: 12px; }
            .kaalaman-language-switch { padding: 12px; }
            .kaalaman-hero h1 { font-size: 20px; line-height: 1.2; }
            .kaalaman-hero p { margin-top: 10px; font-size: 12px; line-height: 1.55; }
            .kaalaman-focus-card { min-height: 0; padding: 14px; }
            .kaalaman-focus-card strong { font-size: 17px; line-height: 1.25; overflow-wrap: anywhere; }
            .kaalaman-timeline-shell { gap: 14px; padding: 14px; border-radius: 16px; }
            .kaalaman-timeline-header { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: start; gap: 10px; }
            .kaalaman-timeline-header strong { max-width: 100%; font-size: 17px; line-height: 1.25; overflow-wrap: anywhere; }
            .kaalaman-timeline-count { min-height: 28px; padding: 0 10px; font-size: 10px; }
            .kaalaman-stage-viewport { grid-template-columns: 34px minmax(0, 1fr) 34px; gap: 6px; }
            .kaalaman-stage-arrow { width: 34px; height: 34px; min-height: 34px; padding: 0; }
            .kaalaman-stage-arrow svg { width: 17px; height: 17px; }
            .kaalaman-trimester-grid { gap: 10px; padding: 2px 0 6px; }
            .kaalaman-trimester-card { flex-basis: 100%; gap: 10px; padding: 14px; border-radius: 12px; }
            .kaalaman-stage-head { grid-template-columns: 40px minmax(0, 1fr); align-items: start; }
            .kaalaman-stage-icon { width: 40px; height: 40px; border-radius: 11px; }
            .kaalaman-stage-kicker { font-size: 9px; }
            .kaalaman-trimester-title { font-size: 15px; line-height: 1.18; }
            .kaalaman-pill { min-height: 24px; padding: 5px 10px; font-size: 9px; }
            .kaalaman-stage-bulletin > span { padding: 10px; font-size: 11px; }
            .kaalaman-slider-pagination { gap: 8px; min-height: 30px; padding-top: 0; }
            .kaalaman-slider-dot { width: 9px; height: 28px; min-width: 0; min-height: 28px; padding: 0; }
            .kaalaman-slider-dot.is-active { width: 36px; }
            .kaalaman-managed-content { padding: 14px; }
            .kaalaman-managed-content-head,
            .kaalaman-youtube-head { align-items: flex-start; flex-direction: column; }
            .kaalaman-managed-content-grid,
            .kaalaman-info-grid,
            .kaalaman-video-grid,
            .kaalaman-status-grid,
            .kaalaman-lower-grid,
            .kaalaman-upload-row { grid-template-columns: 1fr; }
            .kaalaman-youtube-head > div,
            .kaalaman-youtube-head .kaalaman-video-status-pill { width: 100%; }
            .kaalaman-video-status-pill,
            .kaalaman-progress-status,
            .kaalaman-infographic-status { min-height: 28px; white-space: normal; text-align: left; }
            .kaalaman-read-panel { align-items: stretch; flex-direction: column; }
            .kaalaman-button { width: 100%; min-height: 42px; }
            .kaalaman-more { justify-self: stretch; }
            .kaalaman-task-card,
            .kaalaman-infographic-card { grid-template-columns: auto minmax(0, 1fr); align-items: start; }
        }

        @media (max-width: 560px) {
            .kaalaman-video-modal.kaalaman-infographic-modal:not([hidden]) { display: flex; align-items: center; justify-content: center; padding: 14px; }
            .kaalaman-infographic-modal .kaalaman-video-dialog { width: 100%; max-height: calc(var(--kaalaman-modal-height, 100dvh) - 28px); padding: 24px 20px 0; border-radius: 18px; }
            .kaalaman-infographic-modal .kaalaman-video-close { top: 14px; right: 14px; width: 40px; height: 40px; }
            .kaalaman-infographic-modal .kaalaman-infographic-icon.is-large { margin-right: 52px; margin-bottom: 8px; }
            .kaalaman-infographic-modal .kaalaman-video-dialog h2 { padding-right: 6px; font-size: 23px; line-height: 1.18; overflow-wrap: anywhere; }
            .kaalaman-infographic-modal .kaalaman-modal-actions { position: sticky; bottom: 0; margin: 8px -20px 0; padding: 12px 20px calc(14px + env(safe-area-inset-bottom)); background: #ffffff; border-top: 1px solid #e5edf6; box-shadow: 0 -10px 18px rgba(15, 23, 42, 0.06); }
            .kaalaman-timeline-header { grid-template-columns: 1fr; }
            .kaalaman-timeline-count { justify-self: start; }
            .kaalaman-stage-viewport { grid-template-columns: 30px minmax(0, 1fr) 30px; gap: 5px; }
            .kaalaman-stage-arrow { width: 30px; height: 30px; min-height: 30px; }
            .kaalaman-trimester-card { padding: 13px; }
            .kaalaman-month-summary { grid-template-columns: 1fr auto; gap: 10px; min-height: 0; padding: 13px 14px; }
            .kaalaman-month-badge { grid-column: 1; justify-self: start; min-width: 0; min-height: 26px; padding: 0 11px; font-size: 10px; }
            .kaalaman-month-summary > span:nth-child(2) { grid-column: 1 / -1; }
            .kaalaman-month-title { display: block; font-size: 15px; }
            .kaalaman-month-title span { display: inline; font-size: 11px; }
            .kaalaman-month-hint { display: block; margin-top: 5px; }
            .kaalaman-month-complete { grid-column: 2; grid-row: 1; width: 30px; height: 30px; }
            .kaalaman-chevron { grid-column: 2; grid-row: 2; align-self: start; }
            .kaalaman-month-body { gap: 14px; padding: 6px 14px 16px; }
            .kaalaman-info-card,
            .kaalaman-youtube-card,
            .kaalaman-status-panel,
            .kaalaman-risk-panel,
            .kaalaman-note-panel,
            .kaalaman-complete-panel,
            .kaalaman-upload-history { padding: 12px; }
            .kaalaman-status-card,
            .kaalaman-upload-history-item { padding: 10px; }
        }

        @media (max-width: 420px) {
            .kaalaman-hero { padding: 16px; }
            .kaalaman-hero h1 { font-size: 18px; }
            .kaalaman-language-option { min-height: 34px; padding: 0 8px; font-size: 10px; }
            .kaalaman-stage-viewport { grid-template-columns: 1fr; }
            .kaalaman-stage-arrow { display: none; }
            .kaalaman-trimester-card { flex-basis: 100%; }
            .kaalaman-managed-content-grid { grid-template-columns: 1fr; }
            .kaalaman-slider-dot { width: 8px; height: 24px; min-height: 24px; }
            .kaalaman-slider-dot.is-active { width: 32px; }
        }
    </style>
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/inay-infographics.css') }}">
@endpush

@section('content')
    <section class="kaalaman-shell" aria-label="INAY Kaalaman learning module" data-kaalaman-page>
        <div class="kaalaman-hero">
            <div>
                <h1>{!! $iconBook !!} <span {!! $i18n('INAY Kaalaman Learning Hub', 'INAY Kaalaman Learning Hub') !!}>INAY Kaalaman Learning Hub</span></h1>
                <p {!! $i18n('Your month-by-month guide through pregnancy. Learn about body changes, baby development, proper nutrition, and important reminders to help keep you and your baby safe.', 'Ang iyong gabay sa bawat buwan ng pagbubuntis. Alamin ang mga pagbabago sa iyong katawan, pag-unlad ng sanggol, wastong nutrisyon, at mahahalagang paalala upang manatiling ligtas kayong mag-ina.') !!}>Your month-by-month guide through pregnancy. Learn about body changes, baby development, proper nutrition, and important reminders to help keep you and your baby safe.</p>
            </div>
            <div class="kaalaman-hero-side">
                <div class="kaalaman-focus-card">
                    <span class="kaalaman-eyebrow" {!! $i18n('Current Learning Focus', 'Kasalukuyang Pokus sa Pag-aaral') !!}>Current Learning Focus</span>
                    <strong data-kaalaman-focus data-focus-en="Month 1 (Weeks 1-4)" data-focus-tl="Buwan 1 (Linggo 1-4)">Month 1 (Weeks 1-4)</strong>
                    <small data-overall-progress-copy data-completed-months="{{ $kaalamanOverallProgress['completed_months'] ?? 0 }}" data-total-months="{{ $kaalamanOverallProgress['total_months'] ?? 10 }}">{{ $kaalamanOverallProgress['completed_months'] ?? 0 }} of {{ $kaalamanOverallProgress['total_months'] ?? 10 }} months completed</small>
                </div>
                <div class="kaalaman-language-switch" role="group" aria-label="INAY Kaalaman language">
                    <span {!! $i18n('Language', 'Wika') !!}>Language</span>
                    <div class="kaalaman-language-options">
                        <button class="kaalaman-language-option is-active" type="button" data-language-choice="en">English</button>
                        <button class="kaalaman-language-option" type="button" data-language-choice="tl">Tagalog</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="kaalaman-timeline-shell">
            <div class="kaalaman-timeline-header">
                <div>
                    <span class="kaalaman-eyebrow" {!! $i18n('Learning Path', 'Learning Path') !!}>Learning Path</span>
                    <strong {!! $i18n('Pregnancy and newborn care path', 'Pregnancy at newborn care path') !!}>Pregnancy and newborn care path</strong>
                </div>
                <span class="kaalaman-timeline-count" {!! $i18n('6 care stages', '6 yugto ng pangangalaga') !!}>6 care stages</span>
            </div>

            <div class="kaalaman-stage-viewport" aria-label="Pregnancy and newborn care path controls">
                <button class="kaalaman-stage-arrow" type="button" aria-label="Previous care stage" data-stage-arrow="prev">{!! $iconChevronLeft !!}</button>
                <div class="kaalaman-trimester-grid" role="tablist" aria-label="Maternal health learning timeline" data-stage-timeline>
                    @foreach ($learningStages as $stageKey => $card)
                        @php
                            $stageTl = $stageTranslations[$stageKey] ?? [];
                        @endphp
                        <button class="kaalaman-trimester-card @if($loop->first) is-selected @endif" type="button" role="tab" aria-selected="{{ $loop->first ? 'true' : 'false' }}" data-stage-button="{{ $stageKey }}" data-focus-en="{{ $card['focus'] }}" data-focus-tl="{{ $stageTl['focus'] ?? $card['focus'] }}" data-focus="{{ $card['focus'] }}">
                            <span class="kaalaman-stage-head">
                                <span class="kaalaman-stage-icon" aria-hidden="true">{!! $stageIcons[$card['icon']] ?? $iconBook !!}</span>
                                <span>
                                    <span class="kaalaman-stage-kicker" {!! $i18n('Care stage '.$loop->iteration, 'Yugto '.$loop->iteration) !!}>Care stage {{ $loop->iteration }}</span>
                                    <span class="kaalaman-trimester-title" {!! $i18n($card['title'], $stageTl['title'] ?? $card['title']) !!}>{{ $card['title'] }}</span>
                                    <span class="kaalaman-pill @if(! $loop->first) is-muted @endif" {!! $i18n($card['label'], $stageTl['label'] ?? $card['label']) !!}>{{ $card['label'] }}</span>
                                </span>
                            </span>
                            <span class="kaalaman-stage-progress" aria-hidden="true"><span style="width: {{ $card['progress'] }}%"></span></span>
                            <span class="kaalaman-stage-percent" data-stage-percent="{{ $card['progress'] }}">{{ $card['progress'] }}% complete</span>
                            <span class="kaalaman-stage-bulletin">
                                <span><strong {!! $i18n('Expected outcome', 'Inaasahang resulta') !!}>Expected outcome</strong><em {!! $i18n($card['output'], $stageTl['output'] ?? $card['output']) !!}>{{ $card['output'] }}</em></span>
                            </span>
                        </button>
                    @endforeach
                </div>
                <button class="kaalaman-stage-arrow" type="button" aria-label="Next care stage" data-stage-arrow="next">{!! $iconChevronRight !!}</button>
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
                @php
                    $panelTl = $continuumTranslations[$stageKey] ?? [];
                @endphp
                <section class="kaalaman-stage-detail is-hidden" data-stage-detail="{{ $stageKey }}">
                    <div class="kaalaman-stage-detail-head">
                        <span class="kaalaman-stage-icon is-large" aria-hidden="true">{!! $stageIcons[$panel['icon']] ?? $iconBook !!}</span>
                        <div>
                            <span class="kaalaman-pill" {!! $i18n($panel['label'], $stageTranslations[$stageKey]['label'] ?? $panel['label']) !!}>{{ $panel['label'] }}</span>
                            <h2 {!! $i18n($panel['title'], $stageTranslations[$stageKey]['title'] ?? $panel['title']) !!}>{{ $panel['title'] }}</h2>
                            <p {!! $i18n($panel['summary'], $panelTl['summary'] ?? $panel['summary']) !!}>{{ $panel['summary'] }}</p>
                        </div>
                    </div>

                    <div class="kaalaman-topic-grid" aria-label="{{ $panel['title'] }} topics">
                        @foreach ($panel['topics'] as $topic)
                            <span {!! $i18n($topic, $panelTl['topics'][$loop->index] ?? $topic) !!}>{{ $topic }}</span>
                        @endforeach
                    </div>

                    <section class="kaalaman-read-panel">
                        <div>
                            <h3>{!! $iconBook !!} <span {!! $i18n('Reading Checkpoint', 'Reading Checkpoint') !!}>Reading Checkpoint</span></h3>
                            <p {!! $i18n('Review these topics to prepare for this care stage.', 'Basahin ang mga paksa para makapaghanda sa yugtong ito ng pangangalaga.') !!}>Review these topics to prepare for this care stage.</p>
                        </div>
                        <span class="kaalaman-progress-status" data-status-static="not_started">Not Started</span>
                    </section>

                    <div class="kaalaman-status-grid">
                        <div class="kaalaman-status-card"><span class="kaalaman-status-mini-icon">{!! $iconBook !!}</span><div><span {!! $i18n('Reading', 'Pagbabasa') !!}>Reading</span><strong {!! $i18n('Pending', 'Nakabinbin') !!}>Pending</strong></div></div>
                        <div class="kaalaman-status-card"><span class="kaalaman-status-mini-icon is-blue">{!! $iconVideo !!}</span><div><span {!! $i18n('Videos', 'Mga Video') !!}>Videos</span><strong>0/{{ count($panel['topics']) }}</strong></div></div>
                        <div class="kaalaman-status-card is-needed"><span class="kaalaman-status-mini-icon is-warning">{!! $iconTask !!}</span><div><span {!! $i18n($stageKey === 'labor-delivery' ? 'Counseling Tasks' : 'Medical Tasks', $stageKey === 'labor-delivery' ? 'Mga Gawain sa Counseling' : 'Gawaing Medikal') !!}>{{ $stageKey === 'labor-delivery' ? 'Counseling Tasks' : 'Medical Tasks' }}</span><strong {!! $i18n('Needed', 'Kailangan') !!}>Needed</strong></div></div>
                    </div>

                    <div class="kaalaman-task-list">
                        @foreach ($panel['tasks'] as $task)
                            <article class="kaalaman-task-card">
                                <span class="kaalaman-task-icon">{!! $iconFile !!}</span>
                                <div>
                                    <h4 {!! $i18n($task, $panelTl['tasks'][$loop->index] ?? $task) !!}>{{ $task }}</h4>
                                    <p><strong {!! $i18n('Timing: '.$panel['label'], 'Oras: '.($stageTranslations[$stageKey]['label'] ?? $panel['label'])) !!}>Timing: {{ $panel['label'] }}</strong><br><span {!! $i18n($stageKey === 'labor-delivery' ? 'Importance: Supports informed, voluntary decisions with professional guidance.' : 'Importance: Keeps mother and baby care aligned with the next health visit.', $stageKey === 'labor-delivery' ? 'Kahalagahan: Sinusuportahan ang malaya at may sapat na kaalamang desisyon kasama ang propesyonal na gabay.' : 'Kahalagahan: Tinutulungan na manatiling tugma ang pangangalaga sa ina at sanggol sa susunod na health visit.') !!}>{{ $stageKey === 'labor-delivery' ? 'Importance: Supports informed, voluntary decisions with professional guidance.' : 'Importance: Keeps mother and baby care aligned with the next health visit.' }}</span></p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach

            @foreach ($months as $month)
                @php
                    $monthTl = $monthTranslations[$month['month']] ?? [];
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
                    $requiredVideoTotal = (int) ($monthProgress['total_videos'] ?? count($month['videos']));
                    $requiredVideoWatched = (int) ($monthProgress['watched_videos'] ?? 0);
                    $videoStatusText = $requiredVideoTotal > 0 ? "{$requiredVideoWatched}/{$requiredVideoTotal} Watched" : 'No video posted';
                    $videoStatusClass = $requiredVideoTotal > 0
                        ? ($requiredVideoWatched >= $requiredVideoTotal ? 'is-complete' : ($requiredVideoWatched > 0 ? 'is-progress' : ''))
                        : '';
                    $isFamilyPlanningMonth = $month['stage'] === 'labor-delivery';
                @endphp
                <details class="kaalaman-month @if($month['stage'] !== 'first-trimester') is-hidden @endif" data-stage-detail="{{ $month['stage'] }}" data-month="{{ $month['month'] }}">
                    <summary class="kaalaman-month-summary">
                        <span class="kaalaman-month-badge" {!! $i18n('Month '.$month['month'], 'Buwan '.$month['month']) !!}>Month {{ $month['month'] }}</span>
                        <span>
                            <span class="kaalaman-month-title"><span class="kaalaman-month-title-main" {!! $i18n($month['title'], $monthTl['title'] ?? $month['title']) !!}>{{ $month['title'] }}</span> <span {!! $i18n('('.$month['weeks'].')', '('.($monthTl['weeks'] ?? $month['weeks']).')') !!}>({{ $month['weeks'] }})</span></span>
                            <span class="kaalaman-month-hint" {!! $i18n($month['current'] ? 'This is your current month' : 'Open details', $month['current'] ? 'Kasalukuyang buwan mo ito' : 'Buksan ang detalye') !!}>{{ $month['current'] ? 'This is your current month' : 'Open details' }}</span>
                        </span>
                        <span class="kaalaman-month-complete" data-month-complete @if(! $requirementsComplete) hidden @endif>{!! $iconCheck !!}</span>
                        <span class="kaalaman-chevron">{!! $iconChevron !!}</span>
                    </summary>

                    <div class="kaalaman-month-body">
                        <div class="kaalaman-info-grid">
                            <article class="kaalaman-info-card"><h3>{!! $iconHeart !!} <span {!! $i18n($isFamilyPlanningMonth ? 'Planning Goals' : 'Baby Development', $isFamilyPlanningMonth ? 'Mga Layunin sa Pagpaplano' : 'Pag-unlad ng Sanggol') !!}>{{ $isFamilyPlanningMonth ? 'Planning Goals' : 'Baby Development' }}</span></h3><p {!! $i18n($month['baby'], $monthTl['baby'] ?? $month['baby']) !!}>{{ $month['baby'] }}</p></article>
                            <article class="kaalaman-info-card is-blue"><h3>{!! $iconPulse !!} <span {!! $i18n($isFamilyPlanningMonth ? 'Health Considerations' : 'Maternal Changes', $isFamilyPlanningMonth ? 'Mga Dapat Isaalang-alang sa Kalusugan' : 'Pagbabago sa Katawan ng Ina') !!}>{{ $isFamilyPlanningMonth ? 'Health Considerations' : 'Maternal Changes' }}</span></h3><p {!! $i18n($month['mother'], $monthTl['mother'] ?? $month['mother']) !!}>{{ $month['mother'] }}</p></article>
                            <article class="kaalaman-info-card"><h3 {!! $i18n($isFamilyPlanningMonth ? 'Questions to Discuss' : 'Expected Symptoms This Month', $isFamilyPlanningMonth ? 'Mga Tanong na Dapat Talakayin' : 'Inaasahang Sintomas sa Buwang Ito') !!}>{{ $isFamilyPlanningMonth ? 'Questions to Discuss' : 'Expected Symptoms This Month' }}</h3><p {!! $i18n($month['symptoms'], $monthTl['symptoms'] ?? $month['symptoms']) !!}>{{ $month['symptoms'] }}</p></article>
                            <article class="kaalaman-info-card is-blue"><h3 {!! $i18n($isFamilyPlanningMonth ? 'Counseling Preparation' : 'Nutritional Guidance', $isFamilyPlanningMonth ? 'Paghahanda sa Counseling' : 'Gabay sa Wastong Nutrisyon') !!}>{{ $isFamilyPlanningMonth ? 'Counseling Preparation' : 'Nutritional Guidance' }}</h3><p {!! $i18n($month['nutrition'], $monthTl['nutrition'] ?? $month['nutrition']) !!}>{{ $month['nutrition'] }}</p></article>
                        </div>

                        <section class="kaalaman-read-panel" data-reading-panel data-month="{{ $month['month'] }}" data-item-key="month-{{ $month['month'] }}-reading" data-current-status="{{ $readingProgress['status'] }}">
                            <div>
                                <h3>{!! $iconBook !!} <span {!! $i18n('Reading Guide for Month '.$month['month'], 'Gabay sa Pagbabasa para sa Buwan '.$month['month']) !!}>Reading Guide for Month {{ $month['month'] }}</span></h3>
                                <p data-reading-copy data-reading-complete-en="Reading guide completed and saved." data-reading-complete-tl="Nabasa at na-save na ang gabay sa pagbabasa." data-reading-pending-en="Click Mark as Read once you finish this month&apos;s guide." data-reading-pending-tl="I-click ang Mark as Read kapag natapos mo nang basahin ang gabay sa buwang ito.">{{ $readingProgress['status'] === 'read' ? 'Reading guide completed and saved.' : 'Click Mark as Read once you finish this month\'s guide.' }}</p>
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

                        <h3 class="kaalaman-section-title">{!! $iconVideo !!} <span {!! $i18n('Trimester Videos', 'Mga Video ng Trimester') !!}>Trimester Videos</span></h3>
                        @if (count($month['videos']) > 0)
                            <div class="kaalaman-video-grid">
                                @foreach ($month['videos'] as $video)
                                    @php
                                        $videoIndex = $loop->index;
                                        $videoKey = $video['key'] ?? "month-{$month['month']}-video-{$videoIndex}";
                                        $videoProgress = $videoProgressItems->firstWhere('key', $videoKey) ?? ['status' => 'not_started', 'label' => 'Not Started', 'completed_at' => null];
                                        $playerId = "kaalaman-youtube-{$month['month']}-{$videoIndex}";
                                        $embedUrl = 'https://www.youtube.com/embed/'.$video['youtube_id'].'?'.http_build_query([
                                            'enablejsapi' => 1,
                                            'playsinline' => 1,
                                            'rel' => 0,
                                            'modestbranding' => 1,
                                            'origin' => request()->getSchemeAndHttpHost(),
                                        ]);
                                    @endphp
                                    <article class="kaalaman-youtube-card {{ $videoProgress['status'] === 'watched' ? 'is-complete' : '' }}" data-youtube-video data-month="{{ $month['month'] }}" data-video-key="{{ $videoKey }}" data-video-title="{{ $video['title'] }}" data-youtube-id="{{ $video['youtube_id'] }}" data-current-status="{{ $videoProgress['status'] }}" data-player-id="{{ $playerId }}">
                                        <div class="kaalaman-youtube-head">
                                            <div>
                                                <h4>{{ $video['title'] }}</h4>
                                                <p>{{ $video['tag'] }} - {{ ($video['required'] ?? false) ? 'Required video' : $video['time'] }}</p>
                                            </div>
                                            <span class="kaalaman-video-status-pill {{ $videoProgress['status'] === 'watched' ? 'is-complete' : ($videoProgress['status'] === 'in_progress' ? 'is-in-progress' : '') }}" data-video-status>{{ $videoProgress['label'] }}</span>
                                        </div>
                                        <div class="kaalaman-youtube-shell">
                                            <div class="kaalaman-video-loading" data-video-loading><span></span><b {!! $i18n('Preparing video...', 'Inihahanda ang video...') !!}>Preparing video...</b></div>
                                            <iframe id="{{ $playerId }}" src="{{ $embedUrl }}" title="{{ $video['title'] }}" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                                        </div>
                                        <p class="kaalaman-video-complete-note" {!! $i18n('Status changes to In Progress when playback starts and Watched only after the video finishes.', 'Magiging In Progress ang status kapag nagsimula ang panonood at Watched lamang kapag natapos ang video.') !!}>Status changes to In Progress when playback starts and Watched only after the video finishes.</p>
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <p class="kaalaman-video-complete-note" {!! $i18n('No trimester video is assigned to this month. Complete the reading guide and monthly learning content to finish this month.', 'Walang trimester video na naka-assign sa buwang ito. Kumpletuhin ang reading guide at monthly learning content upang matapos ang buwan na ito.') !!}>No trimester video is assigned to this month. Complete the reading guide and monthly learning content to finish this month.</p>
                        @endif

                        <a class="kaalaman-button kaalaman-more" href="{{ route('inay-kaalaman.videos', ['month' => $month['month']]) }}" {!! $i18n('View More Videos', 'Tingnan Pa ang Mga Video') !!}>View More Videos</a>

                        <div class="kaalaman-divider"></div>

                        <section class="kaalaman-risk-panel">
                            <h3>{!! $iconAlert !!} <span {!! $i18n($isFamilyPlanningMonth ? 'When to Seek Guidance' : 'Risk Alerts and Consequences', $isFamilyPlanningMonth ? 'Kailan Dapat Humingi ng Gabay' : 'Mahahalagang Babala at Panganib') !!}>{{ $isFamilyPlanningMonth ? 'When to Seek Guidance' : 'Risk Alerts and Consequences' }}</span></h3>
                            <div class="kaalaman-risk-item">
                                <span class="kaalaman-risk-icon">{!! $iconAlert !!}</span>
                                <p class="kaalaman-risk-copy"><strong class="kaalaman-risk-name" {!! $i18n($month['risk'], $monthTl['risk'] ?? $month['risk']) !!}>{{ $month['risk'] }}</strong><strong {!! $i18n($isFamilyPlanningMonth ? 'Why it matters:' : 'Possible consequence:', $isFamilyPlanningMonth ? 'Bakit ito mahalaga:' : 'Posibleng epekto:') !!}>{{ $isFamilyPlanningMonth ? 'Why it matters:' : 'Possible consequence:' }}</strong> <span {!! $i18n($isFamilyPlanningMonth ? 'This may require prompt professional assessment.' : 'This may indicate an urgent condition.', $isFamilyPlanningMonth ? 'Maaaring kailanganin nito ang agarang professional assessment.' : 'Maaaring senyales ito ng agarang kundisyon.') !!}>{{ $isFamilyPlanningMonth ? 'This may require prompt professional assessment.' : 'This may indicate an urgent condition.' }}</span> <br><strong {!! $i18n('Recommendation:', 'Rekomendasyon:') !!}>Recommendation:</strong> <span {!! $i18n($isFamilyPlanningMonth ? 'Contact a qualified healthcare provider; seek urgent care for severe symptoms.' : 'Seek immediate care at the nearest Barangay Health Station, RHU, or hospital.', $isFamilyPlanningMonth ? 'Makipag-ugnayan sa kwalipikadong healthcare provider; humingi ng agarang tulong kapag malala ang sintomas.' : 'Magpakonsulta agad sa pinakamalapit na Barangay Health Station, RHU, o ospital.') !!}>{{ $isFamilyPlanningMonth ? 'Contact a qualified healthcare provider; seek urgent care for severe symptoms.' : 'Seek immediate care at the nearest Barangay Health Station, RHU, or hospital.' }}</span></p>
                            </div>
                        </section>

                        <div class="kaalaman-lower-grid">
                            <section>
                                <h3 class="kaalaman-task-title">{!! $iconTask !!} <span {!! $i18n('Vaccines and Medical Tasks for This Month', 'Mga Bakuna at Gawaing Medikal para sa Buwang Ito') !!}>Vaccines and Medical Tasks for This Month</span></h3>
                                <div class="kaalaman-task-list">
                                    <article class="kaalaman-task-card">
                                        <span class="kaalaman-task-icon">{!! $iconFile !!}</span>
                                        <div><h4 {!! $i18n('Supplement Prescription or Refill Record', 'Reseta o Refill Record ng Supplement') !!}>Supplement Prescription or Refill Record</h4><p><strong {!! $i18n('Timing: Keep your latest prescription or refill record updated', 'Oras: Panatilihing updated ang pinakabagong reseta o refill record') !!}>Timing: Keep your latest prescription or refill record updated</strong><br><span {!! $i18n('Importance: Helps your Program Staff confirm the medicine or supplements you need.', 'Kahalagahan: Tinutulungan ang Program Staff na makumpirma ang gamot o supplements na kailangan mo.') !!}>Importance: Helps your Program Staff confirm the medicine or supplements you need.</span></p></div>
                                    </article>
                                </div>
                            </section>

                            @include('modules.partials.infographic-cards', ['infographics' => $infographicsByMonth->get($month['month'], collect())])
                        </div>

                        <form class="kaalaman-upload-panel" method="POST" action="{{ route('inay-kaalaman.upload') }}" enctype="multipart/form-data" data-upload-form novalidate>
                            @csrf
                            <input type="hidden" name="month" value="{{ $month['month'] }}">
                            <h3 class="kaalaman-upload-title">{!! $iconUpload !!} <span {!! $i18n('Upload Prenatal Records and Receipts', 'Mag-upload ng Prenatal Records at Resibo') !!}>Upload Prenatal Records and Receipts</span></h3>
                            <div class="kaalaman-upload-row">
                                <div class="kaalaman-upload-field"><label {!! $i18n('Record Type', 'Uri ng Record') !!}>Record Type</label><select name="record_type" required><option value="Prenatal Records and Receipts" {!! $i18n('Prenatal Records and Receipts', 'Prenatal Records at Resibo') !!}>Prenatal Records and Receipts</option><option value="Certificate" {!! $i18n('Certificate', 'Sertipiko') !!}>Certificate</option><option value="Other Documents" {!! $i18n('Other Supporting Document', 'Iba Pang Suportang Dokumento') !!}>Other Supporting Document</option></select></div>
                                <div class="kaalaman-upload-field">
                                    <label {!! $i18n('Select Document', 'Pumili ng Dokumento') !!}>Select Document</label>
                                    <label class="kaalaman-file-picker">
                                        <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required data-upload-input>
                                        <span {!! $i18n('Choose file', 'Pumili ng file') !!}>Choose file</span>
                                        <strong data-upload-file data-empty-en="No file chosen" data-empty-tl="Walang napiling file">No file chosen</strong>
                                    </label>
                                </div>
                                <div class="kaalaman-upload-actions">
                                    <button class="kaalaman-button kaalaman-upload-button" type="submit">{!! $iconUpload !!} <span {!! $i18n('Upload to Records', 'I-upload sa Records') !!}>Upload to Records</span></button>
                                    <button class="kaalaman-button secondary kaalaman-upload-cancel" type="button" data-upload-cancel hidden {!! $i18n('Cancel', 'Kanselahin') !!}>Cancel</button>
                                </div>
                            </div>
                            <p class="kaalaman-upload-error" data-upload-error hidden {!! $i18n('Please select a file before uploading.', 'Pumili muna ng file bago mag-upload.') !!}>Please select a file before uploading.</p>
                        </form>

                        <section class="kaalaman-upload-history">
                            <h3>{!! $iconFile !!} <span {!! $i18n('Prenatal Records and Receipts History', 'Kasaysayan ng Prenatal Records at Resibo') !!}>Prenatal Records and Receipts History</span></h3>
                            @if ($monthUploads->isEmpty())
                                <p class="kaalaman-video-complete-note" {!! $i18n('No prenatal records or receipts have been uploaded for this month yet.', 'Wala pang na-upload na prenatal records o resibo para sa buwang ito.') !!}>No prenatal records or receipts have been uploaded for this month yet.</p>
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
                                                <button class="kaalaman-upload-delete" type="submit" {!! $i18n('Delete', 'Burahin') !!}>Delete</button>
                                            </form>
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                        </section>

                        <section class="kaalaman-note-panel">
                            <h3>{!! $iconAlert !!} <span {!! $i18n('Program Staff Notes', 'Payo mula sa Program Staff') !!}>Program Staff Notes</span></h3>
                            <p {!! $i18n('Great job. Your upcoming checkups are scheduled every two weeks starting this month. Keep practicing regular deep-breathing exercises.', 'Magaling. Ang mga susunod mong checkup ay naka-schedule kada dalawang linggo simula ngayong buwan. Ipagpatuloy ang regular na deep-breathing exercises.') !!}>Great job. Your upcoming checkups are scheduled every two weeks starting this month. Keep practicing regular deep-breathing exercises.</p>
                        </section>

                        <section class="kaalaman-status-panel">
                            <h3 class="kaalaman-section-title">{!! $iconTask !!} <span {!! $i18n('Documentation Status', 'Status ng Dokumentasyon') !!}>Documentation Status</span></h3>
                            <p {!! $i18n('This shows reading, video, and uploaded-document progress for this month.', 'Ipinapakita nito ang progreso sa pagbabasa, video, at uploaded documents para sa buwang ito.') !!}>This shows reading, video, and uploaded-document progress for this month.</p>
                            <div class="kaalaman-status-grid">
                                <div class="kaalaman-status-card {{ $readingProgress['status'] === 'read' ? 'is-complete' : ($readingProgress['status'] === 'in_progress' ? 'is-progress' : '') }}"><span class="kaalaman-status-mini-icon">{!! $iconBook !!}</span><div><span {!! $i18n('Reading', 'Pagbabasa') !!}>Reading</span><strong data-status-reading data-status-value="{{ $readingProgress['status'] }}">{{ $readingProgress['label'] }}</strong></div></div>
                                <div class="kaalaman-status-card {{ $videoStatusClass }}"><span class="kaalaman-status-mini-icon is-blue">{!! $iconVideo !!}</span><div><span {!! $i18n('Required Video', 'Kailangang Video') !!}>Required Video</span><strong data-status-videos data-watched-videos="{{ $requiredVideoWatched }}" data-total-videos="{{ $requiredVideoTotal }}">{{ $videoStatusText }}</strong></div></div>
                                <div class="kaalaman-status-card {{ $infographicProgress['status'] === 'reviewed' ? 'is-complete' : ($infographicProgress['status'] === 'in_progress' ? 'is-progress' : 'is-needed') }}"><span class="kaalaman-status-mini-icon {{ $infographicProgress['status'] === 'reviewed' ? 'is-success' : 'is-warning' }}">{!! $iconFile !!}</span><div><span {!! $i18n('Infographic', 'Infographic') !!}>Infographic</span><strong data-status-infographic data-status-value="{{ $infographicProgress['status'] }}">{{ $infographicProgress['label'] }}</strong></div></div>
                                <div class="kaalaman-status-card {{ $hasPrenatalUpload ? 'is-complete' : 'is-needed' }}"><span class="kaalaman-status-mini-icon {{ $hasPrenatalUpload ? 'is-success' : 'is-warning' }}">{!! $iconTask !!}</span><div><span {!! $i18n('Prenatal Records and Receipts', 'Prenatal Records at Resibo') !!}>Prenatal Records and Receipts</span><strong data-status-static="{{ $hasPrenatalUpload ? 'uploaded' : 'needed' }}">{{ $hasPrenatalUpload ? 'Uploaded' : 'Needed' }}</strong></div></div>
                            </div>
                        </section>

                        <section class="kaalaman-complete-panel">
                            <span class="kaalaman-complete-icon">{!! $iconShield !!}</span>
                            <div><h3 data-completion-title data-complete="{{ $requirementsComplete ? 'true' : 'false' }}">{{ $requirementsComplete ? 'Month Completed' : 'Complete This Month\'s Learning Requirements' }}</h3><p data-completion-copy data-reading-status="{{ $readingProgress['status'] }}" data-infographic-status="{{ $infographicProgress['status'] }}" data-watched-videos="{{ $requiredVideoWatched }}" data-total-videos="{{ $requiredVideoTotal }}" data-uploaded-count="{{ $uploadedCount }}" data-uploaded-required="{{ $monthProgress['uploaded_required_documents'] ?? 0 }}" data-required-documents="{{ $monthProgress['required_documents'] ?? 1 }}" data-month-status="{{ $monthProgress['status'] ?? 'Not Started' }}">Reading: {{ $readingProgress['label'] }}. Required Video: {{ $videoStatusText }}. Infographic: {{ $infographicProgress['label'] }}. Records: {{ $monthProgress['uploaded_required_documents'] ?? 0 }}/{{ $monthProgress['required_documents'] ?? 1 }} uploaded. Overall Status: {{ $monthProgress['status'] ?? 'Not Started' }}. {{ $uploadedCount }} saved upload{{ $uploadedCount === 1 ? '' : 's' }}.</p></div>
                        </section>
                    </div>
                </details>
            @endforeach
        </div>
    </section>

    @include('modules.partials.infographic-viewer')

    <script src="{{ asset('js/inay-infographics.js') }}"></script>

    <script>
        const kaalamanProgressUrl = @json(route('inay-kaalaman.progress'));
        const kaalamanCsrfToken = @json(csrf_token());
        const kaalamanLanguageStorageKey = 'project-inay-kaalaman-language';
        let kaalamanCurrentLanguage = 'en';
        const kaalamanStatusLabels = {
            en: {
                not_started: 'Not Started',
                in_progress: 'In Progress',
                saving: 'Saving...',
                read: 'Read',
                watched: 'Watched',
                reviewed: 'Reviewed',
                completed: 'Completed',
                uploaded: 'Uploaded',
                needed: 'Needed',
                pending: 'Pending',
            },
            tl: {
                not_started: 'Hindi Pa Nasimulan',
                in_progress: 'Kasalukuyang Ginagawa',
                saving: 'Sine-save...',
                read: 'Nabasa',
                watched: 'Napanood',
                reviewed: 'Na-review',
                completed: 'Nakumpleto',
                uploaded: 'Na-upload',
                needed: 'Kailangan',
                pending: 'Nakabinbin',
            },
        };
        const kaalamanActionLabels = {
            en: {
                open_pdf: 'Open PDF',
                save_reviewed: 'Save as Reviewed',
                already_reviewed: 'Already Reviewed',
                close: 'Close',
                mark_read: 'Mark as Read',
                marked_read: 'Marked as Read',
                saving: 'Saving...',
                no_video: 'No video posted',
                watched: 'Watched',
                complete_suffix: 'complete',
                months_completed: (completed, total) => `${completed} of ${total} months completed`,
                completion_pending: 'Complete This Month\'s Learning Requirements',
                completion_done: 'Month Completed',
                completion_copy: ({ reading, video, infographic, uploadedRequired, requiredDocuments, status, uploads }) => `Reading: ${reading}. Required Video: ${video}. Infographic: ${infographic}. Records: ${uploadedRequired}/${requiredDocuments} uploaded. Overall Status: ${status}. ${uploads} saved upload${uploads === 1 ? '' : 's'}.`,
            },
            tl: {
                open_pdf: 'Buksan ang PDF',
                save_reviewed: 'I-save bilang Na-review',
                already_reviewed: 'Na-review Na',
                close: 'Isara',
                mark_read: 'Mark as Read',
                marked_read: 'Marked as Read',
                saving: 'Sine-save...',
                no_video: 'Walang video',
                watched: 'Napanood',
                complete_suffix: 'kumpleto',
                months_completed: (completed, total) => `${completed} sa ${total} buwan ang nakumpleto`,
                completion_pending: 'Kumpletuhin ang Learning Requirements ng Buwang Ito',
                completion_done: 'Nakumpleto ang Buwan',
                completion_copy: ({ reading, video, infographic, uploadedRequired, requiredDocuments, status, uploads }) => `Pagbabasa: ${reading}. Kailangang Video: ${video}. Infographic: ${infographic}. Records: ${uploadedRequired}/${requiredDocuments} na-upload. Kabuuang Status: ${status}. ${uploads} na-save na upload.`,
            },
        };

        const languagePack = () => kaalamanActionLabels[kaalamanCurrentLanguage] || kaalamanActionLabels.en;
        const statusLabel = (status) => (kaalamanStatusLabels[kaalamanCurrentLanguage] || kaalamanStatusLabels.en)[status] || status;
        const normalizeStatusKey = (label) => ({
            'Not Started': 'not_started',
            'In Progress': 'in_progress',
            'Reading in Progress...': 'in_progress',
            'Read': 'read',
            'Watched': 'watched',
            'Reviewed': 'reviewed',
            'Completed': 'completed',
        }[label] || String(label || '').toLowerCase().replace(/\s+/g, '_'));
        const videoCountText = (watched, total) => Number(total) > 0
            ? `${Number(watched) || 0}/${Number(total) || 0} ${languagePack().watched}`
            : languagePack().no_video;
        const updateOverallCopy = (overall = null) => {
            const overallCopy = document.querySelector('[data-overall-progress-copy]');
            if (!overallCopy) return;

            if (overall) {
                overallCopy.dataset.completedMonths = overall.completed_months || 0;
                overallCopy.dataset.totalMonths = overall.total_months || 0;
            }

            overallCopy.textContent = languagePack().months_completed(
                Number(overallCopy.dataset.completedMonths || 0),
                Number(overallCopy.dataset.totalMonths || 0),
            );
        };
        const setActionText = (element, key) => {
            if (!element) return;
            element.textContent = languagePack()[key] || element.textContent;
        };
        const setReadingPanelText = (panel) => {
            if (!panel) return;

            const button = panel.querySelector('[data-reading-button]');
            const copy = panel.querySelector('[data-reading-copy]');
            const isRead = (panel.dataset.currentStatus || 'not_started') === 'read';

            setActionText(button, isRead ? 'marked_read' : 'mark_read');
            if (copy) {
                copy.textContent = isRead
                    ? (kaalamanCurrentLanguage === 'tl' ? copy.dataset.readingCompleteTl : copy.dataset.readingCompleteEn)
                    : (kaalamanCurrentLanguage === 'tl' ? copy.dataset.readingPendingTl : copy.dataset.readingPendingEn);
            }
        };
        const setCompletionCopy = (copy) => {
            if (!copy) return;

            const reading = statusLabel(copy.dataset.readingStatus || 'not_started');
            const infographic = statusLabel(copy.dataset.infographicStatus || 'not_started');
            const status = statusLabel(normalizeStatusKey(copy.dataset.monthStatus || 'Not Started'));

            copy.textContent = languagePack().completion_copy({
                reading,
                video: videoCountText(copy.dataset.watchedVideos || 0, copy.dataset.totalVideos || 0),
                infographic,
                uploadedRequired: Number(copy.dataset.uploadedRequired || 0),
                requiredDocuments: Number(copy.dataset.requiredDocuments || 0),
                status,
                uploads: Number(copy.dataset.uploadedCount || 0),
            });
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
            element.dataset.statusValue = status;
            element.textContent = label ? statusLabel(normalizeStatusKey(label)) : statusLabel(status);
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

        const refreshLearningStageProgress = (overall = null) => {
            const stageMonths = {
                'first-trimester': [1, 2, 3],
                'second-trimester': [4, 5, 6],
                'third-trimester': [7, 8, 9],
                'labor-delivery': [10],
            };

            Object.entries(stageMonths).forEach(([stageKey, months]) => {
                const completed = months.filter((month) => {
                    const badge = document.querySelector(`[data-month="${month}"] [data-month-complete]`);

                    return badge && !badge.hidden;
                }).length;
                const percentage = months.length ? Math.round((completed / months.length) * 100) : 0;
                const stageButton = document.querySelector(`[data-stage-button="${stageKey}"]`);
                const progressBar = stageButton?.querySelector('.kaalaman-stage-progress span');
                const progressText = stageButton?.querySelector('.kaalaman-stage-percent');

                if (progressBar) progressBar.style.width = `${percentage}%`;
                if (progressText) {
                    progressText.dataset.stagePercent = percentage;
                    progressText.textContent = `${percentage}% ${languagePack().complete_suffix}`;
                }
            });

            updateOverallCopy(overall);
        };

        const updateMonthSummary = (summary, overall = null) => {
            if (!summary?.month) return;

            const monthPanel = document.querySelector(`[data-month="${summary.month}"]`);
            if (!monthPanel) return;

            const completeBadge = monthPanel.querySelector('[data-month-complete]');
            if (completeBadge) completeBadge.hidden = !summary.is_complete;

            const readingStatus = monthPanel.querySelector('[data-status-reading]');
            if (readingStatus) {
                readingStatus.dataset.statusValue = summary.reading?.status || 'not_started';
                readingStatus.textContent = statusLabel(summary.reading?.status || 'not_started');
            }

            const readingButton = monthPanel.querySelector('[data-reading-button]');
            if (readingButton && summary.reading?.status === 'read') {
                readingButton.disabled = true;
                readingButton.classList.add('is-done');
            }

            const readingCopy = monthPanel.querySelector('[data-reading-copy]');
            const readingPanel = monthPanel.querySelector('[data-reading-panel]');
            if (readingPanel && summary.reading?.status) {
                readingPanel.dataset.currentStatus = summary.reading.status;
                setReadingPanelText(readingPanel);
            } else if (readingCopy && summary.reading?.status === 'read') {
                readingCopy.textContent = kaalamanCurrentLanguage === 'tl' ? readingCopy.dataset.readingCompleteTl : readingCopy.dataset.readingCompleteEn;
            }

            const videoStatus = monthPanel.querySelector('[data-status-videos]');
            const videoText = (summary.total_videos || 0) > 0
                ? videoCountText(summary.watched_videos || 0, summary.total_videos || 0)
                : languagePack().no_video;
            if (videoStatus) {
                videoStatus.dataset.watchedVideos = summary.watched_videos || 0;
                videoStatus.dataset.totalVideos = summary.total_videos || 0;
                videoStatus.textContent = videoText;
            }

            const infographicStatus = monthPanel.querySelector('[data-status-infographic]');
            if (infographicStatus) {
                infographicStatus.dataset.statusValue = summary.infographic?.status || 'not_started';
                infographicStatus.textContent = statusLabel(summary.infographic?.status || 'not_started');
            }

            const title = monthPanel.querySelector('[data-completion-title]');
            if (title) {
                title.dataset.complete = summary.is_complete ? 'true' : 'false';
                title.textContent = summary.is_complete ? languagePack().completion_done : languagePack().completion_pending;
            }

            const copy = monthPanel.querySelector('[data-completion-copy]');
            if (copy) {
                const uploadedCount = summary.uploaded_documents?.length || 0;
                copy.dataset.readingStatus = summary.reading?.status || 'not_started';
                copy.dataset.infographicStatus = summary.infographic?.status || 'not_started';
                copy.dataset.watchedVideos = summary.watched_videos || 0;
                copy.dataset.totalVideos = summary.total_videos || 0;
                copy.dataset.uploadedRequired = summary.uploaded_required_documents || 0;
                copy.dataset.requiredDocuments = summary.required_documents || 0;
                copy.dataset.monthStatus = summary.status || 'Not Started';
                copy.dataset.uploadedCount = uploadedCount;
                setCompletionCopy(copy);
            }

            refreshLearningStageProgress(overall);
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
                button.textContent = languagePack().saving;

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
                    button.classList.add('is-done');
                    setReadingPanelText(panel);
                    updateMonthSummary(data.month_summary, data.overall);
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
        const stageArrowButtons = Array.from(document.querySelectorAll('[data-stage-arrow]'));
        let stageIgnoreScrollUntil = 0;

        const scrollStageIntoPlace = (button) => {
            if (!stageTimeline || !button) return;

            const maxScroll = Math.max(0, stageTimeline.scrollWidth - stageTimeline.clientWidth);
            const targetIndex = stageButtons.indexOf(button);
            const currentScroll = stageTimeline.scrollLeft;
            const buttonStart = button.offsetLeft;
            const buttonEnd = buttonStart + button.offsetWidth;
            const viewportEnd = currentScroll + stageTimeline.clientWidth;
            let nextScroll = currentScroll;

            if (targetIndex <= 0) {
                nextScroll = 0;
            } else if (targetIndex >= stageButtons.length - 1) {
                nextScroll = maxScroll;
            } else if (buttonStart < currentScroll) {
                nextScroll = buttonStart;
            } else if (buttonEnd > viewportEnd) {
                nextScroll = buttonEnd - stageTimeline.clientWidth;
            }

            const clampedScroll = Math.min(Math.max(nextScroll, 0), maxScroll);

            stageTimeline.scrollTo({
                left: clampedScroll,
                behavior: 'smooth',
            });
        };

        const selectedStageIndex = () => Math.max(0, stageButtons.findIndex((button) => button.classList.contains('is-selected')));

        const updateStageArrows = () => {
            const selectedIndex = selectedStageIndex();

            stageArrowButtons.forEach((button) => {
                button.disabled = button.dataset.stageArrow === 'prev'
                    ? selectedIndex <= 0
                    : selectedIndex >= stageButtons.length - 1;
            });
        };

        const closeMonthPanels = (stage = null) => {
            stageDetails.forEach((panel) => {
                if (panel.tagName.toLowerCase() !== 'details') return;
                if (stage && panel.dataset.stageDetail !== stage) return;
                panel.open = false;
            });
        };

        const selectLearningStage = (button, shouldSlide = true, closeMonths = false) => {
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

            stageDetails.forEach((panel) => {
                const isVisible = panel.dataset.stageDetail === stage;

                panel.classList.toggle('is-hidden', !isVisible);

                if (!isVisible && panel.tagName.toLowerCase() === 'details') {
                    panel.open = false;
                }
            });

            if (closeMonths) closeMonthPanels(stage);
            if (focusLabel) focusLabel.textContent = kaalamanCurrentLanguage === 'tl'
                ? (button.dataset.focusTl || button.dataset.focus)
                : (button.dataset.focusEn || button.dataset.focus);
            updateStageArrows();
            if (shouldSlide) {
                stageIgnoreScrollUntil = Date.now() + 1000;
                scrollStageIntoPlace(button);
            }
        };

        stageButtons.forEach((button) => {
            button.addEventListener('click', () => selectLearningStage(button, true, true));
        });

        stageDots.forEach((dot) => {
            dot.addEventListener('click', () => {
                const button = stageButtons.find((item) => item.dataset.stageButton === dot.dataset.stageDot);
                selectLearningStage(button, true, true);
            });
        });

        stageArrowButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const nextIndex = selectedStageIndex() + (button.dataset.stageArrow === 'next' ? 1 : -1);
                selectLearningStage(stageButtons[Math.min(Math.max(nextIndex, 0), stageButtons.length - 1)], true, true);
            });
        });

        const resetStageTimeline = () => {
            if (!stageTimeline || !stageButtons[0]) return;

            stageIgnoreScrollUntil = Date.now() + 350;
            stageTimeline.scrollLeft = 0;
            selectLearningStage(stageButtons[0], false, false);
        };

        updateStageArrows();
        resetStageTimeline();
        window.requestAnimationFrame(resetStageTimeline);
        window.addEventListener('load', () => window.requestAnimationFrame(resetStageTimeline), { once: true });

        let stageScrollFrame = null;
        stageTimeline?.addEventListener('scroll', () => {
            if (Date.now() < stageIgnoreScrollUntil) return;
            if (stageScrollFrame) return;

            stageScrollFrame = window.requestAnimationFrame(() => {
                stageScrollFrame = null;

                const timelineStart = stageTimeline.scrollLeft;
                const maxScroll = Math.max(0, stageTimeline.scrollWidth - stageTimeline.clientWidth);

                if (maxScroll <= 1) return;

                const timelineRect = stageTimeline.getBoundingClientRect();
                const timelineCenter = timelineRect.left + (timelineRect.width / 2);
                const nearestButton = timelineStart <= 2
                    ? stageButtons[0]
                    : timelineStart >= maxScroll - 2
                    ? stageButtons[stageButtons.length - 1]
                    : stageButtons.reduce((nearest, button) => {
                        const currentRect = button.getBoundingClientRect();
                        const nearestRect = nearest.getBoundingClientRect();
                        const currentDistance = Math.abs((currentRect.left + (currentRect.width / 2)) - timelineCenter);
                        const nearestDistance = Math.abs((nearestRect.left + (nearestRect.width / 2)) - timelineCenter);

                        return currentDistance < nearestDistance ? button : nearest;
                    }, stageButtons[0]);

                selectLearningStage(nearestButton, false, false);
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

        const setVideoCardStatus = (card, status) => {
            if (!card) return;

            card.dataset.currentStatus = status;
            setStatusPill(card.querySelector('[data-video-status]'), status);
        };

        const saveVideoCardStatus = async (card, status) => {
            if (!card) return;

            const previousStatus = card.dataset.currentStatus || 'not_started';

            if (previousStatus === 'watched' || previousStatus === status || card.dataset.savingStatus === status) {
                return;
            }

            card.dataset.savingStatus = status;

            try {
                if (status === 'watched') {
                    setStatusPill(card.querySelector('[data-video-status]'), 'saving');
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
                updateMonthSummary(data.month_summary, data.overall);
            } catch (error) {
                setVideoCardStatus(card, previousStatus);
                window.alert(error.message);
            } finally {
                delete card.dataset.savingStatus;
            }
        };

        const requiredVideoCards = Array.from(document.querySelectorAll('[data-youtube-video]'));
        const createKaalamanPlayers = () => {
            if (!window.YT?.Player) return;

            requiredVideoCards.forEach((card) => {
                if (card.dataset.playerBound === 'true') return;

                const playerElement = document.getElementById(card.dataset.playerId);
                if (!playerElement) return;

                card.dataset.playerBound = 'true';
                new YT.Player(playerElement, {
                    events: {
                        onReady: () => card.classList.add('is-ready'),
                        onStateChange: (event) => {
                            if (event.data === YT.PlayerState.PLAYING) {
                                saveVideoCardStatus(card, 'in_progress');
                            }

                            if (event.data === YT.PlayerState.ENDED) {
                                saveVideoCardStatus(card, 'watched');
                            }
                        },
                    },
                });
            });
        };

        if (requiredVideoCards.length > 0) {
            requiredVideoCards.forEach((card) => {
                document.getElementById(card.dataset.playerId)?.addEventListener('load', () => {
                    card.classList.add('is-ready');
                }, { once: true });
            });

            window.onYouTubeIframeAPIReady = createKaalamanPlayers;

            if (window.YT?.Player) {
                createKaalamanPlayers();
            } else if (!document.querySelector('script[src="https://www.youtube.com/iframe_api"]')) {
                const script = document.createElement('script');
                script.src = 'https://www.youtube.com/iframe_api';
                document.head.appendChild(script);
            }
        }

        const applyKaalamanLanguage = (language = 'en', persist = true) => {
            kaalamanCurrentLanguage = language === 'tl' ? 'tl' : 'en';
            document.querySelector('[data-kaalaman-page]')?.setAttribute('data-language', kaalamanCurrentLanguage);

            if (persist) {
                try {
                    window.localStorage.setItem(kaalamanLanguageStorageKey, kaalamanCurrentLanguage);
                } catch (error) {
                    // Language preference is cosmetic; ignore blocked storage.
                }
            }

            document.querySelectorAll('[data-language-choice]').forEach((button) => {
                const isActive = button.dataset.languageChoice === kaalamanCurrentLanguage;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });

            document.querySelectorAll('[data-i18n-en]').forEach((element) => {
                element.textContent = kaalamanCurrentLanguage === 'tl'
                    ? (element.dataset.i18nTl || element.dataset.i18nEn || element.textContent)
                    : (element.dataset.i18nEn || element.textContent);
            });

            document.querySelectorAll('[data-stage-percent]').forEach((element) => {
                element.textContent = `${Number(element.dataset.stagePercent || 0)}% ${languagePack().complete_suffix}`;
            });

            updateOverallCopy();

            const selectedStage = document.querySelector('[data-stage-button].is-selected');
            if (focusLabel && selectedStage) {
                focusLabel.textContent = kaalamanCurrentLanguage === 'tl'
                    ? (selectedStage.dataset.focusTl || selectedStage.dataset.focusEn || selectedStage.dataset.focus)
                    : (selectedStage.dataset.focusEn || selectedStage.dataset.focus);
            }

            document.querySelectorAll('[data-status-static]').forEach((element) => {
                element.textContent = statusLabel(element.dataset.statusStatic);
            });

            document.querySelectorAll('[data-reading-panel]').forEach(setReadingPanelText);

            document.querySelectorAll('[data-status-reading], [data-status-infographic]').forEach((element) => {
                element.textContent = statusLabel(element.dataset.statusValue || 'not_started');
            });

            document.querySelectorAll('[data-status-videos]').forEach((element) => {
                element.textContent = videoCountText(element.dataset.watchedVideos || 0, element.dataset.totalVideos || 0);
            });

            document.querySelectorAll('[data-youtube-video]').forEach((card) => {
                setStatusPill(card.querySelector('[data-video-status]'), card.dataset.currentStatus || 'not_started');
            });

            document.querySelectorAll('[data-completion-title]').forEach((element) => {
                element.textContent = element.dataset.complete === 'true'
                    ? languagePack().completion_done
                    : languagePack().completion_pending;
            });

            document.querySelectorAll('[data-completion-copy]').forEach(setCompletionCopy);

            document.querySelectorAll('[data-upload-file]').forEach((element) => {
                const hasSelectedFile = element.closest('[data-upload-form]')?.querySelector('[data-upload-input]')?.files?.length;
                if (!hasSelectedFile) {
                    element.textContent = kaalamanCurrentLanguage === 'tl' ? element.dataset.emptyTl : element.dataset.emptyEn;
                }
            });

            document.querySelectorAll('[data-action-key]').forEach((element) => {
                setActionText(element, element.dataset.actionKey);
            });

        };

        window.InayInfographics.init({ saveProgress: saveKaalamanProgress, updateSummary: updateMonthSummary });

        document.querySelectorAll('[data-upload-form]').forEach((form) => {
            const input = form.querySelector('[data-upload-input]');
            const filename = form.querySelector('[data-upload-file]');
            const cancel = form.querySelector('[data-upload-cancel]');
            const error = form.querySelector('[data-upload-error]');

            const refreshUploadState = () => {
                const file = input?.files?.[0] || null;
                if (filename) filename.textContent = file ? file.name : (kaalamanCurrentLanguage === 'tl' ? filename.dataset.emptyTl : filename.dataset.emptyEn);
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

        document.querySelectorAll('[data-language-choice]').forEach((button) => {
            button.addEventListener('click', () => applyKaalamanLanguage(button.dataset.languageChoice || 'en'));
        });

        let storedKaalamanLanguage = 'en';
        try {
            storedKaalamanLanguage = window.localStorage.getItem(kaalamanLanguageStorageKey) || 'en';
        } catch (error) {
            storedKaalamanLanguage = 'en';
        }
        applyKaalamanLanguage(storedKaalamanLanguage, false);


    </script>
@endsection
