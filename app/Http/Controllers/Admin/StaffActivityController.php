<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AdminUser, DswdStaff, Mother, ProgramStaff};
use App\Support\StaffActivity;
use Illuminate\Http\Request;

class StaffActivityController extends Controller
{
    public function index(Request $request)
    {
        $admin = $request->attributes->get('department_admin');
        abort_unless($admin instanceof AdminUser && $admin->canAuthenticate(), 403);
        $cho = in_array($admin->role, ['admin', 'admin_cho'], true);
        $dswd = in_array($admin->role, ['admin', 'admin_dswd'], true);
        if ($request->expectsJson() && ($request->has('program_ids') || $request->has('dswd_ids') || $request->has('mother_ids'))) {
            $ids = $request->validate([
                'program_ids' => ['nullable', 'array', 'max:30'], 'program_ids.*' => ['integer', 'min:1'],
                'dswd_ids' => ['nullable', 'array', 'max:30'], 'dswd_ids.*' => ['integer', 'min:1'],
                'mother_ids' => ['nullable', 'array', 'max:30'], 'mother_ids.*' => ['integer', 'min:1'],
            ]);
            return response()->json([
                'program_staff' => $cho ? ProgramStaff::whereIn('id', $ids['program_ids'] ?? [])->get()->map(fn ($staff) => StaffActivity::summary($staff))->values() : null,
                'dswd_staff' => $dswd ? DswdStaff::whereIn('id', $ids['dswd_ids'] ?? [])->get()->map(fn ($staff) => StaffActivity::summary($staff))->values() : null,
                'mother' => $cho ? Mother::whereIn('id', $ids['mother_ids'] ?? [])->get()->map(fn ($mother) => StaffActivity::summary($mother))->values() : null,
            ])->header('Cache-Control', 'private, no-store');
        }
        $programStaff = $cho ? ProgramStaff::orderBy('last_name')->paginate(15, ['*'], 'cho_page')->withQueryString() : null;
        $dswdStaff = $dswd ? DswdStaff::orderBy('name')->paginate(15, ['*'], 'dswd_page')->withQueryString() : null;
        $searchData = $request->validate(['mother_q' => ['nullable', 'string', 'max:100']]);
        $motherSearch = trim((string) ($searchData['mother_q'] ?? ''));
        $mothers = $cho ? Mother::query()->when($motherSearch !== '', function ($query) use ($motherSearch) {
            foreach (preg_split('/\s+/', $motherSearch, -1, PREG_SPLIT_NO_EMPTY) as $token) {
                $query->where(fn ($match) => $match->where('first_name', 'like', '%'.$token.'%')->orWhere('last_name', 'like', '%'.$token.'%')->orWhere('email', 'like', '%'.$token.'%'));
            }
        })->orderBy('last_name')->orderBy('first_name')->paginate(15, ['*'], 'mother_page')->withQueryString() : null;
        if ($request->expectsJson()) {
            return response()->json([
                'program_staff' => $programStaff?->map(fn ($staff) => StaffActivity::summary($staff))->values(),
                'dswd_staff' => $dswdStaff?->map(fn ($staff) => StaffActivity::summary($staff))->values(),
                'mother' => $mothers?->map(fn ($mother) => StaffActivity::summary($mother))->values(),
            ])->header('Cache-Control', 'private, no-store');
        }
        return response()->view('admin.staff-activity', compact('programStaff', 'dswdStaff', 'mothers', 'motherSearch'))->header('Cache-Control', 'private, no-store');
    }
}
