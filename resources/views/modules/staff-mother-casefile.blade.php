@extends('layouts.app')

@section('title', $mother->full_name.' Casefile - Project INAY')
@section('portal_title', 'Mother Care Summary')

@php
    $iconArrowLeft = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>';
    $iconChevron = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>';
    $iconPhone = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.4 19.4 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.4 2.1L8.1 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.6 1.9Z"/></svg>';
    $iconMap = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>';
    $iconUser = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 21a7 7 0 0 0-14 0"/><circle cx="12" cy="7" r="4"/></svg>';
    $iconShield = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>';
    $iconFile = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>';
    $iconPulse = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>';
    $iconCalendar = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="5" width="18" height="17" rx="2"/><path d="M3 10h18"/></svg>';
    $iconClock = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>';
    $iconHeart = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 1 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/></svg>';
    $iconEdit = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>';
    $iconPrinter = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>';
    $iconDownload = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>';
    $iconTrend = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 17 6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>';
    $iconHistory = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 3v6h6"/><path d="M12 7v5l3 2"/></svg>';
    $iconAlert = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>';
    $iconCheck = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>';
    $iconHighRisk = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 7v6"/><path d="M12 17h.01"/></svg>';

    $statusLabels = [
        'pregnant' => 'Pregnant',
        'postpartum' => 'Postpartum',
        'planning' => 'Planning pregnancy',
        'not_pregnant' => 'Not pregnant',
    ];
    $uploadTypeLabels = [
        'Prenatal Records and Receipts' => 'Prenatal Records and Receipts',
        'Checkup Records' => 'Prenatal Records and Receipts',
        'Prescription' => 'Prenatal Records and Receipts',
        'Receipts' => 'Prenatal Records and Receipts',
        'Certificate' => 'Certificate',
        'Other Documents' => 'Other Supporting Document',
    ];
    $screeningStatusLabel = fn ($status) => \App\Support\MaternalVitalScreening::normalizeStatus($status);
    $screeningStatusClass = fn ($status) => 'is-'.\App\Support\MaternalVitalScreening::statusSlug($status);
    $latestVitals = $maternalVitalsPayload['latest'] ?? null;
    $formattedVitalsById = collect($maternalVitalsPayload['records'] ?? [])->keyBy('id');
    $riskLabel = $latestVitals['screening_summary_status'] ?? $screeningStatusLabel($latestRecord?->screening_summary_status ?? $latestRecord?->risk_level);
    $riskValue = \App\Support\MaternalVitalScreening::statusKey($riskLabel);
    $riskClass = $screeningStatusClass($riskLabel);
    $initials = strtoupper(substr($mother->first_name, 0, 1).substr($mother->last_name, 0, 1));
    $caseId = 'MAT-RHU-'.str_pad((string) $mother->id, 3, '0', STR_PAD_LEFT);
    $pregnancyWeek = $latestRecord?->pregnancy_week;
    $pregnancyMonth = $latestRecord?->pregnancy_month;
    $isCurrentlyPregnant = $mother->pregnancy_status === 'pregnant';
    $trimester = $isCurrentlyPregnant
        ? ($pregnancyWeek ? ($pregnancyWeek >= 28 ? 'Third Trimester' : ($pregnancyWeek >= 14 ? 'Second Trimester' : 'First Trimester')) : 'Not provided')
        : ($mother->pregnancy_status === 'postpartum' ? 'Postpartum' : 'Not applicable');
    $bpValue = $latestRecord?->bp_systolic && $latestRecord?->bp_diastolic ? $latestRecord->bp_systolic.'/'.$latestRecord->bp_diastolic.' mmHg' : 'Not logged';
    $weightNumber = $latestRecord?->weight;
    $weightValue = $weightNumber === null ? 'Not logged' : rtrim(rtrim(number_format((float) $weightNumber, 2), '0'), '.').' kg';
    $heightNumber = $records->first(fn ($record) => $record->height_cm !== null)?->height_cm;
    $prePregnancyWeightNumber = $records->first(fn ($record) => $record->pre_pregnancy_weight !== null)?->pre_pregnancy_weight;
    $prePregnancyBmiNumber = $heightNumber !== null && $prePregnancyWeightNumber !== null && (float) $heightNumber > 0
        ? round((float) $prePregnancyWeightNumber / ((((float) $heightNumber / 100) ** 2)), 2)
        : null;
    $sugarValue = $latestRecord?->blood_sugar === null ? 'Not logged' : rtrim(rtrim(number_format((float) $latestRecord->blood_sugar, 1), '0'), '.').' mg/dL';
    $temperatureValue = $latestRecord?->temperature === null ? 'Not logged' : rtrim(rtrim(number_format((float) $latestRecord->temperature, 1), '0'), '.').' C';
    $heartRateValue = $latestRecord?->heart_rate === null ? 'Not logged' : $latestRecord->heart_rate.' bpm';
    $bloodSugarTestTypes = \App\Support\MaternalVitalScreening::bloodSugarTestTypes();
    $bloodSugarTestTypeLabel = $latestVitals['blood_sugar_test_type_label'] ?? 'Test type not recorded';
    $latestDate = ($latestRecord?->recorded_at ?? $latestRecord?->created_at);
    $kaalamanMonthlyProgress = $kaalamanMonthlyProgress ?? ['months' => [], 'overall' => ['completed_months' => 0, 'total_months' => 10, 'pending_required' => 0, 'files_uploaded' => $uploads->count()]];
    $kaalamanOverallProgress = $kaalamanOverallProgress ?? ($kaalamanMonthlyProgress['overall'] ?? ['completed_months' => 0, 'total_months' => 10, 'pending_required' => 0, 'files_uploaded' => $uploads->count()]);
    $completion = $careCompletion ?? min(100, ($records->count() * 5) + ($uploads->count() * 5));
    $prescriptionUploads = $uploads->filter(fn ($upload) => str_contains(strtolower($upload->record_type), 'prescription'))->count();
    $weightRecords = $records->filter(fn ($record) => $record->weight !== null)->values();
    $bpRecords = $records->filter(fn ($record) => $record->bp_systolic !== null && $record->bp_diastolic !== null)->values();
    $latestBpEntry = collect($maternalVitalsPayload['blood_pressure_history'] ?? [])->last();
    $bpStatusState = function (?string $status, bool $hasRecord): string {
        if (! $hasRecord) {
            return 'is-empty';
        }

        $rawStatus = strtolower(trim((string) $status));
        $statusKey = \App\Support\MaternalVitalScreening::statusKey($status);

        if ($statusKey === 'urgent_referral_recommended' || in_array($rawStatus, ['high risk', 'high-risk', 'critical'], true)) {
            return 'is-high-risk';
        }

        if ($statusKey === 'within_reference_range' || in_array($rawStatus, ['normal / stable', 'normal', 'stable'], true)) {
            return 'is-normal';
        }

        return 'is-monitoring';
    };
    $bpStatusLabelFor = function (?string $status, bool $hasRecord) use ($bpStatusState): string {
        return match ($bpStatusState($status, $hasRecord)) {
            'is-normal' => 'Normal / Stable',
            'is-high-risk' => 'High Risk',
            'is-empty' => 'No Record',
            default => 'Needs Monitoring',
        };
    };
    $bpStatusExplanationFor = function (?string $status, bool $hasRecord) use ($bpStatusState): string {
        return match ($bpStatusState($status, $hasRecord)) {
            'is-normal' => 'Latest recorded blood pressure is within the configured screening review range.',
            'is-high-risk' => 'A high-risk blood pressure status was recorded. Professional clinical assessment is required.',
            'is-empty' => 'No blood pressure record available yet.',
            default => 'Blood pressure requires further monitoring and professional assessment.',
        };
    };
    $latestBpStatusSource = $latestBpEntry['raw_status'] ?? $latestBpEntry['status'] ?? null;
    $latestBpStatusState = $bpStatusState($latestBpStatusSource, $latestBpEntry !== null);
    $latestBpStatusLabel = $bpStatusLabelFor($latestBpStatusSource, $latestBpEntry !== null);
    $latestBpStatusExplanation = $latestBpEntry['explanation'] ?? $bpStatusExplanationFor($latestBpStatusSource, $latestBpEntry !== null);
    $latestBpDisplay = $latestBpEntry ? $latestBpEntry['systolic'].' / '.$latestBpEntry['diastolic'] : '-- / --';
    $latestBpContext = $latestBpEntry
        ? ($latestBpEntry['pregnancy_week'] ? 'Pregnancy week '.$latestBpEntry['pregnancy_week'] : 'Recorded '.($latestBpEntry['recorded_label'] ?? 'Date not recorded'))
        : 'No blood pressure record available yet.';
    $bpPointX = 52;
    $bpSystolicY = $latestRecord?->bp_systolic ? max(12, min(84, 100 - (($latestRecord->bp_systolic - 70) / 70 * 90))) : 52;
    $bpDiastolicY = $latestRecord?->bp_diastolic ? max(12, min(84, 100 - (($latestRecord->bp_diastolic - 60) / 70 * 90))) : 76;
    $weightPointY = $weightNumber ? max(12, min(84, 100 - (((float) $weightNumber - 70) / 10 * 90))) : 54;
    $initialRecordDate = $latestDate?->format('Y-m-d') ?? now()->toDateString();
    $latestRecordedLabel = $latestDate?->format('M j, Y') ?? 'No data available';
    $learningPercent = (int) ($kaalamanOverallProgress['percentage'] ?? 0);
    $learningCompleted = (int) ($kaalamanOverallProgress['completed_months'] ?? 0);
    $learningTotal = (int) ($kaalamanOverallProgress['total_months'] ?? 10);
    $uploadedFileCount = (int) ($kaalamanOverallProgress['files_uploaded'] ?? $uploads->count());
    $visitCount = min($records->count(), 8);
    $visitPercent = $visitCount > 0 ? round(($visitCount / 8) * 100) : 0;
    $consultations = $consultations ?? collect();
    $fourPsLabel = $mother->is_4ps_beneficiary ? '4Ps Beneficiary' : 'Not 4Ps beneficiary';
    $fourPsCopy = $mother->is_4ps_beneficiary ? 'Social support status confirmed' : 'No 4Ps record on file';
    $maternalAgeRisk = $mother->maternal_age_risk ?? 'Not provided';
    $obstetricHistory = $mother->gravidity === null && $mother->parity === null
        ? 'Not provided'
        : 'G'.($mother->gravidity ?? '—').' P'.($mother->parity ?? '—');
    $riskCardClass = match ($riskValue) {
        'urgent_referral_recommended' => 'is-risk',
        'for_review', 'for_professional_interpretation' => 'is-review',
        'within_reference_range' => 'is-green',
        default => 'is-neutral',
    };
    $riskDateCopy = $latestDate ? 'Updated '.$latestDate->format('M j, Y') : 'No monitoring record yet';
    $vitalDateLabels = [
        'blood_pressure' => ($latestRecord?->bp_systolic && $latestRecord?->bp_diastolic) ? 'Recorded '.$latestRecordedLabel : 'No measurement available',
        'blood_sugar' => $latestRecord?->blood_sugar === null ? 'No measurement available' : 'Recorded '.$latestRecordedLabel,
        'weight' => $latestRecord?->weight === null ? 'No measurement available' : 'Recorded '.$latestRecordedLabel,
        'temperature' => $latestRecord?->temperature === null ? 'No measurement available' : 'Recorded '.$latestRecordedLabel,
        'heart_rate' => $latestRecord?->heart_rate === null ? 'No measurement available' : 'Recorded '.$latestRecordedLabel,
    ];
    $vitalStatus = fn (string $key) => $latestVitals['statuses'][$key] ?? \App\Support\MaternalVitalScreening::STATUS_LOGGED;
    $vitalExplanation = fn (string $key) => $latestVitals['explanations'][$key] ?? 'No screening explanation available yet.';
    $vitalGuideline = fn (string $key) => $latestVitals['guidelines'][$key] ?? ['name' => 'Facility-configurable maternal vital screening rule', 'version' => 'Pending partner validation', 'source_url' => null];
    $vitalCards = [
        ['key' => 'blood_pressure', 'title' => 'Blood Pressure', 'icon' => $iconHeart, 'tone' => 'is-pink', 'value' => $bpValue, 'unit' => 'mmHg', 'test_type' => 'Not applicable'],
        ['key' => 'blood_sugar', 'title' => 'Blood Sugar', 'icon' => $iconPulse, 'tone' => 'is-pink', 'value' => $sugarValue, 'unit' => 'mg/dL', 'test_type' => $bloodSugarTestTypeLabel],
        ['key' => 'weight', 'title' => 'Weight', 'icon' => $iconShield, 'tone' => 'is-green', 'value' => $weightValue, 'unit' => 'kg', 'test_type' => 'Not applicable'],
        ['key' => 'temperature', 'title' => 'Temperature', 'icon' => $iconPulse, 'tone' => 'is-blue', 'value' => $temperatureValue, 'unit' => 'C', 'test_type' => 'Not applicable'],
        ['key' => 'heart_rate', 'title' => 'Heart Rate', 'icon' => $iconHeart, 'tone' => 'is-green', 'value' => $heartRateValue, 'unit' => 'bpm', 'test_type' => 'Not applicable'],
    ];
    $clinicalReferences = \App\Support\MaternalVitalScreening::references();
    $safetyNotice = $maternalVitalsPayload['safety_notice'] ?? 'Project INAY provides threshold-based screening alerts for monitoring purposes only. Results must be verified and interpreted by a qualified healthcare professional. The system does not provide a medical diagnosis.';
    $currentJourneyStage = match (true) {
        $mother->pregnancy_status === 'postpartum' => 'postpartum',
        $isCurrentlyPregnant && $pregnancyWeek && $pregnancyWeek >= 28 => 'third',
        $isCurrentlyPregnant && $pregnancyWeek && $pregnancyWeek >= 14 => 'second',
        $isCurrentlyPregnant => 'first',
        default => 'registration',
    };
    $journeyStatus = function (string $stage) use ($currentJourneyStage): string {
        $order = ['registration' => 0, 'first' => 1, 'second' => 2, 'third' => 3, 'delivery' => 4, 'postpartum' => 5];

        if ($stage === $currentJourneyStage) {
            return 'current';
        }

        return ($order[$stage] ?? 0) < ($order[$currentJourneyStage] ?? 0) ? 'complete' : 'upcoming';
    };
    $journeyStages = [
        ['key' => 'registration', 'title' => 'Registration', 'description' => 'Patient account created', 'date' => $mother->created_at?->format('M j, Y') ?? 'Date not recorded', 'icon' => $iconUser],
        ['key' => 'first', 'title' => 'First Trimester', 'description' => 'Weeks 1-13', 'date' => $currentJourneyStage === 'first' ? 'Current stage' : ($pregnancyWeek && $pregnancyWeek >= 14 ? 'Completed' : 'Upcoming'), 'icon' => $iconHeart],
        ['key' => 'second', 'title' => 'Second Trimester', 'description' => 'Weeks 14-27', 'date' => $currentJourneyStage === 'second' ? 'Current stage' : ($pregnancyWeek && $pregnancyWeek >= 28 ? 'Completed' : 'Upcoming'), 'icon' => $iconPulse],
        ['key' => 'third', 'title' => 'Third Trimester', 'description' => 'Weeks 28-40', 'date' => $currentJourneyStage === 'third' ? 'Current stage' : ($mother->pregnancy_status === 'postpartum' ? 'Completed' : 'Upcoming'), 'icon' => $iconShield],
        ['key' => 'delivery', 'title' => 'Delivery', 'description' => 'Birth plan and delivery', 'date' => $mother->pregnancy_status === 'postpartum' ? 'Completed' : 'Upcoming', 'icon' => $iconCalendar],
        ['key' => 'postpartum', 'title' => 'Postpartum Care', 'description' => 'After delivery follow-up', 'date' => $mother->pregnancy_status === 'postpartum' ? 'Current stage' : 'Upcoming', 'icon' => $iconClock],
    ];
    $patientActivities = collect();
    $patientActivities->push([
        'title' => 'Patient registered',
        'description' => 'Mother account was created in Project INAY.',
        'category' => 'Registration',
        'date' => $mother->created_at,
    ]);
    foreach ($records as $record) {
        $recordDate = $record->recorded_at ?? $record->created_at;
        $formattedRecord = $formattedVitalsById->get($record->id);
        $recordRiskLabel = $screeningStatusLabel($formattedRecord['screening_summary_status'] ?? $record->screening_summary_status ?? $record->risk_level);
        $patientActivities->push([
            'title' => 'Maternal monitoring updated',
            'description' => 'Week '.($record->pregnancy_week ?: 'N/A').' vitals recorded with '.$recordRiskLabel.' screening status.',
            'category' => 'Monitoring',
            'date' => $recordDate,
        ]);

        if ($record->risk_level) {
            $patientActivities->push([
                'title' => 'Screening status updated',
                'description' => 'Maternal vital screening status is '.$recordRiskLabel.'.',
                'category' => 'Screening Update',
                'date' => $recordDate,
            ]);
        }
    }
    foreach ($uploads as $upload) {
        $patientActivities->push([
            'title' => 'Prenatal document uploaded',
            'description' => ($uploadTypeLabels[$upload->record_type] ?? $upload->record_type).' file received: '.$upload->original_name.'.',
            'category' => 'Documents',
            'date' => $upload->created_at,
        ]);
    }
    foreach ($consultations as $conversation) {
        $conversationDate = $conversation->last_message_at ?? $conversation->updated_at ?? $conversation->created_at;
        $patientActivities->push([
            'title' => 'Consultation updated',
            'description' => $conversation->lastMessage ? 'Recent message recorded in the mother-staff consultation.' : 'Consultation channel is available for this patient.',
            'category' => 'Consultation',
            'date' => $conversationDate,
        ]);
    }
    if ($learningPercent > 0) {
        $patientActivities->push([
            'title' => 'Learning progress updated',
            'description' => $learningCompleted.'/'.$learningTotal.' INAY Kaalaman months completed.',
            'category' => 'Learning',
            'date' => $uploads->first()?->created_at ?? $latestDate ?? $mother->created_at,
        ]);
    }
    $patientActivities = $patientActivities
        ->sortByDesc(fn ($activity) => $activity['date']?->getTimestamp() ?? 0)
        ->values();
@endphp

