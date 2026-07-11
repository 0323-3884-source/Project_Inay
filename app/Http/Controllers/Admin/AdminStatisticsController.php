<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaternalMonitoringRecord;
use App\Models\Mother;
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
        $highRiskPregnancies = $pregnantMothers
            ->filter(fn (Mother $mother): bool => strtolower((string) ($latestRiskRecords[$mother->id]?->risk_level ?? '')) === 'high')
            ->count();
        $lowRiskPregnancies = $pregnantMothers
            ->filter(fn (Mother $mother): bool => strtolower((string) ($latestRiskRecords[$mother->id]?->risk_level ?? '')) === 'low')
            ->count();
        $barangaysRepresented = $mothers
            ->pluck('barangay')
            ->filter()
            ->unique()
            ->count();
        $fourPsPercentage = $totalMothers > 0 ? round(($total4ps / $totalMothers) * 100, 1) : 0;

        $pregnantByBarangay = Mother::query()
            ->where('pregnancy_status', 'pregnant')
            ->selectRaw('barangay, COUNT(*) as total')
            ->groupBy('barangay')
            ->orderByDesc('total')
            ->orderBy('barangay')
            ->get()
            ->map(fn ($row): array => [
                'barangay' => $row->barangay ?: 'Unspecified',
                'total' => (int) $row->total,
            ])
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
                ['title' => 'High-Risk Pregnancies', 'count' => $highRiskPregnancies, 'icon' => 'alert'],
                ['title' => 'Active Pregnancies', 'count' => $activePregnancies, 'icon' => 'activity'],
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
                'high_risk' => $highRiskPregnancies,
                'low_risk' => $lowRiskPregnancies,
            ],
        ]);
    }
}
