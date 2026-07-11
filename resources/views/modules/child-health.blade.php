@extends('layouts.app')

@section('title', 'Child Health Monitoring - Project INAY')
@section('portal_title', 'Child Health Monitoring')

@php
    $iconTrend = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 17 6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>';
    $iconAlert = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>';
    $iconBaby = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 12h.01"/><path d="M15 12h.01"/><path d="M10 16c.5.3 1.2.5 2 .5s1.5-.2 2-.5"/><path d="M19 12a7 7 0 1 1-14 0c0-2.2 1-4.1 2.6-5.4"/></svg>';
    $iconPlus = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg>';
    $iconClose = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>';
    $iconCalendar = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/></svg>';
    $iconRuler = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m16 2 6 6L8 22l-6-6L16 2Z"/><path d="m7.5 10.5 2 2"/><path d="m10.5 7.5 2 2"/><path d="m13.5 4.5 2 2"/></svg>';
    $iconScale = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v18"/><path d="m6 7 6-4 6 4"/><path d="M6 7 3 14h6L6 7Z"/><path d="m18 7-3 7h6l-3-7Z"/></svg>';
    $iconSyringe = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m18 2 4 4"/><path d="m17 7 3-3"/><path d="M19 9 8.7 19.3a2.4 2.4 0 0 1-3.4 0l-.6-.6a2.4 2.4 0 0 1 0-3.4L15 5"/><path d="m9 11 4 4"/></svg>';
    $initials = fn ($name) => collect(preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [])->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('') ?: 'CH';
    $formatNumber = fn ($value, $suffix = '') => $value === null ? 'N/A' : rtrim(rtrim(number_format((float) $value, 2), '0'), '.').$suffix;
    $today = now()->startOfDay();
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
        if (in_array($record->status, ['missed', 'deferred'], true)) return [ucfirst($record->status), 'is-overdue', 1];
        if ($record->due_date && $record->due_date->isSameDay($today)) return ['Due Today', 'is-due', 2];
        if ($record->due_date && $record->due_date->isBefore($today)) return ['Overdue', 'is-overdue', 1];
        return ['Upcoming', 'is-upcoming', 3];
    };
    $chartData = function ($records, $field) {
        $values = $records->filter(fn ($record) => $record->{$field} !== null)->sortBy('age_months')->values();
        if ($values->isEmpty()) return ['has' => false, 'points' => collect(), 'path' => '', 'min' => null, 'mid' => null, 'max' => null, 'min_age' => 0, 'mid_age' => 1, 'max_age' => 3];
        $numbers = $values->map(fn ($record) => (float) $record->{$field});
        $ages = $values->map(fn ($record) => (int) ($record->age_months ?? 0));
        $padding = max(0.5, ($numbers->max() - $numbers->min()) * 0.2);
        $minValue = floor(($numbers->min() - $padding) * 10) / 10;
        $maxValue = ceil(($numbers->max() + $padding) * 10) / 10;
        if ($minValue === $maxValue) { $minValue -= 1; $maxValue += 1; }
        $minAge = max(0, $ages->min());
        $maxAge = max(3, $ages->max(), $minAge + 1);
        $points = $values->map(function ($record) use ($field, $minValue, $maxValue, $minAge, $maxAge) {
            $age = (int) ($record->age_months ?? 0);
            $value = (float) $record->{$field};
            return [
                'x' => round(18 + ((($age - $minAge) / max(1, $maxAge - $minAge)) * 72), 2),
                'y' => round(80 - ((($value - $minValue) / max(1, $maxValue - $minValue)) * 56), 2),
                'age' => $age,
                'value' => $value,
                'date' => $record->measured_at?->format('M j, Y') ?? 'No date',
            ];
        });
        return ['has' => true, 'points' => $points, 'path' => $points->map(fn ($p) => $p['x'].','.$p['y'])->implode(' '), 'min' => $minValue, 'mid' => round(($minValue + $maxValue) / 2, 1), 'max' => $maxValue, 'min_age' => $minAge, 'mid_age' => (int) round(($minAge + $maxAge) / 2), 'max_age' => $maxAge];
    };
    $selectedGrowth = $selectedChild?->growthRecords ?? collect();
    $latestGrowth = $selectedGrowth->last();
    $ageMonths = $selectedChild ? ($latestGrowth?->age_months ?? ($selectedChild->birth_date ? max(0, (int) $selectedChild->birth_date->diffInMonths(now())) : 0)) : 0;
    $weightChart = $chartData($selectedGrowth, 'weight');
    $heightChart = $chartData($selectedGrowth, 'height');
    $vaccines = $selectedChild?->vaccineRecords ?? collect();
    $vaccineRows = $vaccines->map(function ($record) use ($vaccineState) { [$label, $class, $sort] = $vaccineState($record); $record->display_status = $label; $record->display_class = $class; $record->sort_rank = $sort; return $record; })->sortBy(fn ($record) => $record->sort_rank.'-'.($record->due_date?->format('Ymd') ?? '99999999'))->values();
    $completedVaccines = $vaccines->where('status', 'completed')->count();
    $overdueVaccines = $vaccineRows->where('display_class', 'is-overdue')->count();
    $upcomingVaccines = $vaccineRows->where('display_class', 'is-upcoming')->count();
    $alerts = collect();
    if ($latestGrowth) {
        $minWeight = max(2.4, 2.6 + ($ageMonths * 0.45));
        $maxWeight = max(4.6, 5.0 + ($ageMonths * 0.75));
        $minHeight = max(45, 47 + ($ageMonths * 1.4));
        if ((float) $latestGrowth->weight < $minWeight || (float) $latestGrowth->weight > $maxWeight) $alerts->push(['title' => 'Weight needs review', 'text' => $selectedChild->full_name.'\'s weight needs review for age.', 'tone' => 'danger']);
        if ((float) $latestGrowth->height < $minHeight) $alerts->push(['title' => 'Height needs review', 'text' => $selectedChild->full_name.'\'s height needs review for age.', 'tone' => 'danger']);
        if ($alerts->isNotEmpty()) $alerts->push(['title' => 'Nutritional intervention recommended', 'text' => $selectedChild->full_name.'\'s latest growth status needs a priority clinical nutrition review.', 'tone' => 'danger']);
    }
    if ($overdueVaccines > 0) $alerts->push(['title' => 'Missed immunization detected', 'text' => $overdueVaccines.' vaccine'.($overdueVaccines === 1 ? '' : 's').' need barangay health center follow-up guidance.', 'tone' => 'danger']);
    $alerts = $alerts->unique('title')->values();
