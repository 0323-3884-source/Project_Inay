<?php

namespace App\Http\Controllers;

use App\Models\Mother;
use App\Models\ProgramStaff;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    private function account(Request $request): Mother|ProgramStaff|null
    {
        $role = $request->routeIs('mother.*') ? 'mother' : 'staff';
        if ($request->session()->get('auth_role') !== $role) {
            return null;
        }

        return $role === 'mother'
            ? Mother::find($request->session()->get('auth_id'))
            : ProgramStaff::find($request->session()->get('auth_id'));
    }

    public function show(Request $request): View|RedirectResponse
    {
        $account = $this->account($request);
        if (! $account) {
            return redirect()->route('login');
        }

        return view('profiles.show', ['account' => $account, 'isMother' => $account instanceof Mother]);
    }

    public function update(Request $request): RedirectResponse
    {
        $account = $this->account($request);
        if (! $account) {
            return redirect()->route('login');
        }

        $rules = [
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'contact_number' => ['required', 'string', 'max:25', 'regex:/^\+?[0-9 ()-]{7,25}$/', function ($attribute, $value, $fail) {
                if (strlen(preg_replace('/\D/', '', $value)) < 7) {
                    $fail('The contact number must contain at least 7 digits.');
                }
            }],
        ];
        if ($account instanceof Mother) {
            $rules['barangay'] = ['required', 'string', 'max:255'];
            if ($account->is_4ps_beneficiary) {
                $rules['four_ps_household_number'] = ['sometimes', 'required', 'string', 'max:32', 'regex:/^[0-9]+(?:-[0-9]+)*$/'];
            }
        }
        $account->update($request->validate($rules));
        $request->session()->put('auth_name', $account->full_name);

        return redirect()->route($account instanceof Mother ? 'mother.profile.show' : 'staff.profile.show')
            ->with('status', 'Your profile has been updated.');
    }

    public function settings(Request $request): View|RedirectResponse
    {
        $account = $this->account($request);
        if (! $account) {
            return redirect()->route('login');
        }

        return view('profiles.settings', ['account' => $account, 'isMother' => $account instanceof Mother]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $account = $this->account($request);
        if (! $account) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed', 'different:current_password'],
        ]);
        if (! Hash::check($data['current_password'], $account->password)) {
            throw ValidationException::withMessages(['current_password' => 'Your current password is incorrect.']);
        }

        $account->update(['password' => Hash::make($data['password'])]);
        $request->session()->regenerate();

        return redirect()->route($account instanceof Mother ? 'mother.settings' : 'staff.settings')
            ->with('status', 'Your password has been updated.');
    }
}
