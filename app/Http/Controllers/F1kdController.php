<?php

namespace App\Http\Controllers;

use App\Models\F1kdMonitoring;
use App\Models\ProgramStaff;
use App\Models\StaffMotherCasefile;
use App\Support\F1kdCompliance;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

class F1kdController extends Controller
{
    public function __construct(private F1kdCompliance $compliance) {}

    public function dashboard(Request $request)
    {
        $filters = $this->compliance->filters($request);
        $rows = $this->compliance->rows($filters);
        return view('dswd.f1kd.dashboard', [
            'summary' => $this->compliance->summary($rows),
            'filters' => $filters,
        ]);
    }

    public function index(Request $request)
    {
        $filters = $this->compliance->filters($request);
        $rows = $this->compliance->rows($filters);
        $page = max(1, (int) $request->input('page', 1));
        $paginator = new LengthAwarePaginator($rows->forPage($page, 15)->values(), $rows->count(), 15, $page, ['path' => $request->url(), 'query' => $request->query()]);

        $sections = [];
        foreach (['pregnant'=>'Maternal Monitoring', 'child'=>'Children 0–2 Years Monitoring'] as $classification=>$title) {
            $sectionRows = $rows->where('classification', $classification)->values();
            $pageName = $classification.'_page';
            $sectionPage = max(1, (int) $request->input($pageName, 1));
            $sections[] = [
                'title'=>$title, 'classification'=>$classification,
                'beneficiaries'=>new LengthAwarePaginator($sectionRows->forPage($sectionPage, 15)->values(), $sectionRows->count(), 15, $sectionPage, ['path'=>$request->url(), 'query'=>$request->query(), 'pageName'=>$pageName]),
            ];
        }

        return view('dswd.f1kd.index', $this->viewData($filters) + ['beneficiaries' => $paginator, 'sections'=>$sections]);
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
        $history = F1kdMonitoring::with(['recordedByStaff', 'verifiedByDswdStaff'])->where('subject_key', $subject)
            ->orderByDesc('reporting_month')->paginate(12)->withQueryString();

        $childGrowth = null;
        if ($beneficiary->classification === 'child') {
            $child = \App\Models\Infant::where('mother_id', $beneficiary->mother_id)->findOrFail($beneficiary->infant_id);
            $through = min(now()->toDateString(), $beneficiary->month.'-'.\Carbon\CarbonImmutable::parse($beneficiary->month.'-01')->daysInMonth);
            $records = $child->growthRecords()->select(['id', 'infant_id', 'recorded_by_staff_id', 'measured_at', 'age_months', 'weight', 'height'])
                ->whereDate('measured_at', '<=', $through)->with('recorder:id,first_name,middle_name,last_name')->get();
            $growthHistory = $records->sortByDesc(fn ($record) => $record->measured_at->format('Y-m-d').'-'.str_pad((string) $record->id, 20, '0', STR_PAD_LEFT))->values()->map(fn ($record) => (object) [
                'age' => \App\Support\ChildProfileDisplay::months($child->getRawOriginal('birth_date'), $record->getRawOriginal('measured_at')),
                'weight' => $record->weight, 'height' => $record->height,
                'date' => $record->measured_at, 'recorder' => $record->recorder?->full_name ?: 'Not recorded',
            ]);
            $growthPage = max(1, (int) $request->query('growth_page', 1));
            $growthPage = min($growthPage, max(1, (int) ceil($growthHistory->count() / 5)));
            $childGrowth = [
                'weight' => \App\Support\ChildProfileDisplay::chart($records, 'weight', $child->getRawOriginal('birth_date')),
                'height' => \App\Support\ChildProfileDisplay::chart($records, 'height', $child->getRawOriginal('birth_date')),
                'history' => new LengthAwarePaginator($growthHistory->forPage($growthPage, 5)->values(), $growthHistory->count(), 5, $growthPage, [
                    'path' => $request->url(), 'query' => $request->query(), 'pageName' => 'growth_page', 'fragment' => 'growth-history',
                ]),
            ];
        }
        return view('dswd.f1kd.show', compact('beneficiary', 'history', 'filters', 'childGrowth'));
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
                $write(['Municipality / Barangay', 'Total', '4Ps Mothers', 'Children 0–24 months', 'Compliant', 'For Verification', 'Non-Compliant']);
                $write(['All selected areas', ...array_values($summary)]);
                foreach ($breakdown as $area => $counts) {
                    $write([$area, ...array_values($counts)]);
                }
                fclose($out);
            }, 'inay-f1kd-'.$filters['month'].'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $page = max(1, (int) $request->input('page', 1));
        $beneficiaries = new LengthAwarePaginator($rows->forPage($page, 15)->values(), $rows->count(), 15, $page, ['path' => $request->url(), 'query' => $request->query()]);
        return view('dswd.f1kd.reports', $this->viewData($filters) + compact('summary', 'breakdown', 'beneficiaries'));
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

        $history = F1kdMonitoring::with(['recordedByStaff', 'verifiedByDswdStaff'])->where('subject_key', $subject)->orderByDesc('reporting_month')->paginate(12)->withQueryString();
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
        $this->compliance->saveAttendance($beneficiary, $data, 'staff', (int) $request->session()->get('auth_id'));

        return redirect()->route('staff.f1kd.edit', ['subject' => $subject, 'month' => $filters['month']])->with('status', 'Monthly F1KD attendance saved.');
    }

    public function updateDswd(Request $request, string $subject)
    {
        // dswd.auth checks role, account existence and active status.
        abort_unless($request->attributes->get('dswd_staff'), 403);
        $data = $request->validate([
            'month'=>['required','date_format:Y-m','before_or_equal:'.now()->format('Y-m')],
            'attendance_status'=>['required',Rule::in(array_keys(F1kdCompliance::ATTENDANCE))],
            'remark_code'=>['nullable',Rule::in(array_keys(F1kdCompliance::REMARKS))],
        ]);
        $beneficiary = $this->compliance->beneficiary($subject, $data['month']);
        abort_unless($beneficiary, 404);
        $this->compliance->saveAttendance($beneficiary, $data, 'dswd_staff', $request->attributes->get('dswd_staff')->id);
        return redirect()->route('dswd.f1kd.show', ['subject'=>$subject, 'month'=>$data['month']])->with('status', 'Shared attendance updated and verified.');
    }
}
