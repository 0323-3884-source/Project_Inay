<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaternalMonitoringRecord;
use App\Models\Mother;
use App\Models\ProgramStaff;
use App\Support\MaternalVitalScreening;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminStatisticsController extends Controller
{
    public function __invoke(): View
    {
        $mothers = Mother::query()
            ->select(['id', 'barangay', 'pregnancy_status', 'is_4ps_beneficiary', 'created_at'])
            ->get();

        $pregnantMothers = $mothers->where('pregnancy_status', 'pregnant')->values();
        $pregnantMotherIds = $pregnantMothers->pluck('id');
        $latestRiskRecords = MaternalMonitoringRecord::query()
            ->whereIn('mother_id', $pregnantMotherIds)
            ->orderByDesc('recorded_at')
            ->orderByDesc('created_at')
            ->get()
            ->unique('mother_id')
            ->keyBy('mother_id');

        $totalMothers = $mothers->count();
        $total4ps = $mothers->where('is_4ps_beneficiary', true)->count();
        $totalNon4ps = max(0, $totalMothers - $total4ps);
        $activePregnancies = $pregnantMothers->count();
        $completedPregnancies = $mothers->where('pregnancy_status', 'postpartum')->count();
        $forReviewPregnancies = $pregnantMothers
            ->filter(function (Mother $mother) use ($latestRiskRecords): bool {
                $record = $latestRiskRecords->get($mother->id);

                return MaternalVitalScreening::normalizeStatus($record?->screening_summary_status ?? $record?->risk_level) === MaternalVitalScreening::STATUS_REVIEW;
            })
            ->count();
        $withinReferencePregnancies = $pregnantMothers
            ->filter(function (Mother $mother) use ($latestRiskRecords): bool {
                $record = $latestRiskRecords->get($mother->id);

                return MaternalVitalScreening::normalizeStatus($record?->screening_summary_status ?? $record?->risk_level) === MaternalVitalScreening::STATUS_WITHIN;
            })
            ->count();
        $barangaysRepresented = $mothers
            ->map(fn (Mother $mother): string => $this->normalizeBarangay($mother->barangay))
            ->filter(fn (string $barangay): bool => $barangay !== 'Unspecified')
            ->unique()
            ->count();
        $fourPsPercentage = $totalMothers > 0 ? round(($total4ps / $totalMothers) * 100, 1) : 0;
        $staffIdentityStats = [
            'total' => ProgramStaff::count(),
            'account_pending' => ProgramStaff::where('approval_status', 'pending')->count(),
            'account_approved' => ProgramStaff::where('approval_status', 'approved')->count(),
            'account_rejected' => ProgramStaff::where('approval_status', 'rejected')->count(),
            'verified' => ProgramStaff::whereNotNull('healthcare_worker_id_verified_at')->count(),
            'pending' => ProgramStaff::whereNotNull('healthcare_worker_id_photo_path')
                ->whereNull('healthcare_worker_id_verified_at')
                ->count(),
            'missing' => ProgramStaff::whereNull('healthcare_worker_id_photo_path')->count(),
            'complete_profiles' => ProgramStaff::whereNotNull('contact_number')
                ->where(function ($query): void {
                    $query->whereNotNull('role')->orWhereNotNull('position');
                })
                ->count(),
        ];

        $pregnantByBarangay = $pregnantMothers
            ->groupBy(fn (Mother $mother): string => $this->normalizeBarangay($mother->barangay))
            ->map(function (Collection $rows, string $barangay) use ($activePregnancies): array {
                $total = $rows->count();
                $fourPs = $rows->where('is_4ps_beneficiary', true)->count();

                return [
                    'barangay' => $barangay,
                    'total' => $total,
                    'percentage' => $activePregnancies > 0 ? round(($total / $activePregnancies) * 100, 1) : 0,
                    'total_4ps' => $fourPs,
                    'total_non_4ps' => max(0, $total - $fourPs),
                ];
            })
            ->sort(fn (array $left, array $right): int => ($right['total'] <=> $left['total']) ?: strcmp($left['barangay'], $right['barangay']))
            ->values();

        $topBarangays = $pregnantByBarangay->take(8)->values();
        $maxBarangayCount = max(1, (int) $pregnantByBarangay->max('total'));
        $maxTopCount = max(1, (int) $topBarangays->max('total'));

        $monthStart = now()->startOfMonth()->subMonths(11);
        $registrationRows = Mother::query()
            ->where('pregnancy_status', 'pregnant')
            ->where('created_at', '>=', $monthStart)
            ->get(['created_at'])
            ->groupBy(fn (Mother $mother): string => $mother->created_at?->format('Y-m') ?? 'unknown');

        $monthlyRegistrations = collect(range(0, 11))
            ->map(function (int $offset) use ($monthStart, $registrationRows): array {
                $month = $monthStart->copy()->addMonths($offset);
                $key = $month->format('Y-m');

                return [
                    'key' => $key,
                    'label' => $month->format('M Y'),
                    'short_label' => $month->format('M'),
                    'total' => $registrationRows->get($key, collect())->count(),
                ];
            })
            ->values();
        $maxMonthlyCount = max(1, (int) $monthlyRegistrations->max('total'));

        return view('admin.statistics', [
            'adminUsername' => session('admin_username', 'admin'),
            'summaryCards' => [
                ['title' => 'Total Mothers', 'count' => $totalMothers, 'icon' => 'users'],
                ['title' => 'Total 4Ps Beneficiaries', 'count' => $total4ps, 'icon' => 'heart'],
                ['title' => 'Total Non-4Ps', 'count' => $totalNon4ps, 'icon' => 'shield'],
                ['title' => 'Barangays Represented', 'count' => $barangaysRepresented, 'icon' => 'map'],
                ['title' => 'For Review Screenings', 'count' => $forReviewPregnancies, 'icon' => 'alert'],
                ['title' => 'Active Pregnancies', 'count' => $activePregnancies, 'icon' => 'activity'],
                ['title' => 'Program Staff', 'count' => $staffIdentityStats['total'], 'icon' => 'users'],
                ['title' => 'Pending Staff Approval', 'count' => $staffIdentityStats['account_pending'], 'icon' => 'alert'],
                ['title' => 'Verified Staff IDs', 'count' => $staffIdentityStats['verified'], 'icon' => 'shield'],
            ],
            'pregnantByBarangay' => $pregnantByBarangay,
            'topBarangays' => $topBarangays,
            'maxBarangayCount' => $maxBarangayCount,
            'maxTopCount' => $maxTopCount,
            'monthlyRegistrations' => $monthlyRegistrations,
            'maxMonthlyCount' => $maxMonthlyCount,
            'beneficiaryStats' => [
                'total_mothers' => $totalMothers,
                'total_4ps' => $total4ps,
                'total_non_4ps' => $totalNon4ps,
                'percentage_4ps' => $fourPsPercentage,
            ],
            'pregnancyStats' => [
                'total_pregnant' => $activePregnancies,
                'active' => $activePregnancies,
                'completed' => $completedPregnancies,
                'for_review' => $forReviewPregnancies,
                'within_reference_range' => $withinReferencePregnancies,
            ],
            'staffIdentityStats' => $staffIdentityStats,
        ]);
    }

    private function normalizeBarangay(?string $barangay): string
    {
        $barangay = trim((string) $barangay);

        if ($barangay === '') {
            return 'Unspecified';
        }

        $barangay = preg_replace('/^barangay\s+/i', '', $barangay) ?? $barangay;
        $barangay = preg_replace('/\s+/', ' ', $barangay) ?? $barangay;
        $barangay = Str::of($barangay)
            ->replaceMatches('/\bSta\.?\b/i', 'Santa')
            ->replaceMatches('/\bSto\.?\b/i', 'Santo')
            ->replaceMatches('/\bSt\.?\b/i', 'Santa')
            ->title()
            ->toString();

        return trim($barangay) ?: 'Unspecified';
    }
}
