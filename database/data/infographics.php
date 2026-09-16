<?php

// Initial library content. Existing learning keys are retained for saved progress.
// Add future resources through EducationalContent; this file is an installation snapshot.
$topics = [
    1 => ['Maternal Care', 'Paglilihi at Bagong Simula Checklist', 'Mga unang hakbang para sa pangangalaga sa iyo at sa iyong sanggol.', [
        ['Magpa-checkup kapag nakumpirma ang pagbubuntis', 'Makipag-ugnayan sa midwife o healthcare provider upang masimulan ang iyong prenatal care.'],
        ['Alamin ang iyong expected date of delivery', 'Itanong sa provider ang tinatayang petsa ng panganganak at itala ito sa prenatal record.'],
        ['Simulan ang regular prenatal checkups', 'Sundin ang iskedyul na ibinigay ng iyong health center at ihanda ang iyong mga tanong.'],
        ['Kumain ng masustansyang pagkain', 'Pumili ng iba-ibang gulay, prutas, pagkaing may protina, at ligtas na inuming tubig.'],
        ['Sundin lamang ang supplements na inireseta ng healthcare provider', 'Itanong muna bago uminom ng gamot, bitamina, o herbal na produkto.'],
        ['Bantayan ang blood pressure at iba pang maternal vital signs', 'Ipasukat sa checkup at ipaliwanag sa provider ang mga resulta at anumang sintomas.'],
        ['Alamin ang pregnancy danger signs', 'Magpatingin agad kung may pagdurugo, matinding sakit ng tiyan o ulo, panlalabo ng paningin, o hirap sa paghinga.'],
        ['Panatilihin ang iyong prenatal records', 'Dalhin ang prenatal record, mga resulta ng pagsusuri, at listahan ng gamot sa bawat pagbisita.'],
    ]],
    2 => ['Prenatal Care', 'Alagaan ang Iyong Puso at Kalusugan', 'Mga paalala sa checkup at pagbabantay sa kalusugan habang buntis.', [
        ['Dumalo sa prenatal checkup', 'Ipaalam ang iyong health history at mga gamot na iniinom.'],
        ['Ipasukat ang blood pressure', 'Itala ang resulta at itanong kung ano ang ibig sabihin nito para sa iyo.'],
        ['Ibahagi ang nararamdaman', 'Sabihin sa provider kung may bagong sintomas o hirap sa pang-araw-araw na gawain.'],
        ['Kilalanin ang emergency', 'Ang pananakit ng dibdib, hirap sa paghinga, o pagkawala ng malay ay nangangailangan ng agarang tulong medikal.'],
    ]],
    3 => ['Maternal Care', 'Kalusugan at Suporta para kay Nanay', 'Maghanda ng mga tanong at bumuo ng suportang kailangan mo.', [
        ['Ihanda ang iyong mga tanong', 'Isulat ang mga alalahanin tungkol sa pagbubuntis bago ang checkup.'],
        ['Ibahagi ang iyong health history', 'Sabihin ang dating sakit, allergy, at gamot o supplements na ginagamit.'],
        ['Humingi ng suporta', 'Makipag-usap sa taong pinagkakatiwalaan at sa provider kung may matinding pag-aalala o lungkot.'],
        ['Panatilihin ang follow-up', 'Itala ang susunod na checkup at dalhin ang iyong mga resulta at prenatal record.'],
    ]],
    4 => ['Nutrition', 'Masustansyang Pagkain sa Pagbubuntis', 'Simpleng paalala sa pagkain at supplements na maaaring talakayin sa checkup.', [
        ['Pumili ng iba-ibang pagkain', 'Isama ang gulay, prutas, at pagkaing may protina sa pang-araw-araw na pagkain.'],
        ['Unahin ang malinis at ligtas na pagkain', 'Maghugas ng kamay at ihanda nang maayos ang pagkain at inuming tubig.'],
        ['Sundin ang payo sa supplements', 'Gamitin ang inireseta at itanong ang tamang pag-inom sa provider.'],
        ['Humingi ng tulong kung hirap kumain', 'Ipaalam kung tuloy-tuloy ang pagsusuka o hindi makainom ng tubig.'],
    ]],
    5 => ['Prenatal Care', 'Checkup at Prenatal Records Checklist', 'Panatilihing maayos ang mga tala ng iyong pangangalaga.', [
        ['Dalhin ang prenatal record', 'Isama ang resulta ng laboratoryo at iba pang pagsusuring ginawa.'],
        ['Ilista ang iyong mga gamot', 'Isama ang supplements at anumang produktong iniinom.'],
        ['Ipasuri ang mga bagong sintomas', 'Ibahagi ang mga pagbabago sa pakiramdam at galaw ng sanggol.'],
        ['Itala ang susunod na pagbisita', 'Linawin ang iskedyul at kung saan pupunta kapag may emergency.'],
    ]],
    6 => ['Maternal Care', 'Kilalanin ang mga Babala sa Pagbubuntis', 'Alamin kung kailan kailangan ng agarang tulong.', [
        ['Huwag balewalain ang pagdurugo', 'Magpatingin agad kung may pagdurugo o matinding pananakit ng tiyan.'],
        ['Ipaalam ang matinding sakit ng ulo', 'Ang matinding sakit ng ulo o panlalabo ng paningin ay kailangang masuri agad.'],
        ['Humingi ng tulong sa hirap sa paghinga', 'Huwag hintayin ang susunod na nakatakdang checkup kung may malubhang sintomas.'],
        ['Ihanda ang emergency contacts', 'Alamin ang pinakamalapit na pasilidad, masasakyan, at taong maaaring sumama.'],
    ]],
    7 => ['Labor and Delivery', 'Paghahanda sa Panganganak', 'Ayusin ang birth plan kasama ang iyong healthcare provider.', [
        ['Pag-usapan ang lugar ng panganganak', 'Alamin ang angkop na pasilidad at kung sino ang tatawagan.'],
        ['Ihanda ang mga dokumento', 'Isama ang prenatal record, mga resulta ng pagsusuri, at mahahalagang contact.'],
        ['Planuhin ang transportasyon', 'Maghanda ng masasakyan at kasama, pati alternatibong plano.'],
        ['Linawin kung kailan pupunta', 'Itanong sa provider ang mga palatandaan ng labor at mga sintomas na nangangailangan ng agarang pagsusuri.'],
    ]],
    8 => ['Labor and Delivery', 'Handa na ba ang Iyong Birth Bag?', 'Mga gamit at impormasyong makatutulong sa pagpunta sa pasilidad.', [
        ['Ihanda ang prenatal records', 'Ilagay sa madaling kuning lalagyan ang mga dokumento at gamot na iniinom.'],
        ['Ilista ang kailangan ng pasilidad', 'Tanungin ang health facility tungkol sa damit, personal na gamit, at gamit ng sanggol.'],
        ['Kumpirmahin ang iyong kasama', 'Ibahagi ang plano sa taong tutulong at sasama sa iyo.'],
        ['Huwag ipagpaliban ang emergency care', 'Kung may babala, unahin ang pagpunta sa pasilidad kaysa pagkumpleto ng mga gamit.'],
    ]],
    9 => ['Labor and Delivery', 'Huling Paghahanda at Ligtas na Pagpunta', 'Balikan ang birth plan at panatilihin ang iyong checkups.', [
        ['Dumalo sa nakatakdang checkup', 'Ipaalam ang mga pagbabago sa sintomas at galaw ng sanggol.'],
        ['Balikan ang birth plan', 'Kumpirmahin ang pasilidad, ruta, transportasyon, at emergency contact.'],
        ['Itanong ang mga palatandaan ng labor', 'Linawin sa provider kung ano ang gagawin kapag may contractions o pagtagas ng tubig.'],
        ['Humingi agad ng tulong kung may babala', 'Huwag hintayin ang takdang petsa kung may pagdurugo, hirap sa paghinga, o iba pang malubhang sintomas.'],
    ]],
    10 => ['Maternal Care', 'Family Planning: May Kaalamang Pagpili', 'Gabay sa pakikipag-usap tungkol sa iyong reproductive goals.', [
        ['Ibahagi ang iyong layunin', 'Pag-usapan kung nais at kailan planong magkaroon ng anak.'],
        ['Alamin ang mga pagpipilian', 'Magtanong tungkol sa bisa, tamang paggamit, at posibleng side effects ng bawat paraan.'],
        ['Ipaalam ang health history', 'Makakatulong ito sa provider sa pagtalakay ng mga angkop na pagpipilian.'],
        ['Magplano ng follow-up', 'Linawin kung saan magtatanong at kailan babalik kung may alalahanin.'],
    ]],
];

