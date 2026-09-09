<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function requestLink(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => 'required|email']);
        $user = User::where('email', $data['email'])->first();
        if (! $user) {
            return back()->with('success', 'If that account exists, a reset link has been generated.');
        }
        $token = Str::random(64);
        $user->update(['reset_token' => hash('sha256', $token), 'token_expires_at' => now()->addHour()]);

        return back()->with('success', 'Testing reset link generated.')->with('reset_url', route('password.reset', $token));
    }

    public function resetForm(string $token): View
    {
        abort_unless($this->userFor($token), 404, 'This reset link is invalid or expired.');

        return view('auth.reset-password', compact('token'));
    }

    public function reset(Request $request, string $token): RedirectResponse
    {
        $data = $request->validate(['password' => 'required|min:8|confirmed']);
        $user = $this->userFor($token);
        abort_unless($user, 404, 'This reset link is invalid or expired.');
        $user->update(['password' => $data['password'], 'reset_token' => null, 'token_expires_at' => null]);

        return redirect()->route('login')->with('success', 'Password updated. You can now sign in.');
    }

    private function userFor(string $token): ?User
    {
        return User::where('reset_token', hash('sha256', $token))->where('token_expires_at', '>', now())->first();
    }
}
