<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', ['users' => User::orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', Password::defaults()],
        ]);

        User::forceCreate([
            ...$validated,
            'is_admin' => $request->boolean('is_admin'),
            'must_change_password' => true,
        ]);

        return redirect()->route('admin.users.index')
            ->with('status', "Created {$validated['email']}. Give them the temporary password; they must change it at first login.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        // Self-delete is the only way to lose the last admin, since there is no demote action.
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            $user->delete();
        });

        return redirect()->route('admin.users.index')->with('status', "Deleted {$user->email}.");
    }
}
