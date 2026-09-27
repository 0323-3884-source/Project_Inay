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
    $linkedChildrenTotal = (int) ($neonatalStats['linked_infants'] ?? 0);
    $completedVaccineTotal = (int) ($neonatalStats['vaccines_completed'] ?? 0);
    $followUpTotal = (int) (($neonatalStats['active_alerts'] ?? 0) + ($neonatalStats['pending_followups'] ?? 0));
    $neonatalSummaryCards = [
        [
            'label' => 'Linked Children',
            'value' => $linkedChildrenTotal,
            'description' => $linkedChildrenTotal === 1 ? 'Child profile from assigned mothers' : 'Child profiles from assigned mothers',
            'icon' => $iconBaby,
            'tone' => 'is-blue',
        ],
        [
            'label' => 'Completed Doses',
            'value' => $completedVaccineTotal,
            'description' => 'Vaccines marked completed',
            'icon' => $iconSyringe,
            'tone' => 'is-green',
        ],
        [
            'label' => 'Follow-ups',
            'value' => $followUpTotal,
            'description' => $followUpTotal > 0 ? 'Alerts and growth reviews pending' : 'No follow-up items pending',
            'icon' => $iconAlert,
            'tone' => $followUpTotal > 0 ? 'is-pink' : 'is-green',
        ],
    ];
    $selectedMother = $selectedMother ?? ($selectedInfant?->mother ?? $mothers->first());
    $selectedMotherInfants = collect($selectedMotherInfants ?? []);
    $selectedGrowth = $selectedInfant?->growthRecords ?? collect();
    $latestGrowth = $selectedGrowth->last();
    $ageMonths = \App\Support\ChildProfileDisplay::months($selectedInfant?->getRawOriginal('birth_date'));
    $growthReviewAge = $latestGrowth?->age_months ?? $ageMonths;
    $selectedPhotoUrl = $selectedInfant?->photo_path ? asset('storage/'.$selectedInfant->photo_path) : null;
    $vaccineState = function ($record) use ($today) {
        if ($record->status === 'completed') return ['Completed', 'is-complete', 5];
        if ($record->status === 'cancelled') return ['Cancelled', 'is-cancelled', 4];
        if (in_array($record->status, ['missed', 'overdue'], true)) return [ucfirst($record->status), 'is-overdue', 1];
        if ($record->due_date && $record->due_date->isSameDay($today)) return ['Due Today', 'is-due', 2];
        if ($record->due_date && $record->due_date->isBefore($today)) return ['Overdue', 'is-overdue', 1];
        return ['Upcoming', 'is-upcoming', 3];
    };

    $weightChart = \App\Support\ChildProfileDisplay::chart($selectedGrowth, 'weight', $selectedInfant?->getRawOriginal('birth_date'));
    $heightChart = \App\Support\ChildProfileDisplay::chart($selectedGrowth, 'height', $selectedInfant?->getRawOriginal('birth_date'));
    $vaccines = $selectedInfant?->vaccineRecords ?? collect();
    $vaccineRows = $vaccines->map(function ($record) use ($vaccineState) { [$label, $class, $sort] = $vaccineState($record); $record->display_status = $label; $record->display_class = $class; $record->sort_rank = $sort; return $record; })->sortBy(fn ($record) => $record->sort_rank.'-'.($record->due_date?->format('Ymd') ?? '99999999'))->values();
    $completedVaccines = $vaccines->where('status', 'completed')->count();
    $overdueVaccines = $vaccineRows->where('display_class', 'is-overdue')->count();
    $upcomingVaccines = $vaccineRows->where('display_class', 'is-upcoming')->count();
    $activeAlerts = ($selectedInfant?->healthAlerts ?? collect())->where('status', 'active');
    $growthAlerts = collect();
    if ($latestGrowth && $selectedInfant) {
        $minWeight = max(2.4, 2.6 + ($growthReviewAge * 0.45));
        $maxWeight = max(4.6, 5.0 + ($growthReviewAge * 0.75));
        $minHeight = max(45, 47 + ($growthReviewAge * 1.4));
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
        .neo-table-action-head,
        .neo-table-action-cell {
            text-align: right;
        }

        .neo-table-action {
            display: inline-flex;
            min-height: 34px;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 0 12px;
            color: #be185d;
            background: #fff5fa;
            border: 1px solid #fbcfe8;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 900;
            cursor: pointer;
            white-space: nowrap;
        }

        .neo-table-action:hover {
            color: #ffffff;
            background: #ec008c;
            border-color: #ec008c;
        }

        .neo-table-action svg {
            width: 15px;
            height: 15px;
        }

        .neo-child-switch {
            display: grid;
            gap: 6px;
            max-width: 340px;
            margin-top: 10px;
            color: #64748b;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .neo-child-switch select {
            width: 100%;
            height: 38px;
            padding: 0 11px;
            color: #0f1b33;
            background: #ffffff;
            border: 1px solid #cbd8ea;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 800;
        }

        .neonatal-shell {
            display: grid;
            gap: 18px;
            max-width: 1480px;
            margin: 0 auto;
        }

        .neonatal-shell .neo-heading {
            align-items: center;
            gap: 18px;
            margin-bottom: 0;
            padding-bottom: 18px;
            border-bottom-color: #dde6f1;
        }

        .neonatal-shell .neo-heading h1 {
            font-size: clamp(24px, 3vw, 34px);
        }

        .neonatal-shell .neo-heading p,
        .neonatal-shell .neo-muted {
            line-height: 1.45;
            font-weight: 650;
        }

        .neonatal-shell .neo-stats {
            grid-template-columns: repeat(3, minmax(118px, 1fr));
        }

        .neonatal-shell .neo-stat,
        .neonatal-shell .neo-card {
            border-color: #dce6f1;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
        }

        .neonatal-shell .neo-stat {
            min-height: 88px;
            padding: 13px 14px;
        }

        .neonatal-shell .neo-workspace {
            grid-template-columns: minmax(290px, 360px) minmax(0, 1fr);
            gap: 18px;
        }

        .neonatal-shell .neo-sidebar {
            top: 88px;
            padding: 16px;
        }

        .neonatal-shell .neo-search {
            height: 42px;
            margin: 14px 0 12px;
            background: #ffffff;
        }

        .neonatal-shell .neo-child-link {
            position: relative;
            min-height: 76px;
            padding: 11px 12px;
            transition: background 160ms ease, border-color 160ms ease, transform 160ms ease, box-shadow 160ms ease;
        }

        .neonatal-shell .neo-child-link:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(236, 0, 140, 0.08);
        }

        .neonatal-shell .neo-child-link.is-active {
            border-color: #ff9bd1;
            background: #fff4fa;
            box-shadow: inset 3px 0 0 #ec008c;
        }

        .neonatal-shell .neo-avatar {
            width: 48px;
            height: 48px;
        }

        .neonatal-shell .neo-profile,
        .neonatal-shell .neo-section,
        .neonatal-shell .neo-chart {
            padding: 16px;
        }

        .neonatal-shell .neo-profile-head {
            grid-template-columns: minmax(260px, 1.25fr) repeat(4, minmax(128px, 1fr));
            align-items: stretch;
            gap: 12px;
        }

        .neonatal-shell .neo-title {
            align-self: center;
        }

        .neonatal-shell .neo-metric,
        .neonatal-shell .neo-extra article {
            min-height: 86px;
            padding: 13px;
            background: #f8fafc;
            border-color: #dce6f1;
        }

        .neonatal-shell .neo-metric strong,
        .neonatal-shell .neo-extra strong {
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        .neonatal-shell .neo-extra {
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
            margin-top: 12px;
        }

        .neonatal-shell .neo-last {
            margin-top: 14px;
            padding-top: 14px;
        }

        .neonatal-shell .neo-button {
            min-height: 40px;
            padding: 0 13px;
            border-radius: 8px;
            transition: background 160ms ease, border-color 160ms ease, color 160ms ease, transform 160ms ease, box-shadow 160ms ease;
        }

        .neonatal-shell .neo-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(236, 0, 140, 0.1);
        }

        .neonatal-shell .neo-alert-grid,
        .neonatal-shell .neo-charts,
        .neonatal-shell .neo-vaccine-grid {
            gap: 12px;
        }

        .neonatal-shell .neo-alert {
            padding: 13px 14px;
            box-shadow: none;
        }

        .neonatal-shell .neo-chart-head,
        .neonatal-shell .neo-section-head {
            align-items: center;
            margin-bottom: 12px;
        }

        .neonatal-shell .neo-chart h3,
        .neonatal-shell .neo-section h2 {
            font-size: 17px;
        }

        .neonatal-shell .neo-plot {
            height: 230px;
            padding: 14px 16px 18px;
        }

        .neonatal-shell .neo-vaccine {
            padding: 14px;
            background: #ffffff;
            transition: background 160ms ease, border-color 160ms ease, transform 160ms ease, box-shadow 160ms ease;
        }

        .neonatal-shell .neo-vaccine:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.08);
        }

        .neonatal-shell .neo-table th,
        .neonatal-shell .neo-table td {
            padding: 12px;
            font-size: 13px;
        }

        .neonatal-shell .neo-modal {
            z-index: 100;
            padding: 18px;
        }

        .neonatal-shell .neo-dialog {
            display: flex;
            width: min(780px, calc(100vw - 24px));
            max-height: min(88vh, 760px);
            flex-direction: column;
            overflow: hidden;
            border: 1px solid #dce6f1;
            border-radius: 10px;
            box-shadow: 0 26px 70px rgba(15, 23, 42, 0.24);
        }

        .neonatal-shell .neo-dialog header,
        .neonatal-shell .neo-dialog footer {
            flex: 0 0 auto;
            padding: 18px 22px;
            background: #ffffff;
        }

        .neonatal-shell .neo-dialog header {
            border-bottom-color: #e5edf6;
        }

        .neonatal-shell .neo-dialog footer {
            border-top-color: #e5edf6;
        }

        .neonatal-shell .neo-dialog h2 {
            color: #9a2d67;
            font-size: 21px;
            line-height: 1.2;
        }

        .neonatal-shell .neo-close {
            display: inline-grid;
            width: 46px;
            height: 46px;
            flex: 0 0 46px;
            place-items: center;
            color: #475569;
            background: #f8fafc;
            border: 1px solid #dbe5f1;
            border-radius: 999px;
            cursor: pointer;
            transition: background 160ms ease, border-color 160ms ease, color 160ms ease, transform 160ms ease, box-shadow 160ms ease;
        }

        .neonatal-shell .neo-close svg {
            width: 22px;
            height: 22px;
            stroke-width: 2.5;
        }

        .neonatal-shell .neo-close:hover,
        .neonatal-shell .neo-close:focus-visible {
            color: #ec008c;
            background: #fff4fa;
            border-color: #ff9bd1;
            box-shadow: 0 8px 16px rgba(236, 0, 140, 0.1);
            outline: 0;
            transform: translateY(-1px);
        }

        .neonatal-shell .neo-form {
            min-height: 0;
            overflow: auto;
            padding: 18px 22px;
        }

        @media (max-width: 1180px) {
            .neonatal-shell .neo-heading,
            .neonatal-shell .neo-workspace,
            .neonatal-shell .neo-charts,
            .neonatal-shell .neo-alert-grid {
                grid-template-columns: 1fr;
            }

            .neonatal-shell .neo-sidebar {
                position: static;
            }

            .neonatal-shell .neo-profile-head {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .neonatal-shell .neo-title {
                grid-column: 1 / -1;
            }

            .neonatal-shell .neo-extra {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .neonatal-shell .neo-stats,
            .neonatal-shell .neo-profile-head,
            .neonatal-shell .neo-extra,
            .neonatal-shell .neo-form,
            .neonatal-shell .neo-vaccine-grid {
                grid-template-columns: 1fr;
            }

            .neonatal-shell .neo-heading {
                align-items: start;
            }

            .neonatal-shell .neo-last,
            .neonatal-shell .neo-section-head,
            .neonatal-shell .neo-chart-head {
                align-items: flex-start;
                flex-direction: column;
            }

            .neonatal-shell .neo-actions,
            .neonatal-shell .neo-vaccine-summary {
                width: 100%;
            }

            .neonatal-shell .neo-button {
                width: 100%;
            }

            .neonatal-shell .neo-dialog {
                width: calc(100vw - 18px);
                max-height: calc(100vh - 18px);
            }

            .neonatal-shell .neo-dialog header,
            .neonatal-shell .neo-dialog footer,
            .neonatal-shell .neo-form {
                padding: 16px;
            }
        }

        .neonatal-shell,
        .neonatal-shell .neo-main,
        .neonatal-shell .neo-profile,
        .neonatal-shell .neo-section,
        .neonatal-shell .neo-chart,
        .neonatal-shell .neo-dialog,
        .neonatal-shell .neo-form,
        .neonatal-shell .neo-vaccine,
        .neonatal-shell .neo-table-wrap {
            min-width: 0;
        }

        .neonatal-shell .neo-workspace {
            grid-template-columns: minmax(270px, clamp(290px, 24vw, 350px)) minmax(0, 1fr);
        }

        .neonatal-shell .neo-stats {
            width: min(100%, 480px);
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        }

        .neonatal-shell .neo-profile-head {
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 148px), 1fr));
        }

        .neonatal-shell .neo-profile-head .neo-title {
            grid-column: span 2;
            min-width: 0;
        }

        .neonatal-shell .neo-title > div {
            min-width: 0;
        }

        .neonatal-shell .neo-title h2,
        .neonatal-shell .neo-title p,
        .neonatal-shell .neo-link-name,
        .neonatal-shell .neo-link-meta {
            overflow-wrap: anywhere;
            white-space: normal;
        }

        .neonatal-shell .neo-child-switch {
            width: min(100%, 340px);
            max-width: 100%;
        }

        .neonatal-shell .neo-extra {
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 150px), 1fr));
        }

        .neonatal-shell .neo-alert-grid {
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 280px), 1fr));
        }

        .neonatal-shell .neo-charts {
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 330px), 1fr));
        }

        .neonatal-shell .neo-vaccine-grid {
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr));
        }

        .neonatal-shell .neo-vaccine-top,
        .neonatal-shell .neo-section-head,
        .neonatal-shell .neo-chart-head,
        .neonatal-shell .neo-last {
            flex-wrap: wrap;
        }

        .neonatal-shell .neo-vaccine-top > span:first-child,
        .neonatal-shell .neo-section-head > div,
        .neonatal-shell .neo-chart-head > div,
        .neonatal-shell .neo-last > span {
            min-width: min(100%, 220px);
        }

        .neonatal-shell .neo-actions,
        .neonatal-shell .neo-vaccine-summary {
            justify-content: flex-end;
        }

        .neonatal-shell .neo-button {
            flex: 0 1 auto;
            white-space: nowrap;
        }

        .neonatal-shell .neo-plot {
            height: clamp(190px, 24vw, 240px);
        }

        .neonatal-shell .neo-table-wrap {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            border: 1px solid #e5edf6;
            border-radius: 8px;
            -webkit-overflow-scrolling: touch;
        }

        .neonatal-shell .neo-table {
            min-width: 760px;
        }

        @media (max-width: 1440px) {
            .neonatal-shell .neo-workspace {
                grid-template-columns: 1fr;
            }

            .neonatal-shell .neo-sidebar {
                position: static;
            }

            .neonatal-shell .neo-list {
                max-height: none;
                grid-template-columns: repeat(auto-fit, minmax(min(100%, 260px), 1fr));
                padding-right: 0;
            }
        }

        @media (max-width: 980px) {
            .neonatal-shell .neo-heading {
                grid-template-columns: 1fr;
            }

            .neonatal-shell .neo-stats {
                width: 100%;
            }

            .neonatal-shell .neo-profile-head .neo-title {
                grid-column: 1 / -1;
            }

            .neonatal-shell .neo-actions,
            .neonatal-shell .neo-vaccine-summary {
                justify-content: flex-start;
            }
        }

        @media (max-width: 640px) {
            .neonatal-shell {
                gap: 14px;
            }

            .neonatal-shell .neo-heading h1 {
                font-size: 26px;
            }

            .neonatal-shell .neo-profile,
            .neonatal-shell .neo-section,
            .neonatal-shell .neo-chart,
            .neonatal-shell .neo-sidebar {
                padding: 14px;
            }

            .neonatal-shell .neo-title {
                align-items: flex-start;
                flex-direction: column;
            }

            .neonatal-shell .neo-avatar.is-large {
                width: 68px;
                height: 68px;
            }

            .neonatal-shell .neo-plot {
                height: 190px;
                padding: 12px;
            }

            .neonatal-shell .neo-vaccine dl {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .neonatal-shell .neo-button,
            .neonatal-shell .neo-table-action {
                width: 100%;
            }
        }

        .neonatal-shell .neo-heading {
            grid-template-columns: minmax(280px, 1fr) minmax(500px, 620px);
            align-items: end;
        }

        .neonatal-shell .neo-stats {
            width: 100%;
            max-width: 620px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            align-self: stretch;
        }

        .neonatal-shell .neo-stat {
            position: relative;
            display: grid;
            min-height: 118px;
            grid-template-columns: auto minmax(0, 1fr);
            grid-template-areas:
                "icon label"
                "value value"
                "copy copy";
            align-content: start;
            column-gap: 10px;
            row-gap: 6px;
            overflow: hidden;
            background: #ffffff;
            transition: border-color 160ms ease, transform 160ms ease, box-shadow 160ms ease;
        }

        .neonatal-shell .neo-stat:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
        }

        .neonatal-shell .neo-stat-icon {
            display: grid;
            width: 34px;
            height: 34px;
            grid-area: icon;
            place-items: center;
            color: #2563eb;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
        }

        .neonatal-shell .neo-stat-icon svg {
            width: 18px;
            height: 18px;
        }

        .neonatal-shell .neo-stat-label {
            display: block;
            grid-area: label;
            align-self: center;
            color: #7f91ad;
            font-size: 11px;
            font-weight: 900;
            line-height: 1.25;
            text-transform: uppercase;
        }

        .neonatal-shell .neo-stat strong {
            grid-area: value;
            margin-top: 4px;
            color: #071127;
            font-size: clamp(24px, 2vw, 32px);
            line-height: 1;
        }

        .neonatal-shell .neo-stat small {
            grid-area: copy;
            color: #52627d;
            font-size: 12px;
            font-weight: 750;
            line-height: 1.35;
        }

        .neonatal-shell .neo-stat.is-green .neo-stat-icon {
            color: #00856a;
            background: #ecfdf5;
            border-color: #a7f3d0;
        }

        .neonatal-shell .neo-stat.is-pink .neo-stat-icon {
            color: #be185d;
            background: #fff4fa;
            border-color: #fbcfe8;
        }

        .neonatal-shell .neo-stat.is-green {
            border-color: #c7f0de;
        }

        .neonatal-shell .neo-stat.is-pink {
            border-color: #f9c4df;
        }

        .neonatal-shell .neo-profile-head {
            align-items: start;
        }

        .neonatal-shell .neo-metric,
        .neonatal-shell .neo-extra article {
            display: grid;
            align-content: start;
            gap: 6px;
        }

        .neonatal-shell .neo-vaccine-summary .neo-status,
        .neonatal-shell .neo-count {
            min-height: 34px;
        }

        .neonatal-shell .neo-vaccine-summary {
            align-items: center;
        }

        .neonatal-shell .neo-vaccine {
            display: grid;
            gap: 12px;
        }

        .neonatal-shell .neo-vaccine dl {
            gap: 10px 14px;
        }

        .neonatal-shell .neo-table tbody tr:hover {
            background: #fff7fb;
        }

        .neonatal-shell .neo-table th:last-child,
        .neonatal-shell .neo-table td:last-child {
            min-width: 132px;
        }

        .neonatal-shell [hidden] {
            display: none !important;
        }

        .neonatal-shell .neo-search input {
            min-width: 0;
            font-size: 14px;
            font-weight: 750;
        }

        .neonatal-shell .neo-search input::placeholder {
            color: #64748b;
            opacity: 1;
        }


        @media (max-width: 1180px) {
            .neonatal-shell .neo-heading {
                grid-template-columns: 1fr;
                align-items: start;
            }

            .neonatal-shell .neo-stats {
                max-width: none;
            }
        }

        @media (max-width: 780px) {
            .neonatal-shell .neo-stats {
                grid-template-columns: repeat(auto-fit, minmax(min(100%, 190px), 1fr));
            }

            .neonatal-shell .neo-stat {
                min-height: 108px;
            }
        }

        @media (max-width: 520px) {
            .neonatal-shell .neo-stat {
                min-height: auto;
            }

            .neonatal-shell .neo-stat strong {
                font-size: 26px;
            }
        }

        .neonatal-shell .neo-profile-head {
            grid-template-columns: minmax(290px, 0.85fr) minmax(0, 1.65fr);
            gap: 16px;
            align-items: stretch;
        }

        .neonatal-shell .neo-profile-head .neo-title {
            grid-column: auto;
            align-items: flex-start;
            padding: 6px 2px;
        }

        .neonatal-shell .neo-core-metrics {
            display: grid;
            min-width: 0;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .neonatal-shell .neo-core-metrics .neo-metric {
            min-height: 106px;
        }

        .neonatal-shell .neo-extra {
            grid-template-columns: repeat(5, minmax(0, 1fr));
            margin-top: 16px;
        }

        .neonatal-shell .neo-extra article {
            min-height: 92px;
        }

        .neonatal-shell .neo-last {
            margin-top: 16px;
        }

        @media (max-width: 1160px) {
            .neonatal-shell .neo-profile-head {
                grid-template-columns: 1fr;
            }

            .neonatal-shell .neo-profile-head .neo-title,
            .neonatal-shell .neo-core-metrics {
                grid-column: 1 / -1;
            }

            .neonatal-shell .neo-core-metrics {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        @media (max-width: 920px) {
            .neonatal-shell .neo-core-metrics,
            .neonatal-shell .neo-extra {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 560px) {
            .neonatal-shell .neo-core-metrics,
            .neonatal-shell .neo-extra {
                grid-template-columns: 1fr;
            }

            .neonatal-shell .neo-profile-head .neo-title {
                padding: 0;
            }
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/child-profile.css') }}?v={{ filemtime(public_path('css/child-profile.css')) }}">
    <script src="{{ asset('js/child-profile.js') }}?v={{ filemtime(public_path('js/child-profile.js')) }}" defer></script>

    <section class="neonatal-shell" aria-label="Staff Neonatal and Vaccine Workspace">
        <header class="neo-heading">
            <div>
                <p class="neo-kicker">Program Staff / Neonatal Care</p>
                <h1>Neonatal & Vaccine Monitoring</h1>
                <p>Mother-linked child profiles, growth history, vaccine status, and follow-up alerts.</p>
            </div>
            <div class="neo-stats" aria-label="Neonatal monitoring summary">
                @foreach($neonatalSummaryCards as $summary)
                    <article class="neo-stat {{ $summary['tone'] }}">
                        <span class="neo-stat-icon" aria-hidden="true">{!! $summary['icon'] !!}</span>
                        <span class="neo-stat-label">{{ $summary['label'] }}</span>
                        <strong>{{ number_format($summary['value']) }}</strong>
                        <small>{{ $summary['description'] }}</small>
                    </article>
                @endforeach
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
                    <div><h2>Mother's Child</h2><p class="neo-muted">{{ $mothers->count() }} assigned mother{{ $mothers->count() === 1 ? '' : 's' }}</p></div>
                </div>
                <div class="neo-search-wrap" data-neo-search-wrap>
                    <label class="neo-search">{!! $iconSearch !!}<input type="search" placeholder="Search mother or child" aria-label="Search mother and child information" aria-controls="neo-search-results" aria-expanded="false" autocomplete="off" data-neo-search></label>
                    <div class="neo-search-results" id="neo-search-results" aria-label="Matching mothers" hidden data-neo-search-results></div>
                    <span class="neo-search-status" role="status" data-neo-search-status></span>
                </div>
                <div class="neo-list" data-neo-list>
                    @forelse($mothers as $mother)
                        @php
                            $motherChildren = $children->where('mother_id', $mother->id)->sortBy(fn ($child) => (\App\Support\ChildProfileDisplay::date($child->getRawOriginal('birth_date'))?->format('Ymd') ?? '99999999').$child->full_name)->values();
                            $motherOverdue = $motherChildren->flatMap->vaccineRecords->filter(function ($record) use ($today) {
                                return in_array($record->status, ['missed', 'overdue'], true) || ($record->status !== 'completed' && $record->due_date && $record->due_date->isBefore($today));
                            })->count();
                            $childSummary = $motherChildren->isEmpty()
                                ? 'No child profile yet'
                                : $motherChildren->pluck('full_name')->take(2)->implode(', ').($motherChildren->count() > 2 ? ' +' . ($motherChildren->count() - 2) : '');
                            $childSearchText = $motherChildren->map(function ($child) use ($formatNumber) {
                                $childAge = \App\Support\ChildProfileDisplay::age($child->getRawOriginal('birth_date'));
                                $latestChildGrowth = $child->growthRecords->last();
                                return collect([
                                    $child->full_name,
                                    $child->sex,
                                    \App\Support\ChildProfileDisplay::date($child->getRawOriginal('birth_date'))?->format('M j, Y'),
                                    \App\Support\ChildProfileDisplay::date($child->getRawOriginal('birth_date'))?->format('Y-m-d'),
                                    $childAge,
                                    $child->blood_type,
                                    $child->facility,
                                    $latestChildGrowth?->measured_at?->format('M j, Y'),
                                    $latestChildGrowth?->weight !== null ? $formatNumber($latestChildGrowth->weight, ' kg') : null,
                                    $latestChildGrowth?->height !== null ? $formatNumber($latestChildGrowth->height, ' cm') : null,
                                    $child->vaccineRecords->pluck('vaccine_name')->implode(' '),
                                    $child->vaccineRecords->pluck('dose_label')->implode(' '),
                                    $child->vaccineRecords->pluck('status')->implode(' '),
                                ])->filter()->implode(' ');
                            })->implode(' ');
                            $childCountLabel = $motherChildren->count().' child'.($motherChildren->count() === 1 ? '' : 'ren');
                            $searchText = collect([
                                $mother->full_name,
                                $mother->barangay,
                                $childCountLabel,
                                $childSummary,
                                $motherChildren->isEmpty() ? 'No child profile yet' : null,
                                $motherOverdue > 0 ? $motherOverdue.' overdue follow-up vaccine alert' : 'no overdue vaccine',
                                $childSearchText,
                            ])->filter()->implode(' ');
                        @endphp
                        <a class="neo-child-link @if($selectedMother?->id === $mother->id) is-active @endif" href="{{ route('staff.neonatal', ['mother' => $mother->id]) }}" data-search-text="{{ $searchText }}">
                            <span class="neo-avatar">{{ $initials($mother->full_name) }}</span>
                            <span><span class="neo-link-name">{{ $mother->full_name }}</span><span class="neo-link-meta">{{ $motherChildren->count() }} child{{ $motherChildren->count() === 1 ? '' : 'ren' }} / {{ $childSummary }}</span></span>
                            <span class="neo-badge {{ $motherOverdue > 0 ? 'is-danger' : ($motherChildren->isEmpty() ? 'is-blue' : 'is-good') }}">{{ $motherChildren->count() }}</span>
                        </a>
                    @empty
                        <div class="neo-empty">No assigned mothers yet.</div>
                    @endforelse
                </div>
            </aside>

            <div class="neo-main">
                @if($childAccessDenied)
                    <section class="neo-card neo-section"><div class="neo-empty">{{ $accessDeniedMessage ?? 'The selected profile is not assigned to your casefiles.' }}</div></section>
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
                            @if($selectedMotherInfants->count() > 1)
                                <label class="neo-child-switch neo-profile-switch">Mother's Child
                                    <select onchange="if(this.value){window.location=this.value}">
                                        @foreach($selectedMotherInfants as $motherChild)
                                            @php $motherChildAge = \App\Support\ChildProfileDisplay::age($motherChild->getRawOriginal('birth_date')); @endphp
                                            <option value="{{ route('staff.neonatal', ['mother' => $selectedMother?->id, 'child' => $motherChild->id]) }}" @selected($selectedInfant->id === $motherChild->id)>{{ $motherChild->full_name }} ({{ $motherChildAge }})</option>
                                        @endforeach
                                    </select>
                                </label>
                            @endif
                            <div class="neo-core-metrics">
                                <article class="neo-metric"><span>{!! $iconBaby !!} Age</span><strong>{{ \App\Support\ChildProfileDisplay::age($selectedInfant->getRawOriginal('birth_date')) }}</strong></article>
                                <article class="neo-metric"><span>{!! $iconCalendar !!} Birth Date</span><strong>{{ \App\Support\ChildProfileDisplay::date($selectedInfant->getRawOriginal('birth_date'))?->format('M j, Y') ?? 'N/A' }}</strong></article>
                                <article class="neo-metric"><span>{!! $iconScale !!} Weight</span><strong>{{ $formatNumber($latestGrowth?->weight, ' kg') }}</strong></article>
                                <article class="neo-metric"><span>{!! $iconRuler !!} Height</span><strong>{{ $formatNumber($latestGrowth?->height, ' cm') }}</strong></article>
                            </div>
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
                            <div class="neo-actions">
                                <button class="neo-button is-light" type="button" data-neo-open="edit-child">{!! $iconEdit !!} Edit Child</button>
                                <button class="neo-button is-green" type="button" data-neo-open="growth">{!! $iconTrend !!} Update Growth</button>
                                <button class="neo-button is-light" type="button" data-casefile-record="print" data-record-kind="child" data-record-url="{{ route('staff.neonatal.print', $selectedInfant) }}">Print Record</button>
                                <button class="neo-button is-light" type="button" data-casefile-record="pdf" data-record-kind="child" data-record-url="{{ route('staff.neonatal.pdf', $selectedInfant) }}">Export PDF</button>
                            </div>
                        </div>
                        <p data-record-error role="alert" hidden></p>
                        <p data-record-ready role="status" hidden><span data-record-status></span> <a data-record-open target="_blank" rel="noopener">Open generated record</a></p>
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
                                <div class="neo-chart-head"><div><h3>{!! $iconTrend !!} {{ $title }}</h3><p>Latest: {{ $formatNumber($chart['latest'], ' '.$unit) }}</p></div><small>Unit: {{ strtoupper($unit) }}</small></div>
                                <div class="neo-plot">
                                    @if($chart['has'])
                                        @include('partials.child-growth-chart')
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
                            <div class="neo-table-wrap">
                                <table class="neo-table">
                                    <thead>
                                        <tr>
                                            <th>Age</th>
                                            <th>Weight</th>
                                            <th>Height</th>
                                            <th>Head / Temp</th>
                                            <th>Date</th>
                                            <th>Recorded By</th>
                                            <th class="neo-table-action-head">Edit Growth</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($selectedGrowth->sortByDesc('measured_at') as $record)
                                            <tr>
                                                <td>{{ $record->age_months }} mo</td>
                                                <td>{{ $formatNumber($record->weight, ' kg') }}</td>
                                                <td>{{ $formatNumber($record->height, ' cm') }}</td>
                                                <td>{{ $formatNumber($record->head_circumference, ' cm') }} / {{ $formatNumber($record->temperature, ' C') }}</td>
                                                <td>{{ $record->measured_at?->format('M j, Y') }}</td>
                                                <td>{{ $record->recorder?->full_name ?? $staff->full_name }}</td>
                                                <td class="neo-table-action-cell">
                                                    <button
                                                        class="neo-table-action"
                                                        type="button"
                                                        data-neo-growth
                                                        data-action="{{ route('staff.neonatal.growth.update', $record) }}"
                                                        data-date="{{ $record->measured_at?->toDateString() }}"
                                                        data-age="{{ $record->age_months }}"
                                                        data-weight="{{ $record->weight }}"
                                                        data-height="{{ $record->height }}"
                                                        data-head="{{ $record->head_circumference }}"
                                                        data-temp="{{ $record->temperature }}"
                                                        data-remarks="{{ $record->remarks }}"
                                                        aria-label="Edit growth record from {{ $record->measured_at?->format('M j, Y') }}"
                                                    >
                                                        {!! $iconEdit !!} Edit
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </section>

                    <section class="neo-card neo-section">
                        <div class="neo-section-head"><div><h2>{!! $iconSyringe !!} Vaccine Surveillance</h2><p class="neo-muted">{{ $selectedInfant->full_name }} schedule and completion status</p></div><div class="neo-vaccine-summary"><span class="neo-status is-complete">{{ $completedVaccines }} completed</span><span class="neo-status is-upcoming">{{ $upcomingVaccines }} upcoming</span><span class="neo-status is-overdue">{{ $overdueVaccines }} overdue</span><button class="neo-button is-light" type="button" data-neo-open="add-vaccine">{!! $iconPlus !!} Add Dose</button></div></div>
                        @if($vaccineRows->isEmpty())
                            <div class="neo-empty">No vaccine records available yet.</div>
                        @else
                            <div class="neo-vaccine-filter">
                                <label for="vaccine-status-filter">Status</label>
                                <select id="vaccine-status-filter" data-vaccine-status-filter>
                                    <option value="">All statuses</option>
                                    @foreach (['Completed', 'Overdue', 'Upcoming', 'Due Today', 'Missed', 'Cancelled'] as $filterStatus)
                                        <option value="{{ $filterStatus }}">{{ $filterStatus }}</option>
                                    @endforeach
                                </select>
                                <span data-vaccine-filter-count role="status"></span>
                            </div>
                            <div class="neo-empty" data-vaccine-filter-empty hidden>No vaccines match this status.</div>
                            <div class="neo-vaccine-grid">
                                @foreach($vaccineRows as $vaccine)
                                    <button type="button" class="neo-vaccine {{ $vaccine->display_class }}" data-due-date="{{ $vaccine->due_date?->toDateString() }}" data-neo-vaccine data-display-status="{{ $vaccine->display_status }}" data-action="{{ route('staff.neonatal.vaccines.update', $vaccine) }}" data-name="{{ $vaccine->vaccine_name }}" data-dose="{{ $vaccine->dose_label }}" data-status="{{ $vaccine->status }}" data-date="{{ $vaccine->administered_at?->toDateString() }}" data-facility="{{ $vaccine->facility }}" data-lot="{{ $vaccine->lot_number }}" data-vaccinator="{{ $vaccine->vaccinator }}" data-remarks="{{ $vaccine->remarks }}">
                                        <span class="neo-vaccine-top"><span><h3>{{ $vaccine->vaccine_name }}</h3><small>{{ $vaccine->dose_label }} / Due {{ $vaccine->due_date?->format('M j, Y') ?? 'Not scheduled' }}</small></span><span class="neo-status {{ $vaccine->display_class }}">{{ $vaccine->display_status }}</span></span>
                                        <dl><div><dt>Vaccination Date</dt><dd>{{ $vaccine->administered_at?->format('M j, Y') ?? 'Not recorded' }}</dd></div><div><dt>Facility</dt><dd>{{ $vaccine->facility ?: 'Not recorded' }}</dd></div><div><dt>Lot Number</dt><dd>{{ $vaccine->lot_number ?: 'N/A' }}</dd></div><div><dt>Vaccinator</dt><dd>{{ $vaccine->vaccinator ?: ($vaccine->recorder?->full_name ?? $staff->full_name) }}</dd></div></dl>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </section>
                @else
                    <section class="neo-card neo-section">
                        <div class="neo-empty">
                            @if($selectedMother)
                                No child profile is linked to {{ $selectedMother->full_name }} yet. Once a child profile is registered, neonatal and vaccine monitoring will appear here.
                            @else
                                No assigned mother record yet. Add mothers to your casefiles before creating child profiles.
                            @endif
                        </div>
                    </section>
                @endif
            </div>
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
                        <label>Birth Date<input type="date" name="birth_date" required max="{{ now()->toDateString() }}" value="{{ old('birth_date', \App\Support\ChildProfileDisplay::date($selectedInfant->getRawOriginal('birth_date'))?->toDateString()) }}"></label>
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
                <form class="neo-dialog" method="POST" action="{{ route('staff.neonatal.growth.store', $selectedInfant) }}" data-growth-form data-store-action="{{ route('staff.neonatal.growth.store', $selectedInfant) }}">
                    @csrf
                    <input type="hidden" name="_method" value="PATCH" data-growth-method disabled>
                    <header><h2 data-growth-title>Update Growth</h2><button type="button" class="neo-close" data-neo-close aria-label="Close growth modal">{!! $iconClose !!}</button></header>
                    <div class="neo-form">
                        <label>Measurement Date<input type="date" name="measured_at" required max="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" data-growth-date></label>
                        <label>Age (months)<input type="number" min="0" max="60" name="age_months" required value="{{ $ageMonths }}" data-growth-age></label>
                        <label>Weight (kg)<input type="number" step="0.01" min="0.5" max="50" name="weight" required data-growth-weight></label>
                        <label>Height (cm)<input type="number" step="0.01" min="20" max="130" name="height" required data-growth-height></label>
                        <label>Head Circumference (cm)<input type="number" step="0.01" min="20" max="70" name="head_circumference" data-growth-head></label>
                        <label>Temperature (C)<input type="number" step="0.1" min="34" max="43" name="temperature" data-growth-temp></label>
                        <label class="is-wide">Remarks<textarea name="remarks" data-growth-remarks></textarea></label>
                    </div>
                    <footer><button class="neo-button is-light" type="button" data-neo-close>Cancel</button><button class="neo-button" type="submit" data-growth-submit>Save Growth</button></footer>
                </form>
            </div>

            <div class="neo-modal" data-neo-modal="vaccine" hidden>
                <div class="neo-backdrop" data-neo-close></div>
                <form class="neo-dialog" method="POST" data-vaccine-form>
                    @csrf
                    <header><h2 data-vaccine-title>Update Vaccine</h2><button type="button" class="neo-close" data-neo-close aria-label="Close vaccine modal">{!! $iconClose !!}</button></header>
                    <div class="neo-form">
                        <label>Status<select name="status" data-vaccine-status><option value="upcoming">Scheduled</option><option value="completed">Given</option><option value="cancelled">Cancelled</option></select></label>
                        @include('partials.vaccine-date-field', ['dateName' => 'due_date', 'dateLabel' => 'Scheduled Date'])
                        @include('partials.vaccine-date-field', ['dateName' => 'administered_at', 'dateLabel' => 'Date Given'])
                        <details class="neo-vaccine-optional"><summary>More details (optional)</summary><div class="neo-vaccine-optional-fields">
                        <label>Facility<input name="facility" data-vaccine-facility></label>
                        <label>Lot Number<input name="lot_number" data-vaccine-lot></label>
                        <label>Vaccinator<input name="vaccinator" data-vaccine-vaccinator></label>
                        <label class="is-wide">Remarks<textarea name="remarks" data-vaccine-remarks></textarea></label>
                        </div></details>
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
                        @include('partials.vaccine-date-field', ['dateName' => 'due_date', 'dateLabel' => 'Scheduled Date'])
                        <label>Status<select name="status" required><option value="upcoming">Scheduled</option><option value="completed">Given</option><option value="cancelled">Cancelled</option></select></label>
                        @include('partials.vaccine-date-field', ['dateName' => 'administered_at', 'dateLabel' => 'Date Given'])
                        <details class="neo-vaccine-optional"><summary>More details (optional)</summary><div class="neo-vaccine-optional-fields">
                        <label>Facility<input name="facility"></label>
                        <label>Lot Number<input name="lot_number"></label>
                        <label>Vaccinator<input name="vaccinator"></label>
                        <label class="is-wide">Remarks<textarea name="remarks"></textarea></label>
                        </div></details>
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
            const growthForm = document.querySelector('[data-growth-form]');
            const growthMethod = growthForm?.querySelector('[data-growth-method]');
            const growthTitle = document.querySelector('[data-growth-title]');
            const growthSubmit = document.querySelector('[data-growth-submit]');
            const resetGrowthForm = () => {
                if (!growthForm) return;
                growthForm.action = growthForm.dataset.storeAction || growthForm.action;
                growthForm.reset();
                if (growthMethod) growthMethod.disabled = true;
                if (growthTitle) growthTitle.textContent = 'Update Growth';
                if (growthSubmit) growthSubmit.textContent = 'Save Growth';
                growthForm.querySelector('[data-growth-date]').value = '{{ now()->toDateString() }}';
                growthForm.querySelector('[data-growth-age]').value = '{{ $ageMonths }}';
            };
            document.querySelectorAll('[data-neo-open]').forEach((button) => button.addEventListener('click', () => {
                if (button.dataset.neoOpen === 'growth') resetGrowthForm();
                open(button.dataset.neoOpen);
            }));
            document.querySelectorAll('[data-neo-close]').forEach((el) => el.addEventListener('click', close));
            document.addEventListener('keydown', (event) => { if (event.key === 'Escape') close(); });

            const search = document.querySelector('[data-neo-search]');
            const rows = Array.from(document.querySelectorAll('[data-neo-list] [data-search-text]'));
            const normalizeSearch = (value) => (value || '')
                .toString()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .replace(/\s+/g, ' ')
                .trim();
            const indexedRows = rows.map((row) => ({
                row,
                text: normalizeSearch(`${row.dataset.searchText || ''} ${row.textContent || ''}`),
            }));
            const searchWrap = document.querySelector('[data-neo-search-wrap]');
            const results = document.querySelector('[data-neo-search-results]');
            const searchStatus = document.querySelector('[data-neo-search-status]');
            const dismissSearch = () => {
                results.hidden = true;
                search.setAttribute('aria-expanded', 'false');
            };
            const showSearch = () => {
                const terms = normalizeSearch(search.value).split(' ').filter(Boolean);
                results.replaceChildren();
                if (!terms.length) {
                    dismissSearch();
                    searchStatus.textContent = '';
                    return;
                }
                const matches = indexedRows.filter(({ text }) => terms.every(term => text.includes(term)));
                matches.forEach(({ row }) => results.append(row.cloneNode(true)));
                searchStatus.textContent = `${matches.length} matching mother${matches.length === 1 ? '' : 's'}`;
                if (!matches.length) {
                    const empty = document.createElement('p');
                    empty.className = 'neo-search-empty';
                    empty.textContent = 'No matching mother or child. Try another name.';
                    results.append(empty);
                }
                results.hidden = false;
                search.setAttribute('aria-expanded', 'true');
            };
            search?.addEventListener('input', showSearch);
            search?.addEventListener('focus', showSearch);
            searchWrap?.addEventListener('keydown', event => {
                if (event.key === 'Escape') {
                    search.focus();
                    dismissSearch();
                }
                if (!['ArrowDown', 'ArrowUp'].includes(event.key)) return;
                if (results.hidden) showSearch();
                const links = Array.from(results.querySelectorAll('a'));
                if (!links.length) return;
                event.preventDefault();
                const index = links.indexOf(document.activeElement);
                const next = index < 0 ? (event.key === 'ArrowDown' ? 0 : links.length - 1)
                    : (index + (event.key === 'ArrowDown' ? 1 : -1) + links.length) % links.length;
                links[next].focus();
            });
            searchWrap?.addEventListener('focusout', event => {
                if (!searchWrap.contains(event.relatedTarget)) dismissSearch();
            });
            document.addEventListener('pointerdown', event => {
                if (searchWrap && !searchWrap.contains(event.target)) dismissSearch();
            });

            const vaccineFilter = document.querySelector('[data-vaccine-status-filter]');
            const vaccineCards = Array.from(document.querySelectorAll('[data-neo-vaccine]'));
            const filterVaccines = () => {
                let shown = 0;
                vaccineCards.forEach(card => {
                    card.hidden = Boolean(vaccineFilter.value && card.dataset.displayStatus !== vaccineFilter.value);
                    if (!card.hidden) shown++;
                });
                document.querySelector('[data-vaccine-filter-count]').textContent = `${shown} of ${vaccineCards.length} doses`;
                document.querySelector('[data-vaccine-filter-empty]').hidden = shown !== 0;
            };
            if (vaccineFilter) {
                vaccineFilter.addEventListener('change', filterVaccines);
                filterVaccines();
            }
            const syncDateField = (field) => {
                const stored = field.querySelector('input[type="hidden"]');
                const [year = '', month = '', day = ''] = stored.value.split('-');
                field.querySelector('[data-date-year]').value = year;
                field.querySelector('[data-date-month]').value = month ? Number(month) : '';
                field.querySelector('[data-date-day]').value = day ? Number(day) : '';
                field.querySelectorAll('select, input[type="number"]').forEach(control => {
                    control.disabled = stored.disabled;
                    control.required = stored.required;
                    control.setCustomValidity('');
                });
            };
            document.querySelectorAll('[data-vaccine-date-field]').forEach(field => {
                const stored = field.querySelector('input[type="hidden"]');
                const year = field.querySelector('[data-date-year]');
                const month = field.querySelector('[data-date-month]');
                const day = field.querySelector('[data-date-day]');
                const saveDate = () => {
                    year.setCustomValidity('');
                    stored.value = '';
                    if (!year.value || !month.value || !day.value) return;
                    const value = `${year.value.padStart(4, '0')}-${month.value.padStart(2, '0')}-${day.value.padStart(2, '0')}`;
                    const parsed = new Date(`${value}T12:00:00`);
                    if (Number.isNaN(parsed.getTime()) || parsed.getFullYear() !== Number(year.value) || parsed.getMonth() + 1 !== Number(month.value) || parsed.getDate() !== Number(day.value)) {
                        year.setCustomValidity('Please choose a valid date.');
                    } else if (stored.dataset.latestDate && value > stored.dataset.latestDate) {
                        year.setCustomValidity('Date given must be today or earlier.');
                    } else stored.value = value;
                };
                [year, month, day].forEach(control => control.addEventListener('input', saveDate));
                field.querySelector('[data-date-today]').addEventListener('click', () => {
                    stored.value = '{{ now()->toDateString() }}';
                    syncDateField(field);
                });
            });
            const updateVaccineFields = (form) => {
                const status = form.querySelector('[name="status"]').value;
                const completed = status === 'completed';
                const given = form.querySelector('[name="administered_at"]');
                given.closest('[data-vaccine-date-field]').hidden = !completed;
                given.disabled = !completed;
                given.required = completed;
                const scheduled = form.querySelector('[name="due_date"]');
                scheduled.closest('[data-vaccine-date-field]').hidden = status !== 'upcoming';
                scheduled.disabled = status !== 'upcoming';
                scheduled.required = status === 'upcoming';
                form.querySelectorAll('[data-vaccine-date-field]').forEach(syncDateField);
            };
            document.querySelectorAll('[data-vaccine-date]').forEach(label => {
                const form = label.closest('form');
                form.querySelector('[name="status"]').addEventListener('change', () => updateVaccineFields(form));
                updateVaccineFields(form);
            });
            const vaccineForm = document.querySelector('[data-vaccine-form]');
            document.querySelectorAll('[data-neo-growth]').forEach((button) => {
                button.addEventListener('click', () => {
                    if (!growthForm) return;
                    growthForm.action = button.dataset.action || '';
                    if (growthMethod) growthMethod.disabled = false;
                    if (growthTitle) growthTitle.textContent = 'Edit Growth Record';
                    if (growthSubmit) growthSubmit.textContent = 'Save Changes';
                    growthForm.querySelector('[data-growth-date]').value = button.dataset.date || '';
                    growthForm.querySelector('[data-growth-age]').value = button.dataset.age || '';
                    growthForm.querySelector('[data-growth-weight]').value = button.dataset.weight || '';
                    growthForm.querySelector('[data-growth-height]').value = button.dataset.height || '';
                    growthForm.querySelector('[data-growth-head]').value = button.dataset.head || '';
                    growthForm.querySelector('[data-growth-temp]').value = button.dataset.temp || '';
                    growthForm.querySelector('[data-growth-remarks]').value = button.dataset.remarks || '';
                    open('growth');
                });
            });

            document.querySelectorAll('[data-neo-vaccine]').forEach((button) => {
                button.addEventListener('click', () => {
                    if (!vaccineForm) return;
                    vaccineForm.action = button.dataset.action || '';
                    document.querySelector('[data-vaccine-title]').textContent = `${button.dataset.name || 'Vaccine'} - ${button.dataset.dose || ''}`;
                    vaccineForm.querySelector('[data-vaccine-status]').value = ['completed', 'cancelled'].includes(button.dataset.status) ? button.dataset.status : 'upcoming';
                    vaccineForm.querySelector('.neo-vaccine-optional').open = false;
                    vaccineForm.querySelector('[data-vaccine-date]').value = button.dataset.date || '';
                    vaccineForm.querySelector('[data-vaccine-due-date]').value = button.dataset.dueDate || '';
                    updateVaccineFields(vaccineForm);
                    vaccineForm.querySelector('[data-vaccine-facility]').value = button.dataset.facility || '';
                    vaccineForm.querySelector('[data-vaccine-lot]').value = button.dataset.lot || '';
                    vaccineForm.querySelector('[data-vaccine-vaccinator]').value = button.dataset.vaccinator || '';
                    vaccineForm.querySelector('[data-vaccine-remarks]').value = button.dataset.remarks || '';
                    open('vaccine');
                });
            });
        })();
    </script>
    <script src="{{ asset('js/mother-care-record.js') }}?v={{ filemtime(public_path('js/mother-care-record.js')) }}" defer></script>
@endsection
