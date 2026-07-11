@extends('layouts.app')

@section('title', 'Neonatal & Vaccines - Project INAY')
@section('portal_title', 'Neonatal & Vaccines')

@php
    $iconSearch = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>';
    $iconBaby = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 12h.01"/><path d="M15 12h.01"/><path d="M10 16c.5.3 1.2.5 2 .5s1.5-.2 2-.5"/><path d="M19 12a7 7 0 1 1-14 0c0-2.2 1-4.1 2.6-5.4"/></svg>';
    $iconPlus = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg>';
    $iconPhone = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.4 19.4 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.4 2.1L8.1 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.6 1.9Z"/></svg>';
    $iconFile = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>';
    $iconTrend = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 17 6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>';
    $iconAlert = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>';
    $iconSyringe = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m18 2 4 4"/><path d="m17 7 3-3"/><path d="M19 9 8.7 19.3a2.4 2.4 0 0 1-3.4 0l-.6-.6a2.4 2.4 0 0 1 0-3.4L15 5"/><path d="m9 11 4 4"/></svg>';
    $iconClose = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>';
    $initials = fn ($name) => collect(preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [])->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('') ?: 'IN';
    $motherInitials = fn ($mother) => strtoupper(substr($mother->first_name, 0, 1).substr($mother->last_name, 0, 1));
    $formatNumber = fn ($value, $suffix = '') => $value === null ? 'N/A' : rtrim(rtrim(number_format((float) $value, 2), '0'), '.').$suffix;
    $statusLabels = ['pregnant' => 'Pregnant', 'postpartum' => 'Postpartum', 'planning' => 'Planning pregnancy', 'not_pregnant' => 'Not pregnant'];
    $riskLabels = ['low' => 'Low Risk Case', 'medium' => 'Needs Review', 'high' => 'High Risk Case'];
    $riskClass = fn ($risk) => in_array($risk, ['low', 'medium', 'high'], true) ? 'is-'.$risk : 'is-pending';
    $today = now()->startOfDay();
    $growthAssessment = function ($infant) {
        $latest = $infant->growthRecords->last();
        if (! $latest) return ['label' => 'Awaiting Growth Record', 'class' => 'is-warning', 'severity' => 'warning', 'alerts' => ['No growth measurement has been recorded yet.']];
        $age = max(0, (int) $latest->age_months); $weight = (float) $latest->weight; $height = (float) $latest->height;
        $minWeight = max(2.4, 2.6 + ($age * 0.45)); $maxWeight = max(4.6, 5.0 + ($age * 0.75)); $minHeight = max(45, 47 + ($age * 1.4));
        $alerts = []; $severity = 'normal';
        if ($weight < $minWeight || $weight > $maxWeight) { $severity = 'warning'; $alerts[] = $weight < $minWeight ? "{$infant->full_name}'s weight needs review for age." : "{$infant->full_name}'s weight is above the usual range for age."; }
        if ($height < $minHeight) { $severity = 'critical'; $alerts[] = "{$infant->full_name}'s height needs review for age."; }
        if ($severity !== 'normal') $alerts[] = "{$infant->full_name}'s latest growth status needs a priority clinical nutrition review."; else $alerts[] = 'Growth indicators are within the expected review range.';
        return ['label' => match ($severity) { 'critical' => 'Growth Needs Review', 'warning' => 'Needs Follow-up', default => 'On Track' }, 'class' => match ($severity) { 'critical' => 'is-critical', 'warning' => 'is-warning', default => 'is-good' }, 'severity' => $severity, 'alerts' => $alerts];
    };
    $vaccineStatus = function ($record) use ($today) {
        if ($record->status === 'completed') return ['label' => 'Completed', 'class' => 'is-complete'];
        if (in_array($record->status, ['missed', 'deferred'], true)) return ['label' => ucfirst($record->status), 'class' => 'is-overdue'];
        if (! $record->due_date) return ['label' => 'Not Eligible', 'class' => 'is-muted'];
        if ($record->due_date->isBefore($today)) return ['label' => 'Overdue', 'class' => 'is-overdue'];
        if ($record->due_date->lessThanOrEqualTo($today->copy()->addDays(14))) return ['label' => 'Due Soon', 'class' => 'is-due'];
        return ['label' => 'Upcoming', 'class' => 'is-upcoming'];
    };
    $chartData = function ($records, $field) {
        $values = $records
            ->filter(fn ($record) => $record->{$field} !== null)
            ->sortBy(fn ($record) => str_pad((string) ($record->age_months ?? 0), 4, '0', STR_PAD_LEFT).'-'.str_pad((string) ($record->id ?? 0), 10, '0', STR_PAD_LEFT))
            ->values();

        if ($values->isEmpty()) {
            return [
                'has_records' => false,
                'points' => collect(),
                'path' => '',
                'min' => null,
                'mid' => null,
                'max' => null,
                'min_age' => 0,
                'mid_age' => 1,
                'max_age' => 3,
            ];
        }

        $numbers = $values->map(fn ($record) => (float) $record->{$field});
        $ages = $values->map(fn ($record) => (int) ($record->age_months ?? 0));
        $minValue = floor($numbers->min());
        $maxValue = ceil($numbers->max());

        if ($minValue === $maxValue) {
            $minValue -= 1;
            $maxValue += 1;
        }

        $minAge = max(0, $ages->min());
        $maxAge = max($minAge + 1, $ages->max(), 3);
        $midValue = round(($minValue + $maxValue) / 2, 1);
        $midAge = (int) round(($minAge + $maxAge) / 2);

        $points = $values->map(function ($record) use ($field, $minValue, $maxValue, $minAge, $maxAge) {
            $age = (int) ($record->age_months ?? 0);
            $value = (float) $record->{$field};
            $x = 16 + ((($age - $minAge) / max(1, $maxAge - $minAge)) * 76);
            $y = 82 - ((($value - $minValue) / max(1, $maxValue - $minValue)) * 58);

            return [
                'x' => round($x, 2),
                'y' => round($y, 2),
                'age' => $age,
                'value' => $value,
            ];
        })->values();

        return [
            'has_records' => true,
            'points' => $points,
            'path' => $points->map(fn ($point) => $point['x'].','.$point['y'])->implode(' '),
            'min' => $minValue,
            'mid' => $midValue,
            'max' => $maxValue,
            'min_age' => $minAge,
            'mid_age' => $midAge,
            'max_age' => $maxAge,
        ];
    };
