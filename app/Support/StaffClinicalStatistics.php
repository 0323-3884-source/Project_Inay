<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StaffClinicalStatistics
{
    // These collections have already been restricted to the staff member's casefiles.
    public static function build(Collection $mothers, Collection $maternalRows, Collection $newbornRows, Carbon $today): array
    {
        $pregnant = $mothers->where('pregnancy_status', 'pregnant');
        $fourPs = $mothers->where('is_4ps_beneficiary', true)->count();
        $nonFourPs = $mothers->filter(fn ($mother) => $mother->is_4ps_beneficiary === false)->count();
        // Reuse Admin Statistics' barangay normalization and active-pregnancy denominator.
        $barangays = $pregnant->groupBy(function ($mother): string {
            $name = preg_replace('/^barangay\s+/i', '', trim((string) $mother->barangay));
            $name = preg_replace('/\s+/', ' ', $name);
            return trim(Str::of($name)->replaceMatches('/\bSta\.?\b/i', 'Santa')
                ->replaceMatches('/\bSto\.?\b/i', 'Santo')->replaceMatches('/\bSt\.?\b/i', 'Santa')->title()->toString()) ?: 'Unspecified';
        })->map->count()->sortDesc()->all();
        $vaccines = $newbornRows->flatMap(fn ($row) => $row['infant']->vaccineRecords);
        $vaccineCounts = ['Completed' => 0, 'Pending / upcoming' => 0, 'Overdue / missed' => 0, 'Cancelled' => 0];
        foreach ($vaccines as $vaccine) {
            $bucket = match (true) {
                $vaccine->status === 'completed' => 'Completed',
                $vaccine->status === 'cancelled' => 'Cancelled',
                in_array($vaccine->status, ['overdue', 'missed'], true)
                    || ($vaccine->due_date && $vaccine->due_date->isBefore($today)) => 'Overdue / missed',
                default => 'Pending / upcoming',
            };
            $vaccineCounts[$bucket]++;
        }
        $pregnancy = $mothers->groupBy(fn ($mother) => $mother->pregnancy_status
            ? ucfirst(str_replace('_', ' ', $mother->pregnancy_status)) : 'Not recorded')->map->count()->all();
        $screenings = $maternalRows->groupBy('risk_label')->map->count()->all();
        $newborn = [
            'Linked newborns' => $newbornRows->count(),
            'At risk / needing review' => $newbornRows->where('status', 'at_risk')->count(),
            'Growth needs review' => $newbornRows->filter(fn ($row) => $row['latest_growth'] && in_array($row['growth_label'], ['Growth Needs Review', 'Needs Follow-up'], true))->count(),
            'Awaiting growth baseline' => $newbornRows->whereNull('latest_growth')->count(),
            'With active health alerts' => $newbornRows->filter(fn ($row) => $row['active_alerts'] > 0)->count(),
        ];
        return [
            'summary' => [
                'Total Assigned Mothers' => $mothers->count(), 'Total 4Ps Beneficiaries' => $fourPs,
                'Total Non-4Ps' => $nonFourPs, 'Active Pregnancies' => $pregnant->count(),
                'For Review Screenings' => $maternalRows->where('risk_level', 'for_review')->count(),
                'Linked Newborns' => $newbornRows->count(), 'At Risk Newborns' => $newborn['At risk / needing review'],
                'Completed Vaccine Doses' => $vaccineCounts['Completed'],
            ],
            'fourPsPercentage' => $mothers->isEmpty() ? 0 : round($fourPs / $mothers->count() * 100, 1),
            'sections' => [
                ['title' => 'Maternal Statistics: Pregnancy Status', 'note' => 'Current status of all assigned mothers.', 'rows' => $pregnancy, 'total' => $mothers->count()],
                ['title' => 'Maternal Screening Status', 'note' => 'Latest screening per assigned mother, including urgent referral and for-review cases.', 'rows' => $screenings, 'total' => $mothers->count()],
                ['title' => 'Pregnant Mothers by Barangay', 'note' => 'Share of active pregnancies in assigned casefiles.', 'rows' => $barangays, 'total' => $pregnant->count()],
                ['title' => '4Ps Distribution', 'note' => 'Coverage among all assigned mothers; unknown status is separate.', 'rows' => ['4Ps Beneficiaries' => $fourPs, 'Non-4Ps' => $nonFourPs, 'Not recorded' => $mothers->count() - $fourPs - $nonFourPs], 'total' => $mothers->count(), 'donut' => true],
                ['title' => 'Newborn Statistics', 'note' => 'Counts can overlap: a newborn may need growth review and have an active alert.', 'rows' => $newborn, 'total' => $newbornRows->count()],
                ['title' => 'Vaccine Statistics', 'note' => 'Recorded doses only. Past-due uncompleted doses are overdue; cancelled doses are separate.', 'rows' => $vaccineCounts, 'total' => $vaccines->count()],
            ],
        ];
    }
}
