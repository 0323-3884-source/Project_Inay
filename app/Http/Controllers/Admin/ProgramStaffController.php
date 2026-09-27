<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ProgramStaffApprovalStatusMail;
use App\Models\ProgramStaff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class ProgramStaffController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $verification = (string) $request->query('verification', 'all');
        $approval = (string) $request->query('approval', 'all');
        $role = trim((string) $request->query('role', ''));

        $staffQuery = ProgramStaff::query()->latest();

        if ($search !== '') {
            $tokens = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            foreach ($tokens as $token) {
                $like = '%'.$token.'%';
                $staffQuery->where(function ($query) use ($like): void {
                    $query->where('first_name', 'like', $like)
                        ->orWhere('middle_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('staff_id', 'like', $like)
                        ->orWhere('contact_number', 'like', $like)
                        ->orWhere('role', 'like', $like)
                        ->orWhere('position', 'like', $like);
                });
            }
        }

        if ($role !== '') {
            $staffQuery->where(function ($query) use ($role): void {
                $query->where('role', $role)->orWhere(function ($fallbackQuery) use ($role): void {
                    $fallbackQuery->whereNull('role')->where('position', $role);
                });
            });
        }

        match ($verification) {
            'verified' => $staffQuery->whereNotNull('healthcare_worker_id_verified_at'),
            'pending' => $staffQuery->whereNotNull('healthcare_worker_id_photo_path')->whereNull('healthcare_worker_id_verified_at'),
            'missing' => $staffQuery->whereNull('healthcare_worker_id_photo_path'),
            default => null,
        };

        match ($approval) {
            'pending' => $staffQuery->where('approval_status', 'pending'),
            'approved' => $staffQuery->where('approval_status', 'approved'),
            'rejected' => $staffQuery->where('approval_status', 'rejected'),
            default => null,
        };

        $staffMembers = $staffQuery
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => ProgramStaff::count(),
            'account_pending' => ProgramStaff::where('approval_status', 'pending')->count(),
            'account_approved' => ProgramStaff::where('approval_status', 'approved')->count(),
            'account_rejected' => ProgramStaff::where('approval_status', 'rejected')->count(),
            'verified' => ProgramStaff::whereNotNull('healthcare_worker_id_verified_at')->count(),
            'pending' => ProgramStaff::whereNotNull('healthcare_worker_id_photo_path')
                ->whereNull('healthcare_worker_id_verified_at')
                ->count(),
            'missing' => ProgramStaff::whereNull('healthcare_worker_id_photo_path')->count(),
        ];

        return view('admin.program-staff.index', [
            'adminUsername' => session('admin_username', 'admin'),
            'staffMembers' => $staffMembers,
            'roleOptions' => $this->roleOptions(),
            'search' => $search,
            'verification' => $verification,
            'approval' => $approval,
            'selectedRole' => $role,
            'stats' => $stats,
        ]);
    }

    public function show(ProgramStaff $programStaff): View
    {
        return view('admin.program-staff.show', [
            'adminUsername' => session('admin_username', 'admin'),
            'staff' => $programStaff->load('verifiedByAdmin', 'approvedByAdmin'),
            'roleOptions' => $this->roleOptions(),
        ]);
    }

    public function update(Request $request, ProgramStaff $programStaff): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('program_staff', 'email')->ignore($programStaff->id)],
            'staff_id' => ['required', 'string', 'max:255', Rule::unique('program_staff', 'staff_id')->ignore($programStaff->id)],
            'role' => ['required', Rule::in(ProgramStaff::ROLE_OPTIONS)],
            'contact_number' => ['required', 'string', 'max:30'],
            'healthcare_worker_id_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $updates = [
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'staff_id' => $validated['staff_id'],
            'position' => $validated['role'],
            'role' => $validated['role'],
            'contact_number' => $validated['contact_number'],
        ];

        if ($request->hasFile('healthcare_worker_id_photo')) {
            $oldPhotoPath = $programStaff->healthcareWorkerIdPhotoStoragePath();
            $updates['healthcare_worker_id_photo_path'] = $request->file('healthcare_worker_id_photo')
                ->store('healthcare-worker-ids', 'public');
            $updates['healthcare_worker_id_verified_at'] = null;
            $updates['healthcare_worker_id_verified_by_admin_id'] = null;

            if ($oldPhotoPath) {
                Storage::disk('public')->delete($oldPhotoPath);
            }
        }

        $programStaff->update($updates);

        return redirect()
            ->route('admin.program-staff.show', $programStaff)
            ->with('status', 'Program Staff profile updated.');
    }

    public function destroy(ProgramStaff $programStaff): RedirectResponse
    {
        $photoPath = $programStaff->healthcareWorkerIdPhotoStoragePath();

        $programStaff->delete();

        if ($photoPath) {
            Storage::disk('public')->delete($photoPath);
        }

        return redirect()
            ->route('admin.program-staff.index')
            ->with('status', 'Program Staff account deleted.');
    }

    public function verify(Request $request, ProgramStaff $programStaff): RedirectResponse
    {
        if (! $programStaff->has_healthcare_worker_id_photo) {
            return back()->withErrors([
                'healthcare_worker_id_photo' => 'Upload an available healthcare worker ID image before verification.',
            ]);
        }

        $programStaff->forceFill([
            'healthcare_worker_id_verified_at' => now(),
            'healthcare_worker_id_verified_by_admin_id' => $request->session()->get('admin_id'),
        ])->save();

        return back()->with('status', 'Healthcare Worker ID verified.');
    }

    public function unverify(ProgramStaff $programStaff): RedirectResponse
    {
        $programStaff->forceFill([
            'healthcare_worker_id_verified_at' => null,
            'healthcare_worker_id_verified_by_admin_id' => null,
        ])->save();

        return back()->with('status', 'Healthcare Worker ID verification cleared.');
    }

    public function approve(Request $request, ProgramStaff $programStaff): RedirectResponse
    {
        $programStaff->forceFill([
            'approval_status' => 'approved',
            'approved_at' => now(),
            'approved_by_admin_id' => $request->session()->get('admin_id'),
            'rejected_at' => null,
            'rejection_reason' => null,
        ])->save();

        $programStaff->refresh();
        $mailSent = $this->sendApprovalStatusEmail($programStaff, 'approved');

        $response = back()->with('status', $mailSent
            ? 'Program Staff account approved and confirmation email sent.'
            : 'Program Staff account approved.');

        if (! $mailSent) {
            $response->with('warning', 'The confirmation email could not be sent. Please check the mail settings, then resend the approval email.');
        }

        return $response;
    }

    public function reject(Request $request, ProgramStaff $programStaff): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $reason = trim((string) ($validated['rejection_reason'] ?? ''));

        $programStaff->forceFill([
            'approval_status' => 'rejected',
            'approved_at' => null,
            'approved_by_admin_id' => null,
            'rejected_at' => now(),
            'rejection_reason' => $reason !== '' ? $reason : null,
        ])->save();

        $programStaff->refresh();
        $mailSent = $this->sendApprovalStatusEmail($programStaff, 'rejected', $reason !== '' ? $reason : null);

        $response = back()->with('status', $mailSent
            ? 'Program Staff account rejected and notification email sent.'
            : 'Program Staff account rejected.');

        if (! $mailSent) {
            $response->with('warning', 'The rejection email could not be sent. Please check the mail settings before notifying the staff member again.');
        }

        return $response;
    }

    private function sendApprovalStatusEmail(ProgramStaff $programStaff, string $status, ?string $reason = null): bool
    {
        try {
            Mail::to($programStaff->email)->send(
                new ProgramStaffApprovalStatusMail($programStaff, $status, $reason)
            );

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    private function roleOptions(): array
    {
        return ProgramStaff::ROLE_OPTIONS;
    }
}
