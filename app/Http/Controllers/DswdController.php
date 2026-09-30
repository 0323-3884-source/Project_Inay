<?php

namespace App\Http\Controllers;

use App\Support\DswdStatistics;
use App\Models\InayKaalamanUpload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class DswdController extends Controller
{
    public const REPORTS = [
        'beneficiaries' => '4Ps Beneficiary Summary',
        'pregnant' => 'Pregnant 4Ps Beneficiary Summary',
        'children' => 'Children Aged 0–2 Summary',
        'barangay' => 'Beneficiary Distribution by Barangay',
        'municipality_city' => 'Beneficiary Distribution by Municipality/City',
    ];

    public function __construct(private DswdStatistics $statistics) {}

    public function dashboard(Request $request)
    {
        return view('dswd.dashboard', ['summary' => $this->statistics->summary([])]);
    }

    public function beneficiaries(Request $request)
    {
        $filters = $this->statistics->filters($request);

        return view('dswd.beneficiaries', [
            'filters' => $filters, 'options' => $this->statistics->options(),
            'beneficiaries' => $this->statistics->withChildren($this->statistics->mothers($filters))->orderBy('last_name')->orderBy('id')->paginate(15)->withQueryString(),
        ]);
    }

    public function beneficiary(string $beneficiary)
    {
        $mother = $this->statistics->withChildren($this->statistics->mothers())->findOrFail($beneficiary);
        $uploads = DB::table('inay_kaalaman_uploads')->where('mother_id', $mother->id)
            ->whereBetween('month', [1, 9])->select('month')->selectRaw('COUNT(*) AS total, MAX(created_at) AS last_received')
            ->groupBy('month')->get()->keyBy('month');
        $learning = DB::table('inay_kaalaman_progress')->where('mother_id', $mother->id)
            ->whereBetween('month', [1, 9])->whereIn('activity_type', ['reading', 'video', 'infographic'])
            ->select('month', 'activity_type', 'status')->get()->groupBy('month');

        return view('dswd.beneficiary', [
            'beneficiary' => $mother,
            'journeyUploads' => $uploads,
            'journeyLearning' => $learning,
            'journeyDocuments' => InayKaalamanUpload::where('mother_id', $mother->id)->whereBetween('month', [1, 9])
                ->orderByDesc('created_at')->get(['id', 'month', 'original_name', 'record_type', 'created_at'])->groupBy('month'),
        ]);
    }

    public function previewDocument(string $beneficiary, string $document)
    {
        $mother = $this->statistics->mothers()->findOrFail($beneficiary);
        $upload = InayKaalamanUpload::where('mother_id', $mother->id)->whereBetween('month', [1, 9])->findOrFail($document);
        $disk = Storage::disk('public');
        abort_unless($disk->exists($upload->path), 404, 'This document is no longer available.');
        $mime = $disk->mimeType($upload->path);
        abort_unless(in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'], true), 415, 'This file type cannot be previewed.');

        return response()->file($disk->path($upload->path), [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }

    public function statistics(Request $request)
    {
        $filters = $this->statistics->filters($request);

        return view('dswd.statistics', ['filters' => $filters, 'options' => $this->statistics->options(), 'summary' => $this->statistics->summary($filters)]);
    }

    public function reports(Request $request)
    {
        $filters = $this->statistics->filters($request);
        $type = $request->validate(['report' => ['nullable', Rule::in(array_keys(self::REPORTS))]])['report'] ?? 'beneficiaries';
        $summary = $this->statistics->summary($filters);
        $rows = match ($type) {
            'pregnant' => ['Pregnant 4Ps beneficiaries' => $summary['pregnant']],
            'children' => ['Total children aged 0–2' => $summary['children'], 'Mothers with children aged 0–2' => $summary['mothers_with_children']] + $summary['ages'],
            'barangay', 'municipality_city' => $summary['groups'][$type],
            default => ['Total 4Ps beneficiaries' => $summary['total'], 'Pregnant 4Ps beneficiaries' => $summary['pregnant'], 'Mothers with children aged 0–2' => $summary['mothers_with_children'], 'Children aged 0–2' => $summary['children']],
        };
        if ($request->routeIs('dswd.reports.download')) {
            return response()->streamDownload(function () use ($rows, $type, $filters) {
                $out = fopen('php://output', 'w');
                $write = function (array $values) use ($out) {
                    // Prevent spreadsheet formula execution from stored location labels.
                    fputcsv($out, array_map(fn ($v) => is_string($v) && preg_match('/^[\s]*[=+@-]/u', $v) ? "'".$v : $v, $values), ',', '"', '');
                };
                $write(['Project INAY', self::REPORTS[$type]]);
                $write(['Generated', now()->toDateTimeString()]);
                $write(['Age definition', '0–24 completed months as of '.today()->toDateString()]);
                $write(['Date filter', 'Beneficiary registration date']);
                foreach ($filters as $key => $value) {
                    if ($value !== null && $value !== '') {
                        $write([$key, $value === '__unrecorded__' ? 'Not recorded' : $value]);
                    }
                }
                $write(['Category', 'Count']);
                foreach ($rows as $label => $count) {
                    $write([$label, $count]);
                }
                fclose($out);
            }, 'inay-4ps-'.$type.'-'.today()->toDateString().'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return view('dswd.reports', ['filters' => $filters, 'options' => $this->statistics->options(), 'type' => $type, 'rows' => $rows]);
    }

    public function evaluation(Request $request)
    {
        return view('dswd.evaluation', ['evaluations' => DB::table('dswd_evaluations')->where('dswd_staff_id', $request->attributes->get('dswd_staff')->id)->latest()->paginate(10)]);
    }

    public function storeEvaluation(Request $request)
    {
        $data = $request->validate(['rating' => ['required', 'integer', 'between:1,5'], 'feedback' => ['nullable', 'string', 'max:2000']]);
        DB::table('dswd_evaluations')->insert($data + ['dswd_staff_id' => $request->attributes->get('dswd_staff')->id, 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('status', 'Thank you. Your system evaluation has been saved.');
    }

    public function profile(Request $request)
    {
        return view('dswd.profile', ['staff' => $request->attributes->get('dswd_staff')]);
    }

    public function updateProfile(Request $request)
    {
        $staff = $request->attributes->get('dswd_staff');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'office' => ['nullable', 'string', 'max:255'],
            'current_password' => ['required_with:password', 'nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);
        if (! empty($data['password'])) {
            if (! Hash::check($data['current_password'], $staff->password)) {
                return back()->withErrors(['current_password' => 'The current password is incorrect.']);
            }
            $staff->password = $data['password'];
            $request->session()->regenerate();
        }
        $staff->fill(['name' => $data['name'], 'office' => $data['office'] ?? null])->save();
        $request->session()->put('auth_name', $staff->name);

        return back()->with('status', 'Profile updated.');
    }
}