@endphp

@section('content')
    <style>
        .child-health{--pink:#ec008c;--navy:#071127;--muted:#52627d;--line:#dbe5f1;--soft:#f8fafc;--green:#00856a;--blue:#2563eb;color:var(--navy);font-weight:400}.child-health *{box-sizing:border-box;letter-spacing:0}.child-health svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}.ch-top{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:20px;align-items:end;border-bottom:1px solid #d4deec;padding-bottom:22px;margin-bottom:24px}.ch-kicker{margin:0 0 10px;color:var(--pink);font-size:12px;font-weight:600;text-transform:uppercase}.ch-top h1{margin:0;font-size:28px;line-height:1.12;font-weight:700}.ch-top p{margin:8px 0 0;color:var(--muted);font-size:15px;font-weight:400}.ch-actions{display:flex;align-items:end;gap:10px;flex-wrap:wrap}.ch-actions label{display:grid;gap:6px;color:#8aa0bd;font-size:11px;text-transform:uppercase;font-weight:600}.ch-select,.ch-input{height:46px;border:1px solid #cbd8ea;border-radius:8px;background:#fff;color:#0f1b33;padding:0 14px;font-size:14px;font-weight:400;outline:none}.ch-select{min-width:280px}.ch-button{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:46px;border-radius:8px;border:1px solid var(--pink);background:var(--pink);color:#fff;padding:0 18px;font-weight:600;cursor:pointer;text-decoration:none}.ch-button.is-light{background:#fff;color:#1f2a44;border-color:#cbd8ea}.ch-card{background:#fff;border:1px solid var(--line);border-radius:8px;box-shadow:0 2px 8px rgba(15,23,42,.08)}.ch-profile{padding:22px;margin-bottom:20px}.ch-profile-main{display:grid;grid-template-columns:minmax(0,1fr) repeat(4,minmax(130px,1fr));gap:14px;align-items:center}.ch-child-title{display:flex;align-items:center;gap:14px;min-width:0}.ch-avatar{position:relative;display:grid;place-items:center;width:58px;height:58px;border-radius:999px;background:#7c2dff;color:#fff;font-size:22px;font-weight:600}.ch-avatar:after{content:"+";position:absolute;right:-2px;bottom:-2px;width:24px;height:24px;border-radius:999px;background:var(--pink);border:2px solid #fff;color:#fff;display:grid;place-items:center;font-size:16px}.ch-child-title h2{margin:0;font-size:22px;font-weight:600;line-height:1.2}.ch-child-title p{margin:6px 0 0;color:var(--muted);font-size:14px}.ch-pills{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}.ch-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;background:#f1f5f9;color:#334155;padding:5px 9px;font-size:12px;font-weight:500}.ch-pill.is-danger{background:#fff1f2;color:#be123c;border:1px solid #fecdd3}.ch-pill.is-blue{background:#eff6ff;color:#075ef2;border:1px solid #bfdbfe}.ch-metric{padding:14px;border:1px solid #d8e2ee;border-radius:7px;background:#f8fbff}.ch-metric span{display:flex;align-items:center;gap:7px;color:#8aa0bd;font-size:11px;text-transform:uppercase;font-weight:600}.ch-metric strong{display:block;margin-top:10px;font-size:17px;font-weight:600}.ch-last{margin-top:18px;padding-top:14px;border-top:1px solid #edf2f7;color:var(--muted);font-size:13px}.ch-alert-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-bottom:20px}.ch-alert{display:flex;gap:12px;align-items:flex-start;padding:14px 16px;border-radius:8px;border:1px solid #fecdd3;background:#fff1f2;color:#be123c}.ch-alert strong{display:block;font-weight:600}.ch-alert p{margin:5px 0 0;font-size:14px;line-height:1.45;font-weight:400}.ch-normal{padding:14px 16px;margin-bottom:20px;border-radius:8px;border:1px solid #86efc2;background:#ecfdf5;color:#007f5f;font-weight:500}.ch-charts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin-bottom:20px}.ch-chart{padding:18px}.ch-chart-head{display:flex;justify-content:space-between;gap:12px;margin-bottom:12px}.ch-chart h3{display:flex;align-items:center;gap:8px;margin:0;font-size:17px;font-weight:600}.ch-chart p{margin:8px 0 0;color:var(--muted);font-size:14px}.ch-chart small{color:#8aa0bd;text-transform:uppercase;font-size:12px;font-weight:600}.ch-plot{height:300px;border:1px solid #e6edf6;border-radius:8px;background:#f8fafc;padding:18px 20px 22px}.ch-plot svg{display:block;width:100%;height:100%;stroke-width:1}.ch-plot .grid{stroke:#dbeafe;stroke-dasharray:4 7}.ch-plot .axis{stroke:#b8c7dc}.ch-plot .label{fill:#8090ad;font-size:3.15px;font-weight:400!important;stroke:none!important;text-shadow:none!important}.ch-plot .axis-title{fill:#94a3b8;font-size:2.85px;font-weight:400!important;stroke:none!important;text-shadow:none!important}.ch-plot .line{fill:none;stroke:var(--pink);stroke-width:1.25}.ch-plot .line.is-purple{stroke:#7c3aed}.ch-plot .dot{fill:#fff;stroke:#2563eb;stroke-width:1.8}.ch-plot .dot.is-purple{stroke:#7c3aed}.ch-empty{display:grid;place-items:center;min-height:180px;color:#64748b;background:#f8fafc;border:1px dashed #d5deea;border-radius:8px;text-align:center;padding:20px;font-weight:400}.ch-section{padding:18px;margin-bottom:20px}.ch-section-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px}.ch-section h2{margin:0;font-size:19px;font-weight:600}.ch-muted{margin:6px 0 0;color:var(--muted);font-size:13px;font-weight:400}.ch-count{border-radius:999px;background:#f8fafc;color:#8090ad;padding:6px 10px;font-size:12px;font-weight:600;text-transform:uppercase}.ch-table-wrap{overflow:auto}.ch-table{width:100%;border-collapse:collapse}.ch-table th{background:#f8fafc;color:#8aa0bd;text-align:left;font-size:12px;text-transform:uppercase;font-weight:500;padding:12px}.ch-table td{padding:13px 12px;border-top:1px solid #eef2f7;color:#24324b;font-weight:400}.ch-vaccine-summary{display:flex;gap:8px;flex-wrap:wrap}.ch-status{display:inline-flex;align-items:center;justify-content:center;border-radius:999px;padding:6px 10px;font-size:11px;text-transform:uppercase;font-weight:600}.ch-status.is-complete{background:#dcfce7;color:#007f5f;border:1px solid #86efc2}.ch-status.is-upcoming{background:#eff6ff;color:#075ef2;border:1px solid #bfdbfe}.ch-status.is-due{background:#fff7ed;color:#c2410c;border:1px solid #fed7aa}.ch-status.is-overdue{background:#fff1f2;color:#e11d48;border:1px solid #fecdd3}.ch-vaccine-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.ch-vaccine{padding:16px;border:1px solid #d7e1ee;border-radius:8px;background:#f8fbff}.ch-vaccine.is-overdue{background:#fff7f9;border-color:#fecdd3}.ch-vaccine-top{display:flex;justify-content:space-between;gap:12px;align-items:start}.ch-vaccine h3{margin:0;font-size:16px;font-weight:600}.ch-vaccine small{display:block;margin-top:4px;color:#52627d;font-weight:400}.ch-vaccine dl{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:14px 0 0}.ch-vaccine dt{color:#64748b;font-size:12px;font-weight:600}.ch-vaccine dd{margin:4px 0 0;color:#17233b;font-size:14px;font-weight:400}.ch-modal[hidden]{display:none}.ch-modal{position:fixed;inset:0;z-index:80;display:flex;align-items:center;justify-content:center;padding:16px}.ch-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.58);backdrop-filter:blur(3px)}.ch-dialog{position:relative;width:min(640px,calc(100vw - 24px));max-height:86vh;overflow:auto;background:#fff;border-radius:8px;box-shadow:0 28px 70px rgba(15,23,42,.26)}.ch-dialog header,.ch-dialog footer{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:20px 22px;border-bottom:1px solid #e2e8f0}.ch-dialog footer{border-top:1px solid #e2e8f0;border-bottom:0;justify-content:end}.ch-dialog h2{margin:0;font-size:20px;font-weight:600}.ch-close{width:38px;height:38px;border:0;background:#fff;color:#64748b;border-radius:999px;display:grid;place-items:center;cursor:pointer}.ch-form{padding:20px 22px;display:grid;grid-template-columns:1fr 1fr;gap:14px}.ch-form label{display:grid;gap:7px;color:#64748b;font-size:12px;text-transform:uppercase;font-weight:600}.ch-form label.is-wide{grid-column:1/-1}.ch-form input,.ch-form select{height:44px;border:1px solid #cbd8ea;border-radius:7px;padding:0 12px;font-size:14px;font-weight:400;outline:none}.ch-help{grid-column:1/-1;border:1px solid #bfdbfe;background:#eff6ff;border-radius:7px;padding:12px;color:#1d4ed8;font-size:13px}.ch-help summary{cursor:pointer;font-weight:600}.ch-help p{margin:8px 0 0;color:#334155;line-height:1.45}.ch-error{grid-column:1/-1;color:#be123c;font-size:13px;font-weight:500}.ch-success{grid-column:1/-1;color:#007f5f;font-size:13px;font-weight:500}body.has-ch-modal{overflow:hidden}@media(max-width:1100px){.ch-profile-main{grid-template-columns:1fr 1fr}.ch-child-title{grid-column:1/-1}.ch-vaccine-grid{grid-template-columns:1fr}}@media(max-width:760px){.ch-top,.ch-charts,.ch-alert-grid{grid-template-columns:1fr}.ch-actions{align-items:stretch}.ch-select,.ch-button{width:100%;min-width:0}.ch-profile-main,.ch-form{grid-template-columns:1fr}.ch-vaccine dl{grid-template-columns:1fr}.ch-plot{height:260px}}
    </style>

    <section class="child-health" aria-label="Mother Child Health Monitoring">
        <header class="ch-top">
            <div>
                <p class="ch-kicker">Mother Portal / Pediatric Care</p>
                <h1>Child Health Monitoring</h1>
                <p>Track children's growth, immunization status, and health alerts in one workspace.</p>
            </div>
            <div class="ch-actions">
                <label>Active Patient Profile
                    <select class="ch-select" onchange="if(this.value){window.location=this.value}">
                        @forelse($mother->infants as $child)
                            @php $childAge = $child->birth_date ? max(0, (int) $child->birth_date->diffInMonths(now())) : 0; @endphp
                            <option value="{{ route('child-health', ['child' => $child->id]) }}" @selected($selectedChild?->id === $child->id)>{{ $child->full_name }} ({{ $childAge }} months)</option>
                        @empty
                            <option>No child profile yet</option>
                        @endforelse
                    </select>
                </label>
                <button class="ch-button" type="button" data-ch-open>{!! $iconPlus !!} Add Child</button>
            </div>
        </header>

        @if (session('status'))
            <div class="ch-normal">{{ session('status') }}</div>
        @endif

        @if($selectedChild)
            <section class="ch-card ch-profile">
                <div class="ch-profile-main">
                    <div class="ch-child-title">
                        <span class="ch-avatar">{{ $initials($selectedChild->full_name) }}</span>
                        <div>
                            <h2>{{ $selectedChild->full_name }}</h2>
                            <p>Monthly Pediatric Wellness Checklist</p>
                            <div class="ch-pills"><span class="ch-pill is-danger">{{ $alerts->count() }} active alert{{ $alerts->count() === 1 ? '' : 's' }}</span><span class="ch-pill is-blue">{{ $completedVaccines }} vaccines completed</span></div>
                        </div>
                    </div>
                    <article class="ch-metric"><span>{!! $iconBaby !!} Age</span><strong>{{ $ageMonths }} months</strong></article>
                    <article class="ch-metric"><span>{!! $iconCalendar !!} Birth Date</span><strong>{{ $selectedChild->birth_date?->format('M j, Y') ?? 'N/A' }}</strong></article>
                    <article class="ch-metric"><span>{!! $iconScale !!} Weight</span><strong>{{ $formatNumber($latestGrowth?->weight, ' kg') }}</strong></article>
                    <article class="ch-metric"><span>{!! $iconRuler !!} Height</span><strong>{{ $formatNumber($latestGrowth?->height, ' cm') }}</strong></article>
                </div>
                <div class="ch-last">Last measurement: <strong>{{ $latestGrowth?->measured_at?->format('M j, Y') ?? 'No growth measurement from Program Staff yet' }}</strong></div>
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
                @foreach([['Weight Progress', 'weight', 'kg', $weightChart, $latestGrowth?->weight, ''], ['Height Progress', 'height', 'cm', $heightChart, $latestGrowth?->height, 'is-purple']] as [$title, $field, $unit, $chart, $latest, $purple])
                    <article class="ch-card ch-chart">
                        <div class="ch-chart-head"><div><h3>{!! $iconTrend !!} {{ $title }}</h3><p>Latest: {{ $formatNumber($latest, ' '.$unit) }}</p></div><small>Unit: {{ strtoupper($unit) }}</small></div>
                        <div class="ch-plot">
                            @if($chart['has'])
                                <svg viewBox="0 0 100 100" role="img" aria-label="{{ $title }} by age in months">
                                    <path class="grid" d="M18 24H90M18 52H90M18 80H90"/><path class="axis" d="M18 18V84H90"/>
                                    <text class="label" x="8" y="25">{{ $chart['max'] }}</text><text class="label" x="8" y="53">{{ $chart['mid'] }}</text><text class="label" x="8" y="81">{{ $chart['min'] }}</text>
                                    <text class="label" x="18" y="92" text-anchor="middle">{{ $chart['min_age'] }}</text><text class="label" x="54" y="92" text-anchor="middle">{{ $chart['mid_age'] }}</text><text class="label" x="90" y="92" text-anchor="middle">{{ $chart['max_age'] }}</text><text class="axis-title" x="54" y="97" text-anchor="middle">Age (months)</text>
                                    @if($chart['points']->count() > 1)<polyline class="line {{ $purple }}" points="{{ $chart['path'] }}"/>@endif
                                    @foreach($chart['points'] as $index => $point)<circle class="dot {{ $purple }}" cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="{{ $index === $chart['points']->count() - 1 ? '2.1' : '1.6' }}"><title>Age {{ $point['age'] }} mo - {{ $formatNumber($point['value'], ' '.$unit) }} - {{ $point['date'] }}</title></circle>@endforeach
                                </svg>
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
                <div class="ch-section-head"><div><h2>{!! $iconSyringe !!} Immunization Schedule</h2><p class="ch-muted">Program staff-recorded vaccine status for {{ $selectedChild->full_name }}.</p></div><div class="ch-vaccine-summary"><span class="ch-status is-complete">{{ $completedVaccines }} completed</span><span class="ch-status is-upcoming">{{ $upcomingVaccines }} upcoming</span><span class="ch-status is-overdue">{{ $overdueVaccines }} overdue</span></div></div>
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
        @else
            <section class="ch-card ch-section"><div class="ch-empty">No child profile yet. Add your child profile to start pediatric monitoring.</div></section>
        @endif

        <div class="ch-modal" data-ch-modal hidden>
            <div class="ch-backdrop" data-ch-close></div>
            <form class="ch-dialog" method="POST" action="{{ route('child-health.children.store') }}" data-child-form>
                @csrf
                <header><h2>Add Child</h2><button type="button" class="ch-close" data-ch-close aria-label="Close add child modal">{!! $iconClose !!}</button></header>
                <div class="ch-form">
                    <details class="ch-help"><summary>What to put in each child profile field</summary><p>Enter the child's full name, biological sex, and birth date. Birth date cannot be in the future.</p></details>
                    <label class="is-wide">Child Name<input name="full_name" required maxlength="255" autocomplete="off"></label>
                    <label>Sex<select name="sex" required><option value="unspecified">Unspecified</option><option value="female">Female</option><option value="male">Male</option><option value="other">Other</option></select></label>
                    <label>Birth Date<input type="date" name="birth_date" required max="{{ now()->toDateString() }}"></label>
                    <div class="ch-error" data-child-error hidden></div><div class="ch-success" data-child-success hidden></div>
                </div>
                <footer><button class="ch-button is-light" type="button" data-ch-close>Cancel</button><button class="ch-button" type="submit" data-child-submit>Save Child</button></footer>
            </form>
        </div>
    </section>

    <script>
        (() => {
            const modal = document.querySelector('[data-ch-modal]');
            const form = document.querySelector('[data-child-form]');
            const submit = document.querySelector('[data-child-submit]');
            const error = document.querySelector('[data-child-error]');
            const success = document.querySelector('[data-child-success]');
            const close = () => { modal.hidden = true; document.body.classList.remove('has-ch-modal'); };
            const open = () => { modal.hidden = false; document.body.classList.add('has-ch-modal'); form?.querySelector('input[name="full_name"]')?.focus(); };
            document.querySelector('[data-ch-open]')?.addEventListener('click', open);
            document.querySelectorAll('[data-ch-close]').forEach((el) => el.addEventListener('click', close));
            document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !modal.hidden) close(); });
            form?.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (submit.disabled) return;
                error.hidden = true; success.hidden = true; submit.disabled = true; submit.textContent = 'Saving...';
                try {
                    const response = await fetch(form.action, { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: new FormData(form) });
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || Object.values(data.errors || {})?.[0]?.[0] || 'Unable to save child profile.');
                    success.textContent = data.message || 'Child profile saved.'; success.hidden = false;
                    setTimeout(() => { window.location.href = data.url; }, 350);
                } catch (err) {
                    error.textContent = err.message || 'Unable to save child profile.'; error.hidden = false; submit.disabled = false; submit.textContent = 'Save Child';
                }
            });
        })();
    </script>
@endsection