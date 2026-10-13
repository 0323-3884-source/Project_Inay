<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AdminUser, AppNotification, DswdStaff, Infant, InfantVaccineRecord, InayKaalamanCheckup, MaternalMonitoringRecord, Mother};
use App\Support\{DswdStatistics, F1kdCompliance, MaternalVitalScreening};
use Illuminate\Http\Request;

class DepartmentAdminController extends Controller
{
    private function scope(Request $request): string
    {
        $scope = $request->routeIs('admin.dswd.*') ? 'dswd' : 'cho';
        $admin = $request->attributes->get('department_admin');
        abort_unless($admin instanceof AdminUser && in_array($admin->role, ['admin', 'admin_'.$scope], true), 403);
        return $scope;
    }

    private function month(Request $request): string
    {
        return $request->validate(['month' => ['nullable', 'date_format:Y-m', 'before_or_equal:'.now()->format('Y-m')]])['month'] ?? now()->format('Y-m');
    }

    private function statistics(string $scope, string $month): array
    {
        if ($scope === 'dswd') {
            $service = app(F1kdCompliance::class);
            $rows = $service->rows(['month' => $month]);
            $summary = $service->summary($rows);
            $beneficiaries = app(DswdStatistics::class)->summary([]);
            $cards = [
                'Total Registered 4Ps Beneficiaries' => $beneficiaries['total'],
                'Total F1KD Beneficiaries' => $summary['total'],
                'Monthly Health Attendance' => $summary['compliant'],
                'Mothers and Children with Missed Checkups' => $summary['non_compliant'],
                'Total Registered DSWD Staff' => DswdStaff::count(),
                'Attendance Awaiting Recording / Verification' => $summary['verification'],
            ];
            $charts = ['Beneficiaries by Barangay' => $beneficiaries['groups']['barangay']];
            foreach (['pregnant' => 'Mother Monthly Attendance', 'child' => 'Child Monthly Attendance'] as $classification => $title) {
                $subset = $rows->where('classification', $classification);
                $charts[$title] = ['Attended' => $subset->where('status', 'compliant')->count(), 'Missed' => $subset->where('status', 'non_compliant')->count(), 'Not Yet Recorded' => $subset->where('status', 'verification')->count()];
            }
            return compact('cards', 'charts');
        }
        $latest = MaternalMonitoringRecord::whereHas('mother', fn ($q) => $q->where('pregnancy_status', 'pregnant'))
            ->orderByDesc('recorded_at')->orderByDesc('id')->get()->unique('mother_id');
        $pending = $latest->filter(fn ($record) => in_array(MaternalVitalScreening::normalizeStatus($record->screening_summary_status ?? $record->risk_level), [MaternalVitalScreening::STATUS_REVIEW, MaternalVitalScreening::STATUS_INTERPRETATION, MaternalVitalScreening::STATUS_URGENT], true))->count();
        $cards = [
            'Total Registered Mothers' => Mother::count(),
            'Active Pregnancies' => Mother::where('pregnancy_status', 'pregnant')->count(),
            'Total Registered Children' => Infant::count(),
            'Maternal Health Records' => MaternalMonitoringRecord::count(),
            'Consultation Records' => InayKaalamanCheckup::count(),
            'Vaccination Monitoring' => InfantVaccineRecord::count(),
            'Pending Health Screenings' => $pending + Mother::where('pregnancy_status', 'pregnant')->whereDoesntHave('maternalMonitoringRecords')->count(),
        ];
        $barangays = Mother::selectRaw("COALESCE(NULLIF(barangay, ''), 'Not recorded') AS label, COUNT(*) AS total")->groupBy('label')->orderByDesc('total')->pluck('total', 'label')->all();
        $trend = [];
        $end = \Carbon\CarbonImmutable::parse($month.'-01');
        for ($i = 5; $i >= 0; $i--) {
            $start = $end->subMonths($i);
            $trend[$start->format('M Y')] = MaternalMonitoringRecord::whereDate('recorded_at', '>=', $start)->whereDate('recorded_at', '<', $start->addMonth())->count();
        }
        $charts = ['Mothers by Barangay' => $barangays, 'Maternal Records — Last Six Months' => $trend,
            'Vaccination Status' => InfantVaccineRecord::selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status')->all()];
        return compact('cards', 'charts');
    }

    public function dashboard(Request $request)
    {
        $scope = $this->scope($request);
        $month = $this->month($request);
        return view('admin.departments.dashboard', compact('scope', 'month') + $this->statistics($scope, $month));
    }

    public function reports(Request $request)
    {
        $scope = $this->scope($request);
        $month = $this->month($request);
        $data = $this->statistics($scope, $month);
        if ($request->boolean('download')) {
            return response()->streamDownload(function () use ($data, $month, $scope) {
                $file = fopen('php://output', 'w');
                $write = static function (array $row) use ($file) {
                    fputcsv($file, array_map(fn ($value) => is_string($value) && preg_match('/^[=+@\-\t\r]/', $value) ? "'".$value : $value, $row));
                };
                $write(['Project INAY', strtoupper($scope).' report', $month]);
                $write(['Metric', 'Count']);
                foreach ($data['cards'] as $label => $count) $write([$label, $count]);
                foreach ($data['charts'] as $label => $values) {
                    $write([$label, '']);
                    foreach ($values as $group => $count) $write([$group, $count]);
                }
                fclose($file);
            }, "inay-{$scope}-{$month}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
        }
        return view('admin.departments.dashboard', compact('scope', 'month') + $data + ['reportMode' => true]);
    }

    public function module(Request $request, string $module)
    {
        $scope = $this->scope($request);
        $month = $this->month($request);
        $search = $request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? '';
        $editBeneficiary = false;
        $viewClinicalRecord = false;
        $headers = [];
        if ($scope === 'dswd' && $module === 'beneficiaries') {
            $title = '4Ps Beneficiaries';
            $editBeneficiary = true;
            $headers = ['Name', 'Household ID', 'Barangay', 'Municipality / City'];
            $records = app(DswdStatistics::class)->mothers(['q' => $search])->orderBy('last_name')->paginate(20)->withQueryString();
            $records->setCollection($records->getCollection()->map(fn ($mother) => ['id' => $mother->id, 'cells' => [$mother->full_name, $mother->four_ps_household_number, $mother->barangay, $mother->municipality_city]]));
        } elseif ($scope === 'dswd' && in_array($module, ['f1kd', 'attendance'], true)) {
            $title = $module === 'f1kd' ? 'F1KD Monitoring' : 'Monthly Health Attendance';
            $headers = ['Beneficiary', 'Type', 'Entry ID', 'Barangay', 'Last Menstrual Period', 'Attendance', 'Submitted / Verified By'];
            $all = app(F1kdCompliance::class)->rows(['month' => $month, 'q' => $search]);
            // Pending attendance is distinct from a recorded missed visit.
            $groups = ['Mothers' => $all->where('classification', 'pregnant')->map(fn ($row) => $this->attendanceCells($row)),
                'Children' => $all->where('classification', 'child')->map(fn ($row) => $this->attendanceCells($row))];
            return view('admin.departments.attendance', compact('scope', 'title', 'month', 'headers', 'groups', 'search'));
        } elseif ($scope === 'cho' && in_array($module, ['mothers', 'pregnancy'], true)) {
            $title = $module === 'mothers' ? 'Mothers and Children' : 'Pregnancy Monitoring';
            $viewClinicalRecord = true;
            $headers = ['Mother', 'Barangay', 'Pregnancy Status', 'Children', 'Latest Screening'];
            $query = Mother::withCount('infants')->with(['maternalMonitoringRecords' => fn ($q) => $q->orderByDesc('recorded_at')->orderByDesc('id')]);
            if ($module === 'pregnancy') $query->where('pregnancy_status', 'pregnant');
            if ($search !== '') $query->where(fn ($q) => $q->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"));
            $records = $query->orderBy('last_name')->paginate(20)->withQueryString();
            $records->setCollection($records->getCollection()->map(fn ($mother) => ['id' => $mother->id, 'cells' => [$mother->full_name, $mother->barangay, $mother->pregnancy_status, $mother->infants_count, $mother->maternalMonitoringRecords->first()?->screening_summary_status ?? 'Not recorded']]));
        } elseif ($scope === 'cho' && $module === 'consultations') {
            $title = 'Consultation Records';
            $headers = ['Mother', 'Checkup Date', 'Healthcare Worker', 'Facility', 'Verified'];
            $records = InayKaalamanCheckup::with('mother')->when($search !== '', fn ($q) => $q->whereHas('mother', fn ($m) => $m->where('last_name', 'like', "%{$search}%")))
                ->orderByDesc('checkup_date')->paginate(20)->withQueryString();
            $records->setCollection($records->getCollection()->map(fn ($record) => ['id' => $record->id, 'cells' => [$record->mother?->full_name, $record->checkup_date?->format('M j, Y'), $record->healthcare_worker_name, $record->facility_name, $record->verified_at ? 'Verified' : 'Pending']]));
        } elseif ($scope === 'cho' && $module === 'vaccinations') {
            $title = 'Vaccination Records';
            $headers = ['Child', 'Vaccine', 'Dose', 'Due Date', 'Administered', 'Status'];
            $records = InfantVaccineRecord::with('infant')->when($search !== '', fn ($q) => $q->whereHas('infant', fn ($child) => $child->where('full_name', 'like', "%{$search}%")))
                ->orderByDesc('due_date')->paginate(20)->withQueryString();
            $records->setCollection($records->getCollection()->map(fn ($record) => ['id' => $record->id, 'cells' => [$record->infant?->full_name, $record->vaccine_name, $record->dose_label, $record->due_date?->format('M j, Y'), $record->administered_at?->format('M j, Y'), $record->status]]));
        } elseif ($module === 'notifications') {
            $title = 'Notifications';
            $headers = ['Title', 'Message', 'Date', 'Read'];
            $records = AppNotification::where('recipient_role', 'admin')->where('recipient_id', $request->session()->get('admin_id'))->latest()->paginate(20)->withQueryString();
            $records->setCollection($records->getCollection()->map(fn ($record) => ['id' => $record->id, 'cells' => [$record->title, $record->body, $record->created_at->format('M j, Y H:i'), $record->read_at ? 'Read' : 'Unread']]));
        } else {
            abort(404);
        }
        return view('admin.departments.records', compact('scope', 'title', 'month', 'records', 'headers', 'search', 'editBeneficiary', 'viewClinicalRecord'));
    }

    public function clinicalRecord(Request $request, int $mother)
    {
        abort_unless($this->scope($request) === 'cho', 403);
        $record = Mother::with(['infants'])->findOrFail($mother);
        $sections = [
            'Maternal Monitoring' => [
                'headers' => ['Recorded', 'Pregnancy Week', 'Blood Pressure', 'Blood Sugar', 'Weight', 'Screening'],
                'records' => $record->maternalMonitoringRecords()->orderByDesc('recorded_at')->get()->map(fn ($vital) => ['cells' => [$vital->recorded_at?->format('M j, Y'), $vital->pregnancy_week, $vital->bp_systolic !== null && $vital->bp_diastolic !== null ? $vital->bp_systolic.'/'.$vital->bp_diastolic.' mmHg' : null, $vital->blood_sugar !== null ? $vital->blood_sugar.' mg/dL' : null, $vital->weight !== null ? $vital->weight.' kg' : null, $vital->screening_summary_status ?? $vital->risk_level]]),
            ],
            'Children' => [
                'headers' => ['Name', 'Birth Date', 'Sex', 'Facility'],
                'records' => $record->infants->map(fn ($child) => ['cells' => [$child->full_name, \App\Support\ChildProfileDisplay::date($child->getRawOriginal('birth_date'))?->format('M j, Y'), $child->sex, $child->facility]]),
            ],
            'Child Growth Records' => [
                'headers' => ['Child', 'Measured', 'Weight', 'Height', 'Head Circumference'],
                'records' => \App\Models\InfantGrowthRecord::with('infant')->whereIn('infant_id', $record->infants->pluck('id'))->orderByDesc('measured_at')->get()->map(fn ($growth) => ['cells' => [$growth->infant?->full_name, $growth->measured_at?->format('M j, Y'), $growth->weight !== null ? $growth->weight.' kg' : null, $growth->height !== null ? $growth->height.' cm' : null, $growth->head_circumference !== null ? $growth->head_circumference.' cm' : null]]),
            ],
        ];
        return view('admin.departments.clinical-record', compact('record', 'sections'));
    }

    private function attendanceCells(object $row): array
    {
        return ['id' => $row->key, 'cells' => [$row->name, $row->classification === 'child' ? 'Child' : 'Mother', $row->dswd_entry_number, $row->barangay,
            $row->classification === 'pregnant' ? ($row->last_menstrual_period?->format('M j, Y') ?? 'Not recorded') : 'Not applicable',
            $row->attendance_status === 'attended' ? 'Attended' : ($row->attendance_status === 'did_not_attend' ? 'Missed checkup' : 'Not yet recorded'),
            $row->attendance_author?->name ?? 'Not submitted']];
    }

    public function updateBeneficiary(Request $request, int $mother)
    {
        abort_unless($this->scope($request) === 'dswd', 403);
        $record = Mother::where('is_4ps_beneficiary', true)->findOrFail($mother);
        $record->update($request->validate([
            'four_ps_household_number' => ['nullable', 'string', 'max:255'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'municipality_city' => ['nullable', 'string', 'max:255'],
        ]));
        return back()->with('status', 'Beneficiary program information updated.');
    }
}
