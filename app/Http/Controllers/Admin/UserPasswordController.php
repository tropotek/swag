<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserPasswordController extends Controller
{
    public function edit(User $user): View
    {
        return view('admin.users.password', ['user' => $user]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate(['password' => ['required', Password::defaults()]]);

        $user->forceFill([
            'password' => $validated['password'],
            'must_change_password' => true,
        ])->save();

        return redirect()->route('admin.users.index')->with('status', "Temporary password set for {$user->email}.");
    }
}