@endphp

@section('content')
    <style>
        .neonatal-shell{--neo-pink:#ec008c;--neo-navy:#071127;--neo-muted:#52627d;--neo-line:#dbe5f1;--neo-green:#00856a;--neo-teal:#2dd4bf;color:var(--neo-navy);font-weight:400}.neonatal-shell *{letter-spacing:0}.neonatal-shell svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}.neonatal-heading{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:24px;align-items:end;border-bottom:1px solid #cbd5e1;padding-bottom:26px;margin-bottom:26px}.neonatal-breadcrumb{margin:0 0 48px;color:#8aa0bd;font-size:12px;font-weight:600;text-transform:uppercase}.neonatal-breadcrumb strong{color:var(--neo-pink);font-weight:600;margin:0 10px}.neonatal-heading h1{margin:0;font-size:32px;line-height:1.08;font-weight:700;color:#020817}.neonatal-heading p{margin:10px 0 0;color:var(--neo-muted);font-size:16px;font-weight:400}.neonatal-stats{display:grid;grid-template-columns:repeat(3,132px);gap:14px}.neonatal-stat{min-height:80px;padding:16px 18px;border:1px solid var(--neo-line);border-radius:8px;background:#fff;box-shadow:0 2px 7px rgba(15,23,42,.11)}.neonatal-stat span{display:block;color:#8aa0bd;text-transform:uppercase;font-size:12px;line-height:1.25;font-weight:600}.neonatal-stat strong{display:block;margin-top:12px;font-size:26px;line-height:1;font-weight:600}.neonatal-stat.is-good{background:#ecfdf5;border-color:#86efc2;color:#007f5f}.neonatal-stat.is-alert{background:#fff1f2;border-color:#fecdd3;color:#be123c}.neonatal-alert{display:flex;align-items:center;justify-content:space-between;gap:18px;margin:-8px 0 26px;padding:16px 18px;border:1px solid #86efc2;border-radius:8px;background:#ecfdf5;color:#007f5f;font-weight:600}.neonatal-alert button{width:34px;height:34px;border:0;background:transparent;color:#007f5f;cursor:pointer}.neonatal-workspace{display:grid;grid-template-columns:minmax(320px,400px) minmax(0,1fr);gap:28px;align-items:start}.neonatal-card{background:#fff;border:1px solid var(--neo-line);border-radius:8px;box-shadow:0 2px 7px rgba(15,23,42,.09)}.neonatal-mothers{position:sticky;top:104px;padding:22px}.sponsor-head{display:flex;align-items:start;justify-content:space-between;gap:12px}.neonatal-section-label{margin:0 0 6px;color:var(--neo-pink);text-transform:uppercase;font-size:12px;font-weight:600}.neonatal-muted{color:var(--neo-muted);font-weight:400}.neonatal-search{margin:22px 0 14px;height:52px;display:flex;align-items:center;gap:12px;padding:0 16px;border:1px solid #cbd8ea;border-radius:8px;color:#8da0bd;background:#fff}.neonatal-search:focus-within{border-color:#ff4bac;box-shadow:0 0 0 3px rgba(236,0,140,.12)}.neonatal-search input{width:100%;border:0;outline:0;font-weight:400;color:#1f2a44;font-size:15px}.neonatal-mother-list{display:grid;gap:10px;max-height:430px;overflow:auto;padding-right:4px}.neonatal-mother-link{position:relative;display:grid;grid-template-columns:48px minmax(0,1fr) auto;gap:14px;align-items:start;padding:14px 16px;text-decoration:none;color:inherit;border:1px solid var(--neo-line);border-radius:9px;background:#fff;box-shadow:0 1px 3px rgba(15,23,42,.04);transition:border-color .18s ease,background .18s ease,transform .18s ease,box-shadow .18s ease}.neonatal-mother-link:hover,.neonatal-mother-link.is-active{border-color:#ff8ccc;background:#fff3fa;box-shadow:0 5px 14px rgba(236,0,140,.08);transform:translateY(-1px);text-decoration:none}.neonatal-avatar{display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:9px;background:#fff0f8;color:var(--neo-pink);font-weight:600}.neonatal-avatar.is-child{background:#fff8e8;color:#d97706}.sponsor-card-name{display:block;font-size:15px;font-weight:600;color:#071127;line-height:1.25;overflow-wrap:anywhere}.sponsor-case{display:block;margin-top:4px;color:#8aa0bd;font-weight:600}.sponsor-meta{display:block;margin-top:6px;color:var(--neo-muted);font-weight:400}.sponsor-status{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-top:10px}.sponsor-status strong{color:var(--neo-pink);font-size:13px;font-weight:600}.sponsor-count{padding:4px 8px;border-radius:999px;background:#f1f5f9;color:#64748b;font-size:12px;font-weight:500}.neonatal-main{display:grid;gap:22px}.mother-summary,.infant-section{padding:24px}.mother-summary-top{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:18px;align-items:start}.mother-title{display:flex;align-items:center;gap:16px;min-width:0}.mother-title h2{margin:6px 0 0;font-size:24px;line-height:1.15;font-weight:600;color:#020817}.mother-title p{margin:0;color:var(--neo-pink);text-transform:uppercase;font-size:12px;font-weight:600}.neonatal-badge{display:inline-flex;align-items:center;justify-content:center;border-radius:999px;padding:6px 10px;font-size:11px;line-height:1.1;font-weight:600;text-transform:uppercase}.neonatal-badge.is-low,.neonatal-badge.is-good,.neonatal-badge.is-complete{background:#dcfce7;color:#007f5f;border:1px solid #86efc2}.neonatal-badge.is-medium,.neonatal-badge.is-warning,.neonatal-badge.is-due{background:#fff7ed;color:#c2410c;border:1px solid #fed7aa}.neonatal-badge.is-high,.neonatal-badge.is-critical,.neonatal-badge.is-overdue,.neonatal-badge.is-missed{background:#fff1f2;color:#e11d48;border:1px solid #fecdd3}.neonatal-badge.is-upcoming{background:#eff6ff;color:#075ef2;border:1px solid #bfdbfe}.neonatal-badge.is-pending,.neonatal-badge.is-muted{background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0}.mother-actions{display:flex;gap:10px;flex-wrap:wrap;justify-content:end}.neonatal-action{display:inline-flex;align-items:center;justify-content:center;gap:9px;min-height:46px;border-radius:8px;border:1px solid #f9a8d4;background:#fff;color:var(--neo-pink);padding:0 16px;text-decoration:none;font-weight:600;cursor:pointer;transition:background .18s ease,transform .18s ease,box-shadow .18s ease}.neonatal-action:hover{background:#fdf2f8;transform:translateY(-1px);text-decoration:none}.neonatal-action.is-primary{background:var(--neo-pink);color:#fff;border-color:var(--neo-pink);box-shadow:0 8px 18px rgba(236,0,140,.2)}.neonatal-action.is-green{color:#007f5f;border-color:#5eead4;background:#f0fdfa}.mother-facts,.child-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-top:24px}.mother-facts article,.child-metrics article{padding:16px;border:1px solid #cbd8ea;border-radius:7px;background:#f8fbff}.mother-facts span,.child-metrics span{display:block;color:#8aa0bd;text-transform:uppercase;font-size:12px;font-weight:600}.mother-facts strong,.child-metrics strong{display:block;margin-top:8px;font-size:17px;font-weight:500;color:#071127}.infant-section{border-left:2px solid var(--neo-teal)}.infant-section-header{display:flex;align-items:start;justify-content:space-between;gap:18px;border-bottom:1px solid #e2e8f0;padding-bottom:18px;margin-bottom:22px}.infant-section-header h2{display:flex;align-items:center;gap:10px;margin:0;color:#007f7a;font-size:18px;font-weight:600}.infant-list{display:grid;gap:18px}.infant-card{padding:20px;border:1px solid var(--neo-line);border-radius:8px;background:#fff}.infant-card summary{list-style:none;cursor:pointer;display:grid;grid-template-columns:auto minmax(0,1fr) auto;gap:16px;align-items:start}.infant-card summary::-webkit-details-marker{display:none}.infant-name{display:flex;gap:10px;align-items:center;flex-wrap:wrap}.infant-name strong{font-size:22px;font-weight:600;color:#020817}.infant-subtitle{margin-top:6px;color:#8aa0bd;text-transform:uppercase;font-size:13px;font-weight:600}.surveillance-strip{display:flex;justify-content:space-between;gap:16px;align-items:center;background:#ecfdf5;border:1px solid #5eead4;border-radius:8px;padding:16px;margin:18px 0 20px}.surveillance-strip span{display:inline-flex;align-items:center;gap:10px;padding:10px 16px;border-radius:7px;background:#fff;box-shadow:0 2px 8px rgba(15,23,42,.08);color:#0f766e;font-weight:500}.alert-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-bottom:20px}.growth-alert{display:flex;gap:12px;align-items:start;min-height:66px;padding:12px 14px;border:1px solid #fecdd3;border-left:3px solid #f43f5e;border-radius:7px;background:#fff1f2;color:#be123c}.growth-alert strong{display:block;font-weight:600}.growth-alert p{margin:5px 0 0;color:#be123c;font-weight:400;line-height:1.45}.growth-charts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.growth-chart{border:1px solid var(--neo-line);border-radius:8px;padding:18px;background:#fff;box-shadow:0 2px 7px rgba(15,23,42,.07)}.growth-chart-head{display:flex;justify-content:space-between;align-items:start;gap:14px;margin-bottom:14px}.growth-chart-title h3{margin:0;display:flex;align-items:center;gap:8px;font-size:16px;font-weight:600}.growth-chart-title p{margin:8px 0 0;color:var(--neo-muted);font-size:13px;font-weight:400}.growth-chart small{color:#8aa0bd;text-transform:uppercase;font-size:12px;font-weight:600}.growth-plot{height:300px;border:1px solid #e6edf6;border-radius:8px;background:#f8fafc;padding:18px 20px 22px}.growth-plot svg{display:block;width:100%;height:100%;stroke-width:1}.growth-plot .grid{stroke:#dbeafe;stroke-dasharray:5 8}.growth-plot .axis{stroke:#b8c7dc}.growth-plot .label{fill:#8090ad;font-size:3.25px;font-weight:400!important;stroke:none!important;text-shadow:none!important}.growth-plot .axis-title{fill:#94a3b8;font-size:3px;font-weight:400!important;text-transform:uppercase;stroke:none!important;text-shadow:none!important}.growth-plot .line{fill:none;stroke:var(--neo-pink);stroke-width:1.25}.growth-plot .line.is-purple{stroke:#7c3aed}.growth-plot .dot{fill:#fff;stroke:#2563eb;stroke-width:1.8}.growth-plot .dot.is-purple{stroke:#7c3aed}.growth-empty{display:grid;place-items:center;height:100%;color:#64748b;font-size:13px}.growth-history,.vaccine-panel{margin-top:20px;border:1px solid var(--neo-line);border-radius:8px;padding:18px;background:#fff}.section-row{display:flex;justify-content:space-between;gap:14px;align-items:center;margin-bottom:14px}.section-row h3{margin:0;font-size:18px;font-weight:600}.section-row strong{font-weight:600}.record-count{border-radius:999px;background:#f8fafc;color:#8090ad;padding:6px 10px;font-size:12px;font-weight:600;text-transform:uppercase}.history-table{width:100%;border-collapse:collapse;overflow:hidden;border-radius:8px}.history-table th{background:#f8fafc;color:#8aa0bd;text-align:left;font-size:12px;font-weight:500;text-transform:uppercase;padding:12px}.history-table td{padding:13px 12px;border-top:1px solid #eef2f7;color:#24324b;font-weight:400}.history-table td strong{font-weight:500}.vaccine-panel h3{display:flex;align-items:center;gap:8px;margin:0;font-size:19px;font-weight:600}.vaccine-progress{height:8px;border-radius:999px;background:#eef2f7;overflow:hidden;margin:12px 0 18px}.vaccine-progress span{display:block;height:100%;width:var(--progress);background:#0f766e}.vaccine-flat-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.vaccine-card{display:grid;grid-template-columns:auto minmax(0,1fr);gap:12px;align-items:center;min-height:64px;text-align:left;border:1px solid #b9cce5;border-radius:7px;padding:12px 14px;background:#fff;color:#071127;cursor:pointer;transition:border-color .18s ease,box-shadow .18s ease,transform .18s ease}.vaccine-card:hover{border-color:#8bb8ff;box-shadow:0 4px 12px rgba(37,99,235,.12);transform:translateY(-1px)}.vaccine-box{position:relative;width:18px;height:18px;border:1px solid #9fb3d0;border-radius:4px;background:#fff}.vaccine-box.is-checked{border-color:#00856a;background:#dcfce7}.vaccine-box.is-checked:after{content:"";position:absolute;left:5px;top:2px;width:5px;height:9px;border:solid #00856a;border-width:0 2px 2px 0;transform:rotate(45deg)}.vaccine-card strong{display:block;font-size:14px;font-weight:500}.vaccine-card small{display:block;margin-top:4px;color:#52627d;font-size:12px;font-weight:400}.neonatal-empty{padding:28px;text-align:center;border:1px dashed #cbd5e1;border-radius:8px;color:var(--neo-muted);background:#f8fafc}.neonatal-modal[hidden]{display:none}.neonatal-modal{position:fixed;inset:0;z-index:60;display:flex;align-items:center;justify-content:center;padding:20px}.neonatal-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(4px)}.neonatal-dialog{position:relative;width:min(760px,calc(100vw - 24px));max-height:85vh;overflow:auto;background:#fff;border-radius:16px;box-shadow:0 25px 60px rgba(15,23,42,.25)}.neonatal-dialog header,.neonatal-dialog footer{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:20px 24px;border-bottom:1px solid #e2e8f0}.neonatal-dialog footer{border-top:1px solid #e2e8f0;border-bottom:0;justify-content:end}.neonatal-dialog h2{margin:0;font-size:24px;font-weight:600}.neonatal-dialog-close{width:42px;height:42px;border-radius:999px;border:1px solid var(--neo-line);background:#fff;color:var(--neo-muted);display:inline-flex;align-items:center;justify-content:center;cursor:pointer}.neonatal-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;padding:22px 24px}.neonatal-form-grid label{display:grid;gap:7px;color:var(--neo-muted);font-size:13px;font-weight:600;text-transform:uppercase}.neonatal-form-grid input,.neonatal-form-grid select,.neonatal-form-grid textarea{width:100%;border:1px solid #cbd8ea;border-radius:8px;min-height:46px;padding:10px 12px;color:#0f1b33;font-weight:400;outline:none}.neonatal-form-grid textarea{min-height:96px;resize:vertical}.neonatal-form-grid .is-wide{grid-column:1/-1}.neonatal-secondary{border:1px solid #cbd8ea;background:#fff;color:#24324b;border-radius:8px;min-height:44px;padding:0 18px;font-weight:500;cursor:pointer}body.has-neonatal-modal{overflow:hidden}@media(max-width:1200px){.neonatal-workspace{grid-template-columns:1fr}.neonatal-mothers{position:static}.mother-facts,.child-metrics,.vaccine-flat-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:720px){.neonatal-heading,.mother-summary-top,.infant-section-header,.infant-card summary,.growth-charts,.alert-grid{grid-template-columns:1fr}.neonatal-stats,.mother-facts,.child-metrics,.vaccine-flat-grid,.neonatal-form-grid{grid-template-columns:1fr}.neonatal-stats{grid-template-columns:1fr}.mother-actions{justify-content:start}.surveillance-strip{align-items:stretch;flex-direction:column}.vaccine-dates{grid-template-columns:1fr}.growth-plot{height:260px}}
    </style>

    <section class="neonatal-shell" aria-label="Program Staff Neonatal and Vaccine Command">
        <header class="neonatal-heading">
            <div>
                <p class="neonatal-breadcrumb">Neonatal Desk <strong>-</strong> Vaccine Surveillance</p>
                <h1>Neonatal & Vaccine Command</h1>
                <p>Track linked infants, growth signals, and vaccine completion for assigned maternal casefiles.</p>
            </div>
            <div class="neonatal-stats" aria-label="Neonatal summary">
                <article class="neonatal-stat"><span>Linked Infants</span><strong>{{ $neonatalStats['linked_infants'] }}</strong></article>
                <article class="neonatal-stat is-good"><span>Vaccines Done</span><strong>{{ $neonatalStats['vaccines_completed'] }}</strong></article>
                <article class="neonatal-stat is-alert"><span>Follow-ups</span><strong>{{ $neonatalStats['pending_followups'] }}</strong></article>
            </div>
        </header>

        @if (session('success'))
            <div class="neonatal-alert"><span>{{ session('success') }}</span><button type="button" data-neonatal-alert-close aria-label="Dismiss success alert">{!! $iconClose !!}</button></div>
        @endif

        <div class="neonatal-workspace">
            <aside class="neonatal-card neonatal-mothers" aria-label="Maternal sponsors">
                <p class="neonatal-section-label">Maternal Sponsors</p>
                <p class="neonatal-muted">{{ $mothers->count() }} assigned casefile{{ $mothers->count() === 1 ? '' : 's' }}</p>
                <label class="neonatal-search">{!! $iconSearch !!}<input type="search" placeholder="Search mother..." data-neonatal-mother-search></label>
                <div class="neonatal-mother-list" data-neonatal-mother-list>
                    @forelse ($mothers as $motherItem)
                        @php
                            $latestRecord = $motherItem->maternalMonitoringRecords->first();
                            $riskValue = strtolower((string) ($latestRecord?->risk_level ?? ''));
                            $searchText = strtolower($motherItem->full_name.' '.$motherItem->contact_number.' '.$motherItem->email.' '.$motherItem->barangay);
                        @endphp
                        <a class="neonatal-mother-link @if($selectedMother?->id === $motherItem->id) is-active @endif" href="{{ route('staff.neonatal', ['mother' => $motherItem->id]) }}" data-search-text="{{ $searchText }}">
                            <span class="neonatal-avatar">{{ $motherInitials($motherItem) }}</span>
                            <span>
                                <span class="sponsor-card-name">{{ $motherItem->full_name }}</span>
                                <span class="sponsor-case">MAT-RHU-{{ str_pad((string) $motherItem->id, 3, '0', STR_PAD_LEFT) }}</span>
                                <span class="sponsor-meta">{{ $motherItem->age ? $motherItem->age.' y/o' : 'Age N/A' }} - Blood {{ $motherItem->blood_type ?: 'N/A' }}</span>
                                <span class="sponsor-status"><strong>{{ $statusLabels[$motherItem->pregnancy_status] ?? 'Not provided' }}</strong><span class="sponsor-count">{{ $motherItem->infants->count() }} linked child{{ $motherItem->infants->count() === 1 ? '' : 'ren' }}</span></span>
                            </span>
                            <span class="neonatal-badge {{ $riskClass($riskValue ?: 'low') }}">{{ strtoupper($riskValue ?: 'low') }}</span>
                        </a>
                    @empty
                        <div class="neonatal-empty">No mother casefiles yet. Add mothers in Mothers Casefiles first.</div>
                    @endforelse
                </div>
            </aside>

            <div class="neonatal-main">
                @if ($selectedMother)
                    @php
                        $latestRecord = $selectedMother->maternalMonitoringRecords->first();
                        $riskValue = strtolower((string) ($latestRecord?->risk_level ?? ''));
                        $caseId = 'MAT-RHU-'.str_pad((string) $selectedMother->id, 3, '0', STR_PAD_LEFT);
                    @endphp
                    <section class="neonatal-card mother-summary" aria-label="Selected mother summary">
                        <div class="mother-summary-top">
                            <div class="mother-title">
                                <span class="neonatal-avatar">{{ $motherInitials($selectedMother) }}</span>
                                <div><p>Maternal Case Sponsor <span class="neonatal-badge {{ $riskClass($riskValue) }}">{{ $riskLabels[$riskValue] ?? 'Risk Pending' }}</span></p><h2>{{ $selectedMother->full_name }} <span class="neonatal-muted">(Mother ID: {{ $caseId }})</span></h2></div>
                            </div>
                            <div class="mother-actions">
                                @if ($selectedMother->contact_number)<a class="neonatal-action" href="tel:{{ $selectedMother->contact_number }}">{!! $iconPhone !!} Call</a>@endif
                                <a class="neonatal-action" href="{{ route('staff.mothers.show', $selectedMother) }}">{!! $iconFile !!} Open Maternal Case File</a>
                            </div>
                        </div>
                        <div class="mother-facts">
                            <article><span>Sponsor Demographics</span><strong>{{ $selectedMother->age ? $selectedMother->age.' y/o' : 'Age N/A' }} - Blood Type {{ $selectedMother->blood_type ?: 'Unknown' }}</strong></article>
                            <article><span>Delivery / Cohort Status</span><strong>{{ $statusLabels[$selectedMother->pregnancy_status] ?? 'Not provided' }}</strong></article>
                            <article><span>Next Scheduled Visit</span><strong>Not scheduled</strong></article>
                            <article><span>Hotline Center & Location</span><strong>{{ $selectedMother->barangay ? 'III-D ('.$selectedMother->barangay.')' : 'Location pending' }}</strong></article>
                        </div>
                    </section>
                    <section class="neonatal-card infant-section" aria-label="Linked child records">
                        <div class="infant-section-header">
                            <div><h2>{!! $iconBaby !!} Linked Child Case Records ({{ $selectedMother->infants->count() }})</h2><p class="neonatal-muted">Summary: {{ $selectedMother->infants->count() }} infant{{ $selectedMother->infants->count() === 1 ? '' : 's' }} - {{ $selectedMother->infants->flatMap->vaccineRecords->filter(fn ($record) => $record->status !== 'completed' && $record->due_date && $record->due_date->isBefore($today))->count() }} overdue vaccines</p></div>
                            <button class="neonatal-action is-green" type="button" data-neonatal-open="infant">{!! $iconPlus !!} Onboard Infant to {{ $selectedMother->full_name }}</button>
                        </div>
                        @if ($selectedMother->infants->isEmpty())
                            <div class="neonatal-empty">No child profile linked to this mother yet. Use Onboard Infant to start neonatal monitoring.</div>
                        @else
                            <div class="infant-list">
                                @foreach ($selectedMother->infants as $infant)
                                    @php
                                        $latestGrowth = $infant->growthRecords->last();
                                        $assessment = $growthAssessment($infant);
                                        $ageMonths = $latestGrowth?->age_months ?? ($infant->birth_date ? max(0, (int) $infant->birth_date->diffInMonths(now())) : 0);
                                        $vaccines = $infant->vaccineRecords;
                                        $completedVaccines = $vaccines->where('status', 'completed')->count();
                                        $vaccineCompletion = $vaccines->count() ? round(($completedVaccines / $vaccines->count()) * 100) : 0;
                                        $overdueVaccines = $vaccines->filter(fn ($record) => $record->status !== 'completed' && $record->due_date && $record->due_date->isBefore($today))->count();
                                        $weightChart = $chartData($infant->growthRecords, 'weight');
                                        $heightChart = $chartData($infant->growthRecords, 'height');
                                        $vaccineGroups = $vaccines->groupBy('vaccine_group');
                                    @endphp
                                    <details class="infant-card" open>
                                        <summary>
                                            <span class="neonatal-avatar is-child">{{ $initials($infant->full_name) }}</span>
                                            <span><span class="infant-name"><strong>{{ $infant->full_name }}</strong><span class="neonatal-badge is-upcoming">{{ ucfirst($infant->sex) }}</span><span class="neonatal-badge {{ $assessment['class'] }}">{{ $assessment['label'] }}</span></span><span class="infant-subtitle">{{ $ageMonths }} months - registered child - {{ $vaccineCompletion }}% vaccines complete</span></span>
                                            <span class="neonatal-badge {{ $overdueVaccines > 0 || $assessment['severity'] !== 'normal' ? 'is-high' : 'is-good' }}">{{ $overdueVaccines + ($assessment['severity'] !== 'normal' ? count($assessment['alerts']) : 0) }} alerts</span>
                                        </summary>
                                        <div class="child-metrics">
                                            <article><span>Child Age</span><strong>{{ $ageMonths }} months</strong></article><article><span>Current Weight</span><strong>{{ $formatNumber($latestGrowth?->weight, ' kg') }}</strong></article><article><span>Current Height</span><strong>{{ $formatNumber($latestGrowth?->height, ' cm') }}</strong></article><article><span>Last Measurement</span><strong>{{ $latestGrowth?->measured_at?->format('M j, Y') ?? 'Not logged' }}</strong></article>
                                        </div>
                                        <div class="surveillance-strip"><span>{!! $iconBaby !!} Last Surveillance <strong>{{ $latestGrowth?->measured_at?->format('M j, Y') ?? 'Awaiting record' }}</strong></span><button class="neonatal-action is-green" type="button" data-neonatal-open="growth" data-action="{{ route('staff.neonatal.growth.store', $infant) }}" data-name="{{ $infant->full_name }}" data-age="{{ $ageMonths }}">{!! $iconTrend !!} Update Growth</button></div>
                                        @if ($assessment['severity'] !== 'normal' || $overdueVaccines > 0)
                                            <div class="alert-grid">
                                                @foreach ($assessment['alerts'] as $alert)
                                                    @if ($assessment['severity'] !== 'normal')<article class="growth-alert">{!! $iconAlert !!}<div><strong>Growth needs review</strong><p>{{ $alert }}</p></div></article>@endif
                                                @endforeach
                                                @if ($overdueVaccines > 0)<article class="growth-alert">{!! $iconAlert !!}<div><strong>Overdue vaccine follow-up</strong><p>{{ $overdueVaccines }} vaccine{{ $overdueVaccines === 1 ? '' : 's' }} need immediate status review.</p></div></article>@endif
                                            </div>
                                        @endif
                                        <div class="growth-charts">
                                            <article class="growth-chart">
                                                <div class="growth-chart-head"><div class="growth-chart-title"><h3>{!! $iconTrend !!} Weight Progress</h3><p>Latest: {{ $formatNumber($latestGrowth?->weight, ' kg') }}</p></div><small>Unit: KG</small></div>
                                                <div class="growth-plot">
                                                    @if($weightChart['has_records'])
                                                        <svg viewBox="0 0 100 100" role="img" aria-label="Weight progress by age in months">
                                                            <path class="grid" d="M16 24H92M16 53H92M16 82H92"/><path class="axis" d="M16 18V84H92"/>
                                                            <text class="label" x="8" y="25">{{ $weightChart['max'] }}</text><text class="label" x="8" y="54">{{ $weightChart['mid'] }}</text><text class="label" x="8" y="83">{{ $weightChart['min'] }}</text>
                                                            <text class="label" x="16" y="92" text-anchor="middle">{{ $weightChart['min_age'] }}</text><text class="label" x="54" y="92" text-anchor="middle">{{ $weightChart['mid_age'] }}</text><text class="label" x="92" y="92" text-anchor="middle">{{ $weightChart['max_age'] }}</text><text class="axis-title" x="54" y="97" text-anchor="middle">Age (months)</text>
                                                            @if($weightChart['points']->count() > 1)<polyline class="line" points="{{ $weightChart['path'] }}"/>@endif
                                                            @foreach($weightChart['points'] as $point)<circle class="dot" cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="1.7"><title>Age {{ $point['age'] }} mo - {{ $formatNumber($point['value'], ' kg') }}</title></circle>@endforeach
                                                        </svg>
                                                    @else
                                                        <div class="growth-empty">No weight record yet</div>
                                                    @endif
                                                </div>
                                            </article>
                                            <article class="growth-chart">
                                                <div class="growth-chart-head"><div class="growth-chart-title"><h3>{!! $iconTrend !!} Height Progress</h3><p>Latest: {{ $formatNumber($latestGrowth?->height, ' cm') }}</p></div><small>Unit: CM</small></div>
                                                <div class="growth-plot">
                                                    @if($heightChart['has_records'])
                                                        <svg viewBox="0 0 100 100" role="img" aria-label="Height progress by age in months">
                                                            <path class="grid" d="M16 24H92M16 53H92M16 82H92"/><path class="axis" d="M16 18V84H92"/>
                                                            <text class="label" x="8" y="25">{{ $heightChart['max'] }}</text><text class="label" x="8" y="54">{{ $heightChart['mid'] }}</text><text class="label" x="8" y="83">{{ $heightChart['min'] }}</text>
                                                            <text class="label" x="16" y="92" text-anchor="middle">{{ $heightChart['min_age'] }}</text><text class="label" x="54" y="92" text-anchor="middle">{{ $heightChart['mid_age'] }}</text><text class="label" x="92" y="92" text-anchor="middle">{{ $heightChart['max_age'] }}</text><text class="axis-title" x="54" y="97" text-anchor="middle">Age (months)</text>
                                                            @if($heightChart['points']->count() > 1)<polyline class="line is-purple" points="{{ $heightChart['path'] }}"/>@endif
                                                            @foreach($heightChart['points'] as $point)<circle class="dot is-purple" cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="1.7"><title>Age {{ $point['age'] }} mo - {{ $formatNumber($point['value'], ' cm') }}</title></circle>@endforeach
                                                        </svg>
                                                    @else
                                                        <div class="growth-empty">No height record yet</div>
                                                    @endif
                                                </div>
                                            </article>
                                        </div>
                                        <section class="growth-history"><div class="section-row"><h3>Growth History</h3><span class="record-count">{{ $infant->growthRecords->count() }} record{{ $infant->growthRecords->count() === 1 ? '' : 's' }}</span></div>@if($infant->growthRecords->isEmpty())<div class="neonatal-empty">No growth history available yet.</div>@else<div style="overflow:auto"><table class="history-table"><thead><tr><th>Age</th><th>Weight</th><th>Height</th><th>Date</th><th>Recorded By</th></tr></thead><tbody>@foreach($infant->growthRecords->take(-5) as $record)<tr><td><strong>{{ $record->age_months }} mo</strong></td><td>{{ $formatNumber($record->weight, ' kg') }}</td><td>{{ $formatNumber($record->height, ' cm') }}</td><td>{{ $record->measured_at?->format('M j, Y') }}</td><td>{{ $record->recorder?->full_name ?? $staff->full_name }}</td></tr>@endforeach</tbody></table></div>@endif</section>
                                        <section class="vaccine-panel">
                                            <div class="section-row"><h3>{!! $iconSyringe !!} Vaccine Surveillance</h3><strong>{{ $completedVaccines }}/{{ $vaccines->count() }} complete - {{ $vaccineCompletion }}%</strong></div>
                                                                                        <div class="vaccine-progress"><span style="--progress: {{ $vaccineCompletion }}%"></span></div>
                                            @php
                                                $orderedVaccines = $vaccines->sortBy(fn ($record) => ($record->due_date?->timestamp ?? PHP_INT_MAX).str_pad((string) $record->id, 10, '0', STR_PAD_LEFT))->values();
                                            @endphp
                                            @if($orderedVaccines->isEmpty())
                                                <div class="neonatal-empty">No vaccine records yet.</div>
                                            @else
                                                <div class="vaccine-flat-grid">
                                                    @foreach($orderedVaccines as $vaccine)
                                                        <button type="button" class="vaccine-card" data-neonatal-open="vaccine" data-action="{{ route('staff.neonatal.vaccines.update', $vaccine) }}" data-name="{{ $vaccine->vaccine_name }}" data-dose="{{ $vaccine->dose_label }}" data-status="{{ $vaccine->status }}" data-date="{{ $vaccine->administered_at?->toDateString() }}" data-facility="{{ $vaccine->facility }}" data-lot="{{ $vaccine->lot_number }}" data-vaccinator="{{ $vaccine->vaccinator }}" data-remarks="{{ $vaccine->remarks }}">
                                                            <span class="vaccine-box @if($vaccine->status === 'completed') is-checked @endif" aria-hidden="true"></span>
                                                            <span><strong>{{ $vaccine->vaccine_name }}</strong><small>{{ $vaccine->dose_label }}</small></span>
                                                        </button>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </section>
                                    </details>
                                @endforeach
                            </div>
                        @endif
                    </section>
                @else
                    <section class="neonatal-card neonatal-empty">No assigned mother casefile was found. Add mothers in Mothers Casefiles first.</section>
                @endif
            </div>
        </div>
        @if ($selectedMother)
            <div class="neonatal-modal" data-neonatal-modal="infant" hidden>
                <div class="neonatal-backdrop" data-neonatal-close></div>
                <form class="neonatal-dialog" method="POST" action="{{ route('staff.neonatal.infants.store') }}">
                    @csrf
                    <input type="hidden" name="mother_id" value="{{ $selectedMother->id }}">
                    <header><h2>Onboard Infant</h2><button type="button" class="neonatal-dialog-close" data-neonatal-close>{!! $iconClose !!}</button></header>
                    <div class="neonatal-form-grid">
                        <label><span>Child Full Name</span><input name="full_name" required value="{{ old('full_name') }}"></label>
                        <label><span>Sex</span><select name="sex" required><option value="female">Female</option><option value="male">Male</option><option value="other">Other</option></select></label>
                        <label><span>Birth Date</span><input type="date" name="birth_date" required value="{{ old('birth_date', now()->toDateString()) }}"></label>
                        <label><span>Birth Weight (kg)</span><input type="number" step="0.1" min="0.5" max="12" name="birth_weight" value="{{ old('birth_weight') }}"></label>
                        <label><span>Birth Height (cm)</span><input type="number" step="0.1" min="20" max="80" name="birth_height" value="{{ old('birth_height') }}"></label>
                        <label><span>Facility</span><input name="facility" value="{{ old('facility', $selectedMother->barangay ? 'RHU - '.$selectedMother->barangay : '') }}"></label>
                        <label class="is-wide"><span>Notes</span><textarea name="notes">{{ old('notes') }}</textarea></label>
                    </div>
                    <footer><button type="button" class="neonatal-secondary" data-neonatal-close>Cancel</button><button class="neonatal-action is-primary" type="submit">Save Infant</button></footer>
                </form>
            </div>
        @endif

        <div class="neonatal-modal" data-neonatal-modal="growth" hidden>
            <div class="neonatal-backdrop" data-neonatal-close></div>
            <form class="neonatal-dialog" method="POST" data-growth-form>
                @csrf
                <header><h2 data-growth-title>Update Growth</h2><button type="button" class="neonatal-dialog-close" data-neonatal-close>{!! $iconClose !!}</button></header>
                <div class="neonatal-form-grid">
                    <label><span>Measurement Date</span><input type="date" name="measured_at" required value="{{ now()->toDateString() }}"></label>
                    <label><span>Age (months)</span><input type="number" min="0" max="60" name="age_months" required data-growth-age></label>
                    <label><span>Weight (kg)</span><input type="number" step="0.1" min="0.5" max="50" name="weight" required></label>
                    <label><span>Height (cm)</span><input type="number" step="0.1" min="20" max="130" name="height" required></label>
                    <label><span>Head Circumference (cm)</span><input type="number" step="0.1" min="20" max="70" name="head_circumference"></label>
                    <label><span>Temperature (C)</span><input type="number" step="0.1" min="34" max="43" name="temperature"></label>
                    <label class="is-wide"><span>Remarks</span><textarea name="remarks" placeholder="Document growth observation, nutrition advice, or referral notes..."></textarea></label>
                </div>
                <footer><button type="button" class="neonatal-secondary" data-neonatal-close>Cancel</button><button class="neonatal-action is-primary" type="submit">Save Growth</button></footer>
            </form>
        </div>

        <div class="neonatal-modal" data-neonatal-modal="vaccine" hidden>
            <div class="neonatal-backdrop" data-neonatal-close></div>
            <form class="neonatal-dialog" method="POST" data-vaccine-form>
                @csrf
                <header><h2 data-vaccine-title>Update Vaccine</h2><button type="button" class="neonatal-dialog-close" data-neonatal-close>{!! $iconClose !!}</button></header>
                <div class="neonatal-form-grid">
                    <label><span>Status</span><select name="status" data-vaccine-status><option value="upcoming">Upcoming</option><option value="completed">Completed</option><option value="deferred">Deferred</option><option value="missed">Missed</option></select></label>
                    <label><span>Administration Date</span><input type="date" name="administered_at" data-vaccine-date></label>
                    <label><span>Facility</span><input name="facility" data-vaccine-facility></label>
                    <label><span>Lot Number</span><input name="lot_number" data-vaccine-lot></label>
                    <label><span>Vaccinator</span><input name="vaccinator" data-vaccine-vaccinator></label>
                    <label class="is-wide"><span>Remarks</span><textarea name="remarks" data-vaccine-remarks></textarea></label>
                </div>
                <footer><button type="button" class="neonatal-secondary" data-neonatal-close>Cancel</button><button class="neonatal-action is-primary" type="submit">Save Vaccine</button></footer>
            </form>
        </div>
    </section>

    <script>
        (() => {
            const body = document.body;
            const motherSearch = document.querySelector('[data-neonatal-mother-search]');
            const motherRows = Array.from(document.querySelectorAll('[data-neonatal-mother-list] [data-search-text]'));
            const modals = Array.from(document.querySelectorAll('[data-neonatal-modal]'));
            const growthForm = document.querySelector('[data-growth-form]');
            const vaccineForm = document.querySelector('[data-vaccine-form]');
            const closeModals = () => { modals.forEach((modal) => modal.hidden = true); body.classList.remove('has-neonatal-modal'); };
            const openModal = (name) => { const modal = document.querySelector(`[data-neonatal-modal="${name}"]`); if (!modal) return; closeModals(); modal.hidden = false; body.classList.add('has-neonatal-modal'); const first = modal.querySelector('input, select, textarea, button'); if (first) first.focus(); };
            motherSearch?.addEventListener('input', () => { const term = motherSearch.value.trim().toLowerCase(); motherRows.forEach((row) => { row.hidden = term !== '' && !row.dataset.searchText.includes(term); }); });
            document.querySelectorAll('[data-neonatal-open]').forEach((button) => {
                button.addEventListener('click', () => {
                    const target = button.dataset.neonatalOpen;
                    if (target === 'growth' && growthForm) { growthForm.action = button.dataset.action; document.querySelector('[data-growth-title]').textContent = `Update Growth - ${button.dataset.name || 'Infant'}`; const age = growthForm.querySelector('[data-growth-age]'); if (age) age.value = button.dataset.age || 0; }
                    if (target === 'vaccine' && vaccineForm) { vaccineForm.action = button.dataset.action; document.querySelector('[data-vaccine-title]').textContent = `${button.dataset.name || 'Vaccine'} - ${button.dataset.dose || ''}`; vaccineForm.querySelector('[data-vaccine-status]').value = button.dataset.status || 'upcoming'; vaccineForm.querySelector('[data-vaccine-date]').value = button.dataset.date || ''; vaccineForm.querySelector('[data-vaccine-facility]').value = button.dataset.facility || ''; vaccineForm.querySelector('[data-vaccine-lot]').value = button.dataset.lot || ''; vaccineForm.querySelector('[data-vaccine-vaccinator]').value = button.dataset.vaccinator || ''; vaccineForm.querySelector('[data-vaccine-remarks]').value = button.dataset.remarks || ''; }
                    openModal(target);
                });
            });
            document.querySelectorAll('[data-neonatal-close]').forEach((button) => button.addEventListener('click', closeModals));
            document.querySelector('[data-neonatal-alert-close]')?.addEventListener('click', (event) => event.currentTarget.closest('.neonatal-alert')?.remove());
            document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeModals(); });
        })();
    </script>
@endsection
