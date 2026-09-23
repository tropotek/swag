<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TokenController extends Controller
{
    public function index(Request $request): View
    {
        return view('tokens.index', [
            'tokens' => $request->user()->tokens()->latest()->get(),
            'plainTextToken' => session('plainTextToken'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:255']]);

        $token = $request->user()->createToken($validated['name']);

        return redirect()->route('tokens.index')
            ->with('plainTextToken', $token->plainTextToken)
            ->with('status', "Token \"{$validated['name']}\" created. Copy it now: it won't be shown again.");
    }

    public function destroy(Request $request, string $token): RedirectResponse
    {
        $request->user()->tokens()->findOrFail($token)->delete();

        return redirect()->route('tokens.index')->with('status', 'Token revoked.');
    }
}
