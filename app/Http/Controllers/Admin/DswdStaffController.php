<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DswdStaff;
use Illuminate\Http\Request;

class DswdStaffController extends Controller
{
    public function index()
    {
        return view('admin.dswd-staff', ['accounts' => DswdStaff::orderBy('name')->paginate(15)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:dswd_staff,email'],
            'office' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);
        DswdStaff::create($data);
        return back()->with('status', 'DSWD / 4Ps Staff account created.');
    }

    public function update(Request $request, DswdStaff $dswdStaff)
    {
        $dswdStaff->update($request->validate(['is_active' => ['required', 'boolean']]));
        return back()->with('status', 'DSWD account access updated.');
    }
}
