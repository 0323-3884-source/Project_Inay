<?php

namespace App\Http\Controllers;

use App\Models\Mother;
use App\Models\InayKaalamanProgress;
use App\Models\InayKaalamanUpload;
use App\Models\AdminUser;
use App\Models\ChildHealthAlert;
use App\Models\EducationalContent;
use App\Models\InfantVaccineRecord;
use App\Models\InfantGrowthRecord;
use App\Models\Infant;
use App\Models\MaternalMonitoringRecord;
use App\Models\ProgramStaff;
use App\Models\StaffMotherCasefile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const SAN_PABLO_BARANGAYS = [
        'Bagong Bayan',
        'Concepcion',
        'Del Remedio',
        'San Francisco',
        'San Gabriel',
        'San Gregorio',
        'San Ignacio',
        'San Isidro',
        'San Joaquin',
        'San Jose',
        'San Juan',
        'San Lucas 1',
        'San Lucas 2',
        'San Marcos',
        'San Mateo',
        'San Miguel',
        'San Nicolas',
        'San Pedro',
        'San Rafael',
        'San Roque',
        'San Vicente',
        'Santa Ana',
        'Santa Catalina',
        'Santa Cruz',
        'Santa Elena',
        'Santa Filomena',
        'Santa Isabel',
        'Santa Maria',
        'Santa Monica',
        'Santa Veronica',
        'Santo Angel',
        'Santo Cristo',
        'Santo Nino',
        'Soledad',
    ];

    private const BLOOD_TYPES = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Unknown'];

    private const PREGNANCY_STATUSES = ['not_pregnant', 'pregnant', 'postpartum', 'planning'];

    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->redirectForCurrentRole($request)) {
            return $redirect;
        }

        return view('auth.login');
    }

    public function showMotherRegister(): View
    {
        return view('auth.register-mother', [
            'barangays' => self::SAN_PABLO_BARANGAYS,
        ]);
    }

    public function showStaffRegister(): View
    {
        return view('auth.register-staff');
    }

    public function registerMother(Request $request): RedirectResponse
    {
        $this->prepareNameFields($request);

        $validated = $request->validate([
            'full_name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:mothers,email'],
            'password' => ['required', 'string', 'min:8'],
            'barangay' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:30'],
            'age' => ['nullable', 'integer', 'between:10,60'],
            'blood_type' => ['nullable', Rule::in(self::BLOOD_TYPES)],
            'pregnancy_status' => ['nullable', Rule::in(self::PREGNANCY_STATUSES)],
            'location_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'location_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location_accuracy' => ['nullable', 'integer', 'min:0'],
            'privacy_policy' => ['accepted'],
            'is_4ps_beneficiary' => ['required', Rule::in(['yes', 'no'])],
        ]);

        Mother::create([
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'barangay' => $validated['barangay'],
            'contact_number' => $validated['contact_number'],
            'age' => $validated['age'] ?? null,
            'blood_type' => $validated['blood_type'] ?? null,
            'pregnancy_status' => $validated['pregnancy_status'] ?? null,
            'location_latitude' => $validated['location_latitude'] ?? null,
            'location_longitude' => $validated['location_longitude'] ?? null,
            'location_accuracy' => $validated['location_accuracy'] ?? null,
            'is_4ps_beneficiary' => ($validated['is_4ps_beneficiary'] ?? 'no') === 'yes',
        ]);

        return redirect()
            ->route('login')
            ->with('status', 'Mother account created. Select Mother to login.');
    }

    public function registerStaff(Request $request): RedirectResponse
    {
        $this->prepareNameFields($request);

        if (! $request->filled('staff_id')) {
            $request->merge(['staff_id' => $this->generateStaffId()]);
        }

        if (! $request->filled('role') && $request->filled('position')) {
            $request->merge(['role' => $request->input('position')]);
        }

        if (! $request->filled('role')) {
            $request->merge(['role' => 'Program Staff']);
        }

        if (! $request->filled('position')) {
            $request->merge(['position' => $request->input('role', 'Program Staff')]);
        }

        if (! $request->filled('contact_number')) {
            $request->merge(['contact_number' => 'Not provided']);
        }

        $validated = $request->validate([
            'full_name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:program_staff,email'],
            'password' => ['required', 'string', 'min:8'],
            'staff_id' => ['required', 'string', 'max:255', 'unique:program_staff,staff_id'],
            'position' => ['required', 'string', 'max:255'],
            'role' => ['required', 'string', 'max:80'],
            'contact_number' => ['required', 'string', 'max:30'],
            'healthcare_worker_id_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'privacy_policy' => ['accepted'],
        ]);

        $idPhotoPath = $request->file('healthcare_worker_id_photo')?->store('healthcare-worker-ids', 'public');

        ProgramStaff::create([
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'staff_id' => $validated['staff_id'],
            'position' => $validated['position'],
            'role' => $validated['role'],
            'contact_number' => $validated['contact_number'],
            'healthcare_worker_id_photo_path' => $idPhotoPath,
            'approval_status' => 'pending',
        ]);

        return redirect()
            ->route('login')
            ->with('status', 'Program staff account submitted for admin approval. Please wait for the confirmation email before logging in.');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(['mother', 'staff'])],
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $identifier = trim($validated['email']);

        if ($adminRedirect = $this->attemptAdminLoginFromSharedForm($request, $identifier, $validated['password'])) {
            return $adminRedirect;
        }

        if (! filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $this->throwLoginError();
        }

        if ($validated['role'] === 'mother') {
            $mother = Mother::where('email', $identifier)->first();

            if (! $mother || ! Hash::check($validated['password'], $mother->password)) {
                $this->throwLoginError();
            }

            $this->startLoginSession($request, 'mother', $mother->id, $mother->full_name, $mother->email);

            return redirect()->route('mother.dashboard');
        }

        $staff = ProgramStaff::where('email', $identifier)->first();

        if (! $staff || ! Hash::check($validated['password'], $staff->password)) {
            $this->throwLoginError();
        }

        if ($staff->approval_status === 'pending') {
            throw ValidationException::withMessages([
                'email' => 'Your Program Staff account is waiting for admin approval. Please wait for the confirmation email before logging in.',
            ]);
        }

        if ($staff->approval_status === 'rejected') {
            throw ValidationException::withMessages([
                'email' => 'Your Program Staff registration was not approved. Please contact the administrator for assistance.',
            ]);
        }

        $this->startLoginSession($request, 'staff', $staff->id, $staff->full_name, $staff->email);

        return redirect()->route('staff.dashboard');
    }

    public function motherDashboard(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('auth_role') !== 'mother') {
            return redirect()->route('login')->with('status', 'Please login as Mother first.');
        }

        $mother = Mother::find($request->session()->get('auth_id'));

        if (! $mother) {
            $this->clearLoginSession($request);

            return redirect()->route('login')->with('status', 'Please login again.');
        }

        return view('dashboards.mother', compact('mother'));
    }

    public function maternalMonitoring(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('auth_role') !== 'mother') {
            return redirect()->route('login')->with('status', 'Please login as Mother first.');
        }

        $mother = Mother::find($request->session()->get('auth_id'));

        if (! $mother) {
            $this->clearLoginSession($request);

            return redirect()->route('login')->with('status', 'Please login again.');
        }

        $records = MaternalMonitoringRecord::where('mother_id', $mother->id)
            ->orderByDesc('recorded_at')
            ->orderByDesc('created_at')
            ->get();

        $latestRecord = $records->first();
        $maternalVitalsPayload = $this->maternalVitalsPayload($mother);

        return view('modules.maternal-monitoring', compact('mother', 'records', 'latestRecord', 'maternalVitalsPayload'));
    }

    public function childHealth(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('auth_role') !== 'mother') {
            return redirect()->route('login')->with('status', 'Please login as Mother first.');
        }

        $mother = Mother::with([
            'infants.growthRecords.recorder',
            'infants.vaccineRecords.recorder',
            'infants.healthAlerts.creator',
        ])->find($request->session()->get('auth_id'));

        if (! $mother) {
            $this->clearLoginSession($request);

            return redirect()->route('login')->with('status', 'Please login again.');
        }

        $selectedChildId = (int) $request->query('child', 0);
        $childAccessDenied = false;
        $childAccessMessage = null;
        $selectedChild = null;

        if ($selectedChildId > 0) {
            $selectedChild = $mother->infants->firstWhere('id', $selectedChildId);
            $childAccessDenied = ! $selectedChild;
            $childAccessMessage = $childAccessDenied
                ? 'The selected child profile is not available for your account.'
                : null;
        } else {
            $selectedChild = $mother->infants->first();
        }

        return view('modules.child-health', compact('mother', 'selectedChild', 'childAccessDenied', 'childAccessMessage'));
    }

    public function storeMotherChild(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->session()->get('auth_role') !== 'mother') {
            return $request->expectsJson()
                ? response()->json(['message' => 'Please login as Mother first.'], 403)
                : redirect()->route('login')->with('status', 'Please login as Mother first.');
        }

        $mother = Mother::find($request->session()->get('auth_id'));

        if (! $mother) {
            $this->clearLoginSession($request);

            return $request->expectsJson()
                ? response()->json(['message' => 'Please login again.'], 403)
                : redirect()->route('login')->with('status', 'Please login again.');
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'sex' => ['required', Rule::in(['female', 'male', 'other', 'unspecified'])],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'blood_type' => ['nullable', Rule::in(self::BLOOD_TYPES)],
            'child_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $birthDate = Carbon::parse($validated['birth_date'])->startOfDay();
        $sex = $validated['sex'] === 'unspecified' ? 'other' : $validated['sex'];

        $duplicate = Infant::where('mother_id', $mother->id)
            ->whereRaw('LOWER(full_name) = ?', [Str::lower(trim($validated['full_name']))])
            ->whereDate('birth_date', $birthDate->toDateString())
            ->first();

        if ($duplicate) {
            $message = 'This child profile already exists.';

            return $request->expectsJson()
                ? response()->json(['message' => $message, 'child_id' => $duplicate->id, 'url' => route('child-health', ['child' => $duplicate->id])], 422)
                : back()->withErrors(['full_name' => $message])->withInput();
        }

        $photoPath = $request->file('child_photo')?->store('child-photos', 'public');

        $infant = Infant::create([
            'mother_id' => $mother->id,
            'full_name' => trim($validated['full_name']),
            'sex' => $sex,
            'birth_date' => $birthDate->toDateString(),
            'blood_type' => $validated['blood_type'] ?? null,
            'photo_path' => $photoPath,
            'facility' => $mother->barangay ? 'RHU - '.$mother->barangay : null,
        ]);

        $this->createInfantVaccineSchedule($infant, $birthDate, null);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Child profile saved.',
                'child' => [
                    'id' => $infant->id,
                    'name' => $infant->full_name,
                    'age' => max(0, (int) $birthDate->diffInMonths(now())),
                ],
                'url' => route('child-health', ['child' => $infant->id]),
            ]);
        }

        return redirect()->route('child-health', ['child' => $infant->id])->with('status', 'Child profile saved.');
    }

    public function updateMotherChild(Request $request, Infant $infant): RedirectResponse
    {
        if ($request->session()->get('auth_role') !== 'mother') {
            return redirect()->route('login')->with('status', 'Please login as Mother first.');
        }

        $mother = Mother::find($request->session()->get('auth_id'));

        if (! $mother) {
            $this->clearLoginSession($request);

            return redirect()->route('login')->with('status', 'Please login again.');
        }

        if ((int) $infant->mother_id !== (int) $mother->id) {
            return redirect()->route('child-health')->with('status', 'You cannot update this child profile.');
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'sex' => ['required', Rule::in(['female', 'male', 'other', 'unspecified'])],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'blood_type' => ['nullable', Rule::in(self::BLOOD_TYPES)],
        ]);

        $birthDate = Carbon::parse($validated['birth_date'])->startOfDay();
        $sex = $validated['sex'] === 'unspecified' ? 'other' : $validated['sex'];
        $duplicate = Infant::where('mother_id', $mother->id)
            ->whereRaw('LOWER(full_name) = ?', [Str::lower(trim($validated['full_name']))])
            ->whereDate('birth_date', $birthDate->toDateString())
            ->whereKeyNot($infant->id)
            ->first();

        if ($duplicate) {
            return back()
                ->withInput()
                ->withErrors(['full_name' => 'This child profile already exists.']);
        }

        $infant->update([
            'full_name' => trim($validated['full_name']),
            'sex' => $sex,
            'birth_date' => $birthDate->toDateString(),
            'blood_type' => $validated['blood_type'] ?? null,
        ]);

        return redirect()
            ->route('child-health', ['child' => $infant->id])
            ->with('status', 'Child profile updated.');
    }

    public function updateMotherChildPhoto(Request $request, Infant $infant): RedirectResponse
    {
        if ($request->session()->get('auth_role') !== 'mother') {
            return redirect()->route('login')->with('status', 'Please login as Mother first.');
        }

        $mother = Mother::find($request->session()->get('auth_id'));

        if (! $mother) {
            $this->clearLoginSession($request);

            return redirect()->route('login')->with('status', 'Please login again.');
        }

        if ((int) $infant->mother_id !== (int) $mother->id) {
            return redirect()->route('child-health')->with('status', 'You cannot update this child photo.');
        }

        $validated = $request->validate([
            'child_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $oldPhotoPath = $infant->photo_path;
        $newPhotoPath = $validated['child_photo']->store('child-photos', 'public');

        $infant->update(['photo_path' => $newPhotoPath]);

        if ($oldPhotoPath) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        return redirect()
            ->route('child-health', ['child' => $infant->id])
            ->with('status', 'Child profile photo updated.');
    }

    public function updateMotherProfilePhoto(Request $request): RedirectResponse
    {
        if ($request->session()->get('auth_role') !== 'mother') {
            return redirect()->route('login')->with('status', 'Please login as Mother first.');
        }

        $mother = Mother::find($request->session()->get('auth_id'));

        if (! $mother) {
            $this->clearLoginSession($request);

            return redirect()->route('login')->with('status', 'Please login again.');
        }

        $validated = $request->validate([
            'profile_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $oldPhotoPath = $mother->profile_photo_path;
        $newPhotoPath = $validated['profile_photo']->store('mother-profile-photos', 'public');

        $mother->update(['profile_photo_path' => $newPhotoPath]);

        if ($oldPhotoPath) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        return back()->with('status', 'Profile photo updated.');
    }

    public function inayKaalaman(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('auth_role') !== 'mother') {
            return redirect()->route('login')->with('status', 'Please login as Mother first.');
        }

        $mother = Mother::find($request->session()->get('auth_id'));

        if (! $mother) {
            $this->clearLoginSession($request);

            return redirect()->route('login')->with('status', 'Please login again.');
        }

        $kaalamanUploads = InayKaalamanUpload::where('mother_id', $mother->id)
            ->latest()
            ->get()
            ->groupBy('month');

        $kaalamanProgressRecords = InayKaalamanProgress::where('mother_id', $mother->id)->get();

        $publishedEducationalContents = EducationalContent::published()
            ->orderBy('stage_key')
            ->orderBy('month')
            ->orderBy('display_order')
            ->orderBy('title')
            ->get();

        $publishedEducationalContentByStage = $publishedEducationalContents->groupBy('stage_key');
        $publishedEducationalContentByMonth = $publishedEducationalContents
            ->filter(fn (EducationalContent $content) => $content->month !== null)
            ->groupBy('month');
        $kaalamanMonthlyProgress = $this->inayKaalamanProgressSummary($mother, $kaalamanUploads, $kaalamanProgressRecords);
        $kaalamanOverallProgress = $kaalamanMonthlyProgress['overall'];

        return view('modules.inay-kaalaman', compact(
            'mother',
            'kaalamanUploads',
            'kaalamanMonthlyProgress',
            'kaalamanOverallProgress',
            'publishedEducationalContentByStage',
            'publishedEducationalContentByMonth',
        ));
    }

    public function inayKaalamanVideos(Request $request, int $month): View|RedirectResponse
    {
        if ($request->session()->get('auth_role') !== 'mother') {
            return redirect()->route('login')->with('status', 'Please login as Mother first.');
        }

        $mother = Mother::find($request->session()->get('auth_id'));

        if (! $mother) {
            $this->clearLoginSession($request);

            return redirect()->route('login')->with('status', 'Please login again.');
        }

        $monthData = $this->inayKaalamanVideoMonths()[$month] ?? null;

        if (! $monthData) {
            abort(404);
        }

        return view('modules.inay-kaalaman-videos', compact('mother', 'monthData'));
    }

    public function inayKaalamanInfographicPdf(Request $request, int $month): Response|RedirectResponse
    {
        if ($request->session()->get('auth_role') !== 'mother') {
            return redirect()->route('login')->with('status', 'Please login as Mother first.');
        }

        $monthData = $this->inayKaalamanVideoMonths()[$month] ?? null;

        if (! $monthData) {
            abort(404);
        }

        $pdf = $this->buildSimplePdf([
            'Project INAY',
            "Month {$monthData['month']} Checklist",
            $monthData['title'].' ('.$monthData['weeks'].')',
            'Maternal Care',
            '1. Review the monthly learning guide.',
            '2. Watch all educational videos.',
            '3. Upload prenatal records and receipts.',
            '4. Upload prenatal records or receipts.',
            '5. Contact your Program Staff for urgent warning signs.',
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="inay-kaalaman-month-'.$month.'-checklist.pdf"',
        ]);
    }

    public function deleteInayKaalamanRecord(Request $request, InayKaalamanUpload $upload): RedirectResponse
    {
        if ($request->session()->get('auth_role') !== 'mother') {
            return redirect()->route('login')->with('status', 'Please login as Mother first.');
        }

        $mother = Mother::find($request->session()->get('auth_id'));

        if (! $mother) {
            $this->clearLoginSession($request);

            return redirect()->route('login')->with('status', 'Please login again.');
        }

        if ((int) $upload->mother_id !== (int) $mother->id) {
            abort(403);
        }

        Storage::disk('public')->delete($upload->path);
        $upload->delete();

        return redirect()
            ->route('inay-kaalaman')
            ->with('status', 'Prenatal record deleted.');
    }

    public function uploadInayKaalamanRecord(Request $request): RedirectResponse
    {
        if ($request->session()->get('auth_role') !== 'mother') {
            return redirect()->route('login')->with('status', 'Please login as Mother first.');
        }

        $mother = Mother::find($request->session()->get('auth_id'));

        if (! $mother) {
            $this->clearLoginSession($request);

            return redirect()->route('login')->with('status', 'Please login again.');
        }

        $validated = $request->validate([
            'month' => ['required', 'integer', 'between:1,10'],
            'record_type' => ['required', Rule::in(['Prenatal Records and Receipts', 'Checkup Records', 'Prescription', 'Receipts', 'Certificate', 'Other Documents'])],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ]);

        $document = $validated['document'];

        $duplicateUpload = InayKaalamanUpload::where('mother_id', $mother->id)
            ->where('month', $validated['month'])
            ->where('record_type', $validated['record_type'])
            ->where('original_name', $document->getClientOriginalName())
            ->where('size', $document->getSize() ?: 0)
            ->exists();

        if ($duplicateUpload) {
            return redirect()
                ->route('inay-kaalaman')
                ->with('status', 'This document was already uploaded for the selected month.');
        }

        $path = $document->store('inay-kaalaman-records', 'public');

        InayKaalamanUpload::create([
            'mother_id' => $mother->id,
            'month' => $validated['month'],
            'record_type' => $validated['record_type'],
            'original_name' => $document->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $document->getClientMimeType(),
            'size' => $document->getSize() ?: 0,
        ]);

        return redirect()
            ->route('inay-kaalaman')
            ->with('status', 'Prenatal record uploaded successfully.');
    }

    public function saveInayKaalamanProgress(Request $request): JsonResponse
    {
        if ($request->session()->get('auth_role') !== 'mother') {
            return response()->json(['message' => 'Please login as Mother first.'], 401);
        }

        $mother = Mother::find($request->session()->get('auth_id'));

        if (! $mother) {
            $this->clearLoginSession($request);

            return response()->json(['message' => 'Please login again.'], 401);
        }

        $validated = $request->validate([
            'month' => ['required', 'integer', 'between:1,10'],
            'activity_type' => ['required', Rule::in(['reading', 'video', 'infographic'])],
            'item_key' => ['required', 'string', 'max:160'],
            'item_title' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['in_progress', 'read', 'watched', 'reviewed'])],
        ]);

        $finalStatuses = [
            'reading' => 'read',
            'video' => 'watched',
            'infographic' => 'reviewed',
        ];

        $activityType = $validated['activity_type'];
        $requestedStatus = $validated['status'];

        if ($requestedStatus !== 'in_progress' && $requestedStatus !== $finalStatuses[$activityType]) {
            return response()->json(['message' => 'Invalid progress status for this activity.'], 422);
        }

        $progress = InayKaalamanProgress::firstOrNew([
            'mother_id' => $mother->id,
            'month' => (int) $validated['month'],
            'activity_type' => $activityType,
            'item_key' => $validated['item_key'],
        ]);

        $isAlreadyComplete = $progress->exists && $progress->status === $finalStatuses[$activityType];
        $nextStatus = $isAlreadyComplete ? $progress->status : $requestedStatus;
        $now = now();

        $progress->fill([
            'item_title' => $validated['item_title'] ?? $progress->item_title,
            'status' => $nextStatus,
            'started_at' => $progress->started_at ?: $now,
            'completed_at' => $nextStatus === $finalStatuses[$activityType]
                ? ($progress->completed_at ?: $now)
                : $progress->completed_at,
        ]);
        $progress->save();

        $uploads = InayKaalamanUpload::where('mother_id', $mother->id)->latest()->get()->groupBy('month');
        $progressRecords = InayKaalamanProgress::where('mother_id', $mother->id)->get();
        $summary = $this->inayKaalamanProgressSummary($mother, $uploads, $progressRecords);

        return response()->json([
            'message' => 'INAY Kaalaman progress saved.',
            'progress' => [
                'month' => $progress->month,
                'activity_type' => $progress->activity_type,
                'item_key' => $progress->item_key,
                'status' => $progress->status,
                'completed_at' => $progress->completed_at?->toISOString(),
            ],
            'month_summary' => $summary['months'][$progress->month] ?? null,
            'overall' => $summary['overall'],
        ]);
    }

    public function healthServices(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('auth_role') !== 'mother') {
            return redirect()->route('login')->with('status', 'Please login as Mother first.');
        }

        $mother = Mother::find($request->session()->get('auth_id'));

        if (! $mother) {
            $this->clearLoginSession($request);

            return redirect()->route('login')->with('status', 'Please login again.');
        }

        $facilityCategories = config('health_facilities.categories', []);
        $healthFacilities = config('health_facilities.facilities', []);

        return view('modules.health-services', compact('mother', 'facilityCategories', 'healthFacilities'));
    }
    public function staffDashboard(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('auth_role') !== 'staff') {
            return redirect()->route('login')->with('status', 'Please login as Program Staff first.');
        }

        $staff = ProgramStaff::find($request->session()->get('auth_id'));

        if (! $staff) {
            $this->clearLoginSession($request);

            return redirect()->route('login')->with('status', 'Please login again.');
        }

        return view('dashboards.staff', compact('staff'));
    }

    public function staffMothers(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('auth_role') !== 'staff') {
            return redirect()->route('login')->with('status', 'Please login as Program Staff first.');
        }

        $staff = ProgramStaff::find($request->session()->get('auth_id'));

        if (! $staff) {
            $this->clearLoginSession($request);

            return redirect()->route('login')->with('status', 'Please login again.');
        }

        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $risk = (string) $request->query('risk', 'all');
        $assignedMotherIds = StaffMotherCasefile::where('staff_id', $staff->id)->pluck('mother_id');

        $mothersQuery = Mother::query()
            ->whereIn('id', $assignedMotherIds)
            ->with(['maternalMonitoringRecords' => fn ($query) => $query
                ->orderByDesc('recorded_at')
                ->orderByDesc('created_at')]);

        if ($search !== '') {
            $tokens = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            foreach ($tokens as $token) {
                $like = '%'.$token.'%';

                $mothersQuery->where(function ($query) use ($like) {
                    $query->where('first_name', 'like', $like)
                        ->orWhere('middle_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('contact_number', 'like', $like)
                        ->orWhere('barangay', 'like', $like);
                });
            }
        }

        if (in_array($status, self::PREGNANCY_STATUSES, true)) {
            $mothersQuery->where('pregnancy_status', $status);
        }

        if (in_array($risk, ['low', 'medium', 'high'], true)) {
            $mothersQuery->whereHas('maternalMonitoringRecords', fn ($query) => $query->where('risk_level', $risk));
        }

        $mothers = $mothersQuery
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $allRegisteredMothers = Mother::query()
            ->with(['maternalMonitoringRecords' => fn ($query) => $query
                ->orderByDesc('recorded_at')
                ->orderByDesc('created_at')])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
        $assignedMotherIds = $assignedMotherIds->map(fn ($id) => (int) $id)->all();
        $totalMothers = Mother::count();
        $withMonitoring = MaternalMonitoringRecord::whereIn('mother_id', $assignedMotherIds)->distinct('mother_id')->count('mother_id');

        return view('modules.staff-mothers', compact(
            'staff',
            'mothers',
            'allRegisteredMothers',
            'assignedMotherIds',
            'search',
            'status',
            'risk',
            'totalMothers',
            'withMonitoring',
        ));
    }

    public function storeStaffMothers(Request $request): RedirectResponse
    {
        if ($request->session()->get('auth_role') !== 'staff') {
            return redirect()->route('login')->with('status', 'Please login as Program Staff first.');
        }

        $staff = ProgramStaff::find($request->session()->get('auth_id'));

        if (! $staff) {
            $this->clearLoginSession($request);

            return redirect()->route('login')->with('status', 'Please login again.');
        }

        $validated = $request->validate([
            'mother_ids' => ['required', 'array', 'min:1'],
            'mother_ids.*' => ['integer', 'exists:mothers,id'],
        ]);

        $added = 0;

        foreach (array_unique($validated['mother_ids']) as $motherId) {
            $casefile = StaffMotherCasefile::firstOrCreate([
                'staff_id' => $staff->id,
                'mother_id' => $motherId,
            ]);

            if ($casefile->wasRecentlyCreated) {
                $added++;
            }
        }

        return redirect()
            ->route('staff.mothers')
            ->with('status', $added > 0 ? "{$added} patient casefile added." : 'Selected patient is already in your list.');
    }

    public function staffMotherCasefile(Request $request, Mother $mother): View|RedirectResponse
    {
        if ($request->session()->get('auth_role') !== 'staff') {
            return redirect()->route('login')->with('status', 'Please login as Program Staff first.');
        }

        $staff = ProgramStaff::find($request->session()->get('auth_id'));

        if (! $staff) {
            $this->clearLoginSession($request);

            return redirect()->route('login')->with('status', 'Please login again.');
        }

        $casefile = StaffMotherCasefile::where('staff_id', $staff->id)
            ->where('mother_id', $mother->id)
            ->first();

        if (! $casefile) {
            return redirect()
                ->route('staff.mothers')
                ->with('status', 'Add this mother to your patient list before opening the casefile.');
        }

        $records = MaternalMonitoringRecord::where('mother_id', $mother->id)
            ->orderByDesc('recorded_at')
            ->orderByDesc('created_at')
            ->get();
        $latestRecord = $records->first();
        $uploads = InayKaalamanUpload::where('mother_id', $mother->id)->latest()->get();
        $kaalamanProgressRecords = InayKaalamanProgress::where('mother_id', $mother->id)->get();
        $kaalamanMonthlyProgress = $this->inayKaalamanProgressSummary($mother, $uploads->groupBy('month'), $kaalamanProgressRecords);
        $kaalamanOverallProgress = $kaalamanMonthlyProgress['overall'];
        $careCompletion = $this->careCompletionPercentage($records, $kaalamanOverallProgress);
        $maternalVitalsPayload = $this->maternalVitalsPayload($mother);

        return view('modules.staff-mother-casefile', compact(
            'staff',
            'mother',
            'casefile',
            'records',
            'latestRecord',
            'uploads',
            'kaalamanMonthlyProgress',
            'kaalamanOverallProgress',
            'careCompletion',
            'maternalVitalsPayload',
        ));
    }

    public function getStaffMaternalVitals(Request $request, Mother $mother): JsonResponse
    {
        $staff = $this->staffFromRequest($request);

        if (! $staff) {
            return response()->json(['message' => 'Please login as Program Staff first.'], 401);
        }

        if (! $this->casefileForStaffMother($staff, $mother)) {
            return response()->json(['message' => 'Add this mother to your patient list before opening the casefile.'], 403);
        }

        return response()->json($this->maternalVitalsPayload($mother));
    }

    public function storeStaffMaternalVitals(Request $request, Mother $mother): JsonResponse
    {
        $staff = $this->staffFromRequest($request);

        if (! $staff) {
            return response()->json(['message' => 'Please login as Program Staff first.'], 401);
        }

        $casefile = $this->casefileForStaffMother($staff, $mother);

        if (! $casefile) {
            return response()->json(['message' => 'Add this mother to your patient list before opening the casefile.'], 403);
        }

        $validator = $this->maternalVitalsValidator($request);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Review the flagged values before saving this maternal vital record.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        MaternalMonitoringRecord::create($this->maternalVitalsAttributes($validated, $mother, $staff, $casefile));

        return response()->json([
            'message' => 'Maternal vitals saved.',
            ...$this->maternalVitalsPayload($mother),
        ], 201);
    }

    public function updateStaffMaternalVital(Request $request, MaternalMonitoringRecord $record): JsonResponse
    {
        $staff = $this->staffFromRequest($request);

        if (! $staff) {
            return response()->json(['message' => 'Please login as Program Staff first.'], 401);
        }

        $mother = Mother::find($record->mother_id);

        if (! $mother) {
            return response()->json(['message' => 'Maternal record mother was not found.'], 404);
        }

        $casefile = $this->casefileForStaffMother($staff, $mother);

        if (! $casefile) {
            return response()->json(['message' => 'You cannot update a record outside your patient list.'], 403);
        }

        $validator = $this->maternalVitalsValidator($request);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Review the flagged values before saving this maternal vital record.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $record->update($this->maternalVitalsAttributes($validator->validated(), $mother, $staff, $casefile));

        return response()->json([
            'message' => 'Maternal vitals updated.',
            ...$this->maternalVitalsPayload($mother),
        ]);
    }

    public function deleteStaffMaternalVital(Request $request, MaternalMonitoringRecord $record): JsonResponse
    {
        $staff = $this->staffFromRequest($request);

        if (! $staff) {
            return response()->json(['message' => 'Please login as Program Staff first.'], 401);
        }

        $mother = Mother::find($record->mother_id);

        if (! $mother || ! $this->casefileForStaffMother($staff, $mother)) {
            return response()->json(['message' => 'You cannot delete a record outside your patient list.'], 403);
        }

        $record->delete();

        return response()->json([
            'message' => 'Maternal vitals deleted.',
            ...$this->maternalVitalsPayload($mother),
        ]);
    }

    public function getMotherMaternalVitals(Request $request): JsonResponse
    {
        if ($request->session()->get('auth_role') !== 'mother') {
            return response()->json(['message' => 'Please login as Mother first.'], 401);
        }

        $mother = Mother::find($request->session()->get('auth_id'));

        if (! $mother) {
            return response()->json(['message' => 'Please login again.'], 401);
        }

        return response()->json($this->maternalVitalsPayload($mother));
    }

    public function staffNeonatalVaccines(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('auth_role') !== 'staff') {
            return redirect()->route('login')->with('status', 'Please login as Program Staff first.');
        }

        $staff = ProgramStaff::find($request->session()->get('auth_id'));

        if (! $staff) {
            $this->clearLoginSession($request);

            return redirect()->route('login')->with('status', 'Please login again.');
        }

        $assignedMotherIds = StaffMotherCasefile::where('staff_id', $staff->id)->pluck('mother_id');
        $mothers = Mother::query()
            ->whereIn('id', $assignedMotherIds)
            ->with([
                'infants' => fn ($query) => $query->orderBy('birth_date')->orderBy('full_name'),
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $children = Infant::with([
                'mother',
                'growthRecords.recorder',
                'vaccineRecords.recorder',
                'healthAlerts.creator',
            ])
            ->whereIn('mother_id', $assignedMotherIds)
            ->orderBy('full_name')
            ->get();

        $selectedChildId = (int) $request->query('child', 0);
        $selectedInfant = null;
        $childAccessDenied = false;

        if ($selectedChildId > 0) {
            $selectedInfant = $children->firstWhere('id', $selectedChildId);
            $childAccessDenied = ! $selectedInfant;
        } else {
            $selectedInfant = $children->first();
        }

        $allVaccines = $children->flatMap->vaccineRecords;
        $activeAlerts = $children->flatMap->healthAlerts->where('status', 'active');
        $today = Carbon::today();

        $neonatalStats = [
            'linked_infants' => $children->count(),
            'vaccines_completed' => $allVaccines->where('status', 'completed')->count(),
            'upcoming_vaccines' => $allVaccines->filter(fn (InfantVaccineRecord $record): bool => in_array($record->status, ['upcoming', 'overdue'], true) && $record->due_date && $record->due_date->greaterThanOrEqualTo($today))->count(),
            'overdue_vaccines' => $allVaccines->filter(fn (InfantVaccineRecord $record): bool => $record->status === 'overdue' || ($record->status === 'upcoming' && $record->due_date && $record->due_date->isBefore($today)))->count(),
            'active_alerts' => $activeAlerts->count(),
            'pending_followups' => $children->filter(fn (Infant $infant): bool => $this->infantGrowthAssessment($infant)['severity'] !== 'normal')->count(),
        ];

        return view('modules.staff-neonatal-vaccines', compact('staff', 'mothers', 'children', 'selectedInfant', 'childAccessDenied', 'neonatalStats'));
    }

    public function storeStaffInfant(Request $request): RedirectResponse
    {
        $staff = $this->staffFromRequest($request);

        if (! $staff) {
            return redirect()->route('login')->with('status', 'Please login as Program Staff first.');
        }

        $validated = $request->validate([
            'mother_id' => ['required', 'integer', 'exists:mothers,id'],
            'full_name' => ['required', 'string', 'max:255'],
            'sex' => ['required', Rule::in(['female', 'male', 'other'])],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'birth_weight' => ['nullable', 'numeric', 'between:0.5,12'],
            'birth_height' => ['nullable', 'numeric', 'between:20,80'],
            'blood_type' => ['nullable', Rule::in(self::BLOOD_TYPES)],
            'child_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'facility' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $mother = Mother::findOrFail($validated['mother_id']);

        if (! $this->casefileForStaffMother($staff, $mother)) {
            return redirect()->route('staff.neonatal')->with('status', 'Add this mother to your casefiles before onboarding an infant.');
        }

        $birthDate = Carbon::parse($validated['birth_date'])->startOfDay();
        $duplicate = Infant::where('mother_id', $mother->id)
            ->whereRaw('LOWER(full_name) = ?', [Str::lower(trim($validated['full_name']))])
            ->whereDate('birth_date', $birthDate->toDateString())
            ->first();

        if ($duplicate) {
            return redirect()
                ->route('staff.neonatal', ['child' => $duplicate->id])
                ->withErrors(['full_name' => 'This child profile already exists for the selected mother.']);
        }

        $photoPath = $request->file('child_photo')?->store('child-photos', 'public');

        $infant = Infant::create([
            'mother_id' => $mother->id,
            'full_name' => trim($validated['full_name']),
            'sex' => $validated['sex'],
            'birth_date' => $birthDate->toDateString(),
            'birth_weight' => $validated['birth_weight'] ?? null,
            'birth_height' => $validated['birth_height'] ?? null,
            'blood_type' => $validated['blood_type'] ?? null,
            'photo_path' => $photoPath,
            'facility' => $validated['facility'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        if (! empty($validated['birth_weight']) && ! empty($validated['birth_height'])) {
            InfantGrowthRecord::firstOrCreate(
                ['infant_id' => $infant->id, 'measured_at' => $birthDate->toDateString()],
                [
                    'recorded_by_staff_id' => $staff->id,
                    'age_months' => 0,
                    'weight' => $validated['birth_weight'],
                    'height' => $validated['birth_height'],
                    'remarks' => 'Birth baseline measurement.',
                ]
            );
        }

        $this->createInfantVaccineSchedule($infant, $birthDate, $staff);

        return redirect()
            ->route('staff.neonatal', ['child' => $infant->id])
            ->with('status', 'Child profile registered successfully.');
    }

    public function updateStaffInfant(Request $request, Infant $infant): RedirectResponse
    {
        $staff = $this->staffFromRequest($request);

        if (! $staff || ! $this->staffCanAccessInfant($staff, $infant)) {
            return redirect()->route('staff.neonatal')->with('status', 'You cannot update this child profile.');
        }

        $validated = $request->validate([
            'mother_id' => ['required', 'integer', 'exists:mothers,id'],
            'full_name' => ['required', 'string', 'max:255'],
            'sex' => ['required', Rule::in(['female', 'male', 'other'])],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'birth_weight' => ['nullable', 'numeric', 'between:0.5,12'],
            'birth_height' => ['nullable', 'numeric', 'between:20,80'],
            'blood_type' => ['nullable', Rule::in(self::BLOOD_TYPES)],
            'facility' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $mother = Mother::findOrFail($validated['mother_id']);

        if (! $this->casefileForStaffMother($staff, $mother)) {
            return redirect()->route('staff.neonatal', ['child' => $infant->id])
                ->with('status', 'You can only assign children to mothers in your casefiles.');
        }

        $birthDate = Carbon::parse($validated['birth_date'])->startOfDay();
        $duplicate = Infant::where('mother_id', $mother->id)
            ->whereRaw('LOWER(full_name) = ?', [Str::lower(trim($validated['full_name']))])
            ->whereDate('birth_date', $birthDate->toDateString())
            ->whereKeyNot($infant->id)
            ->first();

        if ($duplicate) {
            return back()
                ->withInput()
                ->withErrors(['full_name' => 'This child profile already exists for the selected mother.']);
        }

        $infant->update([
            'mother_id' => $mother->id,
            'full_name' => trim($validated['full_name']),
            'sex' => $validated['sex'],
            'birth_date' => $birthDate->toDateString(),
            'birth_weight' => $validated['birth_weight'] ?? null,
            'birth_height' => $validated['birth_height'] ?? null,
            'blood_type' => $validated['blood_type'] ?? null,
            'facility' => $validated['facility'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('staff.neonatal', ['child' => $infant->id])
            ->with('status', 'Child profile updated.');
    }

    public function updateStaffInfantPhoto(Request $request, Infant $infant): RedirectResponse
    {
        $staff = $this->staffFromRequest($request);

        if (! $staff || ! $this->staffCanAccessInfant($staff, $infant)) {
            return redirect()->route('staff.neonatal')->with('status', 'You cannot update this child photo.');
        }

        $validated = $request->validate([
            'child_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $oldPhotoPath = $infant->photo_path;
        $newPhotoPath = $validated['child_photo']->store('child-photos', 'public');

        $infant->update(['photo_path' => $newPhotoPath]);

        if ($oldPhotoPath) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        return redirect()
            ->route('staff.neonatal', ['child' => $infant->id])
            ->with('status', 'Child profile photo updated.');
    }

    public function storeStaffInfantGrowth(Request $request, Infant $infant): RedirectResponse
    {
        $staff = $this->staffFromRequest($request);

        if (! $staff || ! $this->staffCanAccessInfant($staff, $infant)) {
            return redirect()->route('staff.neonatal')->with('status', 'You cannot update this infant record.');
        }

        $validated = $request->validate([
            'measured_at' => ['required', 'date', 'before_or_equal:today'],
            'age_months' => ['required', 'integer', 'between:0,60'],
            'weight' => ['required', 'numeric', 'between:0.5,50'],
            'height' => ['required', 'numeric', 'between:20,130'],
            'head_circumference' => ['nullable', 'numeric', 'between:20,70'],
            'temperature' => ['nullable', 'numeric', 'between:34,43'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $date = Carbon::parse($validated['measured_at'])->toDateString();
        $duplicate = InfantGrowthRecord::where('infant_id', $infant->id)->whereDate('measured_at', $date)->exists();

        if ($duplicate) {
            return back()
                ->withInput()
                ->withErrors(['measured_at' => 'A growth measurement already exists for this date.']);
        }

        InfantGrowthRecord::create([
            'infant_id' => $infant->id,
            'recorded_by_staff_id' => $staff->id,
            'measured_at' => $date,
            'age_months' => (int) $validated['age_months'],
            'weight' => (float) $validated['weight'],
            'height' => (float) $validated['height'],
            'head_circumference' => $validated['head_circumference'] ?? null,
            'temperature' => $validated['temperature'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return redirect()
            ->route('staff.neonatal', ['child' => $infant->id])
            ->with('status', 'Growth record saved.');
    }

    public function updateStaffInfantGrowth(Request $request, InfantGrowthRecord $growth): RedirectResponse
    {
        $staff = $this->staffFromRequest($request);
        $infant = $growth->infant;

        if (! $staff || ! $infant || ! $this->staffCanAccessInfant($staff, $infant)) {
            return redirect()->route('staff.neonatal')->with('status', 'You cannot update this growth record.');
        }

        $validated = $request->validate([
            'measured_at' => ['required', 'date', 'before_or_equal:today'],
            'age_months' => ['required', 'integer', 'between:0,60'],
            'weight' => ['required', 'numeric', 'between:0.5,50'],
            'height' => ['required', 'numeric', 'between:20,130'],
            'head_circumference' => ['nullable', 'numeric', 'between:20,70'],
            'temperature' => ['nullable', 'numeric', 'between:34,43'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $date = Carbon::parse($validated['measured_at'])->toDateString();
        $duplicate = InfantGrowthRecord::where('infant_id', $infant->id)
            ->whereDate('measured_at', $date)
            ->whereKeyNot($growth->id)
            ->exists();

        if ($duplicate) {
            return back()
                ->withInput()
                ->withErrors(['measured_at' => 'Another growth measurement already exists for this date.']);
        }

        $growth->update([
            'recorded_by_staff_id' => $staff->id,
            'measured_at' => $date,
            'age_months' => (int) $validated['age_months'],
            'weight' => (float) $validated['weight'],
            'height' => (float) $validated['height'],
            'head_circumference' => $validated['head_circumference'] ?? null,
            'temperature' => $validated['temperature'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return redirect()
            ->route('staff.neonatal', ['child' => $infant->id])
            ->with('status', 'Growth record updated.');
    }

    public function deleteStaffInfantGrowth(Request $request, InfantGrowthRecord $growth): RedirectResponse
    {
        $staff = $this->staffFromRequest($request);
        $infant = $growth->infant;

        if (! $staff || ! $infant || ! $this->staffCanAccessInfant($staff, $infant)) {
            return redirect()->route('staff.neonatal')->with('status', 'You cannot delete this growth record.');
        }

        $growth->delete();

        return redirect()
            ->route('staff.neonatal', ['child' => $infant->id])
            ->with('status', 'Growth record deleted.');
    }

    public function storeStaffInfantVaccine(Request $request, Infant $infant): RedirectResponse
    {
        $staff = $this->staffFromRequest($request);

        if (! $staff || ! $this->staffCanAccessInfant($staff, $infant)) {
            return redirect()->route('staff.neonatal')->with('status', 'You cannot update this child record.');
        }

        $validated = $request->validate([
            'vaccine_group' => ['required', 'string', 'max:120'],
            'vaccine_name' => ['required', 'string', 'max:255'],
            'dose_label' => ['required', 'string', 'max:120'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['upcoming', 'completed', 'overdue', 'missed', 'cancelled'])],
            'administered_at' => ['nullable', 'date', 'before_or_equal:today'],
            'facility' => ['nullable', 'string', 'max:255'],
            'lot_number' => ['nullable', 'string', 'max:120'],
            'vaccinator' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validated['status'] === 'completed' && empty($validated['administered_at'])) {
            return back()
                ->withInput()
                ->withErrors(['administered_at' => 'Administration date is required when marking a vaccine completed.']);
        }

        $duplicate = InfantVaccineRecord::where('infant_id', $infant->id)
            ->whereRaw('LOWER(vaccine_name) = ?', [Str::lower(trim($validated['vaccine_name']))])
            ->whereRaw('LOWER(dose_label) = ?', [Str::lower(trim($validated['dose_label']))])
            ->exists();

        if ($duplicate) {
            return back()
                ->withInput()
                ->withErrors(['vaccine_name' => 'This vaccine dose already exists for this child.']);
        }

        InfantVaccineRecord::create([
            'infant_id' => $infant->id,
            'recorded_by_staff_id' => $staff->id,
            'vaccine_group' => trim($validated['vaccine_group']),
            'vaccine_name' => trim($validated['vaccine_name']),
            'dose_label' => trim($validated['dose_label']),
            'due_date' => $validated['due_date'] ?? null,
            'status' => $validated['status'],
            'administered_at' => $validated['administered_at'] ?? null,
            'facility' => $validated['facility'] ?? null,
            'lot_number' => $validated['lot_number'] ?? null,
            'vaccinator' => $validated['vaccinator'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return redirect()
            ->route('staff.neonatal', ['child' => $infant->id])
            ->with('status', 'Vaccine schedule saved.');
    }

    public function updateStaffInfantVaccine(Request $request, InfantVaccineRecord $vaccine): RedirectResponse
    {
        $staff = $this->staffFromRequest($request);
        $infant = $vaccine->infant;

        if (! $staff || ! $infant || ! $this->staffCanAccessInfant($staff, $infant)) {
            return redirect()->route('staff.neonatal')->with('status', 'You cannot update this vaccine record.');
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['upcoming', 'completed', 'overdue', 'missed', 'cancelled'])],
            'administered_at' => ['nullable', 'date', 'before_or_equal:today'],
            'facility' => ['nullable', 'string', 'max:255'],
            'lot_number' => ['nullable', 'string', 'max:120'],
            'vaccinator' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validated['status'] === 'completed' && empty($validated['administered_at'])) {
            return back()
                ->withInput()
                ->withErrors(['administered_at' => 'Administration date is required when marking a vaccine completed.']);
        }

        $vaccine->update([
            'recorded_by_staff_id' => $staff->id,
            'status' => $validated['status'],
            'administered_at' => $validated['administered_at'] ?? null,
            'facility' => $validated['facility'] ?? null,
            'lot_number' => $validated['lot_number'] ?? null,
            'vaccinator' => $validated['vaccinator'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return redirect()
            ->route('staff.neonatal', ['child' => $infant->id])
            ->with('status', 'Vaccine surveillance updated.');
    }

    public function cancelStaffInfantVaccine(Request $request, InfantVaccineRecord $vaccine): RedirectResponse
    {
        $staff = $this->staffFromRequest($request);
        $infant = $vaccine->infant;

        if (! $staff || ! $infant || ! $this->staffCanAccessInfant($staff, $infant)) {
            return redirect()->route('staff.neonatal')->with('status', 'You cannot cancel this vaccine record.');
        }

        $vaccine->update([
            'recorded_by_staff_id' => $staff->id,
            'status' => 'cancelled',
            'remarks' => $request->filled('remarks') ? Str::limit((string) $request->input('remarks'), 2000, '') : $vaccine->remarks,
        ]);

        return redirect()
            ->route('staff.neonatal', ['child' => $infant->id])
            ->with('status', 'Vaccine record cancelled.');
    }

    public function storeStaffChildAlert(Request $request, Infant $infant): RedirectResponse
    {
        $staff = $this->staffFromRequest($request);

        if (! $staff || ! $this->staffCanAccessInfant($staff, $infant)) {
            return redirect()->route('staff.neonatal')->with('status', 'You cannot update this child record.');
        }

        $validated = $request->validate([
            'alert_type' => ['required', Rule::in(['follow_up_needed', 'nutrition', 'vaccine', 'clinical_review', 'other'])],
            'title' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        ChildHealthAlert::create([
            'infant_id' => $infant->id,
            'created_by_staff_id' => $staff->id,
            'alert_type' => $validated['alert_type'],
            'title' => trim($validated['title']),
            'notes' => $validated['notes'] ?? null,
            'status' => 'active',
        ]);

        return redirect()
            ->route('staff.neonatal', ['child' => $infant->id])
            ->with('status', 'Child health alert added.');
    }

    public function resolveStaffChildAlert(Request $request, ChildHealthAlert $alert): RedirectResponse
    {
        $staff = $this->staffFromRequest($request);
        $infant = $alert->infant;

        if (! $staff || ! $infant || ! $this->staffCanAccessInfant($staff, $infant)) {
            return redirect()->route('staff.neonatal')->with('status', 'You cannot resolve this child alert.');
        }

        $alert->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        return redirect()
            ->route('staff.neonatal', ['child' => $infant->id])
            ->with('status', 'Child health alert resolved.');
    }
    public function logout(Request $request): RedirectResponse
    {
        $this->clearLoginSession($request);
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have been logged out.');
    }

    private function startLoginSession(Request $request, string $role, int $id, string $name, string $email): void
    {
        $request->session()->regenerate();
        $request->session()->forget([
            'admin_authenticated',
            'admin_id',
            'admin_username',
        ]);
        $request->session()->put([
            'auth_role' => $role,
            'auth_id' => $id,
            'auth_name' => $name,
            'auth_email' => $email,
        ]);
    }

    private function clearLoginSession(Request $request): void
    {
        $request->session()->forget([
            'auth_role',
            'auth_id',
            'auth_name',
            'auth_email',
            'admin_authenticated',
            'admin_id',
            'admin_username',
        ]);
    }

    private function attemptAdminLoginFromSharedForm(Request $request, string $identifier, string $password): ?RedirectResponse
    {
        $admin = AdminUser::query()
            ->whereRaw('LOWER(username) = ?', [Str::lower($identifier)])
            ->first();

        if (! $admin) {
            return null;
        }

        if (! Hash::check($password, $admin->password)) {
            $this->throwLoginError();
        }

        $this->startAdminSession($request, $admin);

        return redirect()->route('admin.statistics');
    }

    private function startAdminSession(Request $request, AdminUser $admin): void
    {
        $request->session()->regenerate();
        $request->session()->forget([
            'auth_role',
            'auth_id',
            'auth_name',
            'auth_email',
        ]);
        $request->session()->put([
            'admin_authenticated' => true,
            'admin_id' => $admin->id,
            'admin_username' => $admin->username,
        ]);

        $admin->forceFill(['last_login_at' => now()])->save();
    }

    private function staffFromRequest(Request $request): ?ProgramStaff
    {
        if ($request->session()->get('auth_role') !== 'staff') {
            return null;
        }

        return ProgramStaff::find($request->session()->get('auth_id'));
    }

    private function casefileForStaffMother(ProgramStaff $staff, Mother $mother): ?StaffMotherCasefile
    {
        return StaffMotherCasefile::where('staff_id', $staff->id)
            ->where('mother_id', $mother->id)
            ->first();
    }

    private function maternalVitalsValidator(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recorded_at' => ['required', 'date'],
            'pregnancy_week' => ['required', 'integer', 'between:1,42'],
            'weight' => ['required', 'numeric', 'min:0.1', 'max:300'],
            'bp_systolic' => ['required', 'integer', 'between:60,220'],
            'bp_diastolic' => ['required', 'integer', 'between:40,140'],
            'blood_sugar' => ['required', 'numeric', 'between:40,400'],
            'hemoglobin' => ['required', 'numeric', 'between:5,25'],
            'temperature' => ['required', 'numeric', 'between:34,43'],
            'heart_rate' => ['required', 'integer', 'between:40,180'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], [
            'recorded_at.required' => 'Record date is required.',
            'recorded_at.date' => 'Record date must be a valid date.',
            'pregnancy_week.required' => 'Pregnancy week is required.',
            'pregnancy_week.between' => 'Pregnancy week must be between 1 and 42.',
            'weight.required' => 'Weight is required.',
            'weight.numeric' => 'Weight must be a valid number.',
            'weight.min' => 'Weight must be greater than zero.',
            'weight.max' => 'Weight is outside the accepted range.',
            'bp_systolic.required' => 'Systolic blood pressure is required.',
            'bp_systolic.between' => 'Systolic BP must be from 60 to 220 mmHg.',
            'bp_diastolic.required' => 'Diastolic blood pressure is required.',
            'bp_diastolic.between' => 'Diastolic BP must be from 40 to 140 mmHg.',
            'blood_sugar.required' => 'Blood sugar is required.',
            'blood_sugar.between' => 'Blood sugar must be from 40 to 400 mg/dL.',
            'hemoglobin.required' => 'Hemoglobin is required.',
            'hemoglobin.between' => 'Hemoglobin must be from 5 to 25 g/dL.',
            'temperature.required' => 'Body temperature is required.',
            'temperature.between' => 'Body temperature must be entered in Celsius from 34 to 43 C.',
            'heart_rate.required' => 'Heart rate is required.',
            'heart_rate.between' => 'Heart rate must be from 40 to 180 bpm.',
            'notes.max' => 'Program staff notes must be 5000 characters or fewer.',
        ]);

        $validator->after(function ($validator) use ($request) {
            $systolic = $request->input('bp_systolic');
            $diastolic = $request->input('bp_diastolic');

            if (is_numeric($systolic) && is_numeric($diastolic) && (int) $diastolic >= (int) $systolic) {
                $validator->errors()->add('bp_diastolic', 'Diastolic BP should be lower than systolic BP.');
            }
        });

        return $validator;
    }

    private function maternalVitalsAttributes(array $validated, Mother $mother, ProgramStaff $staff, StaffMotherCasefile $casefile): array
    {
        $pregnancyWeek = (int) $validated['pregnancy_week'];

        return [
            'mother_id' => $mother->id,
            'staff_mother_casefile_id' => $casefile->id,
            'recorded_by_staff_id' => $staff->id,
            'pregnancy_week' => $pregnancyWeek,
            'pregnancy_month' => min(10, (int) ceil($pregnancyWeek / 4)),
            'bp_systolic' => (int) $validated['bp_systolic'],
            'bp_diastolic' => (int) $validated['bp_diastolic'],
            'blood_sugar' => (float) $validated['blood_sugar'],
            'weight' => (float) $validated['weight'],
            'hemoglobin' => (float) $validated['hemoglobin'],
            'temperature' => (float) $validated['temperature'],
            'heart_rate' => (int) $validated['heart_rate'],
            'risk_level' => $this->maternalRiskLevel($validated),
            'notes' => $validated['notes'] ?? null,
            'recorded_at' => Carbon::parse($validated['recorded_at'])->startOfDay(),
        ];
    }

    private function maternalRiskLevel(array $values): string
    {
        $systolic = (int) $values['bp_systolic'];
        $diastolic = (int) $values['bp_diastolic'];
        $sugar = (float) $values['blood_sugar'];
        $hemoglobin = (float) $values['hemoglobin'];
        $temperature = (float) $values['temperature'];
        $heartRate = (int) $values['heart_rate'];

        if (
            $systolic >= 140 ||
            $diastolic >= 90 ||
            $sugar < 60 ||
            $sugar > 200 ||
            $hemoglobin < 10 ||
            $temperature < 35 ||
            $temperature >= 38 ||
            $heartRate < 50 ||
            $heartRate > 120
        ) {
            return 'high';
        }

        if (
            $systolic >= 130 ||
            $diastolic >= 85 ||
            $sugar < 70 ||
            $sugar > 140 ||
            $hemoglobin < 11.5 ||
            $temperature < 36 ||
            $temperature > 37.5 ||
            $heartRate < 60 ||
            $heartRate > 110
        ) {
            return 'medium';
        }

        return 'low';
    }

    private function maternalVitalsPayload(Mother $mother): array
    {
        $records = MaternalMonitoringRecord::with('recorder')
            ->where('mother_id', $mother->id)
            ->orderBy('recorded_at')
            ->orderBy('created_at')
            ->get();
        $latest = $records->last();

        return [
            'latest' => $latest ? $this->formatMaternalVitalsRecord($latest) : null,
            'records' => $records->map(fn (MaternalMonitoringRecord $record): array => $this->formatMaternalVitalsRecord($record))->values(),
            'weight_history' => $records
                ->filter(fn (MaternalMonitoringRecord $record): bool => $record->weight !== null)
                ->map(fn (MaternalMonitoringRecord $record): array => [
                    'id' => $record->id,
                    'recorded_at' => ($record->recorded_at ?? $record->created_at)?->toDateString(),
                    'recorded_label' => ($record->recorded_at ?? $record->created_at)?->format('M j, Y') ?? 'Date not recorded',
                    'pregnancy_week' => $record->pregnancy_week,
                    'label' => $record->pregnancy_week ? 'Wk '.$record->pregnancy_week : (($record->recorded_at ?? $record->created_at)?->format('M j') ?? 'Record'),
                    'weight' => (float) $record->weight,
                    'tooltip' => (($record->recorded_at ?? $record->created_at)?->format('M j, Y') ?? 'Date not recorded').' - Week '.($record->pregnancy_week ?: 'N/A').' - '.rtrim(rtrim(number_format((float) $record->weight, 2), '0'), '.').' kg',
                ])
                ->values(),
            'blood_pressure_history' => $records
                ->filter(fn (MaternalMonitoringRecord $record): bool => $record->bp_systolic !== null && $record->bp_diastolic !== null)
                ->map(fn (MaternalMonitoringRecord $record): array => [
                    'id' => $record->id,
                    'recorded_at' => ($record->recorded_at ?? $record->created_at)?->toDateString(),
                    'recorded_label' => ($record->recorded_at ?? $record->created_at)?->format('M j, Y') ?? 'Date not recorded',
                    'pregnancy_week' => $record->pregnancy_week,
                    'label' => $record->pregnancy_week ? 'Wk '.$record->pregnancy_week : (($record->recorded_at ?? $record->created_at)?->format('M j') ?? 'Record'),
                    'systolic' => (int) $record->bp_systolic,
                    'diastolic' => (int) $record->bp_diastolic,
                    'tooltip' => (($record->recorded_at ?? $record->created_at)?->format('M j, Y') ?? 'Date not recorded').' - Week '.($record->pregnancy_week ?: 'N/A').' - '.$record->bp_systolic.'/'.$record->bp_diastolic.' mmHg',
                ])
                ->values(),
            'risk_status' => $latest?->risk_level ?? 'pending',
            'risk_label' => $this->maternalRiskLabel($latest?->risk_level),
        ];
    }

    private function formatMaternalVitalsRecord(MaternalMonitoringRecord $record): array
    {
        $date = $record->recorded_at ?? $record->created_at;

        return [
            'id' => $record->id,
            'mother_id' => $record->mother_id,
            'casefile_id' => $record->staff_mother_casefile_id,
            'recorded_by_staff_id' => $record->recorded_by_staff_id,
            'recorder_name' => $record->recorder?->full_name,
            'recorded_at' => $date?->toDateString(),
            'recorded_label' => $date?->format('M j, Y') ?? 'Date not recorded',
            'pregnancy_week' => $record->pregnancy_week,
            'pregnancy_month' => $record->pregnancy_month,
            'bp_systolic' => $record->bp_systolic,
            'bp_diastolic' => $record->bp_diastolic,
            'blood_pressure' => $record->bp_systolic && $record->bp_diastolic ? $record->bp_systolic.'/'.$record->bp_diastolic : null,
            'blood_sugar' => $record->blood_sugar === null ? null : (float) $record->blood_sugar,
            'weight' => $record->weight === null ? null : (float) $record->weight,
            'hemoglobin' => $record->hemoglobin === null ? null : (float) $record->hemoglobin,
            'temperature' => $record->temperature === null ? null : (float) $record->temperature,
            'heart_rate' => $record->heart_rate,
            'risk_level' => $record->risk_level ?? 'pending',
            'risk_label' => $this->maternalRiskLabel($record->risk_level),
            'notes' => $record->notes,
        ];
    }

    private function maternalRiskLabel(?string $riskLevel): string
    {
        return match (strtolower((string) $riskLevel)) {
            'low' => 'Low Risk',
            'medium' => 'Needs Review',
            'high' => 'High Risk',
            default => 'Pending',
        };
    }

    private function staffCanAccessInfant(ProgramStaff $staff, Infant $infant): bool
    {
        $infant->loadMissing('mother');

        return $infant->mother !== null && $this->casefileForStaffMother($staff, $infant->mother) !== null;
    }

    private function createInfantVaccineSchedule(Infant $infant, Carbon $birthDate, ?ProgramStaff $staff = null): void
    {
        foreach ($this->defaultInfantVaccineSchedule($birthDate) as $item) {
            InfantVaccineRecord::firstOrCreate(
                [
                    'infant_id' => $infant->id,
                    'vaccine_name' => $item['name'],
                    'dose_label' => $item['dose'],
                ],
                [
                    'recorded_by_staff_id' => $staff?->id,
                    'vaccine_group' => $item['group'],
                    'due_date' => $item['due_date'],
                    'status' => 'upcoming',
                ]
            );
        }
    }

    private function defaultInfantVaccineSchedule(Carbon $birthDate): array
    {
        $items = [
            ['Birth Vaccines', 'BCG', 'Birth dose', 0],
            ['Birth Vaccines', 'Hepatitis B', 'Birth dose', 0],
            ['Primary Series', 'Pentavalent / DPT 1', 'Dose 1', 42],
            ['Primary Series', 'OPV 1', 'Dose 1', 42],
            ['Primary Series', 'PCV 1', 'Dose 1', 42],
            ['Primary Series', 'Pentavalent / DPT 2', 'Dose 2', 70],
            ['Primary Series', 'OPV 2', 'Dose 2', 70],
            ['Primary Series', 'PCV 2', 'Dose 2', 70],
            ['Primary Series', 'Pentavalent / DPT 3', 'Dose 3', 98],
            ['Primary Series', 'OPV 3', 'Dose 3', 98],
            ['Primary Series', 'IPV', 'Primary dose', 98],
            ['Primary Series', 'PCV 3', 'Dose 3', 98],
            ['Measles', 'Measles-Rubella', 'Dose 1', 270],
            ['Measles', 'MMR', 'Dose 1', 365],
            ['Boosters', 'DPT Booster', 'Booster', 455],
            ['Boosters', 'OPV Booster', 'Booster', 455],
        ];

        return collect($items)->map(fn (array $item): array => [
            'group' => $item[0],
            'name' => $item[1],
            'dose' => $item[2],
            'due_date' => $birthDate->copy()->addDays($item[3])->toDateString(),
        ])->all();
    }

    private function infantGrowthAssessment(Infant $infant): array
    {
        $latest = $infant->growthRecords->last();

        if (! $latest) {
            return ['label' => 'Awaiting Growth Record', 'severity' => 'warning', 'alerts' => ['No growth measurement has been recorded yet.']];
        }

        $age = max(0, (int) $latest->age_months);
        $weight = (float) $latest->weight;
        $height = (float) $latest->height;
        $alerts = [];
        $severity = 'normal';

        $minWeight = max(2.4, 2.6 + ($age * 0.45));
        $maxWeight = max(4.6, 5.0 + ($age * 0.75));
        $minHeight = max(45, 47 + ($age * 1.4));

        if ($weight < $minWeight || $weight > $maxWeight) {
            $severity = 'warning';
            $alerts[] = $weight < $minWeight ? 'Weight needs review for age.' : 'Weight is above the usual range for age.';
        }

        if ($height < $minHeight) {
            $severity = 'critical';
            $alerts[] = 'Height needs review for age.';
        }

        if ($severity === 'normal') {
            $alerts[] = 'Growth indicators are within the expected review range.';
        }

        return [
            'label' => match ($severity) {
                'critical' => 'Growth Needs Review',
                'warning' => 'Needs Follow-up',
                default => 'On Track',
            },
            'severity' => $severity,
            'alerts' => $alerts,
        ];
    }
    private function redirectForCurrentRole(Request $request): ?RedirectResponse
    {
        if ($request->session()->get('admin_authenticated') === true) {
            return redirect()->route('admin.statistics');
        }

        return match ($request->session()->get('auth_role')) {
            'mother' => redirect()->route('mother.dashboard'),
            'staff' => redirect()->route('staff.dashboard'),
            default => null,
        };
    }

    private function throwLoginError(): never
    {
        throw ValidationException::withMessages([
            'email' => 'The selected role, email, or password is incorrect.',
        ]);
    }

    private function prepareNameFields(Request $request): void
    {
        if (! $request->filled('full_name') || ($request->filled('first_name') && $request->filled('last_name'))) {
            return;
        }

        [$firstName, $middleName, $lastName] = $this->splitFullName($request->input('full_name'));

        $request->merge([
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
        ]);
    }

    private function splitFullName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($parts) === 0) {
            return ['', null, ''];
        }

        if (count($parts) === 1) {
            return [$parts[0], null, ''];
        }

        $firstName = array_shift($parts);
        $lastName = array_pop($parts);
        $middleName = count($parts) > 0 ? implode(' ', $parts) : null;

        return [$firstName, $middleName, $lastName];
    }

    private function inayKaalamanVideoMonths(): array
    {
        $months = [
            1 => ['Conception & New Beginnings', 'Weeks 1-4', [['Early Pregnancy And Prenatal Care', 'Prenatal Care', '7 min'], ['Conception & New Beginnings Nutrition Tips', 'Nutrition', '5 min']]],
            2 => ['The Tiny Heart Beats', 'Weeks 5-8', [['The Tiny Heart Beats: What To Expect', 'Baby Development', '6 min'], ['Morning Sickness Care At Home', 'Maternal Care', '5 min']]],
            3 => ['First Trimester Milestones', 'Weeks 9-12', [['First Trimester Safety Reminders', 'Safety', '6 min'], ['Healthy Plate For Pregnancy', 'Nutrition', '5 min']]],
            4 => ['Energy Returns', 'Weeks 13-16', [['Second Trimester Changes', 'Maternal Care', '6 min'], ['Safe Movement And Rest', 'Wellness', '4 min']]],
            5 => ['Feeling Baby Move', 'Weeks 17-20', [['Understanding Baby Movements', 'Baby Development', '6 min'], ['Comfort Tips For Back Pain', 'Wellness', '5 min']]],
            6 => ['Growth And Screening', 'Weeks 21-24', [['Screening And Checkup Reminders', 'Checkup', '7 min'], ['Eating Well In Month Six', 'Nutrition', '5 min']]],
            7 => ['Preparing For The Final Stretch', 'Weeks 25-28', [['Preparing For The Final Stretch Guide', 'Maternal Health', '6 min'], ['Preparing For The Final Stretch Nutrition Tips', 'Nutrition', '5 min']]],
            8 => ['Monitoring Baby Position', 'Weeks 29-32', [['Counting Baby Kicks', 'Monitoring', '5 min'], ['Comfortable Sleep Positions', 'Wellness', '4 min']]],
            9 => ['Birth Readiness', 'Weeks 33-36', [['What To Pack For Delivery', 'Birth Plan', '6 min'], ['When To Go To The Clinic', 'Safety', '5 min']]],
            10 => ['Safe Delivery And Newborn Care', 'Weeks 37-40', [['Safe Delivery Reminders', 'Delivery', '7 min'], ['Newborn Care Basics', 'Newborn', '6 min']]],
        ];

        return collect($months)->mapWithKeys(function (array $monthData, int $month): array {
            [$title, $weeks, $videos] = $monthData;

            $currentVideos = collect($videos)->map(fn (array $video): array => $this->formatKaalamanVideo($video))->all();
            $archiveVideos = [
                $this->formatKaalamanVideo(["{$title} Barangay Replay", 'Uploaded Video', '12 min']),
                $this->formatKaalamanVideo(["{$title} Previous Version", 'Previous Upload', '8 min']),
            ];

            return [
                $month => [
                    'month' => $month,
                    'kicker' => "Month {$month} Video Library",
                    'title' => $title,
                    'weeks' => $weeks,
                    'videos' => $currentVideos,
                    'archive' => $archiveVideos,
                ],
            ];
        })->all();
    }

    private function formatKaalamanVideo(array $video): array
    {
        [$title, $tag, $time] = $video;
        $query = urlencode($title.' pregnancy education');

        return [
            'title' => $title,
            'tag' => $tag,
            'time' => $time,
            'url' => "https://www.youtube.com/results?search_query={$query}",
        ];
    }

    private function inayKaalamanProgressSummary(Mother $mother, mixed $uploadsByMonth, mixed $progressRecords): array
    {
        $progressByMonth = collect($progressRecords)->groupBy('month');
        $uploadsByMonth = collect($uploadsByMonth);
        $monthDefinitions = $this->inayKaalamanVideoMonths();
        $requiredDocumentTypes = ['Prenatal Records and Receipts'];
        $months = [];
        $overallCompleted = 0;
        $overallRequired = 0;
        $completedMonths = 0;
        $totalUploads = 0;

        foreach ($monthDefinitions as $month => $definition) {
            $monthProgress = collect($progressByMonth->get($month, []));
            $monthUploads = collect($uploadsByMonth->get($month, []));
            $totalUploads += $monthUploads->count();

            $readingRecord = $monthProgress->first(fn ($record) => $record->activity_type === 'reading');
            $readingStatus = $this->kaalamanActivityStatus($readingRecord, 'reading');
            $readingComplete = $readingStatus['status'] === 'read';

            $videos = collect($definition['videos'])->values()->map(function (array $video, int $index) use ($month, $monthProgress): array {
                $itemKey = "month-{$month}-video-{$index}";
                $record = $monthProgress->first(fn ($progress) => $progress->activity_type === 'video' && $progress->item_key === $itemKey);
                $status = $this->kaalamanActivityStatus($record, 'video');

                return [
                    'key' => $itemKey,
                    'title' => $video['title'],
                    'tag' => $video['tag'],
                    'time' => $video['time'],
                    'status' => $status['status'],
                    'label' => $status['label'],
                    'completed_at' => $status['completed_at'],
                ];
            })->all();

            $watchedVideos = collect($videos)->where('status', 'watched')->count();
            $infographicKey = "month-{$month}-infographic";
            $infographicRecord = $monthProgress->first(fn ($record) => $record->activity_type === 'infographic' && $record->item_key === $infographicKey);
            $infographicStatus = $this->kaalamanActivityStatus($infographicRecord, 'infographic');
            $infographicComplete = $infographicStatus['status'] === 'reviewed';

            $documentStatus = collect($requiredDocumentTypes)->map(function (string $type) use ($monthUploads): array {
                $upload = $type === 'Prenatal Records and Receipts'
                    ? $monthUploads->first(fn ($item) => $this->isPrenatalRecordUpload((string) $item->record_type))
                    : $monthUploads->first(fn ($item) => strcasecmp($item->record_type, $type) === 0);

                return [
                    'type' => $type,
                    'label' => $this->kaalamanDocumentLabel($type),
                    'uploaded' => (bool) $upload,
                    'filename' => $upload?->original_name,
                    'uploaded_at' => $upload?->created_at?->format('M j, Y'),
                ];
            })->all();

            $uploadedRequiredDocuments = collect($documentStatus)->where('uploaded', true)->count();
            $uploadedDocuments = $monthUploads->map(fn ($upload): array => [
                'type' => $upload->record_type,
                'label' => $this->kaalamanDocumentLabel($upload->record_type),
                'filename' => $upload->original_name,
                'uploaded_at' => $upload->created_at?->format('M j, Y'),
            ])->values()->all();

            $requiredCount = 1 + count($videos) + 1 + count($requiredDocumentTypes);
            $completedCount = ($readingComplete ? 1 : 0) + $watchedVideos + ($infographicComplete ? 1 : 0) + $uploadedRequiredDocuments;
            $percentage = $requiredCount > 0 ? (int) round($completedCount / $requiredCount * 100) : 0;
            $status = $completedCount === 0 ? 'Not Started' : ($completedCount >= $requiredCount ? 'Completed' : 'In Progress');

            $overallCompleted += $completedCount;
            $overallRequired += $requiredCount;

            if ($status === 'Completed') {
                $completedMonths++;
            }

            $months[$month] = [
                'month' => $month,
                'title' => $definition['title'],
                'weeks' => $definition['weeks'],
                'trimester' => $this->kaalamanTrimesterLabel($month),
                'reading' => $readingStatus,
                'videos' => $videos,
                'watched_videos' => $watchedVideos,
                'total_videos' => count($videos),
                'infographic' => $infographicStatus,
                'documents' => $documentStatus,
                'uploaded_documents' => $uploadedDocuments,
                'uploaded_required_documents' => $uploadedRequiredDocuments,
                'required_documents' => count($requiredDocumentTypes),
                'completed_count' => $completedCount,
                'required_count' => $requiredCount,
                'percentage' => $percentage,
                'status' => $status,
                'is_complete' => $status === 'Completed',
            ];
        }

        return [
            'months' => $months,
            'overall' => [
                'completed_months' => $completedMonths,
                'total_months' => count($monthDefinitions),
                'completed_required' => $overallCompleted,
                'total_required' => $overallRequired,
                'pending_required' => max(0, $overallRequired - $overallCompleted),
                'percentage' => $overallRequired > 0 ? (int) round($overallCompleted / $overallRequired * 100) : 0,
                'files_uploaded' => $totalUploads,
            ],
        ];
    }

    private function kaalamanActivityStatus(?InayKaalamanProgress $record, string $activityType): array
    {
        $status = $record?->status ?? 'not_started';

        $label = match ($status) {
            'read' => 'Read',
            'watched' => 'Watched',
            'reviewed' => 'Reviewed',
            'in_progress' => $activityType === 'reading' ? 'Reading in Progress...' : 'In Progress',
            default => 'Not Started',
        };

        return [
            'status' => $status,
            'label' => $label,
            'completed_at' => $record?->completed_at?->format('M j, Y, g:i A'),
        ];
    }

    private function kaalamanDocumentLabel(string $type): string
    {
        return [
            'Prenatal Records and Receipts' => 'Prenatal Records and Receipts',
            'Checkup Records' => 'Prenatal Records and Receipts',
            'Prescription' => 'Prenatal Records and Receipts',
            'Receipts' => 'Prenatal Records and Receipts',
            'Certificate' => 'Certificate',
            'Other Documents' => 'Other Supporting Document',
        ][$type] ?? $type;
    }

    private function isPrenatalRecordUpload(string $type): bool
    {
        return in_array($type, [
            'Prenatal Records and Receipts',
            'Checkup Records',
            'Prescription',
            'Receipts',
        ], true);
    }

    private function kaalamanTrimesterLabel(int $month): string
    {
        return match (true) {
            $month <= 3 => 'First Trimester',
            $month <= 6 => 'Second Trimester',
            $month <= 9 => 'Third Trimester',
            default => 'Labor & Delivery',
        };
    }

    private function careCompletionPercentage(mixed $records, array $kaalamanOverallProgress): int
    {
        $monitoringRequired = 8;
        $monitoringCompleted = min(collect($records)->count(), $monitoringRequired);
        $required = $monitoringRequired + (int) ($kaalamanOverallProgress['total_required'] ?? 0);
        $completed = $monitoringCompleted + (int) ($kaalamanOverallProgress['completed_required'] ?? 0);

        return $required > 0 ? (int) round($completed / $required * 100) : 0;
    }

    private function buildSimplePdf(array $lines): string
    {
        $content = "BT\n/F1 20 Tf\n72 760 Td\n";
        $firstLine = true;

        foreach ($lines as $line) {
            $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);

            if (! $firstLine) {
                $content .= "0 -28 Td\n";
            }

            $content .= '('.$text.") Tj\n";
            $content .= "/F1 12 Tf\n";
            $firstLine = false;
        }

        $content .= "ET\n";

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
            '<< /Length '.strlen($content)." >>\nstream\n".$content.'endstream',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xrefOffset."\n%%EOF";

        return $pdf;
    }

    private function generateStaffId(): string
    {
        do {
            $staffId = 'STAFF-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
        } while (ProgramStaff::where('staff_id', $staffId)->exists());

        return $staffId;
    }
}
