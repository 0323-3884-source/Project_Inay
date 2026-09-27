@extends('layouts.app')

@section('title', 'Child Health Monitoring - Project INAY')
@section('portal_title', 'Child Health Monitoring')

@php
    $iconTrend = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 17 6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>';
    $iconAlert = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>';
    $iconBaby = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 12h.01"/><path d="M15 12h.01"/><path d="M10 16c.5.3 1.2.5 2 .5s1.5-.2 2-.5"/><path d="M19 12a7 7 0 1 1-14 0c0-2.2 1-4.1 2.6-5.4"/></svg>';
    $iconPlus = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg>';
    $iconEdit = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/></svg>';
    $iconClose = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>';
    $iconCalendar = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/></svg>';
    $iconRuler = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m16 2 6 6L8 22l-6-6L16 2Z"/><path d="m7.5 10.5 2 2"/><path d="m10.5 7.5 2 2"/><path d="m13.5 4.5 2 2"/></svg>';
    $iconScale = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v18"/><path d="m6 7 6-4 6 4"/><path d="M6 7 3 14h6L6 7Z"/><path d="m18 7-3 7h6l-3-7Z"/></svg>';
    $iconSyringe = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m18 2 4 4"/><path d="m17 7 3-3"/><path d="M19 9 8.7 19.3a2.4 2.4 0 0 1-3.4 0l-.6-.6a2.4 2.4 0 0 1 0-3.4L15 5"/><path d="m9 11 4 4"/></svg>';
    $iconUpload = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M20 16v4H4v-4"/></svg>';
    $initials = fn ($name) => collect(preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [])->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('') ?: 'CH';
    $formatNumber = fn ($value, $suffix = '') => $value === null ? 'N/A' : rtrim(rtrim(number_format((float) $value, 2), '0'), '.').$suffix;
    $bloodTypeOptions = ['Unknown', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    $today = now()->startOfDay();
    $children = $mother->infants->sortBy(fn ($child) => (\App\Support\ChildProfileDisplay::date($child->getRawOriginal('birth_date'))?->format('Ymd') ?? '99999999').$child->full_name)->values();
    $vaccinePurpose = [
        'BCG' => ['Protects against severe forms of tuberculosis.', 'Small injection-site sore, mild swelling, or a small scar.'],
        'Hepatitis B' => ['Helps prevent Hepatitis B infection.', 'Mild fever, soreness, or tiredness.'],
        'Pentavalent / DPT 1' => ['Protects against diphtheria, pertussis, tetanus, Hepatitis B, and Hib.', 'Fever, fussiness, or injection-site soreness.'],
        'Pentavalent / DPT 2' => ['Strengthens immunity from the first dose.', 'Fever, fussiness, or injection-site soreness.'],
        'Pentavalent / DPT 3' => ['Completes the primary pentavalent series.', 'Fever, fussiness, or injection-site soreness.'],
        'OPV 1' => ['Protects against poliovirus.', 'Very rare digestive upset.'],
        'OPV 2' => ['Continues protection against poliovirus.', 'Very rare digestive upset.'],
        'OPV 3' => ['Completes the primary polio series.', 'Very rare digestive upset.'],
        'IPV' => ['Adds injectable protection against poliovirus.', 'Mild soreness or fever.'],
        'PCV 1' => ['Protects against pneumococcal infections.', 'Fever, soreness, or mild appetite changes.'],
        'PCV 2' => ['Strengthens pneumococcal protection.', 'Fever, soreness, or mild appetite changes.'],
        'PCV 3' => ['Completes the primary pneumococcal series.', 'Fever, soreness, or mild appetite changes.'],
        'Measles-Rubella' => ['Protects against measles and rubella infection.', 'Mild fever, rash, or soreness.'],
        'MMR' => ['Protects against measles, mumps, and rubella.', 'Mild fever, rash, or soreness.'],
        'DPT Booster' => ['Extends protection against diphtheria, pertussis, and tetanus.', 'Fever, soreness, or tiredness.'],
        'OPV Booster' => ['Maintains protection against poliovirus.', 'Very rare digestive upset.'],
    ];
    $vaccineState = function ($record) use ($today) {
        if ($record->status === 'completed') return ['Completed', 'is-complete', 5];
        if ($record->status === 'cancelled') return ['Cancelled', 'is-cancelled', 4];
        if (in_array($record->status, ['missed', 'overdue'], true)) return [ucfirst($record->status), 'is-overdue', 1];
        if ($record->due_date && $record->due_date->isSameDay($today)) return ['Due Today', 'is-due', 2];
        if ($record->due_date && $record->due_date->isBefore($today)) return ['Overdue', 'is-overdue', 1];
        return ['Upcoming', 'is-upcoming', 3];
    };

    $selectedGrowth = $selectedChild?->growthRecords ?? collect();
    $latestGrowth = $selectedGrowth->last();
    $activeStaffAlerts = ($selectedChild?->healthAlerts ?? collect())->where('status', 'active');
    $ageMonths = \App\Support\ChildProfileDisplay::months($selectedChild?->getRawOriginal('birth_date'));
    $growthReviewAge = $latestGrowth?->age_months ?? $ageMonths;
    $weightChart = \App\Support\ChildProfileDisplay::chart($selectedGrowth, 'weight', $selectedChild?->getRawOriginal('birth_date'));
    $heightChart = \App\Support\ChildProfileDisplay::chart($selectedGrowth, 'height', $selectedChild?->getRawOriginal('birth_date'));
    $vaccines = $selectedChild?->vaccineRecords ?? collect();
    $vaccineRows = $vaccines->map(function ($record) use ($vaccineState) { [$label, $class, $sort] = $vaccineState($record); $record->display_status = $label; $record->display_class = $class; $record->sort_rank = $sort; return $record; })->sortBy(fn ($record) => $record->sort_rank.'-'.($record->due_date?->format('Ymd') ?? '99999999'))->values();
    $completedVaccines = $vaccines->where('status', 'completed')->count();
    $overdueVaccines = $vaccineRows->where('display_class', 'is-overdue')->count();
    $upcomingVaccines = $vaccineRows->where('display_class', 'is-upcoming')->count();
    $alerts = collect();
    if ($latestGrowth) {
        $minWeight = max(2.4, 2.6 + ($growthReviewAge * 0.45));
        $maxWeight = max(4.6, 5.0 + ($growthReviewAge * 0.75));
        $minHeight = max(45, 47 + ($growthReviewAge * 1.4));
        if ((float) $latestGrowth->weight < $minWeight || (float) $latestGrowth->weight > $maxWeight) $alerts->push(['title' => 'Weight needs review', 'text' => $selectedChild->full_name.'\'s weight needs review for age.']);
        if ((float) $latestGrowth->height < $minHeight) $alerts->push(['title' => 'Height needs review', 'text' => $selectedChild->full_name.'\'s height needs review for age.']);
        if ($alerts->isNotEmpty()) $alerts->push(['title' => 'Nutritional intervention recommended', 'text' => $selectedChild->full_name.'\'s latest growth status needs a priority clinical nutrition review.']);
    }
    if ($overdueVaccines > 0) $alerts->push(['title' => 'Missed immunization detected', 'text' => $overdueVaccines.' vaccine'.($overdueVaccines === 1 ? '' : 's').' need barangay health center follow-up guidance.']);
    foreach ($activeStaffAlerts as $staffAlert) {
        $alerts->push(['title' => $staffAlert->title, 'text' => $staffAlert->notes ?: 'Program Staff marked this child for follow-up.']);
    }
    $alerts = $alerts->unique('title')->values();
    $selectedPhotoUrl = $selectedChild?->photo_path ? asset('storage/'.$selectedChild->photo_path) : null;
@endphp

@section('content')
    <style>
        .child-health{--pink:#ec008c;--navy:#071127;--muted:#52627d;--line:#dbe5f1;--soft:#f8fafc;--green:#00856a;--blue:#2563eb;--amber:#c2410c;color:var(--navy)}.child-health *{box-sizing:border-box;letter-spacing:0}.child-health svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}.ch-top{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:20px;align-items:end;border-bottom:1px solid #d4deec;padding-bottom:22px;margin-bottom:24px}.ch-kicker{margin:0 0 10px;color:var(--pink);font-size:12px;font-weight:700;text-transform:uppercase}.ch-top h1{margin:0;font-size:28px;line-height:1.12;font-weight:800}.ch-top p{margin:8px 0 0;color:var(--muted);font-size:15px}.ch-actions{display:flex;align-items:end;gap:10px;flex-wrap:wrap}.ch-actions label,.ch-form label{display:grid;gap:7px;color:#64748b;font-size:12px;text-transform:uppercase;font-weight:700}.ch-select,.ch-input,.ch-form input,.ch-form select,.ch-form textarea{width:100%;border:1px solid #cbd8ea;border-radius:7px;background:#fff;color:#0f1b33;font-size:14px;outline:none}.ch-select,.ch-input,.ch-form input,.ch-form select{height:44px;padding:0 12px}.ch-form textarea{min-height:96px;padding:12px;resize:vertical}.ch-select{min-width:280px}.ch-button{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:44px;border-radius:8px;border:1px solid var(--pink);background:var(--pink);color:#fff;padding:0 16px;font-weight:800;cursor:pointer;text-decoration:none}.ch-button.is-light{background:#fff;color:#1f2a44;border-color:#cbd8ea}.ch-button.is-quiet{background:#f8fafc;color:#334155;border-color:#dbe5f1}.ch-card{background:#fff;border:1px solid var(--line);border-radius:8px;box-shadow:0 2px 8px rgba(15,23,42,.08)}.ch-profile{padding:22px;margin-bottom:18px}.ch-profile-head{display:grid;grid-template-columns:minmax(260px,1.2fr) repeat(4,minmax(130px,1fr));gap:14px;align-items:center}.ch-child-title{display:flex;align-items:center;gap:16px;min-width:0}.ch-avatar-form{margin:0}.ch-avatar-button{position:relative;display:grid;cursor:pointer}.ch-avatar{display:grid;place-items:center;width:70px;height:70px;border-radius:999px;background:#7c2dff;color:#fff;font-size:24px;font-weight:900;overflow:hidden}.ch-avatar img{width:100%;height:100%;object-fit:cover}.ch-avatar-action{position:absolute;left:-4px;top:-4px;display:grid;width:26px;height:26px;place-items:center;border-radius:999px;background:var(--pink);border:2px solid #fff;color:#fff;box-shadow:0 8px 18px rgba(236,0,140,.2)}.ch-avatar-button input{position:absolute;width:1px;height:1px;opacity:0;pointer-events:none}.ch-child-title h2{margin:0;font-size:24px;font-weight:800;line-height:1.15}.ch-child-title p{margin:6px 0 0;color:var(--muted);font-size:14px}.ch-pills,.ch-profile-actions,.ch-vaccine-summary{display:flex;gap:8px;flex-wrap:wrap}.ch-pills{margin-top:11px}.ch-pill,.ch-status,.ch-count{display:inline-flex;align-items:center;justify-content:center;border-radius:999px;padding:6px 10px;font-size:11px;font-weight:800;text-transform:uppercase}.ch-pill.is-danger,.ch-status.is-overdue{background:#fff1f2;color:#be123c;border:1px solid #fecdd3}.ch-pill.is-blue,.ch-status.is-upcoming{background:#eff6ff;color:#075ef2;border:1px solid #bfdbfe}.ch-status.is-complete{background:#dcfce7;color:#007f5f;border:1px solid #86efc2}.ch-status.is-due{background:#fff7ed;color:#c2410c;border:1px solid #fed7aa}.ch-status.is-cancelled{background:#f1f5f9;color:#64748b;border:1px solid #cbd5e1}.ch-metric,.ch-profile-extra article{padding:14px;border:1px solid #d8e2ee;border-radius:7px;background:#f8fbff}.ch-metric span,.ch-profile-extra span{display:flex;align-items:center;gap:7px;color:#8aa0bd;font-size:11px;text-transform:uppercase;font-weight:800}.ch-metric strong,.ch-profile-extra strong{display:block;margin-top:9px;font-size:17px;font-weight:800}.ch-profile-extra{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-top:16px}.ch-profile-extra strong{font-size:14px}.ch-last{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-top:18px;padding-top:15px;border-top:1px solid #edf2f7;color:var(--muted);font-size:13px}.ch-alert-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-bottom:18px}.ch-alert,.ch-error-box{display:flex;gap:12px;align-items:flex-start;padding:14px 16px;border-radius:8px;border:1px solid #fecdd3;background:#fff1f2;color:#be123c}.ch-alert strong{display:block;font-weight:800}.ch-alert p{margin:5px 0 0;font-size:14px;line-height:1.45}.ch-normal{padding:14px 16px;margin-bottom:18px;border-radius:8px;border:1px solid #86efc2;background:#ecfdf5;color:#007f5f;font-weight:800}.ch-charts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin-bottom:18px}.ch-chart,.ch-section{padding:18px;margin-bottom:18px}.ch-chart-head,.ch-section-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:14px}.ch-chart h3,.ch-section h2{display:flex;align-items:center;gap:8px;margin:0;font-size:18px;font-weight:800}.ch-chart p,.ch-muted{margin:7px 0 0;color:var(--muted);font-size:13px}.ch-chart small{color:#8aa0bd;text-transform:uppercase;font-size:12px;font-weight:800}.ch-plot{height:280px;border:1px solid #e6edf6;border-radius:8px;background:#f8fafc;padding:18px 20px 22px}.ch-plot svg{display:block;width:100%;height:100%;stroke-width:1}.ch-plot .grid{stroke:#dbeafe;stroke-dasharray:4 7}.ch-plot .axis{stroke:#b8c7dc}.ch-plot .label{fill:#8090ad;font-size:3.15px;font-weight:400!important;stroke:none!important}.ch-plot .axis-title{fill:#94a3b8;font-size:2.85px;font-weight:400!important;stroke:none!important}.ch-plot .line{fill:none;stroke:var(--pink);stroke-width:1.25}.ch-plot .line.is-purple{stroke:#7c3aed}.ch-plot .dot{fill:#fff;stroke:#2563eb;stroke-width:1.8}.ch-plot .dot.is-purple{stroke:#7c3aed}.ch-empty{display:grid;place-items:center;min-height:170px;color:#64748b;background:#f8fafc;border:1px dashed #d5deea;border-radius:8px;text-align:center;padding:20px}.ch-count{background:#f8fafc;color:#8090ad}.ch-table-wrap{overflow:auto}.ch-table{width:100%;border-collapse:collapse}.ch-table th{background:#f8fafc;color:#8aa0bd;text-align:left;font-size:12px;text-transform:uppercase;font-weight:800;padding:12px}.ch-table td{padding:13px 12px;border-top:1px solid #eef2f7;color:#24324b}.ch-vaccine-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.ch-vaccine{padding:16px;border:1px solid #d7e1ee;border-radius:8px;background:#f8fbff}.ch-vaccine.is-overdue{background:#fff7f9;border-color:#fecdd3}.ch-vaccine-top{display:flex;justify-content:space-between;gap:12px;align-items:start}.ch-vaccine h3{margin:0;font-size:16px;font-weight:800}.ch-vaccine small{display:block;margin-top:4px;color:#52627d}.ch-vaccine dl{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:14px 0 0}.ch-vaccine dt{color:#64748b;font-size:12px;font-weight:800}.ch-vaccine dd{margin:4px 0 0;color:#17233b;font-size:14px}.ch-modal[hidden]{display:none}.ch-modal{position:fixed;inset:0;z-index:80;display:flex;align-items:center;justify-content:center;padding:16px}.ch-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.58);backdrop-filter:blur(3px)}.ch-dialog{position:relative;width:min(680px,calc(100vw - 24px));max-height:86vh;overflow:auto;background:#fff;border-radius:8px;box-shadow:0 28px 70px rgba(15,23,42,.26)}.ch-dialog header,.ch-dialog footer{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:20px 22px;border-bottom:1px solid #e2e8f0}.ch-dialog footer{border-top:1px solid #e2e8f0;border-bottom:0;justify-content:end}.ch-dialog h2{margin:0;font-size:20px;font-weight:800}.ch-close{width:38px;height:38px;border:0;background:#fff;color:#64748b;border-radius:999px;display:grid;place-items:center;cursor:pointer}.ch-form{padding:20px 22px;display:grid;grid-template-columns:1fr 1fr;gap:14px}.ch-form label.is-wide{grid-column:1/-1}.ch-form input[type=file]{height:auto;padding:10px 12px}.ch-form-note{grid-column:1/-1;margin:0;color:#64748b;font-size:13px}body.has-ch-modal{overflow:hidden}@media(max-width:1200px){.ch-profile-head{grid-template-columns:1fr 1fr}.ch-child-title{grid-column:1/-1}.ch-profile-extra{grid-template-columns:repeat(2,minmax(0,1fr))}.ch-vaccine-grid{grid-template-columns:1fr}}@media(max-width:760px){.ch-top,.ch-charts,.ch-alert-grid{grid-template-columns:1fr}.ch-actions{align-items:stretch}.ch-select,.ch-button{width:100%;min-width:0}.ch-profile-head,.ch-profile-extra,.ch-form{grid-template-columns:1fr}.ch-vaccine dl{grid-template-columns:1fr}.ch-plot{height:250px}.ch-last{align-items:flex-start;flex-direction:column}}
    </style>
    <link rel="stylesheet" href="{{ asset('css/child-profile.css') }}?v={{ filemtime(public_path('css/child-profile.css')) }}">
    <script src="{{ asset('js/child-profile.js') }}?v={{ filemtime(public_path('js/child-profile.js')) }}" defer></script>

    <section class="child-health" aria-label="Mother Child Health Monitoring">
        <header class="ch-top">
            <div>
                <p class="ch-kicker">Mother Portal / Pediatric Care</p>
                <h1>Child Health Monitoring</h1>
                <p>Growth, vaccine, and follow-up status for your registered children.</p>
            </div>
            <div class="ch-actions">
                <label>Active Patient Profile
                    <select class="ch-select" onchange="if(this.value){window.location=this.value}">
                        @forelse($children as $child)
                            @php $childAge = \App\Support\ChildProfileDisplay::age($child->getRawOriginal('birth_date')); @endphp
                            <option value="{{ route('child-health', ['child' => $child->id]) }}" @selected($selectedChild?->id === $child->id)>{{ $child->full_name }} ({{ $childAge }})</option>
                        @empty
                            <option>No child profile yet</option>
                        @endforelse
                    </select>
                </label>
                <button class="ch-button" type="button" data-ch-open="add">{!! $iconPlus !!} Add Child</button>
            </div>
        </header>

        @if ($errors->any())
            <div class="ch-error-box">{!! $iconAlert !!}<span>{{ $errors->first() }}</span></div>
        @endif

        @if (session('status'))
            <div class="ch-normal">{{ session('status') }}</div>
        @endif

        @if($childAccessDenied)
            <section class="ch-card ch-section"><div class="ch-empty">{{ $childAccessMessage }}</div></section>
        @endif

        @if($selectedChild)
            <section class="ch-card ch-profile">
                <div class="ch-profile-head">
                    <div class="ch-child-title">
                        <form class="ch-avatar-form" method="POST" action="{{ route('child-health.children.photo.update', $selectedChild) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PATCH')
                            <label class="ch-avatar-button" aria-label="Upload child profile photo">
                                <span class="ch-avatar">@if($selectedPhotoUrl)<img src="{{ $selectedPhotoUrl }}" alt="{{ $selectedChild->full_name }}">@else{{ $initials($selectedChild->full_name) }}@endif</span>
                                <span class="ch-avatar-action">{!! $iconUpload !!}</span>
                                <input type="file" name="child_photo" accept="image/png,image/jpeg,image/webp" data-photo-crop data-photo-auto-submit="true" data-photo-title="Upload Baby Profile Photo">
                            </label>
                        </form>
                        <div>
                            <h2>{{ $selectedChild->full_name }}</h2>
                            <p>Child Growth and Immunization Record</p>
                            <div class="ch-pills"><span class="ch-pill is-danger">{{ $alerts->count() }} active alert{{ $alerts->count() === 1 ? '' : 's' }}</span><span class="ch-pill is-blue">{{ $completedVaccines }} vaccines completed</span></div>
                        </div>
                    </div>
                    <div class="ch-core-metrics">
                        <article class="ch-metric"><span>{!! $iconBaby !!} Age</span><strong>{{ \App\Support\ChildProfileDisplay::age($selectedChild->getRawOriginal('birth_date')) }}</strong></article>
                        <article class="ch-metric"><span>{!! $iconCalendar !!} Birth Date</span><strong>{{ \App\Support\ChildProfileDisplay::date($selectedChild->getRawOriginal('birth_date'))?->format('M j, Y') ?? 'N/A' }}</strong></article>
                        <article class="ch-metric"><span>{!! $iconScale !!} Weight</span><strong>{{ $formatNumber($latestGrowth?->weight, ' kg') }}</strong></article>
                        <article class="ch-metric"><span>{!! $iconRuler !!} Height</span><strong>{{ $formatNumber($latestGrowth?->height, ' cm') }}</strong></article>
                    </div>
                </div>
                <div class="ch-profile-extra">
                    <article><span>Sex</span><strong>{{ ucfirst($selectedChild->sex) }}</strong></article>
                    <article><span>Birth Weight</span><strong>{{ $formatNumber($selectedChild->birth_weight, ' kg') }}</strong></article>
                    <article><span>Birth Length</span><strong>{{ $formatNumber($selectedChild->birth_height, ' cm') }}</strong></article>
                    <article><span>Blood Type</span><strong>{{ $selectedChild->blood_type ?: 'Unknown' }}</strong></article>
                    <article><span>Facility</span><strong>{{ $selectedChild->facility ?: 'N/A' }}</strong></article>
                    <article><span>Head / Temp</span><strong>{{ $formatNumber($latestGrowth?->head_circumference, ' cm') }} / {{ $formatNumber($latestGrowth?->temperature, ' C') }}</strong></article>
                </div>
                <div class="ch-last">
                    <span>Last measurement: <strong>{{ $latestGrowth?->measured_at?->format('M j, Y') ?? 'No growth measurement from Program Staff yet' }}</strong></span>
                    <div class="ch-profile-actions"><button class="ch-button is-light" type="button" data-ch-open="edit">{!! $iconEdit !!} Edit Profile</button></div>
                </div>
            </section>

            @if($alerts->isEmpty())
                <div class="ch-normal">No active health alerts.</div>
            @else
                <div class="ch-alert-grid">
                    @foreach($alerts as $alert)
                        <article class="ch-alert">{!! $iconAlert !!}<div><strong>{{ $alert['title'] }}</strong><p>{{ $alert['text'] }}</p></div></article>
                    @endforeach
                </div>
            @endif

            <div class="ch-charts">
                @foreach([['Weight Progress', 'kg', $weightChart, $latestGrowth?->weight, ''], ['Height Progress', 'cm', $heightChart, $latestGrowth?->height, 'is-purple']] as [$title, $unit, $chart, $latest, $purple])
                    <article class="ch-card ch-chart">
                        <div class="ch-chart-head"><div><h3>{!! $iconTrend !!} {{ $title }}</h3><p>Latest: {{ $formatNumber($chart['latest'], ' '.$unit) }}</p></div><small>Unit: {{ strtoupper($unit) }}</small></div>
                        <div class="ch-plot">
                            @if($chart['has'])
                                @include('partials.child-growth-chart')
                            @else
                                <div class="ch-empty">No {{ strtolower(str_replace(' Progress', '', $title)) }} record yet.</div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <section class="ch-card ch-section">
                <div class="ch-section-head"><h2>Growth History</h2><span class="ch-count">{{ $selectedGrowth->count() }} record{{ $selectedGrowth->count() === 1 ? '' : 's' }}</span></div>
                @if($selectedGrowth->isEmpty())
                    <div class="ch-empty">No growth history available yet.</div>
                @else
                    <div class="ch-table-wrap"><table class="ch-table"><thead><tr><th>Age</th><th>Weight</th><th>Height</th><th>Measurement Date</th><th>Recorded By</th></tr></thead><tbody>@foreach($selectedGrowth->sortByDesc('measured_at') as $record)<tr><td>{{ $record->age_months }} mo</td><td>{{ $formatNumber($record->weight, ' kg') }}</td><td>{{ $formatNumber($record->height, ' cm') }}</td><td>{{ $record->measured_at?->format('M j, Y') }}</td><td>{{ $record->recorder?->full_name ?? 'Program Staff' }}</td></tr>@endforeach</tbody></table></div>
                @endif
            </section>

            <section class="ch-card ch-section">
                <div class="ch-section-head"><div><h2>{!! $iconSyringe !!} Immunization Schedule</h2><p class="ch-muted">{{ $selectedChild->full_name }} vaccine record</p></div><div class="ch-vaccine-summary"><span class="ch-status is-complete">{{ $completedVaccines }} completed</span><span class="ch-status is-upcoming">{{ $upcomingVaccines }} upcoming</span><span class="ch-status is-overdue">{{ $overdueVaccines }} overdue</span></div></div>
                @if($vaccineRows->isEmpty())
                    <div class="ch-empty">No immunization records available yet.</div>
                @else
                    <div class="ch-vaccine-grid">
                        @foreach($vaccineRows as $vaccine)
                            @php $info = $vaccinePurpose[$vaccine->vaccine_name] ?? ['Follow the barangay immunization guidance for this vaccine.', 'Possible mild fever, soreness, or tiredness.']; @endphp
                            <article class="ch-vaccine {{ $vaccine->display_class }}">
                                <div class="ch-vaccine-top"><div><h3>{{ $vaccine->vaccine_name }}</h3><small>{{ $vaccine->dose_label }} / Due {{ $vaccine->due_date?->format('M j, Y') ?? 'Not scheduled' }}</small></div><span class="ch-status {{ $vaccine->display_class }}">{{ $vaccine->display_status }}</span></div>
                                <dl><div><dt>Vaccination Date</dt><dd>{{ $vaccine->administered_at?->format('M j, Y') ?? 'Not recorded' }}</dd></div><div><dt>Recorded By</dt><dd>{{ $vaccine->recorder?->full_name ?? 'Program Staff' }}</dd></div><div><dt>Purpose</dt><dd>{{ $info[0] }}</dd></div><div><dt>Possible Side Effects</dt><dd>{{ $info[1] }}</dd></div></dl>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @elseif(! $childAccessDenied)
            <section class="ch-card ch-section"><div class="ch-empty">No child profile yet. Add your child profile to start pediatric monitoring.</div></section>
        @endif

        <div class="ch-modal" data-ch-modal="add" hidden>
            <div class="ch-backdrop" data-ch-close></div>
            <form class="ch-dialog" method="POST" action="{{ route('child-health.children.store') }}" enctype="multipart/form-data">
                @csrf
                <header><h2>Add Child</h2><button type="button" class="ch-close" data-ch-close aria-label="Close add child modal">{!! $iconClose !!}</button></header>
                <div class="ch-form">
                    <label class="is-wide">Child Name<input name="full_name" required maxlength="255" autocomplete="off" value="{{ old('full_name') }}"></label>
                    <label>Sex<select name="sex" required><option value="unspecified" @selected(old('sex') === 'unspecified')>Unspecified</option><option value="female" @selected(old('sex') === 'female')>Female</option><option value="male" @selected(old('sex') === 'male')>Male</option><option value="other" @selected(old('sex') === 'other')>Other</option></select></label>
                    <label>Birth Date<input type="date" name="birth_date" required max="{{ now()->toDateString() }}" value="{{ old('birth_date') }}"></label>
                    <label>Blood Type<select name="blood_type">@foreach($bloodTypeOptions as $type)<option value="{{ $type }}" @selected(old('blood_type', 'Unknown') === $type)>{{ $type }}</option>@endforeach</select></label>
                    <label>Profile Photo<input type="file" name="child_photo" accept="image/png,image/jpeg,image/webp" data-photo-crop data-photo-title="Upload Baby Profile Photo"></label>
                    <p class="ch-form-note">JPG, PNG, or WEBP up to 4 MB.</p>
                </div>
                <footer><button class="ch-button is-light" type="button" data-ch-close>Cancel</button><button class="ch-button" type="submit">Save Child</button></footer>
            </form>
        </div>

        @if($selectedChild)
            <div class="ch-modal" data-ch-modal="edit" hidden>
                <div class="ch-backdrop" data-ch-close></div>
                <form class="ch-dialog" method="POST" action="{{ route('child-health.children.update', $selectedChild) }}">
                    @csrf
                    @method('PATCH')
                    <header><h2>Edit Child Profile</h2><button type="button" class="ch-close" data-ch-close aria-label="Close edit child modal">{!! $iconClose !!}</button></header>
                    <div class="ch-form">
                        <label class="is-wide">Child Name<input name="full_name" required maxlength="255" autocomplete="off" value="{{ old('full_name', $selectedChild->full_name) }}"></label>
                        <label>Sex<select name="sex" required><option value="female" @selected(old('sex', $selectedChild->sex) === 'female')>Female</option><option value="male" @selected(old('sex', $selectedChild->sex) === 'male')>Male</option><option value="other" @selected(old('sex', $selectedChild->sex) === 'other')>Other</option></select></label>
                        <label>Birth Date<input type="date" name="birth_date" required max="{{ now()->toDateString() }}" value="{{ old('birth_date', \App\Support\ChildProfileDisplay::date($selectedChild->getRawOriginal('birth_date'))?->toDateString()) }}"></label>
                        <label>Blood Type<select name="blood_type">@foreach($bloodTypeOptions as $type)<option value="{{ $type }}" @selected(old('blood_type', $selectedChild->blood_type ?: 'Unknown') === $type)>{{ $type }}</option>@endforeach</select></label>
                    </div>
                    <footer><button class="ch-button is-light" type="button" data-ch-close>Cancel</button><button class="ch-button" type="submit">Save Changes</button></footer>
                </form>
            </div>
        @endif
    </section>

    <script>
        (() => {
            const body = document.body;
            const modals = Array.from(document.querySelectorAll('[data-ch-modal]'));
            const close = () => { modals.forEach((modal) => modal.hidden = true); body.classList.remove('has-ch-modal'); };
            const open = (name) => {
                const modal = document.querySelector(`[data-ch-modal="${name}"]`);
                if (!modal) return;
                close();
                modal.hidden = false;
                body.classList.add('has-ch-modal');
                modal.querySelector('input, select, textarea, button')?.focus();
            };
            document.querySelectorAll('[data-ch-open]').forEach((button) => button.addEventListener('click', () => open(button.dataset.chOpen)));
            document.querySelectorAll('[data-ch-close]').forEach((el) => el.addEventListener('click', close));
            document.addEventListener('keydown', (event) => { if (event.key === 'Escape') close(); });
        })();
    </script>
@endsection
