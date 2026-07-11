@extends('layouts.app')

@section('title', 'Neonatal & Vaccines - Project INAY')
@section('portal_title', 'Neonatal & Vaccines')

@php
    $iconSearch = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>';
    $iconBaby = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 12h.01"/><path d="M15 12h.01"/><path d="M10 16c.5.3 1.2.5 2 .5s1.5-.2 2-.5"/><path d="M19 12a7 7 0 1 1-14 0c0-2.2 1-4.1 2.6-5.4"/></svg>';
    $iconPlus = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg>';
    $iconEdit = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/></svg>';
    $iconUpload = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M20 16v4H4v-4"/></svg>';
    $iconTrend = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 17 6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>';
    $iconAlert = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>';
    $iconSyringe = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m18 2 4 4"/><path d="m17 7 3-3"/><path d="M19 9 8.7 19.3a2.4 2.4 0 0 1-3.4 0l-.6-.6a2.4 2.4 0 0 1 0-3.4L15 5"/><path d="m9 11 4 4"/></svg>';
    $iconCalendar = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/></svg>';
    $iconScale = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v18"/><path d="m6 7 6-4 6 4"/><path d="M6 7 3 14h6L6 7Z"/><path d="m18 7-3 7h6l-3-7Z"/></svg>';
    $iconRuler = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m16 2 6 6L8 22l-6-6L16 2Z"/><path d="m7.5 10.5 2 2"/><path d="m10.5 7.5 2 2"/><path d="m13.5 4.5 2 2"/></svg>';
    $iconClose = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>';
    $initials = fn ($name) => collect(preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [])->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('') ?: 'CH';
    $formatNumber = fn ($value, $suffix = '') => $value === null ? 'N/A' : rtrim(rtrim(number_format((float) $value, 2), '0'), '.').$suffix;
    $bloodTypeOptions = ['Unknown', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    $today = now()->startOfDay();
    $selectedMother = $selectedInfant?->mother ?? $mothers->first();
    $selectedGrowth = $selectedInfant?->growthRecords ?? collect();
    $latestGrowth = $selectedGrowth->last();
    $ageMonths = $selectedInfant ? ($latestGrowth?->age_months ?? ($selectedInfant->birth_date ? max(0, (int) $selectedInfant->birth_date->diffInMonths(now())) : 0)) : 0;
    $selectedPhotoUrl = $selectedInfant?->photo_path ? asset('storage/'.$selectedInfant->photo_path) : null;
    $vaccineState = function ($record) use ($today) {
        if ($record->status === 'completed') return ['Completed', 'is-complete', 5];
        if ($record->status === 'cancelled') return ['Cancelled', 'is-cancelled', 4];
        if (in_array($record->status, ['missed', 'overdue'], true)) return [ucfirst($record->status), 'is-overdue', 1];
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
    $weightChart = $chartData($selectedGrowth, 'weight');
    $heightChart = $chartData($selectedGrowth, 'height');
    $vaccines = $selectedInfant?->vaccineRecords ?? collect();
    $vaccineRows = $vaccines->map(function ($record) use ($vaccineState) { [$label, $class, $sort] = $vaccineState($record); $record->display_status = $label; $record->display_class = $class; $record->sort_rank = $sort; return $record; })->sortBy(fn ($record) => $record->sort_rank.'-'.($record->due_date?->format('Ymd') ?? '99999999'))->values();
    $completedVaccines = $vaccines->where('status', 'completed')->count();
    $overdueVaccines = $vaccineRows->where('display_class', 'is-overdue')->count();
    $upcomingVaccines = $vaccineRows->where('display_class', 'is-upcoming')->count();
    $activeAlerts = ($selectedInfant?->healthAlerts ?? collect())->where('status', 'active');
    $growthAlerts = collect();
    if ($latestGrowth && $selectedInfant) {
        $minWeight = max(2.4, 2.6 + ($ageMonths * 0.45));
        $maxWeight = max(4.6, 5.0 + ($ageMonths * 0.75));
        $minHeight = max(45, 47 + ($ageMonths * 1.4));
        if ((float) $latestGrowth->weight < $minWeight || (float) $latestGrowth->weight > $maxWeight) $growthAlerts->push(['title' => 'Weight needs review', 'text' => $selectedInfant->full_name.' weight needs review for age.']);
        if ((float) $latestGrowth->height < $minHeight) $growthAlerts->push(['title' => 'Height needs review', 'text' => $selectedInfant->full_name.' height needs review for age.']);
    } elseif ($selectedInfant) {
        $growthAlerts->push(['title' => 'Awaiting growth record', 'text' => 'No growth measurement has been recorded yet.']);
    }
    if ($overdueVaccines > 0) $growthAlerts->push(['title' => 'Overdue vaccine follow-up', 'text' => $overdueVaccines.' vaccine'.($overdueVaccines === 1 ? '' : 's').' need status review.']);
    foreach ($activeAlerts as $staffAlert) {
        $growthAlerts->push(['title' => $staffAlert->title, 'text' => $staffAlert->notes ?: 'Follow-up marked by program staff.', 'record' => $staffAlert]);
    }
@endphp

@section('content')
    <style>
        .neonatal-shell{--pink:#ec008c;--navy:#071127;--muted:#52627d;--line:#dbe5f1;--soft:#f8fafc;--green:#00856a;--blue:#2563eb;color:var(--navy)}.neonatal-shell *{box-sizing:border-box;letter-spacing:0}.neonatal-shell svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}.neo-heading{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:22px;align-items:end;border-bottom:1px solid #d4deec;padding-bottom:22px;margin-bottom:22px}.neo-kicker{margin:0 0 10px;color:var(--pink);font-size:12px;font-weight:800;text-transform:uppercase}.neo-heading h1{margin:0;font-size:30px;line-height:1.08;font-weight:900}.neo-heading p{margin:8px 0 0;color:var(--muted);font-size:15px}.neo-stats{display:grid;grid-template-columns:repeat(3,130px);gap:12px}.neo-stat{padding:14px;border:1px solid var(--line);border-radius:8px;background:#fff;box-shadow:0 2px 8px rgba(15,23,42,.08)}.neo-stat span{display:block;color:#8aa0bd;text-transform:uppercase;font-size:11px;font-weight:800}.neo-stat strong{display:block;margin-top:8px;font-size:24px;font-weight:900}.neo-workspace{display:grid;grid-template-columns:minmax(300px,380px) minmax(0,1fr);gap:22px;align-items:start}.neo-card{background:#fff;border:1px solid var(--line);border-radius:8px;box-shadow:0 2px 8px rgba(15,23,42,.08)}.neo-sidebar{position:sticky;top:96px;padding:18px}.neo-sidebar-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start}.neo-sidebar h2,.neo-section h2{margin:0;font-size:18px;font-weight:900}.neo-muted{margin:6px 0 0;color:var(--muted);font-size:13px}.neo-search{display:flex;align-items:center;gap:10px;height:44px;margin:16px 0 12px;padding:0 12px;border:1px solid #cbd8ea;border-radius:8px;color:#8aa0bd}.neo-search input{width:100%;border:0;outline:0;color:#0f1b33}.neo-list{display:grid;gap:9px;max-height:540px;overflow:auto;padding-right:4px}.neo-child-link{display:grid;grid-template-columns:auto minmax(0,1fr) auto;gap:12px;align-items:center;padding:12px;border:1px solid #e0e8f3;border-radius:8px;background:#fff;color:inherit;text-decoration:none}.neo-child-link:hover,.neo-child-link.is-active{border-color:#ff9bd1;background:#fff4fa;text-decoration:none}.neo-avatar{position:relative;display:grid;place-items:center;width:50px;height:50px;border-radius:999px;background:#7c2dff;color:#fff;font-weight:900;overflow:hidden}.neo-avatar img{width:100%;height:100%;object-fit:cover}.neo-link-name{display:block;overflow:hidden;color:#071127;font-weight:900;text-overflow:ellipsis;white-space:nowrap}.neo-link-meta{display:block;margin-top:4px;color:#64748b;font-size:12px}.neo-badge,.neo-status,.neo-count{display:inline-flex;align-items:center;justify-content:center;border-radius:999px;padding:6px 10px;font-size:11px;font-weight:900;text-transform:uppercase}.neo-badge.is-danger,.neo-status.is-overdue{background:#fff1f2;color:#be123c;border:1px solid #fecdd3}.neo-badge.is-blue,.neo-status.is-upcoming{background:#eff6ff;color:#075ef2;border:1px solid #bfdbfe}.neo-badge.is-good,.neo-status.is-complete{background:#dcfce7;color:#007f5f;border:1px solid #86efc2}.neo-status.is-due{background:#fff7ed;color:#c2410c;border:1px solid #fed7aa}.neo-status.is-cancelled{background:#f1f5f9;color:#64748b;border:1px solid #cbd5e1}.neo-main{display:grid;gap:18px}.neo-profile{padding:22px}.neo-profile-head{display:grid;grid-template-columns:minmax(260px,1.25fr) repeat(4,minmax(120px,1fr));gap:14px;align-items:center}.neo-title{display:flex;gap:16px;align-items:center;min-width:0}.neo-avatar-form{margin:0}.neo-avatar-button{position:relative;display:grid;cursor:pointer}.neo-avatar.is-large{width:74px;height:74px;font-size:26px}.neo-avatar-action{position:absolute;left:-4px;top:-4px;display:grid;width:26px;height:26px;place-items:center;border:2px solid #fff;border-radius:999px;background:var(--pink);color:#fff;box-shadow:0 8px 18px rgba(236,0,140,.2)}.neo-avatar-button input{position:absolute;width:1px;height:1px;opacity:0;pointer-events:none}.neo-title h2{margin:0;font-size:24px;line-height:1.15;font-weight:900}.neo-title p{margin:6px 0 0;color:var(--muted);font-size:14px}.neo-pills,.neo-actions,.neo-vaccine-summary{display:flex;gap:8px;flex-wrap:wrap}.neo-pills{margin-top:10px}.neo-metric,.neo-extra article{padding:14px;border:1px solid #d8e2ee;border-radius:7px;background:#f8fbff}.neo-metric span,.neo-extra span{display:flex;align-items:center;gap:7px;color:#8aa0bd;font-size:11px;text-transform:uppercase;font-weight:900}.neo-metric strong,.neo-extra strong{display:block;margin-top:8px;font-size:16px;font-weight:900}.neo-extra{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin-top:16px}.neo-last{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-top:18px;padding-top:15px;border-top:1px solid #edf2f7;color:var(--muted);font-size:13px}.neo-button{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:42px;border:1px solid var(--pink);border-radius:8px;background:var(--pink);color:#fff;padding:0 14px;font-weight:900;text-decoration:none;cursor:pointer}.neo-button.is-light{background:#fff;color:#1f2a44;border-color:#cbd8ea}.neo-button.is-quiet{background:#f8fafc;color:#334155;border-color:#dbe5f1}.neo-button.is-green{background:#ecfdf5;color:#007f5f;border-color:#86efc2}.neo-alert-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.neo-alert{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;padding:14px;border:1px solid #fecdd3;border-radius:8px;background:#fff1f2;color:#be123c}.neo-alert strong{display:block;font-weight:900}.neo-alert p{margin:5px 0 0;font-size:14px;line-height:1.45}.neo-alert form{margin:0}.neo-normal,.neo-error-box{padding:14px 16px;border-radius:8px;font-weight:800}.neo-normal{border:1px solid #86efc2;background:#ecfdf5;color:#007f5f}.neo-error-box{display:flex;gap:10px;border:1px solid #fecdd3;background:#fff1f2;color:#be123c}.neo-section{padding:18px}.neo-section-head,.neo-chart-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:14px}.neo-section-head h2,.neo-chart h3{display:flex;align-items:center;gap:8px}.neo-charts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.neo-chart{padding:18px}.neo-chart h3{margin:0;font-size:17px;font-weight:900}.neo-chart p{margin:7px 0 0;color:var(--muted);font-size:13px}.neo-chart small{color:#8aa0bd;text-transform:uppercase;font-weight:900}.neo-plot{height:260px;border:1px solid #e6edf6;border-radius:8px;background:#f8fafc;padding:18px 20px 22px}.neo-plot svg{display:block;width:100%;height:100%;stroke-width:1}.neo-plot .grid{stroke:#dbeafe;stroke-dasharray:4 7}.neo-plot .axis{stroke:#b8c7dc}.neo-plot .label{fill:#8090ad;font-size:3.15px;font-weight:400!important;stroke:none!important}.neo-plot .axis-title{fill:#94a3b8;font-size:2.85px;font-weight:400!important;stroke:none!important}.neo-plot .line{fill:none;stroke:var(--pink);stroke-width:1.25}.neo-plot .line.is-purple{stroke:#7c3aed}.neo-plot .dot{fill:#fff;stroke:#2563eb;stroke-width:1.8}.neo-plot .dot.is-purple{stroke:#7c3aed}.neo-empty{display:grid;place-items:center;min-height:160px;color:#64748b;background:#f8fafc;border:1px dashed #d5deea;border-radius:8px;text-align:center;padding:20px}.neo-table-wrap{overflow:auto}.neo-table{width:100%;border-collapse:collapse}.neo-table th{background:#f8fafc;color:#8aa0bd;text-align:left;font-size:12px;text-transform:uppercase;font-weight:900;padding:12px}.neo-table td{padding:13px 12px;border-top:1px solid #eef2f7;color:#24324b}.neo-vaccine-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.neo-vaccine{width:100%;text-align:left;padding:16px;border:1px solid #d7e1ee;border-radius:8px;background:#f8fbff;color:inherit;cursor:pointer}.neo-vaccine:hover{border-color:#9ec5fe;background:#f4f8ff}.neo-vaccine.is-overdue{background:#fff7f9;border-color:#fecdd3}.neo-vaccine-top{display:flex;justify-content:space-between;gap:12px;align-items:flex-start}.neo-vaccine h3{margin:0;font-size:16px;font-weight:900}.neo-vaccine small{display:block;margin-top:4px;color:#52627d}.neo-vaccine dl{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:14px 0 0}.neo-vaccine dt{color:#64748b;font-size:12px;font-weight:900}.neo-vaccine dd{margin:4px 0 0;color:#17233b;font-size:14px}.neo-modal[hidden]{display:none}.neo-modal{position:fixed;inset:0;z-index:80;display:flex;align-items:center;justify-content:center;padding:16px}.neo-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.58);backdrop-filter:blur(3px)}.neo-dialog{position:relative;width:min(740px,calc(100vw - 24px));max-height:86vh;overflow:auto;background:#fff;border-radius:8px;box-shadow:0 28px 70px rgba(15,23,42,.26)}.neo-dialog header,.neo-dialog footer{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:20px 22px;border-bottom:1px solid #e2e8f0}.neo-dialog footer{border-top:1px solid #e2e8f0;border-bottom:0;justify-content:end}.neo-dialog h2{margin:0;font-size:20px;font-weight:900}.neo-close{width:38px;height:38px;border:0;background:#fff;color:#64748b;border-radius:999px;display:grid;place-items:center;cursor:pointer}.neo-form{padding:20px 22px;display:grid;grid-template-columns:1fr 1fr;gap:14px}.neo-form label{display:grid;gap:7px;color:#64748b;font-size:12px;text-transform:uppercase;font-weight:900}.neo-form label.is-wide{grid-column:1/-1}.neo-form input,.neo-form select,.neo-form textarea{width:100%;border:1px solid #cbd8ea;border-radius:7px;background:#fff;color:#0f1b33;font-size:14px;outline:none}.neo-form input,.neo-form select{height:44px;padding:0 12px}.neo-form input[type=file]{height:auto;padding:10px 12px}.neo-form textarea{min-height:96px;padding:12px;resize:vertical}body.has-neo-modal{overflow:hidden}@media(max-width:1180px){.neo-heading,.neo-workspace,.neo-charts,.neo-alert-grid{grid-template-columns:1fr}.neo-sidebar{position:static}.neo-stats{grid-template-columns:repeat(3,minmax(0,1fr))}.neo-profile-head{grid-template-columns:1fr 1fr}.neo-title{grid-column:1/-1}.neo-extra{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:760px){.neo-stats,.neo-profile-head,.neo-extra,.neo-form,.neo-vaccine-grid{grid-template-columns:1fr}.neo-heading{align-items:start}.neo-vaccine dl{grid-template-columns:1fr}.neo-last{align-items:flex-start;flex-direction:column}.neo-button{width:100%}}
    </style>

    <section class="neonatal-shell" aria-label="Staff Neonatal and Vaccine Workspace">
        <header class="neo-heading">
            <div>
                <p class="neo-kicker">Program Staff / Neonatal Care</p>
                <h1>Neonatal & Vaccine Command</h1>
                <p>Child case records linked to your mother casefiles.</p>
            </div>
            <div class="neo-stats">
                <article class="neo-stat"><span>Linked Children</span><strong>{{ $neonatalStats['linked_infants'] ?? 0 }}</strong></article>
                <article class="neo-stat"><span>Completed</span><strong>{{ $neonatalStats['vaccines_completed'] ?? 0 }}</strong></article>
                <article class="neo-stat"><span>Follow-ups</span><strong>{{ ($neonatalStats['active_alerts'] ?? 0) + ($neonatalStats['pending_followups'] ?? 0) }}</strong></article>
            </div>
        </header>

        @if ($errors->any())
            <div class="neo-error-box">{!! $iconAlert !!}<span>{{ $errors->first() }}</span></div>
        @endif

        @if (session('status'))
            <div class="neo-normal">{{ session('status') }}</div>
        @endif

        <div class="neo-workspace">
            <aside class="neo-card neo-sidebar">
                <div class="neo-sidebar-head">
                    <div><h2>Linked Child Case Records</h2><p class="neo-muted">{{ $mothers->count() }} assigned mother{{ $mothers->count() === 1 ? '' : 's' }}</p></div>
                    <button class="neo-button" type="button" data-neo-open="add-child">{!! $iconPlus !!} Add</button>
                </div>
                <label class="neo-search">{!! $iconSearch !!}<input type="search" placeholder="Search child or mother" data-neo-search></label>
                <div class="neo-list" data-neo-list>
                    @forelse($children as $child)
                        @php
                            $childAge = $child->birth_date ? max(0, (int) $child->birth_date->diffInMonths(now())) : 0;
                            $childLatestGrowth = $child->growthRecords->last();
                            $childPhoto = $child->photo_path ? asset('storage/'.$child->photo_path) : null;
                            $childOverdue = $child->vaccineRecords->filter(function ($record) use ($today) {
                                return in_array($record->status, ['missed', 'overdue'], true) || ($record->status !== 'completed' && $record->due_date && $record->due_date->isBefore($today));
                            })->count();
                            $searchText = strtolower($child->full_name.' '.$child->mother?->full_name.' '.$child->mother?->barangay);
                        @endphp
                        <a class="neo-child-link @if($selectedInfant?->id === $child->id) is-active @endif" href="{{ route('staff.neonatal', ['child' => $child->id]) }}" data-search-text="{{ $searchText }}">
                            <span class="neo-avatar">@if($childPhoto)<img src="{{ $childPhoto }}" alt="{{ $child->full_name }}">@else{{ $initials($child->full_name) }}@endif</span>
                            <span><span class="neo-link-name">{{ $child->full_name }}</span><span class="neo-link-meta">{{ $child->mother?->full_name ?? 'No mother linked' }} / {{ $childAge }} mo / {{ $childLatestGrowth?->measured_at?->format('M j') ?? 'No growth' }}</span></span>
                            <span class="neo-badge {{ $childOverdue > 0 ? 'is-danger' : 'is-good' }}">{{ $childOverdue }}</span>
                        </a>
                    @empty
                        <div class="neo-empty">No child case records yet.</div>
                    @endforelse
                </div>
            </aside>

            <div class="neo-main">
                @if($childAccessDenied)
                    <section class="neo-card neo-section"><div class="neo-empty">The selected child profile is not assigned to your casefiles.</div></section>
                @elseif($selectedInfant)
                    <section class="neo-card neo-profile">
                        <div class="neo-profile-head">
                            <div class="neo-title">
                                <form class="neo-avatar-form" method="POST" action="{{ route('staff.neonatal.infants.photo.update', $selectedInfant) }}" enctype="multipart/form-data">
                                    @csrf
                                    @method('PATCH')
                                    <label class="neo-avatar-button" aria-label="Upload child profile photo">
                                        <span class="neo-avatar is-large">@if($selectedPhotoUrl)<img src="{{ $selectedPhotoUrl }}" alt="{{ $selectedInfant->full_name }}">@else{{ $initials($selectedInfant->full_name) }}@endif</span>
                                        <span class="neo-avatar-action">{!! $iconUpload !!}</span>
                                        <input type="file" name="child_photo" accept="image/png,image/jpeg,image/webp" data-photo-crop data-photo-auto-submit="true" data-photo-title="Upload Baby Profile Photo">
                                    </label>
                                </form>
                                <div>
                                    <h2>{{ $selectedInfant->full_name }}</h2>
                                    <p>{{ $selectedInfant->mother?->full_name ?? 'Mother profile unavailable' }}</p>
                                    <div class="neo-pills"><span class="neo-badge {{ $growthAlerts->count() > 0 ? 'is-danger' : 'is-good' }}">{{ $growthAlerts->count() }} alert{{ $growthAlerts->count() === 1 ? '' : 's' }}</span><span class="neo-badge is-blue">{{ $completedVaccines }} vaccines completed</span></div>
                                </div>
                            </div>
                            <article class="neo-metric"><span>{!! $iconBaby !!} Age</span><strong>{{ $ageMonths }} months</strong></article>
                            <article class="neo-metric"><span>{!! $iconCalendar !!} Birth Date</span><strong>{{ $selectedInfant->birth_date?->format('M j, Y') ?? 'N/A' }}</strong></article>
                            <article class="neo-metric"><span>{!! $iconScale !!} Weight</span><strong>{{ $formatNumber($latestGrowth?->weight, ' kg') }}</strong></article>
                            <article class="neo-metric"><span>{!! $iconRuler !!} Height</span><strong>{{ $formatNumber($latestGrowth?->height, ' cm') }}</strong></article>
                        </div>
                        <div class="neo-extra">
                            <article><span>Sex</span><strong>{{ ucfirst($selectedInfant->sex) }}</strong></article>
                            <article><span>Birth Weight</span><strong>{{ $formatNumber($selectedInfant->birth_weight, ' kg') }}</strong></article>
                            <article><span>Birth Length</span><strong>{{ $formatNumber($selectedInfant->birth_height, ' cm') }}</strong></article>
                            <article><span>Blood Type</span><strong>{{ $selectedInfant->blood_type ?: 'Unknown' }}</strong></article>
                            <article><span>Facility</span><strong>{{ $selectedInfant->facility ?: 'Not recorded' }}</strong></article>
                        </div>
                        <div class="neo-last">
                            <span>Last measurement: <strong>{{ $latestGrowth?->measured_at?->format('M j, Y') ?? 'No growth measurement yet' }}</strong></span>
                            <div class="neo-actions"><button class="neo-button is-light" type="button" data-neo-open="edit-child">{!! $iconEdit !!} Edit Child</button><button class="neo-button is-green" type="button" data-neo-open="growth">{!! $iconTrend !!} Update Growth</button></div>
                        </div>
                    </section>

                    @if($growthAlerts->isEmpty())
                        <div class="neo-normal">Growth and vaccine status is clear for the selected child.</div>
                    @else
                        <div class="neo-alert-grid">
                            @foreach($growthAlerts as $alert)
                                <article class="neo-alert">
                                    <div>{!! $iconAlert !!}<strong>{{ $alert['title'] }}</strong><p>{{ $alert['text'] }}</p></div>
                                    @if(! empty($alert['record']))
                                        <form method="POST" action="{{ route('staff.neonatal.alerts.resolve', $alert['record']) }}">@csrf @method('PATCH')<button class="neo-button is-light" type="submit">Resolve</button></form>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    @endif

                    <div class="neo-charts">
                        @foreach([['Weight Progress', 'kg', $weightChart, $latestGrowth?->weight, ''], ['Height Progress', 'cm', $heightChart, $latestGrowth?->height, 'is-purple']] as [$title, $unit, $chart, $latest, $purple])
                            <article class="neo-card neo-chart">
                                <div class="neo-chart-head"><div><h3>{!! $iconTrend !!} {{ $title }}</h3><p>Latest: {{ $formatNumber($latest, ' '.$unit) }}</p></div><small>Unit: {{ strtoupper($unit) }}</small></div>
                                <div class="neo-plot">
                                    @if($chart['has'])
                                        <svg viewBox="0 0 100 100" role="img" aria-label="{{ $title }} by age in months">
                                            <path class="grid" d="M18 24H90M18 52H90M18 80H90"/><path class="axis" d="M18 18V84H90"/>
                                            <text class="label" x="8" y="25">{{ $chart['max'] }}</text><text class="label" x="8" y="53">{{ $chart['mid'] }}</text><text class="label" x="8" y="81">{{ $chart['min'] }}</text>
                                            <text class="label" x="18" y="92" text-anchor="middle">{{ $chart['min_age'] }}</text><text class="label" x="54" y="92" text-anchor="middle">{{ $chart['mid_age'] }}</text><text class="label" x="90" y="92" text-anchor="middle">{{ $chart['max_age'] }}</text><text class="axis-title" x="54" y="97" text-anchor="middle">Age (months)</text>
                                            @if($chart['points']->count() > 1)<polyline class="line {{ $purple }}" points="{{ $chart['path'] }}"/>@endif
                                            @foreach($chart['points'] as $index => $point)<circle class="dot {{ $purple }}" cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="{{ $index === $chart['points']->count() - 1 ? '2.1' : '1.6' }}"><title>Age {{ $point['age'] }} mo - {{ $formatNumber($point['value'], ' '.$unit) }} - {{ $point['date'] }}</title></circle>@endforeach
                                        </svg>
                                    @else
                                        <div class="neo-empty">No {{ strtolower(str_replace(' Progress', '', $title)) }} record yet.</div>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <section class="neo-card neo-section">
                        <div class="neo-section-head"><h2>Growth History</h2><span class="neo-count">{{ $selectedGrowth->count() }} record{{ $selectedGrowth->count() === 1 ? '' : 's' }}</span></div>
                        @if($selectedGrowth->isEmpty())
                            <div class="neo-empty">No growth history available yet.</div>
                        @else
                            <div class="neo-table-wrap"><table class="neo-table"><thead><tr><th>Age</th><th>Weight</th><th>Height</th><th>Head / Temp</th><th>Date</th><th>Recorded By</th></tr></thead><tbody>@foreach($selectedGrowth->sortByDesc('measured_at') as $record)<tr><td>{{ $record->age_months }} mo</td><td>{{ $formatNumber($record->weight, ' kg') }}</td><td>{{ $formatNumber($record->height, ' cm') }}</td><td>{{ $formatNumber($record->head_circumference, ' cm') }} / {{ $formatNumber($record->temperature, ' C') }}</td><td>{{ $record->measured_at?->format('M j, Y') }}</td><td>{{ $record->recorder?->full_name ?? $staff->full_name }}</td></tr>@endforeach</tbody></table></div>
                        @endif
                    </section>

                    <section class="neo-card neo-section">
                        <div class="neo-section-head"><div><h2>{!! $iconSyringe !!} Vaccine Surveillance</h2><p class="neo-muted">{{ $selectedInfant->full_name }} schedule and completion status</p></div><div class="neo-vaccine-summary"><span class="neo-status is-complete">{{ $completedVaccines }} completed</span><span class="neo-status is-upcoming">{{ $upcomingVaccines }} upcoming</span><span class="neo-status is-overdue">{{ $overdueVaccines }} overdue</span><button class="neo-button is-light" type="button" data-neo-open="add-vaccine">{!! $iconPlus !!} Add Dose</button></div></div>
                        @if($vaccineRows->isEmpty())
                            <div class="neo-empty">No vaccine records available yet.</div>
                        @else
                            <div class="neo-vaccine-grid">
                                @foreach($vaccineRows as $vaccine)
                                    <button type="button" class="neo-vaccine {{ $vaccine->display_class }}" data-neo-vaccine data-action="{{ route('staff.neonatal.vaccines.update', $vaccine) }}" data-name="{{ $vaccine->vaccine_name }}" data-dose="{{ $vaccine->dose_label }}" data-status="{{ $vaccine->status }}" data-date="{{ $vaccine->administered_at?->toDateString() }}" data-facility="{{ $vaccine->facility }}" data-lot="{{ $vaccine->lot_number }}" data-vaccinator="{{ $vaccine->vaccinator }}" data-remarks="{{ $vaccine->remarks }}">
                                        <span class="neo-vaccine-top"><span><h3>{{ $vaccine->vaccine_name }}</h3><small>{{ $vaccine->dose_label }} / Due {{ $vaccine->due_date?->format('M j, Y') ?? 'Not scheduled' }}</small></span><span class="neo-status {{ $vaccine->display_class }}">{{ $vaccine->display_status }}</span></span>
                                        <dl><div><dt>Vaccination Date</dt><dd>{{ $vaccine->administered_at?->format('M j, Y') ?? 'Not recorded' }}</dd></div><div><dt>Facility</dt><dd>{{ $vaccine->facility ?: 'Not recorded' }}</dd></div><div><dt>Lot Number</dt><dd>{{ $vaccine->lot_number ?: 'N/A' }}</dd></div><div><dt>Vaccinator</dt><dd>{{ $vaccine->vaccinator ?: ($vaccine->recorder?->full_name ?? $staff->full_name) }}</dd></div></dl>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </section>
                @else
                    <section class="neo-card neo-section"><div class="neo-empty">No assigned child record yet. Add a child from an assigned mother casefile.</div></section>
                @endif
            </div>
        </div>

        <div class="neo-modal" data-neo-modal="add-child" hidden>
            <div class="neo-backdrop" data-neo-close></div>
            <form class="neo-dialog" method="POST" action="{{ route('staff.neonatal.infants.store') }}" enctype="multipart/form-data">
                @csrf
                <header><h2>Add Child Case Record</h2><button type="button" class="neo-close" data-neo-close aria-label="Close add child modal">{!! $iconClose !!}</button></header>
                <div class="neo-form">
                    <label class="is-wide">Mother<select name="mother_id" required>@foreach($mothers as $mother)<option value="{{ $mother->id }}" @selected(old('mother_id', $selectedMother?->id) == $mother->id)>{{ $mother->full_name }} - {{ $mother->barangay }}</option>@endforeach</select></label>
                    <label class="is-wide">Child Name<input name="full_name" required maxlength="255" value="{{ old('full_name') }}"></label>
                    <label>Sex<select name="sex" required><option value="female" @selected(old('sex') === 'female')>Female</option><option value="male" @selected(old('sex') === 'male')>Male</option><option value="other" @selected(old('sex') === 'other')>Other</option></select></label>
                    <label>Birth Date<input type="date" name="birth_date" required max="{{ now()->toDateString() }}" value="{{ old('birth_date') }}"></label>
                    <label>Birth Weight (kg)<input type="number" step="0.01" min="0.5" max="12" name="birth_weight" value="{{ old('birth_weight') }}"></label>
                    <label>Birth Length (cm)<input type="number" step="0.01" min="20" max="80" name="birth_height" value="{{ old('birth_height') }}"></label>
                    <label>Blood Type<select name="blood_type">@foreach($bloodTypeOptions as $type)<option value="{{ $type }}" @selected(old('blood_type', 'Unknown') === $type)>{{ $type }}</option>@endforeach</select></label>
                    <label>Profile Photo<input type="file" name="child_photo" accept="image/png,image/jpeg,image/webp" data-photo-crop data-photo-title="Upload Baby Profile Photo"></label>
                    <label class="is-wide">Facility<input name="facility" value="{{ old('facility', $selectedMother?->barangay ? 'RHU - '.$selectedMother->barangay : '') }}"></label>
                    <label class="is-wide">Notes<textarea name="notes">{{ old('notes') }}</textarea></label>
                </div>
                <footer><button class="neo-button is-light" type="button" data-neo-close>Cancel</button><button class="neo-button" type="submit">Save Child</button></footer>
            </form>
        </div>

        @if($selectedInfant)
            <div class="neo-modal" data-neo-modal="edit-child" hidden>
                <div class="neo-backdrop" data-neo-close></div>
                <form class="neo-dialog" method="POST" action="{{ route('staff.neonatal.infants.update', $selectedInfant) }}">
                    @csrf
                    @method('PATCH')
                    <header><h2>Edit Child Case Record</h2><button type="button" class="neo-close" data-neo-close aria-label="Close edit child modal">{!! $iconClose !!}</button></header>
                    <div class="neo-form">
                        <label class="is-wide">Mother<select name="mother_id" required>@foreach($mothers as $mother)<option value="{{ $mother->id }}" @selected(old('mother_id', $selectedInfant->mother_id) == $mother->id)>{{ $mother->full_name }} - {{ $mother->barangay }}</option>@endforeach</select></label>
                        <label class="is-wide">Child Name<input name="full_name" required maxlength="255" value="{{ old('full_name', $selectedInfant->full_name) }}"></label>
                        <label>Sex<select name="sex" required><option value="female" @selected(old('sex', $selectedInfant->sex) === 'female')>Female</option><option value="male" @selected(old('sex', $selectedInfant->sex) === 'male')>Male</option><option value="other" @selected(old('sex', $selectedInfant->sex) === 'other')>Other</option></select></label>
                        <label>Birth Date<input type="date" name="birth_date" required max="{{ now()->toDateString() }}" value="{{ old('birth_date', $selectedInfant->birth_date?->toDateString()) }}"></label>
                        <label>Birth Weight (kg)<input type="number" step="0.01" min="0.5" max="12" name="birth_weight" value="{{ old('birth_weight', $selectedInfant->birth_weight) }}"></label>
                        <label>Birth Length (cm)<input type="number" step="0.01" min="20" max="80" name="birth_height" value="{{ old('birth_height', $selectedInfant->birth_height) }}"></label>
                        <label>Blood Type<select name="blood_type">@foreach($bloodTypeOptions as $type)<option value="{{ $type }}" @selected(old('blood_type', $selectedInfant->blood_type ?: 'Unknown') === $type)>{{ $type }}</option>@endforeach</select></label>
                        <label>Facility<input name="facility" value="{{ old('facility', $selectedInfant->facility) }}"></label>
                        <label class="is-wide">Notes<textarea name="notes">{{ old('notes', $selectedInfant->notes) }}</textarea></label>
                    </div>
                    <footer><button class="neo-button is-light" type="button" data-neo-close>Cancel</button><button class="neo-button" type="submit">Save Changes</button></footer>
                </form>
            </div>

            <div class="neo-modal" data-neo-modal="growth" hidden>
                <div class="neo-backdrop" data-neo-close></div>
                <form class="neo-dialog" method="POST" action="{{ route('staff.neonatal.growth.store', $selectedInfant) }}">
                    @csrf
                    <header><h2>Update Growth</h2><button type="button" class="neo-close" data-neo-close aria-label="Close growth modal">{!! $iconClose !!}</button></header>
                    <div class="neo-form">
                        <label>Measurement Date<input type="date" name="measured_at" required max="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}"></label>
                        <label>Age (months)<input type="number" min="0" max="60" name="age_months" required value="{{ $ageMonths }}"></label>
                        <label>Weight (kg)<input type="number" step="0.01" min="0.5" max="50" name="weight" required></label>
                        <label>Height (cm)<input type="number" step="0.01" min="20" max="130" name="height" required></label>
                        <label>Head Circumference (cm)<input type="number" step="0.01" min="20" max="70" name="head_circumference"></label>
                        <label>Temperature (C)<input type="number" step="0.1" min="34" max="43" name="temperature"></label>
                        <label class="is-wide">Remarks<textarea name="remarks"></textarea></label>
                    </div>
                    <footer><button class="neo-button is-light" type="button" data-neo-close>Cancel</button><button class="neo-button" type="submit">Save Growth</button></footer>
                </form>
            </div>

            <div class="neo-modal" data-neo-modal="vaccine" hidden>
                <div class="neo-backdrop" data-neo-close></div>
                <form class="neo-dialog" method="POST" data-vaccine-form>
                    @csrf
                    <header><h2 data-vaccine-title>Update Vaccine</h2><button type="button" class="neo-close" data-neo-close aria-label="Close vaccine modal">{!! $iconClose !!}</button></header>
                    <div class="neo-form">
                        <label>Status<select name="status" data-vaccine-status><option value="upcoming">Upcoming</option><option value="completed">Completed</option><option value="overdue">Overdue</option><option value="missed">Missed</option><option value="cancelled">Cancelled</option></select></label>
                        <label>Administration Date<input type="date" name="administered_at" max="{{ now()->toDateString() }}" data-vaccine-date></label>
                        <label>Facility<input name="facility" data-vaccine-facility></label>
                        <label>Lot Number<input name="lot_number" data-vaccine-lot></label>
                        <label>Vaccinator<input name="vaccinator" data-vaccine-vaccinator></label>
                        <label class="is-wide">Remarks<textarea name="remarks" data-vaccine-remarks></textarea></label>
                    </div>
                    <footer><button class="neo-button is-light" type="button" data-neo-close>Cancel</button><button class="neo-button" type="submit">Save Vaccine</button></footer>
                </form>
            </div>

            <div class="neo-modal" data-neo-modal="add-vaccine" hidden>
                <div class="neo-backdrop" data-neo-close></div>
                <form class="neo-dialog" method="POST" action="{{ route('staff.neonatal.vaccines.store', $selectedInfant) }}">
                    @csrf
                    <header><h2>Add Vaccine Dose</h2><button type="button" class="neo-close" data-neo-close aria-label="Close add vaccine modal">{!! $iconClose !!}</button></header>
                    <div class="neo-form">
                        <label>Group<input name="vaccine_group" required maxlength="120"></label>
                        <label>Vaccine Name<input name="vaccine_name" required maxlength="255"></label>
                        <label>Dose Label<input name="dose_label" required maxlength="120"></label>
                        <label>Due Date<input type="date" name="due_date"></label>
                        <label>Status<select name="status" required><option value="upcoming">Upcoming</option><option value="completed">Completed</option><option value="overdue">Overdue</option><option value="missed">Missed</option><option value="cancelled">Cancelled</option></select></label>
                        <label>Administration Date<input type="date" name="administered_at" max="{{ now()->toDateString() }}"></label>
                        <label>Facility<input name="facility"></label>
                        <label>Lot Number<input name="lot_number"></label>
                        <label>Vaccinator<input name="vaccinator"></label>
                        <label class="is-wide">Remarks<textarea name="remarks"></textarea></label>
                    </div>
                    <footer><button class="neo-button is-light" type="button" data-neo-close>Cancel</button><button class="neo-button" type="submit">Save Dose</button></footer>
                </form>
            </div>
        @endif
    </section>

    <script>
        (() => {
            const body = document.body;
            const modals = Array.from(document.querySelectorAll('[data-neo-modal]'));
            const close = () => { modals.forEach((modal) => modal.hidden = true); body.classList.remove('has-neo-modal'); };
            const open = (name) => {
                const modal = document.querySelector(`[data-neo-modal="${name}"]`);
                if (!modal) return;
                close();
                modal.hidden = false;
                body.classList.add('has-neo-modal');
                modal.querySelector('input, select, textarea, button')?.focus();
            };
            document.querySelectorAll('[data-neo-open]').forEach((button) => button.addEventListener('click', () => open(button.dataset.neoOpen)));
            document.querySelectorAll('[data-neo-close]').forEach((el) => el.addEventListener('click', close));
            document.addEventListener('keydown', (event) => { if (event.key === 'Escape') close(); });

            const search = document.querySelector('[data-neo-search]');
            const rows = Array.from(document.querySelectorAll('[data-neo-list] [data-search-text]'));
            search?.addEventListener('input', () => {
                const term = search.value.trim().toLowerCase();
                rows.forEach((row) => { row.hidden = term !== '' && !row.dataset.searchText.includes(term); });
            });

            const vaccineForm = document.querySelector('[data-vaccine-form]');
            document.querySelectorAll('[data-neo-vaccine]').forEach((button) => {
                button.addEventListener('click', () => {
                    if (!vaccineForm) return;
                    vaccineForm.action = button.dataset.action || '';
                    document.querySelector('[data-vaccine-title]').textContent = `${button.dataset.name || 'Vaccine'} - ${button.dataset.dose || ''}`;
                    vaccineForm.querySelector('[data-vaccine-status]').value = button.dataset.status || 'upcoming';
                    vaccineForm.querySelector('[data-vaccine-date]').value = button.dataset.date || '';
                    vaccineForm.querySelector('[data-vaccine-facility]').value = button.dataset.facility || '';
                    vaccineForm.querySelector('[data-vaccine-lot]').value = button.dataset.lot || '';
                    vaccineForm.querySelector('[data-vaccine-vaccinator]').value = button.dataset.vaccinator || '';
                    vaccineForm.querySelector('[data-vaccine-remarks]').value = button.dataset.remarks || '';
                    open('vaccine');
                });
            });
        })();
    </script>
@endsection
