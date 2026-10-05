<?php

namespace App\Http\Controllers;

use App\Models\F1kdMonitoring;
use App\Models\ProgramStaff;
use App\Models\StaffMotherCasefile;
use App\Support\F1kdCompliance;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class F1kdController extends Controller
{
    public function __construct(private F1kdCompliance $compliance) {}

    public function dashboard(Request $request)
    {
        $filters = $this->compliance->filters($request);
        $rows = $this->compliance->rows($filters);
        $charts = [];
        foreach (F1kdCompliance::CLASSES as $key => $label) {
            $charts[$label.' by barangay'] = $rows->where('classification', $key)->countBy('barangay')->all();
        }
        $charts['F1KD compliance status'] = $rows->countBy(fn ($r) => F1kdCompliance::STATUSES[$r->status])->all();
        $monthly = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->startOfMonth()->subMonths($i)->format('Y-m');
            $monthly[$month] = $this->compliance->rows(['month' => $month])->count();
        }
        $charts['Monthly F1KD monitoring (beneficiaries)'] = $monthly;

        return view('dswd.f1kd.dashboard', ['summary' => $this->compliance->summary($rows), 'charts' => $charts, 'filters' => $filters]);
    }

    public function index(Request $request)
    {
        $filters = $this->compliance->filters($request);
        $rows = $this->compliance->rows($filters);
        $page = max(1, (int) $request->input('page', 1));
        $paginator = new LengthAwarePaginator($rows->forPage($page, 15)->values(), $rows->count(), 15, $page, ['path' => $request->url(), 'query' => $request->query()]);

        return view('dswd.f1kd.index', $this->viewData($filters) + ['beneficiaries' => $paginator]);
    }

    private function viewData(array $filters): array
    {
        $all = $this->compliance->rows(['month' => $filters['month']]);

        return ['filters' => $filters, 'options' => ['barangay' => $all->pluck('barangay')->unique()->sort(), 'municipality_city' => $all->pluck('municipality_city')->unique()->sort()]];
    }

    public function show(Request $request, string $subject)
    {
        $filters = $this->compliance->filters($request);
        $beneficiary = $this->compliance->beneficiary($subject, $filters['month']);
        abort_unless($beneficiary, 404);
        $history = F1kdMonitoring::where('subject_key', $subject)
            ->orderByDesc('reporting_month')->paginate(12)->withQueryString();

        return view('dswd.f1kd.show', compact('beneficiary', 'history', 'filters'));
    }

    public function reports(Request $request)
    {
        $filters = $this->compliance->filters($request);
        $rows = $this->compliance->rows($filters);
        $summary = $this->compliance->summary($rows);
        $breakdown = $rows->groupBy(fn ($r) => $r->municipality_city.' / '.$r->barangay)->map(fn ($group) => $this->compliance->summary($group));
        if ($request->routeIs('dswd.f1kd.reports.download')) {
            return response()->streamDownload(function () use ($filters, $summary, $breakdown) {
                $out = fopen('php://output', 'w');
                $write = fn ($values) => fputcsv($out, array_map(fn ($v) => is_string($v) && preg_match('/^[\s]*[=+@-]/u', $v) ? "'".$v : $v, $values), ',', '"', '');
                $write(['Project INAY — Monthly F1KD Report', $filters['month']]);
                foreach ($filters as $key => $value) {
                    if ($value !== null && $value !== '') {
                        $write([$key, $value]);
                    }
                }
                $write(['Municipality / Barangay', 'Total', 'Pregnant', 'Children 0–24 months', 'Compliant', 'For Verification', 'Non-Compliant']);
                $write(['All selected areas', ...array_values($summary)]);
                foreach ($breakdown as $area => $counts) {
                    $write([$area, ...array_values($counts)]);
                }
                fclose($out);
            }, 'inay-f1kd-'.$filters['month'].'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return view('dswd.f1kd.reports', $this->viewData($filters) + compact('summary', 'breakdown'));
    }

    private function authorizeStaff(Request $request, int $motherId): void
    {
        abort_unless($request->session()->get('auth_role') === 'staff', 403);
        $staff = ProgramStaff::find($request->session()->get('auth_id'));
        abort_unless($staff && $staff->approval_status === 'approved' && StaffMotherCasefile::where('staff_id', $staff->id)->where('mother_id', $motherId)->exists(), 403);
    }

    public function edit(Request $request, string $subject)
    {
        $filters = $this->compliance->filters($request);
        $beneficiary = $this->compliance->beneficiary($subject, $filters['month']);
        abort_unless($beneficiary, 404);
        $this->authorizeStaff($request, $beneficiary->mother_id);

        $history = F1kdMonitoring::where('subject_key', $subject)->orderByDesc('reporting_month')->paginate(12)->withQueryString();
        return view('dswd.f1kd.edit', compact('beneficiary', 'history'));
    }

    public function update(Request $request, string $subject)
    {
        $filters = $this->compliance->filters($request);
        $beneficiary = $this->compliance->beneficiary($subject, $filters['month']);
        abort_unless($beneficiary, 404);
        $this->authorizeStaff($request, $beneficiary->mother_id);
        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m', 'before_or_equal:'.now()->format('Y-m')],
            'attendance_status' => ['required', Rule::in(array_keys(F1kdCompliance::ATTENDANCE))],
            'remark_code' => ['nullable', Rule::in(array_keys(F1kdCompliance::REMARKS))],
        ]);
        DB::transaction(function () use ($subject, $filters, $beneficiary, $data, $request) {
            // Atomic upsert respects the existing unique subject/month index,
            // even for simultaneous saves. Never update legacy checklist JSON.
            F1kdMonitoring::upsert([[
                'subject_key' => $subject, 'reporting_month' => (new F1kdMonitoring)->fromDateTime($filters['month'].'-01'),
                'mother_id' => $beneficiary->mother_id, 'infant_id' => $beneficiary->infant_id,
                'classification' => $beneficiary->classification, 'barangay' => $beneficiary->barangay,
                'municipality_city' => $beneficiary->municipality_city, 'checklist' => '[]',
                'attendance_status' => $data['attendance_status'], 'remark_code' => $data['remark_code'] ?? null,
                'status' => F1kdCompliance::attendanceStatus($data['attendance_status']),
                'recorded_by_staff_id' => $request->session()->get('auth_id'),
            ]], ['subject_key', 'reporting_month'], [
                'attendance_status', 'remark_code', 'status', 'recorded_by_staff_id',
            ]);
        });

        return redirect()->route('staff.f1kd.edit', ['subject' => $subject, 'month' => $filters['month']])->with('status', 'Monthly F1KD attendance saved.');
    }
}
