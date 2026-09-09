<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $r): View
    {
        $users = User::when($r->q, fn ($q, $s) => $q->where(fn ($x) => $x->where('first_name', 'like', "%$s%")->orWhere('last_name', 'like', "%$s%")->orWhere('email', 'like', "%$s%")))->latest()->paginate(20);

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        return view('users.form', ['account' => new User]);
    }

    public function store(Request $r): RedirectResponse
    {
        User::create($this->data($r));

        return redirect()->route('users.index')->with('success', 'Account created.');
    }

    public function edit(User $user): View
    {
        return view('users.form', ['account' => $user]);
    }

    public function update(Request $r, User $user): RedirectResponse
    {
        $data = $this->data($r, $user);
        if (empty($data['password'])) {
            unset($data['password']);
        } $user->update($data);

        return redirect()->route('users.index')->with('success', 'Account updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->is(auth()->user()), 422, 'You cannot delete your own account.');
        $user->delete();

        return back()->with('success', 'Account deleted.');
    }

    private function data(Request $r, ?User $u = null): array
    {
        return $r->validate(['first_name' => 'required|max:100', 'last_name' => 'required|max:100', 'email' => ['required', 'email', Rule::unique('users')->ignore($u)], 'phone' => 'nullable|max:30', 'role' => 'required|in:admin,cashier', 'status' => 'required|in:active,inactive', 'password' => [$u ? 'nullable' : 'required', 'min:8', 'confirmed']]);
    }
}
