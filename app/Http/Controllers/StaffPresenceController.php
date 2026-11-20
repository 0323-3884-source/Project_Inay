<?php

namespace App\Http\Controllers;

use App\Models\{DswdStaff, ProgramStaff};
use App\Support\{ChatPresence, StaffActivity};
use Illuminate\Http\Request;

class StaffPresenceController extends Controller
{
    public function update(Request $request)
    {
        $role = $request->routeIs('dswd.activity') ? 'dswd_staff' : 'staff';
        abort_unless($request->session()->get('auth_role') === $role, 403);
        $id = (int) $request->session()->get('auth_id');
        $eligible = $role === 'staff'
            ? ProgramStaff::whereKey($id)->where('approval_status', 'approved')->exists()
            : DswdStaff::whereKey($id)->where('is_active', true)->exists();
        abort_unless($eligible, 403);
        ChatPresence::recordPresence($role, $id);
        StaffActivity::record($role, $id);

        return response()->json(['active' => true])->header('Cache-Control', 'private, no-store');
    }
}
