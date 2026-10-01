<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DswdStaff;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class DswdStaffController extends Controller
{
    private function ensureTableExists(): void
    {
        if (! Schema::hasTable('dswd_staff')) {
            try {
                Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    public function index()
    {
        $this->ensureTableExists();

        if (! Schema::hasTable('dswd_staff')) {
            $emptyPaginator = new LengthAwarePaginator([], 0, 15, 1, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);

            return view('admin.dswd-staff', [
                'accounts' => $emptyPaginator,
                'migrationPending' => true,
            ]);
        }

        return view('admin.dswd-staff', ['accounts' => DswdStaff::orderBy('name')->paginate(15)]);
    }

    public function store(Request $request)
    {
        $this->ensureTableExists();

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
