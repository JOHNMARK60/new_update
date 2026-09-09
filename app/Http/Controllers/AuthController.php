<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(Request $request): View
    {
        $selectedRole = $request->routeIs('admin.login') ? 'admin' : ($request->routeIs('cashier.login') ? 'cashier' : null);

        return view('auth.login', compact('selectedRole'));
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required'], 'role' => ['nullable', 'in:admin,cashier']]);
        $credentials['status'] = 'active';
        $remember = $request->boolean('remember');
        $role = $credentials['role'] ?? null;
        unset($credentials['role']);
        if (! Auth::attempt($credentials, $remember)) {
            return back()->withErrors(['email' => 'The credentials do not match our records.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        if ($role && $request->user()->role !== $role) {
            Auth::logout();

            return back()->withErrors(['email' => 'This account does not have the selected role.']);
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