@push('styles')
    <style>
        /* ===== GLOBAL RESET & BASE ===== */
        .casefile-summary-page {
            --inay-pink: #ec0a78;
            --inay-pink-soft: #fff4fa;
            --inay-pink-border: #ffc7e3;
            --inay-green: #008f6b;
            --inay-green-soft: #ecfdf5;
            --inay-green-border: #9de8c7;
            --inay-ink: #071225;
            --inay-muted: #5f6f86;
            --inay-border: #dce6f1;
            --inay-panel: #ffffff;
            --inay-soft: #f8fafc;
            --inay-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
            --inay-radius: 8px;

            max-width: 1480px;
            margin: 0 auto;
            padding: 20px 24px 40px;
            color: var(--inay-ink);
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .casefile-summary-page * {
            box-sizing: border-box;
            letter-spacing: 0;
        }

        .casefile-summary-page svg {
            width: 18px;
            height: 18px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
            flex-shrink: 0;
        }

        /* Profile card header with kicker + risk badge */
        .casefile-summary-page .casefile-profile-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 6px;
        }
        .casefile-summary-page .casefile-profile-header .casefile-risk {
            flex-shrink: 0;
        }

        .casefile-summary-page .casefile-profile-card {
            padding: 24px 28px;
            gap: 20px;
        }

        .casefile-summary-page .casefile-profile-main {
            gap: 24px;
        }

        .casefile-summary-page .casefile-contact-row {
            gap: 14px;
            margin-top: 12px;
        }

        .casefile-summary-page .casefile-profile-actions {
            padding-top: 18px;
            margin-top: 4px;
        }

        .casefile-summary-page .casefile-profile-facts {
            gap: 14px;
        }

        @media (max-width: 768px) {
            .casefile-summary-page .casefile-profile-header {
                flex-direction: row;
                justify-content: space-between;
            }
            .casefile-summary-page .casefile-profile-header .casefile-risk {
                font-size: 10px;
                padding: 0 10px;
                min-height: 24px;
            }
        }

        /* ===== TYPOGRAPHY ===== */
        .casefile-summary-page h1,
        .casefile-summary-page h2,
        .casefile-summary-page h3,
        .casefile-summary-page h4 {
            margin: 0;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .casefile-summary-page p {
            margin: 0;
        }

        /* ===== HEADER ===== */
        .casefile-summary-page .casefile-detail-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--inay-border);
            flex-wrap: wrap;
        }

        .casefile-summary-page .casefile-detail-heading > div {
            flex: 1 1 auto;
            min-width: 200px;
        }

        .casefile-summary-page .casefile-detail-heading h1 {
            margin-top: 8px;
            font-size: clamp(24px, 3vw, 34px);
            line-height: 1.1;
        }

        .casefile-summary-page .casefile-detail-heading p {
            max-width: 760px;
            margin-top: 4px;
            color: var(--inay-muted);
            font-size: 14px;
            line-height: 1.45;
            font-weight: 600;
        }

        .casefile-summary-page .casefile-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #8394ad;
            font-size: 11px;
            font-weight: 800;
        }

        .casefile-summary-page .casefile-breadcrumb a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #8394ad;
            text-decoration: none;
            transition: color 160ms ease;
        }

        .casefile-summary-page .casefile-breadcrumb a:hover {
            color: var(--inay-pink);
        }

        .casefile-summary-page .casefile-back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            color: var(--inay-ink);
            background: var(--inay-soft);
            border: 1px solid var(--inay-border);
            border-radius: var(--inay-radius);
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: all 160ms ease;
            flex-shrink: 0;
        }

        .casefile-summary-page .casefile-back-link:hover {
            background: var(--inay-pink-soft);
            border-color: var(--inay-pink-border);
            color: var(--inay-pink);
            transform: translateY(-1px);
        }

        /* ===== PROFILE CARD ===== */
        .casefile-summary-page .casefile-profile-card {
            background: var(--inay-panel);
            border: 1px solid var(--inay-border);
            border-radius: var(--inay-radius);
            box-shadow: var(--inay-shadow);
            padding: 20px 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .casefile-summary-page .casefile-profile-kicker {
            margin: 0;
            color: var(--inay-pink);
            font-size: 11px;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .casefile-summary-page .casefile-profile-main {
            display: flex;
            align-items: flex-start;
            gap: 20px;
        }

        .casefile-summary-page .casefile-avatar-wrapper {
            flex-shrink: 0;
        }

        .casefile-summary-page .casefile-avatar.is-xl {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: var(--inay-pink-soft);
            border: 3px solid #ffe2f1;
            font-size: 22px;
            font-weight: 800;
            color: var(--inay-pink);
        }

        .casefile-summary-page .casefile-profile-info {
            flex: 1;
            min-width: 0;
        }

        .casefile-summary-page .casefile-profile-title {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-bottom: 2px;
        }

        .casefile-summary-page .casefile-profile-title h2 {
            font-size: clamp(21px, 2.1vw, 28px);
            line-height: 1.15;
            margin: 0;
        }

        .casefile-summary-page .casefile-id {
            display: block;
            color: var(--inay-muted);
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .casefile-summary-page .casefile-risk {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 0 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .casefile-summary-page .casefile-risk.is-within-reference-range,
        .casefile-summary-page .casefile-risk.is-logged {
            color: #007f5f;
            background: #ecfdf5;
            border: 1px solid #86efc2;
        }

        .casefile-summary-page .casefile-risk.is-for-review {
            color: #975a16;
            background: #fffbeb;
            border: 1px solid #fde68a;
        }

        .casefile-summary-page .casefile-risk.is-for-professional-interpretation {
            color: #1d4ed8;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
        }

        .casefile-summary-page .casefile-risk.is-urgent-referral-recommended {
            color: #b42318;
            background: #fff7f7;
            border: 1px solid #fecaca;
        }

        /* ===== CONTACT ROW ===== */
        .casefile-summary-page .casefile-contact-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 8px;
        }

        .casefile-summary-page .casefile-contact-row article {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 14px;
            background: var(--inay-soft);
            border: 1px solid #e5edf6;
            border-radius: var(--inay-radius);
            min-width: 0;
        }

        .casefile-summary-page .casefile-contact-row article svg {
            flex-shrink: 0;
            margin-top: 2px;
            color: var(--inay-muted);
            width: 16px;
            height: 16px;
        }

        .casefile-summary-page .casefile-contact-row article > div {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }

        .casefile-summary-page .casefile-contact-row span {
            color: #7e8fa8;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .casefile-summary-page .casefile-contact-row strong {
            color: var(--inay-ink);
            font-size: 13px;
            line-height: 1.35;
            overflow-wrap: anywhere;
            font-weight: 700;
        }

        /* ===== PROFILE ACTIONS ===== */
        .casefile-summary-page .casefile-profile-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
            padding-top: 14px;
            border-top: 1px solid #e8eef5;
        }

        .casefile-summary-page .casefile-profile-actions button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 38px;
            padding: 0 16px;
            font-size: 12px;
            font-weight: 800;
            font-family: inherit;
            color: var(--inay-ink);
            background: transparent;
            border: 1px solid var(--inay-border);
            border-radius: var(--inay-radius);
            cursor: pointer;
            transition: all 160ms ease;
        }

        .casefile-summary-page .casefile-profile-actions button:hover {
            color: var(--inay-pink);
            background: var(--inay-pink-soft);
            border-color: var(--inay-pink-border);
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(236, 10, 120, 0.08);
        }

        .casefile-summary-page .casefile-profile-actions button.is-dark {
            color: #ffffff;
            background: var(--inay-ink);
            border-color: var(--inay-ink);
        }

        .casefile-summary-page .casefile-profile-actions button.is-dark:hover {
            background: #1a2a3a;
            border-color: #1a2a3a;
            color: #ffffff;
        }

        /* ===== PROFILE FACTS ===== */
        .casefile-summary-page .casefile-profile-facts {
            display: grid;
            grid-template-columns: repeat(10, minmax(0, 1fr));
            gap: 10px;
            margin: 0;
            padding: 0;
            border: 0;
        }

        .casefile-summary-page .casefile-profile-facts div {
            padding: 10px 14px;
            background: var(--inay-soft);
            border: 1px solid #e5edf6;
            border-radius: var(--inay-radius);
            text-align: left;
        }

        .casefile-summary-page .casefile-profile-facts dt {
            color: #8797ae;
            font-size: 10px;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

    .casefile-summary-page .casefile-profile-facts dd {
            margin: 4px 0 0;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        .casefile-summary-page .casefile-fact-badge {
            display: inline-flex;
            align-items: center;
            max-width: 100%;
            padding: 3px 7px;
            color: #975a16;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 999px;
            font-size: 11px;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        .casefile-summary-page .casefile-fact-badge.is-standard {
            color: #007f5f;
            background: #ecfdf5;
            border-color: #86efc2;
        }

        .casefile-summary-page .casefile-fact-badge.is-neutral {
            color: var(--inay-muted);
            background: #f8fafc;
            border-color: var(--inay-border);
        }

        /* ===== STATUS GRID ===== */
        .casefile-summary-page .casefile-status-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        .casefile-summary-page .casefile-status-grid article {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-height: 112px;
            padding: 16px 18px;
            background: var(--inay-panel);
            border: 1px solid var(--inay-border);
            border-radius: var(--inay-radius);
            box-shadow: var(--inay-shadow);
        }

        .casefile-summary-page .casefile-status-grid article svg {
            position: absolute;
            top: 14px;
            right: 14px;
            width: 20px;
            height: 20px;
            color: currentColor;
            opacity: 0.6;
        }

        .casefile-summary-page .casefile-status-grid article.is-pink {
            color: #c70867;
            background: var(--inay-pink-soft);
            border-color: var(--inay-pink-border);
        }

        .casefile-summary-page .casefile-status-grid article.is-green {
            color: var(--inay-green);
            background: var(--inay-green-soft);
            border-color: var(--inay-green-border);
        }

        .casefile-summary-page .casefile-status-grid article.is-review {
            color: #975a16;
            background: #fffbeb;
            border-color: #fde68a;
        }

        .casefile-summary-page .casefile-status-grid article.is-risk {
            color: #b42318;
            background: #fff7f7;
            border-color: #fecaca;
        }

        .casefile-summary-page .casefile-status-grid article.is-neutral {
            color: var(--inay-muted);
        }

        .casefile-summary-page .casefile-status-grid article span {
            color: #8797ae;
            font-size: 10px;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .casefile-summary-page .casefile-status-grid article strong {
            font-size: clamp(19px, 2vw, 25px);
            line-height: 1.12;
            font-weight: 800;
        }

        .casefile-summary-page .casefile-status-grid article small {
            color: #52627a;
            font-size: 12px;
            line-height: 1.35;
            font-weight: 700;
        }

        .casefile-summary-page .casefile-status-grid article em {
            display: block;
            height: 6px;
            margin-top: 8px;
            background: #e8eef5;
            border-radius: 999px;
            overflow: hidden;
        }

        .casefile-summary-page .casefile-status-grid article em::before {
            display: block;
            height: 100%;
            width: var(--progress, 0%);
            background: currentColor;
            border-radius: 999px;
            content: '';
        }

        /* ===== TABS ===== */
        .casefile-summary-page .casefile-tabs {
            display: flex;
            gap: 6px;
            overflow-x: auto;
            padding: 6px;
            background: #ffffff;
            border: 1px solid var(--inay-border);
            border-radius: var(--inay-radius);
            box-shadow: var(--inay-shadow);
            scrollbar-width: thin;
        }

        .casefile-summary-page .casefile-tabs::-webkit-scrollbar {
            height: 4px;
        }

        .casefile-summary-page .casefile-tabs::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 999px;
        }

        .casefile-summary-page .casefile-tabs::-webkit-scrollbar-thumb {
            background: var(--inay-pink);
            border-radius: 999px;
        }

        .casefile-summary-page .casefile-tabs button {
            flex: 0 0 auto;
            min-width: 154px;
            min-height: 46px;
            padding: 8px 14px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            text-align: left;
            font-family: inherit;
            color: var(--inay-muted);
            background: transparent;
            border: 1px solid transparent;
            border-radius: 6px;
            cursor: pointer;
            transition: all 160ms ease;
        }

        .casefile-summary-page .casefile-tabs button strong {
            font-size: 13px;
            font-weight: 800;
        }

        .casefile-summary-page .casefile-tabs button small {
            font-size: 11px;
            font-weight: 600;
            opacity: 0.7;
        }

        .casefile-summary-page .casefile-tabs button.is-active,
        .casefile-summary-page .casefile-tabs button:hover {
            color: #ffffff;
            background: var(--inay-pink);
            border-color: var(--inay-pink);
        }

        .casefile-summary-page .casefile-tabs button.is-active small,
        .casefile-summary-page .casefile-tabs button:hover small {
            opacity: 0.9;
        }

        .casefile-summary-page .casefile-tabs span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            font-size: 10px;
            font-weight: 800;
            background: rgba(255,255,255,0.2);
            border-radius: 999px;
        }

        /* ===== PANELS ===== */
        .casefile-summary-page [data-casefile-panel][hidden] {
            display: none !important;
        }

        .casefile-summary-page [data-casefile-panel="overview"]:not([hidden]) {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .casefile-summary-page .casefile-panel {
            background: var(--inay-panel);
            border: 1px solid var(--inay-border);
            border-radius: var(--inay-radius);
            box-shadow: var(--inay-shadow);
            padding: 20px 24px;
        }

        .casefile-summary-page .casefile-panel-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 16px;
            padding-bottom: 14px;
            border-bottom: 1px solid #e8eef5;
            flex-wrap: wrap;
        }

        .casefile-summary-page .casefile-panel-title h2 {
            font-size: 20px;
            line-height: 1.2;
        }

        .casefile-summary-page .casefile-panel-title p {
            color: var(--inay-muted);
            font-size: 13px;
            line-height: 1.45;
            font-weight: 600;
        }

        .casefile-summary-page .casefile-panel-title button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 38px;
            padding: 0 16px;
            font-size: 12px;
            font-weight: 800;
            font-family: inherit;
            color: var(--inay-pink);
            background: var(--inay-pink-soft);
            border: 1px solid var(--inay-pink-border);
            border-radius: var(--inay-radius);
            cursor: pointer;
            transition: all 160ms ease;
            flex-shrink: 0;
        }

        .casefile-summary-page .casefile-panel-title button:hover {
            background: var(--inay-pink);
            color: #ffffff;
            border-color: var(--inay-pink);
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(236, 10, 120, 0.12);
        }

        /* ===== VITAL GRID ===== */
        .casefile-summary-page .casefile-vital-grid.is-large {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .casefile-summary-page .casefile-vital-grid.is-large article {
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-height: 244px;
            padding: 16px 18px;
            background: var(--inay-soft);
            border: 1px solid var(--inay-border);
            border-radius: var(--inay-radius);
        }

        .casefile-summary-page .casefile-vital-grid .casefile-vital-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .casefile-summary-page .casefile-vital-grid i {
            display: grid;
            width: 34px;
            height: 34px;
            place-items: center;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .casefile-summary-page .casefile-vital-grid i.is-pink {
            background: var(--inay-pink-soft);
            color: var(--inay-pink);
        }

        .casefile-summary-page .casefile-vital-grid i.is-green {
            background: var(--inay-green-soft);
            color: var(--inay-green);
        }

        .casefile-summary-page .casefile-vital-grid i.is-blue {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .casefile-summary-page .casefile-vital-grid b {
            display: inline-flex;
            align-items: center;
            min-height: 25px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            border: 1px solid transparent;
        }

        .casefile-summary-page .casefile-vital-grid b[data-status-tone="for-review"],
        .casefile-summary-page .casefile-vital-grid b[data-status-tone="review"] {
            color: #975a16;
            background: #fffbeb;
            border-color: #fde68a;
        }

        .casefile-summary-page .casefile-vital-grid b[data-status-tone="pending"],
        .casefile-summary-page .casefile-vital-grid b[data-status-tone="logged"] {
            color: #52627a;
            background: #f1f5f9;
            border-color: #dbe5f0;
        }

        .casefile-summary-page .casefile-vital-grid b[data-status-tone="within-reference-range"] {
            color: #007f5f;
            background: #ecfdf5;
            border-color: #86efc2;
        }

        .casefile-summary-page .casefile-vital-grid b[data-status-tone="for-professional-interpretation"] {
            color: #1d4ed8;
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .casefile-summary-page .casefile-vital-grid b[data-status-tone="urgent-referral-recommended"] {
            color: #b42318;
            background: #fff7f7;
            border-color: #fecaca;
        }

        .casefile-summary-page .casefile-vital-grid span {
            color: #6d7e96;
            font-size: 10px;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .casefile-summary-page .casefile-vital-grid.is-large strong {
            font-size: clamp(20px, 1.9vw, 26px);
            line-height: 1.1;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .casefile-summary-page .casefile-vital-meta {
            display: grid;
            gap: 2px;
            margin-top: 6px;
            color: #52627a;
            font-size: 11px;
            line-height: 1.4;
            font-weight: 700;
        }

        .casefile-summary-page .casefile-vital-explanation {
            margin: 8px 0 0;
            color: #334155;
            font-size: 12px;
            line-height: 1.45;
            font-weight: 700;
            flex: 1 1 auto;
        }

        .casefile-summary-page .casefile-reference-link {
            display: inline-flex;
            width: fit-content;
            align-items: center;
            margin-top: 6px;
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 900;
            text-decoration: none;
            transition: color 160ms ease;
        }

        .casefile-summary-page .casefile-reference-link:hover {
            text-decoration: underline;
        }

        .casefile-summary-page .casefile-vital-foot {
            display: grid;
            gap: 4px;
            margin-top: auto;
            padding-top: 10px;
            border-top: 1px solid #e7edf5;
        }

        .casefile-summary-page .casefile-vital-foot small {
            color: #52627a;
            font-size: 10px;
            line-height: 1.35;
            font-weight: 700;
        }

        /* Mobile-only action. Hidden by default and enabled in the mobile breakpoint below. */
        .casefile-summary-page .casefile-vital-toggle-details,
        .casefile-summary-page .casefile-vital-details-close {
            display: none;
        }

        /* ===== SAFETY NOTICE ===== */
        .casefile-summary-page .casefile-safety-notice {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            margin-top: 12px;
            padding: 13px 16px;
            color: #334155;
            background: #f8fafc;
            border: 1px solid #dbe5f1;
            border-radius: var(--inay-radius);
            font-size: 12px;
            line-height: 1.45;
            font-weight: 750;
        }

        .casefile-summary-page .casefile-safety-notice svg {
            flex: 0 0 18px;
            color: #1d4ed8;
        }

        /* ===== SECTION KICKER ===== */
        .casefile-summary-page .casefile-section-kicker {
            color: var(--inay-pink);
            font-size: 11px;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 2px;
        }

        .casefile-summary-page .casefile-section-subtitle {
            color: var(--inay-muted);
            font-size: 13px;
            line-height: 1.45;
            font-weight: 600;
            margin-top: 2px;
        }

        .casefile-summary-page .casefile-panel > h2 {
            font-size: 20px;
            line-height: 1.2;
        }

        .casefile-summary-page .casefile-panel-note {
            color: var(--inay-muted);
            font-size: 13px;
            line-height: 1.45;
            font-weight: 600;
            margin-top: 6px;
        }

        /* ===== REFERENCE GRID ===== */
        .casefile-summary-page .casefile-reference-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 10px;
            margin-top: 12px;
        }

        .casefile-summary-page .casefile-reference-grid a {
            display: grid;
            min-width: 0;
            gap: 4px;
            padding: 12px 16px;
            color: #071225;
            background: #f8fafc;
            border: 1px solid #dbe5f1;
            border-radius: var(--inay-radius);
            text-decoration: none;
            overflow: hidden;
            transition: border-color 160ms ease;
        }

        .casefile-summary-page .casefile-reference-grid a:hover {
            border-color: #bfdbfe;
        }

        .casefile-summary-page .casefile-reference-grid strong {
            min-width: 0;
            font-size: 13px;
            line-height: 1.35;
            font-weight: 900;
            overflow-wrap: anywhere;
        }

        .casefile-summary-page .casefile-reference-grid span {
            min-width: 0;
            max-width: 100%;
            color: #52627a;
            font-size: 11px;
            line-height: 1.35;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        /* ===== PROGRESS SECTION ===== */
        .casefile-summary-page .casefile-progress-section {
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding: 20px 24px;
            background: #ffffff;
            border: 1px solid var(--inay-border);
            border-radius: var(--inay-radius);
            box-shadow: var(--inay-shadow);
        }

        .casefile-summary-page .casefile-progress-section p {
            color: var(--inay-pink);
            font-size: 11px;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .casefile-summary-page .casefile-progress-section h2 {
            font-size: 20px;
            line-height: 1.2;
        }

        .casefile-summary-page .casefile-progress-section > span {
            color: var(--inay-muted);
            font-size: 13px;
            line-height: 1.45;
            font-weight: 600;
        }

        .casefile-summary-page .casefile-progress-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-top: 6px;
        }

        .casefile-summary-page .casefile-progress-cards article {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-height: 118px;
            padding: 16px 18px;
            background: var(--inay-panel);
            border: 1px solid var(--inay-border);
            border-radius: var(--inay-radius);
            box-shadow: var(--inay-shadow);
        }

        .casefile-summary-page .casefile-progress-cards article svg {
            position: absolute;
            top: 14px;
            right: 14px;
            width: 18px;
            height: 18px;
            color: currentColor;
            opacity: 0.6;
        }

        .casefile-summary-page .casefile-progress-cards span {
            color: #8797ae;
            font-size: 10px;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .casefile-summary-page .casefile-progress-cards strong {
            font-size: clamp(22px, 2.2vw, 28px);
            line-height: 1;
            font-weight: 800;
        }

        .casefile-summary-page .casefile-progress-cards small {
            color: var(--inay-muted);
            font-size: 12px;
            line-height: 1.35;
            font-weight: 650;
        }

        .casefile-summary-page .casefile-progress-cards em {
            display: block;
            height: 6px;
            margin-top: 8px;
            background: #e8eef5;
            border-radius: 999px;
            overflow: hidden;
        }

        .casefile-summary-page .casefile-progress-cards em::before {
            display: block;
            height: 100%;
            width: var(--progress, 0%);
            background: currentColor;
            border-radius: 999px;
            content: '';
        }

        /* ===== TRENDS SECTION ===== */
        .casefile-summary-page .casefile-trends-section {
            display: flex;
            flex-direction: column;
            gap: 12px;
            order: -1;
        }

        .casefile-summary-page .casefile-section-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
        }

        .casefile-summary-page .casefile-section-head h2 {
            font-size: 20px;
            line-height: 1.2;
        }

        .casefile-summary-page .casefile-section-head p {
            margin-top: 4px;
            color: var(--inay-muted);
            font-size: 13px;
            font-weight: 600;
        }

        .casefile-summary-page .casefile-trend-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 40px;
            padding: 0 18px;
            color: #ffffff;
            background: var(--inay-pink);
            border: 1px solid var(--inay-pink);
            border-radius: var(--inay-radius);
            font-size: 12px;
            font-weight: 850;
            font-family: inherit;
            cursor: pointer;
            transition: all 160ms ease;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .casefile-summary-page .casefile-trend-action:hover {
            background: #d6076d;
            border-color: #d6076d;
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(236, 10, 120, 0.12);
        }

        /* ===== CHART GRID ===== */
        .casefile-summary-page .casefile-chart-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        .casefile-summary-page .casefile-chart-card {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 16px 20px;
            background: var(--inay-panel);
            border: 1px solid var(--inay-border);
            border-radius: var(--inay-radius);
            box-shadow: var(--inay-shadow);
        }

        .casefile-summary-page .casefile-chart-card > span {
            color: #8797ae;
            font-size: 10px;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .casefile-summary-page .casefile-chart-card h3 {
            margin: 0 0 4px;
            font-size: 17px;
            font-weight: 800;
        }

        /* ===== CHART SVG ===== */
        .casefile-summary-page .casefile-chart-canvas > svg {
            display: block;
            width: 100%;
            height: 250px;
            padding: 8px;
            background: var(--inay-soft);
            border: 1px solid #edf2f7;
            border-radius: var(--inay-radius);
        }

        .casefile-summary-page .casefile-chart-canvas svg .axis {
            stroke: #dce6f1;
            stroke-width: 1.5;
        }

        .casefile-summary-page .casefile-chart-canvas svg .grid {
            stroke: #e8edf5;
            stroke-width: 0.5;
            stroke-dasharray: 4 4;
        }

        .casefile-summary-page .casefile-chart-canvas svg .axis-label {
            fill: #52627a;
            font-size: 10px;
            font-weight: 700;
        }

        .casefile-summary-page .casefile-chart-canvas svg .empty {
            fill: #94a3b8;
            font-size: 14px;
            font-weight: 700;
            text-anchor: middle;
        }

        .casefile-summary-page .casefile-chart-canvas svg .weight-line {
            fill: none;
            stroke: var(--inay-pink);
            stroke-width: 2.5;
            stroke-linejoin: round;
            stroke-linecap: round;
        }

        .casefile-summary-page .casefile-chart-canvas svg .weight-dot {
            fill: var(--inay-pink);
            stroke: #ffffff;
            stroke-width: 2;
        }

        .casefile-summary-page .casefile-chart-canvas svg text {
            font-family: system-ui, -apple-system, sans-serif;
        }

        .casefile-summary-page .casefile-legend {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 700;
            color: var(--inay-muted);
        }

        .casefile-summary-page .casefile-legend .is-pink {
            display: block;
            width: 14px;
            height: 4px;
            background: var(--inay-pink);
            border-radius: 999px;
        }

        /* ===== BP STATUS ===== */
        .casefile-summary-page .casefile-bp-status {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 18px;
            padding: 14px 8px;
            min-height: 277px;
        }

        .casefile-summary-page .casefile-bp-ring {
            display: grid;
            width: 158px;
            height: 158px;
            flex: 0 0 158px;
            place-items: center;
            align-content: center;
            gap: 4px;
            color: #52627a;
            background: #f8fafc;
            border: 12px solid #dbe5f0;
            border-radius: 50%;
            box-shadow: inset 0 0 0 8px #ffffff, 0 14px 24px rgba(15, 23, 42, 0.08);
            text-align: center;
        }

        .casefile-summary-page .casefile-bp-ring [data-bp-status-icon] {
            display: none;
            width: 28px;
            height: 28px;
            place-items: center;
            background: transparent;
            border: 0;
        }

        .casefile-summary-page .casefile-bp-ring svg {
            display: block;
            width: 24px;
            height: 24px;
            max-width: none;
            padding: 0;
            background: transparent;
            border: 0;
            border-radius: 0;
            box-shadow: none;
            fill: none;
            stroke: currentColor;
            stroke-width: 2.2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .casefile-summary-page .casefile-bp-ring strong {
            display: block;
            max-width: 126px;
            color: currentColor;
            font-size: 22px;
            font-weight: 900;
            line-height: 1;
            overflow-wrap: anywhere;
        }

        .casefile-summary-page .casefile-bp-ring span {
            display: block;
            max-width: 110px;
            color: #64748b;
            font-size: 10px;
            font-weight: 900;
            line-height: 1.15;
            text-transform: uppercase;
        }

        .casefile-summary-page .casefile-bp-status.is-normal .casefile-bp-ring {
            color: var(--inay-green);
            background: var(--inay-green-soft);
            border-color: var(--inay-green-border);
        }

        .casefile-summary-page .casefile-bp-status.is-monitoring .casefile-bp-ring {
            color: #c76a06;
            background: #fffbeb;
            border-color: #facc15;
        }

        .casefile-summary-page .casefile-bp-status.is-high-risk .casefile-bp-ring {
            color: #b42318;
            background: #fff7f7;
            border-color: #f87171;
        }

        .casefile-summary-page .casefile-bp-status.is-empty .casefile-bp-ring {
            color: #94a3b8;
            background: #f8fafc;
            border-color: #e2e8f0;
        }

        .casefile-summary-page .casefile-bp-status.is-normal [data-bp-status-icon="normal"],
        .casefile-summary-page .casefile-bp-status.is-monitoring [data-bp-status-icon="monitoring"],
        .casefile-summary-page .casefile-bp-status.is-high-risk [data-bp-status-icon="high-risk"],
        .casefile-summary-page .casefile-bp-status.is-empty [data-bp-status-icon="empty"] {
            display: grid;
        }

        .casefile-summary-page .casefile-bp-copy {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 0;
            max-width: 520px;
            flex: 1 1 auto;
        }

        .casefile-summary-page .casefile-bp-copy small,
        .casefile-summary-page .casefile-bp-copy em {
            color: var(--inay-muted);
            font-size: 12px;
            line-height: 1.45;
            font-style: normal;
            font-weight: 700;
        }

        .casefile-summary-page .casefile-bp-copy b {
            display: inline-flex;
            width: fit-content;
            min-height: 28px;
            align-items: center;
            padding: 4px 12px;
            color: #52627a;
            background: #f1f5f9;
            border: 1px solid #dbe5f0;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .casefile-summary-page .casefile-bp-status.is-normal .casefile-bp-copy b {
            color: #007f5f;
            background: #ecfdf5;
            border-color: #86efc2;
        }

        .casefile-summary-page .casefile-bp-status.is-monitoring .casefile-bp-copy b {
            color: #975a16;
            background: #fffbeb;
            border-color: #fde68a;
        }

        .casefile-summary-page .casefile-bp-status.is-high-risk .casefile-bp-copy b {
            color: #b42318;
            background: #fff7f7;
            border-color: #fecaca;
        }

        .casefile-summary-page .casefile-bp-readings {
            display: grid;
            width: 100%;
            max-width: 420px;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        .casefile-summary-page .casefile-bp-readings span {
            padding: 8px 12px;
            color: #52627a;
            background: var(--inay-soft);
            border: 1px solid #e5edf6;
            border-radius: var(--inay-radius);
            font-size: 11px;
            font-weight: 750;
        }

        .casefile-summary-page .casefile-bp-readings strong {
            color: var(--inay-ink);
            font-size: 12px;
            font-weight: 800;
        }

        .casefile-summary-page .casefile-bp-copy p {
            margin: 0;
            color: #1f2937;
            font-size: 13px;
            line-height: 1.45;
            font-weight: 750;
        }

        /* ===== HISTORY TRIGGER ===== */
        .casefile-summary-page .monitoring-history-trigger {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 42px;
            padding: 0 14px;
            color: var(--inay-pink);
            background: #ffffff;
            border: 1px solid var(--inay-border);
            border-radius: var(--inay-radius);
            font-size: 12px;
            font-weight: 800;
            font-family: inherit;
            cursor: pointer;
            transition: all 160ms ease;
            width: fit-content;
        }

        .casefile-summary-page .monitoring-history-trigger:hover {
            border-color: var(--inay-pink-border);
            background: var(--inay-pink-soft);
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(236, 10, 120, 0.08);
        }

        .casefile-summary-page .monitoring-history-icon {
            display: inline-grid;
            width: 30px;
            height: 30px;
            flex: 0 0 30px;
            place-items: center;
            color: var(--inay-pink);
            background: var(--inay-pink-soft);
            border: 1px solid var(--inay-pink-border);
            border-radius: var(--inay-radius);
        }

        .casefile-summary-page .monitoring-history-icon svg {
            display: block;
            width: 17px;
            height: 17px;
            max-width: none;
            padding: 0;
            background: transparent;
            border: 0;
            border-radius: 0;
            box-shadow: none;
            fill: none;
            stroke: currentColor;
            stroke-width: 2.2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .casefile-summary-page .monitoring-history-trigger b {
            padding: 4px 10px;
            background: var(--inay-pink-soft);
            border: 1px solid var(--inay-pink-border);
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
        }

        /* ===== JOURNEY GRID ===== */
        .casefile-summary-page .casefile-journey-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 10px;
            margin-top: 14px;
        }

        .casefile-summary-page .casefile-journey-grid article {
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-height: 124px;
            padding: 14px 16px;
            background: #ffffff;
            border: 1px solid var(--inay-border);
            border-radius: var(--inay-radius);
            box-shadow: var(--inay-shadow);
        }

        .casefile-summary-page .casefile-journey-grid i {
            display: inline-grid;
            width: 30px;
            height: 30px;
            place-items: center;
            color: #73839b;
            background: #f1f5f9;
            border: 1px solid #dbe5f0;
            border-radius: var(--inay-radius);
        }

        .casefile-summary-page .casefile-journey-grid article.is-complete i {
            color: var(--inay-green);
            background: var(--inay-green-soft);
            border-color: var(--inay-green-border);
        }

        .casefile-summary-page .casefile-journey-grid article.is-current i {
            color: var(--inay-pink);
            background: var(--inay-pink-soft);
            border-color: var(--inay-pink-border);
        }

        .casefile-summary-page .casefile-journey-grid article.is-upcoming i {
            opacity: 0.5;
        }

        .casefile-summary-page .casefile-journey-grid strong {
            margin-top: 4px;
            color: var(--inay-ink);
            font-size: 13px;
            line-height: 1.25;
        }

        .casefile-summary-page .casefile-journey-grid span {
            color: var(--inay-muted);
            font-size: 12px;
            line-height: 1.35;
            font-weight: 650;
        }

        .casefile-summary-page .casefile-journey-grid small {
            color: #6d7e96;
            font-size: 11px;
            font-weight: 700;
        }

        .casefile-summary-page .casefile-journey-grid em {
            display: inline-flex;
            width: fit-content;
            min-height: 22px;
            align-items: center;
            padding: 0 10px;
            color: #52627a;
            background: #f1f5f9;
            border: 1px solid #dbe5f0;
            border-radius: 999px;
            font-size: 10px;
            font-style: normal;
            font-weight: 800;
            text-transform: uppercase;
            margin-top: 4px;
        }

        .casefile-summary-page .casefile-journey-grid article.is-complete em {
            color: var(--inay-green);
            background: var(--inay-green-soft);
            border-color: var(--inay-green-border);
        }

        .casefile-summary-page .casefile-journey-grid article.is-current {
            color: var(--inay-pink);
            background: #ffffff;
            border-color: var(--inay-pink-border);
            box-shadow: inset 0 0 0 2px #ffe6f2;
        }

        .casefile-summary-page .casefile-journey-grid article.is-current em {
            color: var(--inay-pink);
            background: var(--inay-pink-soft);
            border-color: var(--inay-pink-border);
        }

        /* ===== ACTIVITY LIST ===== */
        .casefile-summary-page .casefile-activity-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 14px;
            max-height: none;
            overflow: visible;
        }

        .casefile-summary-page .casefile-activity-list article {
            padding: 14px 18px;
            background: var(--inay-soft);
            border: 1px solid var(--inay-border);
            border-radius: var(--inay-radius);
        }

        .casefile-summary-page .casefile-activity-list article.is-extra {
            display: none;
        }

        .casefile-summary-page .casefile-activity-list.is-expanded article.is-extra {
            display: block;
        }

        .casefile-summary-page .casefile-activity-list strong {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            color: var(--inay-ink);
            font-size: 14px;
            line-height: 1.35;
            font-weight: 800;
        }

        .casefile-summary-page .casefile-activity-list strong span {
            padding: 2px 10px;
            color: var(--inay-pink);
            background: var(--inay-pink-soft);
            border: 1px solid var(--inay-pink-border);
            border-radius: 999px;
            font-size: 10px;
            font-weight: 850;
            text-transform: uppercase;
        }

        .casefile-summary-page .casefile-activity-list p {
            margin-top: 4px;
            color: var(--inay-muted);
            font-size: 13px;
            line-height: 1.45;
            font-weight: 650;
        }

        .casefile-summary-page .casefile-activity-list time {
            display: block;
            margin-top: 4px;
            color: #8a9bb0;
            font-size: 11px;
            font-weight: 700;
        }

        .casefile-summary-page .casefile-activity-more {
            justify-self: start;
            min-height: 38px;
            margin-top: 10px;
            padding: 0 18px;
            color: var(--inay-pink);
            background: #ffffff;
            border: 1px solid var(--inay-pink-border);
            border-radius: var(--inay-radius);
            font-size: 12px;
            font-weight: 850;
            font-family: inherit;
            cursor: pointer;
            transition: all 160ms ease;
        }

        .casefile-summary-page .casefile-activity-more:hover {
            background: var(--inay-pink-soft);
            transform: translateY(-1px);
        }

        /* ===== RECORD ROW ===== */
        .casefile-summary-page .casefile-record-row {
            display: grid;
            grid-template-columns: minmax(180px, 1.25fr) repeat(5, minmax(120px, 1fr)) auto;
            gap: 10px;
            align-items: center;
            padding: 12px 16px;
            background: var(--inay-soft);
            border: 1px solid var(--inay-border);
            border-radius: var(--inay-radius);
            margin-top: 8px;
        }

        .casefile-summary-page .casefile-record-row strong {
            font-size: 13px;
            font-weight: 800;
        }

        .casefile-summary-page .casefile-record-row span {
            font-size: 12px;
            font-weight: 650;
            color: var(--inay-muted);
        }

        .casefile-summary-page .casefile-record-row button {
            min-height: 34px;
            padding: 0 14px;
            color: var(--inay-pink);
            background: var(--inay-pink-soft);
            border: 1px solid var(--inay-pink-border);
            border-radius: var(--inay-radius);
            font-size: 11px;
            font-weight: 800;
            font-family: inherit;
            cursor: pointer;
            transition: all 160ms ease;
        }

        .casefile-summary-page .casefile-record-row button:hover {
            background: var(--inay-pink);
            color: #ffffff;
            border-color: var(--inay-pink);
        }

        /* ===== LEARNING & DOCUMENTS ===== */
        .document-file-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; padding: 0; list-style: none; }
        .document-file-card { width: 100%; height: 100%; display: flex; flex-direction: column; align-items: flex-start; gap: 10px; padding: 20px; background: #fff; border: 1px solid #dbe4ef; border-radius: 14px; text-align: left; color: #0f172a; cursor: pointer; font: inherit; overflow-wrap: anywhere; }
        .document-file-card:hover, .document-file-card:focus-visible { border-color: #ec008c; box-shadow: 0 4px 16px #fce7f3; }
        .document-file-card small { color: #526581; }
        .document-file-card svg { width: 30px; height: 30px; color: #ec008c; }
        .document-preview-dialog { width: min(960px, calc(100vw - 24px)); max-height: calc(100dvh - 24px); margin: auto; padding: 24px; border: 0; border-radius: 18px; box-sizing: border-box; overflow: auto; }
        .document-preview-dialog::backdrop { background: rgba(15, 23, 42, .6); }
        .document-preview-dialog h2, .document-preview-dialog p { overflow-wrap: anywhere; }
        .document-preview-content img { display: block; max-width: 100%; max-height: 65dvh; object-fit: contain; margin: auto; }
        .document-preview-content iframe { width: 100%; height: 60dvh; border: 1px solid #dbe4ef; border-radius: 8px; }
        body.has-document-preview { overflow: hidden; }
        .document-status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin: 16px 0 24px;
        }
        .document-status-card {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 16px;
            border: 1px solid #dbe4ef;
            border-radius: 12px;
            color: #0f172a;
            text-decoration: none;
            background: #f8fafc;
        }
        .document-status-card:hover, .document-status-card:focus-visible {
            border-color: #ec008c;
            outline: 2px solid #fce7f3;
        }
        .document-submission-status {
            display: inline-block;
            width: fit-content;
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 12px;
            font-weight: 700;
            background: #fff7ed;
            color: #9a3412;
        }
        .document-submission-status.is-submitted {
            background: #ecfdf5;
            color: #047857;
        }
        .document-status-card small { color: #526581; }
        .casefile-learning-list li > span { overflow-wrap: anywhere; min-width: 0; }

        .casefile-learning-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin: 14px 0;
        }

        .casefile-learning-summary article {
            padding: 14px 16px;
            background: #f8fafc;
            border: 1px solid #dde6f0;
            border-radius: var(--inay-radius);
        }

        .casefile-learning-summary span {
            display: block;
            color: #7c8ba3;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .casefile-learning-summary strong {
            display: block;
            margin-top: 4px;
            color: #030813;
            font-size: 18px;
            font-weight: 900;
        }

        .casefile-learning-months {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 14px;
        }

        .casefile-learning-month {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 16px 20px;
            background: #ffffff;
            border: 1px solid #dde6f0;
            border-radius: var(--inay-radius);
        }

        .casefile-learning-month.is-complete {
            border-color: #86efac;
            background: #f0fdf4;
        }

        .casefile-learning-month header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .casefile-learning-month h3 {
            margin: 0;
            color: #030813;
            font-size: 15px;
            font-weight: 900;
        }

        .casefile-learning-month p {
            margin: 2px 0 0;
            color: #52627d;
            font-size: 12px;
            font-weight: 800;
        }

        .casefile-learning-badge {
            display: inline-flex;
            align-items: center;
            min-height: 26px;
            padding: 0 12px;
            color: #1d4ed8;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 900;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .casefile-learning-badge.is-complete {
            color: #008f6b;
            background: #dcfce7;
            border-color: #86efac;
        }

        .casefile-learning-progress {
            overflow: hidden;
            height: 8px;
            background: #e8edf5;
            border-radius: 999px;
        }

        .casefile-learning-progress span {
            display: block;
            height: 100%;
            background: #00a680;
            border-radius: inherit;
            transition: width 400ms ease;
        }

        .casefile-learning-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
        }

        .casefile-learning-grid article {
            padding: 10px 14px;
            background: #f8fafc;
            border: 1px solid #e5edf6;
            border-radius: var(--inay-radius);
        }

        .casefile-learning-grid span {
            display: block;
            color: #7c8ba3;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .casefile-learning-grid strong {
            display: block;
            margin-top: 4px;
            color: #030813;
            font-size: 13px;
            font-weight: 900;
        }

        .casefile-learning-grid small {
            display: block;
            margin-top: 2px;
            color: #52627d;
            font-size: 11px;
            font-weight: 700;
        }

        .casefile-learning-list {
            display: grid;
            gap: 8px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .casefile-learning-list li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 14px;
            background: #f8fafc;
            border: 1px solid #e5edf6;
            border-radius: var(--inay-radius);
            color: #030813;
            font-size: 12px;
            font-weight: 800;
            flex-wrap: wrap;
        }

        .casefile-learning-list small {
            color: #52627d;
            font-size: 11px;
            font-weight: 800;
        }

        .casefile-learning-list a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 30px;
            padding: 0 12px;
            color: #ffffff;
            background: #071225;
            border-radius: var(--inay-radius);
            text-decoration: none;
            font-size: 10px;
            font-weight: 900;
            white-space: nowrap;
            transition: background 160ms ease;
        }

        .casefile-learning-list a:hover {
            background: #1a2a3a;
        }

        .casefile-learning-month h4 {
            margin: 8px 0 4px;
            font-size: 13px;
            font-weight: 800;
            color: var(--inay-ink);
        }

        body.has-vital-detail-modal,
        body.has-monitoring-history-modal {
            overflow: hidden;
        }

        /* ===== MODALS ===== */
        .casefile-summary-page .vitals-modal,
        .casefile-summary-page .history-modal,
        .casefile-summary-page .vital-detail-modal {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(7, 18, 37, 0.5);
            backdrop-filter: blur(4px);
        }

        .casefile-summary-page .vitals-modal[hidden],
        .casefile-summary-page .history-modal[hidden],
        .casefile-summary-page .vital-detail-modal[hidden] {
            display: none !important;
        }

        .casefile-summary-page .vitals-modal-backdrop,
        .casefile-summary-page .history-modal-backdrop,
        .casefile-summary-page .vital-detail-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: -1;
        }

        .casefile-summary-page .vitals-dialog,
        .casefile-summary-page .history-dialog,
        .casefile-summary-page .vital-detail-dialog {
            position: relative;
            max-width: 780px;
            width: 100%;
            max-height: 90vh;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 24px 48px rgba(7, 18, 37, 0.25);
            padding: 28px 32px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .casefile-summary-page .vitals-dialog-header,
        .casefile-summary-page .history-dialog-header,
        .casefile-summary-page .vital-detail-dialog-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--inay-border);
            flex-shrink: 0;
        }

        .casefile-summary-page .vitals-dialog-header h2,
        .casefile-summary-page .history-dialog-title,
        .casefile-summary-page .vital-detail-dialog-title {
            margin: 0;
            font-size: 20px;
            font-weight: 900;
            line-height: 1.2;
        }

        .casefile-summary-page .vitals-dialog-header button,
        .casefile-summary-page .history-close,
        .casefile-summary-page .vital-detail-close {
            display: grid;
            width: 44px;
            height: 44px;
            flex: 0 0 44px;
            place-items: center;
            color: #475569;
            background: #f8fafc;
            border: 1px solid #dbe5f1;
            border-radius: 50%;
            padding: 0;
            cursor: pointer;
            font-size: 28px;
            line-height: 1;
            font-family: inherit;
            transition: all 160ms ease;
        }

        .casefile-summary-page .vitals-dialog-header button:hover,
        .casefile-summary-page .history-close:hover,
        .casefile-summary-page .vital-detail-close:hover {
            color: var(--inay-pink);
            background: var(--inay-pink-soft);
            border-color: var(--inay-pink-border);
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(236, 10, 120, 0.1);
        }

        .casefile-summary-page .vital-detail-dialog-body {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .casefile-summary-page .vital-detail-dialog-body .casefile-vital-meta {
            margin-top: 0;
        }

        .casefile-summary-page .vital-detail-dialog-body .casefile-vital-explanation {
            margin: 4px 0 0;
        }

        .casefile-summary-page .vital-detail-dialog-body .casefile-reference-link {
            margin-top: 2px;
        }

        .casefile-summary-page .vital-detail-dialog-body .casefile-vital-foot {
            margin-top: 0;
            padding-top: 8px;
        }

        .casefile-summary-page .history-dialog-copy {
            color: var(--inay-muted);
            font-size: 13px;
            font-weight: 600;
            margin-top: 2px;
        }

        .casefile-summary-page .history-dialog-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            flex-shrink: 0;
            flex-wrap: wrap;
        }

        .casefile-summary-page .history-search {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1 1 auto;
            min-width: 180px;
            padding: 0 12px;
            background: #f8fafc;
            border: 1px solid #dbe5f1;
            border-radius: var(--inay-radius);
        }

        .casefile-summary-page .history-search input {
            flex: 1 1 auto;
            padding: 10px 0;
            border: 0;
            background: transparent;
            font-size: 13px;
            font-family: inherit;
            outline: none;
            min-width: 100px;
        }

        .casefile-summary-page .history-search input::placeholder {
            color: #94a3b8;
        }

        .casefile-summary-page .history-sort {
            padding: 10px 16px;
            color: var(--inay-ink);
            background: #f8fafc;
            border: 1px solid #dbe5f1;
            border-radius: var(--inay-radius);
            font-size: 12px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: all 160ms ease;
            white-space: nowrap;
        }

        .casefile-summary-page .history-sort:hover {
            background: var(--inay-pink-soft);
            border-color: var(--inay-pink-border);
        }

        .casefile-summary-page .history-count-copy {
            color: var(--inay-muted);
            font-size: 12px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .casefile-summary-page .history-dialog-body {
            flex: 1 1 auto;
            overflow-y: auto;
            min-height: 200px;
        }

        .casefile-summary-page .history-empty {
            color: #94a3b8;
            font-size: 14px;
            font-weight: 700;
            text-align: center;
            padding: 40px 0;
        }

        .casefile-summary-page .history-table-scroll {
            overflow-x: auto;
        }

        .casefile-summary-page .history-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .casefile-summary-page .history-table th {
            text-align: left;
            padding: 10px 12px;
            font-size: 11px;
            font-weight: 850;
            text-transform: uppercase;
            color: #8797ae;
            border-bottom: 2px solid var(--inay-border);
            background: #f8fafc;
            white-space: nowrap;
        }

        .casefile-summary-page .history-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e8edf5;
            vertical-align: middle;
        }

        .casefile-summary-page .history-table td.is-main {
            font-weight: 700;
        }

        .casefile-summary-page .history-table td.is-red {
            color: #dc2626;
            font-weight: 700;
        }

        .casefile-summary-page .history-table td.is-blue {
            color: #2563eb;
            font-weight: 700;
        }

        .casefile-summary-page .history-status {
            display: inline-flex;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .casefile-summary-page .history-status.is-within-reference-range {
            color: #007f5f;
            background: #ecfdf5;
        }

        .casefile-summary-page .history-status.is-for-review {
            color: #975a16;
            background: #fffbeb;
        }

        .casefile-summary-page .history-status.is-for-professional-interpretation {
            color: #1d4ed8;
            background: #eff6ff;
        }

        .casefile-summary-page .history-status.is-urgent-referral-recommended {
            color: #b42318;
            background: #fff7f7;
        }

        .casefile-summary-page .history-status.is-logged {
            color: #52627a;
            background: #f1f5f9;
        }

        .casefile-summary-page .history-row-actions {
            display: flex;
            gap: 6px;
        }

        .casefile-summary-page .history-row-actions button {
            min-height: 30px;
            padding: 0 12px;
            border-radius: var(--inay-radius);
            font-size: 10px;
            font-weight: 800;
            font-family: inherit;
            cursor: pointer;
            transition: all 160ms ease;
            border: 1px solid transparent;
        }

        .casefile-summary-page .history-row-actions .is-edit {
            color: #1d4ed8;
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .casefile-summary-page .history-row-actions .is-edit:hover {
            background: #1d4ed8;
            color: #ffffff;
        }

        .casefile-summary-page .history-row-actions .is-delete {
            color: #b42318;
            background: #fff7f7;
            border-color: #fecaca;
        }

        .casefile-summary-page .history-row-actions .is-delete:hover {
            background: #b42318;
            color: #ffffff;
        }

        body.has-mother-information-dialog {
            overflow: hidden;
        }

        .casefile-summary-page .mother-information-dialog {
            width: min(720px, calc(100vw - 32px));
            max-height: calc(100dvh - 32px);
            margin: auto;
            padding: 24px;
            border: 1px solid #dbe4ef;
            border-radius: 20px;
            background: #fff;
            color: #0f172a;
            box-shadow: 0 24px 80px rgba(15, 23, 42, .25);
            overflow-y: auto;
            box-sizing: border-box;
        }

        .mother-information-dialog::backdrop {
            background: rgba(15, 23, 42, .55);
        }

        /* ===== VITALS FORM ===== */
        .casefile-summary-page .vitals-warning {
            display: flex;
            gap: 12px;
            padding: 14px 18px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: var(--inay-radius);
            margin-bottom: 16px;
            flex-shrink: 0;
        }

        .casefile-summary-page .vitals-warning svg {
            flex: 0 0 20px;
            color: #d97706;
        }

        .casefile-summary-page .vitals-warning strong {
            font-size: 13px;
            font-weight: 800;
            color: #92400e;
        }

        .casefile-summary-page .vitals-warning ul {
            margin: 4px 0 0;
            padding-left: 20px;
            color: #78350f;
            font-size: 13px;
        }

        .casefile-summary-page .vitals-warning p {
            margin-top: 6px;
            font-size: 12px;
            color: #78350f;
            font-weight: 600;
        }

        .casefile-summary-page .vitals-field-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
            flex: 1 1 auto;
        }

        .casefile-summary-page .vitals-field-grid label {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .casefile-summary-page .vitals-field-grid label.is-wide {
            grid-column: 1 / -1;
        }

        .casefile-summary-page .vitals-field-grid span {
            font-size: 12px;
            font-weight: 800;
            color: #334155;
        }

        .casefile-summary-page .vitals-field-grid input,
        .casefile-summary-page .vitals-field-grid select,
        .casefile-summary-page .vitals-field-grid textarea {
            padding: 9px 12px;
            font-size: 13px;
            font-family: inherit;
            border: 1px solid #dbe5f1;
            border-radius: var(--inay-radius);
            background: #ffffff;
            transition: border-color 160ms ease;
            width: 100%;
        }

        .casefile-summary-page .vitals-field-grid input:focus,
        .casefile-summary-page .vitals-field-grid select:focus,
        .casefile-summary-page .vitals-field-grid textarea:focus {
            outline: none;
            border-color: var(--inay-pink);
            box-shadow: 0 0 0 3px rgba(236, 10, 120, 0.12);
        }

        .casefile-summary-page .height-controls {
            display: grid;
            grid-template-columns: minmax(86px, 0.34fr) minmax(0, 1fr);
            gap: 8px;
            min-width: 0;
        }

        .casefile-summary-page .height-ft-in {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            min-width: 0;
        }

        .casefile-summary-page .height-ft-in[hidden] {
            display: none;
        }

        .casefile-summary-page .vitals-field-grid input[readonly] {
            color: #52627d;
            background: #f8fafc;
            cursor: not-allowed;
        }

        .casefile-summary-page .vitals-field-grid textarea {
            resize: vertical;
            min-height: 80px;
        }

        .casefile-summary-page .vitals-field-grid small {
            color: #dc2626;
            font-size: 11px;
            font-weight: 700;
            min-height: 16px;
        }

        .casefile-summary-page .vitals-field-grid label.has-error input,
        .casefile-summary-page .vitals-field-grid label.has-error select,
        .casefile-summary-page .vitals-field-grid label.has-error textarea {
            border-color: #dc2626;
        }

        .casefile-summary-page .vitals-dialog-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid var(--inay-border);
            flex-shrink: 0;
        }

        .casefile-summary-page .vitals-cancel {
            padding: 10px 20px;
            color: var(--inay-muted);
            background: transparent;
            border: 1px solid var(--inay-border);
            border-radius: var(--inay-radius);
            font-size: 13px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: all 160ms ease;
        }

        .casefile-summary-page .vitals-cancel:hover {
            background: #f1f5f9;
        }

        .casefile-summary-page .vitals-save {
            padding: 10px 24px;
            color: #ffffff;
            background: var(--inay-pink);
            border: 1px solid var(--inay-pink);
            border-radius: var(--inay-radius);
            font-size: 13px;
            font-weight: 800;
            font-family: inherit;
            cursor: pointer;
            transition: all 160ms ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .casefile-summary-page .vitals-save:hover:not(:disabled) {
            background: #d6076d;
            border-color: #d6076d;
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(236, 10, 120, 0.12);
        }

        .casefile-summary-page .vitals-save:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .casefile-summary-page .vitals-spinner {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: vitals-spin 0.7s linear infinite;
        }

        .casefile-summary-page .vitals-spinner[hidden] {
            display: none !important;
        }

        @keyframes vitals-spin {
            to { transform: rotate(360deg); }
        }

        .casefile-summary-page .vitals-success {
            padding: 14px 20px;
            color: #007f5f;
            background: #ecfdf5;
            border: 1px solid #86efc2;
            border-radius: var(--inay-radius);
            font-weight: 700;
        }

        .casefile-summary-page .vitals-success[hidden] {
            display: none !important;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1280px) {
            .casefile-summary-page .casefile-status-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .casefile-summary-page .casefile-vital-grid.is-large {
                grid-template-columns: repeat(2, 1fr);
            }
            .casefile-summary-page .casefile-progress-cards {
                grid-template-columns: repeat(2, 1fr);
            }
            .casefile-summary-page .casefile-journey-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            .casefile-summary-page .casefile-learning-summary {
                grid-template-columns: repeat(2, 1fr);
            }
            .casefile-summary-page .casefile-profile-facts {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 1024px) {
            .casefile-summary-page .casefile-chart-grid {
                grid-template-columns: 1fr;
            }
            .casefile-summary-page .casefile-contact-row {
                grid-template-columns: 1fr;
            }
            .casefile-summary-page .casefile-reference-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .casefile-summary-page {
                padding: 14px 16px 32px;
                gap: 12px;
            }

            .casefile-summary-page .casefile-detail-heading {
                flex-direction: column;
                align-items: stretch;
            }

            .casefile-summary-page .casefile-back-link {
                justify-content: center;
            }

            .casefile-summary-page .casefile-profile-main {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .casefile-summary-page .casefile-profile-title {
                justify-content: center;
            }

            .casefile-summary-page .casefile-profile-facts {
                grid-template-columns: 1fr 1fr;
            }

            .casefile-summary-page .casefile-status-grid,
            .casefile-summary-page .casefile-progress-cards,
            .casefile-summary-page .casefile-learning-summary {
                grid-template-columns: 1fr;
            }

            .casefile-summary-page .casefile-status-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }

            .casefile-summary-page .casefile-status-grid article {
                min-height: 108px;
                padding: 13px 12px;
            }

            .casefile-summary-page .casefile-status-grid article:last-child em {
                display: none;
            }

            .casefile-summary-page .casefile-profile-actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
            }

            .casefile-summary-page .casefile-profile-actions button {
                width: 100%;
                min-height: 42px;
            }

            .casefile-summary-page .casefile-tabs {
                padding: 4px;
                scrollbar-width: none;
            }

            .casefile-summary-page .casefile-tabs::-webkit-scrollbar {
                display: none;
            }

            .casefile-summary-page .casefile-tabs button {
                min-width: 120px;
                min-height: 40px;
                padding: 6px 12px;
            }

            .casefile-summary-page .casefile-panel {
                padding: 16px;
            }

            .casefile-summary-page .casefile-panel-title {
                flex-direction: column;
                align-items: stretch;
            }

            .casefile-summary-page .casefile-panel-title button {
                width: 100%;
                justify-content: center;
            }


            /* =========================================================
               MATERNAL VITAL SIGNS - MOBILE BOX LAYOUT
               Two compact cards per row instead of tall full-width cards.
            ========================================================= */
            .casefile-summary-page .casefile-vitals-panel {
                padding: 14px;
            }

            .casefile-summary-page .casefile-vitals-panel .casefile-panel-title {
                margin-bottom: 12px;
                padding-bottom: 12px;
            }

            .casefile-summary-page .casefile-vital-grid.is-large {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
                width: 100%;
            }

            .casefile-summary-page .casefile-vital-grid.is-large article {
                min-width: 0;
                min-height: 158px;
                height: auto;
                padding: 11px;
                gap: 6px;
                background: #ffffff;
                border: 1px solid var(--inay-border);
                border-radius: 12px;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
                overflow: hidden;
                display: flex;
                flex-direction: column;
                align-items: stretch;
            }

            .casefile-summary-page .casefile-vital-grid.is-large article .casefile-vital-head {
                width: 100%;
                min-width: 0;
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 6px;
            }

            .casefile-summary-page .casefile-vital-grid.is-large article .casefile-vital-head i {
                width: 30px;
                height: 30px;
                min-width: 30px;
                flex: 0 0 30px;
            }

            .casefile-summary-page .casefile-vital-grid.is-large article .casefile-vital-head i svg {
                width: 15px;
                height: 15px;
            }

            .casefile-summary-page .casefile-vital-grid.is-large article .casefile-vital-head b {
                min-width: 0;
                max-width: calc(100% - 36px);
                min-height: 24px;
                padding: 4px 7px;
                font-size: 7px;
                line-height: 1.15;
                text-align: center;
                white-space: normal;
                overflow-wrap: anywhere;
                word-break: normal;
                justify-content: center;
            }

            .casefile-summary-page .casefile-vital-grid.is-large article > span {
                display: block;
                margin-top: 1px;
                color: #6d7e96;
                font-size: 9px;
                font-weight: 850;
                line-height: 1.2;
                text-transform: uppercase;
                letter-spacing: 0.04em;
            }

            .casefile-summary-page .casefile-vital-grid.is-large article > strong {
                display: block;
                min-width: 0;
                color: var(--inay-ink);
                font-size: clamp(17px, 5vw, 21px);
                line-height: 1.12;
                font-weight: 850;
                overflow-wrap: anywhere;
            }

            /* Keep the long professional explanation out of the small card. */
            .casefile-summary-page .casefile-vital-grid.is-large article .casefile-vital-details {
                display: none !important;
            }

            .casefile-summary-page .casefile-vital-grid.is-large article .casefile-vital-toggle-details {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                align-self: flex-start;
                min-height: 30px;
                margin-top: auto;
                padding: 5px 10px;
                color: var(--inay-pink);
                background: var(--inay-pink-soft);
                border: 1px solid var(--inay-pink-border);
                border-radius: 999px;
                font-family: inherit;
                font-size: 9px;
                font-weight: 850;
                line-height: 1.2;
                cursor: pointer;
                transition: background 160ms ease, color 160ms ease, border-color 160ms ease, transform 160ms ease;
            }

            .casefile-summary-page .casefile-vital-grid.is-large article .casefile-vital-toggle-details:hover,
            .casefile-summary-page .casefile-vital-grid.is-large article .casefile-vital-toggle-details:focus-visible {
                color: #ffffff;
                background: var(--inay-pink);
                border-color: var(--inay-pink);
                outline: none;
            }

            .casefile-summary-page .casefile-vital-grid.is-large article .casefile-vital-toggle-details:active {
                transform: scale(0.97);
            }

            /* Make the mobile details modal feel like a phone sheet/card. */
            .casefile-summary-page .vital-detail-modal {
                padding: 12px;
                align-items: flex-end;
            }

            .casefile-summary-page .vital-detail-dialog {
                width: 100%;
                max-width: 520px;
                max-height: 82vh;
                padding: 18px;
                border-radius: 16px;
            }

            .casefile-summary-page .vital-detail-dialog-header {
                margin-bottom: 14px;
                padding-bottom: 12px;
            }

            .casefile-summary-page .vital-detail-dialog-title {
                font-size: 18px;
            }

            .casefile-summary-page .vital-detail-close {
                width: 38px;
                height: 38px;
                flex-basis: 38px;
                font-size: 24px;
            }

            .casefile-summary-page .casefile-section-head {
                flex-direction: column;
                align-items: stretch;
            }

            .casefile-summary-page .casefile-trend-action {
                width: 100%;
                justify-content: center;
            }

            .casefile-summary-page .casefile-journey-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }

            .casefile-summary-page .casefile-journey-grid article {
                min-height: 0;
                padding: 12px 10px;
            }

            .casefile-summary-page .casefile-bp-status {
                flex-direction: column;
                align-items: center;
                justify-content: center;
                min-height: 0;
                text-align: center;
            }

            .casefile-summary-page .casefile-bp-ring {
                width: 132px;
                height: 132px;
                flex-basis: 132px;
            }

            .casefile-summary-page .casefile-bp-copy {
                width: 100%;
                align-items: center;
            }

            .casefile-summary-page .casefile-bp-ring strong {
                max-width: 108px;
                font-size: 19px;
            }

            .casefile-summary-page .casefile-record-row {
                grid-template-columns: 1fr;
                gap: 6px;
            }

            .casefile-summary-page .casefile-chart-canvas > svg {
                height: 200px;
            }

            .casefile-summary-page .vitals-dialog,
            .casefile-summary-page .history-dialog,
            .casefile-summary-page .vital-detail-dialog {
                padding: 20px;
                max-height: 95vh;
            }

            .casefile-summary-page .vitals-field-grid {
                grid-template-columns: 1fr;
            }

            .casefile-summary-page .monitoring-history-trigger {
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
            }

            .casefile-summary-page .casefile-contact-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .casefile-summary-page .casefile-vital-grid.is-large {
                gap: 8px;
            }

            .casefile-summary-page .casefile-vital-grid.is-large article {
                min-height: 152px;
                padding: 10px;
            }

            .casefile-summary-page .casefile-vital-grid.is-large article .casefile-vital-head b {
                font-size: 6.5px;
                padding: 4px 6px;
            }

            .casefile-summary-page .casefile-profile-actions {
                grid-template-columns: 1fr;
            }

            .casefile-summary-page .casefile-profile-facts {
                grid-template-columns: 1fr;
            }

            .casefile-summary-page .casefile-learning-summary,
            .casefile-summary-page .casefile-learning-grid {
                grid-template-columns: 1fr;
            }

            .casefile-summary-page .casefile-learning-month header {
                flex-direction: column;
                align-items: stretch;
            }

            .casefile-summary-page .casefile-bp-readings {
                width: 100%;
                max-width: 320px;
                grid-template-columns: 1fr;
            }

            .casefile-summary-page .casefile-bp-readings span {
                text-align: center;
            }

            .casefile-summary-page .casefile-reference-grid {
                gap: 8px;
            }

            .casefile-summary-page .casefile-reference-grid a {
                padding: 11px 12px;
            }

            .casefile-summary-page .casefile-reference-grid strong {
                font-size: 11px;
            }

            .casefile-summary-page .casefile-reference-grid span {
                font-size: 10px;
                line-height: 1.45;
            }
        }

        @media (max-width: 339px) {
            .casefile-summary-page .casefile-vital-grid.is-large {
                grid-template-columns: 1fr;
            }

            .casefile-summary-page .casefile-vital-grid.is-large article {
                min-height: 145px;
            }
        }

        /* ===== PRINT STYLES ===== */
        @media print {
            .casefile-summary-page {
                padding: 0;
                gap: 12px;
                max-width: 100%;
            }

            .casefile-summary-page .casefile-profile-actions,
            .casefile-summary-page .casefile-tabs,
            .casefile-summary-page .casefile-trend-action,
            .casefile-summary-page .monitoring-history-trigger,
            .casefile-summary-page .casefile-activity-more,
            .casefile-summary-page .casefile-panel-title button,
            .casefile-summary-page .vitals-modal,
            .casefile-summary-page .history-modal,
            .casefile-summary-page .vital-detail-modal,
            .casefile-summary-page .casefile-record-row button,
            .casefile-summary-page .history-row-actions {
                display: none !important;
            }

            .casefile-summary-page .casefile-detail-heading {
                border-bottom-color: #000;
            }

            .casefile-summary-page .casefile-profile-card,
            .casefile-summary-page .casefile-panel,
            .casefile-summary-page .casefile-chart-card,
            .casefile-summary-page .casefile-status-grid article,
            .casefile-summary-page .casefile-progress-cards article {
                box-shadow: none !important;
                border-color: #ddd !important;
            }

            .casefile-summary-page .casefile-activity-list article.is-extra {
                display: block !important;
            }

            .casefile-summary-page .casefile-safety-notice {
                background: #f8fafc !important;
                border-color: #ddd !important;
            }

            .casefile-summary-page .casefile-chart-canvas > svg {
                height: 200px !important;
            }

            .casefile-summary-page .casefile-bp-ring {
                border-color: #ccc !important;
            }

            .casefile-summary-page .vitals-success,
            .casefile-summary-page .vitals-warning {
                display: none !important;
            }

            .casefile-summary-page [data-casefile-panel][hidden] {
                display: block !important;
            }

        }
/* =========================================================
   CENTER MATERNAL VITAL DETAILS MODAL ON MOBILE
========================================================= */
@media (max-width: 768px) {

    .casefile-summary-page .vital-detail-modal {
        position: fixed;
        inset: 0;
        z-index: 9999;

        display: flex;
        align-items: center;
        justify-content: center;

        padding: 20px;
    }

    /* Dark/blurred background */
    .casefile-summary-page .vital-detail-modal-backdrop {
        position: absolute;
        inset: 0;
        z-index: 0;

        background: rgba(7, 18, 37, 0.45);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
    }

    /* Actual details box */
    .casefile-summary-page .vital-detail-dialog {
        position: relative;
        z-index: 1;

        width: 100%;
        max-width: 380px;
        max-height: 80vh;

        margin: 0;
        padding: 18px;

        background: #ffffff;

        border-radius: 16px;
        border: 1px solid #e2e8f0;

        box-shadow:
            0 24px 60px rgba(15, 23, 42, 0.25),
            0 8px 24px rgba(15, 23, 42, 0.12);

        overflow-y: auto;
    }

    /* Header */
    .casefile-summary-page .vital-detail-dialog-header {
        display: flex;
        align-items: center;
        justify-content: space-between;

        gap: 12px;

        margin-bottom: 14px;
        padding-bottom: 14px;

        border-bottom: 1px solid #e5edf6;
    }

    .casefile-summary-page .vital-detail-dialog-title {
        margin: 0;

        font-size: 17px;
        line-height: 1.2;
        font-weight: 900;

        color: #9d3668;
    }

    /* Close button */
    .casefile-summary-page .vital-detail-close {
        width: 38px;
        height: 38px;
        flex: 0 0 38px;

        display: grid;
        place-items: center;

        padding: 0;

        font-size: 24px;

        color: #475569;
        background: #f8fafc;

        border: 1px solid #dbe5f1;
        border-radius: 50%;

        cursor: pointer;
    }

    /* Detail body */
    .casefile-summary-page .vital-detail-dialog-body {
        display: flex;
        flex-direction: column;
        gap: 10px;

        font-size: 12px;
        line-height: 1.45;
    }

    /* Keep hidden modal hidden */
    .casefile-summary-page .vital-detail-modal[hidden] {
        display: none !important;
    }
}
    </style>
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/maternal-graphs.css') }}?v={{ filemtime(public_path('css/maternal-graphs.css')) }}">
@endpush

@section('content')
@if($mother->is_4ps_beneficiary)
<div class="account-card"><h2>F1KD compliance monitoring</h2><p>Verify monthly 4Ps service compliance.</p>
@if($mother->pregnancy_status === 'pregnant')<a class="account-button" href="{{ route('staff.f1kd.edit', ['subject'=>'mother-'.$mother->id]) }}">Pregnant woman checklist</a>@endif
@foreach($mother->infants as $f1kdChild)
@if($f1kdChild->birth_date && $f1kdChild->birth_date->lte(today()) && $f1kdChild->birth_date->gt(today()->subMonthsNoOverflow(25)))
<a class="account-button" href="{{ route('staff.f1kd.edit', ['subject'=>'child-'.$f1kdChild->id]) }}">{{ $f1kdChild->full_name }} / F1KD checklist</a>
@endif
@endforeach</div>
@endif

    <style>
        /* Use the neonatal profile's compact, borderless label/value layout. */
        .casefile-summary-page .casefile-profile-card { padding: 22px; gap: 20px; }
        .casefile-summary-page .casefile-profile-main { margin: 0; }
        .casefile-summary-page .casefile-contact-row {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px 20px;
            margin: 0;
        }
        .casefile-summary-page .casefile-profile-facts {
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 18px 20px;
        }
        .casefile-summary-page .casefile-contact-row article,
        .casefile-summary-page .casefile-profile-facts > div {
            min-width: 0;
            padding: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
        }
        .casefile-summary-page .casefile-contact-row span,
        .casefile-summary-page .casefile-profile-facts dt {
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
            text-transform: none;
            letter-spacing: 0;
        }
        .casefile-summary-page .casefile-contact-row strong,
        .casefile-summary-page .casefile-profile-facts dd {
            color: #17233b;
            font-size: 16px;
            font-weight: 700;
            line-height: 1.4;
        }
        .casefile-summary-page .casefile-contact-row article > div { gap: 4px; }
        .casefile-summary-page .casefile-profile-card [hidden] { display: none !important; }
        @media (max-width: 1000px) {
            .casefile-summary-page .casefile-profile-facts { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (max-width: 640px) {
            .casefile-summary-page .casefile-profile-card { padding: 16px; }
            .casefile-summary-page .casefile-profile-facts { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .casefile-summary-page .casefile-contact-row { grid-template-columns: minmax(0, 1fr); }
        }
    </style>
    <section class="casefile-summary-page" aria-label="Mother care summary">
        <!-- ===== HEADER ===== -->
        <header class="casefile-detail-heading">
            <div>
                <nav class="casefile-breadcrumb" aria-label="Breadcrumb">
                    <a href="{{ route('staff.mothers') }}">{!! $iconArrowLeft !!} Mothers Casefiles</a>
                    {!! $iconChevron !!}
                    <span>{{ $caseId }}</span>
                </nav>
                <h1>Mother Care Summary</h1>
                <p>A simplified view of clinical status, care progress, and patient records.</p>
            </div>
            <a class="casefile-back-link" href="{{ route('staff.mothers') }}">{!! $iconArrowLeft !!} Back to Casefiles</a>
        </header>

        <!-- ===== TABS ===== -->
        <div class="casefile-tabs" role="tablist" aria-label="Casefile sections">
            <button class="is-active" type="button" role="tab" aria-selected="true" data-casefile-tab="overview"><strong>Overview</strong><small>Summary</small></button>
            <button type="button" role="tab" aria-selected="false" data-casefile-tab="monitoring"><strong>Monitoring</strong><small><span data-monitoring-count>{{ $records->count() }}</span> {{ $records->count() === 1 ? 'record' : 'records' }}</small></button>
            <button type="button" role="tab" aria-selected="false" data-casefile-tab="learning-documents"><strong>Learning & Documents</strong><small>{{ $uploads->count() }} document{{ $uploads->count() === 1 ? '' : 's' }} received</small></button>
            <button type="button" role="tab" aria-selected="false" data-casefile-tab="documents"><strong>Documents</strong><small>{{ $uploads->count() }} files · View & download</small></button>
            <button type="button" role="tab" aria-selected="false" data-casefile-tab="notes"><strong>Notes</strong><small><span>{{ $latestRecord?->notes ? 1 : 0 }}</span> {{ $latestRecord?->notes ? 'entry' : 'entries' }}</small></button>
        </div>

        <!-- ===== PROFILE CARD ===== -->
        <section class="casefile-profile-card">
            <div class="casefile-profile-header">
                <p class="casefile-profile-kicker">Mother Profile Summary</p>
                <span class="casefile-risk {{ $riskClass }}" data-risk-label>{{ $riskLabel }}</span>
            </div>

            <div class="casefile-profile-main">
                <div class="casefile-avatar-wrapper">
                    <span class="casefile-avatar is-xl">{{ $initials }}</span>
                </div>

                <div class="casefile-profile-info">
                    <div class="casefile-profile-title">
                        <h2>{{ $mother->full_name }}</h2>
                    </div>
                    <strong class="casefile-id">{{ $caseId }}</strong>

                </div>
            </div>

            <div class="casefile-contact-row">
                <article>
                    {!! $iconPhone !!}
                    <div>
                        <span>Mother Contact</span>
                        <strong>{{ $mother->contact_number ?: 'Phone not provided' }}</strong>
                    </div>
                </article>
                <article>
                    {!! $iconMap !!}
                    <div>
                        <span>Barangay</span>
                        <strong>{{ $mother->barangay ?: 'No address recorded' }}</strong>
                    </div>
                </article>
                <article>
                    {!! $iconUser !!}
                    <div>
                        <span>Assigned Program Staff</span>
                        <strong>{{ $staff->full_name }}</strong>
                    </div>
                </article>
            </div>

            <dl class="casefile-profile-facts">
                <div><dt>Age</dt><dd>{{ $mother->age ? $mother->age.' years old' : 'Not provided' }}</dd></div>
                <div><dt>Obstetric History</dt><dd>{{ $obstetricHistory }}</dd></div>
                <div><dt>Blood Type</dt><dd>{{ $mother->blood_type ?: 'Unknown' }}</dd></div>
                <div><dt>Civil Status</dt><dd>{{ $mother->civil_status ?: 'Not provided' }}</dd></div>
                <div>
                    <dt>Maternal Age Risk</dt>
                    <dd>{{ $maternalAgeRisk }}</dd>
                </div>
                <div><dt>Pregnancy Status</dt><dd>{{ $statusLabels[$mother->pregnancy_status] ?? 'Not provided' }}</dd></div>
                <div><dt>Current Trimester</dt><dd>{{ $trimester }}</dd></div>
                <div><dt>4Ps Status</dt><dd>{{ $fourPsLabel }}</dd></div>
                <div><dt>Latest Vitals</dt><dd>{{ $latestRecordedLabel }}</dd></div>
                <div><dt>Monitoring Records</dt><dd>{{ $records->count() }} {{ $records->count() === 1 ? 'record' : 'records' }}</dd></div>
            </dl>

            <div class="casefile-profile-actions">
                <button type="button" data-mother-edit aria-haspopup="dialog" aria-controls="mother-information-dialog" aria-expanded="{{ $errors->motherInformation->any() ? 'true' : 'false' }}">{!! $iconEdit !!} Edit Information</button>
                <button type="button" data-casefile-record="print" data-record-url="{{ route('staff.mothers.print', $mother) }}">{!! $iconPrinter !!} Print Record</button>
                <button type="button" class="is-dark" data-casefile-record="pdf" data-record-url="{{ route('staff.mothers.pdf', $mother) }}">{!! $iconDownload !!} Export PDF</button>
            </div>
            <p class="vitals-warning" data-record-error role="alert" hidden></p>
            <p data-record-ready role="status" hidden><span data-record-status></span> <a data-record-open target="_blank" rel="noopener">Open generated record</a></p>

            @if (session('status'))
                <p role="status">{{ session('status') }}</p>
            @endif
            @include('partials.mother-information-form')

        </section>

        <!-- ===== STATUS GRID ===== -->
        <div class="casefile-status-grid">
            <article class="is-pink"><span>Pregnancy Status</span><strong>{{ $statusLabels[$mother->pregnancy_status] ?? 'Not provided' }}</strong><small>{{ $trimester }}</small>{!! $iconHeart !!}</article>
            <article class="{{ $riskCardClass }}" data-risk-card><span>Screening Status</span><strong data-summary-risk>{{ $riskLabel }}</strong><small data-risk-date>{{ $riskDateCopy }}</small>{!! $iconShield !!}</article>
            <article class="{{ $mother->is_4ps_beneficiary ? 'is-green' : 'is-neutral' }}"><span>4Ps Status</span><strong>{{ $fourPsLabel }}</strong><small>{{ $fourPsCopy }}</small>{!! $iconUser !!}</article>
            <article class="is-green"><span>Learning Progress</span><strong>{{ $learningPercent }}%</strong><small>{{ $learningCompleted }}/{{ $learningTotal }} months completed</small><em style="--progress: {{ $learningPercent }}%"></em>{!! $iconFile !!}</article>
        </div>


        <!-- ===== OVERVIEW PANEL ===== -->
        <section data-casefile-panel="overview">
            <!-- Vitals Panel -->
            <section class="casefile-panel casefile-vitals-panel">
                <div class="casefile-panel-title">
                    <div>
                        <h2>Maternal Vital Signs</h2>
                        <p>Latest pregnancy threshold indicators for {{ $mother->full_name }}</p>
                    </div>
                    <button type="button" data-vitals-open>{!! $iconPulse !!} Update Vitals</button>
                </div>
                <div class="casefile-vital-grid is-large">
                    @foreach($vitalCards as $card)
                        @php
                            $cardStatus = $vitalStatus($card['key']);
                            $cardGuideline = $vitalGuideline($card['key']);
                        @endphp
                        <article data-vital-card="{{ $card['key'] }}">
                            <!-- Always visible (compact) part -->
                            <div class="casefile-vital-head">
                                <i class="{{ $card['tone'] }}">{!! $card['icon'] !!}</i>
                                <b data-vital-status="{{ $card['key'] }}" data-status-tone="{{ \App\Support\MaternalVitalScreening::statusSlug($cardStatus) }}">{{ $cardStatus }}</b>
                            </div>
                            <span>{{ $card['title'] }}</span>
                            <strong data-vital-value="{{ $card['key'] }}">{{ $card['value'] }}</strong>

                            <!-- Toggle button (mobile only) – opens modal -->
                            <button type="button" class="casefile-vital-toggle-details" data-vital-toggle="{{ $card['key'] }}">
                                View Details
                            </button>

                            <!-- Detailed content – hidden on mobile, visible on desktop -->
                            <div class="casefile-vital-details">
                                <div class="casefile-vital-meta">
                                    <small data-vital-date="{{ $card['key'] }}">{{ $vitalDateLabels[$card['key']] }}</small>
                                    <small data-vital-week="{{ $card['key'] }}">Pregnancy week {{ $pregnancyWeek ?: 'N/A' }}</small>
                                    <small data-vital-unit="{{ $card['key'] }}">Unit: {{ $card['unit'] }}</small>
                                    <small data-vital-test-type="{{ $card['key'] }}">Test type: {{ $card['test_type'] }}</small>
                                </div>
                                <p class="casefile-vital-explanation" data-vital-explanation="{{ $card['key'] }}">{{ $vitalExplanation($card['key']) }}</p>
                                @if(! empty($cardGuideline['source_url']))
                                    <a class="casefile-reference-link" data-vital-reference="{{ $card['key'] }}" href="{{ $cardGuideline['source_url'] }}" target="_blank" rel="noopener">View Reference</a>
                                @else
                                    <span class="casefile-reference-link" data-vital-reference="{{ $card['key'] }}">Reference pending</span>
                                @endif
                                <div class="casefile-vital-foot">
                                    <small data-vital-guideline="{{ $card['key'] }}">{{ $cardGuideline['name'] ?? 'Facility-configurable screening rule' }} / {{ $cardGuideline['version'] ?? 'Pending validation' }}</small>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="casefile-safety-notice">{!! $iconShield !!} {{ $safetyNotice }}</div>
            </section>

            <!-- Journey Section -->
            <section class="casefile-panel">
                <p class="casefile-section-kicker">Pregnancy Timeline</p>
                <h2>Care Journey</h2>
                <p class="casefile-section-subtitle">Registration, trimester progression, delivery, and postpartum milestones.</p>
                <div class="casefile-journey-grid">
                    @foreach ($journeyStages as $stage)
                        @php $stageStatus = $journeyStatus($stage['key']); @endphp
                        <article class="is-{{ $stageStatus }}">
                            <i>{!! $stage['icon'] !!}</i>
                            <strong>{{ $stage['title'] }}</strong>
                            <span>{{ $stage['description'] }}</span>
                            <small>{{ $stage['date'] }}</small>
                            <em>{{ $stageStatus === 'complete' ? 'Completed' : ucfirst($stageStatus) }}</em>
                        </article>
                    @endforeach
                </div>
            </section>

            <!-- Progress Section -->
            <section class="casefile-progress-section">
                <p>Statistics</p>
                <h2>Maternal Health Progress</h2>
                <span>Charts and indicators update from stored monitoring records and uploaded documents.</span>
                <div class="casefile-progress-cards">
                    <article><span>Prenatal Visit Completion</span><strong data-visit-count>{{ $visitCount }}/8</strong><small data-visit-copy>{{ $visitPercent }}% of expected visits logged</small><em data-visit-progress style="--progress: {{ $visitPercent }}%"></em>{!! $iconCalendar !!}</article>
                    <article><span>Vaccination Status</span><strong>Pending</strong><small>No maternal vaccine record available</small><em style="--progress: 0%"></em>{!! $iconCalendar !!}</article>
                    <article><span>Care Completion</span><strong>{{ $completion }}%</strong><small>{{ $uploadedFileCount }} uploaded {{ $uploadedFileCount === 1 ? 'file' : 'files' }} included</small><em style="--progress: {{ $completion }}%"></em>{!! $iconPulse !!}</article>
                </div>
            </section>

            <!-- Trends Section -->
            <section class="casefile-trends-section">
                <div class="casefile-section-head">
                    <div>
                        <p class="casefile-section-kicker">Health Monitoring</p>
                        <h2>Weight Progress and Blood Pressure Trends</h2>
                        <p>Saved monitoring records for clinical review.</p>
                    </div>
                    <button type="button" class="casefile-trend-action" data-vitals-open>{!! $iconPulse !!} Update Vitals</button>
                </div>
                <div class="casefile-chart-grid">
                    <article class="casefile-chart-card">
                        <span>Weight Progress</span>
                        <h3>Weight Progression</h3>
                        <div class="casefile-chart-canvas" data-chart="weight"></div>
                        <div class="casefile-legend"><span class="is-pink"></span> Weight (kg) by date or pregnancy week</div>
                        <button type="button" class="monitoring-history-trigger" data-history-open="weight">
                            <span class="monitoring-history-icon" aria-hidden="true">{!! $iconHistory !!}</span>
                            <span>View Weight History</span>
                            <b data-history-count="weight">{{ $weightRecords->count() }} Record{{ $weightRecords->count() === 1 ? '' : 's' }}</b>
                        </button>
                    </article>
                    <article class="casefile-chart-card">
                        <span>Blood Pressure Monitoring</span>
                        <h3>Blood Pressure Status</h3>
                        <div class="casefile-bp-status {{ $latestBpStatusState }}" data-bp-status-card aria-live="polite">
                            <div class="casefile-bp-ring" data-bp-status-indicator aria-label="Blood pressure status: {{ $latestBpStatusLabel }}">
                                <i data-bp-status-icon="normal">{!! $iconCheck !!}</i>
                                <i data-bp-status-icon="monitoring">{!! $iconAlert !!}</i>
                                <i data-bp-status-icon="high-risk">{!! $iconHighRisk !!}</i>
                                <i data-bp-status-icon="empty">{!! $iconPulse !!}</i>
                                <strong data-bp-status-value>{{ $latestBpDisplay }}</strong>
                                <span>mmHg</span>
                            </div>
                            <div class="casefile-bp-copy">
                                <small data-bp-status-context>{{ $latestBpContext }}</small>
                                <b data-bp-status-label>{{ $latestBpStatusLabel }}</b>
                                <div class="casefile-bp-readings">
                                    <span>Systolic: <strong data-bp-systolic>{{ $latestBpEntry['systolic'] ?? '--' }} mmHg</strong></span>
                                    <span>Diastolic: <strong data-bp-diastolic>{{ $latestBpEntry['diastolic'] ?? '--' }} mmHg</strong></span>
                                </div>
                                <p data-bp-status-explanation>{{ $latestBpStatusExplanation }}</p>
                                <em>This status summarizes recorded monitoring information and does not replace professional clinical assessment.</em>
                            </div>
                        </div>
                        <button type="button" class="monitoring-history-trigger" data-history-open="bp">
                            <span class="monitoring-history-icon" aria-hidden="true">{!! $iconHistory !!}</span>
                            <span>View Blood Pressure History</span>
                            <b data-history-count="bp">{{ $bpRecords->count() }} Record{{ $bpRecords->count() === 1 ? '' : 's' }}</b>
                        </button>
                    </article>
                </div>
            </section>

            <!-- Activity Section -->
            <section class="casefile-panel">
                <p class="casefile-section-kicker">Activity Timeline</p>
                <h2>Patient Activity</h2>
                <p class="casefile-section-subtitle">Registration, checkups, monitoring, learning, consultation, scheduling, and risk updates.</p>
                <div class="casefile-activity-list" data-activity-list>
                    @forelse ($patientActivities as $activity)
                        <article class="{{ $loop->iteration > 6 ? 'is-extra' : '' }}">
                            <strong>{{ $activity['title'] }} <span>{{ $activity['category'] }}</span></strong>
                            <p>{{ $activity['description'] }}</p>
                            <time>{{ $activity['date']?->format('M j, Y, g:i A') ?? 'Date not recorded' }}</time>
                        </article>
                    @empty
                        <article>
                            <strong>No activity available <span>No Data</span></strong>
                            <p>No patient activity has been recorded yet.</p>
                            <time>No data available</time>
                        </article>
                    @endforelse
                </div>
                @if ($patientActivities->count() > 6)
                    <button type="button" class="casefile-activity-more" data-activity-toggle data-more-label="View More" data-less-label="View Less">View More</button>
                @endif
            </section>

            <!-- Clinical References -->
            <section class="casefile-panel">
                <p class="casefile-section-kicker">Clinical Guide References</p>
                <h2>Clinical Guide References</h2>
                <p class="casefile-section-subtitle">Reference materials used for configurable screening thresholds and professional review workflows.</p>
                <div class="casefile-reference-grid">
                    @foreach($clinicalReferences as $reference)
                        <a href="{{ $reference['url'] }}" target="_blank" rel="noopener">
                            <strong>{{ $reference['title'] }}</strong>
                            <span>{{ $reference['url'] }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        </section>

        <!-- ===== MONITORING PANEL ===== -->
        <section class="casefile-panel" data-casefile-panel="monitoring" hidden>
            <h2>Monitoring Records</h2>
            <div data-record-list>
            @forelse ($records as $record)
                @php
                    $formattedRecord = $formattedVitalsById->get($record->id);
                @endphp
                <div class="casefile-record-row">
                    <strong>Week {{ $record->pregnancy_week ?: 'N/A' }} &middot; Month {{ $record->pregnancy_month ?: 'N/A' }}</strong>
                    <span>BP {{ $record->bp_systolic && $record->bp_diastolic ? $record->bp_systolic.'/'.$record->bp_diastolic.' mmHg' : 'Not logged' }}</span>
                    <span>Weight {{ $record->weight === null ? 'Not logged' : rtrim(rtrim(number_format((float) $record->weight, 2), '0'), '.').' kg' }}</span>
                    <span>{{ $record->blood_sugar_test_type ? ($bloodSugarTestTypes[$record->blood_sugar_test_type] ?? 'Test type not recorded') : 'Test type not recorded' }}</span>
                    <span>{{ $screeningStatusLabel($formattedRecord['screening_summary_status'] ?? $record->screening_summary_status ?? $record->risk_level) }}</span>
                    <span>{{ ($record->recorded_at ?? $record->created_at)?->format('M j, Y') ?? 'Date not recorded' }}</span>
                    <button type="button" data-edit-record="{{ $record->id }}">Edit</button>
                </div>
            @empty
                <p class="casefile-panel-note">No maternal monitoring record has been saved yet.</p>
            @endforelse
            </div>
        </section>

        <!-- ===== LEARNING & DOCUMENTS PANEL ===== -->
        <section class="casefile-panel" data-casefile-panel="learning-documents" hidden>
            <h2>{!! $iconFile !!} INAY Kaalaman Learning & Documents</h2>
            <p class="casefile-panel-note">Actual saved reading, video, infographic, and uploaded-document progress from this mother.</p>

            <div class="casefile-learning-summary">
                <article><span>Months Completed</span><strong>{{ $kaalamanOverallProgress['completed_months'] ?? 0 }}/{{ $kaalamanOverallProgress['total_months'] ?? 10 }}</strong></article>
                <article><span>Overall Learning</span><strong>{{ $kaalamanOverallProgress['percentage'] ?? 0 }}%</strong></article>
                <article><span>Activities Pending</span><strong>{{ $kaalamanOverallProgress['pending_required'] ?? 0 }}</strong></article>
                <article><span>Files Uploaded</span><strong>{{ $kaalamanOverallProgress['files_uploaded'] ?? $uploads->count() }}</strong></article>
            </div>

            <section aria-labelledby="document-submissions-title">
                <h3 id="document-submissions-title">Document Submissions</h3>
                <p class="casefile-panel-note">See at a glance whether the mother has sent documents for each month. Select a month to check its files and download them for review. Refresh this page to check for new submissions.</p>
                @if ($uploads->isEmpty())
                    <p class="casefile-panel-note"><strong>No documents received yet.</strong> Files will appear here after the mother uploads them in INAY Kaalaman.</p>
                @endif
                <div class="document-status-grid">
                    @foreach (($kaalamanMonthlyProgress['months'] ?? []) as $documentMonth)
                        @php
                            $submittedFiles = $uploads->where('month', $documentMonth['month']);
                        @endphp
                        <a class="document-status-card" href="#month-documents-{{ $documentMonth['month'] }}">
                            <strong>Month {{ $documentMonth['month'] }}</strong>
                            <span class="document-submission-status {{ $submittedFiles->isNotEmpty() ? 'is-submitted' : '' }}">{{ $submittedFiles->isNotEmpty() ? 'Submitted' : 'Not yet submitted' }}</span>
                            <small>{{ $submittedFiles->count() }} file{{ $submittedFiles->count() === 1 ? '' : 's' }} received</small>
                            @if ($submittedFiles->isNotEmpty())
                                <small>Latest: {{ $submittedFiles->first()->created_at?->format('M j, Y, g:i A') ?? 'Date not recorded' }}</small>
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>

            <div class="casefile-learning-months">
                @foreach (($kaalamanMonthlyProgress['months'] ?? []) as $progressMonth)
                    @php
                        $monthUploadsForStaff = $uploads->where('month', $progressMonth['month'])->values();
                        $requiredVideoTotal = (int) ($progressMonth['total_videos'] ?? 0);
                        $requiredVideoWatched = (int) ($progressMonth['watched_videos'] ?? 0);
                        $videoProgressLabel = $requiredVideoTotal > 0 ? "{$requiredVideoWatched}/{$requiredVideoTotal} watched" : 'No video posted';
                        $videoProgressNote = $requiredVideoTotal > 0 ? ($requiredVideoTotal - $requiredVideoWatched).' pending' : 'No video posted this month';
                    @endphp
                    <article class="casefile-learning-month {{ $progressMonth['is_complete'] ? 'is-complete' : '' }}">
                        <header>
                            <div>
                                <h3>Month {{ $progressMonth['month'] }}: {{ $progressMonth['title'] }}</h3>
                                <p>{{ $progressMonth['trimester'] }} &middot; {{ $progressMonth['weeks'] }}</p>
                            </div>
                            <span class="casefile-learning-badge {{ $progressMonth['is_complete'] ? 'is-complete' : '' }}">{{ $progressMonth['status'] }} &middot; {{ $progressMonth['percentage'] }}%</span>
                        </header>

                        <div class="casefile-learning-progress" aria-label="Month {{ $progressMonth['month'] }} completion">
                            <span style="width: {{ $progressMonth['percentage'] }}%"></span>
                        </div>

                        <div class="casefile-learning-grid">
                            <article><span>Reading</span><strong>{{ $progressMonth['reading']['label'] }}</strong><small>{{ $progressMonth['reading']['completed_at'] ?: 'Not completed' }}</small></article>
                            <article><span>Required Video</span><strong>{{ $videoProgressLabel }}</strong><small>{{ $videoProgressNote }}</small></article>
                            <article><span>Infographic</span><strong>{{ $progressMonth['infographic']['label'] }}</strong><small>{{ $progressMonth['infographic']['completed_at'] ?: 'Not completed' }}</small></article>
                            <article><span>Documents</span><strong>{{ $progressMonth['uploaded_required_documents'] }}/{{ $progressMonth['required_documents'] }} uploaded</strong><small>{{ count($progressMonth['uploaded_documents']) }} file{{ count($progressMonth['uploaded_documents']) === 1 ? '' : 's' }} total</small></article>
                        </div>

                        <ul class="casefile-learning-list">
                            @foreach ($progressMonth['videos'] as $video)
                                <li><span>{{ $video['title'] }}</span><small>{{ $video['label'] }}{{ $video['completed_at'] ? ' - '.$video['completed_at'] : '' }}</small></li>
                            @endforeach
                        </ul>

                        <h4 id="month-documents-{{ $progressMonth['month'] }}" style="scroll-margin-top: 100px;">Month {{ $progressMonth['month'] }} Document Checklist</h4>
                        <ul class="casefile-learning-list">
                            @foreach ($progressMonth['documents'] as $document)
                                <li>
                                    <span>{{ $document['label'] }}</span>
                                    <span class="document-submission-status {{ $document['uploaded'] ? 'is-submitted' : '' }}">{{ $document['uploaded'] ? 'Submitted' : 'Not yet submitted' }}</span>
                                </li>
                            @endforeach
                        </ul>
                        @if ($monthUploadsForStaff->isNotEmpty())
                            <h4>Prenatal Records and Receipts History</h4>
                            <ul class="document-file-grid">
                                @foreach ($monthUploadsForStaff as $upload)
                                    <li>
                                        <button type="button" class="document-file-card" data-document-card
                                            data-name="{{ $upload->original_name }}"
                                            data-details="Month {{ $upload->month }} · {{ $uploadTypeLabels[$upload->record_type] ?? $upload->record_type }} · Received {{ $upload->created_at?->format('M j, Y, g:i A') ?? 'date not recorded' }} · {{ number_format($upload->size / 1024, 1) }} KB"
                                            data-preview="{{ route('staff.mothers.kaalaman-uploads.preview', [$mother, $upload]) }}"
                                            data-download="{{ route('staff.mothers.kaalaman-uploads.download', [$mother, $upload]) }}"
                                            aria-haspopup="dialog" aria-controls="document-preview-dialog">
                                            {!! $iconFile !!}
                                            <strong>{{ $upload->original_name }}</strong>
                                            <small>{{ $uploadTypeLabels[$upload->record_type] ?? $upload->record_type }}</small>
                                            <small>Received {{ $upload->created_at?->format('M j, Y, g:i A') ?? 'date not recorded' }}</small>
                                            <span class="document-submission-status is-submitted">View document</span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <h4>Prenatal Records and Receipts History</h4>
                            <p class="casefile-panel-note">No prenatal records or receipts have been sent for this month yet.</p>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>

        <section class="casefile-panel" data-casefile-panel="documents" hidden>
            <h2>{!! $iconFile !!} Mother's Documents</h2>
            <p class="casefile-panel-note">All records and receipts sent through Documents or INAY Kaalaman. Search, filter, or select a file to preview it. Refresh to check for new uploads.</p>
            @include('partials.document-library', ['documentAudience' => 'staff'])
        </section>
        <link rel="stylesheet" href="{{ asset('css/documents.css') }}?v={{ filemtime(public_path('css/documents.css')) }}">
        <script src="{{ asset('js/document-library.js') }}?v={{ filemtime(public_path('js/document-library.js')) }}" defer></script>

        <dialog id="document-preview-dialog" class="document-preview-dialog" aria-labelledby="document-preview-title">
            <header class="vitals-dialog-header">
                <h2 id="document-preview-title">Document details</h2>
                <button type="button" data-document-close aria-label="Close document preview">&times;</button>
            </header>
            <p data-document-details></p>
            <p data-document-status role="status"></p>
            <div class="document-preview-content" data-document-content></div>
            <p><a data-document-download>Download file</a></p>
        </dialog>

        <!-- ===== NOTES PANEL ===== -->
        <section class="casefile-panel" data-casefile-panel="notes" hidden>
            <h2>Clinical Notes</h2>
            <p class="casefile-panel-note">{{ $latestRecord?->notes ?: 'No staff notes recorded for this patient yet.' }}</p>
        </section>

        <!-- ===== VITALS SUCCESS ===== -->
        <div class="vitals-success" data-vitals-success hidden>Maternal vitals saved.</div>

        <!-- ===== HISTORY MODAL ===== -->
        <div class="history-modal" data-history-modal hidden>
            <div class="history-modal-backdrop" data-history-close></div>
            <section class="history-dialog" role="dialog" aria-modal="true" aria-labelledby="history-modal-title">
                <header class="history-dialog-header">
                    <div>
                        <span class="history-dialog-kicker">Monitoring History</span>
                        <h2 class="history-dialog-title" id="history-modal-title" data-history-title>Weight History</h2>
                        <p class="history-dialog-copy" data-history-description>Complete weight readings from recorded maternal vitals.</p>
                    </div>
                    <button type="button" class="history-close" data-history-close aria-label="Close monitoring history">&times;</button>
                </header>
                <div class="history-dialog-controls">
                    <label class="history-search">
                        <span aria-hidden="true">&#9906;</span>
                        <input type="search" data-history-search placeholder="Search date, values, or status...">
                    </label>
                    <button type="button" class="history-sort" data-history-sort>Newest First</button>
                </div>
                <p class="history-count-copy" data-history-count-copy>Showing 0 records</p>
                <div class="history-dialog-body" data-history-content></div>
            </section>
        </div>

        <!-- ===== VITAL DETAIL MODAL (mobile) ===== -->
        <div class="vital-detail-modal" data-vital-detail-modal hidden>
            <div class="vital-detail-modal-backdrop" data-vital-detail-close></div>
            <section class="vital-detail-dialog" role="dialog" aria-modal="true" aria-labelledby="vital-detail-title">
                <header class="vital-detail-dialog-header">
                    <h2 class="vital-detail-dialog-title" id="vital-detail-title">Vital Details</h2>
                    <button type="button" class="vital-detail-close" data-vital-detail-close aria-label="Close details">&times;</button>
                </header>
                <div class="vital-detail-dialog-body" data-vital-detail-body>
                    <!-- Populated by JavaScript -->
                </div>
            </section>
        </div>

        <!-- ===== VITALS MODAL ===== -->
        <div class="vitals-modal" data-vitals-modal hidden>
            <div class="vitals-modal-backdrop" data-vitals-close></div>
            <form class="vitals-dialog" data-vitals-form novalidate>
                <input type="hidden" name="record_id" data-vitals-record-id>
                <input type="hidden" name="confirmed_unusual" value="" data-vitals-confirmed>
                <header class="vitals-dialog-header">
                    <h2 data-vitals-title>Add Maternal Vitals</h2>
                    <button type="button" data-vitals-close aria-label="Close update vitals modal">&times;</button>
                </header>

                <div class="vitals-warning" data-vitals-warning hidden>
                    {!! $iconAlert !!}
                    <div>
                        <strong>Review before saving</strong>
                        <ul data-vitals-warning-list></ul>
                        <p>Review the flagged values before saving this maternal vital record.</p>
                    </div>
                </div>

                <div class="vitals-field-grid">
                    <label>
                        <span>Record Date</span>
                        <input type="date" name="recorded_at" value="{{ $initialRecordDate }}">
                        <small data-field-error="recorded_at"></small>
                    </label>
                    <label>
                        <span>Pregnancy Week</span>
                        <input type="number" name="pregnancy_week" min="1" max="42" value="{{ $pregnancyWeek ?: '' }}">
                        <small data-field-error="pregnancy_week"></small>
                    </label>
                    <label class="height-field">
                        <span>Height (cm / ft-in)</span>
                        <div class="height-controls">
                            <select name="height_unit" data-height-unit aria-label="Height unit">
                                <option value="cm">cm</option>
                                <option value="ft_in">ft / in</option>
                            </select>
                            <input type="number" name="height_cm" data-height-cm step="0.01" min="0.01" value="{{ $heightNumber === null ? '' : number_format((float) $heightNumber, 2, '.', '') }}" placeholder="Height (cm)">
                            <div class="height-ft-in" data-height-ft-in hidden>
                                <input type="number" name="height_feet" data-height-feet min="1" step="1" placeholder="Feet" aria-label="Height in feet">
                                <input type="number" name="height_inches" data-height-inches min="0" max="11.99" step="0.01" placeholder="Inches" aria-label="Height in inches">
                            </div>
                        </div>
                        <small data-field-error="height_cm"></small>
                        <small data-field-error="height_feet"></small>
                        <small data-field-error="height_inches"></small>
                    </label>
                    <label>
                        <span>Weight (kg)</span>
                        <input type="number" name="weight" step="0.1" min="25" max="250" value="{{ $weightNumber ?: '' }}">
                        <small data-field-error="weight"></small>
                    </label>
                    <label>
                        <span>Pre-pregnancy Weight (kg)</span>
                        <input type="number" name="pre_pregnancy_weight" step="0.1" min="25" max="250" value="{{ $prePregnancyWeightNumber ?: '' }}">
                        <small data-field-error="pre_pregnancy_weight"></small>
                    </label>
                    <label>
                        <span>Pre-pregnancy BMI</span>
                        <input type="number" name="pre_pregnancy_bmi" data-pre-pregnancy-bmi step="0.01" min="10" max="70" value="{{ $prePregnancyBmiNumber === null ? '' : number_format($prePregnancyBmiNumber, 2, '.', '') }}" readonly aria-readonly="true" placeholder="Calculated automatically">
                        <small data-field-error="pre_pregnancy_bmi"></small>
                    </label>
                    <label>
                        <span>Systolic BP</span>
                        <input type="number" name="bp_systolic" min="50" max="260" value="{{ $latestRecord?->bp_systolic ?: '' }}">
                        <small data-field-error="bp_systolic"></small>
                    </label>
                    <label>
                        <span>Diastolic BP</span>
                        <input type="number" name="bp_diastolic" min="30" max="160" value="{{ $latestRecord?->bp_diastolic ?: '' }}">
                        <small data-field-error="bp_diastolic"></small>
                    </label>
                    <label>
                        <span>Blood Sugar Test Type</span>
                        <select name="blood_sugar_test_type" required>
                            <option value="">Select test type</option>
                            @foreach($bloodSugarTestTypes as $value => $label)
                                <option value="{{ $value }}" @selected($latestRecord?->blood_sugar_test_type === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <small data-field-error="blood_sugar_test_type"></small>
                    </label>
                    <label>
                        <span>Blood Sugar (mg/dL)</span>
                        <input type="number" name="blood_sugar" step="0.1" min="20" max="700" value="{{ $latestRecord?->blood_sugar ?: '' }}">
                        <small data-field-error="blood_sugar"></small>
                    </label>
                    <label>
                        <span>Temperature (C)</span>
                        <input type="number" name="temperature" step="0.1" min="30" max="45" value="{{ $latestRecord?->temperature ?: '' }}">
                        <small data-field-error="temperature"></small>
                    </label>
                    <label>
                        <span>Heart Rate</span>
                        <input type="number" name="heart_rate" min="30" max="220" value="{{ $latestRecord?->heart_rate ?: '' }}">
                        <small data-field-error="heart_rate"></small>
                    </label>
                    <label class="is-wide">
                        <span>Program Staff Notes</span>
                        <textarea name="notes" rows="4" placeholder="Document symptoms, advice, referral, or follow-up instructions...">{{ $latestRecord?->notes }}</textarea>
                        <small data-field-error="notes"></small>
                    </label>
                </div>

                <footer class="vitals-dialog-footer">
                    <button type="button" class="vitals-cancel" data-vitals-close>Cancel</button>
                    <button type="submit" class="vitals-save" data-vitals-save>
                        <span data-vitals-save-text>Save Vitals</span>
                        <span class="vitals-spinner" hidden data-vitals-spinner></span>
                    </button>
                </footer>
            </form>
        </div>
    </section>

    <!-- ===== JAVASCRIPT ===== -->
    <script>
        (() => {
            const tabs = Array.from(document.querySelectorAll('[data-casefile-tab]'));
            const panels = Array.from(document.querySelectorAll('[data-casefile-panel]'));
            let vitalsState = @json($maternalVitalsPayload);
            const bloodSugarTestTypes = @json($bloodSugarTestTypes);

            const csrfToken = '{{ csrf_token() }}';
            const motherVitalsUrl = '{{ route('api.staff.maternal-vitals.store', $mother) }}';
            const updateVitalsUrl = (id) => `/api/program-staff/maternal-vitals/${id}`;
            const deleteVitalsUrl = (id) => `/api/program-staff/maternal-vitals/${id}`;
            const modal = document.querySelector('[data-vitals-modal]');
            const form = document.querySelector('[data-vitals-form]');
            const warning = document.querySelector('[data-vitals-warning]');
            const warningList = document.querySelector('[data-vitals-warning-list]');
            const saveButton = document.querySelector('[data-vitals-save]');
            const saveText = document.querySelector('[data-vitals-save-text]');
            const spinner = document.querySelector('[data-vitals-spinner]');
            const success = document.querySelector('[data-vitals-success]');
            const historyModal = document.querySelector('[data-history-modal]');
            const historyTitle = document.querySelector('[data-history-title]');
            const historyDescription = document.querySelector('[data-history-description]');
            const historySearch = document.querySelector('[data-history-search]');
            const historySort = document.querySelector('[data-history-sort]');
            const historyCountCopy = document.querySelector('[data-history-count-copy]');
            const historyContent = document.querySelector('[data-history-content]');
            const heightUnitInput = form?.querySelector('[data-height-unit]');
            const heightCmInput = form?.querySelector('[data-height-cm]');
            const heightFtIn = form?.querySelector('[data-height-ft-in]');
            const heightFeetInput = form?.querySelector('[data-height-feet]');
            const heightInchesInput = form?.querySelector('[data-height-inches]');
            const prePregnancyWeightInput = form?.querySelector('[name="pre_pregnancy_weight"]');
            const prePregnancyBmiInput = form?.querySelector('[data-pre-pregnancy-bmi]');
            let activeHistoryType = 'weight';
            let historySortDirection = 'desc';
            const touched = new Set();
            let previousHeightUnit = heightUnitInput?.value || 'cm';

            // Vital detail modal elements
            const vitalDetailModal = document.querySelector('[data-vital-detail-modal]');
            const vitalDetailBody = document.querySelector('[data-vital-detail-body]');
            const vitalDetailTitle = document.querySelector('#vital-detail-title');

            const number = (value, decimals = 0) => {
                if (value === null || value === undefined || value === '') return null;
                const parsed = Number(value);
                if (!Number.isFinite(parsed)) return null;
                return decimals ? parsed.toFixed(decimals).replace(/\.0+$/, '').replace(/(\.\d*[1-9])0+$/, '$1') : String(Math.round(parsed));
            };

            const escapeHtml = (value) => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const setText = (selector, value) => {
                const node = document.querySelector(selector);
                if (node) node.textContent = value;
            };

            const setAllText = (selector, value) => {
                document.querySelectorAll(selector).forEach((node) => {
                    node.textContent = value;
                });
            };

            const statusSlug = (label) => String(label || 'Logged')
                .trim()
                .toLowerCase()
                .replaceAll('&', 'and')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-|-$/g, '') || 'logged';

            const updateStatusBadge = (key, label) => {
                const node = document.querySelector(`[data-vital-status="${key}"]`);
                if (!node) return;
                node.textContent = label;
                node.dataset.statusTone = statusSlug(label);
            };

            const setRiskClass = (node, status) => {
                if (!node) return;
                node.classList.remove('is-low', 'is-medium', 'is-high', 'is-pending', 'is-logged', 'is-within-reference-range', 'is-for-review', 'is-for-professional-interpretation', 'is-urgent-referral-recommended');
                node.classList.add(`is-${statusSlug(status)}`);
            };

            const setRiskCardClass = (status) => {
                const node = document.querySelector('[data-risk-card]');
                if (!node) return;
                node.classList.remove('is-green', 'is-review', 'is-risk', 'is-neutral');
                const slug = statusSlug(status);
                node.classList.add(slug === 'urgent-referral-recommended' ? 'is-risk' : (['for-review', 'for-professional-interpretation'].includes(slug) ? 'is-review' : (slug === 'within-reference-range' ? 'is-green' : 'is-neutral')));
            };

            const statusFor = (latest, key) => {
                if (!latest) return 'Logged';
                return latest.statuses?.[key] || 'Logged';
            };

            const recordCountLabel = (count) => `${count} Record${count === 1 ? '' : 's'}`;

            const valueText = (value, unit, decimals = 0) => {
                const formatted = number(value, decimals);
                return formatted === null ? 'N/A' : `${formatted} ${unit}`;
            };

            const readHeightCm = (unit = heightUnitInput?.value || 'cm') => {
                if (unit === 'ft_in') {
                    const feet = heightFeetInput?.value;
                    const inches = heightInchesInput?.value;
                    const feetNumber = Number(feet);
                    const inchesNumber = Number(inches);
                    if (feet === '' || inches === '' || !Number.isInteger(feetNumber) || feetNumber < 1 || !Number.isFinite(inchesNumber) || inchesNumber < 0 || inchesNumber > 11.99) return null;
                    return (feetNumber * 30.48) + (inchesNumber * 2.54);
                }

                const centimeters = Number(heightCmInput?.value);
                return heightCmInput?.value !== '' && Number.isFinite(centimeters) && centimeters > 0 ? centimeters : null;
            };

            const writeHeight = (heightCm, unit) => {
                if (!Number.isFinite(heightCm) || heightCm <= 0) return;

                if (unit === 'ft_in') {
                    let feet = Math.floor(heightCm / 30.48);
                    let inches = (heightCm - (feet * 30.48)) / 2.54;
                    inches = Math.round(inches * 100) / 100;
                    if (inches >= 12) {
                        feet += 1;
                        inches = 0;
                    }
                    heightFeetInput.value = String(feet);
                    heightInchesInput.value = inches.toFixed(2).replace(/\.00$/, '');
                    return;
                }

                heightCmInput.value = heightCm.toFixed(2).replace(/\.00$/, '');
            };

            const syncHeightControls = () => {
                const isFeetAndInches = heightUnitInput?.value === 'ft_in';
                if (!heightCmInput || !heightFtIn || !heightFeetInput || !heightInchesInput) return;
                heightCmInput.hidden = isFeetAndInches;
                heightCmInput.disabled = isFeetAndInches;
                heightFtIn.hidden = !isFeetAndInches;
                heightFeetInput.disabled = !isFeetAndInches;
                heightInchesInput.disabled = !isFeetAndInches;
            };

            const syncPrePregnancyBmi = () => {
                if (!prePregnancyBmiInput) return;
                const heightCm = readHeightCm();
                const prePregnancyWeight = Number(prePregnancyWeightInput?.value);
                if (heightCm === null || !Number.isFinite(prePregnancyWeight) || prePregnancyWeight <= 0) {
                    prePregnancyBmiInput.value = '';
                    return;
                }

                const heightMeters = heightCm / 100;
                prePregnancyBmiInput.value = (prePregnancyWeight / (heightMeters * heightMeters)).toFixed(2);
            };

            const latestBloodPressureRecord = (payload) => {
                const history = Array.isArray(payload?.blood_pressure_history) ? payload.blood_pressure_history : [];
                const rows = history.filter((item) => item && item.systolic !== null && item.systolic !== undefined && item.diastolic !== null && item.diastolic !== undefined);
                return rows.length ? rows[rows.length - 1] : null;
            };

            const bloodPressureStatusProfile = (status, hasRecord) => {
                if (!hasRecord) {
                    return {
                        className: 'is-empty',
                        label: 'No Record',
                        explanation: 'No blood pressure record available yet.',
                    };
                }

                const rawStatus = String(status || '').trim().toLowerCase();
                const slug = statusSlug(status);

                if (['urgent-referral-recommended', 'high-risk', 'critical'].includes(slug) || rawStatus === 'high risk') {
                    return {
                        className: 'is-high-risk',
                        label: 'High Risk',
                        explanation: 'A high-risk blood pressure status was recorded. Professional clinical assessment is required.',
                    };
                }

                if (['within-reference-range', 'normal-stable', 'normal', 'stable'].includes(slug)) {
                    return {
                        className: 'is-normal',
                        label: 'Normal / Stable',
                        explanation: 'Latest recorded blood pressure is within the configured screening review range.',
                    };
                }

                return {
                    className: 'is-monitoring',
                    label: 'Needs Monitoring',
                    explanation: 'Blood pressure requires further monitoring and professional assessment.',
                };
            };

            const renderBloodPressureStatus = (payload) => {
                const card = document.querySelector('[data-bp-status-card]');
                if (!card) return;

                const record = latestBloodPressureRecord(payload);
                const profile = bloodPressureStatusProfile(record?.raw_status || record?.status, Boolean(record));
                const context = record
                    ? (record.pregnancy_week ? `Pregnancy week ${record.pregnancy_week}` : `Recorded ${record.recorded_label || 'Date not recorded'}`)
                    : 'No blood pressure record available yet.';

                card.classList.remove('is-empty', 'is-normal', 'is-monitoring', 'is-high-risk');
                card.classList.add(profile.className);
                setText('[data-bp-status-value]', record ? `${record.systolic} / ${record.diastolic}` : '-- / --');
                setText('[data-bp-status-context]', context);
                setText('[data-bp-status-label]', profile.label);
                setText('[data-bp-systolic]', record ? `${record.systolic} mmHg` : '-- mmHg');
                setText('[data-bp-diastolic]', record ? `${record.diastolic} mmHg` : '-- mmHg');
                setText('[data-bp-status-explanation]', record?.explanation || profile.explanation);

                const indicator = document.querySelector('[data-bp-status-indicator]');
                if (indicator) {
                    indicator.setAttribute('aria-label', `Blood pressure status: ${profile.label}`);
                }
            };

            const statusInfo = (label) => ({ label: label || 'Logged', className: `is-${statusSlug(label || 'Logged')}` });

            const normalizeDate = (value) => {
                if (!value) return 0;
                const timestamp = new Date(value).getTime();
                return Number.isFinite(timestamp) ? timestamp : 0;
            };

            const historyConfig = () => activeHistoryType === 'weight'
                ? {
                    title: 'Weight History',
                    description: 'Complete weight readings from recorded maternal vitals.',
                    empty: 'No weight history available yet.',
                    rows: vitalsState.weight_history || [],
                    headers: ['Date', 'Pregnancy Week', 'Weight', 'Status'],
                }
                : {
                    title: 'Blood Pressure History',
                    description: 'Complete systolic and diastolic readings from recorded maternal vitals.',
                    empty: 'No blood pressure history available yet.',
                    rows: vitalsState.blood_pressure_history || [],
                    headers: ['Date', 'Pregnancy Week', 'Systolic', 'Diastolic', 'Blood Pressure Status'],
                };

            const historyMatches = (item, status) => {
                const query = (historySearch?.value || '').trim().toLowerCase();
                if (!query) return true;
                const searchable = activeHistoryType === 'weight'
                    ? [
                        item.recorded_label,
                        item.recorded_at,
                        `week ${item.pregnancy_week || 'N/A'}`,
                        item.pregnancy_week,
                        valueText(item.weight, 'kg', 1),
                        status.label,
                    ]
                    : [
                        item.recorded_label,
                        item.recorded_at,
                        `week ${item.pregnancy_week || 'N/A'}`,
                        item.pregnancy_week,
                        item.systolic,
                        item.diastolic,
                        `${item.systolic}/${item.diastolic}`,
                        status.label,
                    ];
                return searchable.some((value) => String(value ?? '').toLowerCase().includes(query));
            };

            const sortedHistoryRows = (rows) => [...rows].sort((a, b) => {
                const diff = normalizeDate(a.recorded_at) - normalizeDate(b.recorded_at);
                return historySortDirection === 'asc' ? diff : -diff;
            });

            const renderHistoryModal = () => {
                if (!historyModal || !historyContent) return;
                const config = historyConfig();
                const rows = sortedHistoryRows(config.rows).filter((item) => {
                    const status = statusInfo(item.status);
                    return historyMatches(item, status);
                });

                historyTitle.textContent = config.title;
                historyDescription.textContent = config.description;
                historySort.textContent = historySortDirection === 'desc' ? 'Newest First' : 'Oldest First';
                historyCountCopy.textContent = `Showing ${rows.length} of ${config.rows.length} ${config.rows.length === 1 ? 'record' : 'records'}`;

                if (!config.rows.length) {
                    historyContent.innerHTML = `<p class="history-empty">${config.empty}</p>`;
                    return;
                }

                if (!rows.length) {
                    historyContent.innerHTML = '<p class="history-empty">No matching history records found.</p>';
                    return;
                }

                const actionHeader = '<th>Actions</th>';
                const tableRows = rows.map((item) => {
                    if (activeHistoryType === 'weight') {
                        const status = statusInfo(item.status);
                        return `
                            <tr>
                                <td class="is-main">${escapeHtml(item.recorded_label || 'Date not recorded')}</td>
                                <td>Week ${escapeHtml(item.pregnancy_week || 'N/A')}</td>
                                <td class="is-main">${escapeHtml(valueText(item.weight, 'kg', 1))}</td>
                                <td><span class="history-status ${status.className}">${status.label}</span></td>
                                <td>
                                    <div class="history-row-actions">
                                        <button type="button" class="is-edit" data-history-edit-record="${item.id}">Edit</button>
                                        <button type="button" class="is-delete" data-history-delete-record="${item.id}">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    }

                    const status = statusInfo(item.status);
                    return `
                        <tr>
                            <td class="is-main">${escapeHtml(item.recorded_label || 'Date not recorded')}</td>
                            <td>Week ${escapeHtml(item.pregnancy_week || 'N/A')}</td>
                            <td class="is-red">${escapeHtml(valueText(item.systolic, 'mmHg'))}</td>
                            <td class="is-blue">${escapeHtml(valueText(item.diastolic, 'mmHg'))}</td>
                            <td><span class="history-status ${status.className}">${status.label}</span></td>
                            <td>
                                <div class="history-row-actions">
                                    <button type="button" class="is-edit" data-history-edit-record="${item.id}">Edit</button>
                                    <button type="button" class="is-delete" data-history-delete-record="${item.id}">Delete</button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');

                historyContent.innerHTML = `
                    <div class="history-table-scroll">
                        <table class="history-table">
                            <thead><tr>${config.headers.map((header) => `<th>${header}</th>`).join('')}${actionHeader}</tr></thead>
                            <tbody>${tableRows}</tbody>
                        </table>
                    </div>
                `;
            };

            const openHistoryModal = (type) => {
                activeHistoryType = type === 'bp' ? 'bp' : 'weight';
                historySortDirection = 'desc';
                if (historySearch) historySearch.value = '';
                renderHistoryModal();
                historyModal.hidden = false;
                document.body.classList.add('has-monitoring-history-modal');
                historySearch?.focus();
            };

            const closeHistoryModal = () => {
                if (!historyModal) return;
                historyModal.hidden = true;
                document.body.classList.remove('has-monitoring-history-modal');
            };

            // === Vital Detail Modal ===
            const openVitalDetailModal = (cardKey) => {
                if (!vitalDetailModal || !vitalDetailBody) return;
                // Find the card
                const card = document.querySelector(`[data-vital-card="${cardKey}"]`);
                if (!card) return;

                // Clone the details content from the card
                const details = card.querySelector('.casefile-vital-details');
                if (!details) return;

                // Set title
                const title = card.querySelector('span')?.textContent || 'Vital Details';
                vitalDetailTitle.textContent = title;

                // Clone body content
                const clone = details.cloneNode(true);
                // Remove any leftover toggle buttons (just in case)
                clone.querySelectorAll('.casefile-vital-toggle-details, .casefile-vital-details-close').forEach(el => el.remove());
                vitalDetailBody.innerHTML = '';
                vitalDetailBody.appendChild(clone);

                vitalDetailModal.hidden = false;
                document.body.classList.add('has-vital-detail-modal');
            };

            const closeVitalDetailModal = () => {
                if (!vitalDetailModal) return;
                vitalDetailModal.hidden = true;
                document.body.classList.remove('has-vital-detail-modal');
            };

            const renderChartEmpty = (message) => `
                <svg viewBox="0 0 640 360" role="img" aria-label="${escapeHtml(message)}">
                    <path d="M76 40v256h516" class="axis"/>
                    <path d="M76 40h516M76 168h516M76 296h516" class="grid"/>
                    <text x="18" y="24" class="axis-label">Value</text>
                    <text x="520" y="347" class="axis-label">Date</text>
                    <text x="260" y="130" class="empty">${escapeHtml(message)}</text>
                </svg>
            `;

            const chartScales = (values, fallbackMin, fallbackMax) => {
                if (!values.length) return { min: fallbackMin, max: fallbackMax };
                let min = Math.min(...values);
                let max = Math.max(...values);
                if (min === max) {
                    min -= 2;
                    max += 2;
                }
                const pad = Math.max(1, (max - min) * 0.15);
                return { min: Math.floor(min - pad), max: Math.ceil(max + pad) };
            };

            const chartPoint = (index, count, value, min, max) => {
                const left = 76;
                const right = 48;
                const top = 40;
                const bottom = 64;
                const width = 640;
                const height = 360;
                const x = count <= 1 ? (width - right + left) / 2 : left + (index * ((width - left - right) / (count - 1)));
                const y = top + ((max - value) / (max - min)) * (height - top - bottom);
                return { x, y };
            };

            const chronologicalWeightHistory = (history) => [...history].sort((a, b) => {
                const weekA = Number(a.pregnancy_week);
                const weekB = Number(b.pregnancy_week);
                const hasWeekA = Number.isFinite(weekA) && weekA > 0;
                const hasWeekB = Number.isFinite(weekB) && weekB > 0;

                if (hasWeekA && hasWeekB && weekA !== weekB) return weekA - weekB;
                if (hasWeekA !== hasWeekB) return hasWeekA ? -1 : 1;

                const dateDiff = normalizeDate(a.recorded_at) - normalizeDate(b.recorded_at);
                if (dateDiff !== 0) return dateDiff;

                return Number(a.id || 0) - Number(b.id || 0);
            });

            const renderWeightChart = (history) => {
                const target = document.querySelector('[data-chart="weight"]');
                if (!target) return;
                const chartHistory = chronologicalWeightHistory(history)
                    .filter((item) => Number.isFinite(Number(item.weight)));
                if (!chartHistory.length) {
                    target.innerHTML = renderChartEmpty('No weight record yet');
                    return;
                }
                const values = chartHistory.map((item) => Number(item.weight));
                const { min, max } = chartScales(values, 70, 80);
                const labels = [max, Math.round((max + min) / 2), min];
                const points = chartHistory.map((item, index) => ({ ...item, ...chartPoint(index, chartHistory.length, Number(item.weight), min, max) }));
                const polyline = points.map((point) => `${point.x},${point.y}`).join(' ');
                target.innerHTML = `
                    <svg viewBox="0 0 640 360" role="img" aria-label="Weight progression chart">
                        <path d="M76 40v256h516" class="axis"/>
                        <path d="M76 40h516M76 168h516M76 296h516" class="grid"/>
                        <text x="18" y="24" class="axis-label">Weight</text>
                        <text x="334" y="347" text-anchor="middle" class="axis-label">Date / Pregnancy Week</text>
                        ${labels.map((label, index) => `<text x="24" y="${44 + index * 128}">${label} kg</text>`).join('')}
                        ${points.length > 1 ? `<polyline points="${polyline}" class="weight-line"/>` : ''}
                        ${points.map((point) => `<circle cx="${point.x}" cy="${point.y}" r="5" class="weight-dot"><title>${escapeHtml(point.tooltip)}</title></circle>`).join('')}
                        ${points.map((point) => `<text x="${point.x - 18}" y="322">${escapeHtml(point.label)}</text>`).join('')}
                    </svg>
                `;
            };

            const renderRecordList = (records) => {
                const list = document.querySelector('[data-record-list]');
                if (!list) return;
                if (!records.length) {
                    list.innerHTML = '<p class="casefile-panel-note">No maternal monitoring record has been saved yet.</p>';
                    return;
                }
                list.innerHTML = [...records].reverse().map((record) => `
                    <div class="casefile-record-row">
                        <strong>Week ${record.pregnancy_week || 'N/A'} &middot; Month ${record.pregnancy_month || 'N/A'}</strong>
                        <span>BP ${record.blood_pressure ? `${record.blood_pressure} mmHg` : 'Not logged'}</span>
                        <span>Weight ${record.weight === null ? 'Not logged' : `${number(record.weight, 1)} kg`}</span>
                        <span>${escapeHtml(record.blood_sugar_test_type_label || 'Test type not recorded')}</span>
                        <span>${escapeHtml(record.screening_summary_status || 'Logged')}</span>
                        <span>${escapeHtml(record.recorded_label || 'Date not recorded')}</span>
                        <button type="button" data-edit-record="${record.id}">Edit</button>
                    </div>
                `).join('');
            };

            const renderVitals = (payload) => {
                vitalsState = payload;
                const latest = payload.latest;
                const screeningStatus = latest?.screening_summary_status || payload.risk_label || 'Logged';
                const recordCount = payload.records?.length || 0;
                const visitCount = Math.min(recordCount, 8);
                const visitPercent = Math.min(100, Math.round((visitCount / 8) * 100));

                setText('[data-vital-value="blood_pressure"]', latest?.blood_pressure ? `${latest.blood_pressure} mmHg` : 'Not logged');
                setText('[data-vital-value="blood_sugar"]', latest && latest.blood_sugar !== null && latest.blood_sugar !== undefined ? `${number(latest.blood_sugar, 1)} mg/dL` : 'Not logged');
                setText('[data-vital-value="weight"]', latest && latest.weight !== null && latest.weight !== undefined ? `${number(latest.weight, 1)} kg` : 'Not logged');
                setText('[data-vital-value="temperature"]', latest && latest.temperature !== null && latest.temperature !== undefined ? `${number(latest.temperature, 1)} C` : 'Not logged');
                setText('[data-vital-value="heart_rate"]', latest && latest.heart_rate !== null && latest.heart_rate !== undefined ? `${number(latest.heart_rate)} bpm` : 'Not logged');
                ['blood_pressure', 'blood_sugar', 'weight', 'temperature', 'heart_rate'].forEach((key) => {
                    updateStatusBadge(key, statusFor(latest, key));
                    setText(`[data-vital-week="${key}"]`, latest?.pregnancy_week ? `Pregnancy week ${latest.pregnancy_week}` : 'Pregnancy week N/A');
                    setText(`[data-vital-explanation="${key}"]`, latest?.explanations?.[key] || 'No screening explanation available yet.');
                    const guideline = latest?.guidelines?.[key];
                    setText(`[data-vital-guideline="${key}"]`, guideline ? `${guideline.name || 'Facility-configurable screening rule'} / ${guideline.version || 'Pending validation'}` : 'Facility-configurable screening rule / Pending validation');
                    const reference = document.querySelector(`[data-vital-reference="${key}"]`);
                    if (reference && guideline?.source_url) {
                        reference.setAttribute('href', guideline.source_url);
                        reference.textContent = 'View Reference';
                    }
                });
                setText('[data-vital-date="blood_pressure"]', latest?.blood_pressure && latest?.recorded_label ? `Recorded ${latest.recorded_label}` : 'No measurement available');
                setText('[data-vital-date="blood_sugar"]', latest && latest.blood_sugar !== null && latest.blood_sugar !== undefined && latest?.recorded_label ? `Recorded ${latest.recorded_label}` : 'No measurement available');
                setText('[data-vital-date="weight"]', latest && latest.weight !== null && latest.weight !== undefined && latest?.recorded_label ? `Recorded ${latest.recorded_label}` : 'No measurement available');
                setText('[data-vital-date="temperature"]', latest && latest.temperature !== null && latest.temperature !== undefined && latest?.recorded_label ? `Recorded ${latest.recorded_label}` : 'No measurement available');
                setText('[data-vital-date="heart_rate"]', latest && latest.heart_rate !== null && latest.heart_rate !== undefined && latest?.recorded_label ? `Recorded ${latest.recorded_label}` : 'No measurement available');
                setText('[data-vital-test-type="blood_sugar"]', `Test type: ${latest?.blood_sugar_test_type_label || 'Test type not recorded'}`);
                ['blood_pressure', 'weight', 'temperature', 'heart_rate'].forEach((key) => setText(`[data-vital-test-type="${key}"]`, 'Test type: Not applicable'));
                setText('[data-risk-label]', screeningStatus);
                setText('[data-summary-risk]', screeningStatus);
                setText('[data-risk-date]', latest?.recorded_label ? `Updated ${latest.recorded_label}` : 'No monitoring record yet');
                setRiskClass(document.querySelector('[data-risk-label]'), screeningStatus);
                setRiskCardClass(screeningStatus);
                setText('[data-monitoring-count]', recordCount);
                setAllText('[data-history-count="weight"]', recordCountLabel(payload.weight_history?.length || 0));
                setAllText('[data-history-count="bp"]', recordCountLabel(payload.blood_pressure_history?.length || 0));
                setText('[data-visit-count]', `${visitCount}/8`);
                setText('[data-visit-copy]', `${visitPercent}% of expected visits logged`);
                const visitProgress = document.querySelector('[data-visit-progress]');
                if (visitProgress) visitProgress.style.setProperty('--progress', `${visitPercent}%`);
                renderWeightChart(payload.weight_history || []);
                renderBloodPressureStatus(payload);
                renderRecordList(payload.records || []);
                if (historyModal && !historyModal.hidden) renderHistoryModal();
            };

            const fieldRules = {
                recorded_at: (value) => value ? '' : 'Record date is required.',
                pregnancy_week: (value) => Number(value) >= 1 && Number(value) <= 42 ? '' : 'Pregnancy week must be between 1 and 42.',
                height_cm: (value, values) => values.height_unit === 'ft_in' || value === '' || Number(value) > 0 ? '' : 'Height must be greater than 0 cm.',
                height_feet: (value, values) => {
                    if (values.height_unit !== 'ft_in' || (value === '' && values.height_inches === '')) return '';
                    return Number.isInteger(Number(value)) && Number(value) >= 1 ? '' : 'Feet must be a positive whole number.';
                },
                height_inches: (value, values) => {
                    if (values.height_unit !== 'ft_in' || (value === '' && values.height_feet === '')) return '';
                    return value !== '' && Number.isFinite(Number(value)) && Number(value) >= 0 && Number(value) <= 11.99 ? '' : 'Inches must be between 0 and 11.99.';
                },
                weight: (value) => Number(value) >= 25 && Number(value) <= 250 ? '' : 'Weight must be from 25 to 250 kg.',
                pre_pregnancy_weight: (value) => value === '' || value === null || value === undefined || (Number(value) >= 25 && Number(value) <= 250) ? '' : 'Pre-pregnancy weight must be from 25 to 250 kg.',
                pre_pregnancy_bmi: (value) => value === '' || value === null || value === undefined || (Number(value) >= 10 && Number(value) <= 70) ? '' : 'Calculated pre-pregnancy BMI must be from 10 to 70.',
                bp_systolic: (value) => Number(value) >= 50 && Number(value) <= 260 ? '' : 'Systolic BP must be from 50 to 260 mmHg.',
                bp_diastolic: (value, values) => {
                    if (!(Number(value) >= 30 && Number(value) <= 160)) return 'Diastolic BP must be from 30 to 160 mmHg.';
                    if (Number(value) >= Number(values.bp_systolic)) return 'Diastolic BP should be lower than systolic BP.';
                    return '';
                },
                blood_sugar_test_type: (value) => bloodSugarTestTypes[value] ? '' : 'Blood Sugar Test Type is required.',
                blood_sugar: (value) => Number(value) >= 20 && Number(value) <= 700 ? '' : 'Blood sugar must be from 20 to 700 mg/dL.',
                temperature: (value) => Number(value) >= 30 && Number(value) <= 45 ? '' : 'Body temperature must be entered in Celsius from 30 to 45 C.',
                heart_rate: (value) => Number(value) >= 30 && Number(value) <= 220 ? '' : 'Heart rate must be from 30 to 220 bpm.',
            };

            const formValues = () => Object.fromEntries(new FormData(form).entries());

            const setErrors = (errors, showGlobal = false) => {
                form.querySelectorAll('[data-field-error]').forEach((node) => {
                    const name = node.dataset.fieldError;
                    const message = errors[name]?.[0] || '';
                    node.textContent = message;
                    node.closest('label')?.classList.toggle('has-error', Boolean(message));
                });

                const messages = Object.values(errors).flat();
                if (showGlobal && messages.length) {
                    warningList.innerHTML = messages.map((message) => `<li>${escapeHtml(message)}</li>`).join('');
                    warning.hidden = false;
                } else {
                    warning.hidden = true;
                }
            };

            const validateClient = (showAll = false) => {
                const values = formValues();
                const errors = {};
                Object.entries(fieldRules).forEach(([field, rule]) => {
                    const message = rule(values[field], values);
                    if (message && (showAll || touched.has(field))) {
                        errors[field] = [message];
                    }
                });
                setErrors(errors, showAll);
                return errors;
            };

            const fillForm = (record = null) => {
                form.reset();
                form.querySelector('[data-vitals-record-id]').value = record?.id || '';
                form.querySelector('[data-vitals-confirmed]').value = '';
                form.querySelector('[name="recorded_at"]').value = record?.recorded_at || new Date().toISOString().slice(0, 10);
                form.querySelector('[name="pregnancy_week"]').value = record?.pregnancy_week || vitalsState.latest?.pregnancy_week || '';
                heightUnitInput.value = 'cm';
                previousHeightUnit = 'cm';
                heightCmInput.value = record?.height_cm ?? vitalsState.defaults?.height_cm ?? '';
                heightFeetInput.value = '';
                heightInchesInput.value = '';
                ['weight', 'bp_systolic', 'bp_diastolic', 'blood_sugar_test_type', 'blood_sugar', 'temperature', 'heart_rate', 'notes'].forEach((field) => {
                    form.querySelector(`[name="${field}"]`).value = record?.[field] ?? '';
                });
                prePregnancyWeightInput.value = record?.pre_pregnancy_weight ?? vitalsState.defaults?.pre_pregnancy_weight ?? '';
                syncHeightControls();
                syncPrePregnancyBmi();
                document.querySelector('[data-vitals-title]').textContent = record ? 'Edit Maternal Vitals' : 'Add Maternal Vitals';
                saveText.textContent = record ? 'Update Vitals' : 'Save Vitals';
                touched.clear();
                setErrors({});
            };

            const openModal = (record = null) => {
                fillForm(record);
                modal.hidden = false;
                document.body.classList.add('has-casefile-modal');
                form.querySelector('[name="recorded_at"]').focus();
            };

            const closeModal = () => {
                modal.hidden = true;
                document.body.classList.remove('has-casefile-modal');
                setErrors({});
                touched.clear();
            };

            const setSaving = (isSaving) => {
                saveButton.disabled = isSaving;
                spinner.hidden = !isSaving;
                saveText.textContent = isSaving ? 'Saving...' : (form.querySelector('[data-vitals-record-id]').value ? 'Update Vitals' : 'Save Vitals');
            };

            tabs.forEach((tab) => {
                tab.addEventListener('click', () => {
                    const target = tab.dataset.casefileTab;
                    tabs.forEach((item) => {
                        const isActive = item === tab;
                        item.classList.toggle('is-active', isActive);
                        item.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    });
                    panels.forEach((panel) => {
                        panel.hidden = panel.dataset.casefilePanel !== target;
                    });
                });
            });

            document.querySelector('[data-activity-toggle]')?.addEventListener('click', (event) => {
                const list = document.querySelector('[data-activity-list]');
                if (!list) return;
                const isExpanded = list.classList.toggle('is-expanded');
                event.currentTarget.textContent = isExpanded
                    ? event.currentTarget.dataset.lessLabel
                    : event.currentTarget.dataset.moreLabel;
            });

            document.querySelectorAll('[data-vitals-open]').forEach((button) => {
                button.addEventListener('click', () => openModal());
            });
            heightUnitInput?.addEventListener('change', () => {
                const nextUnit = heightUnitInput.value;
                const currentHeightCm = readHeightCm(previousHeightUnit);
                if (currentHeightCm !== null) {
                    writeHeight(currentHeightCm, nextUnit);
                } else if (nextUnit === 'ft_in') {
                    heightFeetInput.value = '';
                    heightInchesInput.value = '';
                } else {
                    heightCmInput.value = '';
                }
                previousHeightUnit = nextUnit;
                syncHeightControls();
                syncPrePregnancyBmi();
                touched.add('height_cm');
                validateClient(false);
            });
            [heightCmInput, heightFeetInput, heightInchesInput, prePregnancyWeightInput].forEach((input) => {
                input?.addEventListener('input', () => {
                    syncPrePregnancyBmi();
                    if (input.name) {
                        touched.add(input.name);
                        validateClient(false);
                    }
                });
            });
            modal?.querySelectorAll('[data-vitals-close]').forEach((button) => button.addEventListener('click', closeModal));
            historyModal?.querySelectorAll('[data-history-close]').forEach((button) => button.addEventListener('click', closeHistoryModal));

            // Vital detail modal close events
            vitalDetailModal?.querySelectorAll('[data-vital-detail-close]').forEach(el => el.addEventListener('click', closeVitalDetailModal));
            vitalDetailModal?.querySelector('[data-vital-detail-modal-backdrop]')?.addEventListener('click', closeVitalDetailModal);

            historySearch?.addEventListener('input', renderHistoryModal);
            historySort?.addEventListener('click', () => {
                historySortDirection = historySortDirection === 'desc' ? 'asc' : 'desc';
                renderHistoryModal();
            });
            form?.addEventListener('input', (event) => {
                if (event.target.name) {
                    touched.add(event.target.name);
                    validateClient(false);
                }
            });

            document.addEventListener('click', async (event) => {
                // Handle vital detail toggle (mobile) – open modal
                const toggleButton = event.target.closest('[data-vital-toggle]');
                if (toggleButton) {
                    event.preventDefault();
                    const cardKey = toggleButton.dataset.vitalToggle;
                    openVitalDetailModal(cardKey);
                    return;
                }

                const historyButton = event.target.closest('[data-history-open]');
                if (historyButton) {
                    event.preventDefault();
                    openHistoryModal(historyButton.dataset.historyOpen);
                    return;
                }

                const historyEditButton = event.target.closest('[data-history-edit-record]');
                if (historyEditButton) {
                    const record = (vitalsState.records || []).find((item) => String(item.id) === String(historyEditButton.dataset.historyEditRecord));
                    if (record) {
                        closeHistoryModal();
                        openModal(record);
                    }
                    return;
                }

                const historyDeleteButton = event.target.closest('[data-history-delete-record]');
                if (historyDeleteButton) {
                    const recordId = historyDeleteButton.dataset.historyDeleteRecord;
                    if (!confirm('Delete this maternal vital record? This will update the latest vitals, charts, and history.')) return;

                    historyDeleteButton.disabled = true;

                    try {
                        const response = await fetch(deleteVitalsUrl(recordId), {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                        });
                        const data = await response.json();

                        if (!response.ok) {
                            alert(data.message || 'Unable to delete this maternal vital record.');
                            return;
                        }

                        renderVitals(data);
                        if (success) {
                            success.textContent = data.message || 'Maternal vitals deleted.';
                            success.hidden = false;
                            setTimeout(() => { success.hidden = true; }, 3200);
                        }
                    } catch (error) {
                        alert('Unable to reach the server. Please try again.');
                    } finally {
                        historyDeleteButton.disabled = false;
                    }
                    return;
                }

                const editButton = event.target.closest('[data-edit-record]');
                if (!editButton) return;
                const record = (vitalsState.records || []).find((item) => String(item.id) === String(editButton.dataset.editRecord));
                if (record) openModal(record);
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') return;
                if (vitalDetailModal && !vitalDetailModal.hidden) {
                    closeVitalDetailModal();
                    return;
                }
                if (historyModal && !historyModal.hidden) {
                    closeHistoryModal();
                    return;
                }
                if (modal && !modal.hidden) closeModal();
            });

            form?.addEventListener('submit', async (event) => {
                event.preventDefault();
                const clientErrors = validateClient(true);
                if (Object.keys(clientErrors).length) return;

                const recordId = form.querySelector('[data-vitals-record-id]').value;
                setSaving(true);

                try {
                    let response = await fetch(recordId ? updateVitalsUrl(recordId) : motherVitalsUrl, {
                        method: recordId ? 'PUT' : 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify(formValues()),
                    });
                    let data = await response.json();

                    if (response.status === 409 && data.requires_confirmation) {
                        const warnings = (data.warnings || []).map((warning) => `- ${warning}`).join('\n');
                        const approved = confirm(`${data.message || 'Confirm flagged values before saving.'}\n\n${warnings}`);

                        if (!approved) {
                            setErrors({ form: [data.message || 'Confirm flagged values before saving.'] }, true);
                            return;
                        }

                        const confirmedValues = formValues();
                        confirmedValues.confirmed_unusual = '1';
                        form.querySelector('[data-vitals-confirmed]').value = '1';

                        response = await fetch(recordId ? updateVitalsUrl(recordId) : motherVitalsUrl, {
                            method: recordId ? 'PUT' : 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify(confirmedValues),
                        });
                        data = await response.json();
                    }

                    if (!response.ok) {
                        setErrors(data.errors || { form: [data.message || 'Unable to save maternal vitals.'] }, true);
                        return;
                    }

                    renderVitals(data);
                    closeModal();
                    if (success) {
                        success.textContent = data.message || 'Maternal vitals saved.';
                        success.hidden = false;
                        setTimeout(() => { success.hidden = true; }, 3200);
                    }
                } catch (error) {
                    setErrors({ form: ['Unable to reach the server. Please try again.'] }, true);
                } finally {
                    setSaving(false);
                }
            });

            renderVitals(vitalsState);
        })();
    </script>
    <script src="{{ asset('js/document-preview.js') }}?v={{ filemtime(public_path('js/document-preview.js')) }}" defer></script>
    <script src="{{ asset('js/mother-information.js') }}?v={{ filemtime(public_path('js/mother-information.js')) }}" defer></script>
    <script src="{{ asset('js/mother-care-record.js') }}?v={{ filemtime(public_path('js/mother-care-record.js')) }}" defer></script>
@endsection
