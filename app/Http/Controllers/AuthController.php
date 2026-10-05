<?php

namespace App\Http\Controllers;

use App\Models\Mother;
use App\Models\DswdStaff;
use App\Models\Conversation;
use App\Models\InayKaalamanProgress;
use App\Models\InayKaalamanUpload;
use App\Models\AdminUser;
use App\Models\Appointment;
use App\Models\ChildHealthAlert;
use App\Models\EducationalContent;
use App\Models\InfantVaccineRecord;
use App\Models\InfantGrowthRecord;
use App\Models\Infant;
use App\Models\MaternalMonitoringRecordAudit;
use App\Models\MaternalMonitoringRecord;
use App\Models\Message;
use App\Models\ProgramStaff;
use App\Models\StaffMotherCasefile;
use App\Support\AppNotificationService;
use App\Support\MaternalVitalScreening;
use App\Support\MotherCareRecordPdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuthController extends Controller
{
    private const SAN_PABLO_BARANGAYS = [
        'Bagong Bayan II-A',
        'Bagong Pook VI-C',
        'Barangay I-A',
        'Barangay I-B',
        'Barangay II-A',
        'Barangay II-B',
        'Barangay II-C',
        'Barangay II-D',
        'Barangay II-E',
        'Barangay II-F',
        'Barangay III-A',
        'Barangay III-B',
        'Barangay III-C',
        'Barangay III-D',
        'Barangay III-E',
        'Barangay III-F',
        'Barangay IV-A',
        'Barangay IV-B',
        'Barangay IV-C',
        'Barangay V-A',
        'Barangay V-B',
        'Barangay V-C',
        'Barangay V-D',
        'Barangay VI-A',
        'Barangay VI-B',
        'Barangay VI-D',
        'Barangay VI-E',
        'Barangay VII-A',
        'Barangay VII-B',
        'Barangay VII-C',
        'Barangay VII-D',
        'Barangay VII-E',
        'Bautista',
        'Concepcion',
        'Del Remedio',
        'Dolores',
        'San Antonio 1',
        'San Antonio 2',
        'San Bartolome',
        'San Buenaventura',
        'San Crispin',
        'San Cristobal',
        'San Diego',
        'San Francisco',
        'San Gabriel',
        'San Gregorio',
        'San Ignacio',
        'San Isidro',
        'San Joaquin',
        'San Jose',
        'San Juan',
        'San Lorenzo',
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
        'Santa Felomina',
        'Santa Isabel',
        'Santa Maria Magdalena',
        'Santa Veronica',
        'Santiago I',
        'Santiago II',
        'Santisimo Rosario',
        'Santo Angel',
        'Santo Cristo',
        'Santo Niño',
        'Soledad',
        'Atisan',
        'Santa Elena',
        'Santa Maria',
        'Santa Monica',
    ];

    private const BLOOD_TYPES = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Unknown'];

    private const PREGNANCY_STATUSES = ['not_pregnant', 'pregnant', 'postpartum', 'planning'];

    private const CIVIL_STATUSES = ['Single', 'Married', 'Widowed', 'Separated'];

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
            'municipality_city' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:30'],
            'age' => ['nullable', 'integer', 'between:10,60'],
            'civil_status' => ['nullable', Rule::in(self::CIVIL_STATUSES)],
            'blood_type' => ['nullable', Rule::in(self::BLOOD_TYPES)],
            'pregnancy_status' => ['nullable', Rule::in(self::PREGNANCY_STATUSES)],
            'location_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'location_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location_accuracy' => ['nullable', 'integer', 'min:0'],
            'privacy_policy' => ['accepted'],
            'is_4ps_beneficiary' => ['required', Rule::in(['yes', 'no'])],
        ]);

        $mother = Mother::create([
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'barangay' => $validated['barangay'],
            'municipality_city' => $validated['municipality_city'] ?? null,
            'contact_number' => $validated['contact_number'],
            'age' => $validated['age'] ?? null,
            'civil_status' => $validated['civil_status'] ?? null,
            'blood_type' => $validated['blood_type'] ?? null,
            'pregnancy_status' => $validated['pregnancy_status'] ?? null,
            'location_latitude' => $validated['location_latitude'] ?? null,
            'location_longitude' => $validated['location_longitude'] ?? null,
            'location_accuracy' => $validated['location_accuracy'] ?? null,
            'is_4ps_beneficiary' => ($validated['is_4ps_beneficiary'] ?? 'no') === 'yes',
        ]);

        $this->notifyProgramStaffOfNewMother($mother);

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
            'role' => ['required', Rule::in(['mother', 'staff', 'dswd_staff'])],
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $identifier = trim($validated['email']);

        if ($validated['role'] !== 'dswd_staff') {
            if ($adminRedirect = $this->attemptAdminLoginFromSharedForm($request, $identifier, $validated['password'])) {
                return $adminRedirect;
            }
        }

        if (! filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $this->throwLoginError();
        }

        if ($validated['role'] === 'dswd_staff') {
            if (! \Illuminate\Support\Facades\Schema::hasTable('dswd_staff')) {
                try {
                    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            if (! \Illuminate\Support\Facades\Schema::hasTable('dswd_staff')) {
                $this->throwLoginError();
            }

            $staff = DswdStaff::where('email', $identifier)->where('is_active', true)->first();
            if (! $staff || ! Hash::check($validated['password'], $staff->password)) {
                $this->throwLoginError();
            }
            $this->startLoginSession($request, 'dswd_staff', $staff->id, $staff->name, $staff->email);
            return redirect()->route('dswd.dashboard');
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

        $latestRecord = $mother->maternalMonitoringRecords()->orderByDesc('recorded_at')->orderByDesc('created_at')->first();
        $monitoringCount = $mother->maternalMonitoringRecords()->count();
        $careTeam = $mother->casefileStaff()->orderBy('last_name')->get();
        $childCount = Infant::where('mother_id', $mother->id)->count();
        $unreadMessageCount = Message::where('receiver_id', $mother->id)
            ->where('receiver_role', Message::ROLE_MOTHER)->where('is_read', false)
            ->where('is_unsent', false)->count();

        return view('dashboards.mother', compact('mother', 'latestRecord', 'monitoringCount', 'careTeam', 'childCount', 'unreadMessageCount'));
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
        $infographicsByMonth = $publishedEducationalContents
            ->filter(fn ($content) => $content->has_infographic && $content->month !== null)->groupBy('month');
        $infographicProgressByKey = $kaalamanProgressRecords->where('activity_type', 'infographic')->keyBy('item_key');

        return view('modules.inay-kaalaman', compact(
            'mother',
            'kaalamanUploads',
            'kaalamanMonthlyProgress',
            'kaalamanOverallProgress',
            'publishedEducationalContentByStage',
            'publishedEducationalContentByMonth',
            'infographicsByMonth',
            'infographicProgressByKey',
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
            ->route($request->routeIs('mother.documents.*') ? 'mother.documents.index' : 'inay-kaalaman')
            ->with('status', 'Document removed from Documents and INAY Kaalaman.');
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
                ->route($request->routeIs('mother.documents.*') ? 'mother.documents.index' : 'inay-kaalaman')
                ->with('status', 'This document was already uploaded for the selected month.');
        }

        $path = $document->store('inay-kaalaman-records', 'public');

        InayKaalamanUpload::create([
            'mother_id' => $mother->id,
            'month' => $validated['month'],
            'record_type' => $validated['record_type'],
            'original_name' => $document->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $document->getMimeType(),
            'size' => $document->getSize() ?: 0,
        ]);

        return redirect()
            ->route($request->routeIs('mother.documents.*') ? 'mother.documents.index' : 'inay-kaalaman')
            ->with('status', 'Document uploaded and shared with your program staff.');
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

        if ($activityType === 'infographic') {
            $content = EducationalContent::published()->infographics()->get()
                ->first(fn ($content) => $content->infographic_progress_key === $validated['item_key']
                    && $content->infographic_progress_month === (int) $validated['month']);

            if (! $content) {
                return response()->json(['message' => 'This infographic is unavailable. Refresh the learning page.'], 422);
            }

            $validated['item_title'] = $content->title;
        }

        $progress = DB::transaction(function () use ($mother, $validated, $activityType, $requestedStatus, $finalStatuses) {
            // Serialize writes for this mother, including simultaneous first views.
            Mother::whereKey($mother->id)->lockForUpdate()->firstOrFail();
            $progress = InayKaalamanProgress::firstOrCreate([
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

            return $progress;
        }, 3);

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

        $registeredMotherCount = Mother::count();
        $assignedMotherIds = StaffMotherCasefile::where('staff_id', $staff->id)->pluck('mother_id');
        $assignedMotherCount = $assignedMotherIds->unique()->count();
        $staffAppointments = Appointment::where('staff_id', $staff->id)
            ->whereIn('mother_id', $assignedMotherIds);
        $pendingAppointmentCount = (clone $staffAppointments)
            ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_RESCHEDULE_REQUESTED])
            ->count();
        $todayAppointments = (clone $staffAppointments)->with('mother')
            ->whereDate('appointment_date', today())
            ->whereIn('status', [Appointment::STATUS_CONFIRMED, Appointment::STATUS_RESCHEDULED])
            ->orderBy('start_time')->get();
        $upcomingAppointments = (clone $staffAppointments)->with('mother')
            ->whereDate('appointment_date', '>', today())
            ->whereIn('status', [Appointment::STATUS_CONFIRMED, Appointment::STATUS_RESCHEDULED])
            ->orderBy('appointment_date')->orderBy('start_time')->limit(5)->get();
        $withoutMonitoring = Mother::whereIn('id', $assignedMotherIds)->doesntHave('maternalMonitoringRecords');
        $withoutMonitoringCount = (clone $withoutMonitoring)->count();
        $mothersWithoutMonitoring = $withoutMonitoring->orderBy('last_name')->orderBy('first_name')->limit(5)->get();
        $recentMonitoring = MaternalMonitoringRecord::with('mother')->whereIn('mother_id', $assignedMotherIds)
            ->orderByDesc('recorded_at')->orderByDesc('created_at')->limit(5)->get();

        return view('dashboards.staff', compact(
            'staff', 'registeredMotherCount', 'assignedMotherCount', 'pendingAppointmentCount',
            'todayAppointments', 'upcomingAppointments', 'withoutMonitoringCount',
            'mothersWithoutMonitoring', 'recentMonitoring',
        ));
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
        $fourPs = (string) $request->query('four_ps', 'all');
        $allowedFourPsFilters = ['all', 'beneficiary', 'non_4ps'];
        $fourPs = in_array($fourPs, $allowedFourPsFilters, true) ? $fourPs : 'all';

        $mothersQuery = Mother::query()
            ->with(['maternalMonitoringRecords' => fn ($query) => $query
                ->orderByDesc('recorded_at')
                ->orderByDesc('created_at')]);

        if ($search !== '') {
            $tokens = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            foreach ($tokens as $token) {
                $like = '%'.$token.'%';

                $patientIds = $this->patientIdsFromSearchToken($token);

                $mothersQuery->where(function ($query) use ($like, $patientIds) {
                    $query->where('first_name', 'like', $like)
                        ->orWhere('middle_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('contact_number', 'like', $like)
                        ->orWhere('barangay', 'like', $like);

                    if ($patientIds !== []) {
                        $query->orWhereIn('id', $patientIds);
                    }
                });
            }
        }

        if ($fourPs === 'beneficiary') {
            $mothersQuery->where('is_4ps_beneficiary', true);
        } elseif ($fourPs === 'non_4ps') {
            $mothersQuery->where('is_4ps_beneficiary', false);
        }

        $mothers = $mothersQuery
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(10)
            ->withQueryString();

        $totalMothers = Mother::count();
        $withMonitoring = MaternalMonitoringRecord::query()->distinct('mother_id')->count('mother_id');

        return view('modules.staff-mothers', compact(
            'staff',
            'mothers',
            'search',
            'fourPs',
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

        $casefile = StaffMotherCasefile::firstOrCreate([
            'staff_id' => $staff->id,
            'mother_id' => $mother->id,
        ]);

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
        $consultations = Conversation::with('lastMessage')
            ->where('mother_id', $mother->id)
            ->where('program_staff_id', $staff->id)
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->take(5)
            ->get();

        $f1kdPeriod = $request->validate(['f1kd_month' => ['nullable', 'date_format:Y-m']])['f1kd_month'] ?? now()->format('Y-m');
        $f1kdBeneficiaries = $mother->is_4ps_beneficiary
            ? app(\App\Support\F1kdCompliance::class)->rows(['month'=>$f1kdPeriod, 'mother_id'=>$mother->id])
            : collect();

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
            'consultations',
            'f1kdBeneficiaries',
            'f1kdPeriod',
        ));
    }

    public function updateStaffMother(Request $request, Mother $mother): RedirectResponse
    {
        $staff = $this->staffFromRequest($request);
        abort_unless($staff, 401);
        abort_unless($staff->is_approved && $this->casefileForStaffMother($staff, $mother), 403);

        $validated = $request->validateWithBag('motherInformation', [
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'contact_number' => ['required', 'string', 'max:25', 'regex:/^\\+?[0-9 ()-]{7,25}$/', function ($attribute, $value, $fail) {
                if (strlen(preg_replace('/\\D/', '', $value)) < 7) {
                    $fail('The contact number must contain at least 7 digits.');
                }
            }],
            'barangay' => ['required', 'string', 'max:255'],
            'municipality_city' => ['nullable', 'string', 'max:255'],
            'age' => ['nullable', 'integer', 'min:10', 'max:65'],
            'civil_status' => ['nullable', Rule::in(self::CIVIL_STATUSES)],
            'gravidity' => ['nullable', 'integer', 'min:0', 'max:30'],
            'parity' => ['nullable', 'integer', 'min:0', 'max:30'],
            'blood_type' => ['nullable', Rule::in(self::BLOOD_TYPES)],
            'pregnancy_status' => ['nullable', Rule::in(self::PREGNANCY_STATUSES)],
            'is_4ps_beneficiary' => ['required', 'boolean'],
        ]);

        $mother->update($validated);

        return redirect()->route('staff.mothers.show', $mother)
            ->with('status', 'Mother information updated successfully.');
    }

    public function staffMotherRecord(Request $request, Mother $mother, string $format = 'print'): Response|JsonResponse
    {
        $staff = $this->staffFromRequest($request);
        abort_unless($staff, 401);
        abort_unless($staff->is_approved && $this->casefileForStaffMother($staff, $mother), 403);

        $request->validate(['section' => ['sometimes', 'string', 'in:overview,monitoring,learning-documents,documents,notes']]);
        $section = $request->query('section', 'overview');
        $sectionTitle = ['overview' => 'Overview', 'monitoring' => 'Monitoring', 'learning-documents' => 'Learning & Documents', 'documents' => 'Documents', 'notes' => 'Notes'][$section];

        try {
            $mother->load([
                'casefileStaff',
                'maternalMonitoringRecords' => fn ($query) => $query->orderBy('recorded_at')->orderBy('created_at')->orderBy('id'),
                'maternalMonitoringRecords.recorder',
                'maternalMonitoringRecords.unusualConfirmer',
                'inayKaalamanProgress',
                'inayKaalamanUploads',
                'inayKaalamanCheckups' => fn ($query) => $query->orderBy('checkup_date')->orderBy('id'),
                'inayKaalamanCheckups.recordedByStaff',
                'inayKaalamanCheckups.verifiedByStaff',
            ]);

            $records = $mother->maternalMonitoringRecords;
            $latestRecord = $records->last();
            $learningSummary = $this->inayKaalamanProgressSummary(
                $mother,
                $mother->inayKaalamanUploads->groupBy('month'),
                $mother->inayKaalamanProgress,
            );
            $learning = $learningSummary['overall'];
            $careCompletion = $this->careCompletionPercentage($records, $learning);
            $generatedAt = now()->timezone(config('app.timezone'));
            $recordNumber = 'INAY-'.str_pad((string) $mother->id, 5, '0', STR_PAD_LEFT);
            $images = $this->careRecordImages();

            $html = view('records.mother-care', compact(
                'section', 'sectionTitle', 'learningSummary', 'careCompletion', 'mother', 'staff', 'records', 'latestRecord', 'learning', 'generatedAt', 'recordNumber', 'images',
            ))->render();
            $pdf = app(MotherCareRecordPdf::class)->render($html);

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => ($format === 'pdf' ? 'attachment' : 'inline').'; filename="'.$recordNumber.'-mother-'.$section.'.pdf"',
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'Unable to generate the mother record. Please try again.'], 500, [
                'Cache-Control' => 'private, no-store, max-age=0',
            ]);
        }
    }

    public function staffChildRecord(Request $request, Infant $infant, string $format = 'print'): Response|JsonResponse
    {
        $staff = $this->staffFromRequest($request);
        abort_unless($staff, 401);
        abort_unless($staff->is_approved && $infant->mother && $this->casefileForStaffMother($staff, $infant->mother), 403);

        try {
            $infant->load(['mother', 'growthRecords.recorder', 'vaccineRecords.recorder', 'healthAlerts']);
            $generatedAt = now()->timezone(config('app.timezone'));
            $recordNumber = 'INAY-CHILD-'.str_pad((string) $infant->id, 5, '0', STR_PAD_LEFT);
            $images = $this->careRecordImages();
            $assessment = $this->infantGrowthAssessment($infant);
            $html = view('records.child-care', compact('infant', 'staff', 'generatedAt', 'recordNumber', 'images', 'assessment'))->render();
            $pdf = app(MotherCareRecordPdf::class)->render($html);

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => ($format === 'pdf' ? 'attachment' : 'inline').'; filename="'.$recordNumber.'-child-record.pdf"',
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'Unable to generate the child record. Please try again.'], 500, [
                'Cache-Control' => 'private, no-store, max-age=0',
            ]);
        }
    }

    private function careRecordImages(): array
    {
        $letterheadPath = public_path('assets/images/mother-record/template-letterhead.jpg');
        $footerPath = public_path('assets/images/mother-record/template-footer.jpg');
        return [
            'letterhead' => 'data:image/jpeg;base64,'.base64_encode(file_get_contents($letterheadPath)),
            'footer' => 'data:image/jpeg;base64,'.base64_encode(file_get_contents($footerPath)),
        ];
    }

    public function previewStaffInayKaalamanUpload(Request $request, Mother $mother, InayKaalamanUpload $upload): StreamedResponse
    {
        $staff = $this->staffFromRequest($request);
        abort_unless($staff, 401);
        abort_unless($staff->is_approved && (int) $upload->mother_id === (int) $mother->id && $this->casefileForStaffMother($staff, $mother), 403);
        $disk = Storage::disk('public');
        abort_unless($disk->exists($upload->path), 404);
        $mime = $disk->mimeType($upload->path);
        abort_unless(in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'], true), 415);

        return $disk->response($upload->path, 'document-preview', [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }

    public function downloadStaffInayKaalamanUpload(Request $request, Mother $mother, InayKaalamanUpload $upload): StreamedResponse|RedirectResponse
    {
        $staff = $this->staffFromRequest($request);

        if (! $staff) {
            return redirect()->route('login')->with('status', 'Please login as Program Staff first.');
        }

        if ((int) $upload->mother_id !== (int) $mother->id || ! $this->casefileForStaffMother($staff, $mother)) {
            abort(403);
        }

        if (! Storage::disk('public')->exists($upload->path)) {
            abort(404);
        }

        $fileName = str_replace(['\\', '/', '"'], '', $upload->original_name ?: 'prenatal-record');

        return Storage::disk('public')->download($upload->path, $fileName);
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

        $validator = $this->maternalVitalsValidator($request, $mother);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Review the flagged values before saving this maternal vital record.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $validated = $this->applyMaternalVitalsDerivedValues($validated, $request, $mother);
        $screening = MaternalVitalScreening::screen($validated, $mother);

        if ($confirmation = $this->maternalVitalsConfirmationResponse($request, $screening)) {
            return $confirmation;
        }

        $record = MaternalMonitoringRecord::create($this->maternalVitalsAttributes($validated, $mother, $staff, $casefile, $screening));
        $this->auditMaternalVitals($record, 'created', null, $record->fresh()->toArray(), $staff);

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

        $validator = $this->maternalVitalsValidator($request, $mother);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Review the flagged values before saving this maternal vital record.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $validated = $this->applyMaternalVitalsDerivedValues($validated, $request, $mother);
        $screening = MaternalVitalScreening::screen($validated, $mother, $record->id);

        if ($confirmation = $this->maternalVitalsConfirmationResponse($request, $screening)) {
            return $confirmation;
        }

        $before = $record->fresh()->toArray();
        $record->update($this->maternalVitalsAttributes($validated, $mother, $staff, $casefile, $screening));
        $record->refresh();
        $this->auditMaternalVitals($record, 'updated', $before, $record->toArray(), $staff);

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

        $before = $record->fresh()->toArray();
        $this->auditMaternalVitals($record, 'deleted', $before, null, $staff);
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
        $selectedMotherId = (int) $request->query('mother', 0);
        $selectedMother = null;
        $selectedInfant = null;
        $childAccessDenied = false;
        $accessDeniedMessage = null;

        if ($selectedChildId > 0) {
            $selectedInfant = $children->firstWhere('id', $selectedChildId);
            $childAccessDenied = ! $selectedInfant;
            $selectedMother = $selectedInfant?->mother;
            $accessDeniedMessage = $childAccessDenied
                ? 'The selected child profile is not assigned to your casefiles.'
                : null;
        } elseif ($selectedMotherId > 0) {
            $selectedMother = $mothers->firstWhere('id', $selectedMotherId);
            $childAccessDenied = ! $selectedMother;
            $accessDeniedMessage = $childAccessDenied
                ? 'The selected mother profile is not assigned to your casefiles.'
                : null;

            if ($selectedMother) {
                $selectedInfant = $children
                    ->where('mother_id', $selectedMother->id)
                    ->sortBy(fn (Infant $infant): string => ($infant->birth_date?->format('Ymd') ?? '99999999').$infant->full_name)
                    ->first();
            }
        } else {
            $selectedMother = $mothers->first();

            if ($selectedMother) {
                $selectedInfant = $children
                    ->where('mother_id', $selectedMother->id)
                    ->sortBy(fn (Infant $infant): string => ($infant->birth_date?->format('Ymd') ?? '99999999').$infant->full_name)
                    ->first();
            }
        }

        $selectedMotherInfants = $selectedMother
            ? $children
                ->where('mother_id', $selectedMother->id)
                ->sortBy(fn (Infant $infant): string => ($infant->birth_date?->format('Ymd') ?? '99999999').$infant->full_name)
                ->values()
            : collect();

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

        return view('modules.staff-neonatal-vaccines', compact(
            'staff',
            'mothers',
            'children',
            'selectedMother',
            'selectedMotherInfants',
            'selectedInfant',
            'childAccessDenied',
            'accessDeniedMessage',
            'neonatalStats',
        ));
    }

    public function staffDynamicReports(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('auth_role') !== 'staff') {
            return redirect()->route('login')->with('status', 'Please login as Program Staff first.');
        }

        $staff = ProgramStaff::find($request->session()->get('auth_id'));

        if (! $staff) {
            $this->clearLoginSession($request);

            return redirect()->route('login')->with('status', 'Please login again.');
        }

        $activeTab = in_array($request->query('tab'), ['newborn', 'statistics'], true) ? $request->query('tab') : 'maternal';
        $maternalSearch = trim((string) $request->query('maternal_q', ''));
        $maternalRisk = strtolower((string) $request->query('risk', 'all'));
        $newbornSearch = trim((string) $request->query('newborn_q', ''));
        $today = Carbon::today();
        $assignedMotherIds = StaffMotherCasefile::where('staff_id', $staff->id)->pluck('mother_id');

        $mothers = Mother::query()
            ->whereIn('id', $assignedMotherIds)
            ->with([
                'maternalMonitoringRecords' => fn ($query) => $query
                    ->orderByDesc('recorded_at')
                    ->orderByDesc('created_at'),
                'infants.growthRecords.recorder',
                'infants.vaccineRecords.recorder',
                'infants.healthAlerts',
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $maternalRows = $mothers->map(function (Mother $mother): array {
            $latest = $mother->maternalMonitoringRecords->first();
            $screeningStatus = MaternalVitalScreening::normalizeStatus($latest?->screening_summary_status ?? $latest?->risk_level);
            $screeningKey = MaternalVitalScreening::statusKey($screeningStatus);

            $bloodPressure = $latest?->bp_systolic && $latest?->bp_diastolic
                ? $latest->bp_systolic.'/'.$latest->bp_diastolic.' mmHg'
                : 'No BP logged';
            $bloodSugar = $latest?->blood_sugar === null
                ? 'No data'
                : rtrim(rtrim(number_format((float) $latest->blood_sugar, 2), '0'), '.').' mg/dL';
            $bloodSugarTestType = $latest?->blood_sugar_test_type
                ? (MaternalVitalScreening::bloodSugarTestTypes()[$latest->blood_sugar_test_type] ?? 'Test type not recorded')
                : 'Test type not recorded';
            $temperature = $latest?->temperature === null
                ? 'No temp'
                : rtrim(rtrim(number_format((float) $latest->temperature, 1), '0'), '.').' C';
            $heartRate = $latest?->heart_rate === null ? 'No HR' : $latest->heart_rate.' bpm';

            return [
                'id' => $mother->id,
                'mother' => $mother,
                'code' => 'MAT-RH-'.str_pad((string) $mother->id, 3, '0', STR_PAD_LEFT),
                'name' => $mother->full_name,
                'age' => $mother->age,
                'barangay' => $mother->barangay ?: 'Not provided',
                'pregnancy_status' => $mother->pregnancy_status ?: 'pending',
                'pregnancy_week' => $latest?->pregnancy_week,
                'blood_pressure' => $bloodPressure,
                'blood_sugar' => $bloodSugar,
                'blood_sugar_test_type' => $bloodSugarTestType,
                'blood_sugar_value' => $latest?->blood_sugar === null ? null : (float) $latest->blood_sugar,
                'temperature' => $temperature,
                'heart_rate' => $heartRate,
                'risk_level' => $screeningKey,
                'risk_label' => $screeningStatus,
                'recorded_label' => $latest?->recorded_at?->format('M j, Y') ?? 'No monitoring record',
                'casefile_url' => route('staff.mothers.show', $mother),
                'search_text' => Str::lower(collect([
                    $mother->full_name,
                    $mother->email,
                    $mother->contact_number,
                    $mother->barangay,
                    $mother->age,
                    $screeningKey,
                    $screeningStatus,
                    $bloodPressure,
                    $bloodSugar,
                    $bloodSugarTestType,
                    $latest?->notes,
                ])->filter()->implode(' ')),
            ];
        });

        $maternalSearchTokens = collect(preg_split('/\s+/', Str::lower($maternalSearch), -1, PREG_SPLIT_NO_EMPTY) ?: []);
        $filteredMaternalRows = $maternalRows
            ->filter(function (array $row) use ($maternalRisk): bool {
                return ! in_array($maternalRisk, ['within_reference_range', 'for_review', 'for_professional_interpretation', 'urgent_referral_recommended', 'logged'], true)
                    || $row['risk_level'] === $maternalRisk;
            })
            ->filter(function (array $row) use ($maternalSearchTokens): bool {
                return $maternalSearchTokens->isEmpty()
                    || $maternalSearchTokens->every(fn (string $token): bool => str_contains($row['search_text'], $token));
            })
            ->values();

        $bloodSugarValues = $maternalRows
            ->pluck('blood_sugar_value')
            ->filter(fn ($value): bool => $value !== null);
        $maternalSummary = [
            'urgent_referral_recommended' => $maternalRows->where('risk_level', 'urgent_referral_recommended')->count(),
            'for_review' => $maternalRows->where('risk_level', 'for_review')->count(),
            'for_professional_interpretation' => $maternalRows->where('risk_level', 'for_professional_interpretation')->count(),
            'within_reference_range' => $maternalRows->where('risk_level', 'within_reference_range')->count(),
            'logged' => $maternalRows->where('risk_level', 'logged')->count(),
            'mean_blood_sugar' => $bloodSugarValues->isEmpty() ? null : round((float) $bloodSugarValues->avg(), 2),
            'total' => $maternalRows->count(),
        ];

        $newbornRows = $mothers
            ->flatMap(function (Mother $mother) use ($today) {
                return $mother->infants->map(function (Infant $infant) use ($mother, $today): array {
                    $latestGrowth = $infant->growthRecords->last();
                    $firstGrowth = $infant->growthRecords->first();
                    $growthAssessment = $this->infantGrowthAssessment($infant);
                    $completedVaccines = $infant->vaccineRecords->where('status', 'completed')->count();
                    $overdueVaccines = $infant->vaccineRecords->filter(function (InfantVaccineRecord $record) use ($today): bool {
                        return in_array($record->status, ['missed', 'overdue'], true)
                            || ($record->status !== 'completed' && $record->due_date && $record->due_date->isBefore($today));
                    })->count();
                    $activeAlerts = $infant->healthAlerts->where('status', 'active')->count();
                    $status = $activeAlerts > 0 || $overdueVaccines > 0 || $growthAssessment['severity'] !== 'normal'
                        ? 'at_risk'
                        : ($latestGrowth ? 'healthy' : 'watch');
                    $ageMonths = $latestGrowth?->age_months ?? ($infant->birth_date ? max(0, (int) $infant->birth_date->diffInMonths(now())) : null);

                    return [
                        'id' => $infant->id,
                        'infant' => $infant,
                        'mother' => $mother,
                        'code' => 'NBN-RH-'.str_pad((string) $infant->id, 3, '0', STR_PAD_LEFT),
                        'name' => $infant->full_name,
                        'sex' => ucfirst((string) $infant->sex),
                        'age_months' => $ageMonths,
                        'birth_date_label' => $infant->birth_date?->format('M j, Y') ?? 'Not recorded',
                        'birth_weight' => $infant->birth_weight,
                        'birth_height' => $infant->birth_height,
                        'head_circumference' => $firstGrowth?->head_circumference,
                        'latest_growth' => $latestGrowth,
                        'completed_vaccines' => $completedVaccines,
                        'overdue_vaccines' => $overdueVaccines,
                        'active_alerts' => $activeAlerts,
                        'status' => $status,
                        'status_label' => match ($status) {
                            'at_risk' => 'At Risk',
                            'watch' => 'Needs Baseline',
                            default => 'Healthy',
                        },
                        'growth_label' => $growthAssessment['label'],
                        'profile_url' => route('staff.neonatal', ['mother' => $mother->id, 'child' => $infant->id]),
                        'search_text' => Str::lower(collect([
                            $infant->full_name,
                            $mother->full_name,
                            $mother->barangay,
                            $infant->sex,
                            $status,
                            $growthAssessment['label'],
                            $completedVaccines.' completed vaccines',
                            $overdueVaccines.' overdue vaccines',
                            $infant->vaccineRecords->pluck('vaccine_name')->implode(' '),
                            $infant->vaccineRecords->pluck('status')->implode(' '),
                        ])->filter()->implode(' ')),
                    ];
                });
            })
            ->sortBy(fn (array $row): string => $row['mother']->last_name.$row['mother']->first_name.$row['name'])
            ->values();

        $newbornSearchTokens = collect(preg_split('/\s+/', Str::lower($newbornSearch), -1, PREG_SPLIT_NO_EMPTY) ?: []);
        $filteredNewbornRows = $newbornRows
            ->filter(function (array $row) use ($newbornSearchTokens): bool {
                return $newbornSearchTokens->isEmpty()
                    || $newbornSearchTokens->every(fn (string $token): bool => str_contains($row['search_text'], $token));
            })
            ->values();
        $selectedNewbornId = (int) $request->query('newborn', 0);
        $selectedNewborn = $newbornRows->firstWhere('id', $selectedNewbornId) ?? $filteredNewbornRows->first() ?? $newbornRows->first();
        $selectedGrowthLogs = $selectedNewborn ? $selectedNewborn['infant']->growthRecords->values() : collect();
        $selectedVaccineLogs = $selectedNewborn ? $selectedNewborn['infant']->vaccineRecords->values() : collect();
        $newbornSummary = [
            'total' => $newbornRows->count(),
            'at_risk' => $newbornRows->where('status', 'at_risk')->count(),
            'baseline_needed' => $newbornRows->where('status', 'watch')->count(),
            'completed_vaccines' => $newbornRows->sum('completed_vaccines'),
            'overdue_vaccines' => $newbornRows->sum('overdue_vaccines'),
        ];

        $statistics = $activeTab === 'statistics'
            ? \App\Support\StaffClinicalStatistics::build($mothers, $maternalRows, $newbornRows, $today)
            : null;

        return view('modules.staff-dynamic-reports', compact(
            'statistics',
            'staff',
            'activeTab',
            'maternalRows',
            'filteredMaternalRows',
            'maternalSummary',
            'maternalSearch',
            'maternalRisk',
            'newbornRows',
            'filteredNewbornRows',
            'newbornSummary',
            'newbornSearch',
            'selectedNewborn',
            'selectedGrowthLogs',
            'selectedVaccineLogs',
        ));
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
            'due_date' => ['sometimes', 'nullable', 'date'],
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
            'due_date' => array_key_exists('due_date', $validated) ? $validated['due_date'] : $vaccine->due_date,
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
        \App\Support\ChatPresence::recordPresence($role, $id);
    }

    private function clearLoginSession(Request $request): void
    {
        $role = (string) $request->session()->get('auth_role');
        $id = (int) $request->session()->get('auth_id');
        if ($role !== '' && $id > 0) {
            \App\Support\ChatPresence::recordOffline($role, $id);
        }

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

    private function notifyProgramStaffOfNewMother(Mother $mother): void
    {
        ProgramStaff::query()
            ->where('approval_status', 'approved')
            ->orderBy('id')
            ->each(function (ProgramStaff $staff) use ($mother): void {
                AppNotificationService::createForMotherOnce(
                    (int) $staff->id,
                    Message::ROLE_PROGRAM_STAFF,
                    $mother,
                    'mother_registered',
                    'New mother registered',
                    'A new mother, '.$mother->full_name.', has registered.',
                    [
                        'url' => route('staff.mothers.show', $mother),
                        'mother_id' => $mother->id,
                        'patient_number' => $this->patientNumberForMother($mother),
                    ],
                );
            });
    }

    private function patientIdsFromSearchToken(string $token): array
    {
        preg_match_all('/\d+/', $token, $matches);

        return collect($matches[0] ?? [])
            ->map(fn (string $number): int => (int) ltrim($number, '0'))
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function patientNumberForMother(Mother $mother): string
    {
        return 'INAY-'.str_pad((string) $mother->id, 5, '0', STR_PAD_LEFT);
    }

    private function casefileForStaffMother(ProgramStaff $staff, Mother $mother): ?StaffMotherCasefile
    {
        return StaffMotherCasefile::where('staff_id', $staff->id)
            ->where('mother_id', $mother->id)
            ->first();
    }

    private function maternalVitalsValidator(Request $request, ?Mother $mother = null)
    {
        $validator = Validator::make($request->all(), [
            'recorded_at' => ['required', 'date', 'before_or_equal:today'],
            'pregnancy_week' => ['required', 'integer', 'between:1,42'],
            'weight' => ['required', 'numeric', 'between:25,250'],
            'height_unit' => ['nullable', Rule::in(['cm', 'ft_in'])],
            'height_cm' => ['nullable', 'numeric', 'gt:0'],
            'height_feet' => ['nullable', 'integer', 'min:1'],
            'height_inches' => ['nullable', 'numeric', 'between:0,11.99'],
            'pre_pregnancy_weight' => ['nullable', 'numeric', 'between:25,250'],
            'bp_systolic' => ['required', 'integer', 'between:50,260'],
            'bp_diastolic' => ['required', 'integer', 'between:30,160'],
            'blood_sugar_test_type' => ['required', Rule::in(array_keys(MaternalVitalScreening::bloodSugarTestTypes()))],
            'blood_sugar' => ['required', 'numeric', 'between:20,700'],
            'temperature' => ['required', 'numeric', 'between:30,45'],
            'heart_rate' => ['required', 'integer', 'between:30,220'],
            'confirmed_unusual' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], [
            'recorded_at.required' => 'Record date is required.',
            'recorded_at.date' => 'Record date must be a valid date.',
            'recorded_at.before_or_equal' => 'Record date cannot be in the future.',
            'pregnancy_week.required' => 'Pregnancy week is required.',
            'pregnancy_week.between' => 'Pregnancy week must be between 1 and 42.',
            'weight.required' => 'Weight is required.',
            'weight.numeric' => 'Weight must be a valid number.',
            'weight.between' => 'Weight must be from 25 to 250 kg. Confirm the unit before saving.',
            'height_cm.numeric' => 'Height must be a valid number in centimeters.',
            'height_cm.gt' => 'Height must be greater than 0 cm.',
            'height_feet.integer' => 'Feet must be a positive whole number.',
            'height_feet.min' => 'Feet must be at least 1.',
            'height_inches.numeric' => 'Inches must be a valid number.',
            'height_inches.between' => 'Inches must be between 0 and 11.99.',
            'pre_pregnancy_weight.between' => 'Pre-pregnancy weight must be from 25 to 250 kg.',
            'bp_systolic.required' => 'Systolic blood pressure is required.',
            'bp_systolic.between' => 'Systolic BP must be from 50 to 260 mmHg.',
            'bp_diastolic.required' => 'Diastolic blood pressure is required.',
            'bp_diastolic.between' => 'Diastolic BP must be from 30 to 160 mmHg.',
            'blood_sugar_test_type.required' => 'Blood Sugar Test Type is required.',
            'blood_sugar_test_type.in' => 'Select a valid Blood Sugar Test Type.',
            'blood_sugar.required' => 'Blood sugar is required.',
            'blood_sugar.between' => 'Blood sugar must be from 20 to 700 mg/dL.',
            'temperature.required' => 'Body temperature is required.',
            'temperature.between' => 'Body temperature must be entered in Celsius from 30 to 45 C.',
            'heart_rate.required' => 'Heart rate is required.',
            'heart_rate.between' => 'Heart rate must be from 30 to 220 bpm.',
            'notes.max' => 'Program staff notes must be 5000 characters or fewer.',
        ]);

        $validator->after(function ($validator) use ($request, $mother) {
            $systolic = $request->input('bp_systolic');
            $diastolic = $request->input('bp_diastolic');

            if (is_numeric($systolic) && is_numeric($diastolic) && (int) $diastolic >= (int) $systolic) {
                $validator->errors()->add('bp_diastolic', 'Diastolic BP should be lower than systolic BP.');
            }

            $heightInputPresent = filled($request->input('height_cm'))
                || filled($request->input('height_feet'))
                || filled($request->input('height_inches'));
            $heightCm = $this->normalizedHeightFromRequest($request);

            if ($heightInputPresent && $heightCm === null) {
                $validator->errors()->add('height_cm', 'Enter a complete, valid height before saving.');
            }

            $prePregnancyWeight = $request->input('pre_pregnancy_weight');
            if (($prePregnancyWeight === null || $prePregnancyWeight === '') && $mother) {
                $prePregnancyWeight = $this->latestMaternalValue($mother, 'pre_pregnancy_weight');
            }

            $bmi = $this->calculatePrePregnancyBmi($heightCm, $prePregnancyWeight);
            if ($bmi !== null && ($bmi < 10 || $bmi > 70)) {
                $validator->errors()->add('pre_pregnancy_bmi', 'The calculated pre-pregnancy BMI must be from 10 to 70.');
            }
        });

        return $validator;
    }

    private function applyMaternalVitalsDerivedValues(array $validated, Request $request, Mother $mother): array
    {
        $heightCm = $this->normalizedHeightFromRequest($request)
            ?? $this->latestMaternalValue($mother, 'height_cm');
        $prePregnancyWeight = $validated['pre_pregnancy_weight'] ?? null;

        if ($prePregnancyWeight === null || $prePregnancyWeight === '') {
            $prePregnancyWeight = $this->latestMaternalValue($mother, 'pre_pregnancy_weight');
        }

        $validated['height_cm'] = $heightCm === null ? null : round((float) $heightCm, 2);
        $validated['pre_pregnancy_weight'] = $prePregnancyWeight === null ? null : (float) $prePregnancyWeight;
        $validated['pre_pregnancy_bmi'] = $this->calculatePrePregnancyBmi($heightCm, $prePregnancyWeight);

        return $validated;
    }

    private function normalizedHeightFromRequest(Request $request): ?float
    {
        $unit = $request->input('height_unit', 'cm');

        if ($unit === 'ft_in') {
            $feet = $request->input('height_feet');
            $inches = $request->input('height_inches');

            if (! is_numeric($feet) || filter_var($feet, FILTER_VALIDATE_INT) === false || (int) $feet < 1 || ! is_numeric($inches)) {
                return null;
            }

            $inches = (float) $inches;
            if ($inches < 0 || $inches > 11.99) {
                return null;
            }

            return round(((int) $feet * 30.48) + ($inches * 2.54), 2);
        }

        $heightCm = $request->input('height_cm');

        if ($heightCm === null || $heightCm === '' || ! is_numeric($heightCm) || (float) $heightCm <= 0) {
            return null;
        }

        return round((float) $heightCm, 2);
    }

    private function calculatePrePregnancyBmi(mixed $heightCm, mixed $prePregnancyWeight): ?float
    {
        if (! is_numeric($heightCm) || (float) $heightCm <= 0 || ! is_numeric($prePregnancyWeight) || (float) $prePregnancyWeight <= 0) {
            return null;
        }

        $heightMeters = (float) $heightCm / 100;
        if ($heightMeters <= 0) {
            return null;
        }

        return round((float) $prePregnancyWeight / ($heightMeters * $heightMeters), 2);
    }

    private function latestMaternalValue(Mother $mother, string $column): mixed
    {
        return MaternalMonitoringRecord::query()
            ->where('mother_id', $mother->id)
            ->whereNotNull($column)
            ->orderByDesc('recorded_at')
            ->orderByDesc('created_at')
            ->value($column);
    }

    private function maternalVitalsConfirmationResponse(Request $request, array $screening): ?JsonResponse
    {
        $warnings = $screening['confirmation_warnings'] ?? [];

        if ($warnings === [] || $request->boolean('confirmed_unusual')) {
            return null;
        }

        return response()->json([
            'message' => 'Confirm flagged or unusually high/low values before saving this maternal vital record.',
            'requires_confirmation' => true,
            'warnings' => $warnings,
        ], 409);
    }

    private function maternalVitalsAttributes(array $validated, Mother $mother, ProgramStaff $staff, StaffMotherCasefile $casefile, array $screening): array
    {
        $pregnancyWeek = (int) $validated['pregnancy_week'];
        $confirmed = ! empty($screening['confirmation_warnings']) && ! empty($validated['confirmed_unusual']);

        return [
            'mother_id' => $mother->id,
            'staff_mother_casefile_id' => $casefile->id,
            'recorded_by_staff_id' => $staff->id,
            'pregnancy_week' => $pregnancyWeek,
            'pregnancy_month' => min(10, (int) ceil($pregnancyWeek / 4)),
            'bp_systolic' => (int) $validated['bp_systolic'],
            'bp_diastolic' => (int) $validated['bp_diastolic'],
            'blood_sugar' => (float) $validated['blood_sugar'],
            'blood_sugar_test_type' => $validated['blood_sugar_test_type'],
            'weight' => (float) $validated['weight'],
            'height_cm' => $validated['height_cm'] ?? null,
            'pre_pregnancy_weight' => $validated['pre_pregnancy_weight'] ?? null,
            'pre_pregnancy_bmi' => $validated['pre_pregnancy_bmi'] ?? null,
            'weight_change_from_previous' => $screening['weight_change_from_previous'],
            'temperature' => (float) $validated['temperature'],
            'heart_rate' => (int) $validated['heart_rate'],
            'bp_status' => $screening['statuses']['blood_pressure'],
            'blood_sugar_status' => $screening['statuses']['blood_sugar'],
            'weight_status' => $screening['statuses']['weight'],
            'temperature_status' => $screening['statuses']['temperature'],
            'heart_rate_status' => $screening['statuses']['heart_rate'],
            'screening_summary_status' => $screening['summary_status'],
            'measurement_units' => $screening['units'],
            'screening_explanations' => $screening['explanations'],
            'screening_guidelines' => $screening['guidelines'],
            'confirmed_unusual_at' => $confirmed ? now() : null,
            'confirmed_unusual_by_staff_id' => $confirmed ? $staff->id : null,
            'risk_level' => $screening['summary_status'],
            'notes' => $validated['notes'] ?? null,
            'recorded_at' => Carbon::parse($validated['recorded_at'])->startOfDay(),
        ];
    }

    private function maternalVitalsPayload(Mother $mother): array
    {
        $records = MaternalMonitoringRecord::with(['recorder', 'mother'])
            ->where('mother_id', $mother->id)
            ->orderBy('recorded_at')
            ->orderBy('created_at')
            ->get();
        $latest = $records->last();
        $latestFormatted = $latest ? $this->formatMaternalVitalsRecord($latest) : null;
        $latestHeight = $records->reverse()->first(fn (MaternalMonitoringRecord $record): bool => $record->height_cm !== null);
        $latestPrePregnancyWeight = $records->reverse()->first(fn (MaternalMonitoringRecord $record): bool => $record->pre_pregnancy_weight !== null);

        return [
            'latest' => $latestFormatted,
            'defaults' => [
                'height_cm' => $latestHeight?->height_cm === null ? null : (float) $latestHeight->height_cm,
                'pre_pregnancy_weight' => $latestPrePregnancyWeight?->pre_pregnancy_weight === null ? null : (float) $latestPrePregnancyWeight->pre_pregnancy_weight,
            ],
            'records' => $records->map(fn (MaternalMonitoringRecord $record): array => $this->formatMaternalVitalsRecord($record))->values(),
            'weight_history' => $records
                ->filter(fn (MaternalMonitoringRecord $record): bool => $record->weight !== null)
                ->map(function (MaternalMonitoringRecord $record): array {
                    $formatted = $this->formatMaternalVitalsRecord($record);

                    return [
                        'id' => $record->id,
                        'recorded_at' => ($record->recorded_at ?? $record->created_at)?->toDateString(),
                        'recorded_label' => ($record->recorded_at ?? $record->created_at)?->format('M j, Y') ?? 'Date not recorded',
                        'pregnancy_week' => $record->pregnancy_week,
                        'label' => $record->pregnancy_week ? 'Wk '.$record->pregnancy_week : (($record->recorded_at ?? $record->created_at)?->format('M j') ?? 'Record'),
                        'weight' => (float) $record->weight,
                        'unit' => 'kg',
                        'status' => $formatted['statuses']['weight'],
                        'explanation' => $formatted['explanations']['weight'],
                        'weight_change_from_previous' => $formatted['weight_change_from_previous'],
                        'previous_weight' => $formatted['previous_weight'],
                        'tooltip' => (($record->recorded_at ?? $record->created_at)?->format('M j, Y') ?? 'Date not recorded').' - Week '.($record->pregnancy_week ?: 'N/A').' - '.rtrim(rtrim(number_format((float) $record->weight, 2), '0'), '.').' kg',
                    ];
                })
                ->values(),
            'blood_pressure_history' => $records
                ->filter(fn (MaternalMonitoringRecord $record): bool => $record->bp_systolic !== null && $record->bp_diastolic !== null)
                ->map(function (MaternalMonitoringRecord $record): array {
                    $formatted = $this->formatMaternalVitalsRecord($record);

                    return [
                        'id' => $record->id,
                        'recorded_at' => ($record->recorded_at ?? $record->created_at)?->toDateString(),
                        'recorded_label' => ($record->recorded_at ?? $record->created_at)?->format('M j, Y') ?? 'Date not recorded',
                        'pregnancy_week' => $record->pregnancy_week,
                        'label' => $record->pregnancy_week ? 'Wk '.$record->pregnancy_week : (($record->recorded_at ?? $record->created_at)?->format('M j') ?? 'Record'),
                        'systolic' => (int) $record->bp_systolic,
                        'diastolic' => (int) $record->bp_diastolic,
                        'unit' => 'mmHg',
                        'status' => $formatted['statuses']['blood_pressure'],
                        'raw_status' => $record->bp_status,
                        'explanation' => $formatted['explanations']['blood_pressure'],
                        'tooltip' => (($record->recorded_at ?? $record->created_at)?->format('M j, Y') ?? 'Date not recorded').' - Week '.($record->pregnancy_week ?: 'N/A').' - '.$record->bp_systolic.'/'.$record->bp_diastolic.' mmHg',
                    ];
                })
                ->values(),
            'risk_status' => $latestFormatted['screening_summary_status'] ?? MaternalVitalScreening::STATUS_LOGGED,
            'risk_label' => $latestFormatted['screening_summary_status'] ?? MaternalVitalScreening::STATUS_LOGGED,
            'blood_sugar_test_types' => MaternalVitalScreening::bloodSugarTestTypes(),
            'references' => MaternalVitalScreening::references(),
            'safety_notice' => 'Project INAY provides threshold-based screening alerts for monitoring purposes only. Results must be verified and interpreted by a qualified healthcare professional. The system does not provide a medical diagnosis.',
        ];
    }

    private function formatMaternalVitalsRecord(MaternalMonitoringRecord $record): array
    {
        $date = $record->recorded_at ?? $record->created_at;
        $screening = $this->screeningForRecord($record);
        $bloodSugarTestType = $record->blood_sugar_test_type;
        $bloodSugarTestTypes = MaternalVitalScreening::bloodSugarTestTypes();
        $bloodSugarTestTypeLabel = $bloodSugarTestType && isset($bloodSugarTestTypes[$bloodSugarTestType])
            ? $bloodSugarTestTypes[$bloodSugarTestType]
            : 'Test type not recorded';

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
            'blood_sugar_test_type' => $bloodSugarTestType,
            'blood_sugar_test_type_label' => $bloodSugarTestTypeLabel,
            'weight' => $record->weight === null ? null : (float) $record->weight,
            'height_cm' => $record->height_cm === null ? null : (float) $record->height_cm,
            'pre_pregnancy_weight' => $record->pre_pregnancy_weight === null ? null : (float) $record->pre_pregnancy_weight,
            'pre_pregnancy_bmi' => $record->pre_pregnancy_bmi === null ? null : (float) $record->pre_pregnancy_bmi,
            'weight_change_from_previous' => $screening['weight_change_from_previous'],
            'previous_weight' => $screening['previous_weight'],
            'temperature' => $record->temperature === null ? null : (float) $record->temperature,
            'heart_rate' => $record->heart_rate,
            'statuses' => $screening['statuses'],
            'status_slugs' => collect($screening['statuses'])
                ->map(fn (?string $status): string => MaternalVitalScreening::statusSlug($status))
                ->all(),
            'screening_summary_status' => $screening['summary_status'],
            'screening_summary_slug' => MaternalVitalScreening::statusSlug($screening['summary_status']),
            'risk_level' => $screening['summary_status'],
            'risk_label' => $screening['summary_status'],
            'explanations' => $screening['explanations'],
            'guidelines' => $screening['guidelines'],
            'units' => $screening['units'],
            'confirmed_unusual_at' => $record->confirmed_unusual_at?->toDateTimeString(),
            'confirmed_unusual_by_staff_id' => $record->confirmed_unusual_by_staff_id,
            'notes' => $record->notes,
        ];
    }

    private function maternalRiskLabel(?string $riskLevel): string
    {
        return MaternalVitalScreening::normalizeStatus($riskLevel);
    }

    private function screeningForRecord(MaternalMonitoringRecord $record): array
    {
        $storedStatuses = [
            'blood_pressure' => $record->bp_status,
            'blood_sugar' => $record->blood_sugar_status,
            'weight' => $record->weight_status,
            'temperature' => $record->temperature_status,
            'heart_rate' => $record->heart_rate_status,
        ];

        $hasStoredScreening = collect($storedStatuses)->every(fn ($status): bool => filled($status))
            && is_array($record->screening_explanations)
            && is_array($record->screening_guidelines);

        if ($hasStoredScreening) {
            $statuses = collect($storedStatuses)
                ->map(fn (?string $status): string => MaternalVitalScreening::normalizeStatus($status))
                ->all();
            $screeningKeys = array_flip(array_keys($statuses));
            $unitKeys = array_flip([
                'bp_systolic',
                'bp_diastolic',
                'blood_sugar',
                'weight',
                'height_cm',
                'pre_pregnancy_weight',
                'pre_pregnancy_bmi',
                'temperature',
                'heart_rate',
            ]);
            $units = $record->measurement_units ?: [
                'bp_systolic' => 'mmHg',
                'bp_diastolic' => 'mmHg',
                'blood_sugar' => 'mg/dL',
                'weight' => 'kg',
                'height_cm' => 'cm',
                'pre_pregnancy_weight' => 'kg',
                'pre_pregnancy_bmi' => 'kg/m2',
                'temperature' => 'C',
                'heart_rate' => 'bpm',
            ];

            return [
                'statuses' => $statuses,
                'summary_status' => MaternalVitalScreening::summaryStatus($statuses),
                'explanations' => array_intersect_key($record->screening_explanations, $screeningKeys),
                'guidelines' => array_intersect_key($record->screening_guidelines, $screeningKeys),
                'units' => array_intersect_key($units, $unitKeys),
                'weight_change_from_previous' => $record->weight_change_from_previous === null ? null : (float) $record->weight_change_from_previous,
                'previous_weight' => null,
                'confirmation_warnings' => [],
            ];
        }

        $mother = $record->mother ?: Mother::find($record->mother_id);

        if (! $mother) {
            $statuses = collect($storedStatuses)
                ->map(fn (?string $status): string => MaternalVitalScreening::normalizeStatus($status))
                ->all();

            return [
                'statuses' => $statuses,
                'summary_status' => MaternalVitalScreening::summaryStatus($statuses),
                'explanations' => [],
                'guidelines' => [],
                'units' => [],
                'weight_change_from_previous' => $record->weight_change_from_previous === null ? null : (float) $record->weight_change_from_previous,
                'previous_weight' => null,
                'confirmation_warnings' => [],
            ];
        }

        return MaternalVitalScreening::screen([
            'recorded_at' => ($record->recorded_at ?? $record->created_at ?? now())->toDateString(),
            'pregnancy_week' => $record->pregnancy_week ?: 1,
            'weight' => $record->weight,
            'pre_pregnancy_weight' => $record->pre_pregnancy_weight,
            'pre_pregnancy_bmi' => $record->pre_pregnancy_bmi,
            'bp_systolic' => $record->bp_systolic,
            'bp_diastolic' => $record->bp_diastolic,
            'blood_sugar_test_type' => $record->blood_sugar_test_type ?: '',
            'blood_sugar' => $record->blood_sugar,
            'temperature' => $record->temperature,
            'heart_rate' => $record->heart_rate,
        ], $mother, $record->id);
    }

    private function auditMaternalVitals(MaternalMonitoringRecord $record, string $action, ?array $before, ?array $after, ProgramStaff $staff): void
    {
        MaternalMonitoringRecordAudit::create([
            'maternal_monitoring_record_id' => $record->id,
            'mother_id' => $record->mother_id,
            'staff_id' => $staff->id,
            'action' => $action,
            'before_values' => $before,
            'after_values' => $after,
            'created_at' => now(),
        ]);
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
            'dswd_staff' => redirect()->route('dswd.dashboard'),
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
        $firstTrimesterSupplementalVideos = [
            1 => [
                ['key' => 'month-1-first-trimester-video-2', 'title' => 'Conception and New Beginnings Video 2', 'tag' => 'First Trimester', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/e4UPKPv7v38?si=Mwv0LmxVKzyTD9ev', 'youtube_id' => 'e4UPKPv7v38'],
            ],
            2 => [
                ['key' => 'month-2-first-trimester-video-1', 'title' => 'The Tiny Heart Beats Video 1', 'tag' => 'Month 2', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/_Ux1wPgEpJo?si=HMiCAkDgUNwr_Pi-', 'youtube_id' => '_Ux1wPgEpJo'],
                ['key' => 'month-2-first-trimester-video-2', 'title' => 'The Tiny Heart Beats Video 2', 'tag' => 'Month 2', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/jgnciIgOFmg?si=jNVfw8xB4aDof5QG', 'youtube_id' => 'jgnciIgOFmg'],
                ['key' => 'month-2-first-trimester-video-3', 'title' => 'The Tiny Heart Beats Video 3', 'tag' => 'Month 2', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/GiRnUn0ApKE?si=yuajuH0q3jbcNuEa', 'youtube_id' => 'GiRnUn0ApKE'],
                ['key' => 'month-2-first-trimester-video-4', 'title' => 'The Tiny Heart Beats Video 4', 'tag' => 'Month 2', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/gAkxR_Ept1k?si=BtSQwzVTsSQq25Rv', 'youtube_id' => 'gAkxR_Ept1k'],
            ],
            3 => [
                ['key' => 'month-3-first-trimester-video-1', 'title' => 'First Trimester Milestones Video 1', 'tag' => 'Month 3', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/yRoHw6rnntc?si=GCGN-bD8Yyg7vzAV', 'youtube_id' => 'yRoHw6rnntc'],
                ['key' => 'month-3-first-trimester-video-2', 'title' => 'First Trimester Milestones Video 2', 'tag' => 'Month 3', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/ciG1YICJrDA?si=bbbqHktM2TMMheCW', 'youtube_id' => 'ciG1YICJrDA'],
                ['key' => 'month-3-first-trimester-video-3', 'title' => 'First Trimester Milestones Video 3', 'tag' => 'Month 3', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/vtg1w7TUJ3w?si=iT69DCppncZIAWvD', 'youtube_id' => 'vtg1w7TUJ3w'],
                ['key' => 'month-3-first-trimester-video-4', 'title' => 'First Trimester Milestones Video 4', 'tag' => 'Month 3', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/_QB0qkJ4zRk?si=DKsL1i8tlr4Bq4eo', 'youtube_id' => '_QB0qkJ4zRk'],
            ],
        ];
        $secondTrimesterSupplementalVideos = [
            4 => [
                ['key' => 'month-4-second-trimester-video-1', 'title' => 'Growing and Developing Video 1', 'tag' => 'Month 4', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/IPj4dJnP85o?si=XmiOYwKz8gCOCL6u', 'youtube_id' => 'IPj4dJnP85o'],
                ['key' => 'month-4-second-trimester-video-2', 'title' => 'Growing and Developing Video 2', 'tag' => 'Month 4', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/B9xiKEWc9SM?si=Zs0G3N_otwXbvvoX', 'youtube_id' => 'B9xiKEWc9SM'],
                ['key' => 'month-4-second-trimester-video-3', 'title' => 'Growing and Developing Video 3', 'tag' => 'Month 4', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/hmWtKtbIolE?si=KpdmXsXsGywd_dge', 'youtube_id' => 'hmWtKtbIolE'],
                ['key' => 'month-4-second-trimester-video-4', 'title' => 'Growing and Developing Video 4', 'tag' => 'Month 4', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/shyWWLkA61I?si=WDZovJ13tTqkzeOj', 'youtube_id' => 'shyWWLkA61I'],
                ['key' => 'month-4-second-trimester-video-5', 'title' => 'Growing and Developing Video 5', 'tag' => 'Month 4', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/HTIV2AdFTnc?si=lxxxbyKlLLetwx41', 'youtube_id' => 'HTIV2AdFTnc'],
            ],
            5 => [
                ['key' => 'month-5-second-trimester-video-1', 'title' => 'Feeling the Baby Move Video 1', 'tag' => 'Month 5', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/tycuzmo-s34?si=41LH9sIVm8f-yK6I', 'youtube_id' => 'tycuzmo-s34'],
                ['key' => 'month-5-second-trimester-video-2', 'title' => 'Feeling the Baby Move Video 2', 'tag' => 'Month 5', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/wM7I0krDPTg?si=pNKqK_hMb0JSxkuj', 'youtube_id' => 'wM7I0krDPTg'],
                ['key' => 'month-5-second-trimester-video-3', 'title' => 'Feeling the Baby Move Video 3', 'tag' => 'Month 5', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/L-9NcufiDOo?si=iEwITUCZ9F-V6w-7', 'youtube_id' => 'L-9NcufiDOo'],
                ['key' => 'month-5-second-trimester-video-4', 'title' => 'Feeling the Baby Move Video 4', 'tag' => 'Month 5', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/MLZ0lbkKbgM?si=RVcjGCR5e5ZD_UeP', 'youtube_id' => 'MLZ0lbkKbgM'],
            ],
            6 => [
                ['key' => 'month-6-second-trimester-video-1', 'title' => 'Continued Growth Video 1', 'tag' => 'Month 6', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/umgAThOkJgw?si=BJDaKv9iLRrAmK1y', 'youtube_id' => 'umgAThOkJgw'],
                ['key' => 'month-6-second-trimester-video-2', 'title' => 'Continued Growth Video 2', 'tag' => 'Month 6', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/QctcxLDsWB0?si=60H3b3Mi3NUdviUs', 'youtube_id' => 'QctcxLDsWB0'],
                ['key' => 'month-6-second-trimester-video-3', 'title' => 'Continued Growth Video 3', 'tag' => 'Month 6', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/OmivW51zGvs?si=VirObbd4cmquFtOF', 'youtube_id' => 'OmivW51zGvs'],
                ['key' => 'month-6-second-trimester-video-4', 'title' => 'Continued Growth Video 4', 'tag' => 'Month 6', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/VYYgOi9AkHU?si=d717SRziL9sOu0bF', 'youtube_id' => 'VYYgOi9AkHU'],
                ['key' => 'month-6-second-trimester-video-5', 'title' => 'Continued Growth Video 5', 'tag' => 'Month 6', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/orLWetHISck?si=My5x5lx2RjeI9CHb', 'youtube_id' => 'orLWetHISck'],
                ['key' => 'month-6-second-trimester-video-6', 'title' => 'Continued Growth Video 6', 'tag' => 'Month 6', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/k71_-M5q_H0?si=8mKHcTgdZM2Gwr6H', 'youtube_id' => 'k71_-M5q_H0'],
                ['key' => 'month-6-second-trimester-video-7', 'title' => 'Continued Growth Video 7', 'tag' => 'Month 6', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/14kbze3sSaY?si=YoQK4OI7bcdhBhVB', 'youtube_id' => '14kbze3sSaY'],
            ],
        ];
        $thirdTrimesterSupplementalVideos = [
            7 => [
                ['key' => 'month-7-third-trimester-video-1', 'title' => 'Preparing for Birth Video 1', 'tag' => 'Month 7', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/lpDW00nQhUo?si=OyLqgLRQNw11Qo1f', 'youtube_id' => 'lpDW00nQhUo'],
                ['key' => 'month-7-third-trimester-video-2', 'title' => 'Preparing for Birth Video 2', 'tag' => 'Month 7', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/OzY_2-0NvVM?si=T49XHXyAnL_VnOQy', 'youtube_id' => 'OzY_2-0NvVM'],
                ['key' => 'month-7-third-trimester-video-3', 'title' => 'Preparing for Birth Video 3', 'tag' => 'Month 7', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/GkcPIcxGy9g?si=5pqkAB5pVReRV2S_', 'youtube_id' => 'GkcPIcxGy9g'],
                ['key' => 'month-7-third-trimester-video-4', 'title' => 'Preparing for Birth Video 4', 'tag' => 'Month 7', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/AyEm6K295iE?si=f_Q1M0bvE8hLeOMW', 'youtube_id' => 'AyEm6K295iE'],
                ['key' => 'month-7-third-trimester-video-5', 'title' => 'Preparing for Birth Video 5', 'tag' => 'Month 7', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/_pE5PqZgleI?si=UEdfFgHaUKL5z3yk', 'youtube_id' => '_pE5PqZgleI'],
            ],
            8 => [
                ['key' => 'month-8-third-trimester-video-1', 'title' => 'Birth Readiness Video 1', 'tag' => 'Month 8', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/nFnlovPk1ro?si=rept0jT9pnTTr__W', 'youtube_id' => 'nFnlovPk1ro'],
                ['key' => 'month-8-third-trimester-video-2', 'title' => 'Birth Readiness Video 2', 'tag' => 'Month 8', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/wITeiIVieao?si=lsEjtM65scvoE50E', 'youtube_id' => 'wITeiIVieao'],
                ['key' => 'month-8-third-trimester-video-3', 'title' => 'Birth Readiness Video 3', 'tag' => 'Month 8', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/9HzZeSX6b2o?si=UGlOFClSzqmSf3ru', 'youtube_id' => '9HzZeSX6b2o'],
                ['key' => 'month-8-third-trimester-video-4', 'title' => 'Birth Readiness Video 4', 'tag' => 'Month 8', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/OtG-M8pHODY?si=3VKOdwrooDyKi_XD', 'youtube_id' => 'OtG-M8pHODY'],
            ],
            9 => [
                ['key' => 'month-9-third-trimester-video-1', 'title' => 'Final Preparation Video 1', 'tag' => 'Month 9', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/eFWuydRmFQg?si=_-X7dzUI4UtsZFxo', 'youtube_id' => 'eFWuydRmFQg'],
                ['key' => 'month-9-third-trimester-video-2', 'title' => 'Final Preparation Video 2', 'tag' => 'Month 9', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/_oB6wbpWCSo?si=s738kCP-nAzhij2Q', 'youtube_id' => '_oB6wbpWCSo'],
                ['key' => 'month-9-third-trimester-video-3', 'title' => 'Final Preparation Video 3', 'tag' => 'Month 9', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/c701W2pzuyo?si=Oys7aDXLnKqa5sXz', 'youtube_id' => 'c701W2pzuyo'],
                ['key' => 'month-9-third-trimester-video-4', 'title' => 'Final Preparation Video 4', 'tag' => 'Month 9', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/ww8s7PQWWuY?si=FQ6Aat8X4u362zhD', 'youtube_id' => 'ww8s7PQWWuY'],
                ['key' => 'month-9-third-trimester-video-5', 'title' => 'Final Preparation Video 5', 'tag' => 'Month 9', 'time' => 'Supplemental video', 'url' => 'https://youtu.be/yO6GYS3PnFY?si=x6r0pY6y-TFar1lI', 'youtube_id' => 'yO6GYS3PnFY'],
            ],
        ];

        $months = [
            1 => ['Conception and New Beginnings', 'Weeks 1-4', array_merge([[
                'key' => 'month-1-required-trimester-video',
                'title' => 'First Trimester Prenatal Care Guide',
                'tag' => 'First Trimester',
                'time' => 'Required video',
                'url' => 'https://youtu.be/D_jxGJsEY2A?si=tZLKKH_KnEHkOY2C',
                'youtube_id' => 'D_jxGJsEY2A',
                'required' => true,
            ]], $firstTrimesterSupplementalVideos[1])],
            2 => ['The Tiny Heart Beats', 'Weeks 5-8', $firstTrimesterSupplementalVideos[2]],
            3 => ['First Trimester Milestones', 'Weeks 9-12', $firstTrimesterSupplementalVideos[3]],
            4 => ['Growing and Developing', 'Weeks 13-16', array_merge([[
                'key' => 'month-4-required-trimester-video',
                'title' => 'Second Trimester Prenatal Care Guide',
                'tag' => 'Second Trimester',
                'time' => 'Required video',
                'url' => 'https://youtu.be/H6mZRds0dHo?si=jXxF1SdF5h_emTNW',
                'youtube_id' => 'H6mZRds0dHo',
                'required' => true,
            ]], $secondTrimesterSupplementalVideos[4])],
            5 => ['Feeling the Baby Move', 'Weeks 17-20', $secondTrimesterSupplementalVideos[5]],
            6 => ['Continued Growth', 'Weeks 21-27', $secondTrimesterSupplementalVideos[6]],
            7 => ['Preparing for Birth', 'Weeks 28-31', array_merge([[
                'key' => 'month-7-required-trimester-video',
                'title' => 'Third Trimester Birth Readiness Guide',
                'tag' => 'Third Trimester',
                'time' => 'Required video',
                'url' => 'https://youtu.be/f2dcTHQXwTI?si=YXvFxXuYWXbENi2C',
                'youtube_id' => 'f2dcTHQXwTI',
                'required' => true,
            ]], $thirdTrimesterSupplementalVideos[7])],
            8 => ['Birth Readiness', 'Weeks 32-35', $thirdTrimesterSupplementalVideos[8]],
            9 => ['Final Preparation', 'Weeks 36-40', $thirdTrimesterSupplementalVideos[9]],
            10 => ['Safe Delivery And Newborn Care', 'Birth Process', []],
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
        $title = $video['title'] ?? $video[0];
        $tag = $video['tag'] ?? $video[1];
        $time = $video['time'] ?? $video[2];
        $query = urlencode($title.' pregnancy education');

        return [
            'key' => $video['key'] ?? null,
            'title' => $title,
            'tag' => $tag,
            'time' => $time,
            'url' => $video['url'] ?? "https://www.youtube.com/results?search_query={$query}",
            'youtube_id' => $video['youtube_id'] ?? null,
            'required' => (bool) ($video['required'] ?? false),
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
                $itemKey = $video['key'] ?: "month-{$month}-video-{$index}";
                $record = $monthProgress->first(fn ($progress) => $progress->activity_type === 'video' && $progress->item_key === $itemKey);
                $status = $this->kaalamanActivityStatus($record, 'video');

                return [
                    'key' => $itemKey,
                    'title' => $video['title'],
                    'tag' => $video['tag'],
                    'time' => $video['time'],
                    'url' => $video['url'],
                    'youtube_id' => $video['youtube_id'],
                    'required' => (bool) $video['required'],
                    'status' => $status['status'],
                    'label' => $status['label'],
                    'completed_at' => $status['completed_at'],
                ];
            })->all();

            $requiredVideos = collect($videos);
            $watchedVideos = $requiredVideos->where('status', 'watched')->count();
            $totalRequiredVideos = $requiredVideos->count();
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

            $requiredCount = 1 + $totalRequiredVideos + 1;
            $completedCount = ($readingComplete ? 1 : 0) + $watchedVideos + ($infographicComplete ? 1 : 0);
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
                'total_videos' => $totalRequiredVideos,
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