$items = [];
foreach ($topics as $month => [$category, $title, $description, $sections]) {
    $items[] = [
        'stage_key' => $month <= 3 ? 'first-trimester' : ($month <= 6 ? 'second-trimester' : ($month <= 9 ? 'third-trimester' : 'labor-delivery')),
        'month' => $month,
        'calendar_month' => $month === 4 ? 7 : ($month <= 3 ? $month : null),
        'infographic_key' => "month-{$month}-infographic",
        'category' => $category,
        'title' => $title,
        'description' => $description,
        'infographic_sections' => $sections,
    ];
}

foreach ([
    ['postpartum-care', 8, 'Postpartum Care', 'Paggaling at Suporta sa Pagpapasuso', 'Alagaan din ang sarili habang inaalagaan ang sanggol.', [
        ['Dumalo sa postpartum checkups', 'Ibahagi ang nararamdaman at mga tanong tungkol sa paggaling.'],
        ['Humingi ng tulong sa pagpapasuso', 'Magpatulong sa midwife o provider kung nahihirapan sa pagpapadede.'],
        ['Alagaan ang iyong emosyonal na kalusugan', 'Humingi ng suporta sa pamilya at provider kapag nahihirapan.'],
        ['Bantayan ang mga babala', 'Magpatingin agad kung may malakas na pagdurugo, lagnat, hirap sa paghinga, o matinding pananakit.'],
    ]],
    ['neonatal-care', 9, 'Neonatal Care', 'Unang mga Araw ni Baby', 'Mga paalala sa newborn care at follow-up.', [
        ['Panatilihing mainit at malinis si baby', 'Maghugas ng kamay bago humawak at sundin ang payo sa pag-aalaga ng pusod.'],
        ['Magpatulong sa pagpapakain', 'Ipaalam agad kung hirap o ayaw dumede ang sanggol.'],
        ['Dumalo sa newborn follow-up', 'Dalhin ang tala ng kapanganakan, screening, at bakuna.'],
        ['Kilalanin ang mga babala', 'Humingi agad ng tulong kung hirap huminga, may lagnat, o hindi normal ang pagkilos ng sanggol.'],
    ]],
    ['neonatal-care', 11, 'Vaccination', 'Bakuna at Child Health Records', 'Makipag-ugnayan sa health center para sa bakuna at growth monitoring.', [
        ['Dalhin ang vaccination card', 'Ipakita ang dating bakuna at mga tala ng kalusugan.'],
        ['Kumpirmahin ang iskedyul', 'Tanungin ang healthcare provider kung anong bakuna ang kailangan at kailan babalik.'],
        ['Ipaalam ang dating reaksiyon', 'Sabihin ang allergy, dating reaksiyon sa bakuna, at kasalukuyang karamdaman.'],
        ['Panatilihin ang child health follow-up', 'Itala ang bakuna at growth measurements at itanong kung anong sintomas ang dapat bantayan.'],
    ]],
] as $index => [$stage, $calendarMonth, $category, $title, $description, $sections]) {
    $items[] = ['stage_key' => $stage, 'month' => null, 'calendar_month' => $calendarMonth,
        'infographic_key' => 'inay-extra-'.($index + 1).'-infographic', 'category' => $category,
        'title' => $title, 'description' => $description, 'infographic_sections' => $sections];
}

return $items;
